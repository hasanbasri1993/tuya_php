<?php

namespace Tuya;

class SmartLock extends TuyaClient
{
    /**
     * Obtains the password ticket for the smart-lock.
     */
    public function getPasswordTicket($deviceId)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }

        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/devices/{$deviceId}/door-lock/password-ticket";
        $bodyData  = '{}';
        $sign      = $this->generatePostSignature($timestamp, $nonce, $accessToken, $urlPath, $bodyData);

        $headers = [
            "client_id: "    . $this->clientId,
            "t: "            . $timestamp,
            "nonce: "        . $nonce,
            "sign: "         . $sign,
            "sign_method: HMAC-SHA256",
            "access_token: " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyData,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * Encrypts a numeric password using the ticket_key.
     */
    public function encryptNumericPassword($plainPassword, $ticketKey, &$debug = [])
    {
        if (empty($ticketKey)) {
            return null;
        }

        // 1. Decrypt the ticketKey using Client Secret
        // The ticketKey from API is in Hex, so convert to binary first
        $encryptedTicketKey = hex2bin($ticketKey);
        if ($encryptedTicketKey === false) {
             $debug['error'] = 'Invalid hex ticket key';
             return null;
        }

        // Verified: Tuya Smart Lock password ticket uses AES-256-ECB with the raw Client Secret string (32 bytes)
        $key = $this->clientSecret;
        $decryptedKey = openssl_decrypt($encryptedTicketKey, 'AES-256-ECB', $key, OPENSSL_RAW_DATA);
        
        if ($decryptedKey === false) {
             // Debug OpenSSL errors if needed
             // while ($msg = openssl_error_string()) { ... }
             $debug['error'] = 'Failed to decrypt ticket key';
             return null;
        }

        // 2. Encrypt the password using the decrypted key
        $encryptedPassword = openssl_encrypt($plainPassword, 'AES-128-ECB', $decryptedKey, OPENSSL_RAW_DATA);
        
        // 3. Return as Hex string
        return bin2hex($encryptedPassword);
    }

    /**
     * Creates a temporary password on the smart-lock.
     */
    public function createTempPassword($deviceId, $payload)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }

        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/devices/{$deviceId}/door-lock/temp-password";
        $bodyData  = json_encode($payload);
        $sign      = $this->generatePostSignature($timestamp, $nonce, $accessToken, $urlPath, $bodyData);

        $headers = [
            "client_id: "    . $this->clientId,
            "t: "            . $timestamp,
            "nonce: "        . $nonce,
            "sign: "         . $sign,
            "sign_method: HMAC-SHA256",
            "access_token: " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $bodyData,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }    
    
    /**
     * Gets a temporary password from the smart-lock.
     */
    public function getTempPassword($deviceId, $passwordId)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }

        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/devices/{$deviceId}/door-lock/temp-password/{$passwordId}";
        
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath);

        $headers = [
            "client_id: "    . $this->clientId,
            "t: "            . $timestamp,
            "nonce: "        . $nonce,
            "sign: "         . $sign,
            "sign_method: HMAC-SHA256",
            "access_token: " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * Deletes a temporary password from the smart-lock.
     */
    public function deleteTempPassword($deviceId, $passwordId)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }

        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords/{$passwordId}";
        
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath, 'DELETE');

        $headers = [
            "client_id: "    . $this->clientId,
            "t: "            . $timestamp,
            "nonce: "        . $nonce,
            "sign: "         . $sign,
            "sign_method: HMAC-SHA256",
            "access_token: " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => "DELETE",
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * Lists the temporary passwords for the smart-lock.
     */
    public function listTempPasswords($deviceId)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }

        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords";
        
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath);

        $headers = [
            "client_id: "    . $this->clientId,
            "t: "            . $timestamp,
            "nonce: "        . $nonce,
            "sign: "         . $sign,
            "sign_method: HMAC-SHA256",
            "access_token: " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
}
