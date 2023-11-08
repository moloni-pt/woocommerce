<?php

namespace Moloni;

use Exception;
use Moloni\Services\Stocks\SyncStockFromMoloni;

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
     * Service handler
     *
     * @return bool
     *
     * @global $wpdb
     */
    public static function productsSync(): bool
    {
        global $wpdb;

        $runningAt = time();

        try {
            self::requires();

            if (!Start::login(true)) {
                Storage::$LOGGER->error(__('Não foi possível estabelecer uma ligação a uma empresa Moloni'));
                return false;
            }

            if (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC !== 0) {
                if (!defined('MOLONI_STOCK_SYNC_TIME')) {
                    define('MOLONI_STOCK_SYNC_TIME', (time() - 600));

                    $wpdb->insert($wpdb->get_blog_prefix() . 'moloni_api_config', [
                        'config' => 'moloni_stock_sync_time',
                        'selected' => MOLONI_STOCK_SYNC_TIME
                    ]);
                }

                $service = new SyncStockFromMoloni(MOLONI_STOCK_SYNC_TIME);
                $service->run();

                if ($service->countFoundRecord() > 0) {
                    Storage::$LOGGER->info(__('Sincronização de stock automática'), [
                        'action' => 'stock:sync:cron',
                        'since' => $service->getSince(),
                        'equal' => $service->getEqual(),
                        'not_found' => $service->getNotFound(),
                        'get_updated' => $service->getUpdated(),
                        'get_locked' => $service->getLocked(),
                    ]);
                }
            }
        } catch (Exception $ex) {
            Storage::$LOGGER->critical(__('Erro fatal'), [
                'action' => 'stock:sync:cron:error',
                'exception' => $ex->getMessage()
            ]);
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
