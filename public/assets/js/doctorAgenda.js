$('#from').on('change', function () {
    $('#to').attr("min", $('#from').val());
    $('#to').val($('#from').val());
})
$('#buscarPorNombreYfechas').on('click', function () {
    let url = $(this).data('url');
    let from = $('#from').val();
    let to = $('#to').val();
    let nombreInput = $('#nombreInput').val();

    url = url + '?desde=' + from + '&hasta=' + to + '&nombreInput='+nombreInput;

    window.location.href = url;

})

$('#buscarPorNombre').click( function () {
    var nombreInput = $('#nombreInput').val();
    var url = $(this).data('url');
    var inactivo = $(this).data('inactivo');
    if (inactivo) {
        url += '&nombreInput=';
    } else {
        url += '?nombreInput=';
    }
    url += nombreInput;
    window.location.href = url;
});

$(".completado").on('change', function () {
    let url = $(this).data('url');
    let turnoId = $(this).data('turnoId');
    let check = ($(this).prop("checked")) ? 1 : 0;

    url += turnoId+'/'+check;
    window.location.href = url;
});