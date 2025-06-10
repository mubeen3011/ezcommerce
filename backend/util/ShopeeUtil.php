<?php


namespace backend\util;
use backend\controllers\MainController;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\Couriers;
use common\models\OrderItems;
use common\models\WarehouseStockList;
use yii;

class ShopeeUtil extends MainController
{
    private static $current_channel=null;
    private static $products_fetched=0; // for pagination of products fetched
    private static $orders_fetched=0; // for pagination of orders fetched
    private static $products_loop_executed=0;
    private static $db_log=[]; // to save log of update price / stock

    private static function make_api_request($url,$body,$method)
    {
       // $curl_url = sprintf("%s", $apiUrl);
        $header=self::encryptHash($url,$body);
        $curl = curl_init();
        curl_setopt_array($curl, array(
           // CURLOPT_PORT => $curl_port,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 6000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $header
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            echo "CURL Error #:" . $err . ' call from:' . $url;
        else
            return $response;

    }

    private static function encryptHash($url,$body)
    {
        $authKey = hash_hmac('sha256', $url . '|' . $body, self::$current_channel->api_key);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
        return $access;
    }

    private static function set_config($channel)
    {
        if(!$channel)
                return;

        self::$current_channel=$channel;
        return;

    }

    public static function generate_token()
    {
        //authorization and auth key generation is manual process
        //follow below steps and generated url should be hit in browser it will redirect to shoppe authrztion website input seller center credentials
        //and click on authorize , just then in db credentials change partner_id with the one provided by shopee
        $redirect_url="https://herbion.ezcommerce.io/api/generate-shopee-token";
        $partner_key="bdd361ced4cc6db2951ce76ca1cdd0d87d6dccef29ec6a4b11eea3f70a14f9e6"; // got to open.shope.com developer account // and get Live Key under app list
        $base_string= $partner_key.$redirect_url;
        $token=hash('sha256', $base_string);
        $url="https://partner.shopeemobile.com/api/v1/shop/auth_partner?id=846712&token=$token&redirect=$redirect_url";
        echo $url; die();

    }

    public static function UpdateShippingDetail( $OrderId, $ShippingLabel, $TrackingNumber, $CourierType ){

        $GetOrderItems = OrderItems::find()->where(['order_id'=>$OrderId])->asArray()->all();
        $Courier = Couriers::find()->where(['type'=>$CourierType])->one();

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

    public static function channelProducts($channel=null,$offset=0,$limit=100)
    {
        if(++self::$products_loop_executed > 30) // remove it if more than 3000 products
            die('overlooping ');

        self::set_config($channel);
        $url=self::$current_channel->api_url ."items/get";
        $fields = [
            'partner_id' => (int) json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => (int) json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'pagination_offset'=>$offset,
            'pagination_entries_per_page'=>$limit
        ];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        $response=json_decode($response);
        if ( isset($response->error) && $response->error!='' ){
            echo $response->msg;
            return;
        }

        if(isset($response->items))
        {
            foreach($response->items as $item)
            {
                    $record=self::get_product_detail($item->item_id); // get detail from api
                    $record=isset($record->item) ? $record->item:NULL;
                    $parent_sku_id= self::save_channel_products($record); // will return product id from products table
                      if($parent_sku_id && $record->variations)
                       {
                           foreach($record->variations as $variation)
                           {
                                $new_item=[
                                    'variation_id'=>$variation->variation_id,
                                    'item_sku'=>$variation->variation_sku,
                                    'price'=>$variation->price,
                                    'name'=>$record->name . " " .$variation->name,
                                    'stock'=>$variation->stock,
                                    'parent_sku_id'=>$parent_sku_id,
                                    'item_id'=>$record->item_id,
                                    'status'=>strtolower($variation->status)=="model_normal" ? 1:0,
                                ];

                               self::save_channel_products((object)$new_item);
                           }
                       }
            }
            self:: $products_fetched=self:: $products_fetched + $limit; // for offset
            //// check for pagination ///////////
            if($response->more)
            {
                self::channelProducts($channel ,self:: $products_fetched);
            }
            /// ////////////////////////////////
        }
        return;
    }

    private static function get_product_detail($item_id)
    {
        $url=self::$current_channel->api_url ."item/get";
        $fields = [
            'partner_id' => (int) json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => (int) json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'item_id'=>$item_id
        ];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        return json_decode($response);
        //echo $response; die();
    }


    private static function save_channel_products($data=null)
    {
        $cat=CatalogUtil::getdefaultParentCategory();
        if($data)
        {
            $prepare=[
                'category_id'=>isset($cat->id) ? $cat->id:0,
                'parent_sku_id'=>(isset($data->parent_sku_id) && ($data->parent_sku_id)) ? $data->parent_sku_id:NULL,
                'sku'=>$data->item_id,  // saving in channel_products table
                'channel_sku'=>$data->item_sku,  // sku for product table and channel_sku for channels products table
                'variation_id'=>isset($data->variation_id) ? $data->variation_id:NULL,
                'name'=>$data->name,
                'cost'=>$data->price,
                'ean' => NULL,
                'rccp'=>$data->price,
                'stock_qty'=>$data->stock,
                'channel_id'=>self::$current_channel->id,
                'is_live'=>strtolower($data->status)=='normal' ? 1:0 ,   // is active or is live
            ];
            $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id
            if($product_id)
            {
                $prepare['product_id'] = $product_id;
                CatalogUtil::saveChannelProduct($prepare);  // save , update channel product

            }
        }
        return $product_id;
    }
    public static function GetOrders($start_date,$end_date,$offset,$limit){
        $url=self::$current_channel->api_url ."orders/basics";
        $fields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'pagination_offset' => $offset,
            'pagination_entries_per_page' =>$limit,
            'update_time_from'=>$start_date,
            'update_time_to'=>$end_date
            /*'create_time_from'=>strtotime("-60 days"),
            'create_time_to'=>strtotime("-50 days")*/
        ];
       // self::debug($fields);
        $fields['partner_id'] = (int) $fields['partner_id'];
        $fields['shopid'] = (int) $fields['shopid'];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        $response=json_decode($response);
        return $response;
    }
    public static function GetAllOrders( $start_date, $end_date, $limit ){
        $response = self::GetOrders($start_date, $end_date,0, $limit);

        if (  isset($response->more) && $response->more  ){

            $offset=$limit;
            while( 1 ){
                $response_back = self::GetOrders($start_date, $end_date,$offset, $limit);
                if ( empty($response_back->orders) ){
                    break;
                }else{
                    foreach ( $response_back->orders as $key=>$value ){
                        $response->orders[] = $value;
                    }
                }
                $offset += $limit;
            }
        }

        return $response;
    }
    /**
     * orders fetching
     */

    public static  function fetchOrdersApi($channel,$time_period=null,$offset=0,$limit=50)
    {
        date_default_timezone_set('Asia/Kuala_Lumpur'); // shopee is just in malaysia
        self::set_config($channel);
        $start_date=strtotime('-15 days'); // maximum difference between start and end date shoul be 15 days in shopee
        //$start_date=strtotime('2020-06-03 00:00:00');
        if ($time_period == "day") { // whole day 24 hours
            $start_date = strtotime('-1 days'); // for cron job // after 24 hour //
        } elseif($time_period == "chunk") {  // small period
            $start_date = strtotime('-20 minutes');
        }

        $response = self::GetAllOrders($start_date,time(),$limit);
        //self::debug($response);
        if(isset($response->orders) && $response->orders)
        {
            $order_ids=[];
            $counter=0;
            foreach($response->orders as $order){
                $order_ids[$counter][]=$order->ordersn;
                if( count($order_ids[$counter])==50 ){
                    $counter++;
                }
            }

            $result=self::fetchOrderDetail($order_ids[0]);

            if ( count($order_ids) > 1 ){
                unset($order_ids[0]);
                foreach ( $order_ids as $key=>$orders_bunch ){
                    $result_offset=self::fetchOrderDetail($orders_bunch);
                    foreach ( $result_offset->orders as $k=>$v ){
                        $result->orders[] = $v;
                    }
                }
            }
           //self::debug($result);
           self::orderData($result); // save data

        }
    }

    private static function fetchOrderDetail($order_ids)
    {
        if($order_ids)
        {
            $url=self::$current_channel->api_url ."orders/detail";
            $fields = [
                'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
                'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
                'timestamp' => time(),
                'ordersn_list'=>$order_ids
            ];
            $fields['partner_id'] = (int) $fields['partner_id'];
            $fields['shopid'] = (int) $fields['shopid'];
            $fields = json_encode($fields);
            $response=self::make_api_request($url,$fields,"POST");
            return json_decode($response);
        }
        return;
    }

    private static function orderData($data=null)
    {
       // print_r($data); die();
        if(isset($data->orders) && is_array($data->orders))
        {
            //$response=[];
            foreach ($data->orders as $order)
            {
                $order_data =[
                    'order_id'=>$order->ordersn,
                    'order_no'=>$order->ordersn,
                    'channel_id'=>self::$current_channel->id,
                    'payment_method'=>$order->payment_method ,
                    'order_total'=>$order->total_amount,
                    'order_created_at'=>gmdate('Y-m-d H:i:s',($order->create_time)),  // utc time
                    'order_updated_at'=>gmdate('Y-m-d H:i:s',($order->update_time)),
                    'order_status'=>$order->order_status,
                    'total_items'=>count($order->items),
                    'order_shipping_fee'=>$order->actual_shipping_cost,
                    'order_discount'=>0,
                    'cust_fname'=>$order->buyer_username,
                    'cust_lname'=>'',
                    'full_response'=>'',//$apiresponse,
                ];
                $response=[
                    'order_detail'=>$order_data,
                    'items'=>self::orderItems($order),
                    'customer'=>self::orderCustomerDetail($order),
                    'channel'=>self::$current_channel, // need to get channel detail in orderutil
                ];

                OrderUtil::saveOrder($response);  // process data in db
            }
        }
        return;
    }

    // order items
    private static function orderItems($order=null)
    {
        $items=[];
        if(isset($order->items) && !empty($order->items))
        {
            foreach($order->items as $item)
            {
                   $item_sku=$item->item_sku ? $item->item_sku:$item->variation_sku;
                    $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$item_sku,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
                $qty=$item->variation_quantity_purchased;
                $paid_price=$item->variation_discounted_price;
                $items[]=[
                    'order_id'=> $order->ordersn,
                    'sku_id'=>(isset($sku_id) && $sku_id) ? $sku_id :"",
                    //'sku_code'=>$val->product_reference, //sku number
                    'order_item_id'=>$item->item_id,
                    'item_status'=>$order->order_status,
                    'shop_sku'=>'',
                    'price'=>$item->variation_discounted_price,
                    'paid_price'=>$paid_price,
                    'item_tax'=>0,
                    'shipping_amount'=>0,
                    'tracking_no' => (isset($order->tracking_no)) ? $order->tracking_no : null,
                    'item_discount'=>0,
                    'sub_total'=>($qty * $paid_price),
                    'item_updated_at'=>gmdate('Y-m-d H:i:s',($order->update_time)),
                    'item_created_at'=>gmdate('Y-m-d H:i:s',($order->create_time)),
                    'full_response'=>'',//json_encode($order_items),
                    'quantity'=>$qty,
                    'item_sku'=>$item_sku,
                    'stock_after_order' =>""
                ];
            }
        }
        return $items;
    }

    private static function orderCustomerDetail($data=null)
    {
        if($data):

            return [
                'billing_address'=>[
                    'fname'=>$data->recipient_address->name,
                    'lname'=>'',
                    'address'=>$data->recipient_address->full_address,
                    'phone'=>$data->recipient_address->phone ,
                    'state'=> $data->recipient_address->state,
                    'city'=>$data->recipient_address->city,
                    'country'=>$data->recipient_address->country,
                    'postal_code'=>$data->recipient_address->zipcode,
                ],
                'shipping_address'=>[
                    'fname'=>$data->recipient_address->name,
                    'lname'=>'',
                    'address'=>$data->recipient_address->full_address,
                    'phone'=>$data->recipient_address->phone,
                    'state'=> $data->recipient_address->state,
                    'city'=>$data->recipient_address->city,
                    'country'=>$data->recipient_address->country,
                    'postal_code'=>$data->recipient_address->zipcode ,
                ],

            ];

        endif;

    }

    // philips specific skus

    public static function updateGcStocks($channel){
        self:: set_config($channel);
        $gc_skus = [
            'GC2998/86A' => 'GC2998/86',
            'GC4933/80A' => 'GC4933/80',
            'GC3920/26A' => 'GC3920/26',
            'GC3929/66A' => 'GC3929/66',
            'GC4544/86A' => 'GC4544/86',
            'GC2670/26A' => 'GC2670/26'
        ];
        $stocklist=WarehouseStockList::find()->where(['warehouse_id'=>8])->asArray()->all();
        $main_items=[];
        //self::debug($stocklist);
        foreach ($stocklist as $value){
            if ( isset($gc_skus[$value['sku']]) ){
                //echo $value['sku'];die;
                $channel_product_sku = ChannelsProducts::find()->where(['channel_id'=>24,'channel_sku'=>$gc_skus[$value['sku']]])->asArray()->all();
                if ( $channel_product_sku ){
                    $available = ($value['available'] < 0) ? 0 : $value['available'];
                    $main_items[0][]=['item_id'=>(int)$channel_product_sku[0]['sku'],'stock'=>(int)$available];
                }
            }
        }
        if($main_items)
            return self::updateChannelStockAction($main_items,'items','items_stock');
    }
    // philips specific skus

    /*
     * update stock
     */

    public static function updateChannelStock($channel,$items,$unsync_skus)
    {
          //  echo "<pre>";
         //  print_r($unsync_skus); die();
        $variations=$main_items=[]; // has to update variations and main items seperately
        $variation_index=$main_index=0;
        $variation_counter=$main_counter=0;
        if($channel && $items)
        {
            self::set_config($channel);
            foreach($items as $item)
            {

                if (in_array($item['sku'],$unsync_skus)) // dont update excluded skus
                    continue;



                if(!empty($item['variation_id']))
                {
                    if(fmod($variation_counter,50)==0 && $variation_counter > 0)  // batch update 50 per index
                        $variation_index++;

                    $variations[$variation_index][]=['variation_id'=>(int)$item['variation_id'],'stock'=>(int)$item['stock'],'item_id'=>(int)$item['sku_id']];
                    $variation_counter++;
                }
                else
                {
                    if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                        $main_index++;

                    $main_items[$main_index][]=['item_id'=>(int)$item['sku_id'],'stock'=>(int)$item['stock']];
                    $main_counter++;
                }

            }

            if($main_items)
                self::updateChannelStockAction($main_items,'items','items_stock');
            if($variations)
                self::updateChannelStockAction($variations,'variations','vars_stock');

        }

        return self::$db_log;
    }

    private static function updateChannelStockAction($items,$update_type,$url_part)
    {
        if($items && $update_type)
        {
            $url=self::$current_channel->api_url ."items/update/".$url_part;
            foreach($items as $batch)
            {
                $fields = [
                    'partner_id' => (integer) json_decode(self::$current_channel->auth_params)->partner_id,
                    'shopid' => (integer) json_decode(self::$current_channel->auth_params)->shop_id,
                    'timestamp' => time()
                ];

                $fields[$update_type]=$batch;
                $fields = json_encode($fields);
                $response=self::make_api_request($url,$fields,"POST");
                // for dblog
                $list_log['bulk_sku_stock'][]=$batch;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('update_type'=>$update_type)),
                    'response'=>self::get_json_decode_format($response),
                ];
                //
            }
        }
        return;
    }
    /*
    * update price
    */
    public static function updateDealChannelPrice($channel,$items)
    {
//        self::debug($items);
        $variations=$main_items=[]; // has to update variations and main items seperately
        $variation_index=$main_index=0;
        $variation_counter=$main_counter=0;
        if($channel && $items)
        {
            self::set_config($channel);
            foreach($items as $item => $k)
            {

                /*if (in_array($item['sku'],$unsync_skus)) // dont update excluded skus
                    continue;*/

                if(!empty($item['variation_id']))
                {
                    if(fmod($variation_counter,50)==0 && $variation_counter > 0)  // batch update 50 per index
                        $variation_index++;

//                    $variations[$variation_index][]=['variation_id'=>(int)$item['variation_id'],'price'=>(float)$item['cost_price'],'item_id'=>(int)$item['channels_products_sku']];
                    $main_items[$variation_index][$item]=$k;
                    $variation_counter++;
                }

                else
                {
                    if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                        $main_index++;

                    $main_items[$main_index][$item]=$k;
//                    $main_items[$main_index][]=['item_id'=>(int)$item['channels_products_sku'],'price'=>(float)$item['cost_price']];
                    $main_counter++;
                }

            }
            //self::debug($main_items);
            if($main_items)
                self::updateChannelPriceAction($main_items,'items','items_price');
            if($variations)
                self::updateChannelPriceAction($variations,'variations','vars_price');

        }

        return self::$db_log;
    }
    public static function updateChannelPrice($channel,$items,$unsync_skus)
    {
        $variations=$main_items=[]; // has to update variations and main items seperately
        $variation_index=$main_index=0;
        $variation_counter=$main_counter=0;
        if($channel && $items)
        {
                self::set_config($channel);
                foreach($items as $item)
                {

                    if (in_array($item['sku'],$unsync_skus)) // dont update excluded skus
                        continue;

                    if(!empty($item['variation_id']))
                    {
                        if(fmod($variation_counter,50)==0 && $variation_counter > 0)  // batch update 50 per index
                            $variation_index++;

                        $variations[$variation_index][]=['variation_id'=>(int)$item['variation_id'],'price'=>(float)$item['cost_price'],'item_id'=>(int)$item['channels_products_sku']];
                        $variation_counter++;
                    }

                    else
                    {
                        if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                            $main_index++;

                        $main_items[$main_index][]=['item_id'=>(int)$item['channels_products_sku'],'price'=>(float)$item['cost_price']];
                        $main_counter++;
                    }

                }

                if($main_items)
                   self::updateChannelPriceAction($main_items,'items','items_price');
                if($variations)
                    self::updateChannelPriceAction($variations,'variations','vars_price');

        }

        return self::$db_log;
    }

