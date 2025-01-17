<?php
require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');

// Init session
global $_SESSION;
session_init(true);

if (!$_SESSION['admin']) {
    die("You don't have permissions");
}
?>
<!--

	Panes for clone virtual machine wizard. Logic in vboxWizard()
	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: wizardCloneVM.html 595 2015-04-17 09:50:36Z imoore76 $

 -->
<!-- Step 1 -->
<div id='wizardCloneVMStep1' title='New machine name' style='display: none'>

	<span id='vboxWizCloneMessage1'></span>
	
	<div class='vboxOptions' style='padding: 6px'>
		<input type='text' class='vboxText' name='machineCloneName' style='width: 95%' />
		
		<p><label><input type='checkbox' class='vboxCheckbox' name='vboxCloneReinitNetwork' checked='checked' />
			<span class='translate'>Reinitialize the MAC address of all network cards</span></label>
		</p>
	</div>
	
</div>


<!-- Step 2 -->
<div id='wizardCloneVMStep2' title='Clone type' style='display: none'>

	<span class='translate'>&lt;p&gt;Please choose the type of clone you wish to create.&lt;/p&gt;&lt;p&gt;If you choose &lt;b&gt;Full clone&lt;/b&gt;, an exact copy (including all virtual hard disk files) of the original virtual machine will be created.&lt;/p&gt;&lt;p&gt;If you choose &lt;b&gt;Linked clone&lt;/b&gt;, a new machine will be created, but the virtual hard disk files will be tied to the virtual hard disk files of original machine and you will not be able to move the new virtual machine to a different computer without moving the original as well.&lt;/p&gt;</span>
	
	<span class='translate' id='vboxCloneVMNewSnap'>&lt;p&gt;If you create a &lt;b&gt;Linked clone&lt;/b&gt; then a new snapshot will be created in the original virtual machine as part of the cloning process.&lt;/p&gt;</span>
	
	<div class='vboxOptions'>
		<table>
			<tr style='vertical-align: bottom;'>
				<td><label><input type='radio' class='vboxRadio' name='vboxCloneType' value='Full' onclick='vboxCloneVMUpdateSteps(this.value)' /> <span class='translate'>Full Clone</span></label></td>
			</tr>
			<tr style='vertical-align: bottom;'>
				<td><label><input type='radio' class='vboxRadio' checked='checked' name='vboxCloneType' value='Linked' onclick='vboxCloneVMUpdateSteps(this.value)' /> <span class='translate'>Linked Clone</span></label></td>
			</tr>		
		</table>
	</div>
</div>

<!-- Step 3 -->
<div id='wizardCloneVMStep3' title='Snapshots' style='display: none'>

	<span class='translate'>&lt;p&gt;Please choose which parts of the snapshot tree should be cloned with the machine.&lt;/p&gt;</span>
	
	<p><span class='translate'>&lt;p&gt;If you choose &lt;b&gt;Current machine state&lt;/b&gt;, the new machine will reflect the current state of the original machine and will have no snapshots.&lt;/p&gt;</span>
		<span class='translate' id='wizardCloneVMCurrentAll' style='display: none;'>&lt;p&gt;If you choose &lt;b&gt;Current snapshot tree branch&lt;/b&gt;, the new machine will reflect the current state of the original machine and will have matching snapshots for all snapshots in the tree branch starting at the current state in the original machine.&lt;/p&gt;</span>
		<span class='translate'>&lt;p&gt;If you choose &lt;b&gt;Everything&lt;/b&gt;, the new machine will reflect the current state of the original machine and will have matching snapshots for all snapshots in the original machine.&lt;/p&gt;</span>
		</p>
	
	<div class='vboxOptions'>
		<table>
			<tr style='vertical-align: bottom;'>
				<td><label><input type='radio' class='vboxRadio' checked='checked' name='vmState' value='MachineState' /> <span class='translate'>Current machine state</span></label></td>
			</tr>
			<tr style='vertical-align: bottom; display:none;' id='vboxCloneCurrentAll'>
				<td><label><input type='radio' class='vboxRadio' name='vmState' value='MachineAndChildStates' /> <span class='translate'>Current snapshot tree branch</span></label></td>
			</tr>		
			<tr style='vertical-align: bottom;'>
				<td><label><input type='radio' class='vboxRadio' name='vmState' value='AllStates' /> <span class='translate'>Everything</span></label></td>
			</tr>	
		</table>
	</div>
</div>



<script type='text/javascript'>

$('#wizardCloneVMStep1').on('show',function(e,wiz){

	// Already initialized?
	if($('#wizardCloneVMStep1').data('init') || !wiz.args) return;
	
	$('#wizardCloneVMStep1').data('init',1);
	
	// Hold wizard
	$('#wizardCloneVMStep1').data('wiz',wiz);
	
	// Hold wizard original steps
	$('#wizardCloneVMStep1').data('wizSteps',wiz.steps);
	
	// Hide "new snapshot" message if we're cloning a snapshot
	if(wiz.args && wiz.args.snapshot)
		$('#vboxCloneVMNewSnap').hide();
	
	$('#vboxWizCloneMessage1').html(trans('<p>Please choose a name for the new virtual machine. The new machine will be a clone of the machine <b>%1</b>.</p>','UIWizardCloneVM').replace('%1',wiz.args.vm.name));
	
	
	if((wiz.args.snapshot && wiz.args.snapshot.children && wiz.args.snapshot.children.length)) {
		$('#wizardCloneVMCurrentAll').show();
		$('#vboxCloneCurrentAll').show();
	}

	$(document.forms['frmwizardCloneVM'].elements.machineCloneName).focus();
	document.forms['frmwizardCloneVM'].elements.machineCloneName.value = trans('%1 Clone','UIWizardCloneVMPage1').replace('%1',wiz.args.vm.name);

	var inputBox = $('#wizardCloneVMStep1').find('input.vboxText').select();
	setTimeout(inputBox.focus.bind(inputBox),10);
});

/* When going to step2, make sure a name is entered */
$('#wizardCloneVMStep2').on('show',function(e,wiz){

	document.forms['frmwizardCloneVM'].elements.machineCloneName.value = jQuery.trim(document.forms['frmwizardCloneVM'].elements.machineCloneName.value);

	if(!document.forms['frmwizardCloneVM'].elements.machineCloneName.value) {
		// Go back
		wiz.displayStep(1);
	}
	
	

});

function vboxCloneVMUpdateSteps(cval) {
	
	if(cval == 'Linked') {
		$('#wizardCloneVMStep1').data('wiz').setLast();
	} else if($('#wizardCloneVMStep1').data('wizSteps') != $('#wizardCloneVMStep1').data('wiz').steps) {
		$('#wizardCloneVMStep1').data('wiz').unsetLast();
	}
}

</script>
