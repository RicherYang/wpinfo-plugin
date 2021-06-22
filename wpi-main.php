<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI
{
    public static $option_prefix = 'RY_WPI_';

    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            include_once RY_WPI_PLUGIN_DIR . 'includes/composer/vendor/autoload.php';
            include_once RY_WPI_PLUGIN_DIR . 'includes/site-info.php';
            include_once RY_WPI_PLUGIN_DIR . 'includes/sitemap.php';

            include_once RY_WPI_PLUGIN_DIR . 'wpi-cron.php';
            include_once RY_WPI_PLUGIN_DIR . 'wpi-ajax.php';

            if (is_admin()) {
                include_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler.php';

                include_once RY_WPI_PLUGIN_DIR . 'wpi-update.php';
                include_once RY_WPI_PLUGIN_DIR . 'wpi-admin.php';

                add_action('init', ['RY_WPI_update', 'update']);
            }

            add_filter('xmlrpc_enabled', '__return_false');

            add_action('init', [__CLASS__, 'do_init'], 1);
            add_action('init', [__CLASS__, 'register_post_type'], 9);

            add_action('rest_api_init', [__CLASS__, 'initial_rest_routes'], 100);

            include_once RY_WPI_PLUGIN_DIR . 'includes/composer/vendor/woocommerce/action-scheduler/action-scheduler.php';
        }
    }

    public static function do_init()
    {
        load_plugin_textdomain('wpinfo-plugin', false, plugin_basename(RY_WPI_PLUGIN_DIR) . '/languages');

        if (is_admin()) {
            return ;
        }

        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('template_redirect', 'wp_shortlink_header', 11, 0);
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);

        add_filter('get_the_generator_html', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_xhtml', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_comment', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_atom', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_rss2', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_rdf', [__CLASS__, 'hide_version']);
        add_filter('get_the_generator_export', [__CLASS__, 'hide_version']);

        add_filter('rest_queried_resource_route', '__return_empty_string');
        add_filter('feed_links_show_comments_feed', '__return_false');
    }

    public static function register_post_type()
    {
        register_post_type('website', [
            'labels' => [
                'name' => '網站',
                'add_new' => '新增網站資訊',
                'add_new_item' => '新增網站資訊',
                'search_items' => '搜尋網站資訊',
            ],
            'public' => true,
            'hierarchical' => false,
            'has_archive' => true,
            'show_in_admin_bar' => false,
            'supports' => ['title', 'excerpt', 'custom-fields'],
            'taxonomies' => ['category', 'post_tag']
        ]);

        register_post_type('plugin', [
            'labels' => [
                'name' => '網站 外掛',
                'add_new' => '新增外掛',
                'add_new_item' => '新增外掛',
                'search_items' => '搜尋外掛',
            ],
            'public' => true,
            'hierarchical' => false,
            'has_archive' => true,
            'show_in_admin_bar' => false,
            'supports' => ['title', 'custom-fields'],
            'taxonomies' => ['category', 'post_tag']
        ]);

        register_post_type('theme', [
            'labels' => [
                'name' => '網站 佈景主題',
                'add_new' => '新增佈景主題',
                'add_new_item' => '新增佈景主題',
                'search_items' => '搜尋佈景主題',
            ],
            'public' => true,
            'hierarchical' => false,
            'has_archive' => true,
            'show_in_admin_bar' => false,
            'supports' => ['title', 'custom-fields'],
            'taxonomies' => ['category', 'post_tag']
        ]);

        /*
        register_taxonomy('website-tag', ['website'], [
            'label' => '網站 標籤',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false
        ]);
        */
    }

    public static function initial_rest_routes()
    {
        include RY_WPI_PLUGIN_DIR . '/rest-api/v1/site.php';

        $controller = new RY_WPI_V1_Site_Controller;
        $controller->register_routes();
    }

    public static function hide_version($gen)
    {
        $version = get_bloginfo('version');

        $gen = str_replace('?v=' . $version, '', $gen);
        $gen = str_replace(' ' . $version, '', $gen);
        $gen = str_replace($version, '', $gen);

        return $gen;
    }

    public static function get_option($option, $default = false)
    {
        return get_option(self::$option_prefix . $option, $default);
    }

    public static function update_option($option, $value)
    {
        return update_option(self::$option_prefix . $option, $value);
    }

    public static function plugin_activation()
    {
    }

    public static function plugin_deactivation()
    {
    }

    public static function plugin_uninstall()
    {
    }
}
