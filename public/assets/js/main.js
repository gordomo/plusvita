document.addEventListener("DOMContentLoaded", function () {
    // Añadir un registro para verificar que main.js se está cargando
    console.log("main.js loaded");
    
    // Validaciones de campos vacíos en inputs
    var elements = document.getElementsByTagName("INPUT");
    for (var i = 0; i < elements.length; i++) {
        elements[i].oninvalid = function (e) {
            e.target.setCustomValidity("");
            if (!e.target.validity.valid) {
                e.target.setCustomValidity("This field cannot be left blank");
            }
        };
        elements[i].oninput = function (e) {
            e.target.setCustomValidity("");
        };
    }

    // Esperar a que jQuery esté disponible
    waitForjQuery(initjQueryScripts);
});

function waitForjQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        $(document).ready(callback);
    } else {
        setTimeout(function () {
            waitForjQuery(callback);
        }, 50);
    }
}

function initjQueryScripts() {
    // Tooltips
    if ($('[data-toggle="tooltip"]').length > 0) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Toggle menú
    $("#menu-toggle").click(function (e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });

    // Validación de formularios
    var form = document.querySelector('form');
    if (form && form.name !== 'booking' && form.name !== 'filtros') {
        form.addEventListener('submit', function (event) {
            if ($(form).find('.is-invalid').length) {
                $(form).find('.is-invalid').focus();
                event.preventDefault();
            } else {
                $('form button:submit').attr('disabled', 'disabled');
            }
        }, false);
    }

    // Búsqueda por nombre
    $('#buscarPorNombre').click(function () {
        var nombreInput = $('#nombreInput').val();
        var url = $(this).data('url');
        var inactivo = $(this).data('inactivo');
        url += (inactivo ? '&nombreInput=' : '?nombreInput=') + nombreInput;
        window.location.href = url;
    });

    var input = document.getElementById("nombreInput");
    if (input) {
        input.addEventListener("keyup", function (event) {
            var code = event.key || event.keyCode;
            if (code === 13 || code === 'Enter') {
                event.preventDefault();
                document.getElementById("buscarPorNombre").click();
            }
        });
    }

    $('#todos').on('click', function () {
        $('.check').prop('checked', $(this).prop("checked"));
    });

    $('.check').on('click', function () {
        if (!$(this).prop("checked")) {
            $('#todos').prop("checked", false);
        }
    });

    $('#todosForView').on('click', function () {
        $('.checkForView').prop('checked', $(this).prop("checked"));
    });

    $('.checkForView').on('click', function () {
        if (!$(this).prop("checked")) {
            $('#todosForView').prop("checked", false);
        }
    });

    $("#updateCamposForView").on('click', function () {
        let checkboxes = GetSelected('checkboxesForView');
        $('.table th, .table td').hide();
        $(checkboxes).each(function (e, a) {
            $('.' + a).show();
        });
        $('.acciones').show();
        $('#camposExtras').modal('hide');
    });

    $("#imprimir").on('click', function () {
        $('.filtrosPlanilla').modal('show');
    });

    $("#update").on('click', function () {
        let checkboxes = GetSelected('checkboxes');
        if (checkboxes.length < 1) {
            alert('seleccione al menos un campo');
        } else {
            let htmlToPrint = getHtmlToPrint(checkboxes, true);
            imprimirElemento(htmlToPrint);
            $('.filtrosPlanilla').modal('hide');
            location.reload();
        }
    });

    $("#descargarExcel").on('click', function () {
        let checkboxes = GetSelected('checkboxes');
        if (checkboxes.length < 1) {
            alert('seleccione al menos un campo');
        } else {
            $(this).attr('disabled', true);
            let htmlToPrint = getHtmlToPrint(checkboxes, false);
            $.post({
                url: '/dashboard/excel',
                data: {
                    html: htmlToPrint,
                    tituloExcel: typeof (tituloExcel) != 'undefined' ? tituloExcel : 'default.xlsx',
                },
                success: function (response) {
                    $('#descargarExcel').attr('disabled', false);
                    window.open(response.message);
                    location.reload();
                },
                error: function (e) {
                    console.log(e);
                    alert("download failed");
                    $('#descargarExcel').attr('disabled', false);
                }
            });
        }
    });

    $.extend({
        form: function (url, data, method) {
            if (method == null) method = 'POST';
            if (data == null) data = {};

            var form = $('<form>').attr({
                method: method,
                action: url
            }).css({
                display: 'none'
            });

            var addData = function (name, data) {
                if ($.isArray(data)) {
                    for (var i = 0; i < data.length; i++) {
                        addData(name + '[]', data[i]);
                    }
                } else if (typeof data === 'object') {
                    for (var key in data) {
                        if (data.hasOwnProperty(key)) {
                            addData(name + '[' + key + ']', data[key]);
                        }
                    }
                } else if (data != null) {
                    form.append($('<input>').attr({
                        type: 'hidden',
                        name: String(name),
                        value: String(data)
                    }));
                }
            };

            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    addData(key, data[key]);
                }
            }

            return form.appendTo('body');
        }
    });
}

