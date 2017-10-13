<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

Route::rule('/', 'index/Index/index');
Route::rule('agent/$', 'index/Index/agent');
Route::rule('__miss__', 'index/Index/index');

Route::rule('surl/$', 'index/SUrl/index');
Route::rule('surl/refresh$', 'index/SUrl/refresh');

Route::rule('wx2tb/$', 'index/Msg/index');


Route::rule('go', 'index/Page/promotion');
Route::rule('jump', 'index/Page/index');

Route::rule('touch', 'index/Page/touch');