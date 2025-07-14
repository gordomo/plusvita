// User admin specific JavaScript
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Make sure all columns are shown by default
    $('.check').prop('checked', true);
    $('#todos').prop('checked', true);
    
    // Force column visibility on page load
    $('.id, .user, .roles, .email, .telefono').show();
    
    // Ensure toggleColumns is applied on page load
    toggleColumns();
    
    // Handle column toggling
    function toggleColumns() {
        $('.check').each(function() {
            var column = $(this).val();
            if ($(this).is(':checked')) {
                $('.' + column).show();
            } else {
                $('.' + column).hide();
            }
        });
    }
    
    // Handle printing functionality
    $('#imprimir').on('click', function() {
        $('.filtrosPlanilla').modal('show');
    });
    
    // Highlight row on hover
    $('.table tbody tr').hover(
        function() {
            $(this).addClass('row-highlight');
        }, 
        function() {
            $(this).removeClass('row-highlight');
        }
    );
    
    // Handle delete confirmation
    $('form[onsubmit]').on('submit', function(e) {
        if (!confirm('¿Está seguro que desea eliminar este usuario?')) {
            e.preventDefault();
        }
    });
});
