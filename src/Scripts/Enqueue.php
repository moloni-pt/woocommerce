<?php

namespace Moloni\Scripts;

/**
 * Class Enqueue
 * Add script files to queue
 *
 * @package Moloni\Scripts
 */
class Enqueue
{
    public static function defines() {
        if (isset($_REQUEST['page']) && !wp_doing_ajax() && sanitize_text_field($_REQUEST['page']) === 'moloni') {
            $ver = '4.6';

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.min.css', MOLONI_PLUGIN_FILE), [], $ver);
            wp_enqueue_script('moloni-scripts', plugins_url('assets/js/moloni.min.js', MOLONI_PLUGIN_FILE), [], $ver);
        }
    }
}
