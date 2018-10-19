// <![CDATA[

var obj = null;

// Страница для загрузки товаров
var curPage = 1;

// Флаг, показывающий что происходит загрузка
var loading = false;

function checkHover() {
	if (obj) {
		obj.find('ul').fadeOut('slow');	
		obj = null;
		//alert('!');
	} //if
} //checkHover

$(document).ready(function() {
						   
	// Инициализация всплывающего окна
	$('.fancybox').fancybox({
		closeBtn: false,
		titleShow: false,
		showCloseButton: false,
		margin:15
	});

/*	
	if (typeof fancybox_loaded !=="undefined") {
        $('a.fancybox').fancybox({showCloseButton: false, titleShow: false, margin:3});
	}
*/
	
	$('.ul-mainmenu > li > ul').each(function(){
		if ($('li', this).length == 0) $(this).remove();
	});
	
	  
	  
	$('.ul-mainmenu > li:eq(4) > ul').css('width', '350px');
	
	$('#otown').click(function () {
		if ($(this).parents('.selecttown').hasClass('js_minimized')) {
			$(this).parents('.selecttown').removeClass('js_minimized');
		} else {
			$(this).parents('.selecttown').addClass('js_minimized');
		}
		return false;
		
	});
	
	var basketForms = $('.basket_form');
	if (typeof $.fn.ajaxForm == 'function' && $(basketForms).length) {
		
		// Настройки оповещения о добавлении в корзину
		if (typeof $.gritter == 'object') {
			// global setting override
	        /*
			$.extend($.gritter.options, {
			    class_name: 'gritter-light', // for light notifications (can be added directly to $.gritter.add too)
			    position: 'bottom-left', // possibilities: bottom-left, bottom-right, top-left, top-right
				fade_in_speed: 100, // how fast notifications fade in (string or int)
				fade_out_speed: 100, // how fast the notices fade out
				time: 3000 // hang on the screen for...
			});
	        */
			
		}

		var addGoods = {};
		$(basketForms).ajaxForm({
	    	beforeSubmit: function(formData, jqForm, options){
	    		addGoods['name'] = $('input[name="_name_"]', jqForm).val();
	    		addGoods['img'] = '';
	    		if ($('input[name="_image_"]', jqForm).val() != '')
	    			addGoods['img'] = $('input[name="_image_"]', jqForm).val();
	    		
	    		// Оповещение о добавлении в корзину
				
				/*
				
	    		if (typeof $.gritter == 'object') {
	    			var unique_id = $.gritter.add({
						// (string | mandatory) the heading of the notification
						title: 'Товар добавлен в корзину',
						// (string | mandatory) the text inside the notification
						text: addGoods['name'] + '<br /><br /><div class="toBasket"><a href="/shop/cart/">Перейти к оформлению</a></div>',
						// (string | optional) the image to display on the left
						image: addGoods['img'],
						// (bool | optional) if you want it to fade out on its own or just sit there
						sticky: false,
						// (int | optional) the time you want it to be alive for before fading out
						time: '50000',
						position: 'bottom-right'
					});
	    		}
				
				*/
				
				$('#notifiers_wrap .notifier_baloon_title').html('Товар добавлен в корзину');
				$('#notifiers_wrap .notifier_image_wrap img').attr('src', addGoods['img']);
				$('#notifiers_wrap div.notifier_baloon_msg').html(addGoods['name'] + '<br /><br /><div class="toBasket"><a href="/shop/cart/">Перейти к оформлению</a></div>');
				
				$('#notifiers_wrap').fadeIn('slow');
				
				setTimeout(function(){
					$('#notifiers_wrap').fadeOut('slow');
				}, 5000);
				
				
	    		
	    		//alert(formData[0].value);
	    		//$('.loading').hide();
	    		//$('.id_' + formData[0].value).show();
	    		//loadingBtn = $('.id_' + formData[0].value);
	    	},
	        success: function(r){
	            $('.basket_informer').html(r);
	            //$(loadingBtn).hide();
	            //loadingBtn = false;
	            //alert('Товар успешно добавлен в корзину');
	            //alert(addGoods['name']);
	            //alert(addGoods['img']);
	        }
	    });

	}
	
	$('.btnCompany input').click(function () {
		if ($(this).val() == 'Юридическое лицо')
			$('.ctlCompany').fadeIn();
		else
			$('.ctlCompany').hide();
		//alert($(this).val());
		
	});
	$('.btnCompany input:checked').click();
	
	var timer = null;
	var menuTimeout = 2000;
	var menuMain = $('div.mainmenu > ul');
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
		
		if (lenghtpx + $(this).offset().left > $(menuMain).width() + $(menuMain).offset().left || $(this).attr('class') == 'unique') {
			$(this).find('ul').css({'left':'auto', 'right': '0'});
			$(this).find('li').css({'float':'right'});
			$(this).find('li:first').removeClass('first').addClass('last');
			$(this).find('li:last').removeClass('last').addClass('first');
		}
	}, function() {
		obj = $(this);
		clearTimeout(timer);
		timer = setTimeout(function () {
			checkHover();
		}, menuTimeout);
	});
	
	
	// ФАЙЛЫ
	if ($('.fileInput').length) {
		var source = $('#filefield .fileInput').parent().clone();
	    var fileHtml = $(source).html();
	      
	    $('input[type="file"]', '.fileInput').live('change', function () {
			var parent = $(this).parents('#filefield');
	        var count = $('.fileInput', parent).size();
			if (count == 20) return;
	        var name = $('input:eq(0)', source).attr('name');
	        newInput = fileHtml.replace(new RegExp(name, 'g'), name + '_' + count);
	        
	        $('.fileInput:last', parent).after(newInput);
	    });
	}
	
	// Сортировка
	
	/*
	$('#sortList').bind('change', function() {
		var url = window.location + '';
		var urlparts = url.split('?');
		var query = urlparts[1] ? urlparts[1] : '';
		url = urlparts[0] ? urlparts[0] : '';
		var queryParams = '';
		if (query != '') {
			var queryArr = query.split('&');
			for ( keyVar in queryArr ) {
				var parts = queryArr[keyVar].split('=');
				if (parts[0] && parts[0] != 'pageNum' && parts[0] != $(this).attr('name')) {
					queryParams += queryArr[keyVar] + '&';
				}
				//alert(queryParams[keyVar]);
			}
			//alert(query);
		
		}
		//alert(queryParams);
		var sortParam = $(this).attr('name') + '=' + $(this).val();
		queryParams += sortParam;
		//alert(queryParams);
		window.location = url + '?' + queryParams;
    });
	
	*/
	
	// ЗАКЛАДКИ
	if ($("#tabs").length) {
		$("#tabs").tabs({
	    	
	    	select: function(event, ui) {
	    		//alert(ui.panel.id);
	    		var re = /#.*$/;
	            var url = document.location.toString();
	            // to make bookmarkable
	            url = url.replace(re, "");
	            document.location = url + "#" + ui.panel.id;
	            //alert(document.location);
	    		return true;
    		}
		
	    });
	}
	
	// Главное меню
	$('.mainmenu > ul > li:first').addClass('first');
	$('.mainmenu > ul > li:last').addClass('last');
	
	
	$('#service_menu li a').each(function(){
		if ($(this).attr('href') == u) {
			$(this).parents('li:first').addClass('active');
		}
	});
	
	
