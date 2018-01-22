<?php

namespace app\admin\controller;

use app\common\controller\Backend;

use think\Controller;
use think\Request;

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
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('pkey_name'))
            {
                return $this->selectpage();
            }
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
            $sql = $this->model->getlastsql();
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

}
