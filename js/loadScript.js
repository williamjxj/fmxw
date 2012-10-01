function loadScript(s) {
	var head = document.getElementsByTagName('head')[0];
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = s;
	head.appendChild(script);
}
//没有用到。
function loadScript(sScriptSrc) {
    var oHead = document.getElementsByTagName('head')[0];
    var oScript = document.createElement('script');
    oScript.type = 'text/javascript';
    oScript.src = sScriptSrc;
    oHead.appendChild(oScript);
}
//for (var i=0; i<news.length; i++) {
//	loadScript('http://news.baidu.com/n?cmd=1&class='+news[i]+'&pn=1&tn=newsbrofcu');
//}

function loadScript_callback(sScriptSrc, oCallback) {
    var oHead = document.getElementById('head')[0];
    var oScript = document.createElement('script');
    oScript.type = 'text/javascript';
    oScript.src = sScriptSrc;
    // most browsers
    oScript.onload = oCallback;
    // IE 6 & 7
    oScript.onreadystatechange = function() {
		if (this.readyState == 'complete') {
			oCallback();
		}
    }
    oHead.appendChild(oScript);
}

var news_array = new Array('civilnews', 'internews', 'mil', 'finannews', 'internet', 'housenews', 'autonews', 'sportnews', 'enternews', 'gamenews', 'edunews', 'healthnews', 'socianews', 'technnews');

for (var i=0; i<news_array.length; i++) {
	t = 'http://news.baidu.com/n?cmd=1&class='+news_array[i]+'&pn=1&tn=newsbrofcu';
	loadScript(t);
}