<?php
/**
 * Plugin Name: Shipping
 * Description: نظام شامل لإدارة الشحن المحلي والدولي المحلي والدولي.
 * Version: 97.3.0
 * Author: Shipping
 * Language: ar
 * Text Domain: shipping
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SHIPPING_VERSION', '97.3.0');
define('SHIPPING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHIPPING_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_shipping() {
    require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-activator.php';
    Shipping_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_shipping() {
    require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping-deactivator.php';
    Shipping_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_shipping');
register_deactivation_hook(__FILE__, 'deactivate_shipping');

/**
 * Core class used to maintain the plugin.
 */
require_once SHIPPING_PLUGIN_DIR . 'includes/class-shipping.php';

function run_shipping() {
    $plugin = new Shipping();
    $plugin->run();
}

run_shipping();
