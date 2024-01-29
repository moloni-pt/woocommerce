if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.Settings === undefined) {
    Moloni.Settings = {};
}

Moloni.Settings = (function($) {
    var definedCAE;

    function init(originalCAE) {
        definedCAE = originalCAE;

        startObservers();
    }

    function startObservers() {
        documentSetChange();
        documentAutoChange();
        documentStatusChange();

        shippingInfoChange();
        loadAddressChange();
    }

    function documentSetChange() {
        var select = $('#document_set_id');
        var line = $('#document_set_cae_line');
        var caeElementId = $('#document_set_cae_id');
        var caeElementName = $('#document_set_cae_name');
        var caeElementWarning = $('#document_set_cae_warning');

        if (!select.length ||
            !line.length ||
            !caeElementId.length ||
            !caeElementName.length ||
            !caeElementWarning.length) {
            return;
        }

        select.on('change', function () {
            var selectedOption = $('option[value=' + select.val() + ']');

            caeElementId.val(selectedOption.attr('data-eac-id') || 0);
            caeElementName.val(selectedOption.attr('data-eac-name') || 'Placeholder');

            if (parseInt(definedCAE) !== parseInt(caeElementId.val())) {
                caeElementWarning.show(200);
            } else {
                caeElementWarning.hide(200);
            }

            parseInt(caeElementId.val()) > 0 ? line.show(200) : line.hide(200);
        });

        select.trigger('change');
    }

    function documentAutoChange() {
        toggleLineObserver('invoice_auto' , 'invoice_auto_status_line');
    }

    function documentStatusChange() {
        toggleLineObserver('document_status' , 'create_bill_of_lading_line');
    }

    function shippingInfoChange() {
        toggleLineObserver('shipping_info' , 'load_address_line');
    }

    function loadAddressChange() {
        toggleLineObserver('load_address' , 'load_address_custom_line');
    }

    //      Auxiliary      //

    function toggleLineObserver(inputId, lineId) {
        var input = $('#' + inputId);
        var line = $('#' + lineId);

        if (!input.length || !line.length) {
            return;
        }

        input.on('change', function () {
            parseInt(input.val()) === 1 ? line.show(200) : line.hide(200);
        });

        input.trigger('change');
    }

    return {
        init: init
    }
}(jQuery));
