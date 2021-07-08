<?php
class RY_WPI_Acf
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('acf/init', [__CLASS__, 'load']);
        }
    }

    public static function load()
    {
        include_once RY_WPI_PLUGIN_DIR . 'includes/acf/field-webcat.php';
    }
}

RY_WPI_Acf::init();
