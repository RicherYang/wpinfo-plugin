<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Cron
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wpi/get_info', [__CLASS__, 'get_info'], 10, 2);
            add_action('wpi/get_website_theme_plugin', [__CLASS__, 'get_website_theme_plugin']);
            add_action('wpi/get_theme_info', [__CLASS__, 'get_theme_info']);
            add_action('wpi/get_plugin_info', [__CLASS__, 'get_plugin_info']);

            add_action('wpi/reget_info', [__CLASS__, 'reget_info']);
            add_action('wpi/reget_theme_plugin_info', [__CLASS__, 'reget_theme_plugin_info']);
        }
    }

    public static function get_info($site_ID, $auto_next = true)
    {
        if (get_post_type($site_ID) != 'website') {
            return;
        }

        $site_name = '';
        $url = get_post_meta($site_ID, 'url', true);
        $rest_url = get_post_meta($site_ID, 'rest_url', true);

        $body = self::remote_get($url);
        if (!empty($body)) {
            if (empty($rest_url)) {
                $link_cat = strpos($body, 'https://api.w.org/');
                if ($link_cat !== false) {
                    $rest_url = substr($body, $link_cat + 18, 250);

                    $link_cat = strpos($rest_url, 'href=');
                    $rest_url = substr($rest_url, $link_cat + 5);

                    $end = substr($rest_url, 0, 1);
                    $rest_url = substr($rest_url, 1, strpos($rest_url, $end, 1) - 1);
                }
                if (filter_var($rest_url, FILTER_VALIDATE_URL) === false) {
                    $rest_url = $url . '/wp-json';
                }
            }

            $site_name = self::use_rest_get_site_name($rest_url, $site_ID);

            $domain = parse_url($url, PHP_URL_HOST);
            preg_match_all('@(/[a-z0-9\-\_\./]*/)(themes|plugins)/@iU', $body, $matches);
            if (isset($matches[1])) {
                $dir_list = array_unique($matches[1]);
                $min_dir_len = PHP_INT_MAX;
                $find_same_domain = false;
                $content_path = '';
                foreach ($dir_list as $dir) {
                    if (parse_url($dir, PHP_URL_HOST) == $domain) {
                        if ($find_same_domain === false) {
                            $min_dir_len = PHP_INT_MAX;
                            $find_same_domain = true;
                        }
                        if (strlen($dir) < $min_dir_len) {
                            $content_path = $dir;
                            $min_dir_len = strlen($dir);
                        }
                    }
                    if ($find_same_domain === false) {
                        if (strlen($dir) < $min_dir_len) {
                            $content_path = $dir;
                            $min_dir_len = strlen($dir);
                        }
                    }
                }
                if (!empty($content_path)) {
                    update_post_meta($site_ID, 'content_path', 'https:' . $content_path);
                }
            }
        }

        if (empty($site_name)) {
            $site_name = self::use_feed_get_site_name($url . '/feed', $site_ID);
        }

        if (!empty($site_name)) {
            wp_update_post([
                'ID' => $site_ID,
                'post_title' => $site_name,
                'post_status' => 'publish'
            ]);

            if ($auto_next) {
                as_schedule_single_action(time(), 'wpi/get_website_theme_plugin', [$site_ID]);
            }
        }
    }

    protected static function use_rest_get_site_name($rest_url, $site_ID)
    {
        $body = self::remote_get($rest_url);
        if (!empty($body)) {
            $data = @json_decode($body, true);

            if ($data) {
                update_post_meta($site_ID, 'description', $data['description']);
                update_post_meta($site_ID, 'rest_url', $rest_url);
                update_post_meta($site_ID, 'rest_api', implode(',', $data['namespaces']));

                return $data['name'];
            }
        }

        return '';
    }

    protected static function use_feed_get_site_name($feed_url, $site_ID)
    {
        $post_status = get_post_status($site_ID);
        $body = self::remote_get($feed_url);
        if (!empty($body)) {
            $xml = simplexml_load_string($body);

            if ($xml && isset($xml->channel)) {
                $use_wp = $post_status === 'publish';
                if ($use_wp === false) {
                    if (isset($xml->channel->generator)) {
                        $generator = (string) $xml->channel->generator;
                        $use_wp = strpos($generator, 'https://wordpress.org/');
                    }
                }

                if ($use_wp) {
                    update_post_meta($site_ID, 'description', (string) $xml->channel->description);

                    return (string) $xml->channel->title;
                }
            }
        }

        return '';
    }

    public static function get_website_theme_plugin($site_ID)
    {
        if (get_post_type($site_ID) != 'website') {
            return;
        }

        $url = get_post_meta($site_ID, 'url', true);
        $body = self::remote_get($url);
        if (!empty($body)) {
            $themes = apply_filters('wpi/add_theme', [], $site_ID, $body);
            RY_WPI_SiteInfo::add_site_info($site_ID, 'theme', $themes);

            $plugins = apply_filters('wpi/add_plugin', [], $site_ID, $body);
            RY_WPI_SiteInfo::add_site_info($site_ID, 'plugin', $plugins);
        }

        wp_update_post([
            'ID' => $site_ID
        ]);
    }

    public static function get_theme_info($theme_ID)
    {
        if (get_post_type($theme_ID) != 'theme') {
            return;
        }

        $theme_slug = get_post_field('post_name', $theme_ID, 'raw');

        $response = wp_remote_get('https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=' . $theme_slug);
        if (is_wp_error($response)) {
            return;
        }
        if (200 != wp_remote_retrieve_response_code($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = @json_decode($body, true);
        if ($data && isset($data['name'])) {
            wp_update_post([
                'ID' => $theme_ID,
                'post_title' => $data['name']
            ]);
            if (isset($data['tags'])) {
                wp_set_post_tags($theme_ID, array_values($data['tags']));
            }

            update_post_meta($theme_ID, 'at_org', 1);
            update_post_meta($theme_ID, 'url', $data['homepage']);
            $version = get_post_meta('version', $theme_ID, true);
            if (version_compare($version, $data['version'], '<')) {
                update_post_meta($theme_ID, 'version', $data['version']);
            }
        }
    }

    public static function get_plugin_info($plugin_ID)
    {
        if (get_post_type($plugin_ID) != 'plugin') {
            return;
        }
        $plugin_slug = get_post_field('post_name', $plugin_ID, 'raw');

        $response = wp_remote_get('https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json');
        if (is_wp_error($response)) {
            return;
        }
        if (200 != wp_remote_retrieve_response_code($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = @json_decode($body, true);
        if ($data && isset($data['name'])) {
            wp_update_post([
                'ID' => $plugin_ID,
                'post_title' => $data['name']
            ]);
            if (isset($data['tags'])) {
                wp_set_post_tags($plugin_ID, array_values($data['tags']));
            }

            update_post_meta($plugin_ID, 'at_org', 1);
            update_post_meta($plugin_ID, 'url', $data['homepage']);
            $version = get_post_meta('version', $plugin_ID, true);
            if (version_compare($version, $data['version'], '<')) {
                update_post_meta($plugin_ID, 'version', $data['version']);
            }
        }
    }

    public static function reget_info()
    {
        $checkdate = new DateTime('', wp_timezone());
        $checkdate->sub(new DateInterval('P2D'));

        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        while ($query->have_posts()) {
            $query->the_post();
            do_action('wpi/get_info', get_the_ID());
        }
    }

    public static function reget_theme_plugin_info()
    {
        $query = new WP_Query();
        $query->query([
            'post_type' => ['theme', 'plugin'],
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        while ($query->have_posts()) {
            $query->the_post();
            do_action('wpi/get_' . get_post_type() . '_info', get_the_ID());
        }
    }

    public static function remote_get($url)
    {
        $response = wp_remote_get($url, [
            'timeout' => 10
        ]);

        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                return wp_remote_retrieve_body($response);
            } else {
                wp_insert_post([
                    'post_type' => 'remote_log',
                    'post_title' => wp_remote_retrieve_response_code($response) . ' ' . $url,
                    'post_status' => 'publish',
                    'post_content' => wp_remote_retrieve_body($response)
                ]);
            }
        } else {
            wp_insert_post([
                'post_type' => 'remote_log',
                'post_title' => 'Error ' . $url,
                'post_status' => 'publish',
                'post_content' => wp_remote_retrieve_body($response->get_error_messages())
            ]);
        }
        return '';
    }
}

RY_WPI_Cron::init();
