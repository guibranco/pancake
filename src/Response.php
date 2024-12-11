<?php

namespace GuiBranco\Pancake;

class Response {
    public static function success($data, $message = 'Success') {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    public static function error($code, $message, $details = []) {
        return [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'details' => $details
        ];
    }
}