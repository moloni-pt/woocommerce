<?php
/*
  Plugin Name:  Moloni
  Plugin URI:   https://plugins.moloni.com/woocommerce
  Description:  Send your orders automatically to your Moloni invoice software
  Version:      0.0.1
  Author:       Moloni.com
  Author URI:   https://moloni.com
  License:      GPL2
  License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 */

define('MOLONI_VERSION', '0.0.1');

function moloni_install()
{
    
}

function moloni_deactivate()
{
    
}

register_activation_hook(__FILE__, 'moloni_install');
register_deactivation_hook(__FILE__, 'moloni_deactivate');

require plugin_dir_path(__FILE__) . 'includes/classes/moloni.class.php';

function moloniRun()
{
    $moloni = new Moloni();
    $moloni->run("Olá");
}

moloniRun();
