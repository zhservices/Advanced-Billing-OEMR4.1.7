<?php
set_time_limit(96000);
$ignoreAuth=true;
include_once('interface/globals.php');
include_once("$srcdir/sl_eob.inc.php");
include_once("$srcdir/invoice_summary.inc.php");
//require_once("sites/".$_GET['site']."/sqlconf.php");
///////////////////////////////////////////////////ALTERATION//////////////////////////////////////////////
$host=$sqlconf['host'];
$login=$sqlconf['login'];
$pass=$sqlconf['pass'];
$dbase=$sqlconf['dbase'];
$portVal=$sqlconf['port'];
if(!mysql_connect($host,$login,$pass))
 {
  echo "None of the databases are altered.<br>Reason==>Database connection could not be established.";
  die;
 }
if(!mysql_select_db($dbase))
 {
  echo "None of the databases are altered.<br>Reason==>Database $dbase doesn't exist.";
  die;
 }
$filename='openemr.sql';
$query_from_file=fread(fopen($filename, "r"), filesize($filename));
$query_from_file=trim($query_from_file);
if($query_from_file[strlen($query_from_file)-1]!=';')
 {
  echo "None of the databases are altered.<br>Reason==>Each query should end with a semi colon(;) <b>including the last one</b>.";
  die;
 }
 $queries = explode(";", $query_from_file);
 $querycount=0;
	foreach ($queries as $query)
	 {
	   if (strlen(trim($query)) > 0)
	    {
		 $querycount++;
		 if(mysql_query($query)===false)
		  {
			$count++;
			$erro_details.= "<br><br>$count)Error in query.Database==>[$value].Query $querycount==>$query";
			$erro_details.= "<br>&nbsp;&nbsp;&nbsp;Error returned==>".mysql_error();
		  }
		} 
	 }
///////////////////////////////////////////////CUSTOMLISTS ENTRY////////////////////////////////////////
 $datbase[]=$sqlconf["dbase"];


foreach($datbase as $valDB)
{
    $sqlconf["dbase"] = $valDB;
    $connectionCommonDatabase='';
    
    //$connectionCommonDatabase=sql_connect_specific_db($sqlconf,$valDB);    
    
    $mainlistType='15';
    $sublistType='20';
    $arrMain=array('ENCOUNTER','CODE','QC','BILLING','PATIENT','INSURANCE','CLEARING-HOUSE');
    foreach($arrMain as $valMain)
    {
        $res_check_main=mysql_query("SELECT * FROM customlists WHERE cl_list_type='".$mainlistType."' AND cl_list_item_short='".$valMain."'");
        $row_check_main=mysql_fetch_array($res_check_main);
        $insert_id=$row_check_main['cl_list_slno'];
        if(mysql_numrows($res_check_main)==0){
            mysql_query("INSERT INTO customlists (cl_list_type,cl_list_item_short) VALUES ('".$mainlistType."','".$valMain."')");
            $insert_id=mysql_insert_id();
        }
        mysql_query("UPDATE customlists SET cl_list_id='".$insert_id."',cl_list_item_id='".$insert_id."' WHERE cl_list_slno='".$insert_id."'");
        
        $arr['ENCOUNTER']=array('ENCT_INI_A','ENCT_INI_M','ENCT_DELETE');
        $arr['CODE']=array('ENCT_CODE','ENCT_CODE_DONE');
        $arr['QC']=array('ENCT_READYFORQC','ENCT_READYTOBILL','ENCT_FAILED_QC','ENCT_FAILED_CORRECTED','ENCT_QC_COMPLETED');
        $arr['BILLING']=array('ENCT_BILLEDPRI','ENCT_BILLEDSEC','ENCT_BILLEDTER','ENCT_REJECTPRI','ENCT_REJECTSEC','ENCT_REJECTTER','ENCT_PAIDPRI','ENCT_PAIDSEC','ENCT_PAIDTER','ENCT_REFILE','ENCT_ZERO_BALANCE','ENCT_READYTOBILLPRI','ENCT_READYTOBILLSEC','ENCT_READYTOBILLTER');
        $arr['PATIENT']=array('ENCT_PATIENT_BALANCE');
        $arr['INSURANCE']=array('INSURANCE-ACCEPTED','INITIAL-INSURANCE-REJECTION');
        $arr['CLEARING-HOUSE']=array('CLEARING-HOUSE-ACCEPTED','CLEARING-HOUSE-REJECTED');
        
        foreach($arr[$valMain] as $val)
        {
            $res_check_sub=mysql_query("SELECT * FROM customlists WHERE cl_list_type='".$sublistType."' AND cl_list_item_short='".$val."'");
            $row_check_sub=mysql_fetch_array($res_check_sub);
            $insert_id_sub=$row_check_sub['cl_list_slno'];
            if(mysql_numrows($res_check_sub)==0){
                mysql_query("INSERT INTO customlists (cl_list_type,cl_list_item_short) VALUES ('".$sublistType."','".$val."')");
                $insert_id_sub=mysql_insert_id();
            }
            mysql_query("UPDATE customlists SET cl_list_id='".$insert_id."',cl_list_item_id='".$insert_id_sub."' WHERE cl_list_slno='".$insert_id_sub."'");            
        }
    }
}

