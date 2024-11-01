<?php
/**
 * package: Toristy For WordPress
 *
 * Plugin Name: Toristy
 * Plugin URI: https://toristy.com/toristy-for-wordpress
 * Description: Easily add Toristy reservation content and widget to your WordPress site.
 * Version: 2.0.1
 * Author: Toristy Oy
 * Author URI: https://toristy.com
 */

use Toristy\Cores\Plugin;

// file called directly not allowed!!!
defined('ABSPATH') or die('Humans are not allowed here!');

/*
 * Autoload classes.
 * Only classes within this plugins are loaded here.
 * Namespace have to match after folder name replacement is done.
 */
spl_autoload_register(
    function ($class)
    {
        if (strpos($class, "Toristy") === false) {
            return;
        }
        $class = str_replace("Toristy", "Sources", $class);
        $class = str_replace("\\", "/", $class);
        $path  = __DIR__."/$class.php";
        if (file_exists($path)) {
            require_once($path);
        }
    }
);

if (class_exists('Toristy\Cores\Plugin')) {
    /*
    * Activation of plugin.
    */
    function ToristyApiActivate()
    {
        Plugin::Activate();
    }

    register_activation_hook(__FILE__, 'ToristyApiActivate');

    /*
     * Deactivation of plugin.
     */
    function ToristyApiDeactivate()
    {
        Plugin::Deactivate();
    }

    register_deactivation_hook(__FILE__, 'ToristyApiDeactivate');

    /*
     * uninstall of plugin.
     */
    function ToristyApiUninstall()
    {
        Plugin::Uninstall();
    }

    register_uninstall_hook(__FILE__, 'ToristyApiUninstall');

    //Create instance of plugin.
    Plugin::Init();
}