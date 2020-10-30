<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            require_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler/action-scheduler.php';

            add_action('init', [__CLASS__, 'check_schedule'], 20);

            add_action('post_submitbox_misc_actions', [__CLASS__, 'show_mod_time']);

            add_action('save_post_plugin', [__CLASS__, 'set_theme_plugin_info'], 10, 3);
            add_action('save_post_theme', [__CLASS__, 'set_theme_plugin_info'], 10, 3);
            add_action('add_meta_boxes_website', [__CLASS__, 'website_meta_box']);
            add_action('add_meta_boxes_plugin', [__CLASS__, 'plugin_meta_box']);
            add_action('add_meta_boxes_theme', [__CLASS__, 'plugin_meta_box']);
            add_action('delete_post', [__CLASS__, 'delete_post_info']);
        }
    }

    public static function show_mod_time($post)
    {
        $date_format = _x('M j, Y', 'publish box date format');
        $time_format = _x('H:i', 'publish box time format');

        $date = sprintf(
            __('%1$s at %2$s'),
            date_i18n($date_format, strtotime($post->post_modified)),
            date_i18n($time_format, strtotime($post->post_modified))
        );
        echo '<div class="misc-pub-section curtime misc-pub-curtime">'
            . '<span id="timestamp">編輯時間: ' . '<b>' . $date . '</b>'
            . '</div>';
    }

    public static function set_theme_plugin_info($post_ID, $post, $update)
    {
        if (!$update) {
            update_post_meta($post_ID, 'used_count', 0);
            update_post_meta($post_ID, 'rest_key', '');
        }
    }

    public static function website_meta_box()
    {
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('website_info', '網站資訊', [__CLASS__, 'website_info'], null, 'normal');
        add_meta_box('website_action', 'WPI 操作', [__CLASS__, 'website_action'], null, 'side');
    }

    public static function plugin_meta_box()
    {
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('plugin_info', '基本資訊', [__CLASS__, 'plugin_info'], null, 'normal');
        add_meta_box('plugin_action', 'WEI 操作', [__CLASS__, 'plugin_action'], null, 'side');
    }

    public static function delete_post_info($post_ID)
    {
        $post_type = get_post_type($post_ID);
        $post_query = new WP_Query();
        $post_query->query([
'post_type' => 'website',
'post_status' => 'publish',
'meta_key' => $post_type,
'meta_value' => $post_ID,
'orderby' => 'modified',
'order' => 'DESC',
'posts_per_page' => '-1'
]);
        while ($post_query->have_posts()) {
            $post_query->the_post();

            delete_post_meta(get_the_ID(), $post_type, $post_ID);
        }
    }

    public static function website_info($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/website_info.php';
    }

    public static function website_action($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/website_action.php';
    }

    public static function plugin_info($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/plugin_info.php';
    }

    public static function plugin_action($post)
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/plugin_action.php';
    }

    public static function check_schedule()
    {
        wp_enqueue_style('wpi-admin-style', RY_WPI_PLUGIN_URL . '/assets/css/admin.css', [], RY_WPI_VERSION);
        wp_register_script('wpi-meta_box-script', RY_WPI_PLUGIN_URL . '/assets/js/meta_box.js', ['jquery'], RY_WPI_VERSION, true);

        if (!as_next_scheduled_action('wpi/reget_theme_plugin_info')) {
            as_schedule_recurring_action(time() + 10, 600, 'wpi/reget_theme_plugin_info');
        }

        if (!as_next_scheduled_action('wpi/reget_website_info')) {
            as_schedule_recurring_action(time() + 100, 300, 'wpi/reget_website_info');
        }

        if (!as_next_scheduled_action('wpi/reget_website_tag')) {
            as_schedule_recurring_action(time() + 190, 600, 'wpi/reget_website_tag');
        }
    }
}

RY_WPI_Admin::init();

/*

DELETE FROM wp_term_taxonomy WHERE taxonomy = 'website-tag';
DELETE FROM wp_term_relationships WHERE term_taxonomy_id NOT IN (SELECT term_taxonomy_id FROM wp_term_taxonomy);
DELETE FROM wp_terms WHERE term_id NOT IN (SELECT term_id FROM wp_term_taxonomy);

ALTER TABLE wp_term_taxonomy ADD CONSTRAINT fk_term_id FOREIGN KEY(term_id) REFERENCES wp_terms(term_id) ON DELETE cascade ON UPDATE cascade;
ALTER TABLE wp_term_relationships ADD CONSTRAINT fk_term_taxonomy_id FOREIGN KEY(term_taxonomy_id) REFERENCES wp_term_taxonomy(term_taxonomy_id) ON DELETE cascade ON UPDATE cascade;

SET @num := 0;
UPDATE wp_terms SET term_id = @num := (@num+1) ORDER BY term_id ASC;
SET @num := 0;
UPDATE wp_term_taxonomy SET term_taxonomy_id = @num := (@num+1) ORDER BY term_taxonomy_id ASC;

ALTER TABLE wp_term_taxonomy DROP FOREIGN KEY fk_term_id;
ALTER TABLE wp_term_relationships DROP FOREIGN KEY fk_term_taxonomy_id;

SELECT @max := MAX(term_id) + 1 FROM wp_terms;
SET @alter_statement = concat('ALTER TABLE wp_terms AUTO_INCREMENT = ', @max);
PREPARE stmt FROM @alter_statement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT @max := MAX(term_taxonomy_id) + 1 FROM wp_term_taxonomy;
SET @alter_statement = concat('ALTER TABLE wp_term_taxonomy AUTO_INCREMENT = ', @max);
PREPARE stmt FROM @alter_statement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
*/
