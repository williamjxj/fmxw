//参照soso的方法，来预览摘要的URL.
(function($) {
    $.fmxw5 = {
        preview : function(url) {
            var attrs = {
                src : UrlEncode(url)
            };
            $.extend(attrs, $.fmxw5.defaults);
            $('#preview').html($('<iframe></iframe>').attr(attrs));
        }
	};
	$.fmxw5.defaults = {
        scrolling : 'yes',
        frameborder : 0
    };
})(jQuery);