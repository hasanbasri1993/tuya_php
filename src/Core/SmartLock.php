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
        // TODO: add stronger validation / exceptions for invalid responses
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

    /**
     * Creates a temporary password on the smart lock.
     *
     * This is the low-level API that accepts the full Tuya payload.
     */
    public function createTempPassword(string $deviceId, array $payload): array
    {
        $accessToken = $this->client->getAccessToken()->accessToken;

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = "/v1.0/devices/{$deviceId}/door-lock/temp-password";
        $bodyData = json_encode($payload, JSON_THROW_ON_ERROR);
        $sign = $this->generatePostSignature($timestamp, $nonce, $accessToken, $urlPath, $bodyData);

        $headers = [
            'client_id'    => $this->clientId,
            't'            => (string) $timestamp,
            'nonce'        => $nonce,
            'sign'         => $sign,
            'sign_method'  => 'HMAC-SHA256',
            'access_token' => $accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->post($this->apiUrl . $urlPath, $headers, $bodyData);

        return $response['body'] ?? [];
    }

    /**
     * Retrieves a specific temporary password by its ID.
     */
    public function getTempPassword(string $deviceId, string $passwordId): array
    {
        $accessToken = $this->client->getAccessToken()->accessToken;

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = "/v1.0/devices/{$deviceId}/door-lock/temp-password/{$passwordId}";
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath, 'GET', '');

        $headers = [
            'client_id'    => $this->clientId,
            't'            => (string) $timestamp,
            'nonce'        => $nonce,
            'sign'         => $sign,
            'sign_method'  => 'HMAC-SHA256',
            'access_token' => $accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->get($this->apiUrl . $urlPath, $headers);

        return $response['body'] ?? [];
    }

    /**
     * Deletes a temporary password from the smart lock.
     */
    public function deleteTempPassword(string $deviceId, string $passwordId): array
    {
        $accessToken = $this->client->getAccessToken()->accessToken;

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords/{$passwordId}";
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath, 'DELETE', '');

        $headers = [
            'client_id'    => $this->clientId,
            't'            => (string) $timestamp,
            'nonce'        => $nonce,
            'sign'         => $sign,
            'sign_method'  => 'HMAC-SHA256',
            'access_token' => $accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->delete($this->apiUrl . $urlPath, $headers);

        return $response['body'] ?? [];
    }

    /**
     * Lists all temporary passwords configured on the smart lock.
     */
    public function listTempPasswords(string $deviceId): array
    {
        $accessToken = $this->client->getAccessToken()->accessToken;

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = "/v1.0/devices/{$deviceId}/door-lock/temp-passwords";
        $sign = $this->generateSignature($timestamp, $nonce, $accessToken, $urlPath, 'GET', '');

        $headers = [
            'client_id'    => $this->clientId,
            't'            => (string) $timestamp,
            'nonce'        => $nonce,
            'sign'         => $sign,
            'sign_method'  => 'HMAC-SHA256',
            'access_token' => $accessToken,
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->get($this->apiUrl . $urlPath, $headers);

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

    /**
     * High-level helper to create a numeric temporary password.
     *
     * @return array{
     *     plain_password: string,
     *     response: array
     * }
     */
    public function createNumericTempPassword(
        string $deviceId,
        int $effectiveTime,
        int $invalidTime,
        string $timeZone = '+00:00',
        ?string $name = null,
    ): array {
        $ticket = $this->getPasswordTicket($deviceId);
        $result = $ticket['result'] ?? [];

        if (empty($result['ticket_key']) || empty($result['ticket_id'])) {
            return [
                'plain_password' => '',
                'response' => $ticket,
            ];
        }

        $plainPassword = sprintf('%07d', random_int(0, 9_999_999));

        $encryptedPassword = $this->encryptNumericPassword(
            $plainPassword,
            (string) $result['ticket_key'],
        );

        if ($encryptedPassword === null) {
            return [
                'plain_password' => '',
                'response' => [
                    'success' => false,
                    'error' => 'Failed to encrypt password using provided ticket_key',
                    'ticket' => $ticket,
                ],
            ];
        }

        $payload = [
            'name'           => $name ?? ('TMP-' . date('Ymd-His')),
            'password'       => $encryptedPassword,
            'effective_time' => $effectiveTime,
            'invalid_time'   => $invalidTime,
            'password_type'  => 'ticket',
            'ticket_id'      => (string) $result['ticket_id'],
            'type'           => 0,
            'phone'          => '',
            'time_zone'      => $timeZone,
        ];

        $response = $this->createTempPassword($deviceId, $payload);

        return [
            'plain_password' => $plainPassword,
            'response'       => $response,
        ];
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

    private function generateSignature(
        int $timestamp,
        string $nonce,
        string $accessToken,
        string $urlPath,
        string $method,
        string $bodyData,
    ): string {
        $contentSha256 = hash('sha256', $bodyData);
        $stringToSign = strtoupper($method) . "\n" . $contentSha256 . "\n\n" . $urlPath;
        $stringToHash = $this->clientId . $accessToken . $timestamp . $nonce . $stringToSign;

        return strtoupper(hash_hmac('sha256', $stringToHash, $this->clientSecret));
    }

}

