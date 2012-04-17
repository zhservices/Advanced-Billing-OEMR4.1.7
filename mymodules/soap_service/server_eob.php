<?php
class eob{
    public function global_update(){
        $query1="update form_encounter set last_level_closed=0 where encounter
        in(select distinct b.encounter from billing b  left outer join
        ar_activity aa on aa.encounter=b.encounter and aa.code=b.code
        and aa.modifier=b.modifier and account_code<>'PCP'
        where (b.code_type='CPT4' or b.code_type='HCPCS') and aa.pid is null
        and b.activity=1) and last_level_closed<>4";
        sqlStatement($query1);
        return 1;
    }
    public function update_fe($data)
    {
        list($cred, $today, $encounter)=$data;
        if(UserService::valid($cred)){
        sqlStatement("UPDATE form_encounter SET " .
                     "last_stmt_date = '$today', stmt_count = stmt_count + 1 " .
                     "WHERE id = '" . $encounter . "'");
        return 1;
        }
    }
    public function get_ar_generate_statement_batch($data)
    {
        require_once(dirname(__FILE__).dirname(__FILE__)."/../../library/invoice_summary.inc.php");
        list($cred, $enc_id_array)=$data;
        if(UserService::valid($cred)){
        $column = 'id';        
        $stmt_arr = array();
    
        if (count($enc_id_array) == 0)
            return $stmt_arr;
    
        if ($column != 'encounter' && $column != 'id')
            $column = 'id';
    
        $today = date("Y-m-d");
        $where = "";
        foreach ($enc_id_array as $key => $value) {
            $where .= " OR f." . $column . " = $key";
        }
        $where = substr($where, 4);
    
        $query = "SELECT " .
            "f.id, f.date, f.pid, f.encounter, f.stmt_count, f.last_stmt_date, " .
            "p.fname, p.mname, p.lname, p.street, p.city, p.state, p.postal_code " .
            "FROM form_encounter AS f, patient_data AS p " .
            "WHERE ( $where ) AND " .
            "p.pid = f.pid " .
            "ORDER BY p.lname, p.fname, f.pid, f.date, f.encounter";
        $res = sqlStatement($query);
    
        $stmt = array();
    
        // This loops once for each invoice/encounter.
        //
        while ($row = sqlFetchArray($res)) {
            $svcdate = substr($row['date'], 0, 10);
            $duedate = $svcdate; // TBD?
            $duncount = $row['stmt_count'];
        
            // If this is a new patient then print the pending statement
            // and start a new one.  This is an associative array:
            //
            //  cid     = same as pid
            //  pid     = OpenEMR patient ID
            //  patient = patient name
            //  amount  = total amount due
            //  adjust  = adjustments (already applied to amount)
            //  duedate = due date of the oldest included invoice
            //  age     = number of days from duedate to today
            //  to      = array of addressee name/address lines
            //  lines   = array of:
            //    dos     = date of service "yyyy-mm-dd"
            //    desc    = description
            //    amount  = charge less adjustments
            //    paid    = amount paid
            //    notice  = 1 for first notice, 2 for second, etc.
            //    detail  = array of details, see invoice_summary.inc.php
            //
            if ($stmt['cid'] != $row['pid']) {  // new patient in the batch
                if (!empty($stmt)) {
                    $stmt_arr[] = $stmt;
                    $stmt = array();
                }
                $stmt['cid'] = $row['pid'];
                $stmt['pid'] = $row['pid'];
                $stmt['patient'] = $row['fname'] . ' ' . $row['lname'];
                $stmt['to'] = array($row['fname'] . ' ' . $row['lname']);
                if ($row['street']) $stmt['to'][] = $row['street'];
                $stmt['to'][] = $row['city'] . ", " . $row['state'] . " " . $row['postal_code'];
                $stmt['lines'] = array();
                $stmt['amount'] = '0.00';
                $stmt['today'] = $today;
                $stmt['duedate'] = $duedate;
                // array of encounter ID's for this patient
                $stmt['encounter_list'] = array(($column == 'id') ? $row['id'] : $row['encounter']);
            }
            else {                           // existing patient in the batch
                // Report the oldest due date.
                if ($duedate < $stmt['duedate']) {
                    $stmt['duedate'] = $duedate;
                }
                $stmt['encounter_list'][] = ($column == 'id') ? $row['id'] : $row['encounter'];
            }
    
            // Recompute age at each invoice.
            $stmt['age'] = round((strtotime($today) - strtotime($stmt['duedate'])) / (24 * 60 * 60));
    
            $invlines = ar_get_invoice_summary($row['pid'], $row['encounter'], true);
            foreach ($invlines as $key => $value) {
                $line = array();
                $line['dos']     = $svcdate;
                $line['desc']    = ($key == 'CO-PAY') ? "Patient Payment" : "Procedure $key";
                $line['amount']  = sprintf("%.2f", $value['chg']);
                $line['adjust']  = sprintf("%.2f", $value['adj']);
                $line['paid']    = sprintf("%.2f", $value['chg'] - $value['bal']);
                $line['notice']  = $duncount + 1;
                $line['detail']  = $value['dtl'];
                $stmt['lines'][] = $line;
                $stmt['amount']  = sprintf("%.2f", $stmt['amount'] + $value['bal']);
            }
        } // end while
    
        if (!empty($stmt)) {
            $stmt_arr[] = $stmt;
        }
    
        return $stmt_arr;
        }
    }      
    public function upload_file_to_client_pdf($file_to_send) {
        //Function reads a text file and converts to pdf.
      
        global $webserver_root;
        $pdf =& new Cezpdf('LETTER');//pdf creation starts
        $pdf->ezSetMargins(36,0,36,0);
        $pdf->selectFont($GLOBALS['fileroot'] . "/library/fonts/Courier.afm");
        $pdf->ezSetY($pdf->ez['pageHeight'] - $pdf->ez['topMargin']);
        $countline=1;
        $file = fopen($file_to_send, "r");//this file contains the text to be converted to pdf.
        while(!feof($file))
        {
          $OneLine=fgets($file);//one line is read
          if(stristr($OneLine, "\014") == true && !feof($file))//form feed means we should start a new page.
          {
            $pdf->ezNewPage();
            $pdf->ezSetY($pdf->ez['pageHeight'] - $pdf->ez['topMargin']);
                 str_replace("\014", "", $OneLine);
          }
              
          if(stristr($OneLine, 'REMIT TO') == true || stristr($OneLine, 'Visit Date') == true)//lines are made bold when 'REMIT TO' or 'Visit Date' is there.
           $pdf->ezText('<b>'.$OneLine.'</b>', 12, array('justification' => 'left', 'leading' => 6)); 
          else
           $pdf->ezText($OneLine, 12, array('justification' => 'left', 'leading' => 6)); 
           
          $countline++; 
        }
      
        // generate unique name for the patient statement PDF file
        $stmt_time = time();
        $stmt_filename = "PatientStatement-" . date("Y-m-d-Hi", $stmt_time) . ".pdf";
      
        // changed the "edi" folder location to the "sites" folder
        // 9/23/2010   HB 
        $fh = @fopen($GLOBALS['OE_SITE_DIR'] . "/edi/$stmt_filename", 'w');
        //if ($fh) {
        //  fwrite($fh, $pdf->ezOutput());
        //  fclose($fh);
        //}
        //header("Pragma: public");//this section outputs the pdf file to browser
        //header("Expires: 0");
        //header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        //header("Content-Type: application/force-download");
        //header("Content-Length: " . filesize($GLOBALS['OE_SITE_DIR'] . "/edi/$stmt_filename"));
        //header("Content-Disposition: attachment; filename=" . $stmt_filename);
        //header("Content-Description: File Transfer");
        //readfile($GLOBALS['OE_SITE_DIR'] . "/edi/$stmt_filename");
        //// flush the content to the browser. If you don't do this, the text from the subsequent
        //// output from this script will be in the file instead of sent to the browser.
        //flush();
        //// sleep one second to ensure there's no follow-on.
        //sleep(1);
        return $pdf->ezOutput();
    }
    public function get_file_list($data)
    {
        list($cred,$folder_path)=$data;
        if(UserService::valid($cred)){
        if($handle = opendir($folder_path)){
            $file_list=array();
            while (false !== ($file = readdir($handle))) {
                if($file!='.' && $file!='..')
                $file_list[]=$file;
            }
            closedir($handle);
            return $file_list;
        }
        else{
            return "Failed to open folder";
        }
        }
    }
    public function get_pdf_details($data)
    {
        list($cred,$dat)=$data;
        if(UserService::valid($cred)){
            $where = '';
            $wherearray=array();
            foreach($dat as $k=>$v)
            {
                $where .= " OR f.id = ?";
                $wherearray[]=$v;
            }
            $where = substr($where, 4);
            if(!$where)
            {
                $where='?';
                $wherearray[]=0;
            }
            $query1 = "Select f.id, f.date, f.pid, f.encounter, f.stmt_count, f.last_stmt_date, f.facility_id,f.billing_facility, " .			   
                "p.fname, p.mname, p.lname, p.street, p.city, p.state, p.postal_code " .
                ",u.fname as dfname, u.mname as dmname, u.lname as dlname, ".
                "f.last_level_closed, " .
                "fa.name AS faname,fa.street AS fastreet,fa.city AS facity,fa.state AS fastate,fa.postal_code AS fapostal_code,fa.phone AS faphone, ".
                "COUNT( DISTINCT `type` ) AS NumberOfInsurance ".
                "from ((form_encounter AS f, patient_data AS p) " .		
                "left join users as u on f.provider_id =u.id) ".
                "left join facility as fa on fa.id =f.billing_facility ".
                "LEFT JOIN insurance_data AS idat ON idat.pid=f.pid ".
                "WHERE ( $where) AND " .
                "p.pid = f.pid " .
                "GROUP BY f.pid ".
                "ORDER BY f.pid,f.billing_facility, f.date desc, f.encounter desc";
            $resQ1 = sqlStatement($query1,$wherearray);
            $resultSet = array();
            $i=0;
            while($rowQ1 = sqlFetchArray($resQ1)){
                foreach($rowQ1 as $key1=>$value1){
                    $resultSet[$i][$key1] = $value1;
                }
                $resQ2 = sqlStatement("select date, code_type, code, modifier, code_text, fee , units, justify  from billing " .
                    "WHERE  encounter =? AND pid = ? AND activity = 1 AND fee != 0.00 ORDER BY  fee desc,code,modifier",array($rowQ1['encounter'],$rowQ1['pid']));
                $j=0;
                while($rowQ2 = sqlFetchArray($resQ2)){
                    foreach($rowQ2 as $key2=>$value2){
                        $resultSet[$i]['copay_fee'][$j][$key2] = $value2;
                    }
                    $j++;
                }
                $resQ3 = sqlStatement("select s.drug_id, s.sale_date, s.fee, s.quantity from drug_sales AS s " .
                    "WHERE  s.encounter = ? and s.pid = ? AND s.fee != 0 " .
                    "ORDER BY s.sale_id",array($rowQ1['encounter'],$rowQ1['pid']));
                $j=0;
                while($rowQ3 = sqlFetchArray($resQ3)){
                    foreach($rowQ3 as $key3=>$value3){
                        $resultSet[$i]['drug_sales'][$j][$key3] = $value3;
                    }
                    $j++;
                }
                $resQ4 = sqlStatement("Select a.code, a.modifier, a.memo, a.payer_type, a.adj_amount, a.pay_amount, " .
                    "a.post_time, a.session_id, a.sequence_no,a.follow_up, a.follow_up_note, " .
                    "s.payer_id, s.reference, s.check_date, s.deposit_date " .
                    ",i.name from ar_activity AS a " .
                    "LEFT OUTER JOIN ar_session AS s ON s.session_id = a.session_id " .
                    "LEFT OUTER JOIN insurance_companies AS i ON i.id = s.payer_id " .
                    "WHERE  a.encounter = ? and a.pid = ? " .
                    "ORDER BY s.check_date, a.sequence_no", array($rowQ1['encounter'],$rowQ1['pid']));
                $j=0;
                while($rowQ4 = sqlFetchArray($resQ4)){
                    foreach($rowQ4 as $key4=>$value4){
                        $resultSet[$i]['payments'][$j][$key4] = $value4;
                    }
                    $j++;
                }
                $resQ5 = sqlStatement("Select distinct u.fname, u.mname, u.lname ".
                    "from (form_encounter AS f, billing AS b) " .
                    "left join users as u on f.provider_id =u.id ".
                    "WHERE f.pid = b.pid and  f.encounter = b.encounter " .
                    "and f.encounter=? and f.pid=? ".
                    "ORDER BY u.fname, u.lname", array($rowQ1['encounter'],$rowQ1['pid']));
                $j=0;
                while($rowQ5 = sqlFetchArray($resQ5)){
                    foreach($rowQ5 as $key5=>$value5){
                        $resultSet[$i]['doctor_list'][$j][$key5] = $value5;
                    }
                    $j++;
                }
                $i++;
            }
            return $resultSet;
        }
    }
}
?>