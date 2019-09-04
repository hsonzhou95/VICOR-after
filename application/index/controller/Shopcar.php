<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Request;

class Shopcar extends Controller
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        checkJWT();
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
        $uid = $this->request->id;

        $cart = Db::table('cart')->where('uid',$uid)->find();
        if($cart){
            $goods = Db::table('cart_extra')->alias('c')
                ->field('c.num,c.gid,c.state,goods.gname,goods.ename,goods.gthumb,goods.gtype,goods.cid,goods.price')
                ->join('goods','c.gid=goods.gid')
                ->where('uid',$uid)->select();
            $cart['goods'] = $goods;

            return  json([
                'code'=>config('code.success'),
                'msg'=>'购物获取成功',
                'data'=>$cart
            ]);
        }else{
            return  json([
                'code'=>config('code.success'),
                'msg'=>'购物为空'
            ]);
        }

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
        $uid=$this->request->id;
        $data=$this->request->get();
        $gid=$data['gid'];
        $state=$data['state'];
        if($state==1&&!empty($state)){
            $state=0;
        }else{
            $state=1;
        }
        $result=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$gid])->update(['state'=>$state]);
        if($result){
            return json([
                'code'=>config('code.success'),
                'msg'=>'修改成功'
            ]);
        }else{
            return json([
                'code'=>config('code.fail'),
                'msg'=>'修改失败'
            ]);
        }
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data=$this->request->post();
        $uid=$this->request->id;
        $car=Db::table('cart')->where('uid',$uid)->find();
        //        如果购物车存在的情况
        if($car){
//            如果购物车存在,去购物车表二查一下这个商品
            Db::startTrans();
            $goodsArr=['uid'=>$uid,'gid'=>$data['gid']];
            $goods=Db::table('cart_extra')->where($goodsArr)->find();
//            如果这个商品存在的话
            if($goods){
                $result=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$data['gid']])
                    ->setInc('num');
            }else{
//                如果不存在
                $insertArr=['gid'=>$data['gid'],'num'=>1,'state'=>1,'sid'=>$car['sid'],'uid'=>$uid];
                $result=Db::table('cart_extra')->insert($insertArr);
            }
            $totalinsert=Db::table('cart')->where('uid',$uid)->setInc('total');
            $priceinsert=Db::table('cart')->where('uid',$uid)->setInc('price',$data['price']);
            if($result&&$totalinsert&&$priceinsert){
                Db::commit();
                return json([
                   'code'=>config('code.success'),
                   'msg'=>'加入购物车成功'
                ]);
            }else{
                Db::rollback();
                return json([
                    'code'=>config('code.success'),
                    'msg'=>'加入购物车失败'
                ]);
            }
        }else{
            //        如果购物车不存在的情况
//        购物车表一car(uid:,total:1,sprice:前台拿),
//购物车表二car_extra(gid:前台拿，num:1,state:1,sid:根据购物车表一来创建，uid，用户的id)
            Db::startTrans();
//            表一的参数
            $carCon=['uid'=>$uid,'total'=>1,'price'=>$data['price']];
//            插入到表一中
//            Db::table('cart')->insert($carCon);
            $cart=Db::table('cart')->insertGetId($carCon);
//            表二的参数
            $carinfo=['gid'=>$data['gid'],'num'=>1,'state'=>1,'sid'=>$cart,'uid'=>$uid];
//            插入到表二中
            $carExtra=Db::table('cart_extra')->insert($carinfo);
            if($cart && $carExtra){
                Db::commit();
                return json([
                    'code'=>config('code.success'),
                    'msg'=>'加入购物车成功'
                ]);
            }else{
                Db::rollback();
                return json([
                    'code'=>config('code.success'),
                    'msg'=>'加入购物车成功'
                ]);
            }
        }
        //






    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data=$this->request->put();
        $uid=$this->request->id;
        $gid=$data['gid'];
        $price=$data['price'];
        $type=$data['v'];
        Db::startTrans();
        if($type==='add'){
            $result=Db::table('cart')->where('uid',$uid)->setInc('total');
            $insertprice=Db::table('cart')->where('uid',$uid)->setInc('price',$price);
            $resultEx=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$gid])->setInc('num');
        }else{
            $result=Db::table('cart')->where('uid',$uid)->setDec('total');
            $insertprice=Db::table('cart')->where('uid',$uid)->setDec('price',$price);
            $resultEx=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$gid])->setDec('num');
            $cartdelete=Db::table('cart')->where('uid',$uid)->find();
            if($cartdelete['total']==0){
                $delete=Db::table('cart')->where('uid',$uid)->delete();
            }
            $extradelete=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$gid])->find();
            if($extradelete['num']==0){
                $extradelete=Db::table('cart_extra')->where(['uid'=>$uid,'gid'=>$gid])->delete();
            }
        }
        if($result&&$insertprice&&$resultEx){
            Db::commit();
            return json([
                'code'=>config('code.success'),
                'msg'=>''
            ]);
        }else{
            Db::rollback();
            return json([
                'code'=>config('code.fail'),
                'msg'=>''
            ]);
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
        $uid=$this->request->id;
        $result=Db::table('cart_extra')->where('uid',$uid)->update(['state'=>1]);
        if($result){
            Db::commit();
            return json([
                'code'=>config('code.success'),
                'msg'=>'全选成功'
            ]);
        }else{
            Db::rollback();
            return json([
                'code'=>config('code.fail'),
                'msg'=>''
            ]);
        }

    }
}