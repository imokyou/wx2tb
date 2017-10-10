<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Log;
use think\Config;
use think\Db;

use app\index\model\Material;

class Msg extends Controller
{
    public function index()
    {   
        $sign = Request::instance()->get('signature');
        $msg_sign = Request::instance()->get('msg_signature');
        $timestamp = Request::instance()->get('timestamp');
        $nonce = Request::instance()->get('nonce');

        $xml = file_get_contents('php://input');
        Log::record($xml, 'info');

        if (!trim($xml)) {
            return 'success';
        }

        $origin_data = xml_to_data($xml);

        $wxmsg_config = Config::get('wxmsg');
        $wxmsg = new \WxMsg\WXBizMsgCrypt($wxmsg_config['token'], $wxmsg_config['aes_key'], $wxmsg_config['appid']);
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $origin_data['Encrypt']);
        $msg = '';
        $errcode = $wxmsg->decryptMsg($msg_sign, $timestamp, $nonce, $from_xml, $msg);
        if ($errcode == 0) {
            log::record($msg, 'info');
        }

        $origin_data['Content'] = $msg;
        if (!preg_match('/￥(.*?)￥/i', $origin_data['Content'])) {
            return 'success';
        }

        $ret = $this->_convert_code($origin_data['Content'], $origin_data['FromUserName']);
        if (!$ret) {
            return 'success';
        }

        $content = $origin_data['Content'].$ret['url'];
        $data = array(
            'ToUserName' => $origin_data['FromUserName'],
            'FromUserName' => $origin_data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => $content
        );
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

    private function _convert_code($m, $account='')
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
                    'account' => $account
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

    private function _lurl_to_surl($lurl)
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
