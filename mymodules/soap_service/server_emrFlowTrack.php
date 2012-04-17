<?php
//$sqlconf = array();
//$sqlconf["host"]= 'localhost';
//$sqlconf["port"] = '3306';
//$sqlconf["login"] = 'root';
//$sqlconf["pass"] = 'navas';
//$sqlconf["dbase"] = 'bradlee';
set_time_limit(0);
global $connectionFlow,$sqlconf_common;
$connectionFlow=emrflowtrack::sql_connect($sqlconf);

class emrflowtrack extends FileParse
{
    public function sql_connect($sqlconf)
    {
        global $connectionFlow;
        $hostname=$sqlconf['host'];
        $username=$sqlconf['login'];
        $password=$sqlconf['pass'];
        $database=$sqlconf['dbase'];
        $port=$sqlconf['port'];
        $connectionFlow=mysql_connect("$hostname:$port",$username,$password);
        if (!$connectionFlow)
        {
            return false;
            die('Could not connect: ' . mysql_error());
        }
        mysql_select_db($database,$connectionFlow);
        return $connectionFlow;
    }
    
    public function sql_connect_specific_db($sqlconf,$db)
    {
        $hostname=$sqlconf['host'];
        $username=$sqlconf['login'];
        $password=$sqlconf['pass'];
        $database=$db;
        $port=$sqlconf['port'];
        $connectionDB=mysql_connect("$hostname:$port",$username,$password);
        if (!$connectionDB)
        {
            return false;
            die('Could not connect: ' . mysql_error());
        }
        mysql_select_db($database,$connectionDB);
        return $connectionDB;
    }
    
    public function get_main_status_id($data)
    {
        list($cred,$mainCode)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        return $this->sqlExecute("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$mainCode."') AND cl_list_type IN ('15')",'cl_list_slno');
        }
    }
    
    public function get_sub_status_id($data)
    {
        list($cred,$subCode)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        return $this->sqlExecute("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$subCode."') AND cl_list_type IN ('20')",'cl_list_slno');
        }
    }
    
