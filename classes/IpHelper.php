<?php

require_once __DIR__ . '/../endpoints/lib/config.php';

class IpHelper
{
    const CLOUDFLARE_CIDRS = [
        "173.245.48.0/20",
        "103.21.244.0/22",
        "103.22.200.0/22",
        "103.31.4.0/22",
        "141.101.64.0/18",
        "108.162.192.0/18",
        "190.93.240.0/20",
        "188.114.96.0/20",
        "197.234.240.0/22",
        "198.41.128.0/17",
        "162.158.0.0/15",
        "104.16.0.0/13",
        "104.24.0.0/14",
        "172.64.0.0/13",
        "131.0.72.0/22"
    ];

    private static $settings = null;

    public static function checkIpMatch(string $ip, string $cidr) : bool
    {
        $ip_arr = explode(".", $ip);
        $cidr_regex = "/^[0-9]{1,2}$/";
        $ip_exception = new Exception("Given IP address is incorrect");
        $cidr_exception = new Exception("Given CIDR is incorrect");
        if (count($ip_arr) != 4)
        {
            throw $ip_exception;
        }

        foreach ($ip_arr as $octet)
        {
            if (!self::validateOctet($octet))
            {
                throw $ip_exception;
            }
        }

        $cidr_arr = explode(".", $cidr);

        if (count($ip_arr) != 4)
        {
            throw $cidr_exception;
        }

        $octet_num = 1;

        foreach ($cidr_arr as $octet)
        {
            if ($octet_num == 4)
            {
                if (strpos($octet, "/") === false)
                {
                    $cidr .= "/32";
                    if (!self::validateOctet($octet))
                    {
                        throw $cidr_exception;
                    }
                }
                else
                {
                    $octet_prefix = explode("/", $octet);
                    if (count($octet_prefix) != 2)
                    {
                        throw $cidr_exception;
                    }
                    if (!preg_match($cidr_regex, $octet_prefix[1]))
                    {
                        throw $cidr_exception;
                    }
                    $prefix = intval($octet_prefix[1]);
                    if ($prefix > 32)
                    {
                        throw $cidr_exception;
                    }
                    if (!self::validateOctet($octet_prefix[0]))
                    {
                        throw $cidr_exception;
                    }
                }
            }
            else if (!self::validateOctet($octet))
            {
                throw $cidr_exception;
            }
            $octet_num++;
        }

        list($subnet, $mask) = explode("/", $cidr);

        if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet))
        {
            return true;
        }

        return false;
    }

    public static function isLocalIpAddress(string $ip_address) : bool
    {
        if (self::checkIpMatch($ip_address, "192.168.0.0/16"))
        {
            return true;
        }

        if (self::checkIpMatch($ip_address, "172.16.0.0/12"))
        {
            return true;
        }

        if (self::checkIpMatch($ip_address, "10.0.0.0/8"))
        {
            return true;
        }

        if (self::checkIpMatch($ip_address, "127.0.0.0/8"))
        {
            return true;
        }

        return false;
    }

    public static function isCloudFlareAddress(string $ip) : bool
    {
        foreach (self::CLOUDFLARE_CIDRS as $cidr)
        {
            if (self::checkIpMatch($ip, $cidr))
            {
                return true;
            }
        }
        return false;
    }

    public static function getRemoteIp() : string
    {
        if (self::$settings === null) {
            self::$settings = new phpVBoxConfigClass();
        }
        $source_ip = $_SERVER["REMOTE_ADDR"];
        if (self::$settings->check_cloudflare_ips && IpHelper::isCloudFlareAddress($source_ip)) {
            $source_ip = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"];
        }

        return $source_ip;
    }

    private static function validateOctet(string $octet) : bool
    {
        if (!preg_match("/^[0-9]{1,3}$/", $octet))
        {
            return false;
        }
        $octet_int = intval($octet);
        if ($octet_int > 255)
        {
            return false;
        }
        return true;
    }
}