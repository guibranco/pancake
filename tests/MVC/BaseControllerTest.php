<?php

namespace GuiBranco\Pancake\Tests\MVC;

use PHPUnit\Framework\TestCase;
use Pancake\MVC\BaseController;

class BaseControllerTest extends TestCase
{
    public function testRender()
    {
        $templateEngine = $this->createMock(TemplateEngine::class);
        $templateEngine->expects($this->once())
                       ->method('render')
                       ->with('view', ['key' => 'value'])
                       ->willReturn('rendered view');

        $controller = new BaseController($templateEngine);
        $this->expectOutputString('rendered view');
        $controller->render('view', ['key' => 'value']);
    }

    public function testRedirect()
    {
        $controller = new BaseController(null);

        $this->expectException(\PHPUnit\Framework\Error\Warning::class);
        $this->expectExceptionMessage('Cannot modify header information');

        $controller->redirect('http://example.com');
    }
}
