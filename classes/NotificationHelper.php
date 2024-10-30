<?php

class NotificationHelper
{
    public static function onPasswordSendConfirmationCode(string $username, string $code, int $expires) {
        file_put_contents("/home/semyon/phpvirtualbox/phpvirtualbox.log.txt", "'$username' '$code' '$expires'\n", FILE_APPEND);
    }
}