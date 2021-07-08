var cantidadActual = 0;

/*$('#consumible').change(function () {
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
});*/

/*$('#cantidad').on('keyup', function () {
    if ($('#consumible').find(':selected').val() === '0') {
        alert('primero seleccione un consumible');
        $('#cantidad').val('');
    } else {
        if ($('#cantidad').val() > cantidadActual) {
            alert('La cantidad a imputar no puede ser mayor a la cantidad total de existencia del consumible');
            $('#cantidad').val(cantidadActual);
        }
    }

});*/

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

$('#buscar').click(function () {
    let tipo = $("#tipo").val();
    let mes = $("#mes").val();
    let imputacion = $("#imputacion").val();
    let url = $(this).data('url');

    url += "&tipoSeleccionado=" + tipo + "&mes=" + mes + "&imputacion=" + imputacion;
    location.href = url;
});

let agrega = document.getElementById('agrega');
if (agrega !== null) {
    agrega.addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            $('.desdeHasta').hide();
        }
    })
}
let consume = document.getElementById('consume');
if (consume !== null) {
    consume.addEventListener('change', (event) => {
        if (event.currentTarget.checked) {
            $('.desdeHasta').show();
        }
    })
}

let rowCount = $('#rowContainer').find('.row').length - 2;
$('#agregarItem').click(function () {
    let html = '';
    rowCount ++;
    console.log(rowCount);
    $('#rowOriginal div:not(.noAgregar)').each(function() {
        html += ' <div class="col-sm">' + $(this).html() + '</div>';
    })
    html += ' <div class="col-sm">';

    html += '<div className="col-sm noAgregar" style="padding-top: 5px;"><label class="checkcontainer red" style="display: inline-block; margin-right: 15px">Indicación<input type="radio" checked name="accion-'+rowCount+'" required value="0"><span class="checkmark"></span></label><label class="checkcontainer green" style="display: inline-block" value="1">Ingresa<input type="radio" name="accion-'+rowCount+'"><span class="checkmark"></span></label></div>'

    html += ' </div><div class="col-sm"></div>';

    let newDiv = '<div class="row">' +  html  + '</div>';
    $('#rowContainer').append(newDiv);
});

$('#eliminarItem').click(function () {
    if ($('#rowContainer .row:not(#rowOriginal)').length > 1) {
        rowCount --;
        console.log(rowCount);
        $('#rowContainer .row:not(#rowOriginal)').last().remove();
    }
});