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
// | DateTime：2020-12-31 18:16:13
// +----------------------------------------------------------------------

namespace app\admin\controller\order;


use app\common\components\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\App;
use think\facade\Config;
use app\common\model\Order;
use app\common\controller\AdminController;
use app\common\model\OrderGoods;
use app\common\model\OrderAddress;

use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\facade\Db;

/**
 * Class Home
 * @package app\admin\controller\goods
 * @ControllerAnnotation(title="订单管理")
 */
class Home extends AdminController
{

    protected $sort = [
        'id' => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new Order();
    }

    /**
     * @NodeAnotation(title="订单列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParames();


            $get = $this->request->get();
            $action = isset($get['action']) && !empty($get['action']) ? $get['action'] : '{}';
            $action_model = $this->model;
            switch ($action) {
                case 'paid':
//                    $action_model = $this->model->whereNotNull('apply_time');
                    $this->model = $this->model->where('status', '=',1);
                    break;
                case 'send_goods':
//                    $action_model = $this->model->whereNotNull('content_time');
                    $this->model = $this->model->where('status', '=', 2);
                    break;
            }
            $w1 = false;
            $where1 = array();
            $where2 = array();

            for ($i = 0; $i < count($where); $i++) {
                if (strpos($where[$i][0], 'goods') !== false) {
                    $where[$i][0] = str_replace("goods.", "", $where[$i][0]);
                    $where1[] = $where[$i];
                } else {
                    $where2[] = $where[$i];
                }
            }
            if (count($where1) !== 0) {
                $where1 = OrderGoods::where($where1);
                $w1 = true;
            }

            $count = $this->model;
            if ($w1) {
                $count = $count->hasWhere('goods', $where1);
            }
            $count = $count->with(['goods', 'address'])
                ->where($where2)
                ->count();

            $list = $this->model;
            if ($w1) {
                $list = $list->hasWhere('goods', $where1);
            }
            $list = $list->with(['goods', 'address'])
                ->where($where2)
                ->page($page, $limit)
                ->order($this->sort)
                ->select();

            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            $this->model = $action_model;
            return json($data);
        }

        $get = $this->request->get();
        $action = isset($get['action']) && !empty($get['action']) ? $get['action'] : '{}';
        $this->assign('action', $action);
        return $this->fetch();
    }

    public function edit($id)
    {
        $order = Order::where('id', $id)->find();
        empty($order) && $this->error('订单不存在');
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $status_str = implode(',', array_keys($this->model::STATUS_ARRAY));
            $rule = [
//                'status|订单状态' => 'number|in:' . $status_str,
                'change_price|订单改价金额' => 'float|between: 0,99999999',
                'change_dispatch_price|运费改价金额' => 'float|between: 0,99999999',
                'merchant_remark|商家备注' => 'max:255',
                'express_code|快递公司码' => 'max:50',
                'express_company_name|快递公司名称' => 'max:50',
                'express_sn|快递单号' => 'max:1000',
            ];
            $price = 0;
            if ($order->status!==0){
                unset($post['change_price']);
                unset($post['change_dispatch_price']);
            }
            if ($order->status!==1 && $order->status!==2){
                unset($post['express_code']);
                unset($post['express_company_name']);
                unset($post['express_sn']);
            }
            $price += (isset($post['change_price']) && $post['change_price'] > 0)? $post['change_price'] : $order->goods_price;
            $price += (isset($post['change_dispatch_price']) && $post['change_dispatch_price'] > 0)? $post['change_dispatch_price'] : $order->dispatch_price;
            // 上级分类是否存在 并且存入id
            $this->validate($post, $rule);
            try {
//                $save = $this->model->find($id)->allowField($this->model::ALLOW_FIELDS)->save($post);
                if($price!=$order->price){
                    $order->price = $price;
                }
                if(!empty($post['express_sn'])){
                    $order->status = 2;
                    $order->send_time = time();
                }
//                print_r($post['express_sn']);die();
                $post['express_sn'] = json_encode(array_filter($post['express_sn']));
                $order->save();
                $save = $order->allowField($this->model::ALLOW_FIELDS)->save($post);

            } catch (\Exception $e) {
                $this->error('保存失败' . $e);
            }
            if ($save) {
                $this->success('保存成功');
            }
            $this->error('保存失败');
        }
        // 判断是否是json字符串
//        $first_str = !empty($order->express_sn)?substr($order->express_sn, 0, 1 ):[];
//        print_r(json_decode($order->express_sn));die();
//        $express_sns = ($first_str == '[' || $first_str == '{') ? json_decode($order->express_sn) : $order->express_sn;
//        print_r(json_encode(['23','321',3]));die();
//        $express_sns = json_decode();
        $express_sns = empty($order->express_sn) ? [''] : json_decode($order->express_sn);
        $this->assign('express_sns', $express_sns);
        $this->assign('status_array', Order::STATUS_ARRAY);
        $this->assign('plugin', []);
        $order->goods;
        $order->status_zh = Order::STATUS_ARRAY[$order->status];
//        $order->member = $order->member->field('mobile', 'head_img', 'id')->select();
////        print_r($order->member);die();
        $this->assign('row', $order);
        return $this->fetch();
    }


    // 获取批量发货模版
    public function batch_delivery()
    {
        $is_ajax = $this->request->isAjax();
//        !$is_ajax && return json(['msg'=>'错误']);
        $get = $this->request->get();
        $ids = $get['ids'];
        $ids = explode(',', $ids);
//        $results = $this->model->whereIn('id', $ids)->field('order_sn')->select();
        $results = $this->model->whereIn('id', $ids)
            ->field('order_sn, express_company_name, express_code, express_sn')
            ->select()->toArray();
//        $head = ['订单号', '快递公司名', '快递公司码', $results];
        $head = ['订单号', '快递单号', '快递公司名', '快递公司码'];
//        $head = ['订单号-order_sn', '快递公司名-express_company_name', '快递公司码-express_code', '快递单号-express_sn'];
//        $orders = [
//            ['a'=>1,'b'=>2,'c'=>3,'d'=>4,'e'=>5,]
//        ];
        $keys = ['order_sn', 'express_sn', 'express_company_name', 'express_code'];
        $exp = new \app\common\Excel();
        $file_name = date('Y-m-d H：i：s', time());
        $exp->export($file_name, $results, $head, $keys, 'xlsx');
//        $exp->export('测试1', [], $head, $keys,'xlsx');
    }


    public function receive_money(){
        $post = $this->request->post();
        $order_id = isset($post['order_id'])? $post['order_id'] : '';
        $order = Order::where('id', $order_id)->find();
        (empty($order) || $order->status !== 0)  && $this->error('订单错误');
        try{
            $order->status = 1;
            $save = $order->save();
        }catch (\Exception $e){
            $this->error('订单错误');
        }
        if ($save) {
            event('OrderPay',$order_id);
            $this->success('收款成功', ['status'=>Order::STATUS_ARRAY[$order->status]]);
        }else{
            $this->error('订单错误') ;
        }
        // $save ? $this->success('收款成功', ['status'=>Order::STATUS_ARRAY[$order->status]]) : $this->error('订单错误') ;
    }

    public function send_goods(){
        $post = $this->request->post();
        $order_id = isset($post['order_id'])? $post['order_id'] : '';
        $order = Order::where('id', $order_id)->find();
        (empty($order) || $order->status !== 1)  && $this->error('订单错误');
        try{
            $order->status = 2;
            $order->send_time = time();
            $save = $order->save();
        }catch (\Exception $e){
            $this->error('订单错误');
        }
        if ($save) {
            event('OrderDeliver',$order_id);
            $this->success('发货成功', ['status'=>Order::STATUS_ARRAY[$order->status]]);
        }else{
            $this->error('订单错误') ;
        }
        // $save ? $this->success('发货成功', ['status'=>Order::STATUS_ARRAY[$order->status]]) : $this->error('订单错误') ;
    }

    public function receive_goods(){
        $post = $this->request->post();
        $order_id = isset($post['order_id'])? $post['order_id'] : '';
        $order = Order::where('id', $order_id)->find();
        (empty($order) || $order->status !== 2)  && $this->error('订单错误');
        try{
            $order->status = 3;
            $save = $order->save();
        }catch (\Exception $e){
            $this->error('订单错误');
        }
        if ($save) {
            event('OrderReceiving',$order_id);
            $this->success('收货成功', ['status'=>Order::STATUS_ARRAY[$order->status]]);
        }else{
            $this->error('订单错误') ;
        }
        // $save ? $this->success('收货成功', ['status'=>Order::STATUS_ARRAY[$order->status]]) : $this->error('订单错误') ;
    }

    public function apply_cancel(){
        $post = $this->request->post();
        $order_id = isset($post['order_id'])? $post['order_id'] : '';
        $order = Order::where('id', $order_id)->find();
        (empty($order) || $order->status !== 1)  && $this->error('订单错误');
        Db::startTrans();
        try{
            $data = [
              'uid' => $order->uid,
              'order_id' => $order->id,
              'price' => $order->price,
              'order_sn' => $order->order_sn,
              'status' => 0,
            ];
            $save_refund = $order->order_refund()->save($data);
            $order->status = -2;
            $save = $order->save();
            if ($save === false || $save_refund === false){
//                    $this->success('支付成功');
                throw new \Exception('添加失败');
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            $this->error('订单错误');
        }
//        $save ? $this->success('申请成功', ['status'=>Order::STATUS_ARRAY[$order->status]]) : $this->error('订单错误') ;
//        if ($save_refund && $save){
//            Db::commit();
//            event('OrderRefund',$order_id);
//            $this->success('申请成功', ['status'=>Order::STATUS_ARRAY[$order->status]]);
//        }
        event('OrderRefund',$order_id);
        $this->success('申请成功', ['status'=>Order::STATUS_ARRAY[$order->status]]);
//        $this->error('订单错误');
    }

    // 批量发货
    public function batch_delivery_data()
    {
//        $file = $this->request->file();
        $files = $this->request->file();
        $file = $files['file'];
        $exp = new \app\common\Excel();
//        print_r($file->getPath().'\\'.$file->getFilename());die();
        $content = $exp->import($file->getPath() . '\\' . $file->getFilename(), $file->getOriginalExtension());
        unset($content[0]);
        $msg = '成功';
        Db::startTrans();
        try {
            foreach ($content as $item) {
                $order = $this->model::where('order_sn', $item[0])->where('status', '=', 1)
                    ->select()->first();
                !empty($order) && $order->save(['status' => 2, 'express_sn' => json_encode([$item[1]]),
                    'express_company_name' => $item[2], 'express_code' => $item[3],
                    ]);
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $msg = '批量发货失败, 请检查格式以及订单号是否正确, 且订单已付款';
        }
        return json(['code' => 0, 'msg' => $msg, 'data' => []]);
    }


}
