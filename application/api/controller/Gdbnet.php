<?php
namespace app\api\controller;
use think\Log;
use think\Request;
use think\Db;

class Gdbnet 
{	
	protected $header_arr = [
			  		'version'	=>	'1.0',
					'encoding'	=>	'UTF-8'
				];
	protected $option_arr = [
		'root_node'	=>	'response',
		'root_attr'	=>	[
							' xmlns:ns2'	=>	'http://189itv.com/zxttms/public/api/gdbnet/operrequest'
						]
	];

	// 查单接口
	public function operrequest()
	{

		$params = Request::instance()->param();
		if (!isset($params['operId']) || !isset($params['timeStamp']) || !isset($params['hashcode'])) {
			$operIdparams = isset($params['operId'])?$params['operId']:'null';
			$hashvalidator_outputdata = [
				'operId'	=>	$operIdparams,
				'result'	=>	'22004',
				'hashcode'	=>	strtoupper(md5($operIdparams.'22004'.config('SHARE_KEY')))
			];
			return xml($hashvalidator_outputdata, 200, $this->header_arr, $this->option_arr);
		}
		// 校验哈希码
		if(!$this->md5_operrequest($params['operId'], $params['timeStamp'], $params['hashcode']))
		{
			$hashvalidator_outputdata = [
				'operId'	=>	$params['operId'],
				'result'	=>	'22003',
				'hashcode'	=>	strtoupper(md5($params['operId'].'22003'.config('SHARE_KEY')))
			];
			return xml($hashvalidator_outputdata, 200, $this->header_arr, $this->option_arr);
		}
		// 查询领航平台数据
		$nowtime = date("Y-m-d H:i:s");
		$nowhashcode = strtoupper(md5('gdbnet.queryOper'.$params['operId'].config('APP_SYSTEM_ID').$nowtime.config('SHARE_KEY')));
		$nowtime = rawurlencode($nowtime);//url转义
		$url_queryoper = config('Bnet_URL').'?servName=gdbnet.queryOper&operId='.$params['operId'].'&appSystemId='.config('APP_SYSTEM_ID').'&timeStamp='.$nowtime.'&hashcode='.$nowhashcode;
		$res = $this->https_request($url_queryoper);
		// xml转数组
		libxml_disable_entity_loader(true);
        $returndata = json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        // 数据存储
        switch ($returndata['operPublicInfo']['operType']) {
        	// 产品订购
        	case 'newProd':
		        $addData = $this->queryoper_newProdprocess($returndata);
        		break;
        	// 停复机
        	case 'modifyProdState':
        		$addData = $this->queryoper_modifyProdStateprocess($returndata);
        		break;
        	// 变更
        	case 'modifyProdAttribute':
        		$addData = $this->queryoper_modifyProdAttributeprocess($returndata);
        		break;
        	// 拆机
        	case 'cancelProd':
        		$addData = $this->queryoper_cancelProdprocess($returndata);
        		break;
        	default:
        		$addData = false;
        		break;
        }
        if (!$addData) {
        	$hashvalidator_outputdata = [
				'operId'	=>	$params['operId'],
				'result'	=>	'22007',
				'hashcode'	=>	strtoupper(md5($params['operId'].'22007'.config('SHARE_KEY')))
			];
			return xml($hashvalidator_outputdata, 200, $this->header_arr, $this->option_arr);
        }

        try
        {
        	Db::startTrans();
            if ($addData['oper_type'] == 'modifyProdState') { //停复机状态 直接回单
            	$addData['reply_status'] = 1;
            }

	        // 不同类型工单列表处理形式不一致
	        switch ($returndata['operPublicInfo']['operType']) {
		        // 产品订购 (新增)
		        case 'newProd':
			        Db::name('work_inf')->insert($addData);
			        break;
		        // 停复机 变更 拆机 (更新)
		        default:
			        Db::name('work_inf')->where('accNbr', $addData['accNbr'])->update($addData);
			        break;
	        }
	        // 工单流水插入数据
            Db::name('work_seq')->insert($addData);

        	$success_outputdata = [
				'operId'	=>	$addData['oper_id'],
				'result'	=>	'20000',
				'resultMessage'	=>	'success',
				'hashcode'	=>	strtoupper(md5($addData['oper_id'].'20000'.config('SHARE_KEY')))
			];
			Db::commit();

			if ($addData['oper_type'] == 'modifyProdState') { //停复机状态 调用回单接口
				$nowtime = date("Y-m-d H:i:s");
				$nowhashcode = strtoupper(md5('gdbnet.replyOper'.$addData['oper_id'].config('APP_SYSTEM_ID').'20000'.$nowtime.config('SHARE_KEY')));
				$nowtime = rawurlencode($nowtime);//url转义
				$url_replyoper = config('Bnet_URL').'?servName=gdbnet.replyOper&operId='.$addData['oper_id'].'&appSystemId='.config('APP_SYSTEM_ID').'&result=20000&timeStamp='.$nowtime.'&hashcode='.$nowhashcode;
				$this->https_request($url_replyoper);
			}

            return xml($success_outputdata, 200, $this->header_arr, $this->option_arr);
           
        }
        catch (\think\exception\PDOException $e)
        {
        	$error_outputdata = [
				'operId'	=>	$addData['oper_id'],
				'result'	=>	'21000',
				'hashcode'	=>	strtoupper(md5($addData['oper_id'].'21000'.config('SHARE_KEY')))
			];
	        Log::write('领航请求工单插入有误,工单operid为'.$addData['oper_id']);
			Db::rollback();
            return xml($error_outputdata, 200, $this->header_arr, $this->option_arr);
        }	
	}

