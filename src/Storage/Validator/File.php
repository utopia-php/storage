<?php

namespace Utopia\Storage\Validator;

use Utopia\Validator;

class File extends Validator
{
    public function getDescription(): string
    {
        return 'File is not valid';
    }

    /**
     * NOT MUCH RIGHT NOW.
     *
     * TODO think what to do here, currently only used for parameter to be present in SDKs
     *
     * @param  mixed  $name
     */
    public function isValid($name): bool
    {
        return true;
    }

    /**
     * Is array
     *
     * Function will return true if object is array.
     */
    public function isArray(): bool
    {
        return false;
    }

    /**
     * Get Type
     *
     * Returns validator type.
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }
}
