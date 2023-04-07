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
     * Check if file extension is allowed
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $ext = pathinfo($value, PATHINFO_EXTENSION);

        return \in_array($ext, $this->allowed, true);
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
