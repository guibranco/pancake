<?php

namespace GuiBranco\Pancake\MVC;

/**
 * Class BaseController
 *
 * Base controller for web (HTML) actions: renders views through an injected
 * {@see TemplateEngineInterface} and offers a `redirect()` helper. Resolve
 * subclasses through {@see \GuiBranco\Pancake\DIContainer} so the template
 * engine dependency is wired automatically.
 *
 * `redirect()` sends its header and calls `exit()` through the overridable
 * {@see sendHeader()} / {@see terminate()} hooks, since PHP's CLI SAPI never
 * actually records headers (`headers_list()` is always empty there) and a
 * real `exit()` would kill the test process — tests stub both instead.
 *
 * @package GuiBranco\Pancake\MVC
 */
class BaseController
{
    protected TemplateEngineInterface $templateEngine;

    public function __construct(TemplateEngineInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function render(string $view, array $data = []): void
    {
        echo $this->templateEngine->render($view, $data);
    }

    public function redirect(string $url): void
    {
        $this->sendHeader("Location: {$url}");
        $this->terminate();
    }

    protected function sendHeader(string $header): void
    {
        header($header);
    }

    protected function terminate(): void
    {
        exit();
    }
}
