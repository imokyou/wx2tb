<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;

class TaoCode extends Controller
{
    public function index()
    {
        $code = Request::instance()->post('code');
        $resp = '';
        if ($code) {
            $api = 'http://www.taokouling.com/index.php?m=api&a=taokoulingjm';
            $data = 'username=imokyou&password=7457267293&text='.urlencode($code);
            $resp = curl_post($api, $data);    
        }
        if ($resp) {
            $resp = json_decode($resp);
        }

        $this->assign('code', $code);
        $this->assign('resp', $resp);
        return $this->fetch('code');
    }

    public function code()
    {
        $title = '【稀缺货源】Apple/苹果 iPhone 6 32G 全网通4G智能手机';
        $lurl = 'https://a.m.taobao.com/i545246502913.htm?price=2349&sourceType=item&sourceType=item&suid=e78a14a1-5d1d-4bb2-8ba5-45a01662cd20&ut_sk=1.WUynWXp7BTwDAGyE0xpCqCYX_21646297_1507457668846.Copy.1&un=790d32823f89ec0c8a195baaa22d8f6a&share_crt_v=1';

        $c = new \TopClient;
        $c->appkey = $appkey;
        $c->secretKey = $secret;
        $req = new \WirelessShareTpwdCreateRequest;
        $tpwd_param = new \GenPwdIsvParamDto;
        $tpwd_param->url = $lurl;
        $tpwd_param->text = $title;
        $tpwd_param->user_id = "24234234234";
        $req->setTpwdParam(json_encode($tpwd_param));
        $resp = $c->execute($req);


        $api = 'http://www.taokouling.com/index.php?m=api&a=GetTkl1';
        $data = 'username=imokyou&password=7457267293&text='.urlencode($title).'&url='.urlencode($lurl);
        $resp = curl_post($api, $data);
        echo '<pre>';
        print_r($resp);
        print_r(json_decode($resp));
    }
}
