<?php

namespace Moloni;

/**
 * Class Start
 * This is one of the main classes of the module
 * Every call should pass here before
 * This will render the login form or the company form or it will return a bol
 * This will also handle the tokens
 * @package Moloni
 */
class Start
{
    /** @var bool */
    private static $ajax = false;

    /**
     * Handles session, login and settings
     *
     * @param bool $ajax
     *
     * @return bool
     */
    public static function login($ajax = false)
    {
        global $wpdb;

        $action = isset($_REQUEST['action']) ? sanitize_text_field(trim($_REQUEST['action'])) : '';
        $username = isset($_POST['user']) ? sanitize_email(trim($_POST['user'])) : '';
        $password = isset($_POST['pass']) ? stripslashes(sanitize_text_field(trim($_POST['pass']))) : '';

        if ($ajax) {
            self::$ajax = true;
        }

        if (!empty($username) && !empty($password)) {
            $loginValid = false;
            $errorMessage = '';
            $errorBag = [];

            try {
                $login = Curl::login($username, $password);

                if ($login && isset($login['access_token'])) {
                    $loginValid = true;

                    Model::setTokens($login['access_token'], $login['refresh_token']);
                }
            } catch (Error $e) {
                $errorMessage = $e->getMessage();
                $errorBag = $e->getRequest();
            }

            if (!$loginValid) {
                self::loginForm($errorMessage, $errorBag);
                return false;
            }
        }

        if ($action === 'save') {
            self::saveSettings();
        }

        if ($action === 'logout') {
            Model::resetTokens();
        }

        $tokensRow = Model::getTokensRow();
        if (!empty($tokensRow['main_token']) && !empty($tokensRow['refresh_token'])) {
            Model::defineConfigs();
            Model::refreshTokens();
            Model::defineValues();

            if (empty(Storage::$MOLONI_ACCESS_TOKEN)) {
                self::loginForm();
                return false;
            }

            if (Storage::$MOLONI_COMPANY_ID) {
                return true;
            }

            if (isset($_GET['company_id'])) {
                $wpdb->update($wpdb->get_blog_prefix() . 'moloni_api', [
                    'company_id' => (int)$_GET['company_id']
                ],
                    [
                        'id' => Storage::$MOLONI_SESSION_ID
                    ]
                );

                Model::defineValues();
                return true;
            }

            self::companiesForm();
            return false;
        }

        self::loginForm();
        return false;
    }

    /**
     * Shows a login form
     *
     * @param bool|string $error Is used in include
     * @param bool|array $errorData Is used in include
     */
    public static function loginForm($error = false, $errorData = false)
    {
        if (!self::$ajax) {
            include(MOLONI_TEMPLATE_DIR . 'LoginForm.php');
        }
    }

    /**
     * Draw all companies available to the user
     * Except the
     */
    public static function companiesForm()
    {
        try {
            $companies = Curl::simple('companies/getAll', []);
        } catch (Error $e) {
            $companies = [];
        }

        if (!self::$ajax) {
            include(MOLONI_TEMPLATE_DIR . 'CompanySelect.php');
        }
    }

    /**
     * Save plugin settings
     *
     * @return void
     */
    private static function saveSettings(): void
    {
        add_settings_error('general', 'settings_updated', __('Alterações guardadas.'), 'updated');
        $options = $_POST['opt'];

        foreach ($options as $option => $value) {
            $option = sanitize_text_field($option);
            $value = sanitize_text_field($value);

            Model::setOption($option, $value);
        }
    }
}
