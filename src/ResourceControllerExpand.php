<?php

namespace Ckryo\Laravel\Expand;

use Ckryo\Laravel\Auth\Auth;
use Ckryo\Laravel\Logi\Facades\Logi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait ResourceControllerExpand
{
    /**
     * 授权用户类型
     * @return string
     */
    abstract function authKey ();


    /**
     * 获取当前资源控制器关联的数据库模型
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract function resourceModel ();

    /**
     * 获取模型名称的key
     * @return string
     */
    abstract function resourceModelNameKey ();

    /**
     * 获取模型主键
     * @return string
     */
    protected function resourceModelPrimaryKey () {
        return 'id';
    }


    /**
     * 获取模型、行为 对应的名称
     * @return string
     */
    abstract function resourceName ();

    /**
     * 获取模型 对应的中文名称
     * @return string
     */
    abstract function resourceDescription ();

    // 资源控制器 - 查询,显示,编辑,删除
    // 删除 , 删除单个, 删除多个


    protected function getDestroyMessageWithDatas (Collection $sql) {
        return '删除了'.$sql->count().'条'.$this->resourceDescription().'数据';
    }

    protected function getDestroyMessageWithSinge (Collection $data) {
        $key = $this->resourceModelNameKey();
        return '删除了'.$this->resourceDescription().':'.$data->$key;
    }


//    protected function access_logi () {
//        Logi::action($admin->id, 'admin_role', $data->id, 'delete', $this->getDestroyMessageWithSinge($data), json_encode($data->toArray(), JSON_UNESCAPED_UNICODE));
//    }

    /**
     * 行为操作记录
     * @param string $action 行为名称: create,update,delete
     * @param int $admin_id 管理员ID,操作人员信息
     * @param int $union_id 关联数据ID
     * @param string $errMsg 错误信息
     * @param string $data_str 需要保存的数据,json字符串
     */
    protected function logi ($action, $admin_id, $union_id, $errMsg, $data_str) {
        Logi::action($admin_id, $this->resourceName(), $union_id, $action, $errMsg, $data_str);
    }


    function destroy (Auth $auth, $id_str) {
        $items = explode('|', $id_str);
        DB::transaction(function () use ($items, $auth) {
            $admin = $auth->user($this->authKey());
            $model = $this->resourceModel();
            $model_primaryKey = $this->resourceModelPrimaryKey();
            $sql = $model->whereIn($model_primaryKey, $items);
            $data = $sql->get();
            if (count($data) > 1) {
                $this->logi('deletes', $admin->id, 0, $this->getDestroyMessageWithDatas($data), json_encode($data->toArray(), JSON_UNESCAPED_UNICODE));
            } elseif (count($data) === 1) {
                $item = $data->first();
                $this->logi('delete', $admin->id, $item->$model_primaryKey, $this->getDestroyMessageWithSinge($item), json_encode($item->toArray(), JSON_UNESCAPED_UNICODE));
            }
            $sql->delete();
        });
        return response()->ok('操作成功');
    }

}