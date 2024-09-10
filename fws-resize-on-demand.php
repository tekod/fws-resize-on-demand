<?php

/**
 * Plugin Name: FWS Resize-On-Demand
 * Plugin URI:  https://wordpress.org/plugins/fws-resize-on-demand
 * Description: On-demand image resizer for WordPress.
 * Version:     0.5.0
 * Author:      Miroslav Curcic
 * Author URI:  https://profiles.wordpress.org/tekod
 * Text Domain: fws-resize-on-demand
 * Domain Path: /languages
 * Requires at least: 4.8
 */

defined('ABSPATH') or die();


// constants
define('TEKOD_ROD_PLUGINBASENAME', plugin_basename(__FILE__));
define('TEKOD_ROD_DIR', __DIR__);
define('TEKOD_ROD_VERSION', '0.5.0');


// load classes
require __DIR__.'/src/Activate.php';
require __DIR__.'/src/Deactivate.php';
require __DIR__.'/src/Init.php';


// setup plugin activator and deactivator
function activate_fws_rod_plugin() {
    Tekod\ROD\Activate::activate();
}
register_activation_hook(__FILE__, 'activate_fws_rod_plugin');

function deactivate_fws_rod_plugin() {
    Tekod\ROD\Deactivate::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_fws_rod_plugin');


// start plugin
Tekod\ROD\Init::InitServices();
