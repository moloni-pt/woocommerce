<?php

namespace Moloni\Helpers;

use Automattic\WooCommerce\Utilities\OrderUtil;

class Context
{
    public static function isNewOrdersSystemEnabled(): bool
    {
        if (class_exists(OrderUtil::class)) {
            return OrderUtil::custom_orders_table_usage_is_enabled();
        }

        return false;
    }
}