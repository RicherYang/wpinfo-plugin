<?php
class RY_WPI_Remote
{
    public static $http_code;

    public static function get($url, $post_ID)
    {
        global $wpdb;
        set_time_limit(60);

        self::$http_code = '';
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'user-agent' => 'Mozilla/5.0 (CentOS; Linux x86_64; WordPress/' . get_bloginfo('version') . ') wpinfoShow/' . RY_WPI_VERSION
        ]);

        $error_data = [
            'post_id' => $post_ID,
            'get_url' => $url
        ];
        if (!is_wp_error($response)) {
            self::$http_code = wp_remote_retrieve_response_code($response);
            if (200 == self::$http_code) {
                return wp_remote_retrieve_body($response);
            } else {
                $error_data['http_code'] = wp_remote_retrieve_response_code($response);
            }
        } else {
            $error_data['error_content'] = implode("\n\n", $response->get_error_messages());
        }
        $error_data['get_date'] = current_time('mysql');
        RY_WPI::create_table();
        $wpdb->insert($wpdb->prefix . 'remote_error', $error_data);
        return '';
    }
}
