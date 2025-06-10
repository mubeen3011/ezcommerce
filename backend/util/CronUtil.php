<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/21/2018
 * Time: 10:48 AM
 */
namespace backend\util;

use backend\controllers\ApiController;
use common\models\Channels;
use common\models\ClaimItems;
use common\models\CrossCheckProductPrices;
use common\models\OrderItems;
use common\models\Orders;
use common\models\Products;
use common\models\Settings;
use common\models\TempCrawlResults;
use common\models\Threshold;
use common\models\ThresholdDays;
use common\models\ThresholdSales;
use common\models\WarehouseStockList;
use yii\base\ErrorException;
use yii;

class CronUtil{

    public static function GetSales($channel_id){

        $sql = 'SELECT * from orders
                WHERE order_updated_at BETWEEN DATE(NOW() - INTERVAL 1 DAY) AND \''.date('Y-m-d').'\' AND channel_id ='.$channel_id;
        $FindOrders=Orders::findBySql($sql)->asArray()->all();
        return $FindOrders;

    }

    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    public static function LogClaimsPrestashop(){

        $response = [];
        $response['added']=[];
        $response['updated']=[];

        $mp='prestashop';
        $channels = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => $mp,'name'=>$_GET['channel_name']])->all();

        if (empty($channels)){
            echo '<h2>Channel not found</h2>';
            die;
        }

        $ClaimStatuses = Yii::$app->params['PrestashopClaimsStatuses'];

        foreach ($channels as $k => $v) {

            $v->marketplace = ucwords($v->marketplace);
            $shipping_carriers = PrestashopUtil::Carriers($v);
            $statesListPrestashop = PrestashopUtil::getStatesList($v);
            $orders = PrestashopUtil::GetOrders($v, '-1 day');

            if (json_encode($orders->data->orders)=='{"0":"\n"}')
            {
                echo 'No Orders Found for claims in particular DateTime.';
                die;
            }
            $params = [];

            foreach ($orders->data->orders->order as $o) {

                $OrderId = HelpUtil::exchange_values('order_number','id',$o->reference,'orders');
                $orderItems = OrderItems::find()->where(['order_id' => $OrderId])->asArray()->all();

                $ordercarrier = PrestashopUtil::orderCarriers($v,$o->id);

                foreach ($orderItems as $oi) {

                    $FindItemClaim=ClaimItems::find()->where(['order_id'=>$o->reference,'order_item_id'=>$o->reference,'sku'=>$oi['item_sku']])->asArray()->all();



                    if ( $FindItemClaim ) {

                        $UpdateClaimItem = ClaimItems::findOne($FindItemClaim[0]['id']);
                        $UpdateClaimItem->order_status=$statesListPrestashop[$o->current_state];
                        $UpdateClaimItem->order_id = $o->reference;
                        $UpdateClaimItem->order_item_id = $o->reference;
                        $UpdateClaimItem->order_item_status=$statesListPrestashop[$o->current_state];
                        $UpdateClaimItem->reason='-';
                        $UpdateClaimItem->shipping_type=(isset($ordercarrier->order_carriers->order_carrier->id_carrier)) ? $shipping_carriers[$ordercarrier->order_carriers->order_carrier->id_carrier] : '';
                        $UpdateClaimItem->channel_id=$v->id;
                        $UpdateClaimItem->sku=$oi['item_sku'];
                        $UpdateClaimItem->marketplace='Prestashop';
                        //$UpdateClaimItem->cancel_return_by=$initiator;
                        //$UpdateClaimItem->cancel_return_initiator=$value['cancel_return_initiator'];
                        $UpdateClaimItem->update();
                        if (empty($UpdateClaimItem->errors))
                            $response['updated'][]=$UpdateClaimItem['id'];
                    }else if ( in_array($statesListPrestashop[$o->current_state],$ClaimStatuses) )
                        {
                            $AddClaimItem=new ClaimItems();
                            $AddClaimItem->order_id=$o->reference;
                            $AddClaimItem->order_item_id= (string) $o->reference;
                            $AddClaimItem->order_status=$statesListPrestashop[$o->current_state];
                            $AddClaimItem->order_item_status=$statesListPrestashop[$o->current_state];
                            $AddClaimItem->reason='-';
                            $AddClaimItem->shipping_type=(isset($ordercarrier->order_carriers->order_carrier->id_carrier)) ? $shipping_carriers[$ordercarrier->order_carriers->order_carrier->id_carrier] : '';
                            $AddClaimItem->channel_id=$v->id;
                            $AddClaimItem->marketplace='Prestashop';
                            $AddClaimItem->sku=$oi['item_sku'];
                            //$AddClaimItem->cancel_return_by=$initiator;
                            //$AddClaimItem->cancel_return_initiator=$value['cancel_return_initiator'];
                            $AddClaimItem->added_at = date('Y-m-d H:i:s');
                            $AddClaimItem->save();
                            if (!empty($AddClaimItem->errors))
                                self::debug($AddClaimItem->errors);
                            else
                                $response['added'][] = $AddClaimItem['id'];

                        }
                }
            }
        }
        $GetAllClaims=ClaimItems::find()->where(['marketplace'=>'prestashop'])->asArray()->all();
        //self::debug($GetAllClaims);
        foreach ($GetAllClaims as $key=>$value){

            $color='Orange';

            /*if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Sourcing payment issue' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of Delivery Address' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change payment method' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Out of Stock' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Decided for alternative product' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Fees-shipping costs' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Seller' && $value['reason']=='Out of Stock' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Sourcing payment issue' )
                $color='Green';


            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Lost Item' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Breach-Delivery promise date' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Damaged by 3PL' )
                $color='Red';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Delivery time is too long' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Defective' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Damaged' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Missing accessories' )
                $color='Red';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='System Error' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Wrong size' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Lost Item' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Damaged by 3PL' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Breach-Delivery promise date' )
                $color='Red';


            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Sourcing Delay (cannot meet deadline)' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Delivery address is wrong' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Customer Not At Home or Not Reachable' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of mind' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Found cheaper elsewhere' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Wrong item' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='cancel' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of mind' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Customer Not At Home or Not Reachable' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Delivery address is wrong' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Not as advertised' )
                $color='Orange';*/


            $UpdateClaimColor=ClaimItems::findOne($value['id']);
            $UpdateClaimColor->claim_category=$color;
            $UpdateClaimColor->update();
            if ( !empty($UpdateClaimColor->errors) )
                self::debug($UpdateClaimColor->errors);

        }
        return $response;
    }
    public static function LogClaimsEbay(){
        $mp = 'ebay';
        $response = [];
        $response['added']=[];
        $response['updated']=[];
        $channels = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => $mp,'name'=>$_GET['channel_name']])->all();
        $ClaimStatuses = Yii::$app->params['EbayClaimsStatuses'];
        if( empty($channels) ){
            echo '<h2>Channel not found</h2>';
            die;
        }
        foreach ($channels as $k => $v) {
            $v->marketplace = ucwords($v->marketplace);



            $from = strtotime(date("Y-m-d H:i:s") .' -100 day' ); // will bring last 3 months orders//
            $from= gmdate('Y-m-d\TH:i:s\Z',$from);
            $current_time=date("Y-m-d\TH:i:s",time());
            $current_time = gmdate('Y-m-d\TH:i:s\Z',strtotime($current_time));
            $orders = EbayUtil::GetAllOrders($v->id,'100', $from, $current_time);

            $params = [];
            foreach ($orders as $o) {

                $params['order_id'] = $o->OrderID;
                if ( !in_array($o->OrderStatus,$ClaimStatuses) )
                    continue;
                $t1 =  strtotime( $o->CheckoutStatus->LastModifiedTime );
                $t2 =  strtotime( $o->CreatedTime );
                $diff = $t1 - $t2;
                $hours = $diff / ( 60 * 60 );
                    echo '<br />';
                    if (  $hours >= 4  )
                    {
                        $items=[];
                        ( gettype($o->TransactionArray->Transaction) == 'object' ) ? $items[0]=$o->TransactionArray->Transaction : $items=$o->TransactionArray->Transaction;
                        //self::debug($items);

                        foreach ($items as $value) {

                            $FindItemClaim=ClaimItems::find()->where(['order_item_id'=>$value->Item->ItemID])->asArray()->all();
                            if (empty($FindItemClaim)){
                                $AddClaimItem=new ClaimItems();
                                $AddClaimItem->order_id=$o->OrderID;
                                $AddClaimItem->order_item_id=(string) $value->Item->ItemID;
                                $AddClaimItem->order_status = $o->OrderStatus;
                                $AddClaimItem->shipping_type=(isset($value->ShippingDetails->ShipmentTrackingDetails->ShippingCarrierUsed)) ? $value->ShippingDetails->ShipmentTrackingDetails->ShippingCarrierUsed : '';
                                $AddClaimItem->reason='-';
                                $AddClaimItem->channel_id=$v->id;
                                $AddClaimItem->marketplace='Ebay';
                                $AddClaimItem->sku=(isset($value->Item->SKU)) ? $value->Item->SKU : '';
                                $AddClaimItem->save();
                                if (!empty($AddClaimItem->errors))
                                    self::debug($AddClaimItem->errors);
                                else
                                    $response['added'][] = $AddClaimItem['id'];

                            }else{
                                $UpdateClaimItem = ClaimItems::findOne($FindItemClaim[0]['id']);
                                $UpdateClaimItem->order_status=$o->OrderStatus;
                                $UpdateClaimItem->shipping_type=(isset($value->ShippingDetails->ShipmentTrackingDetails->ShippingCarrierUsed)) ? $value->ShippingDetails->ShipmentTrackingDetails->ShippingCarrierUsed : '';
                                $UpdateClaimItem->channel_id=$v->id;
                                $UpdateClaimItem->reason='-';
                                $UpdateClaimItem->added_at = date('Y-m-d H:i:s');
                                $UpdateClaimItem->sku=(isset($value->Item->SKU)) ? $value->Item->SKU : '';
                                $UpdateClaimItem->marketplace='Ebay';
                                //$UpdateClaimItem->order_item_created_at=date('Y-m-d h:i:s',$resp['orders'][0]['create_time']);
                                //$UpdateClaimItem->order_item_updated_at=date('Y-m-d h:i:s',$resp['orders'][0]['update_time']);
                                $UpdateClaimItem->update();
                                if (empty($UpdateClaimItem->errors))
                                    $response['updated'][]=$UpdateClaimItem['id'];
                            }
                            //die;
                        }
                    }
                }
                /*
                 * Update all the claims with the color statuses
                 *
                 * */
                ClaimItems::updateAll(array( 'claim_category' => 'Orange' ), ' marketplace = \'Ebay\'' );

        }
    }

    public static function LogClaimsStreet(){
        $mp='street';
        $channels = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => $mp])->all();
        foreach ($channels as $k => $v) {
            if ($v->id==17)
                continue;
            $v->marketplace = ucwords($v->marketplace);
            $ordersFunc = "fetch{$v->marketplace}Orders";
            if ($v->marketplace == 'Street') {
                $orders = CronUtil::GetSales($v->id);
                $params = [];
                foreach ($orders as $o) {
                    $params['order_id'] = $o['order_id'];
                    $resp = ApiController::$ordersFunc($v, $params);

                    $t1 = StrToTime ( $resp[0]['ordStlEndDt'] );
                    $t2 = StrToTime ( $resp[0]['ordDt'] );
                    $diff = $t1 - $t2;
                    $hours = $diff / ( 60 * 60 );
                    $streetStatus = ['101' => 'Order Complete', '102' => 'Awaiting Payment', '103' => 'Waiting Pre-Order',
                        '201' => 'Pre-order Payment Complete', '202' => 'Payment Complete', '301' => 'Preparing for Shipment',
                        '401' => 'Shipping in Progress', '501' => 'Shipping Complete', '601' => 'Claim Requested', '701' => 'Cancellation Requested',
                        '801' => ' Awaiting for Re-approval', '901' => 'Purchase Confirmed', 'A01' => 'Return Complete', 'B01' => 'Order Cancelled', 'C01' => 'Cancel Order upon Purchase 
Confirmation'];
                    $streetStatuses=['101','102','103','201','202','301','401','501','801','901','A01'];
                    if (  //$hours >= 4  &&
                    (!in_array($resp[0]['ordPrdStat'],$streetStatuses)) )
                    {
                        foreach ($resp as $oi) {

                            $FindItemClaim=ClaimItems::find()->where(['order_id'=>$oi['ordNo']])->andWhere(['sku'=>$oi['sellerPrdCd']])->asArray()->all();
                            if (empty($FindItemClaim)){
                                if ( gettype($oi['sellerPrdCd']) != 'string' ){
                                    continue;
                                }
                                $AddClaimItem=new ClaimItems();
                                $AddClaimItem->order_id=$o['order_id'];
                                $AddClaimItem->order_status=$streetStatus[$oi['ordPrdStat']];
                                $AddClaimItem->channel_id=$v->id;
                                $AddClaimItem->marketplace='Street';
                                $AddClaimItem->sku=$oi['sellerPrdCd'];
                                $AddClaimItem->reason='-';
                                $AddClaimItem->order_item_created_at=date('Y-m-d h:i:s', strtotime($oi['ordDt']));
                                $AddClaimItem->order_item_updated_at=date('Y-m-d h:i:s', strtotime($oi['ordStlEndDt']));
                                try {
                                    $AddClaimItem->save();
                                }
                                catch ( ErrorException $er ){
                                    self::debug($er);
                                }

                            }else{
                                $UpdateClaimItem = ClaimItems::findOne($FindItemClaim[0]['id']);
                                $UpdateClaimItem->order_status=$streetStatus[$oi['ordPrdStat']];
                                $UpdateClaimItem->channel_id=$v->id;
                                $UpdateClaimItem->sku=$oi['sellerPrdCd'];
                                $UpdateClaimItem->reason='-';
                                $UpdateClaimItem->marketplace='Street';
                                $UpdateClaimItem->order_item_created_at=date('Y-m-d h:i:s', strtotime($oi['ordDt']));
                                $UpdateClaimItem->order_item_updated_at=date('Y-m-d h:i:s', strtotime($oi['ordStlEndDt']));
                                $UpdateClaimItem->update();
                            }
                        }
                    }


                }
                /*
                 * Update all the claims with the color statuses
                 *
                 * */
                ClaimItems::updateAll(array( 'claim_category' => 'Orange' ), ' marketplace = \'Street\' AND order_status = \'Order Cancelled\'' );
            }
        }
        die;
    }
    public static function getClaims($marketplace){
        $encryptionKey = Settings::GetDataEncryptionKey();
        $sql = "SELECT ci.*, chan.prefix as channel_prefix ,chan.NAME AS channel_name,
                AES_DECRYPT(ca.shipping_fname,'$encryptionKey') AS customer_name,
                AES_DECRYPT(ca.shipping_number,'$encryptionKey') AS customer_phone,
                AES_DECRYPT(ca.shipping_city,'$encryptionKey') as shipping_city,
                AES_DECRYPT(ca.shipping_country,'$encryptionKey') as shipping_country
                FROM claim_items ci
                LEFT JOIN orders o ON
                o.order_id = ci.order_id
                LEFT JOIN customers_address ca ON
                ca.order_id = o.id
                LEFT JOIN channels chan ON 
                chan.id = ci.channel_id
                WHERE ci.marketplace='".$marketplace."' AND (ci.claim_category='Orange' || ci.claim_category = 'Red') AND ci.case_in_crm= 0 ";
        $getClaims=ClaimItems::findBySql($sql)->asArray()->all();
        return json_encode($getClaims);
    }
    public static function getCrossCheckPrices(){
        $sql = 'SELECT p.`sku`,c.`name` as channel_name,cc.`price_should_be`,cc.`price_is`,cc.difference,cc.api_response,
                cc.added_at FROM cross_check_product_prices cc
                INNER JOIN `products` p ON
                p.`id` = cc.`sku_id`
                INNER JOIN `channels` c ON
                c.`id` = cc.`channel_id`
                WHERE cc.`price_is` IS NOT NULL 
                AND cc.api_response NOT LIKE \'%"code":"0"%\' 
                AND cc.api_response NOT LIKE \'%Modify discount item success%\'
                AND cc.added_at = \''.date('Y-m-d').'\'
                AND cc.difference>0
                GROUP BY p.`sku`,c.`name`
                ORDER by cc.difference DESC;';
        $list=CrossCheckProductPrices::findBySql($sql)->asArray()->all();
        return $list;
    }
    public static function getCrawledSkus(){
        $sql = "SELECT c.name as channel_name,tcr.* FROM `temp_crawl_results` tcr
                INNER JOIN channels c on c.id = tcr.channel_id
                INNER JOIN products p on p.id = tcr.sku_id
                WHERE tcr.`added_at` = '".date('Y-m-d')."';";

        $result = TempCrawlResults::findBySql($sql)->asArray()->all();
        $_redefine = [];
        foreach ( $result as $value ){
            if ( isset($_redefine[$value['channel_name']]) && in_array($value['sku_id'],$_redefine[$value['channel_name']]) )
                continue;
            $_redefine[$value['channel_name']][]=$value['sku_id'];
        }
        return $_redefine;
    }
    public static function GetWarehouseSalesCounts($warehouse)
    {

        $startDate = date('Y-m-d', strtotime('first day of -3 months'));
        $endDate = date('Y-m-d', strtotime('last day of -1 month'));
        $days= ((strtotime($endDate)-strtotime($startDate))/86400);

        $connection = \Yii::$app->db;

        $sql = "SELECT oi.sku_id, oi.item_sku, SUM(oi.quantity) AS qty, (SUM(oi.quantity)/91) AS per_day_threshold FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                INNER JOIN warehouses w ON w.id = oi.fulfilled_by_warehouse
                WHERE
                oi.`item_status` NOT IN(".GraphsUtil::GetCanceledStatuses().") AND
                oi.fulfilled_by_warehouse = ".$warehouse->id." and
                (item_created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59')
                GROUP BY oi.item_sku";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ( $result as $key=>$value ){
            if ($value['sku_id']==''){
                $skuId=HelpUtil::exchange_values('sku','id',$value['item_sku'],'products');
                if ($skuId!='false'){
                    $result[$key]['sku_id']=$skuId;
                }
            }
        }

        return $result;

    }
    public static function SetStockStatus( $Sales ){

        // if sales weekly >= 25 => High
        // if sales weekly >= 10 => Medium
        // if sales weekly >= 2 => Slow
        // else => Not Moving
        foreach ( $Sales as $key=>$val ){

            if ( $val['qty'] >= 25 )
                $Sales[$key]['selling_status'] = 'High';

            elseif ( $val['qty'] >= 10 )
                $Sales[$key]['selling_status'] = 'Medium';

            elseif ( $val['qty'] >= 2 ){
                $Sales[$key]['selling_status'] = 'Slow';

            }else{
                $Sales[$key]['selling_status'] = 'Not Moving';
            }
        }
        return $Sales;

    }

    public static function SetThresholds($Sales)
    {

        $Thresholds = ThresholdDays::find()->all();

        foreach ($Thresholds as $val){

            foreach ($Sales as $key=>$sale_detail){

                $PerDaySale = $sale_detail['qty'] / 60;
                $Threshold_Sale = $PerDaySale * $val->days;
                $Sales[$key]['Thresholds'][$val->name] = ceil($Threshold_Sale);

            }

        }
        return $Sales;
    }

    public static function GetWarehouseThresholds($warehouse)
    {

        $Sales = self::GetWarehouseSalesCounts($warehouse);  // get sales related to warehouse of each sku
        $Sales = self::SetStockStatus($Sales); // slow medium high

        return $Sales;

    }

    public static function ThresholdList()
    {

        $threshold_list = ThresholdDays::find()->all();
        $data = [];
        foreach ( $threshold_list as $key=>$value ){

            $data[$value->name] = $value;

        }
        return $data;
    }

    public static function SaveThresholds($warehouse, $sales)
    {

        $response = [];

        foreach ( $sales as $saleDetail ){

            $findProduct = ThresholdSales::find()->where(['warehouse_id'=>$warehouse->id,'product_id'=>$saleDetail['sku_id'],'sku'=>$saleDetail['item_sku']])->one();

            if (!$findProduct){
                $addSalesCount = new ThresholdSales();
                $addSalesCount->warehouse_id = $warehouse->id;
                $addSalesCount->product_id = $saleDetail['sku_id'];
                $addSalesCount->sku = $saleDetail['item_sku'];
                $addSalesCount->sales = $saleDetail['qty'];
                $addSalesCount->threshold = $saleDetail['per_day_threshold'];
                $addSalesCount->status = $saleDetail['selling_status'];
                $addSalesCount->save();
                if ($addSalesCount->errors){
                    $response['Sales'][] = $addSalesCount->errors;
                }
            }
            else
            {
                $findProduct->sales = $saleDetail['qty'];
                $findProduct->status = $saleDetail['selling_status'];
                $findProduct->threshold = $saleDetail['per_day_threshold'];
                $findProduct->updated_at = date('Y-m-d H:i:s');
                $findProduct->update();
                if ($findProduct->errors){
                    $response['Sales'][] = $findProduct->errors;
                }
            }
        }
        return $response;
    }
    public static function UpdateThresholdForcast($Sku, $WarehouseId, $PerDayThreshold){
        $search = WarehouseStockList::find()->where(['sku'=>$Sku,'warehouse_id'=>$WarehouseId])->one();
        if ( $search ){
            $updatePerDayThreshold = WarehouseStockList::findOne($search->id);

            try{
                $updatePerDayThreshold->days_left_in_oos = number_format($search->available / $PerDayThreshold,0);
            }
            catch (\ErrorException $e){
                if ( $search->available == 0 ){
                    $updatePerDayThreshold->days_left_in_oos = '0';
                }
                elseif ( $search->available > 0 ){
                    $updatePerDayThreshold->days_left_in_oos = ($search->available*100);
                }
            }

            $updatePerDayThreshold->update();
        }
        else{
            $AddNewRecord = new WarehouseStockList();
            $AddNewRecord->warehouse_id = $WarehouseId;
            $AddNewRecord->sku = $Sku;
            $AddNewRecord->available = '0';
            $AddNewRecord->stock_in_transit = '0';
            $AddNewRecord->stock_in_pending = '0';
            $AddNewRecord->days_left_in_oos = $PerDayThreshold;
            $AddNewRecord->added_at = date('Y-m-d H:i:s');
            $AddNewRecord->updated_at = date('Y-m-d H:i:s');
            $AddNewRecord->save();
        }
    }
    public static function NoSalesForcastOutOfStock($SalesThresholds, $Warehouse){
        $productIds = [];
        foreach ( $SalesThresholds as $detail ){
            if ($detail['product_id']=='')
                continue;
            $productIds[] = $detail['product_id'];
        }
        if ($productIds){
            $sql = "SELECT p.id,p.sku FROM products p INNER JOIN warehouse_stock_list wsl on wsl.sku=p.sku WHERE p.is_active = 1 AND p.id NOT IN (".implode(',',$productIds).")";
        }else{
            $sql = "SELECT p.id,p.sku FROM products p INNER JOIN warehouse_stock_list wsl on wsl.sku=p.sku WHERE p.is_active = 1 AND wsl.warehouse_id = ".$Warehouse->id;
        }
        //echo $sql;die;
        $products = Products::findBySql($sql)->all(); // these are the skus which we didn't get the thresholds by warehouse sales. because of no sales
        //self::debug($products);
        foreach ($products as $p_detail){
            self::UpdateThresholdForcast($p_detail['sku'], $Warehouse->id, '25000');
        }
    }
    public static function NoSalesThresholds($Warehouse, $Sales){

        $productIds = [];
        foreach ( $Sales as $detail ){
            if ($detail['sku_id']=='')
                continue;
            $productIds[] = $detail['sku_id'];
        }
        if ($productIds){
            $sql = "SELECT p.id,p.sku FROM products p INNER JOIN warehouse_stock_list wsl on wsl.sku=p.sku WHERE p.is_active = 1 AND p.id NOT IN (".implode(',',$productIds).")";
        }else{
            $sql = "SELECT p.id,p.sku FROM products p INNER JOIN warehouse_stock_list wsl on wsl.sku=p.sku WHERE p.is_active = 1 AND wsl.warehouse_id = ".$Warehouse->id;
        }
        $products = Products::findBySql($sql)->all(); // these are the skus which we didn't get the thresholds by warehouse sales. because of no sales
        $response =[];

        foreach ( $products as $p_detail ){
            //self::debug($p_detail);
            //$findProduct = ThresholdSales::find()->where(['warehouse_id'=>$Warehouse->id,'product_id'=>$p_detail->id])->one();
            $findProduct = ThresholdSales::find()->where(['warehouse_id'=>$Warehouse->id,'sku'=>$p_detail->sku])->one();

            if (!$findProduct){
                $addSalesCount = new ThresholdSales();
                $addSalesCount->warehouse_id = $Warehouse->id;
                $addSalesCount->product_id = $p_detail['id'];
                $addSalesCount->sku = $p_detail['sku'];
                $addSalesCount->sales = 0;
                $addSalesCount->threshold = 0;
                $addSalesCount->status = 'Not Moving';
                $addSalesCount->save();
                if ($addSalesCount->errors){
                    $response['Sales']['save'][] = $addSalesCount->errors;
                }
                $tsid = $addSalesCount['id'];
            }
            else{
                $findProduct->sales = 0;
                $findProduct->status = 'Not Moving';
                $findProduct->threshold = 0;
                $findProduct->updated_at = date('Y-m-d H:i:s');
                $findProduct->update();
                if ($findProduct->errors){
                    $response['Sales']['update'][] = $findProduct->errors;
                }
            }
        }
    return $response;

    }

    public static function getSellingSTock($channel_id,$start_date,$end_date,$by)
    {
        $sql="SELECT SUM(`oi`.`quantity`) as total_sold_qty,`oi`.`item_sku`
               FROM 
                    `order_items` oi
                    INNER JOIN 
                        `channels_products` cp
                    ON  `oi`.`item_sku`=`cp`.`channel_sku`
                    INNER JOIN
                        `orders` o
                    ON `o`.`id`=`oi`.`order_id`
                    INNER JOIN
                        `channels` ch
                    ON `ch`.`id`=`o`.`channel_id`
                    WHERE `oi`.`item_status` NOT IN(".GraphsUtil::GetCanceledStatuses().")
                    AND (`oi`.`item_created_at` between '".$start_date."' AND '".$end_date."')
                    AND `o`.`channel_id`='".$channel_id."'
                    GROUP BY `oi`.`item_sku`";
        return yii::$app->db->createCommand($sql)->queryAll();
    }

    public function updateSellingStatus($channel_id,$sales,$global_sku_status=null)
    {
        if($channel_id && $sales)
        {
            foreach($sales as $item)
            {
                $connection = Yii::$app->db;
                $connection->createCommand()->update('channels_products', ['sales' => $item['total_sold_qty']], ['channel_id'=>$channel_id,'channel_sku'=>$item['item_sku']])->execute();
                if($global_sku_status && isset($global_sku_status[$item['item_sku']]))
                {
                    $global_sku_status[$item['item_sku']]=($global_sku_status[$item['item_sku']] + $item['total_sold_qty']);
                } else {
                    $global_sku_status[$item['item_sku']]=$item['total_sold_qty'];
                }
            }
        }
        return $global_sku_status;
    }

    public function resetUnsoldStockStatus($channel_id,$reset_items) // if 3 months sales is zero then set that sku sales to 0
    {
        if($channel_id && $reset_items)
        {
            $connection = Yii::$app->db;
            $sql="UPDATE `channels_products` SET `sales`='0' 
                    WHERE
                        `channel_id`='".$channel_id."' and `channel_sku` NOT IN ( '" . implode( "', '" , $reset_items ) . "' )";
            $connection->createCommand($sql)->execute();
        }
        return;
    }

    public function updateProductGlobalStatus($global_sku) // set product status on overall sale based on last 3 months
    {
        if($global_sku)
        {
            foreach($global_sku as $sku=>$val)
            {
                $status="Not Moving";
                 $monthly=ceil($val/3); //month sale
                 $weekly=ceil(($monthly/30)*7);
                 if($monthly >= 12)
                        $status="High";
                elseif($monthly >=5)
                    $status="Medium";
                 elseif($monthly >= 1)
                     $status="Slow";

              //echo $sku . " -> " . $status .PHP_EOL;

                $connection = Yii::$app->db;
                $connection->createCommand()->update('products', ['selling_status' =>$status], ['sku'=>$sku])->execute();

            }
        }
        return;
    }

    public static function resetUnsoldProductGlobalStatus($global_sku)
    {
      //  return;
        if($global_sku)
        {
            $connection = Yii::$app->db;
            $sql="UPDATE `products` SET `selling_status`='Not Moving' 
                    WHERE
                         `sku` NOT IN ( '" . implode( "', '" , array_keys($global_sku) ) . "' )";
            $connection->createCommand($sql)->execute();
        }
        return;
    }

    public static function getMonthlySale($channel_id,$year)
    {
        $sql="SELECT 
                  ((SUM(oi.`quantity` * oi.paid_price))) AS `total_sales`,MONTH(oi.`item_created_at`) as `month`,'$year' as year, 
                  c.id as 'channel_id'
                FROM
                  `order_items` oi 
                  INNER JOIN orders o 
                    ON o.id = oi.`order_id` 
                  INNER JOIN channels c 
                    ON c.`id` = o.`channel_id`
                  INNER JOIN channels_products cp on cp.channel_sku = oi.item_sku and c.id = cp.channel_id 
                   WHERE oi.`item_status` NOT IN(\"Awaiting for Braintree validation\",\"Awaiting bank wire payment\",\"On backorder (not paid)\",\"unpaid\",\"cancelled\",\"invalid\",\"to_return\",\"in_cancel\",\"accepted\",\"refund_paid\",\"closed\",\"seller_dispute\",\"returned\",\"reversed\",\"missing orders\",\"canceled\",\"refunded\",\"expired\",\"failed\",\"returned\",\"reversed\",\"delivery failed\",\"canceled by customer\")
                  AND c.id= '".$channel_id."'
                  AND YEAR(oi.`item_created_at`)  ='".$year."' 
                GROUP BY MONTH(oi.`item_created_at`) ORDER BY month ASC";

          return yii::$app->db->createCommand($sql)->queryAll();

    }

    public static function saveMonthlySale($sales,$channel_id)
    {
        if($sales && is_array($sales))
        {
            $command = yii::$app->db->createCommand();
             $command->delete('channles_targets', ['channel_id' =>$channel_id])->execute();
            foreach($sales as $k=>$val)
            {
              $command->insert('channles_targets',array('channel_id'=>$val['channel_id'],'year'=>$val['year'],'month'=>$val['month'],'target'=>$val['total_sales']))->execute();
            }
        }

    }
    public static function getDelieveredOrdersData($client='philips')
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $orders_date = Date("Y-m-d",strtotime('-5 days'));
        if($client=="philips")
                $payment_method="'Credit' AS 'Payment Method'";
        else
            $payment_method="`o`.`payment_method` AS 'Payment Method'";

        $ordersQuery = "SELECT 
        'website' AS 'Primary Division',
        'SMS' AS 'Medium',
        'B2C E-commerce' AS 'Feedback Group',
        AES_DECRYPT(ca.billing_fname,'$encryptionKey') AS 'Contact Person',
       /* IF (POSITION('0' IN AES_DECRYPT(ca.billing_number,'$encryptionKey')) = 1,
        CONCAT('6','',AES_DECRYPT(ca.billing_number,'$encryptionKey')),
        CONCAT('60','',AES_DECRYPT(ca.billing_number,'$encryptionKey'))
        ) AS 'Contact Number',*/
         AES_DECRYPT(ca.billing_number,'$encryptionKey')  AS 'Contact Number',
         AES_DECRYPT(ca.billing_email,'$encryptionKey')  AS 'Email',
         AES_DECRYPT(ca.billing_city,'$encryptionKey') AS 'Secondary Division',
        o.order_number AS 'Invoice ID',
        REPLACE(o.order_total,',','') AS 'Total',
        DATE_FORMAT(oi.item_updated_at,'%Y-%m-%d') AS 'Transaction Timestamp',
        $payment_method,
        p.sku AS 'Item Code',
        p.name AS 'Item Name',
        REPLACE(p.rccp,',','') AS 'Item Price',
        oi.paid_price as 'Paid Price',
        ch.name AS 'Item Category',
        oi.quantity AS 'Item Quantity'
        FROM orders o
        INNER JOIN order_items oi ON
        o.id = oi.order_id
        INNER JOIN channels c ON 
        c.id = o.channel_id
        INNER JOIN customers_address ca ON
        ca.order_id = o.id
        INNER JOIN products p ON
        p.id = oi.sku_id
        LEFT JOIN category ch
        ON p.sub_category = ch.id
        WHERE 1=1
        AND (
        (oi.item_updated_at LIKE '$orders_date%') 
        )
        AND (oi.item_status IN ('delivered','completed','shipped'))
        AND AES_DECRYPT(ca.billing_fname,'$encryptionKey') IS Not NULL
        AND AES_DECRYPT(ca.billing_number,'$encryptionKey') IS Not NULL
        GROUP BY oi.id
        ORDER BY o.order_number,ca.id";
       // echo $ordersQuery ; die();
        $ordersList = Yii::$app->db->createCommand($ordersQuery)->queryAll();
        return ['orders' => $ordersList];
    }

    /*
     * csv export
     */
    public static function export_customers_csv($record)
    {
        $reportDate = Date("Y-m-d",strtotime('-5 days'));
        $list=[];
        $header=true;
        foreach($record['orders'] as $k=>$item)
        {
            $contact_number=self::format_contact_number($item['Contact Number']);
            if($header){
                $list[]=['Primary Division','Medium' ,'Feedback Group','Contact Person','Contact Number','Email',
                    'Invoice City','Invoice ID','Total','Transaction Timestamp','Payment Method','Item Code'
                    ,'Item Name','Item Price','Paid Price','Item Category','Item Quantity'];
            }
            $list[]=[$item['Primary Division'],$item['Medium'] ,$item['Feedback Group'],$item['Contact Person'],$contact_number,$item['Email'],
                $item['Secondary Division'],$item['Invoice ID'],$item['Total'],$item['Transaction Timestamp'],$item['Payment Method'],$item['Item Code'],
                $item['Item Name'],$item['Item Price'],$item['Paid Price'],$item['Item Category'],$item['Item Quantity']];
            $header=false;
        }
        $file_name='delievered_orders_customer_'. $reportDate .'.csv';
        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);
        $fp = fopen('csv/'.$file_name, 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);
        $filepath='csv/'.$file_name;

        //mail send code
        echo Yii::$app->mailer->compose('@common/mail/layouts/philips_customer_data_ao_marketing')
            ->attach($filepath)
            ->setFrom('notifications@ezcommerce.io')
            ->setTo('data@mail.sentimeter.io')
            ->setCc(['mujtaba.kiani@axleolio.com','ahmed.qasim@arbisoft.com','muhammad.asim@arbisoft.com','mbilalkhan44@gmail.com'])
            ->setSubject('ezcommerce | B2C E-Commerce | send survey invites - '. $reportDate)
            ->send();

        //delete file from server after send email
        if(file_exists($filepath)) {
//            header('Content-Description: File Transfer');
//            header('Content-Type: application/octet-stream');
//            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
//            header('Expires: 0');
//            header('Cache-Control: must-revalidate');
//            header('Pragma: public');
//            header('Content-Length: ' . filesize($filepath));
//            flush(); // Flush system output buffer
//            readfile($filepath);
            unlink($filepath);
        }
    }

    public static function format_contact_number($phone)
    {
        $p= substr($phone, 0, 2);

        if($p=="+9") //+92300xxxxxxx
            return $phone;
        elseif($p=="92") //92300xxxxxxx
            return $phone;

        return "92".ltrim($phone,"0"); // 0300xxxxxxx =>92300xxxxxxx
    }

    public static function KillQuery(){
        $connection = \Yii::$app->db;
        $sql = "SHOW FULL PROCESSLIST";
        $command = $connection->createCommand($sql)->queryAll();
        foreach ( $command as $val ){
            $process_id=$val["Id"];
            if ($val["Time"] > 100) {
                $KillQuerysql="KILL $process_id";
                $connection->createCommand($KillQuerysql);
            }
        }
        $connection = \Yii::$app->db;
        $sql = "SHOW FULL PROCESSLIST";
        $command = $connection->createCommand($sql)->queryAll();
        return $command;
    }
    public static function LogClaimsLazada(){

        $mp='lazada';
        $channels = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => $mp])->all();
        //self::debug($channels);
        foreach ($channels as $k => $v) {
            $v->marketplace = ucwords($v->marketplace);
            $ordersFunc = "fetch{$v->marketplace}Orders";

            if ($v->marketplace == 'Lazada') {
                $orders = CronUtil::GetSales($v->id);
                //self::debug($orders);
                $params = [];

                foreach ($orders as $o) {
                    $params['order_id'] = $o['order_id'];

                    $resp = LazadaUtil::GetOrderDetail($params['order_id'],$v);
                    $resp = json_encode($resp);
                    $resp = json_decode($resp,true);

                    $t1 = StrToTime ( $resp['data']['updated_at'] );
                    $t2 = StrToTime ( $resp['data']['created_at'] );
                    $diff = $t1 - $t2;
                    $hours = $diff / ( 60 * 60 );
                    $statuses = [
                        'delivered',
                        'shipped',
                        'pending',
                        'ready_to_ship'
                    ];
                    if (  $hours >= 4  && (!in_array($resp['data']['statuses'][0],$statuses) || count($resp['data']['statuses']) > 1) )
                    {
                        $orderItems = OrderItems::find()->where(['order_id' => $o['id']])->asArray()->all();

                        foreach ($orderItems as $oi) {
                            $params['OrderId'] = $o['order_id'];
                            $response = LazadaUtil::GetOrderItems($o['order_id'],$v->id);
                            $response = json_encode($response);
                            $response = json_decode($response,true);

                            foreach ( $response['data'] as $key=>$value ){
                                $FindItemClaim=ClaimItems::find()->where(['order_item_id'=>$value['order_item_id']])->asArray()->all();
                                if (empty($FindItemClaim)){

                                    $AddClaimItem=new ClaimItems();
                                    $AddClaimItem->order_id=$o['order_id'];
                                    $AddClaimItem->order_item_id= (string) $value['order_item_id'];
                                    $AddClaimItem->order_status=implode(',',$resp['data']['statuses']);
                                    $AddClaimItem->order_item_status=$value['status'];
                                    $AddClaimItem->reason=$value['reason'];
                                    if ( $value['shipping_type']=='Dropshipping' )
                                        $ship_type='ISIS';
                                    elseif ( $value['shipping_type']=='Own Warehouse' )
                                        $ship_type='FBL';
                                    $AddClaimItem->shipping_type=$ship_type;
                                    $AddClaimItem->channel_id=$v->id;
                                    $AddClaimItem->marketplace='Lazada';
                                    $AddClaimItem->sku=$value['sku'];
                                    $initiator='';
                                    if (strpos($value['cancel_return_initiator'], 'internal') !== false)
                                        $initiator = 'Lazada';
                                    elseif (strpos($value['cancel_return_initiator'], 'seller') !== false)
                                        $initiator = 'Seller';
                                    elseif (strpos($value['cancel_return_initiator'], 'customer') !== false)
                                        $initiator = 'Customer';
                                    elseif (strpos($value['cancel_return_initiator'], 'cancellation-failed Delivery') !== false)
                                        $initiator = 'Failed Delivery';
                                    $AddClaimItem->cancel_return_by=$initiator;
                                    $AddClaimItem->cancel_return_initiator=$value['cancel_return_initiator'];
                                    $AddClaimItem->order_item_created_at=$value['created_at'];
                                    $AddClaimItem->order_item_updated_at=$value['updated_at'];
                                    $AddClaimItem->created_at = time();
                                    $AddClaimItem->updated_at = time();
                                    $AddClaimItem->save();
                                    if (!empty($AddClaimItem->errors))
                                        self::debug($AddClaimItem->errors);
                                }else{
                                    $UpdateClaimItem = ClaimItems::findOne($FindItemClaim[0]['id']);
                                    $UpdateClaimItem->order_status=implode(',',$resp['data']['statuses']);
                                    $UpdateClaimItem->order_item_status=$value['status'];
                                    $UpdateClaimItem->reason=$value['reason'];
                                    if ( $value['shipping_type']=='Dropshipping' )
                                        $ship_type='ISIS';
                                    elseif ( $value['shipping_type']=='Own Warehouse' )
                                        $ship_type='FBL';
                                    $UpdateClaimItem->shipping_type=$ship_type;
                                    $UpdateClaimItem->channel_id=$v->id;
                                    $UpdateClaimItem->sku=$value['sku'];
                                    $UpdateClaimItem->marketplace='Lazada';
                                    $initiator='';
                                    if (strpos($value['cancel_return_initiator'], 'internal') !== false)
                                        $initiator = 'Lazada';
                                    elseif (strpos($value['cancel_return_initiator'], 'seller') !== false)
                                        $initiator = 'Seller';
                                    elseif (strpos($value['cancel_return_initiator'], 'customer') !== false)
                                        $initiator = 'Customer';
                                    elseif (strpos($value['cancel_return_initiator'], 'cancellation-failed Delivery') !== false)
                                        $initiator = 'Failed Delivery';
                                    $UpdateClaimItem->cancel_return_by=$initiator;
                                    $UpdateClaimItem->cancel_return_initiator=$value['cancel_return_initiator'];
                                    $UpdateClaimItem->order_item_created_at=$value['created_at'];
                                    $UpdateClaimItem->order_item_updated_at=$value['updated_at'];
                                    $UpdateClaimItem->updated_at = time();
                                    $UpdateClaimItem->update();
                                }
                            }
                        }
                    }


                }
            }
        }
        $GetAllClaims=ClaimItems::find()->where(['marketplace'=>'lazada'])->asArray()->all();
        //self::debug($GetAllClaims);
        foreach ($GetAllClaims as $key=>$value){
            $color='';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Sourcing payment issue' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of Delivery Address' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change payment method' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Out of Stock' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Decided for alternative product' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Fees-shipping costs' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Seller' && $value['reason']=='Out of Stock' )
                $color='Green';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Sourcing payment issue' )
                $color='Green';


            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Lost Item' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Breach-Delivery promise date' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Damaged by 3PL' )
                $color='Red';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Delivery time is too long' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Defective' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Damaged' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Missing accessories' )
                $color='Red';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='System Error' )
                $color='Red';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Wrong size' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Lost Item' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Damaged by 3PL' )
                $color='Red';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Breach-Delivery promise date' )
                $color='Red';


            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Sourcing Delay (cannot meet deadline)' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Delivery address is wrong' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Lazada' && $value['reason']=='Customer Not At Home or Not Reachable' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of mind' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Customer' && $value['reason']=='Found cheaper elsewhere' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Wrong item' )
                $color='Orange';
            if ( $value['order_item_status']=='canceled' && $value['cancel_return_by']=='Lazada' && $value['reason']=='cancel' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Change of mind' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Customer Not At Home or Not Reachable' )
                $color='Orange';
            if ( $value['order_item_status']=='failed' && $value['cancel_return_by']=='Failed Delivery' && $value['reason']=='Delivery address is wrong' )
                $color='Orange';
            if ( $value['order_item_status']=='returned' && $value['cancel_return_by']=='Customer' && $value['reason']=='Not as advertised' )
                $color='Orange';


            $UpdateClaimColor=ClaimItems::findOne($value['id']);
            $UpdateClaimColor->claim_category=$color;
            $UpdateClaimColor->update();
            if ( !empty($UpdateClaimColor->errors) )
                self::debug($UpdateClaimColor->errors);

        }
    }
    public static function LogClaimsShopee(){

        $mp='shopee';
        $channels = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => $mp])->all();
        foreach ($channels as $k => $v) {
            $v->marketplace = ucwords($v->marketplace);
            if ($v->marketplace == 'Shopee') {
                $orders = CronUtil::GetSales($v->id);
                $params = [];

                foreach ($orders as $o) {
                    $params['order_id'] = $o['order_id'];
                    $resp = ShopeeUtil::GetOrderDetails($v,$params['order_id']);
                    $resp = json_encode($resp);
                    $resp = json_decode($resp,true);
                    if (!isset($resp['orders'][0]['update_time'])){
                        continue;
                    }
                    $t1 =  ( $resp['orders'][0]['update_time'] );
                    $t2 =  ( $resp['orders'][0]['create_time'] );
                    $diff = $t1 - $t2;
                    $hours = $diff / ( 60 * 60 );
                    $statuses = [
                        'COMPLETED',
                        'TO_CONFIRM_RECEIVE',
                        'SHIPPED',
                        'READY_TO_SHIP',
                        'RETRY_SHIP'
                    ];

                    if (  $hours >= 4  && (!in_array($resp['orders'][0]['order_status'],$statuses) ) )
                    {
                        foreach ($resp['orders'][0]['items'] as $value) {
                            $FindItemClaim=ClaimItems::find()->where(['order_item_id'=>$value['item_id']])->asArray()->all();
                            if (empty($FindItemClaim)){
                                $AddClaimItem=new ClaimItems();
                                //$AddClaimItem->order_no = $o['id'];
                                $AddClaimItem->order_id=$o['order_id'];
                                $AddClaimItem->order_item_id=(string) $value['item_id'];
                                $AddClaimItem->order_status=$resp['orders'][0]['order_status'];
                                $AddClaimItem->shipping_type=$resp['orders'][0]['shipping_carrier'];
                                $AddClaimItem->reason='-';
                                $AddClaimItem->channel_id=$v->id;
                                $AddClaimItem->marketplace='Shopee';
                                $AddClaimItem->sku=$value['item_sku'];
                                $AddClaimItem->order_item_created_at=date('Y-m-d h:i:s',$resp['orders'][0]['create_time']);
                                $AddClaimItem->order_item_updated_at=date('Y-m-d h:i:s',$resp['orders'][0]['update_time']);
                                $AddClaimItem->created_at = time();
                                $AddClaimItem->updated_at = time();

                                $AddClaimItem->save();
                                if (!empty($AddClaimItem->errors))
                                    self::debug($AddClaimItem->errors);
                            }else{
                                $UpdateClaimItem = ClaimItems::findOne($FindItemClaim[0]['id']);
                                //$UpdateClaimItem->order_no = $o['id'];
                                $UpdateClaimItem->order_status=$resp['orders'][0]['order_status'];
                                $UpdateClaimItem->shipping_type=$resp['orders'][0]['shipping_carrier'];
                                $UpdateClaimItem->channel_id=$v->id;
                                $UpdateClaimItem->reason='-';
                                $UpdateClaimItem->sku=$value['item_sku'];
                                $UpdateClaimItem->marketplace='Shopee';
                                $UpdateClaimItem->order_item_created_at=date('Y-m-d h:i:s',$resp['orders'][0]['create_time']);
                                $UpdateClaimItem->order_item_updated_at=date('Y-m-d h:i:s',$resp['orders'][0]['update_time']);
                                $UpdateClaimItem->updated_at = time();
                                $UpdateClaimItem->update();
                            }
                            //die;
                        }
                    }
                }
                /*
                 * Update all the claims with the color statuses
                 *
                 * */
                ClaimItems::updateAll(array( 'claim_category' => 'Orange' ), ' marketplace = \'Shopee\'' );

            }
        }
    }
}