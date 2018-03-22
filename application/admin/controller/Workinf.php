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
        ini_set('max_execution_time', '0');

        $params = $this->request->post();
        foreach($params as $key => $value){
            if(!empty($value)){
                $where[$key] = $value;
            }
        }
        $where = [];
        if(!empty($params['complete_time'])){
            $where['complete_time'] = array('between',[strtotime($params['daterangepicker_start']),strtotime($params['daterangepicker_end'])]);
            unset($where['daterangepicker_start']);
            unset($where['daterangepicker_end']);
        }

        $field = "oper_id, timestamp, oper_type, product_id, BNet_Account, accNbr, cust_code, contract_id, cust_city_id, cust_install_addr, cust_name, cust_phone, contract_valid_date, installer_name, installer_phone, product_mix, pay_grade, iTV_option, eTV_license_count, iTV_count, custom_fee, reply_status, setup_person_name, setup_person_phone, mac, complete_time";
        $datalist = Db::name('work_inf')->where($where)->field($field)->select();
        $columns = [
            __('oper_id'),          //工单ID
            __('timestamp'),        //工单时间 时间戳转日期格式
            __('oper_type'),        //工单类型
            __('product_id'),       //客户产品号
            __('BNet_Account'),     //商务领航号
            __('accNbr'),           //eTV的接入号
            __('cust_code'),        //客户名称
            __('contract_id'),      //合同编号
            __('cust_city_id'),     //客户所属区域
            __('cust_install_addr'),//客户装机地址
            __('cust_name'),        //客户姓名
            __('cust_phone'),       //客户手机
            __('contract_valid_date'), //合同有效期
            __('installer_name'),   //揽装人姓名
            __('installer_phone'),  //揽装人电话
            __('product_mix'),      //产品组合
            __('pay_grade'),        //资费档次
            __('iTV_option'),       //iTV选项
            __('eTV_license_count'),//eTV的license数量
            __('iTV_count'),        //iTV数量
            __('custom_fee'),       //定制费
            __('reply_status'),     //回单状态
            __('setup_person_name'),//安装人姓名
            __('setup_person_phone'), //安装人电话
            __('mac'),              //MAC地址
            __('complete_time'),    //竣工时间
        ];
        $fileName = time().'.csv';
        $result = $this->create_csv($columns, $datalist, $fileName);
        if($result){
            $returndata['success'] = true;
            $returndata['url'] = $result;
        }else{
            $returndata['success'] = false;
        }
        echo json_encode($returndata);
    }

    // excel保存并返回导出地址
    function create_csv($columns, $data, $filename){

        if(!file_exists(dirname(dirname(dirname(dirname(__FILE__)))).'/public/excel/')){
            mkdir(dirname(dirname(dirname(dirname(__FILE__)))).'/public/excel/',0777,true);
        }
        $filePath = dirname(dirname(dirname(dirname(__FILE__)))).'/public/excel/'.$filename;

        $fp = fopen($filePath,'a');
        //输出Excel列名信息
        foreach ($columns as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }
        $headlist_str = implode(",", $headlist) . PHP_EOL;
        fwrite($fp, $headlist_str);
        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {

            $num++;

            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $data[$i];
            foreach ($row as $key => $value) {
                switch($key){
                    case 'oper_id':
                        $value = $value."\t";
                        break;
                    case 'cust_city_id':
                        $value = $value."\t";
                        break;
                    case 'timestamp':
                        $value = date("Y-m-d H:i:s", $value);
                        break;
                    case 'oper_type':
                        $value =  __($value);
                        break;
                    case 'product_mix':
                        if($value == 1){
                            $value = __('OneProduct');
                        }
                        break;
                    case 'pay_grade':
                        if($value == 1){
                            $value = '50元/月';
                        }elseif($value == 2){
                            $value = '100元/月';
                        }elseif($value == 3){
                            $value = '15元/月';
                        }elseif($value == 4){
                            $value = '30元/月';
                        }elseif($value == 5){
                            $value = '40元/月';
                        }else{
                            $value = '15元/月';
                        }
                        break;
                    case 'custom_fee':
                        if($value == 0){
                            $value = __('Zero a Month');
                        }elseif($value == 5){
                            $value = __('Five a Month');
                        }elseif($value == 10){
                            $value = __('Ten a Month');
                        }
                        break;
                    case 'reply_status':
                        if($value == 0){
                            $value = __('Noreceipt');
                        }elseif($value == 1){
                            $value = __('Hadreceipt');
                        }elseif($value == 2) {
                            $value = __('Receipterror');
                        }
                        break;
                }
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            $row_str = implode(",", $row) .PHP_EOL;
            fwrite($fp, $row_str);
        }

        $outfilePath = config('excel_path').$filename;
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

    public function checklogin(){
        $data['code'] = 200;
        $data['msg'] = '登陆中';
        echo json_encode($data);
    }
}
