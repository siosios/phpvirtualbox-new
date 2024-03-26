<!--

	VM Console tab
	Copyright (C) 2010-2015 Ian Moore (imoore76 at yahoo dot com)
	
	$Id: tabVMConsole.html 595 2015-04-17 09:50:36Z imoore76 $
	
 -->

<div id='vboxTabVMConsole' class='vboxTabContent' style='display:none;'>
    <p id="UIVMConsoleWarning" style="font-weight: bold;"></p>
    <select id="vboxConsoleHotkey" style="margin-top: 5px;">
        <option value="not_selected" id="UIVMConsoleSelect">Select hotkey or button</option>
    </select>
    <button id="vboxConsolePasteFromClipboard" onmouseover="paste_onmouseover();" onmouseleave="paste_onmouseleave();" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"><span class="ui-button-text" id="UIVMConsolePaste">Direct paste</span></button><br>
    <img src="" id="vboxVMScreenImg" tabindex="-1" style="margin-top: 5px; background-color: black; min-width: 500px; min-height: 500px; max-height: 1024px;">
</div>
<script type="text/javascript">
function paste_onmouseover() {
    document.getElementById('vboxConsolePasteFromClipboard').className = "ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-state-hover";
}

function paste_onmouseleave() {
    document.getElementById('vboxConsolePasteFromClipboard').className = "ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only";
}
(function() {

    var b64decode = function(data)
    {
        var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        var o1, o2, o3, h1, h2, h3, h4, bits, i=0, enc="";

        do
        {
            h1 = b64.indexOf(data.charAt(i++));
            h2 = b64.indexOf(data.charAt(i++));
            h3 = b64.indexOf(data.charAt(i++));
            h4 = b64.indexOf(data.charAt(i++));

            bits = h1<<18 | h2<<12 | h3<<6 | h4;

            o1 = bits>>16 & 0xff;
            o2 = bits>>8 & 0xff;
            o3 = bits & 0xff;

            if (h3 == 64) enc += String.fromCharCode(o1);
            else if (h4 == 64) enc += String.fromCharCode(o1, o2);
            else enc += String.fromCharCode(o1, o2, o3);
        } while (i < data.length);

        return unescape(enc);
    }

    var b64encode = function(data)
    {
        data = escape(data);
        var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        var o1, o2, o3, h1, h2, h3, h4, bits, i=0, enc="";

        do {
            o1 = data.charCodeAt(i++);
            o2 = data.charCodeAt(i++);
            o3 = data.charCodeAt(i++);

            bits = o1<<16 | o2<<8 | o3;

            h1 = bits>>18 & 0x3f;
            h2 = bits>>12 & 0x3f;
            h3 = bits>>6 & 0x3f;
            h4 = bits & 0x3f;

            enc += b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
        } while (i < data.length);

        switch (data.length % 3) {
            case 1:
                enc = enc.slice(0, -2) + "==";
                break;

            case 2:
                enc = enc.slice(0, -1) + "=";
                break;
        }

        return enc;
    }

    var updateImage = function(vmid) {
        if (document.getElementById('vboxTabVMConsole').style.display == 'none') {
            return;
        }
        img_loading = true;
        var rand = Math.floor((new Date()).getTime());
        img.src = vboxEndpointConfig.screen + "?randid="+rand+"&full=1&vm="+vmid;
    }

    document.getElementById('UIVMConsoleSelect').innerHTML = trans('Select hotkey or button','UIVMConsoleSelect');
    document.getElementById('UIVMConsolePaste').innerHTML = trans('Direct paste','UIVMConsolePaste');
    document.getElementById('UIVMConsoleWarning').innerHTML = trans('An emergency console is needed to quickly restore the operation of a virtual server in the event of a malfunction of the virtual server or incorrect network settings that do not allow connecting to it remotely. For added security, do not use this console as your primary method of server management.', 'UIVMConsoleWarning');

    var vmid = null;
    var prev_vmid = null;
    var img = document.getElementById('vboxVMScreenImg');
    var img_loading = false;
    var img_update_int = 0;
    img.onload = function(e) {
        img_loading = false;
    };

    var input_queue = [];
    var img_update_interval = null;

    var renew_interval = function() {
        if (img_update_interval != null) {
            clearInterval(img_update_interval);
        }
        img_update_int += 500;
        img_update_interval = setInterval(function() {
            if (document.getElementById('vboxTabVMConsole').style.display == 'none' || document.hidden) {
                img_loading = false;
                return;
            }
            if (img_loading) {
                console.warn("Image didn't manage to renew in time. Skipping this iteration.");
                return;
            }
            updateImage(vmid);
        }, img_update_int);
    };

    renew_interval();

    setInterval(function() {
        prev_vmid = vmid;
        vmid = vboxChooser.getSingleSelectedId();

        if (vmid != prev_vmid && vmid != null) {
            updateImage(vmid);
        }
        var selectedVms = vboxChooser.getSelectedVMsData();
        var selectedVm;
        if (typeof selectedVms[0] == 'undefined') {
            selectedVm = null;
        } else {
            selectedVm = selectedVms[0];
        }

        if (selectedVms.length > 1) {
            $('#vboxTabVMConsole').parent().trigger('disableTab',['vboxTabVMConsole']);
            return;
        }

        if (vmid != null && vmid != 'host' && selectedVm.state == 'Running') {
            $('#vboxTabVMConsole').parent().trigger('enableTab',['vboxTabVMConsole']);
        } else {
            $('#vboxTabVMConsole').parent().trigger('disableTab',['vboxTabVMConsole']);
        }
    }, 10);

    var keys = {
        9: "tab",
        13: "enter",
        8: "backspace",
        38: "uparrow",
        40: "downarrow",
        37: "leftarrow",
        39: "rightarrow",
        45: "insert",
        36: "home",
        33: "pageup",
        34: "pagedown",
        35: "end",
        46: "delete",
        27: "esc"
    };

    var combinations = {
        "ctrl_alt_del": "Ctrl + Alt + Del",
        "ctrl_c": "Ctrl + C",
        "ctrl_v": "Ctrl + V",
        "ctrl_x": "Ctrl + X",
        "ctrl_y": "Ctrl + Y",
        "ctrl_z": "Ctrl + Z",
        "ctrl_a_d": "Ctrl + A + D",
        "ctrl_l": "Ctrl + L",
        "ctrl_s": "Ctrl + S",
        "win": "Win",
        "win_r": "Win + R",
        "win_l": "Win + L",
        "f1": "F1",
        "f2": "F2",
        "f3": "F3",
        "f4": "F4",
        "f5": "F5",
        "f6": "F6",
        "f7": "F7",
        "f8": "F8",
        "f9": "F9",
        "f10": "F10",
        "f11": "F11",
        "f12": "F12",
        "shift_alt": "Shift + Alt",
        "alt_f4": "Alt + F4"
    };

    var $combinations = $('#vboxConsoleHotkey');

    if ($combinations.length == 1) {
        for (const [name, title] of Object.entries(combinations)) {
            $combinations.append(`
                <option value="`+name+`">`+title+`</option>
            `);
        }
    }

    $combinations.on('change', function(data) {
        var comb_id = $('#vboxConsoleHotkey').val();

        if (comb_id == 'not_selected') {
            return;
        }

        input_queue.push({
            t: 'c',
            c: comb_id
        });
        $combinations.val('not_selected');
        setTimeout(function() {
            document.getElementById("vboxVMScreenImg").focus();
        }, 10);
    });

    $('#vboxConsolePasteFromClipboard').on('click', function() {
        var paste = prompt(trans('Direct paste','UIVMConsolePaste'));

        if (!paste || paste == '') {
            return;
        }
        if (input_queue.length > 0 && input_queue[input_queue.length - 1].t == 'k') {
            input_queue[input_queue.length - 1].k += paste;
        } else {
            input_queue.push({
                t: 'k',
                k: paste
            });
        }
        setTimeout(function() {
            document.getElementById("vboxVMScreenImg").focus();
        }, 10);
    });

    document.body.onkeypress = function(e) {
        if (document.getElementById('vboxTabVMConsole').style.display == 'none') {
            return;
        }
        e.preventDefault();
        var key = String.fromCharCode(e.which);
        if (key == "\r") {
            return;
        }

        if (input_queue.length > 0 && input_queue[input_queue.length - 1].t == 'k') {
            input_queue[input_queue.length - 1].k += key;
        } else {
            input_queue.push({
                t: 'k',
                k: key
            });
        }
    };

    document.body.onkeydown = function(e) {
        if (document.getElementById('vboxTabVMConsole').style.display == 'none') {
            return;
        }

        var id = e.which;
        if (!(id in keys)) {
            return;
        }

        if (keys[id] == 'tab') {
            e.preventDefault();
        }

        input_queue.push({
            t: 'c',
            c: keys[id]
        });
    }

    setInterval(function() {
        if (input_queue.length == 0) {
            return;
        }

        var b64 = b64encode(JSON.stringify(input_queue));
        $.post('/endpoints/command.php', {'vm': vmid, input_queue: b64}, function(data) {
            if (data.replace("\n", "").replace(" ", "") != "") {
                alert(data);
            }
        });

        input_queue = [];
    }, 250);
})();
</script>

  
