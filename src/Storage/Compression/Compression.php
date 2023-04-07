<?php

namespace Utopia\Storage\Compression;

abstract class Compression
{
    /**
     * Return the name of compression algorithm.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @param  string  $data
     * @return string
     */
    abstract public function compress(string $data);

    /**
     * @param  string  $data
     * @return string
     */
    abstract public function decompress(string $data);
}
