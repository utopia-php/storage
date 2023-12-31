<?php

namespace Utopia\Storage\Compression;

abstract class Compression
{
    public const ZSTD = 'zstd';

    public const GZIP = 'gzip';

    public const BROTLI = 'brotli';

    public const LZ4 = 'lz4';

    public const SNAPPY = 'snappy';

    public const XZ = 'xz';

    /**
     * Return the name of compression algorithm.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param $data
     * @return string
     */
    abstract public function compress(string $data);

    /**
     * @param $data
     * @return string
     */
    abstract public function decompress(string $data);
}
