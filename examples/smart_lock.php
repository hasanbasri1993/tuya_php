<?php

require 'vendor/autoload.php';

use Tuya\TuyaClient;
use Tuya\SmartLock;

// Settings
$clientId       = '';
$clientSecret   = '';
$apiUrl         = 'https://openapi.tuyacn.com';
$tokenCacheFile = __DIR__ . '/token_cache.json';
$deviceId       = '';


// For generic functionalities:
$tuyaClient = new TuyaClient($clientId, $clientSecret, $apiUrl, $tokenCacheFile);
$deviceInfo = $tuyaClient->getDeviceInfo($deviceId);
echo "Device Info: " . json_encode($deviceInfo, JSON_PRETTY_PRINT) . PHP_EOL;

// For smart-lock functionalities:
$smartLock = new SmartLock($clientId, $clientSecret, $apiUrl, $tokenCacheFile);

// 1. List existing passwords
echo "--- INITIAL LISTING ---" . PHP_EOL;
$list = $smartLock->listTempPasswords($deviceId);
// echo "Password List: " . json_encode($list, JSON_PRETTY_PRINT) . PHP_EOL;

// 2. Get smart-lock ticket and create a new one
$ticketResponse = $smartLock->getPasswordTicket($deviceId);
echo "--- TICKET ---" . PHP_EOL;
echo "Ticket Response: " . json_encode($ticketResponse, JSON_PRETTY_PRINT) . PHP_EOL;

if (!empty($ticketResponse['result']['ticket_key'])) {
    $plainPassword = sprintf("%07d", rand(0, 9999999));
    $encryptedPassword = $smartLock->encryptNumericPassword($plainPassword, $ticketResponse['result']['ticket_key']);
    echo "Encrypted password: " . $encryptedPassword . PHP_EOL;

    $payload = [
        "name"           => "TP" . sprintf("%08d", rand(0, 99999999)),
        "password"       => $encryptedPassword,
        "effective_time" => time() - 300, // 1 day ago
        "invalid_time"   => time() + 600,
        "password_type"  => "ticket",
        "ticket_id"      => $ticketResponse['result']['ticket_id'],
        "type"           => 0,
        "phone"          => "",
        "time_zone"      => "+07:00"
    ];
    
    echo "--- CREATION ---" . PHP_EOL;
    $tempPasswordResponse = $smartLock->createTempPassword($deviceId, $payload);
    echo "Temp Password Response: " . json_encode($tempPasswordResponse, JSON_PRETTY_PRINT) . PHP_EOL;

    if ($tempPasswordResponse['success'] && isset($tempPasswordResponse['result']['id'])) {
        $passwordId = $tempPasswordResponse['result']['id'];
        
        echo "--- DETAILS ---" . PHP_EOL;
        $details = $smartLock->getTempPassword($deviceId, $passwordId);
        echo "Password Details: " . json_encode($details, JSON_PRETTY_PRINT) . PHP_EOL;
        echo "Plain Password: " . $plainPassword . PHP_EOL;
        // echo "--- DELETION ---" . PHP_EOL;
        // $deleteResult = $smartLock->deleteTempPassword($deviceId, $passwordId);
        // echo "Delete Result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . PHP_EOL;
        
    } else if (isset($tempPasswordResponse['code']) && $tempPasswordResponse['code'] == 2303) {
        echo "--- CLEANUP (Limit Reached) ---" . PHP_EOL;
        if (!empty($list['result'])) {
            $lastPasswordId = end($list['result'])['id'];
            echo "Deleting old password (ID: $lastPasswordId) to free up space..." . PHP_EOL;
            $deleteResult = $smartLock->deleteTempPassword($deviceId, $lastPasswordId);
            echo "Delete Result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . PHP_EOL;
            echo "Try running the script again." . PHP_EOL;
        } else {
            echo "No passwords found to delete." . PHP_EOL;
        }
    }
} else {
    echo "Skipping password creation because ticket retrieval failed." . PHP_EOL;
}
