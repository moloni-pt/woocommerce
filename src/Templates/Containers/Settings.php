<?php use Moloni\Curl;
use Moloni\Model;

?>
<?php ?>

<?php $company = Curl::simple('companies/getOne', []); ?>
<?php $warehouses = Curl::simple('warehouses/getAll', []); ?>

<form method='POST' action='<?= admin_url('admin.php?page=moloni&tab=settings') ?>' id='formOpcoes'>
    <input type='hidden' value='save' name='action'>
    <div>
        <h2 class="title"><?= __('Documentos') ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="company_slug"><?= __('Slug da empresa') ?></label>
                </th>
                <td>
                    <input id="company_slug" name="opt[company_slug]" type="text"
                           value="<?= $company['slug'] ?>" readonly
                           style="width: 330px;">
                </td>
            </tr>
            <tr>
                <th>
                    <label for="document_type"><?= __('Tipo de documento') ?></label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <option value='invoices' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoices' ? 'selected' : '') ?>>
                            <?= __('Faturas') ?>
                        </option>

                        <option value='invoiceReceipts' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'invoiceReceipts' ? 'selected' : '') ?>>
                            <?= __('Factura/Recibo') ?>
                        </option>

                        <option value='simplifiedInvoices'<?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'simplifiedInvoices' ? 'selected' : '') ?>>
                            <?= __('Factura Simplificada') ?>
                        </option>

                        <option value='proFormaInvoices' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'proFormaInvoices' ? 'selected' : '') ?>>
                            <?= __('Fatura Pró-Forma') ?>
                        </option>

                        <option value='billsOfLading' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'billsOfLading' ? 'selected' : '') ?>>
                            <?= __('Guia de Transporte') ?>
                        </option>

                        <option value='purchaseOrder' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'purchaseOrder' ? 'selected' : '') ?>>
                            <?= __('Nota de Encomenda') ?>
                        </option>

                        <option value='estimates' <?= (defined('DOCUMENT_TYPE') && DOCUMENT_TYPE === 'estimates' ? 'selected' : '') ?>>
                            <?= __('Orçamento') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_status"><?= __('Estado do documento') ?></label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <option value='0' <?= (defined('DOCUMENT_STATUS') && (int)DOCUMENT_STATUS === 0 ? 'selected' : '') ?>><?= __('Rascunho') ?></option>
                        <option value='1' <?= (defined('DOCUMENT_STATUS') && (int)DOCUMENT_STATUS === 1 ? 'selected' : '') ?>><?= __('Fechado') ?></option>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_set_id"><?= __('Série de documento') ?></label>
                </th>
                <td>
                    <?php $documentSets = Curl::simple('documentSets/getAll', []); ?>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut' onchange="onDocumentSetChange()">
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <?php
                                $htmlProps = '';
                                $documentSetId = $documentSet['document_set_id'];

                                if ((int)$documentSet['eac_id'] > 0 && !empty($documentSet['eac'])) {
                                    $htmlProps .= ' data-eac-id="' . $documentSet['eac']['eac_id'] . '"';
                                    $htmlProps .= ' data-eac-name="' . htmlspecialchars($documentSet['eac']['description'], ENT_QUOTES) . '"';
                                }

                                if (defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID === (int)$documentSet['document_set_id']) {
                                    $htmlProps .= ' selected';
                                }
                            ?>

                            <option value='<?= $documentSetId ?>' <?= $htmlProps ?>>
                                <?= $documentSet['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr id="document_set_cae_line" style="display: none;">
                <th>
                    <label for="document_set_cae_name"><?= __('CAE da série') ?></label>
                </th>
                <td>
                    <input id="document_set_cae_id" name="opt[document_set_cae_id]" type="hidden" value="0">
                    <input id="document_set_cae_name" type="text" value="Placeholder" readonly style="width: 330px;">

                    <p id="document_set_cae_warning" class='description txt--red' style="display: none;">
                        <?=__('Guarde alterações para associar a CAE ao plugin') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="shipping_info"><?= __('Informação de envio') ?></label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <option value='0' <?= (defined('SHIPPING_INFO') && (int)SHIPPING_INFO === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('SHIPPING_INFO') && (int)SHIPPING_INFO === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Colocar dados de transporte nos documentos') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="email_send"><?= __('Enviar email') ?></label>
                </th>
                <td>
                    <select id="email_send" name='opt[email_send]' class='inputOut'>
                        <option value='0' <?= (defined('EMAIL_SEND') && (int)EMAIL_SEND === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('EMAIL_SEND') && (int)EMAIL_SEND === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('O documento só é enviado para o cliente se for inserido fechado') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_show_download_column"><?= __('Listagem de encomendas WooCommerce') ?></label>
                </th>
                <td>
                    <select id="moloni_show_download_column" name='opt[moloni_show_download_column]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Adicionar, no WooCommerce, uma coluna na listagem de encomendas com download rápido de documentos em PDF.') ?></p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title"><?= __('Artigos') ?></h2>
        <table class="form-table">
            <tbody>

            <?php if (is_array($warehouses) && !empty($warehouses)): ?>
                <tr>

                    <th>
                        <label for="moloni_product_warehouse"><?= __('Armazém') ?></label>
                    </th>
                    <td>
                        <select id="moloni_product_warehouse" name='opt[moloni_product_warehouse]' class='inputOut'>
                            <option value='0'><?= __('Armazém pré-definido') ?></option>
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option value='<?= $warehouse['warehouse_id'] ?>' <?= defined('MOLONI_PRODUCT_WAREHOUSE') && (int)MOLONI_PRODUCT_WAREHOUSE === (int)$warehouse['warehouse_id'] ? 'selected' : '' ?>>
                                    <?= $warehouse['title'] ?> (<?= $warehouse['code'] ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>
                        <p class='description'><?= __('Obrigatório') ?></p>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th>
                    <label for="measure_unit_id"><?= __('Unidade de medida') ?></label>
                </th>
                <td>
                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php $measurementUnits = Curl::simple('measurementUnits/getAll', []); ?>
                        <?php if (is_array($measurementUnits)): ?>
                            <?php foreach ($measurementUnits as $measurementUnit) : ?>
                                <option value='<?= $measurementUnit['unit_id'] ?>' <?= defined('MEASURE_UNIT') && (int)MEASURE_UNIT === (int)$measurementUnit['unit_id'] ? 'selected' : '' ?>><?= $measurementUnit['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason"><?= __('Razão de isenção') ?></label>
                </th>
                <td>
                    <select id="exemption_reason" name='opt[exemption_reason]' class='inputOut'>
                        <option value='' <?= defined('EXEMPTION_REASON') && EXEMPTION_REASON === '' ? 'selected' : '' ?>><?= __('Nenhuma') ?></option>
                        <?php $exemptionReasons = Curl::simple('taxExemptions/getAll', []); ?>
                        <?php if (is_array($exemptionReasons)): ?>
                            <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                                <option value='<?= $exemptionReason['code'] ?>' <?= defined('EXEMPTION_REASON') && EXEMPTION_REASON === $exemptionReason['code'] ? 'selected' : '' ?>><?= $exemptionReason['code'] . ' - ' . $exemptionReason['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Será usada se os artigos não tiverem uma taxa de IVA') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping"><?= __('Razão de isenção de portes') ?></label>
                </th>
                <td>
                    <select id="exemption_reason_shipping" name='opt[exemption_reason_shipping]' class='inputOut'>
                        <option value='' <?= defined('EXEMPTION_REASON_SHIPPING') && EXEMPTION_REASON_SHIPPING === '' ? 'selected' : '' ?>><?= __('Nenhuma') ?></option>
                        <?php if (is_array($exemptionReasons)): ?>
                            <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                                <option value='<?= $exemptionReason['code'] ?>' <?= defined('EXEMPTION_REASON_SHIPPING') && EXEMPTION_REASON_SHIPPING === $exemptionReason['code'] ? 'selected' : '' ?>><?= $exemptionReason['code'] . ' - ' . $exemptionReason['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Será usada se os portes não tiverem uma taxa de IVA') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_extra_community"><?= __('Razão de isenção de vendas extra-comunitárias') ?></label>
                </th>
                <td>
                    <select id="exemption_reason_extra_community" name='opt[exemption_reason_extra_community]' class='inputOut'>
                        <option value='' <?= defined('EXEMPTION_REASON_EXTRA_COMMUNITY') && EXEMPTION_REASON_EXTRA_COMMUNITY === '' ? 'selected' : '' ?>><?= __('Nenhuma') ?></option>
                        <?php $exemptionReasons = Curl::simple('taxExemptions/getAll', []); ?>
                        <?php if (is_array($exemptionReasons)): ?>
                            <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                                <option value='<?= $exemptionReason['code'] ?>' <?= defined('EXEMPTION_REASON_EXTRA_COMMUNITY') && EXEMPTION_REASON_EXTRA_COMMUNITY === $exemptionReason['code'] ? 'selected' : '' ?>><?= $exemptionReason['code'] . ' - ' . $exemptionReason['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'>
                        <?= __('Razão de isenção "especial" usada nos artigos que não tiverem uma taxa de IVA e, na encomenda, o país de faturação do cliente <b>não</b> pertença à União Europeia') ?>
                        <a style="cursor: help;" title="<?=__('Países da União Europeia') . ': ' . implode(", ", \Moloni\Tools::$europeanCountryCodes)?>">(?)</a>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="use_moloni_product_details"><?= __('Usar dados do Moloni ') ?></label>
                </th>
                <td>
                    <select id="use_moloni_product_details" name='opt[use_moloni_product_details]' class='inputOut'>
                        <option value='0' <?= (defined('USE_MOLONI_PRODUCT_DETAILS') && (int)USE_MOLONI_PRODUCT_DETAILS === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('USE_MOLONI_PRODUCT_DETAILS') && (int)USE_MOLONI_PRODUCT_DETAILS === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Se o artigo já existir no Moloni, será usado o Nome e o Resumo que existem no Moloni, em vez dos que estão na encomenda') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="use_name_for_moloni_reference"><?= __('Incluir nome na referência ') ?></label>
                </th>
                <td>
                    <select id="use_name_for_moloni_reference" name='opt[use_name_for_moloni_reference]'
                            class='inputOut'>
                        <option value='0' <?= (defined('USE_NAME_FOR_MOLONI_REFERENCE') && (int)USE_NAME_FOR_MOLONI_REFERENCE === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('USE_NAME_FOR_MOLONI_REFERENCE') && (int)USE_NAME_FOR_MOLONI_REFERENCE === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'>
                        <?= __('Caso um artigo não tenha referência é criada uma automáticamente para o mesmo.') ?><br>
                        <?= __('Por defeito a referência é feita com base no ID do artigo, esta opção faz com que a referência automática use também o nome do artigo de forma a evitar conflitos.') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title"><?= __('Clientes') ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="maturity_date_id"><?= __('Prazo de Vencimento') ?></label>
                </th>
                <td>
                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option value='0' <?= defined('MATURITY_DATE') && (int)MATURITY_DATE === 0 ? 'selected' : '' ?>><?= __('Escolha uma opção') ?></option>
                        <?php $maturityDates = Curl::simple('maturityDates/getAll', []); ?>
                        <?php if (is_array($maturityDates)): ?>
                            <?php foreach ($maturityDates as $maturityDate) : ?>
                                <option value='<?= $maturityDate['maturity_date_id'] ?>' <?= defined('MATURITY_DATE') && (int)MATURITY_DATE === (int)$maturityDate['maturity_date_id'] ? 'selected' : '' ?>><?= $maturityDate['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Prazo de vencimento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id"><?= __('Método de pagamento') ?></label>
                </th>
                <td>
                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option value='0' <?= defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === 0 ? 'selected' : '' ?>><?= __('Escolha uma opção') ?></option>
                        <?php $paymentMethods = Curl::simple('paymentMethods/getAll', []); ?>
                        <?php if (is_array($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $paymentMethod) : ?>
                                <option value='<?= $paymentMethod['payment_method_id'] ?>' <?= defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === (int)$paymentMethod['payment_method_id'] ? 'selected' : '' ?>><?= $paymentMethod['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Método de pagamento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field"><?= __('Contribuinte do cliente') ?></label>
                </th>
                <td>
                    <select id="vat_field" name='opt[vat_field]' class='inputOut'>
                        <option value='' <?= defined('VAT_FIELD') && VAT_FIELD === '' ? 'selected' : '' ?>><?= __('Escolha uma opção') ?></option>
                        <?php $customFields = Model::getCustomFields(); ?>
                        <?php if (is_array($customFields)): ?>
                            <?php foreach ($customFields as $customField) : ?>
                                <option value='<?= $customField['meta_key'] ?>' <?= defined('VAT_FIELD') && VAT_FIELD === $customField['meta_key'] ? 'selected' : '' ?>><?= $customField['meta_key'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'>
                        <?= __('Custom field associado ao contribuinte do cliente. Se o campo não aparecer, certifique-se que tem pelo menos uma encomenda com o campo em uso.') ?>
                        <br>
                        <?= __('Para que o Custom Field apareça, deverá ter pelo menos uma encomenda com o contribuinte preenchido. O campo deverá ter um nome por exemplo <i>_billing_vat</i>.') ?>
                        <br>
                        <?= __('Se ainda não tiver nenhum campo para o contribuinte, poderá adicionar o plugin disponível <a target="_blank" href="https://wordpress.org/plugins/contribuinte-checkout/">aqui.</a> ') ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <h2 class="title"><?= __('Automatização') ?></h2>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="invoice_auto"><?= __('Criar documento automaticamente') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'
                            onchange="onInvoiceAutoChange()">
                        <option value='0' <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Criar documentos automaticamente') ?></p>
                </td>
            </tr>

            <tr id="invoice_auto_status_line" <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 0 ? 'style="display: none;"' : '') ?>>
                <th>
                    <label for="invoice_auto_status"><?= __('Criar documentos quando a encomenda está') ?></label>
                </th>
                <td>
                    <select id="invoice_auto_status" name='opt[invoice_auto_status]' class='inputOut'>
                        <option value='completed' <?= (defined('INVOICE_AUTO_STATUS') && INVOICE_AUTO_STATUS === 'completed' ? 'selected' : '') ?>><?= __('Completa') ?></option>
                        <option value='processing' <?= (defined('INVOICE_AUTO_STATUS') && INVOICE_AUTO_STATUS === 'processing' ? 'selected' : '') ?>><?= __('Em processamento') ?></option>
                    </select>
                    <p class='description'><?= __('Os documentos vão ser criados automaticamente assim que estiverem no estado seleccionado') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?= __('Sincronizar stocks automaticamente') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === 1 ? 'selected' : '') ?>><?= __('Sim, de todos os armazéns') ?></option>

                        <?php if (is_array($warehouses)): ?>
                            <optgroup label="<?= __('Sim, apenas do armazém:') ?>">

                                <?php foreach ($warehouses as $warehouse) : ?>
                                    <option value='<?= $warehouse['warehouse_id'] ?>' <?= defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === $warehouse['warehouse_id'] ? 'selected' : '' ?>>
                                        <?= $warehouse['title'] ?> (<?= $warehouse['code'] ?>)
                                    </option>
                                <?php endforeach; ?>

                            </optgroup>
                        <?php endif; ?>

                    </select>
                    <p class='description'><?= __('Sincronização de stocks automática (corre a cada 5 minutos e actualiza o stock dos artigos com base no Moloni)') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_status"><?= __('Estado do Stock') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_status" name='opt[moloni_stock_status]' class='inputOut'>
                        <option value='outofstock' <?= (defined('MOLONI_STOCK_STATUS') && MOLONI_STOCK_STATUS === 'outofstock' ? 'selected' : '') ?>><?= __('Sem stock') ?></option>
                        <option value='onbackorder' <?= (defined('MOLONI_STOCK_STATUS') && MOLONI_STOCK_STATUS === 'onbackorder' ? 'selected' : '') ?>><?= __('Por encomenda') ?></option>
                    </select>
                    <p class='description'><?= __('O estado do produto quando o seu stock após sincronização é 0') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?= __('Criar artigos') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Ao guardar um artigo no WooCommerce, o plugin vai criar automaticamente o artigo no Moloni') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync_update"><?= __('Actualizar artigos') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync_update" name='opt[moloni_product_sync_update]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_PRODUCT_SYNC_UPDATE') && (int)MOLONI_PRODUCT_SYNC_UPDATE === 0 ? 'selected' : '') ?>><?= __('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_PRODUCT_SYNC_UPDATE') && (int)MOLONI_PRODUCT_SYNC_UPDATE === 1 ? 'selected' : '') ?>><?= __('Sim') ?></option>
                    </select>
                    <p class='description'><?= __('Ao guardar um artigo no WooCommerce, se o artigo já existir no Moloni vai actualizar os dados do artigo') ?></p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?= __('Guardar alterações') ?>">
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</form>

<script>
    var originalCAE = <?= (defined('DOCUMENT_SET_CAE_ID') && (int)DOCUMENT_SET_CAE_ID > 0 ? (int)DOCUMENT_SET_CAE_ID : 0) ?>;

    function onDocumentSetChange() {
        var select = document.getElementById('document_set_id');
        var line = document.getElementById('document_set_cae_line');

        if (!select || !line) {
            return false;
        }

        var selectedDocumentSet = select.selectedOptions;

        if (!selectedDocumentSet.length) {
            return false;
        }

        selectedDocumentSet = selectedDocumentSet[0];

        var caeElementId = document.getElementById('document_set_cae_id');
        var caeElementName = document.getElementById('document_set_cae_name');
        var caeElementWarning = document.getElementById('document_set_cae_warning');

        if (!caeElementId || !caeElementName || !caeElementWarning) {
            return false;
        }

        caeElementId.value = selectedDocumentSet.getAttribute('data-eac-id') || 0;
        caeElementName.value = selectedDocumentSet.getAttribute('data-eac-name') || 'Placeholder';

        if (parseInt(originalCAE) !== parseInt(caeElementId.value)) {
            caeElementWarning.style['display'] = 'table-row';
        } else {
            caeElementWarning.style['display'] = 'none';
        }

        if (parseInt(caeElementId.value) > 0) {
            line.style['display'] = 'table-row';
        } else {
            line.style['display'] = 'none';
        }

        return true;
    }

    function onInvoiceAutoChange() {
        var selectedOption = document.getElementById('invoice_auto');

        if (selectedOption && selectedOption.value === '1') {
            document.getElementById('invoice_auto_status_line').style['display'] = 'table-row';
        } else {
            document.getElementById('invoice_auto_status_line').style['display'] = 'none';
        }
    }

    onDocumentSetChange();
</script>