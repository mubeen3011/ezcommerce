<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 7/21/2020
 * Time: 11:31 AM
 */
namespace backend\util;
use backend\controllers\WarehouseController;
use common\models\Products;
use common\models\Settings;
use common\models\WarehouseStockList;
use Yii;

class SplWarehouseUtil
{

    private static $current_warehouse=NULL;
    private static $current_channel=NULL;
    private static $sql_db=[
                        'magento'=>['db'=>'db2','table'=>'SPL_Stock_View'],
                        'prestashop'=>['db'=>'db3','table'=>'OL_PEDRO_AVL_STOCK'],
                        ];

    /*private static function create_connection()
    {
        $connection= yii::$app->db2; // connection with second database

    }*/

    public static function getWarehouseStock($warehouse=null,$channel)
    {
        if(!$warehouse)
            return ['error_in_adding'=>'cron controller missed warehouse param','error_in_updating'=>''];

        self::$current_warehouse=$warehouse;
        self::$current_channel=$channel;
        //echo "<pre>";
       // print_r($warehouse); die();
        $center_id=json_decode(self::$current_warehouse->configuration);
        $warehouse_center_id=isset($center_id->center_id) ? $center_id->center_id:NULL;
        if(!$warehouse_center_id)
            return ['error_in_adding'=>'database settings fail','error_in_updating'=>''];

        try{

                //$connection= yii::$app->{self::$sql_db[$channel->marketplace]['db']}; // connection with second database
                $connection= yii::$app->db2; // connection with second database
               // $sql="SELECT BarCode,ExStock FROM ".self::$sql_db[$channel->marketplace]['table']." WHERE FKCostCentreID='".$warehouse_center_id."'";
                $sql="SELECT BarCode,ExStock FROM SPL_Stock_View WHERE FKCostCentreID='".$warehouse_center_id."'";
               // echo $sql; die();
                $command = $connection->createCommand($sql);

                $result = $command->queryAll();
                //$connection->close();
            } catch(\Exception $e) {
            return ['error_in_adding'=>$e->getMessage(),'error_in_updating'=>''];
        }
        if($result){
          /*  echo "<pre>";
            print_r($result);
            die('come');*/
            return  self::saveStock($result,true);
        }
         else{
            //self::set_whole_warehouse_stock_zero(self::$current_warehouse->id); // set whole warehouse stock to zero if data against that warehouse not found
             return ['error_in_adding'=>'error in fetching | no data fetched','error_in_updating'=>'error in fetching | no data fetched'];
         }



    }

    /***********set whole warehouse stock to zero
    *****/
    private static function set_whole_warehouse_stock_zero($warehouse_id)
    {
        $sql="UPDATE `warehouse_stock_list` SET 
              `available`=0 ,
              `updated_at`='".date('Y-m-d H:i:s')."'
                where
                  `available`<>0 AND `warehouse_id`='".$warehouse_id."'";
        $connection = Yii::$app->db;
        $connection->createCommand($sql)->execute();
        return;
    }

    private static function saveStock($data=NULL,$set_zero_stock=false)
    {
        $warehouse_id=self::$current_warehouse->id;
        $updateToskus=[];
        if($data)
        {
            $response = [];

            foreach ($data as $value ){
                $sku=Products::find()->select('sku')->where(['barcode'=>$value['BarCode']])->orderBy(['id' => SORT_DESC])->scalar();
                if(empty($sku))
                    continue;

                $updateToskus[]=$sku;
                $findProduct = WarehouseStockList::findone(['sku'=>$sku,'warehouse_id'=>$warehouse_id]);

                if (!$findProduct){
                    $addInWh = new WarehouseStockList();
                    $addInWh->warehouse_id = $warehouse_id;
                    $addInWh->sku = $sku;
                    $addInWh->available = $value['ExStock'];
                    $addInWh->added_at = date('Y-m-d H:i:s');
                    $addInWh->updated_at = date('Y-m-d H:i:s');
                    $addInWh->save();

                   if (empty($addInWh->errors)){}
                        //$response['added'][]=['sku'=>$sku,'stock'=> $value['ExStock'],'status'=>'stock added'];
                    else
                        $response['error_in_adding'][]=['sku'=>$sku,'stock'=> $value['ExStock'],'status'=>$addInWh->errors];


                }else if($findProduct->available !=$value['ExStock']){ // if already have stock change than incoming then update
                         $findProduct->available =  $value['ExStock'];
                         $findProduct->updated_at = date('Y-m-d H:i:s');
                         $findProduct->update();
                       if (empty($findProduct->errors)){}
                          //  $response['updated'][]=['sku'=>$sku,'stock'=> $value['ExStock'],'status'=>'updated'];
                        else
                            $response['error_in_update'][]=['sku'=>$sku,'stock'=> $value['ExStock'],'status'=>$findProduct->errors];
                } else{
                   // $response['unchanged'][]=['sku'=>$sku,'stock'=> $value['ExStock'],'status'=>'server_local_same_stock'];
                }


            } // foreach end

            if($set_zero_stock){ // update if need
               WarehouseController::SetZeroStock($warehouse_id,$updateToskus);// Update skus to 0 which api don't giving the data to update.
            }

            return $response;
        }
    }

    /**
     * in pagination the stock of warehouse will be fetched
     */
    public static function pagination_for_stock_fetching($channel)
    {
        $pagination=Settings::findone(['name'=>'magento_warehouse_stock_fetch_pagination_'.$channel->id]);  // call stock by pagination to avoid load
        if(!$pagination){ // if not already exists make
            $value=[
                'total_warehouses'=>\common\models\WarehouseChannels:: find()->where(['channel_id'=>$channel->id,'is_active'=>1])->count(),
                'next_offset'=>0,
            ];
            $settings=new Settings();
            $settings->name='magento_warehouse_stock_fetch_pagination_'.$channel->id;
            $settings->value=json_encode($value);
            $settings->save();
            return ['offset'=>$value['next_offset']];
        }
        $page=json_decode($pagination->value);
        return ['offset'=>$page->next_offset];
    }
    /**
     * update pagination for stock fetching from warehouse
     */
    public static function update_pagination_for_stock_fetching($channel,$add_offset=5)
    {
        $pagination=Settings::findone(['name'=>'magento_warehouse_stock_fetch_pagination_'.$channel->id]);  // call stock by pagination to avoid load
        if(!$pagination){ // if not already exists make
            $value=[
                'total_warehouses'=>\common\models\WarehouseChannels:: find()->where(['channel_id'=>$channel->id,'is_active'=>1])->count(),
                'next_offset'=>$add_offset,
            ];
            $settings=new Settings();
            $settings->name='magento_warehouse_stock_fetch_pagination_'.$channel->id;
            $settings->value=json_encode($value);
            $settings->save();
            return ;
        }
        $page=json_decode($pagination->value);
        $value=[
            'total_warehouses'=>\common\models\WarehouseChannels:: find()->where(['channel_id'=>$channel->id,'is_active'=>1])->count(),
            'next_offset'=>($page->next_offset + $add_offset),
        ];
        if($value['next_offset'] >= $value['total_warehouses'])
            $value['next_offset']=0;
        $pagination->value=json_encode($value);
        $pagination->update();
        return;
    }
}