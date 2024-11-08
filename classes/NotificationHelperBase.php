<?php

class NotificationHelperBase
{
    /**
     * Password Reset Event Handler
     *
     * @param string $username
     * @param string $code
     * @param int $expires
     * @return void
     */
    public static function onPasswordSendConfirmationCode(string $username, string $code, int $expires) {

    }

    /**
     * IP address confirmation Event Handler
     *
     * @param string $username
     * @param string $code
     * @param string $fullUri
     * @param string $ipAddress
     * @return void
     */
    public static function onIpConfirmation(string $username, string $code, string $fullUri, string $ipAddress) {
        
    }

    /**
     * Account creation Event Handler
     *
     * @param string $username
     * @param string $password
     * @return void
     */
    public static function onUserCreation(string $username, string $password) {

    }
}