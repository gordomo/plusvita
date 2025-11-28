/**
 * Kardex System for Medical Indications
 */
// Variable global para evitar que se ejecuten filtros automáticos
var disableAutoFilters = true;

$(document).ready(function() {
    console.log("DOM listo, iniciando kardex...");
    
    // Forzar mostrar todas las categorías y elementos al inicio
    $('.kardex-category-content').show();
    $('.kardex-item').show();
    
    // Establecer los filtros en 'all' antes de inicializar
    $('#kardex-filter-year').val('all');
    $('#kardex-filter-month').val('all');
    if ($('#kardex-filter-category').length) {
        $('#kardex-filter-category').val('all');
    }
    
    // Inicializar filtros y elementos del Kardex después de un pequeño retraso
    setTimeout(function() {
        initKardex();
        console.log("Kardex inicializado con retraso");
    }, 200);
    
    // Manejar cambio de filtro por año
    $('#kardex-filter-year').on('change', function() {
        disableAutoFilters = false; // Ahora el usuario está filtrando explícitamente
        filterKardexItems();
    });
    
    // Manejar cambio de filtro por mes
    $('#kardex-filter-month').on('change', function() {
        disableAutoFilters = false;
        filterKardexItems();
    });
    
    // Manejar cambio de filtro por categoría
    $('#kardex-filter-category').on('change', function() {
        disableAutoFilters = false;
        filterKardexItems();
    });
    
    // Botón para limpiar filtros
    $('#kardex-clear-filters').on('click', function() {
        $('#kardex-filter-year').val('all');
        $('#kardex-filter-month').val('all');
        $('#kardex-filter-category').val('all');
        disableAutoFilters = true;
        filterKardexItems();
    });
    
    // Toggle para las categorías
    $(document).on('click', '.kardex-category-toggle', function() {
        $(this).toggleClass('collapsed');
        let content = $(this).next('.kardex-category-content');
        
        // Eliminar cualquier estilo inline antes de hacer el toggle
        if (content.is(':visible')) {
            content.slideUp();
        } else {
            // Primero eliminar cualquier estilo inline
            content.attr('style', '').show();
            // Luego animar
            content.hide().slideDown();
        }
    });
    
    // Mostramos todas las categorías al inicio de nuevo
    $('.kardex-category-content').attr('style', '').show();
    $('.kardex-item').attr('style', '').show();
    $('.kardex-empty').hide();
    
    console.log("Kardex inicializado correctamente - Mostrando todas las indicaciones");
});

/**
 * Inicializar el sistema de Kardex
 */
function initKardex() {
    console.log("Inicializando el sistema de Kardex");
    
    // Asegurarnos de que los filtros estén en 'all' al inicio
    $('#kardex-filter-year').val('all');
    $('#kardex-filter-month').val('all');
    if ($('#kardex-filter-category').length) {
        $('#kardex-filter-category').val('all');
    }
    
    // Actualizar contador de indicaciones
    updateIndicationCounter();
    
    // Mostrar todas las indicaciones al inicio
    // Usamos setTimeout para asegurar que se aplique después de que el DOM esté listo
    setTimeout(function() {
        // Eliminar cualquier estilo inline que oculte elementos
        $('.kardex-category-content').attr('style', '').css('display', 'block');
        $('.kardex-item').attr('style', '').show();
        
        // Asegurarnos de que todas las categorías estén desplegadas
        $('.kardex-category-toggle').removeClass('collapsed');
        
        // Filtrar para asegurarnos que se apliquen correctamente los filtros (que están en 'all')
        filterKardexItems();
        console.log("Inicialización completada - Se han mostrado todas las indicaciones");
    }, 100);
}

/**
 * Filtrar indicaciones según los criterios seleccionados
 */
function filterKardexItems() {
    const selectedYear = $('#kardex-filter-year').val();
    const selectedMonth = $('#kardex-filter-month').val();
    const selectedCategory = $('#kardex-filter-category').val();
    
    console.log("Filtrando indicaciones - Año: " + selectedYear + ", Mes: " + selectedMonth + ", Categoría: " + selectedCategory + ", autoFilters: " + (disableAutoFilters ? "deshabilitados" : "habilitados"));
    
    let totalItems = 0;
    let visibleItems = 0;
    
    // Si los autoFiltros están deshabilitados y todos los filtros están en 'all', mostramos todo directamente
    if (disableAutoFilters && selectedYear === 'all' && selectedMonth === 'all' && 
        (selectedCategory === 'all' || selectedCategory === undefined)) {
        console.log("Auto-filtros deshabilitados y filtros en 'all' - Mostrando todas las indicaciones");
        $('.kardex-item').attr('style', '').show();
        // Forzamos mostrar categorías desplegadas
        $('.kardex-category-content').attr('style', '').show();
        updateIndicationCounter();
        $('.kardex-empty').hide();
        return;
    }
    
    $('.kardex-item').each(function() {
        const itemYear = $(this).data('year');
        const itemMonth = $(this).data('month');
        const itemCategory = $(this).data('category');
        
        totalItems++;
        let showItem = true;
        
        // Mostrar todo si todos los filtros están en "all"
        if (selectedYear === 'all' && selectedMonth === 'all' && 
            (selectedCategory === 'all' || selectedCategory === undefined)) {
            showItem = true;
        } else {
            // Aplicar filtro de año
            if (selectedYear !== 'all' && itemYear != selectedYear) {
                showItem = false;
            }
            
            // Aplicar filtro de mes
            if (selectedMonth !== 'all' && itemMonth != selectedMonth) {
                showItem = false;
            }
            
            // Aplicar filtro de categoría
            if (selectedCategory !== 'all' && selectedCategory !== undefined && itemCategory != selectedCategory) {
                showItem = false;
            }
        }
        
        // Mostrar u ocultar el item
        if (showItem) {
            $(this).attr('style', '').show();
            visibleItems++;
            console.log("Mostrando item - Año: " + itemYear + ", Mes: " + itemMonth + ", Categoría: " + itemCategory);
        } else {
            $(this).hide();
        }
    });
    
    console.log("Total items: " + totalItems + ", Items visibles: " + visibleItems);
    
    updateIndicationCounter();
    checkEmptyState();
}

/**
 * Actualizar el contador de indicaciones visibles
 */
function updateIndicationCounter() {
    const visibleItems = $('.kardex-item:visible').length;
    const totalItems = $('.kardex-item').length;
    
    console.log("Actualizando contador: " + visibleItems + " / " + totalItems + " indicaciones");
    
    $('#kardex-counter').text(visibleItems);
    $('#kardex-total-counter').text(totalItems);
    
    // Actualizar también la clase CSS según si hay filtros aplicados
    if (visibleItems < totalItems) {
        $('.kardex-badge').addClass('filtered');
    } else {
        $('.kardex-badge').removeClass('filtered');
    }
}

/**
 * Verificar si no hay indicaciones para mostrar mensaje vacío
 */
function checkEmptyState() {
    console.log("Verificando estado vacío - Indicaciones visibles: " + $('.kardex-item:visible').length);
    
    if ($('.kardex-item:visible').length === 0) {
        console.log("No hay indicaciones visibles, mostrando mensaje vacío");
        
        // No creamos un nuevo elemento, usamos el que ya está en el HTML
        $('.kardex-empty').show();
    } else {
        console.log("Hay indicaciones visibles, ocultando mensaje vacío");
        $('.kardex-empty').hide();
    }
}
