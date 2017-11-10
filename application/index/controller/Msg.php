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
use app\index\model\UserTask;
use app\index\model\User;
use app\index\model\UserReportTask;

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
            return 'success';
        }

        if($origin_data['MsgType'] == 'event') {
            if($origin_data['Event'] == 'subscribe') {
                $resp_data = array(
                    'ToUserName' => $origin_data['FromUserName'],
                    'FromUserName' => $origin_data['ToUserName'],
                    'CreateTime' => time(),
                    'MsgType' => 'text',
                    'Content' => "哎哟喂~~同志\n现在淘宝和天猫链接终于可以在微信上直接打开了\n只需要发送淘宝或天猫口令给我，系统就会自动将口令转为短链接，实现一秒进店啦！\n作为一个专业的淘宝客服务平台，我们坚持只做一个免费的口令转换器，不会抽取任何优惠券佣金噢！！！\n1、在对话框发送淘宝口令或淘宝链接，系统自动回复短链接；\n2、输入“日报”，可查看每天短链接点击数据；"
                    # 'Content' => "哎哟喂~~同志\n现在淘宝和天猫链接终于可以在微信上直接打开了\n只需要发送淘宝或天猫口令给我，系统就会自动将口令转为短链接，实现一秒进店啦！\n作为一个专业的淘宝客服务平台，我们坚持只做一个免费的口令转换器，不会抽取任何优惠券佣金噢！！！"
                );
                return Response::create($resp_data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            } else if($origin_data['Event'] == 'SCAN') {
                $nickname = '';
                $user = User::get(['openid' => $origin_data['FromUserName']]);
                if($user) {
                    $nickname = $user->nickname;
                    $user->ticket = $origin_data['Ticket'];
                    $user->save();
                } else {
                    $userinfo = $this->_get_userinfo($origin_data['FromUserName']);
                    if(!empty($userinfo)) {
                        $nickname = $userinfo['nickname'];

                        $user = new User;
                        $user->data([
                            'nickname' => $userinfo['nickname'],
                            'openid' => $userinfo['openid'],
                            'ticket' => $origin_data['Ticket'],
                            'ext' => json_encode($userinfo)
                        ]);
                        $user->save();
                    }
                }
                $resp_data = array(
                    'ToUserName' => $origin_data['FromUserName'],
                    'FromUserName' => $origin_data['ToUserName'],
                    'CreateTime' => time(),
                    'MsgType' => 'text',
                    'Content' => "欢迎回来, {$nickname}"
                );
                return Response::create($resp_data, 'xml')->code(200)->options(['root_node'=> 'xml']);
            }
            return 'success';
        }

        if($this->detect_keyword_msg($origin_data['Content'], $origin_data['FromUserName'])) {
            $resp_data = array(
                'ToUserName' => $origin_data['FromUserName'],
                'FromUserName' => $origin_data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'text',
                'Content' => ''
            );
            $resp_data['Content'] = '请稍后,正在为您查询...';
            return Response::create($resp_data, 'xml')->code(200)->options(['root_node'=> 'xml']);
        }

        $code_url = ['code'=> '', 'url'=> ''];
        $code_url = $this->detect_code_url($origin_data['Content']);
        if(empty($code_url['code']) and empty($code_url['url'])) {
            return 'success';
        }

        $resp_data = array(
            'ToUserName' => $origin_data['FromUserName'],
            'FromUserName' => $origin_data['ToUserName'],
            'CreateTime' => time(),
            'MsgType' => 'text',
            'Content' => ''
        );

        $conv_time = ConvertTimes::get(['account' => $origin_data['FromUserName'], 'date' => date('Y-m-d')]);
        $conv_limit = 100;
        if ($conv_time && $conv_time->times >= $conv_limit) {
            $resp_data['Content'] = '您已达到每日转换上限'.$conv_limit.'条独立淘口令,请明天再来!';
            return Response::create($resp_data, 'xml')->code(200)->options(['root_node'=> 'xml']);
        }
        if($conv_time) {
            $conv_time->times += 1;
            $conv_time->save();
        } else {
            $conv = new ConvertTimes;
            $conv->data([
                'account' => $origin_data['FromUserName'],
                'date' => date('Y-m-d'),
                'times' => 1
            ]);
            $conv->save();
        }
        

        if(!empty($code_url['code'])) {
            $this->_create_code_task($code_url['code'], $origin_data['Content'], $origin_data['FromUserName']);
        } elseif(!empty($code_url['url'])) {
            $this->_create_url_task($code_url['url'], $origin_data['Content'], $origin_data['FromUserName']);
        }
        

        $resp_data['Content'] = '请稍后,正在为您查询...';
        return Response::create($resp_data, 'xml')->code(200)->options(['root_node'=> 'xml']);
    }

    private function _create_code_task($code, $origin_content, $fromuser='')
    {
        $code_md5 = md5(urlencode($code));
        $material = Material::get(['code_md5' => $code_md5]);
        if(empty($info)) {
            $material = new Material;
            $material->data([
                'title' => $origin_content,
                'content' => '',
                'code' => $code,
                'code_md5' => $code_md5,
                'mid' => '100000',
                'origin_url' => '',
                'origin_url_md5' => '',
                'local_url' => '',
                'short_url' => '',
                'account' => $fromuser,
                'ext' => ''
            ]);
            $material->save();
        }
        $user_task = new UserTask;
        $user_task->data([
            'material_id' => $material->id,
            'account' => $fromuser,
            'is_sended' => 0
        ]);
        $user_task->save();
    }

    private function detect_keyword_msg($origin_content, $fromuser) {
        $flag = False;
        if($origin_content == '日报') {
            $flag = True;
            $user_report_task = New UserReportTask;
            $user_report_task->data([
                'account' => $fromuser,
                'is_sended' => 0
            ]);
            $user_report_task->save();
        }
        return $flag;
    }

    private function detect_code_url($origin_content)
    {
        $code_url = ['code'=> '', 'url'=> ''];
        if(stripos($origin_content, 'taobao.com/') || stripos($origin_content, 'tmall.com/')){
            $code_url['url'] = $origin_content;
        } else {
            preg_match('/￥(.*?)￥/i', $origin_content, $code_match);
            if(empty($code_match)) {
                if(ctype_alnum($origin_content) && mb_strlen($origin_content) <= 17 ) {
                    $code_url['code'] = '￥'.$origin_content.'￥';
                } else {
                    preg_match_all('/[0-9A-Za-z]{11}/i', $origin_content, $code_match);
                    if(!empty($code_match[0])) {
                        $code_url['code'] = '￥'.end($code_match[0]).'￥';
                    }
                }
            } else {
                $code_url['code'] = $code_match[0];
            }    
        }
        return $code_url;        
    }

    private function _create_url_task($url, $origin_content, $fromuser='')
    {
        $url_md5 = md5($url);
        $material = Material::get(['origin_url_md5' => $url_md5]);
        if(empty($info)) {
            $material = new Material;
            $material->data([
                'title' => $origin_content,
                'content' => '',
                'code' => '',
                'code_md5' => '',
                'mid' => '100000',
                'origin_url' => $url,
                'origin_url_md5' => $url_md5,
                'local_url' => '',
                'short_url' => '',
                'account' => $fromuser,
                'ext' => ''
            ]);
            $material->save();
        }
        $user_task = new UserTask;
        $user_task->data([
            'material_id' => $material->id,
            'account' => $fromuser,
            'is_sended' => 0
        ]);
        $user_task->save();
    }

    private function _get_userinfo($openid)
    {
        $token = $this->_get_wx_token();
        $api = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$token['access_token'].'&openid='.$openid.'&lang=zh_CN';
        $resp = curl_get($api);
        $resp = json_decode($resp, true);
        return $resp;
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
}
