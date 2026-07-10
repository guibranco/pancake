<?php

namespace GuiBranco\Pancake\MVC;

use RuntimeException;

/**
 * Class DefaultTemplateEngine
 *
 * Minimal built-in {@see TemplateEngineInterface} implementation: resolves a
 * dot-separated view name (e.g. `"users.profile"`) to a plain PHP file
 * (`{$viewsPath}/users/profile.php`), extracts $data as local variables, and
 * captures the file's output. Swap in Twig/Mustache/etc. by registering your
 * own {@see TemplateEngineInterface} implementation instead.
 *
 * ### Example view file (views/greeting.php)
 *
 * ```php
 * <p>Hello, <?= htmlspecialchars($name) ?>!</p>
 * ```
 *
 * @package GuiBranco\Pancake\MVC
 */
class DefaultTemplateEngine implements TemplateEngineInterface
{
    private string $viewsPath;

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = rtrim($viewsPath, '/\\');
    }

    public function render(string $view, array $data = []): string
    {
        $file = $this->resolveViewFile($view);

        if (!is_file($file)) {
            throw new RuntimeException("View '{$view}' not found at '{$file}'.");
        }

        $render = function () use ($file, $data) {
            extract($data, EXTR_SKIP);
            include $file;
        };

        ob_start();
        try {
            $render();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    private function resolveViewFile(string $view): string
    {
        $relative = str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';
        return $this->viewsPath . DIRECTORY_SEPARATOR . $relative;
    }
}
