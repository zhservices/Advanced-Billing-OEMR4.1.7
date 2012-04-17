<?php
// +-----------------------------------------------------------------------------+ 
// Copyright (C) 2010 Z&H Consultancy Services Private Limited <sam@zhservices.com>
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
//           Paul Simon K <paul@zhservices.com> 
//
// +------------------------------------------------------------------------------+
//===============================================================================
//This section handles the common functins of payment screens.
//===============================================================================
require_once(dirname(__FILE__)."/../../../../library/invoice_summary.inc.php");
function DistributionInsert($CountRow,$created_time,$user_id)
 {//Function inserts the distribution.Payment,Adjustment,Deductable,Takeback & Follow up reasons are inserted as seperate rows.
 //It automatically pushes to next insurance for billing.
 //In the screen a drop down of Ins1,Ins2,Ins3,Pat are given.The posting can be done for any level.
	global $Denial;
	if($Denial!='yes')
	 {
		$Denial='no';
	 }
	$Affected='no';
  if (isset($_POST["Payment$CountRow"]) && $_POST["Payment$CountRow"]*1>0)
   {
		if(trim(formData('type_name'   ))=='insurance')
		 {
		  if(trim(formData("HiddenIns$CountRow"   ))==1)
		   {
			  $AccountCode="IPP";
		   }
		  if(trim(formData("HiddenIns$CountRow"   ))==2)
		   {
			  $AccountCode="ISP";
		   }
		  if(trim(formData("HiddenIns$CountRow"   ))==3)
		   {
			  $AccountCode="ITP";
		   }
		 }
		elseif(trim(formData('type_name'   ))=='patient')
		 {
		  $AccountCode="PP";
		 }
	  sqlStatement("insert into ar_activity set "    .
		"pid = '"       . trim(formData('hidden_patient_code' )) .
		"', encounter = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
		"', code = '"      . trim(formData("HiddenCode$CountRow"   ))  .
		"', modifier = '"      . trim(formData("HiddenModifier$CountRow"   ))  .
		"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
		"', post_time = '"  . trim($created_time					) .
		"', post_user = '" . trim($user_id            )  .
		"', session_id = '"    . trim(formData('payment_id')) .
		"', modified_time = '"  . trim($created_time					) .
		"', pay_amount = '" . trim(formData("Payment$CountRow"   ))  .
		"', adj_amount = '"    . 0 .
		"', account_code = '" . "$AccountCode"  .
		"'");
	  $Affected='yes';
	     if($GLOBALS['flow_track']==1)
	     {
			  global $connectionFlow;
			  /*$main = get_main_status_id($connectionFlow,'BILLING');
			  if(trim(formData("HiddenIns$CountRow"   ))==1)
			  $sub = get_sub_status_id($connectionFlow,'ENCT_PAIDPRI');
			  elseif(trim(formData("HiddenIns$CountRow"   ))==2)
			  $sub = get_sub_status_id($connectionFlow,'ENCT_PAIDSEC');
			  elseif(trim(formData("HiddenIns$CountRow"   ))==3)
			  $sub = get_sub_status_id($connectionFlow,'ENCT_PAIDTER');
			  $count=get_count($connectionFlow,trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )),$main,$sub);
			  if(final_code_check($connectionFlow,$sub,trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )))==0)
			  {
			      $arrstatustrackid = insert_arr_status($connectionFlow,$_SESSION['authId'],trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )),'',$main,$sub,'','','','','','','',$count,'Manual Payment Distribution(function:DistributionInsert)');
			      $arrid = get_arr_id($connectionFlow,trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )));
			      update_arr_status($connectionFlow,$arrid,trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )));
			      update_arr_master($connectionFlow,$arrstatustrackid,$sub,trim(formData('hidden_patient_code' )),trim(formData("HiddenEncounter$CountRow"   )));
			  }*/
		     $main = 'BILLING';
		     if(trim(formData("HiddenIns$CountRow"   ))==1)
		     $sub = 'ENCT_PAIDPRI';
		     elseif(trim(formData("HiddenIns$CountRow"   ))==2)
		     $sub = 'ENCT_PAIDSEC';
		     elseif(trim(formData("HiddenIns$CountRow"   ))==3)
		     $sub = 'ENCT_PAIDTER';
		     $emrflowtrack = new emrflowtrack();
		     $emrflowtrack->update_status(array($data[0],$main,$sub,trim($_REQUEST['hidden_patient_code']),trim($_REQUEST["HiddenEncounter$CountRow"]),'BillingPortal:PaymentDistribution - Payment values'));

	     }
   }
  if (isset($_POST["AdjAmount$CountRow"]) && $_POST["AdjAmount$CountRow"]*1!=0)
   {
		if(trim(formData('type_name'   ))=='insurance')
		 {
		  $AdjustString="Ins adjust Ins".trim(formData("HiddenIns$CountRow"   ));
		  $AccountCode="IA";
		 }
		elseif(trim(formData('type_name'   ))=='patient')
		 {
		  $AdjustString="Pt adjust";
		  $AccountCode="PA";
		 }


	  idSqlStatement("insert into ar_activity set "    .
		"pid = '"       . trim(formData('hidden_patient_code' )) .
		"', encounter = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
		"', code = '"      . trim(formData("HiddenCode$CountRow"   ))  .
		"', modifier = '"      . trim(formData("HiddenModifier$CountRow"   ))  .
		"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
		"', post_time = '"  . trim($created_time					) .
		"', post_user = '" . trim($user_id            )  .
		"', session_id = '"    . trim(formData('payment_id')) .
		"', modified_time = '"  . trim($created_time					) .
		"', pay_amount = '" . 0  .
		"', adj_amount = '"    . trim(formData("AdjAmount$CountRow"   )) .
		"', memo = '" . "$AdjustString"  .
		"', account_code = '" . "$AccountCode"  .
		"'");
	  $Affected='yes';
   }
  if (isset($_POST["Deductible$CountRow"]) && $_POST["Deductible$CountRow"]*1>0)
   {
	  idSqlStatement("insert into ar_activity set "    .
		"pid = '"       . trim(formData('hidden_patient_code' )) .
		"', encounter = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
		"', code = '"      . trim(formData("HiddenCode$CountRow"   ))  .
		"', modifier = '"      . trim(formData("HiddenModifier$CountRow"   ))  .
		"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
		"', post_time = '"  . trim($created_time					) .
		"', post_user = '" . trim($user_id            )  .
		"', session_id = '"    . trim(formData('payment_id')) .
		"', modified_time = '"  . trim($created_time					) .
		"', pay_amount = '" . 0  .
		"', adj_amount = '"    . 0 .
		"', memo = '"    . "Deductable $".trim(formData("Deductible$CountRow"   )) .
		"', account_code = '" . "Deduct"  .
		"'");
	  $Affected='yes';		
   }
  if (isset($_POST["Takeback$CountRow"]) && $_POST["Takeback$CountRow"]*1>0)
   {
	  idSqlStatement("insert into ar_activity set "    .
		"pid = '"       . trim(formData('hidden_patient_code' )) .
		"', encounter = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
		"', code = '"      . trim(formData("HiddenCode$CountRow"   ))  .
		"', modifier = '"      . trim(formData("HiddenModifier$CountRow"   ))  .
		"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
		"', post_time = '"  . trim($created_time					) .
		"', post_user = '" . trim($user_id            )  .
		"', session_id = '"    . trim(formData('payment_id')) .
		"', modified_time = '"  . trim($created_time					) .
		"', pay_amount = '" . trim(formData("Takeback$CountRow"   ))*-1  .
		"', adj_amount = '"    . 0 .
		"', account_code = '" . "Takeback"  .
		"'");
	  $Affected='yes';		
   }
	$drop_down_reason=$_POST["drop_down_reason$CountRow"];
	if($drop_down_reason=='r')
	 {
	 $reason=trim(formData("FollowUpReason$CountRow"   ));
	 }
	elseif($drop_down_reason=='d')
	 {
	 $reason=trim(formData("drop_down_denial$CountRow"   ));
	 }
  if (isset($_POST["drop_down_reason$CountRow"]) && $_POST["drop_down_reason$CountRow"]!='' && $reason!='')
   {
	  idSqlStatement("insert into ar_activity set "    .
		"pid = '"       . trim(formData('hidden_patient_code' )) .
		"', encounter = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
		"', code = '"      . trim(formData("HiddenCode$CountRow"   ))  .
		"', modifier = '"      . trim(formData("HiddenModifier$CountRow"   ))  .
		"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
		"', post_time = '"  . trim($created_time					) .
		"', post_user = '" . trim($user_id            )  .
		"', session_id = '"    . trim(formData('payment_id')) .
		"', modified_time = '"  . trim($created_time					) .
		"', pay_amount = '" . 0  .
		"', adj_amount = '"    . 0 .
		"', follow_up = '"    . "$drop_down_reason" .
		"', follow_up_note = '"    . $reason .
		"'");
		if($drop_down_reason=='d')
		 {
			$code_value = trim(formData("HiddenCode$CountRow"   )).'_'.trim(formData("HiddenModifier$CountRow"   )).'_'.''.'_'.$reason;
			updateClaim(true, trim(formData('hidden_patient_code' )), trim(formData("HiddenEncounter$CountRow"   )), trim(formData('hidden_type_code' )), 
								trim(formData("HiddenIns$CountRow"   )),7,0,$code_value);
			$code_value='';
			$Denial='yes';
		 } 
	  $Affected='yes';		
   }
  return $Affected;
}
//===============================================================================
  // Delete rows, with logging, for the specified table using the
  // specified WHERE clause.  Borrowed from deleter.php.
  //
  function row_delete($table, $where) {
    $tres = sqlStatement("SELECT * FROM $table WHERE $where");
    $count = 0;
    while ($trow = sqlFetchArray($tres)) {
      $logstring = "";
      foreach ($trow as $key => $value) {
        if (! $value || $value == '0000-00-00 00:00:00') continue;
        if ($logstring) $logstring .= " ";
        $logstring .= $key . "='" . addslashes($value) . "'";
      }
      newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "$table: $logstring");
      ++$count;
    }
    if ($count) {
      $query = "DELETE FROM $table WHERE $where";
      sqlStatement($query);
    }
  }
