<?

define(db_arr, 1);
define(db_row, 2);
define(db_val, 3);

function db_del($table, $where='') {
	$where = build_where($where);
    $q = "delete from $table $where";
	mysql_query($q) or die($q.mysql_error());
	return mysql_affected_rows();	
}

function db_ins($values, $table) {
    $keys = array_keys($values);
	$vals = array_values($values);
	foreach ($vals as $key=>$value) {
	    $vals[$key] = "'".$vals[$key]."'";
	}
    $q = "insert into $table (".implode(',', $keys).") values (".implode(', ', $vals).")";
	mysql_query($q) or die($q.mysql_error());
}

function db_upd($values, $table, $where='') {
	$upd = array();
	$where = build_where($where);
	foreach ($values as $key=>$value) {
		$upd[] = "$key = '$value'";
	}
    $q = "update $table set ".implode(',', $upd)." $where";
	mysql_query($q) or die($q.mysql_error());
	return mysql_affected_rows();	
}

function get_result_type($type) {
	if (is_numeric($type)) {
		switch ($type) {
			case db_arr:
				$type = 'array';
				break;
			case db_row:
				$type = 'row';
				break;
			case db_val:
				$type = 'value';
				break;
		}
	}
	return $type;
}

function db_get($type, $field, $table, $where='', $order='', $limit='') {
	$type = get_result_type($type);
    $where = build_where($where);
	$order = ($order == '' ? '' : "order by $order");
	$limit = ($limit == '' ? '' : "limit $limit");
	if (is_numeric($table)) {
		$table = 'Message'.$table;	
	}
	$q = "select $field from $table $where $order $limit";
    $r = mysql_query($q) or die($q.'<br>'.mysql_error());

	if ($type == 'noresult') {
		return;	
	}

	if (mysql_num_rows($r) > 0) {	
	    switch ($type) {
	        case 'value':
	            $row = mysql_fetch_row($r);
                return $row[0];
                break;
	        case 'row':
	            $row = mysql_fetch_assoc($r);
                return $row;
                break;
	        case 'array':
			while ($row = mysql_fetch_assoc($r)) {
	                $res[] = $row;
				}
                return $res;
                break;
        }
	} else {
		switch ($type) {
			case 'value':
				return false;
				break;
			case 'row':
				return array();
				break;
			case 'array':
				return array();
				break;
		}
	}
}

function db_make_array($array, $key) {
    $result = array();
    foreach($array as $item) {
	    $result[$item[$key]] = $item;
	}
	return $result;
}

function build_where($where='') {
    // ƒопустимые форматы where
	// 1.  $whare = "f1 = 'A' and (f2 = 'B' or f3 < 0)";
	// 2.  $whare = array('f1' => 'A', 'f2' => 'B');  трактуютс€ как условие "равно"
    if ($where == '') {
	    return '';
	}
    if (is_array($where)) {
	    $w = array();
        foreach ($where as $key=>$value) {
		    $w[] = "$key = '$value'";
	    }
		if (count($w) > 0) {
		    return 'where '.implode(' and ', $w);
		}
	} else {
	    return 'where '.$where;
	}
}

function db_simple($type, $query) {
	$r = mysql_query($query) or die($query.'\n<br>'.mysql_error());
	$type = get_result_type($type);

	if (mysql_num_rows($r) > 0) {	
	    switch ($type) {
	        case 'value':
	            $row = mysql_fetch_row($r);
                return $row[0];
                break;
	        case 'row':
	            $row = mysql_fetch_assoc($r);
                return $row;
                break;
	        case 'array':
			    while ($row = mysql_fetch_assoc($r)) {
	                $res[] = $row;
				}
                return $res;
                break;
        }
	} else {
		switch ($type) {
			case 'value':
				return false;
				break;
			case 'row':
				return array();
				break;
			case 'array':
				return array();
				break;
		}
	}
}

function db_simple_simple($query) {
	mysql_query($query) or die(mysql_error());
}

function db_last_ins() {
    $r = mysql_query("select last_insert_id() as last");
	$row = mysql_fetch_row($r);
	return $row[0];
}

function db_prepare_string($s, $strip_tags=true) {
	if ($strip_tags) {
		$s = strip_tags($s);
	}
	return mysql_real_escape_string($s);
}

?>