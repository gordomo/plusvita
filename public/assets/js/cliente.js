$( document ).on('change', '.js-cliente-motivo', function () {
    var $motivoSelected = $('.js-cliente-motivo');
    var $motivoTarget = $('.js-cliente-motivo-target');

    $.ajax({
        url: $motivoSelected.data('cliente-motivo_ingreso-select-url'),
        data: {
            motivoIng: $motivoSelected.val()
        },
        success: function (html) {
            if (!html) {
                $motivoTarget.find('select').remove();
                $motivoTarget.addClass('d-none');
                return;
            }
            // Replace the current field and show
            $motivoTarget
                .html(html)
                .removeClass('d-none')
        }
    });
})
$('.predictivo').chosen();

$( document ).on('change', '.js-camas-disp', function () {
    var numeroHabitacion = $(this).val();
    var $camas = $('.camas');
    var url = '/habitacion/cama/disp/' + numeroHabitacion;

    var actualUrl = window.location.href.split('/');

    if (!isNaN(actualUrl[4])) {
        url += '/'+actualUrl[4];
    }

    if (numeroHabitacion) {
        $.ajax({
            url: url,
            success: function (html) {
                if (!html) {
                    $('.dinamicHabitaciones').remove();
                }
                // Replace the current field and show
                $('.habitacion').find('.placeHolder').remove();
                $('.dinamicHabitaciones').remove();
                $('.habDiv').after(html);
            }
        });
    } else {
        $('.dinamicHabitaciones').remove();
        $('.habitacion').find('.placeHolder').removeClass('d-none');
    }
})

$( document ).on('click', '#agregarFamiliar', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var botonQuitarHtml = '<div class="col-sm" id="quitarFamiliarRow"><a href="" id="quitarFamiliar">quitar familiar - </a></div>';
    var htmlNuevoFamiliar = '<br><div class="form-row"><div class="col-sm"><input type="text" name="familiarResponsableExtraNombre[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraTel[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraMail[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraVinculo[]" class="form-control"></div><div class="col-sm"><div class="columnas-de-uno"><select name="familiarResponsableExtraAcompanante[]" class="form-control"><option value="1">Si</option><option value="0" selected="selected">No</option></select></div></div></div>';
    $('#familiares').append(htmlNuevoFamiliar);
    if($('#familiares').find('.form-row').length === 2) {
        $('#agregarQuitarFamiliar').append(botonQuitarHtml)
    }
});

$( document ).on('click', '#quitarFamiliar', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $('#familiares .form-row').last().remove();
    if($('#familiares').find('.form-row').length === 1) {
        $('#quitarFamiliarRow').remove();
    }
});

$( document ).on('change', '#cliente_fNacimiento', function () {
    $('#cliente_edad').val(_calculateAge($(this).val()));
})

$( document ).on('change', '#cliente_modalidad', function () {
    if($(this).val() == 2) {
        $('.habitacion').removeClass('d-none');
    } else {
        $('.habitacion').addClass('d-none');
    }
})

function _calculateAge(birthday) {
    const dateParts = birthday.split('-'); // split the string by '/'
    const day = parseInt(dateParts[2]); // extract the day as an integer
    const month = parseInt(dateParts[1]) - 1; // extract the month as an integer (subtract 1 as January is month 0)
    const year = parseInt(dateParts[0]); // extract the year as an integer

    birthday = new Date(year, month, day)
    var ageDifMs = Date.now() - birthday.getTime();
    var ageDate = new Date(ageDifMs); // miliseconds from epoch
    return Math.abs(ageDate.getUTCFullYear() - 1970);
}

$('.editarPacienteInner').click(function () {
    window.location.href = $(this).data('url');
});

$('#reingreso_disponibleParaTerapia').change(function () {

    $('.terapias-no-habilitadas').toggle();
});

$( document ).ready(function () {
    if($('#familiares').find('.form-row').length > 1) {
        var botonQuitarHtml = '<div class="col-sm" id="quitarFamiliarRow"><a href="" id="quitarFamiliar">quitar familiar - </a></div>';
        $('#agregarQuitarFamiliar').append(botonQuitarHtml);
    }

    if ($('#cliente_modalidad').val() == '2') {
        $('.habitacion').removeClass('d-none');
        var numeroHabitacion = $('.habitacion').find('select').val();
        var $camas = $('.camas');
        if(numeroHabitacion != 0) {
            $camas.removeClass('d-none')
        }
    }

    $('[data-toggle="tooltip"]').tooltip();

    $('#cliente_familiarResponsableAcompanante').find('.radio').addClass('form-check form-check-inline');

});

$('#cliente_email').on('keyup', function () {
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

$('#cliente_dni').on('keyup', function () {
    let dniField = $(this);
    let id = (dniField.data('doc-id')) ? dniField.data('doc-id') : 0;

    if(dniField.val().length > 6 ) {
        $.ajax({
            url: dniField.data('staff_check_dni-url'),
            data: {
                dni: dniField.val(),
                id: id,
            },
            success: function (response) {
                if(response.libre) {
                    dniField.removeClass('is-invalid');
                    dniField.next().hide();
                } else {
                    dniField.addClass('is-invalid');
                    dniField.next().html(response.message);
                    dniField.next().show();
                }
            }
        });
    } else {
        dniField.addClass('is-invalid');
        dniField.next().html('El dni no es válido');
        dniField.next().show();
    }
});

$('#cliente_hClinica').on('keyup', function () {
    let hcField = $(this);
    let id = (hcField.data('cliente-id')) ? hcField.data('cliente-id') : 0;
    if(hcField.val().length > 0) {
        $.ajax({
            url: hcField.data('cliente_check_hc-url'),
            data: {
                hc: hcField.val(),
                id: id,
            },
            success: function (response) {
                if(response.libre) {
                    hcField.removeClass('is-invalid');
                    hcField.next().hide();
                } else {
                    hcField.addClass('is-invalid');
                    hcField.next().html(response.message);
                    hcField.next().show();
                }
            }
        });
    } else {
        hcField.addClass('is-invalid');
        hcField.next().html('Ingrese un número de historia clínica');
        hcField.next().show();
    }
});