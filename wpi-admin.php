<?php
class RY_WPI_Admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('init', [__CLASS__, 'load_script_style'], 20);

            add_action('post_submitbox_misc_actions', [__CLASS__, 'show_mod_time']);

            add_action('add_meta_boxes_website', [__CLASS__, 'website_meta_box']);
            add_action('add_meta_boxes_plugin', [__CLASS__, 'plugin_meta_box']);
            add_action('add_meta_boxes_theme', [__CLASS__, 'theme_meta_box']);
            add_action('delete_post', [__CLASS__, 'delete_post_info']);
        }
    }

    public static function load_script_style()
    {
        wp_enqueue_style('wpi-admin-style', RY_WPI_PLUGIN_URL . '/assets/css/admin.css', [], RY_WPI_VERSION);
        wp_register_script('wpi-meta_box-script', RY_WPI_PLUGIN_URL . '/assets/js/meta_box.js', ['jquery'], RY_WPI_VERSION, true);
    }

    public static function show_mod_time($post)
    {
        $date_format = _x('M j, Y', 'publish box date format', 'wpinfo-plugin');
        $time_format = _x('H:i', 'publish box time format', 'wpinfo-plugin');

        $date = sprintf(
            __('%1$s at %2$s', 'wpinfo-plugin'),
            date_i18n($date_format, strtotime($post->post_modified)),
            date_i18n($time_format, strtotime($post->post_modified))
        );
        echo '<div class="misc-pub-section curtime misc-pub-curtime">'
            . '<span id="timestamp">編輯時間: ' . '<b>' . $date . '</b>'
            . '</div>';
    }

    public static function website_meta_box()
    {
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('website_action', 'WPI 操作', [__CLASS__, 'website_action'], null, 'side');
    }

    public static function plugin_meta_box()
    {
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('plugin_action', 'WEI 操作', [__CLASS__, 'plugin_action'], null, 'side');
    }

    public static function theme_meta_box()
    {
        wp_enqueue_script('wpi-meta_box-script');

        remove_meta_box('postcustom', null, 'normal');
        add_meta_box('theme_action', 'WEI 操作', [__CLASS__, 'theme_action'], null, 'side');
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

    public static function website_action()
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/website_action.php';
    }

    public static function plugin_action()
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/plugin_action.php';
    }

    public static function theme_action()
    {
        include RY_WPI_PLUGIN_DIR . 'html/meta_box/theme_action.php';
    }
}

RY_WPI_Admin::init();
