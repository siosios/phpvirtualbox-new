<?php

class NotificationHelperBase
{
    public static function onPasswordSendConfirmationCode(string $username, string $code, int $expires) {

    }

    public static function onIpConfirmation(string $username, string $code, string $fullUri, string $ipAddress) {
        
    }
}