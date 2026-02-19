<?php

declare(strict_types=1);

namespace Tuya\Core;

use Tuya\Core\Contracts\HttpClientInterface;

final class SmartLock
{
    public function __construct(
        private readonly TuyaClient $client,
        private readonly HttpClientInterface $httpClient,
        private readonly string $clientSecret,
        private readonly string $apiUrl,
        private readonly string $clientId,
    ) {
    }

    public function getPasswordTicket(string $deviceId): array
    {
        $accessToken = $this->client->getAccessToken()->accessToken;

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = "/v1.0/devices/{$deviceId}/door-lock/password-ticket";
        $bodyData = '{}';
        $sign = $this->generatePostSignature($timestamp, $nonce, $accessToken, $urlPath, $bodyData);

        $headers = [
            'client_id'   => $this->clientId,
            't'           => (string) $timestamp,
            'nonce'       => $nonce,
            'sign'        => $sign,
            'sign_method' => 'HMAC-SHA256',
            'access_token'=> $accessToken,
            'Content-Type'=> 'application/json',
        ];

        $response = $this->httpClient->post($this->apiUrl . $urlPath, $headers, $bodyData);

        return $response['body'] ?? [];
    }

    public function encryptNumericPassword(string $plainPassword, string $ticketKey, array &$debug = []): ?string
    {
        if ($ticketKey === '') {
            return null;
        }

        $encryptedTicketKey = hex2bin($ticketKey);
        if ($encryptedTicketKey === false) {
            $debug['error'] = 'Invalid hex ticket key';
            return null;
        }

        $decryptedKey = openssl_decrypt($encryptedTicketKey, 'AES-256-ECB', $this->clientSecret, OPENSSL_RAW_DATA);
        if ($decryptedKey === false) {
            $debug['error'] = 'Failed to decrypt ticket key';
            return null;
        }

        $encryptedPassword = openssl_encrypt($plainPassword, 'AES-128-ECB', $decryptedKey, OPENSSL_RAW_DATA);
        if ($encryptedPassword === false) {
            $debug['error'] = 'Failed to encrypt password';
            return null;
        }

        return bin2hex($encryptedPassword);
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }

    private function generatePostSignature(
        int $timestamp,
        string $nonce,
        string $accessToken,
        string $urlPath,
        string $bodyData,
    ): string {
        $contentSha256 = hash('sha256', $bodyData);
        $stringToSign = 'POST' . "\n" . $contentSha256 . "\n\n" . $urlPath;
        $stringToHash = $this->clientId . $accessToken . $timestamp . $nonce . $stringToSign;

        return strtoupper(hash_hmac('sha256', $stringToHash, $this->clientSecret));
    }

}

