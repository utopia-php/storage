<?php

namespace Utopia\Tests\Storage\Validator;

use PHPUnit\Framework\TestCase;
use Utopia\Storage\Validator\FileExt;

class FileExtTest extends TestCase
{
    /**
     * @var FileExt
     */
    protected $object = null;

    public function setUp(): void
    {
        $this->object = new FileExt([FileExt::TYPE_GIF, FileExt::TYPE_GZIP]);
    }

    public function tearDown(): void
    {
    }

    public function testValues()
    {
        $this->assertEquals($this->object->isValid(''), false);
        $this->assertEquals($this->object->isValid(null), false);
        $this->assertEquals($this->object->isValid(false), false);
        $this->assertEquals($this->object->isValid('test'), false);
        $this->assertEquals($this->object->isValid('...test.png'), false);
        $this->assertEquals($this->object->isValid('.gif'), true);
        $this->assertEquals($this->object->isValid('x.gif'), true);
        $this->assertEquals($this->object->isValid('gif'), false);
        $this->assertEquals($this->object->isValid('file.tar'), false);
        $this->assertEquals($this->object->isValid('file.tar.g'), false);
        $this->assertEquals($this->object->isValid('file.tar.gz'), true);
        $this->assertEquals($this->object->isValid('file.gz'), true);
    }
}
