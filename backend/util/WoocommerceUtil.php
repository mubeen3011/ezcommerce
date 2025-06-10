<?php
namespace backend\util;
use backend\controllers\ChannelProductsController;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\OrderShipment;
use common\models\Products;
use PHPUnit\Exception;
use yii;
use GuzzleHttp\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;

class WoocommerceUtil
{
    private static $current_channel = null;
    private static $products_fetch_page = 1;
    private static $orders_next_page = 1; // during orders fetch pagination
    private static $consumer_key;
    private static $consumer_secret;
    private static $fetched_skus = []; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    private static $db_log = []; // to save log of update price / stock
    // const VERSION = 'wc/v3';  // api version
    private static function makeGetApiCall($path)
    {

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => self::$current_channel->api_url,
        ]);
        try {
            $response = $client->request('GET', $path, [
                'headers' => [
                    "Authorization" => "Basic " . base64_encode(self::$consumer_key . ':' . self::$consumer_secret)
                ],
                'verify' => false, //only needed if you are facing SSL certificate issue
            ]);

            return ["status" => "success", "msg" => $response->getReasonPhrase(), "data" => $response->getBody()];

        } catch (\GuzzleHttp\Exception\RequestException $e) {

            $response = $e->getResponse();
            return ["status" => "failed", 'error'=>$e->getResponse()->getBody()->getContents(),"msg" => $response->getReasonPhrase(), "data" => ""];

        }

    }



    private static function makePutApiCall($path, $data, $method = 'PUT')
    {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => self::$current_channel->api_url,
        ]);

        try {
            $response = $client->request($method, $path, [
                'headers' => [
                    "Authorization" => "Basic " . base64_encode(self::$consumer_key . ':' . self::$consumer_secret)
                ],
                'json' => $data,
                'verify' => false, //only needed if you are facing SSL certificate issue
            ]);

            return ["status" => "success", "msg" => $response->getReasonPhrase(), "data" => ""];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            return ["status" => "failed", 'error'=>$e->getResponse()->getBody()->getContents(),"msg" => $response->getReasonPhrase(), "data" => ""];

        }

    }


    public static function setConfig($channel)
    {

        self::$current_channel = $channel;

        $access_token = json_decode($channel->auth_params);
        self::$consumer_key = $access_token->consumer_key;
        self::$consumer_secret = $access_token->consumer_secret;
    }


    public static function ChannelProducts($channel = null, $single_item = null)
    {
        self::setConfig($channel);
        $product_all_product = "/wp-json/wc/v3/products?per_page=100&page=";
        //path woocommerce fetch product
        $total_products = self::total_products();
        $total_page = ceil($total_products  / 100);

        for ($i = 1; $i <= (int)$total_page; $i++) {

            $product_all_products = $product_all_product . (int)$i;
            $response = self::makeGetApiCall($product_all_products); // get product api response
            // echo "<pre>";print_r($response);exit;
            if ($response["status"] == "success") {
                $products = json_decode($response["data"]);
                // self::debug($products);
                if ($products) {
                    self::organizeChannelProducts($products); // organize products data and store in database
                }
            } else {
                $log = ['type' => 'product-fetch-api', 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'woocommerce product fetch request', 'addtional_info' => 'pagination executed ' . $i, 'response' => $response['error'], 'log_type' => 'error'];
                LogUtil::add_log($log);
            }

        }

    }

    public static function total_products()
    {
        $product_total_count = '/wc-api/v3/products/count';
        $response_count_product = self::makeGetApiCall($product_total_count);
        $total = $response_count_product['data'];
        $total_products = json_decode($total);
        $total=isset($total_products->count) ? $total_products->count:0;
        return  $total;
    }

    //organize products data and store in database///////////
    public static function organizeChannelProducts($products)
    {
        foreach ($products as $key => $product) {

            $image = NULL;
            $brand = NULL;

            $dimensions = ""; // weight height width length
            if (isset($product->dimensions) && $product->dimensions) {
                $dimensions = [
                    'width' => ($product->dimensions->width) ? $product->dimensions->width : 0.00,
                    'height' => ($product->dimensions->height) ? $product->dimensions->height : 0.00,
                    'length' => ($product->dimensions->length) ? $product->dimensions->length : 0.00,
                    'weight' => round($product->weight, 2),
                ];
            }

            self::$fetched_skus[] = trim($product->sku); // del those skus which are deleted from platform
            $parent_cost=(isset($product->regular_price) && $product->regular_price) ? $product->regular_price:$product->price;
            $ean=self::get_product_ean($product);
            $data = [
                'category_id' => '',
                'sku' => $product->id,  // saving in channel_products table
                'channel_sku' => trim($product->sku),  // sku for product table and channel_sku for channels products table
                'name' => isset($product->name) ? $product->name : '',
                'cost' =>$parent_cost ? $parent_cost:0 ,
                'image' => isset($product->images[0]->src) ? $product->images[0]->src : '',
                'rccp' => $parent_cost ? $parent_cost:0,
                'ean' => $ean ? $ean: '',
                'stock_qty' => isset($product->stock_quantity) ? $product->stock_quantity : 0,
                'channel_id' => self::$current_channel->id,
                'is_live' => $product->catalog_visibility == 'hidden' ? 0 : 1,    // is active or is live
                'brand' => '',     // brand,
            ];

            if ($dimensions)
                $data['dimensions_magento'] = json_encode($dimensions);
            $arr_variation = array();
            $arr_variation['variation'] = $product->variations;
            $product_id_woocommarce = $product->id;
            $product_id = CatalogUtil::saveProduct($data);  //product insert

            if ($product_id) {
                $data['product_id'] = $product_id;
                $channel_product = CatalogUtil::saveChannelProduct($data);  // save update channel product
            }

            //     save or update product and get product id
            if ($arr_variation['variation']) {
                $total_product_veriation = count($arr_variation['variation']);
                $path = '/wp-json/wc/v3/products/' . $product_id_woocommarce . '/variations?per_page=' . $total_product_veriation;
                $response = self::makeGetApiCall($path);

                if ($response["status"] != "success") {
                    $log = ['type' => 'product-variation-fetch-api', 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'woocommerce product variation fetch request', 'addtional_info' => 'product variation id ' . $product_id_woocommarce, 'response' => $response["msg"], 'log_type' => 'error'];
                    LogUtil::add_log($log);
                }

                $veriation_products = json_decode($response["data"]);
               // self::debug($veriation_products);
                if (!empty($veriation_products) || isset($veriation_products)) {
                    foreach ($veriation_products as $key => $value) {
                        $child_cost=isset($value->price) ? $value->price : $product->price;
                        $child_ean=self::get_product_ean($value);
                        $child_data = [
                            'parent_sku_id' => $product_id ? $product_id : NULL,
                            'category_id' => '',
                            'sku' => $product->id,  // saving in channel_products table
                            'channel_sku' => $value->sku,  // sku for product table and channel_sku for channels products table
                            'variation_id' => $value->id,
                            'name' => isset($product->name) ? $product->name : '',
                            'cost' => $child_cost ? $child_cost:0,
                            'image' => isset($value->images->src) ? $value->images->src : isset($product->images[0]->src) ? $product->images[0]->src : '',
                            'rccp' => $child_cost ? $child_cost:0,
                            'ean' => $child_ean ? $child_ean:'',
                            'stock_qty' => isset($value->stock_quantity) ? $value->stock_quantity : 0,
                            'channel_id' => self::$current_channel->id,
                            'is_live' => $product->catalog_visibility == 'hidden' ? 0 : 1,    // is active or is live
                            'brand' => '',     // brand,
                        ];

                        if ($dimensions)
                            $child_data['dimensions_magento'] = json_encode($dimensions);

                        $c_product_id = CatalogUtil::saveProduct($child_data);  //save or update product and get product id
                        if ($c_product_id) {
                            $child_data['product_id'] = $c_product_id;
                            $channel_product_c = CatalogUtil::saveChannelProduct($child_data);  // save update channel product

                        }
                    }
                }
            }
            ProductsUtil::remove_deleted_skus(self::$fetched_skus, self::$current_channel['id']);

        }


    }

    private static function get_product_ean($product)
    {
        if(isset($product->meta_data))
        {
            foreach($product->meta_data as $meta_data)
            {
                if($meta_data->key=="_alg_ean")
                {
                    if($meta_data->value)
                        return $meta_data->value;
                }
            }
        }
        return false;
    }


    // api to fetch order data from presta
    public static function fetchOrdersApi($channel, $time_period)
    {
        self::setConfig($channel);
        $param = "per_page=100";

        if ($time_period == "day")  // whole day 24 hours
        {
            $from = strtotime(date("Y-m-d H:i:s")) - (1440 * 60); // for cron job // after 24 hour //
            $from = gmdate("Y-m-d\TH:i:s", $from);
            $param .= "&after=" . $from;

        } else if ($time_period == "chunk") {
            $from = strtotime(date("Y-m-d H:i:s")) - (60 * 60); // for cron job //
            $from = gmdate("Y-m-d\TH:i:s", $from);
            $param .= "&after=" . $from;
        }
        while (self::$orders_next_page)
        {

            $params_all_orders = "/wp-json/wc/v3/orders?" .  $param . "&page=" . self::$orders_next_page;
            $api_response = self::makeGetApiCall($params_all_orders);  // calling api request method getting response json

            if ($api_response['status'] == "success") {

                $orders = json_decode($api_response['data']);
                if(empty($orders) || self::$orders_next_page >=200)
                    break;

                if ($orders) {
                    self::orderData($orders, self::$current_channel);  // get orderdetail , order items , order customer detail
                }

            } else {
                $log = ['type' => 'order-fetch-api', 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'woocommerce order fetch request', 'addtional_info' => 'pagination executed', 'response' => $api_response['error'], 'log_type' => 'error'];
                LogUtil::add_log($log);
                throw new \Exception($api_response['error']);
                break;
            }

            self::$orders_next_page++;
        }
    }


    //main order detail
    private static function orderData($apiresponse=null)
    {
        if(!empty($apiresponse)) {

            foreach ($apiresponse as $order) {
                $order_items = self::OrderItems($order, self::$current_channel);  //get order items
                $customer = self::orderCustomerDetail($order);
                $order_data = array(
                    'order_id' => $order->id,
                    'order_no' => $order->order_key,
                    'channel_id' => self::$current_channel->id,
                    'payment_method' => $order->payment_method,
                    'order_total' => $order->total,
                    'order_created_at' => $order->date_created_gmt,
                    'order_updated_at' => $order->date_modified_gmt,
                    'order_status' => $order->status,
                    'total_items' => count($order->line_items),
                    'order_shipping_fee' => $order->shipping_total,
                    'order_discount' => $order->discount_total,
                    'cust_fname' => isset($order->billing->first_name) ? $order->billing->first_name : "",
                    'cust_lname' => isset($order->billing->last_name) ? $order->billing->first_name : "",
                    'full_response' => '',//$apiresponse,
                );

                $response = [
                    'order_detail' => $order_data,
                    'items' => $order_items,
                    'customer' => $customer,
                    'channel' => self::$current_channel, // need to get channel detail in orderutil
                ];
                // self::debug($response);
                OrderUtil::saveOrder($response);  // process data in db

            }
        }
        return;
    }


