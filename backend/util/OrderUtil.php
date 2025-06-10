<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 8/21/2019
 * Time: 11:56 AM
 */

namespace backend\util;
use backend\controllers\ApiController;
use common\models\Channels;
use common\models\Couriers;
use common\models\OrderShipment;
use common\models\Products;
use common\models\ChannelsProducts;
use common\models\StockDepletionLog;
use common\models\ThirdPartyOrders;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use yii\db\Exception;
use common\models\CustomersAddress;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrdersCustomersAddress;
use common\models\Settings;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use backend\util\HelpUtil;
use backend\util\InventoryUtil;
use yii;

class OrderUtil
{
    private static $current_order_id;  //primary key db orders table
    private static $current_channel;
    private static $update_stock=false;  // check if adding or updating order items need to update stock
    private static $additional_params;  // you can send additional params variable and assign to it , then can apply checks
    public function __construct()
    {
        date_default_timezone_set(Yii::$app->params['current_time_zone']); // reset to store local db dates
    }

    /*
     * map marketplace order statuses with our own , pending, canceled, shipped,completed
     */
    public static function mapOrderStatus($order_status)
    {
        $shipped_orders = ['shipped','to_confirm_received', 'approved'];
        $completed_orders = ['delivered','completed','complete','fulfilled']; // fulfilled is for shopify
        $canceled_orders = ['cancelled', 'canceled','refunded','cancel','invalid','to_return', 'in_cancel', 'refund_paid','returned', 'missing orders', 'refunded', 'failed', 'reversed', 'delivery failed','failure', 'canceled by customer','payment_refused','payment refused'];
        $pending_orders = ['acknowledged','pending','unshipped','created','holded','awaiting check','awaiting paypal payment', 'payment accepted','processing in progress','remote payment accepted','payment error',
            'pending_payment','awaiting check payment','awaiting bank wire payment','amazon pay - authorized','unpaid','ready_to_ship', 'retry_ship','exchange','processing','ready_to_ship', 'require_review'];

        if(in_array(strtolower($order_status),$shipped_orders))
            return "shipped";
        if(in_array(strtolower($order_status),$completed_orders))
            return "completed";
        if(in_array(strtolower($order_status),$canceled_orders))
            return "canceled";
        if(in_array(strtolower($order_status),$pending_orders))
            return "pending";

        return $order_status;
    }
    public static function saveOrder($order_data = null)
    {
        if ($order_data) {

            self::$current_channel = $order_data['channel'];
            self::saveOrderDetail($order_data['order_detail']);  // save order detail
            self::saveOrderItems($order_data['items'], $order_data['customer']);        // save order items
            self::saveCustomer($order_data['customer']);     // save order customer detail
            self::$current_order_id=NULL; // set it to null again

        }
        return;
    }

    public static function saveOrderDetail($detail)
    {
        //self::debug(self::$current_channel);
        $encryptionKey = Settings::GetDataEncryptionKey();
        $orders = Orders::find()->where(['order_number' => $detail['order_no'], 'channel_id' => self::$current_channel->id])->one();
        //self::$current_channel_id=$detail['channel_id'];
        if (!$orders) {

            $orders = new Orders();
            $orders->channel_id = self::$current_channel->id;
            $orders->order_id = (string)$detail['order_id'];
            $orders->order_number = (string)$detail['order_no'];
            $orders->payment_method = $detail['payment_method'];
            $orders->order_total =round( $detail['order_total'],2);
            $orders->order_created_at = $detail['order_created_at'];
            if ( isset($detail['customer_type']) ){
                $orders->customer_type=$detail['customer_type']; // customer_type will only have B2B or B2C
            }
            if ( isset($detail['coupon_code']) && $detail['coupon_code']){
                $orders->coupon_code=$detail['coupon_code'];
            }
            if ( isset($detail['order_note']) && $detail['order_note'])
                $orders->order_note=$detail['order_note'];

            $orders->order_updated_at = $detail['order_updated_at'];
            $orders->order_status =self::mapOrderStatus($detail['order_status']) ; // marketplace status mapped with our statuses
            $orders->order_market_status = $detail['order_status'];  // original marketplace status
            $orders->order_count = $detail['total_items'];
            $orders->order_shipping_fee = $detail['order_shipping_fee'];
            $orders->order_discount = isset($detail['order_discount']) ? $detail['order_discount'] : 0;
            $orders->customer_fname = new \yii\db\Expression('AES_ENCRYPT("' . substr($detail['cust_fname'], 0, 49) . '","' . $encryptionKey . '")');//substr($s['customer_first_name'],0,49);
            $orders->customer_lname = substr($detail['cust_lname'], 0, 49);//new \yii\db\Expression('AES_ENCRYPT("'.substr($s['customer_last_name'],0,49).'","'.$encryptionKey.'")');
            $orders->full_response = $detail['full_response'];
            $orders->created_by = 33;  //cron job
            $orders->updated_by = 33;   //cron job
            if (!$orders->save(false))
                print_r($orders->getErrors());
            self::$current_order_id = $orders->id;
        } else {
            $orders->order_created_at = $detail['order_created_at'];
            $orders->order_updated_at = $detail['order_updated_at'];
            $orders->order_status = self::mapOrderStatus($detail['order_status']) ; // marketplace status mapped with our statuses
            $orders->order_market_status = $detail['order_status'];  // original marketplace status
            $orders->order_total = round( $detail['order_total'],2);
            if ( isset($detail['customer_type']) ){
                $orders->customer_type=$detail['customer_type'];// customer_type will only have B2B or B2C
            }
            if ( isset($detail['coupon_code']) && $detail['coupon_code']){
                $orders->coupon_code=$detail['coupon_code'];
            }
            if ( isset($detail['order_note']) && $detail['order_note'])
                $orders->order_note=$detail['order_note'];

            $orders->order_count = $detail['total_items'];
            $orders->order_shipping_fee = $detail['order_shipping_fee'];
            $orders->order_discount = isset($detail['order_discount']) ? $detail['order_discount'] : 0;
            $orders->is_update = 1;
            if (!$orders->save(false))
                print_r($orders->getErrors());
            self::$current_order_id = $orders->id;
        }
        return;
    }

