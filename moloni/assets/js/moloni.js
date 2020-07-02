'use strict';

jQuery(document).ready(function ($) {

    var ProgressWindow;
    var ProgressWindowMessage;
    var ProgressCurrent = 0;
    var ProgressTotal = 0;
    var ProgressWindowCloseButton = '<br><a class="wp-core-ui button-secondary" style="width: 80px; text-align: center; float:right" href="#" rel="modal:close">Fechar</a>'

    function PendingOrders() {
        ProgressWindow = $("#bulk-action-progress-modal");
        ProgressWindowMessage = $('#bulk-action-progress-message');
        LoadActions();
    }

    function LoadActions() {
        $(".moloni-pending-orders-select-all").click(function () {
            $('input[id^="moloni-pending-order-"]').not(this).prop('checked', this.checked);
        });

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

        $('.tablenav #doAction').click(function () {
            var action = $('#bulk-action-selector-top').val();

            if (action === 'bulkGenInvoice') {
                bulkGenInvoice()
            } else if (action === 'bulkRemInvoice') {

            }
        });
    }

    /**
     * @returns {boolean}
     */
    function bulkGenInvoice() {
        ProgressWindow.modal({
            escapeClose: false,
            clickClose: false,
            showClose: true
        });

        var SelectedOrders = $('input[id^="moloni-pending-order-"]:checked');

        if (SelectedOrders && SelectedOrders.length === 0) {
            ProgressWindowMessage
                .html("<div>Não foram seleccionadas encomendas para emitir" + ProgressWindowCloseButton + "</div>");
            return false;
        }

        ProgressTotal = SelectedOrders.length;
        ProgressWindowMessage.html('');
        $('#bulk-action-progress-total').html(ProgressTotal);
    }

    new PendingOrders;
});
