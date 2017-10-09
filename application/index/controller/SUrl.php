<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Loader;
use think\Config;
use think\DB;

use app\index\model\Material;


class SUrl extends Controller
{
    public function index()
    {
        $m = Request::instance()->post('m');
        $t = Request::instance()->post('t');
        $s = Request::instance()->post('s');
        $account = Request::instance()->post('account');

        $data = array('c' => 0, 'm' => '', 'd' => array());

        if (empty($m) || empty($t) || empty($s)) {
            $data['c'] = -1;
            $data['m'] = 'args missing';
            return Response::create($data, 'json')->code(200);
        }
        if (strtoupper(md5($t)) != $s) {
            $data['c'] = -1;
            $data['m'] = 'sign error';
            return Response::create($data, 'json')->code(200);
        }

        if (trim($m)) {
            $m = urldecode(trim($m));    
        }
        // $m = '【北欧实木皮烤漆书柜书架组合简约现代创意多宝阁格子柜定制白SG-1】，复制这条信息￥fKu30fG9i7J￥后打开👉手机淘宝👈';
        if (preg_match('/http/i', $m)) {
            # 这是淘宝链接, 需要生成淘口令以及生成短链接, 暂不支持
            $data['c'] = -1;
            $data['m'] = '暂时不支持淘宝链接';
            return Response::create($data, 'json')->code(200);
            # $ret = $this->_convert_url($m);
        } elseif (preg_match('/￥/i', $m)) {
            # 这是淘口令, 需要还原成淘宝链接以及生成短链接
            $ret = $this->_convert_code($m);
        } else {
            $ret = array();
        }

        if ($ret) {
            $data['d'] = array('url' => $ret['url'], 'lurl' => $ret['lurl'], 'code' => $ret['code']);
        }
        return Response::create($data, 'json')->code(200);
    }

    private function _convert_url($m)
    {
        $ret = array();
        $ret['code'] = $this->_lurl_to_code($m);
        $ret['url'] = $this->_lurl_to_surl($m);
        return $ret;
    }

    private function _convert_code($m)
    {
        $ret = array();

        $code_md5 = md5(urlencode($m));
        $info = Db::table('material')->where('code_md5', $code_md5)->select();
        if (!empty($info)) {
            $ret = array(
                'url' => $info[0]['short_url'],
                'lurl' => $info[0]['origin_url'],
                'code' => $info[0]['code']
            );
        } else {
            $config = Config::get('taokouling');
            $account = $config['accounts'][array_rand($config['accounts'])];
            $data = "username={$account['u']}&password={$account['p']}&text=".urlencode($m);
            $resp = curl_post($config['api'], $data);
            if ($resp) {
                $resp = json_decode($resp, true);

                $material = new Material;
                $material->data([
                    'title' => $m,
                    'code' => $m,
                    'code_md5' => md5(urlencode($m)),
                    'mid' => '100000',
                    'origin_url' => $resp['url'],
                    'origin_url_md5' => md5(urlencode($resp['url'])),
                    'local_url' => '',
                    'short_url' => '',
                    'account' => ''
                ]);
                $material->save();

                $local_url = $config['domain'].'/go/?itemid='.$material->id; 

                $ret['code'] = $m;
                $ret['lurl'] = $resp['url'];
                $ret['url'] = $this->_lurl_to_surl($local_url);

                $material->local_url = $local_url;
                $material->short_url = $ret['url'];
                $material->save();
            }
        }
        return $ret;
    }

    private function _lurl_to_code($lurl)
    {
        $ret = '';
        return $ret;
    }

    public function _lurl_to_surl($lurl)
    {
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

        $params= new \ShortURL\Model\addModel();
        $params->setLongurl($lurl);
        $api_result = $api->add($params);
        if ($api_result && $api_result['status'] == 1) {
            return $api_result['data']['short_url'];
        }
        return '';
    }
}
