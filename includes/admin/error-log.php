<?php
class RY_WPI_Admin_ErrorLog
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
        add_submenu_page('tools.php', 'WPI 擷取錯誤紀錄', 'WPI 擷取錯誤紀錄', 'manage_options', 'wpi-error-log', [__CLASS__, 'error_log_page']);
    }

    public static function error_log_page()
    {
        include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        include_once RY_WPI_PLUGIN_DIR . 'includes/admin/list-table/error-log.php';

        $list_table = new RY_WPI_Admin_ListTable_ErrorLog();
        $list_table->prepare_items();

        include RY_WPI_PLUGIN_DIR . 'html/error-log.php';
    }
}

RY_WPI_Admin_ErrorLog::init();
