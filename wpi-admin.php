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

            add_action('save_post_plugin', [__CLASS__, 'get_theme_plugin_info'], 10, 3);
            add_action('save_post_theme', [__CLASS__, 'get_theme_plugin_info'], 10, 3);
            add_action('add_meta_boxes_website', [__CLASS__, 'website_meta_box']);
            add_action('add_meta_boxes_plugin', [__CLASS__, 'plugin_meta_box']);
            add_action('add_meta_boxes_theme', [__CLASS__, 'plugin_meta_box']);
            add_action('delete_post', [__CLASS__, 'delete_post_info']);
        }
    }

    public static function get_theme_plugin_info($post_ID, $post, $update)
    {
        if (!$update) {
            update_post_meta($post_ID, 'used_count', 0);
            update_post_meta($post_ID, 'rest_key', '');
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

    public static function delete_post_info($post_ID)
    {
        $post_type = get_post_type($post_ID);
        $post_query = new WP_Query();
        $post_query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_key' => $post_type,
            'meta_value' => $post_ID,
            'orderby' => 'modified',
            'order' => 'DESC',
            'posts_per_page' => '-1'
        ]);
        while ($post_query->have_posts()) {
            $post_query->the_post();

            delete_post_meta(get_the_ID(), $post_type, $post_ID);
        }
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
            as_schedule_recurring_action(time(), 600, 'wei/reget_theme_plugin_info');
        }

        if (!as_next_scheduled_action('wei/reget_website_theme_plugin')) {
            as_schedule_recurring_action(time(), 600, 'wei/reget_website_theme_plugin');
        }
    }
}

RY_WPI_Admin::init();
