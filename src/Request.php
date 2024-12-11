<?php

namespace GuiBranco\Pancake;

use stdClass;
use GuiBranco\Pancake\Response;

class Request {
    public function get($url, $options = []) {

        // Simulate a successful response
        return Response::success(['data' => 'Sample data'], 'Request successful');
    }

    public function post($url, $data = [], $options = [])
    {
        return Response::error(400, 'Bad Request', ['error' => 'Invalid data']);
        // Simulate an error response

    public function delete($url, $options = []) {
        // Simulate an error response
        return Response::error(404, 'Not Found', ['error' => 'Resource not found']);
    }

    public function put($url, $data = [], $options = []) {
        // Simulate a successful response
        return Response::success(['data' => 'Updated data'], 'Update successful');
    }
    private function extractHeader($index, $line): ?array
    {
        if ($index === 0) {
            return array("http_code", $line);

        }

        $explode = explode(": ", $line);

        if (count($explode) != 2) {
            return null;
        }

        list($key, $value) = $explode;
        return array($key, $value);
        return array($key, $value);
        return array($key, $value);
    private function execute($fields): stdClass
    }

    {
        $curl = curl_init();
        curl_setopt_array($curl, $fields);
        $response = curl_exec($curl);
        $result = new stdCLass();
        $result->url = $fields[CURLOPT_URL];

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);

            $result->statusCode = -1;
            $result->error = $error;
            return $result;
        }

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $headers = $this->extractHeaders($header);
        $body = substr($response, $headerSize);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result->statusCode = $httpCode;
        $result->headers = $headers;
        $result->body = $body;
        return $result;
    }

    private function getFields($url, $headers): array
    {
        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers
        );
    }

    public function get($url, $headers = array()): stdClass
    {
        $fields = $this->getFields($url, $headers);
        return $this->execute($fields);
    }

    public function post($url, $headers = array(), $data = null): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "POST";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    public function put($url, $headers = array(), $data = null): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PUT";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    public function delete($url, $headers = array(), $data = null): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "DELETE";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    public function patch($url, $headers = array(), $data = null): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PATCH";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }
        return $this->execute($fields);
    }

    public function options($url, $headers = array()): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "OPTIONS";
        return $this->execute($fields);
    }

    public function head($url, $headers = array()): stdClass
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "HEAD";
        $fields[CURLOPT_NOBODY] = true;
        return $this->execute($fields);
    }
