<?php

namespace Utopia\Storage;

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

    const DEVICE_SCALITY = 'scality';

    /**
     * Devices.
     *
     * List of all available storage devices
     *
     * @var array<string, Device>
     */
    public static array $devices = [];

    /**
     * Set Device.
     *
     * Add device by name
     *
     * @param  string  $name
     * @param  Device  $device
     * @return void
     *
     * @throws \Exception
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
     * @param  string  $name
     * @return Device
     *
     * @throws \Exception
     */
    public static function getDevice(string $name): Device
    {
        if (! isset(self::$devices[$name])) {
            throw new \Exception('The device "'.$name.'" is not listed');
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
    public static function exists(string $name): bool
    {
        return isset(self::$devices[$name]);
    }

    /**
     * Human-readable data size format from bytes input.
     *
     * Based on: https://stackoverflow.com/a/38659168/2299554
     *
     * @param  int  $bytes
     * @param  int  $decimals
     * @param  string  $system
     * @return string
     */
    public static function human(int $bytes, int $decimals = 2, string $system = 'metric'): string
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
