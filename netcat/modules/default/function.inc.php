<?php

require_once 'db.php';
require_once 'parser.php';
require_once 'common_lib.php';
require_once 'mail_utf8.php';
require_once 'parents_utf8.php';
require_once 'resizer_inside.php';
require_once 'filter.php';

require_once 'encLink.php';
//require_once 'seohide.php';


$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root.'/redirect.php';


function map() {
	global $current_catalogue;
	$root = $_SERVER['DOCUMENT_ROOT'];
	
	return implode('', file($root.'/netcat/modules/default/map.html'));
	
	
	$items = db_get(db_arr, '*', 'Subdivision', "Catalogue_ID = {$current_catalogue['Catalogue_ID']} and ExcludeMap = 0", "Parent_Sub_ID, Priority");
	foreach($items as $item) {
		$tree[$item['Parent_Sub_ID']][$item['Priority'] * 1000 + $item['Subdivision_ID']] = $item;
	}
	
	return out_tree(0, $tree);
}

function out_tree($parent, $tree) {
	if (!is_array($tree[$parent])) return;
	if ($parent == 0) {
		$class = ' class="siteMap"';
	}
	$s = "<ul{$class}>";
	foreach($tree[$parent] as $item) {
		$s .= "<li><a href=\"{$item['Hidden_URL']}\">{$item['Subdivision_Name']}</a>".out_tree($item['Subdivision_ID'], $tree)."</li>";
	}
	
	$s .= '</ul>';
	return $s;
}


function edit_props($classid, $tid) {
	$items = db_get(db_arr, '*', 'Message186', "Tovar_ID = $tid and Tovar_Class_ID = $classid", "Priority");
	$s = '<div style="padding:15px; border:1px Solid #333"><h4>Свойства товара</h4><table cellpadding="3" cellspacing="0" border="0" id="tprops" width="100%"><tr><th>Название свойства</th><th>Значение</th></tr>';
	foreach($items as $item) {
		// к айди здесь будем прибавлять тысячу, чтобы при разборе массива post можно было легко отличить новые свойства от старых
		$id = $item['Message_ID'] + 1000;
		
		$s .= "<tr class=\"sortableRow\">
		<td class=\"caption\">
		    <input type=\"hidden\" name=\"proporder[{$id}]\" value=\"{$item['Priority']}\" class=\"proporder\">
		    <input type=\"text\" name=\"propnames[{$id}]\" value=\"{$item['Name']}\">:
		</td>
		<td>
		      <input type=\"text\" name=\"props[{$id}]\" value=\"".htmlspecialchars($item['Value'])."\">
	    </td>
		</tr>\n";
	}
	$s .= "</table>
	<a href=\"#\" class=\"addprop\">Добавить свойство</a></div>";
	return $s;
}

function add_props($classid, $tid) {
	$props = $_POST['props'];
	$propnames = $_POST['propnames'];
	$proporder = $_POST['proporder'];
	
	if ($classid == 184) {
		$sample_id = 1;
	} elseif($classid == 185) {
		$sample_id = 1854;
	}
	$sample = db_get(db_row, '*', 'Message186', 'Message_ID = '.$sample_id);
	
	foreach($props as $i=>$v) {
		// Если поле названия заполнено
		if ($propnames[$i]) {
			if ($i <= 1000) {
				// Если это новое свойство
		        add_attrib($sample, $tid, $propnames[$i], $props[$i]);
			} else {
				// Если свойство существует
				$id = $i - 1000;
				$values = array(
					'Name' => mysql_real_escape_string($propnames[$i]),
					'Value' => mysql_real_escape_string($props[$i]),
					'Priority' => intval($proporder[$i])
				);
				db_upd($values, 'Message186', "Message_ID = $id");
			}
		} elseif($i > 1000) {
			$id = $i - 1000;
			db_del('Message186', "Message_ID = $id");
		}
	}
}

