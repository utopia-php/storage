<?php

namespace Utopia\Storage;

use Exception;

class Storage
{
    /**
     * Supported devices
     */
    const DEVICE_LOCAL = 'local';

    const DEVICE_S3 = 's3';

    const DEVICE_DO_SPACES = 'dospaces';

    const DEVICE_WASABI = 'wasabi';

    const DEVICE_BACKBLAZE = 'backblaze';

    const DEVICE_LINODE = 'linode';

    const DEVICE_EXOSCALE = 'exoscale';

    /**
     * Devices.
     *
     * List of all available storage devices
     *
     * @var array
     */
    public static $devices = [];

    /**
     * Set Device.
     *
     * Add device by name
     *
     * @param  string  $name
     * @param  Device  $device
     * @return void
     *
     * @throws Exception
     */
    public static function setDevice($name, Device $device): void
    {
        self::$devices[$name] = $device;
    }

    /**
     * Get Device.
     *
     * Get device by name
     *
     * @param  string  $name
     * @return Device
     *
     * @throws Exception
     */
    public static function getDevice($name)
    {
        if (! \array_key_exists($name, self::$devices)) {
            throw new Exception('The device "'.$name.'" is not listed');
        }

        return self::$devices[$name];
    }

    /**
     * Exists.
     *
     * Checks if given storage name is registered or not
     *
     * @param  string  $name
     * @return bool
     */
    public static function exists($name)
    {
        return (bool) \array_key_exists($name, self::$devices);
    }

    /**
     * Human readable data size format from bytes input.
     *
     * Based on: https://stackoverflow.com/a/38659168/2299554
     *
     * @param  int  $bytes
     * @param  int  $decimals
     * @param  string  $system
     * @return string
     */
    public static function human(int $bytes, $decimals = 2, $system = 'metric')
    {
        $mod = ($system === 'binary') ? 1024 : 1000;

        $units = [
            'binary' => [
                'B',
                'KiB',
                'MiB',
                'GiB',
                'TiB',
                'PiB',
                'EiB',
                'ZiB',
                'YiB',
            ],
            'metric' => [
                'B',
                'kB',
                'MB',
                'GB',
                'TB',
                'PB',
                'EB',
                'ZB',
                'YB',
            ],
        ];

        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);

        return sprintf("%.{$decimals}f%s", $bytes / pow($mod, $factor), $units[$system][$factor]);
    }
}
