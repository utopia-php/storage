<?php

namespace Utopia\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Device\Local;
use Utopia\Storage\Storage;

class StorageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Storage::setDevice('disk-a', new Local(__DIR__.'/../resources/disk-a'));
        Storage::setDevice('disk-b', new Local(__DIR__.'/../resources/disk-b'));
    }

    public function testGetDevice(): void
    {
        $this->assertInstanceOf(Local::class, Storage::getDevice('disk-a'));
        $this->assertInstanceOf(Local::class, Storage::getDevice('disk-b'));

        try {
            Storage::getDevice('disk-c');
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertSame('The device "disk-c" is not listed', $e->getMessage());
        }
    }

    public function testExists(): void
    {
        $this->assertTrue(Storage::exists('disk-a'));
        $this->assertTrue(Storage::exists('disk-b'));
        $this->assertFalse(Storage::exists('disk-c'));
    }
}
