<?php
namespace backend\util;
use common\models\ChannelsProducts;
use common\models\DeleteChannelProducts;
use common\models\OrderShipment;
use common\models\ProductMagentoAttribute;
use common\models\Products;
use common\models\SaleCatModificationLog;
use common\models\Settings;
use common\models\ThirdPartyOrders;
use common\models\ThirdPartyOrdersLog;
use common\models\Warehouses;
use yii;
use Codeception\Module\Yii1;
use common\models\Category;
use common\models\Channels;
use libphonenumber\ValidationResult;
use yii\db\Exception;
use yii\web\Controller;
use backend\util\OrderUtil;
use backend\util\CatalogUtil;
class MagentoUtil
{
    //generate request to api
    private static $current_token;  // save current calling token
    private static $current_channel;
    private static $counter = 0;
    private static $products_fetch_page = 1; // during products fetch pagination
    private static $products_sale_cat_page = 1; // during products set sell category pagination
    private static $orders_fetch_page = 1; // during orders fetch pagination
    private static $product_brands = [];  // brands list to assign to products during fetching
    private static $parent_child_relationship = [];  // during product fetch parent child relationship
    private static $product_fetched_pagination_ended = false;
    private static $fetched_skus = []; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    //private static $main_parent_cat
    private static $sale_category_id = null;
    private static $setting_table_set_cat_name = "magento_set_cat_pagination";
    private static $setting_table_unset_name = "magento_unset_cat_pagination";

    private static function makeGetApiCall($requestUrl, $headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        return $result;  //json format
    }

    private function makePostApiCall($requestUrl, $body_enclosed, $headers, $method = "PUT")
    {
//        self::debug($requestUrl);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body_enclosed);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        //  self::debug($response);
        return $response;
    }

    //refresh token if expired
    private static function refreshToken($channel)
    {

    }

    //check memory usage
    private static function print_mem()
    {
        /* Currently used memory */
        $mem_usage = memory_get_usage();

        /* Peak memory usage */
        $mem_peak = memory_get_peak_usage();

        echo 'The script is now using: <strong>' . round($mem_usage / 1024) . 'KB</strong> of memory.<br>';
        echo 'Peak usage: <strong>' . round($mem_peak / 1024) . 'KB</strong> of memory.<br><br>';
    }

    // api to fetch order data from magento
    public static function fetchOrdersApi($channel, $time_period)
    {
        if (self::$orders_fetch_page > 50) //
        {
            die('too much loop execution');
        }
        $access_token = json_decode($channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        // date_default_timezone_set('Asia/karachi');
        $current_time = gmdate("Y-m-d%20H:i:s");
        //echo $current_time; die();
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $params = 'searchCriteria[pageSize]=50&searchCriteria[currentPage]=' . self::$orders_fetch_page;
        $params .= '&searchCriteria[sortOrders][1][field]=entity_id&searchCriteria[sortOrders][1][direction]=DESC';
        if ($time_period == "day")  // whole day 24 hours
        {

            $from = strtotime(gmdate("Y-m-d H:i:s")) - (86400 * 2); // for cron job // after 24 hour //
            $from = date("Y-m-d%20H:i:s", $from);
            $params .= '&searchCriteria[filter_groups][0][filters][0][field]=updated_at&searchCriteria[filter_groups][0][filters][0][value]=' . $from . '&searchCriteria[filter_groups][0][filters][0][condition_type]=from&searchCriteria[filter_groups][1][filters][1][field]=updated_at&searchCriteria[filter_groups][1][filters][1][value]=' . $current_time . '&searchCriteria[filter_groups][1][filters][1][condition_type]=to';
        } else if ($time_period == "chunk") {

            $from = strtotime(gmdate("Y-m-d H:i:s")) - (40 * 60); // for cron job // will bring last 40 min orders
            $from = date("Y-m-d%20H:i:s", $from);
            $params .= '&searchCriteria[filter_groups][0][filters][0][field]=updated_at&searchCriteria[filter_groups][0][filters][0][value]=' . $from . '&searchCriteria[filter_groups][0][filters][0][condition_type]=from&searchCriteria[filter_groups][1][filters][1][field]=updated_at&searchCriteria[filter_groups][1][filters][1][value]=' . $current_time . '&searchCriteria[filter_groups][1][filters][1][condition_type]=to';
        }

        $requestUrl = $channel->api_url . 'rest/V1/orders?' . $params;
        //  echo $requestUrl; die();
        $response = self::makeGetApiCall($requestUrl, $headers);
        // $response=json_decode($response);

        //self::debug($response);
        if ($response) {
            // echo $response; die();
            $res = json_decode($response);
            // echo "<pre>";
            // print_r($res); die();
            self::OrderData($channel, $res);
            if (isset($res->total_count) && $res->total_count > 50) {
                self::$orders_fetch_page++;
                $total_page = ceil($res->total_count / 50);
                if (self::$orders_fetch_page <= $total_page)  //pagination
                {
                    self::fetchOrdersApi($channel, $time_period);
                }

            }

        }
        return $response;

    }

    public static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    //receive all data
    private static function OrderData($channel, $data)
    {
        self::$current_channel = $channel;
        if (isset($data->items)) {
            foreach ($data->items as $order) {
                $customer = self::orderCustomerDetail($order);
                $order_data = array(
                    'order_id' => $order->entity_id,
                    'order_no' => $order->increment_id,
                    'channel_id' => self::$current_channel->id,
                    'payment_method' => $order->payment->method,
                    'order_total' => $order->base_grand_total,
                    'order_created_at' => $order->created_at,//gmdate('Y-m-d H:i:s',strtotime($order->created_at)),  // utc time
                    'order_updated_at' => $order->updated_at,//gmdate('Y-m-d H:i:s',strtotime($order->updated_at)), // utc time
                    'order_status' => $order->status,
                    'total_items' => $order->total_item_count,
                    'order_shipping_fee' => $order->payment->shipping_amount,
                    'order_discount' => $order->discount_amount,
                    'coupon_code' => isset($order->coupon_code) ? $order->coupon_code : NULL,
                    'cust_fname' => isset($order->customer_firstname) ? $order->customer_firstname : $customer['billing_address']['fname'],
                    'cust_lname' => isset($order->customer_lastname) ? $order->customer_lastname : $customer['billing_address']['lname'],
                    'full_response' => '',//$apiresponse,
                );

                $response = [
                    'order_detail' => $order_data,
                    'items' => self::OrderItems($order),  //get order items
                    'customer' => $customer,
                    'channel' => $channel, // need to get channel detail in orderutil
                ];
                // echo json_encode($response);
                // die();

                OrderUtil::saveOrder($response);  // process data in db
            }
        }
        // date_default_timezone_set('Asia/Kuala_Lumpur');
        return;


    }

    // order items
    private static function OrderItems($order)
    {
        $items = array();
        foreach ($order->items as $val) {
            // $stock_plus_product= self::ChannelProducts(self::$current_channel,$val->sku );  // will update stock and will return product_id and stock
            $sku_id = HelpUtil::getChannelProductsProductId(array('sku' => $val->sku, 'channel_id' => self::$current_channel->id));
            // $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$val->sku,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
            if ($val->product_type == "configurable") {
                continue; // because magento gives 2 indexes of same item for configurable , so we dont need first index
            }
            $paid_price = isset($val->parent_item->price) ? $val->parent_item->price : $val->price;
            $discount_amount = isset($val->parent_item->discount_amount) ? $val->parent_item->discount_amount : $val->discount_amount;
            $qty = (($val->qty_ordered - $val->qty_canceled) - $val->qty_refunded);
            $items[] = array(
                'order_id' => $val->order_id,
                'sku_id' => $sku_id,
                //'sku_code'=>$val->product_reference, //sku number
                'order_item_id' => $val->item_id,
                'item_status' => $order->status,
                'shop_sku' => '',
                'price' => isset($val->parent_item->original_price) ? $val->parent_item->original_price : $val->original_price, // if item is configuarble/variation then it will give additional index of paren_item and price have to pick from there
                'paid_price' => $paid_price,
                'shipping_amount' => 0,
                'item_created_at' => $val->created_at,//gmdate('Y-m-d H:i:s',strtotime($val->created_at)),
                'item_updated_at' => $val->updated_at,//gmdate('Y-m-d H:i:s',strtotime($val->updated_at)),
                'item_tax' => isset($val->parent_item->tax_amount) ? $val->parent_item->tax_amount : $val->tax_amount,
                'item_discount' => $discount_amount,
                'sub_total' => (($qty * $paid_price) - $discount_amount),
                'full_response' => '',//json_encode($order_items),
                'quantity' => $qty,
                'item_sku' => $val->sku,
                'stock_after_order' => "" //isset($stock_plus_product['stock']) ? $stock_plus_product['stock']  :""
            );
        }

        return $items;

    }

    /// detail of customer who ordered
    private static function orderCustomerDetail($order = null)
    {

        $billing = [
            'fname' => $order->billing_address->firstname,
            'lname' => $order->billing_address->lastname,
            'email' => isset($order->billing_address->email) ? $order->billing_address->email : NULL,
            'address' => implode(",", $order->billing_address->street),
            'state' => isset($order->billing_address->region) ? $order->billing_address->region : $order->billing_address->city,
            'city' => trim($order->billing_address->city),
            'phone' => trim($order->billing_address->telephone),
            'country' => $order->billing_address->country_id,
            'postal_code' => $order->billing_address->postcode,
        ];
        if (isset($order->extension_attributes->shipping_assignments[0]->shipping->address)) {  // for virtual products no shipping address
            $shipping = [
                'fname' => $order->extension_attributes->shipping_assignments[0]->shipping->address->firstname,
                'lname' => $order->extension_attributes->shipping_assignments[0]->shipping->address->lastname,
                'email' => ($order->extension_attributes->shipping_assignments[0]->shipping->address->email) ? $order->extension_attributes->shipping_assignments[0]->shipping->address->email : NULL,
                'address' => implode(",", $order->extension_attributes->shipping_assignments[0]->shipping->address->street),
                'state' => isset($order->extension_attributes->shipping_assignments[0]->shipping->address->region) ? $order->extension_attributes->shipping_assignments[0]->shipping->address->region : $order->extension_attributes->shipping_assignments[0]->shipping->address->city,
                'city' => trim($order->extension_attributes->shipping_assignments[0]->shipping->address->city),
                'phone' => trim($order->extension_attributes->shipping_assignments[0]->shipping->address->telephone),
                'country' => $order->extension_attributes->shipping_assignments[0]->shipping->address->country_id,
                'postal_code' => $order->extension_attributes->shipping_assignments[0]->shipping->address->postcode,
            ];
        } else {
            $shipping = $billing;
        }
        return [
            'billing_address' => $billing,
            'shipping_address' => $shipping,

        ];

    }

    //***********************************************************************************************************/
    //****************************** channel category fetching**********************************************/
    //***********************************************************************************************************/
    public static function ChannelCategories($channel = null, $save_in_db = true)
    {
        if ($channel) {
            $access_token = json_decode($channel->auth_params);
            self::$current_channel = $channel;
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            //// parameteres
            // date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');
            // $current_time=date("Y-m-d%20H:i:s");
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );
            $params = 'searchCriteria[pageSize]=500&searchCriteria[currentPage]=1';
            $requestUrl = $channel->api_url . 'rest/V1/categories?' . $params;
            $response = self::  makeGetApiCall($requestUrl, $headers);
            // $data= json_decode($response);
            //echo "<pre>";
            // print_r($data); die();
            if ($response) {

                $res = json_decode($response);
                if ($save_in_db) {
                    self::saveChannelCategories($res, $channel);
                }

                return $res;
            }
        }
        return;
    }

