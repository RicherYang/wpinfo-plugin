<?php
class RY_WPI_Website
{
    public static function get_basic_info($website_ID)
    {
        $url = get_field('url', $website_ID, false);
        $is_wp = get_field('is_wp', $website_ID);
        $rest_url = get_field('rest_url', $website_ID, false);

        if (is_null($is_wp)) {
            $is_wp = false;
        }
        $html = RY_WPI_Remote::get($url, $website_ID);
        update_field('info_update', current_time('mysql'), $website_ID);

        if (empty($html)) {
            if (count(RY_WPI_Remote::$error_messages) == 1) {
                if (substr(RY_WPI_Remote::$error_messages[0], 0, 37) == 'cURL error 6: Could not resolve host:') {
                    wp_update_post([
                        'ID' => $website_ID,
                        'post_status' => 'abandoned'
                    ]);
                }
            }
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
                    check_and_update_post([
                        'ID' => $website_ID,
                        'post_title' => (string) $xml->channel->title,
                        'post_excerpt' => (string) $xml->channel->description,
                        'post_status' => 'publish'
                    ]);
                }
            }
        } else {
            $rest_url = RY_WPI_Remote::build_rest_url($rest_url, '/');
            $rest = RY_WPI_Remote::get($rest_url, $website_ID);
            $rest_data = json_decode($rest);
            if (!empty($rest_data)) {
                update_field('is_wp', true, $website_ID);
                update_field('support_rest', true, $website_ID);
                update_field('rest_url', $rest_url, $website_ID);
                update_field('support_cat', true, $website_ID);

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
                check_and_update_post([
                    'ID' => $website_ID,
                    'post_title' => (string) $rest_data->name,
                    'post_excerpt' => (string) $rest_data->description,
                    'post_status' => 'publish'
                ]);
            }
        }
    }

    public static function get_website_theme_plugin($website_ID, $html)
    {
        if (get_post_type($website_ID) != 'website') {
            return;
        }
        if (get_field('is_wp', $website_ID) !== true) {
            return;
        }

        $url = get_field('url', $website_ID, false);

        if (empty($html)) {
            $html = RY_WPI_Remote::get($url, $website_ID);
        }
        if (empty($html)) {
            return;
        }

        $url = substr($url, 8);

        $plugins = [];
        preg_match_all('@' . preg_quote($url, '@') . '/[a-z0-9\-\_\./]*/plugins/([a-z0-9\-\_\.]*)/@iU', $html, $matches, PREG_SET_ORDER);
        if (count($matches)) {
            foreach ($matches as $plugin) {
                $plugins[] = sanitize_title(strtolower($plugin[1]));
            }
        }
        $plugin_rest = get_the_terms($website_ID, 'plugin-rest');
        if (!empty($plugin_rest)) {
            $plugin_query = new WP_Query([
                'post_type' => 'plugin',
                'post_status' => ['publish'],
                'tax_query' => [[
                    'taxonomy' => 'plugin-rest',
                    'field' => 'term_id',
                    'terms' => array_column($plugin_rest, 'term_id'),
                ]],
                'posts_per_page' => -1
            ]);

            while ($plugin_query->have_posts()) {
                $plugin_query->the_post();
                $plugin = get_post();
                $plugins[] = $plugin->post_name;
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

    public static function get_category_list($website_ID)
    {
        if (get_post_type($website_ID) != 'website') {
            return;
        }
        if (get_field('support_cat', $website_ID) !== true) {
            return;
        }

        $rest_url = get_field('rest_url', $website_ID, false);

        $query_arg = [
            'hide_empty' => false,
            'page' => 1,
            'per_page' => 50,
            'orderby' => 'id',
            'order' => 'asc'
        ];
        $all_category = [];
        $category_map = [
            0 => 0
        ];
        do {
            $body = RY_WPI_Remote::get(RY_WPI_Remote::build_rest_url($rest_url, '/wp/v2/categories', $query_arg), $website_ID);
            if (empty($body)) {
                break;
            }

            $data = json_decode($body);
            if ($data && count($data)) {
                foreach ($data as $category) {
                    $term_info = term_exists($category->name, 'website-category');
                    if (!$term_info) {
                        $term_info = wp_insert_term($category->name, 'website-category');
                    }
                    if (!is_wp_error($term_info)) {
                        $term_id = (int) $term_info['term_id'];
                        if (isset($all_category[$term_id])) {
                            break;
                        }

                        $category_map[$category->id] = $term_id;
                        $all_category[$term_id] = [
                            'parent_category_id' => $category->parent,
                            'url' => $category->link,
                            'description' => $category->description,
                            'count' => $category->count
                        ];
                    }
                }

                $query_arg['page'] += 1;
            } else {
                break;
            }
        } while (true);

        global $wpdb;
        $website_category_map =$wpdb->get_results("SELECT website_category_id, category_id FROM {$wpdb->prefix}website_category
            WHERE website_id = $website_ID");
        $website_category_map = array_column($website_category_map, 'website_category_id', 'category_id');
        foreach ($all_category as $category_id => $category) {
            $category['parent_category_id'] = $category_map[$category['parent_category_id']] ?? 0;
            if (isset($website_category_map[$category_id])) {
                $wpdb->update($wpdb->prefix . 'website_category', $category, [
                    'website_category_id' => $website_category_map[$category_id]
                ]);
            } else {
                $category['website_id'] = $website_ID;
                $category['category_id'] = $category_id;
                $wpdb->insert($wpdb->prefix . 'website_category', $category);
            }
        }

        wp_set_post_terms($website_ID, array_keys($all_category), 'website-category');
        update_field('cat_update', current_time('mysql'), $website_ID);
    }
}
