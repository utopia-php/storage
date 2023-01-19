<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class BZIP2 extends Compression
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'bzip2';
    }

    /**
     * Compress.
     *
     * @see https://www.php.net/manual/en/function.bzcompress.php
     *
     * @param string $data
     * @return string
     */
    public function compress(string $data): string
    {
        return \bzcompress($data);
    }

    /**
     * Decompress.
     *
     * @see https://www.php.net/manual/en/function.bzdecompress.php
     *
     * @param string $data
     * @return string
     */
    public function decompress(string $data):string
    {
        return \bzdecompress($data);
    }
}
