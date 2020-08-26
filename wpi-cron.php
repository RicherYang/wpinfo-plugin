<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Cron
{
    private static $action_id = null;
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wpi/get_website_info', [__CLASS__, 'get_website_info'], 10, 2);
            add_action('wpi/get_website_theme_plugin', [__CLASS__, 'get_website_theme_plugin']);
            add_action('wpi/get_website_tag', [__CLASS__, 'get_website_tag'], 10, 2);

            add_action('wpi/get_theme_info', [__CLASS__, 'get_theme_info']);
            add_action('wpi/get_plugin_info', [__CLASS__, 'get_plugin_info']);

            add_action('action_scheduler_begin_execute', [__CLASS__, 'set_as_action_id']);
            add_action('wpi/reget_website_info', [__CLASS__, 'reget_website_info']);
            add_action('wpi/reget_website_tag', [__CLASS__, 'reget_website_tag']);
            add_action('wpi/reget_theme_plugin_info', [__CLASS__, 'reget_theme_plugin_info']);
        }
    }

    public static function get_website_info($site_ID, $auto_next = true)
    {
        if (get_post_type($site_ID) != 'website') {
            return;
        }

        set_time_limit(90);
        $site_name = '';
        $do_update = false;
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
                    $start = 1;
                    if ($end != '"' || $end != "'") {
                        $end = ' ';
                        $start = 0;
                    }
                    $rest_url = substr($rest_url, $start, strpos($rest_url, $end, 1) - 1);
                }
                if (filter_var($rest_url, FILTER_VALIDATE_URL) === false) {
                    $rest_url = $url . '/wp-json';
                }
            }

            if ($rest_url != 'not_use') {
                list($site_name, $site_description) = self::use_rest_get_site_name($rest_url, $site_ID, $do_update);
            }

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
                    $content_path = 'https:' . $content_path;
                    $content_path = rtrim($content_path, '/');
                    $do_update = $do_update || update_post_meta($site_ID, 'content_path', $content_path);
                }
            }
        }

        if (empty($site_name)) {
            list($site_name, $site_description) = self::use_feed_get_site_name($url . '/feed', $site_ID);
        }

        $site_post = get_post($site_ID);
        if (empty($site_name) && get_post_status($site_ID) == 'publish') {
            $site_name = $site_post->post_title;
        }

        if (!empty($site_name)) {
            $update_data = [
                'ID' => $site_ID,
                'post_title' => $site_name,
                'post_status' => 'publish'
            ];
            if (!empty($site_description)) {
                $update_data['post_excerpt'] = $site_description;
            }

            $do_update = $do_update || $update_data['post_title'] != $site_post->post_title;
            $do_update = $do_update || $update_data['post_excerpt'] != $site_post->post_excerpt;
            if ($do_update) {
                wp_update_post($update_data);
            }
            update_post_meta($site_ID, 'info_time', current_time('timestamp'));

            if ($auto_next) {
                as_schedule_single_action(time(), 'wpi/get_website_theme_plugin', [$site_ID]);
            }
        }
    }

    protected static function use_rest_get_site_name($rest_url, $site_ID, &$do_update)
    {
        $body = self::remote_get($rest_url);
        if (empty($body)) {
            return '';
        }
        $data = @json_decode($body, true);
        if ($data) {
            $rest_url = rtrim($rest_url, '/');
            $do_update = $do_update || update_post_meta($site_ID, 'rest_url', $rest_url);
            $do_update = $do_update || update_post_meta($site_ID, 'rest_api', implode(',', $data['namespaces']));

            return [$data['name'], $data['description']];
        }
        return '';
    }

    protected static function use_feed_get_site_name($feed_url, $site_ID)
    {
        $post_status = get_post_status($site_ID);
        $body = self::remote_get($feed_url);
        if (empty($body)) {
            return '';
        }
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
                return [(string) $xml->channel->title, (string) $xml->channel->description];
            }
        }
        return '';
    }

    public static function get_website_theme_plugin($site_ID)
    {
        if (get_post_type($site_ID) != 'website') {
            return;
        }

        set_time_limit(90);
        $do_update = false;
        $url = get_post_meta($site_ID, 'url', true);
        $body = self::remote_get($url);
        if (!empty($body)) {
            $themes = apply_filters('wpi/add_theme', [], $site_ID, $body);
            $do_update = $do_update || RY_WPI_SiteInfo::add_site_info($site_ID, 'theme', $themes);

            $plugins = apply_filters('wpi/add_plugin', [], $site_ID, $body);
            $do_update = $do_update || RY_WPI_SiteInfo::add_site_info($site_ID, 'plugin', $plugins);
        }

        if ($do_update) {
            wp_update_post([
                'ID' => $site_ID
            ]);
        }
        update_post_meta($site_ID, 'info_time', current_time('timestamp'));
    }

    public static function get_website_tag($site_ID, $tag_page = 1)
    {
        if (get_post_type($site_ID) != 'website') {
            return;
        }

        $rest_url = get_post_meta($site_ID, 'rest_url', true);
        if ($rest_url == 'not_use') {
            return;
        }
        $rest_url = rtrim($rest_url, '/');

        $rest_api = get_post_meta($site_ID, 'rest_api', true);
        $rest_api = explode(',', $rest_api);
        if (!in_array('wp/v2', $rest_api)) {
            return;
        }

        set_time_limit(90);
        $start_time = time();
        $tag_list = get_post_meta($site_ID, '_tmp_tag', true);
        $tag_list = @json_decode($tag_list, true);
        if (!is_array($tag_list)) {
            $tag_list = [];
            update_post_meta($site_ID, 'tag_time', current_time('timestamp'));
        }
        $query_arg = [
            'hide_empty' => true,
            'per_page' => 100,
            'page' => $tag_page
        ];
        $end = false;
        do {
            $body = self::remote_get(add_query_arg($query_arg, $rest_url . '/wp/v2/tags'));
            if (empty($body)) {
                $end = true;
                break;
            }

            $data = @json_decode($body, true);
            if ($data && count($data)) {
                $new_tag = array_column($data, 'name');
                foreach ($new_tag as $term) {
                    $tags = RY_WPI_SiteInfo::cat_tag($term);
                    if (empty($tags)) {
                        continue;
                    }

                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (empty($tag)) {
                            continue;
                        }

                        $term_info = term_exists($tag, 'website-tag');
                        if (!$term_info) {
                            $term_info = wp_insert_term($tag, 'website-tag');
                        }
                        if (!is_wp_error($term_info)) {
                            $tag_list[] = (int) $term_info['term_id'];
                        }
                    }
                }
                $tag_list = array_values(array_unique($tag_list));

                $query_arg['page'] += 1;
                if (count($data) < $query_arg['per_page']) {
                    $end = true;
                    break;
                }
            } else {
                $end = true;
                break;
            }
            sleep(1);
        } while (time() - $start_time < 30);


        if ($end) {
            update_post_meta($site_ID, '_tmp_tag', '');
            wp_set_post_terms($site_ID, $tag_list, 'website-tag');
            update_post_meta($site_ID, 'tag_time', current_time('timestamp'));
        } else {
            update_post_meta($site_ID, '_tmp_tag', json_encode($tag_list));
            as_schedule_single_action(time() + 10, 'wpi/get_website_tag', [$site_ID, $query_arg['page']]);
        }
    }

    public static function get_theme_info($theme_ID)
    {
        if (get_post_type($theme_ID) != 'theme') {
            return;
        }

        set_time_limit(90);
        $theme_slug = get_post_field('post_name', $theme_ID, 'raw');
        $body = self::remote_get('https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=' . $theme_slug);
        if (empty($body)) {
            return;
        }
        $data = @json_decode($body, true);
        if ($data && isset($data['name'])) {
            $do_update = false;

            $do_update = $do_update || update_post_meta($theme_ID, 'at_org', 1);
            $do_update = $do_update || update_post_meta($theme_ID, 'url', $data['homepage']);
            $version = get_post_meta('version', $theme_ID, true);
            if (version_compare($version, $data['version'], '<')) {
                $do_update = $do_update || update_post_meta($theme_ID, 'version', $data['version']);
            }

            $update_data = [
                'ID' => $theme_ID,
                'post_title' => $data['name']
            ];
            $theme_post = get_post($theme_ID);
            $do_update = $do_update || $update_data['post_title'] != $theme_post->post_title;
            if ($do_update) {
                wp_update_post($update_data);
            }
            if (isset($data['tags'])) {
                wp_set_post_tags($theme_ID, array_values($data['tags']));
            }
        }
    }

    public static function get_plugin_info($plugin_ID)
    {
        if (get_post_type($plugin_ID) != 'plugin') {
            return;
        }

        set_time_limit(90);
        $plugin_slug = get_post_field('post_name', $plugin_ID, 'raw');
        $body = self::remote_get('https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json');
        if (empty($body)) {
            return;
        }
        $data = @json_decode($body, true);
        if ($data && isset($data['name'])) {
            $do_update = false;

            $do_update = $do_update || update_post_meta($plugin_ID, 'at_org', 1);
            $do_update = $do_update || update_post_meta($plugin_ID, 'url', $data['homepage']);
            $version = get_post_meta('version', $plugin_ID, true);
            if (version_compare($version, $data['version'], '<')) {
                $do_update = $do_update || update_post_meta($plugin_ID, 'version', $data['version']);
            }

            $update_data = [
                'ID' => $plugin_ID,
                'post_title' => $data['name']
            ];
            $plugin_post = get_post($plugin_ID);
            $do_update = $do_update || $update_data['post_title'] != $plugin_post->post_title;
            if ($do_update) {
                wp_update_post($update_data);
            }
            if (isset($data['tags'])) {
                wp_set_post_tags($plugin_ID, array_values($data['tags']));
            }
        }
    }

    public static function get_meta_sql($args)
    {
        $args['join'] = str_replace('INNER JOIN', 'LEFT JOIN', $args['join']);
        $args['join'] = str_replace(' )', $args['where'] . ' )', $args['join']);
        $args['where'] = '';
        return $args;
    }

    public static function set_as_action_id($action_id)
    {
        self::$action_id = $action_id;
    }

    public static function reget_website_info()
    {
        add_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'info_time',
                    'type' => 'NUMERIC'
                ]
            ],
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        remove_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        while ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'website:' . get_the_ID());
            }

            do_action('wpi/get_website_info', get_the_ID());
        }
    }

    public static function reget_website_tag()
    {
        add_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'tag_time',
                    'type' => 'NUMERIC'
                ]
            ],
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        remove_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        while ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'website:' . get_the_ID());
            }

            do_action('wpi/get_website_tag', get_the_ID());
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
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (CentOS; Linux x86_64; WordPress/' . get_bloginfo('version') . ') wpinfoShow/' . RY_WPI_VERSION
        ]);

        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                return wp_remote_retrieve_body($response);
            } else {
                $log_ID = wp_insert_post([
                    'post_type' => 'remote_log',
                    'post_title' => wp_remote_retrieve_response_code($response) . ' ' . $url,
                    'post_status' => 'publish',
                    'post_content' => wp_remote_retrieve_body($response)
                ]);
                wp_set_post_terms($log_ID, [(string) wp_remote_retrieve_response_code($response)], 'remote_log-tag');
            }
        } else {
            $tag_list = [];
            $messages = $response->get_error_messages();
            foreach ($messages as $message) {
                $tag_list[] = strstr($message, ':', true);
            }
            $log_ID = wp_insert_post([
                'post_type' => 'remote_log',
                'post_title' => 'Error ' . $url,
                'post_status' => 'publish',
                'post_content' => implode("\n", $messages)
            ]);
            wp_set_post_terms($log_ID, array_filter($tag_list), 'remote_log-tag');
        }
        return '';
    }
}

RY_WPI_Cron::init();
