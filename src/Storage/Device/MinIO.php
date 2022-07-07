<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device\S3;

class MinIO extends S3
{
    /**
     * Regions constants
     *
     */
    const EU_CENTRAL_1='eu-central-1';
    const US_SOUTHEAST_1='us-southeast-1';
    const US_EAST_1='us-east-1';
    const AP_SOUTH_1='ap-south-1';

    /**
     * Object Storage Constructor
     *
     * @param string $root
     * @param string $accessKey
     * @param string $secretKey
     * @param string $protocol
     * @param string $host
     * @param string $bucket
     * @param string $region
     * @param string $acl
     */
    public function __construct(string $root, string $accessKey, string $secretKey, string $protocol, string $host, string $bucket, string $region = self::EU_CENTRAL_1, string $acl = self::ACL_PRIVATE)
    {
        parent::__construct($root, $accessKey, $secretKey, $bucket, $region, $acl);
        
        $this->protocol = $protocol;
        $this->headers['host'] = $host;
    }

    /**
     * Get list of objects in the given path.
     *
     * @param string $path
     * 
     * @throws \Exception
     *
     * @return array
     */
    public function listObjects($prefix = '', $maxKeys = 1000, $continuationToken = '')
    {
        $uri = '/' . $this->getRoot();
        $this->headers['content-type'] = 'text/plain';
        $this->headers['content-md5'] = \base64_encode(md5('', true));

        $parameters = [
            'list-type' => 2,
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ];
        if(!empty($continuationToken)) {
            $parameters['continuation-token'] = $continuationToken;
        }
        $response = parent::call(self::METHOD_GET, $uri, '', $parameters);
        return $response->body;
    }

    /**
     * Delete files in given path, path must be a directory. Return true on success and false on failure.
     *
     * @param string $path
     * 
     * @throws \Exception
     *
     * @return bool
     */
    public function deletePath(string $path): bool
    {
        $uri = '/' . $this->getRoot();
        $continuationToken = '';
        do {
            $objects = $this->listObjects($path, continuationToken: $continuationToken);
            $count = (int) ($objects['KeyCount'] ?? 1);
            if($count < 1) {
                break;
            }
            $continuationToken = $objects['NextContinuationToken'] ?? '';
            $body = '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/">';
            
            if($count > 1) {
                foreach ($objects['Contents'] as $object) {
                    $body .= "<Object><Key>{$object['Key']}</Key></Object>";
                }
            } else {
                $body .= "<Object><Key>{$objects['Contents']['Key']}</Key></Object>"; 
            }
            $body .= '<Quiet>true</Quiet>';
            $body .= '</Delete>';
            $this->amzHeaders['x-amz-content-sha256'] = \hash('sha256', $body);
            $this->headers['content-md5'] = \base64_encode(md5($body, true));
            parent::call(self::METHOD_POST, $uri, $body, ['delete'=>'']);
        } while(!empty($continuationToken));

        return true;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'MinIO Object Storage';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'MinIO Object Storage';
    }
}