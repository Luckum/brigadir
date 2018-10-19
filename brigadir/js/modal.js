$('.question-button').click( function() {
	$('#question').fadeIn();
	$('html').css('overflow','hidden');
	// $('#question').delay(3000).fadeOut();
	// $('html').delay(3000).css('overflow','auto');
	setTimeout(function(){
	    $("#question").fadeOut();
	    $('html').css('overflow','auto');
	}, 4000)
});

$('.close').click( function(){
	$('#question').fadeOut();
	$('html').css('overflow','auto');
})

$('#question').click( function(){
	$('#question').fadeOut();
	$('html').css('overflow','auto');
})




