<?php

namespace Moloni\Models;

class PendingOrders
{
    private static $limit = 50;
    private static $ordersStatuses = ['wc-processing', 'wc-completed'];
    private static $totalPages = 1;
    private static $currentPage = 1;

    /**
     * Fetch pending orders paginated
     *
     * @see https://github.com/woocommerce/woocommerce/wiki/HPOS:-new-order-querying-APIs
     * @see https://developer.wordpress.org/reference/classes/wp_query/#custom-field-post-meta-parameters
     *
     * @return array
     */
    public static function getAllAvailable(): array
    {
        self::$currentPage = (isset($_GET['paged']) && (int)($_GET['paged']) > 0) ? $_GET['paged'] : 1;

        $args = [
            'post_status' => self::$ordersStatuses,
            'posts_per_page' => self::$limit,
            'paged' => self::$currentPage,
            'orderby' => 'date',
            'paginate' => true,
            'order' => 'DESC',
            'post_type' => 'shop_order', //filter out refunds
            'meta_key'      => '_moloni_sent',
            'meta_compare'  => 'NOT EXISTS',
        ];

        if (defined('ORDER_CREATED_AT_MAX') && !empty(ORDER_CREATED_AT_MAX)) {
            $args['date_created'] = ">=" . ORDER_CREATED_AT_MAX;
        }

        $args = apply_filters('moloni_before_pending_orders_fetch', $args);

        $results = wc_get_orders($args);

        self::$totalPages = $results->max_num_pages;

        return $results->orders;
    }

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
