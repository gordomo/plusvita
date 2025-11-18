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

// Verificar que el script se carga
console.log('user.js cargado');

$(document).ready(function() {
    console.log('jQuery ready ejecutado');
    
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
        console.log('Inicializando toggle de campos de doctor');
        
        const doctorSection = document.getElementById('doctor-info-section');
        
        if (!doctorSection) {
            console.error('No se encontró la sección doctor-info-section');
            return;
        }
        
        console.log('Sección doctor encontrada:', doctorSection);
        
        // Función para mostrar/ocultar campos
        function toggleDoctorFields() {
            // Buscar el checkbox de múltiples formas
            let checkbox = document.getElementById('completar_info_doctor');
            
            if (!checkbox) {
                // Buscar por nombre del campo (Symfony puede generar IDs diferentes)
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');
                console.log('Total de checkboxes encontrados:', checkboxes.length);
                for (let i = 0; i < checkboxes.length; i++) {
                    const cb = checkboxes[i];
                    console.log('Checkbox:', cb.id, cb.name, cb.type);
                    if (cb.name && cb.name.indexOf('completarInfoDoctor') !== -1) {
                        checkbox = cb;
                        console.log('Checkbox encontrado por nombre:', cb.name);
                        break;
                    }
                }
            } else {
                console.log('Checkbox encontrado por ID:', checkbox.id);
            }
            
            if (!checkbox) {
                console.error('No se encontró el checkbox completarInfoDoctor');
                return;
            }
            
            const isChecked = checkbox.checked;
            console.log('Toggle doctor fields, checkbox checked:', isChecked);
            
            // Mostrar/ocultar la sección completa de doctor
            if (isChecked) {
                doctorSection.style.display = 'block';
                console.log('Mostrando campos de doctor');
            } else {
                doctorSection.style.display = 'none';
                console.log('Ocultando campos de doctor');
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
            console.log('Checkbox encontrado para eventos:', checkbox.id || checkbox.name);
            
            // Agregar event listeners directamente al checkbox
            checkbox.addEventListener('change', function() {
                console.log('Evento change en checkbox');
                toggleDoctorFields();
            });
            
            checkbox.addEventListener('click', function() {
                console.log('Evento click en checkbox');
                setTimeout(toggleDoctorFields, 10);
            });
            
            // También usar jQuery para estar seguro
            $(checkbox).on('change click', function() {
                console.log('jQuery event en checkbox');
                setTimeout(toggleDoctorFields, 10);
            });
            
            // También escuchar en el contenedor padre por si se hace click en el label
            const parentContainer = checkbox.closest('.custom-control');
            if (parentContainer) {
                $(parentContainer).on('click', function(e) {
                    console.log('Click en contenedor padre');
                    setTimeout(toggleDoctorFields, 10);
                });
            }
            
            // Ejecutar al cargar la página
            toggleDoctorFields();
        } else {
            console.error('No se pudo encontrar el checkbox completarInfoDoctor');
            console.log('Todos los checkboxes en la página:');
            document.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                console.log('  - ID:', cb.id, 'Name:', cb.name, 'Type:', cb.type);
            });
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
            
            // Agregar listener al checkbox "cruza medianoche"
            const nextDayCheckbox = newRange.querySelector('.next-day-checkbox');
            if (nextDayCheckbox) {
                nextDayCheckbox.addEventListener('change', function() {
                    toggleNextDayFields(newRange);
                });
            }
        }
        
        // Función para mostrar/ocultar campos según si cruza medianoche
        function toggleNextDayFields(rangeElement) {
            const nextDayCheckbox = rangeElement.querySelector('.next-day-checkbox');
            const normalEndContainer = rangeElement.querySelector('.normal-end-container');
            const nextDayContainer = rangeElement.querySelector('.next-day-container');
            
            if (nextDayCheckbox && normalEndContainer && nextDayContainer) {
                if (nextDayCheckbox.checked) {
                    // Ocultar "Hasta" normal y mostrar "Hasta día siguiente"
                    normalEndContainer.style.display = 'none';
                    nextDayContainer.style.display = 'block';
                    
                    // Limpiar el valor del campo "Hasta" normal
                    const normalEndSelect = rangeElement.querySelector('.time-end');
                    if (normalEndSelect) {
                        normalEndSelect.value = '';
                    }
                } else {
                    // Mostrar "Hasta" normal y ocultar "Hasta día siguiente"
                    normalEndContainer.style.display = 'block';
                    nextDayContainer.style.display = 'none';
                    
                    // Limpiar el valor del campo "Hasta día siguiente"
                    const nextDayEndSelect = rangeElement.querySelector('.time-end-next-day');
                    if (nextDayEndSelect) {
                        nextDayEndSelect.value = '';
                    }
                }
            }
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
                const endNextDaySelect = range.querySelector('select[name*="[endNextDay]"]');
                const nextDayCheckbox = range.querySelector('input[name*="[nextDay]"]');
                
                if (startSelect) {
                    startSelect.name = `doctor_${dayName}_ranges[${index}][start]`;
                }
                if (endSelect) {
                    endSelect.name = `doctor_${dayName}_ranges[${index}][end]`;
                }
                if (endNextDaySelect) {
                    endNextDaySelect.name = `doctor_${dayName}_ranges[${index}][endNextDay]`;
                }
                if (nextDayCheckbox) {
                    nextDayCheckbox.name = `doctor_${dayName}_ranges[${index}][nextDay]`;
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
        
        // Agregar listeners a todos los checkboxes "cruza medianoche" existentes
        container.querySelectorAll('.next-day-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                const rangeElement = this.closest('.time-range-row');
                toggleNextDayFields(rangeElement);
            });
            
            // Ejecutar al cargar para establecer el estado inicial
            const rangeElement = checkbox.closest('.time-range-row');
            toggleNextDayFields(rangeElement);
        });
        
        // Función para crear un rango con valores específicos
        function addRangeWithValues(dayElement, dayName, startValue, endValue, nextDayValue, endNextDayValue) {
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
            const endNextDaySelect = newRange.querySelector('.time-end-next-day');
            const nextDayCheckbox = newRange.querySelector('.next-day-checkbox');
            
            if (startSelect && startValue) {
                startSelect.value = startValue;
            }
            
            if (nextDayValue && endNextDayValue) {
                // Si cruza medianoche
                if (nextDayCheckbox) {
                    nextDayCheckbox.checked = true;
                }
                if (endNextDaySelect) {
                    endNextDaySelect.value = endNextDayValue;
                }
                toggleNextDayFields(newRange);
            } else if (endSelect && endValue) {
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
            
            // Agregar listener al checkbox "cruza medianoche"
            const nextDayCb = newRange.querySelector('.next-day-checkbox');
            if (nextDayCb) {
                nextDayCb.addEventListener('change', function() {
                    toggleNextDayFields(newRange);
                });
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
                        const endNextDaySelect = newRange.querySelector('.time-end-next-day');
                        const nextDayCheckbox = newRange.querySelector('.next-day-checkbox');
                        
                        if (startSelect && rangeData.start) {
                            startSelect.value = rangeData.start;
                        }
                        
                        // Verificar si cruza medianoche
                        // nextDay puede venir como "1" (string) del request o como true (boolean)
                        const hasNextDay = rangeData.nextDay === "1" || rangeData.nextDay === 1 || rangeData.nextDay === true;
                        
                        if (hasNextDay && rangeData.endNextDay) {
                            if (nextDayCheckbox) {
                                nextDayCheckbox.checked = true;
                            }
                            if (endNextDaySelect) {
                                endNextDaySelect.value = rangeData.endNextDay;
                            }
                            toggleNextDayFields(newRange);
                        } else if (endSelect && rangeData.end) {
                            endSelect.value = rangeData.end;
                        }
                        
                        // Agregar listeners
                        const removeBtn = newRange.querySelector('.remove-range-btn');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', function() {
                                removeRange(newRange, dayElement);
                            });
                        }
                        
                        const nextDayCb = newRange.querySelector('.next-day-checkbox');
                        if (nextDayCb) {
                            nextDayCb.addEventListener('change', function() {
                                toggleNextDayFields(newRange);
                            });
                        }
                    });
                    
                    // Actualizar índices
                    updateRangeIndexes(dayElement);
                } else {
                    // No hay datos guardados para este día, usar valores por defecto
                    if (defaultDays.includes(dayName)) {
                        // Lunes a viernes: 08:00 a 16:00
                        addRangeWithValues(dayElement, dayName, '08:00', '16:00', null, null);
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
