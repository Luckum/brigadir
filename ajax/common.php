<?
$root = $_SERVER['DOCUMENT_ROOT'];
require_once $root.'/netcat/modules/default/function.inc.php';
require_once $root.'/vars.inc.php';

mysql_connect($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASSWORD);
mysql_select_db($MYSQL_DB_NAME);
mysql_query('set names cp1251');

?>