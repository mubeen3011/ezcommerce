<?php
namespace backend\util;

use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\EzcomToWarehouseSync;
use common\models\Warehouses;

use Yii;

class WishUtil
{
    private static $current_config = null;
    private static $current_channel = null;
    private static $fetched_skus = []; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    private static $products_fetch_page = 1; // during products fetch pagination
    private static $db_log = []; // to save log of update stock
    private static $orders_next_page = 1; // during orders fetch pagination

    public static function makePostApiCall($params, $url, $headers, $method="POST")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Check HTTP status code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            return ["status" => "success", "msg" => $http_code, "response" => json_decode($response)];
        } else {
            return ["status" => "fail", "msg" => "$http_code", "response" => json_decode($response)];
        }
        curl_close($ch);
    }


    private static function makeGetApiCall($requestUrl, $headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $response = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            return ["status" => "success", "msg" => $http_code, "response" => json_decode($response)];
        } else {
            return ["status" => "fail", "msg" => "$http_code", "response" => json_decode($response)];
        }
        return $response;  //json format
    }

    public static function setConfig($params)
    {
        $Ctime = time("YYYY-MM-DDTHH:mm:ss.sss±XX:ZZ");
        self::$current_config = $params;
        self::$current_channel = json_decode($params['auth_params']);

        if(self::$current_channel->time > $Ctime) {
           return self::$current_channel;

        }else {

            $fields = 'grant_type=refresh_token&client_id=' . self::$current_channel->client_id . '&client_secret=' . self::$current_channel->client_secret . '&refresh_token=' . self::$current_channel->refresh_token;
            $request_url= rtrim(self::$current_config['api_url'], "/"). "/api/v3/oauth/refresh_token?" . $fields;
            $headers = array('Content-Type: application/x-www-form-urlencoded;charset=utf-8');
            $access_token = self::makeGetApiCall($request_url, $headers);
            //echo "<pre>";print_r($access_token);exit;
                $arr_conf = [];
                $arr_conf['client_id'] = self::$current_channel->client_id;
                $arr_conf['client_secret'] = self::$current_channel->client_secret;
                $arr_conf['refresh_token'] = $access_token['response']->data->refresh_token;
                $arr_conf['access_token'] = $access_token['response']->data->access_token;
                $arr_conf['time'] = strtotime($access_token['response']->data->expiry_time);

                $updateWarehouse = Channels::findOne($params['id']);
                $updateWarehouse->auth_params = json_encode($arr_conf);
                $updateWarehouse->update();
                return self::$current_channel = (object)$arr_conf;
        }
    }

    //***********************************************************************************************************/
    //****************************** channel products fetching**********************************************/
    //***********************************************************************************************************/
    public static function ChannelProducts($channel = null, $single_item = null)
    {
        $j = 1;
        $start = 0;
        $limit = 50;
        self::setConfig($channel);

        for ($i =0; $i <= $j; $i++) {
            $request_url= rtrim(self::$current_config['api_url'], "/")."/api/v2/product/multi-get?start=".$start."&limit=".$limit; //Create Path of Stock Updates
            $response = self::makeGetApiCall($request_url, self::header());
           // echo "<pre>";print_r($response);exit;
            if ($response["status"] == "success") {
                self::organizeChannelProducts($response['response']->data); // organize products data and store in database
                if (isset($response['response']->paging->next)) {
                    $j++;
                    $start++;
            }
          }
            else {
                $log = ['type' => 'product-fetch-api', 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'wish product fetch request', 'addtional_info' => 'pagination executed '.$i, 'response' => $response['error'], 'log_type' => 'error'];
                LogUtil::add_log($log);
            }
        }
    }

    //organize products data and store in database///////////
    public static function organizeChannelProducts($products)
    {
        if ($products) {
            foreach ($products as $key => $product) {
                $product = $product->Product;
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

                self::$fetched_skus[] = trim($product->parent_sku); // del those skus which are deleted from platform
                $parent_cost = (isset($product->variants[0]->Variant->price) && $product->variants[0]->Variant->price) ? $product->variants[0]->Variant->price : $product->variants[0]->Variant->price;
                $data = [
                    'category_id' => '',
                    'sku' => $product->id,  // saving in channel_products table
                    'channel_sku' => trim($product->parent_sku),  // sku for product table and channel_sku for channels products table
                    'name' => isset($product->name) ? $product->name : '',
                    'cost' => $parent_cost ? $parent_cost : 0,
                    'image' => isset($product->main_image) ? $product->main_image : '',
                    'rccp' => $parent_cost ? $parent_cost : 0,
                    'stock_qty' => 0,
                    'channel_id' => self::$current_config->id,
                    'is_live' => 1,     // is active or is live
                    'brand' => isset($product->product_brand_name) ? $product->product_brand_name : "",     // brand,
                ];

                if ($dimensions)
                    $data['dimensions_magento'] = json_encode($dimensions);
                $product_id = CatalogUtil::saveProduct($data);  //product insert
                if ($product_id) {
                    $data['product_id'] = $product_id;
                    $channel_product = CatalogUtil::saveChannelProduct($data);  // save update channel product
                }

                //     save or update product and get product id
                if (!empty($product->variants) || isset($product->variants)) {
                    foreach ($product->variants as $value) {
                        $child_cost = isset($value->Variant->price) ? $value->Variant->price : $product->Variant->price;

                        $child_data = [
                            'parent_sku_id' => $product_id ? $product_id : NULL,
                            'category_id' => '',
                            'sku' => $product->id,  // saving in channel_products table
                            'channel_sku' => $value->Variant->sku,  // sku for product table and channel_sku for channels products table
                            'variation_id' => $value->Variant->id,
                            'name' => isset($value->Variant->name) ? $value->Variant->name : '',
                            'cost' => $child_cost ? $child_cost : 0,
                            'image' => isset($value->Variant->main_image) ? $value->Variant->main_image : isset($product->main_image) ? $product->main_image : '',
                            'rccp' => $child_cost ? $child_cost : 0,
                            'ean' => '',
                            'stock_qty' => isset($value->Variant->inventory) ? $value->Variant->inventory : 0,
                            'channel_id' => self::$current_config->id,
                            'is_live' => $value->Variant->enabled == 'True' ? 1 : 0,    // is active or is live
                            'brand' => isset($product->product_brand_name) ? $product->product_brand_name : "",
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
                ProductsUtil::remove_deleted_skus(self::$fetched_skus, self::$current_config['id']);
            }
        }
    }
    //header of curl function///////////
    public static function header(){
        return $headers = array(
            "Authorization: Bearer " . self::$current_channel->access_token
        );
    }

    //***********************************************************************************************************/
    //****************************** Bulk Stock Updates**********************************************/
    //***********************************************************************************************************/
    public static function updateChannelStock($channel=null,$item=array(), $unsync_skus)   // item paramater is array having sku and stock index
    {
        self::setConfig($channel);
        $data=[];
        $list_log = [];
        $request_url= rtrim(self::$current_config['api_url'], "/")."/api/v2/variant/bulk-sku-update"; //Create Path of Stock Updates

        if ($item) {
            foreach ($item as $index => $value) {
                if (in_array($value['sku'], $unsync_skus)) // dont update excluded skus
                    continue;
                //Creating List of Bulk//
                $data[] = ['sku' => $value["sku"], "inventory" => $value["stock"]];
                $list_log['bulk_sku_stock'][] = $data;
            }
            if (!empty($data)) {
                $fields = 'updates=' . json_encode($data);
                $response = self::makePostApiCall($fields, $request_url, self::header());//post bulk data of stocks
                //****** Create Log******//
                self::$db_log[] = [
                    'request' => array($list_log, 'additional_info' => array()),
                    'response' => self::get_json_decode_format($response),
                ];
                return self::$db_log;
            }
        }
        else {
            return "Failed to update";
        }
    }

    //**************** response convert into json ***************//
    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {
            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }

    // api to fetch order data from wish
    public static function fetchOrdersApi($channel, $time_period)
    {
        $j = 1;
        $limit = 50;
        self::setConfig($channel);
        $param = "";

        if ($time_period == "day")  // whole day 24 hours
        {
            $from = strtotime(date("Y-m-d H:i:s")) - (1440 * 60); // for cron job // after 24 hour //
            $from = gmdate("Y-m-d\TH:i:s", $from);
            $param .= $from;

        } else if ($time_period == "chunk") {
            $from = strtotime(date("Y-m-d H:i:s")) - (60 * 60); // for cron job //
            $from = gmdate("Y-m-d\TH:i:s", $from);
            $param .= $from;
        }
        else if ($time_period > 0) {
            $from = strtotime(date("Y-m-d H:i:s")) - ($time_period * 60); // for cron job //
            $from = gmdate("Y-m-d\TH:i:s", $from);
            $param .= $from;
        }

        for ($start =0; $start <= $j; $start++) {

            $request_url= rtrim(self::$current_config['api_url'], "/") . "/api/v2/order/multi-get?start=" . $start . "&limit=" . $limit . "&since=".$param; //Create Path of Stock Updates
            $response = self::makeGetApiCall($request_url, self::header());  // calling api request method getting response json

            if ($response['status'] == "success") {
                self::orderData($response['response'], self::$current_channel);  // get orderdetail , order items , order customer detail
                if (isset($response['response']->paging->next)) {
                    $j++;
                    $start++;
                }
            }
        }
    }


    //main order detail
    private static function orderData($apiresponse=null)
    {
        if(isset($apiresponse->data) && !empty($apiresponse->data)) {
            foreach ($apiresponse->data as $order) {

                $order_items = self::OrderItems($order->Order);  //get order items
                $customer = self::orderCustomerDetail($order->Order);
                $order_data = array(
                    'order_id' => $order->Order->order_id,
                    'order_no' => $order->Order->order_id,
                    'channel_id' => self::$current_config->id,
                    'payment_method' => "",
                    'order_total' => $order->Order->order_total,
                    'order_created_at' => $order->Order->order_time,
                    'order_updated_at' => $order->Order->last_updated,
                    'order_status' => $order->Order->state,
                    'total_items' => 1,
                    'order_shipping_fee' => $order->Order->shipping,
                    'order_discount' => "0",
                    'cust_fname' => isset($order->Order->ShippingDetail->name) ? $order->Order->ShippingDetail->name : "",
                    'cust_lname' => isset($order->Order->ShippingDetail->name) ? $order->Order->ShippingDetail->name : "",
                    'full_response' => '',//$apiresponse,
                );

                $response = [
                    'order_detail' => $order_data,
                    'items' => $order_items,
                    'customer' => $customer,
                    'channel' => self::$current_config, // need to get channel detail in orderutil
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
        if(isset($order) && !empty($order)) {

                $sku_id = HelpUtil::getChannelProductsProductId(array('sku' => $order->sku, 'channel_id' => self::$current_config->id));
                $paid_price = $order->price;
                $qty = $order->quantity;
                $items[] = array(
                    'order_id' => $order->order_id,
                    'sku_id' => $sku_id,
                    'order_item_id' => $order->product_id,
                    'item_status' => $order->state,
                    'shop_sku' => '',
                    'price' => $order->price, // if item is configuarble/variation then it will give additional index of paren_item and price have to pick from there
                    'paid_price' => $paid_price,
                    'shipping_amount' => $order->shipping_cost,
                    'item_created_at' => $order->order_time,//gmdate('Y-m-d H:i:s',strtotime($val->created_at)),
                    'item_updated_at' => $order->last_updated,//gmdate('Y-m-d H:i:s',strtotime($val->updated_at)),
                    'item_tax' => "0",
                    'item_discount' => '0',
                    'sub_total' => $order->order_total,
                    'full_response' => '',//json_encode($order_items),
                    'quantity' => $qty,
                    'item_sku' => $order->sku,
                    'stock_after_order' => "" //isset($stock_plus_product['stock']) ? $stock_plus_product['stock']  :""
                );

        }
        return $items;
    }

    private static function orderCustomerDetail($order=null)
    {
        if($order->ShippingDetail):

            return [
                'billing_address'=>[
                    'fname'=>isset($order->ShippingDetail->name) ? $order->ShippingDetail->name : "",
                    'lname'=>isset($order->ShippingDetail->last_name) ? $order->ShippingDetail->last_name : "" ,
                    'email'=>isset($order->ShippingDetail->email) ? $order->ShippingDetail->email:NULL,
                    'address'=>isset($order->ShippingDetail->street_address2) ? $order->ShippingDetail->street_address2 : "",
                    'state'=> isset($order->ShippingDetail->state) ? $order->ShippingDetail->state:$order->ShippingDetail->state,
                    'city'=>trim(isset($order->ShippingDetail->city) ? $order->ShippingDetail->city : "") ,
                    'phone'=>trim(isset($order->ShippingDetail->phone_number) ? $order->ShippingDetail->phone_number : ""),
                    'country'=>isset($order->ShippingDetail->country) ? $order->ShippingDetail->country : "" ,
                    'postal_code'=>isset($order->ShippingDetail->zipcode) ? $order->ShippingDetail->zipcode : "",
                ],
                'shipping_address'=>[
                    'fname'=>isset($order->ShippingDetail->name) ? $order->ShippingDetail->name : "",
                    'lname'=>isset($order->ShippingDetail->last_name) ? $order->ShippingDetail->last_name : "",
                    'address'=>isset($order->ShippingDetail->street_address2) ? $order->ShippingDetail->street_address2 : "",
                    'state'=> isset($order->ShippingDetail->state) ? $order->ShippingDetail->state : "",
                    'city'=>isset($order->ShippingDetail->city) ? $order->ShippingDetail->city : "",
                    'country'=>isset($order->ShippingDetail->country) ? $order->ShippingDetail->country : "",
                    'postal_code'=>isset($order->ShippingDetail->zipcode) ? $order->ShippingDetail->zipcode : "",
                ],

            ];
        endif;
    }

}
