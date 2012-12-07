//添加namespace: 2012-10-03
;(function($) {
	$.news = {
		tengxun_news : function(q) {
			var kw = UrlEncode(q);
			$.getJSON('/cgi-bin/threads/news_tengxun.cgi', { q : kw }, function(data) {
				if(data==null || (typeof data==='undefined') || (data.length==0)) {
					$('#tengxun_news').html('没有关于['+q+'], ['+kw+'] 的数据。');
					return false;
				}
				var txt='<ul class="nav nav-pills nav-stacked">';
				$.each(data, function(key, val) {
					txt += '<li><a href="' + val[0] + '">' + val[1] + '</a><br>' + val[2] + '</li>';
				});
				txt +='</ul>\n';
				$('#tengxun_news').html(txt);
			});
		},
		i360_news : function(q) {
			$.getJSON('/cgi-bin/threads/news_360.cgi', { q : q }, function(data) {
				if(data==null || (typeof data==='undefined') || (data.length==0)) {
					$('#360_news').html('没有关于['+q+'], ['+kw+'] 的数据。');
					return false;
				}
				var txt='<ul class="nav nav-pills nav-stacked">';
				$.each(data, function(key, val) {
					txt += '<li><a href="' + val[0] + '">' + val[1] + '</a><br>' + val[2] + '</li>';
				});
				txt +='</ul>\n';
				$('#360_news').html(txt);
			});
		},
		netease_news : function(q) {
			$.getJSON('/cgi-bin/threads/news_163.cgi', { 'q' : q }, function(data) {
				var txt='<ul class="nav nav-pills nav-stacked">';
				$.each(data, function(key, val) {
					txt += '<li><a href="' + val[0] + '">' + val[1] + '</a><br>' + val[2] + '</li>';
				});
				txt +='</ul>\n';
				$('#163_news').html(txt);
			});
		},
		sogou_news : function(q) {
			var kw = UrlEncode(q);
			$.getJSON('/cgi-bin/threads/news_sogou.cgi', { q : kw }, function(data) {
				if (data == null || ( typeof data === 'undefined') || (data.length == 0)) {// data is null.
					$('#sohu_news').html('没有关于['+q+'], ['+kw+'] 的数据。');
					return false;
				}
				var txt='<ul class="nav nav-pills nav-stacked">';
				$.each(data, function(key, val) {
					txt += '<li><a href="' + val[0] + '">' + val[1] + '</a><br>' + val[2] + '</li>';
				});
				txt +='</ul>\n';
				$('#sohu_news').html(txt);
			});
		}
	};
})(jQuery);

;(function($) {
    $.fmxw4 = {
        sina_weibo : function(q) {
            var attrs = {
                src : 'http://s.weibo.com/weibo/'
            };
            $.extend(attrs, $.fmxw4.defaults);
            attrs.src += q;
            $('#sina_wb').html($('<iframe></iframe>').attr(attrs));
        },
        qq_weibo : function(q) {
            var attrs = {
                src : 'http://search.t.qq.com/index.php'
            };
            $.extend(attrs, $.fmxw4.defaults);
            var params = {
                pos : 1002,
                su : 1,
                smart : 1
            };
            var t = '?';
            Object.keys(params).forEach(function(key) {
                t += key + '=' + params[key] + '&';
            });
            attrs.src += t + 'k=' + q;
            $('#tengxun_wb').html($('<iframe></iframe>').attr(attrs));
        },

        netease_weibo : function(q) {
            var attrs = {
                src : 'http://t.163.com/tag/'
            };
            $.extend(attrs, $.fmxw4.defaults);
            attrs.src += q;
            $('#163_wb').html($('<iframe></iframe>').attr(attrs));
        },

        sohu_weibo : function(q) {
            var attrs = {
                src : 'http://t.sohu.com/twsearch/twSearch?key='
            };
            $.extend(attrs, $.fmxw4.defaults);
            attrs.src += UrlEncode(q);
            $('#sohu_wb').html($('<iframe></iframe>').attr(attrs));
        }
    };

    $.fmxw4.defaults = {
        width : '100%',
        scrolling : 'yes',
        height : 500,
        frameborder : 0
    };
})(jQuery);
