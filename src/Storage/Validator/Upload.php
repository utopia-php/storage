<?php

namespace Utopia\Storage\Validator;

use Utopia\Validator;

class Upload extends Validator
{
    /**
     * Get Description
     */
    public function getDescription(): string
    {
        return 'Not a valid upload file';
    }

    /**
     * Check if a file is a valid upload file
     *
     * @param mixed $path
     *
     * @return bool
     */
    public function isValid($path): bool
    {
        if (!is_string($path)) {
            return false;
        }
        
        if (\is_uploaded_file($path)) {
            return true;
        }
        
        return false;
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
        return self::TYPE_STRING;
    }
}
