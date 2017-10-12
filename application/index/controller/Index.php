<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
        echo 'Hello World';
    }

    public function agent()
    {
        $agent = Request::instance()->header('user-agent');
        return $agent;
    }

}
