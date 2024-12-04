<?php

namespace Utopia\Storage\Validator;

use Utopia\Http\Validator;

class FileName extends Validator
{
    /**
     * Get Description
     */
    public function getDescription(): string
    {
        return 'Filename is not valid';
    }

    /**
     * The file name can only contain "a-z", "A-Z", "0-9" and "-" and not empty.
     *
     * @param  mixed  $name
     * @return bool
     */
    public function isValid($name): bool
    {
        if (empty($name)) {
            return false;
        }

        if (! is_string($name)) {
            return false;
        }

        if (! \preg_match('/^[a-zA-Z0-9.]+$/', $name)) {
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
        return self::TYPE_STRING;
    }
}
