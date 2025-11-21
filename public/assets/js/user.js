$('#form_email').not('.not-check').on('keyup', function () {
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


$(document).ready(function() {
    
    let emailField = $('#form_email').not('.not-check');
    
    // Solo ejecutar validación de email si el campo existe
    if (emailField.length > 0) {
    let id = (emailField.data('doc-id')) ? emailField.data('doc-id') : 0;
        if(emailField.val() && emailField.val().length > 4 && validateEmail(emailField.val())) {
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
        emailField.next().show();
    }
    }
    
    // Mostrar/ocultar campos de doctor cuando se marca el checkbox
    function initDoctorFieldsToggle() {
        const doctorSection = document.getElementById('doctor-info-section');
        
        if (!doctorSection) {
            return;
        }
        
        // Función para mostrar/ocultar campos
        function toggleDoctorFields() {
            // Buscar el checkbox de múltiples formas
            let checkbox = document.getElementById('completar_info_doctor');
            
            if (!checkbox) {
                // Buscar por nombre del campo (Symfony puede generar IDs diferentes)
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                for (let i = 0; i < checkboxes.length; i++) {
                    const cb = checkboxes[i];
                    if (cb.name && cb.name.indexOf('completarInfoDoctor') !== -1) {
                        checkbox = cb;
                        break;
                    }
                }
            }
            
            if (!checkbox) {
                return;
            }
            
            const isChecked = checkbox.checked;
            
            // Mostrar/ocultar la sección completa de doctor
            if (isChecked) {
                doctorSection.style.display = 'block';
            } else {
                doctorSection.style.display = 'none';
            }
        }
        
        // Buscar el checkbox inicialmente
        let checkbox = document.getElementById('completar_info_doctor');
        
        if (!checkbox) {
            // Buscar por nombre del campo
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            for (let i = 0; i < checkboxes.length; i++) {
                const cb = checkboxes[i];
                if (cb.name && cb.name.indexOf('completarInfoDoctor') !== -1) {
                    checkbox = cb;
                    break;
                }
            }
        }
        
        if (checkbox) {
            // Agregar event listeners directamente al checkbox
            checkbox.addEventListener('change', function() {
                toggleDoctorFields();
            });
            
            checkbox.addEventListener('click', function() {
                setTimeout(toggleDoctorFields, 10);
            });
            
            // También usar jQuery para estar seguro
            $(checkbox).on('change click', function() {
                setTimeout(toggleDoctorFields, 10);
            });
            
            // También escuchar en el contenedor padre por si se hace click en el label
            const parentContainer = checkbox.closest('.custom-control');
            if (parentContainer) {
                $(parentContainer).on('click', function(e) {
                    setTimeout(toggleDoctorFields, 10);
                });
            }
            
            // Ejecutar al cargar la página
            toggleDoctorFields();
        }
    }
    
    // Ejecutar después de un pequeño delay para asegurar que todo esté cargado
    setTimeout(initDoctorFieldsToggle, 500);
    
    // Gestión de rangos de horarios dinámicos
    function initBusinessHoursRanges() {
        const container = document.getElementById('business-hours-container');
        if (!container) {
            return;
        }
        
        // Template para crear un nuevo rango
        const template = document.getElementById('time-range-template');
        if (!template) {
            return;
        }
        
        // Función para agregar un rango
        function addRange(dayElement, dayName) {
            const rangesContainer = dayElement.querySelector('.time-ranges-container');
            const existingRanges = rangesContainer.querySelectorAll('.time-range-row');
            const index = existingRanges.length;
            const dayNum = dayElement.dataset.dayNum;
            
            // Crear nuevo rango desde el template
            let html = template.innerHTML
                .replace(/__DAY__/g, dayName)
                .replace(/__INDEX__/g, index)
                .replace(/__DAY_NUM__/g, dayNum);
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newRange = tempDiv.firstElementChild;
            
            rangesContainer.appendChild(newRange);
            
            // Actualizar índices de todos los rangos
            updateRangeIndexes(dayElement);
            
            // Agregar listener al botón eliminar
            const removeBtn = newRange.querySelector('.remove-range-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    removeRange(newRange, dayElement);
                });
            }
            
            // Agregar listener al campo "Desde" para filtrar opciones de "Hasta"
            const startSelect = newRange.querySelector('.time-start');
            const endSelect = newRange.querySelector('.time-end');
            if (startSelect && endSelect) {
                startSelect.addEventListener('change', function() {
                    filterEndTimeOptions(startSelect, endSelect);
                });
                // Aplicar filtro inicial si ya hay un valor seleccionado
                if (startSelect.value) {
                    filterEndTimeOptions(startSelect, endSelect);
                }
            }
        }
        
        // Función para filtrar las opciones de "Hasta" basándose en la hora "Desde"
        function filterEndTimeOptions(startSelect, endSelect) {
            const startTime = startSelect.value;
            
            if (!startTime) {
                // Si no hay hora "Desde" seleccionada, mostrar todas las opciones
                Array.from(endSelect.options).forEach(function(option) {
                    option.style.display = '';
                });
                return;
            }
            
            // Convertir hora "Desde" a minutos para comparación
            const [startHour, startMinute] = startTime.split(':').map(Number);
            const startTotalMinutes = startHour * 60 + startMinute;
            
            // Filtrar opciones del campo "Hasta"
            Array.from(endSelect.options).forEach(function(option) {
                if (option.value === '') {
                    // Mantener la opción vacía visible
                    option.style.display = '';
                    return;
                }
                
                const [endHour, endMinute] = option.value.split(':').map(Number);
                const endTotalMinutes = endHour * 60 + endMinute;
                
                // Mostrar solo horas posteriores a la hora "Desde"
                if (endTotalMinutes > startTotalMinutes) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                    // Si la opción seleccionada es anterior o igual, limpiar la selección
                    if (endSelect.value === option.value) {
                        endSelect.value = '';
                    }
                }
            });
        }
        
        // Función para eliminar un rango
        function removeRange(rangeElement, dayElement) {
            const rangesContainer = dayElement.querySelector('.time-ranges-container');
            const ranges = rangesContainer.querySelectorAll('.time-range-row');
            
            if (ranges.length > 1) {
                rangeElement.remove();
                updateRangeIndexes(dayElement);
            }
        }
        
        // Función para actualizar los índices de los rangos
        function updateRangeIndexes(dayElement) {
            const rangesContainer = dayElement.querySelector('.time-ranges-container');
            const ranges = rangesContainer.querySelectorAll('.time-range-row');
            const dayName = dayElement.dataset.day;
            
            ranges.forEach(function(range, index) {
                range.dataset.rangeIndex = index;
                
                // Actualizar nombres de los campos
                const startSelect = range.querySelector('select[name*="[start]"]');
                const endSelect = range.querySelector('select[name*="[end]"]');
                
                if (startSelect) {
                    startSelect.name = `doctor_${dayName}_ranges[${index}][start]`;
                }
                if (endSelect) {
                    endSelect.name = `doctor_${dayName}_ranges[${index}][end]`;
                }
            });
            
            // Habilitar/deshabilitar botones eliminar
            const removeBtns = rangesContainer.querySelectorAll('.remove-range-btn');
            removeBtns.forEach(function(btn) {
                btn.disabled = ranges.length <= 1;
            });
        }
        
        // Agregar listeners a todos los botones "Agregar horario"
        container.querySelectorAll('.add-range-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const dayElement = this.closest('.business-hours-day');
                const dayName = dayElement.dataset.day;
                addRange(dayElement, dayName);
            });
        });
        
        // Agregar listeners a todos los botones "Eliminar" existentes
        container.querySelectorAll('.remove-range-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const rangeElement = this.closest('.time-range-row');
                const dayElement = rangeElement.closest('.business-hours-day');
                removeRange(rangeElement, dayElement);
            });
        });
        
        // Función para crear un rango con valores específicos
        function addRangeWithValues(dayElement, dayName, startValue, endValue) {
            const rangesContainer = dayElement.querySelector('.time-ranges-container');
            const existingRanges = rangesContainer.querySelectorAll('.time-range-row');
            const index = existingRanges.length;
            const dayNum = dayElement.dataset.dayNum;
            
            // Crear nuevo rango desde el template
            let html = template.innerHTML
                .replace(/__DAY__/g, dayName)
                .replace(/__INDEX__/g, index)
                .replace(/__DAY_NUM__/g, dayNum);
            
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            const newRange = tempDiv.firstElementChild;
            
            rangesContainer.appendChild(newRange);
            
            // Establecer valores si se proporcionan
            const startSelect = newRange.querySelector('.time-start');
            const endSelect = newRange.querySelector('.time-end');
            
            if (startSelect && startValue) {
                startSelect.value = startValue;
            }
            
            if (endSelect && endValue) {
                endSelect.value = endValue;
            }
            
            // Actualizar índices de todos los rangos
            updateRangeIndexes(dayElement);
            
            // Agregar listener al botón eliminar
            const removeBtn = newRange.querySelector('.remove-range-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    removeRange(newRange, dayElement);
                });
            }
            
            // Agregar listener al campo "Desde" para filtrar opciones de "Hasta"
            if (startSelect && endSelect) {
                startSelect.addEventListener('change', function() {
                    filterEndTimeOptions(startSelect, endSelect);
                });
                // Aplicar filtro inicial si ya hay un valor seleccionado
                if (startSelect.value) {
                    filterEndTimeOptions(startSelect, endSelect);
                }
            }
        }
        
        // Función para restaurar horarios desde datos guardados
        function restoreBusinessHours() {
            const defaultDays = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
            const hasSavedData = window.businessHoursData && typeof window.businessHoursData === 'object' && Object.keys(window.businessHoursData).length > 0;
            
            container.querySelectorAll('.business-hours-day').forEach(function(dayElement) {
                const dayName = dayElement.dataset.day;
                const rangesContainer = dayElement.querySelector('.time-ranges-container');
                
                // Limpiar contenedor
                rangesContainer.innerHTML = '';
                
                // Verificar si hay datos guardados para este día específico
                const rangesData = hasSavedData ? (window.businessHoursData[dayName] || null) : null;
                
                if (rangesData && Array.isArray(rangesData) && rangesData.length > 0) {
                    // Restaurar cada rango guardado
                    rangesData.forEach(function(rangeData, index) {
                        const dayNum = dayElement.dataset.dayNum;
                        
                        let html = template.innerHTML
                            .replace(/__DAY__/g, dayName)
                            .replace(/__INDEX__/g, index)
                            .replace(/__DAY_NUM__/g, dayNum);
                        
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const newRange = tempDiv.firstElementChild;
                        
                        rangesContainer.appendChild(newRange);
                        
                        // Restaurar valores
                        const startSelect = newRange.querySelector('.time-start');
                        const endSelect = newRange.querySelector('.time-end');
                        
                        if (startSelect && rangeData.start) {
                            startSelect.value = rangeData.start;
                        }
                        
                        if (endSelect && rangeData.end) {
                            endSelect.value = rangeData.end;
                        }
                        
                        // Agregar listeners
                        const removeBtn = newRange.querySelector('.remove-range-btn');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', function() {
                                removeRange(newRange, dayElement);
                            });
                        }
                        
                        // Agregar listener al campo "Desde" para filtrar opciones de "Hasta"
                        if (startSelect && endSelect) {
                            startSelect.addEventListener('change', function() {
                                filterEndTimeOptions(startSelect, endSelect);
                            });
                            // Aplicar filtro inicial si ya hay un valor seleccionado
                            if (startSelect.value) {
                                filterEndTimeOptions(startSelect, endSelect);
                            }
                        }
                    });
                    
                    // Actualizar índices
                    updateRangeIndexes(dayElement);
                } else {
                    // No hay datos guardados para este día, usar valores por defecto
                    if (defaultDays.includes(dayName)) {
                        // Lunes a viernes: 08:00 a 16:00
                        addRangeWithValues(dayElement, dayName, '08:00', '16:00');
                    } else {
                        // Sábado y domingo: rango vacío
                        addRange(dayElement, dayName);
                    }
                }
            });
        }
        
        // Restaurar horarios después de inicializar
        restoreBusinessHours();
    }
    
    // Inicializar gestión de rangos de horarios
    setTimeout(initBusinessHoursRanges, 600);
});
