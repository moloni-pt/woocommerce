<?php

namespace Moloni\Hooks;

use Moloni\Plugin;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

class WoocommerceInitialize
{
    public $parent;

    /**
     * Constructor
     *
     * @see https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#declaring-extension-incompatibility
     *
     * @param Plugin $parent
     */
    public function __construct(Plugin $parent)
    {
        $this->parent = $parent;
        add_action('before_woocommerce_init', [$this, 'beforeWoocommerceInit']);
    }

    public function beforeWoocommerceInit()
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', MOLONI_PLUGIN_FILE, true);
        }
    }
}