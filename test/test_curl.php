<<<<<<< HEAD
<?php
$ch = curl_init("http://www.example.com/");
$fp = fopen("example_homepage.txt", "w");

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);

curl_exec($ch);
curl_close($ch);
fclose($fp);
?>
=======
<?php
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "http://www.example.com/");

curl_setopt($ch, CURLOPT_HEADER, 0);

curl_exec($ch);

curl_close($ch);
?>
>>>>>>> 457dcb5e1f90ba3015149a9b6fccd8546d6a199e
