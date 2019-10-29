<form method='POST' action='admin.php?page=moloni&tab=settings' id='formOpcoes'>
    <input type='hidden' value='save' name='action'>
    <div>
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="document_type"><?= __("Tipo de documento") ?></label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <option value='invoices' <?= (DOCUMENT_TYPE == "invoices" ? "selected" : "") ?>>
                            <?= __('Faturas') ?>
                        </option>

                        <option value='invoiceReceipts' <?= (DOCUMENT_TYPE == "invoiceReceipts" ? "selected" : "") ?>>
                            <?= __("Factura/Recibo") ?>
                        </option>

                        <option value='simplifiedInvoices'<?= (DOCUMENT_TYPE == "simplifiedInvoices" ? "selected" : "") ?>>
                            <?= __('Factura Simplificada') ?>
                        </option>

                        <option value='billsOfLading' <?= (DOCUMENT_TYPE == "billsOfLading" ? "selected" : "") ?>>
                            <?= __('Guia de Transporte') ?>
                        </option>

                        <option value='purchaseOrder' <?= (DOCUMENT_TYPE == "purchaseOrder" ? "selected" : "") ?>>
                            <?= __('Nota de Encomenda') ?>
                        </option>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_status"><?= __("Estado do documento") ?></label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <option value='0' <?= (DOCUMENT_STATUS == "0" ? "selected" : "") ?>><?= __('Rascunho') ?></option>
                        <option value='1' <?= (DOCUMENT_STATUS == "1" ? "selected" : "") ?>><?= __("Fechado") ?></option>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="document_set_id"><?= __("Série de documento") ?></label>
                </th>
                <td>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut'>
                        <?php $documentSets = \Moloni\Curl::simple("documentSets/getAll", []); ?>
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <option value='<?= $documentSet['document_set_id'] ?>' <?= DOCUMENT_SET_ID == $documentSet['document_set_id'] ? 'selected' : '' ?>><?= $documentSet['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="measure_unit_id"><?= __("Unidade de medida") ?></label>
                </th>
                <td>
                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php $measurementUnits = \Moloni\Curl::simple("measurementUnits/getAll", []); ?>
                        <?php if (is_array($measurementUnits)): ?>
                            <?php foreach ($measurementUnits as $measurementUnit) : ?>
                                <option value='<?= $measurementUnit['unit_id'] ?>' <?= MEASURE_UNIT == $measurementUnit['unit_id'] ? 'selected' : '' ?>><?= $measurementUnit['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason"><?= __("Razão de Isenção") ?></label>
                </th>
                <td>
                    <select id="exemption_reason" name='opt[exemption_reason]' class='inputOut'>
                        <option value='' <?= EXEMPTION_REASON == '' ? 'selected' : '' ?>><?= __("Nenhuma") ?></option>
                        <?php $exemptionReasons = \Moloni\Curl::simple("taxExemptions/getAll", []); ?>
                        <?php if (is_array($exemptionReasons)): ?>
                            <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                                <option value='<?= $exemptionReason['code'] ?>' <?= EXEMPTION_REASON == $exemptionReason['code'] ? 'selected' : '' ?>><?= $exemptionReason['code'] . " - " . $exemptionReason['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Será usada se os artigos não tiverem uma taxa de IVA') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping"><?= __("Razão de Isenção de Portes/Taxas") ?></label>
                </th>
                <td>
                    <select id="exemption_reason_shipping" name='opt[exemption_reason_shipping]' class='inputOut'>
                        <option value='' <?= EXEMPTION_REASON_SHIPPING == '' ? 'selected' : '' ?>><?= __("Nenhuma") ?></option>
                        <?php if (is_array($exemptionReasons)): ?>
                            <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                                <option value='<?= $exemptionReason['code'] ?>' <?= EXEMPTION_REASON_SHIPPING == $exemptionReason['code'] ? 'selected' : '' ?>><?= $exemptionReason['code'] . " - " . $exemptionReason['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Será usada se os portes não tiverem uma taxa de IVA') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="maturity_date_id"><?= __("Prazo de Vencimento") ?></label>
                </th>
                <td>
                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option value='0' <?= MATURITY_DATE == 0 ? 'selected' : '' ?>><?= __("Escolha uma opção") ?></option>
                        <?php $maturityDates = \Moloni\Curl::simple("maturityDates/getAll", []); ?>
                        <?php if (is_array($maturityDates)): ?>
                            <?php foreach ($maturityDates as $maturityDate) : ?>
                                <option value='<?= $maturityDate['maturity_date_id'] ?>' <?= MATURITY_DATE == $maturityDate['maturity_date_id'] ? 'selected' : '' ?>><?= $maturityDate['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Prazo de vencimento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id"><?= __("Método de pagamento") ?></label>
                </th>
                <td>
                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option value='0' <?= PAYMENT_METHOD == 0 ? 'selected' : '' ?>><?= __("Escolha uma opção") ?></option>
                        <?php $paymentMethods = \Moloni\Curl::simple("paymentMethods/getAll", []); ?>
                        <?php if (is_array($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $paymentMethod) : ?>
                                <option value='<?= $paymentMethod['payment_method_id'] ?>' <?= PAYMENT_METHOD == $paymentMethod['payment_method_id'] ? 'selected' : '' ?>><?= $paymentMethod['name'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Método de pagamento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field"><?= __("Contribuinte do cliente") ?></label>
                </th>
                <td>
                    <select id="vat_field" name='opt[vat_field]' class='inputOut'>
                        <option value='' <?= VAT_FIELD == '' ? 'selected' : '' ?>><?= __("Escolha uma opção") ?></option>
                        <?php $customFields = \Moloni\Model::getCustomFields(); ?>
                        <?php if (is_array($customFields)): ?>
                            <?php foreach ($customFields as $customField) : ?>
                                <option value='<?= $customField['meta_key'] ?>' <?= VAT_FIELD == $customField['meta_key'] ? 'selected' : '' ?>><?= $customField['meta_key'] ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?= __('Custom field associado ao contribuinte do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="shipping_info"><?= __("Informação de envio") ?></label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <option value='0' <?= (SHIPPING_INFO == "0" ? "selected" : "") ?>><?= __('Não') ?></option>
                        <option value='1' <?= (SHIPPING_INFO == "1" ? "selected" : "") ?>><?= __("Sim") ?></option>
                    </select>
                    <p class='description'><?= __('Colocar dados de transporte nos documentos') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="email_send"><?= __("Enviar email") ?></label>
                </th>
                <td>
                    <select id="email_send" name='opt[email_send]' class='inputOut'>
                        <option value='0' <?= (EMAIL_SEND == "0" ? "selected" : "") ?>><?= __('Não') ?></option>
                        <option value='1' <?= (EMAIL_SEND == "1" ? "selected" : "") ?>><?= __("Sim") ?></option>
                    </select>
                    <p class='description'><?= __('O documento só é enviado para o cliente se for inserido fechado') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="invoice_auto"><?= __("Criar documento quando completado") ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <option value='0' <?= (INVOICE_AUTO == "0" ? "selected" : "") ?>><?= __('Não') ?></option>
                        <option value='1' <?= (INVOICE_AUTO == "1" ? "selected" : "") ?>><?= __("Sim") ?></option>
                    </select>
                    <p class='description'><?= __('Criar documento automaticamente quando uma encomenda é paga') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?= __("Sincronizar stocks automaticamente") ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?= (MOLONI_STOCK_SYNC == "0" ? "selected" : "") ?>><?= __('Não') ?></option>
                        <option value='1' <?= (MOLONI_STOCK_SYNC == "1" ? "selected" : "") ?>><?= __("Sim") ?></option>
                    </select>
                    <p class='description'><?= __('Sincronização de stocks automática (corre a cada 5 minutos e actualiza o stock dos artigos com base no Moloni)') ?></p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?= __("Guardar alterações") ?>">
                </td>
            </tr>
            </tbody>
        </table>

        <h1><?= __("Ferramentas") ?></h1>
        <table class="wc_status_table wc_status_table--tools widefat">
            <tbody class="tools">
            <tr>
                <th style="padding: 2rem">
                    <strong class="name"><?= __('Forçar sincronização de stocks') ?></strong>
                    <p class='description'><?= __('Sincronizar os stocks de todos os artigos usados nos últimos 7 dias') ?></p>
                </th>
                <td class="run-tool" style="padding: 2rem; text-align: right">
                    <a class="button button-large"
                       href='admin.php?page=moloni&tab=settings&action=syncStocks&since=<?= gmdate('Y-m-d', strtotime("-1 week")) ?>'>
                        <?= __('Forçar sincronização de stocks') ?>
                    </a>
                </td>
            </tr>

            <tr>
                <th style="padding: 2rem">
                    <strong class="name"><?= __('Limpar encomendas pendentes') ?></strong>
                    <p class='description'><?= __('Remover todas as encomendas da listagem de encomendas') ?></p>
                </th>
                <td class="run-tool" style="padding: 2rem; text-align: right">
                    <a class="button button-large"
                       href='admin.php?page=moloni&action=remAll&deleteAll=permission'>
                        <?= __('Limpar encomendas pendentes') ?>
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</form>