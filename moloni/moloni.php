<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      2.3.1
 *   Author:       Moloni.com
 *   Author URI:   https://moloni.com
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni;

if (!defined('ABSPATH')) {
    exit;
}

$composer_autoloader = __DIR__ . '/vendor/autoload.php';
if (is_readable($composer_autoloader)) {
    /** @noinspection PhpIncludeInspection */
    require $composer_autoloader;
}

if (!defined('MOLONI_PLUGIN_FILE')) {
    define('MOLONI_PLUGIN_FILE', __FILE__);
}

if (!defined('MOLONI_DIR')) {
    define('MOLONI_DIR', __DIR__);
}

if (!defined('MOLONI_TEMPLATE_DIR')) {
    define('MOLONI_TEMPLATE_DIR', __DIR__ . "/src/Templates/");
}

register_activation_hook(__FILE__, '\Moloni\Activators\Install::run');
register_deactivation_hook(__FILE__, '\Moloni\Activators\Remove::run');

add_action('plugins_loaded', '\Moloni\Start');

function Start()
{
    return new Plugin();
}