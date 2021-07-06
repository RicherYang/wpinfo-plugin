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

        if (version_compare($now_version, '2.0.16', '<')) {
            RY_WPI::update_option('version', '2.0.16');
        }
    }
}
