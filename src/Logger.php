<?php

namespace GuiBranco\Pancake;

class Logger implements ILogger
{
    private $headers;

    private $baseUrl;

    private $request;

    public function __construct($loggerUrl, $loggerApiKey, $loggerApiToken, $customUserAgent = null)
    {
        $this->headers = array(
            "User-Agent: " . ($customUserAgent ?? "Pancake/1.0.0"),
            "Content-Type: application/json; charset=UTF-8",
            "X-API-KEY: " . $loggerApiKey,
            "X-API-TOKEN: " . $loggerApiToken,
            "X-Correlation-Id: " . GUIDv4::random()
        );
        $this->baseUrl = $loggerUrl;
        $this->request = new Request();
    }

    public function log($message, $details): void
    {
        $trace = debug_backtrace();
        $caller = $trace[1];

        $caller["message"] = $message;
        $caller["details"] = $details;
        $caller["object"] = isset($caller["object"]) ? print_r($caller["object"], true) : "";
        $caller["args"] = isset($caller["args"]) ? print_r($caller["args"], true) : "";

        $body = json_encode($caller);

        $this->request->post($this->baseUrl . "log-message", $body, $this->headers);
    }
}