/*!
 * jQuery Table.Edit Plugin
 * version: 0.01 (2012-04-28)
 * @requires jQuery v1.7.1 or later
 *
 * Examples and documentation at: http://nwpro.ru
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
;(function($) {

/*
	Usage Note:
	-----------
	
	$(document).ready(function() {
		$('#myTable').tableEdit({
			
		});
	});

*/

/**
 * 
 */
$.fn.tableEdit = function(options) {
	// fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
	if (!this.length) {
		log('tableEdit: no element selected');
		return this;
	}
	
	jQuery.tableEdit.table = this;
	
	// Переопределяем настройки
	options = jQuery.extend(true, {
		//
	}, options);
	
	if (typeof options.getHtmlSelect != 'undefined') {
		jQuery.tableEdit.getHtmlSelect = options.getHtmlSelect;
	}
	
	if (typeof options.getFieldsNameGoods != 'undefined') {
		jQuery.tableEdit.getFieldsNameGoods = options.getFieldsNameGoods;
	}
	
	if (typeof options.linkSave != 'undefined') {
		jQuery.tableEdit.linkSave = options.linkSave;
	}
	
	

	// Вешаем события на клик по TD
	jQuery('td.ctrl-input, td.ctrl-dt, td.ctrl-select', this).unbind('click').bind('click', function () {
		jQuery.tableEdit.makeEditable(jQuery(this).parent('tr'));
	});
	
}

/**
 * 
 */
