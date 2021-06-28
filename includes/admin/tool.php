<?php
class RY_WPI_Admin_Tool
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('admin_menu', [__CLASS__, 'admin_menu']);
        }
    }

    public static function admin_menu()
    {
        add_submenu_page('tools.php', 'WPI 工具', 'WPI 工具', 'manage_options', 'wpi-tool', [__CLASS__, 'tool_page']);
    }

    public static function tool_page()
    {
        if (isset($_GET['action']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(wp_unslash($_GET['_wpnonce']), $_GET['action'])) {
                $action = wp_unslash($_GET['action']);
                if (method_exists(__CLASS__, 'tool_' . $action)) {
                    call_user_func([__CLASS__, 'tool_' . $action]);
                }
            }
        }

        include RY_WPI_PLUGIN_DIR . 'html/tool.php';
    }

    protected static function tool_check_cron()
    {
        RY_WPI_Cron::set_scheduled_job();

        echo '<div class="notice notice-success is-dismissible"><p>檢查完成。</p></div>';
    }
}

RY_WPI_Admin_Tool::init();
