<?php

namespace GuiBranco\Pancake;

use GuiBranco\Pancake\Response;
use GuiBranco\Pancake\RequestException;
use CurlHandle;

class Request
{
    private array $multiRequests = [];

    private bool $verifySSL = true;

    private const MAX_CONCURRENT_REQUESTS = 10;

    private const BATCH_TIMEOUT = 30;

    /**
     * Extract headers from the response.
     *
     * @param string $header Raw header string.
     * @return array Parsed headers as an associative array.
     */
    private function extractHeaders(string $header): array
    {
        $headers = [];
        foreach (explode("\r\n", $header) as $i => $line) {
            $result = $this->extractHeader($i, $line);
            if ($result === null) {
                continue;
            }
            list($key, $value) = $result;
            $headers[$key] = $value;
        }
        return $headers;
    }

    /**
     * Parse a single header line.
     *
     * @param int $index Line index in the header block.
     * @param string $line Header line.
     * @return array|null Key-value pair or null for invalid headers.
     */
    private function extractHeader(int $index, string $line): ?array
    {
        if ($index === 0) {
            return ["http_code", $line];
        }

        $explode = explode(": ", $line);

        if (count($explode) != 2) {
            return null;
        }

        return [$explode[0], $explode[1]];
    }

    /**
     * Prepare CURL options for a request.
     *
     * @param string $url Request URL.
     * @param array $headers Headers for the request.
     * @return array CURL options.
     */
    private function getFields(string $url, array $headers): array
    {
        return [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => $this->verifySSL,
            CURLOPT_SSL_VERIFYHOST => $this->verifySSL ? 2 : 0,
            CURLOPT_HTTPHEADER => $headers,
        ];
    }

    /**
     * Handles the response from a cURL request.
     *
     * @param bool|string $response The response from the cURL request.
     * @param CurlHandle $curl The cURL handle.
     * @param string $url The URL that was requested.
     * @return Response The processed response.
     */
    private function handleResponse(bool|string $response, CurlHandle $curl, string $url): Response
    {
        if ($response === false) {
            $error = curl_error($curl);
            return Response::error($error, $url, -1);
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $headers = $this->extractHeaders($header);
        $body = substr($response, $headerSize);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        return Response::success($body, $url, $headers, $httpCode);

    }

    /**
     * Executes a request with the given fields and returns a Response object.
     *
     * @param array $fields The fields to be included in the request.
     * @return Response The response from the request execution.
     */
    private function execute($fields): Response
    {
        $curl = curl_init();
        curl_setopt_array($curl, $fields);
        $responseContent = curl_exec($curl);

        $response = $this->handleResponse($responseContent, $curl, $fields[CURLOPT_URL]);
        curl_close($curl);

        return $response;
    }

    /**
     * Set the SSL verification status.
     *
     * @param bool $verify Whether to enable or disable SSL verification.
     *
     * @return void
     */
    public function setSSLVerification(bool $verify): void
    {
        $this->verifySSL = $verify;
    }

    /**
     * Add a request to the batch.
     *
     * @param string $key A unique identifier for the request.
     * @param string $url The request URL.
     * @param array $headers Optional headers for the request.
     */
    public function addRequest(string $key, string $url, array $headers = [], string $method = 'GET', $payload = null): void
    {
        $requestCount = count($this->multiRequests);
        if ($requestCount >= self::MAX_CONCURRENT_REQUESTS) {
            throw new RequestException("Maximum number of concurrent requests reached.");
        }
        $fields = $this->getFields($url, $headers);
        if ($method !== 'GET') {
            $fields[CURLOPT_CUSTOMREQUEST] = $method;
        }
        if ($payload !== null) {
            $fields[CURLOPT_POSTFIELDS] = $payload;
        }

        $this->multiRequests[$key] = $fields;
    }

    /**
     * Execute all requests in the batch.
     *
     * @return array An array of Response objects, keyed by their unique identifiers.
     */
    public function executeBatch(): array
    {
        $multiCurl = curl_multi_init();
        $curlHandles = [];
        $responses = [];
        $active = null;

        foreach ($this->multiRequests as $key => $fields) {
            $curl = curl_init();
            curl_setopt_array($curl, $fields);
            curl_setopt($curl, CURLOPT_TIMEOUT, self::BATCH_TIMEOUT);
            curl_multi_add_handle($multiCurl, $curl);
            $curlHandles[$key] = $curl;
        }

        do {
            $status = curl_multi_exec($multiCurl, $active);
            if ($status > 0) {
                usleep(100);
            }
            $select = curl_multi_select($multiCurl, 1.0);
            if ($select === -1) {
                usleep(100);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($curlHandles as $key => $curl) {
            $responseContent = curl_multi_getcontent($curl);
            $responses[$key] = $this->handleResponse($responseContent, $curl, $this->multiRequests[$key][CURLOPT_URL]);
            curl_multi_remove_handle($multiCurl, $curl);
            curl_close($curl);
        }

        curl_multi_close($multiCurl);

        return $responses;
    }

    /**
     * Sends a GET request to the specified URL with optional headers.
     *
     * @param string $url The URL to send the GET request to.
     * @param array $headers Optional. An array of headers to include in the request. Default is an empty array.
     * @return Response The response object containing the result of the GET request.
     */
    public function get($url, $headers = array()): Response
    {
        $fields = $this->getFields($url, $headers);
        return $this->execute($fields);
    }

    /**
     * Sends a POST request to the specified URL.
     *
     * @param string $url The URL to send the POST request to.
     * @param array $headers An optional array of headers to include in the request.
     * @param mixed $data Optional data to include in the body of the POST request.
     * @return Response The response from the server.
     */
    public function post($url, $headers = array(), $data = null): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "POST";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    /**
     * Sends a PUT request to the specified URL.
     *
     * @param string $url The URL to send the PUT request to.
     * @param array $headers Optional. An array of headers to include in the request.
     * @param mixed $data Optional. The data to send with the PUT request.
     * @return Response The response from the server.
     */
    public function put($url, $headers = array(), $data = null): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PUT";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    /**
     * Sends a DELETE request to the specified URL.
     *
     * @param string $url The URL to send the DELETE request to.
     * @param array $headers Optional. An array of headers to include in the request.
     * @param mixed $data Optional. The data to send with the request.
     * @return Response The response from the DELETE request.
     */
    public function delete($url, $headers = array(), $data = null): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "DELETE";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    /**
     * Sends a PATCH request to the specified URL.
     *
     * @param string $url The URL to send the PATCH request to.
     * @param array $headers Optional. An array of headers to include in the request.
     * @param mixed $data Optional. The data to send with the request.
     * @return Response The response from the server.
     */
    public function patch($url, $headers = array(), $data = null): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PATCH";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    /**
     * Sends an HTTP OPTIONS request to the specified URL.
     *
     * @param string $url The URL to send the request to.
     * @param array $headers Optional. An array of headers to include in the request.
     * @return Response The response object containing the result of the request.
     */
    public function options($url, $headers = array()): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "OPTIONS";
        return $this->execute($fields);
    }

    /**
     * Sends a HEAD request to the specified URL with optional headers.
     *
     * @param string $url The URL to send the request to.
     * @param array $headers Optional. An array of headers to include in the request.
     * @return Response The response object containing the response data.
     */
    public function head($url, $headers = array()): Response
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "HEAD";
        $fields[CURLOPT_NOBODY] = true;
        return $this->execute($fields);
    }
}
