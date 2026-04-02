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
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? [];

        $safeCaller = [
            "file" => $caller["file"] ?? null,
            "line" => $caller["line"] ?? null,
            "function" => $caller["function"] ?? null,
            "class" => $caller["class"] ?? null,
            "message" => $message,
            "details" => $details
        ];

        $body = json_encode($safeCaller);

        if ($body === false) {
            $body = json_encode([
                "message" => $message,
                "details" => json_last_error_msg()
            ]);
        }

        $result = $this->request->post($this->baseUrl . "log-message", $this->headers, $body);

        $statusCode = $result->getStatusCode();

        if ($statusCode !== 202) {
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . $statusCode . ": " . $result->toJson());
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . $message);
            error_log("[" . date("Y-m-d H:i:s.u e") . "] Pancake::Logger: " . json_encode($trace[1]));
        }

        return $statusCode === 202;
    }
}
