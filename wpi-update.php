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

        global $wpdb;

        if (version_compare($now_version, '2.0.5', '<')) {
            RY_WPI::create_table();

            RY_WPI::update_option('version', '2.0.5');
        }

        if (version_compare($now_version, '2.0.15', '<')) {
            as_unschedule_all_actions('wpi/reget_info');
            as_unschedule_all_actions('wpi/reget_website_category');

            RY_WPI_Cron::set_scheduled_job();
            RY_WPI::update_option('version', '2.0.15');
        }

        if (version_compare($now_version, '2.1.0', '<')) {
            RY_WPI::create_table();

            $wpdb->query("TRUNCATE {$wpdb->prefix}actionscheduler_actions");
            $wpdb->query("TRUNCATE {$wpdb->prefix}actionscheduler_claims");
            $wpdb->query("TRUNCATE {$wpdb->prefix}actionscheduler_groups");
            $wpdb->query("TRUNCATE {$wpdb->prefix}actionscheduler_logs");


            $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%cat%'");
            $wpdb->query("SET @num := 0;");
            $wpdb->query("UPDATE $wpdb->postmeta SET meta_id = @num := (@num+1) ORDER BY meta_id ASC;");
            $max_id = (int) $wpdb->get_var("SELECT MAX(meta_id) FROM $wpdb->postmeta");
            $max_id += 1;
            $wpdb->query("ALTER TABLE $wpdb->postmeta AUTO_INCREMENT = $max_id");

            $results = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta
                        WHERE meta_key = 'support_rest'");
            foreach ($results as $meta) {
                update_field('support_cat', $meta->meta_value, $meta->post_id);
                update_field('cat_update', '2020-01-01 00:00:00', $meta->post_id);
            }

            $query = new WP_Query();
            $post_list = $query->query([
                'post_type' => ['website', 'plugin', 'theme'],
                'post_status' => 'publish',
                'orderby' => 'none',
                'fields' => 'ids',
                'posts_per_page' => -1
            ]);
            foreach ($post_list as $post_ID) {
                update_field('info_update', '2020-01-01 00:00:00', $post_ID);
            }

            RY_WPI::update_option('version', '2.1.0');
        }

        if (version_compare($now_version, '2.1.1', '<')) {
            $wpdb->query("SET @num := 0;");
            $wpdb->query("UPDATE $wpdb->options SET option_id = @num := (@num+1) ORDER BY option_id ASC;");
            $max_id = (int) $wpdb->get_var("SELECT MAX(option_id) FROM $wpdb->options");
            $max_id += 1;
            $wpdb->query("ALTER TABLE $wpdb->options AUTO_INCREMENT = $max_id");

            RY_WPI::update_option('version', '2.1.1');
        }

        if (version_compare($now_version, '2.1.2', '<')) {
            RY_WPI::update_option('version', '2.1.2');
        }
    }
}
