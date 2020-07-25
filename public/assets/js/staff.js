$( document ).on('change', '#form_modalidad', function () {
    var e = document.getElementById("form_modalidad");
    var modalidad = e.options[e.selectedIndex].value;
    if(modalidad == 2) {
        $('#form_especialidad').parent().parent().removeClass('d-none');
        $('#form_vtoMatricula').parent().removeClass('d-none');
    } else {
        $('#form_especialidad').parent().parent().addClass('d-none');
        $('#form_vtoMatricula').parent().addClass('d-none');
        $('#form_especialidad :checkbox').prop('checked',false);
        $('#form_vtoMatricula').val('');
    }
})

$('#form_especialidad').find('.checkbox').each(function(index, value) {
    $(this).css('float', 'left');
    $(this).css('margin-right', '15px');
});