<?php

error_reporting(E_ALL ^ E_NOTICE);
session_start();
define('ROOT', '../');
include_once(ROOT.'configs/mysql-connect.php');


class LoginAdmin 
{
  var $site, $dbh;
  function __construct(){
    $this->site = 'dixitruth_admin'; //$site;
    $this->site_id = 1;
    $this->dbh = mysql_connect_fmxw();
  }
  
  function initial() {
?>
<script language="javascript" type="text/javascript">
$(function () {
    $('li.login_tab', '#signup_n_login_container').click(function () {
        if ($('#signup_form_container').is(':visible')) {
            $('#login_form_container').show();
        }
    });

    var form = $('#login_form');
    $(form).submit(function (event) {
        event.preventDefault();
        $.ajax({
            type: $(this).attr('method'),
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function () {
                $('#msg').show();
            },
            success: function (data) {
				console.log(data);
                if (data instanceof Object) {
                    document.location.href = '/admin/index.php';
                } else {
                    $('#msg').hide();
                    if ($('#div1').length > 0) {
                        $('#div1').show();
                    } else {
                        $('<div></div>').attr({
                            'id': 'div1',
                            'class': 'noUser'
                        }).html("该用户不存在，请重新输入。").insertAfter(form);
                        $('#username').select().focus();
                    }
                    if (data instanceof Array) {
                        alert('==============' + data + '--------------');
                    }
                }
            }
        });
        return false;
    });

    var t = $('div.site_browser').attr('id');
    var site = t.substring(0, t.lastIndexOf('_'));
    var browser = t.substr(t.lastIndexOf('_') + 1);

    if ($.cookie('dixitruth_admin[username]') && $.cookie('dixitruth_admin[userpass]')) {
        $('#username').val($.cookie('dixitruth_admin[username]'));
        $('#password').val($.cookie('dixitruth_admin[userpass]'));
        $('#rememberme').attr('checked', true);
    } else {
        $('#rememberme').attr('checked', false);
    }
    // $('#username').select().focus();  
});
</script>
<link rel="stylesheet" type="text/css" href="login/login_admin.css" />
<div class="site_browser" id="<?=$this->site.'_'.$this->browser_id();?>"></div>
<div id="signup_n_login_container">
  <div class="forms_container">
    <div id="login_form_container" class="form_container">
      <form method="post" class="cf" id="login_form" action="<?=$_SERVER['PHP_SELF'];?>">
        <div class="input first">
          <input type="text" placeholder="用户名" id="username" name="user[username]" autocomplete="on">
        </div>
        <div class="input">
          <input type="password" placeholder="口令" id="password" name="user[userpass]" autocomplete="on">
        </div>
        <div class="input last">
          <input type="submit" value="登录" class="submit_btn" />
          &nbsp;
          <input type="button" value="关闭" onclick="$('#div_ls').hide();" />
          <span id="msg" name="msg" style="display: none"><img src="images/spinner.gif" width="16" height="16" border="0"></span> </div>
        <div class="input remember_me">
          <input type="checkbox" value="1" id="rememberme" name="user[rememberme]" class="checkbox_field">
          <span>记住我的选择</span> </div>
      </form>
    </div>
  </div>
</div>
<?
  }
  
  
  // user[pwd]	ILoveJesus, user[rememberme] 1, user[name]	OneFamily
  function check_user()
  {
    $username = mysql_real_escape_string(trim($_POST['user']['username']));
    $password =  $_POST['user']['userpass'];
    $rememberme = isset($_POST['user']['rememberme']) ? true : false;

    $query = "SELECT * FROM admin_users WHERE username='".$username."' AND password='" . $password . "'";
    $res = mysql_query($query);
    $total = mysql_num_rows($res);
    if ($total>0) {
      $username = ucfirst(strtolower($username));
      if($rememberme) {
        $expire = time() + 17280000;

        setcookie($this->site.'[username]', $username, $expire);
        setcookie($this->site.'[userpass]', $password, $expire);
      }
      else {
        setcookie($this->site.'[username]', NULL);
        setcookie($this->site.'[userpass]', NULL);
      }

      $row = mysql_fetch_assoc($res);
      $_SESSION[$this->site]['username'] = $row['username'];
      $_SESSION[$this->site]['userpass'] = $row['userpass'];
      $_SESSION[$this->site]['expire'] = time() + 30*60;

      $this->update_login_info($username, $row['uid']);
      return $row;
    }
    return false;
  }

  function update_login_info($username, $uid)  {
    $ip = $this->get_real_ip();
	$browser = $this->get_browser();
	$session = session_id();
    $query = "insert into login_info(uid,ip,browser,username,session,count,login_time,logout,logout_time, expired)
      values(".$uid.", '".$ip."', '".$browser."', '".$username."', '".$session."', 1, NULL, 'N', '', NOW() + INTERVAL 10 HOUR)
      on duplicate key update
      count = count+1,
	  login_time = NULL,
	  expired = NOW() + INTERVAL 10 HOUR,
	  session = '".$session."', 
      logout='N',
	  logout_time=''";
	mysql_query($query);
  }

  function browser_id() {
  	$id = '';
	if(strstr($_SERVER['HTTP_USER_AGENT'], 'Firefox')){ $id="firefox"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'Safari') && !strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome')){ $id="safari"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'Chrome')){ $id="chrome"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'Opera')){ $id="opera"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6')){ $id="ie6"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 7')){ $id="ie7"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 8')){ $id="ie8"; }
	elseif(strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 9')){ $id="ie9"; }
	return $id;
  }

  function get_browser() {
	return $_SERVER["HTTP_USER_AGENT"];
  }
  function get_real_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    else
      $ip=$_SERVER['REMOTE_ADDR'];
    return $ip;
  }
}

//////////////////////////
$login = new LoginAdmin();

if (isset($_POST['user'])) {
  $ret = $login->check_user();
  if($ret) echo json_encode($ret);
}
elseif(isset($_GET['logout'])) {
  session_unset();
  session_destroy();
  header('Location: admin/index.php');
}
else {
  $login->initial();
}

?>
