<?php

namespace Moloni\Activators;

class Remove
{
    public static function run()
    {
        global $wpdb,$bdprefix;
        $sites =get_sites();
        foreach($sites as $site){   
            if($site->blog_id == 1){
                $bdprefix = '';
            }else{
                $bdprefix = '_'.$site->blog_id;
            }
            self::droptables();
        }
        wp_clear_scheduled_hook('moloniProductsSync');
    }

    public static function droptables($prefix = null){
        global $wpdb,$bdprefix;
        if(!empty($prefix))
            $bdprefix = "_".$prefix->blog_id;        
        $wpdb->query("DROP TABLE moloni_api".$bdprefix);
        $wpdb->query("DROP TABLE moloni_api_config".$bdprefix);
    }

}