/*
    // Автоподгрузка товаров - отменено
    if (typeof start != 'undefined') {
		window.onscroll = function(){								 
			var a = pageHeight();
			var b = scrollY();
			var c = windowHeight();
			var d = a - b - c;
			
			if (!loading && pageHeight() - scrollY() - windowHeight() < windowHeight() ) {
				$('#loading').fadeIn('fast');
				start = start + 10;
				if (u.indexOf('?') > -1) {
					u = u + '&isNaked=1';
				} else {
					u = u + '?isNaked=1';
				}
				loading = true;
				$.post(u, {start: start, ajax: 1}, function(data){
					$('#loading').css('display', 'none');															
					if (data.indexOf('tov_box') >= 0) {
						$('#loading').before(data);
						loading = false;
					}
				});
			}
		}
	}
*/	

	$('.mainbar_two_article_inner').each(function(){
		$('p:last', this).css('margin-bottom', '0px');
	});

    var s = $('.phoneInput input:eq(0), .phoneInput input:eq(1)');
	
	$('.phoneInput').each(function(){
		$('input:eq(0)', this).charLimit({limit:3, callbackForward: callbackForward, callbackBackward: callbackBackward});
		$('input:eq(1)', this).charLimit({limit:3, callbackForward: callbackForward, callbackBackward: callbackBackward});

		$('input:eq(2)', this).charLimit({limit:2, callbackForward: callbackForward, callbackBackward: callbackBackward});
		$('input:eq(3)', this).charLimit({limit:2, callbackForward: callbackForward, callbackBackward: callbackBackward});
	});
	
	/*
	$('.phoneInput input:eq(0), .phoneInput input:eq(1)').charLimit({limit:3, callbackForward: callbackForward, callbackBackward: callbackBackward});
	$('.phoneInput input:eq(2), .phoneInput input:eq(3)').charLimit({limit:2, callbackForward: callbackForward, callbackBackward: callbackBackward});
	*/
	
	function callbackForward(obj) {
		$(obj).next().focus(); 
	}
	function callbackBackward(obj) {
		$(obj).prev().focus(); 
	}
	
	
	$('a.popup2-call').bind('click', function(){
		var id = '#' + $(this).attr('rel');
			
		var w = $(id).width() + 4;
		var h = $(id).height();
		$(id).css('margin-left', '-' + parseInt(w/2) + 'px');
		$(id).css('margin-top', '-' + parseInt(h/2) + 'px');
		
		$(id).fadeIn('fast');
		$('#overlay').fadeIn('fast');
	});
	
	$('#overlay').bind('click', function(){
		var p = $('.popup2:visible');
		$(p).fadeOut('fast', function(){
			$('.popup2-content', p).css('display', 'block');
			$('.popup2-after', p).css('display', 'none');
		});
		$('#overlay').fadeOut('fast');
	});
	
	
	$('.order-send').bind('click', function(){
		if (!$(this).hasClass('button_blue_big_disabled')) {
			var popup = $(this).parents('.popup2:first');
			var form = $(this).parents('form:first');
			var action = $(form).attr('action');
			var vals = $(form).serialize();
			
			var options = {
				success: function(){
					$('.popup2-content', popup).slideUp('fast');
					$('.popup2-after', popup).slideDown('fast', function(){
						var h = $(popup).height();
						$(popup).css('margin-top', '-' + parseInt(h/2) + 'px');
					});
					
					setTimeout(function(){
						if ($('.popup2:visible').length == 1)	{
							var popup2 = $('.popup2:visible');
							
							$(popup2).fadeOut('fast', function(){
								$('.popup2-content', popup2).css('display', 'block');
								$('.popup2-after', popup2).css('display', 'none');
							});
							$('#overlay').fadeOut('fast');
						}
					}, 5000);
				}
			};
			
			$(form).ajaxSubmit(options);
			
		}
		return false;
	});
	
	$('.form-submit').bind('click', function(){
		if ($(this).hasClass('button_blue_big_disabled')) return false;
		$(this).parents('form:first').submit();
	});
	
	$('.popup2 .close-popup').bind('click', function(){
		if ($('.popup2:visible').length > 0) {
		    var popup2 = $('.popup2:visible');
			
			$(popup2).fadeOut('fast', function(){
				$('.popup2-content', popup2).css('display', 'block');
				$('.popup2-after', popup2).css('display', 'none');
			});
			$('#overlay').fadeOut('fast');
		}
	});
	
	var form_validation = function(ob) {
		var form = $(this).parents('form:first');
		var valid = true;
		$('input[type=text].required', form).each(function(){
			if ($(this).val() == '') valid = false;
		});
		$('textarea.required', form).each(function(){
			if ($(this).val() == '') valid = false;
		});
		$('select.required', form).each(function(){
			if ($(this).val() == '' || $(this).val() == '0' || $(this).val() == '-1') valid = false;
		});
		if (valid) {
			$('.button_blue_big', form).removeClass('button_blue_big_disabled');
		} else {
			$('.button_blue_big', form).addClass('button_blue_big_disabled');
		}
	}
	
	$('.popup2 input[type=text], .with-validate input[type=text], ' + 
	   '.popup2 textarea, .with-validate textarea').bind('keyup', form_validation);
	
	$('.popup2 select.required, .with-validate select.required' + 
	   '').bind('change', form_validation);
	
	$('form input.field4, form input.field5, form input.field6, form input.field7, form input.phone_1, form input.phone_2').bind('keydown', function(e){
 
 		var keynum = e.keyCode;
		if ((keynum < 46 || keynum > 57) && (keynum != 13) && (keynum != 58) && (keynum != 8) && (keynum != 112) && (keynum != 80) && (keynum != 32) && (keynum != 77) && (keynum != 109) && (keynum != 65) && (keynum != 97) && (keynum != 9) && (keynum != 16)) return false;																																							
	});
	
	

});

