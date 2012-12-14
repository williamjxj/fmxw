<?php
# instead in php.ini, set PEAR PATH here.

define('SMARTY_DIR', ROOT . 'include/Smarty-3.0.4/libs/');
require_once (SMARTY_DIR . 'Smarty.class.php');
require_once ('MDB2.php');

defined("DBHOST") or define("DBHOST", "localhost");
defined('DBUSER') or define('DBUSER', 'dixitruth');
defined("DBPASS") or define("DBPASS", "dixi123456");
defined('DBNAME') or define('DBNAME', 'dixi');

defined('LOG_FILE') or define('LOG_FILE', 'test.xml.html');

class BaseClass extends Smarty {
    var $url, $self, $mdb2, $template_dir, $compile_dir, $config_dir, $cache_dir, $session;

    function __construct() {
        parent::__construct();
        $this -> url = $_SERVER["PHP_SELF"];
        $this -> self = basename($this -> url, '.php');
        // will extend in sub-class.

        $this -> caching = false; //true;
        //$this->caching = Smarty::CACHING_LIFETIME_CURRENT;
        $this -> auto_literal = true;
        $this -> template_dir = ROOT . 'templates/default/';
        $this -> compile_dir = ROOT . 'templates_c/';
        $this -> config_dir = ROOT . 'configs/';
        $this -> cache_dir = ROOT . 'cache/';
		
		//缺省设置:
        $this -> lang = isset($_SESSION[PACKAGE]['language']) ? $_SESSION[PACKAGE]['language'] : '中文';
        $this -> locale = $this -> lang == 'English' ? 'en' : 'cn';
		
		//时区设置:http://php.net/manual/en/function.date-default-timezone-set.php
		$timezone = "Asia/Shanghai";
		if(function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
    }

    public function pear_connect_admin() {
        $dsn = array('phptype' => 'mysqli', 'username' => DBUSER, 'password' => DBPASS, 'hostspec' => DBHOST, 'database' => DBNAME);
        $options = array('debug' => 2, 'persistent' => true, 'portability' => MDB2_PORTABILITY_ALL, );
        $mdb2 = MDB2::factory($dsn, $options);
        if (PEAR::isError($mdb2)) {
            die($mdb2 -> getMessage());
        }
        $mdb2 -> query("SET NAMES 'utf8'");
        return $mdb2;
    }

    function mysql_connect_fmxw() {
        $db = mysql_pconnect(DBHOST, DBUSER, DBPASS) or die(mysql_error());
        mysql_select_db(DBNAME, $db);
        mysql_query("SET NAMES 'utf8'", $db);
        return $db;
    }

    function get_session() {
        return session_id();
    }

    function set_default_config($array) {
        global $config;
        foreach ($array as $k => $v)
            $config[$k] = $v;
    }

    // new for the front-side.
    function write_file($content) {
        $log = ROOT . LOG_FILE;
        if (is_file($log)) $log .= '.' . rand(5,15);
        $fh = fopen($log, 'w') or die("can't open file: " . __FILE__ . __LINE__);        
        fwrite($fh, $content);
        fclose($fh);
    }

    function __p($vars, $debug=true) {
        if (!$debug) return;
        global $config;
        if (isset($config['debug']) && $config['debug']) {
        if (is_array($vars) || is_object($vars)) {
            echo "<pre>";
            print_r($vars);
            echo "</pre>";
        } else
            echo $vars . "<br>\n";
        }
    }

    function get_env() {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            if (preg_match('/Win32/i', $_SERVER['SERVER_SOFTWARE']))
                return 'Windows';
            return 'Unix';
        }
    }

    function browser_id() {
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'Firefox')) { $id = "firefox";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome')) { $id = "safari";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome')) { $id = "chrome";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')) { $id = "opera";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6')) { $id = "ie6";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 7')) { $id = "ie7";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 8')) { $id = "ie8";
        } elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 9')) { $id = "ie9";
        }
        return $id;
    }

    function get_sitemap_1($item = '') {
        $ary = array('dixi' => array('关于底细', 'About Dixi'), 'us' => array('联系我们', 'Contact Us'), 'privacy' => array('隐私保护', 'Privacy'), 'ads' => array('广告服务', 'Advertisement'), 'business' => array('商务洽谈', 'Business'), 'recruit' => array('底细招聘', 'Recruitment'), 'welfare' => array('底细公益', 'Charity'), 'customer' => array('客服中心', 'Customer Service Center'), 'navigator' => array('网站导航', 'Site Navigation'), 'law' => array('法律声明', 'Legal Notices'), 'report' => array('有害信息举报', 'Harmful SMS Report'), );
		$l = $_SESSION[PACKAGE]['language']=='English'?1:0;
        if ($item) return $ary[$item];
            //return $ary[$item][$l];
        else {
			$a = array();
			foreach($ary as $k=>$v) $a[$k] = $v[$l];
	        return $a;
		}
    }
    function get_sitemap($item = '') {
        $ary = array('dixi' => array('关于底细', 'About Dixi'), 'business' => array('商务洽谈', 'Business'), 'law' => array('法律声明', 'Legal Notices'), 'recruit' => array('人力资源', 'Human Resource'), 'report' => array('联系我们', 'Contact Us'), );
		$l = $_SESSION[PACKAGE]['language']=='English'?1:0;
        if ($item) return $ary[$item];
            //return $ary[$item][$l];
        else {
			$a = array();
			foreach($ary as $k=>$v) $a[$k] = $v[$l];
	        return $a;
		}
    }

    function __t($k) {
        $f = array();
        $l = $_SESSION[PACKAGE]['language'] == '中文' ? 'cn' : 'en';
        return $f[$k][$l];
    }

	/* 这样写的目的是让程序更加清楚。 */
	public function _get_label($array) {
		$ary = array();
        foreach ($array as $k => $v) $ary[$k] = $v[$this->locale];
		return $ary;
	}
	public function get_header_label($header) {
        return $this->_get_label($header);
    }
    public function get_footer_label($footer) {
        return $this->_get_label($footer);
    }

}
?>
