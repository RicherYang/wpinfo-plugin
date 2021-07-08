<?php
class RY_WPI_Remote
{
    private static $response;
    public static $error_messages;

    public static function get($url, $post_ID)
    {
        global $wpdb;
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        self::$error_messages = [];
        self::$response = wp_remote_get($url, [
            'timeout' => 25,
            'httpversion' => '1.1',
            'user-agent' => 'Mozilla/5.0 (X11; CentOS; Linux x86_64) WordPress/' . get_bloginfo('version') . ' wpinfoShow/' . RY_WPI_VERSION
        ]);

        $error_data = [
            'post_id' => $post_ID,
            'get_url' => $url
        ];
        if (!is_wp_error(self::$response)) {
            if (200 == wp_remote_retrieve_response_code(self::$response)) {
                return wp_remote_retrieve_body(self::$response);
            } else {
                $error_data['http_code'] = wp_remote_retrieve_response_code(self::$response);
            }
        } else {
            self::$error_messages = self::$response->get_error_messages();
            $error_data['http_code'] = 0;
            $error_data['error_content'] = implode("\n\n", self::$error_messages);
        }
        $error_data['get_date'] = current_time('mysql');
        RY_WPI::create_table();
        $wpdb->insert($wpdb->prefix . 'remote_error', $error_data);
        return '';
    }

    public static function build_rest_url(string $rest_url, string $rest_path, $args = [])
    {
        if (strpos($rest_url, '?') === false) {
            $rest_url = rtrim($rest_url, '/') . $rest_path;
        } else {
            $rest_url = remove_query_arg('rest_route', $rest_url);
            $rest_url = add_query_arg(['rest_route' => $rest_path], $rest_url);
        }
        if (!empty($args)) {
            $rest_url = add_query_arg($args, $rest_url);
        }

        return $rest_url;
    }
}
