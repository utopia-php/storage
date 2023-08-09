<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class XZ extends Compression
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'xz';
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
        return \xzencode($data);
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
        return \xzdecode($data);
    }
}
