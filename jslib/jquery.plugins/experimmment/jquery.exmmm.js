/*!
 * jQuery Experimmment Plugin
 * version: 0.01 (2012-04-01)
 * @requires jQuery v1.7.1 or later
 *
 * Examples and documentation at: http://freedommm.me
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
;(function($) {

/*
	Usage Note:
	-----------
	
	$(document).ready(function() {
		$('#myForm').experimment({
			currency: 'RUB', // Валюта вложений и пересчета
			money: [], // Массив вложений
			additional: [], // Массив дополнительного притока
			dtRegistration: '2012-01-01', // Дата регистрации
			dtCash: '2012-12-21', // Дата вывода
			template: ':data',
			tplRealtime: '<span class="mmmRealtime">:resultMavroMoney :resultCurrencyMavroMoney (+:resultGrowth%)</span>',
			realtime: false,
			currencyPlural: {
			    'RUB': {
			        0: 'рубль',
			        1: 'рубля',
			        2: 'рублей'
			    }
			}
		});
	});

*/

/**
 * ajaxSubmit() provides a mechanism for immediately submitting
 * an HTML form using AJAX.
 */
$.fn.experimmment = function(options, value) {
	// fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
	if (!this.length) {
		log('Experimmment: no element selected');
		return this;
	}
	
	// Дергаем необходимые функции
	/*
	if (typeof options == 'string') {
		//callbacks.push(options.success);
	}
	*/
	
	// Массив курсов на даты в разрезе:
	// Депозит / Покупка, Продажа
	var exchangeRate = [];
	// Массив бонусов на даты в процентах
	var bonusSize = [];
	
	var dayPlural = {
        0: 'день',
        1: 'дня',
        2: 'дней'
	};
	
	options = jQuery.extend(true, {
		currency: 'RUB', // Валюта вложений и пересчета
		money: [], // Массив вложений
		additional: [], // Массив дополнительного притока
		dtRegistration: '2012-01-01', // Дата регистрации
		dtCash: '2012-12-21', // Дата вывода
		template: ':data',
		tplRealtime: '<span class="mmmRealtime">:resultMavroMoney :resultCurrencyMavroMoney (+:resultGrowth%)</span>',
		realtime: false,
		currencyPlural: {
		    'RUB': {
		        0: 'рубль',
		        1: 'рубля',
		        2: 'рублей'
		    }
		}
	}, options);

	
	var currencyPlural = options.currencyPlural;
	
	//alert(print_r(options));
	
	// Объект виджета
	var widget = this;
	var today = new Date();
	var todayStr = today.getFullYear() + '-' + leadingZero(today.getMonth() + 1, 2) + '-' + leadingZero(today.getDate(), 2);
	// Получаем количество дней в системе
	var resultDays = diffDate(options.dtRegistration, today);
	// Сколько дней до счастья
	var resultOutDays = diffDate(today, options.dtCash);
	// Вложенные средства
	var resultMoney = 0;
	// Текущая сумма в системе
	var resultMavroMoney = 0;
	// Cумма к выводу
	var resultOutMoney = 0;
	// Сумма при следующем изменении курса
	var tomorrowMoney = 0;
	// Сумма в текущий момент
	var realtimeMoney = 0;
	// Сумма в текущий момент
	var secondMoney = 0;
	var dtYesterday, dtTomorrow;
	
	
	// Массив дат для получения курсов и бонусов
	var dtArr = [];
	// Получаем сумму вложенных средств
	jQuery.each(options.money, function() {
		// Собираем сумму вложений
		resultMoney += this.sum;
		
		// Собираем даты для получения курсов и бонусов
		dtArr[dtArr.length] = this.dt;
	});
	// Получаем даты дополнительных средств
	jQuery.each(options.additional, function() {
		// Собираем даты для получения курсов и бонусов
		dtArr[dtArr.length] = this.dt;
	});

	// Добавляем сегодняшнюю дату
	dtArr[dtArr.length] = todayStr;
	// Добавляем дату вывода средств
	dtArr[dtArr.length] = options.dtCash;
	
	// Делаем запрос необходимых курсов
	// Этаже функция все посчитает и отрисует
	getRatesAndBonuses();
	
	//alert(Math.floor((resultOutMoney - resultMavroMoney) / (resultOutDays * 24 * 60) * 100));
	
	return this;
	
	/*
	1. Итог вложенных наличных в основной валюте (resultMoney)
	2. Общая сумма текущих вкладов в основной валюте по текущему курсу МАВРО (resultMavroMoney)
	3. Кол-во дней в системе (resultDays)
	4. Когда обещают (resultOutWhen)
	4. Сколько осталось дней (resultOutDays)
	5. Сколько заберу (resultOutMoney)
	*/
	
	function getRatesAndBonuses()
	{
		var url = "http://mmm.nwpf/rates.js?" + jQuery.param({
			'getRates': 1,
			'dt': dtArr
		}) + '&callback=?';
		
		jQuery.getJSON(
			url,
			function(data) {
				exchangeRate = data.rates;
				bonusSize = data.bonuses;
				//alert(print_r(data));
				/* Получаем текущее состояние дел: 
				 * 1. Сколько у нас МАВРО
				 * - Считаем все вложения, получаем бонус на дату, получаем курс на дату
				 * - Добавляем бонусы, получаем курс на дату
				 * 2. Сколько стоят наши МАВРО сегодня
				 * - умножаем текущее количество на сегодняшний курс
				 * 3. Сколько будут стоить на момент вывода с учетом депозитов и бонусов
				 * - умножаем на курс вывода
				 * - игнорируем суммы, которые обнуляться или пересчитываем их
				 */
				resultMavroMoney = mavroToMoney(getMavroToDate(today, true));
				//alert(print_r(resultMavroMoney));
				resultOutMoney = mavroToMoney(getMavroToDate(options.dtCash, false), options.dtCash);
				//alert(print_r(data));
				tomorrowMoney = mavroToMoney(getMavroToDate(data.tomorrow, false), data.tomorrow);
				//alert(print_r(tomorrowMoney));
				
				if (options.realtime)
					realtimeInit(data.yesterday, data.tomorrow);
				
				draw();
			}
		);
		
	}
	
	/**
	 * Инициализируем автообновление
	 * 
	 * @param yesterday - дата предыдущего изменения курса
	 * @param tomorrow - дата будущего изменения курса
	 */
	function realtimeInit(yesterday, tomorrow) {
		dtYesterday = new Date(yesterday.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3 0:0:0'));
		dtTomorrow = new Date(tomorrow.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3 0:0:0'));
		
		/*
		 * Интервал: 1000
		 * Доля: относительно секунды
		 * Старт от суммы: 
		 */
		window.setInterval(function() { getRealtimeMoney(); }, 1000);
		
	}
	
	/**
	 * Получаем сумму в реальном времени
	 * 
	 */
	function getRealtimeMoney()
	{
		dtNow = new Date();
		// Прошло секунд
		secBefore = Math.ceil((dtNow - dtYesterday) / (1000));
		// Должно пройти секунд
		secAfter = Math.ceil((dtTomorrow - dtNow) / (1000))
		
		// Всего секунд между сменой курсов
		secSum = secBefore + secAfter;
		
		// Всего рост между сменой курсов и за каждую секунду
		periodMoney = tomorrowMoney - resultMavroMoney;
		secondMoney = periodMoney / secSum;
		
		realtimeMoney = resultMavroMoney + (secondMoney * secBefore);

		var htmlReal = options.tplRealtime;
		htmlReal = htmlReal.replace(/:resultMavroMoney/g, number_format(
			realtimeMoney, 
			{decimals: 2, thousands_sep: '&#160;', dec_point : '<span class="mmmDec">,'}
		) + '</span>');
		htmlReal = htmlReal.replace(/:resultCurrencyMavroMoney/g, pluralStr(realtimeMoney, currencyPlural[options.currency]));
		
		var resultGrowth = Math.floor(realtimeMoney / resultMoney * 100) - 100;
		htmlReal = htmlReal.replace(/:resultGrowth/g, resultGrowth);
		
		jQuery('span.mmmRealtime', widget).html(htmlReal);
		//alert(resultMavroMoney);
		//alert(realtimeMoney);
		//alert(tomorrowMoney);
		
	}
	
	function draw()
	{
		var resultCurrency = options.currency;
		
		var html = options.template;
		html = html.replace(/:data/g, 
			'<p>Вложил: :resultMoney :resultCurrencyMoney</p>'
			+ '<p>Участвую в системе: :resultDays :resultStrDays</p>'
			+ '<p>Уже в системе: :tplRealtime</p>'
			+ '<p>Буду забирать через: :resultOutDays :resultStrOutDays</p>'
			+ '<p>Получу: :resultOutMoney :resultCurrencyOutMoney</p>'
		);
		html = html.replace(/:tplRealtime/g, options.tplRealtime);
		
		html = html.replace(/:resultMoney/g, number_format(resultMoney, {decimals: 0, thousands_sep: '&#160;'}));
		html = html.replace(/:resultCurrencyMoney/g, pluralStr(resultMoney, currencyPlural[resultCurrency]));
		html = html.replace(/:resultDays/g, resultDays);
		html = html.replace(/:resultStrDays/g, pluralStr(resultDays, dayPlural));
		
		html = html.replace(/:resultMavroMoney/g, number_format(resultMavroMoney, {decimals: 0, thousands_sep: '&#160;'}));
		html = html.replace(/:resultCurrencyMavroMoney/g, pluralStr(resultMavroMoney, currencyPlural[resultCurrency]));
		
		var resultGrowth = Math.floor(resultMavroMoney / resultMoney * 100) - 100;
		html = html.replace(/:resultGrowth/g, resultGrowth);
		
		html = html.replace(/:resultOutMoney/g, number_format(resultOutMoney, {decimals: 0, thousands_sep: '&#160;'}));
		html = html.replace(/:resultCurrencyOutMoney/g, pluralStr(resultOutMoney, currencyPlural[resultCurrency]));
		html = html.replace(/:resultOutDays/g, resultOutDays);
		html = html.replace(/:resultStrOutDays/g, pluralStr(resultOutDays, dayPlural));

		jQuery(widget).html(html);
		
		if (options.realtime)
			getRealtimeMoney();
		
		if (typeof options.afterLoad == 'function') {
			options.afterLoad();
		}
		
	}
	
	/**
	 * Получаем количество и доепозиты на дату учитывая сроки депозитов и бонусов.
	 * 
	 * Возвращаем массив: deposit => sum
	 * @param bool force - на основании параметра учитываем сроки заморозки
	 */
	function getMavroToDate(dt, force)
	{
		// Преобразуем в объект Date
		if (typeof dt == 'string')
			dt = new Date(dt.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3'));
		//alert(print_r(options));
		
		// Массив с суммами мавро
		var resultSum = {};
		
		// Получаем сумму вложенных средств
		jQuery.each(options.money, function() {
			if (typeof resultSum[this.dep] == 'undefined') {
				resultSum[this.dep] = 0;
			}
			var sum = this.sum;
			var sumTotal = sum;
			
			if (typeof bonusSize[this.dt] != 'undefined') {
				jQuery.each(bonusSize[this.dt], function(key, item) {
					sumTotal += (sum * parseFloat(item) / 100);
				});
			}
			
			if (typeof exchangeRate[this.dt] != 'undefined') {
				resultSum[this.dep] += Math.round(sumTotal / parseFloat(exchangeRate[this.dt][this.dep][0]) * 10000) / 10000;
			}
			
		});
		
		// Добавляем сумму дополнительных средств
		jQuery.each(options.additional, function() {
			if (typeof resultSum[this.dep] == 'undefined') {
				resultSum[this.dep] = 0;
			}
			if (typeof exchangeRate[this.dt] != 'undefined') {
				resultSum[this.dep] += Math.round(this.sum / parseFloat(exchangeRate[this.dt][this.dep][0]) * 10000) / 10000;
			}
		});
		//alert(print_r(resultSum));
		return resultSum;
		
	}
	
	/**
	 * Конвертируем МАВРО в деньги
	 */
	function mavroToMoney(mavroArr, dt)
	{
		// Преобразуем в объект Date
		if (typeof dt == 'undefined')
			dt = new Date();
		else if (typeof dt == 'string')
			dt = new Date(dt.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3'));
		
		// конвертируем в строку
		dt = dt.getFullYear() + '-' + leadingZero(dt.getMonth() + 1, 2) + '-' + leadingZero(dt.getDate(), 2);
		
		var resultSum = 0;
		// Пересчитываем в деньги
		jQuery.each(mavroArr, function(key, item) {
			if (typeof exchangeRate[dt] != 'undefined') {
				//alert(typeof exchangeRate[dt][key][1]);
				resultSum += Math.round(parseFloat(item) * parseFloat(exchangeRate[dt][key][1]) * 100) / 100;
			}
		});
		//alert(print_r(resultSum));
		return resultSum;
		
	}
	
	/**
	 * Возвращает разницу в днях между датами
	 * @param string|Date dt1
	 * @param string dt2
	 */
	function diffDate(dt1, dt2)
	{
		// Преобразуем в объект Date
		if (typeof dt1 == 'string')
			dt1 = new Date(dt1.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3'));
		if (typeof dt2 == 'string')
			dt2 = new Date(dt2.replace(/(\d+)-(\d+)-(\d+)/, '$1/$2/$3'));
		
		// Исключаем возможную разницу из-за минут и секунд
		dt2.setHours(dt1.getHours());
		dt2.setMinutes(dt1.getMinutes());
		dt2.setSeconds(dt1.getSeconds());
		
		// а тут мы вычисляем, сколько же осталось дней — находим разницу в миллисекундах и переводим её в дни
		var diffDays = dt2 > dt1 ? Math.ceil((dt2 - dt1) / (1000 * 60 * 60 * 24)) : 0; 
		return diffDays;
		
	}
	
	/**
	 * Получение слова относительно числа
	 * 
	 */
	function pluralStr(i, strArr) {
		function plural(a) {
			if (a % 10 == 1 && a % 100 != 11) {
				return 0;
			} else if (a % 10 >= 2 && a % 10 <= 4
				&& (a % 100 < 10 || a % 100 >= 20)
			) {
				return 1;
			} else {
				return 2;
			}
		}
		
		if (typeof strArr == 'undefined')
			return '';

		var form = plural(Math.floor(i));
		//alert(i + ' ' + form + ' ' + print_r(strArr));
		
		if (typeof strArr[form] != 'undefined') {
			return strArr[form];
		} else if (typeof strArr[1] != 'undefined') {
			return strArr[1];
		} else if (typeof strArr[0] != 'undefined') {
			return strArr[0];
		}
		
	}
	
	/**
	 * Форматирование чисел
	 * 
	 * var num = 0;
	 * number_format(num, {decimals: 0, thousands_sep: ',', dec_point : '.'})
	 * 
	 */
	function number_format(_number, _cfg) {
		function obj_merge(obj_first, obj_second) {
			var obj_return = {};
			for (key in obj_first) {
				if (typeof obj_second[key] !== 'undefined')
					obj_return[key] = obj_second[key];
				else
					obj_return[key] = obj_first[key];
			}
			return obj_return;
		}
		function thousands_sep(_num, _sep) {
			if (_num.length <= 3)
				return _num;
			var _count = _num.length;
			var _num_parser = '';
			var _count_digits = 0;
			for ( var _p = (_count - 1); _p >= 0; _p--) {
				var _num_digit = _num.substr(_p, 1);
				if (_count_digits % 3 == 0 && _count_digits != 0
						&& !isNaN(parseFloat(_num_digit)))
					_num_parser = _sep + _num_parser;
				_num_parser = _num_digit + _num_parser;
				_count_digits++;
			}
			return _num_parser;
		}
		if (typeof _number !== 'number') {
			_number = parseFloat(_number);
			if (isNaN(_number))
				return false;
		}
		var _cfg_default = {
			before : '',
			after : '',
			decimals : 2,
			dec_point : '.',
			thousands_sep : ','
		};
		if (_cfg && typeof _cfg === 'object') {
			_cfg = obj_merge(_cfg_default, _cfg);
		} else
			_cfg = _cfg_default;
		_number = _number.toFixed(_cfg.decimals);
		if (_number.indexOf('.') != -1) {
			var _number_arr = _number.split('.');
			var _number = thousands_sep(_number_arr[0], _cfg.thousands_sep)
					+ _cfg.dec_point + _number_arr[1];
		} else
			var _number = thousands_sep(_number, _cfg.thousands_sep);
		return _cfg.before + _number + _cfg.after;
	}
	
}


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
 * Добавление ведущих нулей
 * 
 * @param number
 * @param length
 * @returns {String}
 */
function leadingZero(number, length) {
	   
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
   
    return str;

}
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
