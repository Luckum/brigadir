/*!
 * jQuery Multiple.Select Plugin
 * version: 0.01 (2012-04-30)
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
$.fn.multipleSelect = function(options) {
	// fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
	if (!this.length) {
		log('multipleSelect: no element selected');
		return this;
	}
	
	jQuery.multipleSelect.actionsBlock = this;
	
	// Переопределяем настройки
	options = jQuery.extend(true, {
		//
	}, options);
	
	jQuery.multipleSelect.cellClass = options.cellClass;
	jQuery.multipleSelect.form = options.form;
	
	//if (typeof options.getHtmlSelect != 'undefined') {
	//	jQuery.tableEdit.getHtmlSelect = options.getHtmlSelect;
	//}

	// Рисуем форму
	$(this).hide().html(jQuery.multipleSelect.drawForm());
	
	// Инициируем
	jQuery.multipleSelect.init();
	
    // Включение плавающих заголовков (для событий на checkbox в head)
    if ($("table.table_data").length && typeof $.fn.floatHeader == 'function') {
        $("table.table_data").floatHeader({
            onShow: function(){
                $('input.' + jQuery.multipleSelect.headCheckboxClass, '.floatHeader').unbind('click').bind('click', jQuery.multipleSelect.bindHeaderCheckbox)
                    .prop('checked', $(associate_control).prop('checked'));
                associate_control = $('input.' + jQuery.multipleSelect.headCheckboxClass);
            }
        });
    }
	
	
};

/**
 * 
 */
