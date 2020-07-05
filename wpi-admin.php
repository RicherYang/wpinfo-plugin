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
            add_action('add_meta_boxes_website', [__CLASS__, 'website_meta_box']);
            add_action('add_meta_boxes_plugin', [__CLASS__, 'plugin_meta_box']);
            add_action('add_meta_boxes_theme', [__CLASS__, 'plugin_meta_box']);
        }
    }

    public static function website_meta_box()
    {
        wp_enqueue_style('wpi-meta_box-script');
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('website_info', '基本資訊', [__CLASS__, 'website_info'], null, 'normal');
        add_meta_box('website_action', 'WEI 操作', [__CLASS__, 'website_action'], null, 'side');
    }

    public static function plugin_meta_box()
    {
        wp_enqueue_style('wpi-meta_box-script');
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('plugin_info', '基本資訊', [__CLASS__, 'plugin_info'], null, 'normal');
        add_meta_box('plugin_action', 'WEI 操作', [__CLASS__, 'plugin_action'], null, 'side');
    }

    public static function website_info($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/website_info.php';
    }

    public static function website_action($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/website_action.php';
    }

    public static function plugin_info($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/plugin_info.php';
    }

    public static function plugin_action($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/plugin_action.php';
    }

    public static function check_schedule()
    {
        wp_register_style('wpi-meta_box-script', RY_WPI_PLUGIN_URL . '/assets/css/meta_box.css', [], RY_WPI_VERSION);
        wp_register_script('wpi-meta_box-script', RY_WPI_PLUGIN_URL . '/assets/js/meta_box.js', ['jquery'], RY_WPI_VERSION, true);

        if (!as_next_scheduled_action('wei/reget_theme_plugin_info')) {
            as_schedule_recurring_action(time(), 3600, 'wei/reget_theme_plugin_info');
        }

        if (!as_next_scheduled_action('wei/reget_website_theme_plugin')) {
            as_schedule_recurring_action(time(), 3600, 'wei/reget_website_theme_plugin');
        }
    }
}

RY_WPI_Admin::init();
