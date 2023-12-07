if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.OrdersBulkAction === undefined) {
    Moloni.OrdersBulkAction = {};
}

Moloni.OrdersBulkAction = (function() {
    var ProgressWindow = jQuery("#bulk-action-progress-modal");
    var ProgressWindowMessage = jQuery('#bulk-action-progress-message');
    var ProgressWindowCurrent = jQuery('#bulk-action-progress-current');
    var ProgressWindowTotal = jQuery('#bulk-action-progress-total');
    var ProgressWindowTitleStart = jQuery('#bulk-action-progress-title-start');
    var ProgressWindowTitleFinish = jQuery('#bulk-action-progress-title-finish');

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

        SelectedOrders = jQuery('input[id^="moloni-pending-order-"]:checked');

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

            jQuery.ajax({
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
            jQuery("#moloni-pending-order-row-" + GetNextOrder()).remove();
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

        SelectedOrders = jQuery('input[id^="moloni-pending-order-"]:checked');

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

            jQuery.ajax({
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
            jQuery("#moloni-pending-order-row-" + GetNextOrder()).remove();
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
        jQuery(".moloni-pending-orders-select-all").click(function () {
            jQuery('input[id^="moloni-pending-order-"]').not(this).prop('checked', this.checked);
        });
    }

    function OnIndividualSelectClick() {
        jQuery('input[id^="moloni-pending-order-"]').click(function () {
            var AllSelected = true;
            var AllPendingOrders = jQuery('input[id^="moloni-pending-order-"]');

            AllPendingOrders.each(function (Index, PendingOrder) {
                if (!PendingOrder.checked) {
                    AllSelected = false;
                }
            });

            jQuery(".moloni-pending-orders-select-all").prop('checked', AllSelected);
        });
    }

    function OnActionClick() {
        jQuery('.tablenav #doAction').click(function () {
            var action = jQuery('#bulk-action-selector-top').val();

            if (action === 'bulkGenInvoice') {
                BulkGenInvoice();
            } else if (action === 'bulkDiscardOrder') {
                BulkDiscardOrder();
            }
        });
    }

    new PendingOrders;
});