	// 回单接口
	public function replyoper($operId){
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

	// 查询工单 md5校验
	private function md5_operrequest($operId, $timeStamp, $hashcode)
	{
		$md5hashcode = strtoupper(md5($operId.$timeStamp.config('SHARE_KEY')));
		if ($md5hashcode == trim($hashcode)) {
			return true;
		}else{
			return false;
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

	// 查询工单产品订购数据处理
	private function queryoper_newProdprocess($returndata)
	{	
		if ($returndata['operPublicInfo']['result'] == 10000) {
			$data['oper_id'] = $returndata['operPublicInfo']['operId'];
			$data['timestamp'] = time();
			$data['oper_type'] = $returndata['operPublicInfo']['operType'];
			$data['product_id'] = $returndata['operPublicInfo']['productId'];
			$data['BNet_Account'] = $returndata['bnetAccountInfo']['bnetAccount'];
			$data['accNbr'] = $returndata['productInfo']['productAttribute']['14']['attributeValue'];
			$data['cust_code'] = $returndata['productInfo']['productAttribute']['0']['attributeValue'];
			$data['contract_id'] = $returndata['productInfo']['productAttribute']['1']['attributeValue'];
			$data['cust_city_id'] = $returndata['productInfo']['productAttribute']['2']['attributeValue'];
			$data['cust_install_addr'] = $returndata['productInfo']['productAttribute']['3']['attributeValue'];
			$data['cust_name'] = $returndata['productInfo']['productAttribute']['4']['attributeValue'];
			$data['cust_phone'] = $returndata['productInfo']['productAttribute']['5']['attributeValue'];
			$data['contract_valid_date'] = $returndata['productInfo']['productAttribute']['6']['attributeValue'];
			$data['installer_name'] = $returndata['productInfo']['productAttribute']['7']['attributeValue'];
			$data['installer_phone'] = $returndata['productInfo']['productAttribute']['8']['attributeValue'];
			$data['product_mix'] = $returndata['productInfo']['productAttribute']['9']['attributeValue'];
			$data['pay_grade'] = $returndata['productInfo']['productAttribute']['10']['attributeValue'];
			$data['iTV_option'] = $returndata['productInfo']['productAttribute']['11']['attributeValue'];
			$data['eTV_license_count'] = $returndata['productInfo']['productAttribute']['12']['attributeValue'];
			$data['iTV_count'] = $returndata['productInfo']['productAttribute']['13']['attributeValue'];
			$data['custom_fee'] = $returndata['productInfo']['productAttribute']['15']['attributeValue'];
			$data['reply_status'] = 0; //回单状态
			$data['hashcode'] = $returndata['operPublicInfo']['hashcode'];
			$data['query_status'] = 1;
		}else{
			$data['oper_id'] = $returndata['operPublicInfo']['operId'];
			$data['timestamp'] = time();
			$data['oper_type'] = $returndata['operPublicInfo']['operType'];
			$data['product_id'] = $returndata['operPublicInfo']['productId'];
			$data['query_status'] = 0;
			$data['reply_status'] = 2; //回单状态
		}
		return $data;
	}

	// 查询工单产品停复机数据处理
	private function queryoper_modifyProdStateprocess($returndata){
		if($returndata['operPublicInfo']['productId']){
			$vo = Db::name('work_seq')->where('product_id="'.$returndata['operPublicInfo']['productId'].'"')->order('timestamp desc,complete_time desc')->find();
			if ($vo) {
				$vo['oper_id'] = $returndata['operPublicInfo']['operId'];
				$vo['oper_type'] = $returndata['operPublicInfo']['operType'];
				$vo['hashcode'] = $returndata['operPublicInfo']['hashcode'];
				$vo['timestamp'] = time();
				unset($vo['id']);
				return $vo;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	// 查询工单产品变更数据处理
	public function queryoper_modifyProdAttributeprocess($returndata){
		if ($returndata['operPublicInfo']['result'] == 10000) {
			$data['oper_id'] = $returndata['operPublicInfo']['operId'];
			$data['timestamp'] = time();
			$data['oper_type'] = $returndata['operPublicInfo']['operType'];
			$data['product_id'] = $returndata['operPublicInfo']['productId'];
			// $data['BNet_Account'] = $returndata['bnetAccountInfo']['bnetAccount'];
			$data['accNbr'] = $returndata['productInfo']['productAttribute']['14']['attributeValue'];
			$data['cust_code'] = $returndata['productInfo']['productAttribute']['0']['attributeValue'];
			$data['contract_id'] = $returndata['productInfo']['productAttribute']['1']['attributeValue'];
			$data['cust_city_id'] = $returndata['productInfo']['productAttribute']['2']['attributeValue'];
			$data['cust_install_addr'] = $returndata['productInfo']['productAttribute']['3']['attributeValue'];
			// $data['cust_name'] = $returndata['productInfo']['productAttribute']['4']['attributeValue'];
			$data['cust_phone'] = $returndata['productInfo']['productAttribute']['5']['attributeValue'];
			$data['contract_valid_date'] = $returndata['productInfo']['productAttribute']['6']['attributeValue'];
			$data['installer_name'] = $returndata['productInfo']['productAttribute']['7']['attributeValue'];
			$data['installer_phone'] = $returndata['productInfo']['productAttribute']['8']['attributeValue'];
			$data['product_mix'] = $returndata['productInfo']['productAttribute']['9']['attributeValue'];
			$data['pay_grade'] = $returndata['productInfo']['productAttribute']['10']['attributeValue'];
			$data['iTV_option'] = $returndata['productInfo']['productAttribute']['11']['attributeValue'];
			$data['eTV_license_count'] = $returndata['productInfo']['productAttribute']['12']['attributeValue'];
			$data['iTV_count'] = $returndata['productInfo']['productAttribute']['13']['attributeValue'];
			if (isset($returndata['productInfo']['productAttribute']['15']['attributeValue'])) {
				$data['custom_fee'] = $returndata['productInfo']['productAttribute']['15']['attributeValue'];
			}
			$data['reply_status'] = 0; //回单状态
			$data['hashcode'] = $returndata['operPublicInfo']['hashcode'];
			$data['query_status'] = 1;
			$vo = Db::name('work_seq')->where('product_id="'.$returndata['operPublicInfo']['productId'].'"')->field('cust_name,BNet_Account')->order('timestamp desc')->find();
			$data['cust_name'] = isset($vo['cust_name'])?$vo['cust_name']:'';
			$data['BNet_Account'] = isset($vo['BNet_Account'])?$vo['BNet_Account']:'';
		}else{
			$data['oper_id'] = $returndata['operPublicInfo']['operId'];
			$data['timestamp'] = time();
			$data['oper_type'] = $returndata['operPublicInfo']['operType'];
			$data['product_id'] = $returndata['operPublicInfo']['productId'];
			$data['query_status'] = 0;
			$data['reply_status'] = 2; //回单状态 失败
		}
		return $data;
	}

	// 查询工单产品拆机数据处理
	public function queryoper_cancelProdprocess($returndata){
		if($returndata['operPublicInfo']['productId']){
			$vo = Db::name('work_seq')->where('product_id="'.$returndata['operPublicInfo']['productId'].'"')->order('timestamp desc,complete_time desc')->find();
			if ($vo) {
				$vo['oper_id'] = $returndata['operPublicInfo']['operId'];
				$vo['oper_type'] = $returndata['operPublicInfo']['operType'];
				$vo['reply_status'] = 0;
				$vo['hashcode'] = $returndata['operPublicInfo']['hashcode'];
				$vo['timestamp'] = time();
				unset($vo['id']);
				return $vo;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}