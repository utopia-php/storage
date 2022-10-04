<?php

namespace Utopia\Tests;

use InvalidArgumentException;
use Utopia\Storage\Compression\Algorithms\Brotli;
use PHPUnit\Framework\TestCase;

class BrotliTest extends TestCase
{
    /**
     * @var Brotli
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new Brotli();
    }

    public function tearDown(): void
    {
    }

    public function testName()
    {
        $this->assertEquals($this->object->getName(), 'brotli');
    }

    public function testErrorsWhenSettingLevel()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setLevel(-1);
    }

    public function testCompressDecompressWithText()
    {
        $demo = 'This is a demo string';
        $demoSize = mb_strlen($demo, '8bit');

        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 21);
        $this->assertEquals($dataSize, 25);

        $this->assertEquals($this->object->decompress($data), $demo);
    }

    public function testCompressDecompressWithJPGImage()
    {
        $demo = \file_get_contents(__DIR__ . '/../../../resources/disk-a/kitten-1.jpg');
        $demoSize = mb_strlen($demo, '8bit');

        $this->object->setLevel(8);
        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 599639);
        // brotli is not the best for images
        $this->assertEquals($dataSize, 599644);

        $data = $this->object->decompress($data);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($dataSize, 599639);
    }

    public function testCompressDecompressWithPNGImage()
    {
        $demo = \file_get_contents(__DIR__ . '/../../../resources/disk-b/kitten-1.png');
        $demoSize = mb_strlen($demo, '8bit');

        $this->object->setLevel(8);
        $data = $this->object->compress($demo);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($demoSize, 3038056);
        // brotli is not the best for images
        $this->assertEquals($dataSize, 3038068);

        $data = $this->object->decompress($data);
        $dataSize = mb_strlen($data, '8bit');

        $this->assertEquals($dataSize, 3038056);
    }
}
