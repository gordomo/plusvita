// Validación para asegurar que se seleccione una cama
$(document).ready(function() {
    // Remover la opción "sin cama" del dropdown cada vez que se cargue
    function removerOpcionSinCama() {
        $('select.required-cama option').each(function() {
            // Eliminar la opción con valor 0 o texto "sin cama"
            if ($(this).val() === '0' || $(this).text().toLowerCase() === 'sin cama') {
                $(this).remove();
            }
        });
    }
    
    // Ejecutar al cargar la página y cada vez que se actualice el dropdown por AJAX
    removerOpcionSinCama();
    $(document).ajaxComplete(function() {
        removerOpcionSinCama();
    });
    
    // Agregar validación al formulario
    $('#cliente_save').on('click', function(event) {
        // Verificar si hay un campo de cama en el formulario
        const campoNCama = $('.required-cama');
        if (campoNCama.length > 0) {
            // Verificar que se haya seleccionado un valor válido (no vacío y no "sin cama")
            if (!campoNCama.val() || campoNCama.val() === '' || campoNCama.val() === '0') {
                // Mostrar mensaje de error
                campoNCama.addClass('is-invalid');
                event.preventDefault();
                return false;
            } else {
                campoNCama.removeClass('is-invalid');
            }
        }
    });

    // Eliminar mensaje de error al seleccionar una cama
    $(document).on('change', '.required-cama', function() {
        if ($(this).val() && $(this).val() !== '' && $(this).val() !== '0') {
            $(this).removeClass('is-invalid');
        } else {
            $(this).addClass('is-invalid');
        }
    });
});
