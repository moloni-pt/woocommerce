<?php

namespace Moloni;

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
        'taxes/getAll'
    ];

    /**
     * Save a request cache
     * @var array
     */
    private static $cache = [];

    /**
     * @param $action
     * @param bool|array $values
     * @param bool $debug
     * @return array|bool
     * @throws Error
     */
    public static function simple($action, $values = false, $debug = false)
    {
        if (in_array($action, self::$allowedCachedMethods)) {
            if (isset(self::$cache[$action])) {
                return self::$cache[$action];
            }
        }

        if (is_array($values) && defined('COMPANY_ID')) {
            $values['company_id'] = COMPANY_ID;
        }

        $con = curl_init();
        $url = "https://api.moloni.pt/v1/" . $action . "/?human_errors=true&access_token=" . ACCESS_TOKEN;
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $values ? http_build_query($values) : false);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);

        $raw = curl_exec($con);
        curl_close($con);

        $parsed = json_decode($raw, true);


        $log = [
            'url' => $url,
            'sent' => $values,
            'received' => $parsed
        ];

        self::$logs[] = $log;

        if ($debug) {
            echo "<pre>";
            print_r($log);
            echo "</pre>";
        }

        if (!isset($parsed['error'])) {

            if (in_array($action, self::$allowedCachedMethods)) {
                if (!isset(self::$cache[$action])) {
                    self::$cache[$action] = $parsed;
                }
            }

            return $parsed;
        } else {
            throw new Error(__("Ups, foi encontrado um erro..."), $log);
        }
    }

    /**
     * Returns the last curl request made from the logs
     * @return array
     */
    public static function getLog()
    {
        return end(self::$logs);
    }

    /**
     * Do a login request to the API
     * @param $user
     * @param $pass
     * @return array|bool|mixed|object
     * @throws Error
     */
    public static function login($user, $pass)
    {
        $con = curl_init();
        $url = "https://api.moloni.pt/v1/grant/?grant_type=password";
        $url .= "&client_id=" . self::$moloniClient;
        $url .= "&client_secret=" . self::$moloniSecret;
        $url .= "&username=" . urlencode($user);
        $url .= "&password=" . urlencode($pass);

        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, FALSE);
        curl_setopt($con, CURLOPT_POSTFIELDS, FALSE);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);

        $raw = curl_exec($con);
        curl_close($con);

        $parsed = json_decode($raw, true);
        if (!isset($parsed['error'])) {
            return $parsed;
        } else {
            throw new Error(__("Ups, foi encontrado um erro...", "A combinação de utilizador/password está errada"));
        }
    }

    /**
     * Refresh the session tokens
     * @param $refresh
     * @return array|bool|mixed|object
     */
    public static function refresh($refresh)
    {
        $con = curl_init();
        $url = "https://api.moloni.pt/v1/grant/?grant_type=refresh_token";
        $url .= "&client_id=" . self::$moloniClient;
        $url .= "&client_secret=" . self::$moloniSecret;
        $url .= "&refresh_token=" . $refresh;

        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, FALSE);
        curl_setopt($con, CURLOPT_POSTFIELDS, FALSE);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);

        $res_curl = curl_exec($con);
        curl_close($con);

        $res_txt = json_decode($res_curl, true);
        if (!isset($res_txt['error'])) {
            return ($res_txt);
        } else {
            //base::genError($url, $values, $res_txt);
            return (false);
        }
    }

}
