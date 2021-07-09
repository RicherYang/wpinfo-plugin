<?php
class RY_WPI_Ajax
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wp_ajax_wpi_get_website_info', [__CLASS__, 'get_website_info']);
            add_action('wp_ajax_wpi_get_website_category', [__CLASS__, 'get_website_category']);

            add_action('wp_ajax_wpi_get_plugin_info', [__CLASS__, 'get_plugin_info']);
            add_action('wp_ajax_wpi_get_theme_info', [__CLASS__, 'get_theme_info']);
        }
    }

    public static function get_website_info()
    {
        $website_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($website_ID) == 'website') {
            RY_WPI_Website::get_basic_info($website_ID);
        }
    }

    public static function get_website_category()
    {
        $website_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $post_type = get_post_type($website_ID);
        if (in_array($post_type, ['website'])) {
            RY_WPI_Website::get_category_list($website_ID);
        }
    }

    public static function get_plugin_info()
    {
        $plugin_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($plugin_ID) == 'plugin') {
            RY_WPI_Plugin::get_basic_info($plugin_ID);
        }
    }

    public static function get_theme_info()
    {
        $theme_ID = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (get_post_type($theme_ID) == 'theme') {
            RY_WPI_Theme::get_basic_info($theme_ID);
        }
    }
}

RY_WPI_Ajax::init();
