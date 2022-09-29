<?php

namespace Moloni\Hooks;

use Moloni\Error;
use Moloni\Plugin;
use Moloni\Start;

/**
 * Class OrderList
 * Add a Moloni column orders list
 *
 * @package Moloni\Hooks
 */
class OrderList
{
    /**
     * Caller class
     *
     * @var Plugin
     */
    public $parent;

    /**
     * If user want to show Moloni column
     * @var null|bool
     */
    private static $columnVisible;

    /**
     * OrderList constructor
     *
     * @param Plugin $parent Caller
     *
     * @return void
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        add_filter('manage_edit-shop_order_columns', [$this, 'ordersListAddColumn']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'ordersListManageColumn']);
    }

    /**
     * Appends Moloni column list
     *
     * @param array $oldColumns Columns list
     *
     * @return array
     *
     * @throws Error
     */
    public function ordersListAddColumn($oldColumns)
    {
        if (!$this->canShowColumn()) {
             return $oldColumns;
        }

        $newColumns = [];

        foreach ($oldColumns as $name => $info) {
            $newColumns[$name] = $info;

            if ('order_status' === $name) {
                $newColumns['moloni_document'] = __('Documento Moloni');
            }
        }

        return $newColumns;
    }

    /**
     * Draws Moloni column content
     *
     * @param string $currentColumnName Current column name
     *
     * @return void
     *
     * @throws Error
     */
    public function ordersListManageColumn($currentColumnName)
    {
        if (!$this->canShowColumn()) {
            return;
        }

        global $the_order;

        if ($currentColumnName === 'moloni_document') {
            $documentId = 0;

            $documents = get_post_meta($the_order->ID, '_moloni_sent');

            if (is_array($documents) && !empty($documents)) {
                /** Last item in the array is the latest document */
                $documentId = (int)end($documents);
            }

            if ($documentId > 0) {
                $redirectUrl = admin_url('admin.php?page=moloni&action=downloadDocument&id=' . $documentId);

                echo '<a class="button" target="_blank" onclick="window.open(\'' . $redirectUrl . '\', \'_blank\')">' . __('Descarregar') . '</a>';
            } else {
                echo '<div>' . _('Sem documento associado') . '</div>';
            }
        }
    }

    /**
     * Verifies if user wants to show column
     *
     * @return bool
     *
     * @throws Error
     */
    private function canShowColumn()
    {
        if (self::$columnVisible === null) {
            self::$columnVisible = Start::login(true) && defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 1;
        }

        return self::$columnVisible;
    }
}
