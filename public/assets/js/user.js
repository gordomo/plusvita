$('#form_email').on('keyup', function () {
    let emailField = $(this);
    let id = (emailField.data('doc-id')) ? emailField.data('doc-id') : 0;
    if(emailField.val().length > 4 && validateEmail(emailField.val())) {
        $.ajax({
            url: emailField.data('staff_check_email-url'),
            data: {
                email: emailField.val(),
                id: id,
            },
            success: function (response) {
                if(response.libre) {
                    emailField.removeClass('is-invalid');
                    emailField.next().hide();
                } else {
                    emailField.addClass('is-invalid');
                    emailField.next().html(response.message);
                    emailField.next().show();
                }
            }
        });
    } else {
        emailField.addClass('is-invalid');
        emailField.next().html('El email no es válido');
        emailField.next().show();
    }
});

document.addEventListener("DOMContentLoaded", function() {
    let emailField = $('#form_email');
    let id = (emailField.data('doc-id')) ? emailField.data('doc-id') : 0;
    if(emailField.val().length > 4 && validateEmail(emailField.val())) {
        $.ajax({
            url: emailField.data('staff_check_email-url'),
            data: {
                email: emailField.val(),
                id: id,
            },
            success: function (response) {
                if(response.libre) {
                    emailField.removeClass('is-invalid');
                    emailField.next().hide();
                } else {
                    emailField.addClass('is-invalid');
                    emailField.next().html(response.message);
                    emailField.next().show();
                }
            }
        });
    } else {
        emailField.addClass('is-invalid');
        emailField.next().show();
    }
})
