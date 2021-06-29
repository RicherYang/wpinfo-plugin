<?php
class RY_WPI_Cron
{
    private static $action_id = null;
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('action_scheduler_begin_execute', [__CLASS__, 'set_as_action_id']);

            add_action('wpi/get_website_basic_info', ['RY_WPI_Website', 'get_basic_info']);

            add_action('wpi/get_plugin_info', ['RY_WPI_Plugin', 'get_basic_info']);
            add_action('wpi/get_theme_info', ['RY_WPI_Theme', 'get_basic_info']);

            add_action('wpi/reget_website_info', [__CLASS__, 'reget_website_info']);
            /*
            add_action('wpi/get_website_category', [__CLASS__, 'get_website_category']);

            add_action('wpi/reget_website_category', [__CLASS__, 'reget_website_category']);
            add_action('wpi/reget_theme_plugin_info', [__CLASS__, 'reget_theme_plugin_info']);*/
        }
    }

    public static function set_scheduled_job()
    {
        if (!as_next_scheduled_action('wpi/reget_website_info')) {
            as_schedule_recurring_action(time() + MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS, 'wpi/reget_website_info');
        }

        /*
        if (!as_next_scheduled_action('wpi/reget_website_category')) {
            as_schedule_recurring_action(time() + 190, 600, 'wpi/reget_website_category');
        }
        */
    }

    public static function set_as_action_id($action_id)
    {
        self::$action_id = $action_id;
    }

    public static function reget_website_info()
    {
        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        if ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'website:' . get_the_ID());
            }

            RY_WPI_Website::get_basic_info(get_the_ID());
        }
    }

    public static function get_website_category($website_ID)
    {
        if (get_post_type($website_ID) != 'website') {
            return;
        }

        $rest_url = get_post_meta($website_ID, 'rest_url', true);
        if ($rest_url == 'not_use') {
            return;
        }
        $rest_url = rtrim($rest_url, '/');

        $rest_api = get_post_meta($website_ID, 'rest_api', true);
        $rest_api = explode(',', $rest_api);
        if (!in_array('wp/v2', $rest_api)) {
            return;
        }

        $query_arg = [
            'hide_empty' => true,
            'page' => 1,
            'per_page' => 100
        ];
        $all_category = [];
        do {
            $body = RY_WPI_Remote::get(add_query_arg($query_arg, $rest_url . '/wp/v2/categories'), $website_ID);
            if (empty($body)) {
                break;
            }

            $data = json_decode($body, true);
            if ($data && count($data)) {
                $try_next = false;
                $list = array_column($data, 'name');
                foreach ($list as $category) {
                    $term_info = term_exists($category, 'website-category');
                    if (!$term_info) {
                        $term_info = wp_insert_term($category, 'website-category');
                    }
                    if (!is_wp_error($term_info)) {
                        $term_id = (int) $term_info['term_id'];
                        if (!$all_category[$term_id]) {
                            $all_category[$term_id] = 1;
                            $try_next = true;
                        }
                    }
                }

                if ($try_next) {
                    $query_arg['page'] += 1;
                } else {
                    break;
                }
            } else {
                break;
            }
        } while (true);

        wp_set_post_terms($website_ID, array_keys($all_category), 'website-category');
        update_post_meta($website_ID, 'category_time', current_time('timestamp'));
    }

    public static function reget_website_category()
    {
        add_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'category_time',
                    'type' => 'NUMERIC'
                ]
            ],
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        remove_filter('get_meta_sql', [__CLASS__, 'get_meta_sql']);
        if ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'reget:' . get_the_ID());
            }

            do_action('wpi/get_website_category', get_the_ID());
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
        if ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'reget:' . get_the_ID());
            }

            do_action('wpi/get_' . get_post_type() . '_info', get_the_ID());
        }
    }
}

RY_WPI_Cron::init();
