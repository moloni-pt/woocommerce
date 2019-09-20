<div class='outBoxEmpresa'>
    <h2>Bem vindo! Aqui pode seleccionar qual a empresa que pretende ligar com o WooCoommerce</h2>
    <?php foreach ($companies as $key => $company) : ?>
        <?php if ($company['company_id'] == 5) continue; ?>
        <div class="caixaLoginEmpresa"
             onclick="window.location.href = 'admin.php?page=moloni&company_id=<?= $company["company_id"] ?>'"
             title="Login/Entrar <?= $company["name"] ?>">
            <div class="caixaLoginEmpresa_logo">
                <span>
                    <?php if (!empty($company["image"])) : ?>
                        <img src="https://www.moloni.pt/_imagens/?macro=imgAC_iconeEmpresa_s2&img=<?= $company["image"] ?>"
                             style="margin:0 10px 0 0; vertical-align:middle;"
                             alt="<?= $company["name"] ?>"
                        >
                    <?php endif; ?>
                </span>
            </div>
            <span class="t14_b"><?= $company["name"] ?></span>
            <br><?= $company["address"] ?>
            <br><?= $company["zip_code"] ?>
            <p><b>Contribuinte: </b><?= $company["vat"] ?></p></div>
    <?php endforeach; ?>
</div>