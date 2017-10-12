
function tbApp(shopUrl){
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
        return 1;
    }else {
        // $("body").html("<div style='color:#000000;display: block;font-size: 22px;height: 1000px;margin-left:10px;text-align:center;'>正在跳转.....</div> ");
        if (os_type == "iPhone_ios_9") {
            // openIphoneApp_ios_9(shopUrl);
        } else if (os_type == "android") {
            openApp_android(shopUrl);
        } else if (os_type == "iPhone") {
            // openApp_ios(shopUrl);
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

function openIphoneApp_ios_9(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    window.location = "taobao://" + tb_url;
}
function openApp_android(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    console.log(tb_url);
    window.location = "taobao://" + tb_url;
}

function openApp_ios(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    var ifr = document.createElement('iframe');
    ifr.src = 'taobao://' + tb_url;
    ifr.style.display = 'none';
    document.body.appendChild(ifr);
}
