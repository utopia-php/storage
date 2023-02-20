<?php

namespace Utopia\Tests\Storage\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Validator\FileName;

class FileNameTest extends TestCase
{
    /**
     * @var FileName
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new FileName();
    }

    public function tearDown(): void
    {
    }

    public function testValues(): void
    {
        $this->assertEquals($this->object->isValid(''), false);
        $this->assertEquals($this->object->isValid(null), false);
        $this->assertEquals($this->object->isValid(false), false);
        $this->assertEquals($this->object->isValid('../test'), false);
        $this->assertEquals($this->object->isValid('test.png'), true);
        $this->assertEquals($this->object->isValid('test'), true);
    }
}
