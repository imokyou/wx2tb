
function taobao_app_index(shopUrl){
    var ua = navigator.userAgent.toLowerCase();
    ua=ua.toLowerCase();
    var os_type='android';
    if(ua.indexOf("iphone")!=-1){
        if(ua.indexOf("iphone os 9")!=-1||ua.indexOf("iphone os 10")!=-1){
            os_type='iPhone_ios_9';
        }else{
            os_type='iPhone';
        }
    }
    if(is_weixin(ua)){
        //微信中
        return 1;
    }else {
        //非微信中
        if (os_type != "android") {
            $("body").html("<div style='color:#000000;display: block;font-size: 22px;height: 1000px;margin-left:10px;text-align:center;'>正在跳转.....</div> ");
        }
        if (os_type == "iPhone_ios_9") {
            openIphoneApp_ios_9(shopUrl);
        } else if (os_type == "android") {
            return openApp_android(shopUrl);
        } else if (os_type == "iPhone") {
            openApp_ios(shopUrl);
        } else {
            window.location = shopUrl;
        }
    }
}

function is_weixin(ua) {
    if(ua.indexOf("micromessenger")!=-1||ua.indexOf("qiange")!=-1){
        return true;
    }else{
        return false;
    }
}

function GetQueryString(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    if(r!=null)return  r[2]; return null;
}


function openIphoneApp_ios_9(url) {
    $("body").html('<div class="bg_a_app" style="z-index: 101;">\
         </div>\
        <div class="android_tc" style="min-width: 240px;z-index: 123;">\
            <div class="android_tc_1">在"手机淘宝"中打开链接吗？</div>\
            <div class="android_tc_2">\
                <span class="andriod_span_close" type="0">取消</span>\
                <span class="andriod_span_open" type="1">打开</span>\
            </div>\
        </div>');
    $(".andriod_span_close").click(function () {
        window.setTimeout(function() {
            window.location = url;
        }, 1000);
        return 2;
    })
    $(".andriod_span_open").click(function () {
        var tb_url = url.replace("http://", "").replace("https://", "");
        window.location = "taobao://" + tb_url;
    });
}
function openApp_android(url) {
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/tb/i) == "tb") {
        window.location.replace(url);
        return 2;
    }
    $("body").html('<div class="bg_a_app" style="z-index: 101;">\
         </div>\
        <div class="android_tc" style="min-width: 240px;z-index: 123;">\
            <div class="android_tc_1">在"手机淘宝"中打开链接吗？</div>\
            <div class="android_tc_2">\
                <span class="andriod_span_close" type="0">取消</span>\
                <span class="andriod_span_open" type="1">打开</span>\
            </div>\
        </div>');
    $(".andriod_span_close").click(function () {
        window.location = url;
    })
    $(".andriod_span_open").click(function () {
        var tb_url = url.replace("http://", "").replace("https://", "");
        window.location = "taobao://" + tb_url;
        return 2;
    })
}

function openApp_ios(url) {
    // 通过iframe的方式试图打开APP，如果能正常打开，会直接切换到APP，并自动阻止a标签的默认行为
    // 否则打开a标签的href链接
    $("body").html('<div class="bg_a_app" style="z-index: 101;">\
         </div>\
        <div class="android_tc" style="min-width: 240px;z-index: 123;">\
            <div class="android_tc_1">在"手机淘宝"中打开链接吗？</div>\
            <div class="android_tc_2">\
                <span class="andriod_span_close" type="0">取消</span>\
                <span class="andriod_span_open" type="1">打开</span>\
            </div>\
        </div>');
    $(".andriod_span_close").click(function () {
        window.location = url;
    })
    $(".andriod_span_open").click(function () {
        var tb_url = url.replace("http://", "").replace("https://", "");
        var ifr = document.createElement('iframe');
        ifr.src = 'taobao://' + tb_url;
        ifr.style.display = 'none';
        document.body.appendChild(ifr);
        return 2;
    });
}
