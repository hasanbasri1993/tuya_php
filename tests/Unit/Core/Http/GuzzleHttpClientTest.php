<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Http;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\Http\GuzzleHttpClient;

final class GuzzleHttpClientTest extends TestCase
{
    public function test_implements_http_client_interface(): void
    {
        $guzzle = $this->createMock(ClientInterface::class);
        $client = new GuzzleHttpClient($guzzle);

        self::assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function test_get_normalizes_response_to_array(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, [
            '__toString' => '{"foo":"bar"}',
        ]));

        $guzzle = $this->createMock(ClientInterface::class);
        $guzzle->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.example.com', ['headers' => ['X-Test' => '1']])
            ->willReturn($response);

        $client = new GuzzleHttpClient($guzzle);
        $result = $client->get('https://api.example.com', ['X-Test' => '1']);

        self::assertSame(200, $result['status']);
        self::assertSame(['Content-Type' => ['application/json']], $result['headers']);
        self::assertSame(['foo' => 'bar'], $result['body']);
    }

    public function test_post_sends_body_and_headers(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(201);
        $response->method('getHeaders')->willReturn([]);
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, [
            '__toString' => '{"ok":true}',
        ]));

        $guzzle = $this->createMock(ClientInterface::class);
        $guzzle->expects(self::once())
            ->method('request')
            ->with('POST', 'https://api.example.com', [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => '{"foo":"bar"}',
            ])
            ->willReturn($response);

        $client = new GuzzleHttpClient($guzzle);
        $result = $client->post('https://api.example.com', ['Content-Type' => 'application/json'], '{"foo":"bar"}');

        self::assertSame(201, $result['status']);
        self::assertSame(['ok' => true], $result['body']);
    }

    public function test_delete_uses_correct_method(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(204);
        $response->method('getHeaders')->willReturn([]);
        $response->method('getBody')->willReturn($this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, [
            '__toString' => '',
        ]));

        $guzzle = $this->createMock(ClientInterface::class);
        $guzzle->expects(self::once())
            ->method('request')
            ->with('DELETE', 'https://api.example.com/resource', ['headers' => []])
            ->willReturn($response);

        $client = new GuzzleHttpClient($guzzle);
        $result = $client->delete('https://api.example.com/resource');

        self::assertSame(204, $result['status']);
        self::assertSame([], $result['body']);
    }
}

