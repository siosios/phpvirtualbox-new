<?php

require_once 'IpHelper.php';
require_once __DIR__ . '/../endpoints/lib/config.php';
require_once 'NotificationHelper.php';

final class IpProtection
{
    /** @var int IP address is yet to be confirmed */
    const STATE_UNCONFIRMED = 10;

    /** @var int IP address is confirmed */
    const STATE_CONFIRMED = 20;

    /** @var int IP address approved successfully */
    const RESULT_APPROVED = 100;

    /** @var int Link is invalid or expired */
    const RESULT_INVALID_LINK = 200;

    /** @var int  */
    const RESULT_IP_MISMATCH = 300;

    /** @var phpVBoxConfigClass  */
    private $settings;

    /** @var SQLite3 */
    private $db = null;

    /** @var IpProtection */
    private static $instance = null;

    public static function getInstance(): IpProtection
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->settings = new phpVBoxConfigClass();
        if ($this->settings->pathToIpProtectionDatabase) {
            $create = !file_exists($this->settings->pathToIpProtectionDatabase);
            $this->db = new SQLite3($this->settings->pathToIpProtectionDatabase, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
            if ($create) {
                $this->db->exec("CREATE TABLE addresses (
    username VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    lastupdate INTEGER NOT NULL,
    lastnotification INTEGER NOT NULL,
    code VARCHAR(255) NOT NULL,
    adstate INTEGER NOT NULL
);");
            }
            $oneMonthAgo = time() - 86400 * 30;
            $fourHoursAgo = time() - 3600 * 4;
            $this->db->exec("DELETE FROM addresses WHERE
                (lastupdate < $oneMonthAgo AND adstate=" . self::STATE_CONFIRMED . ")
                OR (lastupdate < $fourHoursAgo AND adstate=" . self::STATE_UNCONFIRMED . ")");
        }
    }

    public function canLogin(string $username): bool
    {
        if ($this->db === null) {
            return true;
        }
        $dbUsername = SQLite3::escapeString($username);
        $time = time();
        $sourceIp = IpHelper::getRemoteIp();
        $ip = SQLite3::escapeString($sourceIp);
        $row = $this->db->query("SELECT * FROM addresses WHERE username='$dbUsername' AND address='$ip'")->fetchArray();

        if ($row && $row['adstate'] == self::STATE_CONFIRMED) {
            $this->db->exec("UPDATE addresses SET lastupdate=$time WHERE username='$dbUsername' AND address='$ip'");
            return true;
        }

        if (!$row) {
            $code = md5(rand(0, 100000) . " " . microtime(true) . " " . rand(0, 100000)) . md5(rand(0, 100000) . " " . microtime(true) . " " . rand(0, 100000));
            $this->db->exec("INSERT INTO addresses (username, address, lastupdate, lastnotification, code, adstate) VALUES ('$dbUsername', '$ip', $time, $time, '$code', " . self::STATE_UNCONFIRMED . ");");

            $finalCode = base64_encode(json_encode([
                'u' => $username,
                'c' => $code
            ]));
            NotificationHelper::onIpConfirmation($username, $finalCode, "/approve.php?c=" . urlencode($finalCode), $sourceIp);
        } else {
            $code = $row['code'];
            $finalCode = base64_encode(json_encode([
                'u' => $username,
                'c' => $code
            ]));
            if ($row['lastnotification'] < $time - 600) {
                $this->db->exec("UPDATE addresses SET lastnotification=$time WHERE username='$dbUsername' AND address='$ip'");
                NotificationHelper::onIpConfirmation($username, $finalCode, "/approve.php?c=" . urlencode($finalCode), $sourceIp);
            }
        }
        return false;
    }

    public function approve(string $encoded): int
    {
        if ($this->db === null) {
            return self::RESULT_APPROVED;
        }
        $json = base64_decode($encoded);
        $arr = @json_decode($json, true);

        if (!isset($arr['u']) || !isset($arr['c'])) {
            return self::RESULT_INVALID_LINK;
        }

        $username = $arr['u'];
        $code = $arr['c'];

        $dbUsername = SQLite3::escapeString($username);
        $dbCode = SQLite3::escapeString($code);

        $row = $this->db->query("SELECT * FROM addresses WHERE username='$dbUsername' AND code='$dbCode'")->fetchArray();
        if (!$row) {
            return self::RESULT_INVALID_LINK;
        }
        $ip = IpHelper::getRemoteIp();
        if ($row['address'] != $ip) {
            return self::RESULT_IP_MISMATCH;
        }
        $time = time();
        $ip = SQLite3::escapeString($ip);
        $this->db->exec("UPDATE addresses SET
            code='',
            adstate=" . self::STATE_CONFIRMED . ",
            lastupdate=$time
        WHERE username='$dbUsername' AND address='$ip'
        ");
        return self::RESULT_APPROVED;
    }
}