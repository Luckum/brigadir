<?

function tpl_out($query_or_data, $tpl) {
    if (!is_array($query_or_data)) {
		$items = db_simple('array', $query_or_data);
	} else {
	    $items = $query_or_data;
	}

	$s = '';
	preg_match_all("'\{(.*?)\}'", $tpl, $m);	
	$m = $m[1];
	$search = array();
	
	if (is_array($m)) {
		foreach($m as $key=>$value) {
			$search[] = '{'.$value.'}';
		}
	}
	
	
	foreach($items as $item) {
	    $replace = array();
		if (is_array($m)) {
		    foreach($m as $key=>$value) {
				$replace[] = $item[$value];
			}
		}
		$s .= str_replace($search, $replace, $tpl);
	}
	return $s;
}


function options_out($query_or_data, $value) {
    if (!is_array($query_or_data)) {
		$items = db_simple('array', $query_or_data);
	} else {
	    $items = $query_or_data;
	}
	
	foreach($items as $item) {
		$sel = '';
		if ($item['value'] == $value) {
		    $sel = ' selected';
		}
	
		$s .= "<option value=\"{$item['value']}\"{$sel}>{$item['name']}</option>\n";
	}
	return $s;
}


function real_src($src, $files_folder='netcat_files') {
    $src = explode(':', $src);
    $src = '/'.$files_folder.'/'.$src[3];
    return $src;
}


function random_string($length = 8){
  $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
  $numChars = strlen($chars);
  $string = '';
  for ($i = 0; $i < $length; $i++) {
    $string .= substr($chars, rand(1, $numChars) - 1, 1);
  }
  return $string;
}

//                 1          2,3,4     5,6,7,8,9,0
// $names = array('коробка', 'коробки', 'коробок')
function num_with_string($num, $names) {
    if ($num >= 5 and $num <= 20) {
	    return $names[2];
	} else {
	    $rem = $num % 10;
		if ($rem == 1)
			return $names[0];
		elseif ($rem >= 2 and $rem <= 4)	
			return $names[1];
		else
			return $names[2];
	}
}

function sp_to_array($sp) {
	$items = db_get(db_arr, "{$sp}_ID as id, {$sp}_Name as name", "Classificator_{$sp}");
	$data = array();
	foreach($items as $item) {
		$data[$item['id']] = $item['name'];
	}
	return $data;
}



?>