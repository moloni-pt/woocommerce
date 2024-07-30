<?php

namespace Moloni;

use Moloni\Enums\Boolean;
use Moloni\Exceptions\APIException;

class Curl
{

    /** @var string Moloni Client Not-so-secret used for WooCommerce */
    private static $moloniClient = 'devapi';

    /** @var string Moloni Not-so-secret key used for WooCommerce */
    private static $moloniSecret = '53937d4a8c5889e58fe7f105369d9519a713bf43';

    /** @var array Hold the request log */
    private static $logs = [];

    /**
     * Hold a list of methods that can be cached
     * @var array
     */
    private static $allowedCachedMethods = [
        'companies/getOne',
        'countries/getAll',
        'taxes/getAll',
        'currencyExchange/getAll',
        'currencies/getAll',
        'paymentMethods/getAll'
    ];

    /**
     * Hold a list of methods that need to clean cache
     *
     * @var array
     */
    private static $resetCacheMethods = [
        'taxes/insert' => 'taxes/getAll'
    ];

    /**
     * Save a request cache
     * @var array
     */
    private static $cache = [];

    /**
     * Do simple request
     *
     * @param string $action
     * @param bool|array $values
     * @param int $retry
     *
     * @return array|bool
     *
     * @throws APIException
     */
    public static function simple($action, $values = false, $retry = 0)
    {
        $debugMode = defined('MOLONI_DEBUG_MODE') && (int)MOLONI_DEBUG_MODE === Boolean::YES;

        if (isset(self::$cache[$action]) && in_array($action, self::$allowedCachedMethods, false)) {
            return self::$cache[$action];
        }

        if (is_array($values) && Storage::$MOLONI_COMPANY_ID) {
            $values['company_id'] = Storage::$MOLONI_COMPANY_ID;
        }

        $url = 'https://api.moloni.pt/v2/' . $action . '/?human_errors=true&access_token=' . Storage::$MOLONI_ACCESS_TOKEN;

        $response = wp_remote_post($url, [
            'body' => http_build_query($values),
            'timeout' => 45
        ]);

        $responseCode = (int)wp_remote_retrieve_response_code($response);
        $responseMessage = wp_remote_retrieve_response_message($response);

        if ($responseCode === 429) {
            $retry++;

            if ($retry < 5) {
                sleep(2);
                return self::simple($action, $values, $retry);
            }
        }

        $raw = wp_remote_retrieve_body($response);

        $parsed = json_decode($raw, true);

        $log = [
            'url' => $url,
            'sent' => $values,
            'received' => $parsed
        ];

        if ($debugMode) {
            $log['response_code'] = $responseCode;
            $log['response_message'] = $responseMessage;

            if (is_wp_error($response)) {
                $log['error_messages'] = $response->get_error_messages();
                $log['error_codes'] = $response->get_error_codes();
            }
        }

        self::$logs[] = $log;

        if (!isset($parsed['error'])) {
            if (!isset(self::$cache[$action]) && in_array($action, self::$allowedCachedMethods, false)) {
                self::$cache[$action] = $parsed;
            }

            if (isset(self::$resetCacheMethods[$action])) {
                unset(self::$cache[self::$resetCacheMethods[$action]]);
            }

            return $parsed;
        }

        throw new APIException(__('Ups, foi encontrado um erro...'), $log);
    }

    /**
     * Returns the last curl request made from the logs
     *
     * @return array
     */
    public static function getLog(): array
    {
        $result = end(self::$logs);
        if (!is_array($result)) {
            return [];
        }

        return $result;
    }

    /**
     * Returns the last curl request made from the logs
     *
     * @return array
     */
    public static function getLogs(): array
    {
        return self::$logs ?? [];
    }

    /**
     * Do a login request to the API
     * @param $user
     * @param $pass
     * @return array|bool|mixed|object
     *
     * @throws APIException
     */
    public static function login($user, $pass)
    {
        $url = 'https://api.moloni.pt/v2/grant/?grant_type=password';
        $url .= '&client_id=' . self::$moloniClient;
        $url .= '&client_secret=' . self::$moloniSecret;
        $url .= '&username=' . urlencode($user);
        $url .= '&password=' . urlencode($pass);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            throw new APIException($response->get_error_message(), [
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
                'message' => $response->get_error_message(),
            ]);
        }

        $raw = wp_remote_retrieve_body($response);

        $parsed = json_decode($raw, true);

        if (!isset($parsed['error'])) {
            return $parsed;
        }

        $log = [
            'url' => $url,
            'sent' => [],
            'received' => $parsed
        ];

        throw new APIException(__('O seu e-mail ou palavra-passe estão errados.'), $log);
    }

    /**
     * Refresh the session tokens
     * @param $refresh
     * @return array|bool|mixed|object
     */
    public static function refresh($refresh)
    {
        $url = 'https://api.moloni.pt/v2/grant/?grant_type=refresh_token';
        $url .= '&client_id=' . self::$moloniClient;
        $url .= '&client_secret=' . self::$moloniSecret;
        $url .= '&refresh_token=' . $refresh;

        $response = wp_remote_get($url);
        $raw = wp_remote_retrieve_body($response);

        $res_txt = json_decode($raw, true);
        if (!isset($res_txt['error'])) {
            return ($res_txt);
        }

        return false;
    }

}
