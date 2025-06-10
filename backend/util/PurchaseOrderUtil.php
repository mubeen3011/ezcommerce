<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/17/2019
 * Time: 5:30 PM
 */
namespace backend\util;
use common\models\PoDetails;
use common\models\PoThresholds;
use common\models\StocksPo;
use common\models\WarehouseStockList;

class PurchaseOrderUtil{

    public static function SavePurchaseOrder($Skus){

        if (isset($Skus['po_id'])){
                $UpdatePO = StocksPo::findOne($Skus['po_id']);
                $UpdatePO->po_status = ($Skus['button_clicked']=='Save' || $Skus['button_clicked']=='Initiate PO') ? 'Draft' : 'Pending';
                $UpdatePO->updated_at = time();
                $UpdatePO->po_bill=$Skus['po_bill'];
                $UpdatePO->po_ship=$Skus['po_ship'];
                $UpdatePO->remarks=$Skus['remarks'];
                $UpdatePO->po_finalize_date = ($Skus['button_clicked']=='Finalize') ? date('Y-m-d H:i:s') : null;
                $UpdatePO->update();
                if ($UpdatePO->errors){
                    $response = $UpdatePO->errors;
                }
                else{
                    $response = $Skus['po_id'];
                }
        }
        else
            {
                $AddPurchaseOrder = new StocksPo();
                $AddPurchaseOrder->warehouse_id = $Skus['warehouseId'];
                $AddPurchaseOrder->po_initiate_date = date('Y-m-d H:i:s');
                $AddPurchaseOrder->po_status = ($Skus['po_status']=='New' || $Skus['po_status']=='Draft') ? 'Draft' : 'Pending';
                $AddPurchaseOrder->po_code = $Skus['po_code'];
                $AddPurchaseOrder->po_bill=$Skus['po_bill'];
                $AddPurchaseOrder->po_ship=$Skus['po_ship'];
                $AddPurchaseOrder->remarks=$Skus['remarks'];
                $AddPurchaseOrder->created_at = time();
                $AddPurchaseOrder->updated_at = time();
                $AddPurchaseOrder->save();

                if ($AddPurchaseOrder->errors){
                    $response = $AddPurchaseOrder->errors;
                }
                else{
                    $response = $AddPurchaseOrder['id'];
                }
            }

            return $response;
    }

