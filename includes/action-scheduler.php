<?php
class RY_WPI_ActionScheduler
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('action_scheduler_pre_init', [__CLASS__, 'load']);
        }
    }

    public static function load()
    {
        include_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler/admin-view.php';
        include_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler/list-table.php';

        add_filter('action_scheduler_admin_view_class', [__CLASS__, 'change_adminview']);

        add_filter('action_scheduler_queue_runner_time_limit', [__CLASS__, 'set_90']);
    }

    public static function change_adminview()
    {
        return 'RY_WPI_ActionScheduler_AdminView';
    }

    public static function set_90()
    {
        return 90;
    }
}

RY_WPI_ActionScheduler::init();
