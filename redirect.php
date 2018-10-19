<?

if (preg_match("'\/news\/news\_id\=(\d+)\.html'", $_SERVER['REQUEST_URI'], $m)) {
    $row = db_get(db_row, '*', 'Message181', "Original_ID = ".intval($m[1]));

    // print_r($row);
    // echo strtotime($row['Date']);


    $dt = strtotime($row['Date']);
    // die(strval($dt));
    
    $dt = date('Y/m/d', $dt);

    header('HTTP/1.1 301 Moved Permanently');
    header("Location:/news/{$dt}/news_{$row['Message_ID']}.html");
    exit();
}


/*if (strpos($_SERVER['HTTP_HOST'], 'www.') === false) {
	$u = "http://www.{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	header('HTTP/1.1 301 Moved Permanently');
	header("Location:{$u}");
	exit();
}*/

/*if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
    $u = "https://vash-brigadir.ru{$_SERVER['REQUEST_URI']}";
    header('HTTP/1.1 301 Moved Permanently');
    header("Location:{$u}");
    exit();
}*/


?>