    /*******************Get brands******/

    private static function get_brands()
    {

        $access_token = json_decode(self::$current_channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $requestUrl = self::$current_channel->api_url . 'rest/V1/products/attributes?searchCriteria[filterGroups][0][filters][0][field]=attribute_code&searchCriteria[filterGroups][0][filters][0][value]=mill_name&fields=items[attribute_code,options]';
        $response = self::  makeGetApiCall($requestUrl, $headers);
        if ($response) {
            $brands = json_decode($response);
            if (isset($brands->items[0]->options)) {
                foreach ($brands->items[0]->options as $brand) {
                    if ($brand->label && $brand->value)
                        self::$product_brands[$brand->value] = $brand->label;
                }


            }

        }

        return self::$product_brands;

    }


    private function saveChannelCategories($data)
    {


        $parent_cat_id = 0; //sub_cat_parent_id
        $main_parent_id = 0;
        if (!in_array($data->id, array(1, 2)))   // magento has root and default category(having catid 1 and 2) we dont need
        {
            $record['cat_name'] = $data->name;
            $record['is_active'] = 1;
            $record['parent_cat_id'] = 0;
            $record['magento_cat_id'] = $data->id; // it is marketplace db id
            $record['channel'] = self::$current_channel;
            $response = CatalogUtil::saveCategories($record);
            $main_parent_id = isset($response['id']) ? $response['id'] : 0;


        }
        //if children data set
        if ($data->children_data) {
            self::recursive_save_categories($data->children_data);

        }

    }

    private static function recursive_save_categories($data)
    {

        foreach ($data as $k => $v) {
            $parent_id = NULL;
            if (isset($v->parent_id) && $v->parent_id && !in_array($v->parent_id, [1, 2])) {
                $parent_id = Category::find()->select('id')->where(['magento_cat_id' => $v->parent_id])->scalar();
            }
            $record['cat_name'] = $v->name;
            $record['is_active'] = 1;
            $record['parent_cat_id'] = $parent_id;
            $record['magento_cat_id'] = $v->id; // it is marketplace db id
            $record['channel'] = self::$current_channel;
            $response = CatalogUtil::saveCategories($record);
            $parent_cat_id = isset($response['id']) ? $response['id'] : 0;
            if ($v->children_data) {
                self::recursive_save_categories($v->children_data);
            }
        }
    }


    /* public static function get_local_db_cat($live_store_cat_id)
     {
         $cat=Category::find()->select('id')->where(['magento_cat_id'=>$live_store_cat_id])->scalar();
         return $cat ? $cat:0;
     }*/
    public static function get_local_db_cat($live_store_cats_array)
    {
        $cats = [];
        $categories = Category::find()->asArray()->all();
        // $cat_count=count($live_store_cats_array); // count in how many categroies this product exists
        if ($live_store_cats_array) {
            foreach ($live_store_cats_array as $cat) {
                $cat_id = Category::find()->select('id')->where(['magento_cat_id' => $cat->category_id])->scalar();  // get local db id against magento cat id
                if ($cat_id)
                    $cats[$cat_id] = self::child_level($categories, $cat_id);  // get which level of child is this cat

            }
        }
        return self::auto_assign_cat($cats);// decide which cat to assign to product

    }

    /***
     * choose which cat to asign to product
     */
    private static function auto_assign_cat($cats = [])
    {
        if (empty($cats)) {
            return 0;
        } else if (count($cats) == 1) //if there is only 1 category
        {
            $first = array_keys($cats);
            return $first[0];
        } else if (array_sum($cats) === 0) { // this mean all category are of same level in heirarchy
            $first = array_keys($cats);
            return $first[0]; // return first
        } else {
            arsort($cats); // sort by value desc
            $first = array_keys($cats);
            return $first[0]; // return first

        }
    }

    private static function child_level($categories, $cat_id, &$result = 0)
    {
        foreach ($categories as $cat) {
            if ($cat['id'] == $cat_id) {
                if (empty($cat['parent_id']))
                    return $result;
                else {
                    $result++;
                    self::child_level($categories, $cat['parent_id'], $result);
                }

            }
        }
        return $result;
    }

    private static function get_db_category_id($live_store_cat_id)
    {

        $access_token = json_decode(self::$current_channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        // date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');
        // $current_time=date("Y-m-d%20H:i:s");
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        //$params ="/".$live_store_cat_id;
        $requestUrl = self::$current_channel->api_url . 'rest/V1/category/' . $live_store_cat_id;
        $response = self::  makeGetApiCall($requestUrl, $headers);

        if ($response) {
            $data = json_decode($response);
            if (isset($data->name)) {
                $cat_id = Category::findone(['name' => $data->name]);

            }
            return isset($cat_id['id']) ? $cat_id['id'] : 0;

        }
    }

    /*****assign brand to product******/
    private static function assign_brand_to_product($manufacturer_id)
    {
        if (!self::$product_brands) // if brands already not fetched from api
            self::get_brands();

        return isset(self::$product_brands[$manufacturer_id]) ? self::$product_brands[$manufacturer_id] : NULL;

    }
    //***********************************************************************************************************/
    //****************************** channel products fetching**********************************************/
    //***********************************************************************************************************/

    public static function ChannelProducts($channel = null, $single_item = null)
    {

        if (self::$products_fetch_page > 50) {  // if trapped in loop end fetching
            die('too many loops ending ');
        }
        if ($channel) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );
            $params = isset($single_item) ? urlencode($single_item) : '?searchCriteria[pageSize]=500&searchCriteria[currentPage]=' . self::$products_fetch_page;
            //  $params =isset($single_item) ? $single_item :'?searchCriteria[pageSize]=500&searchCriteria[currentPage]=93';
            $requestUrl = rtrim($channel->api_url, '/') . '/rest/V1/products/' . $params;
            $response = self::  makeGetApiCall($requestUrl, $headers);
            //$response=json_decode($response);
            //  self::debug($response);
            if ($response) {
                $products = json_decode($response);
                $added = self::organizeChannelProducts($products, $single_item ? $single_item : "");
                if (isset($products->total_count) && $products->total_count > 500) {
                    self::$products_fetch_page++;
                    $total_page = ceil($products->total_count / 500);
                    if (self::$products_fetch_page <= $total_page)  //pagination
                    {
                        self::ChannelProducts($channel, null);
                    } else {
                        self::make_product_relationship(); // parent child relationship update local table
                        self::assign_parents_categry_to_child(); // those child products which has not been assigned category assign cat of parent to them
                        ProductsUtil::remove_deleted_skus(self::$fetched_skus, self::$current_channel->id); // delete skus which are deleted from platform
                    }

                } else {
                    self::make_product_relationship(); // parent child relationship update local table
                    self::assign_parents_categry_to_child(); // those child products which has not been assigned category assign cat of parent to them
                    ProductsUtil::remove_deleted_skus(self::$fetched_skus, self::$current_channel->id); // delete skus which are deleted from platform
                }

                if ($single_item) {
                    return $added;
                }

            }

        }
        return;

    }

    public static function product_discount_price($product = null)
    {
        $discounted_price = [];
        /// get brand and discounted/special_price
        if (isset($product->custom_attributes)) {
            foreach ($product->custom_attributes as $attribute) {
                if (isset($attribute->attribute_code) && $attribute->attribute_code == "special_to_date" && ($attribute->value >= date('Y-m-d H:i:s')))
                    $discounted_price['discount_to_date'] = $attribute->value;

                if (isset($attribute->attribute_code) && $attribute->attribute_code == "special_from_date")
                    $discounted_price['discount_from_date'] = $attribute->value;

                if (isset($attribute->attribute_code) && $attribute->attribute_code == "special_price")
                    $discounted_price['discount_price'] = $attribute->value;


            }

            /// discount price from
        }
        if (isset($discounted_price['discount_price']) && isset($discounted_price['discount_to_date']))
            return $discounted_price;
        else
            return NULL;
    }

