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

let rowCount = $('#tabla-imputar tr').length - 1;
$('#agregarItem').click(function () {
    let rowOriginal = $('#rowOriginal');
    let html = '';
    rowCount ++;
        html += '<tr class="row-'+rowCount+'" data-row="'+ rowCount + '">' +
                    '<td>' +
                        '<select class="form-control predictivo" id="consumible-'+ rowCount +'">';
        html += rowOriginal.find('.selectConsumible').html();
        html += '       </select>' +
                    '</td>';

        html += '<td><input class="form-control" type="number" placeholder="Cantidad" id="cantidad-'+ rowCount +'"></td>';

        html += '<td><select class="form-control" id="mes-'+rowCount+'">';
        html += rowOriginal.find('.mes').html();
        html += '</select></td>';

        $('#tabla-imputar').append(html);


    //$('.predictivo').chosen();
});

$('#eliminarItem').click(function () {
    let rows = $('#tabla-imputar tr:not(#rowOriginal, .indicacionesMesAnterior)');
    if (rows.length > 0) {
        rowCount --;
        console.log(rowCount);
        rows.last().remove();
    }
});

$('#verIndMesAnt').click(function () {
    $('.indicacionesMesAnterior').show();
    $('#verIndMesAnt').hide();
    $('#ocultarIndMesAnt').show();
})

$('#ocultarIndMesAnt').click(function () {
    $('.indicacionesMesAnterior').hide();
    $('#verIndMesAnt').show();
    $('#ocultarIndMesAnt').hide();
})
