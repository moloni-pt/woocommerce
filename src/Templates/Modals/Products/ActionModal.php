<div id="action-modal" class="modal" style="display: none">
    <h2 id="action-modal-title-start" style="display: none;">
        <?php esc_html_e('Processo em progresso') ?>
    </h2>

    <h2 id="action-modal-title-end" style="display: none;">
        <?php esc_html_e('Processo concluído') ?>
    </h2>

    <div id="action-modal-content" style="display: none;"></div>

    <div id="action-modal-spinner" style="display: none;">
        <p>
            <?php esc_html_e('Estamos a processar o seu pedido.') ?>
        </p>

        <img src="<?php echo esc_url( includes_url() . 'js/thickbox/loadingAnimation.gif' ); ?>" />

        <p>
            <?php esc_html_e('Por favor aguarde até terminar o processo!') ?>
        </p>
    </div>

    <div id="action-modal-error" style="display: none;">
        <p>
            <?php esc_html_e('Algo correu mal!') ?>
        </p>
        <p>
            <?php esc_html_e('Verifique os registos para mais informações.') ?>
        </p>
    </div>

    <div class="mt-4">
        <a class="button button-large button-secondary" href="#" rel="modal:close" style="display: none;">
            <?php esc_html_e('Fechar') ?>
        </a>
    </div>
</div>
