<?php

declare(strict_types=1);

namespace GuiBranco\Pancake\Tests\Unit;

use GuiBranco\Pancake\HealthChecks;
use GuiBranco\Pancake\Response;
use PHPUnit\Framework\TestCase;

final class HealthChecksTest extends TestCase
{
    public function testCanCreateHealthCheck(): void
    {
        $healthCheck = new HealthChecks('token');
        $this->assertInstanceOf(HealthChecks::class, $healthCheck);
    }

    public function testCanCreateHealthCheckWithRid(): void
    {
        $healthCheck = new HealthChecks('token', 'rid');
        $this->assertInstanceOf(HealthChecks::class, $healthCheck);
    }

    public function testCanSetHeaders(): void
    {
        $healthCheck = new HealthChecks('token');
        $healthCheck->setHeaders(['key' => 'value']);
        $this->assertObjectHasProperty('headers', $healthCheck);
        $this->assertObjectHasProperty('headersSet', $healthCheck);
    }

    public function testCanHeartbeat(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->heartbeat();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanStart(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->start();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanFail(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->fail();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanLog(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->log('test');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanEnd(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->end();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanError(): void
    {
        $healthCheck = new HealthChecks('token');
        $response = $healthCheck->error('error message');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCanResetState(): void
    {
        $healthCheck = new HealthChecks('token');
        $healthCheck->resetState();
        $this->assertObjectHasProperty('failed', $healthCheck);
    }

    public function testCanSetHeadersAndFailAndThenEnd(): void
    {
        $healthCheck = new HealthChecks('token');
        $healthCheck->setHeaders(['key' => 'value']);
        $response = $healthCheck->start();
        $this->assertInstanceOf(Response::class, $response);
        $response = $healthCheck->fail();
        $this->assertInstanceOf(Response::class, $response);
        $response = $healthCheck->end();
        $this->assertInstanceOf(Response::class, $response);
    }
}
