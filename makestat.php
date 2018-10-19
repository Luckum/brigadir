<?php

$db = mysql_connect("starki.mysql.ukraine.com.ua", "starki_brigadir", "dpzg8tgk");
mysql_query('SET NAMES utf8');
    mysql_select_db("starki_brigadir",$db);



//получаем реферер:

$vars_http_referer="/";

if (isset($_SERVER['HTTP_REFERER'])) {

$vars_http_referer=$_SERVER['HTTP_REFERER'];

}

//объявляем рабочие переменные

$query_argument="";

$query_value="";

//ниже - переменные в адресной строке

$text="";//Яндекс

$words="";//Rambler

$q="";//Google и mail.ru

$p="";//Yahoo

$r="";//Апорт

//Теперь ищем имя серверая в строке запроса
//echo $_SERVER['HTTP_REFERER'];
if (preg_match("/yandex.ru/i",$vars_http_referer) && preg_match("/yandsearch/i",$vars_http_referer)) {

	//http://www.yandex.ru/yandsearch?text=Never+cackle+till+your+egg+is+laid&stype=www
	
	//декодируем URL
	
	$query_argument=urldecode($vars_http_referer);
	//echo $query_argument;
	//ищем перменную с нужным названием
	
	$query_argument=strstr($query_argument,"text");
	
	
	if(strstr($_SERVER['REQUEST_URI'],"yclid")){$type="direct";}else{$type="search";}
	
	parse_str($query_argument);
	
	if ($text!="") {
		
		if (isset($_COOKIE['user_id'])){
			$user_id=intval($_COOKIE['user_id']);
			
		} 
		else{
			$result = mysql_query("SELECT  max(`user_id`) FROM `words`");
			$max_value = mysql_result($result, 0); 
			$user_id=$max_value+1;
			setcookie("user_id",$user_id,time()+9600000,'/','vash-brigadir.ru');
		}
		

	$date=time();
		
	
	$query_value=mysql_real_escape_string($text);
	//$query_value=$_SERVER['REQUEST_URI'];
$insert_zapros = mysql_query ("INSERT INTO `words` (`user_id`,`word`,`type`,`date`) VALUES ('$user_id', '$query_value', '$type','$date')");

	
	
	
	
	
	
	
	}
	//<a target="blank" href="http://www.vash-brigadir.ru/index3.php">Бригадир</a>
	//тоже самое с остальными... но!
	
	//Кодировка! Обратите внимание, что в некоторых случаях достаточно декодировать URL
	
	//как, например с Яндексом ниже, а вот с Яндексом ниже приходится еще и перекодировать с KOI-8 в Win
	
	//посмотрите на последовательность: сначала декодируем URL, затем парсируем запрос, и потом уже
	
	//конвертирует KOI в Win
	
} 


/*
elseif (preg_match("/www.yandex.ru/i",$vars_http_referer) && preg_match("/yandpage/i",$vars_http_referer)) {
	
	//http://www.yandex.ru/yandpage?&q=304172936&p=4&qs=text%3D%25C7%25D2%25C1%25CD%25CD%25C1%25D4%25C9%25CB%25C1%2B%25C1%25CE%25C7%25CC%25C9%25CA%25D3%25CB%25CF%25C7%25CF%2B%25D1%25DA%25D9%25CB%25C1%2B%25E7%25C1%25CC%25D8%25D0%25C5%25D2%25C9%25CE%26stype%3Dwww
	
	$query_argument=urldecode($vars_http_referer);
	
	$query_argument=strstr($query_argument,"text");
	
	parse_str($query_argument);
	
	if ($text!="") {
	
	$query_value=convert_cyr_string($text,"k","w");
	
	}

}  
*/




//декодирование UTF в Win

function lib_urldecode_u_to_w($str) {

$replace_what=array("/\%20/si",

"/\%D0%90/si",

"/\%D0%91/si",

"/\%D0%92/si",

"/\%D0%93/si",

"/\%D0%94/si",

"/\%D0%95/si",

"/\%D0%81/si",

"/\%D0%96/si",

"/\%D0%97/si",

"/\%D0%98/si",

"/\%D0%99/si",

"/\%D0%9A/si",

"/\%D0%9B/si",

"/\%D0%9C/si",

"/\%D0%9D/si",

"/\%D0%9E/si",

"/\%D0%9F/si",

"/\%D0%A0/si",

"/\%D0%A1/si",

"/\%D0%A2/si",

"/\%D0%A3/si",

"/\%D0%A4/si",

"/\%D0%A5/si",

"/\%D0%A6/si",

"/\%D0%A7/si",

"/\%D0%A8/si",

"/\%D0%A9/si",

"/\%D0%AA/si",

"/\%D0%AB/si",

"/\%D0%AC/si",

"/\%D0%AD/si",

"/\%D0%AE/si",

"/\%D0%AF/si",

"/\%D0%86/si",

"/\%D0%87/si",

"/\%D0%84/si",

"/\%D0%B0/si",

"/\%D0%B1/si",

"/\%D0%B2/si",

"/\%D0%B3/si",

"/\%D0%B4/si",

"/\%D0%B5/si",

"/\%D1%91/si",

"/\%D0%B6/si",

"/\%D0%B7/si",

"/\%D0%B8/si",

"/\%D0%B9/si",

"/\%D0%BA/si",

"/\%D0%BB/si",

"/\%D0%BC/si",

"/\%D0%BD/si",

"/\%D0%BE/si",

"/\%D0%BF/si",

"/\%D1%80/si",

"/\%D1%81/si",

"/\%D1%82/si",

"/\%D1%83/si",

"/\%D1%84/si",

"/\%D1%85/si",

"/\%D1%86/si",

"/\%D1%87/si",

"/\%D1%88/si",

"/\%D1%89/si",

"/\%D1%8A/si",

"/\%D1%8B/si",

"/\%D1%8C/si",

"/\%D1%8D/si",

"/\%D1%8E/si",

"/\%D1%8F/si",

"/\%D1%96/si",

"/\%D1%97/si",

"/\%D1%94/si",

"/\%21/si",

"/\%22/si",

"/\%23/si",

"/\%24/si",

"/\%25/si",

"/\%26/si",

"/\%27/si",

"/\%28/si",

"/\%29/si",

"/\%2B/si",

"/\%3D/si");

$replace_with=array(" ",

"А",

"Б",

"В",

"Г",

"Д",

"Е",

"Ё",

"Ж",

"З",

"И",

"Й",

"К",

"Л",

"М",

"Н",

"О",

"П",

"Р",

"С",

"Т",

"У",

"Ф",

"Х",

"Ц",

"Ч",

"Ш",

"Щ",

"Ъ",

"М",

"Ь",

"Э",

"Ю",

"Я",

"І",

"Ї",

"Є",

"а",

"б",

"в",

"г",

"д",

"е",

"ё",

"ж",

"з",

"и",

"й",

"к",

"л",

"м",

"н",

"о",

"п",

"р",

"с",

"т",

"у",

"ф",

"х",

"ц",

"ч",

"ш",

"щ",

"ъ",

"м",

"ь",

"э",

"ю",

"я",

"і",

"ї",

"є",

"!",

'"',

"№",

"^;",

"%",

":",

"?",

"(",

")",

"+",

"=");

$str=str_replace("\r\n","",$str);

$str=preg_replace($replace_what, $replace_with, $str);

return $str;

}

?>