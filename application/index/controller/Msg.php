<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;

class Msg extends Controller
{
    public function index()
    {
        $echostr = Request::instance()->get('echostr');
        echo $echostr;
    }
}
