<div class='outBoxEmpresa'>
    <h2><?= __("Bem vindo! Aqui pode seleccionar qual a empresa que pretende ligar com o WooCoommerce") ?></h2>
    <?php if (isset($companies) && is_array($companies)) : ?>
        <?php foreach ($companies as $key => $company) : ?>
            <?php if ($company['company_id'] == 5) continue; ?>
            <div class="caixaLoginEmpresa"
                 onclick="window.location.href = 'admin.php?page=moloni&company_id=<?= $company["company_id"] ?>'"
                 title="<?= __("Login/Entrar") ?> <?= $company["name"] ?>">

                <span><b><?= $company["name"] ?></b></span>
                <br><?= $company["address"] ?>
                <br><?= $company["zip_code"] ?>
                <p><b><?= __("Contribuinte") ?>: </b><?= $company["vat"] ?></p></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>