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
            wp_enqueue_style('jquery-modal', plugins_url('assets/external/jquery.modal.min.css', MOLONI_PLUGIN_FILE));
            wp_enqueue_script('jquery-modal', plugins_url('assets/external/jquery.modal.min.js', MOLONI_PLUGIN_FILE));

            wp_enqueue_style('moloni-styles', plugins_url('assets/css/moloni.css', MOLONI_PLUGIN_FILE));

            if (!in_array($_GET['tab'], ['settings', 'tools'])) {
                wp_enqueue_script('moloni-actions-bulk-documentes-js', plugins_url('assets/js/BulkDocuments.js', MOLONI_PLUGIN_FILE));
            }
        }
    }
}
