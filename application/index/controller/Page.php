<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;

class Page extends Controller
{
    public function index()
    {
        $redirect = Request::instance()->get('redirect');
        $agent = Request::instance()->header('user-agent');
        if(preg_match('/micromessenger/i', strtolower($agent))) {
            header("Content-type: application/octet-stream");  
            header("Accept-Ranges: bytes");  
            header("Accept-Length: 0");  
            header("Content-Disposition: attachment; filename=go.doc");  
            echo '';
        } else {
            $this->assign('redirect', $redirect);
            return $this->fetch('page');
        }
    }

    private function is_weixin($agent)
    {
        $flag = true;
        if(preg_match('/micromessenger/i', strtolower($agent))) { 
            $flag = true;
        } else {
            $flag = false;
        }
        return $flag;
    }

    private function response_weixin()
    {
        $file_name = 'go.doc';
        header("Content-type: application/octet-stream");  
        header("Accept-Ranges: bytes");  
        header("Accept-Length: 0");  
        header("Content-Disposition: attachment; filename=".$file_name);  
        echo '';
    }

    private function response_web($redirect)
    {

    }

}
