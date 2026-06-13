<?php

namespace Utopia\Tests\Storage\Device;

use PHPUnit\Framework\TestCase;
use OSS\Core\OssException;
use Utopia\Storage\Device\Alibaba;

class AlibabaCloudTest extends TestCase
{
        private $alibaba;
    
        protected function setUp(): void
        {
            $this->alibaba = new Alibaba('accessKey', 'secretKey', 'bucket', 'endpoint');
        }
    
        public function testGetName()
        {
            $this->assertEquals('Alibaba Cloud Storage', $this->alibaba->getName());
        }
    
        public function testGetType()
        {
            $this->assertEquals(Storage::DEVICE_ALIBABA_CLOUD, $this->alibaba->getType());
        }
    
        public function testRead()
        {
            $this->expectException(OssException::class);
            $this->alibaba->read('path');
        }
    
        public function testWrite()
        {
            $this->expectException(OssException::class);
            $this->alibaba->write('path', 'data');
        }
    
        public function testDelete()
        {
            $this->expectException(OssException::class);
            $this->alibaba->delete('path');
        }
    
        public function testExists()
        {
            $this->expectException(OssException::class);
            $this->alibaba->exists('path');
        }
    
        public function testUpload()
        {
            $this->expectException(OssException::class);
            $this->alibaba->upload('path', 'filepath');
        }
}
