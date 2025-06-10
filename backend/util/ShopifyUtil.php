<?php
namespace backend\util;

use Codeception\Module\Yii1;
use common\models\Category;
use common\models\Channels;
use common\models\GeneralReferenceKeys;
use libphonenumber\ValidationResult;
use Yii;
use yii\db\Exception;
use yii\web\Controller;
use backend\util\OrderUtil;
use backend\util\CatalogUtil;
class ShopifyUtil
{
    private static $current_token;  // save current calling token
    private static $current_channel;

    private function make_get_request($requestUrl,$headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        return $result;  //json format
    }

    private function make_post_request($requestUrl,$body_enclosed,$headers,$method=null)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $requestUrl);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $body_enclosed);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method ? $method:"PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    ////////////////////////////////////////////////////////////////////////////
    /// //////////////////////category////////////////////////////////////////
    /// ///////////////////////////////////////////////////////////////////////

    public static function ChannelCategories($channel=null)
    {
        self::$current_channel=$channel;
      // $json='{"custom_collections":[{"id":149593882714,"handle":"fight-gloves","title":"fight gloves","updated_at":"2019-10-01T12:20:30+08:00","body_html":"","published_at":"2019-10-01T11:33:41+08:00","sort_order":"best-selling","template_suffix":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149593882714"},{"id":149593849946,"handle":"fitness","title":"fitness","updated_at":"2019-10-01T11:37:03+08:00","body_html":"","published_at":"2019-10-01T11:33:41+08:00","sort_order":"best-selling","template_suffix":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149593849946"},{"id":149593653338,"handle":"gloves","title":"Gloves","updated_at":"2019-10-01T11:34:34+08:00","body_html":"","published_at":"2019-10-01T11:33:41+08:00","sort_order":"best-selling","template_suffix":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149593653338"},{"id":149590933594,"handle":"frontpage","title":"Home page","updated_at":"2019-10-01T12:20:30+08:00","body_html":null,"published_at":"2019-10-01T10:03:44+08:00","sort_order":"best-selling","template_suffix":null,"published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149590933594"},{"id":149593784410,"handle":"protective","title":"protective","updated_at":"2019-10-01T11:36:48+08:00","body_html":"","published_at":"2019-10-01T11:33:41+08:00","sort_order":"best-selling","template_suffix":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149593784410"},{"id":149593915482,"handle":"sparring-gloves","title":"sparring gloves","updated_at":"2019-10-01T12:20:30+08:00","body_html":"","published_at":"2019-10-01T11:33:41+08:00","sort_order":"best-selling","template_suffix":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Collection\/149593915482"}]}';
     // var_dump(json_decode($json));


        if($channel)
        {
            $headers= [ 'Content-Type: application/json',
                        'Authorization: Basic '.  base64_encode($channel->api_user . ":" . $channel->api_key )
                      ];
            $requestUrl=$channel->api_url .'/custom_collections.json';
            $response=self::make_get_request($requestUrl,$headers);
            if($response)
            {
                self::saveChannelCategories(json_decode($response));
            }
        }
        return ;

    }

    private function saveChannelCategories($categories=null)
    {

        if(isset($categories->custom_collections))
        {
            foreach ($categories->custom_collections as $cat)
            {
                if($cat->title!="Home page")
                {
                    $record['cat_name']=$cat->title;
                    $record['is_active']=1;
                    $record['parent_cat_id']=0;
                    $record['channel']=self::$current_channel;
                    $response=CatalogUtil::saveCategories($record);
                    $find_ref_keys=GeneralReferenceKeys::findOne(   ['table_name'=>'category',
                                                                    'key'=>'shopify_collection_id',
                                                                    'table_pk'=>$response['id'],
                                                                    'channel_id'=>self::$current_channel->id]
                                                                 );
                    if(!$find_ref_keys)
                    {
                        $add_presta_cat_id = new GeneralReferenceKeys();
                        $add_presta_cat_id->channel_id = self::$current_channel->id;
                        $add_presta_cat_id->table_name = 'category';
                        $add_presta_cat_id->table_pk = $response['id'];
                        $add_presta_cat_id->key = 'shopify_collection_id';
                        $add_presta_cat_id->value = $cat->id;
                        $add_presta_cat_id->added_at = date('Y-m-d H:i:s');
                        $add_presta_cat_id->save(false);
                    }
                }
            }
        }
        return ;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// ///////////////////////////////// get products/////////////////////////////////////////////////////////
    /// //////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function ChannelProducts($channel=null,$single_item=null)
    {
        //self::$current_channel=$channel;
      //  $json='{"products":[{"id":4178927485018,"title":"adidas Adi-Speed 501 Pro Training Gloves","body_html":"\u003cp\u003eadidas Adi-Speed 501 Pro Training gloves, are top of the line training gloves designed for Professional Boxers and advanced fitness enthusiasts. These gloves use innovative Cloud MVE foam that is extremely protective and has exceptional ability to absorb heavy punches. This model has one block design that covers the whole hand from the finger-tips to the forearm. This offers extra protection to the wrist as well. The glove padding is hand molded and extreme care is taken to ensure the glove will form a perfect wrist to both protect the hand and deliver full power. The shape of this glove ensures comfortable fit the very first time you use it. No need to break in the gloves, they are extremely soft and conform to your hand the first time you wear them. They use Velcro closure for quick on and off.\u003c\/p\u003e\n\u003cp\u003e\u003cimg src=\"https:\/\/www.usboxing.net\/img\/cms\/sales%20sheets\/501%20pro.PNG\" alt=\"\" width=\"1121\" height=\"795\"\u003e\u003c\/p\u003e","vendor":"Kiani Gloves","product_type":"","created_at":"2019-10-01T14:45:55+08:00","handle":"adidas-adi-speed-501-pro-training-gloves","updated_at":"2019-10-01T14:46:00+08:00","published_at":"2019-10-01T14:40:57+08:00","template_suffix":null,"tags":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Product\/4178927485018","variants":[{"id":30337800699994,"product_id":4178927485018,"title":"14oz \/ BLACK\/GOLD","price":"149.95","sku":"ADISBG501Black\/Gold14OZ","position":1,"inventory_policy":"deny","compare_at_price":null,"fulfillment_service":"manual","inventory_management":"shopify","option1":"14oz","option2":"BLACK\/GOLD","option3":null,"created_at":"2019-10-01T14:45:56+08:00","updated_at":"2019-10-01T14:45:56+08:00","taxable":true,"barcode":"","grams":0,"image_id":null,"weight":0.0,"weight_unit":"kg","inventory_item_id":31770601062490,"inventory_quantity":13,"old_inventory_quantity":13,"requires_shipping":true,"admin_graphql_api_id":"gid:\/\/shopify\/ProductVariant\/30337800699994"},{"id":30337800732762,"product_id":4178927485018,"title":"16oz \/ BLACK\/GOLD","price":"149.95","sku":"ADISBG501Black\/Gold16OZ","position":2,"inventory_policy":"deny","compare_at_price":null,"fulfillment_service":"manual","inventory_management":"shopify","option1":"16oz","option2":"BLACK\/GOLD","option3":null,"created_at":"2019-10-01T14:45:56+08:00","updated_at":"2019-10-01T14:45:56+08:00","taxable":true,"barcode":"","grams":0,"image_id":null,"weight":0.0,"weight_unit":"kg","inventory_item_id":31770601095258,"inventory_quantity":21,"old_inventory_quantity":21,"requires_shipping":true,"admin_graphql_api_id":"gid:\/\/shopify\/ProductVariant\/30337800732762"}],"options":[{"id":5453316522074,"product_id":4178927485018,"name":"weight","position":1,"values":["14oz","16oz"]},{"id":5453316554842,"product_id":4178927485018,"name":"Color","position":2,"values":["BLACK\/GOLD"]}],"images":[{"id":12845919666266,"product_id":4178927485018,"position":1,"created_at":"2019-10-01T14:46:00+08:00","updated_at":"2019-10-01T14:46:00+08:00","alt":null,"width":1121,"height":795,"src":"https:\/\/cdn.shopify.com\/s\/files\/1\/0127\/2168\/4570\/products\/501_pro.png?v=1569912360","variant_ids":[],"admin_graphql_api_id":"gid:\/\/shopify\/ProductImage\/12845919666266"}],"image":{"id":12845919666266,"product_id":4178927485018,"position":1,"created_at":"2019-10-01T14:46:00+08:00","updated_at":"2019-10-01T14:46:00+08:00","alt":null,"width":1121,"height":795,"src":"https:\/\/cdn.shopify.com\/s\/files\/1\/0127\/2168\/4570\/products\/501_pro.png?v=1569912360","variant_ids":[],"admin_graphql_api_id":"gid:\/\/shopify\/ProductImage\/12845919666266"}},{"id":4178637783130,"title":"Test Product","body_html":"Test Product","vendor":"Kiani Gloves","product_type":"","created_at":"2019-10-01T10:11:01+08:00","handle":"test-product","updated_at":"2019-10-01T12:20:29+08:00","published_at":"2019-10-01T10:09:16+08:00","template_suffix":null,"tags":"","published_scope":"web","admin_graphql_api_id":"gid:\/\/shopify\/Product\/4178637783130","variants":[{"id":30336550371418,"product_id":4178637783130,"title":"Default Title","price":"5000.00","sku":"1","position":1,"inventory_policy":"deny","compare_at_price":"4000.00","fulfillment_service":"manual","inventory_management":"shopify","option1":"Default Title","option2":null,"option3":null,"created_at":"2019-10-01T10:11:02+08:00","updated_at":"2019-10-01T12:20:29+08:00","taxable":true,"barcode":"123456","grams":1000,"image_id":null,"weight":1.0,"weight_unit":"kg","inventory_item_id":31768976293978,"inventory_quantity":10,"old_inventory_quantity":10,"requires_shipping":true,"admin_graphql_api_id":"gid:\/\/shopify\/ProductVariant\/30336550371418"}],"options":[{"id":5452922683482,"product_id":4178637783130,"name":"Title","position":1,"values":["Default Title"]}],"images":[{"id":12844307677274,"product_id":4178637783130,"position":1,"created_at":"2019-10-01T10:11:05+08:00","updated_at":"2019-10-01T10:11:05+08:00","alt":null,"width":512,"height":512,"src":"https:\/\/cdn.shopify.com\/s\/files\/1\/0127\/2168\/4570\/products\/airplane.png?v=1569895865","variant_ids":[],"admin_graphql_api_id":"gid:\/\/shopify\/ProductImage\/12844307677274"}],"image":{"id":12844307677274,"product_id":4178637783130,"position":1,"created_at":"2019-10-01T10:11:05+08:00","updated_at":"2019-10-01T10:11:05+08:00","alt":null,"width":512,"height":512,"src":"https:\/\/cdn.shopify.com\/s\/files\/1\/0127\/2168\/4570\/products\/airplane.png?v=1569895865","variant_ids":[],"admin_graphql_api_id":"gid:\/\/shopify\/ProductImage\/12844307677274"}}]}';

         if($channel)
         {
             self::$current_channel=$channel;
             $headers= [ 'Content-Type: application/json',
                 'Authorization: Basic '.  base64_encode($channel->api_user . ":" . $channel->api_key )
             ];

             $params=isset($single_item) ? "/api/2021-04/products/" . $single_item['product_id'] . ".json" : "/products.json?status=active";
             $requestUrl=$channel->api_url . $params;
             $response=self::make_get_request($requestUrl,$headers);
             if($response)
             {

                 $data=json_decode($response);
                 //self::debug($data);
                return  self::saveChannelProducts($data,$single_item ? $single_item : null);
             }


         }
        return;

    }

    private function saveChannelProducts($data=null,$single_item=null)
    {


          $return_single = array(); // if single item in params to be requested then return that
            if(isset($data->products))
                $raw_data=$data->products;
            else if(isset($data->product))
                $raw_data[]=$data->product;
            else
                $raw_data=array();


                // $cat=CatalogUtil::getdefaultParentCategory();
                foreach($raw_data as $product)
                {
                    $image=null;
                    if(isset($product->image->src)){
                        $image=$product->image->src;
                    }elseif(isset($product->images[0]->src)){
                        $image=$product->images[0]->src;
                    }
                    $parent_sku_id=self::save_parent_product($product,$image);

                    if(isset($product->variants) && is_array($product->variants))
                    {
                        foreach ($product->variants as $key=>$val)
                        {
                            self::$fetched_skus[] = trim($val->sku); // del those skus which are deleted from platform
                            $prepare=[
                                'parent_sku_id' => $parent_sku_id? $parent_sku_id : NULL,
                                'category_id'=>null,//isset($cat->id) ? $cat->id:0,//self::getShopifyProductCatId($val->product_id),//self::get_db_category_id($product->extension_attributes->category_links[0]->category_id),
                                'sku'=>$val->product_id,  // saving in channel_products table
                                'channel_sku'=>$val->sku,  // sku for product table and channel_sku for channels products table
                                'variation_id'=>$val->id,
                                'name'=>$product->title,
                                'cost'=>$val->price,
                                'rccp'=>$val->price,
                                'stock_qty'=>$val->inventory_quantity,
                                'channel_id'=>self::$current_channel->id,
                                'image'=>$image,
                                'is_live'=>1     // is active or is live
                            ];

                            $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id

                            if($product_id)
                            {

                                $prepare['product_id'] = $product_id;
                                $channel_product_response= CatalogUtil::saveChannelProduct($prepare);  // save update channel product
                                $inventory_level=self::getInventoryDetail($val->inventory_item_id); // get inventory data
                                self::save_products_meta(array('channel_product_pk'=>isset($channel_product_response->id) ? $channel_product_response->id:"",'inventory_level'=>$inventory_level)); //save inventory location address and id in general reference keys table

                                if(isset($single_item['variant_id']) && $single_item['variant_id']==$val->id)  // if requested for single item then return product id and stock
                                {

                                    $return_single = [
                                                            'product_id'=> $product_id,
                                                            'stock'=> $prepare['stock_qty']
                                                     ];

                                }

                            }

                        }

                    }

                }
           // }
             ProductsUtil::remove_deleted_skus(self::$fetched_skus,self::$current_channel->id);
            return $return_single ;
    }

    /*************save parent peoduct********/
    private static function save_parent_product($product,$image)
    {
        self::$fetched_skus[] = trim($product->id); // del those skus which are deleted from platform
        $prepare=[
            'category_id'=>null,
            'sku'=>$product->id,  // saving in channel_products table
            'channel_sku'=>$product->id,  // sku for product table and channel_sku for channels products table
            'variation_id'=>NULL,
            'name'=>$product->title,
            'image'=>$image,
            'cost'=>0,
            'rccp'=>0,
            'stock_qty'=>0,
            'channel_id'=>self::$current_channel->id,
            'is_live'=>1 ,   // is active or is live
        ];

        $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id
        if($product_id)
        {

            $prepare['product_id'] = $product_id;
            CatalogUtil::saveChannelProduct($prepare);  // save update channel product


        }
        return $product_id;
    }



    private function getShopifyProductCatId($product_id)
    {
        $headers= [ 'Content-Type: application/json',
            'Authorization: Basic '.  base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key )
        ];
        $requestUrl=self::$current_channel->api_url .'/collects.json?product_id='.$product_id;
        $response=self::make_get_request($requestUrl,$headers);
        if($response)
        {
           $collection=json_decode($response);
           if(isset($collection->collects))
           {
               foreach($collection->collects as $k=>$v)
               {
                   $cat= GeneralReferenceKeys::findOne(['table_name'=>'category',
                                                        'key'=>'shopify_collection_id',
                                                        'value'=>$v->collection_id,
                                                        'channel_id'=>self::$current_channel->id]
                                                       );
                   if(isset($cat->value))
                   {
                    return $cat->value;
                   }
               }

           }
        }
        return 0;
    }

    private static function save_products_meta($data) // save in general reference keys location id , inventory id
    {
        if(isset($data['channel_product_pk']) && isset($data['inventory_level']) && !empty($data['channel_product_pk']))
        {
            $sql="DELETE FROM `general_reference_keys` 
                    where
                        `table_name`='channels_products'
                        and `key` IN('inventory_item_id','location_id')
                        and `channel_id`='".self::$current_channel->id."'
                        and `table_pk`='".$data['channel_product_pk']."'";
            Yii::$app->db->createCommand($sql)->execute();

            ////now insert//////
            if(isset($data['inventory_level']->inventory_levels[0])):

                Yii::$app->db->createCommand()->batchInsert('general_reference_keys', ['channel_id', 'table_name','table_pk','key','value'],
                    [
                        [self::$current_channel->id, 'channels_products',$data['channel_product_pk'],'inventory_item_id',$data['inventory_level']->inventory_levels[0]->inventory_item_id],
                        [self::$current_channel->id, 'channels_products',$data['channel_product_pk'],'location_id',$data['inventory_level']->inventory_levels[0]->location_id],

                     ])->execute();

            endif;
        }
        return ;
    }

    public function getInventoryDetail($inventory_item_id) // will give inventory item id , location id, and stock
    {
        $headers= [ 'Content-Type: application/json',
            'Authorization: Basic '.  base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key )
        ];
        $requestUrl=self::$current_channel->api_url .'/inventory_levels.json?inventory_item_ids='.$inventory_item_id;
        $response= self::make_get_request($requestUrl,$headers);
        if($response)
        {
            return json_decode($response);
        }
        return false;

    }


    //////////////////////////////////////////////////////////////////////////////////////////////
    /// ////////////////////////////////orders api //////////////////////////////////////////////
    /// ///////////////////////////////////////////////////////////////////////////////////////
     public static function fetchOrdersApi($channel,$time_period)
     {
         if($channel)
         {
             //date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'UTC');
             $current_time=gmdate('Y-m-d H:i:s');
             self::$current_channel=$channel;

             $headers= [ 'Content-Type: application/json',
                 'Authorization: Basic '.  base64_encode($channel->api_user . ":" . $channel->api_key )
             ];
           //  $updated_at= "2019-10-08T05:20:08-04:00";
             $params="status=any&limit=250"; // default is 50 ,max is 250 ,  so force 250

             if ($time_period == "day")  // whole day 24 hours
             {

                 $from = strtotime($current_time) - (1440*60); // for cron job // after 24 hour //
                 $from = gmdate("c", $from); //iso 8601
                 $params .="&updated_at_min=".$from;
             }
             elseif($time_period == "chunk")
             {

                 $from = strtotime($current_time ) - (60*60); // for cron job // after 60 min
                 $from = gmdate("c", $from); //iso 8601
                 $params .="&updated_at_min=".$from;
             }
             $requestUrl=$channel->api_url .'/orders.json/?'.$params;
             $response=self::make_get_request($requestUrl,$headers);
            // echo $response; die();
             if($response)
             {
               // echo $response; die();
                 $res=json_decode($response);
                 //self::debug($res);
                 self::OrderData($res);
             }
         }
         return $response;
     }

    private static function OrderData($data)
    {

        if(isset($data->orders))
        {
            foreach($data->orders as $order)
            {

               // $order->created_at= HelpUtil::get_utc_time($order->created_at,self::$current_channel->default_time_zone ? self::$current_channel->default_time_zone:'America/New_York');
              //  $order->updated_at= HelpUtil::get_utc_time($order->created_at,self::$current_channel->default_time_zone ? self::$current_channel->default_time_zone:'America/New_York');
                $order_status=self::get_order_status($order);
                $order_data =array(
                    'order_id'=>$order->id,
                    'order_no'=>isset($order->order_number) ? $order->order_number:$order->number ,
                    'channel_id'=>self::$current_channel->id,
                    'payment_method'=>isset($order->gateway) ? $order->gateway:"" ,
                    'order_total'=>$order->current_total_price ? $order->current_total_price:$order->current_total_price_set->shop_money->amount ,
                    'order_created_at'=>$order->created_at,
                    'order_updated_at'=>$order->updated_at,
                    'order_status'=>$order_status ? $order_status:"pending",
                    'total_items'=>count($order->line_items),
                    'order_shipping_fee'=>$order->total_shipping_price_set->shop_money->amount,
                    'order_discount'=>$order->total_discounts_set->shop_money->amount,
                    'cust_fname'=>isset($order->customer->first_name) ? $order->customer->first_name:"",
                    'cust_lname'=>isset($order->customer->last_name) ? $order->customer->last_name:"",
                    'full_response'=>'',//$apiresponse,
                );

                $response=[
                    'order_detail'=>$order_data,
                    'items'=>self::OrderItems($order,$order_status ),  //get order items
                    'customer'=>self::orderCustomerDetail($order),
                    'channel'=>self::$current_channel, // need to get channel detail in orderutil
                ];
                 //print_r($response);
               //  die();

                OrderUtil::saveOrder($response);  // process data in db
            }
        }
        return;



    }


    private static function get_order_status($order)
    {
        if($order->financial_status=='refunded')
            return 'canceled';
        elseif($order->fulfillment_status)
            return $order->fulfillment_status;
        elseif(isset($order->fulfillments[0]->status) && $order->fulfillments[0]->status)
            return $order->fulfillments[0]->status;
        else
            return 'pending';

    }

    private static function get_product_primary_key($val)
    {
        //  return 56;  // for now hard coded
        $product_id= \backend\util\HelpUtil::exchange_values('sku', 'id', $val->sku, 'products');
        if ($product_id === 'false') {
            $stock_plus_product= self::ChannelProducts(self::$current_channel,array('product_id'=>$val->product_id,'variant_id'=>$val->variant_id) );  // will update stock and will return product_id and stock
            $product_id=isset($stock_plus_product['product_id']) ? $stock_plus_product['product_id']  :"";
        }
        return $product_id;
    }
    // order items
    private static function OrderItems($order,$order_status)
    {
        //var_dump(self::$current_channel); die();
        $items=array();
        foreach($order->line_items as $val)
        {

            $product_id=self::get_product_primary_key($val);
            $qty=$val->quantity;
            $paid_price=isset($val->price_set->shop_money->amount) ? $val->price_set->shop_money->amount:$val->price;
            $items[]=array(
              //  'order_id'=> $val->id,
                'sku_id'=>$product_id,
                //'sku_code'=>$val->product_reference, //sku number
                'order_item_id'=>$val->id,
                'item_status'=>$order_status ? $order_status:"pending",
                'shop_sku'=>'',
                'price'=>$val->price,
                'paid_price'=>$paid_price,
                'shipping_amount'=>0 ,//isset($val->shipping_lines->price_set->shop_money->amount) ? $val->shipping_lines->price_set->shop_money->amount:(isset($order->total_shipping_price_set->shop_money->money) ? $order->total_shipping_price_set->shop_money->money:0) ,
                'item_discount'=>0,
                'sub_total'=>($qty * $paid_price),
                'item_created_at'=>$order->created_at,
                'item_updated_at'=>$order->updated_at,
                'full_response'=>'',//json_encode($order_items),
                'quantity'=>$qty,
                'item_sku'=>$val->sku,
                'stock_after_order' =>""
            );
        }

        return $items;

    }

    private static function orderCustomerDetail($order)
    {


        return [
            'billing_address'=>[
                'fname'=>isset($order->billing_address->first_name) ? $order->billing_address->first_name: "" ,
                'lname'=>isset($order->billing_address->last_name) ? $order->billing_address->last_name: "" ,
                'address'=>isset($order->billing_address->address1) ? $order->billing_address->address1: "" ,
                'state'=> isset($order->billing_address->province) ? $order->billing_address->province: "" ,
                'phone'=> isset($order->billing_address->phone) ? $order->billing_address->phone: NULL ,
                'city'=>isset($order->billing_address->city) ? $order->billing_address->city: "" ,
                'country'=>isset($order->billing_address->country) ? $order->billing_address->country: "" ,
                'postal_code'=>isset($order->billing_address->zip) ? $order->billing_address->zip: "" ,
            ],
            'shipping_address'=>[
                'fname'=>isset($order->shipping_address->first_name) ? $order->shipping_address->first_name: "" ,
                'lname'=>isset($order->shipping_address->last_name) ? $order->shipping_address->last_name: "" ,
                'address'=>isset($order->shipping_address->address1) ? $order->shipping_address->address1: "" ,
                'state'=> isset($order->shipping_address->province) ? $order->shipping_address->province: "" ,
                'phone'=> isset($order->shipping_address->phone) ? $order->shipping_address->phone: NULL ,
                'city'=>isset($order->shipping_address->city) ? $order->shipping_address->city: "" ,
                'country'=>isset($order->shipping_address->country) ? $order->shipping_address->country: "" ,
                'postal_code'=>isset($order->shipping_address->zip) ? $order->shipping_address->zip: "" ,
            ],

        ];

    }
    public static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    ///update price of products////
    public static function updateChannelPrice($channel,$item=array())
    {
       // var_dump($item);
       // die();
        if(isset($item['check_deal_sku']) && $item['check_deal_sku']=='yes') // while price update check if sku is in currently running deal then skip price update
        {
            // $check_deal_sku= HelpUtil::checkActiveDealSku($channel->id,$item['sku']);
            $check_deal_sku= HelpUtil::SkuInDeal($item['product_id'],$channel['id']);
            if($check_deal_sku)
            {
                return 'sku is in active deal';
            }

        }
        self::$current_channel=$channel;
        //// parameteres
        $headers= [ 'Content-Type: application/json',
            'Authorization: Basic '.  base64_encode($channel['api_user'] . ":" . $channel['api_key'] )
        ];
        $body_enclosed=json_encode(array('variant' => array('id'=>$item['variant_id'],'price'=>$item['price'])));
        $requestUrl=$channel['api_url'] .'/variants/'.$item['variant_id'].'.json';
        return self::make_post_request($requestUrl,$body_enclosed,$headers);

    }
    public static function updateDealChannelPrice($channel,$item=array())
    {
//        self::debug($item);
        self::$current_channel=$channel;
        //// parameteres
        $headers= [ 'Content-Type: application/json',
            'Authorization: Basic '.  base64_encode($channel['api_user'] . ":" . $channel['api_key'] )
        ];
        $body_enclosed=json_encode(array('variant' => array('id'=>$item['variant_id'],'price'=>$item['price'])));
        $requestUrl=$channel['api_url'] .'/variants/'.$item['variant_id'].'.json';
        return self::make_post_request($requestUrl,$body_enclosed,$headers);

    }

    ///update stock
    ///
    public static function updateChannelStock($channel,$item)
    {

        self::$current_channel=$channel;
        //// parameteres
        $headers= [ 'Content-Type: application/json',
            'Authorization: Basic '.  base64_encode($channel['api_user'] . ":" . $channel['api_key'] )
        ];

        $params=self::get_inventory_params($item['channel_product_pk_id']);// get inventory_item_id and location_id from general refernce keys table
        if(isset($params['inventory_item_id']) && isset($params['location_id']))
         {
             $body_enclosed=json_encode(array('inventory_item_id'=>$params['inventory_item_id'],'location_id'=>$params['location_id'],'available'=>$item['stock']));
             $requestUrl=$channel['api_url'] .'/inventory_levels/set.json';
             return self::make_post_request($requestUrl,$body_enclosed,$headers,'POST');
         }

        return "nothing updated against";


    }

    private static function get_inventory_params($table_pk)
    {
            $params=array();
            $sql="SELECT `key` ,`value` 
                    FROM
                        `general_reference_keys`
                    WHERE
                        `channel_id`='".self::$current_channel['id']."'
                        AND
                        `table_name`='channels_products'
                        AND
                        `key` IN('inventory_item_id','location_id')
                        AND
                        `table_pk`='".$table_pk."'";

        $meta= Yii::$app->db->createCommand($sql)->queryall();
        if($meta)
        {
            foreach($meta as $k=>$v)
            {
                $params[$v['key']]=$v['value'];
            }
        }
        return $params;
    }
}
