<?php

namespace Utopia\Storage\Device;

use Utopia\Storage\Device;

class S3 extends Device
{
    protected $key;
    protected $secret;
    protected $bucket;
    protected $region;
    protected $host;
    protected $acl='private';
    protected $root = 'temp';

    public function __construct($root='', $key,$secret, $bucket, $region, $acl) {
        $this->key = $key;
        $this->secret = $secret;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->root = $root;
        $this->acl = $acl;
        $this->host = $bucket . '.s3.'.$region . '.awazonaws.com';
    }

    private function getCredential() {
        $shortDate = gmdate('Ymd');
        $credential = $this->key . '/' . $shortDate . '/' . $this->region . '/s3/aws4_request';
        return $credential;
    }

    private function getSignature() {
        // VARIABLES
        // These are used throughout the request.
        $longDate = gmdate('Ymd\THis\Z');
        $shortDate = gmdate('Ymd');
        $credential = $this->getCredential();
        
        // POST POLICY
        // Amazon requires a base64-encoded POST policy written in JSON.
        // This tells Amazon what is acceptable for this request. For
        // simplicity, we set the expiration date to always be 24H in 
        // the future. The two "starts-with" fields are used to restrict
        // the content of "key" and "Content-Type", which are specified
        // later in the POST fields. Again for simplicity, we use blank
        // values ('') to not put any restrictions on those two fields.
        $policy = base64_encode(json_encode([
            'expiration' => gmdate('Y-m-d\TH:i:s\Z', time() + 86400),
            'conditions' => [
                ['acl' => $this->acl],
                ['bucket' => $this->bucket],
                ['starts-with', '$Content-Type', ''],
                ['starts-with', '$key', ''],
                ['x-amz-algorithm' => 'AWS4-HMAC-SHA256'],
                ['x-amz-credential' => $credential],
                ['x-amz-date' => $longDate]
            ]
        ]));
        
        // SIGNATURE
        // A base64-encoded HMAC hashed signature with your secret key.
        // This is used so Amazon can verify your request, and will be
        // passed along in a POST field later.
        $signingKey = hash_hmac('sha256', $shortDate, 'AWS4' . $this->secret, true);
        $signingKey = hash_hmac('sha256', $this->region, $signingKey, true);
        $signingKey = hash_hmac('sha256', 's3', $signingKey, true);
        $signingKey = hash_hmac('sha256', 'aws4_request', $signingKey, true);
        $signature = hash_hmac('sha256', $policy, $signingKey);
        return [
            "signature"=> $signature,
            "policy"=> $policy,
            "longDate"=> $longDate,
            "credential"=> $credential,
        ];
        
    }