function add_attrib($sample, $tovar, $name, $value)  {
	$data = $sample;
	$unset = array('Message_ID', 'LastIP', 'LastUserAgent');
	foreach($unset as $v) {
		unset($data[$v]);
	}
	$data['Tovar_ID'] = $tovar;
	$data['Name'] = $name;
	$data['Value'] = $value;

	foreach($data as $key=>$value) {
		$data[$key] = mysql_real_escape_string($value);
	}
	
	db_ins($data, 'Message186');
}



function sort_by($caption, $get_var_name) {
	$link = replace_get('sort', $get_var_name);
	// $s = "<!--007  $link  -->";
	$s = '';
	$arr = '';
	$class = '';
	
	if ($_GET['sort'] == $get_var_name) {		
	    $class = ' class="active"';
		if ($_GET['dir'] == 'asc' or !isset($_GET['dir'])) {
			$link = replace_get('dir', 'desc', $link);
			$arr = ' <b>&uarr;</b>';
		} else {
			$link = replace_get('dir', 'asc', $link);
			$arr = ' <b>&darr;</b>';
		}
	} else {
		$link = replace_get('dir', 'asc', $link);
	}
	$s .= "<a href=\"$link\" title=\"Сортировать по '$caption'\"{$class}>{$caption}</a>{$arr}";
	return $s;
}


// Информация из корзины о кол-ве товаров и сумме
function cart_info($cart) {
	// Подсчет кол-ва товаров и общей суммы
	
	$total_qty = 0;
	$total_summa = 0;
	
	if ($cart) {
		foreach($cart as $cid=>$items) {
			if (!$items) continue;
			$ids = array_keys($items);
			$titems = db_get(db_arr, 'Message_ID, if(SpecialPrice > 0, SpecialPrice, Price) as Price', "Message{$cid}", "Message_ID in (".implode(', ', $ids).")");
			foreach($titems as $titem) {
				$qty = $items[$titem['Message_ID']];
				$summa = $titem['Price'] * $qty;
				
				$total_qty += $qty;
				$total_summa += $summa;
			}
		}
	}
    return compact('total_qty', 'total_summa');
}

