<?php
class RY_WPI_Sitemap
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_filter('wp_sitemaps_add_provider', [__CLASS__, 'remove_user_sitemap'], 10, 2);
            add_filter('wp_sitemaps_posts_entry', [__CLASS__, 'add_mod_time'], 10, 2);
        }
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
}

RY_WPI_Sitemap::init();