    public function sign ($path) {
        $service = 's3';
        $headers = array();
        // the docs claim the date is in ISO8601 format. even though their examples aren't, let's go with it

        // add the date header
        $headers[] = 'X-AMZ-Date:' . gmdate('Ymd\THis\Z');

        // if the Algorithm isn't already set, use the default
        $algorithm = 'AWS4-HMAC-SHA256';

        // Task 1: Canonical Request
        $canonical_request = array();

        // 1) HTTP method - they're all POST
        $canonical_request[] = 'POST';

        // 2) CanonicalURI
        $uri = parse_url( rtrim( $path ), PHP_URL_PATH );

        // and URL encode it
        $uri = rawurlencode( $uri );

        // but restore the /'s
        // $uri = str_replace( '%2F', '/', $uri );

        $canonical_request[] = $uri;		// 2) URI

        // 3) CanonicalQueryString
        //  all our requests are POST and shouldn't have a query string
        $canonical_request[] = '';

        // 4) CanonicalHeaders
        $can_headers = array(
            'host' => parse_url( $path, PHP_URL_HOST ),		// you suck, amazon
        );
        foreach ( $headers as $k => $v ) {
            $can_headers[ strtolower( $k ) ] = trim( $v );
        }

        // sort them
        uksort( $can_headers, 'strcmp' );

        // add them to the string
        foreach ( $can_headers as $k => $v ) {
            $canonical_request[] = $k . ':' . $v;
        }

        // add a blank entry so we end up with an extra line break
        $canonical_request[] = '';

        // 5) SignedHeaders - seriously, what the fuck, amazon?
        $canonical_request[] = implode( ';', array_keys( $can_headers ) );

        // 6) Payload
        
        // figure out which algorithm we're using
        $alg = substr( strtolower( $algorithm ), strlen( 'AWS4-HMAC-' ) );	// trim 'aws4-hmac-'
        
        $canonical_request[] = hash( $alg, NULL);

        $canonical_request = implode( "\n", $canonical_request );

        // Task 2: String to Sign
        $string = array();

        // 1) Algorithm
        $string[] = $algorithm;

        // 2) RequestDate
        $string[] = gmdate('Ymd\THis\Z');

        // 3) CredentialScope
        $scope = array(
            gmdate('Ymd'),
        );

        // calculate the service name and region from the endpoint hostname: dynamodb.us-east-1.amazonaws.com
        // list( $service, $this->region, $junk ) = explode( '.', $this->host . $path );

        $scope[] = $this->region;
        $scope[] = $service;
        $scope[] = 'aws4_request';		// this is one of the stupidest things i've ever heard of

        $string[] = implode( '/', $scope );

        // 4) CanonicalRequest
        $string[] = hash( $alg, $canonical_request );

        $string = implode( "\n", $string );

        // Task 3: Signature

        // 1) HMACs
        $kSecret = 'AWS4' . $this->secret;
        $kDate = hash_hmac( $alg, gmdate('Ymd'), $kSecret, true );		// remember, binary!
        $kRegion = hash_hmac( $alg, $this->region, $kDate, true );
        $kService = hash_hmac( $alg, $service, $kRegion, true );
        $kSigning = hash_hmac( $alg, 'aws4_request', $kService, true );		// seriously, you're not securing anything amazon, just being a pain in the ass

        $signature = hash_hmac( $alg, $string, $kSigning );		// the signature is the only part done in hex!

        // finally, for the bloody Authorization header
        $authorization = array(
            'Credential=' . $this->key . '/' . implode( '/', $scope ),
            'SignedHeaders=' . implode( ';', array_keys( $can_headers ) ),
            'Signature=' . $signature,
        );

        $authorization = $algorithm . ' ' . implode( ',', $authorization );

        return $authorization;

    }



    /**
     * @return string
     */
    public function getName():string
    {
        return 'S3 Storage';
    }

    /**
     * @return string
     */
    public function getDescription():string
    {
        return 'S3 Bucket Storage drive for AWS or on premise solution';
    }

