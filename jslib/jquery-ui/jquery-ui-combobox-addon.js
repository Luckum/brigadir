/*
* jQuery combobox addon
* By: Rumyantsev Alexander
* Version 0.1
* Last Modified: 06/01/2011
* 
* На основе примера jQuery UI Combobox
*
* HERES THE CSS:
* .ui-autocomplete {
* 		max-height: 250px;
* 		overflow-y: auto;
* 		// prevent horizontal scrollbar //
* 		overflow-x: hidden;
* 		// add padding to account for vertical scrollbar //
* 		padding-right: 20px;
* 	}
* 	.ui-autocomplete-category {
* 		font-weight: bold;
* 		padding: .2em .4em;
* 		margin: .8em 0 .2em;
* 		line-height: 1.5;
* 	}
*/

(function( $ ) {

	/**
	* Новый элемент COMBOBOX, заменяющий стандартный селект на INPUT, BUTTON и AUTOCOMPLETE
	*/

	$.widget( "ui.combobox", {
		options: {
			buttonText: 'Show All Items',	// Подсказка на кнопке
			autocompleteSource: undefined,	// Данные для autocomplete TODO не работают
			forceElement: true,				// Обязательное соответствие имеющимся элементам 
			buttonImg: '',                  // Картинка внутри кнопки
            nameValue: ''					// Для по умолчанию после инициализации
		},
		_create: function() {
			var self = this,
				select = this.element.hide(),
				selected = select.children( ":selected" ),
				value = (this.options.nameValue && selected.val() == 0) ? this.options.nameValue : selected.val() ? selected.text() : '',
				cache = {},
				selectedElement = elementCount = categoryBeforeSelectedElement = categoryCount = 0;
			
			var autocompleteSource = this.options.autocompleteSource || function( request, response ) {
						/* cache */
						var term = request.term;
						if ( term in cache ) {
							response( cache[ term ] );
							return;
						}
						
						/* no cache */
						var matcher = new RegExp( $.ui.autocomplete.escapeRegex(term), "i" );
						data = select.children( "option" ).map(function() {
							var item = $(this).attr('rel')? eval('({' + $(this).attr('rel') + '})'):'';
							var text = $( this ).text();
							/*
							if ($.trim(item['section']) != '')
								text = text + ', ' + item['section'];
							*/							
							if ( this.value && ( !term || matcher.test(text) ) ) {
								var text_marked = text;
								if (term != '') {
									text_marked = text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												$.ui.autocomplete.escapeRegex(term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<b>$1</b>" );
								}
								return {
									label: text_marked,
									value: text,
									category: item['section'],
									option: this
								};
							}
						});
						cache[ term ] = data;
						response( data );
					};
			var showSelect = function() {
					// close if already visible
					if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
						input.autocomplete( "close" );
						return false;
					}
					input.autocomplete("widget").change();
					// pass empty string as value to search for, displaying all results
					input.autocomplete( "search", "" );
					input.focus();
					return false;
				};

            var comboboxControl = $( "<span></span>" )
                .insertAfter(select)
                .addClass("ui-combobox");
            var keyAllowed = $("<span></span>")
            	.appendTo(comboboxControl)
				.addClass("keys")
				.attr("title", "Введите часть наименования");
			var input = this.input = $( "<input>" )
				//.insertAfter(select)
                .appendTo(comboboxControl)
				.attr('name', $(select).attr('id') + '_name')				// Наименование контрола
				.val( value )
				.autocomplete({
					delay: 300,
					minLength: 0,
					source: autocompleteSource,
					select: function( event, ui ) {
						/*
						 * Событие при выборе элемента 
						 */
						//alert('Выбрали элемент: ' + ui.item.option.value);
						ui.item.option.selected = true;
						// Помечаем элемент в SELECT и вызываем событие CHANGE
						$(select).val(ui.item.option.value).change();
						self._trigger( "selected", event, {
							item: ui.item.option
						});
					},
					change: function( event, ui ) {
						//alert('Изменилось значение');
						if ( !ui.item ) {
							var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
									valid = false;
							select.children( "option" ).each(function() {
								if ( $( this ).text().match( matcher ) ) {
									this.selected = valid = true;
									return false;
								}
                                return true;
							});
							//$('#comment').text($('#comment').text() + "\n" + 'change' + valid);
							if ( !valid && self.options.forceElement) {
								// remove invalid value, as it didn't match anything
								$( this ).val( "" );
								select.val( "0" );
								input.data( "autocomplete" ).term = "";
								return false;
							} else {
								select.val( "0" );
								//alert('Value: ' + select.val() + ', ' + $( this ).val());
								return false;
							}
						}
                        return true;
					},
					open: function(event, ui) {
						var menu = $('ul.ui-autocomplete'),
							lineHeight = $('li:first', menu).height() - 1;
//						alert($('li:first', menu).height());
//						alert(selectedElement + '*' + lineHeight + ' ' + categoryBeforeSelectedElement + '*' + lineHeight);
						$(menu).scrollTop(selectedElement * lineHeight + categoryBeforeSelectedElement * lineHeight);
					}
				})
				.dblclick(showSelect)
				.focus(function(e) {
					this.select();
				})
                .mouseup(function(e){
                    e.preventDefault();
                })
				/* .blur(function(e){
					Дополнительная проверка для срабатывания при сабмите
					TODO проблема с быстрым сохранением, например:
					 * Ввели новое значение в поле и сразу нажали кнопку Submit, новое значение не сохраниться, 
					 * требуется первым делом покинуть элемент для срабатывания события onchange 
					 * 
					 
					if (!(input.autocomplete("widget").is(":visible")) && !self.options.forceElement)
					{
						var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
								valid = false;
						select.children( "option" ).each(function() {
							if ( $( this ).text().match( matcher ) ) {
								this.selected = valid = true;
								return false;
							}
						});
//						$('#comment').text($('#comment').text() + "\n" + 'blur' + valid);
						if ( !valid && self.options.forceElement) {
							// remove invalid value, as it didn't match anything
							$( this ).val( "" );
							select.val( "" );
							input.data( "autocomplete" ).term = "";
							return false;
						} else {
							select.val( "" );
							input.attr('name', $(select).attr('id') + '_name');
							return false;
						}
					}
//					alert('blur');
 					
 
					
				})*/
                ;

//			alert(value);
				
			input.data( "autocomplete" )._renderMenu = function( ul, items ) {
				var self = this,
					currentCategory = "";
				selectedElement = elementCount = categoryBeforeSelectedElement = categoryCount = 0;			// обнуление счетчика и выбранного элемента
				$.each( items, function( index, item ) {
					if ( item.category != undefined && item.category != currentCategory ) {
						ul.append( '<li class="ui-autocomplete-category">' + item.category + "</li>" );
						currentCategory = item.category;
						categoryCount++;
					}
					self._renderItem( ul, item );
				});
			};
			
			input.data( "autocomplete" )._renderItem = function( ul, item ) {
				var element = $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append( "<a>" + item.label + "</a>" );

				if (item.value == input.val())
				{
					$(element).children('a').attr('tabindex', -1).addClass('ui-state-hover');
					selectedElement = elementCount;
					categoryBeforeSelectedElement = categoryCount;
				}
				elementCount++;	
				return $(element)
					.appendTo( ul );
			};

			this.button = $( "<button>" 
                    + ($.trim(this.options.buttonImage) != ''? '<img src="' + this.options.buttonImage + '" alt="" />':'\u25bc')
                    + "</button>" )
				.attr( "tabIndex", -1 )
				.attr( "title", self.options.buttonText )
				.insertAfter( input )
				.click(showSelect)
				.css('font-size', '0.7em')
                ;
		},

		destroy: function() {
			this.input.remove();
			this.button.remove();
			this.element.show();
			$.Widget.prototype.destroy.call( this );
		}
	});
})( jQuery );
