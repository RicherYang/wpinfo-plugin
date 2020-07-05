<?php

final class RY_WPI_update
{
    public static function update()
    {
        $now_version = RY_WPI::get_option('version');

        if ($now_version === false) {
            $now_version = RY_WPI_VERSION;
        }
        if ($now_version == RY_WPI_VERSION) {
            return;
        }

        if (version_compare($now_version, '1.0.3', '<')) {
            RY_WPI::update_option('version', '1.0.3');
        }
    }
}
