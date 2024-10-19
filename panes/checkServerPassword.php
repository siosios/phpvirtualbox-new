<?php

?>
<div id="vboxServerPassword">
    <p><span class="translate">Password</span>:</p>
    <p><input type="text" readonly id="vboxServerPasswordInput" style="width: 98%;"></p>
    <p><b class="translate">For added security, change the administrator password. Changing the password on the server will not reflect the change here.</b></p>
</div>
<script type='text/javascript'>
    $('#vboxServerPassword').find(".translate").html(function(i,h){return trans(h,'VBoxServerCredentials');}).removeClass('translate');
</script>