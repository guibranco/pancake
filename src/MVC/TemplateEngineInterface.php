<?php

namespace GuiBranco\Pancake\MVC;

/**
 * Interface TemplateEngineInterface
 *
 * Contract for view-rendering engines used by {@see BaseController::render()}.
 * Implement this to plug in a real templating library (Twig, Mustache, Plates, ...)
 * instead of {@see DefaultTemplateEngine} — register the implementation in the
 * {@see \GuiBranco\Pancake\DIContainer} under `TemplateEngineInterface::class`.
 *
 * @package GuiBranco\Pancake\MVC
 */
interface TemplateEngineInterface
{
    /**
     * Renders $view with $data and returns the resulting output as a string.
     */
    public function render(string $view, array $data = []): string;
}
