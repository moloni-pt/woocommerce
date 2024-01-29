if (Moloni === undefined) {
    var Moloni = {};
}

if (Moloni.Login === undefined) {
    Moloni.Login = {};
}

Moloni.Login = (function($) {
    function init() {
        startObservers();
    }

    function startObservers() {
        var loginBtn = $('#login_button');
        var emailInput = $('#username');
        var passwordInput = $('#password');

        if (!loginBtn.length || !emailInput.length || !passwordInput.length) {
            return;
        }

        emailInput.add(passwordInput).on('keyup', function () {
            if (emailInput.val() === '' || passwordInput.val() === '') {
                loginBtn.prop('disabled', true);
            } else {
                loginBtn.removeAttr("disabled");
            }
        });
    }

    return {
        init: init,
    }
}(jQuery));
