<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\RequestException;
use JsonException;

class Response
{
    private bool $success;
    private ?string $body;
    private string $message;
    private int $statusCode;
    private string $url;
    private ?array $headers;
    private const HTTP_OK = 200;
    private const HTTP_REDIRECT = 300;
    private const HTTP_BAD_REQUEST = 400;
    private const TEXT_CONTENT_TYPES = [
        'application/json',
        'application/xml',
        'application/javascript',
        'application/x-www-form-urlencoded',
        'application/ld+json',
        'application/graphql',
    ];

    /**
     * Constructor for Response objects.
     *
     * @param bool $success Whether the response was successful.
     * @param string|null $body The body content of the response.
     * @param string $message The message associated with the response.
     * @param int $statusCode The HTTP status code for the response.
     * @param string $url The URL associated with the response.
     * @param array|null $headers An array of headers included in the response.
     */
    public function __construct(bool $success, ?string $body, string $message, int $statusCode, string $url, ?array $headers)
    {
        $this->success = $success;
        $this->body = $body;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->url = $url;
        $this->headers = $headers;
    }

    /**
     * Creates a successful response.
     *
     * @param string $body The body content of the response.
     * @param string $url The URL associated with the response.
     * @param array $headers An array of headers to include in the response.
     * @param int $statusCode The HTTP status code for the response (default is 200).
     * @return self Returns an instance of the response.
     */

    public static function success(string $body, string $url, array $headers, int $statusCode = 200): self
    {
        return new self(true, $body, '', $statusCode, $url, $headers);
    }

    /**
     * Generates an error response.
     *
     * @param string $message The error message to be displayed.
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the error response. Default is 400.
     * @return self Returns an instance of the response with the error details.
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
    * Determines if the response indicates a successful outcome with a valid HTTP Status Code
    *
    * @param bool $includeRedirects Whether to include redirect status codes in the validation.
    *
    * @return bool True if the response is successful, false otherwise.
    */
    public function isSuccessStatusCode(bool $includeRedirects = false): bool
    {
        if ($includeRedirects === true) {
            $maxCode = self::HTTP_BAD_REQUEST;
        } else {
            $maxCode = self::HTTP_REDIRECT;
        }

        return $this->isSuccess() === true
            && $this->statusCode >= self::HTTP_OK
            && $this->statusCode < $maxCode;
    }

    /**
     * Retrieves the body of the response.
     *
     * @return string|null The response body, or null if not set.
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * Get the HTTP request body as a decoded JSON object.
     *
     * @return object|null Returns an object if the body is valid JSON or null otherwise.
     * @throws JsonException If JSON decoding fails.
     */
    public function getBodyAsJson(): ?object
    {
        if ($this->body === null) {
            return null;
        }

        try {
            $decoded = json_decode($this->body, false, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (JsonException $e) {
            throw new JsonException('Invalid JSON string: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the HTTP request body as a decoded JSON array.
     *
     * @return array|null Returns an array if the body is valid JSON or null otherwise.
     * @throws JsonException If JSON decoding fails.
     */
    public function getBodyAsArray(): ?array
    {
        if ($this->body === null) {
            return null;
        }

        try {
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (JsonException $e) {
            throw new JsonException('Invalid JSON string: ' . $e->getMessage(), 0, $e);
        }
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
     * @return array|null An array of headers if available or null if no headers are set.
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * Get a single header value by name (case-insensitive).
     *
     * @param string $name The header name (e.g. "Content-Type").
     * @return string|null The header value, or null if the header is not present.
     */
    public function getHeader(string $name): ?string
    {
        if ($this->headers === null) {
            return null;
        }

        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get the media type of the response, without parameters (e.g. "image/png").
     *
     * @return string|null The lowercased media type, or null if no Content-Type header is present.
     */
    public function getContentType(): ?string
    {
        $contentType = $this->getHeader('Content-Type');
        if ($contentType === null) {
            return null;
        }

        $mediaType = strtolower(trim(explode(';', $contentType)[0]));

        return $mediaType === '' ? null : $mediaType;
    }

    /**
     * Determines whether the response body holds binary (non-text) content.
     *
     * The Content-Type header is used when available; otherwise the body is
     * considered binary when it is not valid UTF-8.
     *
     * @return bool True if the body is binary, false otherwise.
     */
    public function isBinary(): bool
    {
        if ($this->body === null || $this->body === '') {
            return false;
        }

        $contentType = $this->getContentType();
        if ($contentType === null) {
            return !mb_check_encoding($this->body, 'UTF-8');
        }

        $isText = str_starts_with($contentType, 'text/')
            || in_array($contentType, self::TEXT_CONTENT_TYPES, true)
            || str_ends_with($contentType, '+json')
            || str_ends_with($contentType, '+xml');

        return !$isText;
    }

    /**
     * Writes the raw response body to a file, preserving binary content.
     *
     * @param string $path The destination file path.
     * @return int The number of bytes written.
     * @throws RequestException If the response has no body or the file cannot be written.
     */
    public function saveBodyToFile(string $path): int
    {
        if ($this->body === null) {
            throw new RequestException("Response has no body to save");
        }

        $bytes = @file_put_contents($path, $this->body);
        if ($bytes === false) {
            throw new RequestException("Unable to write response body to file: {$path}");
        }

        return $bytes;
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
        if ($this->isSuccessStatusCode($includeRedirects) === false) {
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
     * Binary bodies are base64-encoded and flagged with "bodyEncoding": "base64".
     *
     * @return string The JSON representation of the response.
     */
    public function toJson(): string
    {
        $data = $this->toArray();
        if ($this->isBinary()) {
            $data['body'] = base64_encode($this->body);
            $data['bodyEncoding'] = 'base64';
        }

        return json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
