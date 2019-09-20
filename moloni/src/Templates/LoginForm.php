<div id='formLogin'>
    <a href='https://moloni.pt/dev/' target='_BLANK'>
        <img src='https://www.moloni.pt/_imagens/_tmpl/bo_logo_topo_01.png' width='300px'> </a>
    <hr>
    <form id='formPerm' method='POST' action='admin.php?page=moloni'>
        <table>
            <tr>
                <td><label for='username'>Utilizador/Email</label></td>
                <td><input type='text' name='user'></td>
            </tr>

            <tr>
                <td><label for='password'>Password</label></td>
                <td><input type='password' name='pass'></td>
            </tr>

            <?php if ($error): ?>
                <tr>
                    <td></td>
                    <td style='text-align: center;'><?= $error ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <td></td>
                <td>
                    <input type='submit' name='submit' value='login'>
                    <span class='goRight power'>Powered by: Moloni API</span>
                </td>
            </tr>
        </table>
    </form>
</div>
