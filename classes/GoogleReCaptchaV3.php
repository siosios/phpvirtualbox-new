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
        $settings = new phpVBoxConfigClass();
        $source_ip = $_SERVER["REMOTE_ADDR"];
        if ($settings->check_cloudflare_ips && IpHelper::isCloudFlareAddress($source_ip)) {
            $source_ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"];
        }

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