<?php

namespace Moloni\Helpers;

class Security
{
    const AJAX_NONCE = 'moloni-ajax-nonce';

    const FORM_NONCE = 'moloni-form-nonce';

    /** Actions that only read data (open/download a document), so they don't need a nonce */
    const READ_ONLY_ACTIONS = ['getInvoice', 'downloadDocument'];

    /**
     * Adds the plugin's nonce to an admin URL (unescaped, escape it for the output context)
     *
     * @param string $url
     *
     * @return string
     */
    public static function getNonceUrl(string $url): string
    {
        return add_query_arg('_wpnonce', wp_create_nonce(self::FORM_NONCE), $url);
    }

    /**
     * Same capability that gates the plugin menu
     *
     * @return bool
     */
    public static function verifyUserCanAccessWc(): bool
    {
        return current_user_can(apply_filters('moloni_admin_menu_permission', 'manage_woocommerce'));
    }

    /**
     * Every AJAX request must carry a valid nonce
     *
     * @return void
     */
    public static function verifyAjaxRequestOrDie(): void
    {
        if (!check_ajax_referer(self::AJAX_NONCE, '_wpnonce', false)) {
            wp_send_json_error('Invalid security token', 403);
        }
    }

    /**
     * Every request to the plugin page that changes data must carry a valid nonce
     *
     * @return void
     */
    public static function verifyRequestOrDie(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::verifyNonceOrDie($_POST['_wpnonce'] ?? '');

            return;
        }

        $action = sanitize_text_field($_GET['action'] ?? '');

        // GET requests only need a nonce when they change data (run an action or select a company)
        $changesData = isset($_GET['company_id']) || (!empty($action) && !in_array($action, self::READ_ONLY_ACTIONS, true));

        if (!$changesData) {
            return;
        }

        self::verifyNonceOrDie($_GET['_wpnonce'] ?? '');
    }

    private static function verifyNonceOrDie($nonce): void
    {
        $nonce = sanitize_text_field(wp_unslash($nonce));

        if (!wp_verify_nonce($nonce, self::FORM_NONCE)) {
            wp_die(esc_html__('A ligação expirou. Volte atrás e tente novamente.'), '', ['response' => 403, 'back_link' => true]);
        }
    }
}
