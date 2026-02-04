<?php

namespace Pancake\MVC;

class BaseController
{
    protected $templateEngine;

    public function __construct($templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function render($view, $data = [])
    {
        echo $this->templateEngine->render($view, $data);
    }

    public function redirect($url)
    {
        header('Location: ' . $url);
        exit();
    }
}
