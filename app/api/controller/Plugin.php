<?php
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
// | DateTime：2020-12-31 18:28:38
// +----------------------------------------------------------------------

namespace app\api\controller;

use app\common\controller\ApiController;

use think\facade\Request;
use app\common\Plugins;

class Plugin extends ApiController
{
    public function call()
    {
        //plugin.b_2-index-index-index
        $name = 'plugin.';
        $info = Request::pathinfo();
        if (substr ($info, 0,strlen($name)) !== $name) {
            return $this->error('未知请求', '','');
        }
        $plugin = str_replace($name, '', $info);
        $call = explode('-', $plugin);
        if(count($call) !== 4){
            return $this->error('未知请求', '','');
        }
        $data = Plugins::GetPluginState($call[0]);
        $call3 = explode('.', $call[3]);
        // print_r($data);
        if (!$data) {
            return $this->error('未知请求', '','');
        }
        // AcShop\plugin\<p1>\api\<p2>\<p3>@<p4>
        try {
            $dic = 'HaSog\plugin\\'.$call[0].'\api\\'.$call[1].'\\'.$call[2];
            $dic2 = $call3[0];
            $test = new $dic($this->app);
            $test->$dic2();
        }  catch (\Throwable $e) {
            return $this->error('未知请求', '','');
        }

    }
}