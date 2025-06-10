<?php
namespace backend\util;

use backend\controllers\ApiController;
use common\models\Channels;
use common\models\Settings;
use Yii;

class DarazUtil
{
    private static $current_channel;
    private static $current_api_verion='1.0'; // daraz api version
    private static $generic_cat_id_for_daraz=null;  //  intitlaized by getProductCatId() , same for all products
    const generic_cat_name_for_daraz='daraz_generic_category';  // for time being to save hard coded same cat for all products
    private static $product_api_call_setting=['limit'=>50,'offset'=>0,'total_products'=>50];
    private static $order_api_call_setting=['limit'=>100,'offset'=>0,'total_orders'=>0,'time_period'=>''];
    private static $fetched_skus=[]; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    // private static $global_counter=0;
  //  private static $error_code=0;

    private function make_curl_request($requestUrl,$body=null)
    {
      //  date_default_timezone_set('Asia/Kuala_lumpur');
      //  echo "call number=->" . ++self::$global_counter ." @ ". date('H:m:s'). PHP_EOL;
     //   echo " --call to=->" . $requestUrl . PHP_EOL;
        $ch = curl_init($requestUrl);
       // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        if($body)
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $result = curl_exec($ch);
        return $result;  //json format
    }


    private static function build_params($params=null) // array sent
    {
        date_default_timezone_set(self::$current_channel->default_time_zone ? self::$current_channel->default_time_zone:'UTC');
        $current_timestamp= date('c'); //ISO8601 format
        if($params && is_array($params))
        {
            $mendatory = array(
                                'UserID' => self::$current_channel->api_user, // The ID of the user making the call.
                                'Version' => self::$current_api_verion, // The API version. Currently must be 1.0
                                'Format' => 'json', // The format of the result.
                                'Timestamp' => $current_timestamp // The current time in ISO8601 format
                            );

            ///integrate sent params as well//
            $parameters=array_merge($mendatory,$params); //merge sent params as well as mendatory params
            ksort($parameters); // Sort parameters by name.
            $encoded = array(); // URL encode the parameters.
            foreach ($parameters as $name => $value) {
                /*if(is_array($value)){
                    $implode=implode(',',$value);
                    $value='['.$implode.']';
                    //echo $value; die();
                    $val=rawurlencode($value);
                   // echo $val; die();
                }
                else*/
                    $val=rawurlencode($value);

                $encoded[] = rawurlencode($name) . '=' . $val;
            }
            // Concatenate the sorted and URL encoded parameters into a string.
            $concatenated = implode('&', $encoded);
            //self::debug($concatenated);
            $parameters['Signature'] = self::get_hash_mac($concatenated);  //hashmac using sha256
           // self::debug($parameters);
            return $parameters;
        }
    }

