<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  A forma mais fácil de ligar a sua loja online com a sua faturação.
 *   Version:      3.0.43
 *   Author:       moloni.pt
 *   Author URI:   https://moloni.pt
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni;


if (!defined('ABSPATH')) {
    exit;
}

global $wpdb,$bdprefix;
$prefix = $wpdb->get_blog_prefix();
$prefix = explode('_',$prefix);
if(count($prefix) > 1 && $prefix[1]){
    $bdprefix = '_'.$prefix[1];
}else{
    $bdprefix = '';
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
    define('MOLONI_TEMPLATE_DIR', __DIR__ . '/src/Templates/');
}

if (!defined('MOLONI_PLUGIN_URL')) {
    define('MOLONI_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MOLONI_IMAGES_URL')) {
    define('MOLONI_IMAGES_URL', plugin_dir_url(__FILE__) . 'images/');
}

register_activation_hook(__FILE__, '\Moloni\Activators\Install::run');
register_uninstall_hook(__FILE__, '\Moloni\Activators\Remove::run');
add_action( 'wp_insert_site', '\Moloni\Activators\Install::run' );
add_action( 'wp_uninitialize_site', '\Moloni\Activators\Remove::droptables',10,1 );
 

add_action('plugins_loaded', Start::class);
add_action('admin_enqueue_scripts', '\Moloni\Plugin::defines');

function Start()
{
    return new \Moloni\Plugin();
}