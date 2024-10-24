document.addEventListener("DOMContentLoaded", function() {
    var elements = document.getElementsByTagName("INPUT");
    for (var i = 0; i < elements.length; i++) {
        elements[i].oninvalid = function(e) {
            e.target.setCustomValidity("");
            if (!e.target.validity.valid) {
                e.target.setCustomValidity("This field cannot be left blank");
            }
        };
        elements[i].oninput = function(e) {
            e.target.setCustomValidity("");
        };
    }
})

$("#menu-toggle").click(function(e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});

var form = document.querySelector('form');
if(typeof (form) != "undefined" && form != null && form.name != 'booking' && form.name != 'filtros') {
    form.addEventListener('submit', function(event) {
        if($(form).find('.is-invalid').length) {
            $(form).find('.is-invalid').focus();
            event.preventDefault();
        } else {
            $('form button:submit').attr('disabled', 'disabled');
        }
    }, false);
}

$('#buscarPorNombre').click( function () {
    var nombreInput = $('#nombreInput').val();
    var url = $(this).data('url');
    var inactivo = $(this).data('inactivo');
    if (inactivo) {
        url += '&nombreInput=';
    } else {
        url += '?nombreInput=';
    }
    url += nombreInput;
    window.location.href = url;
});

// Get the input field
var input = document.getElementById("nombreInput");
if(input) {
    // Execute a function when the user releases a key on the keyboard
    input.addEventListener("keyup", function(event) {
        // Number 13 is the "Enter" key on the keyboard
        $code = 0;
        if (event.key !== undefined) {
            $code = event.key;
        } else if (event.keyIdentifier !== undefined) {
            $code = event.keyIdentifier;
        } else if (event.keyCode !== undefined) {
            $code = event.keyCode;
        }
        if ($code === 13 || $code == 'Enter') {
            // Cancel the default action, if needed
            event.preventDefault();
            // Trigger the button element with a click
            document.getElementById("buscarPorNombre").click();
        }
    });
}

function validateEmail(email) {
    const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function imprimirElemento(htmlToPrint){
    var ventana = window.open('', 'PRINT');
    ventana.document.write(htmlToPrint);
    ventana.document.close();
    ventana.focus();
    ventana.print();

    return true;
}

$('#todos').on('click',function(){
    $('.check').prop('checked',$(this).prop("checked"));
});

$('.check').on('click', function() {
    if(!$(this).prop("checked")) {
        $('#todos').prop("checked", false);
    }
});

function GetSelected(id) {
    //Create an Array.
    var selected = new Array();

    //Reference the Table.
    var checkboxes = document.getElementById(id);

    //Reference all the CheckBoxes in Table.
    var chks = checkboxes.getElementsByTagName("INPUT");

    // Loop and push the checked CheckBox value in Array.
    for (var i = 0; i < chks.length; i++) {
        if (chks[i].checked) {
            selected.push(chks[i].value);
        }
    }

    return selected;
};

function getHtmlToPrint(checkboxes, conHead) {
    $(checkboxes).each(function (e, a) {
        $('.'+a).css('display', 'table-cell');
        $('.'+a).addClass('notRemove');
    });
    $('.table td').not('.notRemove').remove();
    $('.table th').not('.notRemove').remove();
    $('.remover').remove();
    $('.collapse').addClass('show');
    let htmlToPrint = '';

    if ( conHead ) {
        htmlToPrint = '<head>' + $('head').html() + '</head>';
        htmlToPrint += $('.title').html() + "<br>";
        htmlToPrint += $('.printiable').html();
    } else {
        htmlToPrint += $('.title').html() + "<br>";
        htmlToPrint += "<table>";
        htmlToPrint += $('.printiable .table').html();
        htmlToPrint += "</table>";
    }

    

    return htmlToPrint;
}

$("#imprimir").on('click', function () {
    $('.filtrosPlanilla').modal('show');
});

$("#update").on('click', function () {
    let checkboxes = GetSelected('checkboxes');
    if (checkboxes.length < 1) {
        alert('seleccione al menos un campo')
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
        alert('seleccione al menos un campo')
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
            error: function(e){
                console.log(e);
                alert("download failed");
                $('#descargarExcel').attr('disabled', false);
            }
        });
    }
});

serialize = function(obj) {
    var str = [];
    for (var p in obj)
        if (obj.hasOwnProperty(p)) {
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        }
    return str.join("&");
}

$('#todosForView').on('click',function(){
    $('.checkForView').prop('checked',$(this).prop("checked"));
});

$('.checkForView').on('click', function() {
    if(!$(this).prop("checked")) {
        $('#todosForView').prop("checked", false);
    }
});

$("#updateCamposForView").on('click', function () {
    let checkboxes = GetSelected('checkboxesForView');
    $('.table th').hide();
    $('.table td').hide();

    $(checkboxes).each(function (e, a) {
        $('.'+a).show();
    });

    $('.acciones').show();

    $('#camposExtras').modal('hide');

});

function file_get_contents(uri, callback) {
    return(fetch(uri));
}

function getCookie(name){
    var pattern = RegExp(name + "=.[^;]*")
    var matched = document.cookie.match(pattern)
    if(matched){
        var cookie = matched[0].split('=')
        return cookie[1]
    }
    return false
}
function set_cookie(name, value) {
    document.cookie = name +'='+ value;
}
function delete_cookie(name) {
    document.cookie = name +'=;';
}

jQuery(function($) { $.extend({
    form: function(url, data, method) {
        if (method == null) method = 'POST';
        if (data == null) data = {};

        var form = $('<form>').attr({
            method: method,
            action: url
        }).css({
            display: 'none'
        });

        var addData = function(name, data) {
            if ($.isArray(data)) {
                for (var i = 0; i < data.length; i++) {
                    var value = data[i];
                    addData(name + '[]', value);
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
}); });