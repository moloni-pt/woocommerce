<?php
if (!defined('ABSPATH')) {
    exit;
}

$hasValidCompany = false;
?>

<?php if (isset($companies) && is_array($companies)) : ?>
    <div class='outBoxEmpresa'>
            <?php foreach ($companies as $key => $company) : ?>
                <?php
                    if ($company['company_id'] == 5) {
                        continue;
                    }
                ?>

                <!-- First valid company, so we need to render the title -->
                <?php if ($hasValidCompany === false) : ?>
                    <h2>
                        <?= __("Bem vindo! Aqui pode seleccionar qual a empresa que pretende ligar com o WooCoommerce") ?>
                    </h2>
                <?php endif; ?>

                <?php $hasValidCompany = true;?>

                <div class="caixaLoginEmpresa"
                     onclick="window.location.href = 'admin.php?page=moloni&company_id=<?= $company["company_id"] ?>'"
                     title="<?= __("Login/Entrar") ?> <?= $company["name"] ?>">

                    <span>
                        <b><?= $company["name"] ?></b>
                    </span>
                    <br>

                    <?= $company["address"] ?>
                    <br>

                    <?= $company["zip_code"] ?>

                    <p>
                        <b><?= __("Contribuinte") ?>: </b><?= $company["vat"] ?>
                    </p>
                </div>
            <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$hasValidCompany) : ?>
    <div class="no-companies__wrapper">
        <img src="<?= MOLONI_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

        <div class="no-companies__title">
            <?= __('Não dispõe de nenhuma empresa válida para o uso do plugin') ?>
        </div>

        <div class="no-companies__message">
            <?= __('Por favor confirme se a sua conta tem acesso a uma empresa ativa e com um plano que lhe permita ter acesso aos plugins.') ?>
        </div>

        <div class="no-companies__help">
            <?= __('Saiba mais sobre os nossos planos em: ') ?>
            <a href="https://www.moloni.pt/planos/" target="_blank">https://www.moloni.pt/planos/</a>
        </div>

        <button class="button button-primary"
                onclick="window.location.href = 'admin.php?page=moloni&action=logout'">
            <?= __('Voltar ao login') ?>
        </button>
    </div>
<?php endif; ?>