// Корзина на странице оформления заказа
function cart_out() {
	if (!is_array($_SESSION['cart'])) {
	    $cart = unserialize($_SESSION['cart']);
	} else {
		$cart = $_SESSION['cart'];
	}
	$root = $_SERVER['DOCUMENT_ROOT'];
	$parser = new Parser();
	$html = '';
	

	foreach($cart as $classid=>$items) {
		if (!$items) continue;
		$ids = array_keys($items);
		$titems = db_simple(db_arr, "
			select a.Message_ID, a.Name, a.Country, if(a.SpecialPrice > 0, a.SpecialPrice, a.Price) as Price , a.ItemID, b.Producer_Name, a.OldImage, a.Image from Message{$classid} a 
			inner join Classificator_Producer b on (a.Producer = b.Producer_ID)
			where a.Message_ID in (".implode(', ', $ids).")
	    ");
		
		$aitems = db_get(db_arr, 'Tovar_ID, Message_ID, Name, Value', 'Message186', "Tovar_Class_ID = {$classid} and Tovar_ID in (".implode(', ', $ids).")", "Tovar_ID, Message_ID");
		
	
		foreach($aitems as $aitem) {
			$attribs[$aitem['Tovar_ID']][$aitem['Message_ID']] = $aitem;
		}
		
		foreach($titems as $titem) {
			$data = $titem;
			$data['Qty'] = $items[$titem['Message_ID']];
			$summa = $titem['Price'] * $data['Qty'];
			$data['Summa'] = price_format($summa);
			$src = $titem['Image'] ? real_src($titem['Image']) : $titem['OldImage'];
			$data['Src'] = resize_function($src, 108, 108, 2);
			
			$i = 0;
			$attribs_html = '';
			foreach($attribs[$titem['Message_ID']] as $aitem) {
				$attribs_html .=  "                                 <span class=\"w120\">{$aitem['Name']}:</span>{$aitem['Value']}<br />\n";
				$i++;
				if ($i == 6) break;
			}
			$data['Attribs'] = $attribs_html;
			$data['link'] = nc_message_link($titem['Message_ID'], $classid);
			$data['classID'] = $classid;
			
			$total_qty += $qty;
			$total_summa += $summa;
			
			$result['items'][] = $data;
		}
	}
	$result['Total'] = price_format($total_summa);
	
	return $parser->parse($root.'/tpl/cart_item.html', $result);
}

// Вывод телефонов для основного сайта
function phones() {
	global $current_catalogue;
	$data['HeaderLeftTown'] = $current_catalogue['HeaderLeftTown'];
	$data['HeaderRightTown'] = $current_catalogue['HeaderRightTown'];

    $sides = array('Left', 'Right');
	foreach($sides as $side) {
		$items = explode("\n", $current_catalogue['Header'.$side.'Phones']);
		$items = array_map('trim', $items);

		if ($_GET['debug']) {
			print_r($items);
		}

		$s = array();
		foreach($items as $item) {
			if (preg_match("'^\((\d+)\)\s*(.*)$'", $item, $m)) {
			    $s[] = "({$m[1]}) <big>{$m[2]}</big>";
			} else {
				$s[] = $item;
			}
		}
		$s = implode("<br />", $s);
		$data['Phones'.$side] = $s;
	}
	
	$root = $_SERVER['DOCUMENT_ROOT'];
	$parser = new Parser();
	return $parser->parse($root.'/tpl/phones.html', $data);
}

// Вывод телефонов для магазина
function phones2() {
	global $current_catalogue;
	$items = explode("\n", $current_catalogue['HeaderLeftPhones']);
	$items = array_map('trim', $items);
	$s = array();
	foreach($items as $i=>$item) {
		preg_match("'^\((\d+)\)\s*(.*)$'", $item, $m);
		$s[$i] = "<span class=\"green\">({$m[1]})</span> <big>{$m[2]}</big>";
	}
	return $s;
}

// Вывод хлебных крошек в каталоге продукции
function catalog_bread() {
	global $current_sub;
	$get = array();
	$u = $current_sub['Hidden_URL'];
	$bread[] = array(
		'Link' => $u,
		'Name' => $current_sub['Subdivision_Name']
	);
	if ($_GET['Производитель']) {
		$get[] = "Производитель={$_GET['Производитель']}";
		$bread[] = array(
			'Link' => $u.'?'.implode('&', $get),
			'Name' => $_GET['Производитель']
		);
	}

    $sort = unserialize($current_sub['SortData']);	
	foreach($sort as $sitem) {
		$name = $sitem['Name'];
		$name2 = str_replace(' ', '_', $name);
		if ($_GET[$name2]) {
			$value = mysql_real_escape_string($_GET[$name2]);
			// Диапазон
			$bname = $value;
			if ($sitem['Type'] == 2) {
				$v = explode('-', $value);
				$bname = "от {$v[0]} до {$v[1]}";
			}
			$get[] = "{$name}={$value}";
	        $bread[] = array(
				'Link' => $u.'?'.implode('&', $get),
				'Name' => $bname
			);
		}
	}
	foreach($bread as $key=>$item) {
		if ($key == count($bread)-1) {
			$bread[$key] = "<span>{$item['Name']}</span>";
		} else {
		    $bread[$key] = "<a href=\"{$item['Link']}\">{$item['Name']}</a>";
		}
	}
	return implode("", $bread);
}

function price_format($v) {
	return str_replace('|', '&acute;', number_format($v, 0, '', '|'));
}

function cart_change($cart, $cid, $tid, $action, $qty) {
    $cart[$cid][$tid] += 0;
    if ($action == 'cart_add') {
        if ($qty > -1) {
            $cart[$cid][$tid] += $qty;
        } else {
            $cart[$cid][$tid]++;
        }
    } else {
        if ($qty > -1) {
            if ($qty > 0) {
                $cart[$cid][$tid] = $qty;
            } else {
                unset($cart[$cid][$tid]);
                if (!$cart[$cid]) unset($cart[$cid]);
            }
        } else {
            $cart[$cid][$tid] = 1;
        }
    }
	return $cart;	
	
}

function rus2lat($s) {
	$rus[2]['search'] = array(
		'ай', 'эй', 'оу', 'кс'
	);	
	$rus[2]['replace'] = array(
		'i', 'a', 'o', 'x'
	);	
	
	$rus[1]['search'] = array(
		'a', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с',
		'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'э', 'ю', 'я'
	);
	$rus[1]['replace'] = array(
		'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's',
		'n', 'u', 'f', 'h', 'c', 'ch', 'sch', 'a', 'you', 'ya'
	);
	
	$s = str_replace($rus[2]['search'], $rus[2]['replace'], $s);
	$s = str_replace($rus[1]['search'], $rus[1]['replace'], $s);
	
	return $s;
}

function current_link($s) {
	return str_replace('<li', '<li class="active"', $s[0]);
}


function highlite_current_link($html, $url) {
	$search = array('/', '-', '_');
	$replace = array('\/', '\-', '\_');
	$url = str_replace($search, $replace, $url);
	
	$reg = "'<li>\s*<a([^<>]*)href\=(\'|\"){$url}(\'|\")([^<>]*)>([^<>]*)<\/a>\s*<\/li>'";
	return preg_replace_callback($reg, 'current_link', $html);
}

function letters_option($v=0) {
	$items = db_get(db_arr, 'Message_ID, Header, Company', 'Message195', 'Checked = 1', 'Header, Company');
	foreach($items as $item) {
		$data[$item['Header']][] = $item;
	}
	$s = '';
	foreach($data as $h=>$items) {
		$s .= "<optgroup label=\"{$h}\">";
		foreach($items as $item) {
			$sel = '';
			if ($item['Message_ID'] == $v) {
				$sel = ' selected="selected"';
			}
			$s .= "<option value=\"{$item['Message_ID']}\"{$sel}>{$item['Company']}</option>";
		}
		$s .= "</optgroup>";
	}
	return $s;
}

function site_sub_id($v) {
	// Исключения:
	$ex = array(2, 256, 265, 266, 270, 275);
	
	$items = db_get(db_arr, 'Parent_Sub_ID, Priority, Subdivision_Name, Hidden_URL, Subdivision_ID', 'Subdivision', "Subdivision_ID not in (".implode(',', $ex).")");
	
	foreach($items as $item) {
		$tree[$item['Parent_Sub_ID']][$item['Priority'] * 10000 + $item['Subdivision_ID']] = $item['Subdivision_ID'];
		$subs[$item['Subdivision_ID']] = $item;
	}
	foreach($tree as $key=>$items) {
		ksort($tree[$key]);
	}
	
	return "<!-- Подразделы корневого раздела -->\n".tree_nodes($tree, $subs, 0, 0, $v);
}

function tree_nodes($tree, $subs, $parent, $level, $v) {
	// echo "parent: $parent | level: $level | strlen: ".strlen($html)." <br>";
	$blank = str_repeat('&nbsp', $level * 4);
	$html = '';
	foreach($tree[$parent] as $subid) {
		$sel = '';
		if ($subid == $v) {
			$sel = ' selected="selected"';
		}
		$html .= "<option value=\"{$subid}\"{$sel}>{$blank}{$subs[$subid]['Subdivision_Name']}</option>\n";
		if (is_array($tree[$subid])) {
			$html .= "<!-- Подразделы {$subs[$subid]['Subdivision_Name']} -->\n".tree_nodes($tree, $subs, $subid, $level+1, $v);
		}
	}
	return $html;
}


function notify() {
	$root = $_SERVER['DOCUMENT_ROOT'];
	$parser = new Parser();
	
	$header = 'Заголовок';
	$src = '';
	$text = 'Это текст сообщения';
	
	return $parser->parse($root.'/tpl/notify.html', compact('header', 'src', 'text'));
	
	
	
}



?>