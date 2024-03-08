<?php

namespace Utopia\Storage\Validator;

use Utopia\Http\Validator;

class FileSize extends Validator
{
    /**
     * @var int
     */
    protected $max;

    /**
     * Max size in bytes
     *
     * @param  int  $max
     */
    public function __construct($max)
    {
        $this->max = $max;
    }

    /**
     * Get Description
     */
    public function getDescription(): string
    {
        return 'File size can\'t be bigger than '.$this->max;
    }

    /**
     * Finds whether a file size is smaller than required limit.
     *
     * @param  mixed  $fileSize
     * @return bool
     */
    public function isValid($fileSize): bool
    {
        if (! is_int($fileSize)) {
            return false;
        }

        if ($fileSize > $this->max) {
            return false;
        }

        return true;
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     *
     * @return string
     */
    public function getType(): string
    {
        return self::TYPE_INTEGER;
    }
}
