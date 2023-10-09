<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

class AzureBlob extends Device
{
    const METHOD_GET = 'GET';

    const METHOD_POST = 'POST';

    const METHOD_PUT = 'PUT';

    const METHOD_PATCH = 'PATCH';

    const METHOD_DELETE = 'DELETE';

    const METHOD_HEAD = 'HEAD';

    const METHOD_OPTIONS = 'OPTIONS';

    const METHOD_CONNECT = 'CONNECT';

    const METHOD_TRACE = 'TRACE';


    /**
     * @var string
     */
    protected string $accountName;

     /**
     * @var string
     */
    protected string $containerName;


    /**
     * @var string
     */
    protected string $accessKey;

    /**
     * @var string
     */
    protected string $root = 'temp';

    /**
     * @var array
     */
    protected array $headers = [
        'host' => '', 'date' => '',
        'content-md5' => '',
        'content-type' => '',
        'x-ms-blob-type' => '',
        'x-ms-date' => '',
        'x-ms-version' => '',
    ];


    /**
     * AzureBlob Constructor
     *
     * @param  string  $root
     * @param  string  $accessKey
     * @param  string  $secretKey
     * @param  string  $bucket
     * @param  string  $region
     * @param  string  $acl
     */
    public function __construct(string $root, string $accountName, string $containerName , string $accessKey)
    {
        $this->accountName = $accountName;
        $this->containerName = $containerName;
        $this->accessKey = $accessKey;
        $this->headers['host'] = $accountName + '.blob.core.windows.net/' + $containerName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Azure Blob Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_AZURE_BLOB;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Blob Storage Drive for Azure';
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

  
}
