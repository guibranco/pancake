<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit\MVC;

use GuiBranco\Pancake\MVC\DefaultTemplateEngine;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DefaultTemplateEngineTest extends TestCase
{
    private DefaultTemplateEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DefaultTemplateEngine(__DIR__ . '/fixtures');
    }

    public function testRendersATopLevelView(): void
    {
        $output = $this->engine->render('greeting', ['name' => 'World']);

        $this->assertSame("<p>Hello, World!</p>\n", $output);
    }

    public function testRendersANestedViewUsingDotNotation(): void
    {
        $output = $this->engine->render('users.profile', ['username' => 'gui']);

        $this->assertSame("<p>User: gui</p>\n", $output);
    }

    public function testEscapesDataPassedToTheView(): void
    {
        $output = $this->engine->render('greeting', ['name' => '<script>']);

        $this->assertSame("<p>Hello, &lt;script&gt;!</p>\n", $output);
    }

    public function testThrowsWhenViewFileDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);

        $this->engine->render('does-not-exist');
    }
}