    private static function get_hash_mac($params)
    {
        return rawurlencode(hash_hmac('sha256', $params, self::$current_channel->api_key, false));
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// /////////////////////////////////////////category/////////////////////////////////////////////////
    /// ///////////////////////////////////////////////////////////////////////////////////////////////////

    public static function channelCategories($channel=null)
    {
        if($channel)
        {
            self::$current_channel=$channel;
            $parameters= self::build_params(array('Action'=>'GetCategoryTree'));
            // Build Query String
            $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
            $requestUrl = rtrim($channel->api_url,'/') . "/?" . $queryString;
            $response=self::make_curl_request($requestUrl);
            if($response)
            {
                $php_format=json_decode($response);
                if(isset($php_format->SuccessResponse))
                {

                    $check_cat=Settings::findOne(['daraz_categories_list']); // saving all category of daraz in settings table
                    if(!$check_cat)
                    {
                        $add_cat=new Settings();
                        $add_cat->name="daraz_categories_list";
                        $add_cat->value=json_encode($php_format);
                        $add_cat->save(false);

                    }
                }

            }
        }
        return;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /// ///////////////////////////////// get products/////////////////////////////////////////////////////////
    /// //////////////////////////////////////////////////////////////////////////////////////////////////////
    private static  function checkChannelProductsCount() // count products of daraz channel in channel_product
    {

        return yii::$app->db->createCommand("SELECT count('id')  FROM `channels_products` WHERE `channel_id`='".self::$current_channel->id."' ")->queryScalar();

    }

    public static function ChannelProducts($channel=null,$single_item=null)
    {

           // echo $single_item; die();
        if($channel)
        {

            self::$current_channel=$channel;
            $params=array('Action'=>'GetProducts','Filter'=>'all','Limit'=>self::$product_api_call_setting['limit'],'Offset'=>self::$product_api_call_setting['offset']);
            if($single_item){
                     $params=array('Action'=>'GetProducts','Filter'=>'all');
                     $params['SkuSellerList']=json_encode((array)$single_item);

            }
            /*if(!$single_item && self::checkChannelProductsCount()){ // check if products already exists then just call products which are updated last 2 days
                date_default_timezone_set('UTC');
                    $from = strtotime(date('Y-m-d H:i:s')) - (172800); // for cron job // last 2 days //
                    $from = date("c", $from); //iso 8601
                    $params['UpdatedAfter']=$from;

            }*/
         // print_r($params); die();
            $parameters= self::build_params($params);
            // Build Query String
            $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
            $requestUrl = rtrim($channel->api_url,'/') . "/?" . $queryString;
           // $requestUrl = $channel->api_url."?Action=GetProducts&Filter=all&Format=json&Timestamp=".$current_timestamp."&UserID=".$channel->api_user."&Version=".self::$current_api_verion."&Signature=".$hash_mac;
            $response=self::make_curl_request($requestUrl);
            // self::debug($response);
            if($response)
            {
                $php_format=json_decode($response);
                //self::debug($php_format);
                return  self::saveChannelProducts($php_format,$single_item);


            }
        }

        return;
    }

    private function saveChannelProducts($data=null,$single_item=null)
    {
            $loop_executed=false;
            if(isset($data->SuccessResponse->Body->Products))
            {
                // for pagination api call setting
                if(isset($data->SuccessResponse->Body->TotalProducts)){
                    self::$product_api_call_setting['total_products']=$data->SuccessResponse->Body->TotalProducts;
                }
               // self::getProductCatId(); // for now assigned generic category  , this func initialize self::$generic_cat_id_for_daraz at one time
               // $cat=CatalogUtil::getdefaultParentCategory();
                foreach($data->SuccessResponse->Body->Products as $product)
                {
                    $loop_executed=true;
                    $brand=(isset($product->brand) &&  $product->brand!='no brand') ? strtolower($product->brand):NULL;
                    $parent_sku_id=self::save_parent_product($product);
                   foreach($product->Skus as $key=>$val)  // variations or skus
                   {
                     //  self::$fetched_skus[] = trim($val->SellerSku); // del those skus which are deleted from platform
                       $image=(isset($val->Images) && isset($val->Images[0])) ? $val->Images[0]:NULL;
                       $prepare=[
                           'parent_sku_id' => $parent_sku_id? $parent_sku_id : NULL,
                           'category_id'=>'',//self::$generic_cat_id_for_daraz,
                           'sku'=>$val->ShopSku,  // saving in channel_products table
                           'channel_sku'=>$val->SellerSku,  // sku for product table and channel_sku for channels products table
                           'variation_id'=>$val->SkuId,
                           'name'=>$product->Attributes->name,
                           'cost'=>$val->price,
                           'rccp'=>$val->price,
                           'image'=>$image,
                           'brand'=>$brand,
                           'discounted_price'=>isset($val->special_price) ? $val->special_price:0 ,
                           'discount_from_date'=>isset($val->special_from_time) ? $val->special_from_time:null ,
                           'discount_to_date'=>isset($val->special_to_time) ? $val->special_to_time:0 ,
                           'stock_qty'=>$val->quantity,
                           'channel_id'=>self::$current_channel->id,
                           'is_live'=>$val->Status=="active" ? 1:0 ,   // is active or is live
                       ];

                       $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id
                       if($product_id)
                       {

                           $prepare['product_id'] = $product_id;
                           CatalogUtil::saveChannelProduct($prepare);  // save update channel product
                           if($single_item)  // if requested for single item then return product id and stock
                           {

                               return  array(
                                            'product_id'=> $prepare['product_id'],
                                            'stock'=> $prepare['stock_qty']
                                            );
                                //return $ok;
                               //break;
                           }

                       }
                   }
                }

                ///check if pagination required
                if($loop_executed &&  self::$product_api_call_setting['total_products'] > 50){
                    self::$product_api_call_setting['offset']=(self::$product_api_call_setting['offset'] + self::$product_api_call_setting['limit']);
                    //echo "offset ->" . self::$product_api_call_setting['offset'] .PHP_EOL;
                    self::ChannelProducts(self::$current_channel);
                }

            }

            return ;
    }

    /*************save parent peoduct********/
    private static function save_parent_product($product)
    {
        self::$fetched_skus[] = trim($product->ItemId); // del those skus which are deleted from platform
        $prepare=[
            'category_id'=>NULL,
            'sku'=>$product->ItemId,  // saving in channel_products table
            'channel_sku'=>$product->ItemId,  // sku for product table and channel_sku for channels products table
            'variation_id'=>NULL,
            'name'=>$product->Attributes->name,
            'image'=>NULL,
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
    public static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    //update stock and price
    public static function updateChannelStockPrice($channel,$items)
    {
        if($channel && $items)
        {
            self::$current_channel=$channel;
            $parameters= self::build_params(array('Action'=>'UpdatePriceQuantity'));
            // Build Query String
            $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
            $requestUrl = rtrim($channel->api_url,'/') . "/?" . $queryString;
            $body='<?xml version="1.0" encoding="UTF-8" ?>
                    <Request>
                        <Product>
                            <Skus>
                                '.$items.'
                             </Skus>
                        </Product>
                    </Request>';
            return self::make_curl_request($requestUrl,$body);

        }

    }
    /***
     * @param $channel
     * @param $deal
     * @param $skus
     * @param bool $start_deal starting or ending deal
     * @return array|string
     */
    public static function updateSalePrices($channel,$deal,$skus,$start_deal=true)
    {
        $body="";
        $counter=1; //to update 50 stock per call
        $response=[];
       // $total_skus=count($skus);
        if($start_deal)
        {
            foreach($skus as $sku)
            {
                $body .='<Sku>
                            <SellerSku>'.$sku['sku'].'</SellerSku>
                            <Quantity/>
                            <Price/>
                            <SalePrice>'.round($sku['deal_price']).'</SalePrice>
                            <SaleStartDate>'.$deal->start_date.'</SaleStartDate>
                            <SaleEndDate>'.$deal->end_date.'</SaleEndDate>
                        </Sku>';
                if(fmod($counter,50) == 0 )  //reminder equals 0 then update 50 batch at once
                {
                    $response[]=self::updateChannelStockPrice($channel,$body);
                    $body="";
                }
                $counter++;
            }
            if($body)
            {
               // self::debug($body);
                $response[]=self::updateChannelStockPrice($channel,$body);
                $body="";
            }
        }else{  // if deal ending
            $counter=1; //to update 50 stock per call
            foreach($skus as $sku)
            {
                $res=DealsUtil::get_nearest_active_deal($deal->channel_id,$deal->id,$sku['sku']);
                if($res)
                {
                    $body .='<Sku>
                            <SellerSku>'.$sku['sku'].'</SellerSku>
                            <Quantity/>
                            <Price/>
                            <SalePrice>'.round($res['deal_price']).'</SalePrice>
                            <SaleStartDate>'.$res['start_date'].'</SaleStartDate>
                            <SaleEndDate>'.$res['end_date'].'</SaleEndDate>
                        </Sku>';
                    if(fmod($counter,50) == 0)
                    {
                        $response[]=self::updateChannelStockPrice($channel,$body);
                        $body="";
                    }
                    $counter++;
                }
            } // foreach end
            if($body){
                $response[]=self::updateChannelStockPrice($channel,$body);
                $body="";
            }
        }
        return $response;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////
    /// ////////////////////////////////orders api //////////////////////////////////////////////
    /// ///////////////////////////////////////////////////////////////////////////////////////

    ////check this api when data available , because not tested with any data , made according to doc
    public static function fetchOrdersApi($channel,$time_period)
    {
        //date_default_timezone_set('UTC');
       // die('come');
        $current_time= gmdate('c'); //iso 8601
        if($channel)
        {
            self::$order_api_call_setting['time_period']=$time_period;
            self::$current_channel=$channel;
            $params['Action']='GetOrders';  // api name
            if ($time_period == "day")  // whole day 24 hours
            {

                $from = strtotime($current_time) - (1440*60); // for cron job // after 24 hour //
                $from = gmdate("c", $from); //iso 8601
                $params['UpdatedAfter']=$from;
            }
            elseif($time_period == "chunk")
            {

                $from = strtotime($current_time ) - (20*60); // for cron job // after 20 min
                $from = gmdate("c", $from); //iso 8601
                $params['UpdatedAfter']=$from;
            }
            $params['SortDirection']='DESC';
            $params['Limit']=self::$order_api_call_setting['limit'];
            $params['Offset']=self::$order_api_call_setting['offset'];

            $parameters= self::build_params($params);
            // Build Query String
            $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
            $requestUrl = rtrim($channel->api_url,'/') . "/?" . $queryString;
            $response= self::make_curl_request($requestUrl);
            //echo $response; die();
            if($response)
            {

                $res=json_decode($response);
              //  self::debug($res);
                if(isset($res->SuccessResponse->Body))
                {
                    self::OrderData($res->SuccessResponse->Body);
                }



            }

        }
        return $response;
    }


    private static function OrderData($data)
    {
      //  $response=array();
       // $counter=0;
       // echo "page # -> " .self::$order_api_call_setting['offset'] ;
       // echo "<br/>";
        $loop_executed=false;
        if(isset($data->Orders) && !empty($data->Orders))
        {
            if(isset($data->SuccessResponse->Head->TotalCount)){
                self::$order_api_call_setting['total_orders']=$data->SuccessResponse->Head->TotalCount;
            }
            foreach($data->Orders as $order)
            {
               // if(++$counter % 5 == 0)
                //     sleep(10);
                $loop_executed=true;
                //$order->created_at= HelpUtil::get_utc_time($order->CreatedAt,self::$current_channel->default_time_zone ? self::$current_channel->default_time_zone:'America/New_York');
               // $order->updated_at= HelpUtil::get_utc_time($order->UpdatedAt,self::$current_channel->default_time_zone ? self::$current_channel->default_time_zone:'America/New_York');
               $order_items=self::fetchOrderItems($order->OrderId); // fetch from api
                if(!$order_items){ continue;}
                $order_subtotal=str_replace(",","",$order->Price);
                $order_shipping=$order->ShippingFee ? str_replace(",","",$order->ShippingFee):0;
                $order_discount=$order->Voucher ? str_replace(",","",$order->Voucher):0;
                $order_total=(($order_subtotal + $order_shipping) - $order_discount);
                $order_data=array(
                    'order_id'=>$order->OrderId,
                    'order_no'=>$order->OrderNumber,
                    'channel_id'=>self::$current_channel->id,
                    'payment_method'=>isset($order->PaymentMethod) ? $order->PaymentMethod:"" ,
                    'order_total'=>$order_total,
                    'order_created_at'=>$order->CreatedAt,
                    'order_updated_at'=>$order->UpdatedAt,
                    'order_status'=>(is_array($order->Statuses) && count($order->Statuses) > 1) ? implode(',',$order->Statuses):(isset($order->Statuses[0]) ? $order->Statuses[0]:"pending"), // check it when data available
                    'total_items'=>$order->ItemsCount,
                    'order_shipping_fee'=>$order_shipping,
                    'order_discount'=>$order_discount,
                    'coupon_code'=>isset($order->VoucherCode) ? $order->VoucherCode:NULL,
                    'cust_fname'=>isset($order->CustomerFirstName) ? $order->CustomerFirstName:"",
                    'cust_lname'=>isset($order->CustomerLastName) ? $order->CustomerLastName:"",
                    'full_response'=>'',//$apiresponse,
                );

                $response=[
                    'order_detail'=>$order_data,
                    'items'=>self::OrderItems($order_items ),  //get order items
                    'customer'=>self::orderCustomerDetail($order),
                    'channel'=>self::$current_channel, // need to get channel detail in orderutil
                ];


                OrderUtil::saveOrder($response);  // process data in db
            }

            ///check if pagination required
         //   if($loop_executed &&  self::$order_api_call_setting['total_orders'] >= 100){
            if($loop_executed){
                self::$order_api_call_setting['offset']=(self::$order_api_call_setting['offset'] + self::$order_api_call_setting['limit']);
               // echo "offset ->" . self::$order_api_call_setting['offset'] .PHP_EOL;
                self::fetchOrdersApi(self::$current_channel,self::$order_api_call_setting['time_period']);
            }
          // echo json_encode($response); die();
        }
        return;



    }

//fetch order items of specific order from api
    private  function fetchOrderItems($order_id=null,$recursive=null)
    {
        if($order_id)
        {

            $parameters= self::build_params(array('Action'=>'GetOrderItems','OrderId'=>$order_id));
            // Build Query String
            $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
            $requestUrl = rtrim(self::$current_channel->api_url,'/')  . "/?" . $queryString;
            $response= self::make_curl_request($requestUrl);
            if($response)
            {
                $res=json_decode($response);
                /*   if(!isset($res->SuccessResponse->Body))
                   {
                       //die('aja');
                     // echo "masla->" . $order_id; die();
                       /*if(self::$error_code <= 2)
                       {
                           echo "in order items before sleep @ " . date('H:i:s') . PHP_EOL ;
                           sleep(100);
                           echo "in order items  after sleep @ " . date('H:i:s') . PHP_EOL ;
                           if($recursive){echo "recusrive called @ ".date('H:i:s');}
                           self::$error_code++;
                          self::fetchOrderItems($order_id,'recursive');

                       }
                       elseif(self::$error_code >=2){
                           die('call ended');
                       }
                      echo $response; die();
                   }*/
                // echo  $order_id . "<br/>";
                return isset($res->SuccessResponse->Body) ? $res->SuccessResponse->Body:null;

            }

        }
        return ;
    }


    // order items
    private static function OrderItems($order_items)
    {
        $items = array();
        if (isset($order_items->OrderItems)) {
        //self::debug($order_items->OrderItems);
        foreach ($order_items->OrderItems as $val)
        {
            $paid_price=isset($val->PaidPrice) ? str_replace(",","",$val->PaidPrice) : str_replace(",","",$val->ItemPrice);
            $item_tax=isset($val->TaxAmount) ? $val->TaxAmount:0 ;
             $if_repeat=array_search($val->Sku,array_column($items,'item_sku'));
             if($if_repeat!==false)  // if order item is repeated then just add one more quantity and skip iteration
             {
                 $items[$if_repeat]['quantity']= (int)$items[$if_repeat]['quantity'] + 1;
                 $items[$if_repeat]['sub_total']= ($items[$if_repeat]['quantity'] * $items[$if_repeat]['paid_price']);
                 continue;
             }
            $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$val->Sku ,'channel_id'=>self::$current_channel->id));
             if(empty($sku_id)){
                 $stock_plus_product = self::ChannelProducts(self::$current_channel, $val->Sku);  // will update stock and will return product_id and stock
                 $sku_id=isset($stock_plus_product['product_id']) ? $stock_plus_product['product_id'] : "";
             }

            //print_r($stock_plus_product); die();

            $items[] = array(
                //  'order_id'=> $val->id,
                'sku_id' =>$sku_id ,
                //'sku_code'=>$val->product_reference, //sku number
                'order_item_id' => $val->OrderItemId,
                'item_status' => $val->Status,
                'shop_sku' => $val->ShopSku,
                'price' => str_replace(",","",$val->ItemPrice),
                'paid_price' => $paid_price,
                'item_tax'=>$item_tax,
                'shipping_amount' => $val->ShippingAmount,//isset($val->shipping_lines->price_set->shop_money->amount) ? $val->shipping_lines->price_set->shop_money->amount:(isset($order->total_shipping_price_set->shop_money->money) ? $order->total_shipping_price_set->shop_money->money:0) ,
                'item_discount' => 0,
                'sub_total'=>(1 * $paid_price), // check when live data available
                'item_created_at' => $val->CreatedAt,
                'item_updated_at' => $val->UpdatedAt,
                'full_response' => '',//json_encode($order_items),
                'quantity' => 1,  //check when live data available
                'item_sku' => $val->Sku,
                'stock_after_order' =>""
            );
        }
    }

        return $items;

    }

    private static function orderCustomerDetail($order)
    {
        return [
            'billing_address'=>[
                'fname'=>isset($order->AddressBilling->FirstName) ? $order->AddressBilling->FirstName: "" ,
                'lname'=>isset($order->AddressBilling->LastName) ? $order->AddressBilling->LastName: "" ,
                'address'=>isset($order->AddressBilling->Address1) ? $order->AddressBilling->Address1: "" ,
                'state'=> "" ,
                'phone'=> isset($order->AddressBilling->Phone) ? $order->AddressBilling->Phone: NULL ,
                'city'=>isset($order->AddressBilling->City) ? $order->AddressBilling->City: "" ,
                'country'=>isset($order->AddressBilling->Country) ? $order->AddressBilling->Country: "" ,
                'postal_code'=>isset($order->AddressBilling->PostCode) ? $order->AddressBilling->PostCode: "" ,
            ],
            'shipping_address'=>[
                'fname'=>isset($order->AddressShipping->FirstName) ? $order->AddressShipping->FirstName: "" ,
                'lname'=>isset($order->AddressShipping->LastName) ? $order->AddressShipping->LastName: "" ,
                'address'=>isset($order->AddressShipping->Address1) ? $order->AddressShipping->Address1: "" ,
                'state'=> "" ,
                'phone'=> isset($order->AddressShipping->Phone) ? $order->AddressShipping->Phone: NULL ,
                'city'=>isset($order->AddressShipping->City) ? $order->AddressShipping->City: "" ,
                'country'=>isset($order->AddressShipping->Country) ? $order->AddressShipping->Country: "" ,
                'postal_code'=>isset($order->AddressShipping->PostCode) ? $order->AddressShipping->PostCode: "" ,
            ],

        ];
    }

    //********************/
    public static function getOrderDocumentId($channel)
    {

        self::$current_channel=$channel;
        $parameters= self::build_params(array('Action'=>'GetDocument','DocumentType'=>'shippingLabel','OrderItemIds'=>['104689877448482']));
        // Build Query String
        $queryString = http_build_query($parameters, '', '&',PHP_QUERY_RFC3986);
        //self::debug($queryString);
        $requestUrl = rtrim(self::$current_channel->api_url,'/')  . "/?" . $queryString;
        $response= self::make_curl_request($requestUrl);
        $res=json_decode($response);
        self::debug($res);
    }

    //// generic cat id for daraz // below func will check if added already then will return its id ,else will add and return its id
    private static function getProductCatId()
    {

        if(self::$generic_cat_id_for_daraz == null)  // if static cat id not set for all products then make and assign
        {
            $cat=array(
                'cat_name'=>self::generic_cat_name_for_daraz,
                'is_active'=>1,
                'parent_cat_id'=>0,
                'channel'=>self::$current_channel,

            );
            $cat= CatalogUtil::saveCategories($cat); // if added fecth id of cat else will add
            if(isset($cat->id)){
                self::$generic_cat_id_for_daraz=$cat->id;
            }
        }
        return;
    }

}
