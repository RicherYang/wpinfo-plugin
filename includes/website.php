<?php
class RY_WPI_Website
{
    public static function get_basic_info($website_ID)
    {
        $url = get_field('url', $website_ID, false);
        $is_wp = get_field('is_wp', $website_ID);
        $rest_url = get_field('rest_url', $website_ID, false);
        $html = RY_WPI_Remote::get($url, $website_ID);

        if (empty($html)) {
            wp_update_post([
                'ID' => $website_ID
            ]);
            return;
        }

        if ($is_wp === false && empty($rest_url)) {
            $link_cat = strpos($html, 'https://api.w.org/');
            if ($link_cat !== false) {
                $rest_url = substr($html, 0, strpos($html, '>', $link_cat));
                $rest_url = substr($rest_url, strrpos($rest_url, '<'));

                $link_cat = strpos($rest_url, 'href=');
                $rest_url = substr($rest_url, $link_cat + 5);

                $end = substr($rest_url, 0, 1);
                if ($end == '"' || $end == "'") {
                    $rest_url = substr($rest_url, 1);
                } else {
                    $end = ' ';
                }
                $rest_url = substr($rest_url, 0, strpos($rest_url, $end));
                if (substr($rest_url, 0, 2) == '//') {
                    $rest_url = 'https:' . $rest_url;
                }
            } else {
                $rest_url = $url . '/wp-json';
            }

            $rest_url = filter_var($rest_url, FILTER_VALIDATE_URL);
        }

        if (empty($rest_url)) {
            if ($is_wp === true) {
                $rss_url = get_field('rss_url', $website_ID, false);
                $rss = RY_WPI_Remote::get($rss_url, $website_ID);
                $xml = @simplexml_load_string($rss);
                if ($xml) {
                    self::get_website_theme_plugin($website_ID, $html, FILE_APPEND);
                    wp_update_post([
                        'ID' => $website_ID,
                        'post_title' => (string) $xml->channel->title,
                        'post_excerpt' => (string) $xml->channel->description,
                        'post_status' => 'publish'
                    ]);
                    return;
                }
            }
        } else {
            $rest = RY_WPI_Remote::get($rest_url, $website_ID);
            $rest_data = json_decode($rest);
            if (!empty($rest)) {
                update_field('is_wp', true, $website_ID);
                update_field('support_rest', true, $website_ID);
                update_field('rest_url', $rest_url, $website_ID);

                $rest_namespaces = [];
                foreach ($rest_data->namespaces as $namespace) {
                    $term_info = term_exists($namespace, 'plugin-rest');
                    if (!$term_info) {
                        $term_info = wp_insert_term($namespace, 'plugin-rest');
                    }
                    if (!is_wp_error($term_info)) {
                        $rest_namespaces[] = (int) $term_info['term_id'];
                    }
                }
                $rest_namespaces = array_unique($rest_namespaces);
                wp_set_post_terms($website_ID, $rest_namespaces, 'plugin-rest');

                self::get_website_theme_plugin($website_ID, $html);
                wp_update_post([
                    'ID' => $website_ID,
                    'post_title' => $rest_data->name,
                    'post_excerpt' => $rest_data->description,
                    'post_status' => 'publish'
                ]);
                return;
            }
        }
        wp_update_post([
            'ID' => $website_ID
        ]);
        return;
    }

    public static function get_website_theme_plugin($website_ID, $html)
    {
        $url = get_field('url', $website_ID, false);

        if (empty($html)) {
            $html = RY_WPI_Remote::get($url, $website_ID);
        }
        if (empty($html)) {
            return '';
        }

        $url = substr($url, 8);

        $plugins = [];
        preg_match_all('@' . preg_quote($url, '@') . '/[a-z0-9\-\_\./]*/plugins/([a-z0-9\-\_\.]*)/@iU', $html, $matches, PREG_SET_ORDER);
        if (count($matches)) {
            foreach ($matches as $plugin) {
                $plugins[] = sanitize_title(strtolower($plugin[1]));
            }
        }
        $plugins = array_unique($plugins);
        self::add_use_info($website_ID, 'plugin', $plugins);

        $themes = [];
        preg_match_all('@' . preg_quote($url, '@') . '/[a-z0-9\-\_\./]*/themes/([a-z0-9\-\_\.]*)/@iU', $html, $matches, PREG_SET_ORDER);
        if (count($matches)) {
            foreach ($matches as $theme) {
                $themes[] = sanitize_title(strtolower($theme[1]));
            }
        }
        $themes = array_unique($themes);
        self::add_use_info($website_ID, 'theme', $themes);
    }

    protected static function add_use_info($website_ID, $type, $list)
    {
        $type_list = [];
        $query = new WP_Query();
        $query_args = [
            'post_type' => $type,
            'post_status' => 'publish',
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        ];

        foreach ($list as $slug) {
            $query_args['name'] = $slug;
            $type_ID = $query->query($query_args);
            if (empty($type_ID)) {
                $type_ID = wp_insert_post([
                    'post_type' => $type,
                    'post_title' => $slug,
                    'post_name' => $slug,
                    'post_status' => 'publish',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ]);
                if ($type_ID > 0) {
                    as_schedule_single_action(time() + 30, 'wpi/get_' . $type . '_info', [$type_ID]);
                }
            } else {
                $type_ID = (int) $type_ID[0];
            }
            if ($type_ID > 0) {
                $type_list[] = $type_ID;
            }
        }

        $type_list = array_filter(array_unique($type_list));
        update_field($type . 's', $type_list, $website_ID);
        array_walk($type_list, ['RY_WPI_' . ucfirst($type), 'update_used_count']);
    }
    /*
        protected static function get_feed_site_name($feed_url, $website_ID)
        {
            $body = RY_WPI_Remote::get($feed_url, $website_ID);
            if (empty($body)) {
                return '';
            }
            $xml = @simplexml_load_string($body);
            if ($xml && isset($xml->channel)) {
                $use_wp = get_post_status($website_ID) === 'publish';
                if ($use_wp === false) {
                    if (isset($xml->channel->generator)) {
                        $generator = (string) $xml->channel->generator;
                        $use_wp = strpos($generator, 'https://wordpress.org') === 0;
                    }
                }

                if ($use_wp) {
                    return [(string) $xml->channel->title, (string) $xml->channel->description];
                }
            }
            return '';
        }

    public static function plugin_from_rest($plugins, $website_ID)
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

        $rest_api = get_post_meta($website_ID, 'rest_api', true);
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
*/
}
