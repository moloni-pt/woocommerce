<?php
namespace Moloni;

class ViewHooks
{

    public $parent;

    /**
     * 
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box($post)
    {
        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'make_invoice_html'], 'shop_order', 'side', 'core');
    }

    function make_invoice_html($post)
    {
        if ($post->post_status == "wc-processing" || $post->post_status == "wc-completed") {
            $meta = get_post_meta($post->ID);
            if (isset($meta['_moloni_sent'][0])) {
                echo "O documento já foi gerado no moloni<br>";
                echo '	<div style="height: 24px; margin-top: 10px;">
						<a type="button" class="button button-primary" target="_BLANK" style="float:right" href="admin.php?page=moloni_settings&action=getInvoice&id=' . $meta['_moloni_sent'][0] . '">
							Ver documento
                        </a>
						';
                echo '      <a type="button" class="button" target="_BLANK" style="float:left" href="admin.php?page=moloni_settings&action=genInvoice&id=' . $post->ID . '">
							Gerar novamente
                        </a>
                    </div>';
            } else {
                echo '	<div style="height: 24px">
						<a type="button" class="button button-primary" target="_BLANK" style="float:right" href="admin.php?page=moloni_settings&action=genInvoice&id=' . $post->ID . '">
							Gerar documento moloni
						</a>
                    </div>';
            }
        } else {
            echo "A encomenda tem que ser dada como paga para poder ser gerada.";
        }
    }

}
