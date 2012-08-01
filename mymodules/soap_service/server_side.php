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
session_id($_GET['OpenEMR']);
//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//

global $ISSUE_TYPES,$sqlconf;
$ignoreAuth=true;
ob_start();
require_once(dirname(__FILE__)."/../../interface/globals.php");
require_once(dirname(__FILE__)."/server_eob.php");
require_once(dirname(__FILE__)."/server_fileParse.php");
require_once(dirname(__FILE__)."/server_emrFlowTrack.php");
require_once(dirname(__FILE__)."/server_payment.php");
require_once(dirname(__FILE__)."/server_billing.php");
$err = '';
//if(!extension_loaded("soap")){
//  dl("php_soap.dll");
//}
require_once(dirname(__FILE__)."/factory_class.php");
require_once(dirname(__FILE__)."/fee_sheet.php");
ob_end_clean();
class UserService Extends FeeSheet
{
    public function query_to_xml_result($data){//Accepts a select query string.It queries the database and returns the result in xml format.Format is as follows
	if($this->valid($data[0])){
	 $fields=$data[1];
	 $from = $data[2];
	 $fields=preg_replace("/(delete)*|(drop)*|(alter)*|(grant)*|(update)*|(insert)*|(show)*/","",$fields);
	 $fields=preg_replace("/(,[^\w]username[^\w],)/",",",$fields);
	 $fields=preg_replace("/(,[^\w]username[^\w])|([^\w]username[^\w],)|([^\w]username[^\w])/","",$fields);
	 $fields=preg_replace("/(,[^\w]password[^\w],)/",",",$fields);
	 $fields=preg_replace("/(,[^\w]password[^\w])|([^\w]password[^\w],)|([^\w]password[^\w])/","",$fields);
	 if(strtolower($fields)=='all')
         {
          $fields=' * ';
         }
	 $sql_query = sqlStatement("SELECT $fields FROM $from");
	 $resultset = array();
	 $i=0;
	 while($row = sqlFetchArray($sql_query))
	 {
	   
	   foreach($row as $key=>$value){
	    if(trim($key) != 'username' && trim($key) != 'password' && trim($key) != 'signature_image'){
	      if(!$value)
	      $value="";
	      $resultset[$i][$key]=$value;
	    }
	   }
	   $i++;
	 }
	 return $resultset;
	}
	else{
		throw new SoapFault("Server", "credentials failed in query_to_xml_result error message");
	}
    }
    static function ClaimErrormsg($pid,$encounter_date){
      $query = "SELECT pd.pubpid, pd.lname, pd.mname, pd.fname, pd.dob, " .
	  "pd.street, pd.city, pd.state, pd.postal_code, pd.phone_home, insd.provider AS primary_ins, " .
	  "insd.policy_number, insd.subscriber_lname, insd.subscriber_fname, insd.subscriber_sex, " . 
	  "insd.subscriber_dob, insd.subscriber_street, insd.subscriber_city, insd.subscriber_state, " .
	  "insd.subscriber_relationship, insd.subscriber_phone, insd.subscriber_postal_code, " .
	  "insd.date AS ins_effect_date, ic.name AS ins_name, ic.freeb_type, ic.x12_default_partner_id " .
	  "FROM (patient_data AS pd LEFT JOIN insurance_data AS insd ON insd.pid = pd.pid) " .
	  "LEFT JOIN insurance_companies AS ic ON insd.provider = ic.id " .
	  "WHERE pd.pid = '" . $pid . "' AND (insd.type is null OR insd.type = 'primary') " .
          "ORDER BY ins_effect_date DESC";
      $ptres = sqlQuery($query);
  
      global $patientErrMsg,$wc_label;
    
      $missingFieldErrMsg = 'Missing: ';
      $insDateErrMsg = '';
      if($ptres['freeb_type']==25)
	      $wc_label=$ptres['freeb_type'];
    
      if ($ptres['dob'] == null || strlen(trim($ptres['dob'])) == 0 ||
	  trim($ptres['dob']) == '0000-00-00')
      {
	$missingFieldErrMsg .= 'Patient DOB, ';
      }
      if ($ptres['street'] == null || strlen(trim($ptres['street'])) == 0 ||
	  $ptres['city'] == null || strlen(trim($ptres['city'])) == 0 ||
	  $ptres['state'] == null || strlen(trim($ptres['state'])) == 0 ||
	  $ptres['postal_code'] == null || strlen(trim($ptres['postal_code'])) == 0)
      {
	$missingFieldErrMsg .= 'Patient address, ';
      }
      if ($ptres['phone_home'] == null || strlen(trim($ptres['phone_home'])) == 0) {
	$missingFieldErrMsg .= 'Patient phone, ';
      }
    
      if ($ptres['primary_ins'] == null || $ptres['primary_ins'] <= 0) {
	$missingFieldErrMsg .= 'Primary insurance provider, ';
      }
      else if ($ptres['primary_ins'] != '1205' &&     // cash
	       $ptres['primary_ins'] != '2239')       // samsha
      {
	if ($ptres['policy_number'] == null || 
	    strlen(trim($ptres['policy_number'])) == 0 ||
	    strcasecmp(trim($ptres['policy_number']), 'null') == 0)
	{
	  $missingFieldErrMsg .= 'Primary insurance policy number, ';
	}
	if ($ptres['freeb_type'] == 25 && ($ptres['onset_date'] == null || strlen(trim($ptres['onset_date'])) == 0)) {
	  $missingFieldErrMsg .= 'Onset/Hospitalization Date, ';
	}
	if ($ptres['subscriber_lname'] == null || strlen(trim($ptres['subscriber_lname'])) == 0 ||
	    $ptres['subscriber_fname'] == null || strlen(trim($ptres['subscriber_fname'])) == 0)
	{
	  $missingFieldErrMsg .= 'Subscriber name, ';
	}
	if ($ptres['subscriber_sex'] == null || strlen(trim($ptres['subscriber_sex'])) == 0) {
	  $missingFieldErrMsg .= 'Subscriber sex, ';
	}
	if ($ptres['subscriber_dob'] == null || strlen(trim($ptres['subscriber_dob'])) == 0 ||
	    trim($ptres['subscriber_dob']) == '0000-00-00')
	{
	  $missingFieldErrMsg .= 'Subscriber DOB, ';
	}
	if ($ptres['subscriber_relationship'] == null) {
	  $missingFieldErrMsg .= 'Subscriber relationship, ';
	}
	if ($ptres['subscriber_street'] == null || strlen(trim($ptres['subscriber_street'])) == 0 ||
	    $ptres['subscriber_city'] == null || strlen(trim($ptres['subscriber_city'])) == 0 ||
	    $ptres['subscriber_state'] == null || strlen(trim($ptres['subscriber_state'])) == 0 ||
	    $ptres['subscriber_postal_code'] == null || strlen(trim($ptres['subscriber_postal_code'])) == 0)
	{
	  $missingFieldErrMsg .= 'Subscriber address, ';
	}
	if ($ptres['subscriber_phone'] == null || strlen(trim($ptres['subscriber_phone'])) == 0) {
	  $missingFieldErrMsg .= 'Subscriber phone, ';
	}
	if ($ptres['x12_default_partner_id'] == null || $ptres['x12_default_partner_id'] == 0) {
	  $missingFieldErrMsg .= 'Partner for Insurance Company ' . '"' . $ptres['ins_name'] . '", ';
	}
	if ($ptres['ins_effect_date']) {
	  if (strtotime($ptres['ins_effect_date']) > strtotime($encounter_date)) {
		$insDateErrMsg .= 'Effective date of primary insurance ' . $ptres['ins_effect_date'] . 
				  ' is more recent than Encounter date ' . date("Y-m-d", strtotime($encounter_date));
	  }
	}
      }
      if ($missingFieldErrMsg == 'Missing: ') {
	$missingFieldErrMsg = '';
      }
      else {
	$missingFieldErrMsg = substr($missingFieldErrMsg, 0, strlen($missingFieldErrMsg) - 2);
      }
      $patientErrMsg = $missingFieldErrMsg;
      if ($patientErrMsg != '' && $insDateErrMsg != '')
	$patientErrMsg .= '<br>&nbsp;' . $insDateErrMsg;
      return $patientErrMsg;
    }
    static function getClaimBillingStatus($pid, $encounter)
    {
      $msg = '';
      $pay_amount = 0;
      $adj_amount = 0;
    
      $query = "SELECT pay_amount, adj_amount, payer_type FROM ar_activity AS a WHERE " .
	       "a.pid = '" . $pid . "' AND a.encounter = '" . $encounter . "'";
      $res = sqlStatement($query);
      while ($row = sqlFetchArray($res)) {
	if ($row['payer_type'] == 1) {
	  $pay_amount1 += $row['pay_amount'];
	  $adj_amount1 += $row['adj_amount'];
	}
	if ($row['payer_type'] == 2) {
	  $pay_amount2 += $row['pay_amount'];
	  $adj_amount2 += $row['adj_amount'];
	}
	if ($row['payer_type'] == 3) {
	  $pay_amount3 += $row['pay_amount'];
	  $adj_amount3 += $row['adj_amount'];
	}
      }
    
      if ($pay_amount1) {
	$msg .= " Ins1: paid = \$" . sprintf("%.2f", $pay_amount1) . 
	       ", adjust = \$" . sprintf("%.2f", $adj_amount1) . "  ";
      }
      if ($pay_amount2) {
	$msg .= " Ins2: paid = \$" . sprintf("%.2f", $pay_amount2) . 
	       ", adjust = \$" . sprintf("%.2f", $adj_amount2) . "  ";
      }
      if ($pay_amount3) {
	$msg .= " Ins3: paid = \$" . sprintf("%.2f", $pay_amount3) . 
	       ", adjust = \$" . sprintf("%.2f", $adj_amount3) . " ";
      }
      if ($msg != '') {
	$msg = "Ins adjudicated --" . $msg;
      }
    
      return $msg;
    }
    public function query_to_xml_result_billing_manager($data){//Accepts a select query string.It queries the database and returns the result in xml format.Format is as follows
	if($this->valid($data[0])){
	 $fields=$data[1];
	 $from = $data[2];
	 $fields=preg_replace("/(delete)*|(drop)*|(alter)*|(grant)*|(update)*|(insert)*|(show)*/","",$fields);
	 $fields=preg_replace("/(,[^\w]username[^\w],)/",",",$fields);
	 $fields=preg_replace("/(,[^\w]username[^\w])|([^\w]username[^\w],)|([^\w]username[^\w])/","",$fields);
	 $fields=preg_replace("/(,[^\w]password[^\w],)/",",",$fields);
	 $fields=preg_replace("/(,[^\w]password[^\w])|([^\w]password[^\w],)|([^\w]password[^\w])/","",$fields);
	 if(strtolower($fields)=='all')
         {
          $fields=' * ';
         }
	 $sql_query = "SELECT $fields FROM $from";
	 $sql_result_set = sqlStatement($sql_query);
	 $resultset = array();
	 $i=0;
	 while($row = sqlFetchArray($sql_result_set))
	 {
	   
	   foreach($row as $key=>$value){
	    if(trim($key) != 'username' && trim($key) != 'password' && trim($key) != 'signature_image'){
	      if(!$value)
		$value="";
	      $resultset[$i][$key]=$value;
	    }
	   }
			foreach (array('primary','secondary','tertiary') as $instype) {
				$query = "SELECT id.provider,id.date,ic.name,id.policy_number,id.plan_name FROM insurance_data AS id LEFT JOIN insurance_companies AS ic ON id.provider = ic.id WHERE pid = '".$resultset[$i]['am_pid']."' AND type = '".$instype."' ORDER BY date DESC";
				$res = sqlStatement($query);
				$enddate = 'Present';
				while($row = sqlFetchArray($res)){
					if($row['provider']){
						$ins_description = ucfirst($instype);
						$ins_description = xl($ins_description);
						$ins_description .= strcmp($enddate, 'Present') != 0 ? " (".xl('Old').")" : "";
						$ins_description .= " : <span class='boldval' >".$row['name']."</span> ";
						if(strcmp($row['date'],'0000-00-00') != 0){
							$ins_description .= xl('from','',' ',' ').$row['date'];
						}
						$ins_description .= xl('until','',' ',' ');
						$ins_description .= (strcmp($enddate, 'Present') != 0) ? $enddate : xl('Present');
						$ins_description .= "&nbsp;&nbsp;&nbsp;&nbsp;Policy Number : <span class='boldval' >".$row['policy_number'].
						"</span>&nbsp;&nbsp;&nbsp;&nbsp;Plan Name : <span class='boldval' >".$row['plan_number']."</span>";
						$ins_description .= "<br>";
						$resultset[$i]['insurance'] .= htmlspecialchars($ins_description,ENT_NOQUOTES);
					}
					$enddate = $row['date'];
				}
			}
	      $query = "SELECT code_type, code, justify, modifier,fee,DATE_FORMAT(date,'%Y-%m-%d') AS date,
	      CONCAT_WS(' ',u.fname,u.mname,u.lname) AS provider,user
	       FROM billing LEFT OUTER JOIN users AS u ON provider_id=u.id " .
	      " WHERE encounter = '".$resultset[$i]['am_encounter']."' AND pid = '".$resultset[$i]['am_pid']."' AND " .
	      "activity = '1' ORDER BY date, billing.id";
	      $res = sqlStatement($query);
	      while ($row1 = sqlFetchArray($res)) {
	      $resultset[$i]['user'] = $row1['user'];
	      if ($row1['code_type'] == 'ICD9') {
		  $resultset[$i][$resultset[$i]['am_encounter']]['diag'][$row1['code']]['code'] = $row1['code'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['diag'][$row1['code']]['date'] = $row1['date'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['diag'][$row1['code']]['provider'] = $row1['provider'];
		  continue;
	      }
	      else{
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['code_type']= $row1['code_type'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['code']= $row1['code'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['justify'] = $row1['justify'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['modifier'] = $row1['modifier'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['fee'] = $row1['fee'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['date'] = $row1['date'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['proc'][$row1['code']]['provider'] = $row1['provider'];
	      }
	      }
	      $qcopay = "SELECT pay_amount AS COPAY,DATE_FORMAT(post_time,'%Y-%m-%d') AS post_time  FROM ar_activity WHERE
			encounter = '".$resultset[$i]['am_encounter']."' AND pid = '".$resultset[$i]['am_pid']."' AND account_code='PCP'";
	      $rcopay = sqlStatement($qcopay);
	      while ($rowcopay = sqlFetchArray($rcopay)) {
		  $resultset[$i][$resultset[$i]['am_encounter']]['cp']['copay']['fee'] = $rowcopay['COPAY'];
		  $resultset[$i][$resultset[$i]['am_encounter']]['cp']['copay']['date'] = $rowcopay['post_time'];
	      }
		  $resultset[$i]['ClaimErrorMsg'] = $this->ClaimErrormsg($resultset[$i]['am_pid'],$resultset[$i]['fdate']);
		  $resultset[$i]['ClaimBillingStat'] = $this->getClaimBillingStatus($resultset[$i]['am_pid'],$resultset[$i]['am_encounter']);
		  
		  $query = "SELECT id.provider AS id, id.type, id.date, " .
		    "ic.x12_default_partner_id AS ic_x12id, ic.name AS provider, x12.name AS x12_name " .
		    "FROM insurance_data AS id, insurance_companies AS ic  " .
		    "LEFT OUTER JOIN x12_partners AS x12 " .
		    "ON x12.id = ic.x12_default_partner_id " .
		    "WHERE ic.id = id.provider AND " .
		    "id.pid = '" . mysql_escape_string($resultset[$i]['am_pid']) . "' AND " .
		    "id.date <= '".$resultset[$i]['fdate']."' " .
		    "ORDER BY id.type ASC, id.date DESC";
	  	  $result = sqlStatement($query);
		  $default_x12_partner = '';
		  $prevtype = '';
		  $found_ins = false;    // true if pt has an ins comp matching this claim's ins comp ($row[id] == iter[payer_id]) 
	  	  while ($row12 = sqlFetchArray($result)) {
		    if (strcmp($row12['type'], $prevtype) == 0) continue;
		    $prevtype = $row12['type'];
	  	    if (strlen($row12['provider']) > 0) {
		      // This preserves any existing insurance company selection, which is
		      // important when EOB posting has re-queued for secondary billing.
		      $resultset[$i]['InsDDL'][strtoupper(substr($row12['type'],0,1)) . $row12['id']] = substr($row12['type'],0,3) . ": " . $row12['provider'];
		      if (!is_numeric($default_x12_partner)) $resultset[$i]['default_x12_partner'] = $row12['ic_x12id'];
		      $resultset[$i]['default_x12_partner_name'] = $row12['x12_name'];
		    }
		  }
		  $query = "SELECT DISTINCT name, id FROM x12_partners ORDER BY name";
		  $dares = sqlStatement($query);
		  while($xname = sqlFetchArray($dares)){
		    //if ($resultset[$i]['default_x12_partner_name'] && strcasecmp($resultset[$i]['default_x12_partner_name'], "Cash") != 0) {
		    //  $augmented_xname = $xname['name'] . ' (' . $resultset[$i]['default_x12_partner_name'] . ')';
		    //}
		    $resultset[$i]['x12partners'][$xname['id']] = $xname['name'];
		  }
		  $query = "SELECT * FROM claims WHERE " .
		  "patient_id = '" . $resultset[$i]['am_pid'] . "' AND " .
		  "encounter_id = '" . $resultset[$i]['am_encounter'] . "' " .
		  "ORDER BY version";
		  $cres = sqlStatement($query);
		  $lastcrow = false;
		  while($crow = sqlFetchArray($cres)){
		    $payer_array = array(1 => 'primary', 2 => 'secondary', 3 => 'tertiary');
		    $payer_type_str = $payer_array[$crow['payer_type']];
		    $payer_type_query = empty($payer_type_str) ? "" : "id.type = '$payer_type_str' AND ";
		    $query = "SELECT id.type, ic.name " .
		      "FROM insurance_data AS id, insurance_companies AS ic WHERE " .
		      "id.pid = '" . $resultset[$i]['am_pid'] . "' AND " .
		      "id.provider = '" . $crow['payer_id'] . "' AND " . 
		      $payer_type_query .                     
		      "id.date <= '".$resultset[$i]['fdate']."' AND " .
		      "ic.id = id.provider " .
		      "ORDER BY id.type ASC, id.date DESC";
		    $irow= sqlQuery($query);
		    if (empty($irow) && $crow['payer_id'] != 0) {
		    $query = "select name from insurance_companies where id = '" . $crow['payer_id'] . "'";
		    $irow = sqlQuery($query);
		      $resultset[$i]['claimHistory'] = "<br>&nbsp;<span class=text><font color='#ff7777'>Billed ins comp ";
		      if (! empty($irow['name']))
			$resultset[$i]['claimHistory'] .= '"' . $irow['name'] . '"';
		      $resultset[$i]['claimHistory'] .= " has been deleted from pt demographics</font></span>";
		    }
		    if ($crow['bill_process']) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['bill_time'], 0, 16) . " " .
			"Queued for" . " {$irow['type']} {$crow['target']} " .
			"billing to ";
	    	      if (strcasecmp($irow['name'], "Cash") == 0)     
			$resultset[$i]['claimHistory'] .= 'Patient';
		      else 
			$resultset[$i]['claimHistory'] .= $irow['name'];
		      ++$lcount;
		    }
		    else if ($crow['status'] < 6) {
		      if ($crow['status'] > 1) {
			    $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['bill_time'], 0, 16) . " " .
			      "Marked as cleared";
			    ++$lcount;
		      }
		      else {
			    $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['bill_time'], 0, 16) . " " .
			      "Re-opened";
			    if ($crow['payer_id'] == 0)
			      $resultset[$i]['claimHistory'] .= '.';
			    ++$lcount;
		      }
		    }
		    else if ($crow['status'] == 6) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['bill_time'], 0, 16) . " " .
			htmlspecialchars("This claim has been forwarded to next level.", ENT_QUOTES);
		      ++$lcount;
		    }
		    else if ($crow['status'] == 7) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['bill_time'], 0, 16) . " " .
		      htmlspecialchars("This claim has been denied.Reason:-", ENT_QUOTES);
		      if($crow['process_file'])
		       {
			$code_array=split(',',$crow['process_file']);
			    foreach($code_array as $code_key => $code_value)
			     {
				    $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;&nbsp;&nbsp;";
				    $reason_array=split('_',$code_value);
				    if(!isset($adjustment_reasons[$reason_array[3]]))
				     {
					    $resultset[$i]['claimHistory'] .=htmlspecialchars( "For code [{$reason_array[0]}] and modifier [{$reason_array[1]}] the Denial code is [{$reason_array[2]} {$reason_array[3]}]", ENT_QUOTES);
				     }
				    else
				     {
					    $resultset[$i]['claimHistory'] .=htmlspecialchars( "For code [{$reason_array[0]}] and modifier [{$reason_array[1]}] the Denial Group code is [{$reason_array[2]}] and the Reason is:- {$adjustment_reasons[$reason_array[3]]}", ENT_QUOTES);
				     }
			     }
		       }
		      else
		       {
			$resultset[$i]['claimHistory'] .=htmlspecialchars( "Not Specified.", ENT_QUOTES);
		       }
		      ++$lcount;
		    }
		    if ($crow['process_time']) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;" . substr($crow['process_time'], 0, 16) . " " .
			"Claim was generated to file " .
			"<a href='get_claim_file.php?key=" . $crow['process_file'] .
			"'>" .
			$crow['process_file'] . "</a>";
		      ++$lcount;
		    }
		    $lastcrow = $crow;
		    if ($lastcrow && $lastcrow['status'] == 4) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;This claim has been closed.";
		      ++$lcount;
		    }
	    
		    if ($lastcrow && $lastcrow['status'] == 5) {
		      $resultset[$i]['claimHistory'] .= "<br>\n&nbsp;This claim has been canceled.";
		      ++$lcount;
		    }
		  }
	   $i++;
	 }
	 return $resultset;
	}
	else{
		throw new SoapFault("Server", "credentials failed in query_to_xml_result error message");
	}
    }
    public function function_return_to_xml($var=array()){//Accepts a select query string.It queries the database and returns the result in xml format.Format is as follows
	
	  $doc = new DOMDocument();
	  $doc->formatOutput = true;
	 
	  $root = $doc->createElement( "root" );
	  $doc->appendChild( $root );
	
	 
	   $level = $doc->createElement( "level" );
	   $root->appendChild( $level );
	   foreach($var as $key=>$value){
	   $element = $doc->createElement( "$key" );
	   $element->appendChild(
	       $doc->createTextNode( $value )
	   );
	   $level->appendChild( $element );
	       }
	   
	 return $doc->saveXML();
	
    }
    public function delete_file($data){
	if($this->valid($data[0])){
	 $file_name_with_path=$data[1];
	 @unlink($file_name_with_path);
	}
	else{
		throw new SoapFault("Server", "credentials failed in delete_file error message");
	}
    }  
    public function file_to_xml($data){//Accepts a file path.Fetches the file in xml format.Format is as follows
	if($this->valid($data[0])){
	   $file_name_with_path=dirname(__FILE__)."/".$data[1];
	   $path_parts = pathinfo($file_name_with_path);
	   $handler = fopen($file_name_with_path,"rb");
	   $returnData = fread($handler,filesize($file_name_with_path));
	   fclose($handler);
	   return array($path_parts['basename'],base64_encode($returnData));
	}
	else{
		throw new SoapFault("Server", "credentials failed in file_to_xml error message");
	}
    }
    public function store_to_file($data){
	if($this->valid($data[0])){
	       $file_name_with_path=dirname(__FILE__)."/".$data[1];
	       $data=$data[2];
	       $savedpath=dirname(__FILE__)."/../documents/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777);
		       chmod($savedpath, 0777);
	       }
	       $savedpath=dirname(__FILE__)."/../documents/unsigned/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777);
		       chmod($savedpath, 0777);
	       }
	       $savedpath=dirname(__FILE__)."/../documents/signed/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777);
		       chmod($savedpath, 0777);
	       }
	       $savedpath=dirname(__FILE__)."/../documents/upload/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777);
		       chmod($savedpath, 0777);
	       }
	       $savedpath=$GLOBALS['OE_SITE_DIR']."/edi/master/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777,true);
		       chmod($savedpath, 0777);
	       }
	       $savedpath=$GLOBALS['OE_SITE_DIR']."/officeally/";
	       if(is_dir($savedpath));
	       else
	       {
		       mkdir($savedpath,0777,true);
		       chmod($savedpath, 0777);
	       }
	   $handler = fopen($file_name_with_path,"w");
	   fwrite($handler, base64_decode($data));
	   fclose($handler);
	       chmod($file_name_with_path,0777);
	}
	else{
		throw new SoapFault("Server", "credentials failed in store_to_file error message");
	}
	}
   
    static public function batch_despatch($var,$func,$data_credentials){
	if(UserService::valid($data_credentials)){
	require_once(dirname(__FILE__)."/../../library/invoice_summary.inc.php");
	require_once(dirname(__FILE__)."/../../library/options.inc.php");
	require_once(dirname(__FILE__)."/../../library/acl.inc");
	require_once(dirname(__FILE__)."/../../library/formdata.inc.php");
	require_once(dirname(__FILE__)."/../../library/patient.inc");
	if($func=='ar_responsible_party')
	 {
		$patient_id=$var['pid'];
		$encounter_id=$var['encounter'];
		$x['ar_responsible_party']=ar_responsible_party($patient_id,$encounter_id);
		return $x;
	 }
	elseif($func=='getInsuranceData')
	 {
		$pid=$var['pid'];
		$type=$var['type'];
		$given=$var['given'];
		$x=getInsuranceData($pid,$type,$given);
		return $x;
	 }
	elseif($func=='generate_select_list')
	 {
		$tag_name=$var['tag_name'];
		$list_id=$var['list_id'];
		$currvalue=$var['currvalue'];
		$title=$var['title'];
		$empty_name=$var['empty_name'];
		$class=$var['class'];
		$onchange=$var['onchange'];
		$x['generate_select_list']=generate_select_list($tag_name,$list_id,$currvalue,$title,$empty_name,$class,$onchange);
		return $x;
	 }
	elseif($func=='arGetPayerID')
	 {
	    require_once(dirname(__FILE__)."/../../library/sl_eob.inc.php");
	    $patient_id=$var['patient_id'];
	    $date_of_service=$var['date_of_service'];
	    $payer_type=$var['payer_type'];
	    $x['arGetPayerID']=arGetPayerID($patient_id,$date_of_service,$payer_type);
	    return $x;
	 }
	elseif($func=='get_policy_types'){
	    $policy_types = array(
		''   => xl('N/A'),
		'12' => xl('Working Aged Beneficiary or Spouse with Employer Group Health Plan'),
		'13' => xl('End-Stage Renal Disease Beneficiary in MCP with Employer`s Group Plan'),
		'14' => xl('No-fault Insurance including Auto is Primary'),
		'15' => xl('Worker`s Compensation'),
		'16' => xl('Public Health Service (PHS) or Other Federal Agency'),
		'41' => xl('Black Lung'),
		'42' => xl('Veteran`s Administration'),
		'43' => xl('Disabled Beneficiary Under Age 65 with Large Group Health Plan (LGHP)'),
		'47' => xl('Other Liability Insurance is Primary'),
	    );
	    $x[]=$policy_types;
	    return $x;
	}
	elseif($func=='xl_layout_label')
	 {
		$constant=$var['constant'];
	        $x['xl_layout_label']=xl_layout_label($constant);
		return $x;
	 }
	elseif($func=='generate_form_field')
	 {
		$frow=$var['frow'];
		$currvalue=$var['currvalue'];
	        ob_start();
		generate_form_field($frow,$currvalue);
		$x['generate_form_field']=ob_get_contents();
		ob_end_clean();
		return $x;
	 }
	elseif($func=='getInsuranceProviders')
	 {
		$i=$var['i'];
		$provider=$var['provider'];
		$insurancei=getInsuranceProviders();
	        $x=$insurancei;
		return $x;
	 }
	elseif($func=='get_layout_form_value')
	 {
		$frow=$var['frow'];
		$_POST=$var['post_array'];
		$x['get_layout_form_value']=get_layout_form_value($frow);
		return $x;
	 }
	elseif($func=='updatePatientData')
	 {
		$patient_data=$var['patient_data'];
		$create=$var['create'];
		updatePatientData($var['pid'],$patient_data,$create);
		$x['ok']='ok';
		return x;
	 }
	elseif($func=='updateEmployerData')
	 {
		$employer_data=$var['employer_data'];
		$create=$var['create'];
		updateEmployerData($var['pid'],$employer_data,$create);
		$x['ok']='ok';
		return $x;
	 }
	elseif($func=='newHistoryData')
	 {
		newHistoryData($var['pid']);
		$x['ok']='ok';
		return $x;
	 }
	elseif($func=='newInsuranceData')
	 {
		$_POST=$var[0];
		foreach($var as $key=>$value)
		 {
			if($key>=3)//first 3 need to be skipped.
			 {
			  $var[$key]=formData($value,'P');
			 }
			if($key>=1)
			 {
			  $parameters[$key]=$var[$key];
			 }
		 }
		$parameters[12]=fixDate($parameters[12]);
		$parameters[27]=fixDate($parameters[27]);

		list($bl0,$pid,$type,$provider,$policy_number,$group_number,$plan_name,$subscriber_lname,$subscriber_mname,
		     $subscriber_fname,$subscriber_relationship,$subscriber_ss,$subscriber_DOB,$subscriber_street,$subscriber_postal_code,
		     $subscriber_city,$subscriber_state,$subscriber_country,$subscriber_phone,$subscriber_employer,$subscriber_employer_street,
		     $subscriber_employer_city,$subscriber_employer_postal_code,$subscriber_employer_state,$subscriber_employer_country,$copay,
		     $subscriber_sex,$effective_date,$accept_assignment,$bl1,$bl2,$bl3,$bl4,$auth_required,$msp_category,$policy_type)=$parameters;
		$accept_assignment = $accept_assignment ? $accept_assignment : "TRUE";
		$effective_date = $effective_date ? $effective_date : "0000-00-00";
		$auth_required = $parameters['33'] ? $parameters['33'] : "0";
		if($GLOBALS['msp_capturing_payment_screen']){
		$msp_category = $parameters['34'] ? $parameters['34'] : null;
		$policy_type = $parameters['35'] ? $parameters['35'] : "";
		}
		if (strlen($type) <= 0) return FALSE;

		// If a bad date was passed, err on the side of caution.
		$effective_date = fixDate($effective_date, date('Y-m-d'));
	      
		$idres = sqlStatement("SELECT * FROM insurance_data WHERE " .
		  "pid = '$pid' AND type = '$type' ORDER BY date DESC");
		$idrow = sqlFetchArray($idres);
		// Replace the most recent entry in any of the following cases:
		// * Its effective date is >= this effective date.
		// * It is the first entry and it has no (insurance) provider.
		// * There is no encounter that is earlier than the new effective date but
		//   on or after the old effective date.
		// Otherwise insert a new entry.

		$replace = false;
		if ($idrow) {
		  if (strcmp($idrow['date'], $effective_date) > 0) {
		    $replace = true;
		  }
		  else {
		    if (!$idrow['provider'] && !sqlFetchArray($idres)) {
		      $replace = true;
		    }
		    else {
		      $ferow = sqlQuery("SELECT count(*) AS count FROM form_encounter " .
			"WHERE pid = '$pid' AND date < '$effective_date 00:00:00' AND " .
			"date >= '" . $idrow['date'] . " 00:00:00'");
		      if ($ferow['count'] == 0) $replace = true;
		    }
		  }
		}

		if ($replace) {
	      
		  // TBD: This is a bit dangerous in that a typo in entering the effective
		  // date can wipe out previous insurance history.  So we want some data
		  // entry validation somewhere.
		  sqlStatement("DELETE FROM insurance_data WHERE " .
		    "pid = '$pid' AND type = '$type' AND date >= '$effective_date' AND " .
		    "id != " . $idrow['id']);
	      
		  $data = array();
		  $data['type'] = $type;
		  $data['provider'] = $provider;
		  $data['policy_number'] = $policy_number;
		  $data['group_number'] = $group_number;
		  $data['plan_name'] = $plan_name;
		  $data['subscriber_lname'] = $subscriber_lname;
		  $data['subscriber_mname'] = $subscriber_mname;
		  $data['subscriber_fname'] = $subscriber_fname;
		  $data['subscriber_relationship'] = $subscriber_relationship;
		  $data['subscriber_ss'] = $subscriber_ss;
		  $data['subscriber_DOB'] = $subscriber_DOB;
		  $data['subscriber_street'] = $subscriber_street;
		  $data['subscriber_postal_code'] = $subscriber_postal_code;
		  $data['subscriber_city'] = $subscriber_city;
		  $data['subscriber_state'] = $subscriber_state;
		  $data['subscriber_country'] = $subscriber_country;
		  $data['subscriber_phone'] = $subscriber_phone;
		  $data['subscriber_employer'] = $subscriber_employer;
		  $data['subscriber_employer_city'] = $subscriber_employer_city;
		  $data['subscriber_employer_street'] = $subscriber_employer_street;
		  $data['subscriber_employer_postal_code'] = $subscriber_employer_postal_code;
		  $data['subscriber_employer_state'] = $subscriber_employer_state;
		  $data['subscriber_employer_country'] = $subscriber_employer_country;
		  $data['copay'] = $copay;
		  $data['subscriber_sex'] = $subscriber_sex;
		  $data['pid'] = $pid;
		  $data['date'] = $effective_date;
		  $data['accept_assignment'] = $accept_assignment;
		  $data['auth_required'] = $auth_required;
		  if($GLOBALS['msp_capturing_payment_screen']){
		  $data['msp_category'] = $msp_category;
		  $data['policy_type'] = $policy_type;
		  }
		  updateInsuranceData($idrow['id'], $data);
		}
		else {
		    $msp = " ";
		    if($GLOBALS['msp_capturing_payment_screen']){
		    $msp = ",msp_category = '$msp_category',policy_type = '$policy_type'";
		    }
		  sqlInsert("INSERT INTO insurance_data SET
		    type = '$type',
		    provider = '$provider',
		    policy_number = '$policy_number',
		    group_number = '$group_number',
		    plan_name = '$plan_name',
		    subscriber_lname = '$subscriber_lname',
		    subscriber_mname = '$subscriber_mname',
		    subscriber_fname = '$subscriber_fname',
		    subscriber_relationship = '$subscriber_relationship',
		    subscriber_ss = '$subscriber_ss',
		    subscriber_DOB = '$subscriber_DOB',
		    subscriber_street = '$subscriber_street',
		    subscriber_postal_code = '$subscriber_postal_code',
		    subscriber_city = '$subscriber_city',
		    subscriber_state = '$subscriber_state',
		    subscriber_country = '$subscriber_country',
		    subscriber_phone = '$subscriber_phone',
		    subscriber_employer = '$subscriber_employer',
		    subscriber_employer_city = '$subscriber_employer_city',
		    subscriber_employer_street = '$subscriber_employer_street',
		    subscriber_employer_postal_code = '$subscriber_employer_postal_code',
		    subscriber_employer_state = '$subscriber_employer_state',
		    subscriber_employer_country = '$subscriber_employer_country',
		    copay = '$copay',
		    subscriber_sex = '$subscriber_sex',
		    pid = '$pid',
		    date = '$effective_date',
		    accept_assignment = '$accept_assignment',
		    auth_required = '$auth_required'
		    $msp
		  ");
		}
		//call_user_func_array('newInsuranceData',$parameters);
		$x['ok']='ok';
		return $x;
	 }
	elseif($func=='acl_check'){
	  $section = $var['section'];
	  $value = $var['value'];
	  $user = $_SESSION['authUser'];
	  $axo_section_value = $var['axo_section'];
	  $axo_value = $var['axo_value'];
	  if($axo_section_value || $axo_value)
      	  return acl_check($section,$value,$user,$axo_section_value,$axo_value);
	  else
	  return acl_check($section,$value);
	}
	elseif($func=='acl_check_batch'){
	  $section = $var['section'];
	  $value = $var['value'];
	  $user = $_SESSION['authUser'];
	  $aclArray=$var['aclValues'];
	  $retunArray=array();
	  foreach($aclArray as $row){	    
	    $retunArray[$row[0]][$row[1]]=acl_check($section,$value,$user,$row[0],$row[1]);
	  }
	  return $retunArray;	  
	}
	}
	else{
		throw new SoapFault("Server", "credentials failed in batch_despatch error message");
	}
    }
  
    public function batch_select($data){
	if($this->valid($data[0])){
		$batch = $data[1];
		foreach($batch as $key=>$value)
		{
		     
		$batchkey=$value['batchkey'];
		$fields=$value['fields'];
		$from=$value['from'];
		$arrproc[] = $data[0];
		$arrproc[] = $fields;
		$arrproc[] = $from;
		$return_array[$batchkey]=$this->query_to_xml_result($arrproc);
		$arrproc=null;
		}
		return $return_array;
	}
	else{
		throw new SoapFault("Server", "credentials failed in batch_select error message");
	}
    }
    public function batch_select_billing_manager($data){
	if($this->valid($data[0])){
		$batch = $data[1];
		foreach($batch as $key=>$value)
		{
		     
		$batchkey=$value['batchkey'];
		$fields=$value['fields'];
		$from=$value['from'];
		$arrproc[] = $data[0];
		$arrproc[] = $fields;
		$arrproc[] = $from;
		if($batchkey==0)
		$return_array[$batchkey]=$this->query_to_xml_result_billing_manager($arrproc);
		else
		$return_array[$batchkey]=$this->query_to_xml_result($arrproc);
		$arrproc=null;
		}
		return $return_array;
	}
	else{
		throw new SoapFault("Server", "credentials failed in batch_select error message");
	}
    }
    public function batch_function($data){
	if($this->valid($data[0])){
		$batch = $data[1];
		foreach($batch as $key=>$value)
		{
		
		$batchkey=$value['batchkey'];
		$function=$value['funcname'];
		$param=$value['param'];
		$param[]=$data[0];
		$res=call_user_func_array("UserService::$function",$param);
		$return_array[$batchkey]=$res;
		}
		return $return_array;
	}
	else{
		throw new SoapFault("Server", "credentials failed in batch_function error message");
	}
    }
    public function multiplecall($data){
       if($this->valid($data[0])){
	        $batch = $data[1];
		foreach($batch as $key=>$value)
		{
		$batchkey=$value['batchkey'];
		$function=$value['funcname'];
		$param=$value['param'];
		if(is_array($param))
		array_unshift($param,$data[0]);
		else
		$param[]=$data[0];
		$res= UserService::$function($param);
		$return_array[$batchkey]=$res;
		}
		return $return_array;
       }
       else{
		throw new SoapFault("Server", "credentials failed in multiplecall error message");
       }
    }
  public function getversion($data){
      if($this->valid($data[0])){
	     return 1;
      }
      else{
	       throw new SoapFault("Server", "credentials failed in getversion error message");
      }
  }
    //Execute a query and return its results.

  public function selectquery($data){
      //global $pid;
      $sql_result_set='';
      $utype = $this->valid($data[0]);
      if($utype){
      $newobj = factoryclass::dynamic_class_factory("query");
      $sql_result_setarr = $newobj->query_formation($data[1]);
      $sql_result_set = sqlStatement($sql_result_setarr[0],$sql_result_setarr[1]);
      return $this->resourcetoArray($sql_result_set);
      }
    }
    
//Return an SQL resultset as an Array

  public function resourcetoArray($sql_result_set){
      $i=0;
      while($row = sqlFetchArray($sql_result_set))
	 {
	  foreach($row as $key=>$value){
	    if(!$value)
	      $value="";
	   $resultset[$i][$key]=$value;
	  }
	   $i++;
	 }
	 return $resultset;
  }    

  //REceive an array of Select cases from portal execute it and return
// it in the keys received from portal. A batch of queries execute and returns it in one batch.

  public function batch_select_new($data){
	if($this->valid($data[0])){
		$batch = $data[1];
		foreach($batch as $key=>$value)
		{
		$batchkey=$value['batchkey'];
		$case=$value['case'];
		$param=$value['param'];
		$arrproc[] = $case;
		$arrproc[] = $param;
		$return_array[$batchkey]=$this->selectquery(array($data[0],$arrproc));
		$arrproc=null;
		}
		return $return_array;
	}
	else{
		throw new SoapFault("Server", "credentials failed in batch_select error message");
	}
    }

  public function check_field_exists($data){
    global $sqlconf;
    if($this->valid($data[0])){
      $query = sqlStatement("SELECT * FROM information_schema.COLUMNS WHERE column_name = '".$data[1]."' AND table_name = '".$data[2]."' AND table_schema = '".$sqlconf["dbase"]."'");
      if(sqlNumRows($query)){
        return 1;
      }
      else{
        return 0;
      }
    }else{
      throw new SoapFault("Server", "credentials failed in check_field_exists");
    }
  }
  
  public function get_session_val($data){
    if($this->valid($data[0])){
      list(,$userauthorized) = $data;
      return $_SESSION[$userauthorized];
    }
    else{
      throw new SoapFault("Server", "credentials failed in get_session_val");
    }
  }
  
 //call from addreport : module report
 
 public function report_inserted($data)
 {
  
   if($this->valid($data[0])){
        require_once(dirname(__FILE__)."/../../gacl/gacl_api.class.php");
		$obj=new gacl_api();
		$report_id=$data[1];
	  	$report_name=$data[2];
		$user_grp_name=$data[3];
		$user_rpt_name=$data[4];
		$force_install=$data[5];
		$group_name=$data[6];
		$user_id=$data[7];
		$gcl_flag=$data[8];
		$grp_acl=$data[9];
		$rpt_acl=$data[10];
		
		//acl
		$x=$obj->get_group_id('reports','Reports','AxO');
		if(!$x)
		{
		$obj->add_group('reports','Reports',0, $group_type='AxO');
		}
		else
		{  
		   if($gcl_flag)
		   {
				$obj->add_group($grp_acl,$group_name,$x,'AxO');
				$obj->add_object_section($group_name, $grp_acl, $order=0, $hidden=0, 'axo');
				$y=$obj->get_group_id($grp_acl,$group_name,'AxO');
				if($y)
				{
				//add_group_object($group_id, $object_section_value, $object_value, $group_type='ARO')
				$obj->add_group_object($y,$grp_acl,$grp_acl, $group_type='AxO');
				}
		   }
		   $objr=new gacl_api();
			$objr->add_object($grp_acl,$report_name,$rpt_acl, $order=0, $hidden=0, 'axo');
			//add_group_object($group_id, $object_section_value, $object_value, $group_type='ARO')
			
			$y=$objr->get_group_id($grp_acl,$group_name,'AxO');
			
			$objr->add_group_object($y,$grp_acl,$rpt_acl, $group_type='AxO');	
		}
		
		//acl
		
        
		mysql_query("delete from module_report_master where mm_rid='$report_id' and user_id='$user_id'");
		
		$sql='insert into module_report_master (mm_rid,user_id,gacl_id) values(?,?,?)';
	    SqlStatement($sql,array($report_id,$user_id,$rpt_acl));
		return $rpt_acl;
		
    }
    else{
      throw new SoapFault("Server", "credentials failed in get_session_val");
    }
 }
 
 //call from deletereport : billing module
 
public function report_deleted($data)
 {
  
   if($this->valid($data[0])){
   
         $report_id=$data[1];
	  	 $user_id=$data[2];
          mysql_query("delete from module_report_master where mm_rid='$report_id' and user_id='$user_id'");
   }
   else
   {throw new SoapFault("Server", "credentials failed in get_session_val");}
 }
 
public function server_print_elig($data){
		if($this->valid($data[0])){
				require_once dirname(__FILE__)."/../../library/edi.inc";
				ob_start();
				print_elig($data[1],$data[2],$data[3],$data[4],$data[5]);
				$x12 = ob_get_clean();
				return $x12;
		}
		else{
				throw new SoapFault("Server", "credentials failed in server_print_elig");
		}
}
  
  public function valid($credentials){
		return true;
	$timminus = date("Y-m-d H:m",(strtotime(date("Y-m-d H:m"))-7200)).":00";
	sqlStatement("DELETE FROM external_modules WHERE type=5 AND created_time<=?",array($timminus));
       	
	global $pid;
	$ok=0;
	$tim = strtotime(gmdate("Y-m-d H:m"));
	$res = sqlStatement("SELECT * FROM external_modules WHERE field_value=?",array($credentials[3]));
	if(sqlNumRows($res)){
		if($GLOBALS['valid_billing_portal']!=true){
		return false;
		}
	}
	else{
	      sqlStatement("INSERT INTO external_modules SET field_value=? , type=?",array($credentials[3],5));
	}
	if(sha1($GLOBALS['external_module_password'].date("Y-m-d H",$tim).$credentials[3])==$credentials[2]){
	      $ok =1;
	}
	elseif(sha1($GLOBALS['external_module_password'].date("Y-m-d H",($tim-3600)).$credentials[3])==$credentials[2]){
	      $ok =1;
	}
	elseif(sha1($GLOBALS['external_module_password'].date("Y-m-d H",($tim+3600)).$credentials[3])==$credentials[2]){
	      $ok =1;
	}
	if(($credentials[1]==$GLOBALS['external_module_username'] && $ok==1)||$GLOBALS['valid_billing_portal']==true){
		$_GET['site'] = $credentials[0];
		$GLOBALS['valid_billing_portal']=true;
		
		return true;
	}
	else{
		return false;
	}
    }
    public function check_connection($data){
       if($this->valid($data[0])){
	   return 'ok';
       }
       else{
	   return 'notok';
       }
    }
    public function get_directory_list($data){
	if($this->valid($data[0])){
	    $directory_name = dirname(__FILE__)."/".$data[1];
	    $directory_list = array();
	    if($handle = opendir($directory_name)){
		while(false != ($entry = readdir($handle))){
		    if($entry != "." && $entry != ".."){
			$f123=fopen();
			if($fh = fopen($directory_name."/".$entry."/name.txt","r")){
			    $theData = fread($fh, filesize($directory_name."/".$entry."/name.txt"));
			    $directory_list[$entry] = $theData;
			}
			else{
			    $directory_list[$entry] = $entry;
			}
		    }
		}
	    }
	    closedir($handle);
	    return $directory_list;
	}
    }
    public function update_x12_cred($data){
	if($this->valid($data[0])){
	    list($cred,$id,$username,$pasword) = $data;
	    $username = base64_decode($username);
	    $pasword = base64_decode($pasword);
	    sqlQuery("update x12_partners set x12_username=? , x12_password=? where id=?",array($username,$pasword,$id));
	    return true;
	}
    }
}
//$server = new SoapServer(null,array('uri' => "urn://portal/res"));
//$server->setClass('UserService');
//$server->setPersistence(SOAP_PERSISTENCE_SESSION);
//$server->handle();
?>