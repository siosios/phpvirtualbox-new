<!--
	Login dialog

	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)

	$Id: login.html 595 2015-04-17 09:50:36Z imoore76 $
-->
<div id='vboxResetPasswordCheckCode'>
    <form>
        <table class='vboxVertical' style='margin:0px;'>
            <tr>
                <th><span class='translate'>Confirmation code</span>:</th>
                <td><input type='text' name='code' class='vboxText' /></td>
            </tr>
            <tr id='vboxSendCodeBtnRow' style='display:none;padding:0px;margin:0px;border:0px;'>
                <th style='padding:0px;margin:0px;border:0px'></th>
                <td style='padding:0px;margin:0px;border:0px'><input type='submit' value='' style='border:0px;margin:0px;display:inline;background:#fff;text:#fff;padding:0px;height:1px;' /></td>
            </tr>
        </table>
    </form>
    <div style="display: flex; flex-direction: column; margin-top: 10px;">
        <a href="javascript:;" id="resendCodeBtn" class="translate" style="margin: auto; color: blue;">Resend Code</a>
    </div>
</div>
<script type='text/javascript'>

    $('#vboxResetPasswordCheckCode').find(".translate").html(function(i,h){return trans(h,'UIUsers');}).removeClass('translate');

    $('#vboxResetPasswordCheckCode').submit(function(e){
        e.preventDefault();
    });
</script>