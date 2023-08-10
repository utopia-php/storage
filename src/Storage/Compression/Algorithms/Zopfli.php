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
    function zopfli_encode($data) {
        return gzencode($data, 9, FORCE_DEFLATE);
    }

    /**
     * Decompress.
     *
     * @param string $data
     *
     * @return string
     */
    function zopfli_decode($data) {
       return gzdecode($data);
    }
}
?>
