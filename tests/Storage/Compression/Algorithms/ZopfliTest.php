<?php

namespace Utopia\Tests\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Algorithms\Zopfli;
use PHPUnit\Framework\TestCase;

class ZopfliTest extends TestCase
{
    protected Zopfli $object;

    public function setUp(): void
    {
        $this->object = new Zopfli();
    }

    public function tearDown(): void
    {
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'zopfli');
    }

    public function testCompressDecompressWithText()
    {
        $demo = 'This is a demo string';
        $demoSize = mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 21);
        $this->assertEquals($dataSize, 80);

        $this->assertEquals($this->object->decompress($data), $demo);
    }

    public function testCompressDecompressWithJPGImage()
    {
        $demo = \file_get_contents(__DIR__ . '/../../../resources/disk-a/kitten-1.jpg');
        $demoSize = mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 599639);
        $this->assertEquals($dataSize, 599432);

        $this->assertGreaterThan($dataSize, $demoSize);

        $data = $this->object->decompress($data);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($dataSize, 599639);
    }

    public function testCompressDecompressWithPNGImage()
    {
        $demo = \file_get_contents(__DIR__ . '/../../../resources/disk-b/kitten-1.png');
        $demoSize = mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 3038056);
        $this->assertEquals($dataSize, 2981000);

        $this->assertGreaterThan($dataSize, $demoSize);

        $data = $this->object->decompress($data);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($dataSize, 3038056);
    }
}
