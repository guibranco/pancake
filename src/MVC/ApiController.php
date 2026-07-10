<?php

namespace GuiBranco\Pancake\MVC;

/**
 * Class ApiController
 *
 * {@see BaseController} variant for API (JSON) actions: overrides `render()`
 * to always emit a JSON response instead of a templated view. The `$view`
 * parameter is accepted only to keep the method signature compatible with
 * {@see BaseController::render()} — it is unused here.
 *
 * @package GuiBranco\Pancake\MVC
 */
class ApiController extends BaseController
{
    public function render(string $view, array $data = []): void
    {
        $this->sendHeader('Content-Type: application/json');
        echo json_encode($data);
    }
}
