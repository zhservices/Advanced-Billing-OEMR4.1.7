<?php
// +-----------------------------------------------------------------------------+ 
// Copyright (C) 2011 Z&H Consultancy Services Private Limited <sam@zhservices.com>
//
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// openemr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 
// Author:   Eldho Chacko <eldho@zhservices.com>
//           Jacob T Paul <jacob@zhservices.com>
//           Ajil P.M     <ajilpm@zhservices.com>
//           Vinish K     <vinish@zhservices.com>
//
// +------------------------------------------------------------------------------+

include_once(dirname(__FILE__)."/../../library/api.inc");
include_once(dirname(__FILE__)."/../../library/forms.inc");

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

class FeeSheet Extends Billing{

  public function save_misc_bill($data){
    if($this->valid($data[0])){
      list($credentials,$mode,$pid,$encounter,$group,$authId,$auth_no,$off_work_from,$off_work_to,$hospitalization_date_from,$hospitalization_date_to,$medicaid_resubmission_code,
        $medicaid_original_reference,$outside_lab,$lab_amount,$employment_related,$auto_accident,$accident_state,$other_accident,$replacement_claim,$comments,$formid) = $data;
      $user = sqlQuery("SELECT username FROM users WHERE id='".$authId."'");
      $authUser = $user['username'];
      if ($off_work_from == "0000-00-00" || $off_work_from == ""){
        $is_unable_to_work = "0";
        $off_work_to = "";
      }else{
        $is_unable_to_work = "1";
      }
      if ($hospitalization_date_from == "0000-00-00" || $hospitalization_date_from == ""){
        $is_hospitalized = "0";
        $hospitalization_date_to = "";
      }else{
        $is_hospitalized = "1";
      }
      if ($encounter == "")
        $encounter = date("Ymd");
      if($employment_related !='' || $auto_accident !='' || $accident_state !='' || $other_accident !='' || 
        $outside_lab !='' || $lab_amount !='' || $off_work_from !='' || $off_work_to !='' ||
        $hospitalization_date_from !='' || $hospitalization_date_to !='' || $medicaid_resubmission_code !='' || 
        $medicaid_original_reference !='' || $auth_no !='' || $comments !='' || $replacement_claim !='')
      {
        if ($mode == "new"){
          $newid=sqlInsert("INSERT INTO form_misc_billing_options (date,pid,user,groupname,authorized,activity,employment_related, ".
                        "auto_accident,accident_state,other_accident,outside_lab,lab_amount,is_unable_to_work,off_work_from, ".
                        "off_work_to,is_hospitalized,hospitalization_date_from,hospitalization_date_to,medicaid_resubmission_code, ".
                        "medicaid_original_reference,prior_auth_number,comments,replacement_claim) VALUES ( " .
                        "now(),'".$pid."','".$authUser."','".$group."','1','1','".$employment_related."', ".
                        "'".$auto_accident."','".$accident_state."','".$other_accident."','".$outside_lab."','".$lab_amount."','".$is_unable_to_work."','".$off_work_from."', ".
                        "'".$off_work_to."','".$is_hospitalized."','".$hospitalization_date_from."','".$hospitalization_date_to."','".$medicaid_resubmission_code."', ".
                        "'".$medicaid_original_reference."','".$auth_no."','".$comments."','".$replacement_claim."' )");
          addForm($encounter, "Misc Billing Options", $newid, "misc_billing_options", $pid, 1);
        }elseif( $mode == "update") {
          sqlInsert("update form_misc_billing_options set pid = '".$pid."',
            groupname='".$group."',
            user='".$authUser."',
            authorized=1,activity=1, date = NOW(),
            employment_related='".$employment_related."',
            auto_accident='".$auto_accident."',
            accident_state='".$accident_state."',
            other_accident='".$other_accident."',
            outside_lab='".$outside_lab."',
            lab_amount='".$lab_amount."',
            is_unable_to_work='".$is_unable_to_work."',
            off_work_from='".$off_work_from."',
            off_work_to='".$off_work_to."',
            is_hospitalized='".$is_hospitalized."',
            hospitalization_date_from='".$hospitalization_date_from."',
            hospitalization_date_to='".$hospitalization_date_to."',
            medicaid_resubmission_code='".$medicaid_resubmission_code."',
            medicaid_original_reference='".$medicaid_original_reference."',
            prior_auth_number='".$auth_no."',
            replacement_claim='".$replacement_claim."',
            comments='".$comments."'
            where id='".$formid."' ");
        }
      }
    }
    else{
      throw new SoapFault("Server", "credentials failed in save_misc_bill");
    } 
  }
  
  public function update_provider($data){
    if($this->valid($data[0])){
      list($credentials,$refering_provider,$pid) = $data;
      $query = "UPDATE patient_data SET providerID='$refering_provider' WHERE pid='$pid'";
      sqlStatement($query);
    }
    else{
      throw new SoapFault("Server", "credentials failed in update_provider");
    }
  }
  
  public function fee_sheet_update($data){
    if($this->valid($data[0])){
      list($credentials,$rendering_pro,$supervising_pro,$assignment_value,$pos_code,$pID,$encounterID,$notbill) = $data;
      sqlStatement("UPDATE billing SET activity=0 WHERE pid='$pID' AND encounter='$encounterID' AND code_type IN('CPT4','HCPCS','ICD9','NOSHOW') AND billed = 0 AND activity = 1");
      $lastlevelquery='';
      if($notbill=='false'){
        $lastlevel = sqlQuery("SELECT last_level_billed,last_level_closed FROM form_encounter WHERE pid=? AND encounter=?",array($pID,$encounterID));
      if($lastlevel['last_level_billed']==4 && $lastlevel['last_level_closed']==4)
      $lastlevelquery = ",last_level_billed='0', last_level_closed='0' ";
      }
      sqlStatement("UPDATE form_encounter SET ".
        "provider_id = '$rendering_pro', " .
        "supervisor_id = '$supervising_pro', " .
        "assignment = '$assignment_value', " .
        "pos_code = '$pos_code' " . $lastlevelquery .
        "WHERE ".
        "pid = '$pID' " .
        "AND ".
        "encounter = '$encounterID'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update");
    }
  }
  
  public function fee_sheet_update1($data){
    if($this->valid($data[0])){
      list($credentials,$cnt,$encounterID,$pID) = $data;
      sqlStatement("UPDATE form_encounter SET last_level_billed='$cnt', last_level_closed='$cnt' WHERE encounter='$encounterID' AND pid='$pID'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update1");
    }
  }
  
  public function fee_sheet_update2($data){
    if($this->valid($data[0])){
      list($credentials,$auth_id_val) = $data;
      sqlStatement("UPDATE authorization SET auth_no_of_visits = auth_no_of_visits - 1 WHERE auth_id = '$auth_id_val' AND auth_unlimited = 'n' AND auth_visit_type = 'visits'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update2");
    }
  }
  
  public function fee_sheet_update3($data){
    if($this->valid($data[0])){
      list($credentials,$submitted_auth_no) = $data;
      sqlStatement("UPDATE authorization SET auth_no_of_visits = auth_no_of_visits - 1 WHERE auth_id = '$submitted_auth_no' AND auth_no_of_visits > 0 AND auth_unlimited = 'n' AND auth_visit_type = 'visits'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update3");
    }
  }
  
  public function fee_sheet_update4($data){
    if($this->valid($data[0])){
      list($credentials,$value) = $data;
      sqlStatement("UPDATE authorization SET auth_no_of_visits = auth_no_of_visits + 1 WHERE auth_id = '$value' AND auth_unlimited = 'n' AND auth_visit_type = 'visits'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update4");
    }
  }
  
  public function fee_sheet_update5($data){
    if($this->valid($data[0])){
      list($credentials,$auth_id) = $data;
      sqlStatement("UPDATE authorization SET auth_no_of_visits = auth_no_of_visits + 1 WHERE auth_id = '$auth_id' AND auth_unlimited = 'n' AND auth_visit_type = 'visits'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update5");
    }
  }
  
  public function fee_sheet_update6($data){
    if($this->valid($data[0])){
      list($credentials,$encounterID,$pID) = $data;
      sqlStatement("UPDATE form_encounter SET last_level_billed='4', last_level_closed='4' WHERE encounter='$encounterID' AND pid='$pID'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update6");
    }
  }
  
  public function fee_sheet_update7($data){
    if($this->valid($data[0])){
      list($credentials,$authId,$reference,$check_date,$post_date,$copay_val,$payment_method,$adj_code,$billFac,$SID) = $data;
	  if($post_date){
		sqlStatement("UPDATE ar_session SET user_id='$authId', reference='$reference', check_date='$check_date', deposit_date=now(), ".
		  " pay_total='$copay_val', modified_time=now(), payment_method='$payment_method',adjustment_code='$adj_code', post_to_date='$post_date',cap_bill_facId='$billFac' WHERE session_id='$SID'");
	  }else{
	    sqlStatement("UPDATE ar_session SET user_id='$authId', reference='$reference', check_date='$check_date', deposit_date=now(), ".
		  " pay_total='$copay_val', modified_time=now(), payment_method='$payment_method',adjustment_code='$adj_code',post_to_date=now(),cap_bill_facId='$billFac' WHERE session_id='$SID'");
	  }
	}
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update7");
    }
  }
  
