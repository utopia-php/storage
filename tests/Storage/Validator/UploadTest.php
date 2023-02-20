<?php

namespace Utopia\Tests\Storage\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Validator\Upload;

class UploadTest extends TestCase
{
    /**
     * @var Upload
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new Upload();
    }

    public function tearDown(): void
    {
    }

    public function testValues(): void
    {
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-a/kitten-1.jpg'), false);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-a/kitten-2.jpg'), false);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-b/kitten-1.png'), false);
        $this->assertEquals($this->object->isValid(__DIR__.'/../../resources/disk-b/kitten-2.png'), false);
        $this->assertEquals($this->object->isValid(__FILE__), false);
    }
}
