'use strict';

jQuery(document).ready(function ($) {
    var ProgressWindow = $("#bulk-action-progress-modal");
    var ProgressWindowMessage = $('#bulk-action-progress-message');
    var ProgressWindowCurrent = $('#bulk-action-progress-current');
    var ProgressWindowTotal = $('#bulk-action-progress-total');
    var ProgressWindowTitleStart = $('#bulk-action-progress-title-start');
    var ProgressWindowTitleFinish = $('#bulk-action-progress-title-finish');

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

    //             Create documents             //

    function BulkGenInvoice() {
        ShowModal();
        ResetCounters();

        SelectedOrders = $('input[id^="moloni-pending-order-"]:checked');

        if (SelectedOrders && SelectedOrders.length === 0) {
            ProgressWindowMessage.html("<div>Não foram selecionadas encomendas" + ProgressWindowCloseButton + "</div>");
        } else {
            ProgressWindowMessage.html('A iniciar processo...');
            ProgressWindowTotal.html(SelectedOrders.length);

            GenInvoice(GetNextOrder());
        }
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
            $("#moloni-pending-order-row-" + GetNextOrder()).remove();
            FinalResultSuccess.push(result);
        } else {
            FinalResultFailure.push(result);
        }

        RemoveLastOrder();

        if (GetNextOrder()) {
            GenInvoice(GetNextOrder());
        } else {
            setTimeout(FinishBulkGenInvoice, 1000);
        }
    }

    function FinishBulkGenInvoice() {
        ProgressWindowTitleStart.hide();
        ProgressWindowTitleFinish.show();
        ProgressWindowMessage.html("");

        ProgressWindowMessage.append('Documentos emitidos: ' + FinalResultSuccess.length);
        ProgressWindowMessage.append('<br>Documentos com erros: ' + FinalResultFailure.length);
        ProgressWindowMessage.append(ProgressWindowCloseButton);
    }

    //             Discard orders             //

    function BulkDiscardOrder() {
        ShowModal();
        ResetCounters();

        SelectedOrders = $('input[id^="moloni-pending-order-"]:checked');

        if (SelectedOrders && SelectedOrders.length === 0) {
            ProgressWindowMessage.html("<div>Não foram selecionadas encomendas" + ProgressWindowCloseButton + "</div>");
        } else {
            ProgressWindowMessage.html('A iniciar processo...');
            ProgressWindowTotal.html(SelectedOrders.length);

            DiscardInvoice(GetNextOrder());
        }
    }

    function DiscardInvoice(OrderId, DelayTime) {
        DelayTime = DelayTime || 1000;

        ProgressCurrent++;
        ProgressWindowCurrent.html(ProgressCurrent);

        setTimeout(function () {
            ProgressWindowMessage.html("A descartar encomenda " + OrderId);

            var data = {
                'action': 'discardOrder',
                'id': OrderId
            };

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data,
                success: HandleDiscardInvoiceSuccess,
                async: false
            });
        }, DelayTime)
    }

    function HandleDiscardInvoiceSuccess(result) {
        ProgressWindowMessage.html(result.message);

        if (result.valid) {
            $("#moloni-pending-order-row-" + GetNextOrder()).remove();
            FinalResultSuccess.push(result);
        } else {
            FinalResultFailure.push(result);
        }

        RemoveLastOrder();

        if (GetNextOrder()) {
            DiscardInvoice(GetNextOrder());
        } else {
            setTimeout(FinishBulkDiscardOrder, 1000);
        }
    }

    function FinishBulkDiscardOrder() {
        ProgressWindowTitleStart.hide();
        ProgressWindowTitleFinish.show();
        ProgressWindowMessage.html("");

        ProgressWindowMessage.append('Encomendas descartadas: ' + FinalResultSuccess.length);
        ProgressWindowMessage.append('<br>Encomendas com erros: ' + FinalResultFailure.length);
        ProgressWindowMessage.append(ProgressWindowCloseButton);
    }

    //             Common             //

    function RemoveLastOrder() {
        SelectedOrders = SelectedOrders.slice(1);
    }

    function GetNextOrder() {
        return (!SelectedOrders[0] || !SelectedOrders[0].value) ? false : SelectedOrders[0].value;
    }

    function ShowModal() {
        ProgressWindow.modal({
            fadeDuration: 100,
            escapeClose: false,
            clickClose: false,
            showClose: true
        });

        ProgressWindowTitleFinish.hide();
        ProgressWindowTitleStart.show();
    }

    //             Auxiliary             //

    function ResetCounters() {
        FinalResultSuccess = [];
        FinalResultFailure = [];
        ProgressCurrent = 0;
    }

    //             Observers             //

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
                BulkGenInvoice();
            } else if (action === 'bulkDiscardOrder') {
                BulkDiscardOrder();
            }
        });
    }

    new PendingOrders;
});
