<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\Telemetry;
use Utopia\Telemetry\Adapter\Test as TestTelemetry;

final class TelemetryTest extends TestCase
{
    public function testStorageOperationTelemetryIsCreatedOnFirstRecord(): void
    {
        $telemetry = new TestTelemetry();
        $underlying = new Local(__DIR__ . '/../../resources/disk-a');
        $device = new Telemetry($telemetry, $underlying);
        $path = $underlying->getPath('lorem.txt');

        $this->assertArrayNotHasKey('storage.operation', $telemetry->histograms);

        $device->exists($path);

        $this->assertArrayHasKey('storage.operation', $telemetry->histograms);
    }

    public function testDecoratedDeviceIsAccessible(): void
    {
        $underlying = new Local(__DIR__ . '/../../resources/disk-a');
        $device = new Telemetry(new TestTelemetry(), $underlying);

        $this->assertSame($underlying, $device->getDevice());
    }
}
