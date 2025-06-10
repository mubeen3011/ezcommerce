<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 10/22/2020
 * Time: 4:34 PM
 */
namespace backend\util;
use common\models\OrderShipment;

class BackmarketUtil{

    private static $current_channel=null;

    private static function debug($data)
    {
        echo "<pre>";
        print_r($data);
        die();
    }

    private static function order_status_translation($status_id)
    {
        $stuses=[
            0=>'pending_payment',   //New `Order`. Payment validation is pending. We are doing verifications to check the customer identity. The `Orders` in this `State` must not be shipped.
            1=>'payment accepted',  //Payment is checked & validated. The merchant has to process (accept or cancel) the `Orderlines`.
            3=>'ready_to_ship',  //`Order` shipping is pending. The payment and the `Order` (all `Orderlines`) have been validated. The merchant has to ship the package to the customer.
            8=>'canceled',  //Order is not paid. Payment process has failed, the merchant must ignore this `Order`.
            9=>'shipped',  //Order processed. The merchant has shipped the package.
        ];
        return isset($statuses[$status_id]) ? $statuses[$status_id]:"pending";
    }

    private static function order_item_status_translation($status_id)
    {
            $statuses=[
                0=>'pending_payment', // New `Orderline`. The merchant has to wait for payment confirmation.
                1=>'payment accepted', // `Orderline` is paid. The merchant has received the payment and must validate or cancel the `Orderline`
                2=>'ready_to_ship', // `Orderline` is accepted by the merchant, who must now prepare the `Product` for shipment.
                3=>'shipped', // The merchant has deliver the `Orderline` to the shipping company. The package delivery is in progress.
                4=>'canceled', // `Orderline` is cancelled. The customer will be refunded for the `Orderline`.
                5=>'refunded', // Orderline is refunded before shipping.
                6=>'refunded', // Orderline is refunded after shipping. The customer made a refund request.
                7=>'payment_refused', // Orderline is not paid. The payment has been refused by the bank.
            ];
        return isset($statuses[$status_id]) ? $statuses[$status_id]:"pending";
    }

    private static function set_config($channel=null)
    {
        if($channel && !self::$current_channel){
            self::$current_channel=$channel;
        }
    }