// Funciones auxiliares
function GetSelected(id) {
    var selected = [];
    var checkboxes = document.getElementById(id);
    var chks = checkboxes.getElementsByTagName("INPUT");
    for (var i = 0; i < chks.length; i++) {
        if (chks[i].checked) {
            selected.push(chks[i].value);
        }
    }
    return selected;
}

// Función mejorada para imprimir que expande todas las novedades
function getHtmlToPrint(checkboxes, conHead) {
    // Marcar los elementos seleccionados para conservar
    $(checkboxes).each(function (e, a) {
        $('.' + a).css('display', 'table-cell').addClass('notRemove');
    });
    
    // Expandir todas las secciones ocultas de novedades antes de imprimir
    $('.pacientes-prescripciones').css('display', 'block');
    
    // Mostrar todas las filas ocultas
    $('tr[style*="display:none"]').css('display', 'table-row');
    
    // Eliminar elementos no seleccionados
    $('.table td').not('.notRemove').remove();
    $('.table th').not('.notRemove').remove();
    $('.remover').remove();
    $('.collapse').addClass('show');
    
    // Añadir fecha actual
    let currentDate = new Date().toLocaleString();
    $('.printiable').attr('data-date', currentDate);
    
    let htmlToPrint = '';

    if (conHead) {
        htmlToPrint = '<head>' + $('head').html() + '</head>';
        htmlToPrint += '<body class="printing">';
        htmlToPrint += '<div class="print-header">';
        htmlToPrint += '<h3>Histórico de Cliente</h3>';
        htmlToPrint += '<p>Fecha: ' + currentDate + '</p>';
        htmlToPrint += '</div>';
        htmlToPrint += $('.title').html() + "<br>";
        htmlToPrint += $('.printiable').html();
        htmlToPrint += '</body>';
    } else {
        htmlToPrint += $('.title').html() + "<br><table>" + $('.printiable .table').html() + "</table>";
    }

    return htmlToPrint;
}

function imprimirElemento(htmlToPrint) {
    var ventana = window.open('', 'PRINT');
    ventana.document.write(htmlToPrint);
    
    // Añadir evento para expandir las secciones de novedades al imprimir
    ventana.document.write(`
        <script>
            window.onload = function() {
                // Expandir todas las secciones ocultas cuando la página se carga para imprimir
                var elements = document.querySelectorAll('tr[style*="display:none"]');
                for (var i = 0; i < elements.length; i++) {
                    elements[i].style.display = 'table-row';
                }
                
                // Expandir todas las secciones de novedades
                var novedades = document.querySelectorAll('.pacientes-prescripciones');
                for (var i = 0; i < novedades.length; i++) {
                    novedades[i].style.display = 'block';
                }
                
                setTimeout(function() {
                    window.print();
                    window.close();
                }, 500);
            };
        </script>
    `);
    
    ventana.document.close();
    ventana.focus();
    
    return true;
}

// Añadir un evento específico para imprimir el histórico de clientes
$(document).ready(function() {
    // Verificar si estamos en la página de histórico
    if ($('.pacientes-prescripciones-head').length > 0) {
        // Botón de impresión directo para historias de pacientes
        $("#imprimirHistorico").on('click', function() {
            // Preparar la página para imprimir directamente sin modal
            prepararHistoricoParaImprimir();
            window.print();
            return false;
        });
        
        // Agregar evento para la impresión de la página
        window.addEventListener('beforeprint', function() {
            prepararHistoricoParaImprimir();
        });
        
        // Restaurar estado después de imprimir
        window.addEventListener('afterprint', function() {
            // Restaurar el estado anterior
            $('.pacientes-prescripciones').each(function() {
                if (!$(this).hasClass('showing-before-print')) {
                    $(this).hide();
                }
                $(this).removeClass('showing-before-print');
            });
        });
    }
});

// Función para preparar el histórico para imprimir
function prepararHistoricoParaImprimir() {
    // Marcar las secciones que ya estaban expandidas
    $('.pacientes-prescripciones:visible').addClass('showing-before-print');
    
    // Expandir todas las secciones de novedades antes de imprimir
    $('.pacientes-prescripciones').show();
    
    // Mostrar todas las filas ocultas
    $('tr[style*="display:none"]').show();
    
    // Añadir fecha actual
    let currentDate = new Date().toLocaleString();
    $('.printiable').attr('data-date', currentDate);
}

function validateEmail(email) {
    const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function file_get_contents(uri, callback) {
    return fetch(uri);
}

function serialize(obj) {
    var str = [];
    for (var p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

function getCookie(name) {
    var pattern = RegExp(name + "=.[^;]*")
    var matched = document.cookie.match(pattern)
    if (matched) {
        var cookie = matched[0].split('=')
        return cookie[1]
    }
    return false
}

function set_cookie(name, value) {
    document.cookie = name + '=' + value;
}

function delete_cookie(name) {
    document.cookie = name + '=;';
}
