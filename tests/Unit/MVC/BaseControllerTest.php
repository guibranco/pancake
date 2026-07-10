<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\MVC;

use GuiBranco\Pancake\MVC\BaseController;
use GuiBranco\Pancake\MVC\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

final class BaseControllerTest extends TestCase
{
    public function testRenderEchoesTheTemplateEngineOutput(): void
    {
        $templateEngine = $this->createMock(TemplateEngineInterface::class);
        $templateEngine->expects($this->once())
            ->method('render')
            ->with('home', ['key' => 'value'])
            ->willReturn('rendered view');

        $controller = new BaseController($templateEngine);

        $this->expectOutputString('rendered view');
        $controller->render('home', ['key' => 'value']);
    }

    public function testRedirectSendsLocationHeaderAndTerminates(): void
    {
        $templateEngine = $this->createStub(TemplateEngineInterface::class);
        $controller = new RecordingBaseController($templateEngine);

        $controller->redirect('http://example.com');

        $this->assertSame(['Location: http://example.com'], $controller->sentHeaders);
        $this->assertTrue($controller->terminated);
    }
}

final class RecordingBaseController extends BaseController
{
    public array $sentHeaders = [];
    public bool $terminated = false;

    protected function sendHeader(string $header): void
    {
        $this->sentHeaders[] = $header;
    }

    protected function terminate(): void
    {
        $this->terminated = true;
    }
}
