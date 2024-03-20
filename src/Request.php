<?php

namespace GuiBranco\Pancake;

class Request
{
    private function extractHeaders($header)
    {
        $headers = array();

        foreach (explode("\r\n", $header) as $i => $line) {
            $result = $this->extractHeader($i, $line);

            if ($result === null) {
                continue;
            }

            $headers[$result["key"]] = $result["value"];
        }

        return $headers;
    }

    private function extractHeader($i, $line)
    {
        if ($i === 0) {
            return array("key" => "http_code", "value" => $line);
        }

        $explode = explode(": ", $line);

        if ($count($explode) != 2) {
            return null;
        }

        list($key, $value) = $explode;
        return array("key" => $key, "value" => $value);
    }

    private function execute($fields)
    {
        $curl = curl_init();
        curl_setopt_array($curl, $fields);
        $response = curl_exec($curl);
        $result = new \stdCLass();
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

    private function getFields($url, $headers)
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

    public function get($url, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        return $this->execute($fields);
    }

    public function post($url, $data, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "POST";
        $fields[CURLOPT_POSTFIELDS] = $data;
        return $this->execute($fields);
    }

    public function put($url, $data, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PUT";
        $fields[CURLOPT_POSTFIELDS] = $data;
        return $this->execute($fields);
    }

    public function delete($url, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "DELETE";
        return $this->execute($fields);
    }

    public function patch($url, $data, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "PATCH";
        $fields[CURLOPT_POSTFIELDS] = $data;
        return $this->execute($fields);
    }

    public function options($url, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "OPTIONS";
        return $this->execute($fields);
    }

    public function head($url, $headers = array())
    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "HEAD";
        return $this->execute($fields);
    }
}
