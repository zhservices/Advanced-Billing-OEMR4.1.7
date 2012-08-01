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
//Payments can be edited here.It includes deletion of an allocation,modifying the 
//same or adding a new allocation.Log is kept for the deleted ones.
//===============================================================================
ob_start();
require_once(dirname(__FILE__) . "/../../../../interface/globals.php");
$srcdir = $GLOBALS['srcdir'];
require_once("$srcdir/log.inc");
require_once("$srcdir/invoice_summary.inc.php");
require_once("$srcdir/sl_eob.inc.php");
require_once("$srcdir/parse_era.inc.php");
require_once(dirname(__FILE__) . "/../../../../library/acl.inc");
require_once("$srcdir/sql.inc");
//require_once("$srcdir/auth.inc");
require_once("$srcdir/formdata.inc.php");
require_once(dirname(__FILE__) . "/../../../../custom/code_types.inc.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/billrep.inc");
require_once(dirname(__FILE__) . "/../../../../library/classes/OFX.class.php");
require_once(dirname(__FILE__) . "/../../../../library/classes/X12Partner.class.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/formatting.inc.php");
//require_once("$srcdir/payment.inc.php");
require_once(dirname(__FILE__) . "/payment.inc.php");
require_once(dirname(__FILE__) . "/adjustment_reason_codes.php");

//require_once($_SERVER['DOCUMENT_ROOT'] . "/modules/billingportal/reports/report.inc.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/modules/billingportal/library/report.class.php");
//===============================================================================
	$screen='edit_payment';
//===============================================================================
// deletion of payment distribution code
//===============================================================================
if (isset($_POST["mode"]))
 {
  if ($_POST["mode"] == "DeletePaymentDistribution")
   {
    $DeletePaymentDistributionId=trim(formData('DeletePaymentDistributionId' ));
	$DeletePaymentDistributionIdArray=split('_',$DeletePaymentDistributionId);
	$payment_id=$DeletePaymentDistributionIdArray[0];
	$PId=$DeletePaymentDistributionIdArray[1];
	$Encounter=$DeletePaymentDistributionIdArray[2];
	$Code=$DeletePaymentDistributionIdArray[3];
	$Modifier=$DeletePaymentDistributionIdArray[4];
	//delete and log that action
	row_delete("ar_activity", "session_id ='$payment_id' and  pid ='$PId' AND " .
	  "encounter='$Encounter' and  code='$Code' and modifier='$Modifier'");
	$Message='Delete';
	//------------------
    $_POST["mode"] = "searchdatabase";
   }
 }
