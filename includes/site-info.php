<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_SiteInfo
{
    public static $rest_namespace_map = [
        'akismet' => 'akismet',
        'jetpack' => 'jetpack',
        'wc' => 'woocommerce',
        'yoast' => 'wordpress-seo',
        'sowb' => 'so-widgets-bundle',
        'rankmath' => 'seo-by-rank-math',
        'tweet-old-post' => 'tweet-old-post',
        'google-site-kit' => 'google-site-kit',
        'mxp_fb2wp' => 'fb2wp-integration-tools',
        'siteground-optimizer' => 'sg-cachepress'
    ];

    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_filter('wei/add_theme', [__CLASS__, 'theme_from_file_name'], 10, 3);
            add_filter('wei/add_plugin', [__CLASS__, 'plugin_from_file_name'], 10, 3);

            add_filter('wei/add_plugin', [__CLASS__, 'plugin_from_rest'], 10, 2);
        }
    }

    public static function theme_from_file_name($themes, $site_ID, $body)
    {
        return self::from_file_name($themes, $site_ID, $body, 'themes');
    }

    public static function plugin_from_file_name($plugins, $site_ID, $body)
    {
        return self::from_file_name($plugins, $site_ID, $body, 'plugins');
    }

    protected static function from_file_name($list, $site_ID, &$body, $dir_name)
    {
        $url = get_field('url', $site_ID);
        preg_match_all('@' . $url . '/[^\'"]*/' . $dir_name . '/([a-z0-9\-\_]*)/@iU', $body, $matches);

        if (isset($matches[1])) {
            $list = array_merge($list, $matches[1]);
        }

        return $list;
    }

    public static function plugin_from_rest($plugins, $site_ID)
    {
        $rest_api = get_field('rest_api', $site_ID);
        $rest_api = explode(',', $rest_api);

        foreach ($rest_api as $rest_namespace) {
            $namespace = explode('/', $rest_namespace);
            if (isset($namespace[0])) {
                if (isset(self::$rest_namespace_map[$namespace[0]])) {
                    $plugins[] = self::$rest_namespace_map[$namespace[0]];
                }
            }
        }
        return $plugins;
    }
}

RY_WPI_SiteInfo::init();
