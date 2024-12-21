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

    /**
     * Response constructor.
     *
     * @param bool $success Indicates whether the response was successful.
     * @param string|null $body The body of the response, or null if there is no body.
     * @param string $message A message associated with the response.
     * @param int $statusCode The HTTP status code of the response.
     * @param string $url The URL associated with the response.
     * @param array|null $headers An array of headers associated with the response, or null if there are no headers.
     *
     * @return self Returns an instance of the response class.
     */
    public static function error(string $message, string $url, int $statusCode = 400): self
    {
        return new self(false, null, $message, $statusCode, $url, null);
    }

    /**
     * Determines if the response indicates a successful outcome.
     *
     * @return bool True if the response is successful, false otherwise.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Retrieves the body of the response.
     *
     * @return string|null The body of the response, or null if not set.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Retrieves the message.
     *
     * @return string The message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the HTTP status code of the response.
     *
     * @return int The HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retrieves the URL.
     *
     * @return string The URL as a string.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the headers of the response.
     *
     * @return array|null An array of headers if available, or null if no headers are set.
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * Ensures that the response has a successful status code.
     *
     * This method will check the status code of the response and
     * throw an exception if the status code indicates a failure.
     *
     * @throws \Exception If the status code indicates a failure.
     */
    public function ensureSuccessStatus(): void
    {
        if (!$this->success) {
            throw new RequestException("Response indicates failure: " . $this->message, $this->statusCode);
        }

        $this->validateStatusCode(false);
    }

    /**
     * Validates the status code of the response.
     *
     * @param bool $includeRedirects Whether to include redirect status codes in the validation.
     *
     * @return void
     */
    public function validateStatusCode(bool $includeRedirects = false): void
    {
        if ($includeRedirects) {
            if ($this->statusCode < 200 || $this->statusCode >= 400) {
                throw new RequestException("Invalid status code", $this->statusCode);
            }

            return;
        }

        if ($this->statusCode < 200 || $this->statusCode >= 300) {
            throw new RequestException("Invalid status code", $this->statusCode);

        }
    }
    /**
     * Converts the response to an array.
     *
     * @return array The response data as an array.
     */
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

    /**
     * Converts the response to a JSON string.
     *
     * @return string The JSON representation of the response.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
