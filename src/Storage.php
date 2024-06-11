<?php

namespace Moloni;

use Moloni\Helpers\Logger;

/**
 * Class Storage
 *
 * @package Moloni
 */
class Storage
{
    public static $MOLONI_SESSION_ID;
    public static $MOLONI_ACCESS_TOKEN;
    public static $MOLONI_COMPANY_ID;

    /**
     * Checks if new order system is being used
     *
     * @var bool
     */
    public static $USES_NEW_ORDERS_SYSTEM = false;

    /**
     * Logger instance
     *
     * @var Logger|null
     */
    public static $LOGGER;
}