    /**
     * @return string
     */
    public function getRoot():string
    {
        return $this->root;
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function getPath($filename):string
    {
        $path = '';

        for ($i = 0; $i < 4; ++$i) {
            $path = ($i < \strlen($filename)) ? $path.DIRECTORY_SEPARATOR.$filename[$i] : $path.DIRECTORY_SEPARATOR.'x';
        }

        return $this->getRoot().$path.DIRECTORY_SEPARATOR.$filename;
    }


    /**
     * Upload.
     *
     * Upload a file to desired destination in the selected disk.
     *
     * @param string $source
     * @param string $path
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function upload($source, $path):bool
    {
        $this->write($path,$source);
    }

    /**
     * Read file by given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function read(string $path):string
    {
        return '';
    }

    /**
     * Write file by given path.
     *
     * @param string $path
     * @param string $data
     *
     * @return bool
     */
    public function write(string $path, string $data):bool
    {
        // CURL
        // The cURL request. Passes in the full URL to your Amazon bucket.
        // Sets RETURNTRANSFER and HEADER to true to see the full response from
        // Amazon, including body and head. Sets POST fields for cURL.
        // Then executes the cURL request.
        $signing = $this->getSignature();
        $pathinfo = pathinfo($path);

        $fileName = $pathinfo['basename'];
        $fileType = 'plain/text';
        $ch = curl_init('https://' . $this->host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'Content-Type' =>  $fileType,
            'acl' => $this->acl,
            'key' => $pathinfo['dirname'] . '/' . $fileName,
            'policy' =>  $signing['policy'],
            'x-amz-algorithm' => 'AWS4-HMAC-SHA256',
            'x-amz-credential' => $signing['credential'],
            'x-amz-date' => $signing['longDate'],
            'x-amz-signature' => $signing['signature'],
            'file' => $data
        ]);
        $response = curl_exec($ch);

        // RESPONSE
        // If Amazon returns a response code of 204, the request was
        // successful and the file should be sitting in your Amazon S3
        // bucket. If a code other than 204 is returned, there will be an
        // XML-formatted error code in the body. For simplicity, we use
        // substr to extract the error code and output it.
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 204) {
            echo 'Success';
            return true;
        } else {
            $error = substr($response, strpos($response, '<Code>') + 6);
            echo $error;
            echo substr($error, 0, strpos($error, '</Code>'));
            return false;
        }
    }

    /**
     * Move file from given source to given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $source
     * @param string $target
     *
     * @return bool
     */
    public function move(string $source, string $target):bool
    {
        return false;
    }

    /**
     * Delete file in given path, Return true on success and false on failure.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param string $path
     *
     * @return bool
     */

     /* 
     DELETE /my-second-image.jpg HTTP/1.1
    Host: <bucket>.s3.<Region>.amazonaws.com
    Date: Wed, 12 Oct 2009 17:50:00 GMT
    Authorization: authorization string
    Content-Type: text/plain    
     */

    public function delete(string $path, bool $recursive = false):bool
    {
        $signing = $this->getSignature();
        
        $ch = curl_init('https://' . $this->host);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization:'. $this->sign($path),
        ));
        $response = curl_exec($ch);

        // RESPONSE
        // If Amazon returns a response code of 204, the request was
        // successful and the file should be sitting in your Amazon S3
        // bucket. If a code other than 204 is returned, there will be an
        // XML-formatted error code in the body. For simplicity, we use
        // substr to extract the error code and output it.
        echo $response;
        echo curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 204) {
            echo 'Success';
            return true;
        } else {
            $error = substr($response, strpos($response, '<Code>') + 6);
            echo $error;
            echo substr($error, 0, strpos($error, '</Code>'));
            return false;
        }
    }
    
    /**
     * Returns given file path its size.
     *
     * @see http://php.net/manual/en/function.filesize.php
     *
     * @param $path
     *
     * @return int
     */
    public function getFileSize(string $path):int
    {
        return 0;
    }

    /**
     * Returns given file path its mime type.
     *
     * @see http://php.net/manual/en/function.mime-content-type.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileMimeType(string $path):string
    {
        return '';
    }

    /**
     * Returns given file path its MD5 hash value.
     *
     * @see http://php.net/manual/en/function.md5-file.php
     *
     * @param $path
     *
     * @return string
     */
    public function getFileHash(string $path):string
    {
        return '';
    }

    /**
     * Get directory size in bytes.
     *
     * Return -1 on error
     *
     * Based on http://www.jonasjohn.de/snippets/php/dir-size.htm
     *
     * @param $path
     *
     * @return int
     */
    public function getDirectorySize(string $path):int
    {
        return 0;
    }
    
    /**
     * Get Partition Free Space.
     *
     * disk_free_space — Returns available space on filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionFreeSpace():float
    {
        return 0.0;
    }

    /**
     * Get Partition Total Space.
     *
     * disk_total_space — Returns the total size of a filesystem or disk partition
     *
     * @return float
     */
    public function getPartitionTotalSpace():float
    {
        return 0.0;
    }
}