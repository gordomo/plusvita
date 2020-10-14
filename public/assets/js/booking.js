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


document.addEventListener('DOMContentLoaded', () => {
    var calendarEl = document.getElementById('calendar-holder');

    calendar = new FullCalendar.Calendar(calendarEl, {
        locale: es,
        navLinks: true,
        defaultView: 'dayGridMonth',
        editable: true,
        dateClick: function(info) {
            info.date.setDate(info.date.getDate() + 1);
            console.log(info.date);
            var hoy = new Date();
            if( info.date <= hoy ) {

            } else {
                if (info.view.type === 'dayGridMonth') {
                    calendar.changeView('timeGridDay');
                    calendar.gotoDate(info.dateStr);
                } else {
                    url = url.replace("info.dateStr", info.dateStr);
                    window.location.href = url;
                }
            }

        },
        eventSources: [
            {
                url: eventSourceUrl,
                method: "POST",
                extraParams: {
                    filters: JSON.stringify({
                        doctor_id: doc_id,
                        cliente_id: cli_id
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
    });
    calendar.render();

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
});

$('#limpiar').click(function () {
    $('.filtrosModal').find('input[type=checkbox]').prop('checked', false);
    location.href = 'calendar';
});

$('#filtrar').click(function () {
    var doctoresId = [];
    $('.filtrosModal').find("input:checkbox[name=doctores]:checked").each(function(){
        doctoresId.push($(this).val());
    });

    var clientesId = [];
    $('.filtrosModal').find("input:checkbox[name=clientes]:checked").each(function(){
        clientesId.push($(this).val());
    });

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

