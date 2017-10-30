<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Config;
use think\Session;

use app\index\model\Material;
use app\index\model\User;

class Index  extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->userinfo = Session::get('userinfo') ? Session::get('userinfo') : [];
    }

    public function index()
    {
        $this->assign('userinfo', $this->userinfo);
        return $this->fetch('index');
    }

    public function tpwd()
    {
        try {
            $data = ['c' => 0, 'm' => '', 'd' => []];
            $req = Request::instance();

            $url = $req->post('url');
            # $url = 'https://detail.tmall.com/item.htm?spm=a1z10.4-b-s.w5003-15646340731.2.227f4e3flGEZVP&id=559983439811&scene=taobao_shop&skuId=3493501272522';
            if(empty($url)) {
                throw new \Exception("Arg Missing", -1024);
            }
            if(!url_valid($url)) {
                throw new \Exception("Url InValid", -1024); 
            }


            $url_md5 = MD5($url);
            $material = Material::get(['origin_url_md5' => $url_md5]);
            if($material) {
                $data['d'] = [
                    'code' => $material->code,
                    'title' => $material->content,
                    'short_url' => $material->short_url
                ];
                return Response::create($data, 'json')->code(200);
            }

            $html = curl_get($url);
            $pos = strpos($html,'utf8');
            if($pos===false) {
                $html = iconv("gbk", "utf8", $html);
            }
            preg_match("/<title>(.*)<\/title>/i",$html, $title);
            $data['d']['title'] = $title[1];
            

            $tconfig = Config::get('tpwd');
            $topClient = new \TopClient();
            $topClient->appkey = $tconfig['appkey'];
            $topClient->secretKey = $tconfig['appsecret'];
            $topClient->format = 'json';

            $req = new \WirelessShareTpwdCreateRequest;

            $tpwd_param = new \GenPwdIsvParamDto;
            $tpwd_param->url = $url;
            $tpwd_param->text = $data['d']['title'];

            $req->setTpwdParam(json_encode($tpwd_param));
            $resp = $topClient->execute($req);
            $data['d']['code'] = $resp->model;

            $material = new Material;
            $material->data([
                'title' => $url,
                'content' => $data['d']['title'],
                'code' => $data['d']['code'],
                'code_md5' => MD5($data['d']['code']),
                'mid' => '100000',
                'origin_url' => $url,
                'origin_url_md5' => MD5($url),
                'local_url' => '',
                'short_url' => '',
                'account' => 'web',
                'ext' => ''
            ]);
            $material->save();


            $sconfig = Config::get('shorturl');
            $config = new \ShortURL\Config($sconfig['key'], $sconfig['secret']);
            $api = new \ShortURL\API($config);

            $access_token_file = dirname(__FILE__).'/../../../pyscript/res/short_url_access_token.txt';
            $stoken = json_decode(file_get_contents($access_token_file), true);
            $api->setAccessToken($stoken['access_token']);

            $local_url = $this->_get_domain($sconfig['domains']).'/go?itemid='.$material->id;
            $params= new \ShortURL\Model\addModel();
            $params->setLongurl($local_url);
            $api_result = $api->add($params);
            $data['d']['short_url'] = $api_result['data']['short_url'];

            $material->local_url = $local_url;
            $material->short_url = $data['d']['short_url'];
            $material->save();

        } catch (\Exception $e) {
            $data = ['c' => -1024, 'm' => $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function qrcode()
    {
        try {
            $data = ['c' => 0, 'm' => '', 'd' => []];

            if(!empty($this->userinfo)) {
                throw new \Exception("Request Error", -1024);   
            }

            $wx_token = $this->_get_wx_token();
            if(empty($wx_token)) {
                throw new \Exception("Something Error", -1024);
            }

            $ticket = $this->_get_ticket($wx_token);
            if(empty($ticket)) {
                throw new \Exception("Something Error", -1024);
            }

            $qrcode = $this->_get_qrcode($ticket);
            if(empty($qrcode)) {
                throw new \Exception("Something Error", -1024);
            }
            $data['d'] = [
                'uniqid'=> $ticket['uniqid'],
                'qrcode' => base64_encode($qrcode),
                'ticket' => $ticket['ticket']
            ];

        } catch (\Exception $e) {
            $data = ['c' => -1024, 'm' => $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    } 

    public function checklogin()
    {
        try {
            $data = ['c' => 0, 'm' => '', 'd' => []];

            if(!empty($this->userinfo)) {
                $data['d'] = $this->userinfo;
            } else {
                $req = Request::instance();
                $ticket = $req->post('ticket');
                if(empty($ticket)) {
                    throw new \Exception("Arg Missing", 1);
                }

                $user = User::get(['ticket' => $ticket]);
                if(empty($user)) {
                    throw new \Exception("Data Missing", 1);
                }

                $ext = json_decode($user['ext'], true);
                $this->userinfo = [
                    'nickname' => $user->nickname,
                    'id' => $user->id,
                    'avatar' => $ext['headimgurl']
                ];
                Session::set('userinfo', $this->userinfo);
                Cookie::set('userinfo', $this->userinfo, 3600*24);
                $data['d'] = $this->userinfo;
            }

        } catch (\Exception $e) {
            $data = ['c' => -1024, 'm' => $e->getMessage(), 'd' => []];
        }
        return Response::create($data, 'json')->code(200);
    }

    public function agent()
    {
        $agent = Request::instance()->header('user-agent');
        return $agent;
    }

    private function _get_domain($domains)
    {
        return $domains[rand(0, count($domains)-1)];
    }

    private function _get_wx_token()
    {
        $token = [];
        $token_expired = true;

        $token_file = './../application/extra/wx_access_token.txt';
        if(file_exists($token_file)){
            $token = json_decode(file_get_contents($token_file), true);
            if(!empty($token)) {
                if($token['expires_time']-time()-1000 > 0) {
                    $token_expired = false;
                } 
            }
        }

        if($token_expired) {
            $config = Config::get('wxmsg');
            $resp = curl_post($config['token_api']);
            $token = json_decode($resp, true);
            if(!empty($token) && array_key_exists('access_token', $token)) {
                $token['expires_time'] = time() + $token['expires_in'];
                file_put_contents($token_file, json_encode($token));
            }
        }
        return $token;
    }

    private function _get_ticket($wx_token)
    {
        $uniqid = get_uniqid();
        $api = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$wx_token['access_token'];
        $req_data = [
            'expire_seconds'=> 120,
            'action_name'=> 'QR_STR_SCENE',
            'action_info'=> [
                'scene'=> ['scene_str'=> $uniqid]
            ]
        ];
        $resp = curl_post($api, json_encode($req_data));
        $resp = json_decode($resp, true);
        if(!empty($resp) && array_key_exists('ticket', $resp)) {
            $resp['uniqid'] = $uniqid;
        }
        return $resp;
    }

    private function _get_qrcode($ticket)
    {
        $api = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.urlencode($ticket['ticket']);
        $resp = curl_get($api);
        return $resp;
    }

}
