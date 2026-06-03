<?php

namespace Utopia\Tests\Storage;

use Exception;
use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Device\Telemetry;
use Utopia\Storage\Storage;
use Utopia\Telemetry\Adapter\Test as TestTelemetry;

Storage::setDevice('disk-a', new Local(__DIR__.'/../resources/disk-a'));
Storage::setDevice('disk-b', new Local(__DIR__.'/../resources/disk-b'));

class StorageTest extends TestCase
{
    protected function setUp(): void {}

    protected function tearDown(): void {}

    public function testGetters()
    {
        $this->assertEquals(get_class(Storage::getDevice('disk-a')), 'Utopia\Storage\Device\Local');
        $this->assertEquals(get_class(Storage::getDevice('disk-b')), 'Utopia\Storage\Device\Local');

        try {
            get_class(Storage::getDevice('disk-c'));
            $this->fail('Expected exception not thrown');
        } catch (Exception $e) {
            $this->assertEquals('The device "disk-c" is not listed', $e->getMessage());
        }
    }

    public function testExists()
    {
        $this->assertEquals(Storage::exists('disk-a'), true);
        $this->assertEquals(Storage::exists('disk-b'), true);
        $this->assertEquals(Storage::exists('disk-c'), false);
    }

    public function testMoveIdenticalName()
    {
        $file = '/kitten-1.jpg';
        $device = Storage::getDevice('disk-a');
        $this->assertFalse($device->move($file, $file));
    }

    public function testStorageOperationTelemetryIsCreatedOnFirstRecord(): void
    {
        $telemetry = new TestTelemetry();
        $underlying = new Local(__DIR__.'/../resources/disk-a');
        $device = new Telemetry($telemetry, $underlying);
        $path = $underlying->getPath('lorem.txt');

        $this->assertArrayNotHasKey('storage.operation', $telemetry->histograms);

        $device->exists($path);

        $this->assertArrayHasKey('storage.operation', $telemetry->histograms);
        $this->assertCount(1, $telemetry->histograms['storage.operation']->values);
    }
}
