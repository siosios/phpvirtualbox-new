<?php

?>
<div id="vboxCtrlAltDelWarning">
    <p><span class="translate">The Ctrl+Alt+Del key combination on server operating systems Ubuntu, Debian and others reboots the operating system. Do you want to continue?</span></p>
</div>
<script type='text/javascript'>
    $('#vboxCtrlAltDelWarning').find(".translate").html(function(i,h){return trans(h,'UIVMCtrlAltDelWarning');}).removeClass('translate');
</script>