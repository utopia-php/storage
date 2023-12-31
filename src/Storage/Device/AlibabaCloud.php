<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;

class AlibabaCloud extends Device
{
    /**
     * @var string
     */
    protected string $accessKey;

    /**
     * @var string
     */
    protected string $secretKey;

    /**
     * @var string
     */
    protected string $bucket;

    /**
     * @var string
     */
    protected string $endpoint;

    /**
     * @var OssClient
     */
    protected OssClient $client;

    /**
     * Alibaba constructor
     *
     * @param string $accessKey
     * @param string $secretKey
     * @param string $bucket
     * @param string $endpoint
     */
    public function __construct(string $accessKey, string $secretKey, string $bucket, string $endpoint)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->bucket = $bucket;
        $this->endpoint = $endpoint;

        try {
            $this->client = new OssClient($this->accessKey, $this->secretKey, $this->endpoint);
        } catch (OssException $e) {
            throw new Exception('Could not establish connection with Alibaba Cloud: '.$e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Alibaba Cloud Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_ALIBABA_CLOUD;
    }

    // Refer to the Alibaba Cloud OSS PHP SDK documentation for more details: https://www.alibabacloud.com/help/doc-detail/32099.htm

    /**
     * @param string $path
     * @return string
     */
    public function read(string $path): string
    {
        return $this->client->getObject($this->bucket, $path);
    }

    /**
     * @param string $path
     * @param string $data
     * @return bool
     */
    public function write(string $path, string $data): bool
    {
        try {
            $this->client->putObject($this->bucket, $path, $data);
            return true;
        } catch (OssException $e) {
            throw new Exception('Could not write data to Alibaba Cloud: '.$e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject($this->bucket, $path);
            return true;
        } catch (OssException $e) {
            throw new Exception('Could not delete data from Alibaba Cloud: '.$e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->client->doesObjectExist($this->bucket, $path);
    }

    /**
     * @param string $path
    * @param string $filepath
     * @return bool
     */
    public function upload(string $path, string $filepath): bool
    {
        try {
            $this->client->uploadFile($this->bucket, $path, $filepath);
            return true;
        } catch (OssException $e) {
            throw new Exception('Could not upload file to Alibaba Cloud: '.$e->getMessage());
        }
    }
}
