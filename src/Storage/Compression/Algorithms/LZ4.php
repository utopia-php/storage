<?php

namespace Utopia\Storage\Compression\Algorithms;

use Utopia\Storage\Compression\Compression;

class LZ4 extends Compression
{
    /**
     * Compression level from 0 up to a current max of 12.
     * Recommended values are between 4 and 9.
     *
     * Default value is 0, Not high compression mode.
     */
    protected int $level = 0;

    public function __construct(int $level = 0)
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
     * Allow values from 0 up to a current max of 12.
     *
     * @param  int  $level
     * @return void
     */
    public function setLevel(int $level): void
    {
        if ($level < 0 || $level > 12) {
            throw new \InvalidArgumentException('Level must be between 0 and 12');
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
        return 'lz4';
    }

    /**
     * Compress.
     *
     * @param  string  $data
     * @return string
     */
    public function compress(string $data): string
    {
        return \lz4_compress($data, $this->level);
    }

    /**
     * Decompress.
     *
     * @param  string  $data
     * @return string
     */
    public function decompress(string $data): string
    {
        return \lz4_uncompress($data);
    }
}
