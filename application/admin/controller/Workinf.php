<?php

namespace app\admin\controller;

use app\common\controller\Backend;

use think\Controller;
use think\Request;
use think\Db;
use PHPEXCEL;
use PHPExcel_IOFactory;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Workinf extends Backend
{
    
    /**
     * WorkInf模型对象
     */
    protected $model = null;
    protected $searchFields = 'oper_id';
    protected $where_condition = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('WorkInf');
        $groupinfo = $this->auth->getGroups();//获取筛选condition
        $this->where_condition = json_decode($groupinfo['0']['condition'],true);
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个方法
     * 因此在当前控制器中可不用编写增删改查的代码,如果需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                     ->where($this->where_condition)
                    ->order($sort, $order)
                    ->count();

            $list = $this->model
                    ->where($where)
                     ->where($this->where_condition)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $allowField = "setup_person_name,setup_person_phone,mac";
                    $result = $row->allowField($allowField)->save($params);
                    if ($result !== false)
                    {
                        $this->success();
                    }
                    else
                    {
                        $this->error($row->getError());
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['product_mix_name'] = common_convert_product_mix($row['product_mix']);
        $row['pay_grade_name'] = common_convert_pay_grade($row['pay_grade']);
        $row['iTV_option_name'] = common_convert_itvoption($row['iTV_option']);
        $row['reply_status_name'] = common_convert_replystatusname($row['reply_status']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

	/**
     * 详情
     * @param $ids
     * @return result
     */
    public function detail($ids){
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        $row['product_mix_name'] = common_convert_product_mix($row['product_mix']);
        $row['pay_grade_name'] = common_convert_pay_grade($row['pay_grade']);
        $row['iTV_option_name'] = common_convert_itvoption($row['iTV_option']);
        $row['reply_status_name'] = common_convert_replystatusname($row['reply_status']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function excelout(){
        set_time_limit(600);
        // 查询列名
        $column = Db::name('work_inf')->query('SHOW COLUMNS from gdbnet_work_inf');
        $PHPExcel = new \PHPExcel(); //实例化PHPExcel类，类似于在桌面上新建一个Excel表格
        $PHPSheet = $PHPExcel->getActiveSheet(0); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle('工单列表'); //给当前活动sheet设置名称

        $CachedObject = new \PHPExcel_CachedObjectStorageFactory();
        $Settings = new \PHPExcel_Settings();
        $cacheMethod = $CachedObject::cache_in_memory_gzip;
        $cacheSettings = array( ' memoryCacheSize ' => '30MB' );
        $Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        // 写入内容数据
        $key = ord("A");//A--65
        $key2 = ord("@");//@--64
        foreach($column as $v){
            if($key>ord("Z")){
                $key2 += 1;
                $key = ord("A");
                $colum = chr($key2).chr($key);//超过26个字母时才会启用
            }else{
                if($key2>=ord("A")){
                    $colum = chr($key2).chr($key);//超过26个字母时才会启用
                }else{
                    $colum = chr($key);
                }
            }
            $PHPSheet->setCellValue($colum.'1', __($v['Field']));
            $key += 1;
        }

        $params = $this->request->post();
        // 查询数据处理
        $where = [];
        foreach($params as $key => $value){
            if(!empty($value)){
                $where[$key] = $value;
            }
        }
        if(!empty($params['complete_time'])){
            $where['complete_time'] = array('between',[strtotime($params['daterangepicker_start']),strtotime($params['daterangepicker_end'])]);
            unset($where['daterangepicker_start']);
            unset($where['daterangepicker_end']);
        }

        // 查询列表
        $allcount = Db::name('work_inf')->where($where)->count();
        $pagelimit = 1000;

        if($allcount>$pagelimit){
            $startlimit = 0;
            $selectcount = floor($allcount/$pagelimit);
            for($startselectcount = 0; $startselectcount<=$selectcount;$startselectcount++){
                $list = Db::name('work_inf')->where($where)->limit($startlimit,$pagelimit)->select();
                foreach($list as $k=>$value){
                    $key = ord("A");//A--65
                    $key2 = ord("@");//@--64
                    foreach($value as $vv){
                        if($key>ord("Z")){
                            $key2 += 1;
                            $key = ord("A");
                            $colum = chr($key2).chr($key);//超过26个字母时才会启用
                        }else{
                            if($key2>=ord("A")){
                                $colum = chr($key2).chr($key);//超过26个字母时才会启用
                            }else{
                                $colum = chr($key);
                            }
                        }
                        $PHPSheet->setCellValue($colum.($startlimit+$k+2), $vv);
                        $key += 1;
                    }
                }
                $startlimit = $startlimit + $pagelimit;
            }
        }else{
            $list = Db::name('work_inf')->where($where)->select();
            foreach($list as $k=>$value){
                $key = ord("A");//A--65
                $key2 = ord("@");//@--64
                foreach($value as $vv){
                    if($key>ord("Z")){
                        $key2 += 1;
                        $key = ord("A");
                        $colum = chr($key2).chr($key);//超过26个字母时才会启用
                    }else{
                        if($key2>=ord("A")){
                            $colum = chr($key2).chr($key);//超过26个字母时才会启用
                        }else{
                            $colum = chr($key);
                        }
                    }
                    $PHPSheet->setCellValue($colum.($k+2), $vv);
                    $key += 1;
                }
            }
        }
        $fileName = time().rand(100,999);//导出excal 文件名称
        $xlsTitle = iconv('utf-8', 'gb2312', '订单列表');//文件名称
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xlsx"');
        header("Content-Disposition:attachment;filename=$fileName.xlsx");//attachment新窗口打印inline本窗口打印
        header('Cache-Control: max-age=0');
//        $objWriter = PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel5');
//        $objWriter->save('php://output');

        $objWriter = new \PHPExcel_Writer_Excel2007($PHPExcel);
        $result = $this->saveExcelToLocalFile($objWriter, $fileName);
        if($result){
            echo 1;
        }else{
            echo 0;
        }
        die();
        $response = array(
            'success' => true,
            'url' => $this->saveExcelToLocalFile($objWriter, $fileName)
        );
        if ($response) {
            echo json_encode($response);
        }else{
            $return_arr = [
                'success' => false,
            ];
            echo json_encode($return_arr);
        }
    }

    // excel保存并返回导出地址
    function saveExcelToLocalFile($objWriter,$filename){
        if(!file_exists(dirname(dirname(dirname(dirname(__FILE__)))).'/Public/excel/')){
            mkdir(dirname(dirname(dirname(dirname(__FILE__)))).'/Public/excel/',0777,true);
        }
        $filePath = dirname(dirname(dirname(dirname(__FILE__)))).'/Public/excel/'.$filename.'.xlsx';
        $objWriter->save($filePath);
        return true;
        $outfilePath = config('excel_path').$filename.'.xlsx';
        return $outfilePath;
    }

    // 回单接口
    public function replyoper($ids){
        $operId_arr = Db::name('work_inf')->where('id',$ids)->field('oper_id')->find();
        if (empty($operId_arr)) {
            return json(['code'=>0, 'message'=>'获取operId失败']);
        }else{
            $operId = $operId_arr['oper_id'];
        }

        // 回单调用
        $nowtime = date("Y-m-d H:i:s");
        $nowhashcode = strtoupper(md5('gdbnet.replyOper'.$operId.config('APP_SYSTEM_ID').'20000'.$nowtime.config('SHARE_KEY')));
        $nowtime = rawurlencode($nowtime);//url转义
        $url_replyoper = config('Bnet_URL').'?servName=gdbnet.replyOper&operId='.$operId.'&appSystemId='.config('APP_SYSTEM_ID').'&result=20000&timeStamp='.$nowtime.'&hashcode='.$nowhashcode;
        $res = $this->https_request($url_replyoper);
        // xml转数组
        libxml_disable_entity_loader(true);
        $returndata = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        if (isset($returndata['operPublicInfo']) && $returndata['operPublicInfo']['result'] == 10000) {
            // 回单数据处理入库
            $addData = Db::name('work_inf')->where('oper_id="'.$operId.'"')->find();
            unset($addData['id']);
            $addData['hashcode'] = $nowhashcode;
            $addData['complete_time'] = time();
            $addData['reply_status'] = 1;
            try {
                Db::startTrans();
                Db::name('work_inf')->where('oper_id',$operId)->update(['reply_status'=>1, 'complete_time'=>$addData['complete_time']]);
                Db::name('work_seq')->insert($addData);
            } catch (\think\exception\PDOException $e) {
                Db::rollback();
                Log::write('回单有误,工单operid为'.$operId);
                return json(['code'=>0, 'message'=>'回单失败']);
            }
            Db::commit();
            return json(['code'=>200, 'message'=>'回单成功']);
        }else{
            return json(['code'=>0, 'message'=>'连接平台回单失败']);
        }
    }

    // curl请求
    private function https_request($url, $data=null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}
