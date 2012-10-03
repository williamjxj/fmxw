;(function($) {
    $.extend($.fn, {
        fmxw3 : function(settings) {
            defaults = {};
            settings = $.extend(defaults, settings);
        },

        /** 邮件共享
         * https://github.com/cabbiepete/jQuery-Share-Email/blob/master/src/jquery.shareemail.js
         */
        shareEmail : function(options) {

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
                        title : document.title,
                        description : $('meta[name=description]').attr('content'),
                        url : window.location.href,
                        nl : "\n" // tmpl seems to each newline chars so we use this instead.
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
        },
    });

    // plugin default options
    $.fn.shareEmail.defaults = {
        displayText : '通过邮件共享',
        title : '通过邮件共享这篇文章',
        template : '<span style="text-decoration:none;display:inline-block;cursor:pointer;" class="button"><span class="chicklets email"><a class="btn btn-danger btn-small" href="javascript:;"><i class="icon-envelope icon-white"></i>${displayText}</a></span></span>',
        subjectTemplate : "${title} - 想和你共享",
        bodyTemplate : "${nl}${nl}${title}${nl}${nl}源文件: ${url}${nl}${nl}${description}"
    };

})(jQuery);
