<?php

namespace Utopia\Storage\Validator;

use Utopia\Validator;

class FileExt extends Validator
{
    const TYPE_JPEG = 'jpeg';
    const TYPE_JPG = 'jpg';
    const TYPE_GIF = 'gif';
    const TYPE_PNG = 'png';
    const TYPE_GZIP = 'gz';

    /**
     * @var array
     */
    protected $allowed;

    /**
     * @param array $allowed
     *
     */
    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * Get Description
     */
    public function getDescription()
    {
        return 'File extension is not valid';
    }

    /**
     * Check if file extenstion is allowed
     * 
     * @param mixed $filename
     *
     * @return bool
     */
    public function isValid($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array($ext, $this->allowed)) {
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
