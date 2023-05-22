if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Logs = (function($) {
    var Modal;

    function init() {
        startObservers();
    }

    function startObservers()
    {
        Modal = $("#logs-context-modal");
    }

    function openContextDialog(data) {
        var context = JSON.stringify(data || {}, null, 2);

        Modal.find('#logs-context-modal-content').text(context);
        Modal.find('Button').off('click').on('click', function () {
            downloadContextData(context);
        });

        Modal.modal();
    }

    function downloadContextData(data) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data));
        element.setAttribute('download', 'log.txt');
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    return {
        init: init,
        openContextDialog: openContextDialog,
    }
}(jQuery));
