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

        if (version_compare($now_version, '1.3.0', '<')) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'remote_log'");
            $wpdb->query("DELETE FROM {$wpdb->prefix}postmeta
                WHERE post_id NOT IN (SELECT ID FROM {$wpdb->prefix}posts)");

            $wpdb->query("SET @num := 0;");
            $wpdb->query("UPDATE {$wpdb->prefix}postmeta SET meta_id = @num := (@num+1) ORDER BY meta_id ASC;");
            $max_key = (int) $wpdb->get_var("SELECT max(meta_id) FROM {$wpdb->prefix}postmeta");
            $max_key += 1;
            $wpdb->query("ALTER TABLE {$wpdb->prefix}postmeta AUTO_INCREMENT = $max_key");

            $wpdb->query("DELETE FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = 'remote_log-tag'");
            $wpdb->query("DELETE FROM {$wpdb->prefix}term_relationships
                WHERE term_taxonomy_id NOT IN (SELECT term_taxonomy_id FROM {$wpdb->prefix}term_taxonomy)");
            $wpdb->query("DELETE FROM {$wpdb->prefix}terms
                WHERE term_id NOT IN (SELECT term_id FROM {$wpdb->prefix}term_taxonomy)");

            RY_WPI::update_option('version', '1.3.0');
        }

        if (version_compare($now_version, '1.3.1', '<')) {
            RY_WPI::update_option('version', '1.3.1');
        }
    }
}
