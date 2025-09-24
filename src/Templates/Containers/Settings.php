<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php

use Moloni\Curl;
use Moloni\Exceptions\APIException;
use Moloni\Helpers\Context;
use Moloni\Model;
use Moloni\Enums\Boolean;
use Moloni\Enums\DocumentTypes;
use Moloni\Enums\DocumentStatus;
use Moloni\Tools;

$wcOrdersStatus = wc_get_order_statuses();

try {
    $company = Curl::simple('companies/getOne', []);
    $warehouses = Curl::simple('warehouses/getAll', []);
    $countries = Curl::simple('countries/getAll', []);
    $documentSets = Curl::simple('documentSets/getAll', []);
    $exemptionReasons = Curl::simple('taxExemptions/getAll', []);
    $measurementUnits = Curl::simple('measurementUnits/getAll', []);
    $maturityDates = Curl::simple('maturityDates/getAll', []);
    $paymentMethods = Curl::simple('paymentMethods/getAll', []);

    if (!is_array($exemptionReasons)) {
        $exemptionReasons = [];
    }
} catch (APIException $e) {
    $e->showError();
    return;
}
?>

<form method='POST' action='<?= esc_url(admin_url('admin.php?page=moloni&tab=settings')) ?>' id='formOpcoes'>
    <input type='hidden' value='save' name='action'>
    <div>
        <!-- Documento -->
        <h2 class="title">
            <?php esc_html_e('Documentos') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Slug da empresa -->
            <tr>
                <th>
                    <label for="company_slug"><?php esc_html_e('Slug da empresa') ?></label>
                </th>
                <td>
                    <input id="company_slug" name="opt[company_slug]" type="text"
                           value="<?= esc_html($company['slug']) ?>" readonly
                           style="width: 330px;">
                </td>
            </tr>

            <!-- Tipo de documento -->
            <tr>
                <th>
                    <label for="document_type">
                        <?php esc_html_e('Tipo de documento') ?>
                    </label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <?php
                        $documentType = '';

                        if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                            $documentType = DOCUMENT_TYPE;
                        }
                        ?>

                        <?php foreach (DocumentTypes::getDocumentTypeForRender() as $id => $name) : ?>
                            <option value='<?= esc_html($id) ?>' <?= ($documentType === $id ? 'selected' : '') ?>>
                                <?php esc_html_e($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Obrigatório') ?></p>
                </td>
            </tr>

            <!-- Série de documento -->
            <tr>
                <th>
                    <label for="document_set_id"><?php esc_html_e('Série do documento') ?></label>
                </th>
                <td>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut'>
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <?php
                            $documentSetId = $documentSet['document_set_id'];
                            ?>

                            <option value='<?= esc_html($documentSetId) ?>'
                                <?= isset($documentSet['eac']['eac_id']) ? ' data-eac-id="' . esc_html($documentSet['eac']['eac_id']) . '"' : '' ?>
                                <?= isset($documentSet['eac']['description']) ? ' data-eac-name="' . esc_html($documentSet['eac']['description']) . '"' : '' ?>
                                <?= defined('DOCUMENT_SET_ID') && (int)DOCUMENT_SET_ID === (int)$documentSet['document_set_id'] ? ' selected' : '' ?>
                            >
                                <?= esc_html($documentSet['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Obrigatório') ?></p>
                </td>
            </tr>

            <!-- CAE -->
            <tr id="document_set_cae_line" style="display: none;">
                <th>
                    <label for="document_set_cae_name"><?php esc_html_e('CAE da série') ?></label>
                </th>
                <td>
                    <input id="document_set_cae_id" name="opt[document_set_cae_id]" type="hidden" value="0">
                    <input id="document_set_cae_name" type="text" value="Placeholder" readonly style="width: 330px;">

                    <p id="document_set_cae_warning" class='description txt--red' style="display: none;">
                        <?php esc_html_e('Guarde alterações para associar a CAE ao plugin') ?>
                    </p>
                </td>
            </tr>

            <!-- Estado do documento -->
            <tr id="document_status_line">
                <th>
                    <label for="document_status"><?php esc_html_e('Estado do documento') ?></label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <option value='0' <?= (defined('DOCUMENT_STATUS') && (int)DOCUMENT_STATUS === 0 ? 'selected' : '') ?>><?php esc_html_e('Rascunho') ?></option>
                        <option value='1' <?= (defined('DOCUMENT_STATUS') && (int)DOCUMENT_STATUS === 1 ? 'selected' : '') ?>><?php esc_html_e('Fechado') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Obrigatório') ?></p>
                </td>
            </tr>

            <!-- Criar documento de transporte -->
            <tr id="create_bill_of_lading_line">
                <th>
                    <label for="create_bill_of_lading"><?php esc_html_e('Documento de transporte') ?></label>
                </th>
                <td>
                    <?php
                    $createBillOfLading = 0;

                    if (defined('CREATE_BILL_OF_LADING')) {
                        $createBillOfLading = (int)CREATE_BILL_OF_LADING;
                    }
                    ?>

                    <select id="create_bill_of_lading" name='opt[create_bill_of_lading]' class='inputOut'>
                        <option value='0' <?= ($createBillOfLading === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($createBillOfLading === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Criar documento de transporte') ?></p>
                </td>
            </tr>

            <!-- Informação de envio -->
            <tr>
                <th>
                    <label for="shipping_info"><?php esc_html_e('Informação de envio') ?></label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <option value='0' <?= (defined('SHIPPING_INFO') && (int)SHIPPING_INFO === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('SHIPPING_INFO') && (int)SHIPPING_INFO === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Colocar dados de transporte nos documentos') ?></p>
                </td>
            </tr>

            <!-- Morada de carga -->
            <tr id="load_address_line" style="display: none;">
                <th>
                    <label for="load_address"><?php esc_html_e('Morada de carga') ?></label>
                </th>
                <td>
                    <select id="load_address" name='opt[load_address]' class='inputOut'>
                        <?php $activeLoadAddress = defined('LOAD_ADDRESS') ? (int)LOAD_ADDRESS : 0; ?>

                        <option value='0' <?= ($activeLoadAddress === 0 ? 'selected' : '') ?>><?php esc_html_e('Morada da empresa') ?></option>
                        <option value='1' <?= ($activeLoadAddress === 1 ? 'selected' : '') ?>><?php esc_html_e('Personalizada') ?></option>
                    </select>

                    <div class="custom-address__wrapper" id="load_address_custom_line">
                        <div class="custom-address__line">
                            <?php $loadAddressCustomAddress = defined('LOAD_ADDRESS_CUSTOM_ADDRESS') ? LOAD_ADDRESS_CUSTOM_ADDRESS : ''; ?>


                            <input name="opt[load_address_custom_address]" id="load_address_custom_address"
                                   value="<?= esc_html($loadAddressCustomAddress) ?>"
                                   placeholder="Morada" type="text" class="inputOut">
                        </div>
                        <div class="custom-address__line">
                            <?php $loadAddressCustomCode = defined('LOAD_ADDRESS_CUSTOM_CODE') ? LOAD_ADDRESS_CUSTOM_CODE : ''; ?>

                            <input name="opt[load_address_custom_code]" id="load_address_custom_code"
                                   value="<?= esc_html($loadAddressCustomCode) ?>"
                                   placeholder="Código Postal" type="text" class="inputOut inputOut--sm">

                            <?php $loadAddressCustomCity = defined('LOAD_ADDRESS_CUSTOM_CITY') ? LOAD_ADDRESS_CUSTOM_CITY : ''; ?>

                            <input name="opt[load_address_custom_city]" id="load_address_custom_city"
                                   value="<?= esc_html($loadAddressCustomCity) ?>"
                                   placeholder="Localidade" type="text" class="inputOut inputOut--sm">
                        </div>
                        <div class="custom-address__line">
                            <select id="load_address_custom_country" name="opt[load_address_custom_country]"
                                    class="inputOut inputOut--sm">
                                <?php $activeCountry = defined('LOAD_ADDRESS_CUSTOM_COUNTRY') ? (int)LOAD_ADDRESS_CUSTOM_COUNTRY : 0; ?>

                                <?php foreach ($countries as $country) : ?>
                                    <option value='<?= esc_html($country['country_id']) ?>' <?= $activeCountry === (int)$country['country_id'] ? 'selected' : '' ?>>
                                        <?= esc_html($country['languages'][0]['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <p class='description'><?php esc_html_e('Morada de carga utilizada nos dados de transporte') ?></p>
                </td>
            </tr>

            <!-- Notas da encomenda -->
            <tr>
                <th>
                    <label for="add_order_notes"><?php esc_html_e('Notas da encomenda') ?></label>
                </th>
                <td>
                    <?php
                    $addOrderNotes = Boolean::YES;

                    if (defined('ADD_ORDER_NOTES')) {
                        $addOrderNotes = (int)ADD_ORDER_NOTES;
                    }
                    ?>

                    <select id="add_order_notes" name='opt[add_order_notes]' class='inputOut'>
                        <option value='0' <?= ($addOrderNotes === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($addOrderNotes === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Colocar notas da encomenda nas notas dos documentos.') ?></p>
                </td>
            </tr>

            <!-- Enviar email -->
            <tr>
                <th>
                    <label for="email_send"><?php esc_html_e('Enviar email') ?></label>
                </th>
                <td>
                    <select id="email_send" name='opt[email_send]' class='inputOut'>
                        <option value='0' <?= (defined('EMAIL_SEND') && (int)EMAIL_SEND === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('EMAIL_SEND') && (int)EMAIL_SEND === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('O documento só é enviado para o cliente se for inserido fechado') ?></p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Documentos - Isenções -->
        <h2 class="title">
            <?php esc_html_e('Documentos - Isenções') ?>
        </h2>

        <div class="subtitle">
            <?php esc_html_e('Vendas nacionais e intra comunitárias') ?>
            <?php esc_html_e('(dentro da união europeia)') ?>
            <a style="cursor: help;"
               title="<?php esc_html_e('Países da União Europeia') . ': ' . implode(", ", Tools::$europeanCountryCodes) ?>">(?)</a>
        </div>
        <table class="form-table mb-4">
            <tbody>
            <tr>
                <th>
                    <label for="exemption_reason">
                        <?php esc_html_e('Artigos') ?>
                    </label>
                </th>
                <td>
                    <select id="exemption_reason" name='opt[exemption_reason]' class='inputOut'>
                        <?php
                        $exemptionReasonProduct = '';

                        if (defined('EXEMPTION_REASON')) {
                            $exemptionReasonProduct = EXEMPTION_REASON;
                        }
                        ?>

                        <option value='' <?= $exemptionReasonProduct === '' ? 'selected' : '' ?>>
                            <?php esc_html_e('Nenhuma') ?>
                        </option>

                        <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                            <?= $selected = in_array($exemptionReasonProduct, [$exemptionReason['code'], $exemptionReason['at_code']], true) ?>

                            <option
                                    title="<?= esc_html($exemptionReason['description']) ?>"
                                    value='<?= esc_html($exemptionReason['code']) ?>' <?= $selected ? 'selected' : '' ?>
                            >
                                <?= esc_html($exemptionReason['at_code'] . ' - ' . $exemptionReason['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Será usada se os artigos não tiverem uma taxa de IVA') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping">
                        <?php esc_html_e('Portes') ?>
                    </label>
                </th>
                <td>
                    <select id="exemption_reason_shipping" name='opt[exemption_reason_shipping]' class='inputOut'>
                        <?php
                        $exemptionReasonShipping = '';

                        if (defined('EXEMPTION_REASON_SHIPPING')) {
                            $exemptionReasonShipping = EXEMPTION_REASON_SHIPPING;
                        }
                        ?>

                        <option value='' <?= $exemptionReasonShipping === '' ? 'selected' : '' ?>>
                            <?php esc_html_e('Nenhuma') ?>
                        </option>

                        <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                            <?= $selected = in_array($exemptionReasonShipping, [$exemptionReason['code'], $exemptionReason['at_code']], true) ?>

                            <option
                                    title="<?= esc_html($exemptionReason['description']) ?>"
                                    value='<?= esc_html($exemptionReason['code']) ?>' <?= $selected ? 'selected' : '' ?>
                            >
                                <?= esc_html($exemptionReason['at_code'] . ' - ' . $exemptionReason['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Será usada se os portes não tiverem uma taxa de IVA') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <div class="subtitle">
            <?php esc_html_e('Vendas extra comunitárias') ?>
            <?php esc_html_e('(fora da união europeia)') ?>
            <a style="cursor: help;"
               title="<?php esc_html_e('Países da União Europeia') . ': ' . implode(", ", Tools::$europeanCountryCodes) ?>">(?)</a>
        </div>
        <table class="form-table mb-4">
            <tbody>

            <tr>
                <th>
                    <label for="exemption_reason_extra_community">
                        <?php esc_html_e('Artigos') ?>
                    </label>
                </th>
                <td>
                    <select id="exemption_reason_extra_community" name='opt[exemption_reason_extra_community]'
                            class='inputOut'>
                        <?php
                        $exemptionReasonExtraCommunity = '';

                        if (defined('EXEMPTION_REASON_EXTRA_COMMUNITY')) {
                            $exemptionReasonExtraCommunity = EXEMPTION_REASON_EXTRA_COMMUNITY;
                        }
                        ?>

                        <option value='' <?= $exemptionReasonExtraCommunity === '' ? 'selected' : '' ?>>
                            <?php esc_html_e('Nenhuma') ?>
                        </option>

                        <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                            <?= $selected = in_array($exemptionReasonExtraCommunity, [$exemptionReason['code'], $exemptionReason['at_code']], true) ?>

                            <option
                                    title="<?= esc_html($exemptionReason['description']) ?>"
                                    value='<?= esc_html($exemptionReason['code']) ?>' <?= $selected ? 'selected' : '' ?>
                            >
                                <?= esc_html($exemptionReason['at_code'] . ' - ' . $exemptionReason['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Será usada se os artigos não tiverem uma taxa de IVA') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="exemption_reason_shipping_extra_community">
                        <?php esc_html_e('Portes') ?>
                    </label>
                </th>
                <td>
                    <select id="exemption_reason_shipping_extra_community"
                            name='opt[exemption_reason_shipping_extra_community]'
                            class='inputOut'>
                        <?php
                        $exemptionReasonShippingExtraCommunity = '';

                        if (defined('EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY')) {
                            $exemptionReasonShippingExtraCommunity = EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY;
                        }
                        ?>

                        <option value='' <?= $exemptionReasonShippingExtraCommunity === '' ? 'selected' : '' ?>>
                            <?php esc_html_e('Nenhuma') ?>
                        </option>

                        <?php foreach ($exemptionReasons as $exemptionReason) : ?>
                            <?= $selected = in_array($exemptionReasonShippingExtraCommunity, [$exemptionReason['code'], $exemptionReason['at_code']], true) ?>

                            <option
                                    title="<?= esc_html($exemptionReason['description']) ?>"
                                    value='<?= esc_html($exemptionReason['code']) ?>' <?= $selected ? 'selected' : '' ?>
                            >
                                <?= esc_html($exemptionReason['at_code'] . ' - ' . $exemptionReason['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Será usada se os portes não tiverem uma taxa de IVA') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Nota de crédito -->
        <h2 class="title">
            <?php esc_html_e('Nota de crédito') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Criar documento de crédito -->
            <tr>
                <th>
                    <label for="create_credit_note"><?php esc_html_e('Documento de crédito') ?></label>
                </th>
                <td>
                    <?php
                    $createCreditNote = Boolean::NO;

                    if (defined('CREATE_CREDIT_NOTE')) {
                        $createCreditNote = (int)CREATE_CREDIT_NOTE;
                    }
                    ?>

                    <select id="create_credit_note" name='opt[create_credit_note]' class='inputOut'>
                        <option value='0' <?= ($createCreditNote === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($createCreditNote === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Criar automaticamente nota de crédito quando uma devolução é criada. Apenas será gerada se existir algum documento associado à encomenda.') ?></p>
                </td>
            </tr>

            <!-- Série do documento de crédito -->
            <tr>
                <th>
                    <label for="credit_note_document_set_id"><?php esc_html_e('Série do documento de crédito') ?></label>
                </th>
                <td>
                    <select id="credit_note_document_set_id" name='opt[credit_note_document_set_id]' class='inputOut'>
                        <?php foreach ($documentSets as $documentSet) : ?>
                            <?php
                            $htmlProps = '';
                            $documentSetId = $documentSet['document_set_id'];

                            if (defined('CREDIT_NOTE_DOCUMENT_SET_ID') && (int)CREDIT_NOTE_DOCUMENT_SET_ID === (int)$documentSet['document_set_id']) {
                                $htmlProps .= ' selected';
                            }
                            ?>

                            <option value='<?= esc_html($documentSetId) ?>' <?= esc_html($htmlProps) ?>>
                                <?= esc_html($documentSet['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Estado do documento -->
            <tr>
                <th>
                    <label for="credit_note_document_status"><?php esc_html_e('Estado do documento') ?></label>
                </th>
                <td>
                    <?php
                    $creditNoteDocumentStatus = DocumentStatus::DRAFT;

                    if (defined('CREDIT_NOTE_DOCUMENT_STATUS')) {
                        $creditNoteDocumentStatus = (int)CREDIT_NOTE_DOCUMENT_STATUS;
                    }
                    ?>

                    <select id="credit_note_document_status" name='opt[credit_note_document_status]' class='inputOut'>
                        <option value='0' <?= ($creditNoteDocumentStatus === DocumentStatus::DRAFT ? 'selected' : '') ?>><?php esc_html_e('Rascunho') ?></option>
                        <option value='1' <?= ($creditNoteDocumentStatus === DocumentStatus::CLOSED ? 'selected' : '') ?>><?php esc_html_e('Fechado') ?></option>
                    </select>
                </td>
            </tr>

            <!-- Enviar email -->
            <tr>
                <th>
                    <label for="credit_note_email_send"><?php esc_html_e('Enviar email') ?></label>
                </th>
                <td>
                    <?php
                    $creditNoteEmailSend = Boolean::NO;

                    if (defined('CREDIT_NOTE_EMAIL_SEND')) {
                        $creditNoteEmailSend = (int)CREDIT_NOTE_EMAIL_SEND;
                    }
                    ?>

                    <select id="credit_note_email_send" name='opt[credit_note_email_send]' class='inputOut'>
                        <option value='0' <?= ($creditNoteEmailSend === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($creditNoteEmailSend === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('A nota de crédito só é enviada para o cliente se for inserida no estado "fechado"') ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Artigos -->
        <h2 class="title">
            <?php esc_html_e('Artigos') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <?php if (is_array($warehouses) && !empty($warehouses)): ?>
                <tr>

                    <th>
                        <label for="moloni_product_warehouse"><?php esc_html_e('Armazém') ?></label>
                    </th>
                    <td>
                        <select id="moloni_product_warehouse" name='opt[moloni_product_warehouse]' class='inputOut'>
                            <option value='0'><?php esc_html_e('Armazém pré-definido') ?></option>
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option value='<?= esc_html($warehouse['warehouse_id']) ?>' <?= defined('MOLONI_PRODUCT_WAREHOUSE') && (int)MOLONI_PRODUCT_WAREHOUSE === (int)$warehouse['warehouse_id'] ? 'selected' : '' ?>>
                                    <?= esc_html($warehouse['title']) ?> (<?= esc_html($warehouse['code']) ?>)
                                </option>
                            <?php endforeach; ?>

                        </select>
                        <p class='description'><?php esc_html_e('Obrigatório') ?></p>
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th>
                    <label for="measure_unit_id"><?php esc_html_e('Unidade de medida') ?></label>
                </th>
                <td>
                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php if (is_array($measurementUnits)): ?>
                            <?php foreach ($measurementUnits as $measurementUnit) : ?>
                                <option value='<?= esc_html($measurementUnit['unit_id']) ?>' <?= defined('MEASURE_UNIT') && (int)MEASURE_UNIT === (int)$measurementUnit['unit_id'] ? 'selected' : '' ?>>
                                    <?= esc_html($measurementUnit['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Obrigatório') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="use_moloni_product_details"><?php esc_html_e('Usar dados do Moloni ') ?></label>
                </th>
                <td>
                    <select id="use_moloni_product_details" name='opt[use_moloni_product_details]' class='inputOut'>
                        <option value='0' <?= (defined('USE_MOLONI_PRODUCT_DETAILS') && (int)USE_MOLONI_PRODUCT_DETAILS === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('USE_MOLONI_PRODUCT_DETAILS') && (int)USE_MOLONI_PRODUCT_DETAILS === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Se o artigo já existir no Moloni, será usado o Nome e o Resumo que existem no Moloni, em vez dos que estão na encomenda') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="use_name_for_moloni_reference"><?php esc_html_e('Incluir nome na referência ') ?></label>
                </th>
                <td>
                    <select id="use_name_for_moloni_reference" name='opt[use_name_for_moloni_reference]'
                            class='inputOut'>
                        <option value='0' <?= (defined('USE_NAME_FOR_MOLONI_REFERENCE') && (int)USE_NAME_FOR_MOLONI_REFERENCE === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('USE_NAME_FOR_MOLONI_REFERENCE') && (int)USE_NAME_FOR_MOLONI_REFERENCE === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Caso um artigo não tenha referência é criada uma automaticamente para o mesmo.') ?><br>
                        <?php esc_html_e('Por defeito a referência é feita com base no ID do artigo, esta opção faz com que a referência automática use também o nome do artigo de forma a evitar conflitos.') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Clientes -->
        <h2 class="title">
            <?php esc_html_e('Clientes') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>
            <tr>
                <?php
                $customerNumberPrefix = '';

                if (defined('CUSTOMER_NUMBER_PREFIX')) {
                    $customerNumberPrefix = esc_html(CUSTOMER_NUMBER_PREFIX);
                }
                ?>

                <th>
                    <label for="customer_number_prefix"><?php esc_html_e('Prefixo cliente') ?></label>
                </th>
                <td>
                    <input value="<?= $customerNumberPrefix ?>"
                           id="customer_number_prefix"
                           name='opt[customer_number_prefix]'
                           type="text"
                           style="width: 330px;"
                           placeholder="">
                    <p class='description'>
                        <?php esc_html_e('Prefixo adicionado aos clientes criados através do plugin') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="maturity_date_id"><?php esc_html_e('Prazo de Vencimento') ?></label>
                </th>
                <td>
                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option value='0' <?= defined('MATURITY_DATE') && (int)MATURITY_DATE === 0 ? 'selected' : '' ?>><?php esc_html_e('Escolha uma opção') ?></option>
                        <?php if (is_array($maturityDates)): ?>
                            <?php foreach ($maturityDates as $maturityDate) : ?>
                                <option value='<?= esc_html($maturityDate['maturity_date_id']) ?>' <?= defined('MATURITY_DATE') && (int)MATURITY_DATE === (int)$maturityDate['maturity_date_id'] ? 'selected' : '' ?>>
                                    <?= esc_html($maturityDate['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Prazo de vencimento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id"><?php esc_html_e('Método de pagamento') ?></label>
                </th>
                <td>
                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option value='0' <?= defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === 0 ? 'selected' : '' ?>><?php esc_html_e('Escolha uma opção') ?></option>
                        <?php if (is_array($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $paymentMethod) : ?>
                                <option value='<?= esc_html($paymentMethod['payment_method_id']) ?>' <?= defined('PAYMENT_METHOD') && (int)PAYMENT_METHOD === (int)$paymentMethod['payment_method_id'] ? 'selected' : '' ?>>
                                    <?= esc_html($paymentMethod['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Método de pagamento por defeito do cliente') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field"><?php esc_html_e('Contribuinte do cliente') ?></label>
                </th>
                <td>
                    <?php
                    $customFields = Model::getPossibleVatFields();

                    $vatField = '';

                    if (defined('VAT_FIELD') && !empty(VAT_FIELD)) {
                        $vatField = VAT_FIELD;
                    } elseif (Context::isMoloniVatPluginActive()) {
                        $vatField = '_billing_vat';

                        if (!in_array($vatField, $customFields, true)) {
                            $customFields[] = $vatField;
                        }
                    }
                    ?>

                    <select id="vat_field" name='opt[vat_field]' class='inputOut'>
                        <option value='' <?= empty($vatField) ? 'selected' : '' ?>>
                            <?php esc_html_e('Escolha uma opção') ?>
                        </option>

                        <?php foreach ($customFields as $customField) : ?>
                            <option value='<?= esc_html($customField) ?>' <?= $vatField === $customField ? 'selected' : '' ?>>
                                <?= esc_html($customField) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class='description'>
                        <?php esc_html_e('Custom field associado ao contribuinte do cliente. Se o campo não aparecer, certifique-se que tem pelo menos uma encomenda com o campo em uso.') ?>
                        <br>
                        <?php _e('Para que o Custom Field apareça, deverá ter pelo menos uma encomenda com o contribuinte preenchido. O campo deverá ter um nome por exemplo <i>_billing_vat</i>.') ?>
                        <br>
                        <?php _e('Se ainda não tiver nenhum campo para o contribuinte, poderá adicionar o plugin disponível <a target="_blank" href="https://wordpress.org/plugins/contribuinte-checkout/">aqui.</a> ') ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Hooks -->
        <h2 class="title">
            <?php esc_html_e('Hooks') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Listagem de encomendas -->
            <tr>
                <th>
                    <label for="moloni_show_download_column"><?php esc_html_e('Listagem de encomendas WooCommerce') ?></label>
                </th>
                <td>
                    <select id="moloni_show_download_column" name='opt[moloni_show_download_column]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_SHOW_DOWNLOAD_COLUMN') && (int)MOLONI_SHOW_DOWNLOAD_COLUMN === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Adicionar, no WooCommerce, uma coluna na listagem de encomendas com download rápido de documentos em PDF') ?></p>
                </td>
            </tr>

            <!-- Detalhes da encomenda -->
            <tr>
                <th>
                    <label for="moloni_show_download_my_account_order_view"><?php esc_html_e('Detalhes de encomenda WooCommerce') ?></label>
                </th>
                <td>
                    <select id="moloni_show_download_my_account_order_view" name='opt[moloni_show_download_my_account_order_view]' class='inputOut'>
                        <?php $myAccountOrderViewShowDownload = defined('MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW') ? (int)MOLONI_SHOW_DOWNLOAD_MY_ACCOUNT_ORDER_VIEW : Boolean::NO; ?>

                        <option value='0' <?= ($myAccountOrderViewShowDownload === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($myAccountOrderViewShowDownload === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Adicionar, na página visualização encomenda (do cliente), uma secção com o documento gerado para descarregar em PDF') ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Automatização -->
        <h2 class="title">
            <?php esc_html_e('Automatização') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Criação automática de documentos -->
            <tr>
                <th>
                    <label for="invoice_auto"><?php esc_html_e('Criar documento automaticamente') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <option value='0' <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Criar documentos automaticamente') ?></p>
                </td>
            </tr>

            <!-- Estado das encomendas -->
            <tr id="invoice_auto_status_line" <?= (defined('INVOICE_AUTO') && (int)INVOICE_AUTO === 0 ? 'style="display: none;"' : '') ?>>
                <th>
                    <label for="invoice_auto_status"><?php esc_html_e('Criar documentos quando a encomenda está') ?></label>
                </th>
                <td>
                    <select id="invoice_auto_status" name='opt[invoice_auto_status]' class='inputOut'>
                        <?php
                            $invoiceAutoStatus = defined('INVOICE_AUTO_STATUS') ? INVOICE_AUTO_STATUS : '' ;
                        ?>

                        <?php foreach ($wcOrdersStatus as $id => $name) : ?>
                            <?php
                                $needle = 'wc-';

                                if(substr($id, 0, strlen($needle)) === $needle) {
                                    $parsedId = substr($id, strlen($needle));
                                } else {
                                    $parsedId = $id;
                                }
                            ?>

                            <option value='<?= esc_html($parsedId) ?>' <?= $invoiceAutoStatus === $parsedId ? 'selected' : '' ?>>
                                <?= esc_html($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'><?php esc_html_e('Os documentos vão ser criados automaticamente assim que estiverem no estado selecionado') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="alert_email"><?php esc_html_e('Alerta de erros via e-mail') ?></label>
                </th>
                <td>
                    <input value="<?= esc_html(defined('ALERT_EMAIL') ? ALERT_EMAIL : '') ?>"
                           id="alert_email"
                           name='opt[alert_email]'
                           type="text"
                           style="width: 330px;"
                           placeholder="mail@example.com">
                    <p class='description'><?php esc_html_e('E-mail usado para envio de notificações em caso de erro') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?php esc_html_e('Sincronizar stocks automaticamente') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim, de todos os armazéns') ?></option>

                        <?php if (is_array($warehouses)): ?>
                            <optgroup label="<?php esc_html_e('Sim, apenas do armazém:') ?>">

                                <?php foreach ($warehouses as $warehouse) : ?>
                                    <option value='<?= esc_html($warehouse['warehouse_id']) ?>' <?= defined('MOLONI_STOCK_SYNC') && (int)MOLONI_STOCK_SYNC === $warehouse['warehouse_id'] ? 'selected' : '' ?>>
                                        <?= esc_html($warehouse['title']) ?> (<?= esc_html($warehouse['code']) ?>)
                                    </option>
                                <?php endforeach; ?>

                            </optgroup>
                        <?php endif; ?>

                    </select>
                    <p class='description'><?php esc_html_e('Sincronização de stocks automática (corre a cada 5 minutos e atualiza o stock dos artigos com base no Moloni)') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?php esc_html_e('Criar artigos') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_PRODUCT_SYNC') && (int)MOLONI_PRODUCT_SYNC === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Ao guardar um artigo no WooCommerce, o plugin vai criar automaticamente o artigo no Moloni') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync_update"><?php esc_html_e('Atualizar artigos') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync_update" name='opt[moloni_product_sync_update]' class='inputOut'>
                        <option value='0' <?= (defined('MOLONI_PRODUCT_SYNC_UPDATE') && (int)MOLONI_PRODUCT_SYNC_UPDATE === 0 ? 'selected' : '') ?>><?php esc_html_e('Não') ?></option>
                        <option value='1' <?= (defined('MOLONI_PRODUCT_SYNC_UPDATE') && (int)MOLONI_PRODUCT_SYNC_UPDATE === 1 ? 'selected' : '') ?>><?php esc_html_e('Sim') ?></option>
                    </select>
                    <p class='description'><?php esc_html_e('Ao guardar um artigo no WooCommerce, se o artigo já existir no Moloni vai atualizar os dados do artigo') ?></p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Avançado -->
        <h2 class="title">
            <?php esc_html_e('Avançado') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Limitar encomendas por data -->
            <tr>
                <th>
                    <label for="order_created_at_max"><?php esc_html_e('Apresentar encomendas desde a seguinte data') ?></label>
                </th>
                <td>
                    <?php
                    $orderCreatedAtMax = '';

                    if (defined('ORDER_CREATED_AT_MAX')) {
                        $orderCreatedAtMax = ORDER_CREATED_AT_MAX;
                    }
                    ?>

                    <input value="<?= esc_html($orderCreatedAtMax) ?>"
                           id="order_created_at_max"
                           name='opt[order_created_at_max]'
                           type="date"
                           style="width: 330px;"
                           placeholder="">

                    <p class='description'>
                        <?php esc_html_e('Data usada para limitar a pesquisa de encomendas pendentes') ?>
                    </p>
                </td>
            </tr>

            <!-- Ativar modo debug -->
            <tr>
                <th>
                    <label for="moloni_debug_mode"><?php esc_html_e('Ativar modo DEBUG') ?></label>
                </th>
                <td>
                    <?php
                    $moloniDebugMode = Boolean::NO;

                    if (defined('MOLONI_DEBUG_MODE')) {
                        $moloniDebugMode = (int)MOLONI_DEBUG_MODE;
                    }
                    ?>

                    <select id="moloni_debug_mode" name='opt[moloni_debug_mode]' class='inputOut'>
                        <option value='0' <?= ($moloniDebugMode === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('Não') ?>
                        </option>
                        <option value='1' <?= ($moloniDebugMode === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Sim') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Ativar funcionalidades DEV e aumentar logs') ?></p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?php esc_html_e('Guardar alterações') ?>">
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</form>

<script>
    var originalCAE = <?= (defined('DOCUMENT_SET_CAE_ID') && (int)DOCUMENT_SET_CAE_ID > 0 ? (int)DOCUMENT_SET_CAE_ID : 0) ?>;

    jQuery(document).ready(function () {
        Moloni.Settings.init(originalCAE);
    });
</script>
