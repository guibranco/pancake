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
            "User-Agent: ".($customUserAgent ?? Constants::USER_AGENT_VENDOR),
            Constants::CONTENT_TYPE_JSON_HEADER,
            "X-API-KEY: ".$loggerApiKey,
            "X-API-TOKEN: ".$loggerApiToken,
            "X-Correlation-Id: ".GUIDv4::random()
        );
        $this->baseUrl = $loggerUrl;
        $this->request = new Request();
    }

    public function log($message, $details): bool
    {
        $trace = debug_backtrace();
        $caller = $trace[1] ?? [];

        $caller["message"] = $message;
        $caller["details"] = $details;
        $caller["object"] = isset($caller["object"]) ? print_r($caller["object"], true) : "";
        $caller["args"] = isset($caller["args"]) ? print_r($caller["args"], true) : "";

        $body = json_encode($caller);

        $result = $this->request->post($this->baseUrl . "log-message", $this->headers, $body);

        $statusCode = $result->getStatusCode();

        if ($statusCode !== 202) {
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . $statusCode . ": " . $result->toJson());
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . $message);
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . json_encode($trace[1]));
        }

        return $statusCode === 200;
    }
}
