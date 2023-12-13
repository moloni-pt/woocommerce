if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.MoloniProducts === undefined) {
    Moloni.MoloniProducts = {};
}

Moloni.MoloniProducts = (function ($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        $('.import_product').off('click').on('click', function () {
            var mlProductId = $(this).closest('.product__row').data('moloniId');

            importProduct(mlProductId);
        });

        $('.import_stock').off('click').on('click', function () {
            var mlProductId = $(this).closest('.product__row').data('moloniId');
            var wcProductId = $(this).closest('.product__row').data('wcId');

            importStock(mlProductId, wcProductId);
        });
    }

    //            Requests            //

    function importStock(mlProductId, wcProductId) {
        disableAllButtons();

        var data = {
            'action': 'toolsUpdateWcStock',
            'ml_product_id': mlProductId,
            'wc_product_id': wcProductId
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data,
            success: onAjaxRequestFinishes,
            async: true
        });
    }

    function importProduct(mlProductId) {
        disableAllButtons();

        var data = {
            'action': 'toolsCreateWcProduct',
            'ml_product_id': mlProductId
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data,
            success: onAjaxRequestFinishes,
            async: true
        });
    }

    //            Request callbacks            //

    function onAjaxRequestFinishes(result) {
        // enableAllButtons();

        if (result.valid) {
            location.reload();

            // toggleRow();
        } else {
            console.log(result);
            alert('Ocorreu um erro!');
        }
    }

    //            Auxiliary            //

    function enableAllButtons() {
        $('.dropdown').removeClass('dropdown--disabled')
    }

    function disableAllButtons() {
        $('.dropdown').addClass('dropdown--disabled')
    }

    function toggleRow() {
        // todo: do stuff

        startObservers();
    }

    return {
        init: init,
    }
}(jQuery));
