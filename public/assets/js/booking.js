$('.predictivo').chosen();

var getParams = function (url) {
    var params = {};
    var parser = document.createElement('a');
    parser.href = url;
    var query = parser.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        var key = pair[0];
        var value = decodeURIComponent(pair[1] || '');
        
        // Manejar arrays en la URL (ej: doc_id[0]=151)
        var arrayMatch = key.match(/^(.+)\[(\d+)\]$/);
        if (arrayMatch) {
            var arrayKey = arrayMatch[1];
            var arrayIndex = parseInt(arrayMatch[2]);
            if (!params[arrayKey] || !Array.isArray(params[arrayKey])) {
                params[arrayKey] = [];
            }
            // Convertir a número si es posible
            var numValue = parseInt(value);
            params[arrayKey][arrayIndex] = (!isNaN(numValue) && numValue.toString() === value) ? numValue : value;
        } else {
            // Si la clave ya existe y es un array, agregar al array
            if (params[key] !== undefined && Array.isArray(params[key])) {
                var numValue = parseInt(value);
                params[key].push((!isNaN(numValue) && numValue.toString() === value) ? numValue : value);
            } else if (params[key] !== undefined && !Array.isArray(params[key])) {
                // Convertir a array si ya existe un valor
                var numValue1 = parseInt(params[key]);
                var numValue2 = parseInt(value);
                params[key] = [
                    (!isNaN(numValue1) && numValue1.toString() === params[key]) ? numValue1 : params[key],
                    (!isNaN(numValue2) && numValue2.toString() === value) ? numValue2 : value
                ];
            } else {
                // Nuevo valor
                var numValue = parseInt(value);
                params[key] = (!isNaN(numValue) && numValue.toString() === value) ? numValue : value;
            }
        }
    }
    // Limpiar arrays para asegurar que no tengan huecos
    for (var key in params) {
        if (Array.isArray(params[key])) {
            params[key] = params[key].filter(function(item) { return item !== undefined && item !== null; });
        }
    }
    return params;
};

var calendar = '';
var es = {
    code: "es",
    week: {
        dow: 1, // Monday is the first day of the week.
        doy: 4  // The week that contains Jan 4th is the first week of the year.
    },
    buttonText: {
        prev: "Ant",
        next: "Sig",
        today: "Hoy",
        month: "Mes",
        week: "Semana",
        day: "Día",
        list: "Agenda"
    },
    weekText: "Sm",
    allDayText: "Todo el día",
    moreLinkText: "más",
    noEventsText: "No hay eventos para mostrar"
};

$params = getParams(window.location.href);
var cli_id = [];
if (typeof($params.cli_id) !== "undefined") {
    // Si viene como string separado por comas o como array, convertir a array
    if (Array.isArray($params.cli_id)) {
        cli_id = $params.cli_id;
    } else if (typeof $params.cli_id === 'string' && $params.cli_id.includes(',')) {
        cli_id = $params.cli_id.split(',').map(function(id) { return parseInt(id); }).filter(function(id) { return !isNaN(id); });
    } else {
        var parsedId = parseInt($params.cli_id);
        if (!isNaN(parsedId) && parsedId > 0) {
            cli_id = [parsedId];
        }
    }
}
var doc_id = [];
if (typeof($params.doc_id) !== "undefined") {
    // Si viene como string separado por comas o como array, convertir a array
    if (Array.isArray($params.doc_id)) {
        doc_id = $params.doc_id;
    } else if (typeof $params.doc_id === 'string' && $params.doc_id.includes(',')) {
        doc_id = $params.doc_id.split(',').map(function(id) { return parseInt(id); }).filter(function(id) { return !isNaN(id); });
    } else {
        var parsedId = parseInt($params.doc_id);
        if (!isNaN(parsedId) && parsedId > 0) {
            doc_id = [parsedId];
        }
    }
}

var ctr = 0;
if (typeof($params.ctr) !== "undefined") {
    ctr = $params.ctr;
}

