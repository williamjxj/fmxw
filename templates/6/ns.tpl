{$url={$config.search}}
{$t6={$config.t6}}
{include file="{$config.t}base_layout.tpl.html"}
<link rel="stylesheet" type="text/css" href="{$config.f1.c}" media="screen" />
<script type="text/javascript" src="js/jquery.validate.min.js"></script>
<script type="text/javascript" src="js/UrlEncode.js"></script>
</head><body>
<div class="container">
  <div id="header"> {include file="{$header_template}"} </div>
  <div class="row-fluid">
    <div class="span8"> {include file="{$config.t6}search.tpl.html"} </div>
    <div class="span4 alert"> <img src="{$config.img}discus.gif" width="14" height="14" border="0" /> <a href="#pk" id="apk">关于 ‘{$smarty.session.fmxw.search.key}’，<strong class="pk">我要PK...</strong></a> </div>
  </div>
</div>
<div class="container-fluid" align="left">
  <div class="row-fluid">
    <div class="span1" id="sleft">
      <ul id="tf1" class="nav nav-tabs nav-stacked">
        <li class="active"><a href="javascript:;" class="sort_by_time" id="all" rel="tooltip">全部时间</a></li>
        <li><a href="javascript:;" class="sort_by_time" id="day">一天内</a></li>
        <li><a href="javascript:;" class="sort_by_time" id="week">一周内</a></li>
        <li><a href="javascript:;" class="sort_by_time" id="month">一月内</a></li>
        <li><a href="javascript:;" class="sort_by_time" id="year">一年内</a></li>
      </ul>
      <div class="alert" style="margin-top:20px;padding:2px;">
        <form action="{$url}" method="get" name="ct_search" id="ct_search">
          <label for="category">类别:</label>
          <select name="category" id="category" style="width:100%">
            <option value="">--- 请选择 ---</option>
          </select>
          <div id="div_item" style="display:none">
            <label for="item">栏目:</label>
            <select name="item" id="item" style="width:100%">
              <option value="">--- 请选择 ---</option>
            </select>
          </div>
          <button class="btn btn-small btn-primary" type="submit" disabled="disabled"><i class="icon-white icon-search"></i>查 询</button>
        </form>
      </div>
      <ul id="tf2" class="nav nav-list box">
        <li><a href="javascript:;" id="clicks" rel="tooltip" title="阅读次数">阅读次数</a></li>
        <li><a href="javascript:;" id="guanzhu" rel="tooltip" title="关注度">关注度</a></li>
        <li><a href="javascript:;" id="pinglun" rel="tooltip" title="评论数">评论数</a></li>
        <li><a href="javascript:;" id="likes" rel="tooltip" title="赞同">赞同</a></li>
        <li><a href="javascript:;" id="fandui" rel="tooltip" title="反对">反对</a></li>
      </ul>
      <!--ul id="sf" class="nav nav-list box">
        <li class="active"><a class="all" rel="tooltip" title="全部来源">全部来源</a></li>
        <li><a href="#baidu" rel="tooltip" title="百度">百度</a></li>
        <li><a href="#soso" rel="tooltip" title="腾讯网">搜搜</a></li>
        <li><a href="#google" rel="tooltip" title="谷歌">谷歌</a></li>
        <li><a href="#yahoo" rel="tooltip" title="雅虎">雅虎</a></li>
        <li><a href="#hk.yahoo" rel="tooltip" title="雅虎">雅虎香港</a></li>
        <li><a href="#tw.yahoo" rel="tooltip" title="雅虎">雅虎台湾</a></li>
      </ul-->
    </div>
    <div class="span4" id="smiddle"> {include file="{$nav_template}"} </div>
    <div class="span7" id="sright">
      <div id="sd"></div>
      <div id="pk" style="display:none;"></div>
      <div id="pk_result" class="box4" style="display:none;"></div>
      <div class="alert"> <strong>网友在查:</strong> <span> {foreach $kr as $r} <span><a id="rk_{$r.rid}" class="rk" href="{$r.kurl}">{$r.rk}</a></span> {/foreach} </span> </div>
      <div id="news">{include file="{$config.t3}weibo.tpl.html"}</div>
      <div id="weibo">{include file="{$config.t6}news.tpl.html"}</div>
      <div id="reping">{include file="{$config.t6}reping.tpl.html"}</div>
      <div class="guanggao">{include file="{$config.t}guanggao2.tpl.html"}</div>
    </div>
  </div>
</div>
<div class="clear"></div>
<div class="container">
  <div class="row-fluid" id="guanggao">
    <div> {include file="{$config.t}guanggao.tpl.html"} </div>
  </div>
  <div id="footer"> {include file="{$footer_template}"} </div>
</div>
<script type="text/javascript">
$(function() {
	$('a.sort_by_time', '#tf1').click(function() {
		//$('div#smiddle').load('{$url}?js_sortby='+$(this).attr('id')+'&q={$smarty.session.fmxw.search.key}');
		$('div#smiddle').load('{$url}?js_sortby_dwmy='+$(this).attr('id'));
	});
	$('a', '#tf2').click(function() {
		$('div#smiddle').load('{$url}?js_sortby_attr='+$(this).attr('id'));
	});
	
	$('li.li3 a').live('click', function() {
		$t = $(this);
		$('{$config.wait}').insertAfter(this);
		$('#sd').load($(this).attr('href'), function() {
			$(this).fadeIn(200);
			$('li.li3').removeClass('highlight');
			$t.parent('li.li3').addClass('highlight');
			$t.next('img').remove();
		});
		return false;
	});

	//阅览右上角。
	$('div.pagination a').live('click', function() {
		$(this).html('{$config.wait}');
		var url = $(this).attr('href');
		//var t = new Date().getTime();
		if (! /js_page=/.test(url)) url+='&js_page';
		$.ajax({
			type: 'get',
			url: url,
			success: function(data) {
				$('div#smiddle').hide().html(data).fadeIn(200);
			}
		});
		return false;
	});

	//PK框的显示和隐藏。
	$('#apk').click(function() {
		if($('#pk').is(':visible')) {
			$('#pk').hide();
			$('#pk_result').hide();
		}
		else
			$('#pk').load('{$config.search}?js_pk=1').fadeIn(100);
		return false;
	});
	
	$('#category').change(function() {
		cate_id = $(this).attr('value');
		$.getJSON("{$url}?js_item=1&cate_id=" + cate_id, function(data) {
			var items ='<option value="0">Default</option>\n';
			$.each(data, function(id, name) {
				items += '<option value="' + name[0] + '">' + name[1] + '</option>\n';
			});
			$('#div_item').fadeIn(500);
			//$('#item').empty().append(items);
			$('#item').html(items);
			$('button:submit', '#ct_search').attr('disabled', false);
		});
	});
	
	//排序框Form的提交。
	$('#ct_search').submit(function(e) {
		$('div#smiddle').load('{$url}', $(this).serialize()+'&js_ct_search=1');
		return false;
	});
	
	{if $smarty.session.fmxw.search.key || {$smarty.session.fmxw.search.key}!='所有记录'}
		$('#q').val('{$smarty.session.fmxw.search.key}');
	{/if}
});
$(window).load(function() {
	$.getJSON('{$url}?js_category=1', function(data) {
		var cates='<option value="0">Default</option>\n';
		$.each(data, function(key, val) {
			cates += '<option value="' + parseInt(val[0]) + '">' + val[1] + '</option>\n';
		});
		$('#category').append(cates);
	});
}); 
</script>
