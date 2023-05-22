<?php

namespace Moloni\Activators;

use WP_Site;

class Install
{
    /**
     * Run the installation process
     * Install API Connection table
     * Install Settings table
     * Start sync crons
     */
    public static function run(): void
    {
        if (!function_exists('curl_version')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('cURL library is required for using Moloni Plugin.', 'moloni-pt'));
        }

        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Requires WooCommerce 3.0.0 or above.', 'moloni-pt'));
        }

        global $wpdb;

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $prefix = $wpdb->get_blog_prefix($site->blog_id);

                self::createTables($prefix);
                self::insertSettings($prefix);
            }
        } else {
            $prefix = $wpdb->get_blog_prefix();

            self::createTables($prefix);
            self::insertSettings($prefix);
        }
    }

    public static function initializeSite(WP_Site $site)
    {
        global $wpdb;

        $prefix = $wpdb->get_blog_prefix($site->blog_id);

        self::createTables($prefix);
        self::insertSettings($prefix);
    }

    /**
     * Create API connection table
     */
    private static function createTables(string $prefix): void
    {
        global $wpdb;

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_api`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                main_token VARCHAR(100), 
                refresh_token VARCHAR(100), 
                client_id VARCHAR(100), 
                client_secret VARCHAR(100), 
                company_id INT,
                dated TIMESTAMP default CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_api_config`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                config VARCHAR(100), 
                description VARCHAR(100), 
                selected VARCHAR(100), 
                changed TIMESTAMP default CURRENT_TIMESTAMP
			) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_sync_logs` (
			    log_id INT NOT NULL AUTO_INCREMENT,
                type_id INT NOT NULL,
                entity_id INT NOT NULL,
                sync_date VARCHAR(250) CHARACTER SET utf8 NOT NULL,
			    PRIMARY KEY (`log_id`)
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `" . $prefix . "moloni_logs` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_level VARCHAR(100) NULL,
                company_id INT,
                message TEXT,
                context TEXT,
                created_at TIMESTAMP default CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }

    /**
     * Create Moloni account settings
     */
    private static function insertSettings(string $prefix): void
    {
        global $wpdb;

        $wpdb->query("INSERT INTO `" . $prefix . "moloni_api_config`(config, description) 
                        VALUES('document_set_id', 'Escolha uma Série de Documentos para melhor organização'),
                        ('exemption_reason', 'Escolha uma Isenção de Impostos para os produtos que não têm impostos'),
                        ('exemption_reason_shipping', 'Escolha uma Isenção de Impostos para os portes que não têmimpostos'),
                        ('payment_method', 'Escolha um metodo de pagamento por defeito'),
                        ('measure_unit', 'Escolha a unidade de medida a usar'),
                        ('maturity_date', 'Prazo de Pagamento'),
                        ('document_status', 'Escolha o estado do documento (fechado ou em rascunho)'),
                        ('document_type', 'Escolha o tipo de documentos que deseja emitir'),
                        ('client_prefix', 'Prefixo da referência do cliente'),
                        ('product_prefix', 'Prefixo da referência do produto'),
                        ('use_moloni_product_details', 'Usar os detalhes do artigo já existente no Moloni'),
                        ('update_final_consumer', 'Actualizar consumidor final'),
                        ('shipping_info', 'Informação de envio'),
                        ('vat_field', 'Número de contribuinte'),
                        ('email_send', 'Enviar email'),
                        ('moloni_stock_sync', 'Sincronizar Stocks'),
                        ('moloni_product_sync', 'Inserir Artigos'),
                        ('moloni_product_sync_update', 'Actualizar Artigos')
                        ");
    }
}