function getBussinesHours() {
    var businessHoursA = [];
    if (typeof (businessHoursJson) != 'undefined' && businessHours.length > 2) {
        if(typeof (businessHoursJson.lunes) != 'undefined') {
            businessHoursA.push(    {
                    daysOfWeek: [1], // Lunes
                    startTime: businessHoursJson.lunes.desde, // 8am
                    endTime: businessHoursJson.lunes.hasta // 6pm
                },
                {
                    daysOfWeek: [1], // Lunes
                    startTime: businessHoursJson.lunes.ydesde, // 8am
                    endTime: businessHoursJson.lunes.yhasta // 6pm
                });
        }
        if(typeof (businessHoursJson.martes) != 'undefined') {
            businessHoursA.push({
                    daysOfWeek: [2], // Martes
                    startTime: businessHoursJson.martes.desde, // 10am
                    endTime: businessHoursJson.martes.hasta,// 4pm
                },
                {
                    daysOfWeek: [2], // Martes
                    startTime: businessHoursJson.martes.ydesde, // 10am
                    endTime: businessHoursJson.martes.yhasta,// 4pm
                },);
        }
        if(typeof (businessHoursJson.miercoles) != 'undefined') {
            businessHoursA.push({
                    daysOfWeek: [3], // Miercoles
                    startTime: businessHoursJson.miercoles.desde, // 10am
                    endTime: businessHoursJson.miercoles.hasta,// 4pm
                },
                {
                    daysOfWeek: [3], // Miercoles
                    startTime: businessHoursJson.miercoles.ydesde, // 10am
                    endTime: businessHoursJson.miercoles.yhasta,// 4pm
                },)
        }
        if(typeof (businessHoursJson.jueves) != 'undefined') {
            businessHoursA.push({
                    daysOfWeek: [4], // Jueves
                    startTime: businessHoursJson.jueves.desde, // 10am
                    endTime: businessHoursJson.jueves.hasta,// 4pm
                },
                {
                    daysOfWeek: [4], // Jueves
                    startTime: businessHoursJson.jueves.ydesde, // 10am
                    endTime: businessHoursJson.jueves.yhasta,// 4pm
                },);
        }
        if(typeof (businessHoursJson.viernes) != 'undefined') {
            businessHoursA.push({
                    daysOfWeek: [5], // Viernes
                    startTime: businessHoursJson.viernes.desde, // 10am
                    endTime: businessHoursJson.viernes.hasta,// 4pm
                },
                {
                    daysOfWeek: [5], // Viernes
                    startTime: businessHoursJson.viernes.ydesde, // 10am
                    endTime: businessHoursJson.viernes.yhasta,// 4pm
                },);
        }
        if(typeof (businessHoursJson.sabado) != 'undefined') {
            businessHoursA.push({
                    daysOfWeek: [6], // Sábado
                    startTime: businessHoursJson.sabado.desde, // 10am
                    endTime: businessHoursJson.sabado.hasta,// 4pm
                },
                {
                    daysOfWeek: [6], // Sábado
                    startTime: businessHoursJson.sabado.ydesde, // 10am
                    endTime: businessHoursJson.sabado.yhasta,// 4pm
                })
        }
    } else {
        if(!window.location.href.includes('booking/new')) {
            alert('No hay horarios disponibles para este grupo de profesionales');
        }

    }

    return businessHoursA;
}

