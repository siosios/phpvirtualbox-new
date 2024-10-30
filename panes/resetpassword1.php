<!--
	Login dialog

	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)

	$Id: login.html 595 2015-04-17 09:50:36Z imoore76 $
-->
<div id='vboxResetPassword1'>
    <form>
        <table class='vboxVertical' style='margin:0px;'>
            <tr>
                <th><span class='translate'>Username</span>:</th>
                <td><input type='text' name='username' class='vboxText' /></td>
            </tr>
            <tr id='vboxSendCodeBtnRow' style='display:none;padding:0px;margin:0px;border:0px;'>
                <th style='padding:0px;margin:0px;border:0px'></th>
                <td style='padding:0px;margin:0px;border:0px'><input type='submit' value='' style='border:0px;margin:0px;display:inline;background:#fff;text:#fff;padding:0px;height:1px;' /></td>
            </tr>
        </table>
    </form>
</div>
<script type='text/javascript'>

    $('#vboxResetPassword1').find(".translate").html(function(i,h){return trans(h,'UIUsers');}).removeClass('translate');

    $('#vboxResetPassword1').submit(function(e){
        e.preventDefault();
    });
</script>