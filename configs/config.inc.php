<?php
/*
 * 前端和管理界面共享全局变量.
 * Before 调用, <strong>ROOT</strong> must be setup.
 * CMS: ROOT=/admin/
 * 前端: ROOT=/fmxw/
 */
defined('HOME') or define('HOME', 'http://dixitruth.com/fmxw/');
defined('ROOT') or define('ROOT', '/');
defined('PACKAGE') or define('PACKAGE', 'fmxw');
defined('SEARCH') or define('SEARCH', 'search');

defined('CORESEEK_PORT') or define('CORESEEK_PORT', 9313);
defined('CORESEEK_PORT2') or define('CORESEEK_PORT2', 9312);

defined('TAB_LIST') or define('TAB_LIST', 10);
defined('PER_TOTAL') or define('PER_TOTAL', 10);
defined('LIMIT') or define('LIMIT', 25);
defined('ROWS_PER_PAGE') or define('ROWS_PER_PAGE', 25);

if (!isset($config)) {
  $config = array(
    'debug' => true, 
    'home' => HOME, 
    'base' => ROOT,
	'search' => 's.php',
    'header' => array(
        'lang' => 'zh_CN', 
        'charset' => 'UTF-8', 
        'title' => '负面新闻网', 
        'desc' => '负面新闻网.关于中国的负面新闻,比如明星,食品,体育,医疗,教育,人物,机构,娱乐,财经,政府等.底细,真相,还原真相,反映实际情况.', 
        'keywords' => '负面新闻,底细,真相,还原真相,反映实际情况', 
    ), 
    'css' => array(
        'bootstrap' => 'include/bootstrap/css/bootstrap.css', 
        'fmxw' => 'css/dixi.css', 
        'extra' => 'css/extra.css',
        'jquery-ui' => 'include/jqueryui/js/jquery-ui-1.8.22.custom.min.js', 
    ), 
    'js' => array(
        'jquery' => 'js/jquery-1.7.2.min.js', 
        'bootstrap' => 'include/bootstrap/js/bootstrap.min.js', 
        'bts' => 'include/bootstrap/js/bootstrap_search.js', 
        'gb_big5' => 'js/init.js', 
        'ga' => 'js/ga.js', 
        'cookie' => 'js/cookie.js',
        'easing' => 'js/jquery.easing.1.3.js', 
        'fancybox' => 'include/jquery.fancybox', 
        'fmxw' => 'js/dixi.js',
    ),
    'f0' => array(
        'c' => 'css/f0.css', 
        'l' => 'locale/f0.inc.php', 
        's' => 'f0.php', 
        't' => 'templates/0/',
    ), 
    'f1' => array(
        'c' => 'css/f1.css', 
        't' => 'templates/1/', 
        's' => 'f1.php',
    ),
    'f2' => array(
        'c' => 'css/f2.css', 
        't' => 'templates/2/', 
        's' => 'f2.php', 
    ),
    'f3' => array(
        'c' => 'css/f3.css',
        't' => 'templates/3/',
        's' => 'f3.php', 
    ),
    'f6' => array(
        'c' => 'css/f0.css', 
        'l' => 'locale/f0.inc.php', 
        's' => 's.php', 
        't' => 'templates/6/',
    ), 
    'include' => 'include/', 
    'img' => 'images/', 
    't' => 'templates/', 
    't0' => 'templates/0/', 
    't1' => 'templates/1/', 
    't2' => 'templates/2/', 
    't3' => 'templates/3/', 
    't6' => 'templates/6/', 
    't8' => 'templates/8/', 
    'shared' => 'templates/shared/', 
    'smarty' => 'configs/smarty.ini', 
    'favicon' => 'favicon.ico', 
    'browser' => browser_id(), 
    'phone' => '(866)789-5432', 
    'email' => 'admin@dixitruth.com', 
    'logo' => array(
        'logo_290x96' => 'images/logo_290x96.png', 
        'logo_130x60' => 'images/logo_130x60.png', 
        'logo_20x12' => 'images/logo_20x12.png',
    ),
	'footer' => array(
        'copyright' => '负面新闻网。底细，真相，事实传播媒体。', 
        'menu' => '负面新闻网。底细，真相，事实传播媒体。', 
    ),
	'wait' => '<img src="images/spinner.gif" width="16" height="16" border="0" />',
  );
}

/* 设置语言'中文', 和locale区域设置'zh_CN' */
function set_lang() {
    global $config;
    //echo "<pre>"; print_r($_COOKIE); print_r($_SESSION); echo "</pre>";
    if (isset($_COOKIE[PACKAGE]['language']) && isset($_SESSION[PACKAGE]['language']) && ($_COOKIE[PACKAGE]['language'] == $_SESSION[PACKAGE]['language']))
        return;

    if (isset($_COOKIE[PACKAGE]['language']))
        $_SESSION[PACKAGE]['language'] = $_COOKIE[PACKAGE]['language'];
    else
        $_SESSION[PACKAGE]['language'] = '中文';

    if (isset($config) && is_array($config['header']))
        $config['header']['lang'] = $_SESSION[PACKAGE]['language'] == '中文' ? 'zh_CN' : 'en';
}

function browser_id() {
    if (!isset($_SERVER['HTTP_USER_AGENT']))
        return 'unknown';
    if (strstr($_SERVER['HTTP_USER_AGENT'], 'Firefox'))
        $id = "firefox";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome'))
        $id = "safari";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome'))
        $id = "chrome";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Opera'))
        $id = "opera";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6'))
        $id = "ie6";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 7'))
        $id = "ie7";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 8'))
        $id = "ie8";
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 9'))
        $id = "ie9";
	else $id = "Unkown"; //for googlebot.com
    return $id;
}
?>
