if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.modals === undefined) {
    Moloni.modals = {};
}

Moloni.modals.ProductsBulkProcess = (async function (rows, createProductAction, updateStockAction, endCallback) {
    const $ = jQuery;

    const history = {
        'create': [],
        'stock': [],
        'update': [],
    };
    const actionModal = $('#action-modal');

    const closeButton = actionModal.find('.button-secondary');
    const spinner = actionModal.find('#action-modal-spinner');
    const content = actionModal.find('#action-modal-content');
    const error = actionModal.find('#action-modal-error');
    const titleStart = actionModal.find('#action-modal-title-start');
    const titleEnd = actionModal.find('#action-modal-title-end');

    content.html('').show();
    closeButton.hide();
    error.hide();
    spinner.show();
    titleStart.show();
    titleEnd.hide();

    const updateStock = (mlProductId, wcProductId) => {
        var data = {
            'action': updateStockAction,
            'ml_product_id': mlProductId,
            'wc_product_id': wcProductId
        };

        return $.ajax({
            type: 'POST',
            url: ajaxurl,
            data,
            async: true
        });
    }

    const createProduct = (mlProductId, wcProductId) => {
        var data = {
            'action': createProductAction,
            'ml_product_id': mlProductId,
            'wc_product_id': wcProductId
        };

        return $.ajax({
            type: 'POST',
            url: ajaxurl,
            data,
            async: true
        });
    }

    const showProcessReport = () => {
        content.html('');

        let processReport = $('<div class="flex flex-col gap-3"></div>');
        let list = $('<div class="flex flex-col p-2 rounded bg-neutral-40 max-h-44 overflow-auto mt-2" style="display: none"></div>');
        let toggle = $('<a class="mt-1" href="#">Clique para ver</a>');
        let wrapper = $('<div></div>');

        let possibleActions = [
            {
                name: 'create',
                label: 'processos de criação de produtos.'
            },
            {
                name: 'update',
                label: 'processos de atualização de produtos.'
            },
            {
                name: 'stock',
                label: 'processos de atualização de stock.'
            }
        ];

        possibleActions.forEach((possibleAction) => {
            if (!history[possibleAction.name].length) {
                return;
            }

            let tempList = list.clone();
            let tempToggle = toggle.clone();
            let tempWrapper = wrapper.clone();

            history[possibleAction.name].forEach((element) => tempList.append('<div>' + element + '</div>'));

            tempToggle.on('click', function () {
                tempList.toggle(200);
            });

            tempWrapper.append('<div>Concluídos ' + history[possibleAction.name].length + ' ' + possibleAction.label + '</div>');
            tempWrapper.append(tempList, tempToggle);

            processReport.append(tempWrapper);
        });

        content.append(processReport);
    };

    const processRow = async () => {
        if (!rows.length) {
            return;
        }

        var element = $(rows.shift());
        var row = element.closest('.product__row');

        var mlProductId = row.data('moloniId');
        var wcProductId = row.data('wcId');
        var name = row.find('.product__row-name').text().trim();
        var reference = row.find('.product__row-reference').text().trim();

        var request = null;
        var action = null;

        if (element.hasClass('checkbox_create_product')) {
            request = createProduct.bind(this, mlProductId, wcProductId);
            action = 'create'
        } else if (element.hasClass('checkbox_update_stock_product')) {
            request = updateStock.bind(this, mlProductId, wcProductId);
            action = 'stock'
        }

        if (!request || !action) {
            return;
        }

        var displayName = name + ' (' + reference + ')';

        content.html('A processar produto: ' + displayName);

        var data = await request();

        if (data && data.valid) {
            history[action].push(displayName + ': Processado com sucesso');

            var newRow = $(data.product_row);

            row.replaceWith(newRow);
            newRow.addClass('product__row--new');

            setTimeout(() => {
                newRow.removeClass('product__row--new');
            }, 3000);
        } else {
            history[action].push(displayName + ': Erro no processo');
            console.log(data);

            row.addClass('product__row--error');

            setTimeout(() => {
                row.removeClass('product__row--error');
            }, 3000);
        }

        if (actionModal.is(':visible')) {
            return await processRow();
        }
    }

    actionModal.modal({
        fadeDuration: 0,
        escapeClose: false,
        clickClose: false,
        closeExisting: true,
    });

    try {
        await processRow();

        showProcessReport();

        spinner.fadeOut(50);
    } catch (ex) {
        spinner.fadeOut(50);
        content.fadeOut(50);
        error.fadeIn(200);

        console.log(ex);
    }

    titleStart.hide();
    titleEnd.show();
    closeButton.show(200);

    if (endCallback) {
        endCallback();
    }
});
