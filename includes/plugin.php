<?php
class RY_WPI_Plugin
{
    public static function get_basic_info($plugin_ID)
    {
        if (get_post_type($plugin_ID) != 'plugin') {
            return;
        }

        $at_org = get_field('at_org', $plugin_ID);
        if ($at_org === false) {
            wp_update_post([
                'ID' => $plugin_ID
            ]);
            return;
        }

        $plugin_slug = get_post_field('post_name', $plugin_ID, 'raw');
        $json = RY_WPI_Remote::get('https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json', $plugin_ID);
        if (empty($json)) {
            update_field('at_org', false, $plugin_ID);
            wp_update_post([
                'ID' => $plugin_ID
            ]);
            return;
        }

        $json_data = json_decode($json);
        if ($json_data && isset($json_data->name)) {
            update_field('at_org', true, $plugin_ID);
            update_field('url', $json_data->homepage, $plugin_ID);
            update_field('version', $json_data->version, $plugin_ID);

            if (isset($json_data->tags)) {
                wp_set_post_tags($plugin_ID, (array) $json_data->tags);
            }

            wp_update_post([
                'ID' => $plugin_ID,
                'post_title' => $json_data->name,
            ]);
            return ;
        }
        wp_update_post([
            'ID' => $plugin_ID
        ]);
    }

    public static function update_used_count($plugin_ID)
    {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(meta_id) FROM $wpdb->postmeta
            WHERE meta_key = 'plugins' AND meta_value LIKE '%:\"{$plugin_ID}\";%'");
        update_field('used_count', (int) $count, $plugin_ID);
    }
}
