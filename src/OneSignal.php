<?php

namespace GuiBranco\Pancake;

class OneSignal
{
    private const BASE_URL = "https://onesignal.com/api/v1/";

    private const NOTIFICATIONS_ENDPOINT = "notifications";

    private $request;

    private $logger;

    private $headers;

    private $endpoint;

    public function __construct($token, $logger = null, $customUserAgent = null, $customEndpoint = null)
    {
        $this->request = new Request();
        $this->logger = $logger;
        $this->headers = array(
            "User-Agent: " . ($customUserAgent ?? "Pancake/0.11 (+https://github.com/guibranco/pancake)"),
            "Content-Type: application/json; charset=utf-8",
            "Authorization: Basic " . $token
        );
        $this->endpoint = $customEndpoint ?? self::BASE_URL;
    }

    public function sendNotification($fields)
    {
        return $this->sendInternal(json_encode($fields), $this->headers);
    }

    private function sendInternal($content, $headers, $isRetry = false)
    {
        $result = $this->request->post($this->endpoint . self::NOTIFICATIONS_ENDPOINT, $headers, $content);

        if ($result->statusCode == 200) {
            return true;
        }

        if (!$isRetry) {
            return $this->sendInternal($content, $headers, true);
        }

        if ($this->logger != null) {
            $this->logger->log("Error sending OneSignal", json_encode($result));
        }
        return false;
    }
}
