if (Moloni === undefined) {
    var Moloni = {};
}

Moloni.Logs = (function($) {
    var Modal;
    var logs = {};

    function init(_logs) {
        logs = _logs;

        startObservers();
    }

    function startObservers()
    {
        Modal = $("#logs-context-modal");

        $('.log_button').on('click', function () {
            var id = $(this).data('logId');

            if (!isNaN(id) && id > 0) {
                openContextDialog(id);
            }
        });
    }

    function openContextDialog(id) {
        var context = (id in logs ? logs[id] : '{}');

        try {
            var data = JSON.parse(context);

            context = JSON.stringify(data || {}, null, 2);
        } catch (e) {}

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
    }
}(jQuery));
