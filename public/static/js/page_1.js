
//==扩展js方法
// 替换字符串中的字段
String.prototype.substitute = function (object, regexp) {
    return String(this).replace(regexp || (/\\?\{([^{}]+)\}/g), function (match, name) {
        if (match.charAt(0) == '\\') return match.slice(1);
        return (object[name] != null) ? object[name] : ((name.indexOf(':') != -1) ? '{' + name + '}' : '');
    });
};
String.prototype.test = function (regex, params) {
    return (($.type(regex) == 'regexp') ? regex : new RegExp('' + regex, params)).test(this);
};


$('body').delegate('[target="dialog"]', 'click', function () {

    var options = $(this).attr('options') || $(this).data('options') || {};
    if ($.type(options) === 'string')
        options = eval("(" + options + ")") || {};

    options['type'] = options['type'] ? options['type'] : 1;
    //options['area'] = options['area'] ? options['area'] : ['420px', '240px'];
    var loadlayer = layer.load();
    $.get($(this).attr('href'), function (rs) {
        layer.close(loadlayer);
        options['content'] = rs;
        layer.open(options);
    });

    return false;
}).on('click', '[target="ajax"]', function () {
    event.stopPropagation();
    var url = $(this).attr('href'),
    method = $(this).data('method') || 'get',
    noShade = $(this).data('noshade') || false,
    args = $(this).data('args') || {};
    if (!$[method] || !url) return false;
    if (!noShade) var loadlayer = layer.load();
    $[method](url, args, function (rs) {
        if (loadlayer) layer.close(loadlayer);
        if (rs['msg']) {
            layer.msg(rs.msg);
        }
        if (rs['url'] && rs['url'] != '') {
            if (rs['url'] === true) {
                setTimeout(function () {
                    location.reload();
                },2000);
            } else {
                setTimeout(function () {
                    window.location.href = rs.url;
                }, 2000);
            }
        }
    },'json');
    return false;
}).delegate('form.form-ajax', 'submit', function (event) {
    event.stopPropagation();
    var onBeforeSend = $(this).data('beforesend'),
    onSuccess = $(this).data('success'),
    url = $.trim($(this).attr('action')),
    method = $.trim($(this).attr('method')) || 'post',
    noShade = $(this).data('noshade') || false,
    vthis = this;
    if (!$[method] || !url || (onBeforeSend && onBeforeSend.call(this) === false)) return false;
    if (!noShade) var loadlayer = layer.load();

    $[method](url, $(this).serialize(), function (data) {
        if (loadlayer) layer.close(loadlayer);

        var dialog = $(vthis).closest('.layui-layer');
        var layer_close = dialog.find('.layui-layer-close');

        if (onSuccess && window[onSuccess] && window[onSuccess].call(vthis, data) === false) return;

        if (data['msg']) {
            layer.msg(data.msg);
        }
        if (data.status == 'success' && layer) {
            $(layer_close).trigger('click');
        }
        if (data['url'] && data['url'] != '') {
            if (data['url'] === true) {
                location.reload();
            } else {
                window.location.href = data.url;
            }
        }

    }, 'json');

    return false;
});