$( document ).on('change', '.js-staff-tipo', function () {
    var $tipoSelect = $('.js-staff-tipo');
    var $modalidadTarget = $('.js-staff-modalidad-target');

        $.ajax({
            url: $tipoSelect.data('staff_tipo_select-url'),
            data: {
                tipo: $tipoSelect.val()
            },
            success: function (html) {
                if (!html) {
                    $modalidadTarget.find('select').remove();
                    $modalidadTarget.addClass('d-none');
                    return;
                }
                // Replace the current field and show
                $modalidadTarget
                    .html(html)
                    .removeClass('d-none')
                $('#doctor_modalidad').find('.checkbox').each(function(index, value) {
                    $(this).css('float', 'left');
                    $(this).css('margin-right', '15px');
                });
            }
        });
})
if($('#doctor_modalidad').is(':visible')) {
    var parentModalidadHtml = $('#doctor_modalidad').parent().html();
    $('#doctor_modalidad').parent().html('');
    $('.js-staff-modalidad-target').html(parentModalidadHtml);
    $('#doctor_modalidad').find('.checkbox').each(function(index, value) {
        $(this).css('float', 'left');
        $(this).css('margin-right', '15px');
    });
}