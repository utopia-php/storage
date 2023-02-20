<?php

namespace Utopia\Tests\Storage\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Validator\FileSize;

class FileSizeTest extends TestCase
{
    /**
     * @var FileSize
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new FileSize(1000);
    }

    public function tearDown(): void
    {
    }

    public function testValues(): void
    {
        $this->assertEquals($this->object->isValid(1001), false);
        $this->assertEquals($this->object->isValid(1000), true);
        $this->assertEquals($this->object->isValid(999), true);
    }
}
