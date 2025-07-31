/**
 * Maneja la lógica de los formularios de indicaciones médicas
 */

// Función para mostrar u ocultar campos según el tipo de indicación seleccionado
function mostrarCamposRelevantes(rowId) {
    const tipoIndicacion = $(`#tipoIndicacion-${rowId}`).val();
    const viaAdminContainer = $(`#viaAdministracionContainer-${rowId}`);
    const procPersonalizadoContainer = $(`#procedimientoPersonalizadoContainer-${rowId}`);
    
    // Ocultar todos los contenedores específicos primero
    viaAdminContainer.hide();
    procPersonalizadoContainer.hide();
    
    // Mostrar campos relevantes según el tipo seleccionado
    if (tipoIndicacion === 'medicamento') {
        viaAdminContainer.show();
    } else if (tipoIndicacion === 'procedimiento' || tipoIndicacion === 'control' || tipoIndicacion === 'otro') {
        procPersonalizadoContainer.show();
    }
    
    // Si cambia de medicamento a otro tipo, limpiar el selector de medicamento
    if (tipoIndicacion !== 'medicamento') {
        $(`#consumible-${rowId}`).prop('selectedIndex', 0);
    }
}

// Función para manejar los campos de duración
function mostrarCamposDuracion(rowId) {
    const duracion = $(`#duracion-${rowId}`).val();
    const duracionValorContainer = $(`#duracionValorContainer-${rowId}`);
    const fechaFinContainer = $(`#fechaFinContainer-${rowId}`);
    const duracionUnidad = $(`#duracionUnidad-${rowId}`);
    
    // Ocultar contenedores por defecto
    duracionValorContainer.hide();
    fechaFinContainer.hide();
    
    // Mostrar y configurar según la selección
    if (duracion === 'dias') {
        duracionValorContainer.show();
        duracionUnidad.text('días');
        // Calcular fecha fin automáticamente
        $(`#duracionValor-${rowId}`).on('input', function() {
            calcularFechaFin(rowId);
        });
    } else if (duracion === 'semanas') {
        duracionValorContainer.show();
        duracionUnidad.text('semanas');
        // Calcular fecha fin automáticamente
        $(`#duracionValor-${rowId}`).on('input', function() {
            calcularFechaFin(rowId);
        });
    } else if (duracion === 'meses') {
        duracionValorContainer.show();
        duracionUnidad.text('meses');
        // Calcular fecha fin automáticamente
        $(`#duracionValor-${rowId}`).on('input', function() {
            calcularFechaFin(rowId);
        });
    } else if (duracion === 'fecha') {
        fechaFinContainer.show();
    } else if (duracion === 'dosis') {
        // Para dosis única, la fecha fin es igual a la fecha inicio
        const fechaInicio = $(`#fechaInicio-${rowId}`).val();
        if (fechaInicio) {
            $(`#fechaFin-${rowId}`).val(fechaInicio);
        }
    }
}

// Función para calcular automáticamente la fecha de fin basada en la duración
function calcularFechaFin(rowId) {
    const duracion = $(`#duracion-${rowId}`).val();
    const duracionValor = $(`#duracionValor-${rowId}`).val();
    const fechaInicio = $(`#fechaInicio-${rowId}`).val();
    
    if (!fechaInicio || !duracionValor || duracionValor <= 0) {
        return;
    }
    
    let fechaFin = new Date(fechaInicio);
    
    if (duracion === 'dias') {
        fechaFin.setDate(fechaFin.getDate() + parseInt(duracionValor));
    } else if (duracion === 'semanas') {
        fechaFin.setDate(fechaFin.getDate() + (parseInt(duracionValor) * 7));
    } else if (duracion === 'meses') {
        fechaFin.setMonth(fechaFin.getMonth() + parseInt(duracionValor));
    }
    
    // Formatear la fecha como YYYY-MM-DD para el input date
    const year = fechaFin.getFullYear();
    const month = String(fechaFin.getMonth() + 1).padStart(2, '0');
    const day = String(fechaFin.getDate()).padStart(2, '0');
    $(`#fechaFin-${rowId}`).val(`${year}-${month}-${day}`);
}

// Manejar el campo de otra frecuencia
$(document).on('change', '[id^=frecuencia-]', function() {
    const rowId = $(this).attr('id').split('-')[1];
    const frecuencia = $(this).val();
    const otraFrecuenciaContainer = $(`#otraFrecuenciaContainer-${rowId}`);
    
    if (frecuencia === 'otra') {
        otraFrecuenciaContainer.show();
    } else {
        otraFrecuenciaContainer.hide();
    }
});

// Configuración inicial al cargar la página
$(document).ready(function() {
    // Configurar eventos para filas ya existentes
    $('[id^=tipoIndicacion-]').each(function() {
        const rowId = $(this).attr('id').split('-')[1];
        mostrarCamposRelevantes(rowId);
    });
    
    $('[id^=duracion-]').each(function() {
        const rowId = $(this).attr('id').split('-')[1];
        mostrarCamposDuracion(rowId);
    });
    
    $('[id^=frecuencia-]').each(function() {
        const rowId = $(this).attr('id').split('-')[1];
        const frecuencia = $(this).val();
        const otraFrecuenciaContainer = $(`#otraFrecuenciaContainer-${rowId}`);
        
        if (frecuencia === 'otra') {
            otraFrecuenciaContainer.show();
        }
    });
});
