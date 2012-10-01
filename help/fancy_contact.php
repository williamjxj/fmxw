<?php
// from http://tutorialzine.com/2009/09/fancy-contact-form/, 老的表单。
header('Content-Type: text/html; charset=utf-8'); 
session_name("fancyform");
session_start();

define('ROOT', './');
$_SESSION['n1'] = rand(1,20);
$_SESSION['n2'] = rand(1,20);
$_SESSION['expect'] = $_SESSION['n1']+$_SESSION['n2'];

$str='';
if(isset($_SESSION['errStr']) && $_SESSION['errStr'] )
{
	$str='<div class="error">'.$_SESSION['errStr'].'</div>';
	unset($_SESSION['errStr']);
}

$success='';
if(isset($_SESSION['sent']) && $_SESSION['sent'])
{
	$success='<h1>谢谢! 我们会尽快和您联系。</h1>';
	
	$css='<style type="text/css">#contact-form{display:none;}</style>';
	
	unset($_SESSION['sent']);
}
$ipath = ROOT . 'include/fancy_contact/';

if (!isset($_SESSION['post']['name'])) {
	$_SESSION['post']['name']='';
	$_SESSION['post']['email']='';
	$_SESSION['post']['message']='';
}

?>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="<?=$ipath;?>jqtransformplugin/jqtransform.css" />
<link rel="stylesheet" type="text/css" href="<?=$ipath;?>formValidator/validationEngine.jquery.css" />
<script type="text/javascript" src="<?=$ipath;?>jqtransformplugin/jquery.jqtransform.js"></script>
<script type="text/javascript" src="<?=$ipath;?>formValidator/jquery.validationEngine.js"></script>
<script type="text/javascript" src="<?=$ipath;?>script.js"></script>
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal">×</button>
  <h3>底细 - 联系我们表单</h3>
</div>
<div class="modal-body">
  <form id="contact-form" name="contact-form" method="post" action="<?=$ipath;?>submit.php">
    <table width="100%" border="0" cellspacing="0" cellpadding="5">
      <tr>
        <td width="15%"><label for="name">姓名</label></td>
        <td width="70%"><input type="text" class="validate[required]" name="name" id="name" value="<?=$_SESSION['post']['name']?>" placeholder="您的大名" /></td>
        <td width="15%" id="errOffset">&nbsp;</td>
      </tr>
      <tr>
        <td><label for="email">邮件地址</label></td>
        <td><input type="text" class="validate[required,custom[email]]" name="email" id="email" value="<?=$_SESSION['post']['email']?>" placeholder="您的邮件地址" /></td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td><label for="subject">询问/查询</label></td>
        <td><select name="subject" id="subject">
            <option value="" selected="selected"> - 请选择 -</option>
            <option value="Question">询问</option>
            <option value="Business proposal">查询</option>
            <option value="Advertisement">广告</option>
            <option value="Complaint">投诉</option>
          </select>
        </td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td valign="top"><label for="message">详情</label></td>
        <td><textarea name="message" id="message" class="validate[required]" cols="45" rows="8" placeholder="请输入详情"><?=$_SESSION['post']['message']?>
</textarea></td>
        <td valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td><label for="captcha">
          <?=$_SESSION['n1']?>
          +
          <?=$_SESSION['n2']?>
          =</label></td>
        <td><input type="text" class="validate[required,custom[onlyNumber]]" name="captcha" id="captcha" /></td>
        <td valign="top">&nbsp;</td>
      </tr>
      <tr>
        <td valign="top">&nbsp;</td>
        <td colspan="2"><input type="submit" name="button" id="button" value="提交联系表单" />
          <input type="reset" name="button2" id="button2" value="还原" />
          <?=$str?>
          <img id="loading" src="include/fancy_contact/img/ajax-load.gif" width="16" height="16" alt="loading" style="display:none;" /> <a href="#" class="btn" data-dismiss="modal">关闭本窗口</a></td>
      </tr>
    </table>
  </form>
  <?=$success?>
</div>
