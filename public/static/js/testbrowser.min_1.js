/*!
* testBrowser
* Create time @2017-06-09
*/
(function () {
    function b(f) {
        f = f.toLowerCase();
        var e = /(chrome)[ \/]([\w.]+)/.exec(f) || /(webkit)[ \/]([\w.]+)/.exec(f) || /(opera)(?:.*version|)[ \/]([\w.]+)/.exec(f) || /(msie) ([\w.]+)/.exec(f) || f.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec(f) || [];
        return {
            browser: e[1] || "",
            version: e[2] || "0"
        }
    }
    var a = b(navigator.userAgent);
    var d = {};
    if (a.browser) {
        d[a.browser] = true;
        d.version = a.version
    }
    if (d.msie && (d.version == "7.0" || d.version == "8.0")) {
        var c = encodeURIComponent(location.pathname + location.search);
        window.location.href = "/Public/kill-IE.html?url=" + c
    }
})();
