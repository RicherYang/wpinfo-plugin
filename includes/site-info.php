<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_SiteInfo
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            if (!function_exists('has_meta')) {
                require_once ABSPATH . 'wp-admin/includes/post.php';
            }

            add_filter('wpi/add_theme', [__CLASS__, 'theme_from_file_name'], 10, 3);
            add_filter('wpi/add_plugin', [__CLASS__, 'plugin_from_file_name'], 10, 3);

            add_filter('wpi/add_plugin', [__CLASS__, 'plugin_from_rest'], 10, 2);
        }
    }

    public static function theme_from_file_name($themes, $site_ID, $body)
    {
        $url = get_post_meta($site_ID, 'url', true);
        $url = substr($url, 8);
        preg_match_all('@' . preg_quote($url, '@') . '/[a-z0-9\-\_\./]*/themes/([a-z0-9\-\_\.]*)/@iU', $body, $matches, PREG_SET_ORDER);

        if (count($matches)) {
            foreach ($matches as $theme) {
                $theme[1] = sanitize_title(strtolower($theme[1]));
                if (in_array($theme[1], $themes)) {
                    continue;
                }
                $themes[] = sanitize_title(strtolower($theme[1]));

                $body = RY_WPI_Cron::remote_get('https://' . $theme[0] . 'style.css');
                if (empty($body)) {
                    continue;
                }
                if (preg_match('/^[ \t\/*#@]*Template:(.*)$/mi', $body, $match) && $match[1]) {
                    $themes[] = sanitize_title(strtolower(trim($theme[1])));
                }
            }
        }

        return $themes;
    }

    public static function plugin_from_file_name($plugins, $site_ID, $body)
    {
        $url = get_post_meta($site_ID, 'url', true);
        $url = substr($url, 8);
        preg_match_all('@' . preg_quote($url, '@') . '/[a-z0-9\-\_\./]*/plugins/([a-z0-9\-\_\.]*)/@iU', $body, $matches);

        if (isset($matches[1])) {
            $list = array_filter(array_unique($matches[1]));
            foreach ($list as $name) {
                $plugins[] = sanitize_title(strtolower($name));
            }
        }

        return $plugins;
    }

    public static function plugin_from_rest($plugins, $site_ID)
    {
        static $rest_namespace_map = null;

        if ($rest_namespace_map === null) {
            $rest_namespace_map = [];
            $post_query = new WP_Query();
            $post_query->query([
                'post_type' => 'plugin',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => 'rest_key',
                        'compare' => '!=',
                        'value' => ''
                    ]
                ],
                'posts_per_page' => -1
            ]);
            while ($post_query->have_posts()) {
                $post_query->the_post();

                $rest_key = get_post_meta(get_the_ID(), 'rest_key', true);
                $rest_namespace_map[$rest_key] = get_post_field('post_name');
            }
        }

        $rest_api = get_post_meta($site_ID, 'rest_api', true);
        $rest_api = explode(',', $rest_api);

        foreach ($rest_api as $rest_namespace) {
            $namespace = explode('/', $rest_namespace);
            if (isset($namespace[0])) {
                if (isset($rest_namespace_map[$namespace[0]])) {
                    $plugins[] = $rest_namespace_map[$namespace[0]];
                }
            }
        }
        return $plugins;
    }

    public static function add_site_info($site_ID, $type, $list)
    {
        $list = array_count_values($list);

        if (count($list) == 0) {
            delete_post_meta($site_ID, $type);
            return ;
        }

        $type_query = new WP_Query();
        $type_list = $type_query->query([
            'post_type' => $type,
            'post_status' => 'publish',
            'post_name__in' => array_keys($list),
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);
        foreach ($type_list as $type_ID) {
            $post_name = get_post_field('post_name', $type_ID, 'raw');
            unset($list[$post_name]);
        }

        if (count($list)) {
            foreach ($list as $post_name => $count) {
                $new_ID = wp_insert_post([
                    'post_type' => $type,
                    'post_title' => $post_name,
                    'post_name' => $post_name,
                    'post_status' => 'publish',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ]);
                $type_list[] = $new_ID;
                update_post_meta($new_ID, 'used_count', 0);
                update_post_meta($new_ID, 'rest_key', '');
                as_schedule_single_action(time(), 'wpi/get_' . $type . '_info', [$new_ID]);
            }
        }

        $added_list = [];
        $post_meta = has_meta($site_ID);
        foreach ($post_meta as $data) {
            if ($data['meta_key'] == $type) {
                $key = array_search($data['meta_value'], $type_list);
                if ($key === false) {
                    delete_post_meta($site_ID, $type, $data['meta_value']);
                } else {
                    if (in_array($data['meta_value'], $added_list)) {
                        delete_metadata_by_mid('post', $data['meta_id']);
                    } else {
                        $added_list[] = $data['meta_value'];
                    }
                }
            }
        }

        foreach ($type_list as $type_ID) {
            if (!in_array($type_ID, $added_list)) {
                add_post_meta($site_ID, $type, $type_ID);
            }

            $used_list = $type_query->query([
                'post_type' => 'website',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_key' => $type,
                'meta_value' => $type_ID,
                'orderby' => 'ID',
                'order' => 'ASC'
            ]);
            update_post_meta($type_ID, 'used_count', count($used_list));
        }
    }
}

RY_WPI_SiteInfo::init();
