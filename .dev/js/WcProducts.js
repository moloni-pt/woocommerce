if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.WcProducts === undefined) {
    Moloni.WcProducts = {};
}

Moloni.WcProducts = (function ($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        let allSelectedCheckboxQuery = '.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked';
        let actionButton = $('.button-start-exports');

        $('.checkbox_create_product:enabled, .checkbox_update_stock_product:enabled').off('change').on('change', function () {
            if ($(allSelectedCheckboxQuery).length) {
                actionButton.removeAttr("disabled");
            } else {
                actionButton.attr("disabled", true);
            }
        });

        actionButton.off('click').on('click', function () {
            let rows = [];

            $(allSelectedCheckboxQuery).each(function () {
                rows.push($(this));
            });

            if (rows.length) {
                Moloni.modals.ProductsBulkProcess(rows, 'toolsCreateMoloniProduct', 'toolsUpdateMoloniStock');
            }
        });
    }

    return {
        init: init,
    }
}(jQuery));
