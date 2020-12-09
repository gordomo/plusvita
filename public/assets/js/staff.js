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

    if (typeof (businessHoursJson) != 'undefined') {
        if (typeof (businessHoursJson.lunes) != 'undefined') {
            var lunesDesde = (typeof (businessHoursJson.lunes.desde) != 'undefined') ? businessHoursJson.lunes.desde : '';
            var lunesHasta = (typeof (businessHoursJson.lunes.hasta) != 'undefined') ? businessHoursJson.lunes.hasta : '';
            var lunesyDesde = (typeof (businessHoursJson.lunes.ydesde) != 'undefined' && businessHoursJson.lunes.ydesde != businessHoursJson.lunes.desde) ? businessHoursJson.lunes.ydesde : '';
            var lunesyHasta = (typeof (businessHoursJson.lunes.yhasta) != 'undefined' && businessHoursJson.lunes.yhasta != businessHoursJson.lunes.hasta) ? businessHoursJson.lunes.yhasta : '';
        }

        if (typeof (businessHoursJson.martes) != 'undefined') {
            var martesDesde = (typeof (businessHoursJson.martes.desde) != 'undefined') ? businessHoursJson.martes.desde : '';
            var martesHasta = (typeof (businessHoursJson.martes.hasta) != 'undefined') ? businessHoursJson.martes.hasta : '';
            var martesyDesde = (typeof (businessHoursJson.martes.ydesde) != 'undefined' && businessHoursJson.martes.ydesde != businessHoursJson.martes.desde) ? businessHoursJson.martes.ydesde : '';
            var martesyHasta = (typeof (businessHoursJson.martes.yhasta) != 'undefined' && businessHoursJson.martes.yhasta != businessHoursJson.martes.hasta) ? businessHoursJson.martes.yhasta : '';
        }

        if (typeof (businessHoursJson.miercoles) != 'undefined') {
            var miercolesDesde = (typeof (businessHoursJson.miercoles.desde) != 'undefined') ? businessHoursJson.miercoles.desde : '';
            var miercolesHasta = (typeof (businessHoursJson.miercoles.hasta) != 'undefined') ? businessHoursJson.miercoles.hasta : '';
            var miercolesyDesde = (typeof (businessHoursJson.miercoles.ydesde) != 'undefined' && businessHoursJson.miercoles.ydesde != businessHoursJson.miercoles.desde) ? businessHoursJson.miercoles.ydesde : '';
            var miercolesyHasta = (typeof (businessHoursJson.miercoles.yhasta) != 'undefined' && businessHoursJson.miercoles.yhasta != businessHoursJson.miercoles.hasta) ? businessHoursJson.miercoles.yhasta : '';
        }

        if (typeof (businessHoursJson.jueves.desde) != 'undefined') {
            var juevesDesde = (typeof (businessHoursJson.jueves.desde) != 'undefined') ? businessHoursJson.jueves.desde : '';
            var juevesHasta = (typeof (businessHoursJson.jueves.hasta) != 'undefined') ? businessHoursJson.jueves.hasta : '';
            var juevesyDesde = (typeof (businessHoursJson.jueves.ydesde) != 'undefined' && businessHoursJson.jueves.ydesde != businessHoursJson.jueves.desde) ? businessHoursJson.jueves.ydesde : '';
            var juevesyHasta = (typeof (businessHoursJson.jueves.yhasta) != 'undefined' && businessHoursJson.jueves.yhasta != businessHoursJson.jueves.hasta) ? businessHoursJson.jueves.yhasta : '';
        }

        if (typeof (businessHoursJson.viernes.desde) != 'undefined') {
            var viernesDesde = (typeof (businessHoursJson.viernes.desde) != 'undefined') ? businessHoursJson.viernes.desde : '';
            var viernesHasta = (typeof (businessHoursJson.viernes.hasta) != 'undefined') ? businessHoursJson.viernes.hasta : '';
            var viernesyDesde = (typeof (businessHoursJson.viernes.ydesde) != 'undefined' && businessHoursJson.viernes.ydesde != businessHoursJson.viernes.desde) ? businessHoursJson.viernes.ydesde : '';
            var viernesyHasta = (typeof (businessHoursJson.viernes.yhasta) != 'undefined' && businessHoursJson.viernes.yhasta != businessHoursJson.viernes.hasta) ? businessHoursJson.viernes.hasta : '';
        }

        if (typeof (businessHoursJson.sabado) != 'undefined') {
            var sabadoDesde = (typeof (businessHoursJson.sabado.desde) != 'undefined') ? businessHoursJson.sabado.desde : '';
            var sabadoHasta = (typeof (businessHoursJson.sabado.hasta) != 'undefined') ? businessHoursJson.sabado.hasta : '';
            var sabadoyDesde = (typeof (businessHoursJson.sabado.ydesde) != 'undefined' && businessHoursJson.sabado.ydesde != businessHoursJson.sabado.desde) ? businessHoursJson.sabado.ydesde : '';
            var sabadoyHasta = (typeof (businessHoursJson.sabado.yhasta) != 'undefined' && businessHoursJson.sabado.yhasta != businessHoursJson.sabado.hasta) ? businessHoursJson.sabado.yhasta : '';
        }


        $('#doctor_lunesdesde').val(lunesDesde);
        $('#doctor_luneshasta').val(lunesHasta);
        $('#doctor_yluneshasta').val(lunesyHasta);
        $('#doctor_ylunesdesde').val(lunesyDesde);

        $('#doctor_martesdesde').val(martesDesde);
        $('#doctor_marteshasta').val(martesHasta);
        $('#doctor_ymarteshasta').val(martesyHasta);
        $('#doctor_ymartesdesde').val(martesyDesde);

        $('#doctor_miercolesdesde').val(miercolesDesde);
        $('#doctor_miercoleshasta').val(miercolesHasta);
        $('#doctor_ymiercoleshasta').val(miercolesyHasta);
        $('#doctor_ymiercolesdesde').val(miercolesyDesde);

        $('#doctor_juevesdesde').val(juevesDesde);
        $('#doctor_jueveshasta').val(juevesHasta);
        $('#doctor_yjueveshasta').val(juevesyHasta);
        $('#doctor_yjuevesdesde').val(juevesyDesde);

        $('#doctor_viernesdesde').val(viernesDesde);
        $('#doctor_vierneshasta').val(viernesHasta);
        $('#doctor_yvierneshasta').val(viernesyHasta);
        $('#doctor_yviernesdesde').val(viernesyDesde);


        $('#doctor_sabadodesde').val(sabadoDesde);
        $('#doctor_sabadohasta').val(sabadoHasta);
        $('#doctor_ysabadohasta').val(sabadoyHasta);
        $('#doctor_ysabadodesde').val(sabadoyDesde);
    }



});

$('#doctor_email').on('keyup', function () {
    let emailField = $(this);
    let id = (emailField.data('doc-id')) ? emailField.data('doc-id') : 0;

    if(emailField.val().length > 4 && validateEmail(emailField.val())) {
        $.ajax({
            url: emailField.data('staff_check_email-url'),
            data: {
                email: emailField.val(),
                id: id,
            },
            success: function (response) {
                if(response.libre) {
                    emailField.removeClass('is-invalid');
                    emailField.next().hide();
                } else {
                    emailField.addClass('is-invalid');
                    emailField.next().html(response.message);
                    emailField.next().show();
                }
            }
        });
    } else {
        emailField.addClass('is-invalid');
        emailField.next().html('El email no es válido');
        emailField.next().show();
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

$('.js-datepicker').datepicker({
    format: 'yyyy-mm-dd'
});

$('.predictivo').chosen();
$( "#buscarContrato" ).on('click', function(){
    location.href = '?ctr=' + $('#contrato').val();
});

$( "#limpiarContratos" ).on('click', function(){
    location.href = '?ctr=';
});