// order items
    private static function OrderItems($order)
    {
        $items=array();
        if(!empty($order->line_items)) {
            foreach ($order->line_items as $val) {
                $sku_id = HelpUtil::getChannelProductsProductId(array('sku' => $val->sku, 'channel_id' => self::$current_channel->id));
                $paid_price = $val->price;
                $qty = $val->quantity;
                $items[] = array(
                    'order_id' => $order->id,
                    'sku_id' => $sku_id,
                    //'sku_code'=>$val->product_reference, //sku number
                    'order_item_id' => $val->product_id,
                    'item_status' => $order->status,
                    'shop_sku' => '',
                    'price' => $val->price, // if item is configuarble/variation then it will give additional index of paren_item and price have to pick from there
                    'paid_price' => $paid_price,
                    'shipping_amount' => 0,
                    'item_created_at' => $order->date_created_gmt,//gmdate('Y-m-d H:i:s',strtotime($val->created_at)),
                    'item_updated_at' => $order->date_modified_gmt,//gmdate('Y-m-d H:i:s',strtotime($val->updated_at)),
                    'item_tax' => $val->total_tax,
                    'item_discount' => '0',
                    'sub_total' => $val->subtotal,
                    'full_response' => '',//json_encode($order_items),
                    'quantity' => $qty,
                    'item_sku' => $val->sku,
                    'stock_after_order' => "" //isset($stock_plus_product['stock']) ? $stock_plus_product['stock']  :""
                );
            }
        }
        return $items;

    }

    private static function orderCustomerDetail($order=null)
    {
        if($order):
            //echo "<pre>";  print_r($order);exit;
            return [
                'billing_address'=>[
                    'fname'=>isset($order->billing->first_name) ? $order->billing->first_name : "",
                    'lname'=>isset($order->billing->last_name) ? $order->billing->last_name : "" ,
                    'email'=>isset($order->billing->email) ? $order->billing->email:NULL,
                    'address'=>isset($order->billing->address_1) ? $order->billing->address_1 : "",
                    'state'=> isset($order->billing->state) ? $order->billing->state:$order->billing->city,
                    'city'=>trim(isset($order->billing->city) ? $order->billing->city : "") ,
                    'phone'=>trim(isset($order->billing->phone) ? $order->billing->phone : ""),
                    'country'=>isset($order->billing->country) ? $order->billing->phone : "" ,
                    'postal_code'=>isset($order->billing->postcode) ? $order->billing->postcode : "",
                ],
                'shipping_address'=>[
                    'fname'=>isset($order->shipping->first_name) ? $order->shipping->first_name : "",
                    'lname'=>isset($order->shipping->last_name) ? $order->shipping->last_name : "",
                    'address'=>isset($order->shipping->address_1) ? $order->shipping->address_1 : "",
                    'state'=> isset($order->shipping->state) ? $order->shipping->state : "",
                    'city'=>isset($order->shipping->city) ? $order->shipping->city : "",
                    'country'=>isset($order->shipping->country) ? $order->shipping->country : "",
                    'postal_code'=>isset($order->shipping->postcode) ? $order->shipping->postcode : "",
                ],

            ];
        endif;
    }



    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }


    public static function updateChannelStock($channel=null,$item=array(), $unsync_skus)   // item paramater is array having sku and stock index
    {
        $simple_stock = [];
        $variation=[];

        $path = "/wp-json/wc/v3/products/batch";

        self::setConfig($channel);

        if ($item) {
            foreach($item as $list) {

                if (in_array($list['sku'],$unsync_skus)) // dont update excluded skus
                    continue;

                $data = array('manage_stock' => 1, "stock_quantity" => $list["stock"], "in_stock" => ($list["stock"] > 0) ? 1 : 0);
                if (isset($list['variation_id']) && !empty($list['variation_id'])) {
                    $data['id'] = (int)$list['variation_id'];
                    $variation[$list['sku_id']][]=$data;
                }
                else {
                    $data['id'] = $list['sku_id'];
                    $datas[] = $data;
                    $simple_stock['update'] = $datas;
                }

            }

            if($variation) {
                foreach ($variation as $parent_id => $child) {
                    $paths = "/wp-json/wc/v3/products/".$parent_id."/variations/batch";
                    $body['update'] = $child;
                    $response = self::makePutApiCall($paths, $body, 'POST');
                    // for dblog
                    $list_log['bulk_sku_stock'][]=$child;

                    self::$db_log[]=[
                        'request'=>array($list_log,'additional_info'=>array()),
                        'response'=>self::get_json_decode_format($response),
                    ];
                }
            }

            /////////////if simple stock///////////////
            if($simple_stock)
            {
                $response = self::makePutApiCall($path, $simple_stock, 'POST');
                $list_log['bulk_sku_stock'][]=$simple_stock;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];
            }


            return self::$db_log;
        }

        else {
            return "Failed to update";
        }


    }


    public static function updateChannelPrice($channel=null,$item=array(), $UnsyncSkus)   // item paramater is array having sku and stock index
    {
        $simple_price = array();
        $variation_price=[];
        $path = "/wp-json/wc/v3/products/batch";

        self::setConfig($channel);
        //  self::arrCheck($item);
        if ($item) {
            foreach($item as $list) {

                if (in_array($list['sku'],$UnsyncSkus)) // dont update excluded skus
                    continue;

                $data = array("regular_price" => $list["cost_price"]);
                if (isset($list['variation_id']) && !empty($list['variation_id'])) {
                    $data['id'] = (int)$list['variation_id'];
                    $variation_price[$list['sku_id']][]=$data;
                }
                else {
                    $data['id'] = $list['channels_products_sku'];
                    $datas[] = $data;
                    $simple_price['update'] = $datas;
                }
            }

            if($variation_price) {
                foreach ($variation_price as $parent_id => $child) {
                    $paths = "/wp-json/wc/v3/products/".$parent_id."/variations/batch";
                    $body['update'] = $child;
                    $response = self::makePutApiCall($paths, $body, 'POST');
                    // for dblog
                    $list_log['bulk_sku_stock'][]=$child;

                    self::$db_log[]=[
                        'request'=>array($list_log,'additional_info'=>array()),
                        'response'=>self::get_json_decode_format($response),
                    ];
                }
            }


            /////
            if($simple_price)
            {
                $response = self::makePutApiCall($path, $simple_price, 'POST');
                $list_log['bulk_sku_stock'][]=$simple_price;
                self::$db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];
            }


            return self::$db_log;

        }
        else {
            return "Failed to update";
        }
    }


    public static function updateSalePrices($channel,$deal,$skus,$start_deal=true)
    {
        self::setConfig($channel);
        if (!empty(self::$current_channel)) {
            $path = "/wp-json/wc/v3/products/";
            $response = [];
            if ($start_deal) // if deal is starting
            {
                foreach ($skus as $sku) {
                    $get_channel_product = ChannelsProducts::find()->where(['channel_sku' => $sku['sku']])->asArray()->one();

                    $data = array('status' => "publish", 'sale_price' => $sku['deal_price'], "date_on_sale_from_gmt" => $deal->start_date, "date_on_sale_to_gmt" => $deal->end_date);
                    if (isset($get_channel_product['variation_id']) && !empty($get_channel_product['variation_id'])) {
                        $product_variation_update_url = $path . $get_channel_product['sku'] . "/variations/" . $get_channel_product['variation_id'];
                        $response = self::makePutApiCall($product_variation_update_url, $data);

                    } else {
                        $product_variation_update_url = $path . $get_channel_product['sku'];
                        $response = self::makePutApiCall($product_variation_update_url, $data);
                    }
                }
            } else { // if deal is ending

                foreach ($skus as $sku) {
                    $res = DealsUtil::get_nearest_active_deal($deal->channel_id, $deal->id, $sku['sku']);
                    $get_channel_product = ChannelsProducts::find()->where(['channel_sku' => $sku['sku']])->asArray()->one();
                    if ($res) {
                        $data = array('status' => "publish", 'sale_price' => $res['deal_price'], "date_on_sale_from_gmt" => $res['start_date'], "date_on_sale_to_gmt" => $res['end_date']);
                        if (isset($get_channel_product['variation_id']) && !empty($get_channel_product['variation_id'])) {

                            $product_variation_update_url = $path . $get_channel_product['sku'] . "/variations/" . $get_channel_product['variation_id'];
                            $response = self::makePutApiCall($product_variation_update_url, $data);
                        } else {
                            $product_variation_update_url = $path . $get_channel_product['sku'];
                            $response = self::makePutApiCall($product_variation_update_url, $data);
                        }
                    } else {
                        $data = array('status' => "publish", 'sale_price' => "", "date_on_sale_from_gmt" => "", "date_on_sale_to_gmt" => "");
                        // echo "<pre>";print_r($get_channel_product);exit;
                        if (isset($get_channel_product['variation_id']) && !empty($get_channel_product['variation_id'])) {
                            $product_variation_update_url = $path . $get_channel_product['sku'] . "/variations/" . $get_channel_product['variation_id'];
                            $response = self::makePutApiCall($product_variation_update_url, $data);
                        } else {
                            $product_variation_update_url = $path . $get_channel_product['sku'];
                            $response = self::makePutApiCall($product_variation_update_url, $data);

                        }


                    }
                }
            }

            return $response;

        }
    }

    public static function debug($var){
        echo "<pre>";print_r($var);exit;
    }

    /**arrange order for shipment , called from cron controller**/
    public static function arrangeOrderForShipment($item_list)
    {
        $order=[];
        $items=[];
        foreach($item_list as $item){
            $items[$item['order_id_PK']][]=$item;
            $order[$item['order_id_PK']]=[
                'marketplace_order_id'=>$item['channel_order_id'],
                'items'=>$items[$item['order_id_PK']]
            ];
        }
        return $order;
    }

    public static function updateShipmentAndTracking($channel,$orders)
    {
        if($channel && $orders):
            self::setConfig($channel);
            $response=[];
            foreach ($orders as $pk_id=>$order)
            {
                foreach($order['items'] as $order_item)
                {
                    //for($i=2;$i<=3;$i++):  // change status first to 2(ready to ship , then 3 ship)
                        $body = array('tracking_provider' => "tcs", 'tracking_number' => $order_item['tracking_number'], "date_shipped" => "");
                        $api_url="/wp-json/wc-ast/v3/orders/".$order['marketplace_order_id']."/shipment-trackings";
                        $response = self::makePutApiCall($api_url, $body, 'POST');
                        $response[$pk_id][]=$response;
                    //endfor;
                }
            }

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

    public static function map_system_courier_marketplace_statuses($system_status)
    {
        if(strtolower($system_status)=="completed")
            return "completed";
        else
            return $system_status;
    }

    public static function SetOrderStatus($channelId, $channel_order_id, $status){

        $channel= Channels::find()->where(['id' =>$channelId])->one();
        self::setConfig($channel);
        if(!empty(self::$current_channel)){
            $path = "/wp-json/wc/v3/orders/";
            $data = array('status' => $status);
            $product_veriation_update_url = $path.$channel_order_id;
            $response = self::makePutApiCall($product_veriation_update_url, $data);
        }
    }





}
