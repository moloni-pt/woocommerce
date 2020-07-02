'use strict';

jQuery(document).ready(function ($) {

    var ProgressWindow = $("#bulk-action-progress-modal");
    var ProgressWindowMessage = $('#bulk-action-progress-message');
    var ProgressWindowCurrent = $('#bulk-action-progress-current');
    var ProgressWindowTotal = $('#bulk-action-progress-total');
    var ProgressWindowTitle = $('#bulk-action-progress-content h2')

    var ProgressCurrent = 0;
    var ProgressWindowCloseButton = '<br><a class="wp-core-ui button-secondary" style="width: 80px; text-align: center; float:right" href="#" rel="modal:close">Fechar</a>'

    var SelectedOrders = [];

    var FinalResultSuccess = [];
    var FinalResultFailure = [];

    function PendingOrders() {
        SetActions();
    }

    function SetActions() {
        OnMassSelectClick();
        OnIndividualSelectClick();
        OnActionClick();
    }


    function BulkGenInvoice() {
        ShowModal();
        ResetCounters();

        SelectedOrders = $('input[id^="moloni-pending-order-"]:checked');

        if (SelectedOrders && SelectedOrders.length === 0) {
            ProgressWindowMessage
                .html("<div>Não foram seleccionadas encomendas para emitir" + ProgressWindowCloseButton + "</div>");
        } else {
            ProgressWindowMessage.html('A iniciar processo...');
            ProgressWindowTotal.html(SelectedOrders.length);

            GenInvoice(GetNextInvoice());
        }
    }

    function FinishBulkImport() {
        ProgressWindowTitle.html("Processo de importação concluído");
        ProgressWindowMessage.html("");

        ProgressWindowMessage.append('Documentos emitidos: ' + FinalResultSuccess.length);
        ProgressWindowMessage.append('<br>Documentos com erros: ' + FinalResultFailure.length);
        ProgressWindowMessage.append(ProgressWindowCloseButton);
    }

    /**
     * @return {boolean}
     */
    function GetNextInvoice() {
        return (!SelectedOrders[0] || !SelectedOrders[0].value) ? false : SelectedOrders[0].value;
    }

    function RemoveLastInvoice() {
        SelectedOrders = SelectedOrders.slice(1);
    }

    function GenInvoice(OrderId, DelayTime) {
        DelayTime = DelayTime || 1000;

        ProgressCurrent++;
        ProgressWindowCurrent.html(ProgressCurrent);

        setTimeout(function () {
            ProgressWindowMessage.html("A gerar documento da encomenda " + OrderId);

            var data = {
                'action': 'genInvoice',
                'id': OrderId
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data,
                success: HandleGenInvoiceSuccess,
                async: false
            });
        }, DelayTime)
    }

    function HandleGenInvoiceSuccess(result) {
        ProgressWindowMessage.html(result.message);
        if (result.valid) {
            $("#moloni-pending-order-row-" + GetNextInvoice()).remove();
            FinalResultSuccess.push(result);
        } else {
            FinalResultFailure.push(result);
        }

        RemoveLastInvoice();
        if (GetNextInvoice()) {
            GenInvoice(GetNextInvoice());
        } else {
            setTimeout(FinishBulkImport, 1000);
        }
    }

    function ShowModal() {
        ProgressWindow.modal({
            escapeClose: false,
            clickClose: false,
            showClose: true
        });
    }

    function ResetCounters() {
        FinalResultSuccess = [];
        FinalResultFailure = [];
        ProgressCurrent = 0;
    }

    function OnMassSelectClick() {
        $(".moloni-pending-orders-select-all").click(function () {
            $('input[id^="moloni-pending-order-"]').not(this).prop('checked', this.checked);
        });
    }

    function OnIndividualSelectClick() {
        $('input[id^="moloni-pending-order-"]').click(function () {
            var AllSelected = true;
            var AllPendingOrders = $('input[id^="moloni-pending-order-"]');

            AllPendingOrders.each(function (Index, PendingOrder) {
                if (!PendingOrder.checked) {
                    AllSelected = false;
                }
            });

            $(".moloni-pending-orders-select-all").prop('checked', AllSelected);
        });
    }

    function OnActionClick() {
        $('.tablenav #doAction').click(function () {
            var action = $('#bulk-action-selector-top').val();

            if (action === 'bulkGenInvoice') {
                BulkGenInvoice()
            } else if (action === 'bulkRemInvoice') {

            }
        });
    }

    new PendingOrders;
});
