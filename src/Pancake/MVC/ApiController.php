<?php

namespace Pancake\MVC;

class ApiController extends BaseController
{
    public function render($data = [])
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
