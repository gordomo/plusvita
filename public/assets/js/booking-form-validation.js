/**
 * Validación y visualización de horarios disponibles del doctor en el formulario de booking
 */

(function() {
    'use strict';

    // Función para formatear horarios del doctor para mostrar
    function formatBusinessHours(businessHours) {
        if (!businessHours || typeof businessHours !== 'object') {
            return null;
        }

        const dias = {
            1: 'Lunes',
            2: 'Martes',
            3: 'Miércoles',
            4: 'Jueves',
            5: 'Viernes',
            6: 'Sábado',
            7: 'Domingo'
        };

        const diasNombres = {
            'lunes': 'Lunes',
            'martes': 'Martes',
            'miercoles': 'Miércoles',
            'jueves': 'Jueves',
            'viernes': 'Viernes',
            'sabado': 'Sábado',
            'domingo': 'Domingo'
        };

        let formatted = {};
        
        // Convertir estructura numérica (1-7) a nombres de días
        for (let dayNum = 1; dayNum <= 7; dayNum++) {
            if (businessHours[dayNum] && Array.isArray(businessHours[dayNum])) {
                const dayName = diasNombres[Object.keys(diasNombres)[dayNum - 1]];
                formatted[dayName] = businessHours[dayNum];
            }
        }

        // También verificar estructura por nombre de día
        Object.keys(diasNombres).forEach(dayKey => {
            if (businessHours[dayKey] && Array.isArray(businessHours[dayKey])) {
                formatted[diasNombres[dayKey]] = businessHours[dayKey];
            }
        });

        return Object.keys(formatted).length > 0 ? formatted : null;
    }

    // Función para mostrar los horarios disponibles del doctor
    function displayDoctorBusinessHours(businessHours) {
        const section = document.getElementById('doctor-business-hours-section');
        const display = document.getElementById('business-hours-display');
        
        if (!section || !display) return;

        const formatted = formatBusinessHours(businessHours);
        
        if (!formatted || Object.keys(formatted).length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        display.innerHTML = '';

        const diasOrden = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        
        diasOrden.forEach(dia => {
            if (!formatted[dia] || formatted[dia].length === 0) return;

            const col = document.createElement('div');
            col.className = 'col-md-6 mb-2';
            
            const dayCard = document.createElement('div');
            dayCard.className = 'border rounded p-2 bg-light';
            
            const dayTitle = document.createElement('strong');
            dayTitle.textContent = dia + ': ';
            dayTitle.className = 'text-primary';
            
            const ranges = [];
            formatted[dia].forEach(range => {
                let rangeText = '';
                if (range.nextDay) {
                    rangeText = `${range.start} - ${range.end} (día siguiente)`;
                } else {
                    rangeText = `${range.start} - ${range.end}`;
                }
                ranges.push(rangeText);
            });
            
            const dayContent = document.createElement('span');
            dayContent.textContent = ranges.join(', ');
            
            dayCard.appendChild(dayTitle);
            dayCard.appendChild(dayContent);
            col.appendChild(dayCard);
            display.appendChild(col);
        });
    }

    // Función para validar si un horario está dentro de los horarios disponibles
    function validateTimeAgainstBusinessHours(beginAt, endAt, businessHours) {
        if (!businessHours || !beginAt) return { valid: true };

        const formatted = formatBusinessHours(businessHours);
        if (!formatted || Object.keys(formatted).length === 0) {
            return { valid: true }; // Si no hay horarios configurados, permitir cualquier horario
        }

        const date = new Date(beginAt);
        const dayOfWeek = date.getDay(); // 0 = Domingo, 1 = Lunes, etc.
        const dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const dayName = dayNames[dayOfWeek];

        if (!formatted[dayName] || formatted[dayName].length === 0) {
            return { 
                valid: false, 
                message: `El profesional no tiene horarios disponibles los ${dayName.toLowerCase()}s` 
            };
        }

        const timeStr = date.toTimeString().substring(0, 5); // HH:MM
        const endTimeStr = endAt ? new Date(endAt).toTimeString().substring(0, 5) : null;

        // Verificar si el horario está dentro de algún rango disponible
        let foundValidRange = false;
        let message = '';

        formatted[dayName].forEach(range => {
            if (range.nextDay) {
                // Horario que cruza medianoche
                if (timeStr >= range.start || timeStr <= range.end) {
                    foundValidRange = true;
                }
            } else {
                // Horario normal
                if (timeStr >= range.start && timeStr <= range.end) {
                    if (!endTimeStr || (endTimeStr <= range.end)) {
                        foundValidRange = true;
                    } else {
                        message = `El horario de fin debe ser antes de las ${range.end}`;
                    }
                }
            }
        });

        if (!foundValidRange) {
            const availableRanges = formatted[dayName].map(r => 
                r.nextDay ? `${r.start} - ${r.end} (día siguiente)` : `${r.start} - ${r.end}`
            ).join(', ');
            return { 
                valid: false, 
                message: `El horario seleccionado no está dentro de los horarios disponibles (${availableRanges})` 
            };
        }

        if (message) {
            return { valid: false, message: message };
        }

        return { valid: true };
    }

    // Función para actualizar la validación visual
    function updateValidationDisplay(elementId, validation) {
        const element = document.getElementById(elementId);
        if (!element) return;

        if (validation.valid) {
            element.style.display = 'none';
            element.textContent = '';
            if (element.previousElementSibling) {
                element.previousElementSibling.classList.remove('is-invalid');
                element.previousElementSibling.classList.add('is-valid');
            }
        } else {
            element.style.display = 'block';
            element.textContent = validation.message;
            if (element.previousElementSibling) {
                element.previousElementSibling.classList.remove('is-valid');
                element.previousElementSibling.classList.add('is-invalid');
            }
        }
    }

    // Función para convertir hora HH:MM a minutos para comparación
    function timeToMinutes(timeStr) {
        if (!timeStr) return null;
        const parts = timeStr.split(':');
        if (parts.length !== 2) return null;
        return parseInt(parts[0]) * 60 + parseInt(parts[1]);
    }

    // Función para verificar si un doctor tiene disponible un horario específico
    function doctorHasTimeAvailable(doctorId, beginAt, endAt) {
        if (!beginAt || typeof allDoctorsBusinessHours === 'undefined') {
            return true; // Si no hay horario seleccionado, mostrar todos los doctores
        }

        // Convertir doctorId a string para buscar en el objeto (por si viene como número)
        const doctorIdStr = String(doctorId);
        const businessHours = allDoctorsBusinessHours[doctorId] || allDoctorsBusinessHours[doctorIdStr];
        
        if (!businessHours || typeof businessHours !== 'object' || Object.keys(businessHours).length === 0) {
            return false; // Si el doctor no tiene horarios configurados, no está disponible
        }

        // Parsear la fecha/hora - puede venir en formato ISO o datetime-local
        let date;
        if (beginAt.includes('T')) {
            // Formato ISO o datetime-local (YYYY-MM-DDTHH:mm)
            date = new Date(beginAt);
        } else {
            // Intentar otros formatos
            date = new Date(beginAt);
        }
        
        if (isNaN(date.getTime())) {
            return true; // Si la fecha no es válida, mostrar todos los doctores
        }

        const dayOfWeek = date.getDay(); // 0 = Domingo, 1 = Lunes, etc.
        // Convertir a formato numérico (1 = Lunes, 7 = Domingo)
        const dayNum = dayOfWeek === 0 ? 7 : dayOfWeek;
        
        // Obtener los rangos del día - también verificar como string
        const dayRanges = businessHours[dayNum] || businessHours[String(dayNum)];
        if (!dayRanges) {
            return false; // No tiene horarios ese día
        }

        // Extraer hora y minuto de forma más robusta
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const beginTimeStr = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
        
        let endTimeStr = null;
        if (endAt) {
            const endDate = new Date(endAt);
            if (!isNaN(endDate.getTime())) {
                const endHours = endDate.getHours();
                const endMinutes = endDate.getMinutes();
                endTimeStr = String(endHours).padStart(2, '0') + ':' + String(endMinutes).padStart(2, '0');
            }
        }

        const beginMinutes = timeToMinutes(beginTimeStr);
        if (beginMinutes === null) return false;

        const endMinutes = endTimeStr ? timeToMinutes(endTimeStr) : null;

        // Manejar ambos formatos: nuevo (array) y antiguo (objeto con desde/hasta)
        let rangesToCheck = [];
        
        if (typeof dayRanges === 'object' && dayRanges !== null && !Array.isArray(dayRanges)) {
            // Formato antiguo: {"desde": "08:00", "hasta": "18:00", "ydesde": "08:00", "yhasta": "18:00"}
            if (dayRanges.desde && dayRanges.hasta) {
                rangesToCheck = [{
                    start: dayRanges.desde,
                    end: dayRanges.hasta
                }];
            }
        } else if (Array.isArray(dayRanges)) {
            // Formato nuevo: [{"start": "08:00", "end": "16:00"}, ...]
            // Pero este formato no debería existir si mantenemos el formato antiguo
            rangesToCheck = dayRanges.map(range => {
                if (typeof range === 'object' && range !== null) {
                    // Si tiene start/end, usar esos
                    if (range.start && range.end) {
                        return { start: range.start, end: range.end };
                    }
                    // Si tiene desde/hasta, convertir
                    if (range.desde && range.hasta) {
                        return { start: range.desde, end: range.hasta };
                    }
                }
                return null;
            }).filter(r => r !== null);
        }

        if (rangesToCheck.length === 0) {
            return false; // No hay rangos válidos
        }

        // Verificar si el horario está dentro de algún rango disponible
        for (let i = 0; i < rangesToCheck.length; i++) {
            const range = rangesToCheck[i];
            if (!range.start || !range.end) continue;

            const rangeStartMinutes = timeToMinutes(range.start);
            const rangeEndMinutes = timeToMinutes(range.end);
            
            if (rangeStartMinutes === null || rangeEndMinutes === null) continue;

            // Verificar si el horario de inicio está dentro del rango
            if (beginMinutes >= rangeStartMinutes && beginMinutes <= rangeEndMinutes) {
                if (endMinutes !== null) {
                    // Verificar que el horario completo esté dentro del rango
                    if (endMinutes >= rangeStartMinutes && endMinutes <= rangeEndMinutes) {
                        return true;
                    }
                } else {
                    // Solo verificar inicio (si el inicio está dentro del rango, es válido)
                    return true;
                }
            }
        }

        return false;
    }

    // Función para filtrar las opciones del select de doctores según el horario seleccionado
    function filterDoctorsByTime(beginAt, endAt) {
        const doctorSelect = document.getElementById('booking-doctor-select');
        if (!doctorSelect) {
            return;
        }

        if (!beginAt) {
            // Mostrar todos los doctores si no hay horario
            const options = doctorSelect.querySelectorAll('option');
            options.forEach(function(option) {
                option.style.display = '';
                option.disabled = false;
            });
            if (typeof jQuery !== 'undefined' && jQuery(doctorSelect).hasClass('chosen-done')) {
                jQuery(doctorSelect).trigger('chosen:updated');
            }
            return;
        }

        const selectedValue = doctorSelect.value;
        const options = doctorSelect.querySelectorAll('option');
        
        let hasAvailableDoctors = false;
        let selectedDoctorUnavailable = false;
        let availableCount = 0;
        let hiddenCount = 0;

        options.forEach(function(option) {
            if (option.value === '') {
                // Mantener la opción vacía visible
                option.style.display = '';
                option.disabled = false;
                return;
            }

            const doctorId = parseInt(option.value);
            if (isNaN(doctorId)) {
                option.style.display = '';
                option.disabled = false;
                return;
            }

            const isAvailable = doctorHasTimeAvailable(doctorId, beginAt, endAt);
            
            if (isAvailable) {
                // Mostrar opción disponible
                option.style.display = '';
                option.disabled = false;
                option.removeAttribute('data-disabled');
                availableCount++;
                if (!hasAvailableDoctors) {
                    hasAvailableDoctors = true;
                }
            } else {
                // Ocultar opción no disponible
                // Para Chosen, usar disabled y data-disabled
                option.disabled = true;
                option.setAttribute('data-disabled', 'true');
                // También ocultar visualmente
                option.style.display = 'none';
                hiddenCount++;
                // Si el doctor seleccionado no está disponible, marcar para limpiar
                if (option.value === selectedValue) {
                    selectedDoctorUnavailable = true;
                }
            }
        });


        // Si el doctor seleccionado ya no está disponible, limpiar la selección
        if (selectedDoctorUnavailable) {
            doctorSelect.value = '';
            // Actualizar Chosen si está inicializado
            if (typeof jQuery !== 'undefined' && jQuery(doctorSelect).hasClass('chosen-done')) {
                jQuery(doctorSelect).trigger('chosen:updated');
            }
        } else if (typeof jQuery !== 'undefined' && jQuery(doctorSelect).hasClass('chosen-done')) {
            // Actualizar Chosen para reflejar los cambios
            jQuery(doctorSelect).trigger('chosen:updated');
        }

        // Mostrar mensaje si había un doctor seleccionado pero ya no está disponible
        if (selectedDoctorUnavailable) {
            const validationMsg = document.getElementById('doctor-validation-msg');
            if (validationMsg) {
                validationMsg.style.display = 'block';
                validationMsg.textContent = 'El profesional seleccionado no está disponible en el horario elegido. Por favor, seleccione otro profesional.';
                validationMsg.className = 'text-danger small';
            }
        } else {
            const validationMsg = document.getElementById('doctor-validation-msg');
            if (validationMsg) {
                validationMsg.style.display = 'none';
            }
        }
    }

    // Función para inicializar los event listeners
    function initializeBookingFormValidation() {
        // Buscar el select de doctores de diferentes formas
        let doctorSelect = document.getElementById('booking-doctor-select');
        
        // Si no se encuentra por ID, intentar buscar por clase o name
        if (!doctorSelect) {
            // Buscar por name attribute (Symfony puede generar diferentes IDs)
            doctorSelect = document.querySelector('select[name*="doctor"]');
        }
        
        if (!doctorSelect) {
            // Buscar por clase predictivo dentro del formulario
            const form = document.getElementById('booking-form');
            if (form) {
                doctorSelect = form.querySelector('select.predictivo');
            }
        }

        const beginAtInput = document.getElementById('booking-begin-at');
        const endAtInput = document.getElementById('booking-end-at');

        if (!doctorSelect) {
            // No mostrar error en consola si el elemento no existe (puede ser otra página)
            return;
        }
        
        // Asegurar que el ID esté configurado correctamente
        if (!doctorSelect.id) {
            doctorSelect.id = 'booking-doctor-select';
        }

        // Crear elemento para mensajes de validación del doctor si no existe
        let doctorValidationMsg = document.getElementById('doctor-validation-msg');
        if (!doctorValidationMsg && doctorSelect.parentElement) {
            doctorValidationMsg = document.createElement('small');
            doctorValidationMsg.id = 'doctor-validation-msg';
            doctorValidationMsg.className = 'text-danger small';
            doctorValidationMsg.style.display = 'none';
            doctorSelect.parentElement.appendChild(doctorValidationMsg);
        }

        // Cuando se selecciona un doctor, mostrar sus horarios
        doctorSelect.addEventListener('change', function() {
            const doctorId = this.value;
            const businessHours = typeof allDoctorsBusinessHours !== 'undefined' && allDoctorsBusinessHours[doctorId] 
                ? allDoctorsBusinessHours[doctorId] 
                : null;
            
            displayDoctorBusinessHours(businessHours);
            
            // Limpiar validaciones anteriores
            updateValidationDisplay('begin-at-validation', { valid: true });
            updateValidationDisplay('end-at-validation', { valid: true });
            
            // Ocultar mensaje de validación del doctor si hay uno seleccionado
            if (doctorValidationMsg && this.value) {
                doctorValidationMsg.style.display = 'none';
            }
        });

        // Función para actualizar filtro y validación cuando cambia el horario
        function updateTimeBasedFilter() {
            const beginAt = beginAtInput ? beginAtInput.value : null;
            const endAt = endAtInput ? endAtInput.value : null;

            if (!beginAt) {
                // Si no hay horario seleccionado, mostrar todos los doctores
                const options = doctorSelect.querySelectorAll('option');
                options.forEach(function(option) {
                    option.style.display = '';
                    option.disabled = false;
                });
                if (typeof jQuery !== 'undefined' && jQuery(doctorSelect).hasClass('chosen-done')) {
                    jQuery(doctorSelect).trigger('chosen:updated');
                }
                return;
            }

            // Filtrar doctores según el horario seleccionado
            filterDoctorsByTime(beginAt, endAt);

            // Validar horario del doctor seleccionado
            const doctorId = doctorSelect.value;
            if (doctorId && beginAt) {
                const businessHours = typeof allDoctorsBusinessHours !== 'undefined' && allDoctorsBusinessHours[doctorId] 
                    ? allDoctorsBusinessHours[doctorId] 
                    : null;
                
                if (businessHours) {
                    const validation = validateTimeAgainstBusinessHours(
                        beginAt, 
                        endAt, 
                        businessHours
                    );
                    updateValidationDisplay('begin-at-validation', validation);
                    if (endAt) {
                        updateValidationDisplay('end-at-validation', validation);
                    }
                }
            }
        }

        // Validar y filtrar cuando cambia el horario de inicio
        if (beginAtInput) {
            beginAtInput.addEventListener('change', updateTimeBasedFilter);
            beginAtInput.addEventListener('input', updateTimeBasedFilter); // También en tiempo real
        }

        // Validar y filtrar cuando cambia el horario de fin
        if (endAtInput) {
            endAtInput.addEventListener('change', updateTimeBasedFilter);
            endAtInput.addEventListener('input', updateTimeBasedFilter); // También en tiempo real
        }

        // Mostrar horarios del doctor si ya hay uno seleccionado
        if (doctorSelect.value && typeof allDoctorsBusinessHours !== 'undefined') {
            const businessHours = allDoctorsBusinessHours[doctorSelect.value];
            if (businessHours) {
                displayDoctorBusinessHours(businessHours);
            }
        }

        // Aplicar filtro inicial si hay un horario pre-seleccionado
        // Esperar a que Chosen se inicialice primero y que los datos estén disponibles
        function applyInitialFilter() {
            if (typeof allDoctorsBusinessHours === 'undefined') {
                setTimeout(applyInitialFilter, 100);
                return;
            }
            
            if (beginAtInput && beginAtInput.value) {
                updateTimeBasedFilter();
            }
        }

        // Esperar a que Chosen se inicialice
        if (typeof jQuery !== 'undefined') {
            jQuery(document).ready(function() {
                setTimeout(applyInitialFilter, 500);
            });
        } else {
            setTimeout(applyInitialFilter, 500);
        }
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        initializeBookingFormValidation();
    });
})();

