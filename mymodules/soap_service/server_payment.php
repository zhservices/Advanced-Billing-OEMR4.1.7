<?php
require_once(dirname(__FILE__) . "/../../library/invoice_summary.inc.php");
require_once(dirname(__FILE__) . "/../../library/sl_eob.inc.php");
require_once(dirname(__FILE__) . "/../../library/parse_era.inc.php");
require_once(dirname(__FILE__) . "/../../library/acl.inc");
require_once(dirname(__FILE__) . "/../../library/sql.inc");
//require_once(dirname(__FILE__) . "/../../library/auth.inc");
require_once(dirname(__FILE__) . "/../../library/formdata.inc.php");
require_once(dirname(__FILE__) . "/../../custom/code_types.inc.php");
require_once(dirname(__FILE__) . "/../../library/patient.inc");
require_once(dirname(__FILE__) . "/../../library/billrep.inc");
require_once(dirname(__FILE__) . "/../../library/classes/OFX.class.php");
require_once(dirname(__FILE__) . "/../../library/classes/X12Partner.class.php");
require_once(dirname(__FILE__) . "/../../library/options.inc.php");
require_once(dirname(__FILE__) . "/../../library/formatting.inc.php");
//require_once(dirname(__FILE__) . "/../../library/payment.inc.php");
class Payment extends emrflowtrack
{
    public function payment_master($data)
    {
        if(UserService::valid($data[0])){
        global $payment_id,$screen,$DateFormat;
        $payment_id=$data[1];
        $screen=$data[2];
        $_POST=$data[3];
        $_REQUEST=$data[3];
        $payment_screen_folder = $data[4];
        $DateFormat=DateFormatRead();
        $checkCap=sqlQuery("SELECT cap_bill_facId,cap_from_date,cap_to_date,account_code FROM ar_session AS ars, ar_activity AS act
            WHERE ars.session_id=act.session_id and ars.session_id='$payment_id'");
        if($payment_id>0 && $checkCap['cap_bill_facId']>0){
            $rs= sqlStatement("select pay_total,global_amount from ar_session where session_id='$payment_id'");
            $row=sqlFetchArray($rs);
            $pay_total=$row['pay_total'];
            $global_amount=$row['global_amount'];
            $rs= sqlStatement("select sum(pay_amount) sum_pay_amount from ar_activity where session_id='$payment_id'");
            $row=sqlFetchArray($rs);
            $pay_amount=$row['sum_pay_amount'];
            $UndistributedAmount=$pay_total-$pay_amount-$global_amount;                
            $res = sqlStatement("SELECT check_date ,reference ,insurance_companies.name,
            payer_id,pay_total,payment_type,post_to_date,patient_id ,
            adjustment_code,description,deposit_date,payment_method
            FROM ar_session left join insurance_companies on ar_session.payer_id=insurance_companies.id 	where ar_session.session_id ='$payment_id'");
            $row = sqlFetchArray($res);
            $InsuranceCompanyName=$row['name'];
            $InsuranceCompanyId=$row['payer_id'];
            $PatientId=$row['patient_id'];
            $CheckNumber=$row['reference'];
            $CheckDate=$row['check_date']=='0000-00-00'?'':$row['check_date'];
            $PayTotal=$row['pay_total'];
            $PostToDate=$row['post_to_date']=='0000-00-00'?'':$row['post_to_date'];
            $PaymentMethod=$row['payment_method'];
            $PaymentType=$row['payment_type'];
            $AdjustmentCode=$row['adjustment_code'];
            $DepositDate=$row['deposit_date']=='0000-00-00'?'':$row['deposit_date'];
            $Description=$row['description'];
            $Capfromdate=$row['cap_from_date'] ? $row['cap_from_date'] : date("Y-m-d");
            $Captodate=$row['cap_to_date'] ? $row['cap_to_date'] : date("Y-m-d");
            $CapbillfacId=$row['cap_bill_facId'];
            if($row['payment_type']=='insurance' || $row['payer_id']*1 > 0)
            {
                $res = sqlStatement("SELECT insurance_companies.name FROM insurance_companies
                    where insurance_companies.id ='$InsuranceCompanyId'");
                $row = sqlFetchArray($res);
                $div_after_save=$row['name'];
                $TypeCode=$InsuranceCompanyId;
                if($PaymentType=='')
                {
                    $PaymentType='insurance';
                }
            }
            elseif($row['payment_type']=='patient' || $row['patient_id']*1 > 0)
            {
                $res = sqlStatement("SELECT fname,lname,mname FROM patient_data
                    where pid ='$PatientId'");
                $row = sqlFetchArray($res);
                $fname=$row['fname'];
                $lname=$row['lname'];
                $mname=$row['mname'];
                $div_after_save=$lname.' '.$fname.' '.$mname;
                $TypeCode=$PatientId;
                if($PaymentType=='')
                {
                    $PaymentType='patient';
                }
            }
            ?>
            <?php
            //================================================================================================
            if(($screen=='new_payment' && $payment_id*1==0) || ($screen=='edit_payment' && $payment_id*1>0))
            {//New entry or edit in edit screen comes here.
                ob_start();
                include_once(dirname(__FILE__)."/payment_screen/zh/payment_jav.inc.php");
                include_once(dirname(__FILE__)."/payment_screen/zh/payment_ajax_jav.inc.php");
                //include_once(dirname(__FILE__)."/payment_screen/zh/new_payment_jav.php");
            ?>
            <table width="958" border="0" cellspacing="0" cellpadding="10" bgcolor="#DEDEDE">
                <tr>
                    <td>
                        <table width="936" border="0" style="border:1px solid black" cellspacing="0" cellpadding="0">
                            <tr height="5">
                                <td colspan="14" align="left" ></td>
                            </tr>
                            <tr>
                                <td colspan="14" align="left">&nbsp;
                                    <font class='title'>
                                    <?php
                                    if($_REQUEST['ParentPage']=='new_payment')//This case comes when the Finish Payments is pressed from the New Payment screen.
                                    {
                                    ?>
                                        <?php echo htmlspecialchars( xl('Confirm Payment'), ENT_QUOTES) ?>
                                    <?php
                                    }
                                    elseif($screen=='new_payment')
                                    {
                                    ?>
                                        <?php echo htmlspecialchars( xl('Batch Payment Entry'), ENT_QUOTES) ?>
                                    <?php
                                    }
                                    else
                                    {
                                    ?>
                                        <?php echo htmlspecialchars( xl('Edit Payment'), ENT_QUOTES) ?>
                                    <?php
                                    }
                                    ?>
                                    </font>
                                </td>
                            </tr>
                            <tr height="20">
                                <td align="left" width="5" ></td>
                                <td align="left" width="110" ></td>
                                <td align="left" width="128"></td>
                                <td align="left" width="25"></td>
                                <td align="left" width="5"></td>
                                <td align="left" width="85"></td>
                                <td align="left" width="128"></td>
                                <td align="left" width="25"></td>
                                <td align="left" width="5"></td>
                                <td align="left" width="113"></td>
                                <td align="left" width="125"></td>
                                <td align="left" width="5"></td>
                                <td align="left" width="93"></td>
                                <td align="left" width="152"></td>
                            </tr>
                            <tr>
                                <td align="left" class='text'></td>
                                <td align="left" class='text'><?php echo htmlspecialchars( xl('Date'), ENT_QUOTES).':' ?></td>
                                <td align="left" class="text" ><input type='text' size='9' name='check_date' id='check_date' class="class1 text "  value="<?php echo htmlspecialchars(oeFormatShortDate($CheckDate));?>"/></td>
                                <td><img src='../../interface/main/calendar/modules/PostCalendar/pntemplates/default/images/new.jpg' align='absbottom'
                                    id='img_checkdate' border='0' alt='[?]' style='cursor:pointer'
                                    title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>' />
                                    <script>
                                        Calendar.setup({inputField:"check_date", ifFormat:"<?php echo $DateFormat; ?>", button:"img_checkdate"});
                                    </script>
                                </td>
                                <td></td>
                                <td align="left" class='text'><?php echo htmlspecialchars( xl('Post To Date'), ENT_QUOTES).':' ?></td>
                                <td align="left" class="text"><input type='text' size='9' name='post_to_date' id='post_to_date' class="class1 text "   value="<?php echo $screen=='new_payment'?htmlspecialchars(oeFormatShortDate(date('Y-m-d'))):htmlspecialchars(oeFormatShortDate($PostToDate));?>"  readonly="" /></td>
                                <td><img src='../../interface/main/calendar/modules/PostCalendar/pntemplates/default/images/new.jpg' align='absbottom'
                                    id='img_post_to_date' border='0' alt='[?]' style='cursor:pointer'
                                    title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>' />
                                    <script>
                                        Calendar.setup({inputField:"post_to_date", ifFormat:"<?php echo $DateFormat; ?>", button:"img_post_to_date"});
                                    </script>
                                </td>
                                <td></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Payment Method'), ENT_QUOTES).':' ?></td>
                                <td align="left">
                                    <?php	
                                        if($PaymentMethod=='' && $screen=='edit_payment') 
                                            $blankValue=' '; 
                                        else 
                                            $blankValue='';
                                    echo generate_select_list("payment_method", "payment_method", "$PaymentMethod", "Payment Method","$blankValue","class1 text",'CheckVisible("yes")');
                                    ?>
                                </td>
                                <td></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Check Number'), ENT_QUOTES).':' ?></td>
                                <td>
                                    <?php
                                    if($PaymentMethod=='check_payment' || $PaymentMethod=='bank_draft' || $CheckNumber!='' || $screen=='new_payment')
                                    {
                                        $CheckDisplay='';
                                        $CheckDivDisplay=' display:none; ';
                                    }
                                    else
                                    {
                                        $CheckDisplay=' display:none; ';
                                        $CheckDivDisplay='';
                                    }
                                    ?>
                                    <input type="text" name="check_number"  style="width:140px;<?php echo $CheckDisplay;?>"  autocomplete="off"  value="<?php echo htmlspecialchars($CheckNumber);?>"  onKeyUp="ConvertToUpperCase(this)"  id="check_number"  class="text "   />
                                    <div  id="div_check_number" class="text"  style="border:1px solid black; width:140px;<?php echo $CheckDivDisplay;?>">&nbsp;</div>
                                </td>
                            </tr>
                            <tr height="1">
                                <td colspan="14" align="left" ></td>
                            </tr>
                            <tr>
                                <td align="left" class="text"></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Payment Amount'), ENT_QUOTES).':' ?></td>
                                <td align="left"><input   type="text" name="payment_amount"   autocomplete="off"  id="payment_amount"  onchange="ValidateNumeric(this);<?php echo $screen=='new_payment'?'FillUnappliedAmount();':'FillAmount();';?>"  value="<?php echo $screen=='new_payment'?htmlspecialchars('0.00'):htmlspecialchars($PayTotal);?>"  style="text-align:right"    class="class1 text "   /></td>
                                <td align="left" ></td>
                                <td align="left" ></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Paying Entity'), ENT_QUOTES).':' ?></td>
                                <td align="left">
                                    <?php
                                    if($PaymentType=='' && $screen=='edit_payment') 
                                        $blankValue=' '; 
                                    else 
                                        $blankValue='';
                                    echo generate_select_list("type_name", "payment_type", "$PaymentType", "Paying Entity","$blankValue","class1 text",'PayingEntityAction()');
                                    ?>
                                </td>
                                <td align="left" ></td>
                                <td align="left" ></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Payment Category'), ENT_QUOTES).':' ?></td>
                                <td align="left" class="text"><?php
                                    if($AdjustmentCode=='' && $screen=='edit_payment') 
                                        $blankValue=' '; 
                                    else 
                                        $blankValue='';
                                    echo "Capitation Payment";
                                    //echo $this->get_generate_list_payment_category(array($cred,"adjustment_code", "payment_adjustment_code", "cap_payment", 
                                    //"Payment Category","$blankValue","class1 text",'FilterSelection(this)',"$PaymentType","$screen"));
                                    $dispcapdatetd ='style="display:none"';
                                    $disppaymentpat = '';
                                    if($AdjustmentCode=='cap_payment'){
                                        $dispcapdatetd = '';
                                        $disppaymentpat = 'style="display:none"';
                                    }
                                    ?>
                                </td>
                                <td align="left" ></td>
                                <td align="left" ></td>
                                <td align="left" ></td>
                            </tr>
                            <tr height="1">
                                <td colspan="14" align="left" ></td>
                            </tr>
                            <tr class="text" id="capdatetd" <?php echo $dispcapdatetd;?>>
                                <td align="left" class="text"></td>
                                <td align="left" class="text" colspan="4"></td>
                                <td align="left" class="text"><?php echo htmlspecialchars(xl('Billing Entity'),ENT_QUOTES).':';?></td>
                                <td align="left" class="text" colspan="3"><?php echo billing_facility('billing_facility',$CapbillfacId,'text');?></td>
                                <td align="left" class="text"><?php echo htmlspecialchars(xl('For The Period'),ENT_QUOTES).':';?></td>
                                <td align="left" class="text">
                                    <?php
                                        echo generate_select_list("date_master_criteria","date_master_criteria","custom", 
                                        "Date Criteria","","class1 text",'calendar_function(this.value,"cap_from_date","cap_to_date")');
                                    ?>
                                <td align="left" colspan="4" class="text">
                                    <?php echo htmlspecialchars(xl('From'),ENT_QUOTES);?><input type="text" name="cap_from_date" id="cap_from_date" size="8" readonly value="<?php echo $Capfromdate;?>">
                                    <img src='../../interface/main/calendar/modules/PostCalendar/pntemplates/default/images/new.jpg' align='absbottom'
                                    id='img_cap_from_date' border='0' alt='[?]' style='cursor:pointer'
                                    title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>' />
                                    <?php echo htmlspecialchars(xl('To'),ENT_QUOTES);?><input type="text" name="cap_to_date" id="cap_to_date"  size="8" readonly value="<?php echo $Captodate;?>">
                                    <img src='../../interface/main/calendar/modules/PostCalendar/pntemplates/default/images/new.jpg' align='absbottom'
                                    id='img_cap_to_date' border='0' alt='[?]' style='cursor:pointer'
                                    title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>' />
                                    <script>
                                        Calendar.setup({inputField:"cap_from_date", ifFormat:"%Y-%m-%d", button:"img_cap_from_date"});
                                        Calendar.setup({inputField:"cap_to_date", ifFormat:"%Y-%m-%d", button:"img_cap_to_date"});
                                    </script>
                                </td>
                            </tr>
                            <tr height="1">
                                <td colspan="14" align="left" ></td>
                            </tr>
                            <tr>
                                <td align="left" class="text"></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Payment From'), ENT_QUOTES).':' ?></td>
                                <td align="left" colspan="5"><input type="hidden" id="hidden_ajax_close_value" value="<?php echo htmlspecialchars($div_after_save);?>" /><input name='type_code'  id='type_code' class="text "  style="width:369px"   onKeyDown="PreventIt(event)"  value="<?php echo htmlspecialchars($div_after_save);?>"  autocomplete="off"   /><br>
                                <!-- onKeyUp="ajaxFunction(event,'non','edit_payment.php');" -->
                                    <div id='ajax_div_insurance_section'>
                                        <div id='ajax_div_insurance_error'>
                                        </div>
                                        <div id="ajax_div_insurance" style="display:none;"></div>
                                        </div>
                                    </div>
                                </td>
                                <td align="left" colspan="5"><div  name="div_insurance_or_patient" id="div_insurance_or_patient" class="text"  style="border:1px solid black; padding-left:5px; width:55px; height:17px;"><?php echo htmlspecialchars($TypeCode);?></div></td>
                                <td align="left" ></td>
                                <td align="left" ></td>
                            </tr>
                            <tr>
                                <td align="left" class='text'></td>
                                <td align="left" class='text'><?php echo htmlspecialchars( xl('Deposit Date'), ENT_QUOTES).':' ?></td>
                                <td align="left"><input type='text' size='9' name='deposit_date' id='deposit_date'  onKeyDown="PreventIt(event)"   class="class1 text " value="<?php echo htmlspecialchars(oeFormatShortDate($DepositDate));?>"    />	   </td>
                                <td><img src='../../interface/main/calendar/modules/PostCalendar/pntemplates/default/images/new.jpg' align='absbottom'
                                    id='img_depositdate' border='0' alt='[?]' style='cursor:pointer'
                                    title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>' />
                                    <script>
                                        Calendar.setup({inputField:"deposit_date", ifFormat:"<?php echo $DateFormat; ?>", button:"img_depositdate"});
                                    </script>
                                </td>
                                <td></td>
                                <td align="left" class="text"><?php echo htmlspecialchars( xl('Description'), ENT_QUOTES).':' ?></td>
                                <td colspan="6" align="left"><input type="text" name="description"  id="description"   onKeyDown="PreventIt(event)"   value="<?php echo htmlspecialchars($Description);?>"   style="width:396px" class="text "   /></td>
                                <td align="left" class="text"><font  style="font-size:11px"><?php echo htmlspecialchars( xl('UNDISTRIBUTED'), ENT_QUOTES).':' ?></font><input name="HidUnappliedAmount" id="HidUnappliedAmount"  value="<?php echo ($UndistributedAmount*1==0)? htmlspecialchars("0.00") : htmlspecialchars(number_format($UndistributedAmount,2,'.',','));?>" type="hidden"/><input name="HidUnpostedAmount" id="HidUnpostedAmount"  value="<?php echo htmlspecialchars($UndistributedAmount); ?>" type="hidden"/><input name="HidCurrentPostedAmount" id="HidCurrentPostedAmount"  value="" type="hidden"/></td>
                                <td align="left" class="text"><div  id="TdUnappliedAmount" class="text"  style="border:1px solid black; width:75px; background-color:#EC7676; padding-left:5px;"><?php echo ($UndistributedAmount*1==0)? htmlspecialchars("0.00") : htmlspecialchars(number_format($UndistributedAmount,2,'.',','));?></div></td>
                            </tr>
                            <tr height="5">
                                <td colspan="14" align="left" ></td>
                            </tr>
                        </table>
                    </td> 
                </tr>
            </table>
            <?php
            $v=ob_get_clean(); 
            }//if(($screen=='new_payment' && $payment_id*1==0) || ($screen=='edit_payment' && $payment_id*1>0))
            //================================================================================================
            return $v;
        }
        else{
            ob_start();
            include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/payment_jav.inc.php");
            include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/payment_ajax_jav.inc.php");
            //include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/new_payment_jav.php");
            require_once(dirname(__FILE__) . "/payment_screen/$payment_screen_folder/payment_master.inc.php");
            $v=ob_get_clean();        
            return $v;
        }
        }
    }
    public function payment_pat($data)
    {
        if(UserService::valid($data[0])){
        global $payment_id,$screen,$ResultSearchNew,$CountIndexBelow,$CountIndexAbove,$CountIndex;
        $payment_id=$data[1];
        $screen=$data[2];
        $_POST=$data[3];
        $_REQUEST=$data[3];
        $CountIndexAbove=$data[4];
        $payment_screen_folder =$data[5];
        if(!$CountIndexAbove)
            $CountIndexAbove=0;
        $CountIndex=$CountIndexAbove;
        ob_start();
        include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/payment_jav.inc.php");
        include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/payment_ajax_jav.inc.php");
        //include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/new_payment_jav.php");
        require_once(dirname(__FILE__) . "/payment_screen/$payment_screen_folder/payment_pat_sel.inc.php");
        $v=ob_get_clean(); 
        return array($v,$CountIndexBelow);
        }
    }
    public function edit_payment($data)
    {
        if(UserService::valid($data[0])){
            $payment_screen_folder=$data[1];
            $_POST=$data[2];
            $_REQUEST=$data[2];            
            ob_start();
            include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/edit_payment.php");
            $v=ob_get_clean();
            return $v;
        }
    }
    public function new_payment($data)
    {
        if(UserService::valid($data[0])){
            $payment_screen_folder=$data[1];
            $_POST=$data[2];
            $_REQUEST=$data[2];            
            ob_start();
            include_once(dirname(__FILE__)."/payment_screen/$payment_screen_folder/new_payment.php");
            $v=ob_get_clean();
            return $v;
        }
    }
    public function get_DateFormatRead()
    {
        return DateFormatRead();
    }
    public function get_DateToYYYYMMDD($data)
    {
        if(UserService::valid($data[0])){
        return DateToYYYYMMDD($data[1]);
        }
    }
    public function get_QueueToNextLevel($data)
    {
        if(UserService::valid($data[0])){
        require_once(dirname(__FILE__) . "/../../library/payment.inc.php");
        global $EncounterRowArray,$InsRowArray,$AffectedRowArray;
        $EncounterRowArray=$data[1];
        $InsRowArray=$data[2];
        $AffectedRowArray=$data[3];
        $_REQUEST=$data[4];
        return QueueToNextLevel();
        }
    }
    public function get_idSqlStatement($data)
    {
        if(UserService::valid($data[0])){
        return idSqlStatement($data[1],$data[2]);
        }
    }
    public function get_DistributionInsert($data)
    {
        if(UserService::valid($data[0])){
        require_once(dirname(__FILE__) . "/../../library/payment.inc.php");
        $_POST=$data[4];
        $_REQUEST=$data[4];
        $CountRow=$data[1];
        $Affected=DistributionInsert($data[1],$data[2],$_SESSION['authUserID']);
        $main = 'BILLING';
        if(trim($_REQUEST["HiddenIns$CountRow"])==1)
            $sub = 'ENCT_PAIDPRI';
        elseif(trim($_REQUEST["HiddenIns$CountRow"])==2)        
            $sub = 'ENCT_PAIDSEC';
        elseif(trim($_REQUEST["HiddenIns$CountRow"])==3)
            $sub = 'ENCT_PAIDTER';
        $this->update_status(array($data[0],$main,$sub,trim($_REQUEST['hidden_patient_code']),trim($_REQUEST["HiddenEncounter$CountRow"]),'BillingPortal:Manual Payment Distribution(function:DistributionInsert)'));
        $this->update_zero_balance(array($data[0],trim($_REQUEST['hidden_patient_code']),trim($_REQUEST["HiddenEncounter$CountRow"])));
        //$this->get_QueueToNextLevel(array($data[0],array('1' => $_POST["HiddenEncounter$CountRow"]),array('1' => $_POST["HiddenIns$CountRow"]),array('1' => $Affected),$_REQUEST));
        return $Affected;
        }
    }
    public function get_oeFormatShortDate($data)
    {
        if(UserService::valid($data[0])){
        return oeFormatShortDate($data[1]);
        }
    }
    public function get_parse_era($data)
    {
        list($cred, $tmp_name, $era_st)=$data;
        if(UserService::valid($cred)){
        $era_st='era_callback_1';
        global $where, $eracount, $eraname, $INTEGRATED_AR,$out;
        $eraname='';
        if(!function_exists("era_callback_1") && $era_st=='era_callback_1'){
        function era_callback_1(&$out) {
            global $where, $eracount, $eraname, $INTEGRATED_AR;
            ++$eracount;
            $eraname = $out['gs_date'] . '_' . ltrim($out['isa_control_number'], '0') . '_' . ltrim($out['payer_id'], '0');
            list($pid, $encounter, $invnumber) = slInvoiceNumber($out);
            if ($pid && $encounter) {
                if ($where) $where .= ' OR ';
                if ($INTEGRATED_AR) {
                    $where .= "( f.pid = '$pid' AND f.encounter = '$encounter' )";
                }
                else {
                    $where .= "invnumber = '$invnumber'";
                }
            }
        }
        }        
		$tmp_name = dirname(__FILE__)."/".$tmp_name;
        $t=parse_era($tmp_name, $era_st);
        return array($eraname,$t);
        }
    }
    public function write_era($data)
    {
        list($cred, $site_folder, $era_name, $era_content)=$data;
        if(UserService::valid($cred)){
        $f=fopen(dirname(__FILE__) . "/../../sites/$site_folder/edi/$era_name.edi",'w');
        fwrite($f,$era_content);
        fclose($f);
        }
    }
    public function update_unapplied_amount($data)
    {
        if(UserService::valid($data[0])){
        sqlStatement("update ar_session set global_amount=".trim($data[1])*1 ." where session_id ='".$data[2]."'");
        return 1;
        }
    }
    public function get_billing_facility($data)
    {
        list($cred,$name,$select,$class,$all)=$data;
        if(UserService::valid($cred)){
        ob_start();
        billing_facility($name,$select,$class,$all);
        $v=ob_get_clean();
        return $v;
        }
    }
    public function insert_query($data)
    {
        list($cred,$q,$dat)=$data;
        if(UserService::valid($cred)){
        switch($q)
        {
            case 'P1':
                list($QueryPart,$user_id,$closed,$check_number,$check_date,$deposit_date,$payment_amount,$modified_time,$type_name,$description,$adjustment_code,$post_to_date,$payment_method,$cap_from_date,$cap_to_date,$billing_facility)=$dat;
                $query="insert into ar_session set "    .
                $QueryPart .
                "', user_id = '"     . $user_id  .
                "', closed = '"      . $closed  .
                "', reference = '"   . $check_number .
                "', check_date = '"  . $check_date .
                "', deposit_date = '" . $deposit_date  .
                "', pay_total = '"    . $payment_amount .
                "', modified_time = '" . $modified_time .
                "', payment_type = '"   . $type_name .
                "', description = '"   . $description .
                "', adjustment_code = '"   . $adjustment_code .
                "', post_to_date = '" . $post_to_date  .
                "', payment_method = '"   . $payment_method .
                "', cap_from_date = '" . $cap_from_date  .
                "', cap_to_date = '" . $cap_to_date  .
                "', cap_bill_facId = '" . $billing_facility  .
                "'";
                return idSqlStatement($query);
                break;
            case 'P2':
                list($user_id,$cap_id,$payment_amount)=$dat;
                $query="INSERT INTO ar_activity SET pid = 0, encounter = 0, payer_type = 1, post_time=now(), post_user='".$user_id."', ".
                " session_id = '".$cap_id."',pay_amount ='".trim($payment_amount)."' ,modified_time=now(),account_code='CAPPMNT'";
                sqlQuery($query);
                break;
        }
        return 1;
        }
    }
    public function get_generate_list_payment_category($data)
    {
        list($cred, $tag_name, $list_id, $currvalue, $title, $empty_name, $class, $onchange, $PaymentType , $screen )=$data;
        if(UserService::valid($cred)){
        ob_start();
        require_once(dirname(__FILE__) . "/payment_master.inc.php");
        $v=ob_get_clean();
        return generate_list_payment_category($tag_name, $list_id, $currvalue, $title,$empty_name=' ', $class='', $onchange='',$PaymentType='insurance',$screen='new_payment');
        }
    }
    public function get_generate_select_list($data)
    {
        list($cred, $tag_name, $list_id, $currvalue, $title, $empty_name, $class, $onchange, $tag_id , $custom_attributes )=$data;
        if(UserService::valid($cred)){
        return generate_select_list($tag_name, $list_id, $currvalue, $title, $empty_name, $class, $onchange, $tag_id , $custom_attributes );
        }
    }
    public function get_generate_print_field($data)
    {
        list($cred, $frow, $currvalue)=$data;
        if(UserService::valid($cred)){
        return generate_print_field($frow, $currvalue);
        }
    }
    public function get_row_delete($data)
    {
        list($cred, $table, $c, $dat)=$data;
        if(UserService::valid($cred)){
        require_once(dirname(__FILE__) . "/../../library/payment.inc.php");
        $where='';
        switch($c)
        {
            case 'D1':
                $where="session_id ='".$dat[0]."' and  pid ='".$dat[1]."' AND " .
                "encounter='".$dat[2]."' and  code='".$dat[3]."' and modifier='".$dat[4]."'";
                break;
        }
        row_delete($table, $where);
        return 1;}
    }
    public function get_arGetPayerID($data)
    {
        list($cred, $patient_code, $date_of_service, $new_payer_type)=$data;
        if(UserService::valid($cred)){
        return arGetPayerID($patient_code, $date_of_service, $new_payer_type);
        }
    }
    public function get_arSetupSecondary($data)
    {
        list($cred, $a, $b, $c)=$data;
        if(UserService::valid($cred)){
        return arSetupSecondary($a, $b, $c);
        }
    }
    public function get_updateClaim($data)
    {
        list($cred, $newversion, $patient_id, $encounter_id, $payer_id, $payer_type, $status, $bill_process, $process_file)=$data;
        if(UserService::valid($cred)){
        $r=updateClaim($newversion, $patient_id, $encounter_id, $payer_id, $payer_type, $status, $bill_process, $process_file);
        if($status==7)
        {
            $main = 'BILLING';
            if($payer_type==1)
                $sub = 'ENCT_REJECTPRI';
            elseif($payer_type==2)
                $sub = 'ENCT_REJECTSEC';
            elseif($payer_type==3)
                $sub = 'ENCT_REJECTTER';
            $this->update_status(array($cred,$main,$sub,$patient_id,$encounter_id,'Update Claim Denial Status (Status:7)(function:get_updateClaim)'));
        }
        if ($status == 2)
        {
            //global $connectionFlow;
            $main = 'BILLING';
            if($payer_type==1)
            {
                $sub = 'ENCT_BILLEDPRI';
            }
            elseif($payer_type==2)
            {
                $sub = 'ENCT_BILLEDSEC';
            }
            elseif($payer_type==3)
            {
                $sub = 'ENCT_BILLEDTER';
            }
            $this->update_status(array($cred,$main,$sub,$patient_id,$encounter_id,'Updation if marked as cleared/Generate X12 file (function:get_updateClaim)'));
            $this->update_arr_master_insurance(array($cred,$patient_id,$encounter_id,$ins1,$ins2,$ins3,$payer_type));
        }
        return $r;
        }
    }
    public function get_sl_eob_process($data)
    {        
        global $site_folder,$cred;
        list($cred, $site_folder, $dat)=$data;
        if(UserService::valid($cred)){
            $t='';
            if(count($dat['file'])>0){
                $return_chk_number=array();
                while($ros=array_shift($dat['file'])){
                    $_REQUEST=$dat;
                    unset($_REQUEST['file']);
                    $path=dirname(__FILE__) . '/../../sites/'.$ros['site'].'/edi/master';
                    $to_path=dirname(__FILE__) . '/../../sites/'.$ros['site'].'/edi';
                    $value = $ros['file_name'];                    
                    $eradetails=array();
                    $eradetails=preg_split('/~/',$value);
                    $file=$path."/".$eradetails[1];
                    $chk = $eradetails[2];
                    
                    $return_details = array();
					$file_temp = "../../sites/".$ros['site']."/edi/master/".$eradetails[1];
                    $return_details = $this->get_parse_era(array($cred, $file_temp, 'era_callback'));
                    if($return_details[0]){
                        $file_source=$path."/".$eradetails[1];
                        $erafullname_dest = "$to_path/".$return_details[0].".edi";
                        $this->copy_file(array($cred,$file_source,$erafullname_dest));
                        
                        $_REQUEST["insname$chk"]=$ros["insname$chk"];
                        $indid=sqlQuery("select id from insurance_companies where name like '".$_REQUEST["insname$chk"]."'");
                        if($indid['id']){
                            $_REQUEST['InsId']=$indid['id'];
                        }
                        else{
                            $get_ins_id=sqlQuery("select max(id) as id from insurance_companies");
                            $_REQUEST['InsId']=$get_ins_id['id']+1;
                            sqlQuery("insert into insurance_companies (id,name,freeb_type,x12_receiver_id,x12_default_partner_id,is_group) values ('".($get_ins_id['id']+1)."','".$_REQUEST["insname$chk"]."','1','23714','23714','1')");
                        }
                        $_REQUEST['eraname']=$return_details[0];
                        $_REQUEST['CheckSubmit']=$ros['CheckSubmit'];
                        $_REQUEST['chk_number']=$chk;
                        $_REQUEST['debug']=$ros['debug'];
                        $_REQUEST['paydate']=$ros['paydate'];
                        $_REQUEST['post_to_date']=$ros['post_to_date'];
                        $_REQUEST['deposit_date']=$ros['deposit_date'];                    
                        $_REQUEST['return_value']=$ros['return_value'];
                        $_REQUEST["chk$chk"]=$ros["chk$chk"];
                        $_REQUEST["InsId$chk"]=$_REQUEST['InsId'];                    
                        
                        $_GET=$_REQUEST;
                        ob_start();
                        require(dirname(__FILE__) . '/sl_eob_process.php');
                        $t.=ob_get_clean();
                        $check_updation_of_check=sqlQuery("select reference from ar_session where reference like '%".$chk."%'");
                        if($check_updation_of_check['reference'])
                            $return_chk_number[]=$chk;
                    }
                    unset($_REQUEST);
                    unset($return_details);
                    unset($eradetails);
                }
                return $return_chk_number;            
            }
            else
            {
                $_REQUEST=$dat;
                $_GET=$dat;
                ob_start();
                require(dirname(__FILE__) . '/sl_eob_process.php');
                $t=ob_get_clean();                
            }
        return $t;
        }
    }
    public function update_ar_session($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {
            case 'Q1':
                list($QueryPart,$user_id,$closed,$check_number,$check_date,$deposit_date,$payment_amount,$modified_time,$type_name,$description,$adjustment_code,$post_to_date,$payment_method,$cap_from_date,$cap_to_date,$billing_facility,$payment_id)=$dat;
                sqlStatement("update ar_session set "    .
                            $QueryPart .
                            "', user_id = '"     . trim($user_id)  .
                            "', closed = '"      . trim($closed)  .
                            "', reference = '"   . trim($check_number) .
                            "', check_date = '"  . trim($check_date) .
                            "', deposit_date = '" . trim($deposit_date)  .
                            "', pay_total = '"    . trim($payment_amount) .
                            "', modified_time = '" . trim($modified_time)  .
                            "', payment_type = '"   . trim($type_name) .
                            "', description = '"   . trim($description) .
                            "', adjustment_code = '"   . trim($adjustment_code) .
                            "', post_to_date = '" . trim($post_to_date)  .
                            "', payment_method = '"   . trim($payment_method) .
                            "', cap_from_date = '" . trim($cap_from_date)  .
                            "', cap_to_date = '" . trim($cap_to_date)  .
                            "', cap_bill_facId = '" . trim($billing_facility)  .
                            "'	where session_id='$payment_id'");
                break;
            case 'Q2':
                list($QueryPart,$user_id,$closed,$check_number,$check_date,$deposit_date,$payment_amount,$modified_time,$type_name,$description,$adjustment_code,$post_to_date,$payment_method,$payment_id)=$dat;
                sqlStatement("update ar_session set "    .
                            $QueryPart .
                            "', user_id = '"     . trim($user_id)  .
                            "', closed = '"      . trim($closed)  .
                            "', reference = '"   . trim($check_number) .
                            "', check_date = '"  . trim($check_date) .
                            "', deposit_date = '" . trim($deposit_date)  .
                            "', pay_total = '"    . trim($payment_amount) .
                            "', modified_time = '" . trim($modified_time)  .
                            "', payment_type = '"   . trim($type_name) .
                            "', description = '"   . trim($description) .
                            "', adjustment_code = '"   . trim($adjustment_code) .
                            "', post_to_date = '" . trim($post_to_date)  .
                            "', payment_method = '"   . trim($payment_method) .
                            "'	where session_id='$payment_id'");
                break;
            case 'Q3':
                list($global_amount,$payment_id)=$dat;
                sqlStatement("update ar_session set global_amount=".trim($global_amount)*1 ." where session_id ='$payment_id'");
                break;
        }
        return 1;
        }
    }
    public function update_ar_activity($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {
            case 'P1':
                list($user_id,$payment_amount,$payment_id)=$dat;
                sqlStatement("update ar_activity SET pid = 0, encounter = 0, payer_type = 1, post_time=now(), post_user='".$user_id."', ".
                            " pay_amount ='".trim($payment_amount)."' ,modified_time=now(),account_code='CAPPMNT' ".
                            " where session_id='$payment_id'");
                break;
            case 'P3':
                list($user_id,$created_time,$pay_amount,$AccountCode,$payer_type,$payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("update ar_activity set "    .
                            "   post_user = '" . trim($user_id)  .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . trim($pay_amount)  .
                            "', account_code = '" . "$AccountCode"  .
                            "', payer_type = '"   . trim($payer_type) .
                            "' where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and pay_amount>0");
                break;
            case 'P4':
                list($user_id,$created_time,$adj_amount,$AdjustString,$AccountCode,$payer_type,$payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("update ar_activity set "    .
                            "   post_user = '" . trim($user_id)  .
                            "', modified_time = '"  . trim($created_time) .
                            "', adj_amount = '"    . trim($adj_amount) .
                            "', memo = '" . "$AdjustString"  .
                            "', account_code = '" . "$AccountCode"  .
                            "', payer_type = '"   . trim($payer_type) .
                            "' where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and adj_amount!=0");
                break;
            case 'P5':
                list($user_id,$created_time,$memo,$payer_type,$payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("update ar_activity set "    .
                            "   post_user = '" . trim($user_id)  .
                            "', modified_time = '"  . trim($created_time) .
                            "', memo = '"    . "Deductable $".trim($memo) .
                            "', account_code = '" . "Deduct"  .
                            "', payer_type = '"   . trim($payer_type) .
                            "' where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and memo like 'Deductable%'");
                break;
            case 'P6':
                list($user_id,$created_time,$pay_amount,$payer_type,$payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("update ar_activity set "    .
                            "   post_user = '" . trim($user_id)  .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . trim($pay_amount)*-1  .
                            "', account_code = '" . "Takeback"  .
                            "', payer_type = '"   . trim($payer_type) .
                            "' where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and pay_amount < 0");
                break;
            case 'P7':
                list($user_id,$created_time,$drop_down_reason,$reason,$payer_type,$payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("update ar_activity set "    .
                            "   post_user = '" . trim($user_id)  .
                            "', modified_time = '"  . trim($created_time) .
                            "', follow_up = '"    . "$drop_down_reason" .
                            "', follow_up_note = '"    . $reason .
                            "', payer_type = '"   . trim($payer_type) .
                            "' where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and follow_up !=''");
                break;
                
        }
        return 1;
        }
    }
    public function update_billing($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {            
            case 'B1':
                list($pid,$encounter)=$dat;
                sqlStatement("update billing set bill_process=2 where  pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and bill_process ='7'");
                break;
            case 'B2':
                list($pid,$encounter)=$dat;
                sqlStatement("update billing set bill_process=2 where  pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and bill_process ='7'");
                break;
        }
        return 1;
        }
    }
    public function update_claims($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {
            case 'C1':
                list($code_value,$patient_id,$encounter_id)=$dat;
                sqlStatement("update claims set "    .
                            "process_file = '"       . $code_value .
                            "' where	patient_id = '"       . trim($patient_id) .
                            "' and encounter_id = '"     . trim($encounter_id)  .
                            "' and status = '7'");
                break;            
        }
        return 1;
        }
    }
    public function insert_ar_activity($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {
            case 'I1':
                list($pid,$encounter,$code,$modifier,$payer_type,$created_time,$user_id,$session_id,$created_time,$pay_amount,$AccountCode)=$dat;
                sqlStatement("insert into ar_activity set "    .
                            "pid = '"       . trim($pid) .
                            "', encounter = '"     . trim($encounter)  .
                            "', code = '"      . trim($code)  .
                            "', modifier = '"      . trim($modifier)  .
                            "', payer_type = '"   . trim($payer_type) .
                            "', post_time = '"  . trim($created_time) .
                            "', post_user = '" . trim($user_id)  .
                            "', session_id = '"    . trim($session_id) .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . trim($pay_amount)  .
                            "', adj_amount = '"    . 0 .
                            "', account_code = '" . "$AccountCode"  .
                            "'");
                break;
            case 'I2':
                list($pid,$encounter,$code,$modifier,$payer_type,$created_time,$user_id,$session_id,$created_time,$adj_amount,$AdjustString,$AccountCode)=$dat;
                sqlStatement("insert into ar_activity set "    .
                            "pid = '"       . trim($pid) .
                            "', encounter = '"     . trim($encounter)  .
                            "', code = '"      . trim($code)  .
                            "', modifier = '"      . trim($modifier)  .
                            "', payer_type = '"   . trim($payer_type) .
                            "', post_time = '"  . trim($created_time) .
                            "', post_user = '" . trim($user_id)  .
                            "', session_id = '"    . trim($session_id) .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . 0  .
                            "', adj_amount = '"    . trim($adj_amount) .
                            "', memo = '" . "$AdjustString"  .
                            "', account_code = '" . "$AccountCode"  .
                            "'");
                break;
            case 'I3':
                list($pid,$encounter,$code,$modifier,$payer_type,$created_time,$user_id,$session_id,$created_time,$memo)=$dat;
                sqlStatement("insert into ar_activity set "    .
                            "pid = '"       . trim($pid) .
                            "', encounter = '"     . trim($encounter)  .
                            "', code = '"      . trim($code)  .
                            "', modifier = '"      . trim($modifier)  .
                            "', payer_type = '"   . trim($payer_type) .
                            "', post_time = '"  . trim($created_time) .
                            "', post_user = '" . trim($user_id)  .
                            "', session_id = '"    . trim($session_id) .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . 0  .
                            "', adj_amount = '"    . 0 .
                            "', memo = '"    . "Deductable $".trim($memo) .
                            "', account_code = '" . "Deduct"  .
                            "'");
                break;
            case 'I4':
                list($pid,$encounter,$code,$modifier,$payer_type,$created_time,$user_id,$session_id,$created_time,$pay_amount)=$dat;
                sqlStatement("insert into ar_activity set "    .
                            "pid = '"       . trim($pid) .
                            "', encounter = '"     . trim($encounter)  .
                            "', code = '"      . trim($code)  .
                            "', modifier = '"      . trim($modifier)  .
                            "', payer_type = '"   . trim($payer_type) .
                            "', post_time = '"  . trim($created_time) .
                            "', post_user = '" . trim($user_id)  .
                            "', session_id = '"    . trim($session_id) .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . trim($pay_amount)*-1  .
                            "', adj_amount = '"    . 0 .
                            "', account_code = '" . "Takeback"  .
                            "'");
                break;
            case 'I5':
                list($pid,$encounter,$code,$modifier,$payer_type,$created_time,$user_id,$session_id,$created_time,$drop_down_reason,$reason)=$dat;
                sqlStatement("insert into ar_activity set "    .
                            "pid = '"       . trim($pid ) .
                            "', encounter = '"     . trim($encounter)  .
                            "', code = '"      . trim($code)  .
                            "', modifier = '"      . trim($modifier)  .
                            "', payer_type = '"   . trim($payer_type) .
                            "', post_time = '"  . trim($created_time) .
                            "', post_user = '" . trim($user_id)  .
                            "', session_id = '"    . trim($session_id) .
                            "', modified_time = '"  . trim($created_time) .
                            "', pay_amount = '" . 0  .
                            "', adj_amount = '"    . 0 .
                            "', follow_up = '"    . "$drop_down_reason" .
                            "', follow_up_note = '"    . $reason .
                            "'");
                break;
        }
        return 1;
        }
    }
    public function delete_ar_activity($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {
            case 'D1':
                list($payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("delete from ar_activity " .
                            " where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and pay_amount>0");
                break;
            case 'D2':
                list($payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("delete from ar_activity " .
                            " where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and adj_amount!=0");
                break;
            case 'D3':
                list($payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("delete from ar_activity " .
                            " where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and memo like 'Deductable%'");
                break;
            case 'D4':
                list($payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("delete from ar_activity " .
                            " where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and pay_amount < 0");
                break;
            case 'D5':
                list($payment_id,$pid,$encounter,$code,$modifier)=$dat;
                sqlStatement("delete from ar_activity " .
                            " where  session_id ='$payment_id' and pid ='" . trim($pid)  .
                            "' and  encounter  ='" . trim($encounter)  .
                            "' and  code  ='" . trim($code)  .
                            "' and  modifier  ='" . trim($modifier)  .
                            "' and follow_up !=''");
                break;
        }
        return 1;
        }
    }
    public function update_form_encounter($data)
    {
        list($cred, $a, $dat)=$data;
        if(UserService::valid($cred)){
        switch($a)
        {            
            case 'F2':
                list($last_level_closed,$pid,$encounter)=$dat;
                sqlStatement("update form_encounter set last_level_closed='".$last_level_closed."' where 
                pid ='".trim($pid)."' and encounter='".$encounter."'");
                break;
            case 'F3':
                list($today,$fid)=$dat;
                sqlStatement("UPDATE form_encounter SET " .
                "last_stmt_date = '$today', stmt_count = stmt_count + 1 " .
                "WHERE id = " . $fid);
                break;
        }
        return 1;
        }
    }
    public function delete_payment($data)
    {
        list($cred, $DeletePaymentId)=$data;
        if(UserService::valid($cred)){
        require_once(dirname(__FILE__) . "/../../library/payment.inc.php");
        $ResultSearch = sqlStatement("SELECT distinct encounter,pid from ar_activity where  session_id ='$DeletePaymentId'");
        if(sqlNumRows($ResultSearch)>0)
        {
            while ($RowSearch = sqlFetchArray($ResultSearch))
            {
                $Encounter=$RowSearch['encounter'];
                $PId=$RowSearch['pid'];
                sqlStatement("update form_encounter set last_level_closed=last_level_closed - 1 where pid ='$PId' and encounter='$Encounter'" );
            }
        }
        //delete and log that action
        row_delete("ar_session", "session_id ='$DeletePaymentId'");
        row_delete("ar_activity", "session_id ='$DeletePaymentId'");
        return 1;
        }
    }
    public function copy_file($data)
    {
        list($cred, $source, $dest)=$data;
        if(UserService::valid($cred)){
        copy($source,$dest);
        return 1;
        }
    }
    public function update_era_details($data)
    {
        list($cred,$chk)=$data;
        if(UserService::valid($cred)){
            foreach($chk as $key=>$value){
                sqlQuery("update era_details set processed=1 where check_number='$value'");
            }
            return 1;
	}
    }
    public function get_result($data)
    {
        list($cred,$payment_id,$datArray)=$data;
        if($this->valid($cred)){
            $Resultset=array();
            while ($RowSearchSub = array_shift($datArray))
            {
                $CountPatient++;
                $PId=$RowSearchSub['pid'];
                $EncounterMaster=$RowSearchSub['encounter'];
                $CodeMaster=$RowSearchSub['code'];
                $ModifierMaster=$RowSearchSub['modifier'];
                $res = sqlStatement("SELECT fname,lname,mname FROM patient_data	where pid ='$PId'");
                $row = sqlFetchArray($res);
                $fname=$row['fname'];
                $lname=$row['lname'];
                $mname=$row['mname'];
                $NameDB=$lname.' '.$fname.' '.$mname;
                $ResultSearch = sqlStatement("
                SELECT billing.id,last_level_closed,billing.encounter,form_encounter.`date`,billing.code,billing.modifier,fee
                FROM billing ,form_encounter
                where billing.encounter=form_encounter.encounter and billing.pid=form_encounter.pid and 
                code_type!='ICD9' and  code_type!='COPAY' and billing.activity!=0 and 
                form_encounter.pid ='$PId' and billing.pid ='$PId' and billing.encounter ='$EncounterMaster'
                and billing.code ='$CodeMaster'
                and billing.modifier ='$ModifierMaster'
                ORDER BY form_encounter.`date`,form_encounter.encounter,billing.code,billing.modifier");
                if(sqlNumRows($ResultSearch)>0)
                {
                    while ($RowSearch = sqlFetchArray($ResultSearch))
                    {
                        $CountIndex++;
                        $CountIndexAbove++;
                        $ServiceDateArray=split(' ',$RowSearch['date']);
                        $ServiceDate=oeFormatShortDate($ServiceDateArray[0]);
                        $Code=$RowSearch['code'];
                        $Modifier =$RowSearch['modifier'];
                        if($Modifier!='')
                            $ModifierString=", $Modifier";
                        else
                            $ModifierString="";
                        $Fee=$RowSearch['fee'];
                        $Encounter=$RowSearch['encounter'];
                        
                        $resPayer = sqlStatement("SELECT  payer_type from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier' ");
                        $rowPayer = sqlFetchArray($resPayer);
                        $Ins=$rowPayer['payer_type'];
                        
                        //Always associating the copay to a particular charge.
                        $BillingId=$RowSearch['id'];
                        $resId = sqlStatement("SELECT id  FROM billing where code_type!='ICD9' and  code_type!='COPAY'  and
                        pid ='$PId' and  encounter  ='$Encounter' and billing.activity!=0 order by id");
                        $rowId = sqlFetchArray($resId);
                        $Id=$rowId['id'];

                        if($BillingId!=$Id)//multiple cpt in single encounter
                        {
                            $Copay=0.00;
                        }
                        else
                        {
                            $resCopay = sqlStatement("SELECT sum(fee) as copay FROM billing where
                            code_type='COPAY' and  pid ='$PId' and  encounter  ='$Encounter' and billing.activity!=0");
                            $rowCopay = sqlFetchArray($resCopay);
                            
                            $Copay=$rowCopay['copay']*-1;

                            $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as PatientPay FROM ar_activity where
                            pid ='$PId'  and  encounter  ='$Encounter' and  payer_type=0 and 
                            (code='CO-PAY' or account_code='PCP')");//new fees screen copay gives account_code='PCP'
                            //openemr payment screen copay gives code='CO-PAY'
                            $rowMoneyGot = sqlFetchArray($resMoneyGot);
                            
                            $PatientPay=$rowMoneyGot['PatientPay'];                                           
                            $Copay=$Copay+$PatientPay;
                        }

                        //For calculating Remainder
                        if($Ins==0)
                        {//Fetch all values
                            $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as MoneyGot FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'  and  encounter  ='$Encounter' and  !(payer_type=0 and 
                            (code='CO-PAY' or account_code='PCP'))");
                            ////new fees screen copay gives account_code='PCP'
                            ////openemr payment screen copay gives code='CO-PAY'
                            $rowMoneyGot = sqlFetchArray($resMoneyGot);
                            
                            $MoneyGot=$rowMoneyGot['MoneyGot'];

                            $resMoneyAdjusted = sqlStatement("SELECT sum(adj_amount) as MoneyAdjusted FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'  and  encounter  ='$Encounter'");
                            $rowMoneyAdjusted = sqlFetchArray($resMoneyAdjusted);
                            $MoneyAdjusted=$rowMoneyAdjusted['MoneyAdjusted'];
                        }
                        else//Fetch till that much got
                        {
                            //Fetch the HIGHEST sequence_no till this session.
                            //Used maily in  the case if primary/others pays once more.
                            $resSequence = sqlStatement("SELECT  sequence_no from ar_activity where  session_id ='$payment_id' and
                            pid ='$PId' and  encounter  ='$Encounter' order by sequence_no desc ");
                            $rowSequence = sqlFetchArray($resSequence);
                            $Sequence=$rowSequence['sequence_no'];
                            
                            $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as MoneyGot FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'  and  encounter  ='$Encounter' and  
                            payer_type > 0 and payer_type <='$Ins' and sequence_no<='$Sequence'");
                            $rowMoneyGot = sqlFetchArray($resMoneyGot);                            
                            $MoneyGot=$rowMoneyGot['MoneyGot'];

                            $resMoneyAdjusted = sqlStatement("SELECT sum(adj_amount) as MoneyAdjusted FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'   and  encounter  ='$Encounter' and  
                            payer_type > 0 and payer_type <='$Ins' and sequence_no<='$Sequence'");
                            $rowMoneyAdjusted = sqlFetchArray($resMoneyAdjusted);                             
                            $MoneyAdjusted=$rowMoneyAdjusted['MoneyAdjusted'];
                        }
                        $Remainder=$Fee-$Copay-$MoneyGot-$MoneyAdjusted;

                        //For calculating RemainderJS.Used while restoring back the values.
                        if($Ins==0)
                        {   //Got just before Patient
                            $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as MoneyGot FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'  and  encounter  ='$Encounter' and  payer_type !=0");
                            $rowMoneyGot = sqlFetchArray($resMoneyGot);
                            $MoneyGot=$rowMoneyGot['MoneyGot'];

                            $resMoneyAdjusted = sqlStatement("SELECT sum(adj_amount) as MoneyAdjusted FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'  and  encounter  ='$Encounter' and payer_type !=0");
                            $rowMoneyAdjusted = sqlFetchArray($resMoneyAdjusted);
                            $MoneyAdjusted=$rowMoneyAdjusted['MoneyAdjusted'];
                        }
                        else
                        {   //Got just before the previous
                            //Fetch the LOWEST sequence_no till this session.
                            //Used maily in  the case if primary/others pays once more.
                            $resSequence = sqlStatement("SELECT  sequence_no from ar_activity where  session_id ='$payment_id' and
                            pid ='$PId' and  encounter  ='$Encounter' order by sequence_no  ");
                            $rowSequence = sqlFetchArray($resSequence);
                            $Sequence=$rowSequence['sequence_no'];

                            $resMoneyGot = sqlStatement("SELECT sum(pay_amount) as MoneyGot FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'   and  encounter  ='$Encounter' 
                            and payer_type > 0  and payer_type <='$Ins' and sequence_no<'$Sequence'");
                            $rowMoneyGot = sqlFetchArray($resMoneyGot);
                            $MoneyGot=$rowMoneyGot['MoneyGot'];

                            $resMoneyAdjusted = sqlStatement("SELECT sum(adj_amount) as MoneyAdjusted FROM ar_activity where
                            pid ='$PId' and  code='$Code' and modifier='$Modifier'   and  encounter  ='$Encounter' 
                            and payer_type <='$Ins' and sequence_no<'$Sequence' ");
                            $rowMoneyAdjusted = sqlFetchArray($resMoneyAdjusted);
                            $MoneyAdjusted=$rowMoneyAdjusted['MoneyAdjusted'];
                        }
                        //Stored in hidden so that can be used while restoring back the values.
                        $RemainderJS=$Fee-$Copay-$MoneyGot-$MoneyAdjusted;
                        $resPayment = sqlStatement("SELECT  pay_amount from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier'  and pay_amount>0");
                        $rowPayment = sqlFetchArray($resPayment);
                        
                        $PaymentDB=$rowPayment['pay_amount']*1;
                        $PaymentDB=$PaymentDB == 0 ? '' : $PaymentDB;

                        $resPayment = sqlStatement("SELECT  pay_amount from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier'  and pay_amount<0");
                        $rowPayment = sqlFetchArray($resPayment);
                        $TakebackDB=$rowPayment['pay_amount']*-1;
                        $TakebackDB=$TakebackDB == 0 ? '' : $TakebackDB;

                        $resPayment = sqlStatement("SELECT  adj_amount from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier'  and adj_amount!=0");
                        $rowPayment = sqlFetchArray($resPayment);
                        $AdjAmountDB=$rowPayment['adj_amount']*1;
                        $AdjAmountDB=$AdjAmountDB == 0 ? '' : $AdjAmountDB;

                        $resPayment = sqlStatement("SELECT  memo from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier'  and memo like 'Deductable%'");
                        $rowPayment = sqlFetchArray($resPayment);
                        $DeductibleDB=$rowPayment['memo'];
                        $DeductibleDB=str_replace('Deductable $','',$DeductibleDB);

                        $resPayment = sqlStatement("SELECT  follow_up,follow_up_note from ar_activity where  session_id ='$payment_id' and
                        pid ='$PId' and  encounter  ='$Encounter' and  code='$Code' and modifier='$Modifier'  and follow_up != ''");
                        $rowPayment = sqlFetchArray($resPayment);
                        $drop_down_reason=$rowPayment['follow_up'];
                        $FollowUpReasonDB=$rowPayment['follow_up_note'];

                        if($Ins==1)
                        {
                            $AllowedDB=number_format($Fee-$AdjAmountDB,2);
                        }
                        else
                        {
                            $AllowedDB = 0;
                        }
                        $AllowedDB=$AllowedDB == 0 ? '' : $AllowedDB;

                        if($CountIndex==$TotalRows)
                        {
                            $StringClass=' bottom left top ';
                        }
                        else
                        {
                            $StringClass=' left top ';
                        }

                        if($Ins==1)
                        {
                            $bgcolor='#ddddff';
                        }
                        elseif($Ins==2)
                        {
                            $bgcolor='#ffdddd';
                        }
                        elseif($Ins==3)
                        {
                            $bgcolor='#F2F1BC';
                        }
                        elseif($Ins==0)
                        {
                            $bgcolor='#AAFFFF';
                        }
                        $paymenttot=$paymenttot+$PaymentDB;
                        $adjamttot=$adjamttot+$AdjAmountDB;
                        $deductibletot=$deductibletot+$DeductibleDB;
                        $takebacktot=$takebacktot+$TakebackDB;
                        $allowedtot=$allowedtot+$AllowedDB;
                        //===================================================================================								 
                        //Export values for pdf and excell
                        //===================================================================================
                        $Resultset[$CountIndex-1]['Patient ID']=htmlspecialchars($PId);
                        $Resultset[$CountIndex-1]['Encounter']=htmlspecialchars($Encounter);
                        $Resultset[$CountIndex-1]['Patient Name']=htmlspecialchars($NameDB);
                        $Resultset[$CountIndex-1]['Payment From']=htmlspecialchars($div_after_save);
                        if($Ins==0)
                        {
                            $Resultset[$CountIndex-1]['Post From']='Patient';
                        }
                        elseif($Ins==1)
                        {
                            $Resultset[$CountIndex-1]['Post From']='Primary';
                        }
                        elseif($Ins==2)
                        {
                            $Resultset[$CountIndex-1]['Post From']='Secondary';
                        }
                        elseif($Ins==3)
                        {
                            $Resultset[$CountIndex-1]['Post From']='Tertiary';
                        }
                        $Resultset[$CountIndex-1]['DOS']=htmlspecialchars($ServiceDate);
                        $Resultset[$CountIndex-1]['Encounter']=htmlspecialchars($Encounter);
                        $Resultset[$CountIndex-1]['CPT']=htmlspecialchars($Code);
                        $Resultset[$CountIndex-1]['Code']=htmlspecialchars($Code);
                        $Resultset[$CountIndex-1]['Mod']=htmlspecialchars($ModifierString);
						$Resultset[$CountIndex-1]['Modifier_Code']=htmlspecialchars($Modifier);
                        $Resultset[$CountIndex-1]['CPT/Mod']=htmlspecialchars($Code.$ModifierString);
                        $Resultset[$CountIndex-1]['Charge']=htmlspecialchars($Fee);
                        $Resultset[$CountIndex-1]['Copay']=htmlspecialchars(number_format($Copay,2));
                        $Resultset[$CountIndex-1]['Remaider']=htmlspecialchars(round($Remainder,2));
                        $Resultset[$CountIndex-1]['RemaiderJS']=htmlspecialchars($RemainderJS);
						if(htmlspecialchars(trim($drop_down_reason))!='d')
                        $Resultset[$CountIndex-1]['Allowed']=htmlspecialchars($AllowedDB);
                        $Resultset[$CountIndex-1]['Payment']=htmlspecialchars($PaymentDB);
                        $Resultset[$CountIndex-1]['Adjustment']=htmlspecialchars($AdjAmountDB);
                        $Resultset[$CountIndex-1]['Deductible']=htmlspecialchars($DeductibleDB);
                        $Resultset[$CountIndex-1]['Takeback']=htmlspecialchars($TakebackDB);
                        $Resultset[$CountIndex-1]['Insurance']=htmlspecialchars($Ins);
                        $Resultset[$CountIndex-1]['DropReason']=htmlspecialchars($drop_down_reason);
                        $Resultset[$CountIndex-1]['FollowupReason']=htmlspecialchars($FollowUpReasonDB);
                        //===================================================================================                                    
                    }//while ($RowSearch = sqlFetchArray($ResultSearch))
                }//if(sqlNumRows($ResultSearch)>0)
            }
            return $Resultset;
        }
    }
}
?>