/**
 * jQuery.charLimit()
 * 
 * Плагин следящий за кол-вом введенных в input символов и добавляющий полезные фичи
 * 
 */
(function($){
	$.fn.charLimit = function(options) {
		if(typeof options == 'undefined' || typeof options.limit == 'undefined' || typeof options.limit != 'number') {
			$.error('Option limit must be defined and must be a number.');
		}
		
		//alert('!');

		return this.each(function() {
			var self = $(this);
			var charLimit = options.limit;

			function _truncate(ev) {
				var caretPos;
				if (ev.target.selectionStart !== undefined) {
					caretPos = ev.target.selectionEnd;
				} else if(document.selection) {
					ev.target.focus();
					var range = document.selection.createRange();
					range.moveStart('character', -ev.target.value.length);
					caretPos = range.text.length;
				}

				self.val(self.val().substring(0, charLimit));
				_setCaretPos(ev, caretPos);
			}

			function _setCaretPos(ev, pos) {
				if ($(ev.target).get(0).setSelectionRange) {
					$(ev.target).get(0).setSelectionRange(pos, pos);
				} else if ($(ev.target).get(0).createTextRange) {
					var range = $(ev.target).get(0).createTextRange();
					range.collapse(true);
					range.moveEnd('character', pos);
					range.moveStart('character', pos);
					range.select();
				}
			}

			self.keypress(function(ev) {
				var charCount = self.val().length;
				var selected;
				if (ev.target.selectionStart !== undefined) {
					selected = !(ev.target.selectionStart==ev.target.selectionEnd);
				} else if(document.selection) {
					ev.target.focus();
					var range = document.selection.createRange();
					selected = (range.text.length > 0);
				}

				if (charCount > charLimit-1 && !selected) {
					return false;
				}
				setTimeout(function() {
					_truncate(ev);
				}, 1);
				
			}).bind('paste', function(ev) {
				setTimeout(function() {
					_truncate(ev);
				}, 1);
				
			}).keydown(function(ev) {
				var charCount = self.val().length;
				
				// backspace
				if (ev.which == 8 && charCount == 0) {
					if (typeof options.callbackBackward == 'function') {
						options.callbackBackward(this);
						return false;
					}
				}
				
			}).keyup(function(ev) {
				var charCount = self.val().length;
				var selected;
				if (ev.target.selectionStart !== undefined) {
					selected = !(ev.target.selectionStart == ev.target.selectionEnd);
				} else if(document.selection) {
					ev.target.focus();
					var range = document.selection.createRange();
					selected = (range.text.length > 0);
				}
				
				if (charCount == charLimit && !selected) {
					if (typeof options.callbackForward == 'function') {
						options.callbackForward(this);
					}
				}
				
			}).focusin(function() {
			    $(this).select();
			    
			});

		});
	};
	
})(jQuery);


