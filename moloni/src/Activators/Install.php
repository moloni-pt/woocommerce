<?php

namespace Moloni\Activators;

class Install
{
    /**
     * Run the installation process
     * Install API Connection table
     * Install Settings table
     * Start sync crons
     */
    public static function run()
    {
        global $wpdb;
        define("TP", $wpdb->prefix);

        if (!function_exists('curl_version')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('cURL library is required for using Moloni Plugin.', 'moloni-pt'));
        }

        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Requires WooCommerce 3.0.0 or above.', 'moloni-pt'));
        }

        self::createTables();
        self::insertSettings();
    }

    /**
     * Create API connection table
     */
    private static function createTables()
    {
        global $wpdb;
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `moloni_api`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                main_token VARCHAR(100), 
                refresh_token VARCHAR(100), 
                client_id VARCHAR(100), 
                client_secret VARCHAR(100), 
                company_id INT,
                dated TIMESTAMP default CURRENT_TIMESTAMP
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `moloni_api_config`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                config VARCHAR(100), 
                description VARCHAR(100), 
                selected VARCHAR(100), 
                changed TIMESTAMP default CURRENT_TIMESTAMP
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;"
        );
    }

    /**
     * Create Moloni account settings
     */
    private static function insertSettings()
    {
        global $wpdb;
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('document_set_id', 'Escolha uma Série de Documentos para melhor organização')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('exemption_reason', 'Escolha uma Isenção de Impostos para os produtos que não têm impostos')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('exemption_reason_shipping', 'Escolha uma Isenção de Impostos para os portes que não têmimpostos')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('payment_method', 'Escolha um metodo de pagamento por defeito')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('measure_unit', 'Escolha a unidade de medida a usar')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('maturity_date', 'Prazo de Pagamento')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('document_status', 'Escolha o estado do documento (fechado ou em rascunho)')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('document_type', 'Escolha o tipo de documentos que deseja emitir')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('client_prefix', 'Prefixo da referência do cliente')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('product_prefix', 'Prefixo da referência do produto')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('update_final_consumer', 'Actualizar consumidor final')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('shipping_info', 'Informação de envio')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('vat_field', 'Número de contribuinte')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('email_send', 'Enviar email')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('moloni_stock_sync', 'Sincronizar Stocks')");
        $wpdb->query("INSERT INTO `moloni_api_config`(config, description) VALUES('moloni_products_sync', 'Sincronizar Artigos')");
    }

}
