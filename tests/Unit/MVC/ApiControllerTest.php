<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\MVC;

use GuiBranco\Pancake\MVC\ApiController;
use GuiBranco\Pancake\MVC\TemplateEngineInterface;
use PHPUnit\Framework\TestCase;

final class ApiControllerTest extends TestCase
{
    public function testRenderIgnoresViewAndEchoesJsonOfData(): void
    {
        $controller = new RecordingApiController($this->createStub(TemplateEngineInterface::class));

        $this->expectOutputString('{"key":"value"}');
        $controller->render('ignored-view', ['key' => 'value']);

        $this->assertSame(['Content-Type: application/json'], $controller->sentHeaders);
    }

    public function testRenderWithEmptyDataProducesEmptyJsonObject(): void
    {
        $controller = new RecordingApiController($this->createStub(TemplateEngineInterface::class));

        $this->expectOutputString('[]');
        $controller->render('ignored-view');
    }
}

final class RecordingApiController extends ApiController
{
    public array $sentHeaders = [];

    protected function sendHeader(string $header): void
    {
        $this->sentHeaders[] = $header;
    }
}
