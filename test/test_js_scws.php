<?php
if(isset($_REQUEST['msg'])) {
	$returns = '';

	header("Content-type: text/html; charset=utf-8");
	header("Pragma: no-cache");
	header("Expires: 0");
	
	#获取内容TAGS
	$msg = strip_tags($_REQUEST['msg']);
	if (!isset($msg) || empty($msg))
	   $myContent = " ";
	else {
		$myContent = $msg;
		if (get_magic_quotes_gpc()) $myContent = stripslashes($myContent);
	}
	echo $myContent."<br>\n";
	$xattr = '~v';
	$limit = isset($_REQUEST['limit']) ?  $_REQUEST['limit'] : 5;
	$cws = scws_new();
	$cws->set_charset('utf8');
$cws->set_dict(ini_get('scws.default.fpath') .  '/dict.utf8.xdb');
$cws->set_rule(ini_get('scws.default.fpath') .  '/rules.utf8.ini');
	
	$cws->send_text($myContent);
	$list = $cws->get_tops($limit, $xattr);
	settype($list, 'array');
	foreach ($list as $tmp){
		echo "<pre>"; print_r($tmp); echo "</pre>";
		$returns .= $tmp['word']." ";
	}
	$cws->close();
	echo $returns;
}
else {

////////////////////////////////////////////
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="./bootstrap/css/bootstrap.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<div class="container">
  <div class="hero-unit">
    <div>
      <input name="tagname" type="text" id="tagname" size="100" value=""/>
      <input type="button" class="btn btn-primary btn-large" value="可用TAG" />
    </div>
    <div id = "tagid"></div>
  </div>
</div>
<script type="text/javascript">
$(function() {
	$('input:button').click(function() {
		var url = '<?=$_SERVER['PHP_SELF'];?>';

		var message = $('#tagname').val();
		if(/^\s*$/.message) message = "需要进行分词的字段"; 
		$.get(url +'?msg='+message, function(s) {
			$('#tagid').html(s);
		});
		return false;
	});
});
</script>
<?php
}
?>
