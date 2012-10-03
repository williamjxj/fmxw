;(function($) {
    //全局函数的扩展.
    $.fmxw2 = {
        get_date1 : function() {
            var today = new Date();
            var wday = '星期';
            switch (today.getDay()) {
                case 0:
                    wday += '日';
                    break;
                case 1:
                    wday += '一';
                    break;
                case 2:
                    wday += '二';
                    break;
                case 3:
                    wday += '三';
                    break;
                case 4:
                    wday += '四';
                    break;
                case 5:
                    wday += '五';
                    break;
                case 6:
                    wday += '六';
                    break;
            }
            document.write((today.getMonth() + 1) + '月' + today.getDate() + '日' + wday);
        },
        //从yahoo.com.hk：
        get_date2 : function() {
            var now, mon, day, todaydate;
            now = new Date;
            mon = new Array("1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月");
            day = new Array("星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六");
            todaydate = '<span class="date">' + mon[now.getMonth()] + now.getDate() + '日</span><span class="weekday">(' + day[now.getDay()] + ')</span>';
            return todaydate;
        },
    };
    //实例方法的扩展.
    $.extend($.fn, {

    });
})(jQuery);