    //organize products data and store in database///////////
    public static function organizeChannelProducts($products, $single_item = null)
    {
        $img_prefix = self::$current_channel->api_url . "/pub/media/catalog/product";
        $items = NULL;
        $relationship = []; // to store parent child
        if (isset($products->items))
            $items = $products->items;
        else
            $items[] = $products;


        if ($items) {
            //$cat=CatalogUtil::getdefaultParentCategory();
            foreach ($items as $product) {
                $cat_id = NULL;
                $image = NULL;
                $brand = NULL;
                $discounted_price = []; // special price
                if (isset($product->extension_attributes->category_links)) {
                    $cat_id = self::get_local_db_cat($product->extension_attributes->category_links);
                }
                if ($product->type_id == "configurable")  // mean the product has variations
                {
                    self::$parent_child_relationship[$product->sku] = isset($product->extension_attributes->configurable_product_links) ? $product->extension_attributes->configurable_product_links : [];
                }

                /////product discount price =>
                $discounted_price = self::product_discount_price($product);

                /// get brand and discounted/special_price
                if (isset($product->custom_attributes)) {
                    foreach ($product->custom_attributes as $attribute) {
                        if (isset($attribute->attribute_code) && $attribute->attribute_code == "mill_name" && $attribute->value && $attribute->value != "")
                            $brand = self::assign_brand_to_product($attribute->value); // get brand of product

                    }
                }

                if (isset($product->media_gallery_entries[0]->file))
                    $image = $img_prefix . $product->media_gallery_entries[0]->file;
                elseif (isset($product->media_gallery_entries[1]->file))
                    $image = $img_prefix . $product->media_gallery_entries[0]->file;

                $dimensions = ""; // weight height width length
                if (isset($product->weight) && $product->weight) {
                    $dimensions = [
                        'width' => 0.00,
                        'height' => 0.00,
                        'length' => 0.00,
                        'weight' => round($product->weight, 2),
                    ];
                }

                self::$fetched_skus[] = trim($product->sku); // del those skus which are deleted from platform

                $data = [
                    'category_id' => $cat_id,
                    'sku' => $product->id,  // saving in channel_products table
                    'channel_sku' => trim($product->sku),  // sku for product table and channel_sku for channels products table
                    'name' => isset($product->name) ? $product->name : "",
                    'cost' => isset($product->price) ? $product->price : 0,
                    'image' => $image,
                    'rccp' => isset($product->price) ? $product->price : 0,
                    'ean' => "",
                    'stock_qty' => 0,//self::getItemStock($product->sku),
                    'channel_id' => self::$current_channel->id,
                    'is_live' => $product->status == 2 ? 0 : 1,    // is active or is live
                    'brand' => strtolower($brand),     // brand,
                    'marketplace' => 'magento',
                    'discounted_price' => isset($discounted_price['discount_price']) ? $discounted_price['discount_price'] : "",  // channel product table
                    'discount_from_date' => isset($discounted_price['discount_from_date']) ? $discounted_price['discount_from_date'] : "", // channel product table
                    'discount_to_date' => isset($discounted_price['discount_to_date']) ? $discounted_price['discount_to_date'] : "", // channel product table
                ];
                if ($dimensions)
                    $data['dimensions_magento'] = json_encode($dimensions);

                // echo "<pre>";
                // var_dump($data);die();
                // $product_id=NULL;
                $product_id = CatalogUtil::saveProduct($data);  //save or update product and get product id

                if ($product_id) {

                    $data['product_id'] = $product_id;
                    CatalogUtil::saveChannelProduct($data);  // save update channel product
                    if ($single_item)  // if requested for single item then return product id and stock
                    {

                        return [
                            'product_id' => $product_id,
                            'stock' => $data['stock_qty']
                        ];
                    }
                }

            }

        }
        //   self::make_product_relationship($relationship); // parent child relationship update local table
        //   self::assign_parents_categry_to_child(); // those child products which has not been assigned category assign cat of parent to them
        return;

    }

    /**
     * parent child relationship
     */
    private static function make_product_relationship()
    {
        // echo "<pre>";
        //print_r($relationship); die();
        if (self::$parent_child_relationship) {
            $connection = Yii::$app->db;
            foreach (self::$parent_child_relationship as $parent => $child) {
                if (!$child || !is_array($child))
                    continue;

                $child_pk_ids = implode(',', $child);
                $sql = "SET @product_id := (SELECT `product_id` FROM `channels_products` WHERE `channel_sku`='" . $parent . "' AND `channel_id`='" . self::$current_channel->id . "');
                    SET @image:=(SELECT `image` FROM `products` WHERE `id`=@product_id );
                    SET @brand:=(SELECT `brand` FROM `products` WHERE `id`=@product_id );
                    UPDATE `products` p
                      JOIN 
                        `channels_products` cp
                        ON
                          `p`.`id`=`cp`.`product_id`
                      SET 
                         `p`.`parent_sku_id`=@product_id ,
                         `p`.`image`=IFNULL(`p`.`image`,  @image),
                         `p`.`brand`=@brand
                      WHERE 
                        `cp`.`sku` IN($child_pk_ids) ";
                // echo $sql;die();
                $connection->createCommand($sql)->execute();
            }
        }
        return;
    }

    // those child products which has not been assigned category assign cat of parent to them
    private function assign_parents_categry_to_child()
    {
        $connection = Yii::$app->db;
        $sql = "UPDATE `products` child
              JOIN `products` parent
              ON `child`.`parent_sku_id`=`parent`.`id`
              SET
                `child`.`sub_Category`=`parent`.`sub_category`
              WHERE `child`.`sub_category` IS NULL
               AND `child`.`parent_sku_id` IS NOT NULL";
        $connection->createCommand($sql)->execute();
    }

    public static function getItemStockRequest($sku)
    {

        $access_token = json_decode(self::$current_channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        // $params ='searchCriteria[pageSize]=500&searchCriteria[currentPage]=1';
        $requestUrl = self::$current_channel->api_url . 'rest/V1/stockItems/' . $sku;
        $response = self::  makeGetApiCall($requestUrl, $headers);
        if ($response) {
            $stock = json_decode($response);
            return isset($stock->qty) ? $stock->qty : "";
        }
        return "";
    }

    public static function getItemStock($channel, $products)
    {
        $response = [];
        self::$current_channel = $channel;
        foreach ($products as $product) {
            $response[$product['channel_sku']] = self::getItemStockRequest($product['channel_sku']);
        }
        if ($response) {
            CatalogUtil::save_shop_stock($response, self::$current_channel->id);
        }
        return $response;
    }

    ////update magento live store data
    public static function updateChannelStock($channel = null, $item = array())   // item paramater is array having sku and stock index
    {
        if (!self::$current_channel)
            self::$current_channel = $channel;

        $access_token = json_decode(self::$current_channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $body_enclosed = json_encode(array('stockItem' => array('qty' => $item['stock'], 'is_in_stock' => $item['stock'] > 0 ? true : false)));
        $requestUrl = self::$current_channel->api_url . 'rest/V1/products/' . urlencode($item['sku']) . '/stockItems/1'; //
        //$requestUrl='http://speedsports.bgj.sjl.mybluehost.me/rest/V1/products/'.$item['sku'].'/stockItems/1'; /// it is for testing uncomment when stock above when testing done
        $response = self::makePostApiCall($requestUrl, $body_enclosed, $headers);
        return $response;

    }


    ///update price of products////
    public static function updateChannelPrice($channel, $item = array())
    {
//        self::debug($item['sku']);

        if (isset($item['check_deal_sku']) && $item['check_deal_sku'] == 'yes') // while price update check if sku is in currently running deal then skip price update
        {
            // $check_deal_sku= HelpUtil::checkActiveDealSku($channel->id,$item['sku']);
            $check_deal_sku = HelpUtil::SkuInDeal($item['product_id'], $channel['id']);
            if ($check_deal_sku) {
                return 'sku is in active deal , failed to update';
            }
        }
        self::$current_channel = $channel;
        $access_token = json_decode($channel['auth_params']);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres

        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $body_enclosed = json_encode(array('product' => array('sku' => $item['sku'], 'price' => $item['price'])));
        $requestUrl = $channel['api_url'] . 'rest/V1/products/' . urlencode($item['sku']);
        $response = self::makePostApiCall($requestUrl, $body_enclosed, $headers);
        return $response;
        // self::debug($response);
        // die();
    }

    public static function GetCarriers($channel)
    {
        if ($channel) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            //// parameteres
            //date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');
            $current_time = date("Y-m-d%20H:i:s");
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );
            //$params =isset($single_item) ? $single_item :'?searchCriteria[pageSize]=500&searchCriteria[currentPage]=1';
            $requestUrl = $channel->api_url . 'rest/V1/carriers/';
            $response = self::  makeGetApiCall($requestUrl, $headers);
            $data = json_decode($response);
            echo '<pre>';
            print_r($data);
            die;
        }
        return;
    }

    /**arrange order for shipment , called from cron controller**/
    public static function arrangeOrderForShipment($item_list)
    {
        $order = [];
        $items = [];
        foreach ($item_list as $item) {
            $items[$item['order_id_PK']][] = $item;
            $order[$item['order_id_PK']] = [
                'marketplace_order_id' => $item['channel_order_id'],
                'order_status' => $item['order_status'],
                'order_market_status' => $item['order_market_status'],
                'payment_method' => $item['payment_method'],
                'items' => $items[$item['order_id_PK']]
            ];
        }
        return $order;
    }

