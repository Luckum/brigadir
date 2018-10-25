// $( window ).resize(function() {
//   location.reload();
// });

$('.ul-mainmenu li ul').fadeIn()
$('.ul-mainmenu li ul').hide()

	var timer = null;
	var menuTimeout = 2000;
	var menuMain = $('div.head > ul');
	$(' > li', menuMain).hover(function() {
		
		if (obj) {
			obj.find('ul').hide();
			obj = null;
		} //if

		
		$(this).find('ul').show();
		
		var items = $(this).find('li');
		
		lenghtpx = 0;
		for (i = 0; i < items.length; i++) {
			lenghtpx = lenghtpx + $(items[i]).width();
		}
		
		// if (lenghtpx + $(this).offset().left > $(menuMain).width() + $(menuMain).offset().left || $(this).attr('class') == 'unique') {
		// 	$(this).find('ul').css({'left':'auto', 'right': '0'});
		// 	$(this).find('li').css({'float':'right'});
		// 	$(this).find('li:first').removeClass('first').addClass('last');
		// 	$(this).find('li:last').removeClass('last').addClass('first');
		// }
	}, function() {
		obj = $(this);
		clearTimeout(timer);
		timer = setTimeout(function () {
			checkHover();
		}, menuTimeout);
	});


$(function() {
    $('a[href^="#call"]').on('click', function(e) {
        e.preventDefault();
        if ($(window).width() > 570) {
        $('html, body').animate({
            scrollTop: $($(this).attr('href')).offset().top - 147 + 'px'
        }, 1000, 'swing');
    	}
    	if ($(window).width() <= 570) {
        $('html, body').animate({
            scrollTop: $($(this).attr('href')).offset().top - 120 + 'px'
        }, 1000, 'swing');
    	}
       	$("#nav-icon1").removeClass('open');
		$("body").removeClass('open');
		$("body").removeClass('pushy-open-left');
		$("html").removeClass('open');

    });

    if( window.location.hash && $(window).width() > 570 ){
        $('html, body').animate({
            scrollTop: $(window.location.hash).offset().top - 147 + 'px'
        }, 1000, 'swing');
    }

        if( window.location.hash && $(window).width() <= 570 ){
        $('html, body').animate({
            scrollTop: $(window.location.hash).offset().top - 120 + 'px'
        }, 1000, 'swing');
    }

    // $('#SkypeButton_Call_vash-brigadir_1_paraElement img').attr('src', 'images/contact-phone.svg');
    $('#SkypeButton_Call_vash-brigadir_1_paraElement a').append('<p>vash-brigadir</p>');
    $('#SkypeButton_Call_vash-brigadir_1-2_paraElement a').append('<p>vash-brigadir</p>');

 //    if ($('.table .row .col2 a').height() > 14) {
	// 	$(this).addClass('asdfasdf');
	// }

	$('.table .row .col2').filter(function () {
	  return ($(this).height() > 24)
	}).parent().find('.col3').addClass('row2');

	$('.table .row .col2').filter(function () {
	  return ($(this).height() > 40)
	}).parent().find('.col3').addClass('row3');

});

$(document).ready(function(){
	$('#nav-icon1,#nav-icon2,#nav-icon3,#nav-icon4').click(function(){
		$(this).toggleClass('open');
		// $("body").toggleClass('open');
		// $("html").toggleClass('open');

		// if (!$('.mobile').hasClass('open')) {
		// 	setTimeout( function(){
		// 		$(".mobile").addClass('open');
		// 	},200);
		// }		
		// if (!$('body').hasClass('open')) {
		// 	setTimeout( function(){
		// 		$(".mobile").removeClass('open');
		// 	},0);
		// }
	});



	$('.b-header__menu-swipe-button').on('click touchstart', function () {
		$('#nav-icon1').addClass('open');
	});

	$('.in-menu').on('click touchstart', function () {
		$('#nav-icon1').removeClass('open');
		$('.b-main-menu').removeClass('m-state_shown');
		$('.b-app').removeClass('m-state_swiped');
	});
});


$(document).ready(function(){
	$('.meanmenu-reveal').click(function(){
		$(this).toggleClass('open');
		$(".mean-bar").toggleClass('a');
		$("html, body").animate({ scrollTop: 0 }, 600);
    	return false;
	});
});

$(document).ready(function(){
	$('.bloc .head').click(function(){
		$('.head').removeClass('active');
		$(this).addClass('active');
	});
});



	$(document).ready(function(){
		$('#h1').click(function(){
			$('.bloc-menu').css('display','none');		
			$('#m1').css('display','block');
		});
	});

	$(document).ready(function(){
		$('#h2').click(function(){
			$('.bloc-menu').css('display','none');		
			$('#m2').css('display','block');
		});
	});

	$(document).ready(function(){
		$('#h3').click(function(){
			$('.bloc-menu').css('display','none');		
			$('#m3').css('display','block');
		});
	});

$(document).ready(function(){
	$('.t1').click(function(){
		$('.col3-1').css('display','block');	
		$('.col3-2').css('display','none');
	});
});


