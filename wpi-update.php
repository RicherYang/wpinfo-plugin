<?php

final class RY_WPI_update
{
    public static function update()
    {
        $now_version = RY_WPI::get_option('version');

        if ($now_version === false) {
            $now_version = '0.0.0';
        }
        if ($now_version == RY_WPI_VERSION) {
            return;
        }

        if (version_compare($now_version, '1.1.1', '<')) {
            global $wpdb;

            $wpdb->update($wpdb->posts, [
                'post_type' => 'website'
            ], [
                'post_type' => 'site'
            ]);
            delete_post_meta_by_key('_description');
            delete_post_meta_by_key('_rest_api');
            delete_post_meta_by_key('_rest_url');
            delete_post_meta_by_key('_url');

            $post_query = new WP_Query();
            $post_query->query([
                'post_type' => 'website',
                'post_status' => 'publish',
                'posts_per_page' => -1
            ]);
            as_unschedule_all_actions('wpi/get_info');
            as_unschedule_all_actions('wpi/get_website_theme_plugin');
            while ($post_query->have_posts()) {
                $post_query->the_post();
                as_schedule_single_action(time(), 'wpi/get_info', [get_the_ID()]);
            }

            $term_query = new WP_Term_Query();
            $terms = $term_query->query([
                'taxonomy' => ['theme', 'plugin']
            ]);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $term->taxonomy);
            }

            RY_WPI::update_option('version', '1.1.1');
        }

        if (version_compare($now_version, '1.2.2', '<')) {
            set_time_limit(120);
            ini_set('memory_limit', -1);
            $post_query = new WP_Query();
            $post_query->query([
                'post_type' => 'website',
                'post_status' => 'publish',
                'posts_per_page' => -1
            ]);
            while ($post_query->have_posts()) {
                $post_query->the_post();

                $post_ID = get_the_ID();
                wp_update_post([
                    'ID' => $post_ID,
                    'post_excerpt' => get_post_meta($post_ID, 'description', true)
                ]);
            }

            RY_WPI::update_option('version', '1.2.2');
        }

        if (version_compare($now_version, '1.2.12', '<')) {
            RY_WPI::update_option('version', '1.2.12');
        }
    }
}
