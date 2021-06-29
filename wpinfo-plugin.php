<?php
/*
* Plugin Name: WPInfo plugin
* Version: 2.0.6
* Author: Richer Yang
* Author URI: https://richer.tw/
* GitHub Plugin URI: RicherYang/wpinfo-plugin
* Text Domain: wpinfo-plugin
* Domain Path: /languages/
*/

function_exists('plugin_dir_url') or exit('No direct script access allowed');

define('RY_WPI_VERSION', '2.0.6');

define('RY_WPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_WPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_WPI_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once RY_WPI_PLUGIN_DIR . 'wpi-main.php';

register_activation_hook(__FILE__, ['RY_WPI', 'plugin_activation']);
register_deactivation_hook(__FILE__, ['RY_WPI', 'plugin_deactivation']);
register_uninstall_hook(__FILE__, ['RY_WPI', 'plugin_uninstall']);

RY_WPI::init();
