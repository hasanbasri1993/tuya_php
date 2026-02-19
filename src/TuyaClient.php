<?php

namespace Tuya;

class TuyaClient
{
    protected $clientId;
    protected $clientSecret;
    protected $apiUrl;
    protected $tokenCacheFile;
    
    public function __construct($clientId, $clientSecret, $apiUrl, $tokenCacheFile)
    {
        $this->clientId       = $clientId;
        $this->clientSecret   = $clientSecret;
        $this->apiUrl         = $apiUrl;
        $this->tokenCacheFile = $tokenCacheFile;
    }
    
    protected function generateUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
    
    protected function generateSignature($timestamp, $nonce, $accessToken = '', $urlPath = '', $method = 'GET', $bodyData = '')
    {
        $content_sha256 = hash('sha256', $bodyData);
        $stringToSign   = strtoupper($method) . "\n" . $content_sha256 . "\n\n" . $urlPath;
        $stringToHash   = $this->clientId . $accessToken . $timestamp . $nonce . $stringToSign;
        return strtoupper(hash_hmac('sha256', $stringToHash, $this->clientSecret));
    }
    
    protected function generatePostSignature($timestamp, $nonce, $accessToken, $urlPath, $bodyData)
    {
        return $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath, 'POST', $bodyData);
    }
    
    public function getAccessToken()
    {
        if (file_exists($this->tokenCacheFile)) {
            $tokenData = json_decode(file_get_contents($this->tokenCacheFile), true);
            if (time() < $tokenData['expires']) {
                return [$tokenData['access_token'], $tokenData['uid']];
            }
        }
        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v1.0/token?grant_type=1";
        $sign      = $this->generateSignature($timestamp, $nonce, '', $urlPath);
        
        $headers = [
            "client_id: "      . $this->clientId,
            "t: "              . $timestamp,
            "nonce: "          . $nonce,
            "sign: "           . $sign,
            "sign_method: HMAC-SHA256",
            "Content-Type: application/json"
        ];
        
        $ch = curl_init($this->apiUrl . $urlPath);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1
        ]);
        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);
        $response = json_decode($raw, true);
        
        if ($response && !empty($response['result']['access_token'])) {
            $response['result']['expires'] = time() + $response['result']['expire_time'] - 60;
            file_put_contents($this->tokenCacheFile, json_encode($response['result']));
            return [$response['result']['access_token'], $response['result']['uid']];
        }
        return [null, null];
    }
    
    public function getDeviceInfo($deviceId)
    {
        list($accessToken) = $this->getAccessToken();
        if (!$accessToken) {
            return ["error" => "Invalid token"];
        }
        $timestamp = round(microtime(true) * 1000);
        $nonce     = $this->generateUUID();
        $urlPath   = "/v2.0/cloud/thing/{$deviceId}";
        $sign      = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath);
        
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
            CURLOPT_VERBOSE        => false,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    /**
     * Authenticate and return the access token response.
     */
    public function authenticate()
    {
        // Clear cache to force a fresh token
        if (file_exists($this->tokenCacheFile)) {
            unlink($this->tokenCacheFile);
        }

        list($accessToken, $uid) = $this->getAccessToken();
        if ($accessToken) {
            return [
                'success' => true,
                'access_token' => $accessToken,
                'uid' => $uid
            ];
        }
        return ['success' => false, 'error' => 'Failed to authenticate with Tuya API'];
    }
}
