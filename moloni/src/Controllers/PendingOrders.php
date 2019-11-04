<?php

namespace Moloni\Controllers;

use WC_Order;
use WP_Query;

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

        $query = new WP_Query($args);
        $orders = $query->posts;

        foreach ($orders as $order) {

            $orderDetails = new WC_Order($order->ID);
            $meta = self::getPostMeta($order->ID);
            $status = get_post_status_object(get_post_status($order->ID));

            if (!isset($meta["_moloni_sent"]) || $meta["_moloni_sent"] == "0") {
                $ordersList[] = [
                    "info" => $meta,
                    "status" => $status->label,
                    "number" => $orderDetails->get_order_number(),
                    "id" => $order->ID
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