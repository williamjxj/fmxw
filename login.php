<?php
session_start();
if (isset($_POST['js_check'])) {
	if(check()) echo 'Y';
	else echo 'N';
}
elseif(isset($_GET['logout'])) {
	session_unset();
	session_destroy();
	init();
}
else {
	init();
}
exit;

///////////////////////////////

function init()
{
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="login/css/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="login/login.css" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript" src="login/js/cookie.js"></script>
<script type="text/javascript" src="login/js/jquery.validate.min.js"></script>
<script type="text/javascript" src="login/js/bootstrap.popover.js"></script>
<div class="logo"></div>
<div id="container">
  <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="form_id" class="well">
    <fieldset>
    <legend><img src="login/login-icon.png" /><strong>用户注册</strong></legend>
    <div class="control-group">
      <label class="control-label" for="username">用户名：</label>
      <div class="controls">
        <div class="input-prepend"> <span class="add-on"> <i class="icon-user"></i></span>
          <input name="username" id="username" type="text" placeholder="用 户 名" class="input-xlarge" data-content="用户名栏不能为空。" data-original-title="用户名验证" />
        </div>
      </div>
    </div>
    <div class="control-group">
      <label class="control-label" for="password">口令：</label>
      <div class="controls">
        <div class="input-prepend"> <span class="add-on"><i class="icon-lock"></i></span>
          <input name="password" id="password" type="password" placeholder="口 令" class="input-xlarge" data-content="口令栏不能为空。" data-original-title="口令验证"/>
        </div>
      </div>
    </div>
    <div class="control-group">
      <div class="controls">
        <label class="checkbox">
        <input type="checkbox" id="rememberme" name="rememberme">
        记住我的选择！ </label>
      </div>
    </div>
    <div class="control-group">
      <div align="center">
        <button type="submit" class="btn btn-primary">登 录</button>
        <img src="login/loading.gif" width="32" height="32" border="0" style="display:none;" /> </div>
    </div>
    <div class="control-group error">
      <label id="error"></label>
    </div>
    </fieldset>
  </form>
</div>
<!--hr width="60%" style="margin:0 auto" />-->
<div class="copyright">Copyright &copy; 2012 <abbr title="dixiTruth Inc">dixiTruth, Inc</abbr>. All rights reserved.</div>

<script type="text/javascript">
$(function() {
	var validator = $('#form_id').validate({
		rules: {
			username: "required",
			password: "required"
		},
		messages: {
			username: "",
			password: ""
		},
		
		highlight:function(element, errorClass, validClass) {
		  $(element).parents('.control-group').addClass('error');
		},
		unhighlight: function(element, errorClass, validClass) {
		  $(element).parents('.control-group').removeClass('error');
		  $(element).parents('.control-group').addClass('success');
		}
	});

	var form = $('#form_id');
	$('input', form).focus(function() {
		if ($('#error').html().length>0)
			$('#error').empty().parent('div').hide();
	});
	
	form.submit(function(e) {
		e.preventDefault();
		
		if(!validator.element('#username')) {
			$('#username').closest('.control-group').removeClass('success').addClass('error');
			$('#username', '#form_id').popover('show');
			return false;
		}
		if(!validator.element('#password')) {
			$('#password').closest('.control-group').removeClass('success').addClass('error');
			$('#password', '#form_id').popover('show');
			return false;
		}

		$.ajax({
			type: form.attr('method'),
			url: form.attr('action'),
			data: form.serialize() + '&js_check=1',
			beforeSend: function() {
				$('button:submit', form).attr('disabled', true).next('img').fadeIn();
			},
			success: function(succ) {
				if(succ == 'Y')
					document.location.href='/fmxw/';
				else {
					var msg = "登录信息不正确，请重新输入。";
					$('#error').html(msg).parent('div').fadeIn(100);
				}
				$('button:submit', form).attr('disabled', false).next('img').fadeOut();
			}
		});
		return false;
	});
	
	if( $.cookie("fmxw[username]") && $.cookie("fmxw[password]") ) {
		$('#username').val($.cookie("fmxw[username]"));
		$('#password').val($.cookie("fmxw[password]"));	
		$('#rememberme').attr('checked', true);
	}
	else {
		$('#rememberme').attr('checked', false);
	}
});
$('input:text, input:password', '#form_id')
.change(function() {
	var t = $(this).val();
	if(/^\s*$/.test(t)) $(this).popover('show');
	else $(this).popover('hide');
})
.hover(function() {
	$(this).popover('show');
});
</script>
<?php
}

function check()
{
    $username = strtolower(trim($_POST['username']));
    $password = strtolower($_POST['password']);
    $rememberme = isset($_POST['rememberme']) ? true : false;
    
    if (strcmp($username, 'adminadmin')==0 && strcmp($password, 'dixi123456')==0) {
		if($rememberme) {
			$expire = time() + 1728000; // Expire in 20 days
			setcookie('fmxw[username]', $username, $expire);
			setcookie('fmxw[password]', $password, $expire);
		}
		else {
			setcookie('fmxw[username]', NULL);
			setcookie('fmxw[password]', NULL);
		}

		$_SESSION['fmxw']['username'] = ucfirst($username);
    	return true;
    }
	else {
		if(! $rememberme) {
			setcookie('fmxw[username]', NULL);
			setcookie('fmxw[password]', NULL);
		}
	}
    return false;
}
?>
