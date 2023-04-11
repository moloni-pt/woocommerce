<?php

namespace Moloni;

use Exception;
use Moloni\Controllers\SyncProducts;

/**
 * This crons will run in isolation
 */
class Crons
{
    public static function addCronInterval($schedules = [])
    {
        $schedules['everyficeminutes'] = array(
            'interval' => 300,
            'display' => __('A cada cinco minutos')
        );

        return $schedules;
    }

    /**
     * @return bool
     * @global $wpdb
     */
    public static function productsSync()
    {
        global $wpdb;
        $runningAt = time();
        try {
            self::requires();

            if (!Start::login(true)) {
                Log::write('Não foi possível estabelecer uma ligação a uma empresa Moloni');
                return false;
            }

            if (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC !== 0) {
                Log::write('A iniciar a sincronização de stocks automática...');
                if (!defined('MOLONI_STOCK_SYNC_TIME')) {
                    define('MOLONI_STOCK_SYNC_TIME', (time() - 600));
                    $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_api_config', [
                        'config' => 'moloni_stock_sync_time',
                        'selected' => MOLONI_STOCK_SYNC_TIME
                    ]);
                }

                (new SyncProducts(MOLONI_STOCK_SYNC_TIME))->run();
            } else {
                Log::write('Stock sync disabled in plugin settings');
            }

        } catch (Exception $ex) {
            Log::write('Fatal Errror: ' . $ex->getMessage());
        }

        Model::setOption('moloni_stock_sync_time', $runningAt);
        return true;
    }


    public static function requires()
    {
        $composer_autoloader = '../vendor/autoload.php';
        if (is_readable($composer_autoloader)) {
            /** @noinspection PhpIncludeInspection */
            require $composer_autoloader;
        }
    }

}
