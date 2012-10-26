<?php

$memd = new Memcached();

$key = trim($key);
$result = $memd->get($key);

if ($result == null) {
	$sql = "select key from keywords where key like '%'" . mysql_real_escape_string($key) . "%'";
	$res = mysql_query($sql);
	
	if (mysql_num_rows($res)>0) {
		$row = mysql_fetch_row($res);
		$memd->set($key, $row, 0, 3600); //1 hour expires
	}
}
else {
	//关键词+1,放到前面 
}

$list = $memd->get($key);

foreach($list as $l) {
	$html = "<li>".$l."</li>";
}
?>
<script type="text/javascript">
$.ajax({ url: '/path-to-get-tags-as-json.php',
        type: "GET",
        contentType: "application/json",
        success: function(tags) {
            $( "#tags" ).autocomplete({
                source: tags
            });
        }
    });
</script>