    public static function saveOrderItems($item_list, $customer_info)
    {

        for ($i = 0; $i < count($item_list); $i++) {

            self::$update_stock = false;
            $mapped_status= self::mapOrderStatus((string)$item_list[$i]['item_status']); // marketplace status mapped with our statuses
            $items = OrderItems::find()->where(['order_id' => self::$current_order_id, 'item_sku' => $item_list[$i]['item_sku']])->one();
            //$sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$item_list[$i]['item_sku'],'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
            if (!$items) {

                $items = new OrderItems();
                $items->order_id = self::$current_order_id;
                $items->sku_id = $item_list[$i]['sku_id'];
                $items->order_item_id = $item_list[$i]['order_item_id'];
                $items->item_status =$mapped_status;
                $items->item_market_status = (string)$item_list[$i]['item_status']; // original marketplace status
                $items->shop_sku = $item_list[$i]['shop_sku'];
                $items->price = $item_list[$i]['price'];
                $items->paid_price = $item_list[$i]['paid_price'];
                $items->shipping_amount = $item_list[$i]['shipping_amount'];
                $items->item_discount = $item_list[$i]['item_discount'];
                $items->sub_total=$item_list[$i]['sub_total'];

                // map warehouse assigned to the order item
                $whid = (isset($item_list[$i]['fulfilled_by_warehouse'])) ? $item_list[$i]['fulfilled_by_warehouse'] : ''; // this index will only come in amazon
                $ItemWarehouse = WarehouseUtil::GetOrderItemWarehouse($item_list[$i]['item_sku'], $customer_info,self::$current_channel,$whid);
                $items->fulfilled_by_warehouse = $ItemWarehouse;
                // map warehouse assigned to the order item



                $items->item_created_at = $item_list[$i]['item_created_at'];
                $items->item_updated_at = $item_list[$i]['item_updated_at'];
                $items->quantity = $item_list[$i]['quantity'];
                if ( isset($item_list[$i]['tracking_no']) && $item_list[$i]['tracking_no']!=null ) {
                    $items->tracking_number = $item_list[$i]['tracking_no'];
                }
                if (isset($ItemWarehouse) && $ItemWarehouse!='') {
                    self::$update_stock = true;  // need to update stock
                }
                $items->item_tax = isset($item_list[$i]['item_tax']) ? $item_list[$i]['item_tax'] : 0.00;
                $items->item_sku = (string)$item_list[$i]['item_sku'];
                $items->full_response = $item_list[$i]['full_response'];
                $items->created_by = 33;  //cron job
                $items->updated_by = 33;   //cron job

                // Assign the default warehouse to the order item if it was not assigned on pre-conditions.
                if ( (!isset($items->fulfilled_by_warehouse) || $items->fulfilled_by_warehouse=='') && self::$current_channel->default_warehouse!='' ){
                    $items->fulfilled_by_warehouse = self::$current_channel->default_warehouse;
                }

                if (!$items->save(false))
                    print_r($items->getErrors());


                $newItem = 1;
            }
            else {

                $items->sku_id = $item_list[$i]['sku_id'];
                $items->order_item_id = $item_list[$i]['order_item_id'];
                $items->item_status = $mapped_status;   // marketplace status mapped with our statuses
                $items->item_market_status = (string)$item_list[$i]['item_status']; // original marketplace status
                $items->item_updated_at = $item_list[$i]['item_updated_at'];
                $items->item_created_at = $item_list[$i]['item_created_at'];
                $items->quantity = $item_list[$i]['quantity'];
                $items->item_tax = isset($item_list[$i]['item_tax']) ? $item_list[$i]['item_tax'] : 0.00;
                $items->sub_total=$item_list[$i]['sub_total'];
                $items->price = $item_list[$i]['price'];

                if ( isset($item_list[$i]['tracking_no']) && $item_list[$i]['tracking_no']!=null ){
                    $items->tracking_number = $item_list[$i]['tracking_no'];
                }

                $whid = (isset($item_list[$i]['fulfilled_by_warehouse'])) ? $item_list[$i]['fulfilled_by_warehouse'] : ''; // this index will only come in amazon
                $ItemWarehouse = WarehouseUtil::GetOrderItemWarehouse($item_list[$i]['item_sku'], $customer_info,self::$current_channel,$whid);
                if ( $ItemWarehouse!='' ){
                    $items->fulfilled_by_warehouse = $ItemWarehouse;
                }

                $items->paid_price = $item_list[$i]['paid_price'];
                $items->shipping_amount = $item_list[$i]['shipping_amount'];
                if (!$items->save(false))
                    print_r($items->getErrors());


                if (($items->quantity != $item_list[$i]['quantity']) && ($items->item_status != $mapped_status)) {
                    self::$update_stock = true;
                }
                $newItem = 0;
            }
            //die;
            //self::debug($ItemWarehouse);
            if ( self::$update_stock && $ItemWarehouse!='' ) {

                $warehouse = Warehouses::find()->where(['id'=>$ItemWarehouse])->one();
                if (in_array($warehouse->warehouse,['ezcom','ezcomm']) && $warehouse->stock_deplete){
                    self::DepleteStock(
                        [
                            'warehouse_id'=>$warehouse->id,
                            'channel_id' =>self::$current_channel['id'],
                            'sku' => $item_list[$i]['item_sku'],
                            'qty' => $item_list[$i]['quantity'],
                            'item_status' => $mapped_status,
                            'order_item_id' =>$items->id,// $item_list[$i]['order_item_id'],
                            'order_id_pk' => self::$current_order_id,
                            'new' => $newItem
                        ]
                    );
                }
            }
        }
    }

