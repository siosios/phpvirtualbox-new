<?php
require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');

$settings = new phpVBoxConfigClass();
?>
<!--
	Login dialog

	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: login.html 595 2015-04-17 09:50:36Z imoore76 $
-->
<div id='vboxLogin'>
    <form>
        <table class='vboxVertical' style='margin:0px;'>
            <tr>
                <th><span class='translate'>Username</span>:</th>
                <td><input type='text' name='username' class='vboxText' /></td>
            </tr>
            <tr>
                <th><span class='translate'>Password</span>:</th>
                <td><input type='password' name='password' class='vboxText' /></td>
            </tr>
            <tr id='vboxLoginBtnRow' style='display:none;padding:0px;margin:0px;border:0px;'>
                <th style='padding:0px;margin:0px;border:0px'></th>
                <td style='padding:0px;margin:0px;border:0px'><input type='submit' value='' style='border:0px;margin:0px;display:inline;background:#fff;text:#fff;padding:0px;height:1px;' /></td>
            </tr>
        </table>
    </form>
    <div style="display: flex; flex-direction: column;">
        <?php if ($settings->enablePasswordReset): ?>
        <a href="javascript:;" id="resetPasswordBtn" class="translate" style="margin: auto; color: blue;">Restore password</a>
        <?php endif; ?>
    </div>
