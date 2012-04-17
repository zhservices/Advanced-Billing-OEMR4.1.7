<script LANGUAGE="javascript" TYPE="text/javascript">
function PreventIt(evt)//Specially for the browser chrome.
{//When focus is on the text box and enter key is pressed the form gets submitted.TO prevent it this function is used.
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode == 13)//tab key,enter key
    {
        if (evt.preventDefault) evt.preventDefault();
        if (evt.stopPropagation) evt.stopPropagation();
    }
}
function CancelDistribute()
 {//Used in the cancel button.Helpful while cancelling the distribution.
	if(confirm("<?php echo htmlspecialchars( xl('Would you like to Cancel Distribution for this Patient?'), ENT_QUOTES) ?>"))
	 {
		document.getElementById('hidden_patient_code').value='';
		document.getElementById('mode').value='search';
		//top.restoreSession();
		document.forms[0].submit();
	 }
	else
	 return false;
 }
function PostPayments()
 {//Used in saving the allocation
 	if(CompletlyBlank())//Checks whether any of the allocation row is filled.
	 {
	  alert("<?php echo htmlspecialchars( xl('Fill the Row.'), ENT_QUOTES) ?>")
	  return false;
	 }
 	if(!CheckPayingEntityAndDistributionPostFor())//Ensures that Insurance payment is distributed under Ins1,Ins2,Ins3 and Patient paymentat under Pat.
	 {
	  return false;
	 }
	PostValue=CheckUnappliedAmount();//Decides TdUnappliedAmount >0, or <0 or =0
	if(PostValue==1)
	 {
	  alert("<?php echo htmlspecialchars( xl('Cannot Post Payments.Undistributed is Negative.'), ENT_QUOTES) ?>")
	  return false;
	 }
	if(confirm("<?php echo htmlspecialchars( xl('Would you like to Post Payments?'), ENT_QUOTES) ?>"))
	 {
		document.getElementById('mode').value='PostPayments';
		//top.restoreSession();
		document.forms[0].submit();
	 }
	else
	 return false;
 }
function FinishPayments()
 {//Used in finishig the allocation.Usually done when the amount gets reduced to zero.
 //After this is pressed a confirmation screen comes,where you can edit if needed.
 	if(CompletlyBlank())//Checks whether any of the allocation row is filled.
	 {
	  alert("<?php echo htmlspecialchars( xl('Fill the Row.'), ENT_QUOTES) ?>")
	  return false;
	 }
 	if(!CheckPayingEntityAndDistributionPostFor())//Ensures that Insurance payment is distributed under Ins1,Ins2,Ins3 and Patient paymentat under Pat.
	 {
	  return false;
	 }
 	PostValue=CheckUnappliedAmount();//Decides TdUnappliedAmount >0, or <0 or =0
	if(PostValue==1)
	 {
	  alert("<?php echo htmlspecialchars( xl('Cannot Post Payments.Undistributed is Negative.'), ENT_QUOTES) ?>")
	  return false;
	 }
	if(PostValue==2)
	 {
		if(confirm("<?php echo htmlspecialchars( xl('Would you like to Post and Finish Payments?'), ENT_QUOTES) ?>"))
		 {
			UnappliedAmount=document.getElementById('TdUnappliedAmount').innerHTML*1;
			if(confirm("<?php echo htmlspecialchars( xl('Undistributed is'), ENT_QUOTES) ?>" + ' ' + UnappliedAmount +  '.' + "<?php echo htmlspecialchars('\n');echo htmlspecialchars( xl('Would you like the balance amount to apply to Global Account?'), ENT_QUOTES) ?>"))
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
		if(confirm("<?php echo htmlspecialchars( xl('Would you like to Post and Finish Payments?'), ENT_QUOTES) ?>"))
		 {
			document.getElementById('mode').value='FinishPayments';
			//top.restoreSession();
			document.forms[0].submit();
		 }
		else
		 return false;
	 }

 }
function CompletlyBlank()
 {//Checks whether any of the allocation row is filled.
  for(RowCount=1;;RowCount++)
   {
	  if(!document.getElementById('Payment'+RowCount))
	   break;
	  else
	   {
		   if(document.getElementById('Allowed'+RowCount).value=='' && document.getElementById('Payment'+RowCount).value=='' && document.getElementById('AdjAmount'+RowCount).value=='' && document.getElementById('Deductible'+RowCount).value=='' && document.getElementById('Takeback'+RowCount).value=='' && document.getElementById('FollowUp'+RowCount).checked==false)
			{

			}
		    else
			 return false;
	   }
   }
  return true;
 }
function OnloadAction()
 {//Displays message after saving to master table.
  after_value=document.getElementById("after_value").value;
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
	if(confirm("<?php echo htmlspecialchars( xl('Successfully Saved.Would you like to Allocate?'), ENT_QUOTES) ?>"))
	 {
		if(document.getElementById('TablePatientPortion'))
		 {
			document.getElementById('TablePatientPortion').style.display='';
		 }
	 }
   }

 }
function ResetForm()
 {//Resets form used in the 'Cancel Changes' button in the master screen.
  document.forms[0].reset();
  document.getElementById('TdUnappliedAmount').innerHTML='0.00';
  document.getElementById('div_insurance_or_patient').innerHTML='&nbsp;';
  CheckVisible('yes');//Payment Method is made 'Check Payment' and the Check box is made visible.
  PayingEntityAction();//Paying Entity is made 'insurance' and Payment Category is 'Insurance Payment'
 }
function FillUnappliedAmount()
 {//Filling the amount
  document.getElementById('TdUnappliedAmount').innerHTML=document.getElementById('payment_amount').value;
 }
</script>
<script language="javascript" type="text/javascript">
document.onclick=HideTheAjaxDivs;
</script>