    public static function DepleteStock($data){

        self::DepleteWarehouseStock($data);

    }
    public static function DepleteWarehouseStock($data)
    {
        //self::debug($data);
        $updated=false;
        if ($data['new'])
        {
            $UpdateStock = WarehouseStockList::find()->where(['warehouse_id'=>$data['warehouse_id'],'sku'=>$data['sku']])->one();
            if(!$UpdateStock)
                return;

            $AvailableQty = $UpdateStock->available;
            $PendingQty = $UpdateStock->stock_in_pending;
          //  self::debug($WarehouseStocks);
           // $UpdateStock = WarehouseStockList::findOne($WarehouseStocks->id);
            if ($data['item_status']=='pending'){

                $Available = $AvailableQty - $data['qty'];
                $Pending   = $PendingQty   + $data['qty'];
                $UpdateStock->available = $Available;
                $UpdateStock->stock_in_pending = $Pending;
                $updated=true;

            }else if ( $data['item_status'] == 'completed' || $data['item_status'] == 'shipped' ){
                $Available = $AvailableQty - $data['qty'];
                $UpdateStock->available = $Available;
                $UpdateStock->updated_at=date('Y-m-d H:i:s');
                $updated=true;
            }


        }
        else{  // if item is updating not inserting
            //echo 'New not one';die;
            $GetLastLog = StockDepletionLog::find()->where(['warehouse_id'=>$data['warehouse_id'],'item_sku'=>$data['sku'],'order_id'=>$data['order_id_pk'],
                'order_item_id'=>$data['order_item_id']])->orderBy('id DESC')->one();
            //self::debug($GetLastLog);
            if(!$GetLastLog )
                return;

            if (  $GetLastLog && $data['item_status'] != $GetLastLog->status )
            {
                $UpdateStock = WarehouseStockList::find()->where(['warehouse_id'=>$data['warehouse_id'],'sku'=>$data['sku']])->one();
                if(!$UpdateStock)
                    return;

                $AvailableQty = $UpdateStock->available;
                $PendingQty = $UpdateStock->stock_in_pending;

               // $UpdateStock = WarehouseStockList::findOne($WarehouseStocks->id);
                if ( ($data['item_status'] == 'completed' || $data['item_status'] == 'shipped') && $GetLastLog->status=='canceled' ){
                    $UpdateStock->available = $AvailableQty - $data['qty'];
                    $updated=true;
                }
                else if ( ($GetLastLog->status=='completed' || $GetLastLog->status=='shipped') && $data['item_status'] == 'pending' ){
                    $Pending = $PendingQty + $data['qty'];
                    $UpdateStock->stock_in_pending = $Pending;
                    $updated=true;
                }
                else if ( $data['item_status']=='pending' ){
                    $Available = $AvailableQty - $data['qty'];
                    $Pending   = $PendingQty   + $data['qty'];
                    $UpdateStock->available = $Available;
                    $UpdateStock->stock_in_pending = $Pending;
                    $updated=true;
                }
                else if ( ($data['item_status'] == 'completed' || $data['item_status'] == 'shipped') && $GetLastLog->status=='pending'){
                    $Pending = $PendingQty - $data['qty'];
                    $UpdateStock->stock_in_pending = $Pending;
                    $updated=true;
                }
                else if ( $data['item_status'] == 'canceled' && ($GetLastLog->status=='completed' || $GetLastLog->status=='shipped') ){
                    $Available = $AvailableQty + $data['qty'];
                    $UpdateStock->available = $Available;
                    $updated=true;
                }
                else if ( $data['item_status'] == 'canceled' ){
                    $Pending = $PendingQty - $data['qty'];
                    $Available = $AvailableQty + $data['qty'];
                    $UpdateStock->available = $Available;
                    $UpdateStock->stock_in_pending = $Pending;
                    $updated=true;
                }
               /* $UpdateStock->update();

                $data['available']=$WarehouseStocks->available;
                $data['stock_in_pending']=$WarehouseStocks->stock_in_pending;
                return self::StockDepletionLog($data);*/
            }
        }
        if ($updated){
            $UpdateStock->updated_at=date('Y-m-d H:i:s');
            $UpdateStock->update();
            $data['available_before']=$AvailableQty; // stock before order
            $data['available_after']=$UpdateStock->available;  // stock after order
            $data['stock_in_pending_before_order']=$PendingQty;   // pending stock before order
            $data['stock_in_pending_after_order']=$UpdateStock->stock_in_pending;  // stock pending after order
            return  self::StockDepletionLog($data);
        }

    }
    public static function StockDepletionLog($data){

        $AddDepletionLog = new StockDepletionLog();
        $AddDepletionLog->warehouse_id = $data['warehouse_id'];
        $AddDepletionLog->item_sku = $data['sku'];
        $AddDepletionLog->order_id = $data['order_id_pk'];
        $AddDepletionLog->order_item_id = $data['order_item_id'];
        $AddDepletionLog->status = $data['item_status'];
        if ( $data['item_status']=='pending' )
            $stock = '-'.$data['qty'];
        elseif ($data['item_status']=='canceled')
            $stock = '+'.$data['qty'];
        elseif ( $data['item_status']=='shipped' || $data['item_status']=='completed' || $data['item_status']=='delivered' )
            $stock = '-'.$data['qty'];

        $AddDepletionLog->quantity = (isset($stock)) ? $stock : $data['qty'];
        $AddDepletionLog->stock_before = $data['available_before'];
        $AddDepletionLog->stock_after = $data['available_after'];
        $AddDepletionLog->stock_pending_before = $data['stock_in_pending_before_order'];
        $AddDepletionLog->stock_pending_after = $data['stock_in_pending_after_order'];
        $AddDepletionLog->added_at = date('Y-m-d H:i:s');
        $AddDepletionLog->type ='order';
        $AddDepletionLog->save(false);
        if ( $AddDepletionLog->errors ){
            return $AddDepletionLog->errors;
        }else{
            return 1;
        }
    }

