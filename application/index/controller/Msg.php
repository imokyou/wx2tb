<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Log;
use think\Config;
use think\Db;

use app\index\model\Material;
use app\index\model\ConvertTimes;

class Msg extends Controller
{
    public function index()
    {   
        $sign = Request::instance()->get('signature');
        $msg_sign = Request::instance()->get('msg_signature');
        $timestamp = Request::instance()->get('timestamp');
        $nonce = Request::instance()->get('nonce');
        $echostr = Request::instance()->get('echostr');
        if (!empty($echostr)) {
            return $echostr;
        }

        $xml = file_get_contents('php://input');
        Log::record($xml, 'info');

        if (!trim($xml)) {
            return 'success';
        }

        $encrypt_data = xml_to_data($xml);

        $wxmsg_config = Config::get('wxmsg');
        $wxmsg = new \WxMsg\WXBizMsgCrypt($wxmsg_config['token'], $wxmsg_config['aes_key'], $wxmsg_config['appid']);
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt_data['Encrypt']);
        $decrypt_xml = '';
        $errcode = $wxmsg->decryptMsg($msg_sign, $timestamp, $nonce, $from_xml, $decrypt_xml);
        if ($errcode == 0) {
            $origin_data = xml_to_data($decrypt_xml);
        } else {
            $origin_data = [];
            return 'success';
        }

        if (!preg_match('/￥(.*?)￥/i', $origin_data['Content'])) {
            return 'success';
        }


        $data = array(
            'ToUserName' => $origin_data['FromUserName'],
            'FromUserName' => $origin_data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => ''
        );
        $ret = $this->_convert_code($origin_data['Content'], $origin_data['FromUserName']);
        if (empty($ret)) {
            return 'success';
        } elseif ($ret['c'] != 0) {
            $data['Content'] = $ret['m'];
        } else {
            $data['Content'] = $origin_data['Content'].'  '.$ret['url'];    
        }
        return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

    private function _convert_code($m, $fromuser='')
    {
        $ret = array();

        $code_md5 = md5(urlencode($m));
        $info = Db::table('material')->where('code_md5', $code_md5)->select();
        if (!empty($info)) {
            $ret = array(
                'c' => 0,
                'm' => '',
                'url' => $info[0]['short_url'],
                'lurl' => $info[0]['origin_url'],
                'code' => $info[0]['code']
            );
        } else {
            # 检查是达到每日转换限量
            $conv_time = ConvertTimes::get(['account' => $fromuser, 'date' => date('Y-m-d')]);
            $conv_limit = 10;
            if ($conv_time) {
                if ($conv_time->times >= $conv_limit) {
                    $ret = array('c' => -1, 'm' => '您已达到每日转换上限'.$conv_limit.'条独立淘口令,请明天再来!');
                    return $ret;
                }
                $conv_time->times += 1;
                $conv_time->save();
            } else {
                $conv = new ConvertTimes;
                $conv->data([
                    'account' => $fromuser,
                    'date' => date('Y-m-d'),
                    'times' => 1
                ]);
                $conv->save();
            }
            $config = Config::get('taokouling');
            $account = $config['accounts'][array_rand($config['accounts'])];
            $data = "username={$account['u']}&password={$account['p']}&text=".urlencode($m);
            Log::record($data);
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
                    'account' => $fromuser
                ]);
                $material->save();

                $local_domain = $config['domains'][array_rand($config['domains'])];
                $local_url = $local_domain.'/go/?itemid='.$material->id; 

                $ret['code'] = $m;
                $ret['c'] = 0;
                $ret['m'] = '';
                $ret['lurl'] = $resp['url'];
                $ret['url'] = $this->_lurl_to_surl($local_url);

                $material->local_url = $local_url;
                $material->short_url = $ret['url'];
                $material->save();
            } else {
                $ret = array('c' => -1, 'm' => '淘口令解密失败,请重试');
                    return $ret;
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
