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

            require_once RY_WPI_PLUGIN_DIR . 'includes/composer/vendor/autoload.php';
            require_once RY_WPI_PLUGIN_DIR . 'includes/composer/vendor/woocommerce/action-scheduler/action-scheduler.php';
            require_once RY_WPI_PLUGIN_DIR . 'includes/site-info.php';

            require_once RY_WPI_PLUGIN_DIR . 'wpi-cron.php';
            require_once RY_WPI_PLUGIN_DIR . 'wpi-ajax.php';

            if (is_admin()) {
                require_once RY_WPI_PLUGIN_DIR . 'wpi-update.php';
                add_action('init', ['RY_WPI_update', 'update']);

                require_once RY_WPI_PLUGIN_DIR . 'wpi-admin.php';
            } else {
                require_once RY_WPI_PLUGIN_DIR . 'includes/seo.php';

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

                add_filter('action_scheduler_queue_runner_time_limit', [__CLASS__, 'as_to_60']);

                add_filter('rest_queried_resource_route', '__return_empty_string');
                add_filter('wp_sitemaps_stylesheet_url', '__return_empty_string');
                add_filter('wp_sitemaps_stylesheet_index_url', '__return_empty_string');
                add_filter('wp_sitemaps_add_provider', [__CLASS__, 'remove_user_sitemap'], 10, 2);
                add_filter('wp_sitemaps_posts_entry', [__CLASS__, 'add_mod_time'], 10, 2);


                add_filter('feed_links_show_comments_feed', '__return_false');
                add_filter('show_admin_bar', '__return_false');
            }

            add_filter('xmlrpc_enabled', '__return_false');

            add_action('init', [__CLASS__, 'register_post_type'], 9);
            add_action('rest_api_init', [__CLASS__, 'initial_rest_routes'], 100);
        }
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

        register_post_type('remote_log', [
            'label' => '抓取紀錄',
            'public' => false,
            'hierarchical' => false,
            'show_ui' => true,
            'show_in_admin_bar' => false,
            'supports' => ['title', 'editor']
        ]);

        register_taxonomy('website-tag', ['website'], [
            'label' => '網站 標籤',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'hierarchical' => false
        ]);
    }

    public static function initial_rest_routes()
    {
        include RY_WPI_PLUGIN_DIR . '/rest-api/site.php';

        $controller = new RY_WPI_Site_Controller;
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

    public static function as_to_60()
    {
        return 60;
    }

    public static function remove_user_sitemap($provider, $name)
    {
        if ($name == 'users') {
            return new stdClass;
        }
        return $provider;
    }

    public static function add_mod_time($sitemap_entry, $post)
    {
        $sitemap_entry['lastmod'] = get_post_modified_time('c', false, $post);
        return $sitemap_entry;
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
