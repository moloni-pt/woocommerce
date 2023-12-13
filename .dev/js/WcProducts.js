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
        $('.export_product').off('click').on('click', function () {
            var wcProductId = $(this).closest('.product__row').data('wcId');

            exportProduct(wcProductId);
        });

        $('.export_stock').off('click').on('click', function () {
            var mlProductId = $(this).closest('.product__row').data('moloniId');
            var wcProductId = $(this).closest('.product__row').data('wcId');

            exportStock(wcProductId, mlProductId);
        });
    }

    //            Requests            //

    function exportStock(wcProductId, mlProductId) {
        disableAllButtons();

        var data = {
            'action': 'toolsUpdateMoloniStock',
            'wc_product_id': wcProductId,
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

    function exportProduct(wcProductId) {
        disableAllButtons();

        var data = {
            'action': 'toolsCreateMoloniProduct',
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
