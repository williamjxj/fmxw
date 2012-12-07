//参照soso的方法，来预览摘要的URL.
;(function($) {
    $.fmxw5 = {
        preview : function(url) {
            var attrs = {
                src : url
            };
            $.extend(attrs, $.fmxw5.defaults);
            $('#sd1').html($('<iframe></iframe>').attr(attrs));
        }
	};
	$.fmxw5.defaults = {
		width : '100%',
        scrolling : 'yes',
		height : 500,
        frameborder : 0
    };
})(jQuery);

;(function($) {
	$.fn.nav_preview = function(options) {
		$(this).html('<div class="ajaxloading"></div>').show();
		var defaults = {
			scrolling : 'yes',
			frameborder : 0
		},
		settings = $.extend(defaults, options),
		f = $('<iframe></iframe>').attr(settings);
		this.each(function() {
			$(this).html(f).show();
		});
	}
})(jQuery);
