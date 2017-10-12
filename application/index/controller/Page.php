<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Response;

use app\index\model\Material;

class Page extends Controller
{
    public function index()
    {
        $itemid = Request::instance()->get('itemid');
        $agent = Request::instance()->header('user-agent');

        if(preg_match('/micromessenger/i', $agent) && !preg_match('/iphone/i', $agent)) {
            header("Content-type: application/octet-stream");  
            header("Accept-Ranges: bytes");  
            header("Accept-Length: 0");  
            header("Content-Disposition: attachment; filename=go.doc");  
            echo '';
        } else {
            $show_iframe = '1';
            if(preg_match('/iphone/i', $agent)) {
                $show_iframe = '0';
            }
            $redirect = '';
            $material = Material::get(intval($itemid));
            if ($material) {
                $redirect = $material->origin_url;
                $material->click_count += 1;
                $material->save();
            }
            $this->assign('show_iframe', $show_iframe);
            $this->assign('redirect', $redirect);
            return $this->fetch('page');
        }
    }

    public function promotion()
    {
        $itemid = Request::instance()->get('itemid');
        $redirect = '/jump?itemid='.$itemid;
        $this->assign('redirect', $redirect);
        return $this->fetch('promotion');
    }
}
