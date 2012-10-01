// 最开始的时间函数库.
;(function ($) {
	$.dixi = $.dixi || {};
    $.dixi = {
        version: '1.0',
        enMonthNames: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
        getEnMonthName: function () {
            return this.enMonthNames[this.getMonth()];
        },
        cnMonthNames: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
        getCNMonthName: function () {
            return this.cnMonthNames[this.getMonth()];
        },
        get_current_datetime: function () {
            var dt = new Date();
            //return dt.getFullYear() + '年' + (dt.getMonth() + 1) + '月' + dt.getDate() + '日';
            return dt.getFullYear() + '年' + (dt.getMonth() + 1) + '月' + dt.getDate() + '日 ' + dt.getHours() + '时' + dt.getMinutes() + '分' + dt.getSeconds() + '秒';
        },
		getMonth: function() {
			var dt = new Date();
			return dt.getMonth();
		},
        getShortMonthName: function () {
            return this.getENMonthName().substr(0, 3);
        },
        get_birthday_array: function (birthday) {
            if (birthday === null || birthday === undefined || (typeof birthday === 'undefined')) {
                return new Array();
            }
            var b = new Date(birthday);
            var d = ("0" + (b.getDate())).slice(-2);
            return [b.toDateString(), b.getShortMonthName(), d, b.getFullYear()];
        }
    };
})(jQuery);

// public functions definition
/*$.fn.pluginName.functionName = function(foo) {
	return this;
};
// private functions definition
function foobar() {}
//从yahoo.com.hk：
var now,mon,day,todaydate;
now=new Date;
mon=new Array("1月","2月","3月","4月","5月","6月","7月","8月","9月","10月","11月","12月");
day=new Array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");
todaydate= '<span class="date">' + mon[now.getMonth()]+now.getDate()+'日</span><span class="weekday">(' + day[now.getDay()] +')</span>';
*/
function get_date()
{
	var today = new Date();
	var wday = '星期';
	switch (today.getDay()) {
		case 0: wday += '日'; break;
		case 1: wday += '一'; break;
		case 2: wday += '二'; break;
		case 3: wday += '三'; break;
		case 4: wday += '四'; break;
		case 5: wday += '五'; break;
		case 6: wday += '六'; break;
	}
	document.write((today.getMonth()+1)+'月'+today.getDate()+'日'+wday); 
}
/* http://lab.mattvarone.com/projects/jquery/totop/js/jquery.ui.totop.js */
(function($){
	$.fn.UItoTop = function(options) {

 		var defaults = {
			text: 'To Top',
			min: 200,
			inDelay:600,
			outDelay:400,
			containerID: 'toTop',
			containerHoverID: 'toTopHover',
			scrollSpeed: 1200,
			easingType: 'linear'
		},
		settings = $.extend(defaults, options),
		containerIDhash = '#' + settings.containerID,
		containerHoverIDHash = '#'+settings.containerHoverID;
		
		$('body').append('<a href="#" id="'+settings.containerID+'">'+settings.text+'</a>');
		$(containerIDhash).hide().on('click.UItoTop',function(){
			$('html, body').animate({scrollTop:0}, settings.scrollSpeed, settings.easingType);
			$('#'+settings.containerHoverID, this).stop().animate({'opacity': 0 }, settings.inDelay, settings.easingType);
			return false;
		})
		.prepend('<span id="'+settings.containerHoverID+'"></span>')
		.hover(function() {
				$(containerHoverIDHash, this).stop().animate({
					'opacity': 1
				}, 600, 'linear');
			}, function() { 
				$(containerHoverIDHash, this).stop().animate({
					'opacity': 0
				}, 700, 'linear');
			});
					
		$(window).scroll(function() {
			var sd = $(window).scrollTop();
			if(typeof document.body.style.maxHeight === "undefined") {
				$(containerIDhash).css({
					'position': 'absolute',
					'top': sd + $(window).height() - 50
				});
			}
			if ( sd > settings.min ) 
				$(containerIDhash).fadeIn(settings.inDelay);
			else 
				$(containerIDhash).fadeOut(settings.Outdelay);
		});
	};
})(jQuery);

/** 邮件共享 
 * https://github.com/cabbiepete/jQuery-Share-Email/blob/master/src/jquery.shareemail.js 
 */
;(function($) {
  // replace 'pluginName' with the name of your plugin
  $.fn.shareEmail = function(options) {

    // extends defaults with options provided
    var o = $.fn.shareEmail.defaults;
    if (options) {
      o = $.extend(o, options);
    }
    // iterate over matched elements
    return this.each(function() {
      $.tmpl(o.template, o).appendTo($(this));

      $(this).click(function() {
        var data = {
          title: document.title,
          description: $('meta[name=description]').attr('content'),
          url: window.location.href,
          nl: "\n" // tmpl seems to each newline chars so we use this instead.
        }
        var url = 'mailto:?Subject=';
        var subject = $.tmpl(o.subjectTemplate, data).text();
        url += encodeURIComponent(subject);
        url += '&Body=';
        var eBody = $.tmpl(o.bodyTemplate, data).text();
        url += encodeURIComponent(eBody);
        window.location.href = url;
      });
    });
  };

  // plugin default options
  $.fn.shareEmail.defaults = {
    displayText: '通过邮件共享',
    title: '通过邮件共享这篇文章',
    template: '<span style="text-decoration:none;display:inline-block;cursor:pointer;" class="button"><span class="chicklets email"><a class="btn btn-danger btn-small" href="javascript:;"><i class="icon-envelope icon-white"></i>${displayText}</a></span></span>',
    subjectTemplate: "${title} - 想和你共享",
    bodyTemplate: "${nl}${nl}${title}${nl}${nl}源文件: ${url}${nl}${nl}${description}"
  };
})(jQuery);