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
if(typeof (form) != "undefined" && form != null && form.name != 'booking') {
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