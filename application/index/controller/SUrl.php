<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Config;
use think\Db;

use app\index\model\Material;


class SUrl extends Controller
{

    public function index()
    {
        return 'success';
    }

    public function refresh()
    {
        $p = Request::instance()->get('p');
        $n = Request::instance()->get('n');

        $p = $p ? intval($p) : 1;
        $n = $n ? intval($n) : 1;

        $material = new Material();
        $list = $material->where('local_url', 'like', 'http://tb.js101.wang%')
            ->limit($n)
            ->page($p)
            ->order('id', 'asc')
            ->select();

        if (!empty($list)) {
            $config = Config::get('shorturl');
            $access_token_file = './../application/extra/access_token.txt';

            $config = new \ShortURL\Config($config['key'], $config['secret']);
            $api = new \ShortURL\API($config);
            if(file_exists($access_token_file)){
                $api->setAccessToken(file_get_contents($access_token_file));
            }else{
                $token = $api->requestAccessToken();
                file_put_contents($access_token_file, $token);
            }

            $str = '';
            foreach($list as $key => $value){
                $params = new \ShortURL\Model\modifyModel();
                $params->setShorturl($value['short_url']);
                $params->setNewUrl($value['local_url']);
                $api_result = $api->modify($params);

                $str .= $value['local_url'].' | '.$value['short_url'].'<br>';
            }
            $p ++;
            $str .= '<script>setTimeout(function(){window.location="/surl/refresh?p='.$p.'"}, 1000)</script>';
            return $str;
        } else {
            return '域名更换完成！';    
        }
        

    }
}
