<?php

namespace Moloni\Menus;

use Moloni\Plugin;

class Admin
{

    public $parent;

    /**
     *
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('admin_menu', [$this, 'admin_menu'], 55.5);
        add_action('admin_notices', '\Moloni\Notice::showMessages');
    }

    public function admin_menu()
    {
        $permission = apply_filters( 'moloni_admin_menu_permission', 'manage_woocommerce');
        if (current_user_can($permission)) {
            $logoDir = MOLONI_IMAGES_URL . 'small_logo.png';
            add_menu_page(__('Moloni', 'Moloni'), __('Moloni', 'Moloni'), $permission, 'moloni', [$this->parent, 'run'], $logoDir, 55.5);
        }
    }
}
