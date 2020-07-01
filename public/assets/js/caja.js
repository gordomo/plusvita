$('#buscar').click(function () {
    var desc = $('#desc').val();
    var url = $(this).data('url');
    var desde = '';
    var hasta = '';

    $.each($('.input-daterange input'), function(e) {
        if($(this).datepicker('getDate') != null) {
            var date = $(this).datepicker('getDate').toString();
            var n = date.indexOf("00:00:00");
            if(e == 0) {
                desde = date.slice(0, n-1);
            } else {
                hasta = date.slice(0, n-1);
            }
        }
    })

    if (desc != '' || desde != '' || hasta != '') {
        location.href = url + '?buscar=' + desc + '&desde=' + desde + '&hasta=' + hasta;
    }
});

$('#limpiar').click(function () {
    var url = $(this).data('url');
    location.href = url;
});

$('#caja_ingreso, #caja_egreso').on('focus', function(){
    $(this).val('')
})

$('#caja_ingreso, #caja_egreso').on('focusout', function(){
    if($(this).val() == '') {
        $(this).val(0);
    }
});

$('.input-daterange input').each(function() {
    $(this).datepicker('update');
});