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
        let actionButton = $('.button-start-exports');
        let allProductCheckbox = $('.checkbox_create_product:enabled');
        let allStockCheckbox = $('.checkbox_update_stock_product:enabled');
        let productMaster = $('.checkbox_create_product_master');
        let stockMaster = $('.checkbox_update_stock_product_master');

        allProductCheckbox.add(allStockCheckbox).off('change').on('change', function () {
            dealWithMasters();
            dealWithActionButton();
        });

        actionButton.off('click').on('click', function () {
            doAction();
        });

        productMaster.off('click').on('click', function () {
            allProductCheckbox.prop('checked', $(this).prop("checked"));

            dealWithActionButton();
        });

        stockMaster.off('click').on('click', function () {
            allStockCheckbox.prop('checked', $(this).prop("checked"));

            dealWithActionButton();
        });

        dealWithMasters();
        dealWithActionButton();
    }

    function dealWithActionButton() {
        let actionButton = $('.button-start-exports');

        if ($('.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked').length) {
            actionButton.removeAttr("disabled");
        } else {
            actionButton.attr("disabled", true);
        }
    }

    function dealWithMasters() {
        $(".checkbox_create_product_master").prop('checked', $(".checkbox_create_product:enabled:checked").length === $(".checkbox_create_product:enabled").length);
        $(".checkbox_update_stock_product_master").prop('checked', $(".checkbox_update_stock_product:enabled:checked").length === $(".checkbox_update_stock_product:enabled").length);
    }

    function doAction() {
        let rows = [];

        $('.checkbox_create_product:enabled:checked, .checkbox_update_stock_product:enabled:checked').each(function () {
            rows.push($(this));
        });

        if (rows.length) {
            Moloni.modals.ProductsBulkProcess(
                rows,
                'toolsCreateMoloniProduct',
                'toolsUpdateMoloniStock',
                startObservers
            );
        }
    }

    return {
        init: init,
    }
}(jQuery));
