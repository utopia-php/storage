<?php

namespace Utopia\Storage;

use Exception;

class Storage
{

    /**
     * Supported devices
     */
    const DEVICE_LOCAL = 'Local';
    const DEVICE_S3 = 'S3';
    const DEVICE_DO_SPACES = 'DOSpaces';
    const DEVICE_WASABI = 'Wasabi';
    const DEVICE_BACKBLAZE = 'BackBlaze';
    const DEVICE_LINODE= 'Linode';


    /**
     * Devices.
     *
     * List of all available storage devices
     *
     * @var array
     */
    public static array $devices = [];

    /**
     * Set Device.
     *
     * Add device by name
     *
     * @param string $name
     * @param Device $device
     *
     * @return void
     *@throws Exception
     *
     */
    public static function setDevice(string $name, Device $device): void
    {
        self::$devices[$name] = $device;
    }

    /**
     * Get Device.
     *
     * Get device by name
     *
     * @param string $name
     *
     * @return Device
     *
     * @throws Exception
     */
    public static function getDevice(string $name): Device
    {
        if (!\array_key_exists($name, self::$devices)) {
            throw new Exception('The device "'.$name.'" is not listed');
        }

        return self::$devices[$name];
    }

    /**
     * Exists.
     *
     * Checks if given storage name is registered or not
     *
     * @param string $name
     *
     * @return bool
     */
    public static function exists(string $name): bool
    {
        return (bool) \array_key_exists($name, self::$devices);
    }

    /**
     * Human readable data size format from bytes input.
     *
     * Based on: https://stackoverflow.com/a/38659168/2299554
     *
     * @param int $bytes
     * @param int $decimals
     * @param string $system
     *
     * @return string
     */
    public static function human(int $bytes, int $decimals = 2, string $system = 'metric'): string
    {
        $mod = ($system === 'binary') ? 1024 : 1000;

        $units = array(
            'binary' => array(
                'B',
                'KiB',
                'MiB',
                'GiB',
                'TiB',
                'PiB',
                'EiB',
                'ZiB',
                'YiB',
            ),
            'metric' => array(
                'B',
                'kB',
                'MB',
                'GB',
                'TB',
                'PB',
                'EB',
                'ZB',
                'YB',
            ),
        );

        $factor = (int)floor((strlen((string)$bytes) - 1) / 3);

        return sprintf("%.{$decimals}f%s", $bytes / pow($mod, $factor), $units[$system][$factor]);
    }
}
