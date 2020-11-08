$('.predictivo').chosen();

var getParams = function (url) {
    var params = {};
    var parser = document.createElement('a');
    parser.href = url;
    var query = parser.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        params[pair[0]] = decodeURIComponent(pair[1]);
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
var cli_id = 0;
if (typeof($params.cli_id) !== "undefined") {
    cli_id = $params.cli_id;
}
var doc_id = 0;
if (typeof($params.doc_id) !== "undefined") {
    doc_id = $params.doc_id;
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

if(!window.location.href.includes('edit')) {
    document.addEventListener('DOMContentLoaded', () => {
        var calendarEl = document.getElementById('calendar-holder');

        var businessHoursA = getBussinesHours();

        if (typeof (FullCalendar) != 'undefined') {
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: es,
                navLinks: true,
                defaultView: 'dayGridMonth',
                editable: true,
                businessHours: businessHoursA,
                dateClick: function(info) {
                    info.date.setDate(info.date.getDate() + 1);

                    var hoy = new Date();
                    if( info.date <= hoy ) {

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
                            }
                        }
                    }

                },
                eventDrop: function( data) {
                    var url = data.event.url + '/' + data.event.start + '/' + data.event.end;

                    $.ajax({
                        url: url,
                        success: function (data) {
                            if (data != 'ok') {
                                alert('error: ' + data);
                            }
                        }
                    });
                },
                eventSources: [
                    {
                        url: eventSourceUrl,
                        method: "POST",
                        extraParams: {
                            filters: JSON.stringify({
                                doctor_id: doc_id,
                                cliente_id: cli_id,
                                ctr: ctr,
                            })
                        },
                        failure: () => {
                            // alert("There was an error while fetching FullCalendar!");
                        },
                    },
                ],
                customButtons: {
                    myCustomButton: {
                        text: 'Filtros',
                        //icon: 'fc-icon-filter',
                        click: function() {
                            $('.filtros').modal('show');
                        }
                    }
                },
                header: {
                    left: 'prev,next today, myCustomButton',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
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
            $.each(JSON.parse($params.doc_id), function(e, k) {
                $('#doctor-'+k).prop('checked', true);
            })
        }

        if(typeof($params.cli_id) != "undefined") {
            $.each(JSON.parse($params.cli_id), function (e, k) {
                $('#cliente-' + k).prop('checked', true);
            })
        }

        if(typeof($params.ctr) != "undefined") {
            var id = '#' + $params.ctr.replaceAll(' ', '');
            $(id).prop('selected', true);
            $('.filtros').modal('show');
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

