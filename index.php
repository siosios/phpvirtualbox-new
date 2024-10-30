<?php

require_once(dirname(__FILE__).'/endpoints/lib/config.php');
require_once(dirname(__FILE__).'/endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/endpoints/lib/vboxconnector.php');

// Init session
global $_SESSION;
session_init(true);

if (file_exists("classes/NotificationHelper.php-example") && !file_exists("classes/NotificationHelper.php-example")) {
    die("NotificationHelper is disabled. Please, open <b><code>classes</code></b> folder and copy <b><code>NotificationHelper.php-example</code></b> as <b><code>NotificationHelper.php</code></b>");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- $Id: index.html 595 2015-04-17 09:50:36Z imoore76 $ -->
<!-- Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com) -->
<head>

	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
	<meta http-equiv="Expires" content="0"/>
	<meta http-equiv="Cache-Control" content ="no-cache"/>
	<meta http-equiv="Cache-Control" content ="no-store, must-revalidate, max-age=0"/>
	<meta http-equiv="Cache-Control" content ="post-check=0, pre-check=0"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Icon && title -->
	<link rel="shortcut icon" href="images/vbox/OSE/VirtualBox_win.ico"/>
	<link rel="icon" href="images/vbox/OSE/VirtualBox_win.ico"/>
	<title>phpVirtualBox - VirtualBox Web Console</title>

	
	<!--  Style sheets -->
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.css"/>
    <link rel="stylesheet" type="text/css" href="css/jquery.projectPlugins.css"/>
	<link rel="stylesheet" type="text/css" href="css/tipped.css" />
    <link rel="stylesheet" type="text/css" href="css/layout.css"/>

    <!-- External or jQuery related scripts -->
    <script type="text/javascript" src="js/jquery-1.11.2.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.11.4.min.js"></script>
	<script type="text/javascript" src="js/jquery.tipped-2.1b.min.js"></script>
	<script type="text/javascript" src="js/jquery.scrollTo-min.js"></script>
	<script type="text/javascript" src="js/jquery.jec-1.3.1.js"></script>
	
	<!-- Oracle RDP Control -->
	<script type="text/javascript" src="rdpweb/webclient.js"></script>
	<script type="text/javascript" src="rdpweb/swfobject.js"></script>
	
	<!-- Internal / project related scripts -->
    <script type="text/javascript" src="endpoints/config.js"></script>
    <script type="text/javascript" src="js/jquery.projectPlugins.js"></script>
   	<script type="text/javascript" src="js/phpvirtualbox.js"></script>
    <script type="text/javascript" src="js/utils.js"></script>
    <script type="text/javascript" src="js/eventlistener.js"></script>
    <script type="text/javascript" src="js/chooser.js"></script>
    <script type="text/javascript" src="js/datamediator.js"></script>
	<script type="text/javascript" src="js/dialogs.js"></script>
	<script type="text/javascript" src="js/canvasimages.js"></script>


	<!-- Main Setup -->
	<script type='text/javascript'>
	
		$(document).ready(function(){

			/* Synchronously load requirements */
			for(var i = 0; i < vboxEndpointConfig.require.length; i++) {
				$.ajax({
			        type: "GET",
			        url: vboxEndpointConfig.require[i],
			        async: false,
			        dataType: "script"
			    });				
			}

			/*
			 * Parse cookies
			 */
			 vboxParseCookies();
			
			/*
			 *
			 * Begin sanity checks
			 *
			 */
			 
			 
			/**
			 * Check that someone isn't accessing this from their local filesystem
			 */
			try {
				if(window.location.toString().search('file:') == 0) {
					vboxAlert("You are accessing phpVirtualBox from your local filesystem.\
							phpVirtualBox must be accessed through your web browser. E.g. \
							http://localhost/phpVirtualBox (its actual URL may vary).");
					return;
				}
			} catch(err) {
				// noop
			}			
				
			
			/*
			 * If everything loaded correctly, trans() should be defined in
			 * js/language.php and language data should be present.
			 * If not, there is a PHP error somewhere.
			 */
			if(typeof trans != "function" || typeof __vboxLangData == "undefined") {
			//if(true) {

				trans = function(s){return s;};
				vboxAlert("An unknown PHP error occurred. This is most likely a syntax error in\
					config.php in phpVirtualBox's folder. The most common errors are an unclosed\
					 quote or a missing\
					semicolon in a configuration item that has been entered (e.g.\
					 location, username, or password).<p>Depending on your PHP configuration,\
					 navigating directly to <a href='config.php'>config.php</a> in your web\
					 browser may display the PHP error message.</p>\
					 <p>If find that this is not the case,\
					 or have no idea what this error message means, please raise the issue\
					 at <a target=_blank href='https://github.com/phpvirtualbox/phpvirtualbox/issues/'\
					 >https://github.com/phpvirtualbox/phpvirtualbox/issues",{'width':'50%'});
				return;
			}
			
			// Sanity checks passed. Begin processing
			
			// Check for server setting (?server=xxxx in URL)
			if(location.search) {
				var query = location.search.substr(1).split('&');
				for(var kv in query) {
					kv = query[kv].split('=');
					if(kv[0] == 'server') {
						vboxSetCookie('vboxServer',unescape(kv[1]));
						location = location.href.substr(0,location.href.length-location.search.length);
						return;
					}
				}
			}
			


			/*
			 * Resizable panes functionality
			 */
			$('#vboxResizeBar').draggable({cursor:(jQuery.browser.opera ? 'e-resize' : 'col-resize'),axis:'x',zIndex:99,helper:function(){
				
				$('#vboxResizeBarTmp').remove();
				var h = $('#vboxResizeBar').parent().height();
				return $('#vboxResizeBar').clone(false)
					.attr({'id':'vboxResizeBarTmp'}).unbind('mouseleave')
					.css({'background':'#ccc','height':h+'px'});
				
			},scroll:false,'start':function(e,ui){
				
				$('#vboxResizeOverlay').remove();
				$('body').disableSelection().css({'cursor':(jQuery.browser.opera ? 'e-resize' : 'col-resize')});
				$('#vboxPane').append($('<div />').attr({'id':'vboxResizeOverlay','style':'width:100%;height:100%;border:0px;margin:0px;padding:0px;position:absolute;top:0px;left:0px;z-index:10;cursor:'+(jQuery.browser.opera ? 'e-resize' : 'col-resize')}));
				$('#vboxResizeBar').data('vboxX',e.pageX);
				
			},'stop':function(e){

				$('#vboxResizeBarTmp').remove();
				$('#vboxResizeOverlay').remove();
				$('body').enableSelection().css({'cursor':'default'});
				

				var nx = $('#vboxChooserDiv').width() + (e.pageX - $('#vboxResizeBar').data('vboxX'));
				$('#vboxChooserDiv').css('width',(nx)+'px');
				
				vboxSetLocalDataItem("vboxPaneX",($('#vboxChooserDiv').width()));
				
				$('#vboxChooserPane').css('width',$('#vboxChooserDiv').css('width'));
				
				$(window).trigger('resize');
				
			}}).css('cursor',(jQuery.browser.opera ? 'e-resize' : 'col-resize')).parent().disableSelection();

			
			
			/*
				progress resize n / s
			*/
			$('#vboxResizeBarProgress').draggable({cursor:(jQuery.browser.opera ? 'n-resize' : 'row-resize'),axis:'y',zIndex:99,helper:function(){
				
				$('#vboxResizeBarTmp').remove();
				return $('#vboxResizeBarProgress').clone(false)
					.attr({'id':'vboxResizeBarTmp'})
					.css({'background':'#ccc','position':'absolute','left':'0','right':'0','margin-left':'auto','margin-right':'auto'});
				
			},scroll:false,'start':function(e,ui){
				
				$('#vboxResizeOverlay').remove();
				$('body').disableSelection().css({'cursor':(jQuery.browser.opera ? 'n-resize' : 'row-resize')});
				$('#vboxPane').append($('<div />').attr({'id':'vboxResizeOverlay','style':'width:100%;height:100%;border:0px;margin:0px;padding:0px;position:absolute;top:0px;left:0px;z-index:10;cursor:'+(jQuery.browser.opera ? 'n-resize' : 'row-resize')}));
				$('#vboxResizeBarProgress').data('vboxY',e.pageY);
				
			},'stop':function(e){

				$('#vboxResizeBarTmp').remove();
				$('#vboxResizeOverlay').remove();
				$('body').enableSelection().css({'cursor':'default'});
				

				var nx = $('#vboxProgressOps').height() + ($('#vboxResizeBarProgress').data('vboxY') - e.pageY);
				$('#vboxProgressOps').css({'height':(nx)+'px','overflow':'auto'}).parent().css({'height':(nx)+'px'});
				
				vboxSetLocalDataItem("vboxPaneY",($('#vboxProgressOps').height()));
				
				$('#vboxResizeBarProgressEW').css({'height':(vboxGetLocalDataItem("vboxPaneY")-4)+'px'});
				
				$(window).trigger('resize');
				
			}}).css('cursor',(jQuery.browser.opera ? 'n-resize' : 'row-resize')).parent().disableSelection();
			
			/*
				Progress resize E / W
			*/
			var vboxOpsPaneEW = 400;
			if(vboxGetLocalDataItem('vboxOpsPaneEW')) {
				vboxOpsPaneEW = vboxGetLocalDataItem('vboxOpsPaneEW'); 
			} else {
				vboxSetLocalDataItem('vboxOpsPaneEW', vboxOpsPaneEW);
			}
			$('#vboxResizeBarProgressEW').css({'left':vboxOpsPaneEW+'px','top':'0'});
			// inject CSS
			$('head').append('<style type="text/css" id="vboxProgressOpsStyle">div.vboxProgressOpTitle { width: '+vboxOpsPaneEW+'px; }</style>');

			// Show draggablebar onmouseenter
			$('#vboxProgressOps').hover(function(){
				if($(this).children().length == 1) return;
				$('#vboxResizeBarProgressEW').css({'display':'inline-block','height':($(this)[0].scrollHeight-2)+'px'});
			},function(){
				$('#vboxResizeBarProgressEW').css({'display':'none'});
			});
			
			// Draggable bar 
			$('#vboxResizeBarProgressEW').draggable({cursor:(jQuery.browser.opera ? 'e-resize' : 'col-resize'),axis:'x',zIndex:99,helper:function(){
				
				$('#vboxResizeBarTmp').remove();
			
				return $('#vboxResizeBarProgressEW').clone(false)
					.attr({'id':'vboxResizeBarProgressEWTmp'}).css({'background':'#ccc'});
				
			},scroll:false,'start':function(e,ui){
				
				$('#vboxResizeOverlay').remove();
				$('body').disableSelection().css({'cursor':(jQuery.browser.opera ? 'e-resize' : 'col-resize')});
				$('#vboxPane').append($('<div />').attr({'id':'vboxResizeOverlay','style':'width:100%;height:100%;border:0px;margin:0px;padding:0px;position:absolute;top:0px;left:0px;z-index:10;cursor:'+(jQuery.browser.opera ? 'e-resize' : 'col-resize')}));
				$('#vboxResizeBarProgressEW').data('vboxX',e.pageX);
				
			},'stop':function(e){

				$('#vboxResizeBarTmp').remove();
				$('#vboxResizeOverlay').remove();
				$('body').enableSelection().css({'cursor':'default'});
				
				var nx = parseInt(vboxGetLocalDataItem('vboxOpsPaneEW')) + (e.pageX - $('#vboxResizeBarProgressEW').data('vboxX'));
				$('#vboxResizeBarProgressEW').css({'left':nx+'px'});
				vboxSetLocalDataItem('vboxOpsPaneEW',nx);
				
				// re-inject CSS
				$('#vboxProgressOpsStyle').empty().remove();
				$('head').append('<style type="text/css" id="vboxProgressOpsStyle">div.vboxProgressOpTitle { width: '+nx+'px; }</style>');

				
			}}).css('cursor',(jQuery.browser.opera ? 'e-resize' : 'col-resize'));
			
			/*
			 * Resize panes when the window changes sizes
			 */
			$(window).resize(function(){
				
				// Hide 
				$('#vboxChooserResizePane').children().children().css({'display':'none'});
				
				var h = $('#vboxResize').innerHeight();
				$('#vboxChooserResizePane').children().children().css({'height':h+'px','overflow-y':'auto','overflow-x':'hidden','display':''});
				
				// special for resize bar
				$('#vboxResizeBar').css({'height':(h-10)+'px'});
				
				
			});
			

			/*
			 * Refresh data when host changes
			 */
			$('#vboxPane').on('hostChange',function(){
				
				var l = new vboxLoader();
				l.add('getConfig',function(d){$('#vboxPane').data('vboxConfig',d.responseData);$('#vboxPane').trigger('configLoaded');});
				l.add('vboxGetGuestOSTypes',function(d){$('#vboxPane').data('vboxOSTypes',d.responseData);});
				l.add('vboxSystemPropertiesGet',function(d){$('#vboxPane').data('vboxSystemProperties',d.responseData);});
				l.add('vboxGetMedia',function(d){$('#vboxPane').data('vboxMedia',d.responseData);});
				l.add('hostGetDetails',function(d){$('#vboxPane').data('vboxHostDetails',d.responseData);});
				l.add('vboxRecentMediaGet',function(d){$('#vboxPane').data('vboxRecentMedia',d.responseData);});
				l.add('vboxRecentMediaPathsGet',function(d){$('#vboxPane').data('vboxRecentMediaPaths',d.responseData);});
				l.add('vboxGetEnumerationMap',function(d){$('#vboxPane').data('vboxMediumVariants',d.responseData);},{'class':'MediumVariant','ValueMap':1});
				
				l.onLoad = function(){
					$('#vboxPane').trigger('hostChanged');
				};
	
				l.run();
								
			});
			
			
			/*
			 * Load panes all and data after valid login
			 */
			$('#vboxPane').on('login', function() {
				
				var l = new vboxLoader();
				
				// Get data and store it using data()
				l.add('getConfig',function(d){$('#vboxPane').data('vboxConfig',d.responseData);$('#vboxPane').trigger('configLoaded');});
				l.add('vboxGetGuestOSTypes',function(d){$('#vboxPane').data('vboxOSTypes',d.responseData);});
				l.add('vboxSystemPropertiesGet',function(d){$('#vboxPane').data('vboxSystemProperties',d.responseData);});
				l.add('vboxGetMedia',function(d){$('#vboxPane').data('vboxMedia',d.responseData);});
				l.add('hostGetDetails',function(d){$('#vboxPane').data('vboxHostDetails',d.responseData);});
				l.add('vboxRecentMediaGet',function(d){$('#vboxPane').data('vboxRecentMedia',d.responseData);});
				l.add('vboxRecentMediaPathsGet',function(d){$('#vboxPane').data('vboxRecentMediaPaths',d.responseData);});
				l.add('vboxGetEnumerationMap',function(d){$('#vboxPane').data('vboxMediumVariants',d.responseData);},{'class':'MediumVariant','ValueMap':1});
	
				// Load HTML panes and append them to their respective locations
				l.addFileToDOM('panes/chooser.php',$('#vboxChooserPane'));
				l.addFileToDOM('panes/topmenu.php');
				l.addFileToDOM('panes/toolbar.php');
				l.addFileToDOM('panes/tabs.php',$('#vboxPaneTabContent'));
	
				l.onLoad = function() {
	
					
					// Resize to last setting
					if(vboxGetLocalDataItem('vboxPaneX')) {
						$('#vboxChooserDiv').css('width',(vboxGetLocalDataItem('vboxPaneX'))+'px');
					} else {
						vboxSetLocalDataItem('vboxPaneX', $('#vboxChooserDiv').innerWidth());
					}
					
					if(vboxGetLocalDataItem('vboxPaneY')) {
						var nx = vboxGetLocalDataItem('vboxPaneY');
						$('#vboxProgressOps').css({'height':(nx)+'px'}).parent().css({'height':(nx)+'px'});
					} else {
						vboxSetLocalDataItem('vboxPaneY', $('#vboxProgressOps').height());
					}
					$('#vboxResizeBarProgressEW').css({'height':(vboxGetLocalDataItem("vboxPaneY")-4)+'px'});
					
					// Let everyone know that no vms are selected
					$('#vboxPane').trigger('vmSelectionListChanged',[vboxChooser]);
					
				};
				
				// Trigger resize event to size panes
				l.onShow = function() { $(window).trigger('resize'); };
	
				l.hideRoot = true;
				l.run();
				
			});
						
			/**
			 * Check for valid session and display login box if one does not exist
			 * @params {Boolean} tried - true if login was attempted before this call
			 */
			var vboxCheckSession = function(tried) {
				
				// check session info
				if($('#vboxPane').data('vboxSession') && $('#vboxPane').data('vboxSession').valid) {
					
					// Session is valid, trigger login
					$('#vboxPane').trigger('login');
					return;
				}
				
				// Was there an error? Assume it was displayed and just return from function
				if($('#vboxPane').data('vboxSession') && !$('#vboxPane').data('vboxSession').success) {
					return;
				}
				

				// No valid session. Show login pane
				$('#vboxLogin').find('input[name=password]').val('');
				$('#vboxLogin').dialog('open');
				
				if(!$('#vboxLogin').find('input[name=username]').val()) $('#vboxLogin').find('input[name=username]').focus();
				else $('#vboxLogin').find('input[name=password]').focus();
				
				// Display error if we tried to log in
				if(tried) {
					vboxAlert(trans('Invalid username or password.','UIUsers'),{'width':'400px'});
				}
				
			};

			/** Load login form */
			var login = new vboxLoader();
			login.add('getSession',function(d){$('#vboxPane').data('vboxSession',$.extend({'success':d.success},d.responseData));});
			login.addFileToDOM('panes/login.php');
			login.onLoad = function(loader) {

				var buttons = {};
				buttons[trans('Log in','UIUsers')] = function() {
					
					// Login button triggers login attempt
					var u = $('#vboxLogin').find('input[name=username]').val();
					var p = $('#vboxLogin').find('input[name=password]').val();
					if(!(u&&p)) return;
					$('#vboxLogin').dialog('close');
					
					// A valid login should create a valid session
					var trylogin = new vboxLoader();
					trylogin.add('login',function(d){$('#vboxPane').data('vboxSession',$.extend({'success':d.success},d.responseData));},{'u':u,'p':p});
					trylogin.onLoad = function() { vboxCheckSession(true);};
					trylogin.run();
				};
				
				// Create but do not open dialog
				if($.browser.webkit) heightadd = 5;
				else heightadd = 0;
				$('#vboxLogin').dialog({'closeOnEscape':false,'width':300,'height':'auto','buttons':buttons,'modal':true,'autoOpen':false,'dialogClass':'vboxDialogContent','title':'<img src="images/vbox/OSE/about_16px.png" class="vboxDialogTitleIcon" /> phpVirtualBox :: ' + trans('Log in','UIUsers')});
				$('#vboxLogin').find('input[name=username]').first().focus();
				
				// Trick loader into not showing root pane again
				loader.hideRoot = false;
				
				// Login form is loaded, run check for valid session
				vboxCheckSession();
				
			};
			login.hideRoot = true;
			login.run();
			
		}); // </ document.ready event >
		
	</script>