    private static function updateChannelPriceAction($items,$update_type,$url_part)
    {
        if($items && $update_type)
        {
            $url=self::$current_channel->api_url ."items/update/".$url_part;

            foreach($items as $batch)
            {
                $partnerId = (integer) json_decode(self::$current_channel->auth_params)->partner_id;
                $shopId = (integer) json_decode(self::$current_channel->auth_params)->shop_id;
                $fields = [
                    'partner_id' => $partnerId,
                    'shopid' => $shopId,
                    'timestamp' => time()
                ];

                $fields[$update_type][]=$batch;
                //echo '<pre>';print_r($fields);die;
                $fields = json_encode($fields);
                $response=self::make_api_request($url,$fields,"POST");
                echo '<pre>';print_r($response);die;
                // for dblog
                $list_log['bulk_sku_price'][]=$batch;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('update_type'=>$update_type)),
                    'response'=>self::get_json_decode_format($response),
                ];
                //
            }
        }
        return;
    }

    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }
    public static function OrderDetail($channel, $order_ids){
        self::set_config($channel);
        $url=self::$current_channel->api_url ."orders/detail";
        $fields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn_list'=>$order_ids
        ];
        $fields['partner_id'] = (int) $fields['partner_id'];
        $fields['shopid'] = (int) $fields['shopid'];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        return $response;
    }

    public static function GetParameterForInit($channel, $order_id){
        self::set_config($channel);
        $url=self::$current_channel->api_url ."logistics/init_parameter/get";
        $fields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn'=>$order_id
        ];
        $fields['partner_id'] = (int) $fields['partner_id'];
        $fields['shopid'] = (int) $fields['shopid'];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        return $response;
    }
    public static function GetAddress($channel){
        self::set_config($channel);
        $url=self::$current_channel->api_url ."logistics/address/get";
        $fields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time()
        ];
        $fields['partner_id'] = (int) $fields['partner_id'];
        $fields['shopid'] = (int) $fields['shopid'];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        return $response;
    }
    public static function GetTimsSlot($channel,$order_id,$address_id){
        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/timeslot/get";
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn' => $order_id,
            'address_id' => $address_id
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;
    }
    public static function ShopeeInit($channel,$order_id,$Detail){
        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/init";
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn' => $order_id
        ];
        $postFields = array_merge($postFields, $Detail);
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];


        if (isset($postFields['dropoff']) && empty($postFields['dropoff']) && gettype($postFields['dropoff'])=='array'){
            // Replace the empty array with empty object - API requirement
            $postFields['dropoff'] = new \stdClass();
        }
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;
    }
    public static function GetLogistics($channel){
        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/channel/get";
        //$ApiDetails = self::ApiDetails($channel_id);
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;
    }
    public static function GetBranch($channel, $orderid){
        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/branch/get";
        //$ApiDetails = self::ApiDetails($channel_id);
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn' => $orderid
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;
    }
    public static function GetLogisticInfo($channel, $order_id){

        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/init_info/get";
        //$ApiDetails = self::ApiDetails($channel_id);
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn' => $order_id,
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;

    }
    public static function GetAirwayBill($channel,$order_id){
        self::set_config($channel);
        $url = self::$current_channel->api_url."logistics/airway_bill/get_mass";
        //$ApiDetails = self::ApiDetails($channel_id);
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn_list' => [$order_id],
            'is_batch' => false
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        return $response;
    }
    public static function GetOrderDetails($channel,$order_id){

        self::set_config($channel);
        $url=self::$current_channel->api_url ."orders/detail";
        $fields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'ordersn_list' => [$order_id]
        ];
        $fields['partner_id'] = (int) $fields['partner_id'];
        $fields['shopid'] = (int) $fields['shopid'];
        $fields = json_encode($fields);
        $response=self::make_api_request($url,$fields,"POST");
        $response=json_decode($response);
        return $response;
    }
    private static function ApiDetails($channel_id){
        $Channel = Channels::find()->where(['id'=>$channel_id,'is_active'=>'1'])->asArray()->all();
        $apiKey = $Channel[0]['api_key'];
        $apiUser = explode('|', $Channel[0]['api_user']);
        return ['partner_id'=>(int)$apiUser[0],'shop_id'=>(int)$apiUser[1],'api_key'=>$apiKey];
    }
    public static function GetDiscountsList( $channel, $discount_status ){
        self::set_config($channel);
        $url = self::$current_channel->api_url."discounts/get";
        //$ApiDetails = self::ApiDetails($channel_id);
        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
            'discount_status' => $discount_status,
            'pagination_offset' => 0,
            'pagination_entries_per_page' => 100,
        ];
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        //echo '<pre>';print_r(json_decode($response));die;
        return $response;
    }
    public static function updateDiscountPrice($channel,$items){
        self::set_config($channel);
        $url = self::$current_channel->api_url."discount/items/update";

        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
        ];
        $postFields = array_merge($postFields,$items);
        //echo '<pre>';print_r($postFields);die;
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
        $response=self::make_api_request($url,$postFields,"POST");
        //echo '<pre>';print_r(json_decode($response));die;
        return $response;
    }
    public static function delete_discount_item($channel,$items){
        self::set_config($channel);
        $url = self::$current_channel->api_url."discount/item/delete";

        $postFields = [
            'partner_id' => json_decode(self::$current_channel->auth_params)->partner_id,
            'shopid' => json_decode(self::$current_channel->auth_params)->shop_id,
            'timestamp' => time(),
        ];
        $postFields = array_merge($postFields,$items);
        //echo '<pre>';print_r($postFields);die;
        $postFields['partner_id'] = (int) $postFields['partner_id'];
        $postFields['shopid'] = (int) $postFields['shopid'];
        $postFields = json_encode($postFields);
       // echo $postFields; die();
        $response=self::make_api_request($url,$postFields,"POST");
        //echo '<pre>';print_r(json_decode($response));die;
        return $response;
    }
}