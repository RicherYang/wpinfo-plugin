<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Ajax
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wp_ajax_wei_get_info', [__CLASS__, 'get_info']);
            add_action('wp_ajax_wei_get_theme_plugin', [__CLASS__, 'get_theme_plugin']);
        }
    }

    public static function get_info()
    {
        $site_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($site_ID) == 'site') {
            do_action('wei/get_info', $site_ID);
        }
    }

    public static function get_theme_plugin()
    {
        $site_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($site_ID) == 'site') {
            do_action('wei/get_site_theme_plugin', $site_ID);
        }
    }
}

RY_WPI_Ajax::init();
