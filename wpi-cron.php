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

            add_action('wpi/get_website_info', ['RY_WPI_Website', 'get_basic_info']);
            add_action('wpi/get_plugin_info', ['RY_WPI_Plugin', 'get_basic_info']);
            add_action('wpi/get_theme_info', ['RY_WPI_Theme', 'get_basic_info']);

            add_action('wpi/reget_website_info', [__CLASS__, 'reget_website_info']);
            add_action('wpi/reget_wporg_info', [__CLASS__, 'reget_wporg_info']);
        }
    }

    public static function set_scheduled_job()
    {
        if (!as_next_scheduled_action('wpi/reget_website_info')) {
            as_schedule_recurring_action(time() + MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS, 'wpi/reget_website_info');
        }

        if (!as_next_scheduled_action('wpi/reget_wporg_info')) {
            as_schedule_recurring_action(time() + MINUTE_IN_SECONDS, 16 * MINUTE_IN_SECONDS, 'wpi/reget_wporg_info');
        }

        /*
        if (!as_next_scheduled_action('wpi/reget_website_category')) {
            as_schedule_recurring_action(time() + MINUTE_IN_SECONDS, 5 * MINUTE_IN_SECONDS, 'wpi/reget_website_category');
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
        $post_list = $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'info_update',
                    'type' => 'DATETIME'
                ]
            ],
            'meta_key' => 'info_update',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'fields' => 'ids',
            'posts_per_page' => 1
        ]);
        foreach ($post_list as $post_ID) {
            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'get website : ' . $post_ID);
            }

            RY_WPI_Website::get_basic_info($post_ID);
        }
    }

    public static function reget_wporg_info()
    {
        $query = new WP_Query();
        $post_list = $query->query([
            'post_type' => ['plugin', 'theme'],
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'info_update',
                    'type' => 'DATETIME'
                ],
                [
                    'key' => 'at_org',
                    'compare' => '=',
                    'value' => '1'
                ]
            ],
            'meta_key' => 'info_update',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'fields' => 'ids',
            'posts_per_page' => 1
        ]);
        foreach ($post_list as $post_ID) {
            $post = get_post($post_ID);
            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'get ' . $post->post_type . ' : ' . $post->ID);
            }

            do_action('wpi/get_' . $post->post_type . '_info', $post->ID);
        }
    }

    public static function reget_website_category()
    {
        $query = new WP_Query();
        $query->query([
            'post_type' => 'website',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'cat_update',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'support_rest',
                    'value' => '1'
                ]
            ],
            //'meta_key' => 'cat_update',
            //'orderby' => 'meta_value',
            //'order' => 'ASC',
            'posts_per_page' => 1
        ]);
        if ($query->have_posts()) {
            $query->the_post();

            if (self::$action_id !== null) {
                ActionScheduler_Logger::instance()->log(self::$action_id, 'reget:' . get_the_ID());
            }

            RY_WPI_Website::get_category_list(get_the_ID());
        }
    }
}

RY_WPI_Cron::init();
