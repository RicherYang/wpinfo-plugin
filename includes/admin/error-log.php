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

        global $wpdb;

        $list_table = new RY_WPI_Admin_ListTable_ErrorLog();

        $doaction = $list_table->current_action();
        if ($doaction) {
            check_admin_referer('bulk-error-logs');

            $IDs = array_map('intval', $_GET['ids'] ?? []);
            switch ($doaction) {
                case 'delete':
                    $IDs = array_filter(array_unique($IDs));
                    if (count($IDs)) {
                        $id_query = implode(',', $IDs);
                        $wpdb->query("DELETE FROM {$wpdb->prefix}remote_error
                            WHERE remote_error_id IN ($id_query)");
                    }
                    break;
            }
        }

        $list_table->prepare_items();

        include RY_WPI_PLUGIN_DIR . 'html/error-log.php';
    }
}

RY_WPI_Admin_ErrorLog::init();
