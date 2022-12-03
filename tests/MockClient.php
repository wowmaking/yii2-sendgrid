<?php

namespace MarketforceInfo\SendGrid\Tests;

use SendGrid\Client;
use SendGrid\Response;

class MockClient extends Client
{
    private array $requestHistory = [];
    private array $responses = [];

    public function __construct($host = null, $headers = null, $version = null, $path = null, $curlOptions = null, $retryOnLimit = false, $verifySSLCerts = true)
    {
        if (!$host) {
            $host = 'https://sendgrid-mock.example';
        }
        parent::__construct($host, $headers, $version, $path, $curlOptions, $retryOnLimit, $verifySSLCerts);
    }

    public function addSuccessfulResponse(): self
    {
        return $this->addResponse(202, '');
    }

    public function addResponse(int $status = 200, string $body = 'OK', array $headers = []): self
    {
        $this->responses[] = [$status, $body, $headers];
        return $this;
    }

    public function __call($method, $arguments)
    {
        if (empty($this->responses)) {
            throw new \RuntimeException('Missing required response for mock client request.');
        }

        if (in_array(strtolower($method), ['get', 'post', 'patch', 'put', 'delete'], true)) {
            $body = $arguments[0] ?? null;
            $queryParams = $arguments[1] ?? null;
            $url = $this->buildUrl($queryParams);
            $headers = $arguments[2] ?? null;
            $retryOnLimit = $arguments[3] ?? $this->retryOnLimit;
            $this->requestHistory[] = $this->asRequest($method, $url, $body, $headers, $retryOnLimit);

            [$status, $body, $headers] = array_pop($this->responses);
            return new Response($status, $body, $headers);
        }

        $this->path[] = $method;
        return $this;
    }

    public function getRequestHistory(): array
    {
        return $this->requestHistory;
    }

    /**
     * @see Client::buildUrl
     */
    private function buildUrl($queryParams = null): string
    {
        $path = '/' . implode('/', $this->path);
        if (isset($queryParams)) {
            // Regex replaces `[0]=`, `[1]=`, etc. with `=`.
            $path .= '?' . preg_replace('/%5B(?:\d|[1-9]\d+)%5D=/', '=', http_build_query($queryParams));
        }

        return sprintf('%s%s%s', $this->host, $this->version ?: '', $path);
    }

    /**
     * @see Client::makeRequest
     * @see Client::createCurlOptions
     */
    private function asRequest(string $method, string $url, $body = null, $headers = null, bool $retryOnLimit = null): array
    {
        if (isset($body)) {
            $body = json_encode($body, JSON_THROW_ON_ERROR);
            $headers = array_merge($headers ?? [], ['Content-Type: application/json']);
        }

        return [
            'method' => $method,
            'url' => $url,
            'body' => $body,
            'headers' => array_merge($this->headers, $headers ?? []),
            'retryOnLimit' => $retryOnLimit,
        ];
    }
}