//===============================================================================
function QueueToNextLevel()
 {
	global $EncounterRowArray,$InsRowArray,$AffectedRowArray;
	for($RowIndex=1;$RowIndex<=sizeof($EncounterRowArray);$RowIndex++)
	 {
	  if($AffectedRowArray[$RowIndex]=='yes')
	   {
		if(trim(formData('type_name'   ))!='patient')
		 {
		     $codes = ar_get_invoice_summary(trim(formData('hidden_patient_code' )), $EncounterRowArray[$RowIndex], true);
		     $insurance_done = true;
		     foreach ($codes as $code => $prev) {
			    $got_response = false;
			    foreach ($prev['dtl'] as $ddata) {
				   if ($ddata['pmt']) $got_response = true;
			    }
			    if (!$got_response) $insurance_done = false;
		     }
		     if(!$insurance_done)
			    continue;
			$ferow = sqlQuery("select last_level_closed from form_encounter  where 
			pid ='".trim(formData('hidden_patient_code' ))."' and encounter='".$EncounterRowArray[$RowIndex]."'");
			//multiple charges can come.Now not needed.
			fwrite($fh,"pamentINC line no231 beforeIF pid--".trim(formData('hidden_patient_code' ))."encounter--".$EncounterRowArray[$RowIndex]."inslevel".$InsRowArray[$RowIndex]."\r\n");
			if($ferow['last_level_closed']<$InsRowArray[$RowIndex])
			 {
				sqlStatement("update form_encounter set last_level_closed='".$InsRowArray[$RowIndex]."' where 
				pid ='".trim(formData('hidden_patient_code' ))."' and encounter='".$EncounterRowArray[$RowIndex]."'");
				       fwrite($fh,"pamentINC line no236 insideIF pid--".trim(formData('hidden_patient_code' ))."encounter--".$EncounterRowArray[$RowIndex]."inslevel".$InsRowArray[$RowIndex]."\r\n");
					   if($GLOBALS['flow_track']==1)
				       {
					   global $connectionFlow;
					   /*$main = get_main_status_id($connectionFlow,'BILLING');
					   if($InsRowArray[$RowIndex]==1)
					   $sub = get_sub_status_id($connectionFlow,'ENCT_READYTOBILLSEC');
					   elseif($InsRowArray[$RowIndex]==2)
					   $sub = get_sub_status_id($connectionFlow,'ENCT_READYTOBILLTER');
					   $count=get_count($connectionFlow,trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex],$main,$sub);
					   if(final_code_check($connectionFlow,$sub,trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex])==0)
					   {
					       $arrstatustrackid = insert_arr_status($connectionFlow,$_SESSION['authId'],trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex],'',$main,$sub,'','','','','','','',$count,'Queue To Next Level');
					       $arrid = get_arr_id($connectionFlow,trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex]);
					       update_arr_status($connectionFlow,$arrid,trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex]);
					       update_arr_master($connectionFlow,$arrstatustrackid,$sub,trim(formData('hidden_patient_code' )),$EncounterRowArray[$RowIndex]);
					   }*/
					  $main = 'BILLING';
					  if($InsRowArray[$RowIndex]==1)
					  $sub = 'ENCT_READYTOBILLSEC';
					  elseif($InsRowArray[$RowIndex]==2)
					  $sub = 'ENCT_READYTOBILLTER';
					  $emrflowtrack = new emrflowtrack();
					  $emrflowtrack->update_status(array($data[0],$main,$sub,trim($_REQUEST['hidden_patient_code']),trim($_REQUEST["HiddenEncounter$CountRow"]),'BillingPortal:Queue to next level'));
				       }
				//last_level_closed gets increased.
				//-----------------------------------
				// Determine the next insurance level to be billed.
				$ferow = sqlQuery("SELECT date, last_level_closed " .
				  "FROM form_encounter WHERE " .
				  "pid = '".trim(formData('hidden_patient_code' ))."' AND encounter = '".$EncounterRowArray[$RowIndex]."'");
				$date_of_service = substr($ferow['date'], 0, 10);
				$new_payer_type = 0 + $ferow['last_level_closed'];
				if ($new_payer_type <= 3 && !empty($ferow['last_level_closed']) || $new_payer_type == 0)
				  ++$new_payer_type;
				$new_payer_id = arGetPayerID(trim(formData('hidden_patient_code' )), $date_of_service, $new_payer_type);
				if($new_payer_id>0)
				 {
				arSetupSecondary(trim(formData('hidden_patient_code' )), $EncounterRowArray[$RowIndex],0);
				 }
				//-----------------------------------
			 }
		 }
	   }
	}
 }
//==================================================================================================================================
$payment_denial_reasons = array(
	'ADD INFO/ DOC PR'  =>  'Additional Information/ Documents from Provider',
	'ADD INFO/ DOC PT'  =>  'Additional Information/ Documents from Patient',
	'AUTH'  =>  'Authorization',
	'COB'  =>  'Co-ordination of Benefits',
	'DND DUP'  =>  'Denied as duplicate',
	'GLOBAL'  =>  'Global',
	'INC PD'  =>  'Incorrectly paid',
	'INV COD'  =>  'Invalid Code/ coding',
	'INV DOS'  =>  'Invalid date of service',
	'INV PAYER'  =>  'Invalid Payer/ insurance',
	'INV PT INFO'  =>  'Invalid Patient Information',
	'MED NESS'  =>  'Medically necessary',
	'MED REC'  =>  'Medical records',
	'NON CVRD'  =>  'Non-covered',
	'PAYER REFND'  =>  'Payer Refund',
	'PD MAX'  =>  'Paid Maximum',
	'PR ELIG'  =>  'Provider Eligible',
	'PRI EOB'  =>  'Primary EOB',
	'PRO ADJ'  =>  'Provider Adjustments',
	'PROV#'  =>  'Provider number',
	'PT ELIG'  =>  'Patient Eligible',
	'PTRESP'  =>  'Patient Responsibility',
	'REFF'  =>  'No PCP Referral',
	'REVIEW/ IN PROCESS'  =>  'Insurance acknowledgment letter',
	'TFL'  =>  'Timely Filing Limit',
	
	'PRIMARY NOT PAID'  =>  'Amount not allowed according to the primary insurance contract',
	'NO REASON'  =>  'Denial reason not given',
	'HMO PT'  =>  'Pt enrolled in medicare hmo',
	'HOSPICE'  =>  'Pt enrolled in hospice',
	'INVPROV INFO'  =>  'Invalid npi/tax id',
	'NON PAR PROV'  =>  'Out of network provider',
	'FREQUENCY'  =>  "Denied as frequency/the maximum number of doctor's visits have been exhausted",
);
?>