<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>test-seohide</title>
<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript" src="base64.js"></script>
<script type="text/javascript" src="tt.seo.js"></script>
</head>

<body>

<?php  require ("encLink.php"); 

echo  encLink("<a  href='http://www.yandex.ru'>Яндекс</a> ");
echo  encLink("<a href='/link'>internal</a> ");
echo encLink("<a href='/letters/'>Письма</a>");

/*


*/




   ?>
</body>
</html>
