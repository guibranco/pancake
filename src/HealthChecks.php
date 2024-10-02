<?php

namespace GuiBranco\Pancake;

use stdClass;

class HealthChecks
{
    public const BASE_URL = "https://hc-ping.com/";

    private const START_ENDPOINT = "/start";

    private const FAIL_ENDPOINT = "/fail";

    private const LOG_ENDPOINT = "/log";

    private $token;

    private $request;

    private $failed = false;

    private $headers;

    private $headersSet = false;

    private $rid;

    private $endpoint;

    private $startUrl;

    private $failUrl;

    private $logUrl;

    private $endUrl;

    public function __construct($token, $rid = null, $customEndpoint = null)
    {
        $this->token = $token;
        $this->request = new Request();
        $this->rid = $rid;
        $this->endpoint = $customEndpoint ?? self::BASE_URL;
        $this->buildUrls();
    }

    private function buildUrls(): void
    {
        $this->startUrl = $this->buildUrl(self::START_ENDPOINT);
        $this->failUrl = $this->buildUrl(self::FAIL_ENDPOINT);
        $this->logUrl = $this->buildUrl(self::LOG_ENDPOINT);
        $this->endUrl = $this->buildUrl("");
    }

    private function buildUrl($endpoint): string
    {
        $url = $this->endpoint. $this->token . $endpoint;
        if ($this->rid) {
            $url .= "?rid=" . $this->rid;
        }
        return $url;
    }

    private function checkHeaders(): void
    {
        if ($this->headersSet) {
            return;
        }
        $this->headers = ["User-Agent: Pancake/0.11 (+https://github.com/guibranco/pancake)", "Content-Type: application/json; charset=utf-8"];
    }

    public function setHeaders($headers): void
    {
        $this->headers = $headers;
        $this->headersSet = true;
    }

    public function heartbeat(): stdClass
    {
        $this->checkHeaders();
        return $this->request->get($this->endUrl, $this->headers);
    }

    public function start(): stdClass
    {
        $this->checkHeaders();
        return $this->request->get($this->startUrl, $this->headers);
    }

    public function fail(): stdClass
    {
        $this->checkHeaders();
        $this->failed = true;
        return $this->request->get($this->failUrl, $this->headers);
    }

    public function log($message): stdClass
    {
        $this->checkHeaders();
        return $this->request->post($this->logUrl, $this->headers, $message);
    }

    public function error($errorMessage): stdClass
    {
        $this->checkHeaders();
        $this->failed = true;
        return $this->request->post($this->failUrl, $this->headers, $errorMessage);
    }

    public function end(): stdClass
    {
        $this->checkHeaders();
        if ($this->failed) {
            return $this->request->get($this->failUrl, $this->headers);
        }
        return $this->request->get($this->endUrl, $this->headers);
    }

    public function resetState(): void
    {
        $this->failed = false;
    }
}
