<!-- NEW -->
<?php

namespace Utopia\Tests\Storage\Device;

use Utopia\Storage\Device\AzureBlob;
use Utopia\Tests\Storage\S3Base;

class AzureBlobTest extends S3Base
{
    protected function init(): void
    {
      
    }

    protected function getAdapterName(): string
    {
        return 'Azure Blob Storage';
    }

    protected function getAdapterType(): string
    {
        return $this->object->getType();
    }

    protected function getAdapterDescription(): string
    {
        return 'Azure Blob Storage';
    }
}

