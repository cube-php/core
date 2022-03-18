<?php

namespace Cube\Tools;

use Cube\Http\Cookie;

class DeviceIdentifier
{
    /**
     * Auth cookie device name
     *
     * @var string
     */
    private static $_device_name = 'device_fingerprint_id';

    /**
     * Get auth device id
     *
     * @return string
     */
    public static function getDeviceId(): ?string
    {
        return Cookie::getOrSet(static::$_device_name, function () {
            return generate_token(20);
        });
    }
}