jQuery.multipleSelect = {
	form: false,
	rowCheckboxClass: 'ms-checkbox',
	headCheckboxClass: 'ms-main-checkbox',
	actions: {
		'delete': {
			caption:'Удалить / восстановить', 
			callback: function () { 
				if (!confirm('Удалить / восстановить выбранные элементы?')) {
					jQuery.multipleSelect.clearAction();
                    return false;
                }
				jQuery('#sub').val('delete');
				return true;
			}
		},
		'send': {
			caption:'Удалить', 
			callback: function () { 
				if (!confirm('Отправить изменения?')) {
					jQuery.multipleSelect.clearAction();
                    return false;
                }
				jQuery('#sub').val('send');
				return true;
			}
		},
		'edit': {
			caption:'Редактировать', 
			callback: function () { 
				// Выполнить проход по всем checkbox'ам и открыть для каждого выбранного новое окно для редактирования
                if (!confirm('Действительно хотите редактировать элементы (' + associate_arr.filter(':checked').length + ')?')) {
                    jQuery.multipleSelect.clearAction();
                    return false;
                }
                for (i = 0; i < associate_arr.length; i++) {
                    var item = $(associate_arr[i]);
                    if (item.is(':checked')) {
                    	//var edit_link_id = jQuery.multipleSelect.linkEdit.replace(/%25id%25/g, item.val());
                        //window.open(edit_link_id);
                    }
                }
                jQuery.multipleSelect.clearAction();
				return true;
			}
		},
		'copy': {
			caption:'Копировать', 
			callback: function () { 
				// Выполнить проход по всем checkbox'ам и открыть для каждого выбранного новое окно для редактирования
                if (!confirm('Действительно хотите копировать элементы (' + associate_arr.filter(':checked').length + ')?')) {
                    jQuery.multipleSelect.clearAction();
                    return;
                }
                for (i = 0; i < associate_arr.length; i++) {
                    var item = $(associate_arr[i]);
                    if (item.is(':checked')) {
                    	//var copy_link_id = jQuery.multipleSelect.linkEdit.replace(/%id%/g, item.val());
                        //window.open(copy_link_id);
                    }
                }
                jQuery.multipleSelect.clearAction();
				return true;
			}
		}
	},
	clearAction: function () {
		associate_select.val('notSelected');
	},
	drawForm: function () {
		var html = '<label for="associate_actions">Для отмеченных: <br /></label>'
	        + '<select name="action" id="associate_actions" disabled="disabled">'
	        + '<option value="notSelected" >--Не выбрано--</option>';
		
		
		html = html + '<option value="delete" >Удалить</option>'
	        + '<option value="send" >Отправить</option>'
	    + '</select>'
		+ '<input type="hidden" name="sub" value="" id="sub" />';
		
		return html;
		
	},
	drawCell: function (value) {
		return '<input class="' + jQuery.multipleSelect.rowCheckboxClass + '" value="' + value + '" name="id[]" type="checkbox" />';
		
	},
	drawHeadCell: function () {
		return '<input class="' + jQuery.multipleSelect.headCheckboxClass + '" value="" title="отметить все" type="checkbox" />';
		
	},
	/**
	 * Инициализация
	 * 
	 */
	init: function () {
		if (typeof jQuery.multipleSelect.actionsBlock != 'object')
			return;
		
		//alert('init');
		
		// Удаляем INPUT групповой обработки
		jQuery('input.' + jQuery.multipleSelect.rowCheckboxClass).remove();
		
		// Рисуем INPUT во всех TD
		jQuery('td.' + jQuery.multipleSelect.cellClass).each(function () {
			var value = jQuery('input[name="row"]', jQuery(this).parent('tr')).val();
			//alert(value);
			if (value)
				jQuery(this).html(jQuery.multipleSelect.drawCell(value));
		});
		// Рисуем INPUT во всех TH
		jQuery('th.' + jQuery.multipleSelect.cellClass).each(function () {
			jQuery(this).html(jQuery.multipleSelect.drawHeadCell());
		});
		
		// Обработка множественнных действий
	    associate_arr = $('input[type=checkbox].' + jQuery.multipleSelect.rowCheckboxClass);
	    associate_control = $('input.' + jQuery.multipleSelect.headCheckboxClass);
	    associate_select = $('select', jQuery.multipleSelect.actionsBlock);
	    
	 // Обработать события по выделению и снятию галочек с элементов checkbox
	    associate_arr.click(function(){
	        if ($(this).is(':checked')) {
	        	jQuery.multipleSelect.actionsBlock.show();
	            associate_select.prop('disabled', '');
	            if ($('input[type=checkbox].' + jQuery.multipleSelect.rowCheckboxClass + ':checked').length == associate_arr.length) {
	            	associate_control.prop('checked', true);
	            }
	        } else {
	            if (!associate_arr.is(':checked')) {
	            	jQuery.multipleSelect.actionsBlock.hide();
	                associate_select.prop('disabled', 'disabled');
	            }
	            associate_control.prop('checked', false);
	        }
	        jQuery.multipleSelect.clearAction();
	    });
	    // Кнопка "выделить все" и "сброс"
	    $(associate_control).bind('click', jQuery.multipleSelect.bindHeaderCheckbox);
	    
	    // Обработка действий
	    $(associate_select).unbind('change').bind('change', function(){
	        var action = $(this).val();
	        if (action != 'notSelected' && associate_arr.is(':checked')) {
	        	if (typeof jQuery.multipleSelect.actions[ action ] != 'undefined' 
	        		&& jQuery.multipleSelect.actions[ action ].callback()
	    		) {
	        		$(jQuery.multipleSelect.form).submit();
	        	};
	            
	        }
	    });
	    
	},
	/**
     * Событие при клике на основную галку в заголовке таблицы
     *
     */
	bindHeaderCheckbox: function ()
    {
        if ($(this).is(':checked')){
            $(associate_control).prop('checked', 'checked');
            associate_arr.prop('checked', 'checked');
            $(this).attr('title', 'снять выделение');
            if (associate_arr.size() > 0) {
            	jQuery.multipleSelect.actionsBlock.show();
                associate_select.prop('disabled', '');
            }
        } else {
            $(associate_control).prop('checked', false);
            associate_arr.attr('checked', false);
            $(this).attr('title', 'отметить все');
            jQuery.multipleSelect.actionsBlock.hide();
            associate_select.prop('disabled', 'disabled');
        }
        jQuery.multipleSelect.clearAction();
        
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
