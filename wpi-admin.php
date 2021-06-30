<?php
class RY_WPI_Admin
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('init', [__CLASS__, 'load_script_style'], 20);

            add_filter('display_post_states', [__CLASS__, 'add_title_post_states'], 10, 2);
            add_action('post_submitbox_misc_actions', [__CLASS__, 'show_mod_time']);

            add_action('add_meta_boxes_website', [__CLASS__, 'website_meta_box']);
            add_action('add_meta_boxes_plugin', [__CLASS__, 'plugin_meta_box']);
            add_action('add_meta_boxes_theme', [__CLASS__, 'theme_meta_box']);

            add_action('post_action_abandoned', [__CLASS__, 'abandoned_website']);
        }
    }

    public static function load_script_style()
    {
        wp_enqueue_style('wpi-admin-style', RY_WPI_PLUGIN_URL . '/assets/css/admin.css', [], RY_WPI_VERSION);
        wp_register_script('wpi-meta_box-script', RY_WPI_PLUGIN_URL . '/assets/js/meta_box.js', ['jquery'], RY_WPI_VERSION, true);
    }

    public static function add_title_post_states($post_states, $post)
    {
        if ('abandoned' === $post->post_status) {
            $post_states['abandoned'] = '廢站';
        }
        return $post_states;
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

        if ($post->post_status == 'abandoned') {
            echo '<strong>已廢站</strong>';
        }
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

    public static function website_action($post)
    {
        $post_type_object = get_post_type_object($post->post_type);
        $abandoned_link = add_query_arg('action', 'abandoned', admin_url(sprintf($post_type_object->_edit_link, $post->ID)));
        $abandoned_link = wp_nonce_url($abandoned_link, 'abandoned-post_' . $post->ID);

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

    public static function abandoned_website($post_ID)
    {
        check_admin_referer('abandoned-post_' . $post_ID);

        if (get_post_type($post_ID) == 'website') {
            wp_update_post([
                'ID' => $post_ID,
                'post_status' => 'abandoned',
            ]);
            wp_redirect(admin_url('edit.php?post_status=abandoned&post_type=website'));
        } else {
            wp_redirect(admin_url('post.php?post=' . $post_ID . '&action=edit'));
        }
        exit;
    }
}

RY_WPI_Admin::init();
