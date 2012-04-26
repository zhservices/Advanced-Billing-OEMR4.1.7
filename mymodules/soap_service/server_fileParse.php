<?php
class FileParse extends eob
{
    public function file_summary_updation($data)
    {
	if(UserService::valid($data[0])){
	$emrflowtrack=new emrflowtrack();
	global $sqlconf,$sqlconf_common;
	$updated_pid_encounter = array();
	$level=0;		
	while($row=array_shift($data[1]))
	{
	    $row1=array();
	    //$connectionDB=$emrflowtrack->sql_connect_specific_db($sqlconf,$row['databasename']);
	    //$result1=$emrflowtrack->sqlExecute("SELECT * FROM arr_master WHERE am_pid='".$row['pid']."' AND am_encounter='".$row['encounter']."'");
	    //$row1=$emrflowtrack->sqlFetchRowArray($result1);
	    //if($row1['am_id'] && $row1['am_uid'])
	    //{
		//$emrflowtrack->sqlExecute("UPDATE arr_master SET am_clearinghouse_status='".$row['status']."' WHERE am_pid='".$row['pid']."' AND am_encounter='".$row['encounter']."'");
		$updated_pid_encounter[$level]['pid']=$row['pid'];
		$updated_pid_encounter[$level]['encounter']=$row['encounter'];
		
		if($row['flow_status']=='CLEARING-HOUSE'){
		    $updated_pid_encounter[$level]['flow_status']='CLEARING-HOUSE';		    
		    $main = 'CLEARING-HOUSE';
		    if(trim($row['status'])=='OFFICE ALLY ACCEPTED' || trim($row['status'])=='OFFICE ALLY INITIALLY')		    
			$sub = 'CLEARING-HOUSE-ACCEPTED';
		    elseif(trim($row['status'])=='OFFICE ALLY ERROR')
			$sub = 'CLEARING-HOUSE-REJECTED';
		}
		elseif($row['flow_status']=='INSURANCE'){
		    $updated_pid_encounter[$level]['flow_status']='INSURANCE';
		    $main = 'INSURANCE';
		    if($row['status']=='ACCEPTED')
			$sub = 'INSURANCE-ACCEPTED';
		    elseif($row['status']=='REJECTED')
			$sub = 'INITIAL-INSURANCE-REJECTION';
		}		
		$this->update_status(array($data[0],$main,$sub,$row['pid'],$row['encounter'],'File Parse updation (file:fileParser.php)',base64_decode($row['reason'])));
	    //}
	    $level++;
	}
	return $updated_pid_encounter;
	}
    }
    public function eradetails_updation($data)
    {
	global $con;
	list($cred, $eraDetails)=$data;
	if(UserService::valid($cred)){
	    $updated_accounts=array();
	    $level=0;
	    foreach($eraDetails as $row_temp)
	    {
		foreach($row_temp as $row)
		{
		if(is_file(dirname(__FILE__).'/../../sites/'.$row['site_folder'].'/sqlconf.php') && $row['site_folder']!=''){
		    global $sqlconf;
		    require(dirname(__FILE__).'/../../sites/'.$row['site_folder'].'/sqlconf.php');		    
		    $con=$this->sql_connect($sqlconf);
		    if(!$con) continue;
		    $newfile_name=$row['filename'];
		    
		    $path=dirname(__FILE__).'/../../sites/'.$row['site_folder'].'/edi/master';
                    $to_path=dirname(__FILE__).'/../../sites/'.$row['site_folder'].'/edi';
		    if(!is_dir($path))
			mkdir($path);
		    if(!is_dir($to_path))
			mkdir($to_path);
		    
		    $fERA=fopen("$path/$newfile_name","w");
		    fwrite($fERA,base64_decode($row['message']));
		    fclose($fERA);
                    $file="$path/$newfile_name";
		    $check_chkno = sqlStatement("select * from era_details where check_number='".$row['check_number']."'");
		    $era_det_id = '';
		    if(sqlNumRows($check_chkno)==0)
			$era_det_id=$this->sqlExecuteDB($con,"replace into era_details (check_number,check_amount,payer_name,filename,data) values ('".$row['check_number']."','".$row['check_amount']."','".$row['payer_name']."','".$newfile_name."','".mysql_real_escape_string(base64_decode($row['message']))."')","mysql_insert_id");
		    $this->sqlExecuteDB($con,"update x12_partners set last_download_date=NOW() where x12_username='".$row['uname']."'");
		    $updated_accounts[$level]['uname']=$row['uname'];
		    $updated_accounts[$level]['site_folder']=$row['site_folder'];
		    $updated_accounts[$level]['filetodelete']=$row['filetodelete'];
                    $xml = $this->get_parse_era(array($cred,$file, 'era_callback_1'));
                    $eraname = $row['gs_date'] . '_' . ltrim($row['isa_control_number'], '0') . '_' . ltrim($row['payer_id'], '0');
                    $i=1;
                    while(file_exists("$to_path/$eraname$era_suffix.edi"))
                    {
                        $era_suffix = "_".$i;
                        $i++;
                    }
                    $eraname = $eraname.$era_suffix;
                    $erafullname = "$to_path/$eraname.edi";
                    
                    $this->copy_file(array($cred,$file,$erafullname));    
                    $data_value=array();
                    $data_value["CheckSubmit"] = "Submit";
                    $chk = $row['check_number'];                
                    $data_value["chk$chk"] = $chk;
                    $data_value["chk_number"] = $chk;
                    $data_value["eraname"] = $eraname;
                    
                    $data_value["debug"] = 1;
                    $data_value["paydate"] = date("Y-m-d");
                    $data_value["post_to_date"] = date("Y-m-d");
                    $data_value["deposit_date"] = date("Y-m-d");
                    $data_value["InsId"] = 7;
                    $data_value["era_id"] = $era_det_id;
                    $this->get_sl_eob_process(array($cred,$row['site_folder'],$data_value));
		    $level++;
		}
		}
	    }
	}
	return $updated_accounts;
    }    
}
?>