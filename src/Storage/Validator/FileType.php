<?php

namespace Utopia\Storage\Validator;

use Exception;
use Utopia\Validator;

class FileType extends Validator
{
    /**
     * File Types Constants.
     */
    const FILE_TYPE_JPEG = 'jpeg';

    const FILE_TYPE_GIF = 'gif';

    const FILE_TYPE_PNG = 'png';

    const FILE_TYPE_GZIP = 'gz';

    /**
     * File Type Binaries.
     *
     * @var array
     */
    protected $types = [
        self::FILE_TYPE_JPEG => "\xFF\xD8\xFF",
        self::FILE_TYPE_GIF => 'GIF',
        self::FILE_TYPE_PNG => "\x89\x50\x4e\x47\x0d\x0a",
        self::FILE_TYPE_GZIP => 'application/x-gzip',
    ];

    /**
     * @var array
     */
    protected $allowed;

    /**
     * @param  array  $allowed
     *
     * @throws Exception
     */
    public function __construct(array $allowed)
    {
        foreach ($allowed as $key) {
            if (! isset($this->types[$key])) {
                throw new Exception('Unknown file mime type');
            }
        }

        $this->allowed = $allowed;
    }

    /**
     * Get Description
     */
    public function getDescription(): string
    {
        return 'File mime-type is not allowed ';
    }

    /**
     * Is Valid.
     *
     * Binary check to finds whether a file is of valid type
     *
     * @see http://stackoverflow.com/a/3313196
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        if (! \is_readable($value)) {
            return false;
        }

        $handle = \fopen($value, 'r');
        if ($handle === false) {
            return false;
        }

        $bytes = \fgets($handle, 8);

        foreach ($this->allowed as $key) {
            if (str_starts_with($bytes, $this->types[$key])) {
                \fclose($handle);

                return true;
            }
        }

        \fclose($handle);

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
