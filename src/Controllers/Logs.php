<?php

namespace Moloni\Controllers;

use Moloni\Storage;

class Logs
{
    private static $limit = 50;
    private static $totalPages = 1;
    private static $currentPage = 1;

    public static function getAllAvailable(): array
    {
        self::$currentPage = (isset($_GET['paged']) && (int)($_GET['paged']) > 0) ? $_GET['paged'] : 1;

        return self::getAll();
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

    public static function removeOlderLogs()
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "DELETE FROM `" . $wpdb->get_blog_prefix() . "moloni_logs` WHERE created_at < %s",
            date('Y-m-d H:i:s', strtotime("-1 week"))
        );

        $wpdb->query($query);
    }

    //           Privates           //

    /**
     * Fetch logs
     *
     * @return array
     */
    private static function getAll(): array
    {
        global $wpdb;

        $limit = self::$limit;
        $offset = self::$currentPage <= 1 ? 0 : (self::$currentPage - 1) * self::$limit;
        $companyId = Storage::$MOLONI_COMPANY_ID ?? 0;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . $wpdb->get_blog_prefix() . "moloni_logs` WHERE company_id = %d",
            $companyId
        );
        $queryResult = $wpdb->get_row($query, ARRAY_A);

        $numLogs = (int)($queryResult['COUNT(*)'] ?? 0);

        /** Can safely return if there are no logs */
        if ($numLogs === 0) {
            return [];
        }

        self::$totalPages = floor($numLogs / self::$limit);

        $query = $wpdb->prepare(
            "SELECT * FROM `" . $wpdb->get_blog_prefix() . "moloni_logs` 
            WHERE
               company_id = %d 
            ORDER BY id DESC
            LIMIT %d
            OFFSET %d
            ",
            $companyId,
            $limit,
            $offset
        );

        return $wpdb->get_results($query, ARRAY_A);
    }
}