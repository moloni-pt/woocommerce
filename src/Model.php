<?php

namespace Moloni;

use Moloni\Services\Mails\AuthenticationExpired;

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
        return $wpdb->get_row("SELECT * FROM " . $wpdb->get_blog_prefix() . "moloni_api ORDER BY id DESC", ARRAY_A);
    }

    /**
     * Clear moloni_api and set new access and refresh token
     *
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @return true
     *
     * @global $wpdb
     */
    public static function setTokens($accessToken, $refreshToken)
    {
        global $wpdb;

        $wpdb->query("TRUNCATE " . $wpdb->get_blog_prefix() . "moloni_api");
        $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_api', ['main_token' => $accessToken, 'refresh_token' => $refreshToken]);

        return true;
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
        $setting = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . $wpdb->get_blog_prefix() . "moloni_api_config WHERE config = %s", $option
            ), ARRAY_A);

        if (!empty($setting)) {
            $wpdb->update($wpdb->get_blog_prefix() . 'moloni_api_config', ['selected' => $value], ['config' => $option]);
        } else {
            $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_api_config', ['selected' => $value, 'config' => $option]);
        }

        return $wpdb->insert_id;
    }

    /**
     * Checks if tokens need to be refreshed and refreshes them
     * If it fails, log user out
     *
     * @param int $retryNumber
     *
     * @return bool
     *
     * @global $wpdb
     */
    public static function refreshTokens($retryNumber = 0)
    {
        global $wpdb;
        $tokensRow = self::getTokensRow();

        $expire = false;

        if (!array_key_exists("expiretime", $tokensRow)) {
            $wpdb->query("ALTER TABLE " . $wpdb->get_blog_prefix() . "moloni_api ADD expiretime varchar(250)");
        } else {
            $expire = $tokensRow['expiretime'];
        }

        if (!$expire || $expire < time()) {
            $results = Curl::refresh($tokensRow['refresh_token']);

            if (isset($results['access_token'])) {
                $wpdb->update($wpdb->get_blog_prefix() . "moloni_api", [
                    "main_token" => $results['access_token'],
                    "refresh_token" => $results['refresh_token'],
                    "expiretime" => time() + 3000
                ], [
                    "id" => $tokensRow['id']
                ]);
            } else {
                $recheckTokens = self::getTokensRow();

                if (empty($recheckTokens) ||
                    empty($recheckTokens['main_token']) ||
                    empty($recheckTokens['refresh_token']) ||
                    $recheckTokens['main_token'] === $tokensRow['main_token'] ||
                    $recheckTokens['refresh_token'] === $tokensRow['refresh_token']) {

                    if ($retryNumber <= 3) {
                        $retryNumber++;

                        return self::refreshTokens($retryNumber);
                    }

                    // Send e-mail notification if email is set
                    if (defined('ALERT_EMAIL') && !empty(ALERT_EMAIL)) {
                        new AuthenticationExpired(ALERT_EMAIL);
                    }

                    Storage::$LOGGER->error(
                        str_replace('{0}', $retryNumber, __('A limpar as tokens depois de {0} tentativas.')),
                        [
                            'tag' => 'core:refreshTokens:error',
                            'request' => $recheckTokens,
                        ]
                    );

                    self::resetTokens();

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Define constants from database
     */
    public static function defineValues(): void
    {
        $tokensRow = self::getTokensRow();

        Storage::$MOLONI_SESSION_ID = $tokensRow['id'] ?? null;
        Storage::$MOLONI_ACCESS_TOKEN = $tokensRow['main_token'] ?? null;
        Storage::$MOLONI_COMPANY_ID = $tokensRow['company_id'] ?? null;
    }

    /**
     * Define company selected settings
     */
    public static function defineConfigs(): void
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM " . $wpdb->get_blog_prefix() . "moloni_api_config ORDER BY id DESC", ARRAY_A);

        if (empty($results)) {
            return;
        }

        foreach ($results as $result) {
            $setting = strtoupper($result['config']);

            if (!defined($setting)) {
                define($setting, $result['selected']);
            }
        }
    }

    /**
     * Get all available custom fields to use as vat field
     *
     * @return array
     */
    public static function getPossibleVatFields(): array
    {
        $customFields = [];
        $args = [
            'posts_per_page' => 50,
            'orderby' => 'date',
            'paginate' => false,
            'order' => 'DESC',
            'post_type' => 'shop_order'
        ];

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return $customFields;
        }

        foreach ($orders as $order) {
            $metas = $order->get_meta_data();

            if (empty($metas)) {
                continue;
            }

            foreach ($metas as $meta) {
                if (in_array($meta->key, $customFields)) {
                    continue;
                }

                $customFields[] = $meta->key;
            }
        }

        return $customFields;
    }

    public static function resetTokens()
    {
        global $wpdb;

        $wpdb->query("TRUNCATE " . $wpdb->get_blog_prefix() . "moloni_api");

        return true;
    }
}