    public static function saveCustomer($cust)
    {

        $ca = CustomersAddress::find()->where(['order_id' => self::$current_order_id])->one();
        if(!$ca)
            $ca = new CustomersAddress();

        if (gettype($cust['shipping_address']['state'])!='string'){
            $cust['shipping_address']['state'] = '-';
        }
        if (gettype($cust['billing_address']['state'])!='string'){
            $cust['billing_address']['state'] = '-';
        }

        $encryptionKey = Settings::GetDataEncryptionKey();
        $ca->shipping_fname = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['fname'] . '","' . $encryptionKey . '")');
        $ca->shipping_lname = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['lname'] . '","' . $encryptionKey . '")');
        $ca->shipping_number = isset($cust['shipping_address']['phone']) ? new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['phone'] . '","' . $encryptionKey . '")'):NULL;//self::phoneNumberChange($s['customer_contact']);
        if(isset($cust['shipping_address']['email']) && $cust['shipping_address']['email'])
         $ca->shipping_email = isset($cust['shipping_address']['email']) ? new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['email'] . '","' . $encryptionKey . '")'):NULL;

        $ca->shipping_address = new \yii\db\Expression('AES_ENCRYPT("' . addslashes($cust['shipping_address']['address']) . '","' . $encryptionKey . '")');
        $ca->shipping_state = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['state'] . '","' . $encryptionKey . '")');
        $ca->shipping_city = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['city'] . '","' . $encryptionKey . '")');
        $ca->shipping_country = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['country'] . '","' . $encryptionKey . '")');
        $ca->shipping_post_code = new \yii\db\Expression('AES_ENCRYPT("' . $cust['shipping_address']['postal_code'] . '","' . $encryptionKey . '")');
        $ca->billing_fname = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['fname'] . '","' . $encryptionKey . '")');
        $ca->billing_lname = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['lname'] . '","' . $encryptionKey . '")');
        $ca->billing_number = isset($cust['billing_address']['phone']) ? new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['phone'] . '","' . $encryptionKey . '")'):NULL;//self::phoneNumberChange($s['customer_contact']);
        if(isset($cust['billing_address']['email']) && $cust['billing_address']['email'])
            $ca->billing_email = isset($cust['billing_address']['email']) ? new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['email'] . '","' . $encryptionKey . '")'):NULL;

        $ca->billing_address = new \yii\db\Expression('AES_ENCRYPT("' . addslashes($cust['billing_address']['address']) . '","' . $encryptionKey . '")');
        $ca->billing_state = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['state'] . '","' . $encryptionKey . '")');
        $ca->billing_city = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['city'] . '","' . $encryptionKey . '")');
        $ca->billing_country = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['country'] . '","' . $encryptionKey . '")');
        $ca->billing_postal_code = new \yii\db\Expression('AES_ENCRYPT("' . $cust['billing_address']['postal_code'] . '","' . $encryptionKey . '")');
        $ca->order_id = self::$current_order_id;
        $ca->save(false);

        return;
    }


    ///update stock if order placed
    public static function update_stock($params = null)
    {

        return;
        if (isset($params['stock']) && is_int($params['stock']) && $params['sku'] != 'false' && isset($params['channel_id'])) {

            InventoryUtil::updateStock($params);  // warehouse table stock update
        }

    }

    public static function GetOrderItems($ChannelOrderId)
    {
        $orderIdPk = HelpUtil::exchange_values('order_number', 'id', $ChannelOrderId, 'orders');
        return OrderItems::find()->where(['order_id' => $orderIdPk])->asArray()->all();
    }

    public static function GetOrderItemDetail($orderItemPk)
    {
        $Sql = "SELECT * FROM order_items oi WHERE oi.id IN ($orderItemPk)";
        return OrderItems::findBySql($Sql)->asArray()->all();
    }

    public static function GetCustomerDetail($OrderId)
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $SQl = "SELECT 
                AES_DECRYPT(ca.shipping_fname, '" . $encryptionKey . "') as customer_fname,
                AES_DECRYPT(ca.shipping_lname, '" . $encryptionKey . "') as customer_lname,
                AES_DECRYPT(ca.shipping_state, '" . $encryptionKey . "') as customer_state,
                AES_DECRYPT(ca.shipping_city, '" . $encryptionKey . "') as customer_city,
                AES_DECRYPT(ca.shipping_post_code, '" . $encryptionKey . "') as customer_postcode,
                AES_DECRYPT(ca.shipping_address, '" . $encryptionKey . "') as customer_address,
                AES_DECRYPT(ca.shipping_country, '" . $encryptionKey . "') as customer_country,
                AES_DECRYPT(ca.shipping_number, '" . $encryptionKey . "') as customer_number,
                AES_DECRYPT(ca.shipping_email, '" . $encryptionKey . "') as shipping_email
                FROM orders o
                INNER JOIN customers_address ca ON
                o.id = ca.order_id
                WHERE o.order_number = '" . $OrderId . "';";

        return Orders::findBySql($SQl)->asArray()->all();
    }

    /*
     * by order id primary key of orders table
     */
    public static function GetCustomerDetailByPk($order_id)
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $SQl = "SELECT 
                AES_DECRYPT(ca.shipping_fname, '" . $encryptionKey . "') as customer_fname,
                AES_DECRYPT(ca.shipping_lname, '" . $encryptionKey . "') as customer_lname,
                AES_DECRYPT(ca.shipping_state, '" . $encryptionKey . "') as customer_state,
                AES_DECRYPT(ca.shipping_city, '" . $encryptionKey . "') as customer_city,
                AES_DECRYPT(ca.shipping_post_code, '" . $encryptionKey . "') as customer_postcode,
                AES_DECRYPT(ca.shipping_address, '" . $encryptionKey . "') as customer_address,
                AES_DECRYPT(ca.shipping_country, '" . $encryptionKey . "') as customer_country,
                AES_DECRYPT(ca.shipping_number, '" . $encryptionKey . "') as customer_number,
                AES_DECRYPT(ca.shipping_email, '" . $encryptionKey . "') as shipping_email,
                AES_DECRYPT(ca.billing_email, '" . $encryptionKey . "') as billing_email
                FROM 
                customers_address ca 
                 
                WHERE ca.order_id = '" . $order_id . "'";

        return Orders::findBySql($SQl)->asArray()->one();
    }

    public static function UpdateOrderStatus( $ChannelId, $OrderId )
    {

        // get all order items , get their different statuses in comma seperated and update it in to the main order status.

        $UpdateOrder = Orders::findone(['order_number'=>$OrderId,'channel_id'=>$ChannelId]);
        if(!$UpdateOrder)
            return;
        $GetOrderItems = OrderItems::find()->where(['order_id'=>$UpdateOrder->id])->asArray()->all();
        $status = [];
        foreach ( $GetOrderItems as $val ){
            $status[$val['item_status']] = $val['item_status'];
        }
        $status_in_line = implode(',',$status);

        //  $UpdateOrder = Orders::findOne($Orderid);
        $UpdateOrder->order_status = $status_in_line;
        $UpdateOrder->update();
        if ( $UpdateOrder->errors ){
            return 0;
        }else{
            // updated done
            return 1;
        }
    }

    public static function  updateOrderTrackingAndShippingStatus(array $order_item_pk, $label,$tracking_number,$courier_id,$status)
    {
        if($order_item_pk && $tracking_number): // order items table primary key & tracking nmbr
            return  Yii::$app->db->createCommand()
                ->update('order_items',
                    ['courier_id' => $courier_id,
                        'tracking_number'=>$tracking_number,
                        'shipping_label'=>$label,
                        'item_status'=>$status,
                    ],
                    ['IN','id' , $order_item_pk]
                )->execute();
        endif;
        return 0;
    }

    public static function UpdateOrderItemShippingLabels($FedExResponse, $Items, $CourierId, $package_weight){

        $FedEx = [];

        if ( gettype($FedExResponse)=='object' ){
            $FedEx[0] = $FedExResponse;
        }else{
            $FedEx = $FedExResponse;
        }
        //self::debug($FedEx);
        foreach ( $Items as $Key=>$value ){

            $CourierName = HelpUtil::exchange_values('id','name',$CourierId,'couriers');
            $Label = (count($package_weight) == 1) ? $FedEx[0]->Label->Parts->Image : $FedEx[$Key]->Label->Parts->Image;
            $UpdateItems = OrderItems::findOne($value['id']);
            $UpdateItems->shipping_label = '/shipping_labels/'.$CourierName.'-Item-'.$value['id'].'.pdf';
            $UpdateItems->item_status = 'shipped';
            $UpdateItems->courier_id = $CourierId;
            $UpdateItems->tracking_number = (count($package_weight) == 1) ? $FedEx[0]->TrackingIds->TrackingNumber : $FedEx[$Key]->TrackingIds->TrackingNumber;;
            $UpdateItems->update(false);

            self::CreateFile('../web/shipping_labels/'.$CourierName.'-Item-'.$value['id'].'.pdf',$Label);
        }

    }
    public static function UpdateShippingDetail( $OrderId, $ShippingLabel, $TrackingNumber, $Courier_Type ){

        $GetOrderItems = OrderItems::find()->where(['order_id'=>$OrderId])->asArray()->all();
        $Courier = Couriers::find()->where(['type'=>$Courier_Type])->one();

        $response = [];
        foreach ( $GetOrderItems as $Detail ){
            $UpdateOrderItem = OrderItems::findOne($Detail['id']);
            $UpdateOrderItem->tracking_number = $TrackingNumber;
            $UpdateOrderItem->shipping_label = $ShippingLabel;
            $UpdateOrderItem->courier_id = isset($Courier->id) ? $Courier->id : '';
            $UpdateOrderItem->update();
            if (empty($UpdateOrderItem->errors))
                $response[$Detail['id']] = 'Successfully Updated';
            else
                $response[$Detail['id']] = json_encode($UpdateOrderItem->errors);
        }
        return $response;
    }
    public static function CreateFile($Path, $Content){
        $myfile = fopen($Path, "w") or die("Unable to open file!");
        $txt = $Content;
        fwrite($myfile, $txt);
        fclose($myfile);
        chmod($Path, 0777);
    }
    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }
    public static function AssignOwnWarehouse($channelId, $warehouseType){

        $Sql = "SELECT w.id as warehouse_id FROM warehouse_channels wc
                    INNER JOIN warehouses w ON 
                    w.id = wc.warehouse_id
                    INNER JOIN channels c ON 
                    c.id = wc.channel_id
                    WHERE c.id = ".$channelId." AND w.warehouse = '".$warehouseType."'";
        $result=Yii::$app->db->createCommand($Sql)->queryall();
        if ( $result ){
            return $result[0]['warehouse_id'];
        }else{
            return '';
        }
    }
    public static function GetFulFilledBy($channel){
        if (in_array($channel->marketplace,['amazon','amazonspa'])){
            return self::AssignOwnWarehouse($channel->id,'amazon-fba');
        }
        elseif ($channel->marketplace=='lazada' ){
            return self::AssignOwnWarehouse($channel->id,'lazada-fbl');
        }
        else{
            return '';
        }
    }

    /**
     * @param null $channel_id
     * @return array|yii\db\ActiveRecord[]
     */
    public static function GetShippedItems($channel_id=null){
        $where="";
        if($channel_id)
        {
            $where =" AND c.id='".$channel_id."'";
        }
        $sql = "SELECT 
                o.id AS order_id_PK,
                o.payment_method,
                o.order_market_status,
                o.order_status,
                oi.id AS order_item_id_PK,
                oi.quantity AS order_item_qty,
                o.order_id AS channel_order_id,
                o.order_number AS channel_order_number,
                oi.order_item_id AS channel_order_item_id,
                os.id AS order_shipment_id,
                os.shipping_date,
                cour.name AS courier_name,
                cour.type AS courier_type,
                oi.tracking_number,
                oi.item_sku,
                cour.id AS courier_id,
                o.channel_id, c.marketplace,
                os.dimensions,
                os.amount_inc_taxes,
                os.amount_exc_taxes
                FROM orders o
                INNER JOIN order_items oi ON
                o.id = oi.order_id
                INNER JOIN order_shipment os ON
                os.order_item_id = oi.id
                LEFT JOIN couriers cour ON
                cour.id = oi.courier_id
                INNER JOIN channels c ON
                c.id = o.channel_id
                WHERE os.is_tracking_updated = 0  $where";
        $results = OrderShipment::findBySql($sql)->asArray()->all();
        return $results;
    }

    public static function GetInternalCourierShippedItems($channel_id=null,$order_item_pk=null){
        $where="";
        if($channel_id)
        {
            $where =" AND c.id='".$channel_id."'";
        }
        if($order_item_pk){
            $where =" AND os.order_item_id='".$order_item_pk."'";
        }
        $sql = "SELECT 
                o.id AS order_id_PK,
                oi.id AS order_item_id_PK,
                oi.quantity AS order_item_qty,
                o.order_id AS channel_order_id,
                o.order_number AS channel_order_number,
                oi.order_item_id AS channel_order_item_id,
                os.id AS order_shipment_id,
                os.shipping_date,
                cour.name AS courier_name,
                cour.type AS courier_type,
                oi.tracking_number,
                oi.item_sku,
                cour.id AS courier_id,
                o.channel_id, c.marketplace,
                os.dimensions,
                os.amount_inc_taxes,
                os.amount_exc_taxes
                FROM orders o
                INNER JOIN order_items oi ON
                o.id = oi.order_id
                INNER JOIN order_shipment os ON
                os.order_item_id = oi.id
                INNER JOIN couriers cour ON
                cour.id = oi.courier_id
                INNER JOIN channels c ON
                c.id = o.channel_id
                $where";
        $results = OrderShipment::findBySql($sql)->asArray()->all();
        return $results;
    }

    /***
     * @param $limit
     * @param $channel_id
     * get list of skus latest ordered
     */
    public static function getLastOrderedDistinctSkus($limit,$channel_id)
    {
      //  $now=gmdate('Y-m-d H:i:s',strtotime ( '-12 hour'));  // 12 hour minus
        $now=gmdate('Y-m-d H:i:s',strtotime ( '-1 day'));
      //  echo $now; die();
        $sql="SELECT DISTINCT(`p`.`sku`) as channel_sku,oi.item_updated_at 
              FROM
                `products` p
              INNER JOIN `order_items` oi 
               ON 
                oi.sku_id=p.id
               INNER JOIN `orders` o 
               ON o.id=oi.order_id
               WHERE 
                `o`.`channel_id`='".$channel_id."'
                AND
               oi.`item_updated_at` >= '$now'
              ORDER BY
                    oi.created_at DESC
               LIMIT 
                $limit
               ";
      // echo $sql; die();
        $results = yii::$app->db->createCommand($sql)->queryAll();
        return $results;
    }

    public static function getUniqueOrderCities()
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $sql="SELECT 
                DISTINCT(convert(AES_DECRYPT(shipping_city,'".$encryptionKey."' ) USING latin1)) as city
             FROM
                 customers_address WHERE 1";
        $results = yii::$app->db->createCommand($sql)->queryAll();
        return $results;
    }

    /************get orders of warehouse
     * $time_period_minute=>minutes
     *******/
    public static function getWarehouseOrders($warehouse_id,$time_period_minute=null,$limit=null,$offset=null)
    {
        $LIMIT=$OFFSET=$AND_WHERE="";
        if($limit)
            $LIMIT= " LIMIT $limit ";

        if($offset)
            $OFFSET= " OFFSET $offset ";

        if($time_period_minute && is_numeric($time_period_minute))  // in minutes
        {
            $current_time= gmdate('Y-m-d H:i:s');  //utc
            $from = gmdate("Y-m-d H:i:s", (time() -($time_period_minute * 60))); // 24 hours
            $AND_WHERE=" AND o.`order_updated_at` BETWEEN '".$from."' AND '".$current_time."' ";
           // $AND_WHERE=" AND o.`id` IN (5736,5740,5787,5791,5803,5813,5839,5852,5867,5880,5881,5884,5948,6012,6068,6123,6238)";

        }

        $encryptionKey =Settings::GetDataEncryptionKey();
        $query = "SELECT o.id AS order_id,o.order_number,o.order_created_at,o.order_updated_at,o.order_total,o.order_status,oi.item_sku,oi.quantity,oi.price,oi.sub_total,oi.paid_price,w.warehouse AS warehouse_type, w.id as warehouse_id,
                  AES_DECRYPT(ca.shipping_fname,'".$encryptionKey."') AS shipping_fname,
                  AES_DECRYPT(ca.shipping_lname,'".$encryptionKey."') AS shipping_lname,
                  AES_DECRYPT(ca.shipping_email,'".$encryptionKey."') AS shipping_email,
                  AES_DECRYPT(ca.shipping_number,'".$encryptionKey."') AS shipping_number,
                  AES_DECRYPT(ca.shipping_address,'".$encryptionKey."') AS shipping_address,
                  AES_DECRYPT(ca.shipping_state,'".$encryptionKey."') AS shipping_state,
                  AES_DECRYPT(ca.shipping_city,'".$encryptionKey."') AS shipping_city,
                  AES_DECRYPT(ca.shipping_post_code,'".$encryptionKey."') AS shipping_post_code,
                  AES_DECRYPT(ca.shipping_country,'".$encryptionKey."') AS shipping_country,
                  AES_DECRYPT(ca.billing_fname,'".$encryptionKey."') AS billing_fname,
                  AES_DECRYPT(ca.billing_lname,'".$encryptionKey."') AS billing_lname,
                  AES_DECRYPT(ca.billing_email,'".$encryptionKey."') AS billing_email,
                  AES_DECRYPT(ca.billing_number,'".$encryptionKey."') AS billing_number,
                  AES_DECRYPT(ca.billing_address,'".$encryptionKey."') AS billing_address,
                  AES_DECRYPT(ca.billing_state,'".$encryptionKey."') AS billing_state,
                  AES_DECRYPT(ca.billing_city,'".$encryptionKey."') AS billing_city,
                  AES_DECRYPT(ca.billing_postal_code,'".$encryptionKey."') AS billing_postal_code,
                  AES_DECRYPT(ca.billing_country,'".$encryptionKey."') AS billing_country
                  FROM orders o
                  INNER JOIN order_items oi 
                  ON   o.id = oi.order_id
                  INNER JOIN customers_address ca 
                  ON  ca.order_id = o.id
                   INNER JOIN warehouses w 
                   ON w.id = oi.fulfilled_by_warehouse
                   WHERE oi.fulfilled_by_Warehouse='".$warehouse_id."'
                    $AND_WHERE
                    ORDER BY o.order_id desc
                    $LIMIT $OFFSET";

        $results = yii::$app->db->createCommand($query)->queryAll();
        return self::arrange_orders($results);
    }

    public static function arrange_orders($orders=null)
    {

        $list=[];
        if($orders)
        {
            foreach($orders as $order)
            {
                $list[$order['order_id']]['detail']=['order_pk_id'=>$order['order_id'],
                                                    'order_status'=>$order['order_status'],
                                                    'order_total'=>$order['order_total'],
                                                    'order_number'=>$order['order_number'],
                                                    'order_created_at'=>$order['order_created_at'],
                                                    'order_updated_at'=>$order['order_updated_at'],
                                                    ];
                $list[$order['order_id']]['customer']=['shipping_fname'=>$order['shipping_fname'],
                                                        'shipping_lname'=>$order['shipping_lname'],
                                                        'shipping_email'=>$order['shipping_email'],
                                                        'shipping_phone'=>$order['shipping_number'],
                                                        'shipping_address'=>$order['shipping_address'],
                                                        'shipping_state'=>$order['shipping_state'],
                                                        'shipping_city'=>$order['shipping_city'],
                                                        'shipping_post_code'=>$order['shipping_post_code'],
                                                        'shipping_country'=>$order['shipping_country'],
                                                        'billing_fname'=>$order['billing_fname'],
                                                        'billing_lname'=>$order['billing_lname'],
                                                        'billing_email'=>$order['billing_email'],
                                                        'billing_phone'=>$order['billing_number'],
                                                        'billing_address'=>$order['billing_address'],
                                                        'billing_state'=>$order['billing_state'],
                                                        'billing_city'=>$order['billing_city'],
                                                        'billing_post_code'=>$order['billing_postal_code'],
                                                        'billing_country'=>$order['billing_country'],
                                                        ];
                $list[$order['order_id']]['order_items'][]=[
                                                            'item_sku'=>$order['item_sku'],
                                                            'quantity'=>$order['quantity'],
                                                            'price'=>$order['price'],
                                                            'paid_price'=>$order['paid_price'],
                                                            'sub_total'=>$order['sub_total'],
                                                            ];
            }
        }
        return $list;
        //self::debug($list);
    }

    public static function GetThirdPartyOrders(){
        // get third party warehouses
        $get_third_party_warehouses = Warehouses::find()->where(['warehouse'=>'magento-warehouse'])->asArray()->all();
        $warehouseids = [];
        foreach ( $get_third_party_warehouses as $value ){
            $warehouseids[] = $value['id'];
        }
        // get already pushed orders
        $get_already_pushed_orders = ThirdPartyOrders::find()->asArray()->all();
        $already_pushed_order_ids = [];
        foreach ($get_already_pushed_orders as $value){
            $already_pushed_order_ids[]=$value['order_id'];
        }

        $where='';
        if ($warehouseids){
            $where .= " AND oi.fulfilled_by_warehouse IN (".implode(',',$warehouseids).") ";
        }else{
            return ['status'=>0,'message'=>'There is no third party warehouse found'];
        }
        if ( $already_pushed_order_ids ){
            $where .= " AND o.id NOT IN (".implode(',',$already_pushed_order_ids).") ";
        }
        $query = "SELECT o.id AS order_id,oi.item_sku,oi.quantity,w.warehouse AS warehouse_type, w.id as warehouse_id,
                  AES_DECRYPT(ca.shipping_fname,'1bb8d713f296847dca58a883f8820d67') AS shipping_fname,
                  AES_DECRYPT(ca.shipping_lname,'1bb8d713f296847dca58a883f8820d67') AS shipping_lname,
                  AES_DECRYPT(ca.shipping_number,'1bb8d713f296847dca58a883f8820d67') AS shipping_number,
                  AES_DECRYPT(ca.shipping_address,'1bb8d713f296847dca58a883f8820d67') AS shipping_address,
                  AES_DECRYPT(ca.shipping_state,'1bb8d713f296847dca58a883f8820d67') AS shipping_state,
                  AES_DECRYPT(ca.shipping_city,'1bb8d713f296847dca58a883f8820d67') AS shipping_city,
                  AES_DECRYPT(ca.shipping_post_code,'1bb8d713f296847dca58a883f8820d67') AS shipping_post_code,
                  AES_DECRYPT(ca.shipping_country,'1bb8d713f296847dca58a883f8820d67') AS shipping_country,
                  AES_DECRYPT(ca.billing_fname,'1bb8d713f296847dca58a883f8820d67') AS billing_fname,
                  AES_DECRYPT(ca.billing_lname,'1bb8d713f296847dca58a883f8820d67') AS billing_lname,
                  AES_DECRYPT(ca.billing_number,'1bb8d713f296847dca58a883f8820d67') AS billing_number,
                  AES_DECRYPT(ca.billing_address,'1bb8d713f296847dca58a883f8820d67') AS billing_address,
                  AES_DECRYPT(ca.billing_state,'1bb8d713f296847dca58a883f8820d67') AS billing_state,
                  AES_DECRYPT(ca.billing_city,'1bb8d713f296847dca58a883f8820d67') AS billing_city,
                  AES_DECRYPT(ca.billing_postal_code,'1bb8d713f296847dca58a883f8820d67') AS billing_postal_code,
                  AES_DECRYPT(ca.billing_country,'1bb8d713f296847dca58a883f8820d67') AS billing_country
                  FROM orders o
                  INNER JOIN order_items oi ON 
                  o.id = oi.order_id
                  INNER JOIN customers_address ca ON 
                  ca.order_id = o.id
                   INNER JOIN warehouses w ON 
                   w.id = oi.fulfilled_by_warehouse
                  LEFT JOIN third_party_orders tpo ON
                  tpo.order_id = o.id
                  WHERE 1=1 ".$where;

        $results = yii::$app->db->createCommand($query)->queryAll();
        $ordersList=[];
        foreach ( $results as $value ){
            //$customer_detail=$value;

            $ordersList[$value['order_id']]['OrderDetail']['order_id'] = $value['order_id'];
            $ordersList[$value['order_id']]['OrderDetail']['warehouse-type'] = $value['warehouse_type'];
            $ordersList[$value['order_id']]['OrderDetail']['warehouse_id'] = $value['warehouse_id'];

            $ordersList[$value['order_id']]['CustomerInformation']['shipping_fname']=$value['shipping_fname'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_lname']=$value['shipping_lname'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_number']=$value['shipping_number'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_address']=$value['shipping_address'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_state']=$value['shipping_state'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_city']=$value['shipping_city'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_post_code']=$value['shipping_post_code'];
            $ordersList[$value['order_id']]['CustomerInformation']['shipping_country']=$value['shipping_country'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_fname']=$value['billing_fname'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_lname']=$value['billing_lname'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_number']=$value['billing_number'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_address']=$value['billing_address'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_state']=$value['billing_state'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_city']=$value['billing_city'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_postal_code']=$value['billing_postal_code'];
            $ordersList[$value['order_id']]['CustomerInformation']['billing_country']=$value['billing_country'];

            $orderItems=[];
            $orderItems['sku']=$value['item_sku'];
            $orderItems['qty']=$value['quantity'];
            $ordersList[$value['order_id']]['OrderItems'][] = $orderItems;

        }
        return $ordersList;
    }
    public static function GetThirdPartyPushedOrders()
    {
        $sql = "SELECT o.id AS ezcomm_order_id, tpo.thirdparty_order_id,
                oi.item_sku, w.warehouse,w.id AS warehouse_id,
                w.settings AS warehouse_settings
                FROM orders o
                INNER JOIN third_party_orders tpo ON 
                tpo.order_id = o.id
                INNER JOIN order_items oi ON
                oi.order_id = o.id
                INNER JOIN warehouses w ON
                w.id = oi.fulfilled_by_warehouse
                WHERE (oi.item_status = 'pending' OR oi.item_status = 'shipped') AND w.warehouse = 'magento-warehouse';";
        $result=Yii::$app->db->createCommand($sql)->queryAll();
        return $result;
    }
    public static function UpdatePackingSlip( $orderItems, $slip_name ){
        $items = explode(',',$orderItems);
        foreach ( $items as $key=>$item_id ){
            $findOrderItemShipment = OrderShipment::findOne(['order_item_id'=>$item_id]);
            $findOrderItemShipment->packing_slip=$slip_name;
            $findOrderItemShipment->update();
        }
    }
}