</div>
<script type='text/javascript'>
    var __USERNAME, __CODE;
    $('#vboxLogin').find(".translate").html(function(i,h){return trans(h,'UIUsers');}).removeClass('translate');

    if($.browser.msie || $.browser.webkit) $('#vboxLoginBtnRow').css({'display':''});
    $('#vboxLogin form').on('submit', function(e) {
        $('#vboxLogin').dialog( "option", "buttons" )[trans('Log in','UIUsers')]();
        e.stopPropagation();
        e.preventDefault();
        return false;
    });

    $('#resetPasswordBtn').on('click', function() {
        $('#vboxLogin').dialog('close');

        /**
         * SEND CODE FORM START
         */
        var resetPassword = new vboxLoader();
        resetPassword.addFileToDOM('panes/resetpassword1.php');
        resetPassword.onLoad = function(loader) {
            var buttons = {};
            buttons[trans('Send confirmation code','UIUsers')] = function() {
                var sendCode = new vboxLoader();
                __USERNAME = $('#vboxResetPassword1').find('input[name=username]').val();
                sendCode.add('resetPassword1',function(d) {
                    if (!d.success) {
                        vboxAlert(trans(d.error,'UIUsers'),{'width':'400px'});
                        return;
                    }

                    /**
                     * CHECK CODE FORM START
                     */
                    $('#vboxResetPassword1').dialog('close');
                    var checkCodeForm = new vboxLoader();
                    checkCodeForm.addFileToDOM('panes/resetpasswordcheckcode.php');
                    checkCodeForm.onLoad = function(loader) {
                        var buttons = {};
                        buttons[trans('Change password','UIUsers')] = function() {
                            var checkCodeAjax = new vboxLoader();
                            __CODE = $('#vboxResetPasswordCheckCode').find('input[name=code]').val();
                            checkCodeAjax.add('resetPassword2',function(d) {
                                if (!d.success) {
                                    vboxAlert(trans(d.error,'UIUsers'),{'width':'400px'});
                                    return;
                                }

                                /**
                                 * CHANGE PASSWORD FORM START
                                 */
                                $('#vboxResetPasswordCheckCode').dialog('close');
                                var changePasswordForm = new vboxLoader();
                                changePasswordForm.addFileToDOM('panes/resetpassword2.php');
                                changePasswordForm.onLoad = function(loader) {
                                    var buttons = {};
                                    buttons[trans('Update password','UIUsers')] = function() {
                                        var changePasswordAjax = new vboxLoader();
                                        var p1 = $('#vboxResetPassword2').find('input[name=password1]').val();
                                        var p2 = $('#vboxResetPassword2').find('input[name=password2]').val();
                                        if (p1.length === 0 || p1 !== p2) {
                                            vboxAlert(trans('The passwords you have entered do not match.','UIUsers'),{'width':'auto'});
                                            return;
                                        }
                                        changePasswordAjax.add('resetPassword2',function(d) {
                                            if (!d.success) {
                                                vboxAlert(trans(d.error,'UIUsers'),{'width':'400px'});
                                                return;
                                            }

                                            alert(trans('Password changed.', 'UIUsers'));
                                            location.reload();
                                        }, {'u': __USERNAME, c: __CODE, checkCodeOnly: false, p: p1});
                                        changePasswordAjax.onLoad = function() { };
                                        changePasswordAjax.run();
                                    };

                                    // Create but do not open dialog
                                    if($.browser.webkit) heightadd = 5;
                                    else heightadd = 0;
                                    $('#vboxResetPassword2').dialog({'closeOnEscape':false,'width':300,'height':'auto','buttons':buttons,'modal':true,'autoOpen':false,'dialogClass':'vboxDialogContent','title':'<img src="images/vbox/OSE/about_16px.png" class="vboxDialogTitleIcon" /> phpVirtualBox :: ' + trans('Restore password','UIUsers')});
                                    $('#vboxResetPassword2').find('input[name=code]').first().focus();

                                    // Trick loader into not showing root pane again
                                    loader.hideRoot = false;
                                    $('#vboxResetPassword2').dialog('open');
                                };
                                changePasswordForm.hideRoot = true;
                                changePasswordForm.run();
                                /**
                                 * CHANGE PASSWORD FORM END
                                 */
                            }, {'u':__USERNAME, checkCodeOnly: true, c: __CODE});
                            checkCodeAjax.onLoad = function() { };
                            checkCodeAjax.run();
                        };

                        // Create but do not open dialog
                        if($.browser.webkit) heightadd = 5;
                        else heightadd = 0;
                        $('#vboxResetPasswordCheckCode').dialog({'closeOnEscape':false,'width':300,'height':'auto','buttons':buttons,'modal':true,'autoOpen':false,'dialogClass':'vboxDialogContent','title':'<img src="images/vbox/OSE/about_16px.png" class="vboxDialogTitleIcon" /> phpVirtualBox :: ' + trans('Restore password','UIUsers')});
                        $('#vboxResetPasswordCheckCode').find('input[name=code]').first().focus();

                        // Trick loader into not showing root pane again
                        loader.hideRoot = false;
                        $('#vboxResetPasswordCheckCode').dialog('open');

                        $('#vboxResetPasswordCheckCode').find('#resendCodeBtn').off('click');
                        $('#vboxResetPasswordCheckCode').find('#resendCodeBtn').on('click', function() {
                            var resendCodeAjax = new vboxLoader();
                            resendCodeAjax.add('resetPasswordResendCode',function(d) {
                                if (!d.success) {
                                    vboxAlert(trans(d.error,'UIUsers'),{'width':'400px'});
                                    return;
                                }

                                alert(trans('The verification code has been resent.', 'UIUsers'));
                            }, {'u': __USERNAME});
                            resendCodeAjax.onLoad = function() { };
                            resendCodeAjax.run();
                        });
                    };
                    checkCodeForm.hideRoot = true;
                    checkCodeForm.run();
                    /**
                     * CHECK CODE FORM END
                     */
                }, {u: __USERNAME});
                sendCode.onLoad = function() { };
                sendCode.run();
            };

            // Create but do not open dialog
            if($.browser.webkit) heightadd = 5;
            else heightadd = 0;
            $('#vboxResetPassword1').dialog({'closeOnEscape':false,'width':300,'height':'auto','buttons':buttons,'modal':true,'autoOpen':false,'dialogClass':'vboxDialogContent','title':'<img src="images/vbox/OSE/about_16px.png" class="vboxDialogTitleIcon" /> phpVirtualBox :: ' + trans('Restore password','UIUsers')});
            $('#vboxResetPassword1').find('input[name=username]').first().focus();

            // Trick loader into not showing root pane again
            loader.hideRoot = false;
            $('#vboxResetPassword1').dialog('open');
        };
        resetPassword.hideRoot = true;
        resetPassword.run();
        /**
         * SEND CODE FORM END
         */
    });
</script>