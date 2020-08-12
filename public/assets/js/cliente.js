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


$( document ).on('change', '#cliente_fNacimiento', function () {


   $('#cliente_edad').val(_calculateAge($(this).val()));
})

function _calculateAge(birthday) {
    birthday = new Date(birthday);
    var ageDifMs = Date.now() - birthday.getTime();
    var ageDate = new Date(ageDifMs); // miliseconds from epoch
    return Math.abs(ageDate.getUTCFullYear() - 1970);
}