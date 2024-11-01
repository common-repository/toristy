<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores;


use Toristy\Helpers\Data;

class Option extends Data
{
    private static $Name = "toristy_api_plugin";
    
    private static $Data = [];

    /**
     * @param  string  $key
     * @param $default
     * @param  bool  $top  Top level: true / Plugin level: false
     * get directly or within plugin.
     *
     * @return array|mixed|string|void
     */
    public static function Getx(string $key, $default, bool $top = false)
    {
        if ($key === "") {
            return $default;
        }
        $data = $default;
        if ($top) {
            $temp = get_option($key, $default);
            if ($temp !== "") {
                $data = $temp;
            }
        } else {
            $data = parent::Get($key, $default);
            //var_dump($data);
        }

        return self::Check($data);
    }

    public static function Get(string $key, $default, bool $top = false)
    {
        if ($key === "") {
            return $default;
        }
        $data = $default;
        if ($top) {
            $data = self::GetTop($key, $default);
        } else {
            $data = parent::Get($key, $default);
            //var_dump($data);
        }

        return self::Check($data);
    }

    private static function GetTop(string $key, $default)
    {
        $names = (strpos($key, "/") !== false) ? explode("/", $key) : [$key];
        $last = array_pop($names);
        if (empty($names) && isset($last)) {
            return get_option($last, $default);
        }
        $temp = [];
        foreach ($names as $name) {
            if ($name !== $last) {
                if ($names[0] === $name) {
                    $temp = get_option($name, []);
                } else {
                    $temp = (isset($temp[$name])) ? $temp[$name] : [];
                }
            }
        }
        return (isset($temp[$last])) ? $temp[$last] : $default;
    }

    private static function Check($data)
    {
        if (is_string($data) && $data !== "") {
            return esc_attr($data);
        }

        return $data;
    }

    public static function Clear()
    {
        if (!empty(self::$Data)) {
            $bol = update_option(self::$Name, []);
            if ($bol) {
                self::$Data = [];
            }
        }
    }

    public static function ClearStartWith(string $name): bool
    {
        $options = self::Load();
        $count = strlen($name);
        if (empty($options) || $count <= 0) { return true; }
        $bol = false;
        $n = strtolower($name);
        foreach ($options as $key => $option) {
            if (strpos($key, $n) !== -1) {
                unset($options[$key]);
                unset(self::$Data[$key]);
                $bol = true;
            }
        }
        if ($bol) {
            update_option(self::$Name, $options);
        }
        return $bol;
    }

    /**
     * @param  string  $key
     * @param  bool  $top  Top level: true
     *
     * @return bool
     */
    public static function Remove(string $key, bool $top = false): bool
    {
        $bol = true;
        if ($top) {
            return delete_option($key);
        }
        $options = self::Load();
        if (isset($options[$key])) {
            unset($options[$key]);
            $bol = update_option(self::$Name, $options);
            if ($bol) {
                unset(self::$Data[$key]);
            }
        }

        return $bol;
    }

    protected static function Load(): array
    {
        if (empty(self::$Data)) {
            $option     = get_option(self::$Name);
            self::$Data = (is_array($option)) ? $option : [];
        }

        return self::$Data;
    }

    public static function Set(string $key, $value, bool $top = false)
    {
        $bol = false;
        if ($key !== "" && !is_null($value)) {
            $value = self::Check($value);
            if ($top) {
                return update_option($key, $value);
            }
            $options = self::Load();

            $options[$key] = $value;
            $bol           = update_option(self::$Name, $options);
            if ($bol) {
                self::$Data[$key] = $value;
            }
        }

        return $bol;
    }
}