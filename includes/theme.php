<?php
class RY_WPI_Theme
{
    public static function get_basic_info($theme_ID)
    {
        if (get_post_type($theme_ID) != 'theme') {
            return;
        }

        $at_org = get_field('at_org', $theme_ID);
        if ($at_org === false) {
            return;
        }

        $theme_slug = get_post_field('post_name', $theme_ID, 'raw');
        $json = RY_WPI_Remote::get('https://api.wordpress.org/themes/info/1.1/?action=theme_information&request[slug]=' . $theme_slug, $theme_ID);
        update_field('info_update', current_time('mysql'), $theme_ID);

        if (empty($json)) {
            update_field('at_org', false, $theme_ID);
            wp_update_post([
                'ID' => $theme_ID
            ]);
            return;
        }

        $json_data = json_decode($json);
        if ($json_data && isset($json_data->name)) {
            $update_data = [
                'ID' => $theme_ID,
                'post_title' => $json_data->name,
            ];
            if (get_field('version', $theme_ID) != $json_data->version) {
                update_field('version', $json_data->version, $theme_ID);
                $update_data['post_modified'] = current_time('mysql');
            }
            update_field('at_org', true, $theme_ID);
            update_field('url', $json_data->homepage, $theme_ID);

            if (isset($json_data->tags)) {
                wp_set_post_tags($theme_ID, (array) $json_data->tags);
            }

            check_and_update_post($update_data);
        }
    }

    public static function update_used_count($theme_ID)
    {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(meta_id) FROM $wpdb->postmeta
            WHERE meta_key = 'themes' AND meta_value LIKE '%:\"{$theme_ID}\";%'");
        update_field('used_count', (int) $count, $theme_ID);
    }
}
