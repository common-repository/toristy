<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers;


class Domain
{
    private static $Paths = [];

    public static function Load() : void
    {
        if (empty(self::$Paths)) {
            self::$Paths = [
                "base" => plugin_basename(dirname(__FILE__, 3)).'/toristy-booking-plugin.php',
                "url"  => plugin_dir_url(dirname(__FILE__, 2)),
                "path" => plugin_dir_path(dirname(__FILE__, 2))
            ];
        }
    }

    /**
     * Plugin base url
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function Base(string $path): string
    {
        $path = (isset($path) && is_string($path)) ? $path : "";
        $base = (isset(self::$Paths["base"])) ? self::$Paths["base"] : "";

        return ($path !== "" && $path[0] === "/") ? rtrim($base, "/").$path : $base.$path;
    }

    /**
     * Plugin directory path
     *
     * @param  string  $path
     *
     * @return string
     */
    public static function Path(string $path): string
    {
        $path = (isset($path) && is_string($path)) ? $path : "";
        $base = (isset(self::$Paths["path"])) ? self::$Paths["path"] : "";

        return ($path !== "" && $path[0] === "/") ? rtrim($base, "/").$path : $base.$path;
    }

    /**
     * Plugin directory url
     *
     * @param  string  $url
     *
     * @return string
     */
    public static function Url(string $url): string
    {
        $url  = (isset($url) && is_string($url)) ? $url : "";
        $base = (isset(self::$Paths["url"])) ? self::$Paths["url"] : "";

        return ($url !== "" && $url[0] === "/") ? rtrim($base, "/").$url : $base.$url;
    }

    public static function PageUrl(string $url): string
    {
        return home_url($url);
    }

    /**
     * @param  string  $path
     *
     * @return string
     */
    public static function RequireBuffer(string $path): string
    {
        $data = "";
        if (file_exists($path)) {
            ob_start();
            require_once $path;
            $data = ob_get_clean();
        }

        return $data;
    }

    public static function GetContent(string $url): string
    {
        if ($url !== '') {
            return file_get_contents($url);
        }
        return '';
    }

    /**
     * Redirect to a page.
     *
     * @param  string  $url  page url
     * @param  bool  $force  force redirect with JavaScript. true.
     */
    public static function Redirect(string $url, bool $force = false): void
    {
        $url = home_url($url);
        if ( ! $force) {
            wp_redirect($url);
        } else {
            echo "<script type='text/javascript'>window.location.href='$url';</script>";
        }
        exit();
    }
}