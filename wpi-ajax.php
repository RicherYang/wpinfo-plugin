<?php
class RY_WPI_Ajax
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wp_ajax_wpi_get_website_info', [__CLASS__, 'get_website_info']);
            add_action('wp_ajax_wpi_get_website_theme_plugin', [__CLASS__, 'get_website_theme_plugin']);
            //add_action('wp_ajax_wpi_get_website_tag', [__CLASS__, 'get_website_tag']);

            add_action('wp_ajax_wpi_get_plugin_info', [__CLASS__, 'get_plugin_info']);
        }
    }

    public static function get_website_info()
    {
        $site_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($site_ID) == 'website') {
            do_action('wpi/get_website_info', $site_ID, false);
        }
    }

    public static function get_website_theme_plugin()
    {
        $site_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($site_ID) == 'website') {
            do_action('wpi/get_website_theme_plugin', $site_ID);
        }
    }

    public static function get_website_tag()
    {
        $site_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $post_type = get_post_type($site_ID);
        if (in_array($post_type, ['website'])) {
            do_action('wpi/get_website_tag', $site_ID);
        }
    }

    public static function get_plugin_info()
    {
        $plugin_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $post_type = get_post_type($plugin_ID);
        if (in_array($post_type, ['theme', 'plugin'])) {
            do_action('wpi/get_' . $post_type . '_info', $plugin_ID);
        }
    }
}

RY_WPI_Ajax::init();