  public function fee_sheet_update8($data){
    if($this->valid($data[0])){
      list($credentials,$cod,$mod,$authId,$copay_val,$account_code,$SID,$post_date) = $data;
      if($post_date){
	    sqlStatement("UPDATE ar_activity SET code='$cod', modifier='$mod', post_user='$authId', post_time='$post_date', pay_amount='$copay_val', modified_time=now(),memo='Feesheet COPAY' WHERE account_code='$account_code' AND session_id='$SID'");
	  }else{
	    sqlStatement("UPDATE ar_activity SET code='$cod', modifier='$mod', post_user='$authId', post_time=now(), pay_amount='$copay_val', modified_time=now(),memo='Feesheet COPAY' WHERE account_code='$account_code' AND session_id='$SID'");
	  }
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update8");
    }
  }
  
  public function fee_sheet_insert($data){
    if($this->valid($data[0])){
      list($credentials,$code_type,$cpt_code,$pID,$rendering_pro,$authId,$group,$authorized,$encounterID,$code_text,$billed,$activity,$payer_id,
        $bill_process,$bill_date,$process_date,$process_file,$cpt_mod,$cpt_units,$cpt_totalfee,$cpt_justify,$target,$x12_partner_id,$ndc_info,$is_capitation,$notecodes) = $data;
      sqlStatement("INSERT INTO billing (date,code_type,code,pid,provider_id,user,groupname,authorized,encounter, ".
        " code_text,billed,activity,payer_id, bill_process,bill_date,process_date,process_file,modifier,units,fee,justify,target,x12_partner_id,ndc_info,is_capitation,notecodes) ".
        " values (now(),'$code_type','$cpt_code','$pID','$rendering_pro','$authId','$group',$authorized,'$encounterID', ".
        " '$code_text','$billed','$activity','$payer_id','$bill_process','$bill_date','$process_date','$process_file','$cpt_mod','$cpt_units','$cpt_totalfee', ".
        " '$cpt_justify','$target','$x12_partner_id','$ndc_info','$is_capitation','$notecodes')");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert");
    }
  }
  
  public function fee_sheet_insert1($data){
    if($this->valid($data[0])){
      list($credentials,$payerID,$authId,$reference,$check_date,$pay_total,$global_amount,$insurance,$capitation,$pID,$encounterID,$cod,$mod,$payer_type,$totalcharge,$account_code) = $data;
      $session_id=idSqlStatement("INSERT INTO ar_session (payer_id,user_id,reference,check_date,deposit_date,pay_total,".
        " global_amount,payment_type,description,adjustment_code,patient_id,payment_method,post_to_date) ".
        " VALUES('$payerID','$authId','$reference','$check_date',now(),'$pay_total',".
        " '$global_amount','$insurance','$capitation','$capitation','$pID','$capitation',now())"
      );
      sqlStatement("INSERT INTO ar_activity (pid,encounter,code,modifier,payer_type,post_time,post_user,session_id,adj_amount,account_code,memo)".
        " VALUES ('$pID','$encounterID','$cod','$mod','$payer_type',now(),'$authId','$session_id','$totalcharge','$account_code','Feesheet COPAY')"
      );
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert1");
    }
  }
  
