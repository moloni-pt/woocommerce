<?php

namespace Moloni;

class Model
{
    /**
     * Return the row of moloni_api table with all the session details
     * @return array|false
     * @global $wpdb
     */
    public static function getTokensRow()
    {
        global $wpdb;
        $results = $wpdb->get_row("SELECT * FROM moloni_api ORDER BY id DESC", ARRAY_A);
        return $results;
    }

    /**
     * Clear moloni_api and set new access and refresh token
     * @param string $accessToken
     * @param string $refreshToken
     * @return array|false
     * @global $wpdb
     */
    public static function setTokens($accessToken, $refreshToken)
    {
        global $wpdb;
        $wpdb->query("TRUNCATE moloni_api");
        $wpdb->insert('moloni_api', ['main_token' => $accessToken, 'refresh_token' => $refreshToken]);
        return self::getTokensRow();
    }

    /**
     * Check if a setting exists on database and update it or create it
     * @param string $option
     * @param string $value
     * @return int
     * @global $wpdb
     */
    public static function setOption($option, $value)
    {
        global $wpdb;
        $setting = $wpdb->get_row($wpdb->prepare("SELECT * FROM moloni_api_config WHERE config = %s", $option), ARRAY_A);

        if (!empty($setting)) {
            $wpdb->update('moloni_api_config', ['selected' => $value], ['config' => $option]);
        } else {
            $wpdb->insert('moloni_api_config', ['selected' => $value, 'config' => $option]);
        }

        return $wpdb->insert_id;
    }

    /**
     * Checks if tokens need to be refreshed and refreshes them
     * If it fails, log user out
     * @return array|false
     * @global $wpdb
     */
    public static function refreshTokens()
    {
        global $wpdb;
        $tokensRow = self::getTokensRow();

        $expire = false;
        if (!isset($tokensRow['expiretime'])) {
            $wpdb->query("ALTER TABLE moloni_api ADD expiretime varchar(250)");
        } else {
            $expire = $tokensRow['expiretime'];
        }

        if (!$expire || $expire < time()) {
            $results = Curl::refresh($tokensRow['refresh_token']);

            if (!isset($results['access_token'])) {
                $wpdb->query("TRUNCATE moloni_api");
                return false;
            }

            $wpdb->update(
                "moloni_api", [
                    "main_token" => $results['access_token'],
                    "refresh_token" => $results['refresh_token'],
                    "expiretime" => time() + 3000
                ], ["id" => $tokensRow['id']]
            );
        }

        return self::getTokensRow();
    }

    /**
     * Define constants from database
     */
    public static function defineValues()
    {
        $tokensRow = self::getTokensRow();
        define("MOLONI_SESSION_ID", $tokensRow['id']);
        define("MOLONI_ACCESS_TOKEN", $tokensRow['main_token']);

        if (!empty($tokensRow['company_id'])) {
            define("MOLONI_COMPANY_ID", $tokensRow['company_id']);
        }
    }

    /**
     * Define company selected settings
     */
    public static function defineConfigs()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM moloni_api_config ORDER BY id DESC", ARRAY_A);
        foreach ($results as $result) {
            $setting = strtoupper($result['config']);
            if (!defined($setting)) {
                define($setting, $result['selected']);
            }
        }
    }

    /**
     * Get all available custom fields
     * @return array
     */
    public static function getCustomFields()
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT DISTINCT meta_key FROM " . $wpdb->prefix . "postmeta ORDER BY `" . $wpdb->prefix . "postmeta`.`meta_key` ASC",
            ARRAY_A
        );

        $customFields = [];
        if ($results && is_array($results)) {
            foreach ($results as $result) {
                $customFields[] = $result;
            }
        }
        return $customFields;
    }

    public static function resetTokens()
    {
        global $wpdb;
        $wpdb->query("TRUNCATE moloni_api");
        return self::getTokensRow();
    }

}
