<?php

declare(strict_types=1);

namespace Utopia\Tests\Storage;

use Exception;
use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\Telemetry;
use Utopia\Storage\Storage;
use Utopia\Telemetry\Adapter\Test as TestTelemetry;

Storage::setDevice('disk-a', new Local(__DIR__ . '/../resources/disk-a'));
Storage::setDevice('disk-b', new Local(__DIR__ . '/../resources/disk-b'));

final class StorageTest extends TestCase
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testGetters(): void
    {
        $this->assertInstanceOf(\Utopia\Storage\Device\Local::class, Storage::getDevice('disk-a'));
        $this->assertInstanceOf(\Utopia\Storage\Device\Local::class, Storage::getDevice('disk-b'));

        try {
            Storage::getDevice('disk-c');
            $this->fail('Expected exception not thrown');
        } catch (Exception $e) {
            $this->assertSame('The device "disk-c" is not listed', $e->getMessage());
        }
    }

    public function testExists(): void
    {
        $this->assertTrue(Storage::exists('disk-a'));
        $this->assertTrue(Storage::exists('disk-b'));
        $this->assertFalse(Storage::exists('disk-c'));
    }

    public function testMoveIdenticalName(): void
    {
        $file = '/kitten-1.jpg';
        $device = Storage::getDevice('disk-a');
        $this->assertFalse($device->move($file, $file));
    }

    public function testStorageOperationTelemetryIsCreatedOnFirstRecord(): void
    {
        $telemetry = new TestTelemetry();
        $underlying = new Local(__DIR__ . '/../resources/disk-a');
        $device = new Telemetry($telemetry, $underlying);
        $path = $underlying->getPath('lorem.txt');

        $this->assertArrayNotHasKey('storage.operation', $telemetry->histograms);

        $device->exists($path);

        $this->assertArrayHasKey('storage.operation', $telemetry->histograms);
    }
}
