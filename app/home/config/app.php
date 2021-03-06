<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------
// | HaSog (幻神商城系统)
// +----------------------------------------------------------------------
// | 技术支持【幻神科技】: https://www.hasog.com
// +----------------------------------------------------------------------
// | 联系我们:  https://www.hasog.com
// +----------------------------------------------------------------------
// | gitee开源项目：https://gitee.com/orzice/hasog
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/orzice/hasog
// +----------------------------------------------------------------------
// | Author：Orzice(小涛)  https://gitee.com/orzice
// +----------------------------------------------------------------------
// | DateTime：2020-12-31 18:22:57
// +----------------------------------------------------------------------
return [
    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',
   
    'http_exception_template' => [
        // 定义404错误的模板文件地址
        // 200 => \think\facade\App::getAppPath() . 'tpl' . DIRECTORY_SEPARATOR .'404.html',
        // 定义404错误的模板文件地址
        404 => \think\facade\App::getAppPath() . 'tpl' . DIRECTORY_SEPARATOR .'404.html',
        // 还可以定义其它的HTTP status
        403 => \think\facade\App::getAppPath() . 'tpl' . DIRECTORY_SEPARATOR .'403.html',
    ]
];
