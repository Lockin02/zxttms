<?php

namespace app\admin\model;

use think\Model;

class WorkInf extends Model
{
    // 表名
    protected $name = 'work_inf';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'complete_time_text',
        'callback_time_text'
    ];
    

    



    public function getCompleteTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['complete_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCallbackTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['callback_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCompleteTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setCallbackTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
