<?
// Функции для работы с фильтром

// Инициализируется в макете так: $where = build_tovar_where($current_cc['Class_ID']), '')


function filter_control($where, $cid) {
	global $current_sub;
	$sort = unserialize($current_sub['SortData']);
	$s = '';

	foreach($sort as $sitem) {
		$name = $sitem['Name'];
		$type = $sitem['Type'];
		if ($type == 3) {
			// Список из имеющихся значений
			$s .= filter_items($where, $cid, $name);
		} elseif ($type == 2) {
			// Диапазон
			$s .= filter_by_vol($where, $cid, $name, false);
		} elseif ($type == 1) {
			// Да, нет
			$s .= filter_yes_no($where, $cid, $name);
		}
	}
	return $s;
}

// Вывод блока сортировки типа список из имеющихся значений
function filter_items($where, $classid, $fname, $field_in_cart=false) {
	global $current_sub;
	$u = $_SERVER['REQUEST_URI'];
	$pos = strpos($u, '?');
	$u = substr($u, 0, $pos);
    
	$get = $_GET;
	
	// Производителей выводить всех, т.е. игнорировать условие выбора товара по выбранным параметрам
	$producers_all = true;
	
	// Сбор условий
	if ($field_in_cart) {	// Признак, что поле находится в карточке товара (иначе в таблице свойств)
		if ($fname == 'Производитель') {
			$items = db_simple(db_arr, "
				select distinct b.Producer_Name as name
				from Message{$classid} a 
				    inner join Classificator_Producer b on (a.Producer = b.Producer_ID)
				".opt(!$producers_all, $where['query_join'])."
				where a.Checked = 1".opt($where['query_where'] and !$producers_all, ' and '.$where['query_where'])."
				order by b.Producer_Name
			");
		}
	} else {
		$items = db_simple(db_arr, "
			select distinct b.Value as name
			from Message{$classid} a 
			    inner join Message186 b on (b.Tovar_Class_ID = $classid and a.Message_ID = b.Tovar_ID)
				{$where['query_join']}
			where b.Name = '$fname'".opt($where['query_where'], ' and '.$where['query_where'])."
			order by name
		");
	}
	
	$data['items'] = array();
	
	if ($fname == 'Производитель') {
		$data['items'][] = array(
			'url' => $current_sub['Hidden_URL'], 
			'name' => 'Все производители',
			'class' => ''
		);
		
	}
	$fname2 = str_replace(' ', '_', $fname);
	foreach($items as $item) {
		$class = '';
		if ($_GET[$fname2] == $item['name']) {
			$class = ' class="active"';
		}
		
		if ($fname == 'Производитель') {
			$url = replace_get($fname, $item['name'], $current_sub['Hidden_URL']);
		} else {
			$url = replace_get($fname, $item['name']);
		}
		
		$data['items'][] = array(
			'url' => $url, 
			'name' => $item['name'],
			'class' => $class
		);
	}
	
	$parser = new Parser();
	$root = $_SERVER['DOCUMENT_ROOT'];
	
	$data['name'] = $fname;
	
	if ($_GET['test']) {
		print_r($data);
	}
	
	if ($data['items']) {
	    return $parser->parse($root.'/tpl/filter.html', $data);
	}
}

// Сортировка по объему
function filter_by_vol($where, $classid, $fname, $conditions) {
	if (!$conditions) {
		$conditions = array(
			array(0, 60),
			array(60, 120),
			array(120, 180),
			array(180, 240),
			array(240, 300)
		);
	}
	if ($fname == 'Объем бака') {
		// Если выбран производитель то значения диапазонов возьмем из настроек по производителю
		if ($_GET['Производитель']) {
			$p = str_replace('_', ' ', urldecode($_GET['Производитель']));
			$v = db_get(db_val, 'Value', 'Classificator_Producer', "Producer_Name = '".mysql_real_escape_string($p)."'");
			if (intval($v) > 0) {
				$conditions = array();
				for($i = 0; $i <= 10; $i++) {
					$conditions[] = array($i * $v, ($i+1) * $v);
				}
			}
		}
	}
	
	$items = db_simple(db_arr, "
		select distinct convert(b.Value, unsigned) as vol
		from Message{$classid} a inner join Message186 b on (b.Tovar_Class_ID = $classid and a.Message_ID = b.Tovar_ID)
		{$where['query_join']}
		where b.Name = '{$fname}'".opt($where['query_where'], ' and '.$where['query_where'])."
	");
	$cflag = array();
	foreach($items as $item) {
		foreach($conditions as $i => $cond) {
			if ($item['vol'] > $cond[0] and $item['vol'] <= $cond[1]) {
				// Выставление флага если значения в этом диапазоне есть
				$cflag[$i] = true;
			}
		}
	}

	$fname2 = str_replace(' ', '_', $fname);

	foreach($conditions as $key=>$item) {
		if ($cflag[$key]) {

			$class = '';
			if ($_GET[$fname2] == strval($item[0]).'-'.strval($item[1])) {
				$class = ' class="active"';
			}


			$data['items'][] = array(
				'url' => replace_get($fname, $item[0].'-'.$item[1]), 
				'name' => "от {$item[0]} до {$item[1]}",
				'class' => $class
			);

		}
	}
	
	$parser = new Parser();
	$root = $_SERVER['DOCUMENT_ROOT'];
	
	$data['name'] = $fname;
	
	if ($data['items']) {
	    return $parser->parse($root.'/tpl/filter.html', $data);
	}
	
}

function filter_yes_no($where, $cid, $fname) {
	$data['items'] = array();
	
	$items = array(
		array('name' => 'Да'),
		array('name' => 'Нет')
	);
	
	$fname2 = str_replace(' ', '_', $fname);
	foreach($items as $item) {
		$class = '';
		if ($_GET[$fname2] == $item['name']) {
			$class = ' class="active"';
		}
		$data['items'][] = array(
			'url' => replace_get($fname, $item['name']), 
			'name' => $item['name'],
			'class' => $class
		);
	}
	
	$parser = new Parser();
	$root = $_SERVER['DOCUMENT_ROOT'];
	$data['name'] = $fname;
	return $parser->parse($root.'/tpl/filter.html', $data);
}

function replace_get($key, $value, $u='') {
	if ($u == '') {
	    $u = $_SERVER['REQUEST_URI'];
	}
	$pos = strpos($u, '?');
	$get = array();
	if ($pos) {
		
		$q = substr($u, $pos + 1);		
	    $u = substr($u, 0, $pos);
		
		if (strpos($q, '&amp;')) {
			$q = explode('&amp;', $q);
		} else {
		    $q = explode('&', $q);
		}
		foreach($q as $item) {
			$item = explode('=', $item);
			$item[0] = urldecode($item[0]);
			$item[0] = str_replace('_', ' ', $item[0]);
			$item[1] = urldecode($item[1]);
			
			$get[$item[0]] = $item[1];
		}
	}
    
	$get[$key] = $value;
	
	$q = array();
	foreach($get as $key=>$value) {
		$q[] = "$key=$value";
	}
	
	$q = implode('&', $q);
	return $u.'?'.$q;
}

function remove_get($key) {
    $u = $_SERVER['REQUEST_URI'];

    $pos = strpos($u, '?');
	$get = array();
	if ($pos) {
		
		$q = substr($u, $pos + 1);		
	    $u = substr($u, 0, $pos-1);
		
		if (strpos($q, '&amp;')) {
			$q = explode('&amp;', $q);
		} else {
		    $q = explode('&', $q);
		}
		foreach($q as $item) {
			$item = explode('=', $item);
			$item[0] = urldecode($item[0]);
			$item[0] = str_replace('_', ' ', $item[0]);
			$item[1] = urldecode($item[1]);
			
			$get[$item[0]] = $item[1];
		}
	} else {
		return $u;
	}
    
	if (is_array($key)) {
		foreach($key as $k) {
			unset($get[$k]);
		}
		print_r($get);
	} else {
	    unset($get[$key]);
	}
	
	$q = array();
	foreach($get as $key=>$value) {
		$q[] = "$key=$value";
	}
	
	$q = implode('&', $q);
	return $u.'?'.$q;
}


// Построение условия выборки товаров исходя из заданных get параметров
function build_tovar_where($classID) {
	global $current_sub;	
	/* Код копируется практически одимн в один из системных настроек компонента */
	
	// Возьмем данные фильтра для текущего раздела
	$sort = unserialize($current_sub['SortData']);
	$where = array();
	
	// По производителю
	if ($_GET['Производитель']) {
		$name = mysql_real_escape_string($_GET['Производитель']);
		$p = db_get(db_val, 'Producer_ID', 'Classificator_Producer', "Producer_Name = '$name'");
		$p = intval($p);
		$where[] = "a.Producer = $p";
	}

    // Условие по другим параметрам
	$query_join = '';
	foreach($sort as $i=>$item) {
		// Если включен фильтр по этому параметру
		$name = $item['Name'];
		$name2 = str_replace(' ', '_', $name);
		if ($_GET[$name2]) {
			$value = mysql_real_escape_string($_GET[$name2]);    
			if ($item['Type'] == 3) {
				// Тип список
				$query_join .= " inner join Message186 p{$i} on (
					p{$i}.Tovar_Class_ID = $classID and 
					a.Message_ID = p{$i}.Tovar_ID and p{$i}.Name = '{$name}'
				)";
				$where[] = "p{$i}.Value = '$value'";
			} elseif ($item['Type'] == 2) {
				// Диапазон
				$v = explode('-', $value);
				$query_join .= " inner join Message186 p{$i} on (
					p{$i}.Tovar_Class_ID = $classID and 
					a.Message_ID = p{$i}.Tovar_ID and p{$i}.Name = '{$name}'
				)";
				$v[0] = intval($v[0]);
				$v[1] = intval($v[1]);
				$where[] = "p{$i}.Value between {$v[0]} and {$v[1]}";
			} elseif ($item['Type'] == 1) {
				// Да/нет
	
				if ($value == 'Да') {
					$query_join .= " inner join Message186 p{$i} on (
						p{$i}.Tovar_Class_ID = $classID and 
						a.Message_ID = p{$i}.Tovar_ID and p{$i}.Name = '{$name}'
					)";
					$where[] = "p{$i}.Value <> ''";
				} else {
					$query_join .= " left join Message186 p{$i} on (
						p{$i}.Tovar_Class_ID = $classID and 
						a.Message_ID = p{$i}.Tovar_ID and p{$i}.Name = '{$name}'
					)";
					$where[] = "(p{$i}.Value is null or p{$i}.Value = '')";
				}
			
			}
		}
	}
    $query_where = implode(' and ', $where);
	return compact('query_where', 'query_join');
}


?>