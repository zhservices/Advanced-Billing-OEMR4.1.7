<?php
class Billing extends Payment
{
    public function process_billing_file($data)
    {
        if(UserService::valid($data[0])){        
        global $pdf,$bat_filename,$patient_id, $encounter, $payer_id, $payer_type,$sqlconf;
        $_POST=$data[1];
        require_once(dirname(__FILE__)."/billing_process.php");
        if($data[1]['bn_process_hcfa']){
            $pdf = & new Cezpdf('LETTER');
            $pdf->ezSetMargins(trim($data[1]['top_margin'])+0,0,trim($data[1]['left_margin'])+0,0);
            $pdf->selectFont(dirname(__FILE__) . "/../../library/fonts/Courier.afm");
            $bat_filename = isset($data[1]['bn_process_hcfa']) ? str_replace('.txt','.pdf',$bat_filename) : $bat_filename;
        }
        process_form($data[1],$pdf);
        }
    }
    public function update_for_view_hcfa($data)
    {
        list($cred,$patient_id,$encounter,$old_payer_id)=$data;
        if(UserService::valid($cred)){
        if($old_payer_id)
            sqlStatement("UPDATE billing SET billed=0, payer_id = '$old_payer_id' WHERE encounter = '$encounter' AND pid='$patient_id' AND activity = 1");
        else
            sqlStatement("UPDATE billing SET billed=0, payer_id = NULL WHERE encounter = '$encounter' AND pid='$patient_id' AND activity = 1");
        //sqlStatement("DELETE FROM claims WHERE patient_id=? AND encounter_id=? ORDER BY `version` DESC LIMIT 1",array($patient_id,$encounter));
        return 1;
        }
    }
    public function get_generated_file_content($data)
    {
        list($cred,$pid_enc,$bn_process_hcfa,$delete)=$data;
        if(UserService::valid($cred)){
        $bat_content='';
        for($i=0;$i<count($pid_enc);$i++)
        {    
            $arr=array();
            $p=$pid_enc[$i][0];
            $e=$pid_enc[$i][1];
            if($bn_process_hcfa=='bn_view_hcfa')
            {
              $query_view="select payer_id from billing WHERE encounter = '$e' AND pid='$p' AND activity = 1";
              $data_view=sqlQuery($query_view);
              $update_v_h=$this->update_for_view_hcfa(array($cred,$p,$e,$data_view['payer_id']));
            }
            $payer_type=$pid_enc[$i][2];
            $query="select process_file from claims where patient_id='".$p."' and encounter_id='".$e."' order by bill_time desc limit 1";
            $data=sqlQuery($query);
            if($delete == "delete")//for fixing view hcfa pdf download.
            sqlStatement("DELETE FROM claims WHERE patient_id=? AND encounter_id=? ORDER BY `version` DESC LIMIT 1",array($p,$e));
            $file=fopen(dirname(__FILE__)."/../../sites/".$_SESSION['site_id']."/edi/".$data['process_file'],'r');
            $bat_content = fread($file,filesize($GLOBALS['OE_SITE_DIR']."/edi/".$data['process_file']));
            
            if($bn_process_hcfa!='bn_view_hcfa'){
                if($payer_type=='P')
                {
                    $query_refile="SELECT count(*) AS count_val FROM arr_status AS ar, customlists AS c WHERE ar.ac_pid='$p' AND ar.ac_encounter='$e' AND c.cl_list_slno=ar.ac_status AND c.cl_list_item_short='ENCT_BILLEDPRI'";
                    $data_refile=sqlQuery($query_refile);
                    $this->update_status(array($cred,'BILLING','ENCT_BILLEDPRI',$p,$e,'Updation on generating X12/HCFA file (Billing Portal)'));
                    $this->update_arr_master_insurance(array($cred,$p,$e,$ins1,$ins2,$ins3,1));                    
                    if($data_refile['count_val']>0){
                        $this->update_status(array($cred,'BILLING','ENCT_REFILE',$p,$e,'Updation on generating X12/HCFA file : ReFile (Billing Portal)'));
                    }
                }
                elseif($payer_type=='S')
                {
                    $query_refile="SELECT count(*) AS count_val FROM arr_status AS ar, customlists AS c WHERE ar.ac_pid='$p' AND ar.ac_encounter='$e' AND c.cl_list_slno=ar.ac_status AND c.cl_list_item_short='ENCT_BILLEDSEC'";
                    $data_refile=sqlQuery($query_refile);
                    $this->update_status(array($cred,'BILLING','ENCT_BILLEDSEC',$p,$e,'Updation on generating X12/HCFA file (Billing Portal)'));
                    $this->update_arr_master_insurance(array($cred,$p,$e,$ins1,$ins2,$ins3,2));                    
                    if($data_refile['count_val']>0){
                        $this->update_status(array($cred,'BILLING','ENCT_REFILE',$p,$e,'Updation on generating X12/HCFA file : ReFile (Billing Portal)'));
                    }
                }
                elseif($payer_type=='T')
                {
                    $query_refile="SELECT count(*) AS count_val FROM arr_status AS ar, customlists AS c WHERE ar.ac_pid='$p' AND ar.ac_encounter='$e' AND c.cl_list_slno=ar.ac_status AND c.cl_list_item_short='ENCT_BILLEDTER'";
                    $data_refile=sqlQuery($query_refile);
                    $this->update_status(array($cred,'BILLING','ENCT_BILLEDTER',$p,$e,'Updation on generating X12/HCFA file (Billing Portal)'));
                    $this->update_arr_master_insurance(array($cred,$p,$e,$ins1,$ins2,$ins3,3));                    
                    if($data_refile['count_val']>0){
                        $this->update_status(array($cred,'BILLING','ENCT_REFILE',$p,$e,'Updation on generating X12/HCFA file : ReFile (Billing Portal)'));
                    }
                }
            }
        }        
        return base64_encode($bat_content);
        }
    }
    public function ChangedClaimStatusBillingManager($data){
    list($cred,$last_billed,$last_closed,$patient_id,$encounter_id,$type)=$data;
    if(UserService::valid($cred)){
        $p=$patient_id;$e=$encounter_id;
        if($type=='billed_sec'){//Billed in Sec
            $payer_type_val = 2;
            $main = 'BILLING';
            $sub = 'ENCT_BILLEDSEC';
        }
        elseif($type=='need_tobe_bill_sec'){//Need to be bill in Sec
            $main = 'BILLING';
            $sub = 'ENCT_READYTOBILLSEC';
        }
        elseif($type=='patient_balance'){//Patient Bal
            $main = 'PATIENT';
            $sub = 'ENCT_PATIENT_BALANCE';
        }
        elseif($type=='zero_balance'){//Zero balance
            $main = 'BILLING';
            $sub = 'ENCT_ZERO_BALANCE';
        }
        $this->update_status(array($cred,$main,$sub,$p,$e,'Updation from Billing Manager by right clicking on a patient (Billing Portal)'));
        $this->update_arr_master_insurance(array($cred,$p,$e,$ins1,$ins2,$ins3,$payer_type_val));
        if($last_billed && $last_closed)
        sqlStatement("UPDATE form_encounter SET last_level_billed = ?, last_level_closed=? WHERE pid=? AND encounter=?",array($last_billed,$last_closed,$patient_id,$encounter_id));
        sqlStatement("UPDATE billing SET bill_process=0 WHERE pid=? AND encounter=? AND bill_process=7 AND activity=1",array($patient_id,$encounter_id));
        sqlStatement("UPDATE claims SET bill_process=0 WHERE patient_id=? AND encounter_id=? AND bill_process=7 AND version=(SELECT MAX(version)
                     FROM claims WHERE patient_id=? AND encounter_id=?)",array($patient_id,$encounter_id,$patient_id,$encounter_id));
    }
    }
    public function authThisClaim($data){
    list($cred,$pid,$encounter) = $data;
    if(UserService::valid($cred)){
        $stat = sqlQuery("SELECT * FROM form_encounter WHERE pid=? AND encounter=?",array($pid,$encounter));
        sqlStatement("UPDATE billing SET authorized=1 WHERE pid=? AND encounter=? AND activity=1",array($pid,$encounter));
        //Set status as QC Completed
        $main = 'QC';
        $sub = 'ENCT_QC_COMPLETED';
        $this->update_status(array($cred,$main,$sub,$pid,$encounter,'Update QC Completed from Billing Manager by right clicking on a patient (Billing Portal)'));
        
        if($stat['last_level_billed']=='4' && $stat['last_level_closed']=='4'){
        $main = 'PATIENT';
        $sub = 'ENCT_PATIENT_BALANCE';
        }
        else{
        $main = 'BILLING';
        $sub = 'ENCT_READYTOBILL';
        }
        $this->update_status(array($cred,$main,$sub,$pid,$encounter,'Updation from Billing Manager by right clicking on a patient (Billing Portal)'));
    }
    }
    public function authFailedClaim($data){
        list($cred,$pid,$encounter) = $data;
        if(UserService::valid($cred)){
            $main = 'QC';
            $sub = 'ENCT_FAILED_QC';
            $this->update_status(array($cred,$main,$sub,$pid,$encounter,'Update failed qc from Billing Manager by right clicking on a patient (Billing Portal)'));            
        }
    }
    public function authQcCorrected($data){
        list($cred,$pid,$encounter) = $data;
        if(UserService::valid($cred)){
            $main = 'QC';
            $sub = 'ENCT_FAILED_CORRECTED';
            $this->update_status(array($cred,$main,$sub,$pid,$encounter,'Update qc corrected from Billing Manager by right clicking on a patient (Billing Portal)'));
        }
    }
}
?>