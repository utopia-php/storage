<?php

// namespace Utopia\Tests\Compression\Algorithms;

// use PHPUnit\Framework\TestCase;
// use Utopia\Storage\Compression\Algorithms\LZ4;

// class LZ4Test extends TestCase
// {
//     protected LZ4 $object;

//     public function setUp(): void
//     {
//         $this->object = new LZ4();
//     }

//     public function tearDown(): void
//     {
//     }

//     public function testName()
//     {
//         $this->assertEquals($this->object->getName(), 'lz4');
//     }

//     public function testCompressDecompressWithText()
//     {
//         $demo = 'This is a demo string';
//         $demoSize = \mb_strlen($demo, '8bit');

//         $data = $this->object->compress($demo);
//         $dataSize = \mb_strlen($data, '8bit');

//         $this->assertEquals(21, $demoSize);
//         $this->assertEquals(27, $dataSize);

//         $this->assertEquals($demo, $this->object->decompress($data));
//     }

//     public function testCompressDecompressWithJPGImage()
//     {
//         $demo = \file_get_contents(__DIR__.'/../../../resources/disk-a/kitten-1.jpg');
//         $demoSize = \mb_strlen($demo, '8bit');

//         $data = $this->object->compress($demo);
//         $dataSize = \mb_strlen($data, '8bit');

//         $this->assertEquals(599639, $demoSize);
//         $this->assertEquals(601828, $dataSize);

//         $this->assertGreaterThan($demoSize, $dataSize);

//         $data = $this->object->decompress($data);
//         $dataSize = \mb_strlen($data, '8bit');

//         $this->assertEquals(599639, $dataSize);
//     }

//     public function testCompressDecompressWithPNGImage()
//     {
//         $demo = \file_get_contents(__DIR__.'/../../../resources/disk-b/kitten-1.png');
//         $demoSize = \mb_strlen($demo, '8bit');

//         $data = $this->object->compress($demo);
//         $dataSize = \mb_strlen($data, '8bit');

//         $this->assertEquals(3038056, $demoSize);
//         $this->assertEquals(3049975, $dataSize);

//         $this->assertGreaterThan($demoSize, $dataSize);

//         $data = $this->object->decompress($data);
//         $dataSize = \mb_strlen($data, '8bit');

//         $this->assertEquals(3038056, $dataSize);
//     }
// }
