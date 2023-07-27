<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class Zopfli extends Compression
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'zopfli';
    }

    /**
     * Compress.
     *
     * @param string $data
     *
     * @return string
     */
    public function compress(string $data): string
    {
        return \zopfliencode($data);
    }

    /**
     * Decompress.
     *
     * @param string $data
     *
     * @return string
     */
    public function decompress(string $data): string
    {
        return \zopflidecode($data);
    }
}