  public function fee_sheet_insert2($data){
    if($this->valid($data[0])){
      list($credentials,$code_type,$cpt_code,$pID,$rendering_pro,$authId,$group,$authorized,$encounterID,$code_text,$billed,$activity,$payer_id,
        $bill_process,$bill_date,$process_date,$process_file,$cpt_mod,$cpt_units,$cpt_totalfee,$cpt_justify,$target,$x12_partner_id,$ndc_info,$auth_id_val,$for_advanced_cpt,$notecodes) = $data;
      sqlStatement("INSERT INTO billing (date,code_type,code,pid,provider_id,user,groupname,authorized,encounter, ".
        " code_text,billed,activity,payer_id, bill_process,bill_date,process_date,process_file,modifier,units,fee,justify,target,x12_partner_id,ndc_info,auth_id,for_advanced_cpt,notecodes) ".
        " values (now(),'$code_type','$cpt_code','$pID','$rendering_pro','$authId','$group',$authorized,'$encounterID', ".
        " '$code_text','$billed','$activity','$payer_id', '$bill_process','$bill_date','$process_date','$process_file','$cpt_mod','$cpt_units','$cpt_totalfee', ".
        " '$cpt_justify','$target','$x12_partner_id','$ndc_info','$auth_id_val','$for_advanced_cpt','$notecodes')");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert2");
    }
  }
  
  public function fee_sheet_insert3($data){
    if($this->valid($data[0])){
      list($credentials,$date,$reason,$pc_catid,$facility_id,$billing_facility,$sensitivity,
      $pID,$encounter_noshow,$last_level_billed,$last_level_closed,$assignment_value,$provider_id) = $data;
      return sqlInsert("INSERT INTO form_encounter SET " .
        "date = '$date', " .
        "reason = '$reason', " .
        "pc_catid = '$pc_catid', " .
        "facility_id = '$facility_id', " .
        "billing_facility = '$billing_facility', " .
        "sensitivity = '$sensitivity', " .
        "pid = '$pID', " .
        "encounter = '$encounter_noshow', " .
        "last_level_billed = '$last_level_billed', " .
        "last_level_closed = '$last_level_closed', " .
        "assignment = '$assignment_value', " .
        "provider_id = '$provider_id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert3");
    }
  }
  
  public function fee_sheet_insert4($data){
    if($this->valid($data[0])){
      list($credentials,$payerID,$authId,$reference,$check_date,$post_date,$pay_total,$global_amount,$insurance,$capitation,$pID,$payment_method,$adj_code,$billFac) = $data;
      if($post_date){
		return idSqlStatement("INSERT INTO ar_session (payer_id,user_id,reference,check_date,deposit_date,pay_total,".
		  " global_amount,payment_type,description,patient_id,payment_method,adjustment_code,post_to_date,cap_bill_facId) ".
		  " VALUES('$payerID','$authId','$reference','$check_date',now(),'$pay_total',".
		  " '$global_amount','$insurance','$capitation','$pID','$payment_method','$adj_code','$post_date','$billFac')"
		);
	  }else{
	    return idSqlStatement("INSERT INTO ar_session (payer_id,user_id,reference,check_date,deposit_date,pay_total,".
		  " global_amount,payment_type,description,patient_id,payment_method,adjustment_code,post_to_date,cap_bill_facId) ".
		  " VALUES('$payerID','$authId','$reference','$check_date',now(),'$pay_total',".
		  " '$global_amount','$insurance','$capitation','$pID','$payment_method','$adj_code',now(),'$billFac')"
		);
	  }
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert4");
    }
  }
  
  public function fee_sheet_insert5($data){
    if($this->valid($data[0])){
      list($credentials,$pID,$encounterID,$code,$modifier,$payer_type,$post_date,$authId,$session_id,$pay_amount,$account_code) = $data;
      if($post_date){
	    return idSqlStatement("INSERT INTO ar_activity (pid,encounter,code,modifier,payer_type,post_time,post_user,session_id,pay_amount,account_code,memo)".
          " VALUES ('$pID','$encounterID','$code','$modifier','$payer_type','$post_date','$authId','$session_id','$pay_amount','$account_code','Feesheet COPAY')"
        );
	  }else{
	    return idSqlStatement("INSERT INTO ar_activity (pid,encounter,code,modifier,payer_type,post_time,post_user,session_id,pay_amount,account_code,memo)".
          " VALUES ('$pID','$encounterID','$code','$modifier','$payer_type',now(),'$authId','$session_id','$pay_amount','$account_code','Feesheet COPAY')"
        );
	  }
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert5");
    }
  }
  
  public function fee_sheet_insert6($data){
    if($this->valid($data[0])){
      list($credentials,$date,$onset_date,$reason,$pc_catid,$facility_id,$billing_facility,$sensitivity,$pid,$encounter,$provider_id) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("INSERT INTO form_encounter SET " .
      "date = '$date', " .
      "onset_date = '$onset_date', " .
      "reason = '$reason', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "sensitivity = '$sensitivity', " .
      "pid = '$pid', " .
      "encounter = '$encounter', " .
      "provider_id = '$provider_id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert6");
    }
  }
  
  public function fee_sheet_insert7($data){
    if($this->valid($data[0])){
      list($credentials,$date,$onset_date,$reason,$pc_catid,$facility_id,$billing_facility,$sensitivity,$pid,$encounter,$provider_id,$enc_provider_id) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("INSERT INTO form_encounter SET " .
      "date = '$date', " .
      "onset_date = '$onset_date', " .
      "reason = '$reason', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "sensitivity = '$sensitivity', " .
      "pid = '$pid', " .
      "encounter = '$encounter', " .
      "provider_id = '$provider_id', " .
      "encounter_provideID = '$enc_provider_id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_insert7");
    }
  }
  
  public function fee_sheet_delete($data){
    if($this->valid($data[0])){
      list($credentials,$pid,$encounter,$account_code,$session_id) = $data;
      sqlStatement("DELETE FROM ar_activity WHERE pid='$pid' AND encounter='$encounter' AND account_code='$account_code' AND session_id='$session_id'");
      sqlStatement("DELETE FROM ar_session WHERE session_id='$session_id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_delete");
    }
  }
  
  public function process_flow_log($data){
    if($this->valid($data[0])){
      list($credentials,$pfl_flow_id,$pfl_pid,$pfl_encounter,$pfl_table_id,$pfl_table_item_id,$pfl_emp_id,$pfl_flow_level,$pfl_approval_status,$pfm_id,$pfl_flow_id) = $data;
      sqlStatement("insert into process_flow_log(pfl_flow_id,pfl_pid,pfl_encounter,pfl_table_id,
        pfl_table_item_id,pfl_emp_id,pfl_flow_level,pfl_approval_status) values('$pfl_flow_id','$pfl_pid','$pfl_encounter','$pfl_table_id',
        '$pfl_table_item_id','$pfl_emp_id','$pfl_flow_level','$pfl_approval_status')");
      sqlStatement("update process_flow_master set pfm_used_status='$pfm_id' where pfm_id='$pfl_flow_id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in process_flow_log");
    }
  }
  
