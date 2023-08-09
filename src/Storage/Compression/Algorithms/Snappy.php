<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class Snappy extends Compression
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'snappy';
    }

    /**
     * Compress.
     *
     * @param  string  $data
     * @return string
     */
    public function compress(string $data): string
    {
        return \snappy_compress($data);
    }

    /**
     * Decompress.
     *
     * @param  string  $data
     * @return string
     */
    public function decompress(string $data): string
    {
        return \snappy_uncompress($data);
    }
}
