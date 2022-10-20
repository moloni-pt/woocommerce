<?php

namespace Moloni\Controllers;

use WC_Order;
use WP_Query;

class PendingOrders
{
    private static $limit = 50;
    private static $ordersStatuses = ['wc-processing', 'wc-completed'];
    private static $totalPages = 1;
    private static $currentPage = 1;

    /**
     * @return array
     */
    public static function getAllAvailable()
    {
        self::$currentPage = (isset($_GET['paged']) && (int)($_GET['paged']) > 0) ? $_GET['paged'] : 1;

        $ordersList = [];
        $args = [
            'post_type' => 'shop_order',
            'post_status' => self::$ordersStatuses,
            'posts_per_page' => self::$limit,
            'paged' => self::$currentPage,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_moloni_sent',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_moloni_sent',
                    'value' => '0',
                    'compare' => '='
                ]
            ],
        ];

        $args = apply_filters('moloni_before_pending_orders_fetch', $args);

        $query = new WP_Query($args);
        self::$totalPages = $query->max_num_pages;

        foreach ($query->posts as $order) {
            $orderDetails = new WC_Order($order->ID);
            $meta = self::getPostMeta($order->ID);
            $status = get_post_status_object(get_post_status($order->ID));

            if (!isset($meta['_moloni_sent']) || (int)$meta['_moloni_sent'] === 0) {
                $ordersList[] = [
                    'info' => $meta,
                    'status' => $status ? $status->label : '',
                    'number' => $orderDetails->get_order_number(),
                    'id' => $order->ID
                ];
            }
        }

        return $ordersList;
    }

    /**
     * @param $postId
     * @return array
     */
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

    /**
     * @return array|string|void
     */
    public static function getPagination()
    {
        $args = [
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => isset($_GET['paged']) ? (int)$_GET['paged'] : 1,
            'total' => self::$totalPages,
        ];

        return paginate_links($args);
    }
}