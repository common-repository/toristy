<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Helpers
{

    class Hook
    {
        /**
         * @var array
         */
        private static $Data = [];
        /**
         * @var array
         */
        private static $Skips = [];

        /**
         * @param  string  $key
         * @param  string  $type  filter / action
         * @param  array  $data
         * @param  int  $order
         * @param  int  $params
         */
        public static function Add(string $key, string $type, array $data, int $order = 10, int $params = 1)
        {
            self::$Data[$type][$key] = ["data" => $data, "order" => $order, "allowParams" => $params];
        }

        public static function Run(): bool
        {
            return self::RunQueue();
        }

        private static function RunQueue(): bool
        {
            foreach (self::$Data as $name => $actions)
            {
                foreach ($actions as $skip => $action)
                {
                    if (in_array($skip, self::$Skips)) { continue; }
                    if (!has_filter($name, $action["data"]))
                    {
                        add_filter($name, $action["data"], $action["order"], $action["allowParams"]);
                    }
                }
            }
            return true;
        }

        /**
         * This only works before Run is called.
         *
         * @param  string  $name
         *
         * @return bool
         */
        public static function Remove(string $name): bool
        {
            if (isset($name) && $name !== "")
            {
                self::$Skips[] = $name;
                return true;
            }
            return false;
        }
    }
}