if(!window.location.href.includes('edit') && !window.location.href.includes('new')) {
    document.addEventListener('DOMContentLoaded', () => {
        var calendarEl = document.getElementById('calendar-holder');

        var businessHoursA = getBussinesHours();

        if (typeof (FullCalendar) != 'undefined') {
            // Permitir editar si es admin o si es doctor (para sus propios turnos)
            var canEditEvents = typeof(canEditEvents) !== 'undefined' ? canEditEvents : (typeof(canManageAgenda) !== 'undefined' ? canManageAgenda : true);
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: es,
                navLinks: true,
                defaultView: 'dayGridMonth',
                editable: canEditEvents,
                businessHours: businessHoursA,
                dateClick: function(info) {
                    // Verificar permiso antes de permitir crear turno
                    if (typeof(canManageAgenda) !== 'undefined' && !canManageAgenda) {
                        // No hacer nada, simplemente retornar sin mostrar alert ni redirigir
                        return false;
                    }
                    
                    info.date.setDate(info.date.getDate() + 1);

                    var hoy = new Date();
                    if( info.date <= hoy ) {
                        alert('No se puede crear un turno para una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.');
                        return false;
                    } else {
                        if (info.view.type === 'dayGridMonth') {
                            calendar.changeView('timeGridDay');
                            calendar.gotoDate(info.dateStr);
                        } else {
                            url = url.replace("info.dateStr", info.dateStr);
                            hoy = new Date();
                            click = new Date(info.dateStr);
                            click.setHours(click.getHours() + 3)
                            if(Date.parse(click) > Date.parse(hoy)) {
                                window.location.href = url+'&ctr='+ctr;
                            } else {
                                alert('No se puede crear un turno para una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.');
                            }
                        }
                    }
                    return false;
                },
                eventDrop: function( data) {
                    //booking_edit_ajax
                    var url = data.event.url + '/' + data.event.start + '/' + data.event.end;

                    $.ajax({
                        url: url,
                        success: function (response) {
                            if (response.error) {
                                alert('error: ' + response.message);
                                data.revert();
                            } else {
                                alert('Turno guardado correctamente');
                            }
                        }
                    });
                },
                eventSources: [
                    {
                        url: eventSourceUrl,
                        method: "POST",
                        extraParams: {
                            filters: JSON.stringify((function() {
                                var filters = {};
                                if (doc_id && doc_id.length > 0) {
                                    filters.doctor_id = doc_id;
                                }
                                if (cli_id && cli_id.length > 0) {
                                    filters.cliente_id = cli_id;
                                }
                                if (ctr && ctr != 0) {
                                    filters.ctr = ctr;
                                }
                                return filters;
                            })())
                        },
                        failure: () => {
                            // alert("There was an error while fetching FullCalendar!");
                        },
                    },
                ],
                customButtons: (function() {
                    var buttons = {
                        filtros: {
                            text: 'Filtros',
                            //icon: 'fc-icon-filter',
                            click: function() {
                                $('.filtros').modal('show');
                            }
                        }
                    };
                    
                    // Solo agregar el botón "Ver Todos" si el usuario tiene permisos
                    if (typeof(canManageAgenda) !== 'undefined' && canManageAgenda) {
                        buttons.ver = {
                            text: 'Ver Todos',
                            //icon: 'fc-icon-filter',
                            click: function() {
                                location.href = '/booking/';
                            }
                        };
                    }
                    
                    return buttons;
                })(),
                header: (function() {
                    var leftButtons = 'prev,next today, filtros';
                    // Solo agregar "ver" si el usuario tiene permisos
                    if (typeof(canManageAgenda) !== 'undefined' && canManageAgenda) {
                        leftButtons += ', ver';
                    }
                    return {
                        left: leftButtons,
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                    };
                })(),
                plugins: [ 'interaction', 'dayGrid', 'timeGrid' ], // https://fullcalendar.io/docs/plugin-index
                timeZone: 'UTC',
                rrule: {
                    freq: 'weekly',
                    interval: 5,
                    byweekday: [ 'mo', 'fr' ],
                    dtstart: '2020-11-01T10:30:00', // will also accept '20120201T103000'
                    until: '2020-11-15' // will also accept '20120201'
                }
            });
            calendar.render();
        }


        var hoy = new Date();
        $('.fc-day').each(function() {
            var fecha = new Date($( this ).data('date'));
            fecha.setDate(fecha.getDate() + 1)
            if(fecha < hoy) {
                $(this).addClass('disable');
            }

        });

        if(typeof($params.doc_id) != "undefined") {
            var docIds = Array.isArray($params.doc_id) ? $params.doc_id : [$params.doc_id];
            $.each(docIds, function(e, k) {
                var docId = parseInt(k);
                if (!isNaN(docId)) {
                    $('#doctor-'+docId).prop('checked', true);
                }
            })
        }

        if(typeof($params.cli_id) != "undefined") {
            var cliIds = Array.isArray($params.cli_id) ? $params.cli_id : [$params.cli_id];
            $.each(cliIds, function (e, k) {
                var cliId = parseInt(k);
                if (!isNaN(cliId)) {
                    $('#cliente-' + cliId).prop('checked', true);
                }
            })
        }

        if(typeof($params.ctr) != "undefined" && $params.ctr && $params.ctr !== '') {
            var ctrValue = String($params.ctr).replaceAll(' ', '');
            if (ctrValue) {
                var id = '#' + ctrValue;
                var $element = $(id);
                if ($element.length > 0) {
                    $element.prop('selected', true);
                }
            }
            if(typeof($params.cli_id) !== "undefined" && typeof($params.doc_id) == "undefined") {
                $('.filtros').modal('show');
            }
        }
    });
}