    public function get_count($data)
    {
        list($cred,$ac_pid,$ac_encounter,$ac_master_status,$ac_status)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;        
        $count=$this->sqlExecute("SELECT ac_count FROM arr_status WHERE ac_pid='".$ac_pid."' AND ac_encounter='".$ac_encounter."' AND ac_status='".$ac_status."' ORDER BY ac_id DESC LIMIT 1",'ac_count');
        if(!$count)
        $count=0;
        $count=$count+1;
        return ($count);
        }
    }
    
    public function get_insurance_id($data)
    {
        list($cred,$ac_pid,$level)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        if($level==1)
        $ins_level='primary';
        elseif($level==2)
        $ins_level='secondary';
        if($level==3)
        $ins_level='tertiary';
        return $this->sqlExecute("SELECT * FROM insurance_data WHERE pid='".$ac_pid."' AND type='".$ins_level."' ORDER BY date DESC LIMIT 1",'provider');
        }
    }
    
    public function update_status($data)
    {
        list($cred,$mainCode,$subCode,$pid,$encounter,$comment,$reason)=$data;
        if(UserService::valid($cred)){
            global $connectionFlow;
            $get_enc_dt=sqlQuery("SELECT fe.date FROM form_encounter AS fe WHERE fe.pid='$pid' AND fe.encounter='$encounter'");
            $enc_dt=$get_enc_dt['date'];
            $am_ins1=arGetPayerID($pid,$enc_dt,1);
            $am_ins2=arGetPayerID($pid,$enc_dt,2);
            $am_ins3=arGetPayerID($pid,$enc_dt,3);
            $am_inslevel=ar_responsible_party($pid,$encounter);
            $main = $this->sqlExecute("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$mainCode."') AND cl_list_type IN ('15')",'cl_list_slno');
            $sub = $this->sqlExecute("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$subCode."') AND cl_list_type IN ('20')",'cl_list_slno');
            $getAccStatus = $this->sqlExecute("SELECT * FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' ORDER BY ac_id DESC LIMIT 1",'ac_status');
            if($getAccStatus != $sub || $getAccStatus==''){
                $count=$this->sqlExecute("SELECT ac_count FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' AND ac_status='".$sub."' ORDER BY ac_id DESC LIMIT 1",'ac_count');
                if(!$count) $count=1;
                else $count=$count+1;
                $statusId = $this->sqlExecute("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_master_status,ac_status,ac_count,ac_comment,ac_reason) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$main."','".$sub."','".$count."','".$comment."','".$reason."')",'mysql_insert_id');
                $masterId = $this->sqlExecute("select am_id from arr_master where am_pid='$pid' and am_encounter='$encounter'",'am_id');
                if($masterId != ''){
                    $this->sqlExecute("UPDATE arr_master SET am_statustrack='".$statusId."', am_currentstatus='".$sub."', am_ins1='".$am_ins1."', am_ins2='".$am_ins2."', am_ins3='".$am_ins3."', am_inslevel='".$am_inslevel."' WHERE am_pid='".$pid."' AND am_encounter='".$encounter."'");
                }
                else{
                    $masterId = $this->sqlExecute("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$statusId."','".$sub."','$am_ins1','$am_ins2','$am_ins3','$am_inslevel')",'mysql_insert_id');
                }
                $this->sqlExecute("UPDATE arr_status SET ac_arr_id='".$masterId."' WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."'");
            }
        }
        return 1;
    }
    
    public function insert_arr_status($data)
    {
        list($cred,$ac_uid,$ac_pid,$ac_encounter,$ac_arr_id,$ac_master_status,$ac_status,$ac_arstatus,$ac_officeally_status,$ac_callnotes,$ac_denial_remark,$ac_action,$ac_callbackdays,$ac_callbackdate,$ac_count,$ac_comment,$ac_reason)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        return $this->sqlExecute("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_arr_id,ac_master_status,ac_status,ac_arstatus,ac_officeally_status,ac_callnotes,ac_am_denial_remark,ac_am_action,ac_callbackdays,ac_callbackdate,ac_count,ac_comment,ac_reason) VALUES ('".$ac_uid."','".$ac_pid."','".$ac_encounter."','".$ac_arr_id."','".$ac_master_status."','".$ac_status."','".$ac_arstatus."','".$ac_officeally_status."','".$ac_callnotes."','".$ac_denial_remark."','".$ac_action."','".$ac_callbackdays."','".$ac_callbackdate."','".$ac_count."','".$ac_comment."','".trim(mysql_real_escape_string($ac_reason))."')",'mysql_insert_id');
        }
    }
    
    public function update_arr_status($data)
    {
        list($cred,$arr_id,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $this->sqlExecute("UPDATE arr_status SET ac_arr_id='".$arr_id."' WHERE ac_pid='".$ac_pid."' AND ac_encounter='".$ac_encounter."'");
        }
    }
    
    public function insert_arr_master($data)
    {
        list($cred,$am_uid,$am_pid,$am_encounter,$am_statustrack,$am_currentstatus)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $am_id=$this->sqlExecute("select am_id from arr_master where am_pid='$am_pid' and am_encounter='$am_encounter'",'am_id');
        if($am_id!=''){
            $this->update_arr_master(array($cred,$am_statustrack,$am_currentstatus,$am_pid,$am_encounter));
            return $am_id;
        }
        else{
            return $this->sqlExecute("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus) VALUES ('".$am_uid."','".$am_pid."','".$am_encounter."','".$am_statustrack."','".$am_currentstatus."')",'mysql_insert_id');
        }
        }
    }
    
    public function update_arr_master($data)
    {
        list($cred,$ac_id,$ac_status,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $this->sqlExecute("UPDATE arr_master SET am_statustrack='".$ac_id."', am_currentstatus='".$ac_status."' WHERE am_pid='".$ac_pid."' AND am_encounter='".$ac_encounter."'");
        }
    }
    
    public function update_arr_master_insurance($data)
    {
        list($cred,$ac_pid,$ac_encounter,$ins1,$ins2,$ins3,$am_inslevel)=$data;
        if(UserService::valid($cred)){
        include_once(dirname(__FILE__).'/../../library/sl_eob.inc.php');
        include_once(dirname(__FILE__).'/../../library/invoice_summary.inc.php');
        global $connectionFlow;
        $encDate=$this->sqlExecute("select date from form_encounter where pid='$ac_pid' and encounter='$ac_encounter'",'date');
        if(!$ins1)
            $ins1=arGetPayerID($ac_pid, $encDate, 1);
        if(!$ins2)
            $ins2=arGetPayerID($ac_pid, $encDate, 2);
        if(!$ins3)
            $ins3=arGetPayerID($ac_pid, $encDate, 3);
        if(!$am_inslevel)
            $am_inslevel=ar_responsible_party($ac_pid,$ac_encounter);
        $ins_field=" am_ins1='".$ins1."', am_ins2='".$ins2."', am_ins3='".$ins3."', ";
        
        $this->sqlExecute("UPDATE arr_master SET $ins_field am_inslevel='".$am_inslevel."' WHERE am_pid='".$ac_pid."' AND am_encounter='".$ac_encounter."'");
        }
    }
    
    public function get_arr_id($data)
    {
        list($cred,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        return $this->sqlExecute("SELECT am_id FROM arr_master WHERE am_pid='".$ac_pid."' AND am_encounter='".$ac_encounter."'",'am_id');
        }
    }
    
    public function checkDIAG($data)
    {
        list($cred,$billing_pid,$billing_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $getResult=$this->sqlExecute("SELECT * FROM billing WHERE pid='".$billing_pid."' AND encounter='".$billing_encounter."' AND activity='1' AND code_type='ICD9'");
        return $this->sqlGetRows($getResult);
        }
    }
    
    public function checkCPT($data)
    {
        list($cred,$billing_pid,$billing_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $getResult=$this->sqlExecute("SELECT * FROM billing WHERE pid='".$billing_pid."' AND encounter='".$billing_encounter."' AND activity='1' AND code_type='CPT4'");
        return $this->sqlGetRows($getResult);
        }
    }
    
    public function code_check($data)
    {
        list($cred,$status_code,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $getResult=$this->sqlExecute("SELECT * FROM arr_status WHERE ac_pid='".$ac_pid."' AND ac_encounter='".$ac_encounter."' AND ac_status='".$status_code."'");
        return $this->sqlGetRows($getResult);
        }
    }
    
    public function final_code_check($data)
    {
        list($cred,$status_code,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $getAccStatus=$this->sqlExecute("SELECT * FROM arr_status WHERE ac_pid='".$ac_pid."' AND ac_encounter='".$ac_encounter."' ORDER BY ac_id DESC LIMIT 1",'ac_status');
        if($getAccStatus==$status_code)
            return 1;
        else
            return 0;
        }
    }
    
    public function cpt_justify_check($data)
    {
        list($cred,$ac_pid,$ac_encounter)=$data;
        if(UserService::valid($cred)){
        global $connectionFlow;
        $getResult=$this->sqlExecute("SELECT justify FROM billing WHERE pid='$ac_pid' AND encounter='$ac_encounter' AND code_type='CPT4' AND activity='1' AND (justify IS NULL OR justify = '')");
        if($this->sqlGetRows($getResult)==0)
            return 1;
        else
            return 0;
        }
    }
    
    public function update_zero_balance($data)
    {
        list($cred,$patid,$enc)=$data;
        if(UserService::valid($cred)){
        $charges=sqlQuery("SELECT SUM(b.fee) AS charges FROM billing AS b WHERE b.pid=? AND b.encounter=? AND b.activity=1",array($patid,$enc));
        $pay_adj=sqlQuery("SELECT SUM(ar.pay_amount) AS payment,SUM(ar.adj_amount) AS adjustment FROM ar_activity AS ar WHERE ar.pid=? AND ar.encounter=?",array($patid,$enc));
        $balance=$charges['charges']-$pay_adj['payment']-$pay_adj['adjustment'];
        if($balance<=0)
        {
            $main = $this->get_main_status_id(array($cred,'BILLING'));
            $sub = $this->get_sub_status_id(array($cred,'ENCT_ZERO_BALANCE'));
            $count = $this->get_count(array($cred,$patid,$enc,$main,$sub));
            if($this->final_code_check(array($cred,$sub,$patid,$enc))==0)
            {
                $arrstatustrackid = $this->insert_arr_status(array($cred,$_SESSION['authId'],$patid,$enc,'',$main,$sub,'','','','','','','',$count,'BillingPortal:Zero Blance:Common Function'));
                $arrid = $this->get_arr_id(array($cred,$patid,$enc));
                $this->update_arr_status(array($cred,$arrid,$patid,$enc));
                $this->update_arr_master(array($cred,$arrstatustrackid,$sub,$patid,$enc));
            }
        }
        }
    }

    public function sqlExecute($statement,$return_value="")
    {
        global $connectionFlow;
        $res=mysql_query($statement,$connectionFlow) or die($statement."-".mysql_error());
        $getinsertId=mysql_query("SELECT LAST_INSERT_ID() as INSERT_ID",$connectionFlow);
        $insertId=mysql_fetch_array($getinsertId);
        if($return_value == 'mysql_insert_id'){
            return $insertId['INSERT_ID'];
        }
        elseif($return_value){
            $row=mysql_fetch_array($res);
            return $row[$return_value];
        }
        else{
            return $res;
        }
    }
    
    public function sqlExecuteDB($connection,$statement,$return_value="")
    {
        $res=mysql_query($statement,$connection) or die($statement."-".mysql_error());
        $getinsertId=mysql_query("SELECT LAST_INSERT_ID() as INSERT_ID",$connection);
        $insertId=mysql_fetch_array($getinsertId);
        if($return_value == 'mysql_insert_id'){
            return $insertId['INSERT_ID'];
        }
        elseif($return_value){
            $row=mysql_fetch_array($res);
            return $row[$return_value];
        }
        else{
            return $res;
        }
	return false;
    }
    
    public function sqlGetRows($resultset)
    {
        global $connectionFlow;
        return mysql_numrows($resultset);
    }
    
    public function sqlFetchRowArray($resultset)
    {
        global $connectionFlow;
        if(is_resource($resultset))
        return mysql_fetch_array($resultset);
    }
    
    public function import_provider_details($data)
    {
        if(UserService::valid($data[0])){
        $dir=dirname(__FILE__).'/../../sites/';
        $dh = opendir($dir);
        $s=array();
        while (($file = readdir($dh)) !== false)
        {
            if($file=='.'||$file=='..') continue;
            require($dir.$file."/sqlconf.php");
            $con=$this->sql_connect($sqlconf);
            $res=$this->sqlExecuteDB($con,"SELECT u.id, u.lname, u.fname, f.facility_npi FROM users AS u, facility AS f WHERE f.id=u.facility_id AND u.authorized = 1 AND u.username != '' AND u.username NOT LIKE '%Admin%' AND u.active = 1 AND ( u.info IS NULL OR u.info NOT LIKE '%Inactive%' ) ORDER BY u.lname, u.fname");
            mysql_select_db('emr_database_list',$con);
            while($r=$this->sqlFetchRowArray($res))
            {
                $res_check=$this->sqlExecuteDB($con,"select * from provider where provider_id='".$r['id']."' and npi='".$r['facility_npi']."'");
                if($this->sqlGetRows($res_check)<=0)
                    $this->sqlExecuteDB($con,"insert into provider (provider_id,npi,db,site_folder) values ('".$r['id']."','".$r['facility_npi']."','".$sqlconf["dbase"]."','".$file."')");
            }
        }
        return $s;
        }
    }
    
    public function import_facility_details($data)
    {
        if(UserService::valid($data[0])){
        global $sqlconf;
        $dir=dirname(__FILE__).'/../../sites/';
        $dh = opendir($dir);
        $facility_details=array();
        /*while (($file = readdir($dh)) !== false)
        {*/
			$file=$data[2];
            if($file=='.'||$file=='..') continue;
            if(!is_file($dir.$file."/sqlconf.php")) continue;
            require($dir.$file."/sqlconf.php");
            $con=$this->sql_connect($sqlconf);
            if($con){			
            $result=$this->sqlExecuteDB($con,"SELECT f.id,f.federal_ein,x12.x12_username,f.facility_npi FROM facility AS f LEFT JOIN x12_partners AS x12 ON x12.name = 'OfficeAlly' WHERE billing_location=1");
            while($row=$this->sqlFetchRowArray($result))
            {
                $facility_details[$sqlconf['dbase']][$row['id']]['id'] = $row['id'];
                $facility_details[$sqlconf['dbase']][$row['id']]['federal_ein'] = $row['federal_ein'];
                $facility_details[$sqlconf['dbase']][$row['id']]['facility_npi'] = $row['facility_npi'];
                $facility_details[$sqlconf['dbase']][$row['id']]['x12_username'] = $row['x12_username'];
                $facility_details[$sqlconf['dbase']][$row['id']]['site_folder'] = $file;
                $facility_details[$sqlconf['dbase']][$row['id']]['db'] = $sqlconf['dbase'];
            }
            mysql_close($con);
            }
        /*}*/
        return $facility_details;
        }
    }
    
    public function parse_era($data) {
        list($cred, $filename)=$data;
        if(UserService::valid($cred)){
        $delimiter1 = '~';
        $delimiter2 = '|';
        $delimiter3 = '^';
      
        $infh = fopen($filename, 'r');
        if (! $infh) return "ERA input file open failed";
    
        $out = array();
        $out['loopid'] = '';
        $out['st_segment_count'] = 0;
        $buffer = '';
        $segid = '';
      
        while (true) {
            if (strlen($buffer) < 2048 && ! feof($infh)) $buffer .= fread($infh, 2048);
            $tpos = strpos($buffer, $delimiter1);
            if ($tpos === false) break;
            $inline = substr($buffer, 0, $tpos);
            $buffer = substr($buffer, $tpos + 1);
            
            if ($segid === '' && substr($inline, 0, 3) === 'ISA') {
                $delimiter2 = substr($inline, 3, 1);
                $delimiter3 = substr($inline, -1);
            }
            
            $seg = explode($delimiter2, $inline);
            $segid = $seg[0];
            if ($segid == 'ISA') {
                if ($out['loopid']) return 'Unexpected ISA segment';
                $out['isa_sender_id']      = trim($seg[6]);
                $out['isa_receiver_id']    = trim($seg[8]);
                $out['isa_control_number'] = trim($seg[13]);
                // TBD: clear some stuff if we allow multiple transmission files.
            }
            else if ($segid == 'GS') {
                if ($out['loopid']) return 'Unexpected GS segment';
                $out['gs_date'] = trim($seg[4]);
                $out['gs_time'] = trim($seg[5]);
                $out['gs_control_number'] = trim($seg[6]);
            }
            else if ($segid == 'BPR') {
                if ($out['loopid']) return 'Unexpected BPR segment';
                $out['check_amount'] = trim($seg[2]);
                $out['check_date'] = trim($seg[16]); // yyyymmdd
                // TBD: BPR04 is a payment method code.
            }
            elseif ($segid == 'TRN') {
                if ($out['loopid']) return 'Unexpected TRN segment';
                $out['check_number'] = trim($seg[2]);
                $out['payer_tax_id'] = substr($seg[3], 1); // 9 digits
                $out['payer_id'] = trim($seg[4]);
                // Note: TRN04 further qualifies the paying entity within the
                // organization identified by TRN03.
            }
            else if ($segid == 'N1' && $seg[1] == 'PR') {
                if ($out['loopid']) return 'Unexpected N1|PR segment';
                $out['loopid'] = '1000A';
                $out['payer_name'] = trim($seg[2]);
            }
            else if ($segid == 'N1' && $seg[1] == 'PE') {
                if ($out['loopid'] != '1000A') return 'Unexpected N1|PE segment';
                $out['loopid'] = '1000B';
                $out['payee_name']   = trim($seg[2]);
                $out['payee_tax_id'] = trim($seg[4]);
            }
            else if ($segid == 'LX') {
                if (! $out['loopid']) return 'Unexpected LX segment';
                $out['loopid'] = '2000';
            }
            else if ($segid == 'CLP') {
                if (! $out['loopid']) return 'Unexpected CLP segment';                
                $out['loopid'] = '2100';
            }
            else if ($segid == 'SVC') {
                if (! $out['loopid']) return 'Unexpected SVC segment';
                $out['loopid'] = '2110';
            }
            else if ($segid == 'SE') {
                $out['loopid'] = '';
            }  
            ++$out['st_segment_count'];
        }      
        if ($segid != 'IEA') return 'Premature end of ERA file';
        return $out;
        }
    }
    public function get_OfficeAlly_cred($data)
    {
        list($cred)=$data;
        if(UserService::valid($cred)){
            $x12Partner=sqlStatement("select x12_username,x12_password,last_download_date from x12_partners where name = 'OfficeAlly'");
            $x12=array();
            $i=0;
            while($row=sqlFetchArray($x12Partner)){
                $x12[$i]['user']=$row['x12_username'];
                $x12[$i]['pwd']=$row['x12_password'];
                $x12[$i]['date']=$row['last_download_date'];
                $i++;
            }
            return $x12;
        }
    }
    
    public function InsertCallNotes($data){
        if($this->valid($data[0])){
            foreach($data[1] as $key=>$val){
                $row[$key]=$val;
            }
            $callbackdate='';
            if($row['call_back']){
            $callbackdatearr = sqlQuery("SELECT DATE_FORMAT(DATE_ADD(NOW(),INTERVAL ".$row['call_back']." DAY),'%Y-%m-%d') as callbackdate");
            $callbackdate = $callbackdatearr['callbackdate'];
            }
            $arrstatus = sqlQuery("SELECT * FROM arr_status WHERE ac_pid=? AND ac_encounter=? ORDER BY ac_timestamp DESC LIMIT 1",array($row['am_pid'],$row['am_encounter']));
            $insertID = sqlInsert("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_arr_id,ac_master_status,ac_status,ac_arstatus,ac_officeally_status,
                      ac_callnotes,ac_am_denial_remark,ac_am_action,ac_callbackdays,ac_callbackdate,ac_count,ac_reason,ac_am_status)
                      VALUES(?,?,?,?,?,?,?,?,
                      ?,?,?,?,?,?,?,?)",
                      array($_SESSION['authId'],$row['pid'],$row['encounter'],$arrstatus['ac_arr_id'],$arrstatus['ac_master_status'],$arrstatus['ac_status'],$arrstatus['ac_arstatus'],$arrstatus['ac_officeally_status'],
                      $row['today_callnotes'],$row['today_denial_remarks'],$row['today_action'],$row['call_back'],$callbackdate,$arrstatus['ac_count'],$arrstatus['ac_reason'],$row['today_status']));
            sqlStatement("UPDATE arr_master SET am_uid=?,am_callnotes=?,am_denialremark=?,am_action=?,am_status=?,am_callback=?,am_statustrack=?,
                         am_date=now(),am_calldate=now(),am_callbackdate=? WHERE am_pid=? AND am_encounter=?",
                         array($_SESSION['authId'],$row['today_callnotes'],$row['today_denial_remarks'],$row['today_action'],$row['today_status'],$row['call_back'],$insertID,
                         $callbackdate,$row['pid'],$row['encounter']
                         ));
        }
        else{
            throw new SoapFault("Server", "credentials failed");
        }
    }
    
    public function get_db_name($data){
        list($cred)=$data;
        global $sqlconf;
        if($this->valid($cred)){
            return $sqlconf['dbase'];
        }
    }
    
    public function update_status_batch($data){
        list($cred)=$data;
        if($this->valid($cred)){
            $checkRes = sqlStatement("SELECT pid,encounter,am_encounter,fe.date FROM form_encounter AS fe ".
            "LEFT JOIN arr_master AS ar ON ar.am_encounter=fe.encounter ".
            "WHERE am_encounter IS NULL");
            if(sqlNumRows($checkRes) > 0){
                include_once(dirname(__FILE__).'/../../library/sl_eob.inc.php');
                include_once(dirname(__FILE__).'/../../library/invoice_summary.inc.php');
                set_time_limit(0);
                while($checkRow = sqlFetchArray($checkRes)){
                    $mainCode = 'ENCOUNTER';
                    $subCode = 'ENCT_INI_M';
                    
                    $pid = $checkRow['pid'];
                    $encounter = $checkRow['encounter'];
                    $enc_dt = $checkRow['date'];
                    
                    $am_ins1=arGetPayerID($pid,$enc_dt,1);
                    $am_ins2=arGetPayerID($pid,$enc_dt,2);
                    $am_ins3=arGetPayerID($pid,$enc_dt,3);
                    $am_inslevel=ar_responsible_party($pid,$encounter);
                    
                    $main = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$mainCode."') AND cl_list_type IN ('15')");
                    $sub = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$subCode."') AND cl_list_type IN ('20')");
                    $getAccStatus = sqlQuery("SELECT * FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' ORDER BY ac_id DESC LIMIT 1");
                    if($getAccStatus != $sub || $getAccStatus==''){
                        $count=sqlQuery("SELECT ac_count FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' AND ac_status='".$sub['cl_list_slno']."' ORDER BY ac_id DESC LIMIT 1");
                        if(!$count['ac_count']) $count['ac_count']=0;
                        $statusId = sqlInsert("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_master_status,ac_status,ac_count,ac_comment,ac_reason) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$main['cl_list_slno']."','".$sub['cl_list_slno']."','".$count['ac_count']."','".$comment."','".$reason."')");
                        $master = sqlQuery("select am_id from arr_master where am_pid='$pid' and am_encounter='$encounter'");
                        $masterId = $master['am_id'];
                        if($masterId != '' && $masterId != 0){
                            sqlQuery("UPDATE arr_master SET am_statustrack='".$statusId."', am_currentstatus='".$sub['cl_list_slno']."', am_ins1='".$am_ins1."', am_ins2='".$am_ins2."', am_ins3='".$am_ins3."', am_inslevel='".$am_inslevel."' WHERE am_pid='".$pid."' AND am_encounter='".$encounter."'");
                        }
                        else{
                            $masterId = sqlInsert("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$statusId."','".$sub['cl_list_slno']."','$am_ins1','$am_ins2','$am_ins3','$am_inslevel')");
                        }
                        sqlQuery("UPDATE arr_status SET ac_arr_id='".$masterId."' WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."'");
                    }
                }
            }
        }
        return 1;
    }
}
?>