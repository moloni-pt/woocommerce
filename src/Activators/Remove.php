<?php

namespace Moloni\Activators;

use WP_Site;

class Remove
{
    public static function run()
    {
        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                self::dropTables($wpdb->get_blog_prefix($site->blog_id));
            }
        } else {
            self::dropTables($wpdb->get_blog_prefix());
        }

        wp_clear_scheduled_hook('moloniProductsSync');
    }

    public static function uninitializeSite(WP_Site $site)
    {
        global $wpdb;

        self::dropTables($wpdb->get_blog_prefix($site->blog_id));
    }

    private static function dropTables($prefix = null)
    {
        global $wpdb;

        $wpdb->query("DROP TABLE " . $prefix . "moloni_api");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_api_config");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_logs");
        $wpdb->query("DROP TABLE " . $prefix . "moloni_sync_logs");
    }
}
