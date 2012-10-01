;
(function ($) {
    var ts = 5000;
    $.fn.rotator = function (param) {
        var $container = $(this);
        var did, ww;

        if (param !== undefined || typeof param !== 'undefined') {
            ww = param;
        }

        did = $container.attr('id').replace(/rss_/, '');

        $(this).each(function () {
            $container.empty();

            var fadeHeight = $container.height() / 4;
            for (var yPos = 0; yPos < fadeHeight; yPos += 2) {
                $('<div></div>').css({
                    opacity: yPos / fadeHeight,
                    top: $container.height() - fadeHeight + yPos
                }).addClass('fade-slice').appendTo($container);
            }

            var $loadingIndicator = $('<img/>').attr({
                'src': './images/loading.gif',
                'alt': '正在下载,请稍等...'
            }).addClass('news-wait').appendTo($container);

            var url = 'rotators.php?rss=' + did;
            if (ww) url += '&ww='+ww;
            $.get(url, function (data) {

                $loadingIndicator.remove();
                $(data).each(function () {
                    var $link;

                    if (/\d:\d/.test(this.title)) {
                        $link = $('<a></a>').attr('href', this.link).text(this.title);
                    } else {
						t = this.title + '(' + this.date + ')';
                        $link = $('<a></a>').attr('href', this.link).text(t);
                    }

                    var $headline = $('<h4></h4>').append($link);

                    var $summary = $('<div></div>').addClass('summary').html(this.text);

                    $('<div></div>').addClass('headline').append($headline, $summary).appendTo($container);
                });

                var currentHeadline = 0,
                    oldHeadline = 0;
                var hiddenPosition = $container.height() + 10;

                for (i = 0; i <= 2; i++) {
                    //$('div.headline').eq(currentHeadline).css('top', 0);
                    $('div.headline', $container).eq(i).css('top', 150 * i);
                }

                var headlineCount = $('div.headline', $container).length;
                var pause = false;
                var rotateInProgress = false;

                var headlineRotate = function () {
                    if (!rotateInProgress) {
                        rotateInProgress = true;
                        pause = false;
                        currentHeadline = (oldHeadline + 1) % headlineCount;

                        $('div.headline', $container).eq(oldHeadline).animate({
                            top: -hiddenPosition
                        }, 'slow', function () {
                            $(this).css('top', hiddenPosition);
                        });
                        $('div.headline', $container).eq(currentHeadline).animate({
                            top: 0
                        }, 'slow', function () {
                            rotateInProgress = false;
                            if (!pause) {
                                pause = setTimeout(headlineRotate, ts);
                            }
                        });
                        $('div.headline', $container).eq(currentHeadline + 1).animate({
                            top: 150
                        }, 'slow', function () {
                            rotateInProgress = false;
                            if (!pause) {
                                pause = setTimeout(headlineRotate, ts);
                            }
                        });
                        $('div.headline', $container).eq(currentHeadline + 2).animate({
                            top: 300
                        }, 'slow', function () {
                            rotateInProgress = false;
                            if (!pause) {
                                pause = setTimeout(headlineRotate, ts);
                            }
                        });
                        oldHeadline = currentHeadline;
                    }
                };
                if (!pause) {
                    pause = setTimeout(headlineRotate, ts);
                }

                $container.hover(function () {
                    clearTimeout(pause);
                    pause = false;
                }, function () {
                    if (!pause) {
                        pause = setTimeout(headlineRotate, 250);
                    }
                });
            }, "json");
        });
    }
})(jQuery);