<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/15/2019
 * Time: 2:56 PM
 */
namespace backend\util;
use Codeception\Module\Yii1;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\DealsMaker;
use common\models\DistributorZipcodes;
use common\models\EzcomToWarehouseProductSync;
use common\models\OrdFulfilledByWhZipcodes;
use common\models\PoDetails;
use common\models\PoThresholds;
use common\models\ProductRelationsSkus;
use common\models\Products;
use common\models\ProductsRelations;
use common\models\StockDepletionLog;
use common\models\StocksPo;
use common\models\ThresholdSales;
use common\models\UserRoles;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use common\models\WarehouseStockLog;
use common\models\Zipcodes;
use Yii;

class WarehouseUtil {

    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    public static function AddLineItemPo($warehouseId, $SkuList){

        $sql = "SELECT p.id as sku_id, p.sku  FROM products p 
                INNER JOIN warehouse_stock_list wsl ON wsl.sku = p.sku
                INNER JOIN warehouses w ON w.id = wsl.warehouse_id
                WHERE p.is_active = 1 AND w.id = $warehouseId AND w.is_active = 1 AND p.parent_sku_id IS NULL
                ";
        if ($SkuList)
            $sql .= "AND p.id NOT IN (".implode(',',$SkuList).")";

        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function GetThresholds($warehouseId,$sku_id=[]){

        if ($sku_id){
            foreach ( $sku_id as $skuId ){
                $skuTxt=HelpUtil::exchange_values('id','sku',$skuId,'products');
                self::CreateThreshold($warehouseId,$skuId,$skuTxt);
            }
        }

        $Sql = "SELECT w.NAME AS warehouse_name,p.ean,p.cost AS cost_price,p.cost,p.sku,p.parent_sku_id,p.id AS sku_id, ts.`status`,
                CEIL(w.t1 * ts.threshold) AS t1, CEIL(w.t2 * ts.threshold) AS t2, CEIL(w.transit_days * ts.threshold) as transit_days_threshold
                FROM threshold_sales ts
                INNER JOIN products p ON p.id = ts.product_id
                INNER JOIN warehouses w ON w.id = ts.warehouse_id
                WHERE w.id = $warehouseId AND w.is_active = 1 AND p.is_active = 1";
        if (!empty($sku_id))
            $Sql .= " AND p.id IN (".implode(',',$sku_id).")";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($Sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function CreateThreshold($warehouseId,$skuId,$sku){

        $updateProduct = Products::findOne($skuId);
        $updateProduct->is_active=1;
        $updateProduct->updated_at=time();
        $updateProduct->update();

        $findStock = WarehouseStockList::find()->where(['warehouse_id'=>$warehouseId,'sku'=>$sku])->one();
        if (!$findStock){
            $createStock = new WarehouseStockList();
            $createStock->warehouse_id=$warehouseId;
            $createStock->sku = $sku;
            $createStock->available='0';
            $createStock->added_at=date('Y-m-d H:i:s');
            $createStock->updated_at=date('Y-m-d H:i:s');
            $createStock->save();
        }
        $findThreshold = ThresholdSales::find()->where(['warehouse_id'=>$warehouseId,'product_id'=>$skuId,'sku'=>$sku])->one();
        if ( !$findThreshold ){
            $create = new ThresholdSales();
            $create->warehouse_id=$warehouseId;
            $create->product_id=$skuId;
            $create->sku= $sku;
            $create->sales=0;
            $create->threshold=0.00;
            $create->status='Not Moving';
            $create->added_at=date('Y-m-d H:i:s');
            $create->updated_at=date('Y-m-d H:i:s');
            $create->save();
        }
    }
    public static function GetChannelWarehouses($channel){
        $Sql = "SELECT w.id as warehouseId
                FROM warehouse_channels wc
                INNER JOIN channels c ON c.id = wc.channel_id
                INNER JOIN warehouses w ON w.id = wc.warehouse_id
                WHERE c.id = ".$channel->id." AND w.is_active=1 AND c.is_active = 1 AND w.warehouse NOT IN ('lazada-fbl','amazon-fba')";
        $result = \Yii::$app->db->createCommand($Sql)->queryAll();
        return $result;
    }
    public static function GetDistributorWarehouseForSkuToFulfil($Sku, $postal_code, $channel){

        $Sql = "SELECT w.id as warehouseId, w.warehouse, wsl.available,w.name FROM warehouses w 
                INNER JOIN ord_fulfilled_by_wh_zipcodes ofz ON 
                ofz.warehouse_id = w.id 
                INNER JOIN warehouse_stock_list wsl ON 
                wsl.warehouse_id = w.id
                INNER JOIN warehouse_channels wc ON 
                wc.warehouse_id = w.id
                WHERE 
                wsl.sku = '$Sku' AND 
                w.is_active = 1 AND ofz.zipcode = '".$postal_code."' AND wc.channel_id = ".$channel['id']." AND wc.is_active = 1
                ORDER BY wsl.available DESC;";
        //echo $Sql;die;

        $connection = \Yii::$app->db;
        $command = $connection->createCommand($Sql);
        $result = $command->queryAll();
        if ( $result ){
            foreach ( $result as $key=>$value ){
                if ($value['warehouseId']==$channel->default_warehouse){
                    $result[$key]['default_warehouse']=1;
                }else{
                    $result[$key]['default_warehouse']=0;
                }
            }
        }
        return $result;
    }

    /***
     * @param $Sku
     * @param $postal_code
     * @param $channel
     * @return array
     * by city
     */
    public static function GetDistributorWarehouseForSkuToFulfilByCity($Sku, $city, $channel)
    {

        $Sql = "SELECT w.id as warehouseId, w.warehouse, wsl.available,w.name FROM warehouses w 
                INNER JOIN ord_fulfilled_by_wh_zipcodes ofz ON 
                ofz.warehouse_id = w.id
                INNER JOIN zipcodes zc
                ON zc.zipcode= ofz.zipcode
                INNER JOIN warehouse_stock_list wsl ON 
                wsl.warehouse_id = w.id
                INNER JOIN warehouse_channels wc ON 
                wc.warehouse_id = w.id
                WHERE 
                wsl.sku = '$Sku' AND 
                w.is_active = 1 AND zc.city_name = '".$city."' AND wc.channel_id = ".$channel['id']." AND wc.is_active = 1
                GROUP BY zc.city_name ,ofz.warehouse_id
                ORDER BY wsl.available DESC;";
       // echo $Sql;die;

        $connection = \Yii::$app->db;
        $command = $connection->createCommand($Sql);
        $result = $command->queryAll();
        if ( $result ){
            foreach ( $result as $key=>$value ){
                if ($value['warehouseId']==$channel->default_warehouse){
                    $result[$key]['default_warehouse']=1;
                }else{
                    $result[$key]['default_warehouse']=0;
                }
            }
        }
        return $result;
    }


    public static function GetOrderItemWarehouse($Sku, $customerInfo, $channel, $fulFilledByWarehouse=''){

        $ChannelsWarehouses = self::GetChannelWarehouses($channel);// exclude own warehouse like FBA, FBL or any other
        // specific for amazon currently
        if ( $fulFilledByWarehouse!='' ){
            return OrderUtil::GetFulFilledBy($channel);
        }
        // channel is not connected with any warehouse
        if ( empty($ChannelsWarehouses) ){
            return '';
        }
        if(in_array(strtolower($channel->name),['spl','pedro']) && in_array($channel->marketplace,['magento','prestashop'])){ // incase of that currently assign default warehouse
            return $channel->default_warehouse;
        }
        // means only one warehouse is connected with that channel
        else if ( count($ChannelsWarehouses) == 1 ){
            return $ChannelsWarehouses[0]['warehouseId'];
        }

        // there is no zipcode of customer,
       /* else if ( gettype($customerInfo['shipping_address']['postal_code']) != 'string' )
        {
            return $ChannelsWarehouses[0]['warehouseId'];
        }*/
        elseif ( count($ChannelsWarehouses) > 1 ){
            // now we have more than 1 warehouse. We will choose the one which have biggest stock and available sku
            // check by postal  code if postal code not empty
            if (isset($customerInfo['shipping_address']['postal_code']) && !empty($customerInfo['shipping_address']['postal_code']) )
                $distributor_warehouse = self::GetDistributorWarehouseForSkuToFulfil($Sku,$customerInfo['shipping_address']['postal_code'],$channel);
             else if(isset($customerInfo['shipping_address']['city']) && !empty($customerInfo['shipping_address']['city']))  // if postal code not present check by city
                $distributor_warehouse = self::GetDistributorWarehouseForSkuToFulfilByCity($Sku,$customerInfo['shipping_address']['city'],$channel);

             if(isset($distributor_warehouse) && !empty($distributor_warehouse)){
                 $warehouseId=self::AssignWarehouse($distributor_warehouse,$channel->default_warehouse);
                 return $warehouseId;
             }
             else if ( count($ChannelsWarehouses) > 1 ){
                 return self::AssignWarehouseWithMoreStocks($Sku, $ChannelsWarehouses);
             }
             else{
                return $channel->default_warehouse;
            }

        }
    }
    public static function AssignWarehouseWithMoreStocks( $sku, $ChannelsWarehouses ){
        $warehouseids=[];
        foreach ( $ChannelsWarehouses as $value ){
            $warehouseids[]=$value['warehouseId'];
        }
        //self::debug($warehouseids);
        $query = "SELECT * FROM warehouse_stock_list wsl
                  WHERE wsl.sku = '".$sku."' AND wsl.warehouse_id IN (".implode(',',$warehouseids).")
                  ORDER BY wsl.available DESC LIMIT 1;";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($query);
        $result = $command->queryAll();
        //self::debug($result);
        if ( $result ){
            return $result[0]['warehouse_id'];
        }else{
            return '';
        }
    }
    public static function AssignWarehouse($distributor_warehouse, $default_warehouse_id){

        $warehouseStocks=[];
        foreach ( $distributor_warehouse as $value ){
            $warehouseStocks[$value['warehouseId']]=$value['available'];
        }

        if ( array_sum($warehouseStocks)==0 ){ // all warehouses have 0 stock
            return $default_warehouse_id;
        }
        elseif ( isset($warehouseStocks[$default_warehouse_id]) && $warehouseStocks[$default_warehouse_id] == 0 ){ // if default warehouse stock is 0
            unset($warehouseStocks[$default_warehouse_id]);
            $MaxStockWarehouse = array_keys($warehouseStocks,max($warehouseStocks));
            return $MaxStockWarehouse[0];
        }
        elseif ( isset($warehouseStocks[$default_warehouse_id]) && $warehouseStocks[$default_warehouse_id] != 0 ){ // if default warehouse stock is not 0
            $defaultWarehouseStock = $warehouseStocks[$default_warehouse_id];
            unset($warehouseStocks[$default_warehouse_id]);
            $HighestDistributorStock = (!empty($warehouseStocks)) ? max($warehouseStocks) : 0;
            if ( $defaultWarehouseStock < $HighestDistributorStock ){ // if default warehouse stock is less then the highest distributor stock available
                $wh = array_keys($warehouseStocks,max($warehouseStocks));
                return $wh[0];
            }elseif ( $defaultWarehouseStock > $HighestDistributorStock ){ // if default warehouse stock is greater then the highest distributor stock available
                return $default_warehouse_id;
            }
            elseif ( count(array_unique($warehouseStocks))==1 ){ // if all stocks are same
                return $default_warehouse_id;
            }
        }
        elseif(count(array_unique($warehouseStocks))==1 ) // if all warehouses has same stock and default warehouse is not included
        {
            $first_value = key($warehouseStocks); // First element's key /// return first one
            return $first_value;
        }
        elseif ( !empty($warehouseStocks) ){
            $wh = array_keys($warehouseStocks,max($warehouseStocks));
            return $wh[0];
        }

        return $default_warehouse_id;
    }
    public static function GetThresholdsForPO($warehouseId, $sku_id=[]){

        $Thresholds = self::GetThresholds($warehouseId, $sku_id);
        //self::debug($Thresholds);
        $Skus = [];

        foreach ( $Thresholds as $key=>$values ){
            $Skus[$values['sku_id']]['status'] = $values['status'];
            $Skus[$values['sku_id']]['sku_id'] = $values['sku_id'];
            $Skus[$values['sku_id']]['sku'] = $values['sku'];
            $Skus[$values['sku_id']]['ean'] = $values['ean'];
            $Skus[$values['sku_id']]['cost_price'] = $values['cost_price'];
            $Skus[$values['sku_id']]['parent_sku_id'] = $values['parent_sku_id'];
            $Skus[$values['sku_id']]['threshold']['t1'] = $values['t1'];
            $Skus[$values['sku_id']]['threshold']['t2'] = $values['t2'];
            $Skus[$values['sku_id']]['threshold']['transit_days_threshold'] = $values['transit_days_threshold'];
        }
        // Set total sum of threshold
        foreach ( $Skus as $key=>$val ){

            $Total_Threshold = array_sum($val['threshold']);
            $Skus[$key]['total_threshold'] = $Total_Threshold;

        }
        return $Skus;

    }
    public static function getWarehouseStocklist($warehouseId){
        $sql = "SELECT wsl.warehouse_id, p.sku, wsl.available, wsl.stock_in_transit,p.id as sku_id FROM warehouse_stock_list wsl 
                INNER JOIN products p on p.sku = wsl.sku
                WHERE wsl.warehouse_id = ".$warehouseId;
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $list = [];
        foreach ( $result as $value ){
            $list[$value['sku_id']] = $value;
        }
        return $list;
    }
    public static function GetCurrentStocks($warehouseId , $WarehouseSales){


        //$stocks = WarehouseStockList::find()->where(['warehouse_id'=>$warehouseId])->asArray()->all();
        $stocks = self::getWarehouseStocklist($warehouseId);

        foreach ( $stocks as $Key=>$Val ){
            if ( !isset($WarehouseSales[$Val['sku_id']]) ){
                 continue; // it means product is available in the warehouse but ezcommerce doesn't have that product at all.
            }else{
                $WarehouseSales[$Val['sku_id']]['current_stock'] = $Val['available'];
                $WarehouseSales[$Val['sku_id']]['stock_in_transit'] = $Val['stock_in_transit'];
            }
        }
        foreach ( $WarehouseSales as $key=>$val ){
            if (!isset($val['current_stock'])){
                $WarehouseSales[$key]['current_stock'] = 0;
                $WarehouseSales[$key]['stock_in_transit'] = 0;
            }
        }

        return $WarehouseSales;
    }
    public static function GetDealsTargetForSku($warehouseId, $skuIdList=[]){
        $sql = "SELECT p.sku,p.id as sku_id,c.NAME AS channel_name,dm.NAME AS deal_name,dms.deal_target
                FROM deals_maker dm
                INNER JOIN deals_maker_skus dms ON
                dms.deals_maker_id = dm.id
                INNER JOIN channels c ON
                c.id = dm.channel_id
                INNER JOIN warehouse_channels wc ON
                wc.channel_id = c.id
                INNER JOIN warehouses w ON
                wc.warehouse_id = w.id
                INNER JOIN products p ON
                p.id = dms.sku_id
                INNER JOIN warehouse_stock_list wsl ON
                p.sku = wsl.sku
                WHERE dm.`status`='new' AND dms.`status` = 'Approved' AND wc.is_active = 1 AND c.is_active = 1 AND dm.start_date > '".date('Y-m-d H:i:s')."'
                AND w.id = $warehouseId";
        if (!empty($skuIdList))
            $sql .= " AND p.id IN (".implode(',',$skuIdList).")";

        $deal_targets = DealsMaker::findBySql($sql)->asArray()->all();
        return $deal_targets;
    }
    public static function GetDealsTargetsForPO($warehouseId, $WarehouseSales, $skuList=[]){
        //self::debug($skuList);
        $sql = "SELECT p.sku,p.id as sku_id,c.NAME AS channel_name,dm.NAME AS deal_name,dms.deal_target
                FROM deals_maker dm
                INNER JOIN deals_maker_skus dms ON
                dms.deals_maker_id = dm.id
                INNER JOIN channels c ON
                c.id = dm.channel_id
                INNER JOIN warehouse_channels wc ON
                wc.channel_id = c.id
                INNER JOIN warehouses w ON
                wc.warehouse_id = w.id
                INNER JOIN products p ON
                p.id = dms.sku_id
                INNER JOIN warehouse_stock_list wsl ON
                p.sku = wsl.sku
                WHERE dm.`status`='new' AND dms.`status` = 'Approved' AND wc.is_active = 1 AND c.is_active = 1 AND dm.start_date > '".date('Y-m-d H:i:s')."'
                AND w.id = $warehouseId ";

        if (!empty($skuList)){
            $sql .= " AND p.id IN (".implode(',',$skuList).")";
        }
        $sql .= " GROUP BY dm.id";

        $deal_targets = DealsMaker::findBySql($sql)->asArray()->all();

        foreach ($deal_targets as $value){ // set the deals target of each sku
            if ( isset($WarehouseSales[$value['sku_id']]) ){
                $WarehouseSales[$value['sku_id']]['deals_target'][] = $value;
                $Deals_total_targets = 0;
                foreach ( $WarehouseSales[$value['sku_id']]['deals_target'] as $targets ){
                    $Deals_total_targets += $targets['deal_target'];
                }
                $WarehouseSales[$value['sku_id']]['total_target_deals'] = $Deals_total_targets;
            }
        }
        foreach ( $WarehouseSales as $key=>$val ){ // set all the deals target 0 and empty array for sku which don't have deals to get rid of many if conditions before foreach
            if (!isset($val['deals_target']))
                $WarehouseSales[$key]['deals_target'] = array();
            if (!isset($val['total_target_deals']))
                $WarehouseSales[$key]['total_target_deals'] = 0;
        }
        return $WarehouseSales;
    }
    public static function GetSuggestedOrderQtyPO($Sales, $LineItem=false, $OrdQty=false){

        $SuggestedQty = [];
        //self::debug($Sales);
        foreach ( $Sales as $sku=>$detail ){

            //self::debug($detail);
            $Total_required_stocks = $detail['total_threshold'] + $detail['total_target_deals'];
            //echo $Total_required_stocks;die;
            $Difference = $Total_required_stocks -( $detail['current_stock'] + $detail['stock_in_transit'] ); // current stock - required, Ex : 20 - 180 = 160 stocks we need to order

            if ( $Difference > 0 && $LineItem == false ){
                $detail['suggested_order_qty'] = $Difference;
                $SuggestedQty[$sku] = $detail;

            }else if ( $LineItem == true ) {
                $detail['suggested_order_qty'] = 0;
                $detail['order_qty'] = $OrdQty;
                $SuggestedQty[$sku] = $detail;
            }

        }
        //self::debug($SuggestedQty);
        return $SuggestedQty;
    }
    public static function GetPoCode($WarehouseId, $PoId=''){
        $WarehouseDetail = Warehouses::find()->where(['id'=>$WarehouseId])->one();
        $PoNum = '';
        if ( $PoId == '' ){
            $GetPoCount = StocksPo::find()->select(['id'=>'( MAX(`id`)+ 1) '])->one()->id;
            $PoNum = $GetPoCount;
        }else{
            $PoNum = $PoId;
        }
        $PoCode = $WarehouseDetail->prefix.'-'.$PoNum;
        return $PoCode;
    }
    //public static function
    public static function SkuParentChildTree($Sales, $warehouseId=''){

        $SkuList = [];
        foreach ( $Sales as $key=>$value ){

            if ( $value['parent_sku_id']==0 ){

                $SkuList['single'][]=$value;
            }else{

                $parent = WarehouseUtil::GetThresholdsForPO($warehouseId,[$value['parent_sku_id']]);
                $parent = WarehouseUtil::GetCurrentStocks($warehouseId, $parent); // get the current stock of warehouse
                $parent   = WarehouseUtil::GetDealsTargetsForPO($warehouseId,$parent); // get the deals target for shops connected with channels
                $parent = WarehouseUtil::GetSuggestedOrderQtyPO($parent,true,$value['suggested_order_qty']); // Put the index of suggested order qty

                $SkuList['variations'][$value['parent_sku_id']]['parent'] = $parent[$value['parent_sku_id']];
                //self::debug($SkuList);
                $SkuList['variations'][$value['parent_sku_id']]['child'][] = $value;
            }
        }
        if( isset($SkuList['single']) ){
            foreach ( $SkuList['single'] as $key=>$detail ){
                if ( isset($SkuList['variations'][$detail['sku_id']]) ){
                    unset($SkuList['single'][$key]);
                }
            }
        }

        //self::debug($SkuList);
        if ( !isset($SkuList['single']) )
            $SkuList['single'] = [];
        elseif ( !isset($SkuList['variations']) )
            $SkuList['variations'] = [];

        return $SkuList;

    }
    public static function SkuParentChildTreePoSaved($Sales){
        $SkuList = [];
        //self::debug($Sales);
        foreach ( $Sales as $key=>$value ){

            if ( $value['parent_sku_id']==0 || !isset($Sales[$value['parent_sku_id']]) ){ // if parent is 0 or if parent sku not added in po

                $SkuList['single'][]=$value;

            }else{
                $SkuList['variations'][$value['parent_sku_id']]['parent'] = $Sales[$value['parent_sku_id']];
                $SkuList['variations'][$value['parent_sku_id']]['child'][] = $value;
            }
        }
        //self::debug($SkuList);
        foreach ( $SkuList['single'] as $key=>$detail ){
            if ( isset($SkuList['variations'][$detail['sku_id']]) ){
                unset($SkuList['single'][$key]);
            }
        }
        //self::debug($SkuList);
        if ( !isset($SkuList['single']) )
            $SkuList['single'] = [];
        elseif ( !isset($SkuList['variations']) )
            $SkuList['variations'] = [];

        return $SkuList;
    }
    public static function ShuffleSkuTypesPo($Sales, $wid=''){

        return self::SkuParentChildTree($Sales,$wid);

    }
    public static function GetPOThresholds( $PoId ){

        $getThresholds = PoThresholds::find()->where(['po_id'=>$PoId])->asArray()->all();
        $response = [];
        foreach ( $getThresholds as $key=>$val ){
            $response[$val['po_details_id']][$val['name']] = $val['value'];
        }
        return $response;

    }
    public static function GetPoDetail($poId){

        $PoDetail = PoDetails::find()->where(['po_id'=>$poId])->andWhere(['IS', 'bundle', null])->asArray()->all();
        //self::debug($PoDetail);
        $response = [];
        $po_thresholds = self::GetPOThresholds($poId);
        //self::debug($po_thresholds);


        foreach ( $PoDetail as $value ){

            $response[$value['sku_id']] = [
                'status' => $value['sku_status'],
                'sku_id' => $value['sku_id'],
                'sku' => $value['sku'],
                'cost_price' => $value['cost_price'],
                'parent_sku_id' => $value['parent_sku_id'],
                'ean' => $value['p_unique_code'],
                'threshold' => $po_thresholds[$value['id']],
                'total_threshold' => array_sum($po_thresholds[$value['id']]),
                'current_stock' => $value['current_stock'],
                'stock_in_transit' => $value['stock_in_transit'],
                'deals_target' => json_decode($value['deals_target_json'],1),
                'total_target_deals' => $value['total_deals_target'],
                'suggested_order_qty' => $value['suggested_order_qty'],
                'order_qty' => $value['order_qty'],
                'final_order_qty' => $value['final_order_qty'],
                'er_qty' => $value['er_qty']
            ];
        }

        return $response;

    }
    public static function UpdateStockInTransit($Skus){

        $WarehouseId = $Skus['warehouseId'];
        $response = [];

        if ( isset($Skus['button_clicked']) && $Skus['button_clicked'] == 'Finalize' ){

            foreach ( $Skus['SKUS'] as $SkuId => $SkuDetail ):

                $findStock = WarehouseStockList::findOne(['sku'=>$SkuDetail['sku'],'warehouse_id'=>$WarehouseId]);
                if ( $findStock ):
                    $findStock->updated_at = date('Y-m-d H:i:s');
                    $findStock->stock_in_transit = $SkuDetail['final_order_qty'] + $findStock['stock_in_transit'];
                    $findStock->update();
                    if ( !$findStock->errors ){
                        $response[$SkuDetail['sku']] = 'Updated Successfully';
                     }else{
                        $response[$SkuDetail['sku']] = json_encode($findStock->errors);
                    }
                else:
                    $addStock = new WarehouseStockList();
                    $addStock->warehouse_id = $WarehouseId;
                    $addStock->sku = $SkuDetail['sku'];
                    $addStock->available = '0';
                    $addStock->stock_in_transit = $SkuDetail['final_order_qty'];
                    $addStock->added_at = date('Y-m-d H:i:s');
                    $addStock->updated_at = date('Y-m-d H:i:s');
                    $addStock->save();
                    if ( !$addStock->errors ){
                        $response[$SkuDetail['sku']] = 'Added Successfully';
                    }else{
                        $response[$SkuDetail['sku']] = json_encode($addStock->errors);
                    }
                endif;

            endforeach;

        }
        return $response;

    }
    public static function GetPos(){

        $sql = "SELECT psp.id as po_id,w.warehouse,w.id as warehouse_id,psp.po_code, `er_no`,psp.po_initiate_date
                FROM 
                  `product_stocks_po` psp
                INNER JOIN warehouses w on w.id = psp.warehouse_id
                WHERE psp.po_initiate_date > DATE_SUB(NOW(), INTERVAL 18 DAY)
                 AND w.warehouse!='lazada-fbl'
                AND (psp.po_status = 'Pending' OR psp.po_status = 'Partial Shipped')";
        $results = StocksPo::findBySql($sql)->asArray()->all();
        return $results;
    }
    public static function GetPOSStockInTransit(){
        $sql = "SELECT w.warehouse AS warehouse_type,w.id AS warehouse_id,pod.sku,SUM(pod.stock_in_transit) 
                AS warehouse_stock_in_transit
                FROM `product_stocks_po` psp
                INNER JOIN warehouses w ON w.id = psp.warehouse_id
                INNER JOIN po_details pod ON pod.po_id = psp.id
                WHERE psp.po_initiate_date > DATE_SUB(NOW(), INTERVAL 18 DAY) AND (psp.po_status = 'Pending' OR psp.po_status = 'Partial Shipped')
                GROUP BY pod.sku,w.id";
        $results = StocksPo::findBySql($sql)->asArray()->all();
        return $results;
    }
    public static function UpdateWarehouseStockInTransit($list){

        foreach ( $list as $value ){
            $UpdatePoStatus = WarehouseStockList::findOne(['warehouse_id'=>$value['warehouse_id'],'sku'=>$value['sku']]);
            $UpdatePoStatus->stock_in_transit = $value['warehouse_stock_in_transit'];
            $UpdatePoStatus->update();
        }

    }
    public static function CreatePo($Skus){

        $warehouse = Warehouses::find()->where(['id'=>$Skus['warehouseId']])->one();

        if ( isset($Skus['button_clicked']) && $Skus['button_clicked'] == 'Finalize' ){

            if ( $Skus['warehouseType']=='skuvault' )
            {
                $po_request = self::SkuVaultPoRequestObj($Skus, $warehouse);
                $savePo = SkuVault::createPO($Skus['warehouseId'],$po_request);
                return $savePo;
            }
            if ( $Skus['warehouseType']=='istoreisend' )
            {
                // Create ER Number from ISIS
                $createEr = IsisUtil::createErc($warehouse->id, $Skus['po_id']);
                if ( $createEr['status'] ){
                    $erDetail=IsisUtil::addDetailErc($warehouse->id,$createEr['erNo'],$Skus['po_id']);
                }
                return $erDetail;
            }
            if ( $Skus['warehouseType']=='amazon-fba' )
            {
                if ( $warehouse->settings!='' ){
                    $warehouseSettings=json_decode($warehouse->settings,true);
                    if ( isset($warehouseSettings['warehouse_type']) && $warehouseSettings['warehouse_type']=='skuvault' ){
                        $po_request = self::SkuVaultPoRequestObj($Skus, $warehouse);
                        $savePo = SkuVault::createPO($warehouse->id,$po_request);
                        return $savePo;
                    }
                }
            }
        }

    }
    public static function SkuVaultPoRequestObj($Skus, $warehouse){
        $po_request = [];
        $po_request['OrderDate'] = HelpUtil::exchange_values('id','po_finalize_date',$Skus['po_id'],'product_stocks_po');
        $po_request['PaymentStatus'] = 'FullyPaid';
        $po_request['PoNumber'] = $Skus['po_code'];
        $po_request['SentStatus'] = 'Sent';
        // get warehouseCode from json confiugration
        $configWarehouse=json_decode($warehouse->configuration,true);
        $po_request['ShipToWarehouse'] = $configWarehouse['SkuVault_Warehouse_Code'];
        $po_request['SupplierName'] = 'Double D Imports SAS';

        foreach ( $Skus['SKUS'] as $SkuId => $SkuDetail ){
            $LineItem = [];
            $LineItem['Cost'] = $SkuDetail['cost_price'];
            $LineItem['Quantity'] = $SkuDetail['final_order_qty'];
            $LineItem['SKU'] = $SkuDetail['sku'];
            $po_request['LineItems'][] = $LineItem;
        }
        return $po_request;
    }
    public static function GetWarehouseRecipentInfo($wid){
        if ( \Yii::$app->getUser()->identity->role->name == 'Super' ){
            $Sql = "SELECT * from warehouses w WHERE w.id = ".$wid;
        }else{
            $Sql = "SELECT * from warehouses w INNER JOIN user  u ON u.id = w.user_id WHERE w.id = ".$wid;
        }
        $result = Warehouses::findBySql($Sql)->asArray()->all();
        return $result;
    }
    public static function GetWarehouseSkus(){

        if (!isset($_GET['warehouse']))
            return [];

        $Sql = "SELECT sku FROM warehouse_stock_list WHERE warehouse_id = ".$_GET['warehouse'];
        $Skus = WarehouseStockList::findBySql($Sql)->asArray()->all();
        return $Skus;

    }
    public static function GetWarehouseChannels( $warehouseId ){

        $WarehouseChannels = WarehouseChannels::findBySql("SELECT wc.channel_id,c.name FROM warehouse_channels wc
                                                                INNER JOIN channels c ON
                                                                c.id = wc.channel_id
                                                                INNER JOIN warehouses w ON
                                                                w.id = wc.warehouse_id
                                                                WHERE wc.is_active = 1 AND c.is_active = 1 AND w.is_active = 1 AND 
                                                                w.id = ".$_GET['warehouse'])->asArray()->all();
        if ($WarehouseChannels)
            return $WarehouseChannels;
        else
            return [];
    }
    public static function GetUnlistedSkus(){

        $WarehouseChannels = self::GetWarehouseChannels($_GET['warehouse']);

        //self::debug($WarehouseChannels);

        if (empty($WarehouseChannels))
            return $WarehouseChannels;

        $queryArry=[];
        foreach ( $WarehouseChannels as $channel ){

            $where = '';
            if (isset($_GET['Search']['SKU']) && $_GET['Search']['SKU']!=''){
                $where .= " AND wsl.sku = '".$_GET['Search']['SKU']."'";
            }
            if ( isset($_GET['Search']['shop']) && $_GET['Search']['shop']!='' ){
                $where .= " AND c.id = ".$_GET['Search']['shop'];
            }
            if ( isset($_GET['Search']['available']) && $_GET['Search']['available']!='' ){
                $where .= " AND wsl.available ".$_GET['Search']['available'];
            }

            $Sql = "SELECT wsl.sku, wsl.available,c.name FROM warehouse_stock_list wsl
                INNER JOIN warehouse_channels wc ON
                wc.warehouse_id = wsl.warehouse_id
                INNER JOIN channels c ON 
                c.id = wc.channel_id
                WHERE wsl.warehouse_id = ".$_GET['warehouse']." AND wc.channel_id = ".$channel['channel_id']." AND wsl.sku NOT IN (
                    SELECT cp.channel_sku FROM channels_products cp WHERE cp.channel_id = ".$channel['channel_id']."
                ) $where GROUP BY wsl.sku ";
            //echo $Sql;die;

            /*if ($_GET['page'] == 'All') {
                $Sql .= ' ';
            } else {
                $offset = 10 * ($_GET['page'] - 1);
                $Sql .= " LIMIT " . $offset . ",10 ";
            }*/

            $queryArry[] = $Sql;

        }

        $UnionQueyr = implode(' UNION ',$queryArry);
        //echo $UnionQueyr;die;
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($UnionQueyr);
        $result = $command->queryAll();
        return $result;


    }
    public static function GetUserRole(){
        $roleid = \Yii::$app->user->identity->role_id;
        $role = UserRoles::find()->where(['id'=>$roleid])->one();
        return strtolower($role->name);
    }
    public static function GetUserWarehouse(){
        $getWarehouse = Warehouses::find()->where(['user_id'=>\Yii::$app->user->identity->id])->one();
        return $getWarehouse;
    }
    public static function PoWarehouse(){

        $role = self::GetUserRole();

        if ( $role=='distributor' ){

            $getWarehouse = self::GetUserWarehouse();
            $Warehouses = Warehouses::find()->where(['is_active'=>1,'id'=>$getWarehouse->id])->all();

        }else{
            $Warehouses = Warehouses::find()->where(['is_active'=>1])->all();
        }

        return $Warehouses;
    }
    public static function GetLazadaProductsStocksFbl($channel_id){
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        $LazadaFblStocks=[];
        //$LazadaProducts=LazadaUtil::GetAllProducts($channel->id,'all',false,[],'');
        $LazadaProducts=ChannelsProducts::find()->where(['channel_id'=>$channel_id,'is_live'=>1,'deleted'=>0])->asArray()->all();
      //  self::debug($LazadaProducts);
        foreach( $LazadaProducts as $pDetail ) {
            $detail = [];
            $detail['sku'] = $pDetail['channel_sku'];//$pDetail['SellerSku'];
            $detail['available'] = LazadaUtil::GetFBLStock($channel,$pDetail['channel_sku']);//$pDetail['fulfilmentStock'];
            $LazadaFblStocks[]=$detail;
        }
        return $LazadaFblStocks;
    }
    public static function GetAmazonProductsStocksFba($channel_id){
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        $channel_products=ChannelsProducts::find()->where(['channel_id'=>$channel_id,'fulfilled_by'=>'FBA','is_live'=>1])->all();

        $list=[];
        $counter=0;
        $calls=0;
        foreach ($channel_products as $detail){
            $list[$calls][]=$detail['channel_sku'];
            ++$counter;
            if ( $counter==50 ){
                $counter=1;
                $calls++;
            }
        }

        $finalSkuList=[];
        foreach ( $list as $skuList ){
            $amazon_stock= AmazonUtil::getFbaStock ($channel,$skuList);
            foreach ( $amazon_stock as $detail ){

                $info = [];
                $info['sku'] = $detail['sku'];
                $info['available'] = $detail['available'];
                $finalSkuList[]=$info;
            }
        }
        return $finalSkuList;
    }
    public static function GetWarehouseSkusList($WarehouseId){
        $skus = WarehouseStockList::find()->where(['warehouse_id'=>$WarehouseId])->asArray()->all();
        $list=[];
        foreach ($skus as $detail){
            $list[]=$detail['sku'];
        }
        return $list;
    }
    public static function UpdateStocks($FBLstocks, $warehouseId){

        $GetStocks = self::GetWarehouseSkusList($warehouseId);
        $response=[];
        foreach ( $FBLstocks as $Sku=>$Stock ){

            if ( in_array($Sku,$GetStocks) ){

                $Update=\Yii::$app->db->createCommand()
                    ->update('warehouse_stock_list',['available'=>$Stock],['sku'=>$Sku,'warehouse_id'=>$warehouseId])->execute();
                if ( $Update )
                    $response[$Sku]='Successfully Updated';
                else if ($Update==0)
                    $response[$Sku]='Record Already Same';
                else
                    $response[$Sku]='Failed to Update';
            }else{
                $InsertStock = new WarehouseStockList();
                $InsertStock->available=$Stock;
                $InsertStock->added_at=date('Y-m-d H:i:s');
                $InsertStock->warehouse_id=$warehouseId;
                $InsertStock->sku=$Sku;
                $InsertStock->save();
                if ($InsertStock->errors)
                    $response[$Sku]=$InsertStock->errors;
                else
                    $response[$Sku]='Successfully Inserted';
            }
        }
        return $response;
    }
    public static function StockZeroSku($EzcommStocks, $LazadaFblStocks){

        if (empty($EzcommStocks))
            return 'Empty Array';

        $SkusList = [];
        foreach ( $EzcommStocks as $Sku=>$detail ){
            if ( !isset($LazadaFblStocks[$Sku]) && $detail['available'] != '0'  ){
                $SkusList[]=$Sku;
            }
        }

        if (!empty($SkusList)){
            $result = "'" . implode ( "', '", $SkusList ) . "'";
            $Sql = "UPDATE warehouse_stock_list SET available = 0 WHERE sku IN ($result)";
            $connection = \Yii::$app->db;
            $command = $connection->createCommand($Sql);
            $result = $command->queryAll();
            return $result;
        }


    }
    public static function GetFblWarehousesChannels(){

        $Sql="SELECT c.id AS channel_id,c.name, w.id AS warehouse_id,w.name AS warehouse_name, c.api_url, c.api_user, c.api_key, c.auth_params,
             w.warehouse AS warehouse_type, w.configuration AS warehouse_configuration
            FROM channels c
            INNER JOIN warehouse_channels wc ON
            c.id = wc.channel_id
            INNER JOIN warehouses w ON
            w.id = wc.warehouse_id
            WHERE w.is_active = 1 AND c.is_active = 1 AND wc.is_active =1 AND w.warehouse = 'lazada-fbl'";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($Sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function GetFbaWarehousesChannels($warehouse_id=null)
    {
        $extra_and="";
        if($warehouse_id)
        {
            $extra_and .=" AND `w`.`id`='".$warehouse_id."'";
        }

        $Sql="SELECT c.id AS channel_id,c.name, w.id AS warehouse_id,w.name AS warehouse_name, c.api_url, c.api_user, c.api_key, c.auth_params,c.marketplace,
             w.warehouse AS warehouse_type, w.configuration AS warehouse_configuration
            FROM channels c
            INNER JOIN warehouse_channels wc ON
            c.id = wc.channel_id
            INNER JOIN warehouses w ON
            w.id = wc.warehouse_id
            WHERE w.is_active = 1 AND c.is_active = 1 AND wc.is_active =1 AND w.warehouse = 'amazon-fba' $extra_and";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($Sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function GetCountryStates(){
        $Sql = "SELECT zc.id, zc.country, zc.state_name,zc.state_id FROM zipcodes zc
                GROUP BY zc.country,zc.state_id";
        $result = Warehouses::findBySql($Sql)->asArray()->all();
        return $result;
    }
    public static function GetZipCodes(){
        $AllZipCodes = Zipcodes::find()->groupBy('state_id')->asArray()->all();
        $zip = [];
        foreach ( $AllZipCodes as $key=>$value ){
            $zip[$key]['id'] = $value['zipcode'];
            $zip[$key]['title'] = $value['state_id'];

            $Cities = Zipcodes::find()->where(['state_id'=>$value['state_id']])->asArray()->all();
            foreach ( $Cities as $val ){
                $cities = [];
                $cities['id'] = $val['zipcode'];
                $cities['title'] = $val['city_name'] . ' - '.$val['zipcode'];
                $zip[$key]['subs'][]=$cities;
            }
        }
        return $zip;
    }
    public static function GetPreSelectedZipCodes($warehouseId){
        $PreSelected = [];
        $GetZipCodes = OrdFulfilledByWhZipcodes::find()->where(['warehouse_id'=>$warehouseId])->asArray()->all();
        foreach ( $GetZipCodes as $v ){
            $PreSelected[] = $v['zipcode'];
        }
        return $PreSelected;
    }
    public static function GetWarehouseStatesAndZipForUpdate($wid){
        $PreSelected = self::GetPreSelectedZipCodes($wid);
        $PreSelectedZipStates = OrdFulfilledByWhZipcodes::findBySql("SELECT zc.state_id, count(fbz.id) AS attached_zipcodes
                                                                        FROM zipcodes zc
                                                                        INNER JOIN ord_fulfilled_by_wh_zipcodes fbz ON
                                                                        zc.zipcode = fbz.zipcode
                                                                        WHERE
                                                                        fbz.warehouse_id = $wid
                                                                        GROUP BY zc.state_id
                                                                        ORDER BY zc.city_name ASC")->asArray()->all();
        //self::debug($PreSelectedZipStates);
        $realPreSelected = [];
        foreach ( $PreSelectedZipStates as $key=>$value ){
            $GetAllZIps = Zipcodes::find()->where(['state_id'=>$value['state_id']])->orderBy(['city_name'=>SORT_ASC])->asArray()->all();
            foreach ( $GetAllZIps as $key1=>$value1 ){
                if ( in_array($value1['zipcode'],$PreSelected) ){
                    $value1['selected']=1;
                }else{
                    $value1['selected']=0;
                }
                $value1['attached_zip']=$value['attached_zipcodes'];
                $realPreSelected[$value1['state_id']][$value1['id']]=$value1;
            }
        }
        return $realPreSelected;
    }
    public static function SaveZipCodes($warehouseId, $zipCodes){
        OrdFulfilledByWhZipcodes::deleteAll(['warehouse_id'=>$warehouseId]);
        $bulkInsert=[];
        foreach ( $zipCodes as $key=>$zipcode ){
            $bulkInsert[$key][] = $warehouseId;
            $bulkInsert[$key][] = $zipcode;
        }
        \Yii::$app->db
            ->createCommand()
            ->batchInsert('ord_fulfilled_by_wh_zipcodes', ['warehouse_id','zipcode'],$bulkInsert)
            ->execute();

    }
    public static function StockListBadges(){
        $warehouses = Warehouses::find()->where(['is_active'=>1])->asArray()->all();
        $Badges=[];
        foreach ( $warehouses as $value ){
            if ($value['settings']!=''){
                $warehouseSetting = json_decode($value['settings'],1);
                if ( isset($warehouseSetting['StockListBadges']['DaysListBadges']) ){
                    foreach ( $warehouseSetting['StockListBadges']['DaysListBadges'] as $BadgeKey=>$BadgeVal ){
                        $Badges[$value['name'].'_Est_OOS'][$BadgeKey]=$BadgeVal;
                    }
                }
            }
        }
        return $Badges;
    }
    public static function StockListLevels(){
        $warehouses = Warehouses::find()->where(['is_active'=>1])->asArray()->all();
        $Levels=[];
        foreach ( $warehouses as $value ){
            if ($value['settings']!=''){
                $warehouseSetting = json_decode($value['settings'],1);
                //self::debug($warehouseSetting);
                if ( isset($warehouseSetting['StockListBadges']['DaysLevelRanges']) ){
                    foreach ( $warehouseSetting['StockListBadges']['DaysLevelRanges'] as $LevelKey=>$LevelVal ){
                        $Levels[$value['name'].'_Est_OOS'][$LevelKey]=$LevelVal;
                    }
                }
            }
        }
        return $Levels;
    }
    public static function GetWarehouseChannelsNames($WarehouseId){

        $result = WarehouseChannels::findBySql("SELECT wc.channel_id,c.name FROM warehouse_channels wc
                                                                INNER JOIN channels c ON
                                                                c.id = wc.channel_id
                                                                INNER JOIN warehouses w ON
                                                                w.id = wc.warehouse_id
                                                                WHERE wc.is_active = 1 AND c.is_active = 1 AND w.is_active = 1 AND 
                                                                w.id = ".$WarehouseId)->asArray()->all();
        if ( !empty($result) ){
            $Channels='';
            foreach ($result as $key=>$value)
            {
                $Channels .= $value['name'].',';
            }
            return rtrim($Channels,',');
        }else{
            return '';
        }

    }
    public static function GetWarehouseAssignZipCodes($warehouseId){

        $GetZipCodes = OrdFulfilledByWhZipcodes::findBySql("SELECT zc.country,zc.state_name,zc.city_name,zc.zipcode FROM warehouses w
                                                                INNER JOIN ord_fulfilled_by_wh_zipcodes z ON
                                                                w.id = z.warehouse_id
                                                                INNER JOIN zipcodes zc ON
                                                                zc.zipcode = z.zipcode
                                                                WHERE w.id ='".$warehouseId."'
                                                                ORDER BY zc.state_name ASC,zc.city_name ASC")->asArray()->all();
        return $GetZipCodes;
    }
    public static function GetItemWarehouseDetail( $ItemIdPK ){
        $sql = "SELECT w.* FROM warehouses w 
                INNER JOIN order_items oi ON 
                w.id = oi.fulfilled_by_warehouse
                WHERE oi.id = $ItemIdPK";
        $result = Warehouses::findBySql($sql)->one();
        return $result;
    }
    public static function GetActiveBundles(){
        $BundleList = \Yii::$app->db->createCommand("SELECT * FROM products_relations WHERE is_active = 1")->queryAll();

        $bundlist=[];
        foreach ( $BundleList as $detail ){
            $bundlist[] = $detail['relation_name'];
        }
        return $bundlist;
    }
    public static function GetExpiredBundles(){
        $BundleList = \Yii::$app->db->createCommand("SELECT * FROM products_relations WHERE is_active = 0")->queryAll();
        $bundlist=[];
        foreach ( $BundleList as $detail ){
            $bundlist[] = $detail['relation_name'];
        }
        return $bundlist;
    }
    public function GetBundleParentSku( $BundleName){
        $BundleId = HelpUtil::exchange_values('relation_name','id',$BundleName,'products_relations');
        $ParentSkuId = HelpUtil::exchange_values('bundle_id','main_sku_id',$BundleId,'product_relations_skus');
        $ProductDetail = Products::find()->where(['id'=>$ParentSkuId])->asArray()->one();
        return $ProductDetail;
    }
    public static function GetBundleChildSkus( $BundleName ){
        $BundleId = HelpUtil::exchange_values('relation_name','id',$BundleName,'products_relations');
        $BundleSku = ProductRelationsSkus::find()->where(['bundle_id'=>$BundleId])->asArray()->all();
        $childSkus = [];
        foreach ( $BundleSku as $BundleDetail ){
            $ProductDetail = Products::find()->where(['id'=>$BundleDetail['child_sku_id']])->asArray()->one();
            $childSkus[] = $ProductDetail;
        }
        return $childSkus;
    }
    public function GetBundleDetail( $BundleName ){
        $BundleDetail = ProductsRelations::find()->select(['id as bundle_id','relation_type as bundle_type','relation_name as bundle_name',
            'bundle_cost'])->where(['relation_name'=>$BundleName])->asArray()->one();
        return $BundleDetail;
    }
    public static function Bundles($PoSkuList,$poId=null){

        if ( $poId==null ){
            $Bundle = [];
            $Bundle['active'] = self::GetActiveBundles();
            $Bundle['inactive'] = self::GetExpiredBundles();

            //self::debug($PoSkuList);
            foreach ( $PoSkuList['single'] as $key=>$detail ){

                if ( in_array($detail['sku'],$Bundle['inactive']) ){
                    unset( $PoSkuList['single'][$key] );
                }
                if ( in_array($detail['sku'],$Bundle['active']) ){

                    $BundleDetail=[];
                    $BundleInfo = self::GetBundleDetail($detail['sku']);
                    $BundleDetail['Bundle_Information'] = array_merge($detail,$BundleInfo);
                    $BundleDetail['Bundle_Parent_Sku'] = self::GetBundleParentSku($detail['sku']);
                    $BundleDetail['Bundle_Child_Sku'] = self::GetBundleChildSkus($detail['sku']);
                    $PoSkuList['bundles'][] = $BundleDetail;
                    unset( $PoSkuList['single'][$key] );
                }

            }
            //self::debug($PoSkuList);
            return $PoSkuList;
        }
        else{
            $findBundle = PoDetails::find()->where(['po_id'=>$poId])->andWhere(['IS NOT', 'bundle', null])->asArray()->all();
            $PoSkuList['bundles'] = self::poBundleRedefine($findBundle);
            return $PoSkuList;
        }


    }
    public static function poBundleRedefine( $poBundles ){
        //self::debug($poBundles);
        $bundles=[];
        //self::debug($bundles);
        foreach ( $poBundles as $bundleinfo ){
            $relation_data = ProductsRelations::find()->where(['id'=>$bundleinfo['bundle']])->asArray()->one();
            //self::debug($bundleinfo);
            if ( $bundleinfo['cost_price']!=''  ){
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['status'] =$bundleinfo['sku_status'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['sku_id'] =$bundleinfo['sku_id'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['bundle_name'] =$relation_data['relation_name'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['sku'] =$bundleinfo['sku'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['cost_price'] =$bundleinfo['cost_price'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['parent_sku_id'] =$bundleinfo['parent_sku_id'];



                // get total threshold;
                $total_threshold = [];
                $getThreshold=PoThresholds::find()->where(['po_id'=>$bundleinfo['po_id'],'po_details_id'=>$bundleinfo['id']])->asArray()->all();
                foreach ( $getThreshold as $value ){
                    $total_threshold[$value['name']] = $value['value'];
                }
                //self::debug($total_threshold);
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['threshold'] =$total_threshold;
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['total_threshold'] =array_sum($total_threshold);
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['current_stock'] =$bundleinfo['current_stock'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['stock_in_transit'] =$bundleinfo['stock_in_transit'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['deals_target'] =json_decode($bundleinfo['deals_target_json'],1);
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['total_target_deals'] =$bundleinfo['total_deals_target'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['suggested_order_qty'] =$bundleinfo['suggested_order_qty'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['bundle_id'] =$bundleinfo['bundle'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['bundle_type'] =$relation_data['relation_type'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['bundle_cost'] =$bundleinfo['bundle_cost'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['order_qty'] =$bundleinfo['order_qty'];
                $bundles[$bundleinfo['bundle']]['Bundle_Information']['final_order_qty'] =$bundleinfo['final_order_qty'];
                $bundles[$bundleinfo['bundle']]['Bundle_Parent_Sku'] =Products::find()->where(['id'=>$bundleinfo['sku_id']])->asArray()->one();
            }else{
                //echo 'child';

                $productDetail = Products::find()->where(['id'=>$bundleinfo['sku_id']])->asArray()->one();
                $bundles[$bundleinfo['bundle']]['Bundle_Child_Sku'][] = $productDetail;
            }

        }
        return $bundles;
    }

    /*****
     * @param $warehouse_id
     * @return array|\yii\db\ActiveRecord[]
     * get warehouse products not synced to online warehouse from ezcommerce
     */
   /* public static function getWarehouseProductsNotSynced($warehouse_id)
    {
        $sql = "SELECT p.sku ,p.name ,p.rccp,p.ean,p.rccp   
                FROM 
                  products p
                INNER JOIN channels_products cp
                 ON p.id = cp.product_id
                WHERE
                  p.ean != 0 and p.is_active = 1 and p.ean is not null AND cp.deleted=0
                AND cp.channel_id IN (SELECT channel_id FROM warehouse_channels wc WHERE warehouse_id = $warehouse_id)
                AND p.sku NOT IN (select `ezcom_entity_id` from `ezcom_to_warehouse_sync` where `warehouse_id`='".$warehouse_id."' and `type`='product') 
                GROUP BY
                  p.sku";
        $products = Products::findBySql($sql)->asArray()->all();
        //self::debug($products);
        return $products;
    }*/

    /*****
     * @param $warehouse_id
     * @return array|\yii\db\ActiveRecord[]
     * get warehouse products not synced to online warehouse from ezcommerce
     */

    public static function getProductsNotSyncedTOWarehouse($warehouse,$unique_column=null)
    {
        $and="";
        if(is_array($warehouse))
            $warehouse_id=$warehouse['id'];
        else
            $warehouse_id=$warehouse->id;

        if($unique_column=='ean')
            $and .= " AND p.ean != 0 AND p.ean is not null";
        else if($unique_column=='barcode')
            $and .= " AND p.barcode!='' AND p.barcode!='0'";

        $sql = "SELECT p.sku ,p.name ,p.rccp,p.ean,p.cost,p.rccp,p.barcode,p.brand   
                FROM 
                  products p
                INNER JOIN channels_products cp
                 ON p.id = cp.product_id
                 INNER JOIN ezcom_to_warehouse_product_sync ewp
                 ON ewp.sku=p.sku
                WHERE
                     p.is_active = 1  AND cp.deleted=0 $and
                  AND
                    cp.channel_id IN (SELECT channel_id FROM warehouse_channels wc WHERE warehouse_id = $warehouse_id)
                  AND
                    ewp.status='pending'
                GROUP BY
                  p.sku";
       // echo $sql; die();
        $products = Products::findBySql($sql)->asArray()->all();
        //self::debug($products);
        return $products;
    }

    /****************ezcom to warehouse product sync response update **************/
    public static function ezcomToWarehouseProductSyncResponse($data)
    {
        $sku=EzcomToWarehouseProductSync::findOne(['sku'=>$data['sku'],'warehouse_id'=>$data['warehouse_id']]);
        if($sku)
        {
             $sku->status=$data['status'];
             if(isset($data['response']) && $data['response'])
                 $sku->response=$data['response'];

            if(isset($data['comment']) && $data['comment'])
             $sku->comment=$data['comment'];

             if(isset($data['third_party_id']) && $data['third_party_id'])
                 $sku->third_party_id=$data['third_party_id'];

             $sku->synced_at=date('Y-m-d H:i:s');
             $sku->save();
        }
        return;
    }

    public static function add_stock_log($data)
    {
        $log = new StockDepletionLog();
        $log->warehouse_id = $data['warehouse_id'];
        $log->item_sku = $data['sku'];
        $log->order_id = isset($data['order_id_pk']) ?  $data['order_id_pk']:NULL;
        $log->order_item_id = isset($data['order_item_id']) ? $data['order_item_id']:NULL ;
        $log->status = isset($data['status']) ? $data['status']:NULL;
        $log->quantity = (isset($data['qty'])) ? $data['qty'] : NULL;
        $log->stock_before = $data['stock_before'];
        $log->stock_after = $data['stock_after'];
        $log->stock_pending_before = $data['stock_pending_before'];
        $log->stock_pending_after = $data['stock_pending_after'];
        $log->note = isset($data['note']) ? $data['note']:NULL ;
        $log->added_at = date('Y-m-d H:i:s');
        $log->type =$data['type'];
        $log->save(false);
        return;
    }




}