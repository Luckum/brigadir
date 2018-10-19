<?

function my_mail($to, $subject, $message, $from_email='', $from_name='') {
	$header  = 'MIME-Version: 1.0' . "\r\n";
	$header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
	
	if ($from_name == '') {
		$from_name = $_SERVER['HTTP_HOST'];
	} else {
		$from_name = '=?utf-8?B?'.base64_encode($from_name).'?=';
	}

	$from_email = ($from_email == '' ? 'info@'.$_SERVER['HTTP_HOST'] : $from_email);
	$header .= 'From: '.$from_name.' <'.$from_email.'>' . "\r\n";
	
	$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
	
	//$to = 'arcady117@gmail.com';
	mail($to, $subject, $message, $header);
}


// Отправка письма с вложениями
function my_mail_attach($to, $subject, $message, $from_email='', $from_name='', $mail_file, $mail_file_name, $mail_data='') {
	
	// $mail_file - массив путей файлов
	// $mail_file_name - массив имен файлов
	// $mail_data - ужене помню зачем сделал, не будем использовать
	
	$header  = 'MIME-Version: 1.0' . "\r\n";
    if ($mail_file or $mail_data) {
	    $boundary     = "--".md5(uniqid(time()));
        $EOL = "\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"$boundary\"$EOL";
    } else {
		$header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    }
	
	if ($from_name == '') {
		$from_name = $_SERVER['HTTP_HOST'];
	} else {
		$from_name = '=?utf-8?B?'.base64_encode($from_name).'?=';
	}

	$from_email = ($from_email == '' ? 'info@'.$_SERVER['HTTP_HOST'] : $from_email);
	$header .= 'From: '.$from_name.' <'.$from_email.'>' . "\r\n";
	
	$subject = '=?utf-8?B?'.base64_encode($subject).'?=';
    
    if ($mail_file or $mail_data) {
        $multipart  = "--$boundary$EOL";   
        $multipart .= "Content-Type: text/html; charset=utf-8$EOL";   
        $multipart .= "Content-Transfer-Encoding: base64$EOL";   
        $multipart .= $EOL; // раздел между заголовками и телом html-части 
        $multipart .= chunk_split(base64_encode($message));   
    
		$files = array();
		if (is_array($mail_file)) {
			foreach($mail_file as $i=>$item) {
				if ($mail_file_name[$i]) {
					$name = $mail_file_name[$i];
				} else {
					$name = basename($mail_file[$i]);
				}
				$files[] = array('path'=>$item, 'name'=>$name);
			}
		} else {
			if ($mail_file_name != '') {
				$name = $mail_file_name;
			} else {
				$name = basename($mail_file);
			}
			$files[] = array('path'=>$mail_file, 'name'=>$name);
		}
		
		foreach($files as $item) {
            $multipart .=  "$EOL--$boundary$EOL";   			
			$multipart .= "Content-Type: application/octet-stream; name=\"{$item['name']}\"$EOL";   
			$multipart .= "Content-Transfer-Encoding: base64$EOL";   
			$multipart .= "Content-Disposition: attachment; filename=\"{$item['name']}\"$EOL";   
			$multipart .= $EOL; // раздел между заголовками и телом прикрепленного файла 
			if ($mail_data) {
				$multipart .= chunk_split(base64_encode($mail_data));
			} else {
				$multipart .= chunk_split(base64_encode(file_get_contents($item['path'])));   
			}
		}
		$multipart .= "$EOL--$boundary--$EOL";
		//echo "001. Отправлено на {$to}<br>";
        mail($to, $subject, $multipart, $header);
    } else {
		//echo "002. Отправлено на {$to}<br>";		
		mail($to, $subject, $message, $header);
    }    
}

?>