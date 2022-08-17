<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<!doctype html>
<html lang="pt">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>
    <title>Alerta de documento Moloni</title>
    <meta name="description" content="Template de alerta de documento.">
    <style type="text/css">
        a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>

<body marginheight="0" topmargin="0" marginwidth="0" style="margin: 0px; background-color: #303A4D;" leftmargin="0">
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#303A4D"
       style="@import url(https://fonts.googleapis.com/css?family=Rubik:300,400,500,700|Open+Sans:300,400,600,700); font-family: 'Open Sans', sans-serif;">
    <tr>
        <td>
            <table style="background-color: #303A4D; max-width:670px;  margin:0 auto;" width="100%" border="0"
                   align="center" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="height:80px;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="text-align:center;">
                        <a href="<?= $url ?? '' ?>" title="logo" target="_blank">
                            <img width="200px" src="<?= $image ?? '' ?>" title="logo"
                                 alt="logo">
                        </a>
                    </td>
                </tr>
                <tr>
                    <td style="height:20px;">&nbsp;</td>
                </tr>
                <tr>
                    <td>
                        <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
                               style="max-width:670px;background:#fff; border-radius:3px; text-align:center;-webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);-moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);box-shadow:0 6px 18px 0 rgba(0,0,0,.06);">
                            <tr>
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td style="padding:0 35px;">
                                    <h1 style="color:#1e1e2d; font-weight:500; margin:0;font-size:28px;font-family:'Rubik',sans-serif;">
                                        Alerta de documento Moloni
                                    </h1>
                                    <span style="display:inline-block; vertical-align:middle; margin:29px 0 26px; border-bottom:1px solid #cecece; width:100px;"></span>
                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        Um documento foi criado como rascunho porque os valores da encomenda e do documento não correspondem.
                                    </p>
                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        Por favor, verifique o documento resultante.
                                    </p>
                                    <br>
                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                        <?= $extra ?? '' ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="height:40px;">&nbsp;</td>
                            </tr>
                        </table>
                    </td>
            </table>
        </td>
    </tr>
    <tr>
        <td style="height:20px;">&nbsp;</td>
    </tr>
    <tr>
        <td style="text-align:center;">
            <p style="font-size:14px; color:#fff; line-height:18px; margin:0 0 0;">
                &copy; <strong><?= $year ?? '' ?> Moloni - Software de faturação online</strong>
            </p>
        </td>
    </tr>
    <tr>
        <td style="height:80px;">&nbsp;</td>
    </tr>
</table>
</body>

</html>
