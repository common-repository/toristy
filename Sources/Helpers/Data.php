<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;


abstract class Data
{
    public static function Pull(string $key, $default)
    {
        $value = static::Get("$key", "");
        if (isset($value)) {
            static::Remove($key);

            return $value;
        }

        return $default;
    }

    public static function Getx(string $key, $default)
    {
        $data = static::Load();
        if (empty($data)) {
            return $default;
        }
        $found = false;
        $names = (strpos($key, "/") !== false) ? explode("/", $key) : [$key];
        foreach ($names as $name) {
            if (isset($data[$name]) && $data[$name] !== "") {
                $data  = $data[$name];
                $found = true;
                continue;
            }
            $found = false;
        }

        return ($found) ? $data : $default;
    }

    public static function Get(string $key, $default)
    {
        $data = static::Load();
        if (empty($data)) {
            return $default;
        }
        $names = (strpos($key, "/") !== false) ? explode("/", $key) : [$key];
        $last = array_pop($names);
        if (empty($names) && isset($last) && isset($data[$last])) {
            return $data[$last];
        }
        $temp = [];
        foreach ($names as $name) {
            if (isset($data[$name]) && $data[$name] !== "" && $name !== $last) {
                $temp  = $data[$name];
            }
        }
        return (isset($temp[$last])) ? $temp[$last] : $default;
    }

    protected static abstract function Load(): array;

    public abstract static function Remove(string $key);

    public static function Pick(array $keys, string $prefix = "", bool $skip = false): array
    {
        $prefix = ($prefix !== "") ? "$prefix/" : "";
        $data   = [];
        foreach ($keys as $key) {
            $value = static::Get("$prefix$key", "");
            if (isset($value) && $value === "" && !$skip) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public static function All(): array
    {
        return static::Load();
    }

    public abstract static function Clear();

    public static function Has(string $key)
    {
        $data = static::Load();

        return array_key_exists($key, $data);
    }
}