$('#limpiar').click(function () {
    $('.filtrosModal').find('input[type=checkbox]').prop('checked', false);
    location.href = 'calendar';
});

$('#filtrar').click(function () {
    var doctoresId = $('#doctores').val();
    var clientesId = $('#cliente').val();
    var contrato = $('#contrato').val();

    var url = 'calendar?'
    if (doctoresId.length > 0) {
        url += 'doc_id=[' + doctoresId + ']';
    }
    if (clientesId.length > 0) {
        if (url.includes('doc_id')) {
            url += '&';
        }
        url += 'cli_id=['+clientesId+']';
    }
    if ( contrato.length > 0 ) {
        if (url.includes('doc_id') || url.includes('cli_id')) {
            url += '&';
        }
        url += 'ctr='+contrato+'';
    }

    if(doctoresId.length > 0 || clientesId.length > 0) {
        set_cookie('doctoresId', doctoresId);
        set_cookie('clientesId', clientesId);
        set_cookie('contrato', contrato);
    }
    if(url.includes('doc_id') || url.includes('cli_id')) {
        location.href = url;
    }
});

$( ".filtros" ).on('shown.bs.modal', function(){
    $('.filtrosModal').show();
    $('.filtros .form-control').chosen();
    $('.loading').hide();
});

$( "#contrato" ).on('change', function(){
    location.href = 'calendar?ctr=' + $(this).val();
});

$('#booking_dias input').on('change', function() {
    let comienso = $('#booking_beginAt').val();
    let desde = $('#booking_desde').val();
    let comiensoDia = new Date(comienso).getDay();
    let clickOn = this.value;

    $.each($('#booking_dias').find('input'), function(e, a) {
        if((clickOn != comiensoDia || $('#booking_dias input[type="checkbox"]:checked').length >= 1) && a.value == comiensoDia && !a.checked) {
            alert('Recuerda que la repetición debe comenzar en el mismo día del primer turno');
            a.checked = true;
        }
    })

    if($('#booking_dias input[type="checkbox"]:checked').length > 0 && desde == '') {
        $('#booking_desde').val(comienso.substring(0, 10));
        $('#booking_desde').attr("min", comienso.substring(0, 10));
        $('#booking_hasta').attr("min", comienso.substring(0, 10));

    } else if ($('#booking_dias input[type="checkbox"]:checked').length == 0) {
        $('#booking_desde').val('');
    }

})

// Establecer fecha mínima para los campos de fecha/hora al cargar la página
$(document).ready(function() {
    if ($('#booking_beginAt').length) {
        var ahora = new Date();
        ahora.setMinutes(ahora.getMinutes() + 1); // Agregar 1 minuto para evitar problemas de tiempo
        var fechaMinima = ahora.toISOString().slice(0, 16);
        $('#booking_beginAt').attr('min', fechaMinima);
        $('#booking_endAt').attr('min', fechaMinima);
    }
});

$('#booking_desde').on('change', function () {
    $('#booking_hasta').attr("min", $(this).val());
});

