<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Статистика пересечений</title>
 <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script type="text/javascript" src="../jslib/jquery.min.js"></script>
  <script type="text/javascript" src="../js/jquery.form.js"></script>
<script src="jquery-1.9.1.js"></script>
<script src="jquery-ui.js"></script>
 <script>
  $(function() {
$( "#from" ).datepicker();

$( "#from" ).datepicker( "option", "dateFormat", "dd-mm-yy" );

$( "#to" ).datepicker();

$( "#to" ).datepicker( "option", "dateFormat", "dd-mm-yy" );


});
  
  
</script>

</head>

<body>





<div style="margin:0 auto;width:800px;height:100%;">
<div style="mwidth:100%;height:50px;margin-top:30px;">

<form action="index.php">
<label for="from">From</label>
<input   type="text" id="from" name="from" value="<?php if(isset($_GET['from'])){echo $_GET['from'];} ?>" />
<label for="to" >to</label>
<input type="text" id="to" name="to"   value="<?php if(isset($_GET['to'])){echo $_GET['to'];} ?>"/>
<input type="submit">
</form>

</div>
<div style="mwidth:100%;height:50px;margin-top:30px;">
С  <?php if(isset($_GET['from'])){echo $_GET['from'];}else{echo date('d-m-Y', mktime(0,0,0) ) ;} ?> по  <?php if(isset($_GET['to'])){echo $_GET['to'];}else{echo date('d-m-Y', time()) ;} ?>
</div>

<?php

$db = mysql_connect("mysqlserver", "z247786_main", "tln8kpxBO4hC");
mysql_query('SET NAMES utf8');
    mysql_select_db("z247786_main",$db);
	
if(isset($_GET['from'])){
//$u = strtotime('22-02-12 14:58');	

$from=strtotime($_GET['from']);
$to=strtotime($_GET['to'])+86400;

//echo date('d-m-Y', $from )
}
else{
	$from=mktime(0,0,0);
	$to=time();
	
}
	
	//echo "SELECT `id`,`user_id`,`word`,`type`,`date` FROM `words` WHERE `date` >= $from and `date` <= $to ";
	
	
	//SELECT * FROM `user` WHERE `u_id_grup` IN (SELECT `u_id_grup` FROM `user` GROUP BY `u_id_grup` HAVING COUNT(*) > 1)
	
	$result = mysql_query("SELECT `id`,`user_id`,`word`,`type`,`date` FROM `words` WHERE (`date` >= $from and `date` <= $to)  and `user_id`  IN (SELECT `user_id` FROM `words` GROUP BY `user_id` HAVING COUNT(*) > 1) ORDER BY `user_id`,`date`");
	
	
//	$result = mysql_query("SELECT `id`,`user_id`,`word`,`type`,`date` FROM `words` WHERE `user_id` IN (SELECT `user_id` FROM `words` GROUP BY `user_id` HAVING COUNT(*) > 1 and `date` >= $from and `date` <= $to) ORDER BY `user_id`,`date`");
//$result = mysql_query("SELECT `id`,`user_id`,`word`,`type`,`date` FROM `words` WHERE `date` >= $from and `date` <= $to ORDER BY `user_id` ");
$row='';
if (!$result)
{ 
echo "zapros ne proshel";
}  
elseif  (mysql_num_rows($result) > 0 )
{
	$c = 1;
	$text='';
	$reklama=0;
	$poisk=0;
	while ($myrow = mysql_fetch_array($result)) {
	
		if($row!==$myrow['user_id']){
			
			if($text!=='' && $reklama==1 &&  $poisk==1){
				echo"<div style='width:100%; height:1px; margin-top:40px;background-color:#ccc;'></div>";
				echo $text;
				$text='';
					
			}
				$text='';
				$reklama=0;
				$poisk=0;

		}
		$c++;
		if($myrow['type']=='search'){$bg="background-color:#c60464;"; $istochnik="поиск";$poisk=1;}
		else{$bg="background-color:#0468c6;"; $istochnik="реклама";$reklama=1;}
		
		$text=$text."<div style='width:100%; height:22px; margin-top:10px; '><div style='width:50px; float:left; height:22px;'>".$myrow['id']."</div><div style='width:50px; float:left; height:22px;'>".$myrow['user_id']."</div><div style='width:470px; float:left; height:22px;'>".$myrow['word']."</div><div style='width:140px; float:left; height:22px;'>".date('d.m.y  G:i:s', $myrow['date'])."</div><div style='width:70px; float:left;  color: #FFFFFF;padding:2px;padding-left:10px; height:22px;".$bg."'>".$istochnik."</div></div>";
		
		$row=$myrow['user_id'];

	}
	echo"<div style='width:100%; height:1px; margin-top:40px;background-color:#ccc;'></div>";
	if($text!=='' && $reklama==1 &&  $poisk==1){echo $text;}
}
//if(1==1){echo"ok";}

?>
<div style='width:100%; height:200px; margin-top:30px;'></div>
</div>
</body>
</html>