    private static function makePostApiCall($request_url,$body_enclosed,$headers)
    {
       // echo $_SERVER['HTTP_USER_AGENT']; die();
      //  echo $body_enclosed; die();
        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $body_enclosed);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); //bk
        $result = curl_exec($ch);
        return $result;  //json format

    }

    private static function get_header()
    {
        $headers=['Content-Type: application/json',
                  'Accept:application/json',
                  'Authorization: Basic '. self::$current_channel->api_key,
                  'Accept-Language: en-us ',
                 // 'user-agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.75 Safari/537.36'
                  'user-agent: ' .$_SERVER['HTTP_USER_AGENT'],
                 ];
        return $headers;
    }

    private static function makeGetApiCall($request_url,$headers)
    {
        $ch = curl_init($request_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        return $result;  //json format
    }

    public static function channelProducts($channel=null,$page=null)
    {
        ///https://www.backmarket.com  // for us live market
        /// https://preprod.backmarket.com  // for us test market

        self::set_config($channel);
        $api_url=self::$current_channel->api_url."/ws/listings/".$page;
        $headers=self::get_header();
        $products=self::makeGetApiCall($api_url,$headers);
        $products=json_decode($products);
       // self::debug($products);
        if((json_last_error() === JSON_ERROR_NONE) && isset($products->count) && $products->count && $products->results){
           self:: save_channel_products($products->results);
           if($products->next){ // if nect page exists
               $page_no= parse_url($products->next, PHP_URL_QUERY);// get page param
               self::channelProducts($channel,"?".$page_no);
           }
        }
        return;


    }

    private static function save_channel_products($list)
    {
        $cat=CatalogUtil::getdefaultParentCategory();
        foreach($list as $product)
        {
            $data=[
                'category_id'=>isset($cat->id) ? $cat->id:0,
                'sku'=>$product->listing_id,  // saving in channel_products table
                'channel_sku'=>trim($product->sku),  // sku for product table and channel_sku for channels products table
                'name'=>$product->title,
                'cost'=>isset($product->price) ? $product->price:0 ,
                'image'=>isset($product->image[0]) ? $product->image[0]:NULL,
                'rccp'=>isset($product->price) ? $product->price:0,
                'ean' => NULL,
                'stock_qty'=>$product->quantity,//self::getItemStock($product->sku),
                'channel_id'=>self::$current_channel->id,
                'is_live'=>$product->publication_state==2 ? 1:0 ,    // is active or is live
              //  'brand'=>strtolower($brand),     // brand,
            ];
            $product_id=CatalogUtil::saveProduct($data);  //save or update product and get product id
            if($product_id)
            {
                $data['product_id'] = $product_id;
                CatalogUtil::saveChannelProduct($data);  // save , update channel product

            }
        }
        return;
    }

    public static  function fetchOrdersApi($channel,$time_period,$page=null)
    {
       // echo date_default_timezone_get ( ); die();
        $filter=$page;
        self::set_config($channel);
        if ($time_period == "day") { // whole day 24 hours
            $start_date = date('Y-m-d H:i:s',(time()-(1440*60))); // for cron job will bring last 15 days orders// walmart not giving order update date
        } elseif($time_period == "chunk") {  // small period
            $start_date = date('Y-m-d H:i:s',(time()-(20*60))); // for cron job // after 20 min
        }

        if(isset($start_date) && $start_date)
        {
            if($filter)
                $filter .="&date_modification=".urlencode($start_date);
            else
                $filter="?date_modification=".urlencode($start_date);
        }
        $api_url=self::$current_channel->api_url."/ws/orders".$filter;
        $headers=self::get_header();
        $orders=self::makeGetApiCall($api_url,$headers);
      //  self::debug($orders);
        $orders=json_decode($orders);
       // self::debug($orders);
        if((json_last_error() === JSON_ERROR_NONE) && isset($orders->count) && $orders->count && $orders->results){
            self:: orderData($orders->results);
            if($orders->next){ // if next page exists
                $page_no= parse_url($orders->next, PHP_URL_QUERY);// get page param
                self::fetchOrdersApi($channel,$time_period,"?".$page_no);
            }
        }
    }

    private static function orderData($data=null)
    {
        foreach ($data as $order)
        {
            $order_data =[
                'order_id'=>$order->order_id,
                'order_no'=>$order->order_id,
                'channel_id'=>self::$current_channel->id,
                'payment_method'=>$order->payment_method ,
                'order_total'=>$order->price,   // excluded tax
                'order_created_at'=>gmdate('Y-m-d H:i:s',strtotime($order->date_creation)),  // utc time
                'order_updated_at'=>gmdate('Y-m-d H:i:s',strtotime($order->date_modification)), // utc
                'order_status'=>self::order_status_translation($order->state),
                'total_items'=>count($order->orderlines),
                'order_shipping_fee'=>$order->shipping_price,
                //'order_discount'=>,
                'cust_fname'=>isset($order->billing_address->first_name)? $order->billing_address->first_name:"",
                'cust_lname'=>isset($order->billing_address->last_name)? $order->billing_address->last_name:"",
                'full_response'=>'',//$apiresponse,
            ];

            $response=[
                'order_detail'=>$order_data,
                'items'=>self::orderItems($order),
                'customer'=>self::orderCustomerDetail($order),
                'channel'=>self::$current_channel, // need to get channel detail in orderutil
            ];
            //self::debug($response);
            OrderUtil::saveOrder($response);  // process data in db
        }
        return;
    }
    // order items
    private static function orderItems($order)
    {

        if(isset($order->orderlines) && !empty($order->orderlines))
        {
            foreach($order->orderlines as $item)
            {
                $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$item->listing_id,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
                $items[]=[
                    'sku_id'=>$sku_id,
                    //'sku_code'=>$val->product_reference, //sku number
                    'order_item_id'=>$item->id,
                    'item_status'=> self::order_item_status_translation($item->state),
                    'shop_sku'=>NULL,
                    'price'=>($item->price/$item->quantity),
                    'paid_price'=>($item->price/$item->quantity),
                    'item_tax'=>$item->sales_taxes,
                    'shipping_amount'=>$item->shipping_price,
                    'item_discount'=>0.00,
                    'sub_total'=>$item->price,
                    'item_updated_at'=>gmdate('Y-m-d H:i:s',strtotime($item->date_creation)),
                    'item_created_at'=>gmdate('Y-m-d H:i:s',strtotime($item->date_creation)),
                    'full_response'=>'',//json_encode($order_items),
                    'quantity'=>$item->quantity,
                    'item_sku'=>$item->listing,
                    //'fulfilled_by_warehouse' => '',
                   // 'stock_after_order' =>""
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
                    'fname'=>$data->billing_address->first_name,
                    'lname'=>$data->billing_address->last_name,
                    'address'=>$data->billing_address->street ." " . $data->billing_address->street2,
                    'phone'=>$data->billing_address->phone,
                    'email'=>$data->billing_address->email,
                    'state'=> $data->billing_address->state_or_province,
                    'city'=>$data->billing_address->city,
                    'country'=>$data->billing_address->country,
                    'postal_code'=>$data->billing_address->postal_code,
                ],
                'shipping_address'=>[
                    'fname'=>$data->shipping_address->first_name,
                    'lname'=>$data->shipping_address->last_name,
                    'address'=>$data->shipping_address->street . " " . $data->shipping_address->street2,
                    'phone'=>$data->shipping_address->phone,
                    'phone'=>$data->shipping_address->email,
                    'state'=> $data->shipping_address->state_or_province,
                    'city'=>$data->shipping_address->city,
                    'country'=>$data->shipping_address->country,
                    'postal_code'=>$data->shipping_address->postal_code ,
                ],

            ];

        endif;

    }

    public static function updateChannelStock($channel,$item)
    {
        if($channel && $item):
            self:: set_config($channel);
            $api_url=self::$current_channel->api_url."/ws/listings/".$item['sku'];
            $headers=self::get_header();
            $body=['quantity'=>$item['stock']];
            $res=self::makePostApiCall($api_url,json_encode($body),$headers);
            return $res;
        endif;
        return "Failed to update";
    }

    public static function updateChannelPrice($channel,$item)
    {
        if($channel && $item):
            self:: set_config($channel);
            $api_url=self::$current_channel->api_url."/ws/listings/".$item['sku'];
           // echo $api_url; die();
            $headers=self::get_header();
            $body=['price'=>number_format($item['price'], 2),'currency'=>'USD'];
           // self::debug($body);
            $res=self::makePostApiCall($api_url,json_encode($body),$headers);
            return json_decode($res);
        endif;
        return "Failed to update";
    }

    public static function updateShipmentAndTracking($channel,$orders)
    {
        if($channel && $orders):
            self:: set_config($channel);
            $response=[];
            foreach ($orders as $pk_id=>$order)
            {
                foreach($order['items'] as $order_item)
                {
                    for($i=2;$i<=3;$i++):  // change status first to 2(ready to ship , then 3 ship)
                    $body=['order_id'=>$order['marketplace_order_id'],
                            'new_state'=>$i, // ready to ship , it cannot be directly ship steps are 1 -> 2 , then 2->3 or 4 ,, when we get order and its pais its status is 1, then have to chnge it to 2 and then can 3 or 4
                            'sku'=>$order_item['item_sku'],
                            'tracking_number'=>$order_item['tracking_number']];  // in new_state=2 , this will not be updated , this will only update in stat3
                    $api_url=self::$current_channel->api_url."/ws/orders/".$order['marketplace_order_id'];
                    $headers=self::get_header();
                    $res=self::makePostApiCall($api_url,json_encode($body),$headers);
                    $response[$pk_id][]=json_decode($res);
                    endfor;
                }
                // self::debug($body);


            }

          //  self::debug($res);
            return $response;
        endif;
        return "Failed to update";
    }
    /**
     * update order shipment local databse if tracking updated , called from croncontroller
     */
    public static function updateOrderShipmentLocalDb($orders,$api_response,$tracking_update=true)
    {
        foreach($orders as $order_pk_id=>$order){
            foreach($order['items'] as $order_item)
            {
                $updateTracking = OrderShipment::findOne($order_item['order_shipment_id']);
                if($api_response[$order_pk_id] && $tracking_update){
                    $updateTracking->is_tracking_updated = 1;
                }

                $updateTracking->marketplace_tracking_response=isset($api_response[$order_pk_id]) ? json_encode($api_response[$order_pk_id]):null;
                $updateTracking->update();
            }
        }
        return;
    }
}