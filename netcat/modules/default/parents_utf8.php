<?

/* Функция для вывода фильтрующей формы перед списком объектов 
когда в объекте есть поля для связи с другими объектами
*/

function parent_filter_form($prompt, $items) {
/*
$prompt - текст перед формой
$items = array(
	array(
		'name'=> 'country', 
		'caption' => 'Страна',
		'query'=>'select Message_ID as value, Name as name from Message73 where Checked = 1'),
		'sprav'=>'Category'
	), 
	array(
		...
	)
)	
или 
$item = array(
    'name'=>'favcat',
    'caption'=>'',
    'query'=>'',
    'sprav'=>'CategoriesCharacterist'
);
*/
	if ($items['name']) {
	    $item = $items;
		unset($items);
		$items[0] = $item;
	}

	$s = "$prompt<br>";
	foreach($items as $i=>$item) {
		if ($item['sprav']) {
		    $sp = $item['sprav'];
		    $item['query'] = "select {$sp}_ID as value, {$sp}_Name as name from Classificator_{$sp} order by {$sp}_Priority";
		}
	
		$s .= "
	<form name=\"filter_form_{$i}\" action=\"\" method=\"post\">
	{$item['caption']} <select name=\"{$item['name']}\" action=\"\" onchange=\"document.filter_form_{$i}.submit()\">
		<option value=\"-1\"".opt(!isset($_SESSION[$item['name']]) or $_SESSION[$item['name']] == -1, " selected").">Всех</option>
		".options_out($item['query'], $_SESSION[$item['name']])."
	</select>
	</form>";
	}
	
	return $s;
}

/*
  Функция для вывода поля в форме добавления или изменения
*/

function parent_object_field($caption, $fname, $cid, $name_field, $id_field, $where, $value) {
// $caption - заголовок перед полем
// $fname - имя поля, 
// $cid - Class_ID объекта, с которым делается связь или название таблицы
// $name_field - имя поля или concat() полей, которые буду выводится в option, 
// $id_field - имя поля, значение которого будет браться как ID, 
// $where - условие выбора, 
// $value - текущее значение
    if ($where) {
	    $where = 'where '.$where;
	}

	$table = (is_numeric($cid) ? 'Message'.$cid : $cid);
	$s = "
	$caption:<br>
	<select name=\"{$fname}\">
		".options_out("select {$id_field} as value, {$name_field} as name from {$table} $where", $value)."
	</select><br>";
	
	return $s;
}


function parent_object_field_multiple($caption, $fname, $cid, $name_field, $id_field, $where, $value, $rows) {
	$table = (is_numeric($cid) ? 'Message'.$cid : $cid);
	$vals = explode(',', $value);
	
    if ($where) {
	    $where = 'where '.$where;
	}
	
	
	foreach($vals as $v) {
	    $vals2[$v] = $v;
	}
	$s = "
	$caption:<br>
	<select name=\"{$fname}\" multiple size=\"$rows\">
		".options_out("select {$id_field} as value, {$name_field} as name from {$table} $where", $vals2)."
	</select><br>";
	
	return $s;
}




/*
$where = array();
$where = parent_system('tovar', 'PCat',  $where);
$where = parent_system('mcoll', 'MCollection',  $where);
$query_where = implode(' and ', $where);
*/
function parent_system($name, $field_name,  $where){
	if ($_POST[$name]) {
		$_SESSION[$name] = $_POST[$name];
	} else {
		if (!isset($_SESSION[$name])) {
			$_SESSION[$name] = -1;
		}
	}
	if ($_SESSION[$name] != -1) {
	    $where[] = "a.{$field_name} = {$_SESSION[$name]}";
	}
	return $where;
}




?>