////////////////////////////////////STATUS UPDATE////////////////////////////////////////////
$sql='select pid,date,encounter from form_encounter';
$tr = 'START TRANSACTION';
$co = 'COMMIT';
$rs=sqlStatement($sql);

sqlStatement($tr);
$i=0;
$j=1000;
while($row=sqlFetcharray($rs)){
    $i++;
if($i==$j){
 sqlStatement($co);
 sqlStatement($tr);
 $j = $j + 1000;
}
$encounter=$row['encounter'];
$pid=$row['pid'];
$dt=$row['date'];
if($row['encounter']>0){
$x=fee_sheet_status($encounter);
}
else{
continue;
}
if($x==0)//not coded
$stat='ENCT_INI_M';
elseif($x==1)//code complete
$stat='ENCT_CODE_DONE';
elseif($x==2)//partially coded
$stat='ENCT_CODE';
elseif($x==4)//Ready for Qc
$stat='ENCT_READYFORQC';
status_update($pid,$encounter,$dt,$stat);
$rp=ar_responsible_party($pid,$encounter);
if($x==1 && ($rp==1 ||$rp==2 ||$rp==3)){
$sql="select last_level_billed,last_level_closed from form_encounter where encounter=$encounter";
//echo $sql."<br>";
$rs2=sqlQuery($sql);
$stat="";
if(($rp==$rs2['last_level_billed'] )&& ($rp >$rs2['last_level_closed'])){
    if($rp==1)
    $stat='ENCT_BILLEDPRI';
    elseif($rp==2)
    $stat='ENCT_BILLEDSEC';
    elseif($rp==3)
    $stat='ENCT_BILLEDTER';

}
elseif($rs2['last_level_billed']< $rp){
    if($rp==1)
    $stat='ENCT_READYTOBILLPRI';
    elseif($rp==2)
    $stat='ENCT_READYTOBILLSEC';
    elseif($rp==3)
    $stat='ENCT_READYTOBILLTER';
    }

elseif($rp==0){//patient balance
$stat='ENCT_PATIENT_BALANCE';
}
}
if($stat){
status_update($pid,$encounter,$dt,$stat);
}

}

function status_update($pid,$enc,$enc_dt,$stat){
$m_insert_id=0;
//checking whether entry for encounter already exist in arr_master
$sql="select am_id from arr_master where am_encounter=$enc ";
//echo $sql."<br>";

$rs=sqlQuery($sql);
$m_insert_id=$rs['am_id'];

$sql="select cl_list_id,cl_list_item_id from customlists where cl_list_item_short='$stat'";
//echo $sql."<br>";

$rs=sqlQuery($sql);
if(!$m_insert_id){
$am_ins1=arGetPayerID($pid,$enc_dt,1);
$am_ins2=arGetPayerID($pid,$enc_dt,2);
$am_ins3=arGetPayerID($pid,$enc_dt,3);
$am_inslevel=ar_responsible_party($pid,$enc);
if(!$am_ins1)
$am_ins1=0;
if(!$am_ins2)
$am_ins2=0;
if(!$am_ins3)
$am_ins3=0;
if($am_inslevel>0 && $am_inslevel<4){
}
else{
$am_inslevel=0;
}
 
$sql="insert into arr_master(am_pid,am_encounter,am_currentstatus,am_ins1,am_ins2,am_ins3,am_inslevel)
values($pid,$enc,".$rs['cl_list_item_id'].",$am_ins1,$am_ins2,$am_ins3,$am_inslevel)";
//echo $sql."<br>";

$m_insert_id=sqlInsert($sql);
}
else{
$sql="update arr_master set am_currentstatus=".$rs['cl_list_item_id']. " where am_encounter=$enc" ;
//echo $sql."<br>";

sqlStatement($sql);
}
$sql="insert into arr_status(ac_pid,ac_encounter,ac_arr_id,
ac_master_status,ac_status,ac_comment)values
($pid,$enc,$m_insert_id,".$rs['cl_list_id'].",".$rs['cl_list_item_id'].",'initial')";
//echo $sql."<br>";

$c_insert_id=sqlInsert($sql);
$sql="update arr_master set  am_statustrack=$c_insert_id where am_encounter=$enc";
//echo $sql."<br>";

sqlStatement($sql);
}
//function for finding out whether FEE sheet entered and justified and is ready to be billed
function fee_sheet_status($enc){
$sql="select code from billing where code_type='CPT4' and activity=1 and encounter=$enc";
//echo $sql."<br>";

$cnt=sqlNumRows(sqlStatement($sql));
if($cnt){
$sql="select code from billing where code_type='CPT4' and activity=1 and encounter=$enc 
and (justify='' or justify is null)";
//echo $sql."<br>";

$cnt2=sqlNumRows(sqlStatement($sql));
if($cnt2){
return 2;//partially coded
}
else{
while($row = sqlFetchArray($res)){
    if($row['authorized']!=1 && $row['billed']!=1)
    return 4;//Ready For QC
}
return 1;//Ready to billed
}
}
else{
return 0;//uncoded
}
}
$co = 'COMMIT';
sqlStatement($co);
echo 'completed'
 ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Query</title>
</head>
<body>
Finished.
<?php
if($erro_details!='')
 {
  echo "Please fix the below,all other queries are executed.<br>";
  echo $erro_details;
 }
?>
</body>
</html>