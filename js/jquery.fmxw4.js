//添加namespace: 2012-10-03
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
        //deprecated:
        sina_weibo_deprecated : function(q) {
            var attrs = {
                src : 'http://widget.weibo.com/livestream/listlive.php'
            };
            if ( typeof $.fmxw4.defaults === 'object')
                $.extend(attrs, $.fmxw4.defaults);
            var params = {
                language : 'zh_cn',
                width : 0,
                height : '500',
                uid : 1644057380,
                skin : 1,
                refer : 1,
                pic : 1,
                titlebar : 0,
                border : 1,
                publish : 1,
                atalk : 1,
                recomm : 0,
                at : 0,
                dpc : 1
            };
            //缁勮query瀛楃涓
            var t = '?';
            Object.keys(params).forEach(function(key) {
                t += key + '=' + params[key] + '&';
            });
            t += 'atopic=' + q;
            t += '&ptopic=' + q;
            attrs.src += t;
            $('#sina_wb').html($('<iframe></iframe>').attr(attrs));
        },

        //http://topic.weibo.com/areahot/5460?page=4
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
            //<iframe width="100%" scrolling="yes" height="500" frameborder="0" src=""></iframe>
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
        },
    };

    $.fmxw4.defaults = {
        width : '100%',
        scrolling : 'yes',
        height : 500,
        frameborder : 0
    };
})(jQuery);
