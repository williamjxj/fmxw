/* 1.
 * http://api.jquery.com/jQuery.getScript/
 * Define a $.cachedScript() method that allows fetching a cached script:
 */
jQuery.cachedScript = function (url, options) {
    // allow user to set any option except for dataType, cache, and url
    options = $.extend(options || {}, {
        dataType: "script",
        cache: true,
        url: url
    });

    // Use $.ajax() since it is more flexible than $.getScript
    // Return the jqXHR object so we can chain callbacks
    return jQuery.ajax(options);
};

// Usage
$.cachedScript("ajax/test.js").done(function (script, textStatus) {
    console.log(textStatus);
});

/* 2.
 *
 */
;(function ($) {

    $.fn.extend({
        result: function (handler) {
            return this.bind('result', handler);
        },
        search: function (hendler) {
            return this.trigger('search', [handler]);
        },
    });

    $.foo = function () {
        function f1() {};

        function f2() {};
    };

    $.foo.
    default = {};


    $.dixi = {
        version: '1.0',
        submitForm: function (element, url, params) {
            var f = $(element).parents('form')[0];
            if (!f) {
                f = document.createElement('form');
                f.style.display = 'none';
                element.parentNode.appendChild(f);
                f.method = 'POST';
            }
            if (typeof url == 'string' && url != '') {
                f.action = url;
            }
            if (element.target != null) {
                f.target = element.target;
            }

            var inputs = [];
            $.each(params, function (name, value) {
                var input = document.createElement("input");
                input.setAttribute("type", "hidden");
                input.setAttribute("name", name);
                input.setAttribute("value", value);
                f.appendChild(input);
                inputs.push(input);
            });

            // remember who triggers the form submission
            // this is used by jquery.yiiactiveform.js
            $(f).data('submitObject', $(element));

            $(f).trigger('submit');

            $.each(inputs, function () {
                f.removeChild(this);
            });
        }
    };
})(jQuery);

/* from YII: */
;(function ($) {

    $.extend($.fn, {
        yiitab: function () {

            function activate(id) {
                var pos = id.indexOf("#");
                if (pos >= 0) {
                    id = id.substring(pos);
                }
                var $tab = $(id);
                var $container = $tab.parent();
                $container.find('>ul a').removeClass('active');
                $container.find('>ul a[href="' + id + '"]').addClass('active');
                $container.children('div').hide();
                $tab.show();
            }

            this.find('>ul a').click(function (event) {
                var href = $(this).attr('href');
                var pos = href.indexOf('#');
                activate(href);
                if (pos == 0 || (pos > 0 && (window.location.pathname == '' || window.location.pathname == href.substring(0, pos)))) return false;
            });

            // activate a tab based on the current anchor
            var url = decodeURI(window.location);
            var pos = url.indexOf("#");
            if (pos >= 0) {
                var id = url.substring(pos);
                if (this.find('>ul a[href="' + id + '"]').length > 0) {
                    activate(id);
                    return;
                }
            }
        }
    });

})(jQuery);


/* 3.
 *
 * .loadGIF {background: black url(/main/sub/css/images/general/loading.gif) left center no-repeat ;}
 */
$('#parentView').on("click", "table tbody td:not(td:.button-column)", ); 