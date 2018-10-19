// JavaScript Document
$(document).ready(function(){
	$('a.addprop').bind('click', function(){
		var html = '<tr><td><input type="text" name="propnames[]" value="">:</td><td><input type="text" name="props[]" value=""></td></tr>';
		$('#tprops').append(html);
		return false;
	})
	
	$('a.ready_to_change').bind('click', function(){
		$(this).next('form').css('display', 'inline-block');
		$(this).css('display', 'none');
		return false;
	})
	
	$('a.form_ok').bind('click', function(){
		var f = $(this).parents('form:first');										  
		var vals = $(f).serialize();
		$.post('/ajax/main.php?action=save_price', vals, function(data){
			var price = $('input[name=price]', f).val();
			$(f).prev('a').html(parseInt(price));
			$(f).css('display', 'none');
			$(f).prev('a').css('display', 'inline');
		});
	});
	
	$('a.form_return').bind('click', function(){
		var f = $(this).parents('form:first');
		$(f).css('display', 'none');
		$(f).prev('a').css('display', 'inline');
	});
	
	
});

function textareaCurLineNum(obj) {
    var v = $(obj).val();
	return v.split(/\n/).length;
}

function check_tovar_mark(ob) {
	var tr = $(ob).parents('tr');
	if ($('input.nc_multi_check:checked', tr).length == 1) {
		$('td', tr).css('background-color', '#ECCFA5');
	} else {
		$('td', tr).css('background-color', '');
	}
}