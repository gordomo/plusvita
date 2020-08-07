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

$( document ).on('change', '#doctor_tipo, #doctor_modalidad', function () {
    if($('#doctor_tipo').val() === '1') {
        var values = [];
        $('input[type="checkbox"]:checked').each(function(i,v){
            values[i] = $(v).val();
        });

        if( values.includes("Enfermero/a") || values.includes("Auxiliar de enfermeria") || values.includes("Coordinador de enfermeria")) {
            $('.vtoMatricula').removeClass('d-none');
            $('.libretaSanitaria').addClass('d-none');
        } else {
            $('.vtoMatricula').addClass('d-none');
            $('.libretaSanitaria').removeClass('d-none');
        }
    } else {
        $('.vtoMatricula').removeClass('d-none');
        $('.libretaSanitaria').addClass('d-none');
    }
})
$(document).ready(function() {
    if($('#doctor_tipo').val() === '1') {
        var values = [];
        $('input[type="checkbox"]:checked').each(function(i,v){
            values[i] = $(v).val();
        });

        if( values.includes("Enfermero/a") || values.includes("Auxiliar de enfermeria") || values.includes("Coordinador de enfermeria")) {
            $('.vtoMatricula').removeClass('d-none');
            $('.libretaSanitaria').addClass('d-none');
        } else {
            $('.vtoMatricula').addClass('d-none');
            $('.libretaSanitaria').removeClass('d-none');
        }
    } else {
        $('.vtoMatricula').removeClass('d-none');
        $('.libretaSanitaria').addClass('d-none');
    }
});



if($('#doctor_modalidad').is(':visible')) {
    var parentModalidadHtml = '<div class="form-row"><div class="col-sm">';
    parentModalidadHtml += $('#doctor_modalidad').parent().html();
    parentModalidadHtml += '</div></div>'
    $('#doctor_modalidad').parent().html('');
    $('.js-staff-modalidad-target').html(parentModalidadHtml);
    $('#doctor_modalidad').find('.checkbox').each(function(index, value) {
        $(this).css('float', 'left');
        $(this).css('margin-right', '15px');
    });
}