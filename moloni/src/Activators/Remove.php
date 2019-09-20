<?php

namespace Moloni\Activators;

class Remove
{
    public static function run()
    {
        global $wpdb;
        $wpdb->query("DROP TABLE moloni_api");
        $wpdb->query("DROP TABLE moloni_api_config");
        wp_clear_scheduled_hook('moloniProductsSync');
    }

}
