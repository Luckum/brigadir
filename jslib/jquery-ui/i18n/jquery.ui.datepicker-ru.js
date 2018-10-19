/* Russian (UTF-8) initialisation for the jQuery UI date picker plugin. */
/* Written by Andrew Stromnov (stromnov@gmail.com). */

/**
 * Changed by Alex Rumyantsev
 * Set default function when select month & year
 * */

jQuery(function($){
	$.datepicker.regional['ru'] = {
		closeText: 'ОК', /*Закрыть*/
		prevText: '&#x3c;Пред',
		nextText: 'След&#x3e;',
		currentText: 'Сегодня',
		monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
		'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
		monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн',
		'Июл','Авг','Сен','Окт','Ноя','Дек'],
		dayNames: ['воскресенье','понедельник','вторник','среда','четверг','пятница','суббота'],
		dayNamesShort: ['вск','пнд','втр','срд','чтв','птн','сбт'],
		dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
		weekHeader: 'Нед',
		dateFormat: 'dd.mm.yy',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: '',
		
		onChangeMonthYear: function(year, month, inst) {
			dt = new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay),
			dateFmt = $.datepicker._get(inst, 'dateFormat'),
			formatCfg = $.datepicker._getFormatConfig(inst),
			timeAvailable = dt !== null && this.timeDefined;
			formattedDate = $.datepicker.formatDate(dateFmt, (dt === null ? new Date() : dt), formatCfg);
//			alert($(this).toSource());
			inst.input.val(formattedDate);
//			$.datepicker.("setDate", formattedDate);
			$.datepicker._setDate(inst, formattedDate, true);
//			$.datepicker._updateDatepicker(inst);
			inst.input.trigger("change");
		},
		
		//currentText: 'Сегодня',
		//closeText: 'ОК', /*Закрыть*/
		ampm: false,
		timeFormat: 'hh:mm tt',
		separator: ' ',
		timeOnlyTitle: 'Выбрать время',
		timeText: 'Время',
		hourText: 'Часы',
		minuteText: 'Минуты',
		secondText: 'Секунды'
		
		};
	$.datepicker.setDefaults($.datepicker.regional['ru']);
});

