$( document ).ready(function () {
    // Activar todos los tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Manejo de checkboxes para la visualización de columnas
    var todosCheckbox = $('#todos');
    var checkboxes = $('.check');
    
    if (todosCheckbox.length > 0) {
        todosCheckbox.on('change', function() {
            var isChecked = $(this).prop('checked');
            checkboxes.prop('checked', isChecked);
            toggleColumns();
        });
    }
    
    if (checkboxes.length > 0) {
        checkboxes.on('change', function() {
            toggleColumns();
            
            // Si todos los checks están seleccionados, marcar "todos"
            if (checkboxes.filter(':checked').length === checkboxes.length) {
                if (todosCheckbox.length > 0) {
                    todosCheckbox.prop('checked', true);
                }
            } else {
                if (todosCheckbox.length > 0) {
                    todosCheckbox.prop('checked', false);
                }
            }
        });
    }
    
    function toggleColumns() {
        checkboxes.each(function() {
            var column = $(this).val();
            if ($(this).is(':checked')) {
                $('.' + column).show();
            } else {
                $('.' + column).hide();
            }
        });
    }
    
    // Inicializar estado de las columnas
    if (checkboxes.length > 0) {
        toggleColumns();
    }
    
    // Activar tooltips y popovers
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();
    
    // Manejo del filtro de fechas
    var filtrarBtn = $('#filtrarPorFecha');
    if (filtrarBtn.length > 0) {
        filtrarBtn.click(function () {
            var loading = $('#loading');
            var resultados = $('#resultados');
            
            if (loading.length === 0 || resultados.length === 0) {
                console.error('Elements #loading or #resultados not found in DOM');
                return;
            }
            
            loading.show();
            resultados.hide();
            
            let from = $('#from').val();
            let to = $('#to').val();

            if (from === '' || to === '') {
                alert('Debe completar las fechas');
                loading.hide();
                return 0;
            }

            var headerFrom = new Date(from);
            var headerTo = new Date(to);

            if (headerTo < headerFrom) {
                alert('La fecha desde debe ser mayor a la fecha hasta');
                loading.hide();
                return 0;
            }
            
            let needOneMore = true;
            if (headerTo.toLocaleDateString() === headerFrom.toLocaleDateString()) {
                headerTo.setDate(headerTo.getDate()+1);
                needOneMore = false;
            }

            let html_header = '<th>Cliente</th><th style="min-width: 100px;">O. Social</th>';
            let dateIndex = 0;

            while (headerFrom.toLocaleDateString() !== headerTo.toLocaleDateString()) {
                headerFrom.setDate(headerFrom.getDate()+1);
                html_header += '<th>' + headerFrom.toLocaleDateString() + '<br><span id=totales-'+dateIndex+' class="small"></span></th>';
                dateIndex ++;
            }

            if (needOneMore) {
                headerFrom.setDate(headerFrom.getDate()+1);
                html_header += '<th>' + headerFrom.toLocaleDateString() + '<br><span id=totales-'+dateIndex+' class="small"></span></th>';
            }

            $("#head").html(html_header);

            $.post({
                url: dashboardFilterUrl,
                data: {
                    from: from,
                    to: to,
                },
                success: function (response) {
                    let html = '';
                    let fechaCheck = '';
                    let i = 0;
                    if (response.clientes) {
                        Object.keys(response.clientes).forEach(function(k, v) {
                            html += '<tr><td>'+ k +'</td>';

                            Object.keys(response.clientes[k]).forEach(function(i) {
                                html += '<td>'+ i +'</td>';
                                Object.keys(response.clientes[k][i]).forEach(function(e) {
                                    html += '<td>';
                                    if (response.clientes[k][i][e].habitacion !== undefined) {
                                        html += 'hab: '+response.clientes[k][i][e].habitacion;
                                        if (response.clientes[k][i][e].cama !== undefined) {
                                            html += ' <br> cama: '+ response.clientes[k][i][e].cama;
                                        }
                                    } else {
                                        html += " - ";
                                    }
                                    html +='</td>';
                                });
                            })
                            html += '</tr>';
                        });
                        let dateIndex = 0;
                        for (const key in response.totales) {
                            let texto = 'TOTAL PACIENTES: ' + response.totales[key];
                            $('#totales-' + dateIndex).text(texto);

                            for(const otra in response.docReferentes[key]) {
                                let esp = document.createElement("div");
                                esp.innerHTML = otra +': ' + response.docReferentes[key][otra];
                                var totalElement = document.getElementById('totales-' + dateIndex);
                                if (totalElement) {
                                    totalElement.appendChild(esp);
                                }
                            }

                            dateIndex ++;
                        }
                    }

                    $('#body').html(html);
                    loading.hide();
                    resultados.show();
                },
                error: function(e){
                    console.log(e);
                    alert("Algo salió mal al procesar su solicitud");
                    loading.hide();
                }
            });
        });
    }

    // Funcionalidad de impresión para los resultados filtrados
    var printBtn = $('#printFiltrado');
    if (printBtn.length > 0) {
        printBtn.click(function () {
            let html = '<head>' + $('head').html() + '</head>';
            html += $('#resultados').parent().html().trim();
            imprimirElemento(html);
        });
    }
});
