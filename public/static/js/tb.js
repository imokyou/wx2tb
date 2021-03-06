
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
        $('#weixin-tip').show();  
        return 1;
    }else {
        if (os_type == "iPhone_ios_9") {
            openApp_iphone(shopUrl);
        } else if (os_type == "android") {
            openApp_android(shopUrl);
        } else if (os_type == "iPhone") {
            openApp_iphone(shopUrl);
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

function openApp_android(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    console.log(tb_url);
    window.location = "taobao://" + tb_url;
}

function openApp_iphone(url) {
    window.location="https://t.asczwa.com/taobao?backurl="+url;
}

function openIphoneApp_ios_9(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    window.location.href=tb_url;
    // window.location = "taobao://" + tb_url;
}

function openApp_ios(url) {
    var tb_url = url.replace("http://", "").replace("https://", "");
    var ifr = document.createElement('iframe');
    ifr.src = 'taobao://' + tb_url;
    ifr.style.display = 'none';
    document.body.appendChild(ifr);
}