  public function process_flow_log_at_entry($data){
    if($this->valid($data[0])){
      list($credentials,$pfl_approval_status,$pfl_flow_id,$pfl_pid,$pfl_encounter,$pfl_table_id,$authUserID,$pfl_approval_status) = $data;
      sqlStatement("update process_flow_log set pfl_approval_status='$pfl_approval_status' WHERE pfl_flow_id='$pfl_flow_id' AND pfl_pid='$pfl_pid' AND pfl_encounter='$pfl_encounter' 
        AND pfl_table_id='$pfl_table_id' and pfl_emp_id='$authUserID' and pfl_approval_status='$pfl_approval_status'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in process_flow_log_at_entry");
    }
  }
  
  public function import_template($data){
    if($this->valid($data[0])){
      list($credentials,$provider_id,$facility_id,$tu_template_id) = $data;
      sqlInsert("REPLACE INTO template_users (tu_user_id,tu_facility_id,tu_template_id) VALUES ('".$provider_id."','".$facility_id."','".$tu_template_id."')");
    }
    else{
      throw new SoapFault("Server", "credentials failed in import_template");
    }
  }
  
  public function ajax_code_insert($data){
    if($this->valid($data[0])){
      list($credentials,$source_type,$code,$name) = $data;
      return sqlInsert("INSERT INTO customlists (cl_list_type,cl_list_item_short,cl_list_item_long) VALUES ('".$source_type."','".$code."','".$name."')");
    }
    else{
      throw new SoapFault("Server", "credentials failed in ajax_code_insert");
    }
  }
  
  public function ajax_code_insert2($data){
    if($this->valid($data[0])){
      list($credentials,$provider_id,$facility_id,$insertid) = $data;
      sqlInsert("INSERT INTO template_users (tu_user_id,tu_facility_id,tu_template_id) VALUES ('".$provider_id."','".$facility_id."','".$insertid."')");
    }
    else{
      throw new SoapFault("Server", "credentials failed in ajax_code_insert2");
    }
  }
  
  public function ajax_code_insert3($data){
    if($this->valid($data[0])){
      list($credentials,$price,$id) = $data;
      sqlStatement("INSERT INTO prices SET pr_price='$price' ,pr_level='standard', pr_id='$id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in ajax_code_insert3");
    }
  }
  
  public function ajax_code_update($data){
    if($this->valid($data[0])){
      list($credentials,$price,$id) = $data;
      sqlStatement("UPDATE prices SET pr_price='$price' WHERE pr_id='$id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in ajax_code_update");
    } 
  }
  
  public function delete_template_delete($data){
    if($this->valid($data[0])){
      list($credentials,$tu_id) = $data;
      sqlQuery("DELETE FROM template_users WHERE tu_id='".$tu_id."'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in delete_template_delete");
    }
  }
  
  public function delete_template_delete2($data){
    if($this->valid($data[0])){
      list($credentials,$cl_list_slno) = $data;
      sqlQuery("DELETE FROM customlists WHERE cl_list_slno='".$cl_list_slno."'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in delete_template_delete2");
    }
  }
  
  public function authorization_update($data){
    if($this->valid($data[0])){
      list($credentials,$pid,$auth_form_no,$auth_is_active) = $data;
      sqlStatement("UPDATE authorization SET auth_is_active=0 WHERE auth_pid='$pid' AND auth_form_no='$auth_form_no' AND auth_is_active='$auth_is_active'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in authorization_update");
    }
  }
  
  public function authorization_update1($data){
    if($this->valid($data[0])){
      list($credentials,$ins_id,$id) = $data;
      sqlStatement("UPDATE billing SET auth_id = '$ins_id' WHERE id = '$id'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in authorization_update1");
    }
  }
  
  public function authorization_update2($data){
    if($this->valid($data[0])){
      list($credentials,$ins_id) = $data;
      sqlStatement("UPDATE authorization SET auth_no_of_visits = auth_no_of_visits - 1 WHERE auth_id = '$ins_id' AND auth_visit_type = 'visits' AND auth_unlimited = 'n'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in authorization_update2");
    }
  }
  
  public function authorization_update3($data){
    if($this->valid($data[0])){
      list($credentials,$auth_required,$id) = $data;
      sqlStatement("UPDATE insurance_data SET auth_required = '$auth_required' WHERE id = '$id' AND (auth_required != '$auth_required' OR auth_required IS NULL)");
    }
    else{
      throw new SoapFault("Server", "credentials failed in authorization_update2");
    }
  }
  
  public function authorization_insert($data){
    if($this->valid($data[0])){
      list($credentials,$pid,$auth_ins_id,$auth_ins_data_id,$auth_from,$auth_to,$auth_no_of_visits,$auth_note,$auth_cpt,$auth_no,$auth_form_no,
        $auth_author,$auth_version,$auth_visit_type,$auth_tot_no_of_visits,$auth_unlimited) = $data;      
      return sqlInsert("insert into authorization(auth_pid,auth_ins_id,auth_ins_data_id,auth_from,auth_to,auth_no_of_visits,auth_note,auth_cpt,auth_no,auth_form_no,
        auth_author,auth_version,auth_visit_type,auth_tot_no_of_visits,auth_unlimited)
				values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        array($pid,$auth_ins_id,$auth_ins_data_id,$auth_from,$auth_to,$auth_no_of_visits,$auth_note,$auth_cpt,$auth_no,$auth_form_no,
        $auth_author,$auth_version,$auth_visit_type,$auth_tot_no_of_visits,$auth_unlimited));
    }
    else{
      throw new SoapFault("Server", "credentials failed in authorization_insert");
    }
  }
  
  public function capitation_payment_dropdown($data){
    if($this->valid($data[0])){
      list(,$selected) = $data;
      require_once dirname(__FILE__)."/../../library/options.inc.php";
      return generate_select_list('Payment_Frequency','payment_frequency',$selected,'');
    }
    else{
      throw new SoapFault("Server", "credentials failed in capitation_payment_dropdown");
    }
  }
  
