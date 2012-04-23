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
//
// +------------------------------------------------------------------------------+

//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;
//
//if (!extension_loaded('soap')) {
//   die("PLEASE ENABLE SOAP EXTENSION");
//}

require_once(dirname(__FILE__)."/../interface/globals.php");
 $emr_path = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
 $emrPathdbc1944d7ea01b51319eba54c95b8b841b4c5b19arr = explode("/mymodules",$emr_path);
 $emr_path = (!empty($_SERVER['HTTPS'])) ? "https://".$emrPathdbc1944d7ea01b51319eba54c95b8b841b4c5b19arr[0] : "http://".$emrPathdbc1944d7ea01b51319eba54c95b8b841b4c5b19arr[0];
 $row = sqlQuery("SELECT fname,lname FROM users WHERE id=?",array($_SESSION['authId']));
 $tim = date("Y-m-d H:m",(strtotime(date("Y-m-d H:m")-7200))).":00";
 sqlStatement("DELETE FROM external_modules WHERE type=5 AND created_time<=?",array($tim));
 
 function md5_pass($length = 8)
 {
  $randkey = substr(md5(rand().rand()), 0, $length);
  $res = sqlStatement("SELECT * FROM external_modules WHERE type=5 AND field_value='".$randkey."'");
  if(sqlNumRows($res)){
  md5_pass();
  }
  else{
  sqlStatement("INSERT INTO external_modules SET field_value=? , type=?",array($randkey,5));
  return $randkey;
  }
 }
 for($i=1;$i<=11;$i++){//some times php is continuing without getting the return value from the function md5_pass()
   if(!$randkey){
     if($i>1)
     sleep(1);
     $randkey = md5_pass();
   }
   else{
     break;
   }
 }
?>
<html>
<head>
    <?php include_once($GLOBALS['fileroot']."/library/sha1.js");?>
<script type="text/javascript">
 function getshansubmit(){
   	randkey = "<?php echo $randkey;?>";
	pass = SHA1(document.portal.pass.value+"<?php echo gmdate('Y-m-d H');?>"+randkey);
	document.portal.pwd.value=pass;
	document.portal.randkey.value=randkey;
	document.forms[0].submit();
 }
 
</script>
</head>
<title><?php echo htmlspecialchars(xl("Redirection"),ENT_QUOTES);?></title>
<body onLoad="getshansubmit()">
    <form name="portal" method="post" action="<?php echo htmlspecialchars($GLOBALS['external_module_path'],ENT_QUOTES);?>">
    <input type="hidden" name="user" value="<?php echo htmlspecialchars($GLOBALS['external_module_username'],ENT_QUOTES);?>">
    <input type="hidden" name="emr_path" value="<?php echo htmlspecialchars($emr_path,ENT_QUOTES);?>">
    <input type="hidden" name="emr_site" value="<?php echo htmlspecialchars($_SESSION['site_id'],ENT_QUOTES);?>">
    <input type="hidden" name="OpenEMR" value="<?php echo htmlspecialchars(session_id(),ENT_QUOTES);?>">
    <input type="hidden" name="uname" value="<?php echo htmlspecialchars($row['fname']." ".$row['lname'],ENT_QUOTES);?>">
    <input type="hidden" name="pass" value="<?php echo htmlspecialchars($GLOBALS['external_module_password'],ENT_QUOTES);?>">
    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_SESSION['authId'],ENT_QUOTES);?>">
    
    <?php
   $checkRes = sqlStatement("SELECT pid,encounter,am_encounter,fe.date FROM form_encounter AS fe ".
   "LEFT JOIN arr_master AS ar ON ar.am_encounter=fe.encounter ".
   "WHERE am_encounter IS NULL AND (fe.date is not null or fe.date <>'')");
   if(sqlNumRows($checkRes) > 0){
      include_once(dirname(__FILE__).'/../library/sl_eob.inc.php');
      include_once(dirname(__FILE__).'/../library/invoice_summary.inc.php');
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
	       sqlQuery("UPDATE arr_master SET am_statustrack='".$statusId."', am_currentstatus='".$sub['cl_list_slno']."' WHERE am_pid='".$pid."' AND am_encounter='".$encounter."'");
	    }
	    else{
	       $masterId = sqlInsert("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$statusId."','".$sub['cl_list_slno']."','$am_ins1','$am_ins2','$am_ins3','$am_inslevel')");
	    }
	    sqlQuery("UPDATE arr_status SET ac_arr_id='".$masterId."' WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."'");
	 }
      }
   }
   $mainCode = 'ENCOUNTER';
   $subCode = 'ENCT_DELETE';
   $main = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$mainCode."') AND cl_list_type IN ('15')");
   $sub = sqlQuery("SELECT cl_list_slno FROM customlists WHERE cl_list_item_short IN ('".$subCode."') AND cl_list_type IN ('20')");
   $checkDeleted = sqlStatement("SELECT pid,encounter,am_pid,am_encounter,fe.date FROM arr_master AS ar LEFT JOIN form_encounter AS fe
			    ON ar.am_encounter=fe.encounter WHERE encounter IS NULL AND am_currentstatus!='".$sub['cl_list_slno']."'");
   if(sqlNumRows($checkDeleted) > 0){
       while($checkDeletedRow = sqlFetchArray($checkDeleted)){
	$pid = $checkDeletedRow['am_pid'];
	$encounter = $checkDeletedRow['am_encounter'];
	    $count=sqlQuery("SELECT ac_count FROM arr_status WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."' AND ac_status='".$sub['cl_list_slno']."' ORDER BY ac_id DESC LIMIT 1");
	    if(!$count['ac_count']) $count['ac_count']=0;
	    $statusId = sqlInsert("INSERT INTO arr_status (ac_uid,ac_pid,ac_encounter,ac_master_status,ac_status,ac_count,ac_comment,ac_reason) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$main['cl_list_slno']."','".$sub['cl_list_slno']."','".$count['ac_count']."','".$comment."','".$reason."')");
	    $master = sqlQuery("select am_id from arr_master where am_pid='$pid' and am_encounter='$encounter'");
	    $masterId = $master['am_id'];
	    if($masterId != '' && $masterId != 0){
		sqlQuery("UPDATE arr_master SET am_statustrack='".$statusId."', am_currentstatus='".$sub['cl_list_slno']."' WHERE am_pid='".$pid."' AND am_encounter='".$encounter."'");
	    }
	    else{
		$masterId = sqlInsert("INSERT INTO arr_master (am_uid,am_pid,am_encounter,am_statustrack,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel) VALUES ('".$_SESSION['authId']."','".$pid."','".$encounter."','".$statusId."','".$sub['cl_list_slno']."','$am_ins1','$am_ins2','$am_ins3','$am_inslevel')");
	    }
	    sqlQuery("UPDATE arr_status SET ac_arr_id='".$masterId."' WHERE ac_pid='".$pid."' AND ac_encounter='".$encounter."'");
       }
   }
   
    foreach($_GET as $key=>$value){
    ?>
    <input type="hidden" name="<?php echo $key;?>" value="<?php echo $value;?>">
    <?php
    }
    ?>
	<input type="hidden" name="randkey" value="">
	<input type="hidden" name="pwd" value="">
    </form>
</body>
</html>