$(document).ready(function(){
	$('.t2').click(function(){
		$('.col3-2').css('display','block');	
		$('.col3-1').css('display','none');
	});
});


$(document).ready(function(){
	$('.table .row1.price .col3 .col3-row2 .col3-1').addClass('act');
	$('.t11').click(function(){
		// $('.col3-1').css('display','block');	
		// $('.table .row1.price .col3 .col3-row2 .col3-1').css('display','-webkit-box');	
		$('.table .row1.price .col3 .col3-row2 .col3-1').addClass('act');	
		$('.table .row1.price .col3 .col3-row2 .col3-2').removeClass('act');
		// $('.table .row1.price .col3 .col3-row2 .col3-1').toggleClass('flex-table');
		// $('.col3-2').css('display','none');
	});
});


$(document).ready(function(){
	$('.t21').click(function(){
		// $('.col3-2').css('display','block');
		// $('.table .row1.price .col3 .col3-row2 .col3-2').css('display','-webkit-box');	
		$('.table .row1.price .col3 .col3-row2 .col3-2').addClass('act');	
		$('.table .row1.price .col3 .col3-row2 .col3-1').removeClass('act');
		// $('.table .row1.price .col3 .col3-row2 .col3-2').toggleClass('flex-table');		
		// $('.col3-1').css('display','none');
	});
});


$(document).ready(function(){
	$('.add-news').click(function(){
		$('.all-news.hide').css('display','block');		
		$('.add-news').addClass('hide');
	});
});

$(document).ready(function(){
	$(document).on('click', '.reviews-type-head, .reviews-type-head *',  function(){
	    $(this).parent().children(".reviews-context").slideToggle();
		$(this).parent().find(".arrows").toggleClass('flip');
	});
});

$(document).ready(function(){
	$(".up").click(function(){
	    $(this).parent().parent().find(".reviews-context").slideUp();
	    $(this).parent().parent().find(".arrows").toggleClass('flip');
	});
});

$(document).ready(function(){
	$(".price-block .col1").click(function(){
	    $(this).parent().parent().find(".price-menu").slideToggle();
		$(this).parent().parent().find(".price-arrows").toggleClass('flip');
	});
    
    $(".question-button").click( function () {
        $("#sendFeedback-m").submit();
    });
    
    $(".add-news").click( function () {
        var obj = $(this);
        var vals = {start: obj.attr("data-start")};
        $.post('/ajax/news.php?isNaked=1&action=add_news', vals, function (data){
            $("#more-news").append(data);
            obj.attr("data-start", parseInt(obj.attr("data-start")) + 10);
        });
    });
});

$(window).scroll(function() {    
    var scroll = $(window).scrollTop();
    var objectSelect = $(".pre-works.white .head");
    var objectPosition = objectSelect.offset().top - 150;
    if (scroll > objectPosition) {
        //clearHeader, not clearheader - caps H
        $("#myAffix").addClass("affix-bottom");
    }

    else {
        //clearHeader, not clearheader - caps H
        $("#myAffix").removeClass("affix-bottom");
    }
});

$( window ).resize(function() {
	if ($(window).width() > 570) {
  		$('.table .row1.price .col3 .col3-row2 .col3-2').css('display','block');		
  		$('.table .row1.price .col3 .col3-row2 .col3-1').css('display','block');	
  		$('.table .row1 .col3 .col3-row2 .col3-2').css('display','block');
  		$('.table .row1 .col3 .col3-row2 .col3-1').css('display','block');
  		$('.table .row .col3 .col3-1').css('display','block');	
  		$('.table .row .col3 .col3-2').css('display','block');	
  	}	
  	if ($(window).width() <= 570) {
  		   		$('.table .row1 .col3 .col3-row2 .col3-2').css('display','');	
   		$('.table .row1 .col3 .col3-row2 .col3-1').css('display','');	
  		$('.table .row .col3 .col3-2').css('display','');	
  		$('.table .row .col3 .col3-1').css('display','');	
  		$('.table .row1.price .col3 .col3-row2 .col3-2').css('display','');	
  		$('.table .row1.price .col3 .col3-row2 .col3-1').css('display','');	
   		$('.table .row1.price .col3 .col3-row2 .col3-1').css('display','');	
   		$('.table .row1.price .col3 .col3-row2 .col3-1').addClass('act');	
		$('.table .row1.price .col3 .col3-row2 .col3-2').removeClass('act');


  	}	
});

function openTab(tabName) {
    var i;
    var x = document.getElementsByClassName("tab");
    for (i = 0; i < x.length; i++) {
       x[i].style.display = "none";  
    }
    document.getElementById(tabName).style.display = "block";  
    
}

$(document).ready(function(){
	$('.w3-button').click(function(){
		$('.w3-button').removeClass("active")
		$(this).addClass("active")
	});
});

var fixed = document.getElementById('fixed');

fixed.addEventListener('touchmove', function(e) {

        e.preventDefault();

}, false);


