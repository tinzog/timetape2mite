$( document ).ready(function() {

    //PopUp for delete confirm
    $('.deleteAccount').each(function() {
        $(this).attr('onClick', 'return confirm("Soll der Account wirklich gelöscht werden?"); submit();');
    });

    //Validation with parsley.js
    window.ParsleyConfig = {
        errorsWrapper: '<div class="parsley-errors-list"></div>',
        errorTemplate: '<div class="alert alert-danger" role="alert">...</div>'
    };

    //Validation of new user
    $('#newUser').parsley();
    $('#newUser').attr("data-parsley-ui-enabled", true);
    $('#newUser #identifier').attr("required", true);
    $('#newUser #identifier').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#newUser #identifier').attr("data-parsley-length", "[3, 255]");
    $('#newUser #identifier').attr("data-parsley-length-message",
        "Der Benuztername muss mindestens 3 Zeichen lang sein.");

    $('#newUser #password\\[0\\]').attr("required", true);
    $('#newUser #password\\[0\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#newUser #password\\[0\\]').attr("data-parsley-length", "[6, 50]");
    $('#newUser #password\\[0\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");
    $('#newUser #password\\[1\\]').attr("required", true);
    $('#newUser #password\\[1\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#newUser #password\\[1\\]').attr("data-parsley-trigger", "keyup");
    $('#newUser #password\\[1\\]').attr("data-parsley-equalto", "#newUser #password\\[0\\]");
    $('#newUser #password\\[1\\]').attr("data-parsley-equalto-message",
        "Die beiden Passwörter stimmen nicht überein.");
    $('#newUser #password\\[1\\]').attr("data-parsley-length", "[6, 50]");
    $('#newUser #password\\[1\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");

    $('#newUser #firstName').attr("required", true);
    $('#newUser #firstName').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#newUser #lastName').attr("required", true);
    $('#newUser #lastName').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");

    //Validation of Own Password changes

    $('#updateOwnPassword').parsley();
    $('#updateOwnPassword').attr("data-parsley-ui-enabled", true);

    $('#updateOwnPassword #password\\[0\\]').attr("required", true);
    $('#updateOwnPassword #password\\[0\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#updateOwnPassword #password\\[0\\]').attr("data-parsley-length", "[6, 50]");
    $('#updateOwnPassword #password\\[0\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");
    $('#updateOwnPassword #password\\[1\\]').attr("required", true);
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-trigger", "keyup");
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-equalto", "#updateOwnPassword #password\\[0\\]");
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-equalto-message",
        "Die beiden Passwörter stimmen nicht überein.");
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-length", "[6, 50]");
    $('#updateOwnPassword #password\\[1\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");

    //Validation of Password changes

    $('#updatePassword').parsley();
    $('#updatePassword').attr("data-parsley-ui-enabled", true);

    $('#updatePassword #password\\[0\\]').attr("required", true);
    $('#updatePassword #password\\[0\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#updatePassword #password\\[0\\]').attr("data-parsley-length", "[6, 50]");
    $('#updatePassword #password\\[0\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");
    $('#updatePassword #password\\[1\\]').attr("required", true);
    $('#updatePassword #password\\[1\\]').attr("data-parsley-required-message", "Bitte füllen Sie dieses Feld aus.");
    $('#updatePassword #password\\[1\\]').attr("data-parsley-trigger", "keyup");
    $('#updatePassword #password\\[1\\]').attr("data-parsley-equalto", "#updatePassword #password\\[0\\]");
    $('#updatePassword #password\\[1\\]').attr("data-parsley-equalto-message",
        "Die beiden Passwörter stimmen nicht überein.");
    $('#updatePassword #password\\[1\\]').attr("data-parsley-length", "[6, 50]");
    $('#updatePassword #password\\[1\\]').attr("data-parsley-length-message",
        "Das Passwort muss mindestens 6 Zeichen lang sein.");

});