//===============================================================================
//Modify Payment Code.
//===============================================================================
global $EncounterRowArray,$InsRowArray,$AffectedRowArray;
$EncounterRowArray=array();
$InsRowArray=array();
$AffectedRowArray=array();
$Affected_Any='no';
$Affected='no';
$EncounterRow='';
$RowIndex=0;
if (isset($_POST["mode"]))
 {
  if ($_POST["mode"] == "ModifyPayments" || $_POST["mode"] == "FinishPayments")
   {
	$payment_id=$_REQUEST['payment_id'];
	//ar_session Code
	//===============================================================================
	if(trim(formData('type_name'   ))=='insurance')
	 {
		$QueryPart="payer_id = '"       . trim(formData('hidden_type_code' )) .
		"', patient_id = '"   . 0 ;
	 }
	elseif(trim(formData('type_name'   ))=='patient')
	 {
		$QueryPart="payer_id = '"       . 0 .
		"', patient_id = '"   . trim(formData('hidden_type_code'   )) ;
	 }
      $user_id=$_SESSION['authUserID'];
	  $closed=0;
	  $modified_time = date('Y-m-d H:i:s');
	  $check_date=DateToYYYYMMDD(formData('check_date'));
	  $deposit_date=DateToYYYYMMDD(formData('deposit_date'));
	  $post_to_date=DateToYYYYMMDD(formData('post_to_date'));
	  $cap_from_date=trim(formData('cap_from_date'));
	  $cap_to_date=trim(formData('cap_to_date'));
	  if($post_to_date=='')
	   $post_to_date=date('Y-m-d');
	  if(formData('deposit_date')=='')
	   $deposit_date=$post_to_date;
	  
	  if(trim(formData('adjustment_code'   ))=='cap_payment'){
	   sqlStatement("update ar_session set "    .
	  	$QueryPart .
        "', user_id = '"     . trim($user_id                  )  .
        "', closed = '"      . trim($closed                   )  .
        "', reference = '"   . trim(formData('check_number'   )) .
        "', check_date = '"  . trim($check_date					) .
        "', deposit_date = '" . trim($deposit_date            )  .
        "', pay_total = '"    . trim(formData('payment_amount')) .
        "', modified_time = '" . trim($modified_time            )  .
        "', payment_type = '"   . trim(formData('type_name'   )) .
        "', description = '"   . trim(formData('description'   )) .
        "', adjustment_code = '"   . trim(formData('adjustment_code'   )) .
        "', post_to_date = '" . trim($post_to_date            )  .
        "', payment_method = '"   . trim(formData('payment_method'   )) .
	"', cap_from_date = '" . trim($cap_from_date            )  .
	"', cap_to_date = '" . trim($cap_to_date            )  .
	"', cap_bill_facId = '" . trim(formData('billing_facility'   ))  .
	"', payment_screen = '"   . 0 .
        "'	where session_id='$payment_id'");
	   
	   sqlStatement("update ar_activity SET pid = 0, encounter = 0, payer_type = 1, post_time=now(), post_user='".$user_id."', ".
		       " pay_amount ='".trim(formData('payment_amount'))."' ,modified_time=now(),account_code='CAPPMNT' where session_id='$payment_id'");
	  }
	  else{
	  sqlStatement("update ar_session set "    .
	  	$QueryPart .
        "', user_id = '"     . trim($user_id                  )  .
        "', closed = '"      . trim($closed                   )  .
        "', reference = '"   . trim(formData('check_number'   )) .
        "', check_date = '"  . trim($check_date					) .
        "', deposit_date = '" . trim($deposit_date            )  .
        "', pay_total = '"    . trim(formData('payment_amount')) .
        "', modified_time = '" . trim($modified_time            )  .
        "', payment_type = '"   . trim(formData('type_name'   )) .
        "', description = '"   . trim(formData('description'   )) .
        "', adjustment_code = '"   . trim(formData('adjustment_code'   )) .
        "', post_to_date = '" . trim($post_to_date            )  .
        "', payment_method = '"   . trim(formData('payment_method'   )) .
	"', payment_screen = '"   . 0 .
        "'	where session_id='$payment_id'");
	 }
//===============================================================================
	$CountIndexAbove=$_REQUEST['CountIndexAbove'];
	$CountIndexBelow=$_REQUEST['CountIndexBelow'];
	$hidden_patient_code=$_REQUEST['hidden_patient_code'];
	$user_id=$_SESSION['authUserID'];
	$created_time = date('Y-m-d H:i:s');
	//==================================================================
	//UPDATION
	//It is done with out deleting any old entries.
	//==================================================================
	for($CountRow=1;$CountRow<=$CountIndexAbove;$CountRow++)
	 {
	  if (isset($_POST["HiddenEncounter$CountRow"]))
	   {
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
				$resPayment = sqlStatement("SELECT  * from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount>0");
				if(sqlNumRows($resPayment)>0)
				 {
				  sqlStatement("update ar_activity set "    .
					"   post_user = '" . trim($user_id            )  .
					"', modified_time = '"  . trim($created_time					) .
					"', pay_amount = '" . trim(formData("Payment$CountRow"   ))  .
					"', account_code = '" . "$AccountCode"  .
					"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
					"' where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount>0");
				 }
				else
				 {
				  sqlStatement("insert into ar_activity set "    .
					"pid = '"       . trim(formData("HiddenPId$CountRow"   )) .
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
				 }
		   }
		  else
		   {
		    sqlStatement("delete from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount>0");
		   }
//==============================================================================================================================
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
				$resPayment = sqlStatement("SELECT  * from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and adj_amount!=0");
				if(sqlNumRows($resPayment)>0)
				 {
				  sqlStatement("update ar_activity set "    .
					"   post_user = '" . trim($user_id            )  .
					"', modified_time = '"  . trim($created_time					) .
					"', adj_amount = '"    . trim(formData("AdjAmount$CountRow"   )) .
					"', memo = '" . "$AdjustString"  .
					"', account_code = '" . "$AccountCode"  .
					"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
					"' where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and adj_amount!=0");
				 }
				else
				 {
				  sqlStatement("insert into ar_activity set "    .
					"pid = '"       . trim(formData("HiddenPId$CountRow" )) .
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
				 }

		   }
		  else
		   {
		    sqlStatement("delete from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and adj_amount!=0");
		   }
//==============================================================================================================================
		  if (isset($_POST["Deductible$CountRow"]) && $_POST["Deductible$CountRow"]*1>0)
		   {
				$resPayment = sqlStatement("SELECT  * from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and memo like 'Deductable%'");
				if(sqlNumRows($resPayment)>0)
				 {
				  sqlStatement("update ar_activity set "    .
					"   post_user = '" . trim($user_id            )  .
					"', modified_time = '"  . trim($created_time					) .
					"', memo = '"    . "Deductable $".trim(formData("Deductible$CountRow"   )) .
					"', account_code = '" . "Deduct"  .
					"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
					"' where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and memo like 'Deductable%'");
				 }
				else
				 {
				  sqlStatement("insert into ar_activity set "    .
					"pid = '"       . trim(formData("HiddenPId$CountRow" )) .
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
				 }
		   }
		  else
		   {
		    sqlStatement("delete from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and memo like 'Deductable%'");
		   }
//==============================================================================================================================
		  if (isset($_POST["Takeback$CountRow"]) && $_POST["Takeback$CountRow"]*1>0)
		   {
				$resPayment = sqlStatement("SELECT  * from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount < 0");
				if(sqlNumRows($resPayment)>0)
				 {
				  sqlStatement("update ar_activity set "    .
					"   post_user = '" . trim($user_id            )  .
					"', modified_time = '"  . trim($created_time					) .
					"', pay_amount = '" . trim(formData("Takeback$CountRow"   ))*-1  .
					"', account_code = '" . "Takeback"  .
					"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
					"' where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount < 0");
				 }
				else
				 {
				  sqlStatement("insert into ar_activity set "    .
					"pid = '"       . trim(formData("HiddenPId$CountRow" )) .
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
				 }
		   }
		  else
		   {
		    sqlStatement("delete from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and pay_amount < 0");
		   }
//==============================================================================================================================
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
				$resPayment = sqlStatement("SELECT  * from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and follow_up !=''");
				if(sqlNumRows($resPayment)>0)
				 {
				  sqlStatement("update ar_activity set "    .
					"   post_user = '" . trim($user_id            )  .
					"', modified_time = '"  . trim($created_time					) .
					"', follow_up = '"    . "$drop_down_reason" .
					"', follow_up_note = '"    . $reason .
					"', payer_type = '"   . trim(formData("HiddenIns$CountRow"   )) .
					"' where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and follow_up !=''");
					$rowResPayment=sqlFetchArray($resPayment);
					$code_value = trim(formData("HiddenCode$CountRow"   )).'_'.trim(formData("HiddenModifier$CountRow"   )).'_'.''.'_'.$reason;
					if($rowResPayment['follow_up']=='r')//old value change over
					 {
						if($drop_down_reason=='d')
						 {
							updateClaim(true, trim(formData("HiddenPId$CountRow" )), trim(formData("HiddenEncounter$CountRow"   )), trim(formData('hidden_type_code' )), 
												trim(formData("HiddenIns$CountRow"   )),7,0,$code_value);
							$code_value='';
						 }
					 }
					elseif($rowResPayment['follow_up']=='d')
					 {
						if($drop_down_reason=='d')
						 {
						  sqlStatement("update claims set "    .
							"process_file = '"       . $code_value .
							"' where	patient_id = '"       . trim(formData("HiddenPId$CountRow" )) .
							"' and encounter_id = '"     . trim(formData("HiddenEncounter$CountRow"   ))  .
							"' and status = '7'");
						 }
						else
						 {
							//we keep the history in claims and modify only the billing table
				  			 sqlStatement("update billing set bill_process=2 where  pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
							"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
							"' and bill_process ='7'");
						 }
					 }
				 }
				else
				 {
				  sqlStatement("insert into ar_activity set "    .
					"pid = '"       . trim(formData("HiddenPId$CountRow" )) .
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
						updateClaim(true, trim(formData("HiddenPId$CountRow" )), trim(formData("HiddenEncounter$CountRow"   )), trim(formData('hidden_type_code' )), 
											trim(formData("HiddenIns$CountRow"   )),7,0,$code_value);
						$code_value='';
					 }
				 }
				 if($GLOBALS['flow_track']==1)
				 {
				   if($drop_down_reason=='r')
				   {
				   }
				   elseif($drop_down_reason=='d')
				   {
				     global $connectionFlow;
				     $payer_type = trim(formData("HiddenIns$CountRow"));
				     $patient_id = trim(formData("HiddenPId$CountRow"));
				     $encounter_id = trim(formData("HiddenEncounter$CountRow"));
					 $emrflowtrack = new emrflowtrack();
				     $main = $emrflowtrack->get_main_status_id($connectionFlow,'BILLING');
				     /*if($payer_type==1)
				     $sub = get_sub_status_id($connectionFlow,'ENCT_REJECTPRI');
				     elseif($payer_type==2)
				     $sub = get_sub_status_id($connectionFlow,'ENCT_REJECTSEC');
				     elseif($payer_type==3)
				     $sub = get_sub_status_id($connectionFlow,'ENCT_REJECTTER');
				     $count=get_count($connectionFlow,$patient_id,$encounter_id,$main,$sub);
				     if(final_code_check($connectionFlow,$sub,$patient_id,$encounter_id)==0)
				     {
					 $arrstatustrackid = insert_arr_status($connectionFlow,$_SESSION['authId'],$patient_id,$encounter_id,'',$main,$sub,'','','','','','','',$count,'Manual Posting - Rejected Status (file:edit_payment.php)');
					 $arrid = get_arr_id($connectionFlow,$patient_id,$encounter_id);
					 update_arr_status($connectionFlow,$arrid,$patient_id,$encounter_id);
					 update_arr_master($connectionFlow,$arrstatustrackid,$sub,$patient_id,$encounter_id);
				     }*/
				     $main = 'BILLING';
				     if($payer_type==1)
				     $sub = 'ENCT_REJECTPRI';
				     elseif($payer_type==2)
				     $sub = 'ENCT_REJECTSEC';
				     elseif($payer_type==3)
				     $sub = 'ENCT_REJECTTER';
				     $emrflowtrack->update_status(array($data[0],$main,$sub,trim(formData("HiddenPId$CountRow" )),trim(formData("HiddenEncounter$CountRow"   )),'BillingPortal:Edit payment denial status'));
				   }
				 }
		   }
		  else
		   {
		    sqlStatement("delete from ar_activity " .
					" where  session_id ='$payment_id' and pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and  code  ='" . trim(formData("HiddenCode$CountRow"   ))  .
					"' and  modifier  ='" . trim(formData("HiddenModifier$CountRow"   ))  .
					"' and follow_up !=''");
					//we keep the history in claims and modify only the billing table
		   sqlStatement("update billing set bill_process=2 where  pid ='" . trim(formData("HiddenPId$CountRow"   ))  .
					"' and  encounter  ='" . trim(formData("HiddenEncounter$CountRow"   ))  .
					"' and bill_process ='7'");
		   }
//==============================================================================================================================
	   }
	  else
	   break;
	 }
	//=========
	//INSERTION of new entries,continuation of modification.
	//=========
	for($CountRow=$CountIndexAbove+1;$CountRow<=$CountIndexAbove+$CountIndexBelow;$CountRow++)
	 {
	  if (isset($_POST["HiddenEncounter$CountRow"]))
	   {
        if($EncounterRow!=$_POST["HiddenEncounter$CountRow"])
		 {
		  $RowIndex++;
		  $EncounterRowArray[$RowIndex]=$_POST["HiddenEncounter$CountRow"];
		  $InsRowArray[$RowIndex]=$_POST["HiddenIns$CountRow"];
		  if($RowIndex>1)
		   {
			  $AffectedRowArray[$RowIndex-1]=$Affected;
			  $Affected='no';
		   }
		 }
	    $Affected_Any=DistributionInsert($CountRow,$created_time,$user_id);
		if($Affected_Any=='yes')
		 {
		  $Affected='yes';
		 }
		$EncounterRow=$_POST["HiddenEncounter$CountRow"];
	   }
	  else
	   break;
	 }
	$AffectedRowArray[$RowIndex]=$Affected;
	$Affected='no';
	QueueToNextLevel();
//==================================================================================================================================
	if($_REQUEST['global_amount']=='yes')
		sqlStatement("update ar_session set global_amount=".trim(formData("HidUnappliedAmount"   ))*1 ." where session_id ='$payment_id'");
	if($_POST["mode"]=="FinishPayments")
	 {
	  $Message='Finish';
	 }
    $_POST["mode"] = "searchdatabase";
	$Message='Modify';
   }
 }
//==============================================================================
//Search Code
//===============================================================================
$payment_id=$payment_id*1 > 0 ? $payment_id : $_REQUEST['payment_id'];
$ResultSearchSub = sqlStatement("SELECT  distinct encounter,code,modifier, pid from ar_activity where  session_id ='$payment_id' order by pid,encounter,code,modifier");
//==============================================================================
$DateFormat=DateFormatRead();
//==============================================================================
//===============================================================================
?>

<html>
<head>
<?php if (function_exists('html_header_show')) html_header_show(); ?>

<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">

<!-- supporting javascript code -->

<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>



<?php include_once(dirname(__FILE__)."/payment_jav.inc.php"); ?>
<?php include_once(dirname(__FILE__)."/payment_ajax_jav.inc.php"); ?>
<script type="text/javascript" src="../../library/js/common.js"></script>
<script LANGUAGE="javascript" TYPE="text/javascript">
function ExportData(type)
 {
	document.getElementById('mode').value=type;
	//top.restoreSession();
	document.forms[0].submit();
 }
function ModifyPayments()
 {//Used while modifying the allocation
 	if(!FormValidations())//FormValidations contains the form checks
	 {
	  return false;
	 }
	if(CompletlyBlankAbove())//The distribution rows already in the database are checked.
	 {
	  alert("<?php echo htmlspecialchars( xl('None of the Top Distribution Row Can be Completly Blank.'), ENT_QUOTES);echo htmlspecialchars('\n');echo htmlspecialchars( xl('Use Delete Option to Remove.'), ENT_QUOTES) ?>")
	  return false;
	 }
 	if(!CheckPayingEntityAndDistributionPostFor())//Ensures that Insurance payment is distributed under Ins1,Ins2,Ins3 and Patient paymentat under Pat.
	 {
	  return false;
	 }
 	if(CompletlyBlankBelow())//The newly added distribution rows are checked.
	 {
	  alert("<?php echo htmlspecialchars( xl('Fill any of the Below Row.'), ENT_QUOTES) ?>")
	  return false;
	 }
	PostValue=CheckUnappliedAmount();//Decides TdUnappliedAmount >0, or <0 or =0
	//if(PostValue==1)
	// {
	//  alert("<?php echo htmlspecialchars( xl('Cannot Modify Payments.Undistributed is Negative.'), ENT_QUOTES) ?>")
	//  return false;
	// }
	if(confirm("<?php echo htmlspecialchars( xl('Would you like to Modify Payments?'), ENT_QUOTES) ?>"))
	 {
		document.getElementById('mode').value='ModifyPayments';
		//top.restoreSession();
		document.forms[0].submit();
	 }
	else
	 return false;
 }
function FinishPayments()
 {
 	if(!FormValidations())//FormValidations contains the form checks
	 {
	  return false;
	 }
 	if(CompletlyBlankAbove())//The distribution rows already in the database are checked.
	 {
	  alert("<?php echo htmlspecialchars( xl('None of the Top Distribution Row Can be Completly Blank.'), ENT_QUOTES);echo htmlspecialchars('\n');echo htmlspecialchars( xl('Use Delete Option to Remove.'), ENT_QUOTES) ?>")
	  return false;
	 }
 	if(!CheckPayingEntityAndDistributionPostFor())//Ensures that Insurance payment is distributed under Ins1,Ins2,Ins3 and Patient paymentat under Pat.
	 {
	  return false;
	 }
 	if(CompletlyBlankBelow())//The newly added distribution rows are checked.
	 {
	  alert("<?php echo htmlspecialchars( xl('Fill any of the Below Row.'), ENT_QUOTES) ?>")
	  return false;
	 }
 	PostValue=CheckUnappliedAmount();//Decides TdUnappliedAmount >0, or <0 or =0
	if(PostValue==1)
	 {
	  alert("<?php echo htmlspecialchars( xl('Cannot Modify Payments.UNDISTRIBUTED is Negative.'), ENT_QUOTES) ?>")
	  return false;
	 }
	if(PostValue==2)
	 {
		if(confirm("<?php echo htmlspecialchars( xl('Would you like to Modify and Finish Payments?'), ENT_QUOTES) ?>"))
		 {
			UnappliedAmount=document.getElementById('TdUnappliedAmount').innerHTML*1;
			if(confirm("<?php echo htmlspecialchars( xl('UNDISTRIBUTED is'), ENT_QUOTES) ?>" + ' ' + UnappliedAmount + '.' + "<?php echo htmlspecialchars('\n');echo htmlspecialchars( xl('Would you like the balance amount to apply to Global Account?'), ENT_QUOTES) ?>"))
			 {
				document.getElementById('mode').value='FinishPayments';
				document.getElementById('global_amount').value='yes';
				//top.restoreSession();
				document.forms[0].submit();
			 }
			else
			 {
				document.getElementById('mode').value='FinishPayments';
				//top.restoreSession();
				document.forms[0].submit();
			 }
		 }
		else
		 return false;
	 }
	else
	 {
		if(confirm("<?php echo htmlspecialchars( xl('Would you like to Modify and Finish Payments?'), ENT_QUOTES) ?>"))
		 {
			document.getElementById('mode').value='FinishPayments';
			//top.restoreSession();
			document.forms[0].submit();
		 }
		else
		 return false;
	 }

 }
function CompletlyBlankAbove()
 {//The distribution rows already in the database are checked.
 //It is not allowed to be made completly empty.If needed delete option need to be used.
  CountIndexAbove=document.getElementById('CountIndexAbove').value*1;
  for(RowCount=1;RowCount<=CountIndexAbove;RowCount++)
   {
   if(document.getElementById('Allowed'+RowCount).value=='' && document.getElementById('Payment'+RowCount).value=='' && document.getElementById('AdjAmount'+RowCount).value=='' && document.getElementById('Deductible'+RowCount).value=='' && document.getElementById('Takeback'+RowCount).value=='' && document.getElementById('drop_down_reason'+RowCount).selectedIndex==0)
	{
	 return true;
	}
   }
  return false;
 }
function CompletlyBlankBelow()
 {//The newly added distribution rows are checked.
 //It is not allowed to be made completly empty.
  CountIndexAbove=document.getElementById('CountIndexAbove').value*1;
  CountIndexBelow=document.getElementById('CountIndexBelow').value*1;
  if(CountIndexBelow==0)
   return false;
  for(RowCount=CountIndexAbove+1;RowCount<=CountIndexAbove+CountIndexBelow;RowCount++)
   {
   if(document.getElementById('Allowed'+RowCount).value=='' && document.getElementById('Payment'+RowCount).value=='' && document.getElementById('AdjAmount'+RowCount).value=='' && document.getElementById('Deductible'+RowCount).value=='' && document.getElementById('Takeback'+RowCount).value=='' && document.getElementById('drop_down_reason'+RowCount).selectedIndex==0)
	{

	}
	else
	 return false;
   }
  return true;
 }
function OnloadAction()
 {//Displays message while loading after some action.
  after_value=document.getElementById('ActionStatus').value;
  if(after_value=='Delete')
   {
    alert("<?php echo htmlspecialchars( xl('Successfully Deleted'), ENT_QUOTES) ?>")
	return true;
   }
  if(after_value=='Modify' || after_value=='Finish')
   {
    alert("<?php echo htmlspecialchars( xl('Successfully Modified'), ENT_QUOTES) ?>")
	return true;
   }
  after_value=document.getElementById('after_value').value;
  payment_id=document.getElementById('payment_id').value;
  if(after_value=='distribute')
   {
   }
  else if(after_value=='new_payment')
   {
	if(document.getElementById('TablePatientPortion'))
	 {
		document.getElementById('TablePatientPortion').style.display='none';
	 }
	if(confirm("<?php echo htmlspecialchars( xl('Successfully Saved.Would you like to Distribute?'), ENT_QUOTES) ?>"))
	 {
		if(document.getElementById('TablePatientPortion'))
		 {
			document.getElementById('TablePatientPortion').style.display='';
		 }
	 }
   }

 }
function DeletePaymentDistribution(DeleteId)
 {//Confirms deletion of payment distribution.
	if(confirm("<?php echo htmlspecialchars( xl('Would you like to Delete Payment Distribution?'), ENT_QUOTES) ?>"))
	 {
		document.getElementById('mode').value='DeletePaymentDistribution';
		document.getElementById('DeletePaymentDistributionId').value=DeleteId;
		//top.restoreSession();
		document.forms[0].submit();
	 }
	else
	 return false;
 }
//========================================================================================
</script>
<script language="javascript" type="text/javascript">
document.onclick=HideTheAjaxDivs;
</script>
<style>
.class1{width:125px;}
.class2{width:250px;}
.class3{width:100px;}
.bottom{border-bottom:1px solid black;}
.top{border-top:1px solid black;}
.left{border-left:1px solid black;}
.right{border-right:1px solid black;}
#ajax_div_insurance {
	position: absolute;
	z-index:10;
	/*
	left: 20px;
	top: 300px;
	*/
	background-color: #FBFDD0;
	border: 1px solid #ccc;
	padding: 10px;
}
#ajax_div_patient {
	position: absolute;
	z-index:10;
	/*
	left: 20px;
	top: 300px;
	*/
	background-color: #FBFDD0;
	border: 1px solid #ccc;
	padding: 10px;
}
</style>
<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
</head>
<body class="body_top" onLoad="OnloadAction()"  >
<form name='new_payment' method='post'  action="edit_payment.php" onsubmit='
<?php
 if($payment_id*1==0)
  {
  ?>
return SavePayment();
<?php
  }
 else
  {
  ?>
return false;
<?php
  }
  ?>