$('#booking_beginAt').on('change', function () {
    var beginAtString = $('#booking_beginAt').val();

    // Validar que la fecha/hora seleccionada no sea anterior a la actual
    var fechaInicio = new Date(beginAtString);
    var ahora = new Date();
    if (fechaInicio <= ahora) {
        alert('No se puede crear un turno para una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.');
        // Establecer la fecha/hora mínima como ahora + 1 minuto
        var fechaMinima = new Date(ahora);
        fechaMinima.setMinutes(fechaMinima.getMinutes() + 1);
        var ye = new Intl.DateTimeFormat('es', { year: 'numeric' }).format(fechaMinima);
        var mo = new Intl.DateTimeFormat('es', { month: '2-digit' }).format(fechaMinima);
        var da = new Intl.DateTimeFormat('es', { day: '2-digit' }).format(fechaMinima);
        var hr = new Intl.DateTimeFormat('es', { hour: '2-digit', hour12: false }).format(fechaMinima);
        var min = Intl.DateTimeFormat('en-US', { minute: '2-digit', second: '2-digit', hour12: false }).format(fechaMinima);
        var fechaMinimaString = (`${ye}-${mo}-${da}T${hr}:${min}`);
        $('#booking_beginAt').val(fechaMinimaString);
        beginAtString = fechaMinimaString;
    }

    var newDateEndAt = new Date(beginAtString);

    newDateEndAt.setMinutes(newDateEndAt.getMinutes() + 30);

    var ye = new Intl.DateTimeFormat('es', { year: 'numeric' }).format(newDateEndAt);
    var mo = new Intl.DateTimeFormat('es', { month: '2-digit' }).format(newDateEndAt);
    var da = new Intl.DateTimeFormat('es', { day: '2-digit' }).format(newDateEndAt);
    var hr = new Intl.DateTimeFormat('es', { hour: '2-digit', hour12: false }).format(newDateEndAt);
    var min = Intl.DateTimeFormat('en-US', { minute: '2-digit', second: '2-digit', hour12: false }).format(newDateEndAt);

    var newDateEndAtString = (`${ye}-${mo}-${da}T${hr}:${min}`);

    $('#booking_endAt').val(newDateEndAtString);


    $.each($('#booking_dias').find('input'), function(e, a) {
        a.checked = false;
    })
});

var form = document.querySelector('form');

if(typeof (form) != "undefined" && form != null) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let desde = $('#booking_desde').val();
        let hasta = $('#booking_hasta').val();
        let comienso = $('#booking_beginAt').val();
        
        // Validar que el campo beginAt tenga un valor
        if (!comienso || comienso.trim() === '') {
            alert('El campo "Comienza" es obligatorio. Por favor, seleccione una fecha y hora.');
            $('form button:submit').prop('disabled', false);
            return false;
        }
        
        let comiensoFecha = comienso.substring(0, 10);
        let hayDiasChequeados = false;
        let todoOk = true;

        // Validar que la fecha/hora de inicio no sea anterior a la actual
        let ahora = new Date();
        let fechaInicio = new Date(comienso);
        if (isNaN(fechaInicio.getTime())) {
            todoOk = false;
            alert('La fecha/hora de inicio no es válida. Por favor, verifique el formato.');
            setTimeout(function () {
                $('form button:submit').prop('disabled', false);
            }, 300);
            return false;
        }
        if (fechaInicio <= ahora) {
            todoOk = false;
            alert('No se puede crear un turno para una fecha/hora anterior a la actual. Por favor, seleccione una fecha y hora futura.');
            setTimeout(function () {
                $('form button:submit').prop('disabled', false);
            }, 300);
            return false;
        }

        $.each($('#booking_dias').find('input'), function(e, a) {
            if(a.checked) {
                hayDiasChequeados = true;
            }
        })

        if(hayDiasChequeados) {
            if (desde === '' || hasta === '') {
                todoOk = false;
                alert('Debe completar los campos Desde y Hasta cuando selecciona un día en el que se repite el evento');
            } else if (Date.parse(comiensoFecha) > Date.parse(desde)) {
                todoOk = false;
                alert('El comienzo del turno no puede ser posterior al campo Desde');
            } else if (Date.parse(desde) > Date.parse(hasta)) {
                todoOk = false;
                alert('El campo Desde debe ser anterior al campo Hasta');
            }
            setTimeout(function () {
                $('form button:submit').prop('disabled', false);
            }, 300);
        }

        if(todoOk) {
            $(this).submit();
        }


    }, false);
}
