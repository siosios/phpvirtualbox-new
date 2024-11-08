<?php

require_once __DIR__ . '/classes/IpProtection.php';

$code = $_GET['c'] ?? '';

$ipp = IpProtection::getInstance();

switch ($ipp->approve($code)) {
    case IpProtection::RESULT_APPROVED:
        echo "You IP address has been confirmed! You can close this page.\n";
        echo "<script>
            setTimeout(function() {
                window.close();
            }, 2000);
        </script>";
        break;

    case IpProtection::RESULT_INVALID_LINK:
        echo "Link is expired or invalid.";
        break;

    case IpProtection::RESULT_IP_MISMATCH:
        echo "The initial request had a different IP address.";
        break;
}