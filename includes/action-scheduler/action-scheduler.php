<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

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
        require_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler/ActionScheduler_AdminView.php';
        require_once RY_WPI_PLUGIN_DIR . 'includes/action-scheduler/ActionScheduler_ListTable.php';

        add_filter('action_scheduler_admin_view_class', [__CLASS__, 'change_adminview']);
    }

    public static function change_adminview()
    {
        return 'RY_WPI_ActionScheduler_AdminView';
    }
}

RY_WPI_ActionScheduler::init();
