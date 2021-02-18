var cantidadActual = 0;

$('#consumible').change(function () {
    let unidades = $(this).find(':selected').data('unidades');
    let consumibleId = $(this).find(':selected').val();
    let url = "/consumible/check/existencias?id="+consumibleId;
    if (consumibleId !== "0") {
        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.existencia) {
                    cantidadActual = response.existencia;
                    $('#unidades').html(' ' + response.existencia + ' ' + unidades);
                }
            }
        });
    } else {
        $('#unidades').html('');
    }
});

$('#cantidad').on('keyup', function () {
    if ($('#consumible').find(':selected').val() === '0') {
        alert('primero seleccione un consumible');
        $('#cantidad').val('');
    } else {
        if ($('#cantidad').val() > cantidadActual) {
            alert('La cantidad a imputar no puede ser mayor a la cantidad total de existencia del consumible');
            $('#cantidad').val(cantidadActual);
        }
    }

});

$(document).on('change', 'input[type=radio][name=agregarQuitar]', function (event) {
    switch($(this).val()) {
        case 'agregar' :
            $('#aNombreDe').parent().show();
            break;
        case 'quitar' :
            $('#aNombreDe').parent().hide();
            $('.clientes').hide();
            $('#aNombreDe').prop('checked', false);
            break;
    }
});

$('#aNombreDe').on('change', function () {
    if ( $(this).prop('checked') ) {
        $('#cliente').parent().show();
        $('#cliente').attr('required', true);
    } else {
        $('#cliente').parent().hide();
        $('#cliente').attr('required', false);
    }
});
$('.predictivo').chosen();
$( document ).ready(function () {
   $('.clientes').hide();
});
