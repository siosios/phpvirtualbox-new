<?php

require_once 'IpHelper.php';

class GoogleReCaptchaV3
{
    /** @var string */
    private $secretKey;

    /** @var string */
    private $clientResponse;

    public function __construct(string $secretKey, string $clientResponse) {
        $this->secretKey = $secretKey;
        $this->clientResponse = $clientResponse;
    }

    /**
     * @return bool
     */
    public function validate() {
        $source_ip = IpHelper::getRemoteIp();

        $url = "https://www.google.com/recaptcha/api/siteverify?secret=" . $this->secretKey . "&response=" . $this->clientResponse . "&remoteip=$source_ip";
        $response = @file_get_contents($url);
        $data = @json_decode($response, true);
        if (!$data)
            return false;

        if (!$data["success"]) {
            return false;
        }

        return $data["score"] >= 0.9;
    }
}