  public function capitation_queries($data){
    if($this->valid($data[0])){
      $val = $data[1];
      switch($val){
        case "C1":
          list(,,$insurance,$billing_facility,$cm_activity,$value) = $data;
          return sqlNumRows(sqlStatement("SELECT * FROM capitation_master WHERE cm_insid=? AND cm_billing_facId=? AND cm_activity=? AND cm_provider=?",
            array($insurance,$billing_facility,$cm_activity,$value)));
          break;
        case "C2":
          list(,,$insurance,$billing_facility,$from_date,$to_date,$payment_frequency,$value) = $data;
          $qry = "REPLACE INTO capitation_master (cm_insid,cm_billing_facId,cm_start_date,cm_end_date,cm_payment_frequency,cm_provider) VALUES (?,?,?,?,?,?)";
          sqlStatement($qry,array($insurance,$billing_facility,$from_date,$to_date,$payment_frequency,$value));
          break;
        case "C3":
          list(,,$value) = $data;
          $userarr = sqlQuery("SELECT fname FROM users WHERE id=?",array($value));
          return $userarr['fname'];
          break;
        case "C4":
          $returnval = array();
          $sql = "select insurance_companies.name, insurance_companies.id,city,state,country,line1 from insurance_companies left join addresses " .
            " on addresses.foreign_id = insurance_companies.id " .
            " order by insurance_companies.name, insurance_companies.id";
          $rez = sqlStatement($sql);
          for($iter=0; $row=sqlFetchArray($rez); $iter++) {
            $returnval[$row['id']] = $row['name'].' **('. $row['line1'] .', '.$row['city'].', '.$row['state'].', '.$row['country'].')**';
          }
          return $returnval;
          break;
        case "C5":
          list(,,$name,$select,$class,$all) = $data;
          $qsql = sqlStatement("SELECT id, name FROM facility WHERE billing_location = 1");
          $out = "<select id='".htmlspecialchars($name, ENT_QUOTES)."' name='".htmlspecialchars($name, ENT_QUOTES)."' class=".$class.">";          
          while ($facrow = sqlFetchArray($qsql)) {
            $selected = ( $facrow['id'] == $select ) ? 'selected="selected"' : '' ;
            $out .= "<option value=".htmlspecialchars($facrow['id'],ENT_QUOTES)." $selected>".htmlspecialchars($facrow['name'], ENT_QUOTES)."</option>";
          }
          $out .= "</select>";
          return $out;
          break;
        case "C6":
          $query = "SELECT id, lname, fname FROM users WHERE authorized = 1 AND username != '' AND username NOT LIKE '%Admin%' " .
            "AND active = 1 AND ( info IS NULL OR info NOT LIKE '%Inactive%' ) ORDER BY lname, fname";
          $res = sqlStatement($query);
          while($row=sqlFetchArray($res)){
            $out .= "<option value='".$row['id']."' selected>" . $row['lname'] . ", " . $row['fname'] ."</option>";
          }
          return $out;
          break;
        case "C7":
          $qry =sqlStatement("SELECT * FROM capitation_master WHERE cm_insid=? AND cm_billing_facId=? AND cm_activity=? AND cm_provider=?",array($ins,$bill_fac,1,$arr[$i]));
          return sqlNumRows($qry);
          break;
        case "C8":
          list(,,$capid) = $data;
          return sqlQuery("SELECT * FROM capitation_master WHERE cm_id=?",array($capid));
          break;
        case "C9":
          list(,,$where,$cm_provider,$cm_billing_facId,$cm_insid,$type,$startdate) = $data;
          $qry = "SELECT * FROM form_encounter AS fe LEFT OUTER JOIN insurance_data AS id ON id.pid=fe.pid WHERE
            encounter_provideID=? AND billing_facility=? AND id.provider=? AND id.type=? AND fe.date>=? ".$where." GROUP BY fe.encounter";
          return sqlNumRows(sqlStatement($qry,array($cm_provider,$cm_billing_facId,$cm_insid,$type,$startdate)));
          break;
        case "C10":
          list(,,$provider) = $data;
          $query = "SELECT id, lname, fname FROM users WHERE authorized = 1 AND username != '' AND username NOT LIKE '%Admin%' " .
            "AND active = 1 AND ( info IS NULL OR info NOT LIKE '%Inactive%' ) ORDER BY lname, fname";
          $res = sqlStatement($query);
          while($row=sqlFetchArray($res)){
            $selected = ($row['id']==$provider) ? 'selected' : '';
            $out .= "<option value='".$row['id']."' $selected>" . $row['lname'] . ", " . $row['fname'] ."</option>";
          }
          return $out;
          break;
        case "C11":
          list(,,$where) = $data;
          $res_out = array();
          $qry = "SELECT cm.*,ic.name,CONCAT(u.fname,' ',u.mname,' ',u.lname) AS provider,f.name AS facility FROM capitation_master AS cm
            LEFT OUTER JOIN insurance_companies AS ic ON cm.cm_insid=ic.id LEFT OUTER JOIN users AS u ON cm.cm_provider=u.id
            LEFT OUTER JOIN facility AS f ON cm.cm_billing_facId=f.id WHERE cm_activity=1 $where order by ic.name,provider";
          $res = sqlStatement($qry);
          while($row=sqlFetchArray($res)){
            $res_out[] = $row;
          }
          return $res_out;
          break;
        case "C12":
          list(,,$insurance,$billing_facility,$from_date,$to_date,$payment_frequency,$editid) = $data;
          $qry = "UPDATE capitation_master SET cm_insid=?,cm_billing_facId=?,cm_start_date=?,cm_end_date=?,cm_payment_frequency=? WHERE cm_id=?";
          sqlStatement($qry,array($insurance,$billing_facility,$from_date,$to_date,$payment_frequency,$editid));
          break;
        case "C13":
          list(,,$editid) = $data;
          $qry = "SELECT * FROM capitation_master WHERE cm_id=?";
          return sqlQuery($qry,array($editid));
          break;
        case "C14":
          list(,,$capid) = $data;
          $capitation = sqlQuery("SELECT * FROM capitation_master WHERE cm_id=?",array($capid));
          $where = ($capitation['cm_end_date']!='0000-00-00') ? " AND fe.date<='".$capitation['cm_end_date']." 00:00:00'" : "";
          $startdate = $capitation['cm_start_date']." 00:00:00";
          $qry = "SELECT * FROM form_encounter AS fe LEFT OUTER JOIN insurance_data AS id ON id.pid=fe.pid WHERE
            encounter_provideID=? AND billing_facility=? AND id.provider=? AND id.type=? AND fe.date>=? ".$where." GROUP BY fe.encounter";
          return sqlNumRows(sqlStatement($qry,array($capitation['cm_provider'],$capitation['cm_billing_facId'],$capitation['cm_insid'],'primary',$startdate)));
          break;
        case "C15":
          list(,,$cm_billing_facId) = $data;
          return sqlQuery("SELECT * FROM facility WHERE id=?",array($cm_billing_facId));
          break;
        case "C16":
          list(,,$where,$cm_provider,$cm_billing_facId,$cm_insid,$type,$startdate) = $data;
          $qry = "SELECT * FROM form_encounter AS fe LEFT OUTER JOIN insurance_data AS id ON id.pid=fe.pid WHERE
            provider_id=? AND billing_facility=? AND id.provider=? AND id.type=? AND fe.date>=? ".$where." GROUP BY fe.encounter";
          return sqlNumRows(sqlStatement($qry,array($cm_provider,$cm_billing_facId,$cm_insid,$type,$startdate)));
          break;
        case "C17":
          list(,,$capid) = $data;
          $capitation = sqlQuery("SELECT * FROM capitation_master WHERE cm_id=?",array($capid));
          $where = ($capitation['cm_end_date']!='0000-00-00') ? " AND fe.date<='".$capitation['cm_end_date']." 00:00:00'" : "";
          $startdate = $capitation['cm_start_date']." 00:00:00";
          $qry = "SELECT * FROM form_encounter AS fe LEFT OUTER JOIN insurance_data AS id ON id.pid=fe.pid WHERE
            provider_id=? AND billing_facility=? AND id.provider=? AND id.type=? AND fe.date>=? ".$where." GROUP BY fe.encounter";
          return sqlNumRows(sqlStatement($qry,array($capitation['cm_provider'],$capitation['cm_billing_facId'],$capitation['cm_insid'],'primary',$startdate)));
          break;
      }
    }
    else{
      throw new SoapFault("Server", "credentials failed in capitation_queries");
    }
  }
  
