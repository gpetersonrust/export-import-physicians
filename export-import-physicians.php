<?php
/**
 * Plugin Name: Export Import Physicians
 * Plugin URI: https://moxcar.com
 * Description: A WordPress plugin to export and import physicians.
 * Version: 1.0.0
 * Author: Gino Peterson
 * Author URI: https://moxcar.com
 * License: GPL2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'EIP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EIP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EIP_PLUGIN_VERSION', '1.0.0' );

// Include required files
require_once EIP_PLUGIN_DIR . 'includes/admin-pages.php';

// Activation hook
function eip_activate() {
    // Actions to perform on activation
}
register_activation_hook( __FILE__, 'eip_activate' );

// Deactivation hook
function eip_deactivate() {
    // Actions to perform on deactivation
}
register_deactivation_hook( __FILE__, 'eip_deactivate' );

// Uninstall hook
function eip_uninstall() {
    // Actions to perform on uninstall
}
register_uninstall_hook( __FILE__, 'eip_uninstall' );
