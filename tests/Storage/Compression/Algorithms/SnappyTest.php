<?php

namespace Utopia\Tests\Storage\Compression\Algorithms;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Compression\Algorithms\Snappy;

class SnappyTest extends TestCase
{
    /**
     * @var Snappy
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new Snappy();
    }

    public function tearDown(): void
    {
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'snappy');
    }

    public function testCompressDecompressWithText()
    {
        $demo = 'This is a demo string';
        $demoSize = \mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);

        $dataSize = \mb_strlen($data, '8bit');

        $this->assertEquals(21, $demoSize);
        $this->assertEquals(23, $dataSize);

        $this->assertEquals($this->object->decompress($data), $demo);
    }

    public function testCompressDecompressWithJPGImage()
    {
        $demo = \file_get_contents(__DIR__.'/../../../resources/disk-a/kitten-1.jpg');
        $demoSize = \mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = \mb_strlen($data, '8bit');

        $this->assertEquals(599639, $demoSize);
        $this->assertEquals(599504, $dataSize);

        $this->assertGreaterThan($dataSize, $demoSize);

        $data = $this->object->decompress($data);
        $dataSize = \mb_strlen($data, '8bit');

        $this->assertEquals(599639, $dataSize);
    }

    public function testCompressDecompressWithPNGImage()
    {
        $demo = \file_get_contents(__DIR__.'/../../../resources/disk-b/kitten-1.png');
        $demoSize = \mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = \mb_strlen($data, '8bit');

        $this->assertEquals(3038056, $demoSize);
        $this->assertEquals(3038200, $dataSize);

        $this->assertGreaterThan($demoSize, $dataSize);

        $data = $this->object->decompress($data);
        $dataSize = \mb_strlen($data, '8bit');

        $this->assertEquals(3038056, $dataSize);
    }
}