' style="display:inline" >
 <input type="hidden" name="screen" value="<?php echo $_REQUEST['screen'];?>">
<table width="100%" border="0"  cellspacing="0" cellpadding="0">
<?php
  if($_REQUEST['ParentPage']=='new_payment')
  {
  ?>
  <tr>
    <td colspan="3" align="left"><b><?php echo htmlspecialchars( xl('Payments'), ENT_QUOTES) ?></b></td>
  </tr>
  <tr height="15">
    <td colspan="3" align="left" ></td>
  </tr>
  <tr>
    <td colspan="3" align="left">
		<ul class="tabNav">
		 <li class='current'><a href='new_payment.php'><?php echo htmlspecialchars( xl('New Payment'), ENT_QUOTES) ?></a></li>
		 <li><a href='search_payments.php'><?php echo htmlspecialchars( xl('Search Payment'), ENT_QUOTES) ?></a></li>
		 <li><a href='era_payments.php'><?php echo htmlspecialchars( xl('ERA Posting'), ENT_QUOTES) ?></a></li>
		</ul>	</td>
  </tr>
<?php
  }
 else
  {
  ?>
  <tr height="5">
    <td colspan="3" align="left" ></td>
  </tr>
<?php
  }
  ?>
  <tr>
    <td colspan="3" align="left" >

