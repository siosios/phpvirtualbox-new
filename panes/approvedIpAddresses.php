<?php

require_once(dirname(__FILE__).'/../endpoints/lib/config.php');
require_once(dirname(__FILE__).'/../endpoints/lib/utils.php');
require_once(dirname(__FILE__).'/../endpoints/lib/vboxconnector.php');
require_once(dirname(__FILE__).'/../classes/IpProtection.php');
// Init session
global $_SESSION;
session_init(true);
$settings = new phpVBoxConfigClass();

if (!$settings->pathToIpProtectionDatabase) {
    echo "Unavailable";
    return;
}
$data = IpProtection::getInstance()->getApprovedAddresses($_SESSION['user']);
?>
<div id="vboxApprovedIpAddresses">
    <table>
        <tr>
            <th class="translate">IP address</th>
            <th class="translate">Last log-in</th>
            <th class="translate">Status</th>
            <th class="translate">Delete</th>
        </tr>
        <?php foreach ($data as $row): ?>
        <tr>
            <td><?php echo $row['address']; echo ($_SERVER['REMOTE_ADDR'] == $row['address'] ? " <span class='translate' style='color: cornflowerblue;'>It is you</span>" : ''); ?></td>
            <td><?php echo $row['lastupdate']; ?></td>
            <td>
                <?php
                switch ($row['adstate']) {
                    case IpProtection::STATE_CONFIRMED:
                        echo "<div class='translate' style='color: green;'>Confirmed</div>";
                        break;

                    case IpProtection::STATE_UNCONFIRMED:
                        echo "<div class='translate' style='color: red;'>Not confirmed</div>";
                        break;
                }
                ?>
            </td>
            <td><a href="javascript:;" data-ip="<?php echo $row['address']; ?>" class="deleteAddress translate">Delete</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<style>
    #vboxApprovedIpAddresses table {
        border: 1px solid black;
    }

    #vboxApprovedIpAddresses th {
        border: 1px solid black;
    }

    #vboxApprovedIpAddresses td {
        border: 1px solid black;
        padding: 5px;
    }

    #vboxApprovedIpAddresses .deleteAddress {
        color: indianred;
        border: 1px solid indianred;
        padding: 3px;
        border-radius: 4px;
    }

    #vboxApprovedIpAddresses .deleteAddress:hover {
        color: darkred;
        border: 1px solid darkred;
    }
</style>

<script type="text/javascript">
    $('#vboxApprovedIpAddresses').find(".translate").html(function(i,h){return trans(h,'UIApprovedIpAddresses');}).removeClass('translate');

    $('#vboxApprovedIpAddresses').find('.deleteAddress').on('click', function() {
        const ip = $(this).attr('data-ip');
        let deleteApprovedAddressAjax = new vboxLoader();
        deleteApprovedAddressAjax.add('deleteApprovedAddress',function(d) {
            $('#vboxApprovedIpAddresses').dialog('close');
            var l = new vboxLoader();
            l.addFileToDOM('panes/approvedIpAddresses.php');
            l.onLoad = function(){
                var buttons = {};
                buttons[trans('Cancel','QIMessageBox')] = function(){
                    $(this).remove();
                };
                $('#vboxApprovedIpAddresses').dialog({'closeOnEscape':false,'width':1000,'height':500,'buttons':buttons,'modal':true,'autoOpen':true,'dialogClass':'vboxDialogContent','title':'<img src="images/vbox/nw_16px.png" class="vboxDialogTitleIcon" /> '+trans('Approved IP Addresses','UIApprovedIpAddresses')});
            };
            l.run();
        }, {'a': ip});
        deleteApprovedAddressAjax.onLoad = function() { };
        deleteApprovedAddressAjax.run();
    });
</script>