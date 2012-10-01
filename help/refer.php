<?php
session_start();
if(! ((isset($_SESSION['userid']) && $_SESSION['userid'])) ) {
    echo "<script>if(self.opener){self.opener.location.href='login.php';} else{window.parent.location.href='login.php';}</script>"; exit;
}

if(isset($_POST['js_process'])) {
    exec("ls -l /home/cibp/");
    // exec("nohup /home/ >/dev/null 2>&1 &");
    // sync: need result immediately.
    // system("kill -9 `ps -ef|grep sleep|grep -v grep|awk '{print $2}'`", $retval);
    system("/home/backup/DBs/monthly/process.bash >/dev/null 2>&1", $retval);
    if ($retval == 0) echo $du->errors['4'];
    else echo $du->errors['5'];
}
elseif(isset($_GET['js_cancel'])) {
    // need return variable, result immediately.
    // $ret = shell_exec("kill -9 `ps -ef|grep getCSV|grep -v grep|awk '{print $2}'`");
    system("kill -9 `ps -ef|grep getCSV|grep -v grep|grep -v vi|awk '{print $2}'`", $retval);
    // if ($retval == 0) echo $du->errors['2']; else echo $du->errors['3'];
    echo $retval;
}

elseif(isset($_GET['js_upload'])) {
    // async: don't need result, just call getCSV.bash to run background.
    // exec("nohup /home/backup/DBs/monthly/getCSV.bash >/dev/null 2>&1 &");
    $files = preg_replace("/,/", " ", $_GET['files']);
    echo "/home/backup/DBs/monthly/run.bash $files ";
    exec("/home/backup/DBs/monthly/run.bash $files >/dev/null 2>&1");
}

//Apache/2.2.14 (Win32) DAV/2 mod_ssl/2.2.14 OpenSSL/0.9.8l mod_autoindex_color PHP/5.3.1 mod_apreq2-20090110/2.7.1 mod_perl/2.0.4 Perl/v5.10.1
if(get_env()=='Windows')
    define('PATH', '.\\data\\june_2011\\');
else
    define('PATH', './data/pipe/');


?>