  public function capitation_update($data){
    if($this->valid($data[0])){
      list(,$capid) = $data;
      sqlStatement("UPDATE capitation_master SET cm_activity=? WHERE cm_id=?",array(0,$capid));
    }
    else{
      throw new SoapFault("Server", "credentials failed in capitation_update");
    }
  }
  
  public function feesheet_acl_get_sensitivities($data){
    if($this->valid($data[0])){
      require_once dirname(__FILE__)."/../../library/acl.inc";
      return acl_get_sensitivities();
    }
    else{
      throw new SoapFault("Server", "credentials failed in feesheet_acl_get_sensitivities");
    }
  }
  
  public function feesheet_gen_id($data){
    if($this->valid($data[0])){
      require_once dirname(__FILE__)."/../../library/sql.inc";
      return $GLOBALS['adodb']['db']->GenID($data[1]);
    }
    else{
      throw new SoapFault("Server", "credentials failed in feesheet_gen_id");
    }
  }
  
  public function feesheet_addform($data){
    if($this->valid($data[0])){
      list(,$encounter, $form_name, $form_id, $formdir, $pid, $authorized , $date, $user, $group) = $data;
      require_once dirname(__FILE__)."/../../library/forms.inc";
      return addForm($encounter, $form_name, $form_id, $formdir, $pid, $authorized , $date, $user, $group);
    }
    else{
      throw new SoapFault("Server", "credentials failed in feesheet_addform");
    }
  }
  
  public function billers_fee_sheet_insert($data){
    if($this->valid($data[0])){
      list($credentials,$date,$onset_date,$pc_catid,$facility_id,$billing_facility,$pid,$encounter,$provider_id,$supervisor_id,$assignment_value,$pos_code,$enc_provider_id) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("INSERT INTO form_encounter SET " .
      "date = '$date', " .
      "onset_date = '$onset_date', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "pid = '$pid', " .
      "encounter = '$encounter', " .
      "provider_id = '$provider_id', " .
      "supervisor_id = '$supervisor_id', " .
      "encounter_provideID = '$enc_provider_id', ".
      "assignment = '$assignment_value', " .
      "pos_code = '$pos_code'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_insert");
    }
  }
  
  public function billers_fee_sheet_insert2($data){
    if($this->valid($data[0])){
      list($credentials,$date,$onset_date,$pc_catid,$facility_id,$billing_facility,$pid,$encounter,$provider_id,$supervisor_id,$assignment_value,$pos_code) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("INSERT INTO form_encounter SET " .
      "date = '$date', " .
      "onset_date = '$onset_date', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "pid = '$pid', " .
      "encounter = '$encounter', " .
      "provider_id = '$provider_id', " .
      "supervisor_id = '$supervisor_id', " .
      "assignment = '$assignment_value', " .
      "pos_code = '$pos_code'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_insert2");
    }
  }
  
  public function billers_fee_sheet_insert3($data){
    if($this->valid($data[0])){
      list($credentials,$date,$pc_catid,$facility_id,$billing_facility,$pID,
      $encounter_noshow,$last_level_billed,$last_level_closed,$provider_id,$assignment_value) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("INSERT INTO form_encounter SET " .
        "date = '$date', " .
        "facility = '$facility', " .
        "pc_catid = '$pc_catid', " .
        "facility_id = '$facility_id', " .
        "billing_facility = '$billing_facility', " .
        "pid = '$pID', " .
        "encounter = '$encounter_noshow', " .
        "last_level_billed = '$last_level_billed', " .
        "last_level_closed = '$last_level_closed', " .
        "provider_id = '$provider_id', " .
        "assignment = '$assignment_value'" 
        );
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_insert3");
    }
  }
  
