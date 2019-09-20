<?php
/**
 *
 *   Plugin Name:  Moloni
 *   Plugin URI:   https://plugins.moloni.com/woocommerce
 *   Description:  Send your orders automatically to your Moloni invoice software
 *   Version:      0.0.1
 *   Author:       Moloni.com
 *   Author URI:   https://moloni.com
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

namespace Moloni\Controllers;

class PendingOrders
{
    private static $limit = 50;
    private static $ordersStatuses = ["wc-processing", "wc-completed"];

    public static function getAllAvailable()
    {
        $ordersList = [];
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => self::$ordersStatuses,
            'posts_per_page' => self::$limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_moloni_sent',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_moloni_sent',
                    'value' => '0',
                    'compare' => '='
                )
            ),
        );

        $query = new \WP_Query($args);
        $orders = $query->posts;

        foreach ($orders as $order) {

            $orderDetails = new \WC_Order($order->ID);
            $meta = self::getPostMeta($orderDetails->get_order_number());
            $status = get_post_status_object(get_post_status($orderDetails->get_order_number()));

            if (!isset($meta["_moloni_sent"]) || $meta["_moloni_sent"] == "0") {
                $ordersList[] = [
                    "info" => $meta,
                    "status" => $status->label,
                    "id" => $orderDetails->get_order_number()
                ];
            }
        }
        
        return $ordersList;
    }

    public static function getPostMeta($postId)
    {
        $metas = [];
        $metaKeys = get_post_meta($postId);

        if (!empty($metaKeys) && is_array($metaKeys)) {
            foreach ($metaKeys as $key => $meta) {
                $metas[$key] = $meta[0];
            }
        }

        return $metas;
    }
}