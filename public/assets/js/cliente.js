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
    birthday = new Date(birthday);
    var ageDifMs = Date.now() - birthday.getTime();
    var ageDate = new Date(ageDifMs); // miliseconds from epoch
    return Math.abs(ageDate.getUTCFullYear() - 1970);
}

$('.js-datepicker').datepicker({
    format: 'yyyy-mm-dd'
});

$('.editarPaciente').click(function () {
    window.location.href = $(this).data('url');
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

