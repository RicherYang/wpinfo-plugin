<?php
class RY_WPI_Remote
{
    public static function get($url)
    {
        set_time_limit(60);

        $response = wp_remote_get($url, [
            'timeout' => 5,
            'user-agent' => 'Mozilla/5.0 (CentOS; Linux x86_64; WordPress/' . get_bloginfo('version') . ') wpinfoShow/' . RY_WPI_VERSION
        ]);

        if (!is_wp_error($response)) {
            if (200 == wp_remote_retrieve_response_code($response)) {
                return wp_remote_retrieve_body($response);
            }
        }
        return '';
    }
}
