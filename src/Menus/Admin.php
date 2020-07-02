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
        if (current_user_can('manage_woocommerce')) {
            $logoDir = MOLONI_IMAGES_URL . 'small_logo.png';
            add_menu_page(__('Moloni', 'Moloni'), __('Moloni', 'Moloni'), 'manage_woocommerce', 'moloni', [$this->parent, 'run'], $logoDir, 55.5);
        }
    }
}
