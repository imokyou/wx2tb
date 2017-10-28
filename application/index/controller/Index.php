<?php
namespace app\index\controller;

use think\Controller;
use think\Response;
use think\Request;
use think\Config;
use app\index\model\Material;


class Index  extends Controller
{
    public function index()
    {
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

    public function agent()
    {
        $agent = Request::instance()->header('user-agent');
        return $agent;
    }

    private function _get_domain($domains)
    {
        return $domains[rand(0, count($domains)-1)];
    }

}
