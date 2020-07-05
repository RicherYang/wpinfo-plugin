<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Cron
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wei/get_info', [__CLASS__, 'get_info']);
            add_action('wei/get_site_theme_plugin', [__CLASS__, 'get_site_theme_plugin']);
            add_action('wei/get_theme_info', [__CLASS__, 'get_theme_info']);
            add_action('wei/get_plugin_info', [__CLASS__, 'get_plugin_info']);

            add_action('wei/reget_theme_plugin_info', [__CLASS__, 'reget_theme_plugin_info']);
            add_action('wei/reget_site_theme_plugin', [__CLASS__, 'reget_site_theme_plugin']);
        }
    }

    public static function get_info($site_ID)
    {
        if (get_post_type($site_ID) != 'site') {
            return;
        }

        $site_name = '';
        $url = get_field('url', $site_ID);
        $rest_url = get_field('rest_url', $site_ID);

        if (!empty($rest_url)) {
            $site_name = self::use_rest_get_site_name($rest_url, $site_ID);
        }

        if (empty($site_name)) {
            $response = wp_remote_get($url);
            file_put_contents('D:/123/123.txt', var_export($response, true));
            if (!is_wp_error($response)) {
                if (200 == wp_remote_retrieve_response_code($response)) {
                    $body = wp_remote_retrieve_body($response);

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
                    $site_name = self::use_rest_get_site_name($rest_url, $site_ID);
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

            as_schedule_single_action(time(), 'wei/get_site_theme_plugin', [$site_ID]);
        }
    }

    protected static function use_rest_get_site_name($rest_url, $site_ID)
    {
        $response = wp_remote_get($rest_url);
        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = @json_decode($body, true);

                if ($data) {
                    update_field('description', $data['description'], $site_ID);
                    update_field('rest_url', $rest_url, $site_ID);
                    update_field('rest_api', implode(',', $data['namespaces']), $site_ID);

                    return $data['name'];
                }
            }
        }

        return '';
    }

    protected static function use_feed_get_site_name($feed_url, $site_ID)
    {
        $post_status = get_post_status($site_ID);
        $response = wp_remote_get($feed_url);
        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                $body = wp_remote_retrieve_body($response);
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
                        update_field('description', (string) $xml->channel->description, $site_ID);

                        return (string) $xml->channel->title;
                    }
                }
            }
        }

        return '';
    }

    public static function get_site_theme_plugin($site_ID)
    {
        if (get_post_type($site_ID) != 'site') {
            return;
        }

        $url = get_field('url', $site_ID);
        $response = wp_remote_get($url);
        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                $body = wp_remote_retrieve_body($response);

                $themes = apply_filters('wei/add_theme', [], $site_ID, $body);
                $themes = array_filter(array_unique($themes));
                wp_set_post_terms($site_ID, $themes, 'theme');

                $plugins = apply_filters('wei/add_plugin', [], $site_ID, $body);
                $plugins = array_filter(array_unique($plugins));
                wp_set_post_terms($site_ID, $plugins, 'plugin');
            }
        }

        wp_update_post([
            'ID' => $site_ID
        ]);
    }

    public static function get_theme_info($term_ID)
    {
        $term = get_term($term_ID, 'theme');

        $response = wp_remote_get('https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=' . $term->slug);
        if (is_wp_error($response)) {
            return;
        }
        if (200 != wp_remote_retrieve_response_code($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = @json_decode($body, true);
        if ($data && isset($data['name'])) {
            wp_update_term($term_ID, 'theme', [
                'name' => $data['name']
            ]);
            update_field('at_org', 1, $term);
            update_field('url', $data['homepage'], $term);
            $version = get_field('version', $term);
            if (version_compare($version, $data['version'], '<')) {
                update_field('version', $data['version'], $term);
            }
        }

        update_field('update', current_time('mysql'), $term);
    }

    public static function get_plugin_info($term_ID)
    {
        $term = get_term($term_ID, 'plugin');

        $response = wp_remote_get('https://api.wordpress.org/plugins/info/1.0/' . $term->slug . '.json');
        if (is_wp_error($response)) {
            return;
        }
        if (200 != wp_remote_retrieve_response_code($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = @json_decode($body, true);

        if ($data && isset($data['name'])) {
            wp_update_term($term_ID, 'plugin', [
                'name' => $data['name']
            ]);
            update_field('at_org', 1, $term);
            update_field('url', $data['homepage'], $term);
            $version = get_field('version', $term);
            if (version_compare($version, $data['version'], '<')) {
                update_field('version', $data['version'], $term);
            }
        }

        update_field('update', current_time('mysql'), $term);
    }

    public static function reget_theme_plugin_info()
    {
        $term_query = new WP_Term_Query();
        $terms = $term_query->query([
            'taxonomy' => ['plugin', 'theme'],
            'hide_empty' => false,
            'meta_key' => 'update',
            'meta_type' => 'DATETIME',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'number' => 2
        ]);
        foreach ($terms as $term) {
            do_action('wei/get_' . $term->taxonomy . '_info', $term->term_id);
        }
    }

    public static function reget_site_theme_plugin()
    {
        $site_query = new WP_Query();
        $site_query->query([
            'post_type' => 'site',
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'ASC',
            'posts_per_page' => 2
        ]);
        while ($site_query->have_posts()) {
            $site_query->the_post();
            do_action('wei/get_site_theme_plugin', get_the_ID());
        }
    }
}

RY_WPI_Cron::init();
