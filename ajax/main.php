<?
session_start();
// Перестроить фильтры
error_reporting(E_ALL^E_NOTICE);
ini_set('display_errors', 1);

require_once 'common.php';


$action = $_GET['action'];
switch($action) {
	case 'save_price':
	    $s = save_price();
	    break;
}
echo $s;

function save_price() {
	print_r($_POST);
	extract($_POST);
	$values = array('Price' => intval($price));
	db_upd($values, 'Message'.$cid, "Message_ID = ".intval($mid));
}


?>