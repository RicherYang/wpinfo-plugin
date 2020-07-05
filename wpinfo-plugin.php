<?php
/*
Plugin Name: WPInfo plugin
Version: 1.0.3
Author: Richer Yang
Author URI: https://richer.tw/
GitHub Plugin URI: RicherYang/wpinfo-plugin
*/

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WPI_VERSION', '1.0.3');

define('RY_WPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WPI_PLUGIN_BASENAME', plugin_basename(__FILE__));

include_once RY_WPI_PLUGIN_DIR . 'wpi-main.php';

register_activation_hook(__FILE__, ['RY_WPI', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WPI', 'plugin_deactivation']);
register_uninstall_hook(__FILE__, ['RY_WPI', 'plugin_uninstall']);

add_action('plugins_loaded', ['RY_WPI', 'init'], 10);
