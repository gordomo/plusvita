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

$( document ).on('click', '#agregarFamiliar', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var botonQuitarHtml = '<div class="col-sm" id="quitarFamiliarRow"><a href="" id="quitarFamiliar">quitar familiar - </a></div>';
    var htmlNuevoFamiliar = '<div class="form-row"><div class="col-sm"><input type="text" name="familiarResponsableExtraNombre[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraTel[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraMail[]" class="form-control"></div><div class="col-sm"><input type="text" name="familiarResponsableExtraVinculo[]" class="form-control"></div></div>';
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