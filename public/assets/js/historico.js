window.onscroll = function() {myFunction()};

function myFunction() {
    if (document.body.scrollTop > 800 ) {
        $('.fechas').addClass('dateHeaders'); 
    } else {
        $('.fechas').removeClass('dateHeaders'); 
    }
  }