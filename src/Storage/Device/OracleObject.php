<?php

namespace Utopia\Storage\Device;

use Exception;
use Utopia\Storage\Device;
use Utopia\Storage\Storage;

class OracleObject extends Device
{
    /**
     * @var string
     */
    protected string $root = 'oracle-object';

    /**
     * OracleObject constructor.
     *
     * @param string $root
     */
    public function __construct(string $root = '')
    {
        $this->root = $root;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Oracle Object Storage';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return Storage::DEVICE_ORACLE_OBJECT;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Adapter for Oracle Object Storage.';
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }
}