    public static function append_invoice_record($channel, $items)
    {
        foreach ($items as &$order) {
            $items_invoiced = self::checkorderitemsinvoice($channel, $order['marketplace_order_id']); // how many items invoiced
            foreach ($order['items'] as &$item)
                $item['items_invoiced'] = isset($items_invoiced[$item['channel_order_item_id']]) ? $items_invoiced[$item['channel_order_item_id']] : 0;

        }
        return $items;
    }

    private static function checkorderitemsinvoice($channel, $channel_order_id)
    {
        $items = [];
        $access_token = json_decode($channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $requestUrl = $channel->api_url . 'rest/V1/orders/' . $channel_order_id;
        $response = self::makeGetApiCall($requestUrl, $headers);
        if ($response) {
            $order = json_decode($response);
            /*echo "<pre>";
            print_r($order); die();*/
            if (!isset($order->items))
                return $items;

            foreach ($order->items as $val) {

                if ($val->product_type == "configurable") {
                    continue; // because magento gives 2 indexes of same item for configurable , so we dont need first index
                }
                $items[$val->item_id] = $val->qty_invoiced;
            }
        }
        return $items;
    }

    public static function createShipment($channel, $orders)
    {
        $response = [];
        if ($channel && $orders) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );

            foreach ($orders as $pk_id => $order)  // orders array sent
            {
                $items = [];
                $tracks = [];
                $tracking_duplicate = []; // if in multiple items same then check and send once
                $requestUrl = $channel->api_url . 'rest/V1/order/' . $order['marketplace_order_id'] . '/ship';
                foreach ($order['items'] as $order_item) {
                    $items[] = ['order_item_id' => $order_item['channel_order_item_id'], 'qty' => $order_item['order_item_qty']];
                    if (!in_array($order_item['tracking_number'], $tracking_duplicate)) // if multiple items have same track_number upload once
                        $tracks[] = ['track_number' => $order_item['tracking_number'], 'title' => $order_item['courier_name'], 'carrier_code' => strtoupper($order_item['courier_type'])];
                    $tracking_duplicate[] = $order_item['tracking_number'];
                }
                if ($items) {
                    //  $body['items']=$items; // no need to embed items if shipping whole order
                    /////comment///////////
                    $body['notify'] = true;
                    $body['appendComment'] = false;
                    if ($order['order_market_status'] == 'pending_payment' && in_array(strtolower($order['payment_method']), ['sample_gateway', 'sample gateway', 'hbl pay', 'hbl'])) {
                        $body['appendComment'] = true;
                        $body['comment'] = ['comment' => 'Your payment method was online but payment could not be processed and is pending, Pay Cash on Delivery.', 'is_visible_on_front' => 1];
                    }
                    if ($tracks)
                        $body['tracks'] = $tracks;
                    /// ////////////////////////

                    $body_enclosed = json_encode($body);
                    // echo $body_enclosed; die();
                    $result = self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST'); // response will be shipment id // or incase error message variable will be sent
                    $response[$pk_id] = json_decode($result);
                    /******check if error occured it may be due to stock issue so specially in spl******/
                    if (isset($response[$pk_id]->message) && strpos(strtolower($response[$pk_id]->message), 'error') !== false)  // message always for error
                    {
                        $should_retry = self::check_if_shipment_stock_issue($order['items']); // if before shipment stock is zero it will create 1 stock , if stock is zero shipments fails
                        if ($should_retry['retry']) {
                            $result = self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST'); // response will be shipment id // or incase error message variable will be sent
                            $response[$pk_id] = json_decode($result);
                            self::check_if_retry_shipment_fails($response[$pk_id], $should_retry['items']); // if fail  then revert stock if updated for retry
                        }

                    }
                }

            }

        }
        return $response;
    }

    private static function check_if_shipment_stock_issue($items = NULL)
    {
        $retry = false;
        $items_updated = []; // to store items which are updated
        if ($items) {
            foreach ($items as $order_item) {
                $stock = self::get_stock_detail($order_item['item_sku']);
                if (isset($stock->qty) && ($stock->qty == 0 || $stock->qty < $order_item['order_item_qty'])) {
                    self::updateChannelStock(NULL, ['stock' => $order_item['order_item_qty'], 'sku' => $order_item['item_sku']]);
                    $items_updated[] = ['sku' => $order_item['item_sku'], 'stock_before' => $stock->qty, 'stock_after' => $order_item['order_item_qty']];
                    $retry = true;
                }

            }
        }
        return ['retry' => $retry, 'items' => $items_updated];
    }

    /**************if after retry shipment stil there is  error then revert stock if that updated during retry  **********/
    private static function check_if_retry_shipment_fails($response, $items_updated)
    {
        if (isset($response->message) && strpos(strtolower($response->message), 'error') !== false) {
            if ($items_updated && !empty($items_updated)) {
                foreach ($items_updated as $item) {
                    self::updateChannelStock(NULL, ['stock' => $item['stock_before'], 'sku' => $item['sku']]);
                }
            }
        }
        return;
    }

    /******
     * send email to customer about shipment
     */
    public static function notify_customer_shipping($channel, $orders, $api_response)
    {
        foreach ($orders as $order_pk_id => $order) {
            if (!isset($api_response[$order_pk_id]->message) && filter_var($api_response[$order_pk_id], FILTER_VALIDATE_INT)) {  //message variable sent by magento if fails shipping

                // die('aja');
                self::$current_channel = $channel;
                $access_token = json_decode($channel->auth_params);
                self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

                $headers = array('Content-Type: application/json',
                    'Authorization: Bearer ' . self::$current_token
                );
                $body_enclosed = json_encode(new \stdClass);
                $requestUrl = $channel->api_url . 'rest/V1/shipment/' . $api_response[$order_pk_id] . '/emails';
                self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST');
            }

        }
        return;
    }

    public static function createInvoice($channel, $orders)
    {
        $response = [];
        if ($channel && $orders) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );
            foreach ($orders as $order)  // orders array sent
            {
                $items = [];
                $requestUrl = $channel->api_url . 'rest/V1/order/' . $order['marketplace_order_id'] . '/invoice';
                foreach ($order['items'] as $order_item) {
                    $qty_to_invoice = ($order_item['order_item_qty'] - $order_item['items_invoiced']);
                    if ($qty_to_invoice > 0)
                        $items[] = ['order_item_id' => $order_item['channel_order_item_id'], 'qty' => $qty_to_invoice];

                }
                if ($items) {
                    $body = ['items' => $items];
                    $body_enclosed = json_encode($body);
                    $response[] = self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST');
                }

            }


        }
        return $response;
    }

    /**
     * update order shipment local databse if tracking updated , called from croncontroller
     */
    public static function updateOrderShipmentLocalDb($orders, $api_response, $tracking_update = true)
    {
        foreach ($orders as $order_pk_id => $order) {
            foreach ($order['items'] as $order_item) {
                $updateTracking = OrderShipment::findOne($order_item['order_shipment_id']);
                if (!isset($api_response[$order_pk_id]->message) && $tracking_update) {  //message variable sent by magento if fails shipping
                    $updateTracking->is_tracking_updated = 1;
                } elseif (isset($api_response[$order_pk_id]->message) && in_array($order['order_market_status'], ['complete', 'completed']))// if invoice created and no need of shipment and order already completed
                {
                    $updateTracking->is_tracking_updated = 1;
                }

                $updateTracking->marketplace_tracking_response = isset($api_response[$order_pk_id]) ? json_encode($api_response[$order_pk_id]) : null;
                $updateTracking->update();
            }
        }
        return;
    }

    public static function getOrderInvoice($channel)
    {
        if ($channel) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            //// parameteres
            //date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );
            //$params =isset($single_item) ? $single_item :'?searchCriteria[pageSize]=500&searchCriteria[currentPage]=1';
            $requestUrl = $channel->api_url . 'rest/V1/order/2/invoice';

            $items = [];
            $items[] = ['order_item_id' => '3', 'qty' => '30'];
            $tracks[] = ['track_number' => '2212212020002222', 'title' => 'self', 'carrier_code' => 'custom'];
            $body = ['items' => $items,
                //  'tracks'=>$tracks
            ];
            $body_enclosed = json_encode($body);
            $response = self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST');
            $data = json_decode($response);
            echo '<pre>';
            print_r($data);
            die;
        }
        return;
    }

    public static function refunOrderItem($channel, $orders)
    {
        $response = [];
        self::$current_channel = $channel;
        $access_token = json_decode($channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        //date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        //$params =isset($single_item) ? $single_item :'?searchCriteria[pageSize]=500&searchCriteria[currentPage]=1';
        // $requestUrl=$channel->api_url .'rest/V1/order/1/refund';
        foreach ($orders as $pk_id => $order)  // orders array sent
        {
            $items = [];
            $requestUrl = $channel->api_url . 'rest/V1/order/' . $order['marketplace_order_id'] . '/refund';
            foreach ($order['items'] as $order_item) {
                $items[] = ['order_item_id' => $order_item['channel_order_item_id'], 'qty' => $order_item['order_item_qty']];

            }
            if ($items) {
                $body = ['items' => $items];

                $body_enclosed = json_encode($body);
                $result = self::makePostApiCall($requestUrl, $body_enclosed, $headers, 'POST'); // response will be shipment id // or incase error message variable will be sent
                $response[$pk_id] = json_decode($result);
            }

        }
        return $response;
        /*$body_enclosed='{
                  "items": [
                    {
                      "order_item_id": 2,
                      "qty": 1
                    }
                  ],
                  "notify": true,
                  "arguments": {
                    "shipping_amount": 0,
                    "adjustment_positive": 0,
                    "adjustment_negative": 0,
                    "extension_attributes": {
                      "return_to_stock_items": [
                        1
                      ]
                    }
                  }
                }';
        $response=self::makePostApiCall($requestUrl,$body_enclosed,$headers,'POST');
        // $response=self::makeGetApiCall($requestUrl,$headers);;
        $data= json_decode($response);
        echo '<pre>';
        print_r($data);
        die;*/
    }

    public static function get_store_id($channel)
    {
        $access_token = json_decode($channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $requestUrl = $channel->api_url . '/rest/V1/store/storeViews';
        //  echo $requestUrl; die();
        $response = self::makeGetApiCall($requestUrl, $headers);
        echo "<pre>";
        print_r($response);
        die();
    }

    /***
     * @param $channel
     * @param $deal
     * @param $skus
     * @param bool $start_deal starting or ending deal
     * @return array|string
     */
    public static function updateSalePrices($channel, $deal, $skus, $start_deal = true)
    {
        $sale_prices = [];
        $delete_sale_prices = []; // the deal skus whose dates are ended
        $response = [];
        self::$current_channel = $channel;
        $access_token = json_decode($channel['auth_params']);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        if ($start_deal) // if deal is starting
        {
            foreach ($skus as $sku) {
                $sale_prices[] = [
                    'price' => $sku['deal_price'],
                    'store_id' => 0,
                    'price_from' => $deal->start_date,
                    'price_to' => $deal->end_date,
                    'sku' => $sku['sku'],
                ];

            }
        } else { // if deal is ending

            foreach ($skus as $sku) {
                $res = DealsUtil::get_nearest_active_deal($deal->channel_id, $deal->id, $sku['sku']);
                if ($res) {
                    $sale_prices[] = [
                        'price' => $res['deal_price'],
                        'store_id' => 0,
                        'price_from' => $res['start_date'],
                        'price_to' => $res['end_date'],
                        'sku' => $sku['sku'],
                    ];
                } else {
                    $delete_sale_prices[] = [
                        'price' => $sku['deal_price'],
                        'store_id' => 0,
                        'price_from' => $deal->start_date,
                        'price_to' => $deal->end_date,
                        'sku' => $sku['sku'],
                    ];
                }
            }
        }
        ////////////////
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $body_enclosed = json_encode(['prices' => $sale_prices]);
        if ($sale_prices) // sales prices to be updated
        {
            $requestUrl = $channel['api_url'] . 'rest/V1/products/special-price';
            $response[] = self::makePostApiCall($requestUrl, $body_enclosed, $headers, "POST");
        }
        if ($delete_sale_prices) // sales prices to be deleted
        {
            $body_enclosed = json_encode(['prices' => $delete_sale_prices]);
            $requestUrl = $channel['api_url'] . 'rest/V1/products/special-price-delete';
            $response[] = self::makePostApiCall($requestUrl, $body_enclosed, $headers, "POST");
        }

        return $response;
    }

    public static function remove_expired_special_prices($channel, $skus)
    {
        $to_delete = [];
        $deleted = [];
        self::$current_channel = $channel;
        $access_token = json_decode($channel['auth_params']);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        $body_enclosed = json_encode(['skus' => $skus]);
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $requestUrl = $channel['api_url'] . 'rest/V1/products/special-price-information';
        $response = self::makePostApiCall($requestUrl, $body_enclosed, $headers, "POST");
        if ($response) {
            $response = json_decode($response);
            foreach ($response as $sku) {
                $expired = self::check_if_special_price_date_expire($sku->price_to);
                if ($expired && $sku->price && $sku->price_to)
                    $to_delete[] = $sku;


            }

            if ($to_delete) {
                $body_to_del = json_encode(['prices' => $to_delete]);
                $requestUrl = $channel['api_url'] . 'rest/V1/products/special-price-delete';
                $deleted[] = self::makePostApiCall($requestUrl, $body_to_del, $headers, "POST");
            }

        }
        echo "<pre>";
        print_r($deleted);
    }

    private static function check_if_special_price_date_expire($end_date)
    {
        $current_date = date('Y-m-d H:i:s');
        $sku_date = date("Y-m-d H:i:s", strtotime($end_date));
        if ($sku_date < $current_date)
            return true;
        else
            return false;
    }


    public static function CreateGuestCart($warehouse_settings)
    {
        $curl = curl_init();

        //echo $warehouse_settings['url']."index.php/rest/V1/guest-carts/";die;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        //echo $response;die;
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return ['status' => 0, 'error' => "cURL Error #:" . $err];
        } else {
            return ['status' => 1, 'token' => str_replace('"', '', $response)];
        }
    }

    public static function AddItemsInGuestCheckout($guest_checkout_id, $warehouse_settings, $item_detail)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id/items",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{ "cartItem": { "quote_id": "' . $guest_checkout_id . '", "sku": "' . $item_detail['sku'] . '", "qty": ' . $item_detail['qty'] . ' } }',
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 640ebdf6-dc5a-1bff-c239-f093f3bb2776"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function AddGuestCartOrderShippingDetails($guest_checkout_id, $customer_information, $warehouse_settings)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id/shipping-information",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{
    "addressInformation": {
        "shippingAddress": {
            "region": "' . $customer_information['shipping_state'] . '",
            "region_id": ' . self::GetRegionId($customer_information['shipping_state'], $warehouse_settings) . ',
            "country_id": "' . $customer_information['shipping_country'] . '",
            "street": [
                "' . $customer_information['shipping_address'] . '"
            ],
            "company": "abc",
            "telephone": "' . $customer_information['shipping_number'] . '",
            "postcode": "' . $customer_information['shipping_post_code'] . '",
            "city": "' . $customer_information['shipping_city'] . '",
            "firstname": "' . $customer_information['shipping_fname'] . '",
            "lastname": "' . $customer_information['shipping_lname'] . '",
            "prefix": "address_",
            "email":"aaa@axleolio.com",
            "region_code": "' . $customer_information['shipping_state'] . '",
            "sameAsBilling": 1
        },
        "billingAddress": {
            "region": "' . $customer_information['billing_state'] . '",
            "region_id": "' . self::GetRegionId($customer_information['billing_state'], $warehouse_settings) . '",
            "country_id": "' . $customer_information['billing_country'] . '",
            "street": [
                "' . $customer_information['billing_address'] . '"
            ],
            "company": "abc",
            "telephone": "' . $customer_information['billing_number'] . '",
            "postcode": "' . $customer_information['billing_postal_code'] . '",
            "city": "' . $customer_information['billing_city'] . '",
            "firstname": "' . $customer_information['billing_fname'] . '",
            "lastname": "' . $customer_information['billing_lname'] . '",
            "prefix": "address_",
            "email":"aaa@axleolio.com",
            "region_code": "' . $customer_information['billing_state'] . '"
        },
        "shipping_method_code": "' . $warehouse_settings['shipping_method_code'] . '",
        "shipping_carrier_code": "' . $warehouse_settings['shipping_carrier_code'] . '"
    }
}',
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 1ff25c3e-d326-db8f-d63f-d01a51617aa2"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function GuestCartEstimateShippingCost($guest_checkout_id, $customer_information, $warehouse_settings)
    {


        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id/estimate-shipping-methods",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{  "address": {
      "region": "' . $customer_information['shipping_state'] . '",
      "region_id": ' . self::GetRegionId($customer_information['shipping_state'], $warehouse_settings) . ',
      "region_code": "' . $customer_information['shipping_state'] . '",
      "country_id": "' . $customer_information['shipping_country'] . '",
      "street": [
        "' . $customer_information['shipping_address'] . '"
        ],
      "postcode": "' . $customer_information['shipping_post_code'] . '",
      "city": "' . $customer_information['shipping_city'] . '",
      "firstname": "' . $customer_information['shipping_fname'] . '",
      "lastname": "' . $customer_information['shipping_lname'] . '"
  }
}',

            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 1ff25c3e-d326-db8f-d63f-d01a51617aa2"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function GuestCartData($guest_checkout_id, $warehouse_settings)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 27baffca-45d0-1981-d5ac-467dd2f58539"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function GuestCheckoutSelectPaymentMethodAndOrderNow($guest_checkout_id, $warehouse_settings)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id/order",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => '{
    "paymentMethod": {
        "method": "' . $warehouse_settings['payment_method'] . '"
    }
}',
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: ba7a91c8-ba46-a7e8-0eb7-fe983fd2205f"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function GetGuestOrderShippingMethods($guest_checkout_id, $warehouse_settings)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/guest-carts/$guest_checkout_id/shipping-methods",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 8a7a3130-a03c-7214-c614-733c6d40d72f"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function GetOrderDetail($orderId, $warehouse_settings)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "rest/V1/orders/$orderId",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 746915dd-016c-ce37-8275-4c63ab1f27ce"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public static function OrderShipmentDetail($order_id, $warehouse_settings)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouse_settings['url'] . "index.php/rest/V1/shipments?searchCriteria%5Bfilter_groups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bfield%5D=order_id&searchCriteria%5Bfilter_groups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bvalue%5D=" . $order_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $warehouse_settings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: 4b2bed97-138d-920e-72d3-888c4761e859"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return ['status' => 0, 'error' => "cURL Error #:" . $err];
        } else {
            return ['status' => 1, 'result' => $response];
        }
    }

    public static function CreateThirdPartyLog($order_id, $error_type, $response, $successfully_pushed)
    {
        $insert = new ThirdPartyOrdersLog();
        $insert->order_id = $order_id;
        $insert->third_party_type = 'magento';
        $insert->error_type = $error_type;
        $insert->response = $response;
        $insert->successfuly_pushed = $successfully_pushed;
        $insert->added_at = date('Y-m-d H:i:s');
        $insert->save();
    }

    public static function CreateThirdPartyEntry($order_id, $thirdpartyOrderId, $response)
    {
        $insert = new ThirdPartyOrders();
        $insert->order_id = $order_id;
        $insert->thirdparty_order_id = $thirdpartyOrderId;
        $insert->response = $response;
        $insert->date_added = date('Y-m-d H:i:s');
        $insert->save();
    }

    public static function GetRegionId($state, $warehouse_settings)
    {
        //echo $state;
        //self::debug($warehouse_settings);
        foreach ($warehouse_settings['states'] as $value) {
            if ($value['code'] == $state || $value['default_name'] == $state) {
                return $value['region_id'];
            }
        }

    }

    public static function GetThirdPartyOrderLog()
    {
        $get_unpushed_orders = "SELECT tpol.order_id,tpol.third_party_type,tpol.error_type,tpol.response,tpol.successfuly_pushed,tpol.added_at
                                FROM third_party_orders_log tpol
                                WHERE tpol.order_id NOT IN (
                                SELECT order_id
                                FROM third_party_orders)
                                ";
        $unpushed_orders = Yii::$app->db->createCommand($get_unpushed_orders)->queryAll();

        if (empty($unpushed_orders)) {
            echo 'There is no backlog of unpushed orders. Thank you';
            die;
        }

        $csv = "OrderId,Third party type,Error Type,Response,Successfully pushed,Added At \n";//Column headers
        foreach ($unpushed_orders as $record) {
            $csv .= $record['order_id'] . ',' . $record['third_party_type'] . ',' . str_replace(',', '', $record['error_type']) . ',' . str_replace(',', '', $record['response'])
                . ',' . $record['successfuly_pushed'] . ',' . $record['added_at'] . "\n"; //Append data to csv
        }
        $csv_handler = fopen('Email-Attachments/third-party-unpushed-orders/unpushed-orders-log-' . date('Y-m-d') . '.csv', 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return $send_email = Yii::$app->mailer->compose('@common/mail/layouts/thirdparty-unpushed-orders', ['unpushed-orders' => $get_unpushed_orders])
            ->attach('Email-Attachments/third-party-unpushed-orders/unpushed-orders-log-' . date('Y-m-d') . '.csv')
            ->setFrom('notifications@ezcommerce.io')
            ->setTo('abdullah.khan@ezcommerce.io')
            ->setCc(['mujtaba.kiani@axleolio.io'])
            ->setSubject('ThirdPary Unpushed Orders List')
            ->send();

    }

    public static function GetTrackingNumbersOfOrder($order_id, $warehouse_settings)
    {
        $orderShipmentDetail = self::OrderShipmentDetail($order_id, $warehouse_settings);
        if ($orderShipmentDetail['status'] == '1') {
            $orderShipmentDetail = json_decode($orderShipmentDetail['result'], true);

            if (isset($orderShipmentDetail['items'])) {
                $tracking_numbers = [];
                foreach ($orderShipmentDetail['items'] as $ship_detail) {
                    foreach ($ship_detail['tracks'] as $track_detail):
                        $tracking_numbers[] = $track_detail['track_number'];
                    endforeach;


                }
                return $tracking_numbers;
            } else {
                return [];
            }

        } else {
            return [];
        }
    }

    public static function get_stock_detail($sku)   // item paramater is array having sku and stock index
    {

        $access_token = json_decode(self::$current_channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
        //// parameteres
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $requestUrl = self::$current_channel->api_url . 'rest/V1/stockItems/' . urlencode($sku); //
        //$requestUrl='http://speedsports.bgj.sjl.mybluehost.me/rest/V1/products/'.$item['sku'].'/stockItems/1'; /// it is for testing uncomment when stock above when testing done
        $response = self::makeGetApiCall($requestUrl, $headers);
        return json_decode($response);

    }

    public static function set_product_as_new($channel, $sku)
    {
        //  self::debug($channel);
        $response = [];
        if ($channel && $sku) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );

            $new_from = $sku['set_new_from'];
            $new_to = $sku['set_new_to'];
            if ($sku['action'] == "remove") {
                $new_from = null;
                $new_to = null;
            }

            $custom_attributes[] = ['attribute_code' => 'news_from_date', 'value' => $new_from];
            $custom_attributes[] = ['attribute_code' => 'news_to_date', 'value' => $new_to];
            $product = ['product' => ['custom_attributes' => $custom_attributes]];
            $body = json_encode($product);
            // echo $body; die();
            $requestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/all/V1/products/' . urlencode($sku['sku']);
            // $requestUrl=rtrim(self::$current_channel->api_url,'/') .'/rest/default/V1/products/'.urlencode($sku['sku']);
            //$requestUrl='http://speedsports.bgj.sjl.mybluehost.me/rest/V1/products/rest/all/V1/products/'.$sku['sku'];
            $response = self::makePostApiCall($requestUrl, $body, $headers);
            if ($response) {
                $response = json_decode($response);
                // self::debug($response);
                if (isset($response->message))
                    return ['status' => 'failure', 'msg' => $response->message, 'error' => 1, 'updated' => 0];
                else
                    return ['status' => 'success', 'msg' => 'updated', 'error' => 0, 'updated' => 1];
            }
            return ['status' => 'failure', 'msg' => 'failed to update', 'error' => 1, 'updated' => 0];

        }
        return $response;
    }

    /*************set sale cat id************/
    private static function set_sale_cat_id_body()
    {

        // get sale category id

        $custom_attributes[] = ['attribute_code' => 'category_ids', 'value' => [self::$sale_category_id]];
        $product = ['product' => ['custom_attributes' => $custom_attributes]];
        $body = json_encode($product);

        return $body;

    }

    public static function headers()
    {
        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        return $headers;
    }


    /********deal set category simple product ********/
    public static function setSaleCategory($channel)
    {
        $response_log = [];
        if ($channel) {

            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();

            self::get_sale_cat_id();
            if (!self::$sale_category_id)
                return;

            //get page number of pagination from setting table
            self::get_sale_cat_offset(self::$setting_table_set_cat_name);
            /******** Get Product Via Magento having greater sale expiry date tha today ********/
            $products = self::get_products_with_greater_expiry_sale_date();
          //  self::debug($products);
            $product_arrays = [];
            $is_parent = false;

            if (isset($products->items) && $products->items) {
                foreach ($products->items as $product) {
                    /******** Get Parent Product of Child ********/
                    $get_product_id = "SELECT products.parent_sku_id FROM channels_products
                      INNER JOIN products ON channels_products.channel_sku = products.sku
                      WHERE channels_products.channel_sku = '" . $product->sku . "' and channels_products.channel_id = '" . $channel->id . "'";
                    $get_product_sku = Yii::$app->db->createCommand($get_product_id)->queryOne();
                    // If Parent Exist

                    if ($get_product_sku['parent_sku_id']) {
                        $products_parent_sku = Products::find()->select('sku')->where(['id' => $get_product_sku['parent_sku_id']])->asArray()->all();
                        if ($products_parent_sku) {
                            $parent_product = self::get_product_by_sku($products_parent_sku[0]['sku']);

                            /******** if parent sku exist ********/
                            if ($parent_product->items) {
                                $is_parent = true;
                                $product_arrays[] = $parent_product->items[0];
                            }
                        }
                    }
                    /******** parent and Child Sku ********/
                    if ($is_parent) {
                        $is_parent = false;
                    } else {
                        $product_arrays[] = $product;

                    }
                }
                $response_log = self::assign_sale_category_product($product_arrays);
            }

            /**********set pagination offset ********/
            $total_page_products = ceil($products->total_count / 25);
            if(self::$products_sale_cat_page > $total_page_products)
                self::set_sale_cat_offset(self::$setting_table_set_cat_name,true);
            else
                self::set_sale_cat_offset(self::$setting_table_set_cat_name);

        }
        return $response_log;
    }

    /********deal set category simple product ********/
    public static function assign_sale_category_product($products)
    {
        $response_log = [];
        /******** category assign id set in Post Array ********/
        $body = self::set_sale_cat_id_body();

        /******** Simpe Product and Parent product get from Magento detail ********/
        if (isset($products) && !empty($products)) {

            foreach ($products as $product) {

                $categories = $product->extension_attributes->category_links;
                $is_sale_cat_assigned = false;

                foreach ($categories as $key => $val) {
                    if ($val->category_id == self::$sale_category_id) {
                        $is_sale_cat_assigned = true;
                    }
                }
                /******** Set Sale Category in Product Magento ********/
                if (!$is_sale_cat_assigned) {
                    $response_log[$product->sku]['original_categories_before_update'] = $categories;
                    $setRequestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/all/V1/products/' . urlencode($product->sku);
                    $cat_assign_response = self::makePostApiCall($setRequestUrl, $body, self::headers());
                    $add_log = json_decode($cat_assign_response);
                    if (isset($add_log->message)) {
                        $type = "assign_sale_cat_product";
                        $log = ['type' => $type, 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'remove unset category in magento', 'additional_info' => '', 'response' => $add_log, 'log_type' => 'error'];
                        LogUtil::add_log($log);
                    }

                    //  $response_log[$product->sku]['assign_sale_cat'] = ['request' => $body, 'response' => $cat_assign_response];
                    $response_log[$product->sku]['assign_sale_cat'] = ['request' => $body, 'response' => $cat_assign_response];

                }
            }
        }
        return $response_log;
    }

    /************Remove category of sale from products
     * which dont have sale price or sale date expired****************/
    public static function unsetSaleCategory($channel = null)
    {
        $response_log = [];
        if ($channel) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            // get sale category id
            self::get_sale_cat_id();

            if (!self::$sale_category_id)
                return;

            //get page number of pagination from setting table
             self::get_sale_cat_offset(self::$setting_table_unset_name);

            //////fetch products of sale category
            $products = self::fetch_specific_cat_products(self::$sale_category_id);

            //self::debug($products);

            if ($products->items)
            {
                foreach ($products->items as $product) {
                    /************if configurable type of product***********/
                    if ($product->type_id == 'configurable')  // if varations parent/child
                    {
                        $product_id_child = $product->extension_attributes->configurable_product_links;
                        $is_variation_expire_date = false;

                        if (isset($product_id_child) && !empty($product_id_child)) {
                            foreach ($product_id_child as $variation_product) {

                                $variation_product = self::get_product_detail_by_id($variation_product);
                                if ($variation_product->items) {
                                    if (self::check_special_price_validty($variation_product->items[0]) === false) { // if special price date expired
                                        $response = self::remove_sale_category($variation_product->items[0]);
                                        if ($response)
                                            $response_log[] = $response;
                                    } else {
                                        $is_variation_expire_date = true;
                                    }// if variation special date biggest
                                }
                            }

                            //////  if variation special date not biggest
                            if ($is_variation_expire_date == false) {
                                if ($product) {
                                    if (self::check_special_price_validty($product) === false) // if special price date expired
                                    {
                                        $response = self::remove_sale_category($product);
                                        if ($response)
                                            $response_log[] = $response;
                                    }

                                }
                            }

                        }

                    } else {
                        /************if single or virtual type of product***********/
                        if (self::check_special_price_validty($product) === false) // if special price date expired
                        {
                            $response_log = self::remove_sale_category($product);  // remove sale category from product on marketplace
                        }
                    }
                }

                  /**********Update pagination offset ********/

                     $total_page_products = ceil($products->total_count / 25);
                     if(self::$products_sale_cat_page > $total_page_products)
                         self::set_sale_cat_offset(self::$setting_table_unset_name,true);
                     else
                         self::set_sale_cat_offset(self::$setting_table_unset_name);



            }
        }
        return $response_log;
    }


    /**********Add Pagination in Setting ********/
    private function set_sale_cat_offset($name, $reset = null)
    {
        //get from setting table page number
        $setting = Settings::find()->where(['name' => $name])->one();
        $setting->name = $name;
        $setting->value = $reset ? 1: ($setting->value + 1);
        $setting->created_at = time();
        $setting->updated_at = time();
        $setting->update(false);



    }

    private static function get_sale_cat_offset($name)
    {

        $setting = Settings::find()->where(['name' => $name])->one();
        if ($setting) {
            self::$products_sale_cat_page=$setting->value;
        } else {
            $create_row = new Settings();
            $create_row->name = $name;
            $create_row->value = 1;
            $create_row->created_at = time();
            $create_row->updated_at = time();
            $create_row->save(false);
        }
        return self::$products_sale_cat_page;

    }


    /**********remove sale category from single and virtual product********/
    private static function remove_sale_category($product)
    {
        $response_log = [];
        /******** Header ********/
        $headers = self::headers();

        $is_sale_cat_assigned = false;
        $original_categories = $product->extension_attributes->category_links;
        /********check category if exist********/
        $all_cat_other_than_sale = [];
        foreach ($original_categories as $key => $val) {
            if ($val->category_id == self::$sale_category_id)
                $is_sale_cat_assigned = true;
            else
                $all_cat_other_than_sale[] = $val->category_id;

        }
        /********check category if exist so unset********/
        /*if (in_array(self::$sale_category_id, $arr))
        {
            unset($arr[array_search(self::$sale_category_id,$arr)]);
        }*/


        /********if sale cat assigned ********/
        if ($is_sale_cat_assigned == true) {
            $response_log[$product->sku]['original_categories_before_update'] = $original_categories;
            /*************first remove all categories****************/
            $custom_attributes = ['category_links' => []];
            $delete_category = ['product' => ["extension_attributes" => $custom_attributes]];
            $body = json_encode($delete_category);
            $setRequestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/default/V1/products/' . urlencode($product->sku);
            $del_response = self::makePostApiCall($setRequestUrl, $body, $headers);
            /********remove  all category URL********/
            $response_log[$product->sku]['deleted_all_cats'] = ['request' => $body, 'response' => $del_response];
            /****************assign categories again excluding sale cat*******************/
            $reset_categories[] = ['attribute_code' => 'category_ids', 'value' => $all_cat_other_than_sale];
            $product_categories = ['product' => ['custom_attributes' => $reset_categories]];
            $body_reset = json_encode($product_categories);
            $cat_assign_response = self::makePostApiCall($setRequestUrl, $body_reset, $headers);
            /********add category else sale category post********/
            $add_log = json_decode($cat_assign_response);
            /****************categories assign if post request has error *******************/
            if (isset($add_log->message)) {
                $type = "sale_unset_cat_products";
                $log = ['type' => $type, 'entity_type' => 'channel', 'entity_id' => self::$current_channel->id, 'request' => 'remove unset category in magento', 'additional_info' => '', 'response' => $add_log->message, 'log_type' => 'error'];
                LogUtil::add_log($log);
            }

            $response_log[$product->sku]['reassign_cats_without_sale_cat'] = ['request' => $body_reset, 'response' => $cat_assign_response];
        }
        return $response_log;
    }


    private static function check_special_price_validty($product)
    {
        if (isset($product->custom_attributes)) {
            foreach ($product->custom_attributes as $attribute) {
                if (isset($attribute->attribute_code) && $attribute->attribute_code == "special_to_date" && ($attribute->value >= date('Y-m-d H:i:s')))
                    return true;

            }

            return false;
        }
    }

    private static function get_sale_cat_id()
    {
        if (self::$sale_category_id) {
            return self::$sale_category_id;
        }
        $categories = self::ChannelCategories(self::$current_channel, false);
        //self::debug($categories);
        if ($categories) {
            foreach ($categories->children_data as $cat) {
                // self::debug($cat);
                if (strtolower($cat->name) == 'sale' && $cat->is_active) {
                    return self::$sale_category_id = $cat->id;
                }
            }
        }
        return false;

    }

    /*******fetch specific category products******/
    private static function fetch_specific_cat_products($cat_id)
    {

        $headers = array('Content-Type: application/json',
            'Authorization: Bearer ' . self::$current_token
        );
        $params = '?searchCriteria[filterGroups][0][filters][0][field]=category_id&searchCriteria[filterGroups][0][filters][0][value]=' . $cat_id . '&searchCriteria[filterGroups][0][filters][0][conditionType]=eq&searchCriteria[sortOrders][0][field]=created_at&searchCriteria[sortOrders][0][direction]=DESC&searchCriteria[pageSize]=25&searchCriteria[currentPage]=' . self::$products_sale_cat_page;
        //  $params =isset($single_item) ? $single_item :'?searchCriteria[pageSize]=500&searchCriteria[currentPage]=93';
        $requestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/V1/products' . $params;
        $response = self::makeGetApiCall($requestUrl, $headers);
        // self::debug($response);
        if ($response) {
            $products = json_decode($response);
            return $products;
        }

        return false;

    }

    /*******fetch product which has greater sale expriy date than today ******/
    private static function get_products_with_greater_expiry_sale_date()
    {
       // $params = '?searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bfield%5D=special_to_date&%20searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bvalue%5D=' . date('Y-m-d%20H:i:s') . '&%20searchCriteria%5BfilterGroups%5D%5B0%5D%5Bfilters%5D%5B0%5D%5Bcondition_type%5D=gteq&%20searchCriteria%5BpageSize%5D=25&%20searchCriteria%5BcurrentPage%5D=' . self::$products_set_sell_category_page;
        $params = '?searchCriteria[filterGroups][0][filters][0][field]=special_to_date&searchCriteria[filterGroups][0][filters][0][value]='.urlencode(date('Y-m-d H:i:s')).'&searchCriteria[filterGroups][0][filters][0][condition_type]=gteq&searchCriteria[pageSize]=25&searchCriteria[currentPage]=' . self::$products_sale_cat_page;

        $requestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/V1/products' . $params;

        $response = self::makeGetApiCall($requestUrl, self::headers());
        // self::debug($response);
        if ($response) {
            $products = json_decode($response);
            return $products;
        }

        return false;

    }


    /*******fetch specific category product Details by id******/
    private static function get_product_detail_by_id($product_id = null)
    {
        $requestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=entity_id&searchCriteria[filterGroups][0][filters][0][value]=' . urlencode($product_id);
        $response = self::makeGetApiCall($requestUrl, self::headers());
        // self::debug($response);
        if ($response) {
            $products = json_decode($response);
            return $products;
        }
        return false;

    }

    /*******fetch specific category product Details by id******/
    private static function get_product_by_sku($sku = null)
    {
        $requestUrl = rtrim(self::$current_channel->api_url, '/') . '/rest/V1/products?searchCriteria[filterGroups][0][filters][0][field]=sku&searchCriteria[filterGroups][0][filters][0][value]=' . urlencode($sku);
        $response = self::makeGetApiCall($requestUrl, self::headers());
        // self::debug($response);
        if ($response) {
            $products = json_decode($response);
            return $products;
        }
        return false;

    }


    /*******Add log in Sale Cat Modification Log******/
    public static function sale_cat_modification_log($log, $type)
    {
        if (isset($log) && $log) {
            foreach ($log as $skus => $response) {
                $log = new SaleCatModificationLog();
                $log->type = $type;
                if ($type == "unset-sale") {

                    $sku = array_keys($response);
                    $sku = $sku[0];
                    $log->sku = $sku;
                    $log->response = json_encode($response[$sku]['reassign_cats_without_sale_cat']['response']);

                } elseif ($type == "set-sale") {
                    $log->sku = $skus;
                    $log->response = json_encode($response['assign_sale_cat']['response']);
                }
                $log->created_at = date('Y-m-d H:i:s');
                $log->save();
            }
        }
        return;
    }


    public static function pagination_value_set_sell($total_page_products)
    {
        if (self::$products_set_sell_category_page >= $total_page_products) {
            $settings = Settings::find()->where(['name' => self::$setting_table_set_cat_name])->one();
            if ($settings) {
                $settings->value = 1;
                $settings->update();
                return self::$products_set_sell_category_page = 1;
            } else {
                return false;
            }
        }
    }

    public static function pagination_value_unset_sell($total_page_products)
    {
        if (self::$products_unset_sale_category_page >= $total_page_products) {
            $settings = Settings::find()->where(['name' => self::$setting_table_unset_name])->one();
            if ($settings) {
                $settings->value = 1;
                $settings->update();
                return self::$products_unset_sale_category_page = 1;
            } else {
                return false;
            }
        }
    }

        //********** get products to delete from channel **********//
    public static function getProductsToDelete($channel)
    {

        $products=DeleteChannelProducts::find()->where(['channel_id'=>$channel->id,'processed'=>0])->limit(30)->asArray()->all();
        return $products;
    }
    /*****************delete channel products*************/
    public static function deleteChannelProducts($channel,$products){

        self::$current_channel = $channel;
        $access_token = json_decode($channel->auth_params);
        self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
           // self::debug($products);
        if($products){
            foreach($products as $product){
                $get_product = self::get_product_by_sku($product['sku']); //call product via sku
                //self::debug($get_product);
                if(empty($product['sku']))
                    continue;

                if(isset($get_product->items[0]) && !empty($get_product->items[0]))
                {
                    $requestUrl = rtrim(self::$current_channel->api_url, '/').'/rest/default/V1/products/'.urlencode($product['sku']);
                    $response = self::makePostApiCall($requestUrl, array(), self::headers(), "DELETE");
                    //self::debug($response);
                    if ($response) {
                        // update an existing row of data
                        $res=json_decode($response);
                        //self::debug($res);
                        $update_delete_channel_product = DeleteChannelProducts::findOne($product['id']);
                        $update_delete_channel_product->product_data= json_encode($get_product->items[0]);
                        $update_delete_channel_product->response = $response;
                        if(isset($res->message))
                            $update_delete_channel_product->error = 1;
                        else
                            $update_delete_channel_product->processed = 1;

                        $update_delete_channel_product->update();
                    }
                }
            }
        }
    }


    public static function ProductMagentoInfo($channel = null, $single_item = null)
    {
        if (self::$products_fetch_page > 50) {  // if trapped in loop end fetching
            die('too many loops ending ');
        }
        if ($channel) {
            self::$current_channel = $channel;
            $access_token = json_decode($channel->auth_params);
            self::$current_token = isset($access_token->access_token) ? $access_token->access_token : self::refreshToken();
            $headers = array('Content-Type: application/json',
                'Authorization: Bearer ' . self::$current_token
            );

            $params = isset($single_item) ? urlencode($single_item) : '?searchCriteria[pageSize]=500&searchCriteria[currentPage]=' . self::$products_fetch_page;
            $requestUrl = rtrim($channel->api_url, '/') . '/rest/V1/products/' . $params;
            $response = self::  makeGetApiCall($requestUrl, $headers);
            $response=json_decode($response);

            if ($response) {
                $products = $response;
                self::organizeProductInfo($products, $single_item ? $single_item : "");
                if (isset($products->total_count) && $products->total_count > 500) {
                    self::$products_fetch_page++;
                    $total_page = ceil($products->total_count / 500);
                    if (self::$products_fetch_page <= $total_page)  //pagination
                    {
                        self::ProductMagentoInfo($channel, null);
                    } else {
                        self::make_product_magento_relationship(); // parent child relationship update local table
                    }
                } else {
                    self::make_product_magento_relationship(); // parent child relationship update local table
                }
            }
        }
        return;
    }

    //organize products attribute Info data and store in database///////////
    public static function organizeProductInfo($products, $single_item = null)
    {
        $items = NULL;
        if (isset($products->items))
            $items = $products->items;
        else
            $items[] = $products;

        if ($items) {
            foreach ($items as $product) {
                /************if configurable type of product***********/

                if ($product->type_id == "configurable")  // mean the product has variations
                {
                    self::$parent_child_relationship[$product->sku] = isset($product->extension_attributes->configurable_product_links) ? $product->extension_attributes->configurable_product_links : [];
                }

                $img_prefix = self::$current_channel->api_url . "/pub/media/catalog/product";
                $cat_name = NULL;
                $image = NULL;
                $brand = NULL;
                $color = NULL;
                $size = NULL;
                $image = null;
                $desc = null;

                /// get category
                if (isset($product->extension_attributes->category_links)) {
                    $cat_name = self::getCategory($product->extension_attributes->category_links);
                }
                /// get brand, color, size
                if (isset($product->custom_attributes)) {
                    foreach ($product->custom_attributes as $attribute) {
                        if (isset($attribute->attribute_code) && $attribute->attribute_code == "mill_name" && $attribute->value && $attribute->value != "")
                            $brand = self::assign_brand_to_product($attribute->value); // get brand of product
                        elseif (isset($attribute->attribute_code) && $attribute->attribute_code == "color" && $attribute->value && $attribute->value != "")
                            $color =  self::assign_product_to_color($attribute->value, "color"); // get color of product
                        elseif (isset($attribute->attribute_code) && $attribute->attribute_code == "size" && $attribute->value && $attribute->value != "")
                            $size =  self::assign_product_to_color($attribute->value, "size"); // get size of product
                        elseif (isset($attribute->attribute_code) && $attribute->attribute_code == "description" && $attribute->value && $attribute->value != "")
                            $desc =  $attribute->value; // get desc of product
                    }
                }

                if (isset($product->media_gallery_entries[0]->file))
                    $image = $img_prefix . $product->media_gallery_entries[0]->file;
                elseif (isset($product->media_gallery_entries[1]->file))
                    $image = $img_prefix . $product->media_gallery_entries[0]->file;


                // if sku exist so update otherwise save into table
                $product_exists = ProductMagentoAttribute::findone(['sku'=>$product->sku]);
                if($product_exists)
                {   //update data
                    $product_exists->parent_sku = null;
                    $product_exists->product_magento_id = ($product->id) ? $product->id : null;
                    $product_exists->sku = ($product->sku) ? $product->sku : null;
                    $product_exists->name = isset($product->name) ? $product->name : "";
                    $product_exists->category = $cat_name;
                    $product_exists->brand = $brand;
                    $product_exists->color = $color;
                    $product_exists->size = $size;
                    $product_exists->description = $desc;
                    $product_exists->price = isset($product->price) ? $product->price : 0;
                    $product_exists->image_url = $image;
                    $product_exists->created_at=time();
                    $product_exists->updated_at = time();
                    $product_exists->update(false);
                }
                else
                {   //insert data
                    $product_add = new ProductMagentoAttribute();
                    $product_add->product_magento_id = ($product->id) ? $product->id : null;
                    $product_add->parent_sku =  null;
                    $product_add->sku = ($product->sku) ? $product->sku : null;
                    $product_add->name = isset($product->name) ? $product->name : "";
                    $product_add->category = $cat_name;
                    $product_add->brand = $brand;
                    $product_add->color = $color;
                    $product_add->size = $size;
                    $product_add->description = $desc;
                    $product_add->price = isset($product->price) ? $product->price : 0;
                    $product_add->image_url = $image;
                    $product_add->created_at=time();
                    $product_add->updated_at = time();
                    $product_add->save(false);
                }
            }
        }
        return;
    }

    //get category from magento
    public static function getCategory($categories){

        $categories_name = [];
        foreach ($categories as $category) {
            $requestUrl = rtrim(self::$current_channel->api_url, '/').'/rest/default/V1/categories/'.urlencode($category->category_id);
            $response = self::makePostApiCall($requestUrl, array(), self::headers(), "GET");

            if ($response) {
                $res = json_decode($response);
                $categories_name[] = $res->name;
            }
        }
        $cat_list = implode(', ', $categories_name);//comma

        return $cat_list;
    }

    //get product color, size, desc, from magento product api
    public static function assign_product_to_color($attribute_code, $attribute){
        $color_name = null;
         $requestUrl = rtrim(self::$current_channel->api_url, '/').'/rest/default/V1/products/attributes/'.urlencode($attribute);
         $response = self::makePostApiCall($requestUrl, array(), self::headers(), "GET");
            if ($response) {
                $res = json_decode($response);
                if (isset($res->options)) {
                    foreach ($res->options as $attribute) {
                        if (isset($attribute->value) && $attribute->value == $attribute_code && $attribute->value && $attribute->value != "")
                            $color_name = $attribute->label;
                    }
                }
            }
        return $color_name;
    }

    // make parent child relation product_magento_attribute table
    private static function make_product_magento_relationship()
    {
        // echo "<pre>";
        //print_r($relationship); die();
        if (self::$parent_child_relationship) {
            $connection = Yii::$app->db;
            foreach (self::$parent_child_relationship as $parent => $child) {
                if (!$child || !is_array($child))
                    continue;

                $child_pk_ids = implode(',', $child);
                $sql = "SET @sku := (SELECT `sku` FROM `product_magento_attribute` WHERE `sku`='" . $parent . "');
                    UPDATE `product_magento_attribute`
                      SET 
                         `product_magento_attribute`.`parent_sku`=@sku
                      WHERE 
                        `product_magento_attribute`.`product_magento_id` IN($child_pk_ids) ";
                // echo $sql;die();
                     $connection->createCommand($sql)->execute();
            }
        }
        return;
    }

}
