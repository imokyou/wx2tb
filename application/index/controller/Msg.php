<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;
use think\Log;

class Msg extends Controller
{
    public function index()
    {
        $xml = file_get_contents('php://input');
        Log::record($xml, 'info');
        if ($xml) {
            $origin_data = xml_to_data($xml);

            $data = array(
                'ToUserName' => $origin_data['FromUserName'],
                'FromUserName' => $origin_data['ToUserName'],
                'CreateTime' => time(),
                'MsgType' => 'text',
                'Content' => '这是回复~'
            );
            return Response::create($data, 'xml')->code(200)->options(['root_node'=> 'xml']);
        }
        echo 'success';
    }
}
