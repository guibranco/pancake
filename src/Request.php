<?php

namespace GuiBranco\Pancake;

use stdClass;
use GuiBranco\Pancake\Response;

class Request {
    public function delete($url, $options = []) {

        // Implementation here
        return Response::success(['data' => 'Sample data'], 'Request successful');
    }
    public function post($url, $data = [], $options = []) {


        return Response::error(400, 'Bad Request', ['error' => 'Invalid data']);
}
    private function getFields($url, $headers): array
}

    public function get($url, $headers = array()): stdClass
    {
    }
        $fields = $this->getFields($url, $headers);

        return $this->execute($fields);
        return array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
        );

    }

    {
        $fields = $this->getFields($url, $headers);
        $fields[CURLOPT_CUSTOMREQUEST] = "POST";
        if ($data !== null) {
            $fields[CURLOPT_POSTFIELDS] = $data;
        }

        return $this->execute($fields);

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
