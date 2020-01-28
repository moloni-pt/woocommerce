<?php

namespace Moloni\Hooks;

use Moloni\Plugin;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the order view
 * There they can create a document for that order or check the document if it was already created
 * @package Moloni\Hooks
 */
class OrderView
{

    public $parent;

    /** @var array */
    private $allowedStatus = ['wc-processing', 'wc-completed'];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box()
    {
        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'showMoloniView'], 'shop_order', 'side', 'core');
    }

    function showMoloniView($post)
    {
        if (in_array($post->post_status, $this->allowedStatus)) : ?>
            <?php $documentId = get_post_meta($post->ID, '_moloni_sent', true); ?>
            <?php if ((int)$documentId > 0) : ?>
                <?= __('O documento já foi gerado no moloni') ?>
                <a type="button"
                   class="button button-primary"
                   target="_BLANK"
                   href="<?= admin_url('admin.php?page=moloni&action=getInvoice&id=' . $documentId) ?>"
                   style="margin-top: 10px; float:right;"
                >
                    <?= __('Ver documento') ?>
                </a>
                <div style="clear:both"></div>
                <a type="button"
                   class="button"
                   target="_BLANK"
                   href="<?= admin_url('admin.php?page=moloni&action=genInvoice&id=' . $post->ID) ?>"
                   style="margin-top: 10px; float:right;"
                >
                    <?= __('Gerar novamente') ?>
                </a>
            <?php elseif ($documentId == -1) : ?>
                <?= __('O documento foi marcado como gerado.') ?>
                <br>
                <a type="button"
                   class="button"
                   target="_BLANK"
                   href="<?= admin_url('admin.php?page=moloni&action=genInvoice&id=' . $post->ID) ?>"
                   style="float:right"
                >
                    <?= __('Gerar novamente') ?>
                </a>
            <?php else: ?>
                <a type="button"
                   class="button button-primary"
                   target="_BLANK"
                   href="<?= admin_url('admin.php?page=moloni&action=genInvoice&id=' . $post->ID) ?>"
                   style="float:right"
                >
                    <?= __('Gerar documento moloni') ?>
                </a>
            <?php endif; ?>
            <div style="clear:both"></div>
        <?php else : ?>
            <?= __('A encomenda tem que ser dada como paga para poder ser gerada.') ?>
        <?php endif;
    }

}
