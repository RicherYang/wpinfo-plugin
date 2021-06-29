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

        if (version_compare($now_version, '2.0.2', '<')) {
            RY_WPI::update_option('version', '2.0.2');
        }
    }
}
