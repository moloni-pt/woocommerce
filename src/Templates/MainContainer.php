<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

    <div class="header">
        <img src="<?= MOLONI_IMAGES_URL ?>logo.svg" width='300px' alt="Moloni">
    </div>

<?php settings_errors(); ?>

    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?= admin_url('admin.php?page=moloni') ?>"
           class="nav-tab <?= (isset($_GET['tab'])) ?: 'nav-tab-active' ?>">
            <?= __('Encomendas') ?>
        </a>

        <a href="<?= admin_url('admin.php?page=moloni&tab=settings') ?>"
           class="nav-tab <?= (isset($_GET['tab']) && $_GET['tab'] === 'settings') ? 'nav-tab-active' : '' ?>">
            <?= __('Configurações') ?>
        </a>

        <a href="<?= admin_url('admin.php?page=moloni&tab=tools') ?>"
           class="nav-tab <?= (isset($_GET['tab']) && $_GET['tab'] === 'tools') ? 'nav-tab-active' : '' ?>">
            <?= __('Ferramentas') ?>
        </a>
    </nav>
<?php

if (isset($pluginErrorException) && $pluginErrorException instanceof \Moloni\Error) {
    $pluginErrorException->showError();
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

switch ($tab) {
    case 'tools':
        include MOLONI_TEMPLATE_DIR . 'Containers/Tools.php';
        break;
    case 'settings':
        include MOLONI_TEMPLATE_DIR . 'Containers/Settings.php';
        break;
    default:
        include MOLONI_TEMPLATE_DIR . 'Containers/PendingOrders.php';
        break;
}

