//����soso�ķ�������Ԥ��ժҪ��URL.
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