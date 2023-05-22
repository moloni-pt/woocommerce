<?php

namespace Moloni\Controllers;

use Moloni\Storage;

class Logs
{
    private static $limit = 50;
    private static $totalPages = 1;
    private static $currentPage = 1;

    private static $filterDate = '';
    private static $filterMessage = '';
    private static $filterLevel = '';

    public static function getAllAvailable(): array
    {
        self::$currentPage = (isset($_GET['paged']) && (int)($_GET['paged']) > 0) ? $_GET['paged'] : 1;

        self::$filterDate = $_GET['filter_date'] ?? $_POST['filter_date'] ?? '';
        self::$filterMessage = $_GET['filter_message'] ?? $_POST['filter_message'] ?? '';
        self::$filterLevel = $_GET['filter_level'] ?? $_POST['filter_level'] ?? '';

        return self::getAll();
    }

    public static function getPagination()
    {
        $baseArguments = add_query_arg([
            'paged' => '%#%',
            'filter_date' => self::$filterDate,
            'filter_message' => self::$filterMessage,
            'filter_level' => self::$filterLevel,
        ]);

        $args = [
            'base' => $baseArguments,
            'format' => '',
            'current' => self::$currentPage,
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

        /** Totals */

        $query = "SELECT COUNT(*) FROM `" . $wpdb->get_blog_prefix() . "moloni_logs`";
        $arguments = [];

        self::applyFilters($query, $arguments);

        $queryClean = $wpdb->prepare($query, ...$arguments);
        $queryResult = $wpdb->get_row($queryClean, ARRAY_A);

        $numLogs = (int)($queryResult['COUNT(*)'] ?? 0);

        /** Can safely return if there are no logs */
        if ($numLogs === 0) {
            return [];
        }

        self::$totalPages = ceil($numLogs / self::$limit);

        /** Results */

        $query = "SELECT * FROM `" . $wpdb->get_blog_prefix() . "moloni_logs`";
        $arguments = [];

        self::applyFilters($query, $arguments);

        $query .= ' ORDER BY id DESC LIMIT %d OFFSET %d';
        $arguments[] = $limit;
        $arguments[] = $offset;

        $queryClean = $wpdb->prepare($query, ...$arguments);

        return $wpdb->get_results($queryClean, ARRAY_A);
    }

    //           Auxiliary           //

    private static function applyFilters(&$sql, &$arguments)
    {
        $sql .= ' WHERE company_id = %d';
        $arguments[] = Storage::$MOLONI_COMPANY_ID ?? 0;

        if (!empty(self::$filterMessage)) {
            $sql .= ' AND message LIKE %s';
            $arguments[] = '%' . self::$filterMessage . '%';
        }

        if (!empty(self::$filterLevel)) {
            $sql .= ' AND log_level = %s';
            $arguments[] = self::$filterLevel;
        }

        if (!empty(self::$filterDate)) {
            $sql .= ' AND created_at LIKE %s';
            $arguments[] = self::$filterDate . '%';
        }
    }
}