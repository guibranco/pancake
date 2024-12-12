<?php

namespace GuiBranco\Pancake;

class Response
{
    private bool $success;
    private $data;
    private string $message;
    private int $statusCode;

    private function __construct(bool $success, $data, string $message, int $statusCode)
    {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    public static function success($data, string $message = '', int $statusCode = 200): self
    {
        return new self(true, $data, $message, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, $data = null): self
    {
        return new self(false, $data, $message, $statusCode);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'statusCode' => $this->statusCode,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