jQuery.tableEdit = {
	cache: {
		autocomplete: {},
		goods: {}
	},
	table: false,
	editableRowClass: 'te-editing',
	editableRowChangedClass: 'te-changed',
	timeout: false,
	makeEditable: function (row) {
		if (jQuery(row).hasClass(jQuery.tableEdit.editableRowClass)) {
			return false;
		}
		
		// Выключаем редактируемые строки
		jQuery('tr.' + jQuery.tableEdit.editableRowClass, jQuery(row).parent('tbody')).each(function(i) {
			jQuery.tableEdit.makeStatic(this);
		});
		
		// добавляем класс
		jQuery(row).addClass(jQuery.tableEdit.editableRowClass);
		
		var td = jQuery('td', row);
		td.each(function(i) {
			if (jQuery(this).hasClass('ctrl-input') 
				|| jQuery(this).hasClass('ctrl-dt') 
				|| jQuery(this).hasClass('ctrl-select')
			) {
				if (jQuery("input,select,textarea", this).size() > 0) {			
					//html = html.find("input,select,textarea"); // constrains jQ object to INPUT vs TD			
					//var val = (html.attr('type') == 'checkbox') ? 
					//	html[0].checked :
					//	html.val();
					// add preserve class, remove disabled (if set)
					//html.attr("disabled", false).addClass("tsPreserve");
					//jQuery.tableEditor.cache.add(key, name, val);
					return;
				}
				var name = 'row[' + jQuery(this).index() + ']';
				var val = jQuery(this).html().replace(/[\"]+/g,'&quot;'); // replace " with HTML entity to behave within value=""
				
				if (jQuery(this).hasClass('ctrl-input'))
					html = jQuery.tableEdit.getHtmlInput(name, val);
				else if (jQuery(this).hasClass('ctrl-dt'))
					html = jQuery.tableEdit.getHtmlDt(name, val);
				else if (jQuery(this).hasClass('ctrl-select')) {
					html = jQuery.tableEdit.getHtmlSelect(name, val);
				}
				jQuery(this).html(html);
				
				// Активируем плагин datepicker
				if (jQuery(this).hasClass('ctrl-dt'))
					jQuery.tableEdit.activateHtmlDt(this);
				
				//jQuery.tableEditor.cache.add(key, name, val);
				return true;
			}
		});
		
		
		jQuery('input, select', row).bind('change', function () {
			// При изменении добавляем класс
			var row = jQuery(this).parents('tr.' + jQuery.tableEdit.editableRowClass);
			jQuery(row).addClass(jQuery.tableEdit.editableRowChangedClass);
		}).bind('blur', function () {
			if (jQuery(this).parent('td').hasClass('ctrl-dt'))
				return;
			// При выходе из input запускаем таймер,
			var tr = jQuery(this).parents('tr.' + jQuery.tableEdit.editableRowClass);
			jQuery.tableEdit.timeout = setTimeout(function() {
				jQuery.tableEdit.makeStatic(tr);
			}, 200);
		}).bind('focus', function () {
			// При входе обнуляем таймер
			clearTimeout(jQuery.tableEdit.timeout);			
		});
		
		// TODO Накидываем autocomplete
		if (typeof jQuery.ui.autocomplete != 'undefined') {
			var autoInput = false;
			jQuery('.autocomplete input', row).autocomplete(jQuery.tableEdit.autocompleteGoods);
		}
		
	},
	makeStatic: function (row) {
		if (!jQuery(row).hasClass(jQuery.tableEdit.editableRowClass)) {
			return false;
		}
		
		var data = {};
		data['id'] = jQuery('input[name="row"]', row).val();
		data['rowIndex'] = jQuery(row).index();
		var td = jQuery('td', row);
		td.each(function(i) {
			if (jQuery(this).hasClass('ctrl-input') 
				|| jQuery(this).hasClass('ctrl-dt') 
				|| jQuery(this).hasClass('ctrl-select')
			) {
				html = jQuery(this).find("input,select,textarea"); // constrains jQ object to INPUT vs TD			
				html.prop('disabled', true);
				var val = (html.attr('type') == 'checkbox') 
					? html[0].checked 
					: html.val();
				data[html.attr('name')] = val;
				jQuery(this).html(val);
			}
			
		});
		
		// TODO Отправляем данные на сервер, если были изменения
		if (jQuery(row).hasClass(jQuery.tableEdit.editableRowChangedClass)) {
			jQuery.tableEdit.saveData(data);
		}
		
		// удаляем класс
		jQuery(row).removeClass(jQuery.tableEdit.editableRowClass + ' ' + jQuery.tableEdit.editableRowChangedClass);
		
		return true;
		
	},
	/**
	 * Возвращает HTML-код текстового поля
	 * @param name
	 * @param val
	 * @returns {String}
	 */
	getHtmlInput: function (name, val) {
		return '<input type="text" name="'+name+'" value="'+val+'" style="width:98%"></input>';
		
	},
	/**
	 * Возвращает HTML-код поля даты
	 * @param name
	 * @param val
	 * @returns {String}
	 */
	getHtmlDt: function (name, val) {
		return '<input type="text" name="'+name+'" value="'+val+'" style="width:98%"></input>';
		
	},
	activateHtmlDt: function (obj) {
		$('input', obj).datepicker($.extend($.datepicker.regional['ru'], {
			changeMonth: true,
			changeYear: true,
			showOtherMonths: true,
			selectOtherMonths: true,
			onSelect: function(dateText, inst) { 
				clearTimeout(jQuery.tableEdit.timeout); 
			}
		}));
	},
	/**
	 * Возвращает HTML-код списка, можно переопределить
	 * @param name
	 * @param val
	 * @returns {String}
	 */
	getHtmlSelect: function (name, val) {
		return '<input type="text" name="'+name+'" value="'+val+'" style="width:98%"></input>';
		
	},
	/**
	 * Изменяем в строке данные о товаре, кроме индексного поля
	 * @param data
	 * @param fieldIndex
	 * @param row
	 */
	setRowData: function (data, fieldIndex, row) {
		var fieldsName = {};
		var fieldsClass = {};
		var fieldsTitle = {};
		
		//alert(print_r(data));
		
		if (typeof data.moduleInfo == 'undefined') {
			fieldsName = jQuery.tableEdit.getFieldsNameGoods;
		} else if (typeof data.moduleInfo != 'undefined' && data.moduleInfo == 'ReestrInfo') {
			fieldsName = jQuery.tableEdit.getFieldsNameInfo;
			fieldsClass = jQuery.tableEdit.getFieldsClass;
			fieldsTitle = jQuery.tableEdit.getFieldsTitle;
		} else if (typeof data.moduleInfo != 'undefined' && data.moduleInfo == 'ReestrEan') {
			fieldsName = jQuery.tableEdit.getFieldsNameEan;
			fieldsClass = jQuery.tableEdit.getFieldsClass;
			fieldsTitle = jQuery.tableEdit.getFieldsTitle;
		}
		jQuery('td', row).each(function () {
			var index = jQuery(this).index();
			if (index == fieldIndex) {
				return;
			}
			
			// Значения ячеек
			if (typeof fieldsName[ index ] != 'undefined'
				&& typeof data[ fieldsName[ index ] ] != 'undefined'
			) {
				if (jQuery("input,select,textarea", this).size() > 0) {
					jQuery("input,select,textarea", this).val(data[ fieldsName[ index ] ]);
				} else {
					jQuery(this).html(data[ fieldsName[ index ] ]);
				}
			}
			
			// Значения классов ячеек
			if (typeof fieldsClass[ index ] != 'undefined'
				&& typeof data[ fieldsClass[ index ] ] != 'undefined'
			) {
				// Удаляем стандартные статусные классы
				jQuery(this).removeClass(jQuery.tableEdit.cellStatusClassRemove);
				jQuery(this).addClass(data[ fieldsClass[ index ] ]);
			}
			
			// Значения тайтлов ячеек
			if (typeof fieldsTitle[ index ] != 'undefined'
				&& typeof data[ fieldsTitle[ index ] ] != 'undefined'
			) {
				//alert(data[ fieldsTitle[ index ] ]);
				jQuery(this).attr('title', data[ fieldsTitle[ index ] ]);
			}

			
		});
	},
	cellStatusClassRemove: 'status-grey status-green status-red',
	/**
	 * Массивы номеров ячеек для установки значений
	 * 
	 * ТОВАРЫ
	 */
	getFieldsNameGoods: {
		2: "id",
		3: "ean",
		4: "article",
		5: "name",
		6: "group_code",
		7: "group_name",
		12: "unit_code_name"
	},
	/**
	 * Массивы номеров ячеек для установки КЛАССОВ, ТАЙТЛОВ и ЗНАЧЕНИЙ
	 * 
	 */
	getFieldsTitle: {
		9: "status_comment",
		11: "shop_name",
	},
	getFieldsClass: {
		9: "status_class"
	},
	getFieldsNameEan: {
		2: "goods_id",
		3: "goods_ean",
		4: "article",
		5: "goods_name",
		6: "goods_group",
		7: "goods_group_name",
		9: "status_name",
		10: "dt_status_print",
		12: "unit_name"
	},
	getFieldsNameInfo: {
		2: "goods_id",
		3: "goods_ean",
		4: "article",
		5: "goods_name",
		6: "goods_group",
		7: "goods_group_name",
		9: "status_name",
		10: "dt_status_print",
		11: "shop_id",
        12: "unit_code",
        13: "quantity_min",
        14: "price",
        15: "currency",
        16: "dt1",
        17: "dt2"
	},
	linkSave: '/json/ReestrEan/ajaxSave',
	/**
	 * Сохранение данных
	 * @param data
	 * @returns {Boolean}
	 */
	saveData: function (data) {
		
		//alert(ArrayToURL(data));
		jQuery.ajax({
			type: 'POST',
			url: jQuery.tableEdit.linkSave,
			dataType: 'json',
			data: data,
			success: function (data) {
				
				//alert(print_r(data));
				if (typeof data.rowIndex != 'undefined') {
					
					var row = jQuery('tbody tr:eq(' + data.rowIndex + ')', jQuery.tableEdit.table);
					
					// index поля для групповой обработки
					var multipleIndex = 8;
					var multipleClass = 'multiple';
					
					// Если нет ошибок
					if (typeof data.status != 'undefined' && data.status != 'error') {
					
						// Меняем ID строки
						var input = jQuery('input[name="row"]', row);
						if (jQuery(input).val() == 0)
							jQuery(input).val(data.id);
						
						// Необходимо включить в групповую обработку
						if (!jQuery('td:eq(' + multipleIndex + ')', row).hasClass(multipleClass))
							jQuery('td:eq(' + multipleIndex + ')', row).addClass(multipleClass);
						
					} else {
						//alert('error');
						
						// Необходимо исключить из групповой обработки
						jQuery('td:eq(' + multipleIndex + ')', row).removeClass(multipleClass);
						
					}
					
					// Обновляем галочки мультивыбора 
					if (typeof jQuery.multipleSelect.init != 'undefined')
						jQuery.multipleSelect.init();
					
					jQuery.tableEdit.setRowData(data, 0, row);
					
				}
				
			}
		});
		
		return true;
		
	},
	/**
	 * Настройки AUTOCOMPLETE полей товаров
	 */
	autocompleteGoods: {
		minLength: 1,
		/**
		 * Фиксируем информацию об активном Input
		 * @param event
		 * @param ui
		 */
		search: function(event, ui) {
			autoInput = this;
		},
		/**
		 * Фиксируем информацию о товаре при выборе
		 * @param event
		 * @param ui
		 */
		select: function(event, ui) {
			var inputValue = ui.item.value; 
			var cache = jQuery.tableEdit.cache.goods;
			
			if (typeof cache[ inputValue ] != 'undefined'
			) {
				// Фиксируем информацию о товаре в строке
				jQuery.tableEdit.setRowData(
					cache[ inputValue ], 
					jQuery(autoInput).parent('td').index(), 
					jQuery(autoInput).parent('td').parent('tr')
				);
			}
			
		},
		/**
		 * Запрашиваем данные с сервера
		 * @param request
		 * @param response
		 */
		source: function( request, response ) {
			request['inputIndex'] = jQuery(autoInput).parent('td').index();
			
			var term = request.term;
			if (typeof jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ] != 'undefined'
				&& term in jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ] 
			) {
				jQuery.tableEdit.cache.goods = jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ][ term ].goods;
				response( jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ][ term ].autocomplete );
				return;
			}
			
			//alert(print_r(request));

			lastXhr = $.getJSON( "/json/Goods/getAutocomplete", request, function( data, status, xhr ) {
				if (typeof jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ] == 'undefined') {
					jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ] = {};
				}
				jQuery.tableEdit.cache.autocomplete[ request['inputIndex'] ][ term ] = data;
				if ( xhr === lastXhr ) {
					//alert(print_r(data.autocomplete));
					if (typeof data.autocomplete != 'undefined') {
						jQuery.tableEdit.cache.goods = data.goods;
						response( data.autocomplete );
					}
				}
			});
		}
	} 
	
	
};

// helper fn for console logging
function log() {
	var msg = '[jquery.form] ' + Array.prototype.join.call(arguments,'');
	if (window.console && window.console.log) {
		window.console.log(msg);
	}
	else if (window.opera && window.opera.postError) {
		window.opera.postError(msg);
	}
};

})(jQuery);

/**
 * Аналог функции print_r() в PHP
 * @param arr
 * @param level
 * @returns {String}
 */
function print_r(arr, level, padding, br) {
    var print_red_text = "";
    if (!level) 
    	level = 0;
    if (!padding) 
    	padding = "    ";
    if (!br) 
    	br = "\n";
    var level_padding = "";
    for(var j=0; j<level+1; j++) level_padding += padding;
    if(typeof(arr) == 'object') {
        for(var item in arr) {
            var value = arr[item];
            if(typeof(value) == 'object') {
                print_red_text += level_padding + "'" + item + "' :" + br;
                print_red_text += print_r(value,level+1);
		} 
            else 
                print_red_text += level_padding + "'" + item + "' => \"" + value + "\"" + br;
        }
    } else 
    	print_red_text = "===>"+arr+"<===("+typeof(arr)+")";
    return print_red_text + br;
}
