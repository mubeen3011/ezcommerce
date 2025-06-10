<?php
namespace backend\util;
use common\models\Channels;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Yii;

class WalmartUtil
{
    private static $current_token=array();  // token,expiry
    private static $current_channel=NULL;
    private static $fetched_skus=[]; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    private static function make_curl_request($headers,$body=null,$requestUrl,$method)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $requestUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method, //POST ,GET
            CURLOPT_POSTFIELDS =>$body,
            CURLOPT_HTTPHEADER =>$headers,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err; die();
        } else {
            return $response;
        }
//        self::debug($response);
    }

    /*
     * generation of new token , walmart token expires in 15 minutes,
     */
    private static function generate_token()
    {
        $headers= [  'accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic '.  base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key ),
                    'WM_SVC.NAME:Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID:'.self::$current_channel->name.date('Ymdhis'), // unique request id
                  ];
        $postFields = 'grant_type=client_credentials';
        $requestUrl=rtrim(self::$current_channel->api_url,'/') .'/token';
        $response=self::make_curl_request($headers,$postFields,$requestUrl,'POST');
        if($response)
        {
            $token=json_decode($response);
            self::$current_token->access_token=$token->access_token;
            self::$current_token->token_generated_at=strtotime("now");
        }
        return $response ;
    }

    private static function save_token_in_db()  // save new generated token in channel
    {
        return Yii::$app->db->createCommand()
              ->update('channels', ['auth_params' => json_encode(self::$current_token)],['id'=>self::$current_channel->id])
             ->execute();
    }

    private static function set_config($channel_detail)
    {
//        self::debug($channel_detail->auth_params);
        if(!$channel_detail)
            return;

        self::$current_channel=$channel_detail;
        self::$current_token=json_decode($channel_detail->auth_params);
       // print_r(self::$current_token); die();
        if (self::$current_token && isset(self::$current_token->access_token)) {
            $token_generated_at=self::$current_token->token_generated_at;
            $current_time=strtotime('now');
            if(($current_time-$token_generated_at) < 840 )  // token validity is 15 minutes
                return;

        }

      if(empty(self::$current_token))  // if the auth params field is  empty set  variables
          self::$current_token= json_decode('{"access_token":"","token_generated_at":""}');

       // print_r(self::$current_token->access_token); die();
        $response=self::generate_token();  // generate new token
        self::save_token_in_db();  // save token in db
        return;
    }
    /**
     * Get walmart marketplace products
     */

    public static function channelProducts($channel=null,$next_page=null)
    {
            self::set_config($channel);  // set channel and token
            if(self::$current_channel) {
                $headers = ['accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                    'WM_SVC.NAME:Walmart Marketplace',
                    'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "p" . date('Ymdhis'), // unique request id
                    'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
                ];
                $next_cusrsor = $next_page ? $next_page : '*';
                $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/items?nextCursor=' . $next_cusrsor;
                //$requestUrl = rtrim(self::$current_channel->api_url,'/') . '/items?publishedStatus=PUBLISHED&lifecycleStatus=ACTIVE&nextCursor='.$next_cusrsor;
               $response= self::make_curl_request($headers,null,$requestUrl,'GET');
               if($response)
               {
                   $decoded=json_decode($response);
                  // self::debug($decoded);
                   self::saveChannelProducts(isset($decoded->ItemResponse) ? $decoded->ItemResponse:null);
                   if(isset($decoded->nextCursor)) // if pagination
                   {
                       self::channelProducts(null,$decoded->nextCursor);
                   }
               }
            }

            if(self::$fetched_skus){
                ProductsUtil::remove_deleted_skus(self::$fetched_skus,self::$current_channel->id);
            }


        return;
    }


    private static function saveChannelProducts($data=null)
    {
        if($data)
        {
            //$cat=CatalogUtil::getdefaultParentCategory();
            foreach($data as $item)
            {
                if(!in_array(strtolower($item->publishedStatus),['published','ready_to_publish','in_progress']))
                 continue;

                //$cat_id=CatalogUtil::saveCategories(['cat_name'=>$item->productType,'parent_cat_id'=>0,'channel'=>self::$current_channel]);
             $prepare=[
                    'category_id'=>NULL,//isset($cat->id) ? $cat->id:0,
                    'sku'=>$item->wpid,  // saving in channel_products table
                    'channel_sku'=>$item->sku,  // sku for product table and channel_sku for channels products table
                    'variation_id'=>NULL,
                    'name'=>$item->productName,
                    'cost'=>isset($item->price->amount) ? $item->price->amount:0,
                    'ean' => $item->gtin,
                    'rccp'=>isset($item->price->amount) ? $item->price->amount:0,
                    'stock_qty'=>0,
                    'channel_id'=>self::$current_channel->id,
                    'is_live'=>in_array(strtolower($item->publishedStatus),['published','ready_to_publish','in_progress']) ? 1:0 ,   // is active or is live
                ];
                self::$fetched_skus[]=trim($item->sku); // del those skus which are deleted from platform
                $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id
                if($product_id)
                {
                    $prepare['product_id'] = $product_id;
                    CatalogUtil::saveChannelProduct($prepare);  // save update channel product

                }
            }
        }
        return;
    }
    public static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    /**
     * update prices at bulk
     */

    public static function updateChannelPrice($channel,$items)
    {
//        self::debug($items);
        if($channel && $items)
        {
            self::set_config((object)$channel);

            /// header
            $headers = ['accept: application/xml',
                'Content-Type: multipart/form-data',
                'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                'WM_SVC.NAME:Walmart Marketplace',
                'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "up" . date('Ymdhis'), // unique request id
                'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            ];

            //body
            $body ='<PriceFeed xmlns="http://walmart.com/">
                     <PriceHeader>
                        <version>1.5.1</version>
                    </PriceHeader>
                    '.$items.'
                    </PriceFeed>';
            $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/feeds?feedType=price';
            return   self::make_curl_request($headers,['file'=>$body],$requestUrl,'POST');

        }
        return 'invalid channel or item price array sent' ;
    }
    public static function updateSalePricesInBulk($channel,$items){

        if($channel && $items)
        {
            self::set_config((object)$channel);
            $feedIds = [];
            /// header
            $headers = ['accept: application/json',
                'Content-Type: multipart/form-data',
                'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                'WM_SVC.NAME:Walmart Marketplace',
                'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "up" . date('Ymdhis'), // unique request id
                'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            ];

            foreach ( $items as $key=>$chunk ){
                $body ='<PriceFeed xmlns="http://walmart.com/">
                            <PriceHeader>
                                <version>1.5.1</version>
                        </PriceHeader>
                        '.implode('',$chunk).'
                        </PriceFeed>';

                $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/feeds?feedType=promo';
                $response = self::make_curl_request($headers,['file'=>$body],$requestUrl,'POST');
                $response = json_decode($response,true);
                if (isset($response['error'])){
                    $feedIds[$key]['status']='failed';
                    $feedIds[$key]['response']=$response;
                }else{
                    $feedIds['status'][]='success';
                    $feedIds['feedlist'][] = $response['feedId'];
                }
            }
            return $feedIds;
        }
        return 'invalid channel or item price array sent' ;
    }
    public static function GetAllFeedDetails($channel,$feedIds){
        $feedDetail=[];
        if ( $channel && $feedIds ){
            foreach ( $feedIds as $feedid ){
                $feedDetail[] =self::getFeedDetail($channel,$feedid);
            }
            return $feedDetail;
        }
        return 'invalid channel or item price array sent' ;
    }
    public static function activate_nested_deal($deal,$dskus){

        $xml=[];
        $nested_deal_log=[];
        $nested_deal_log['status']='Nested Deals Price Updted';
        $effectiveDate = HelpUtil::get_utc_time_walmart_deal(date('Y-m-d H:i:s'),'Asia/Kuala_Lumpur','ADD',300);

        foreach ( $dskus as $value ){

            $response=DealsUtil::get_nearest_active_deal($deal->channel_id,$deal->id,$value['sku']);
            if ( $response ){


                $xml[] = self::createDealXml($effectiveDate,$response['end_date'],$response['deal_price'],$value['sku'],'REDUCED');

                // log for nested deals
                $log = [];
                $log['deal_id']=$response['id'];
                $log['deal_price']=$response['deal_price'];
                $log['start_date']=$response['start_date'];
                $log['end_date']=$response['end_date'];
                $nested_deal_log[] = $log;

            }

        }
        if (!empty($xml)){
            // chunks will have 1000 records index wise
            $chunks_xml = array_chunk($xml,1000);
            $channel = Channels::find()->where(['id'=>$deal->channel_id])->one();
            $reponse = WalmartUtil::updateSalePricesInBulk($channel,$chunks_xml);
            HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$deal->channel_id,$deal->id,$reponse,'Nested Deal Update'.json_encode($nested_deal_log)); // save response from api*/
        }

    }
    public static function createDealXml($start_date, $end_date, $dealPrice, $sku, $priceType='REDUCED' ){


        $expirationDate = HelpUtil::get_utc_time_walmart_deal($end_date,'Asia/Kuala_Lumpur');

        return '<Price>
                        <itemIdentifier>
                            <sku>'.$sku.'</sku>
                        </itemIdentifier>
                        <pricingList replaceAll=\'false\'>
                            <pricing effectiveDate="'.$start_date.'" expirationDate="'.$expirationDate.'" processMode="UPSERT">
                                <currentPrice><value amount="'.$dealPrice.'"/></currentPrice>
                                <currentPriceType>'.$priceType.'</currentPriceType>
                            </pricing>
                        </pricingList>
                     </Price>';

    }
    public static function deleteAllPromotionXml($sku){
        return '<Price>
                            <itemIdentifier>
                                <sku>'.$sku.'</sku>
                            </itemIdentifier>
                            <pricingList replaceAll="true"></pricingList>
                          </Price>';
    }
    public static function deletePromotionXml($sku, $start_date, $end_date){
        $effectiveDate = HelpUtil::get_utc_time_walmart_deal($start_date,null,'ADD',180);
        $expirationDate = HelpUtil::get_utc_time_walmart_deal($end_date,null);
        return '<Price>
                  <itemIdentifier>
                    <sku>'.$sku.'</sku>
                  </itemIdentifier>
                  <pricingList replaceAll=\'true\'>
                  </pricingList>
                </Price>';
    }
    public static function deal_item_chunks( $items, $deal_detail, $start=true ){

        $xml = [];
        $effectiveDate = HelpUtil::get_utc_time_walmart_deal(date('Y-m-d H:i:s'),'Asia/Kuala_Lumpur','ADD',300);

        foreach ( $items as $item_detail ){
            if ( $start ){
                $xml[] = self::createDealXml($effectiveDate,$deal_detail->end_date,$item_detail['deal_price'],$item_detail['sku'],'REDUCED');
            }
            else {
                $xml[] = self::deleteAllPromotionXml($item_detail['sku']);
            }
        }
        return array_chunk($xml,1000);
    }
    public static function getSkuPromotions($channel,$sku){
        if($channel && $sku!='')
        {
            self::set_config((object)$channel);
            /// header
            $headers = ['accept: application/json',
                'Content-Type: multipart/form-data',
                'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                'WM_SVC.NAME:Walmart Marketplace',
                'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
                'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            ];




            $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/promo/sku/'.urlencode($sku);
            //echo $requestUrl;die;
            $response= self::make_curl_request($headers,null,$requestUrl,'GET');
            self::debug(json_decode($response));

        }
        return 'invalid channel or feed is empty' ;
    }
    public static function getFeedDetail($channel, $feed){
        if($channel && $feed!='')
        {
            self::set_config((object)$channel);
            /// header
            $headers = [
                'accept: application/json',
                'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                'WM_SVC.NAME:Walmart Marketplace',
                'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
                'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            ];




            $requestUrl = self::$current_channel->api_url . '/feeds/'.($feed).'?includeDetails=true&limit=100';
            $response= self::make_curl_request($headers,null,$requestUrl,'GET');
            $response = json_decode($response,true);
            $results=$response;
            if ( $response['itemsReceived'] > 100 ){
                $offset_total = $response['itemsReceived'] / 100;
                $offset_total = ceil($offset_total);
                $offset = 0;
                $results['itemDetails']['itemIngestionStatus'] = [];
                for ( $i=0; $i<=$offset_total; $i++ ){
                    $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/feeds/'.($feed).'?includeDetails=true&limit=100&offset='.$offset;
                    $response= self::make_curl_request($headers,null,$requestUrl,'GET');
                    $response = json_decode($response,true);
                    if ( isset($response['itemDetails']['itemIngestionStatus']) ){
                        foreach( $response['itemDetails']['itemIngestionStatus'] as $value ){
                            $results['itemDetails']['itemIngestionStatus'][] = $value;
                        }
                    }
                    $offset+=100;
                }
                return $results;
            }else{
                return $response;
            }

        }
        return 'invalid channel or feed is empty' ;
    }
    public static function updateChannelStock($channel,$items)
    {
        if($channel && $items)
        {
            self::set_config((object)$channel);

            /// header
            $headers = ['accept: application/xml',
                        'Content-Type: multipart/form-data',
                        'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
                        'WM_SVC.NAME:Walmart Marketplace',
                        'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
                        'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
                     ];

            //body
            $body ='<InventoryFeed xmlns="http://walmart.com/">
                     <InventoryHeader>
                        <version>1.4</version>
                    </InventoryHeader>
                    '.$items.'
                    </InventoryFeed>';
            $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/feeds?feedType=inventory';
            return self::make_curl_request($headers,['file'=>$body],$requestUrl,'POST');

        }
        return 'invalid channel or sku array sent' ;

    }

    /**
     * orders fetching
     */
    public static  function fetchOrdersApi($channel,$time_period=null,$next_page=null)
    {
        self::set_config((object)$channel);
        $start_date= gmdate('Y-m-d\TH:i:s\Z',1572566400); //ISO8601 format  //2019-11-1 00:00:00
        $end_date= gmdate('Y-m-d\TH:i:s\Z'); //ISO8601 format
        /// header
        $headers = ['accept: application/xml',
            'Content-Type: multipart/form-data',
            'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
            'WM_SVC.NAME:Walmart Marketplace',
            'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
            'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
        ];

        if ($time_period == "day") { // whole day 24 hours
           // $start_date = gmdate('Y-m-d\TH:i:s\Z',(time()-(1440*60))); // for cron job // after 24 hour //
           // $start_date = gmdate('Y-m-d\TH:i:s\Z',(time()-(1440*60))); // for cron job will bring last 15 days orders// walmart not giving order update date
            $start_date = gmdate('Y-m-d\TH:i:s\Z',(time()-(80600*60))); // / walmart not giving order update date
        } elseif($time_period == "chunk") {  // small period
             $start_date = gmdate('Y-m-d\TH:i:s\Z',(time()-(20*60))); // for cron job // after 20 min
        }

        if($next_page)
            $params="nextCursor=".$next_page;
        else
            $params="nextCursor=*&"."createdStartDate=".$start_date."&limit=200";

        $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/orders?'.$params;
        $response= self::make_curl_request($headers,null,$requestUrl,'GET');
      //  self::debug($response);
        if($response)
        {
            $result=json_decode($response);
            self::orderData(isset($result->list->elements->order) ? $result->list->elements->order:NULL);
            if(isset($result->list->meta->nextCursor) && $result->list->meta->nextCursor)
            {
                if(empty($result->list->meta->nextCursor))
                    return;

                self::fetchOrdersApi($channel,null,$result->list->meta->nextCursor);
            }

        }
    }


    // order items
    private static function orderItems($order)
    {
        $items=array();
        foreach($order->orderLines->orderLine as $val)
        {
            $item_tax=isset($val->charges->charge[0]->tax->taxAmount->amount) ?$val->charges->charge[0]->tax->taxAmount->amount:0 ;
             $if_repeat=array_search($val->item->sku,array_column($items,'item_sku'));
             if($if_repeat!==false)  // if order item is repeated then just add one more quantity and skip iteration
             {
                 $items[$if_repeat]['quantity']= $items[$if_repeat]['quantity'] + 1;
                 $items[$if_repeat]['item_tax']= $items[$if_repeat]['item_tax'] + $item_tax;
                 $items[$if_repeat]['sub_total']= ($items[$if_repeat]['quantity'] * $items[$if_repeat]['paid_price']);
                 continue;
             }
            $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$val->item->sku,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
            $qty=$val->orderLineQuantity->amount;
            $paid_price=$val->charges->charge[0]->chargeAmount->amount;
            $items[]=array(
                'order_id'=> $order->purchaseOrderId,
                'sku_id'=>(isset($sku_id) && $sku_id) ? $sku_id :"",
                //'sku_code'=>$val->product_reference, //sku number
                'order_item_id'=>$val->lineNumber,
                'item_status'=>end($val->orderLineStatuses->orderLineStatus)->status,
                'shop_sku'=>'',
                'price'=>$val->charges->charge[0]->chargeAmount->amount,
                'paid_price'=>$paid_price,
                'item_tax'=>$item_tax,
                'shipping_amount'=>0,//isset($val->charges->charge[1]->chargeAmount->amount) ? $val->charges->charge[1]->chargeAmount->amount:0,
                'item_discount'=>0,
                'sub_total'=>($qty * $paid_price),
                'item_created_at'=>gmdate('Y-m-d H:i:s',($order->orderDate/1000)),
                'item_updated_at'=>gmdate('Y-m-d H:i:s',($val->statusDate/1000)),
                'full_response'=>'',//json_encode($order_items),
                'quantity'=>$qty,
                'item_sku'=>$val->item->sku,
                'stock_after_order' =>""
            );
        }

        return $items;

    }


    private static function orderData($data=null)
    {

        $response=array();
        if($data)
        {
            foreach($data as $key=>$order)
            {

                $customer_name=explode(' ' ,$order->shippingInfo->postalAddress->name);
                $order_items_list=self::orderItems($order);
                $order_status=array_unique( array_column($order_items_list,'item_status'));
                $order_status=implode(',' , $order_status);
                $order_total_items=count($order->orderLines->orderLine);
                $order_data =[
                    'order_id'=>$order->customerOrderId,
                    'order_no'=>$order->purchaseOrderId,
                    'channel_id'=>self::$current_channel->id,
                    'payment_method'=>"" ,
                    'order_total'=>(array_sum(array_column($order_items_list,'paid_price')) * $order_total_items),
                    'order_created_at'=>gmdate('Y-m-d H:i:s',($order->orderDate/1000)),  // utc time
                    'order_updated_at'=>gmdate('Y-m-d H:i:s',($order->orderDate/1000)),
                    'order_status'=>$order_status,
                    'total_items'=>$order_total_items,//count($order_items_list),
                    'order_shipping_fee'=>0,
                    'order_discount'=>0,
                    'cust_fname'=>isset($customer_name[1]) ? $customer_name[1]:$order->shippingInfo->postalAddress->name,
                    'cust_lname'=>isset($customer_name[0]) ? $customer_name[0]:$order->shippingInfo->postalAddress->name,
                    'full_response'=>'',//$apiresponse,
                ];

                $response=[
                    'order_detail'=>$order_data,
                    'items'=>$order_items_list,
                    'customer'=>self::orderCustomerDetail(isset($order->shippingInfo) ? $order->shippingInfo:null),
                    'channel'=>self::$current_channel, // need to get channel detail in orderutil
                ];

                OrderUtil::saveOrder($response);  // process data in db
            }
        }
       // echo json_encode($response);die();
       return;
    }

    /// detail of customer who ordered
    private static function orderCustomerDetail($data=null)
    {
        $customer_name=explode(' ' ,$data->postalAddress->name);
         return [
            'billing_address'=>[
                'fname'=>isset($customer_name[1]) ? $customer_name[1]:$data->postalAddress->name,
                'lname'=>isset($customer_name[0]) ? $customer_name[0]:$data->postalAddress->name,
                'address'=>$data->postalAddress->address1,
                'phone'=>$data->phone,
                'state'=> $data->postalAddress->state,
                'city'=>$data->postalAddress->city,
                'country'=>$data->postalAddress->country,
                'postal_code'=>$data->postalAddress->postalCode ,
            ],
            'shipping_address'=>[
                'fname'=>isset($customer_name[1]) ? $customer_name[1]:$data->postalAddress->name,
                'lname'=>isset($customer_name[0]) ? $customer_name[0]:$data->postalAddress->name,
                'address'=>$data->postalAddress->address1,
                'phone'=>$data->phone,
                'state'=> $data->postalAddress->state,
                'city'=>$data->postalAddress->city,
                'country'=>$data->postalAddress->country,
                'postal_code'=>$data->postalAddress->postalCode ,
            ],

        ];

    }


    public static function ShipOrderLines($params){

        $channel = Channels::find()->where(['id'=>$params['channelId']])->one();
        $warehouseInfo = WarehouseUtil::GetItemWarehouseDetail($params['order_item_id_PK']);
        self::set_config($channel);

        /// header
        $headers = ['accept: application/xml',
            'Content-Type: application/xml',
            'Host: marketplace.walmartapis.com',  // extra
            'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
            'WM_SVC.NAME: Walmart Marketplace',
            'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
            'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            'WM_SVC.VERSION: 1.0.0',
        ];
        ////////////
        //$utcDateTime = gmdate('Y-m-dTH:i:sZ', strtotime($params['shipping_date']));
        $utcDateTime =gmdate('Y-m-d\TH:i:s\Z',strtotime($params['shipping_date']));
        $seller_order_id=isset($params['customer_order_id']) ? $params['customer_order_id']: rand();
        //body
        $body ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <orderShipment xmlns="http://walmart.com/mp/v3/orders"> 
                  <orderLines>
                    <orderLine>
                      <lineNumber>'.$params['channel_order_item_id'].'</lineNumber>
                      <sellerOrderId>'. $seller_order_id.'</sellerOrderId>
                      <orderLineStatuses>
                        <orderLineStatus>
                          <status>Shipped</status>
                          <statusQuantity>
                            <unitOfMeasurement>Each</unitOfMeasurement>
                            <amount>1</amount>
                          </statusQuantity>
                          <trackingInfo>
                            <shipDateTime>'.$utcDateTime.'</shipDateTime>
                            <carrierName>
                              <carrier>'.$params['courier_name'].'</carrier>
                            </carrierName>
                            <methodCode>Standard</methodCode>
                            <trackingNumber>'.$params['tracking_number'].'</trackingNumber>
                          </trackingInfo>
                          <returnCenterAddress>
                            <name>'.$warehouseInfo->name.'</name>
                            <address1>'.$warehouseInfo->address.'</address1>
                            <address2>'.$warehouseInfo->address.'</address2>
                            <city>'.$warehouseInfo->city.'</city>
                            <state>'.$warehouseInfo->state.'</state>
                            <postalCode>'.$warehouseInfo->zipcode.'</postalCode>
                            <country>'.$warehouseInfo->country.'</country>
                            <dayPhone>'.$warehouseInfo->phone.'</dayPhone>
                            </returnCenterAddress>
                        </orderLineStatus>
                      </orderLineStatuses>
                    </orderLine>
                  </orderLines>
                </orderShipment>';
        $requestUrl = rtrim(self::$current_channel->api_url,'/') . '/orders/'.$params['channel_order_number'].'/shipping';
        //echo $requestUrl;die;
        $response=self::make_curl_request($headers,$body,$requestUrl,'POST');

        /////save response
        if(substr($response, 0, 5) == "<?xml") { // if  it is xml // response of walmart  is in XSD not pure xml
            $array =simplexml_load_string($response);
            if ($array!== false) {
                $schema = new \DOMDocument();
                $schema->loadXML($response); //that is a String holding your XSD file contents
                $contains_error=$schema->getElementsByTagName('error');  // if contains error tag
                $contains_order_tag = $schema->getElementsByTagName('order'); // if contains order tag
                $contains_order_id_tag = $schema->getElementsByTagName('purchaseorderid'); // if contains purchaseorderid tag
                if(isset($contains_order_tag->length) && $contains_order_tag->length && isset($contains_order_id_tag->length) && $contains_order_id_tag->length)
                    return ['status'=>'success','response'=>$response];
                elseif(isset($contains_error->length) && $contains_error->length )
                    return ['status'=>'failure','response'=>$response];
            };
        }
        elseif($response && is_string($response)){ // if response is in json
            $converted=json_decode($response);
            if(json_last_error() === JSON_ERROR_NONE){
                if(isset($converted->errors) || isset($converted->error))
                    return ['status'=>'failure','response'=>$converted];
                elseif(isset($converted->order) || isset($converted->error->purchaseOrderId))
                    return ['status'=>'success','response'=>$converted];
            }
        }

        return ['status'=>'failure','response'=>$response]; // if respnse not handeled above then at last send failure


    }

   /* public static function ship_order_test($params)
    {
        $channel = Channels::find()->where(['id'=>$params['channelId']])->one();
        $warehouseInfo = WarehouseUtil::GetItemWarehouseDetail($params['order_item_id_PK']);
        self::set_config($channel);


        $headers = ['accept: application/xml',
            'Content-Type: application/xml',
            'Host: marketplace.walmartapis.com',  // extra
            'Authorization: Basic ' . base64_encode(self::$current_channel->api_user . ":" . self::$current_channel->api_key),
            'WM_SVC.NAME: Walmart Marketplace',
            'WM_QOS.CORRELATION_ID:' . self::$current_channel->name . "us" . date('Ymdhis'), // unique request id
            'WM_SEC.ACCESS_TOKEN:' . self::$current_token->access_token,
            'WM_SVC.VERSION: 1.0.0',
        ];

        $utcDateTime =gmdate('Y-m-d\TH:i:s\Z',strtotime($params['shipping_date']));
        $seller_order_id=isset($params['customer_order_id']) ? $params['customer_order_id']: rand();
        //body
        $body ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <orderShipment xmlns="http://walmart.com/mp/v3/orders">
                  <orderLines>
                    <orderLine>
                      <lineNumber>'.$params['channel_order_item_id'].'</lineNumber>
                      <sellerOrderId>'. $seller_order_id.'</sellerOrderId>
                      <orderLineStatuses>
                        <orderLineStatus>
                          <status>Shipped</status>
                          <statusQuantity>
                            <unitOfMeasurement>Each</unitOfMeasurement>
                            <amount>1</amount>
                          </statusQuantity>
                          <trackingInfo>
                            <shipDateTime>'.$utcDateTime.'</shipDateTime>
                            <carrierName>
                              <carrier>'.$params['courier_name'].'</carrier>
                            </carrierName>
                            <methodCode>Standard</methodCode>
                            <trackingNumber>'.$params['tracking_number'].'</trackingNumber>
                          </trackingInfo>
                          <returnCenterAddress>
                            <name>'.$warehouseInfo->name.'</name>
                            <address1>'.$warehouseInfo->address.'</address1>
                            <address2>'.$warehouseInfo->address.'</address2>
                            <city>'.$warehouseInfo->city.'</city>
                            <state>'.$warehouseInfo->state.'</state>
                            <postalCode>'.$warehouseInfo->zipcode.'</postalCode>
                            <country>'.$warehouseInfo->country.'</country>
                            <dayPhone>'.$warehouseInfo->phone.'</dayPhone>
                            </returnCenterAddress>
                        </orderLineStatus>
                      </orderLineStatuses>
                    </orderLine>
                  </orderLines>
                </orderShipment>';
        $requestUrl = self::$current_channel->api_url . '/orders/'.$params['channel_order_number'].'/shipping';

        $response=self::make_curl_request($headers,$body,$requestUrl,'POST');

        /////save response
        if(substr($response, 0, 5) == "<?xml") { // if  it is xml // response of walmart  is in XSD not pure xml
            $array =simplexml_load_string($response);
            if ($array!== false) {
                $schema = new \DOMDocument();
                $schema->loadXML($response); //that is a String holding your XSD file contents
                $contains_error=$schema->getElementsByTagName('error');  // if contains error tag
                $contains_order_tag = $schema->getElementsByTagName('order'); // if contains order tag
                $contains_order_id_tag = $schema->getElementsByTagName('purchaseorderid'); // if contains purchaseorderid tag
                if(isset($contains_order_tag->length) && $contains_order_tag->length && isset($contains_order_id_tag->length) && $contains_order_id_tag->length)
                    return ['status'=>'success','response'=>$response];
                elseif(isset($contains_error->length) && $contains_error->length )
                    return ['status'=>'failure','response'=>$response];
            };
        }
        elseif($response && is_string($response)){ // if response is in json
            $converted=json_decode($response);
            if(json_last_error() === JSON_ERROR_NONE){
                if(isset($converted->errors) || isset($converted->error))
                    return ['status'=>'failure','response'=>$converted];
                elseif(isset($converted->order) || isset($converted->error->purchaseOrderId))
                    return ['status'=>'success','response'=>$converted];
            }
        }

        return ['status'=>'failure','response'=>$response]; // if respnse not handeled above then at last send failure
    }*/
}
