<?

$db = '`arcady_brigadir`';
$items = db_simple(db_arr, "
SELECT s.id AS static_id, t.id, t.name, t.address, t.serial, l.label, s.content, t.tpl
FROM {$db}.nw_pages t
LEFT JOIN {$db}.nw_pages_label l ON t.id = l.cms_page_id
AND `action`
IN (
'input_build', 'content_build', 'textarea_build'
)
AND `param` >0
LEFT JOIN {$db}.nw_pages_staticc s ON l.param = s.id
WHERE 1
AND deleted =0
AND host = 'brigadir'
AND lang = 'ru'

");

foreach($items as $item) {
	$content_key = 'content-'.$item['label'];
	$item[$content_key] = $item['content'];
	
	//echo "{$item['id']}-{$item['label']}<br>";
	unset($item['content']);
	unset($item['label']);
	$meta = unserialize($item['serial']);
	unset($item['serial']);
	$item['title'] = $meta['TKDtitle'];
	$item['keyw'] = $meta['TKDkeywords'];
	$item['descr'] = $meta['TKDdescription'];
	
	if (!$data[$item['id']]) {
	    $data[$item['id']] = $item;
	} else {
		$data[$item['id']][$content_key] = $item[$content_key];
	}
}

//print_r($data[359]);
//die('Конец');

$sample = db_get(db_row, '*', 'Message1', 'Message_ID = 12');

foreach($data as $item) {
	$original_id = $item['id'];
	if ($row = db_get(db_row, "*", 'Message1', "Original_ID = {$original_id}")) {
		
		// Сейчас сделаем только обновление метатегов для случаев когда эти метатеги не были изменены вручную
		// Это решаем проблему совмещения оригинальных длинных мета и неткатовских коротких
		
		$values = array();
		if ((strpos($item['title'], $row['ncTitle']) !== false) and $item['title'] != $row['ncTitle']) {
		    $values['ncTitle'] = $item['title'];
		}
		if (strpos($item['keyw'], $row['ncKeywords']) !== false and $item['keyw'] != $row['ncKeywords']) {
		    $values['ncKeywords'] = $item['keyw'];
		}
		
	
		/*
		$row['ncDescription'] = $item['descr'];		
		$row['TextContent'] = $item['content-content'];
		$row['Original_Name'] = $item['content-title'];
		$row['Original_Right'] = $item['content-right'];
		*/
		
		if ($values) {
			foreach($values as $key=>$value) {
				$values[$key] = mysql_real_escape_string($value);
			}
			
			db_upd($values, 'Message1', "Message_ID = {$row['Message_ID']}");
			echo 'Updated, original_id = '.$original_id."<br>\n";
		}

	} else {
		echo 'Не добавляем';
	    // add_text_object($sample, $item);
	}
}


echo 'Закончено';

function add_text_object($sample, $item) {
	$data = $sample;
	$unset = array('Message_ID', 'LastIP', 'LastUserAgent');
	foreach($unset as $v) {
		unset($data[$v]);
	}
	$data['ncTitle'] = $item['title'];
	$data['ncKeywords'] = $item['keyw'];
	$data['ncDescription'] = $item['descr'];
	$data['TextContent'] = $item['content-content'];
	$data['Original_ID'] = $item['id'];
	$data['Original_Name'] = $item['content-title'];
	$data['Original_Right'] = $item['content-right'];
	$data['Original_URL'] = $item['address'];
	$data['Original_Tpl'] = $item['tpl'];
	
	foreach($data as $key=>$value) {
		$data[$key] = mysql_real_escape_string($value);
	}
	
    db_ins($data, 'Message1');
}




?>