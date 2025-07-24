document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contadores para el número de indicaciones
    const indicacionItems = document.querySelectorAll('.indicacion-item');
    const totalCounter = document.getElementById('indicaciones-total-counter');
    
    if (totalCounter) {
        totalCounter.textContent = indicacionItems.length;
    }

    // Manejadores para los enlaces de colapso/expansión
    document.querySelectorAll('.abrir').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const seccion = this.dataset.seccion;
            const seccionElement = document.getElementById(seccion);
            
            if (seccionElement) {
                if (this.dataset.toogle === 'cerrado') {
                    seccionElement.classList.add('show');
                    this.dataset.toogle = 'abierto';
                    this.querySelector('i').classList.remove('fa-chevron-down');
                    this.querySelector('i').classList.add('fa-chevron-up');
                } else {
                    seccionElement.classList.remove('show');
                    this.dataset.toogle = 'cerrado';
                    this.querySelector('i').classList.remove('fa-chevron-up');
                    this.querySelector('i').classList.add('fa-chevron-down');
                }
            }
        });
    });
});