// Returns the height of the web page
// (could change if new content is added to the page)
function pageHeight() {
    return document.body.scrollHeight;
}
// Returns the width of the web page
function pageWidth() {
    return document.body.scrollWidth;
}

// A function for determining how far horizontally the browser is scrolled
function scrollX() {
    // A shortcut, in case we're using Internet Explorer 6 in Strict Mode
    var de = document.documentElement;
    // If the pageXOffset of the browser is available, use that
    return self.pageXOffset ||
    // Otherwise, try to get the scroll left off of the root node
    ( de && de.scrollLeft ) ||
    // Finally, try to get the scroll left off of the body element
    document.body.scrollLeft;
}

function scrollY() {
    // A shortcut, in case we're using Internet Explorer 6 in Strict Mode
    var de = document.documentElement;
    // If the pageYOffset of the browser is available, use that
    return self.pageYOffset ||
    // Otherwise, try to get the scroll top off of the root node
    ( de && de.scrollTop ) ||
    // Finally, try to get the scroll top off of the body element
    document.body.scrollTop;
}

// Find the height of the viewport
function windowHeight() {
    // A shortcut, in case we're using Internet Explorer 6 in Strict Mode
    var de = document.documentElement;
    // If the innerHeight of the browser is available, use that
    return self.innerHeight ||
    // Otherwise, try to get the height off of the root node
    (de && de.clientHeight) ||
    // Finally, try to get the height off of the body element
    document.body.clientHeight;
}

// Find the width of the viewport
function windowWidth() {
    // A shortcut, in case we're using Internet Explorer 6 in Strict Mode
    var de = document.documentElement;
    // If the innerWidth of the browser is available, use that
    return self.innerWidth ||
    // Otherwise, try to get the width off of the root node
    (de && de.clientWidth) ||
    // Finally, try to get the width off of the body element
    document.body.clientWidth;
}


function basketDeleteClick(cid, oid) {
   if (confirm('Удалить товар из корзины?')) {
	   $('#delete_cart_item input[name=cid]').val(cid);
	   $('#delete_cart_item input[name=oid]').val(oid);
	   $("#delete_cart_item").submit();
   }
}
$('.gradi_bot .col').click(function(){
$('.gradi_bot .col').removeClass('active');
$(this).addClass('active');
});
// ]]>