</head>
<body>
<div id='vboxPane' style='height: 100%; margin: 0px; padding: 0px;'>
<table id='vboxTableMain' cellpadding=0 cellspacing=0 style="height: 100%; width: 100%; padding: 0px; margin: 0px; border: 0px; border-spacing: 0px;">
	<tr style='vertical-align: middle;'>
		<td style='height:20px;border:0px;padding:0px;margin:0px;border-spacing:0px;'>
			<div id='vboxMenu'>
				<!--
				
					Top menu bar
				
				 -->
			</div>
		</td>
	</tr>
	<tr style='vertical-align: middle;'>
		<td id='vboxToolbarMain' style='height: 1%' class='vboxToolbarGrad'>
			<!--
				
				VM list toolbar
				
			-->
			<div id='vboxPaneToolbar'></div>
			<!--
				
				Tabs / buttons
				
			-->
			<div id='vboxTabsList'></div>
		</td>
	</tr>
	<tr style='vertical-align: top;'>
		<td style='border:0px;padding:0px;margin:0px;border-spacing:0px;' id='vboxResize'>
			<table style='width:100%;border:0px;padding:0px;border-spacing:0px;'>
				<tr id='vboxChooserResizePane' style='vertical-align: top;'>
					<td id="vboxChooserPane" style='padding:0px;border-spacing:0px;margin:0px;'>
						<!--
						
						VM List
							
						-->
					</td>
					<td id='vboxResizeTD' style='border: 0px; margin: 0px; padding: 0px; text-align: center;'>
						<div style='margin:0px;padding:0px;width:4px;height:100%;'>
							<div style='position: absolute; margin: 0px; padding:2px; width: 0px; height: 100%;' id='vboxResizeBar' ></div>
						</div>
					</td>
					<td id="vboxPaneTabContent" style='width:100%;border:0px;padding:0px;border-spacing:0px;margin:0px'>
						<!--
						
							Tab content
						
						 -->
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<!--
		Progress operation list resize bar
	-->
	<tr style='padding: 0px; margin: 0px; vertical-align: middle;'>
		<td id='vboxResizeTDProgress' style='border: 0px; margin: 0px; padding: 0px; text-align: center; height: 5px; vertical-align: middle'>
			<div style='width:99%; margin:0 auto; padding:2px;' id='vboxResizeBarProgress' ></div>
		</td>
	</tr>
	<tr style='vertical-align: top; padding: 0px;'>
		<td style='border:0px;padding:0px;margin:0px;border-spacing:0px;height:1%'>
				<div id='vboxProgressOps' style='height:80px;overflow:auto;position:relative'>
					<!--
						Resize bar for E/ W 
					 -->
					<div style="position:absolute;z-index:1;margin:0px;border:0px;padding:1px;width: 0px;background:#aaa;display:none;" id='vboxResizeBarProgressEW' ></div>
					<!--
					
						Progress operation list
					
					 -->
			</div>
		</td>
	</tr>
</table>
</div>
<div onclick="javascript:window.location.href='./';" style="height:100%; cursor:pointer;">&nbsp;</div>
</body>
</html>