  public function billers_fee_sheet_insert4($data){
    if($this->valid($data[0])){
      list($credentials,$code_type,$cpt_code,$pID,$rendering_pro,$authId,$group,$authorized,$encounterID,$code_text,$billed,$activity,$payer_id,
        $bill_process,$bill_date,$process_date,$process_file,$cpt_mod,$cpt_units,$cpt_totalfee,$cpt_justify,$target,$x12_partner_id,$ndc_info,$is_capitation,$notecodes) = $data;
      if($code_type == 'ICD9'){
        $cnt = sqlNumRows(sqlStatement("SELECT * FROM billing WHERE pid = '$pID' AND encounter = '$encounterID' AND code_type = '$code_type' AND code = '$cpt_code' AND activity = '1'"));
      }else{
        $cnt = sqlNumRows(sqlStatement("SELECT * FROM billing WHERE pid = '$pID' AND encounter = '$encounterID' AND code_type = '$code_type' AND code = '$cpt_code' AND modifier = '$cpt_mod' AND activity = '1'"));
      }
      if($cnt == 0){
        sqlStatement("INSERT INTO billing (date,code_type,code,pid,provider_id,user,groupname,authorized,encounter, ".
          " code_text,billed,activity,payer_id, bill_process,bill_date,process_date,process_file,modifier,units,fee,justify,target,x12_partner_id,ndc_info,is_capitation,notecodes) ".
          " values (now(),'$code_type','$cpt_code','$pID','$rendering_pro','$authId','$group',$authorized,'$encounterID', ".
          " '$code_text','$billed','$activity','$payer_id','$bill_process','$bill_date','$process_date','$process_file','$cpt_mod','$cpt_units','$cpt_totalfee', ".
          " '$cpt_justify','$target','$x12_partner_id','$ndc_info','$is_capitation','$notecodes')");
      }
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_insert4");
    }
  }
  
  public function billers_fee_sheet_insert5($data){
    if($this->valid($data[0])){
      list($credentials,$code_type,$cpt_code,$pID,$rendering_pro,$authId,$group,$authorized,$encounterID,$code_text,$billed,$activity,$payer_id,
        $bill_process,$bill_date,$process_date,$process_file,$cpt_mod,$cpt_units,$cpt_totalfee,$cpt_justify,$target,$x12_partner_id,$ndc_info,$auth_id_val,$for_advanced_cpt,$notecodes) = $data;
       $cnt = sqlNumRows(sqlStatement("SELECT * FROM billing WHERE pid = '$pID' AND encounter = '$encounterID' AND code_type = '$code_type' AND code = '$cpt_code' AND activity = '1'"));
      if($cnt == 0){
        sqlStatement("INSERT INTO billing (date,code_type,code,pid,provider_id,user,groupname,authorized,encounter, ".
          " code_text,billed,activity,payer_id, bill_process,bill_date,process_date,process_file,modifier,units,fee,justify,target,x12_partner_id,ndc_info,auth_id,for_advanced_cpt,notecodes) ".
          " values (now(),'$code_type','$cpt_code','$pID','$rendering_pro','$authId','$group',$authorized,'$encounterID', ".
          " '$code_text','$billed','$activity','$payer_id', '$bill_process','$bill_date','$process_date','$process_file','$cpt_mod','$cpt_units','$cpt_totalfee', ".
          " '$cpt_justify','$target','$x12_partner_id','$ndc_info','$auth_id_val','$for_advanced_cpt','$notecodes')");
      }
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_insert5");
    }
  }
  
