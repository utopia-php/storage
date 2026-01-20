<?php

namespace Utopia\Storage\Validator;

use Utopia\Validator;

class FileExt extends Validator
{
    public const TYPE_JPEG = 'jpeg';

    public const TYPE_JPG = 'jpg';

    public const TYPE_GIF = 'gif';

    public const TYPE_PNG = 'png';

    public const TYPE_GZIP = 'gz';

    const TYPE_ZIP = 'zip';

    /**
     * @var array
     */
    protected $allowed;

    /**
     * @param  array  $allowed
     */
    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * Get Description
     */
    public function getDescription(): string
    {
        return 'File extension is not valid';
    }

    /**
     * Check if file extenstion is allowed
     *
     * @param  mixed  $filename
     * @return bool
     */
    public function isValid($filename): bool
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        if (! in_array($ext, $this->allowed)) {
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
