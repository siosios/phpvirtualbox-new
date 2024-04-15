<?php

?>
<div id="vboxTabVMConsoleDirectPaste">
    <form>
        <p><b><span class="translate">Direct paste</span></b></p>
        <p><label><input type='checkbox' id='vboxTabVMConsoleDirectPasteAsPassword' class='vboxCheckbox' /> <span class='translate'>Paste as password</span></label></p>
        <input type="text" class="vboxText" autocomplete="off" autofocus="yes" id="vboxDirectPasteInput" style="width: 95%;">
    </form>
</div>
<script type='text/javascript'>
    $('#vboxTabVMConsoleDirectPaste').find(".translate").html(function(i,h){return trans(h,'UIVMConsolePaste');}).removeClass('translate');
    $('#vboxTabVMConsoleDirectPaste form').on("submit",function(e) {
        $('#vboxTabVMConsoleDirectPaste').parent().find('span:contains("'+trans('OK','QIMessageBox')+'")').trigger('click');
        e.stopPropagation();
        e.preventDefault();
        return false;
    });

    $('#vboxDirectPasteInput').focus();

    $('#vboxTabVMConsoleDirectPasteAsPassword').change(function() {
        if (this.checked) {
            $('#vboxDirectPasteInput').prop('type', 'password');
        } else {
            $('#vboxDirectPasteInput').prop('type', 'text');
        }
    });
</script>