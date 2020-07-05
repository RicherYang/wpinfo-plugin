<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('init', [__CLASS__, 'check_schedule'], 20);
            add_action('add_meta_boxes_site', [__CLASS__, 'add_meta_box']);
        }
    }

    public static function add_meta_box()
    {
        wp_register_script('wpi-site-script', RY_WPI_PLUGIN_URL . '/js/site.js', ['jquery'], RY_WPI_VERSION, true);

        add_meta_box('site_action', '網站動作', [__CLASS__, 'site_action'], null, 'side');
    }

    public static function site_action($post)
    {
        wp_enqueue_script('wpi-site-script');

        include RY_WPI_PLUGIN_DIR . 'html/meta_box/site_action.php';
    }

    public static function check_schedule()
    {
        if (!as_next_scheduled_action('wei/reget_theme_plugin_info')) {
            as_schedule_recurring_action(time(), 3600, 'wei/reget_theme_plugin_info');
        }

        if (!as_next_scheduled_action('wei/reget_site_theme_plugin')) {
            as_schedule_recurring_action(time(), 3600, 'wei/reget_site_theme_plugin');
        }
    }
}

RY_WPI_Admin::init();
