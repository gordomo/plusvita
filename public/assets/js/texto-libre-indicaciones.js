// Texto Libre para Indicaciones
document.addEventListener('DOMContentLoaded', function() {
    // Configurar el manejo de indicaciones de texto libre
    setupTextoLibreIndicaciones();
    
    // Agregar evento al botón de agregar
    const agregarBtn = document.getElementById('agregarItem');
    if (agregarBtn) {
        const originalAgregarHandler = agregarBtn.onclick;
        agregarBtn.onclick = function(e) {
            if (originalAgregarHandler) {
                originalAgregarHandler.call(this, e);
            }
            setupTextoLibreIndicaciones();
        };
    }
});

function setupTextoLibreIndicaciones() {
    // Buscar todos los selectores de consumibles
    const consumiblesSelects = document.querySelectorAll('.selectConsumible');
    
    consumiblesSelects.forEach(select => {
        // Remover el manejador anterior para evitar duplicados
        select.removeEventListener('change', handleTextoLibreChange);
        select.addEventListener('change', handleTextoLibreChange);
        
        // Verificar el valor seleccionado inicialmente
        checkIfTextoLibre(select);
    });
}

function handleTextoLibreChange(e) {
    checkIfTextoLibre(e.target);
}

function checkIfTextoLibre(select) {
    const row = select.getAttribute('data-row');
    const cantidadInput = document.getElementById('cantidad-' + row);
    const consumibleText = select.options[select.selectedIndex].text;
    
    // Verificar si es indicación de texto libre
    if (consumibleText === 'Indicación de texto libre') {
        // Crear campo de texto si no existe
        let textoLibreContainer = document.getElementById('texto-libre-container-' + row);
        if (!textoLibreContainer) {
            // Obtener la celda que contiene el input de cantidad
            const cantidadCell = cantidadInput.parentNode;
            
            // Crear un contenedor para el campo de texto libre
            textoLibreContainer = document.createElement('div');
            textoLibreContainer.id = 'texto-libre-container-' + row;
            textoLibreContainer.className = 'texto-libre-container mt-2';
            
            // Crear textarea para la indicación personalizada
            const textarea = document.createElement('textarea');
            textarea.id = 'texto-libre-' + row;
            textarea.className = 'form-control';
            textarea.placeholder = 'Escriba la indicación personalizada aquí (ej: tomar presión arterial cada 4hs)';
            textarea.rows = 2;
            
            // Agregar la descripción
            const descripcion = document.createElement('small');
            descripcion.className = 'text-muted';
            descripcion.textContent = 'Esta indicación se mostrará en el Kardex con el texto personalizado.';
            
            // Agregar elementos al contenedor
            textoLibreContainer.appendChild(textarea);
            textoLibreContainer.appendChild(descripcion);
            
            // Insertar el contenedor después del input de cantidad
            cantidadCell.appendChild(textoLibreContainer);
            
            // Establecer cantidad por defecto a 1 para indicación de texto
            cantidadInput.value = 1;
        }
        
        // Mostrar el campo de texto libre
        textoLibreContainer.style.display = 'block';
    } else {
        // Ocultar campo de texto libre si existe
        const textoLibreContainer = document.getElementById('texto-libre-container-' + row);
        if (textoLibreContainer) {
            textoLibreContainer.style.display = 'none';
        }
    }
}

// El código con tags de Twig ha sido eliminado ya que debe incluirse directamente
// en el template para que Twig pueda procesarlo correctamente
