<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\RequestException;

class Response
{
    private bool $success;
    private ?string $body;
    private string $message;
    private int $statusCode;
    private string $url;
    private ?array $headers;

    private function __construct(bool $success, ?string $body, string $message, int $statusCode, string $url, ?array $headers)
    {
        $this->success = $success;
        $this->body = $body;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->url = $url;
        $this->headers = $headers;
    }

    public static function success(string $body, string $url, array $headers, int $statusCode = 200): self
    {
        return new self(true, $body, '', $statusCode, $url, $headers);
    }

    public static function error(string $message, string $url, int $statusCode = 400): self
    {
        return new self(false, null, $message, $statusCode, $url, null);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function ensureSuccessStatus(): void
    {
        if (!$this->success) {
            throw new RequestException("Response indicates failure: " . $this->message, $this->statusCode);
        }

        $this->validateStatusCode(false);
    }

    public function validateStatusCode(bool $includeRedirects = false): void
    {
        if ($includeRedirects) {
            if ($this->statusCode < 200 || $this->statusCode >= 400) {
                throw new RequestException("Invalid status code", $this->statusCode);
            }
        } else {
            if ($this->statusCode < 200 || $this->statusCode >= 300) {
                throw new RequestException("Invalid status code", $this->statusCode);
            }
        }
    }
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'statusCode' => $this->statusCode,
            'body' => $this->body,
            'message' => $this->message,
            'url' => $this->url,
            'headers' => $this->headers,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
