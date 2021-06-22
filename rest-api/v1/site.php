<?php
class RY_WPI_V1_Site_Controller extends WP_REST_Controller
{
    public function __construct()
    {
        $this->namespace = 'wpi/v1';
        $this->rest_base = 'site';
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/check_url', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'permission_callback' => '__return_true',
                'callback' => [$this, 'check_url'],
                'args' => [
                    'url' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]
        ]);
    }

    public function check_url($request)
    {
        $data = [];

        $url = trim($request['url']);
        $cat_pos = strpos($url, '://');
        if ($cat_pos !== false) {
            $url = substr($url, $cat_pos + 3);
        }
        $cat_pos = strpos($url, '?');
        if ($cat_pos !== false) {
            $url = substr($url, 0, $cat_pos);
        }
        $url = strtolower(rtrim($url, '/'));
        $slug_url = sanitize_title($url);

        if (empty($url)) {
            $data['info'] = 'error_url';
        }

        $real_url = 'https://' . $url;
        if (filter_var($real_url, FILTER_VALIDATE_URL) === false) {
            $data['info'] = 'error_url';
        }

        if (empty($data)) {
            $site_query = new WP_Query([
                'post_type' => 'website',
                'post_status' => ['publish', 'draft'],
                'name' => $slug_url,
                'orderby' => 'ID',
                'posts_per_page' => 1
            ]);

            if ($site_query->have_posts()) {
                $site_query->the_post();

                if (get_post_status() == 'publish') {
                    $data['url'] = get_permalink();
                } else {
                    $data['info'] = 'confirming';
                }
            }
        }

        if (empty($data)) {
            $site_ID = wp_insert_post([
                'post_type' => 'website',
                'post_title' => $real_url,
                'post_name' => $slug_url,
                'post_status' => 'draft',
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            ]);
            update_post_meta($site_ID, 'url', $real_url);
            update_post_meta($site_ID, 'rest_url', '');

            do_action('wpi/get_website_info', $site_ID, false);
            if (get_post_status($site_ID) == 'publish') {
                do_action('wpi/get_website_theme_plugin', $site_ID);
                $data['url'] = get_permalink($site_ID);
            } else {
                $data['info'] = 'confirming';
            }
        }

        $data = $this->add_additional_fields_to_object($data, $request);

        $response = rest_ensure_response($data);

        return $response;
    }
}