  public function billers_save_misc_bill($data){
    if($this->valid($data[0])){
      list($credentials,$pid,$encounter,$group,$authId,$auth_no,$off_work_from,$off_work_to,$hospitalization_date_from,$hospitalization_date_to,
        $medicaid_resubmission_code,$medicaid_original_reference,$outside_lab,$lab_amount,$employment_related,$auto_accident,
        $accident_state,$other_accident,$replacement_claim,$comments) = $data;
      $res = sqlQuery("SELECT form_id FROM forms,form_misc_billing_options as misc where encounter='".$encounter."' and form_id=misc.id and misc.pid='".$pid."'");
      $user = sqlQuery("SELECT username FROM users WHERE id='".$authId."'");
      $authUser = $user['username'];
      if($res['form_id'] == ""){
        $mode = "new";
      }else{
        $mode = "update";
        $formid = $res['form_id'];
      }
      if($off_work_from == "0000-00-00" || $off_work_from == ""){
        $is_unable_to_work = "0";
        $off_work_to = "";
      }else{
        $is_unable_to_work = "1";
      }
      if($hospitalization_date_from == "0000-00-00" || $hospitalization_date_from == ""){
        $is_hospitalized = "0";
        $hospitalization_date_to = "";
      }else{
        $is_hospitalized = "1";
      }
      if($encounter == "")
        $encounter = date("Ymd");
      if($employment_related !='' || $auto_accident !='' || $accident_state !='' || $other_accident !='' || 
        $outside_lab !='' || $lab_amount !='' || $off_work_from !='' || $off_work_to !='' ||
        $hospitalization_date_from !='' || $hospitalization_date_to !='' || $medicaid_resubmission_code !='' || 
        $medicaid_original_reference !='' || $auth_no !='' || $comments !='' || $replacement_claim !='')
      {
        if ($mode == "new"){
          $newid=sqlInsert("INSERT INTO form_misc_billing_options (date,pid,user,groupname,authorized,activity,employment_related, ".
                        "auto_accident,accident_state,other_accident,outside_lab,lab_amount,is_unable_to_work,off_work_from, ".
                        "off_work_to,is_hospitalized,hospitalization_date_from,hospitalization_date_to,medicaid_resubmission_code, ".
                        "medicaid_original_reference,prior_auth_number,comments,replacement_claim) VALUES ( ".
                        "now(),'".$pid."','".$authUser."','".$group."',1,1,'".$employment_related."', ".
                        "'".$auto_accident."','".$accident_state."','".$other_accident."','".$outside_lab."','".$lab_amount."','".$is_unable_to_work."','".$off_work_from."', ".
                        "'".$off_work_to."','".$is_hospitalized."','".$hospitalization_date_from."','".$hospitalization_date_to."','".$medicaid_resubmission_code."', ".
                        "'".$medicaid_original_reference."','".$auth_no."','".$comments."','".$replacement_claim."' )");
          addForm($encounter, "Misc Billing Options", $newid, "misc_billing_options", $pid, 1);
        }elseif( $mode == "update") {
          sqlInsert("update form_misc_billing_options set pid = '".$pid."',
            groupname='".$group."',
            user='".$authUser."',
            authorized=1,activity=1, date = NOW(),
            employment_related='".$employment_related."',
            auto_accident='".$auto_accident."',
            accident_state='".$accident_state."',
            other_accident='".$other_accident."',
            outside_lab='".$outside_lab."',
            lab_amount='".$lab_amount."',
            is_unable_to_work='".$is_unable_to_work."',
            off_work_from='".$off_work_from."',
            off_work_to='".$off_work_to."',
            is_hospitalized='".$is_hospitalized."',
            hospitalization_date_from='".$hospitalization_date_from."',
            hospitalization_date_to='".$hospitalization_date_to."',
            medicaid_resubmission_code='".$medicaid_resubmission_code."',
            medicaid_original_reference='".$medicaid_original_reference."',
            prior_auth_number='".$auth_no."',
            replacement_claim='".$replacement_claim."',
            comments='".$comments."'
            where id='".$formid."' ");
        }
      }
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_save_misc_bill");
    } 
  }
  
  public function billers_fee_sheet_update($data){
    if($this->valid($data[0])){
      list($credentials,$billed,$closed,$encounterID,$pID) = $data;
      sqlStatement("UPDATE form_encounter SET last_level_billed='$billed', last_level_closed='$closed' WHERE encounter='$encounterID' AND pid='$pID'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in fee_sheet_update1");
    }
  }
  
  public function billers_fee_sheet_update2($data){
    if($this->valid($data[0])){
      list($credentials,$onset_date,$pc_catid,$facility_id,$billing_facility,$pid,$encounter,$provider_id,$supervisor_id,$assignment_value,$pos_code,$enc_provider_id) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("UPDATE form_encounter SET " .
      "onset_date = '$onset_date', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "provider_id = '$provider_id', " .
      "supervisor_id = '$supervisor_id', " .
      "encounter_provideID = '$enc_provider_id', ".
      "assignment = '$assignment_value', " .
      "pos_code = '$pos_code'" .
      "WHERE pid = '$pid' " .
      "AND encounter = '$encounter'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_update2");
    }
  }
  
  public function billers_fee_sheet_update3($data){
    if($this->valid($data[0])){
      list($credentials,$onset_date,$pc_catid,$facility_id,$billing_facility,$pid,$encounter,$provider_id,$supervisor_id,$assignment_value,$pos_code) = $data;
      $facilityresult = sqlQuery("select name FROM facility WHERE id = $facility_id");
      $facility = $facilityresult['name'];
      return sqlInsert("UPDATE form_encounter SET " .
      "onset_date = '$onset_date', " .
      "facility = '$facility', " .
      "pc_catid = '$pc_catid', " .
      "facility_id = '$facility_id', " .
      "billing_facility = '$billing_facility', " .
      "provider_id = '$provider_id', " .
      "supervisor_id = '$supervisor_id', " .
      "assignment = '$assignment_value', " .
      "pos_code = '$pos_code'" .
      "WHERE pid = '$pid' " .
      "AND encounter = '$encounter'");
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_update3");
    }
  }
  
  public function billers_fee_sheet_update4($data){
    if($this->valid($data[0])){
      list($credentials,$pID,$encounterID) = $data;
      sqlStatement("UPDATE billing SET activity=0 WHERE pid='$pID' AND encounter='$encounterID' AND code_type IN('CPT4','HCPCS','ICD9','NOSHOW') AND billed = 0 AND activity = 1");
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_update4");
    }
  }
  
  public function billers_fee_sheet_history($data){
    if($this->valid($data[0])){
      list($credentials,$pID) = $data;
      require_once(dirname(__FILE__)."/../../library/sl_eob.inc.php");
      $query="
        SELECT 
          f.id AS fid,
          DATE(f.date) AS dos,
          p.pid,
          f.encounter,
          DATE(f.onset_date) AS onset_date,
          CONCAT(p.fname,' ',p.mname,' ',p.lname) AS pname,
          u.fname AS bpfname,
          u.lname AS bplname,
          u2.fname AS epfname,
          u2.lname AS eplname,
          u3.fname AS spfname,
          u3.lname AS splname,
          u4.fname AS rpfname,
          u4.lname AS rplname,
          fa.name AS facility,
          fa2.name AS bfacility,
          f.pos_code,
          b.id AS bid,
          b.code_type,
          b.code,
          b.billed,
          b.payer_id,
          b.modifier,
          b.units,
          b.fee,
          b.justify,
          b.auth_id,
          p.providerID AS ref_provider,
          aa.pay_amount,
          aa.session_id,
          f.assignment,
          b.billed,
          fm.prior_auth_number,
          fm.off_work_from,
          fm.off_work_to,
          fm.hospitalization_date_from,
          fm.hospitalization_date_to,
          fm.medicaid_resubmission_code,
          fm.medicaid_original_reference,
          fm.outside_lab,
          fm.lab_amount,
          fm.employment_related,
          fm.auto_accident,
          fm.accident_state,
          fm.other_accident,
          fm.replacement_claim,
          fm.comments,
          f.last_level_billed,
          f.last_level_closed
        FROM
          patient_data AS p
          LEFT JOIN form_encounter AS f
            ON f.pid = p.pid
          LEFT JOIN users AS u 
            ON f.provider_id = u.id
          LEFT JOIN billing AS b 
            ON b.pid = p.pid 
            AND b.encounter = f.encounter
            AND b.code_type NOT IN ('ICD9','COPAY')
            AND b.activity = 1
          LEFT JOIN users AS u2 
            ON b.provider_id = u2.id
          LEFT JOIN users AS u3 
            ON f.supervisor_id = u3.id
          LEFT JOIN users AS u4 
            ON p.providerID = u4.id
          LEFT JOIN facility AS fa
            ON f.facility_id = fa.id
          LEFT JOIN facility AS fa2
            ON f.billing_facility = fa2.id
          LEFT JOIN ar_activity AS aa 
            ON aa.pid = p.pid 
            AND aa.encounter = f.encounter 
            AND aa.account_code = 'PCP'
          LEFT JOIN forms AS fo
            ON fo.pid = p.pid
            AND fo.encounter = f.encounter
            AND fo.form_name = 'Misc Billing Options'
          LEFT JOIN form_misc_billing_options AS fm
            ON fm.id = fo.form_id
        WHERE p.pid = '$pID'
        ORDER BY f.date DESC,
          f.encounter DESC,
          f.billing_facility
      ";
      $res = sqlStatement($query);
      $cnt = 0;
      while($result = sqlFetchArray($res)){
        $arr[$cnt] = $result;
        $ins_id = arGetPayerID($pID,$result['dos'],$result['last_level_billed']);
        $ins_res = sqlQuery("SELECT name FROM insurance_companies WHERE id = '$ins_id'");
        $arr[$cnt]['insurance'] = $ins_res['name'];
        $cnt++;
      }
      return $arr;
    }
    else{
      throw new SoapFault("Server", "credentials failed in billers_fee_sheet_history");
    }
  }
  
}
?>