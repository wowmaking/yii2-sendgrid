<?php

namespace MarketforceInfo\Sendgrid\Tests;

use PHPUnit\Framework\MockObject\Stub;
use SendGrid\Client;
use SendGrid\Response;

class ResponseHandler
{
    private int $status = 202;

    private string $body = '';

    private array $headers = [];

    /**
     * @var Client|Stub
     */
    private Stub $client;

    public function __construct(Stub $client)
    {
        $this->client = $client;
        $client->method('__call')
            ->willReturnCallback([$this, 'handleMethodCall']);
    }

    public function respondWith(int $status = 200, string $body = 'OK', array $headers = []): self
    {
        $this->status = $status;
        $this->body = $body;
        $this->headers = $headers;
        return $this;
    }

    public function handleMethodCall(...$arguments)
    {
        $methodName = array_shift($arguments);
        if (in_array(strtoupper((string)$methodName), ['GET', 'POST', 'PUT', 'DELETE'], true)) {
            return new Response($this->status, $this->body, $this->headers);
        }
        return $this->client;
    }
}
