<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 1/18/2021
 * Time: 3:46 PM
 */

namespace backend\util;


use common\models\EzcomToWarehouseProductSync;
use common\models\EzcomToWarehouseSync;
use common\models\GeneralLog;

class LogUtil
{
        public static function add_log($data=[])
        {
               if($data)
               {
                    $log=new GeneralLog();
                    $log->type=$data['type'];
                    $log->entity_type=(isset($data['entity_type']) && $data['entity_type']) ? $data['entity_type']:NULL;
                    $log->entity_id=(isset($data['entity_id']) && $data['entity_id']) ? $data['entity_id']:NULL;
                    $log->request=(isset($data['request']) && $data['request']) ? $data['request']:NULL;
                    $log->response=(isset($data['response']) && $data['response']) ? $data['response']:NULL;
                    $log->additional_info=(isset($data['additional_info']) && $data['additional_info']) ? $data['additional_info']:NULL;
                    $log->log_type=(isset($data['log_type']) && $data['log_type']) ? $data['log_type']:NULL;
                    $log->url=(isset($data['url']) && $data['url']) ? $data['url']:NULL;
                    $log->created_at=date('Y-m-d H:i:s');
                    $log->save(false);
               }
               return;
        }
    /***Orders/products synced to online warehouse from ezcommerce
     ***/
    public static function ezcomToWarehouseSyncedLog($data)
    {
        //EzcomToWarehouseSync::
        $log=new EzcomToWarehouseSync();
        $log->warehouse_id=isset($data['warehouse_id']) ? $data['warehouse_id']:NULL;
        $log->type=isset($data['type']) ? $data['type']:NULL;
        $log->ezcom_entity_id=isset($data['ezcom_entity_id']) ? $data['ezcom_entity_id']:NULL;
        $log->third_party_entity_id=isset($data['third_party_entity_id']) ? $data['third_party_entity_id']:NULL;
        $log->ezcom_status=isset($data['ezcom_status']) ? $data['ezcom_status']:NULL;
        $log->third_party_status=isset($data['third_party_status']) ? $data['third_party_status']:NULL;
        $log->comment=isset($data['comment']) ? $data['comment']:NULL;
        $log->created_at=date('Y-m-d H:i:s');
        $log->save(false);
        return;
    }

}