    public static function SavePurchaseOrderSkus($PoId, $Skus){

        $response = [];

        if ( isset($Skus['po-selected-skus']) ){
            foreach ( $Skus['po-selected-skus'] as $SkuId ){

                $findPoSku = PoDetails::find()->where(['sku_id'=>$SkuId,'po_id'=>$PoId])->one();
                if (!isset($Skus['SKUS'][$SkuId])) // it means its a bundle sku
                    continue;
                if ( $findPoSku ){
                    $findPoSku->final_order_qty = (isset($Skus['SKUS'][$SkuId]['final_order_qty'])) ? $Skus['SKUS'][$SkuId]['final_order_qty'] : $Skus['SKUS'][$SkuId]['order_qty'];
                    $findPoSku->updated_at = time();
                    if ( isset($Skus['button_clicked']) && $Skus['button_clicked'] == 'Finalize' ){
                        $findPoSku->stock_in_transit = $Skus['SKUS'][$SkuId]['final_order_qty']; // It will be order_qty because current po final order quantity is the SIT
                    }else{
                        $findPoSku->stock_in_transit = $Skus['SKUS'][$SkuId]['stock_in_transit'];
                    }
                    $findPoSku->update();
                }else{
                    $AddSkus = new PoDetails();
                    $AddSkus->po_id = $PoId;
                    $AddSkus->sku_id = $SkuId;
                    $AddSkus->sku = $Skus['SKUS'][$SkuId]['sku'];
                    $AddSkus->p_unique_code = '';
                    $AddSkus->cost_price = $Skus['SKUS'][$SkuId]['cost_price'];
                    $AddSkus->p_unique_code = $Skus['SKUS'][$SkuId]['ean'];
                    $AddSkus->current_stock = $Skus['SKUS'][$SkuId]['current_stock'];
                    $AddSkus->deals_target_json = ($Skus['SKUS'][$SkuId]['deals_json']);
                    $AddSkus->total_deals_target = $Skus['SKUS'][$SkuId]['total_target_deals'];
                    $AddSkus->order_qty = $Skus['SKUS'][$SkuId]['order_qty'];
                    $AddSkus->final_order_qty = $Skus['SKUS'][$SkuId]['order_qty'];
                    $AddSkus->stock_in_transit = $Skus['SKUS'][$SkuId]['stock_in_transit'];
                    $AddSkus->suggested_order_qty = $Skus['SKUS'][$SkuId]['suggested_order_qty'];
                    $AddSkus->sku_status = $Skus['SKUS'][$SkuId]['status'];
                    $AddSkus->parent_sku_id = $Skus['SKUS'][$SkuId]['parent_sku_id'];
                    $AddSkus->created_at = time();
                    $AddSkus->updated_at = time();
                    $AddSkus->save();
                    if ( $AddSkus->errors ){
                        $response[] = $AddSkus->errors;
                    }else{
                        $PoDetailId = $AddSkus['id'];
                        foreach ( $Skus['SKUS'][$SkuId]['Threshold'] as $Tname => $Tval ){

                            $AddThreshold = new PoThresholds();
                            $AddThreshold->po_id = $PoId;
                            $AddThreshold->po_details_id = $PoDetailId;
                            $AddThreshold->name = $Tname;
                            $AddThreshold->description = '';
                            $AddThreshold->value = $Tval;
                            $AddThreshold->save();

                        }
                        $response[] = 'Success';
                    }
                }
            }
            $Sql = "DELETE FROM po_details WHERE po_id = $PoId AND sku_id NOT IN (".implode(',',$Skus['po-selected-skus']).");";
            $connection = \Yii::$app->db;
            $command = $connection->createCommand($Sql);
            $result = $command->query();
        }


        // for bundles specific
        if (isset($Skus['bundle'])){
            foreach ( $Skus['bundle'] as $BundleDetail ){
                //echo '<pre>';print_r($Skus['bundle']);die;
                $saveParent=self::SaveParentSkuBundle($BundleDetail,$PoId);
                $saveChild=self::SaveChildSkuBundle($BundleDetail,$PoId);

                if ( !isset($Skus['po-bundle-selected-skus']) ){
                    $Sql = "DELETE FROM po_details WHERE po_id = $PoId AND bundle IS NOT NULL;";
                    \Yii::$app->db->createCommand($Sql)->execute();
                }else{
                    $Sql = "DELETE FROM po_details WHERE po_id = $PoId AND bundle NOT IN (".implode(',',$Skus['po-bundle-selected-skus']).") AND bundle IS NOT NULL;";
                    \Yii::$app->db->createCommand($Sql)->execute();
                }

            }
            //die;
        }


        // Delete the sku rows that was unchecked in the form
        /*echo '<pre>';
        print_r($Skus['po-selected-skus']);
        die;*/

        return $response;

    }
    public static function SaveChildSkuBundle ( $BundleInfo, $PoId ) {
        // find bundle child skus already added or not
        $BundleName = HelpUtil::exchange_values('id','relation_name',$BundleInfo['bundle_id'],'products_relations');
        $BundleChildSkus = WarehouseUtil::GetBundleChildSkus($BundleName);
        //echo '<pre>';
        //print_r($BundleChildSkus);
        foreach ( $BundleChildSkus as $SkuDetail ){
            $findSku = PoDetails::find()->where(['po_id'=>$PoId,'sku_id'=>$SkuDetail['id'],'bundle'=>$BundleInfo['bundle_id']])->one();
            if ( !$findSku ){
                //echo 'Not found child';
                //echo '<br />';
                $addBundleParentsku=new PoDetails();
                $addBundleParentsku->po_id=$PoId;
                $addBundleParentsku->sku_id=$SkuDetail['id'];
                $addBundleParentsku->sku=$SkuDetail['sku'];
                $addBundleParentsku->p_unique_code=$SkuDetail['ean'];
                $addBundleParentsku->suggested_order_qty=$BundleInfo['suggested_order_qty'];
                $addBundleParentsku->order_qty=$BundleInfo['order_qty'];
                $addBundleParentsku->final_order_qty=(isset($BundleInfo['final_order_qty'])) ? $BundleInfo['final_order_qty'] : $BundleInfo['order_qty'];
                $addBundleParentsku->bundle=$BundleInfo['bundle_id'];
                $addBundleParentsku->bundle_cost=$BundleInfo['bundle_cost_price'];
                $addBundleParentsku->created_at = time();
                $addBundleParentsku->updated_at = time();
                $addBundleParentsku->created_by = \Yii::$app->user->getId();
                $addBundleParentsku->updated_by = \Yii::$app->user->getId();
                $addBundleParentsku->save();
                foreach ( $BundleInfo['Threshold'] as $Tname => $Tval )
                {
                    $AddThreshold = new PoThresholds();
                    $AddThreshold->po_id = $PoId;
                    $AddThreshold->po_details_id = $addBundleParentsku['id'];
                    $AddThreshold->name = $Tname;
                    $AddThreshold->description = '';
                    $AddThreshold->value = $Tval;
                    $AddThreshold->save();
                }
            }else{
               // echo 'Found Child';
                //echo '<br />';
                $findSku->order_qty=$BundleInfo['order_qty'];
                $findSku->final_order_qty=(isset($BundleInfo['final_order_qty'])) ? $BundleInfo['final_order_qty'] : $BundleInfo['order_qty'];
                $findSku->updated_by = \Yii::$app->user->getId();
                $findSku->updated_at = time();
                $findSku->update();
            }
        }
        //die;

    }
    public static function SaveParentSkuBundle( $BundleInfo, $PoId ){
        // find bundle already added or not
        /*echo '<pre>';
        print_r($BundleInfo);die;*/
        $findSku = PoDetails::find()->where(['po_id'=>$PoId,'sku'=>$BundleInfo['sku'],'bundle'=>$BundleInfo['bundle_id']])->one();
        //echo '<pre>';print_r($findSku);die;
        if ( !$findSku ){
            $addBundleParentsku=new PoDetails();
            $addBundleParentsku->po_id=$PoId;
            $addBundleParentsku->sku_id=HelpUtil::exchange_values('sku','id',$BundleInfo['sku'],'products');
            $addBundleParentsku->sku=$BundleInfo['sku'];
            $addBundleParentsku->p_unique_code=$BundleInfo['ean'];
            $addBundleParentsku->cost_price=$BundleInfo['bundle_cost_price'];
            $addBundleParentsku->current_stock=$BundleInfo['current_stock'];
            $addBundleParentsku->sku_status=$BundleInfo['status'];
            $addBundleParentsku->total_deals_target=$BundleInfo['total_target_deals'];
            $addBundleParentsku->stock_in_transit=$BundleInfo['stock_in_transit'];
            $addBundleParentsku->deals_target_json=$BundleInfo['deals_json'];
            $addBundleParentsku->suggested_order_qty=$BundleInfo['suggested_order_qty'];
            $addBundleParentsku->order_qty=$BundleInfo['order_qty'];
            $addBundleParentsku->final_order_qty=(isset($BundleInfo['final_order_qty'])) ? $BundleInfo['final_order_qty'] : $BundleInfo['order_qty'];
            $addBundleParentsku->bundle=$BundleInfo['bundle_id'];
            $addBundleParentsku->bundle_cost=$BundleInfo['bundle_cost_price'];
            $addBundleParentsku->created_at = time();
            $addBundleParentsku->updated_at = time();
            $addBundleParentsku->created_by = \Yii::$app->user->getId();
            $addBundleParentsku->updated_by = \Yii::$app->user->getId();
            $addBundleParentsku->save();
            //echo '<pre>';print_r($BundleInfo);die;
            //echo $addBundleParentsku['id'];die;
            foreach ( $BundleInfo['Threshold'] as $Tname => $Tval )
            {
                $AddThreshold = new PoThresholds();
                $AddThreshold->po_id = $PoId;
                $AddThreshold->po_details_id = $addBundleParentsku['id'];
                $AddThreshold->name = $Tname;
                $AddThreshold->description = '';
                $AddThreshold->value = $Tval;
                $AddThreshold->save();
            }
        }
        else{
            $findSku->order_qty=$BundleInfo['order_qty'];
            $findSku->final_order_qty=(isset($BundleInfo['final_order_qty'])) ? $BundleInfo['final_order_qty'] : $BundleInfo['order_qty'];
            $findSku->updated_by = \Yii::$app->user->getId();
            $findSku->updated_at = time();
            $findSku->update();
            if ( $findSku->errors ){
                echo '<pre>';
                print_r($findSku->errors);
                die;
            }
        }
    }
    public static function GetTotalAmountOfShippedPO(){
        $w = HelpUtil::getWarehouseDetail();

            $sql = "SELECT SUM(po_d.cost_price * po_d.order_qty) AS grand_total
                FROM product_stocks_po psp
                INNER JOIN warehouses w ON
                w.id = psp.warehouse_id
                LEFT JOIN po_details po_d ON
                po_d.po_id = psp.id
                WHERE 1=1 AND psp.po_status = 'Shipped' AND w.id IN ($w);";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        if ( $result )
            $total_amount = $result[0]['grand_total'];
        else
            $total_amount = 0;

        return $total_amount;

    }
    public static function GetPoRecievedQty($WarehouseId,$poCode,$warehouseType){

        if ( $warehouseType=='skuvault' ){
            $response = [];
            $filter['PONumbers'] = '['.$poCode.']';
            $getpos = SkuVault::getPOs($WarehouseId,$filter);
            if (!isset($getpos->PurchaseOrders[0]->LineItems)){
                echo '<h2>Sku vault not returning po detail for '.$poCode.' Maybe 
                  THIS po was deleted on skuVault or Maybe there site is on maintainince please visit https://app.skuvault.com Thank you</h2>';
                die;
            }
            foreach ( $getpos->PurchaseOrders[0]->LineItems as $Item ){
                if ( $Item->ReceivedQuantity > 0 ){
                    $response[$Item->SKU] = $Item->ReceivedQuantity;
                }
            }
        }
        elseif ( $warehouseType=='istoreisend' ){
            $response = [];
            $erNo = HelpUtil::exchange_values('po_code','er_no',$poCode,'product_stocks_po');
            $isisResponse = IsisUtil::fetchER($WarehouseId,$erNo);
            if ( isset($isisResponse['success']) && $isisResponse['success']=='1' ){
                if ( isset($isisResponse['returnObject']['erDetailViewList']) ){
                    foreach ( $isisResponse['returnObject']['erDetailViewList'] as $detail ){
                        $response[$detail['storageClientSkuNo']] = $detail['recvQty'];
                    }
                }
            }
        }


        return $response;
    }
    public static function GetPoFinalOrderedQty( $SkuList ){

        $response = [];
        foreach ( $SkuList as $detail ){
            $response[$detail['sku']] = $detail['final_order_qty'];
        }
        return $response;
    }
    public static function UpdateStockInTransit( $Sku, $WarehouseId,$transitValue=0){

        $GetSkuWh = WarehouseStockList::find()->where(['sku'=>$Sku,'warehouse_id'=>$WarehouseId])->one();
        $GetSkuWh->stock_in_transit = $transitValue;
        $GetSkuWh->update();
        if ( $GetSkuWh->errors )
            return $GetSkuWh->errors;
        else
            return $GetSkuWh;


    }
    public static function UpdateRecievedQtySkus($PoID,$WarehouseId,$poCode,$SkuList,$warehouseType){
        $response=[];
        $SkuVaultPoSkus = self::GetPoRecievedQty($WarehouseId,$poCode,$warehouseType);
        $EzPoSkus = self::GetPoFinalOrderedQty($SkuList);

        $SumVault = array_sum($SkuVaultPoSkus); // total recieved
        $po_status = 'pending'; // by default

        if ( $SumVault > 0 ){
            foreach ( $EzPoSkus as $Sku=>$FinalOrdered ){
                //update recieved quantity
                $findPoDetail = PoDetails::findOne(['po_id'=>$PoID,'sku'=>$Sku]);
                $findPoDetail->er_qty = isset($SkuVaultPoSkus[$Sku]) ? $SkuVaultPoSkus[$Sku] : 0;
                $findPoDetail->stock_in_transit = $findPoDetail->final_order_qty - $findPoDetail->er_qty;
                $findPoDetail->update();

                //update stock in transit according to recieving quantity
               if(isset($SkuVaultPoSkus[$Sku])){
                    $transitValue = $FinalOrdered - $SkuVaultPoSkus[$Sku];
                    $transitValue = ($transitValue<=0)?0:$transitValue;

                    //Decide PO Status
                    if($po_status=='pending' && $transitValue==0){
                        $po_status = 'Shipped';
                    }
                    elseif($po_status=='pending' && $transitValue!=0){
                        $po_status = 'Partial Shipped';
                    }
                    elseif($po_status=='Shipped' && $transitValue==0){
                        $po_status = 'Shipped';
                    }
                    elseif($po_status=='Shipped' && $transitValue!=0){
                        $po_status = 'Partial Shipped';
                    }
                    else{
                        $po_status = 'Partial Shipped';
                    }
                }
                else{
                    if($po_status = 'pending'){
                        $po_status = 'pending';
                    }elseif($po_status=='Shipped'){
                        $po_status = 'Partial Shipped';
                    }
                    else{
                        $po_status = 'Partial Shipped';
                    }
                }
            }
        }
        echo 'PoCode : '.$poCode.'<br />';
        echo 'SkuVaultRecievedQtyTotal : '.$SumVault.'<br />';
        echo 'EZCommerceFinalOrderedTotal : '.array_sum($EzPoSkus).'<br />';
        echo 'PoStatus : '.$po_status.'<br /><br /><br /><br />';

        $response['status'] = $po_status;
        return $response;

    }
    public static function getSkusForErc($po_id){
        // get single skus
        $Single_Sku_sql = "SELECT 
                      pod.sku,
                      pod.final_order_qty,
                  p.`is_foc`,
                  pod.sku AS details
                    FROM
                      `po_details` pod 
                      INNER JOIN products p 
                        ON p.id = pod.`sku_id` 
                    WHERE 
                    pod.`sku_id` NOT IN 
                      (SELECT 
                        a.id 
                      FROM
                        products a 
                        JOIN products b 
                          ON a.id = b.parent_sku_id) 
                  AND pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS  NULL";
        $Single_Result = PoDetails::findBySql($Single_Sku_sql)->asArray()->all();
        // get FB skus
        $Fb_sku_sql = "SELECT 
                  pr.relation_name as sku,
                  pod.final_order_qty,
                  '0' AS `is_foc` ,
                  GROUP_CONCAT(pod.sku SEPARATOR ' + ') AS details
                FROM
                  `po_details` pod 
                  
                  INNER JOIN `products_relations` pr
                    ON pr.id = pod.bundle AND relation_type = 'FB'
                WHERE 
                   pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS NOT NULL 
                  GROUP BY bundle";
        $Fb_Result = PoDetails::findBySql($Fb_sku_sql)->asArray()->all();
        // get FOC skus
        $FOC_sku_sql = "SELECT 
                 pod.sku,
                  pod.final_order_qty,
                  p.`is_foc`,
                  pod.sku AS details
                FROM
                  `po_details` pod 
                  INNER JOIN `products_relations` pr
                    ON pr.id = pod.bundle AND relation_type = 'FOC'
                    INNER JOIN products p 
                    ON p.id = pod.`sku_id` 
                WHERE 
                   pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS NOT NULL ";
        $FOC_result = PoDetails::findBySql($FOC_sku_sql)->asArray()->all();
        return array_merge($Single_Result,$Fb_Result,$FOC_result);
    }
}