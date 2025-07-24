/**
 * Cardex System for Medical Indications
 */
// Variable global para evitar que se ejecuten filtros automáticos
var disableAutoFilters = true;

$(document).ready(function() {
    console.log("DOM listo, iniciando cardex...");
    
    // Forzar mostrar todas las categorías y elementos al inicio
    $('.cardex-category-content').show();
    $('.cardex-item').show();
    
    // Establecer los filtros en 'all' antes de inicializar
    $('#cardex-filter-year').val('all');
    $('#cardex-filter-month').val('all');
    if ($('#cardex-filter-category').length) {
        $('#cardex-filter-category').val('all');
    }
    
    // Inicializar filtros y elementos del Cardex después de un pequeño retraso
    setTimeout(function() {
        initCardex();
        console.log("Cardex inicializado con retraso");
    }, 200);
    
    // Manejar cambio de filtro por año
    $('#cardex-filter-year').on('change', function() {
        disableAutoFilters = false; // Ahora el usuario está filtrando explícitamente
        filterCardexItems();
    });
    
    // Manejar cambio de filtro por mes
    $('#cardex-filter-month').on('change', function() {
        disableAutoFilters = false;
        filterCardexItems();
    });
    
    // Manejar cambio de filtro por categoría
    $('#cardex-filter-category').on('change', function() {
        disableAutoFilters = false;
        filterCardexItems();
    });
    
    // Botón para limpiar filtros
    $('#cardex-clear-filters').on('click', function() {
        $('#cardex-filter-year').val('all');
        $('#cardex-filter-month').val('all');
        $('#cardex-filter-category').val('all');
        disableAutoFilters = true;
        filterCardexItems();
    });
    
    // Toggle para las categorías
    $(document).on('click', '.cardex-category-toggle', function() {
        $(this).toggleClass('collapsed');
        let content = $(this).next('.cardex-category-content');
        
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
    $('.cardex-category-content').attr('style', '').show();
    $('.cardex-item').attr('style', '').show();
    $('.cardex-empty').hide();
    
    console.log("Cardex inicializado correctamente - Mostrando todas las indicaciones");
});

/**
 * Inicializar el sistema de Cardex
 */
function initCardex() {
    console.log("Inicializando el sistema de Cardex");
    
    // Asegurarnos de que los filtros estén en 'all' al inicio
    $('#cardex-filter-year').val('all');
    $('#cardex-filter-month').val('all');
    if ($('#cardex-filter-category').length) {
        $('#cardex-filter-category').val('all');
    }
    
    // Actualizar contador de indicaciones
    updateIndicationCounter();
    
    // Mostrar todas las indicaciones al inicio
    // Usamos setTimeout para asegurar que se aplique después de que el DOM esté listo
    setTimeout(function() {
        // Eliminar cualquier estilo inline que oculte elementos
        $('.cardex-category-content').attr('style', '').css('display', 'block');
        $('.cardex-item').attr('style', '').show();
        
        // Asegurarnos de que todas las categorías estén desplegadas
        $('.cardex-category-toggle').removeClass('collapsed');
        
        // Filtrar para asegurarnos que se apliquen correctamente los filtros (que están en 'all')
        filterCardexItems();
        console.log("Inicialización completada - Se han mostrado todas las indicaciones");
    }, 100);
}

/**
 * Filtrar indicaciones según los criterios seleccionados
 */
function filterCardexItems() {
    const selectedYear = $('#cardex-filter-year').val();
    const selectedMonth = $('#cardex-filter-month').val();
    const selectedCategory = $('#cardex-filter-category').val();
    
    console.log("Filtrando indicaciones - Año: " + selectedYear + ", Mes: " + selectedMonth + ", Categoría: " + selectedCategory + ", autoFilters: " + (disableAutoFilters ? "deshabilitados" : "habilitados"));
    
    let totalItems = 0;
    let visibleItems = 0;
    
    // Si los autoFiltros están deshabilitados y todos los filtros están en 'all', mostramos todo directamente
    if (disableAutoFilters && selectedYear === 'all' && selectedMonth === 'all' && 
        (selectedCategory === 'all' || selectedCategory === undefined)) {
        console.log("Auto-filtros deshabilitados y filtros en 'all' - Mostrando todas las indicaciones");
        $('.cardex-item').attr('style', '').show();
        // Forzamos mostrar categorías desplegadas
        $('.cardex-category-content').attr('style', '').show();
        updateIndicationCounter();
        $('.cardex-empty').hide();
        return;
    }
    
    $('.cardex-item').each(function() {
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
    const visibleItems = $('.cardex-item:visible').length;
    const totalItems = $('.cardex-item').length;
    
    console.log("Actualizando contador: " + visibleItems + " / " + totalItems + " indicaciones");
    
    $('#cardex-counter').text(visibleItems);
    $('#cardex-total-counter').text(totalItems);
    
    // Actualizar también la clase CSS según si hay filtros aplicados
    if (visibleItems < totalItems) {
        $('.cardex-badge').addClass('filtered');
    } else {
        $('.cardex-badge').removeClass('filtered');
    }
}

/**
 * Verificar si no hay indicaciones para mostrar mensaje vacío
 */
function checkEmptyState() {
    console.log("Verificando estado vacío - Indicaciones visibles: " + $('.cardex-item:visible').length);
    
    if ($('.cardex-item:visible').length === 0) {
        console.log("No hay indicaciones visibles, mostrando mensaje vacío");
        
        // No creamos un nuevo elemento, usamos el que ya está en el HTML
        $('.cardex-empty').show();
    } else {
        console.log("Hay indicaciones visibles, ocultando mensaje vacío");
        $('.cardex-empty').hide();
    }
}
