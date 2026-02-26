<?php

namespace Utopia\Tests\Storage\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Validator\FileType;

class FileTypeTest extends TestCase
{
    /**
     * @var FileType
     */
    protected $object = null;

    protected function setUp(): void
    {
        $this->object = new FileType([FileType::FILE_TYPE_JPEG]);
    }

    protected function tearDown(): void {}

    public function testValues()
    {
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-a/kitten-1.jpg'), true);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-a/kitten-2.jpg'), true);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-b/kitten-1.png'), false);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-b/kitten-2.png'), false);
    }
}