<?php
 if($payment_id*1>0)
  {
  ?>
    <?php 
	require_once(dirname(__FILE__)."/payment_master.inc.php");  //Check/cash details are entered here.
	?>
<?php
}
?>
	</td>
  </tr>
</table>



<?php
 if($payment_id*1>0)
  {//Distribution rows already in the database are displayed.
  ?>

<table width="100%" border="0" cellspacing="0" cellpadding="10" bgcolor="#DEDEDE"><tr><td>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">

		  <tr>
			<td colspan="13" align="left" >

				<?php //
				$resCount = sqlStatement("SELECT distinct encounter,code,modifier from ar_activity where  session_id ='$payment_id' ");
				$TotalRows=sqlNumRows($resCount);
				$CountPatient=0;
				$CountIndex=0;
				$CountIndexAbove=0;
				$paymenttot=0;
				$adjamttot=0;
				$deductibletot=0;
				$takebacktot=0;
				$allowedtot=0;
				if($_POST['mode']=='exportpdf' || $_POST['mode']=='exportexcel'){
				 echo "<script type='text/javascript'>";
				 echo "alert('There is no data.');";
				 echo "</script>";
				 die;
				}
				if($RowSearchSub = sqlFetchArray($ResultSearchSub))
				 {
					$Resultset=array();//for exporting to pdf,excell
					do 
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
						$ResultSearch = sqlStatement("SELECT billing.id,last_level_closed,billing.encounter,form_encounter.`date`,billing.code,billing.modifier,fee
						 FROM billing ,form_encounter
						 where billing.encounter=form_encounter.encounter and billing.pid=form_encounter.pid and 
						 code_type!='ICD9' and  code_type!='COPAY' and billing.activity!=0 and 
						 form_encounter.pid ='$PId' and billing.pid ='$PId' and billing.encounter ='$EncounterMaster'
						  and billing.code ='$CodeMaster'
						   and billing.modifier ='$ModifierMaster'
						 ORDER BY form_encounter.`date`,form_encounter.encounter,billing.code,billing.modifier");
						if(sqlNumRows($ResultSearch)>0)
						 {
						if($CountPatient==1)
						 {
						 $Table='yes';
						?>
						<table width="100%"  border="0" cellpadding="0" cellspacing="0" align="center" id="TableDistributePortion">
						  <tr class="text" bgcolor="#dddddd">
						    <td width="25" class="left top" >&nbsp;</td>
						    <td width="144" class="left top" ><?php echo htmlspecialchars( xl('Patient Name'), ENT_QUOTES) ?></td>
							<td width="55" class="left top" ><?php echo htmlspecialchars( xl('Post For'), ENT_QUOTES) ?></td>
							<td width="70" class="left top" ><?php echo htmlspecialchars( xl('Srv Date'), ENT_QUOTES) ?></td>
							<td width="50" class="left top" ><?php echo htmlspecialchars( xl('Encnter'), ENT_QUOTES) ?></td>
							<td width="65" class="left top" ><?php echo htmlspecialchars( xl('CPT Code'), ENT_QUOTES) ?></td>
							<td width="50" class="left top" ><?php echo htmlspecialchars( xl('Charge'), ENT_QUOTES) ?></td>
							<td width="40" class="left top" ><?php echo htmlspecialchars( xl('Copay'), ENT_QUOTES) ?></td>
							<td width="40" class="left top" ><?php echo htmlspecialchars( xl('Remdr'), ENT_QUOTES) ?></td>
							<td width="60" class="left top" ><?php echo htmlspecialchars( xl('Allowed(c)'), ENT_QUOTES) ?></td><!-- (c) means it is calculated.Not stored one. -->
							<td width="60" class="left top" ><?php echo htmlspecialchars( xl('Payment'), ENT_QUOTES) ?></td>
							<td width="70" class="left top" ><?php echo htmlspecialchars( xl('Adj Amount'), ENT_QUOTES) ?></td>
							<td width="60" class="left top" ><?php echo htmlspecialchars( xl('Deductible'), ENT_QUOTES) ?></td>
							<td width="60" class="left top" ><?php echo htmlspecialchars( xl('Takeback'), ENT_QUOTES) ?></td>
							<td width="50" class="left top" ><?php echo htmlspecialchars( xl('Note'), ENT_QUOTES) ?></td>
							<td width="100" class="left top right" id="td_reason" ><?php echo htmlspecialchars( xl('Denial/Reason'), ENT_QUOTES);?>
							</td>
						  </tr>
						  <?php
						  }
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
										//new fees screen copay gives account_code='PCP'
										//openemr payment screen copay gives code='CO-PAY'
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
									 {//Got just before Patient
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
									 {//Got just before the previous
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
								 $Resultset[$CountIndex-1]['CPT/Mod']=htmlspecialchars($Code.$ModifierString);
								 $Resultset[$CountIndex-1]['Charge']=htmlspecialchars($Fee);
								 $Resultset[$CountIndex-1]['Copay']=htmlspecialchars(number_format($Copay,2));
								 $Resultset[$CountIndex-1]['Remaider']=htmlspecialchars(round($Remainder,2));
								 $Resultset[$CountIndex-1]['Allowed']=htmlspecialchars($AllowedDB);
								 $Resultset[$CountIndex-1]['Payment']=htmlspecialchars($PaymentDB);
								 $Resultset[$CountIndex-1]['Adjustment']=htmlspecialchars($AdjAmountDB);
								 $Resultset[$CountIndex-1]['Deductible']=htmlspecialchars($DeductibleDB);
								 $Resultset[$CountIndex-1]['Takeback']=htmlspecialchars($TakebackDB);
								//===================================================================================								 
						  ?>
						  <tr class="text"  bgcolor='<?php echo $bgcolor; ?>' id="trCharges<?php echo $CountIndex; ?>">
						    <td align="left" class="<?php echo $StringClass; ?>" ><a href="#" onClick="javascript:return DeletePaymentDistribution('<?php echo  htmlspecialchars($payment_id.'_'.$PId.'_'.$Encounter.'_'.$Code.'_'.$Modifier); ?>');" ><img src="../images/Delete.gif" border="0"/></a></td>
						    <td align="left" class="<?php echo $StringClass; ?>" ><?php echo htmlspecialchars($NameDB); ?><input name="HiddenPId<?php echo $CountIndex; ?>" value="<?php echo htmlspecialchars($PId); ?>" type="hidden"/></td>
							<td align="left" class="<?php echo $StringClass; ?>" ><input name="HiddenIns<?php echo $CountIndex; ?>" id="HiddenIns<?php echo $CountIndex; ?>"  value="<?php echo htmlspecialchars($Ins); ?>" type="hidden"/><?php echo generate_select_list("payment_ins$CountIndex", "payment_ins", "$Ins", "Insurance/Patient",'','','ActionOnInsPat("'.$CountIndex.'")'); ?></td>
							<td class="<?php echo $StringClass; ?>" ><?php echo htmlspecialchars($ServiceDate); ?></td>
							<td align="right" class="<?php echo $StringClass; ?>" ><input name="HiddenEncounter<?php echo $CountIndex; ?>" value="<?php echo htmlspecialchars($Encounter); ?>" type="hidden"/><?php echo htmlspecialchars($Encounter); ?></td>
							<td class="<?php echo $StringClass; ?>" ><input name="HiddenCode<?php echo $CountIndex; ?>" value="<?php echo htmlspecialchars($Code); ?>" type="hidden"/><?php echo htmlspecialchars($Code.$ModifierString); ?><input name="HiddenModifier<?php echo $CountIndex; ?>" value="<?php echo htmlspecialchars($Modifier); ?>" type="hidden"/></td>
							<td align="right" class="<?php echo $StringClass; ?>" ><input name="HiddenChargeAmount<?php echo $CountIndex; ?>" id="HiddenChargeAmount<?php echo $CountIndex; ?>"  value="<?php echo htmlspecialchars($Fee); ?>" type="hidden"/><?php echo htmlspecialchars($Fee); ?></td>
							<td align="right" class="<?php echo $StringClass; ?>" ><input name="HiddenCopayAmount<?php echo $CountIndex; ?>" id="HiddenCopayAmount<?php echo $CountIndex; ?>"  value="<?php echo htmlspecialchars($Copay); ?>" type="hidden"/><?php echo htmlspecialchars(number_format($Copay,2)); ?></td>
							<td align="right"   id="RemainderTd<?php echo $CountIndex; ?>"  class="<?php echo $StringClass; ?>" ><?php echo htmlspecialchars(round($Remainder,2)); ?></td>
							<input name="HiddenRemainderTd<?php echo $CountIndex; ?>" id="HiddenRemainderTd<?php echo $CountIndex; ?>"  value="<?php echo htmlspecialchars(round($RemainderJS,2)); ?>" type="hidden"/>
							<td class="<?php echo $StringClass; ?>" ><input  name="Allowed<?php echo $CountIndex; ?>" id="Allowed<?php echo $CountIndex; ?>"  onKeyDown="PreventIt(event)"  autocomplete="off"  value="<?php echo htmlspecialchars($AllowedDB); ?>"  onChange="ValidateNumeric(this);ScreenAdjustment(this,<?php echo $CountIndex; ?>);UpdateTotalValues(1,<?php echo $TotalRows; ?>,'Allowed','allowtotal');UpdateTotalValues(1,<?php echo $TotalRows; ?>,'Payment','paymenttotal');UpdateTotalValues(1,<?php echo $TotalRows; ?>,'AdjAmount','AdjAmounttotal');RestoreValues(<?php echo $CountIndex; ?>)"   type="text"   style="width:60px;text-align:right; font-size:12px" /></td>
							<td class="<?php echo $StringClass; ?>" ><input   type="text"  name="Payment<?php echo $CountIndex; ?>"  onKeyDown="PreventIt(event)"   autocomplete="off"  id="Payment<?php echo $CountIndex; ?>" value="<?php echo htmlspecialchars($PaymentDB); ?>"  onChange="ValidateNumeric(this);ScreenAdjustment(this,<?php echo $CountIndex; ?>);UpdateTotalValues(1,<?php echo $TotalRows; ?>,'Payment','paymenttotal');RestoreValues(<?php echo $CountIndex; ?>)"  style="width:60px;text-align:right; font-size:12px" /></td>
							<td class="<?php echo $StringClass; ?>" ><input  name="AdjAmount<?php echo $CountIndex; ?>"  onKeyDown="PreventIt(event)"   autocomplete="off"  id="AdjAmount<?php echo $CountIndex; ?>"  value="<?php echo htmlspecialchars($AdjAmountDB); ?>"   onChange="ValidateNumeric(this);ScreenAdjustment(this,<?php echo $CountIndex; ?>);UpdateTotalValues(1,<?php echo $TotalRows; ?>,'AdjAmount','AdjAmounttotal');RestoreValues(<?php echo $CountIndex; ?>)"  type="text"   style="width:70px;text-align:right; font-size:12px" /></td>
							<td class="<?php echo $StringClass; ?>" ><input  name="Deductible<?php echo $CountIndex; ?>"  id="Deductible<?php echo $CountIndex; ?>"  onKeyDown="PreventIt(event)"  onChange="ValidateNumeric(this);UpdateTotalValues(1,<?php echo $TotalRows; ?>,'Deductible','deductibletotal');"  value="<?php echo htmlspecialchars($DeductibleDB); ?>"   autocomplete="off"   type="text"   style="width:60px;text-align:right; font-size:12px" /></td>
							<td class="<?php echo $StringClass; ?>" ><input  name="Takeback<?php echo $CountIndex; ?>"  onKeyDown="PreventIt(event)"   autocomplete="off"   id="Takeback<?php echo $CountIndex; ?>"   value="<?php echo htmlspecialchars($TakebackDB); ?>"   onChange="ValidateNumeric(this);ScreenAdjustment(this,<?php echo $CountIndex; ?>);UpdateTotalValues(1,<?php echo $TotalRows; ?>,'Takeback','takebacktotal');RestoreValues(<?php echo $CountIndex; ?>)"   type="text"   style="width:60px;text-align:right; font-size:12px" /></td>
							<td align="center" class="<?php echo $StringClass; ?>" >
							<select id="drop_down_reason<?php echo $CountIndex; ?>"  name="drop_down_reason<?php echo $CountIndex; ?>"  onChange="ActionFollowUp(<?php echo $CountIndex; ?>)">
							<option value="" ></option>
							<option value="r" <?php echo $drop_down_reason=='r' || $drop_down_reason=='y' ? ' selected ' : ''; ?> >Rea</option><!-- Follow up reason -->
							<option value="d" <?php echo $drop_down_reason=='d' ? ' selected ' : ''; ?> >Den</option><!-- Denial -->
							</select>
							</td>
							<td class="<?php echo $StringClass; ?> right" >
							<input  id="FollowUpBlank<?php echo $CountIndex; ?>" name="FollowUpBlank<?php echo $CountIndex; ?>"  type="text"  style="width:100px;font-size:12px; <?php echo $drop_down_reason=='' ? '' : ";display:none"; ?> " readonly="" />
							<input  onKeyDown="PreventIt(event)" id="FollowUpReason<?php echo $CountIndex; ?>" name="FollowUpReason<?php echo $CountIndex; ?>"  type="text"  value="<?php echo ($drop_down_reason=='r'  || $drop_down_reason=='y') && $FollowUpReasonDB!='' ?htmlspecialchars($FollowUpReasonDB):'Follow Up Rea'; ?>"  ONFOCUS="javascript:SetFieldBlank('FollowUpReason<?php echo $CountIndex; ?>','Follow Up Rea');" ONBLUR="javascript:SetFieldValue('FollowUpReason<?php echo $CountIndex; ?>','Follow Up Rea');"  style="width:100px;font-size:12px; color:#838383<?php echo $drop_down_reason=='r'  || $drop_down_reason=='y' ? '' : ";display:none"; ?> " />
							<select id="drop_down_denial<?php echo $CountIndex; ?>" style="width:100px;<?php echo $drop_down_reason=='d' ? '' : "display:none"; ?>"  name="drop_down_denial<?php echo $CountIndex; ?>"  >
							<option value="" ></option>
							<?php 
							$found_denial='no';
							foreach($payment_denial_reasons as $denial_key=>$denial_value)
							 {
							  if($denial_key==$FollowUpReasonDB && $drop_down_reason=='d')
							   {
								   $denial_selected=' selected ';
								   $found_denial='yes';
							   }
							  else
							   {
								   $denial_selected='';
							   }
							  echo "<option value='$denial_key' $denial_selected>$denial_value</option>";
							 }
							if($found_denial=='no' && $drop_down_reason=='d')
							 {
							  echo "<option value='$FollowUpReasonDB' selected>{$adjustment_reasons[$FollowUpReasonDB]}</option>";
							 }
							 $found_denial='no';
							 ?>
							</select>
							</td>
						  </tr>
						<?php
								
								
							 }//while ($RowSearch = sqlFetchArray($ResultSearch))
						?>
						<?php
						 }//if(sqlNumRows($ResultSearch)>0)

						 }while ($RowSearchSub = sqlFetchArray($ResultSearchSub));


					//===============================================================================
					//Exporting section.
					//===============================================================================
					//===============================================================================
					$Grouping='Payment From';
					$CalcFields=array('Allowed','Payment','Adjustment','Deductible','Takeback');
					$CalcType='sum';
					$HeadWidth=array(2,1,.75,1,.75,.75,.75,1,1,1,1,1,1);//donot include grouping
					$Align=array('text','text','int','text','int','text','int','int','int','int','int','int','int','int');//include grouping
					$Font='Arial, Helvetica, sans-serif';
					$PaperSize='A4';
					$Orientation='L';
					$Header=array('ZH HEALTHCARE SOLUTIONS,LLC','Payment Report','Check Number: '.htmlspecialchars($CheckNumber),'Date: '.htmlspecialchars(oeFormatShortDate($CheckDate)),'Amount: '.$PayTotal);
					$Alignment='J';
					//print_r($Resultset);die;
					if($_POST['mode']=='exportpdf'){
					$x=ob_get_level();
					for(;$x>0;$x--){
					  ob_get_clean();
					}
					reportPDF($Resultset,$Grouping,$CalcFields,$CalcType,'',$HeadWidth,$Align,$Font,$PaperSize,$Orientation,$Header,'',
					$Alignment,$DatePrepared,$TimePrepared,$PageNo,$ExtraFooterLine,$PrintFooterFirstPage);
					ob_start();
					}
					if($_POST['mode']=='exportexcel'){
					//print_r($Header)."<br>";
					//echo $Alignment."-".$DatePrepared."-".$TimePrepared;
					$x=ob_get_level();
					for(;$x>0;$x--){
					  ob_get_clean();
					}
					reportEXCEL($Resultset,$Grouping,$CalcFields,$CalcType,'',$HeadWidth,$Align,$Font,$PaperSize,$Orientation,$Header,'',
					$Alignment,$DatePrepared,$TimePrepared,$PageNo,$ExtraFooterLine,$PrintFooterFirstPage);
					ob_start();
					}
					//===============================================================================


						if($Table=='yes')
						 {
						?>
						 <tr class="text">
						    <td align="left" colspan="9">&nbsp;</td>
					        <td class="left bottom" bgcolor="#6699FF" id="allowtotal" align="right" ><?php echo htmlspecialchars(number_format($allowedtot,2)); ?></td>
					        <td class="left bottom" bgcolor="#6699FF" id="paymenttotal" align="right" ><?php echo htmlspecialchars(number_format($paymenttot,2)); ?></td>
	  						<td class="left bottom" bgcolor="#6699FF" id="AdjAmounttotal" align="right" ><?php echo htmlspecialchars(number_format($adjamttot,2)); ?></td>						
			      			<td class="left bottom" bgcolor="#6699FF" id="deductibletotal" align="right"><?php echo htmlspecialchars(number_format($deductibletot,2)); ?></td>
						    <td class="left bottom right" bgcolor="#6699FF" id="takebacktotal" align="right"><?php echo htmlspecialchars(number_format($takebacktot,2)); ?></td>
						    <td  align="center">&nbsp;</td>
						    <td  align="center">&nbsp;</td>
				          </tr>
						</table>
						<?php
						}
						?>
						<?
						echo '<br/>';

				}//if($RowSearchSub = sqlFetchArray($ResultSearchSub))
				?>		    </td>
		  </tr>
		  <tr>
		    <td colspan="13" align="left" >
				<?php 
				require_once(dirname(__FILE__)."/payment_pat_sel.inc.php"); //Patient ajax section and listing of charges.
				?>
			</td>
	      </tr>
		  <tr>
			<td colspan="13" align="left" >
				<table border="0" cellspacing="0" cellpadding="0"  align="center">
				  <tr height="5">
					<td ></td>
					<td ></td>
					<td></td>
				  </tr>
				  <tr>
					<td width="110"><a href="#" onClick="javascript:return ModifyPayments();"  class="css_button" style="width:106px"><span><?php echo htmlspecialchars( xl('Modify Payments'), ENT_QUOTES);?></span></a>
					</td>
					<td width="107"><a href="#" onClick="javascript:return FinishPayments();"  class="css_button" style="width:106px"><span><?php echo htmlspecialchars( xl('Finish Payments'), ENT_QUOTES);?></span></a>
					</td>
					<td>
						<?php
						if($screen=='edit_payment' && $payment_id*1>0)
						 {
						?>
						<table border="0" cellspacing="0" cellpadding="0">
						  <tr>
							<td><a href="#" id="ExportExcel" title="ExportExcel" class="css_button" onClick="ExportData('exportexcel');" ><span><?php echo htmlspecialchars(xl('Excel'),ENT_QUOTES);?></span></a>&nbsp;
							<a href="#" id="ExportPdf" title="ExportPdf" class="css_button" onClick="ExportData('exportpdf');"><span><?php echo htmlspecialchars(xl('PDF'),ENT_QUOTES);?></span></a></td>
						  </tr>
						</table>
						<?php
						 }
						?>
					</td>
				  </tr>
				</table>

		<?php
		 }//if($payment_id*1>0)
		?>		</td>
	  </tr>
	</table>
	</td></tr></table>

<input type="hidden" name="hidden_patient_code" id="hidden_patient_code" value="<?php echo htmlspecialchars($hidden_patient_code);?>"/>
<input type='hidden' name='mode' id='mode' value='' />
<input type='hidden' name='ajax_mode' id='ajax_mode' value='' />
<input type="hidden" name="after_value" id="after_value" value="<?php echo htmlspecialchars($_POST["mode"]);?>"/>
<input type="hidden" name="payment_id" id="payment_id" value="<?php echo htmlspecialchars($payment_id);?>"/>
<input type="hidden" name="hidden_type_code" id="hidden_type_code" value="<?php echo htmlspecialchars($TypeCode);?>"/>
<input type='hidden' name='global_amount' id='global_amount' value='' />
<input type='hidden' name='DeletePaymentDistributionId' id='DeletePaymentDistributionId' value='' />
<input type="hidden" name="ActionStatus" id="ActionStatus" value="<?php echo htmlspecialchars($Message);?>"/>
<input type='hidden' name='CountIndexAbove' id='CountIndexAbove' value='<?php echo htmlspecialchars($CountIndexAbove*1);?>' />
<input type='hidden' name='CountIndexBelow' id='CountIndexBelow' value='<?php echo htmlspecialchars($CountIndexBelow*1);?>' />
<input type="hidden" name="ParentPage" id="ParentPage" value="<?php echo htmlspecialchars($_REQUEST['ParentPage']);?>"/>
</form>

</body>
</html>
<?php 
if($_POST['mode']=='exportpdf' || $_POST['mode']=='exportexcel')
 {
	ob_end_clean();
 }
?>