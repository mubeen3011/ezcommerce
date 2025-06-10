<?php


namespace backend\util;
use backend\controllers\MainController;
use yii;

class BigCommerceUtil extends MainController
{
    private static $current_channel = null;
    private static $products_fetched = 0; // for pagination of products fetched
    private static $orders_fetched = 0; // for pagination of oreders fetched
    private static $products_loop_executed = 0;
//    private static $current_page = 0;
    private static $db_log = []; // to save log of update price / stock

    private static function make_api_request($url, $body, $method, $header)
    {
//         $curl_url = sprintf("%s", $url);
//        self::debug($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $server_output = curl_exec($ch);
        $response = json_decode($server_output);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err)
            echo "CURL Error #:" . $err . ' call from:' . $url;
        else
            return $response;
    }


    private static function encryptHash($url, $body)
    {
        $authKey = hash_hmac('sha256', $url . '|' . $body, self::$current_channel->api_key);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
        return $access;
    }

    public function getHeader($channel)
    {
        $headers = [
            "X-Auth-Token:" . json_decode($channel->auth_params)->X_Auth_Token,
            "X-Auth-Client:" . json_decode($channel->auth_params)->X_Auth_Client,
            'Content-Type:application/json',
            'Accept:application/json'
        ];
        return $headers;
    }

    private static function set_config($channel)
    {
        if (!$channel)
            return;

        self::$current_channel = $channel;
        return;

    }

    private static function mapOrderStatus($order_status) // map if it is pending or delivered or cancelled
    {
        $shipped_orders = ['to_confirm_receive', 'requested', 'judging', 'processing', 'delivered', 'reversed', 'self collect', 'complete', 'completed'];
        $canceled_orders = ['unpaid', 'Cancelled', 'invalid', 'to_return', 'in_cancel', 'accepted', 'refund_paid', 'closed', 'seller_dispute', 'returned', 'reversed', 'missing orders', 'canceled', 'refunded', 'expired', 'failed', 'returned', 'reversed', 'delivery failed', 'canceled by customer'];
        $pending_orders = ['ready_to_ship', 'retry_ship', 'exchange', 'Pending', 'processed', 'processing', 'ready_to_ship', 'in transit'];


        if (in_array(strtolower($order_status), $shipped_orders))
            return "shipped";
        if (in_array(strtolower($order_status), $canceled_orders))
            return "canceled";
        if (in_array(strtolower($order_status), $pending_orders))
            return "pending";

        return $order_status;
    }

    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }

    public static function channelProducts($channel = null,$time_period = null)
    {
        date_default_timezone_set("America/New_York");
        self::set_config($channel);
        $apiResponse = self::getProductsListFromBigCommerceApi($channel,$time_period);
        if($apiResponse!= null){
            if(isset($apiResponse->data)){
                self::SaveBigCommerceProducts($apiResponse->data,$channel);
            }
            if($apiResponse->meta){
                $current_page = $apiResponse->meta->pagination->current_page;
                $total_pages = $apiResponse->meta->pagination->total_pages;
                if($total_pages > 1 && $current_page<= $total_pages){
                    for($page = $current_page+1; $page <= $total_pages; $page++)
                    {
                        $apiResponse = self::getProductsListFromBigCommerceApi($channel,$time_period,$page);
                        if($apiResponse!= null && isset($apiResponse->data)){
                            self::SaveBigCommerceProducts($apiResponse->data,$channel);
                        }
                    }
                    echo "All products inserted in multiple calls";
                    return;
                }
                else{
                    echo "All products inserted in single call";
                    return;
                }
            }
            else{
                echo "All products inserted in single call but meta info not found";
                return;
            }
        }
        else{
            echo "No product found";
            return;
        }

        return;
    }

    private static function getProductsListFromBigCommerceApi($channel,$time_period,$page_no=null){
        $currentDate = date('Y-m-d');
        if ($time_period == "day") {
            $start_date = date('Y-m-d', strtotime('-1 days'));
            $url =  (isset($page_no))
                ? self::$current_channel->api_url . "v3/catalog/products?date_modified:min=$start_date&date_modified:max=$currentDate&page=$page_no"
                : self::$current_channel->api_url . "v3/catalog/products?date_modified:min=$start_date&date_modified:max=$currentDate";

        }else if($time_period == "chunk") {
            $start_date = date('Y-m-d', strtotime('-60 minutes'));
            $url =  (isset($page_no))
                ? self::$current_channel->api_url . "v3/catalog/products?date_modified:min=$start_date&date_modified:max=$currentDate&page=$page_no"
                : self::$current_channel->api_url . "v3/catalog/products?date_modified:min=$start_date&date_modified:max=$currentDate";
        }else{
            $url =  (isset($page_no))
                ? self::$current_channel->api_url . "v3/catalog/products?page=$page_no"
                : self::$current_channel->api_url . "v3/catalog/products";
        }
        $headers = self::getHeader($channel);
        $body = '';
        $response = self::make_api_request($url, $body, "GET", $headers);
        return $response;
    }

    private static function SaveBigCommerceProducts($data,$channel){
        if (isset($data))
        {
            foreach ($data as $item)
            {
                $record = self::getProductDetail($channel, $item->id); // get detail from api
                $record = isset($record->data[0]) ? $record->data[0] : NULL;
                if ($record) {
                    $data_full = [
                        'parent_sku_id' => null,
                        'sku' => $record->id,
                        'channel_sku' => $record->sku,
                        'variation_id' => $record->option_set_id,
                        'name' => $record->name,
                        'ean' => $record->upc, //$data->mpn, $data->gtin
                        'price' => $record->price,
                        'stock_qty' => isset($record->inventory_level) ? $record->inventory_level : null,
                        'channel_id' => self::$current_channel->id,
                        'is_live' => isset($record->is_visible) ? $record->is_visible : "1",
                        'type1' => 'main_product',
                    ];
                    $parent_sku_id = self::save_channel_products((object)$data_full); // will return product id from products table

                    if ($parent_sku_id && isset($record->option_set_id)) {
                        $variationDetail = self::getProductDetail($channel,$item->id,"variation");
                        if(isset($variationDetail)) {
                            foreach ($variationDetail->data as $variation) {
                                $data_full = [
                                    'parent_sku_id' => $parent_sku_id,
                                    'sku' => $variation->sku_id,
                                    //'sku' => $variation->product_id,
                                    'channel_sku' => $variation->sku,
                                    // 'variation_id' => $variation->sku_id,
                                    'variation_id' => $variation->id,
                                    'name' => $record->name,
                                    'ean' => $variation->upc, //$variation->mpn, $variation->gtin
                                    'price' => $variation->calculated_price,
                                    'stock_qty' => isset($variation->inventory_level) ? $variation->inventory_level : null,
                                    'channel_id' => self::$current_channel->id,
                                    'is_live' => isset($record->is_visible) ? $record->is_visible : "1",
                                    'type1' => 'variation',
                                ];
                                self::save_channel_products((object)$data_full);
                            }
                        }
                    }
                }
            }
        }
    }
    private static function getProductDetail($channel,$item_id,$type=null)
    {
        $url = (isset($type) && $type=='variation')
            ?self::$current_channel->api_url . "v3/catalog/products/$item_id/variants"
            :self::$current_channel->api_url."v3/catalog/products?id=$item_id";
        $headers = self::getHeader($channel);
        $body = '';
        $response = self::make_api_request($url,$body,"GET",$headers);
        return $response;
    }

    private static function save_channel_products($data=null)
    {
        $cat=CatalogUtil::getdefaultParentCategory();
        if($data)
        {
            $prepare=[
                'category_id'=>isset($cat->id) ? $cat->id:0,
                'parent_sku_id'=>(isset($data->parent_sku_id) && is_int($data->parent_sku_id)) ? $data->parent_sku_id:null,
                'sku'=> isset($data->sku) ? $data->sku:null,  // saving in channel_products table
                'channel_sku'=> isset($data->channel_sku) ? $data->channel_sku:null,  // sku for product table and channel_sku for channels products table
                'variation_id'=>isset($data->variation_id) ? $data->variation_id:null,
                'name'=>$data->name,
                'ean' => $data->ean,
                'cost'=>$data->price,
                'rccp'=>$data->price,
                'stock_qty'=> isset($data->stock_qty) ? $data->stock_qty:null,
                'channel_id'=>self::$current_channel->id,
                'is_live'=>isset($data->is_live) ? $data->is_live:"1" ,   // is active or is live
                'type1' => isset($data->type1) ? $data->type1:"main_product"
            ];
            $product_id = CatalogUtil::saveProduct($prepare);  //save or update product and get product id
            if($product_id)
            {
                $prepare['product_id'] = $product_id;
                CatalogUtil::saveChannelProduct($prepare);  // save , update channel product
            }
        }
        return $product_id;
    }

    /**
     * Code Format
     */
    public static function fetchOrdersApi($channel,$time_period=null,$offset=50)
    {
        $i=0;
        date_default_timezone_set("America/New_York");
        self::set_config($channel);
        $apiCountResponse=self::getCountOrder($channel,$time_period);
//        echo '<pre>'; print_r($apiCountResponse);
        if($apiCountResponse != null)
        {
            if(isset($apiCountResponse)&& $apiCountResponse)
            {
                $count= $apiCountResponse->count;
                $length=round($count/$offset);
                $order_ids = [];
                for($i=1 ; $i<= $length; $i++)
                {
                    $apiOrderResponse = self::getOrderListFromBigCommerceApi($channel, $time_period,$i);
                    if ($apiOrderResponse != null) {
                        if (isset($apiOrderResponse) && $apiOrderResponse) {
                            foreach ($apiOrderResponse as $order)
                                $order_ids[] .= $order->id;
                            $result = self::fetchOrderDetail($order_ids, $channel);
                            self::orderData($result, $channel);
                            echo "Orders Inserted successfully";
                        } else {
                            echo "Order Not found";
                            break;
                        }
                    } else {
                        echo "Order Not found";
                        break;
                    }
                }
                echo "These order Id's inserted successfully";
                echo $order_ids;
            }else{
                echo "Error Getting Count ";
            }
        }
        else{
            echo "Error Getting Response of Count";
        }
        return;
    }

    private static function getCountOrder($channel,$time_period=null)
    {
        $currentDate = date('Y-m-d');
        if ($time_period == "day"){
            $url = self::$current_channel->api_url . "v2/orders/count";
        }else if  ($time_period == "chunk"){
            $url = self::$current_channel->api_url . "v2/orders/count";
        }else {
            $url = self::$current_channel->api_url . "v2/orders/count";
        }
        $headers = self::getHeader($channel);
        $body = '';
        $response = self::make_api_request($url, $body, "GET", $headers);
        return $response;

    }
    private static function getOrderListFromBigCommerceApi($channel,$time_period=null,$i=null)
    {
        $currentDate = date('Y-m-d');
        if ($time_period == "day") {
            $start_date = date('Y-m-d', strtotime('-1 days'));
            $url = self::$current_channel->api_url . "v2/orders?min_date_modified=$start_date&max_date_modified=$currentDate&limit=50&page=$i";

        }else if ($time_period == "chunk") {
            $start_date = date('Y-m-d', strtotime('-60 minutes'));
            $url = self::$current_channel->api_url . "v2/orders?min_date_modified=$start_date&max_date_modified=$currentDate&limit=50&page=$i";

        }else {
            $url = self::$current_channel->api_url . "v2/orders?limit=50&page=$i";

        }
//        print_r($url);
        $headers = self::getHeader($channel);
        $body = '';
        $response = self::make_api_request($url, $body, "GET", $headers);
//        echo '<pre>'; print_r($response);
        return $response;

    }

    /**
     * orders fetching
     */
    private static function fetchOrderDetail($order_ids,$channel)
    {
        if($order_ids)
        {
               //$url = "https://api.bigcommerce.com/stores/ofhbzb9dqf/v2/orders";
               $url = $channel->api_url . "v2/orders";
               $headers = self::getHeader($channel);

                $body = $order_ids;
                $response=self::make_api_request($url,$body,"GET",$headers);
                return $response;
        }
        return;
    }

    private static function orderData($data=null,$channel)
    {
        if(isset($data))
        {
            foreach ($data as $data) {
                $order_status = $data->status;
                $order_data = [
                    'order_id' => $data->id,
                    'order_no' => $data->id,
                    'channel_id' => self::$current_channel->id,
                    'payment_method' => $data->payment_method,
                    'order_total' => $data->total_inc_tax,
                    'order_created_at'=>gmdate('Y-m-d H:i:s',(strtotime($data->date_created))),  // utc time
                    'order_updated_at'=>gmdate('Y-m-d H:i:s',(strtotime($data->date_modified))),
                    'order_status' => $order_status,
                    'total_items' => $data->items_total,//count($order->items),
                    'order_shipping_fee' => $data->base_shipping_cost,
                    'order_discount' => $data->discount_amount,
                    'cust_fname' => $data->billing_address->first_name,
                    'cust_lname' => $data->billing_address->last_name,
                    'full_response' => '',//$apiresponse,
                ];
                $response = [
                    'order_detail' => $order_data,
                    'items' => self::orderItems($data,$channel),
                    'customer' => self::orderCustomerDetail($data,$channel),
                    'channel' => self::$current_channel, // need to get channel detail in orderutil
                ];

                OrderUtil::saveOrder($response);  // process data in db

            }
        }
        return;
    }

    // order items
    private static function orderItems($order=null,$channel)
    {
        $headers = self::getHeader($channel);
        $url = $order->products->url;
        $body = '';
        $response = self::make_api_request($url,$body,"GET",$headers);


        if(isset($response) && !empty($response))
        {
            foreach($response as  $item)
            {
                $item_sku=$item->sku ? $item->sku:$item->sku;
                $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$item_sku,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
                $qty=$item->quantity;
                $paid_price=$item->base_total;
                $items[]=[
                    'order_id'=> $item->order_id,
                    'sku_id'=>$sku_id,
                    //'sku_code'=>$val->product_reference, //sku number
                    'order_item_id'=>$item->id,
                    'item_status'=> strtolower($order->status),
                    'shop_sku'=>'',
                    'price'=>$item->base_price,
                    'paid_price'=>$paid_price,
                    'item_tax'=> $item->price_tax,
                    'shipping_amount'=>$item->fixed_shipping_cost,
                    'item_discount'=>0,
                    'sub_total'=>($qty * $paid_price),
                    'item_created_at'=>gmdate('Y-m-d H:i:s',strtotime(($order->date_created))),
                    'item_updated_at'=>gmdate('Y-m-d H:i:s',strtotime(($order->date_modified))),
                    'full_response'=>'',//json_encode($order_items),
                    'quantity'=>$qty,
                    'item_sku'=>$item->sku,
                    'stock_after_order' =>""
                ];
            }
        }
        return $items;
    }

    private static function orderCustomerDetail($data=null,$channel)
    {

        $url = $data->shipping_addresses->url;
        $headers = self::getHeader($channel);
        $body = '';
        $response=self::make_api_request($url,$body,"GET",$headers);
        $shipping_address = $response[0];
        if($data):

            return [
                'billing_address'=>[
                    'fname'=>$data->billing_address->first_name,
                    'lname'=>$data->billing_address->last_name,
                    'address'=>$data->billing_address->street_1,
                    'phone'=>$data->billing_address->phone ,
                    'state'=> $data->billing_address->state,
                    'city'=>$data->billing_address->city,
                    'country'=>$data->billing_address->country,
                    'postal_code'=>$data->billing_address->zip,
                ],
                'shipping_address'=>[
                    'fname'=>$shipping_address->first_name,
                    'lname'=>$shipping_address->last_name,
                    'address'=>$shipping_address->street_1,
                    'phone'=>$shipping_address->phone,
                    'state'=> $shipping_address->state,
                    'city'=>$shipping_address->city,
                    'country'=>$shipping_address->country,
                    'postal_code'=>$shipping_address->zip ,
                ],

            ];

        endif;

    }

    /*
     * update stock
     */

    public static function updateChannelStock($channel,$items,$unsync_skus)
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
                    $variations[$variation_index][]=['id'=>(int)$item['variation_id'],'inventory_level'=>(int)$item['stock'],'sku_id'=>(int)$item['sku_id']];
                    $variation_counter++;
                }

                else
                {
                    if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                        $main_index++;

                    $main_items[$main_index][]=['id'=>(int)$item['sku_id'],'inventory_level'=>(int)$item['stock']];
                    $main_counter++;
                }

            }

            if($main_items)
                self::updateChannelStockAction($main_items,'products','catalog/products',$channel);
            if($variations)
                self::updateChannelStockAction($variations,'variations','catalog/variants',$channel);

        }

        return self::$db_log;
    }

    private static function updateChannelStockAction($items,$update_type,$url_part,$channel)
    {

        if($items && $update_type)
        {
            $url=self::$current_channel->api_url . "v3/" .$url_part;

            foreach($items as $key => $batch)
            {
                $fields = json_encode($batch);

                $headers = self::getHeader($channel);
                $response = $response=self::make_api_request($url,$fields,"PUT",$headers);
                // for dblog
                $list_log['bulk_sku_stock'][]=$batch;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('update_type'=>$update_type)),
                    'response'=>self::get_json_decode_format($response),
                ];
            }




        }
        return;
    }
    /*
    * update price
    */
    public static function updateDealChannelPrice($channel,$items,$unsync_skus)
    {
        $variations=$main_items=[]; // has to update variations and main items seperately
        $variation_index=$main_index=0;
        $variation_counter=$main_counter=0;
        if($channel && $items)
        {
            self::set_config($channel);
            foreach($items as $item => $k)
            {

                /* if (in_array($item['sku'],$unsync_skus)) // dont update excluded skus
                     continue;*/
                if(!empty($item['variation_id']))
                {
                    if(fmod($variation_counter,50)==0 && $variation_counter > 0)  // batch update 50 per index
                        $variation_index++;

//                    $variations[$variation_index][]=['variation_id'=>(int)$item['variation_id'],'price'=>(float)$item['cost_price'],'sku_id'=>(int)$item['channels_products_sku']];
                    $main_items[$variation_index][$item]=$k;
                    $variation_counter++;
                }
                else
                {
                    if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                        $main_index++;
//

//                    $main_items[$main_index][]=['sku_id'=>(int)$item['channels_products_sku'],'price'=>(float)$item['cost_price']];
                    $main_items[$main_index][$item]=$k;
                    $main_counter++;
                }

            }
//            self::debug($main_items);
            if($main_items)
                self::updateChannelPriceAction($main_items,'products','products',$channel);
            if($variations)
                self::updateChannelPriceAction($variations,'variations','variants',$channel);
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

                    $variations[$variation_index][]=['variation_id'=>(int)$item['variation_id'],'price'=>(float)$item['cost_price'],'sku_id'=>(int)$item['channels_products_sku']];
                    $variation_counter++;
                }
                else
                {
                    if(fmod($main_counter,50)==0 && $main_counter > 0)  // batch update 50 per index
                        $main_index++;

                    $main_items[$main_index][]=['sku_id'=>(int)$item['channels_products_sku'],'price'=>(float)$item['cost_price']];
                    $main_counter++;
                }

            }

            if($main_items)
                self::updateChannelPriceAction($main_items,'products','products',$channel);
            if($variations)
                self::updateChannelPriceAction($variations,'variations','variants',$channel);
        }
        return self::$db_log;
    }

    private static function updateChannelPriceAction($items,$update_type,$url_part,$channel)
    {
        if($items && $update_type)
        {
            $url=self::$current_channel->api_url . "v3/".$url_part;
            foreach($items as $key => $batch)
            {
                $fields = json_encode($batch);
                $headers = self::getHeader($channel);
                $response = $response=self::make_api_request($url,$fields,"PUT",$headers);
                // for dblog
                $list_log['bulk_sku_stock'][]=$batch;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('update_type'=>$update_type)),
                    'response'=>self::get_json_decode_format($response),
                ];
            }

        }
        return;

    }


}