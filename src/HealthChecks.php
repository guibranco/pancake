<?php

namespace GuiBranco\Pancake;

use stdClass;

class HealthChecks
{
    public const BASE_URL = "https://hc-ping.com/";

    private const START_ENDPOINT = "/start";

    private const FAIL_ENDPOINT = "/fail";

    private $token;

    private $request;

    private $failed = false;

    private $headers;

    private $headersSet = false;

    private $rid;

    private $startUrl;

    private $failUrl;

    private $endUrl;

    public function __construct($token, $rid = null)
    {
        $this->token = $token;
        $this->request = new Request();
        $this->rid = $rid;
        $this->buildUrls();
    }

    private function buildUrls(): void
    {
        $this->startUrl = $this->buildUrl(self::START_ENDPOINT);
        $this->failUrl = $this->buildUrl(self::FAIL_ENDPOINT);
        $this->endUrl = $this->buildUrl("");
    }

    private function buildUrl($endpoint): string
    {
        $url = self::BASE_URL . $this->token . $endpoint;
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
        $this->headers = ["User-Agent: Pancake/1.0.0", "Content-Type: application/json"];
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

    public function error($errorMessage): stdClass
    {
        $this->checkHeaders();
        $this->failed = true;
        return $this->request->post($this->failUrl, $errorMessage, $this->headers);
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
