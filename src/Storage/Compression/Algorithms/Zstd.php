<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class Zstd extends Compression
{
    /**
     * Compression level from 1 up to a current max of 22.
     * Levels >= 20 should be used with caution, as they require more memory.
     *
     * Default value is 3.
     */
    protected int $level = 3;

    public function __construct(int $level = 3)
    {
        $this->level = $level;
    }

    /**
     * Get the compression level.
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set the compression level.
     *
     * Allow values from 1 up to a current max of 22.
     *
     * @param int $level
     * @return void
     */
    public function setLevel(int $level): void
    {
        if ($level < 1 || $level > 22) {
            throw new \InvalidArgumentException('Level must be between 1 and 22');
        }
        $this->level = $level;
    }

    /**
     * Get the name of the algorithm.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'zstd';
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
        return \zstd_compress($data, $this->level);
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
        return \zstd_uncompress($data);
    }
}
