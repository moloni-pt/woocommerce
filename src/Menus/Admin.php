<?php

namespace Moloni\Menus;

use Moloni\Plugin;

class Admin
{
    /** @var int This should be the same as WooCommerce to keep menus together */
    private $menuPosition = 56;

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('admin_menu', [$this, 'admin_menu'], $this->menuPosition);
        add_action('admin_notices', '\Moloni\Notice::showMessages');
    }

    public function admin_menu(): void
    {
        $permission = apply_filters('moloni_admin_menu_permission', 'manage_woocommerce');
        if (current_user_can($permission)) {
            $logoDir = MOLONI_IMAGES_URL . 'small_logo.png';
            add_menu_page(
                __('Moloni', 'Moloni'),
                __('Moloni', 'Moloni'),
                $permission,
                'moloni',
                [$this->parent, 'run'],
                $logoDir,
                $this->menuPosition
            );
        }
    }
}
