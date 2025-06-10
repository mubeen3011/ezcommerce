<?php


namespace backend\util;


use backend\controllers\ApiController;
use backend\controllers\MainController;
use common\models\Channels;
use common\models\WarehouseStockList;
use yii;

class LazadaUtil extends MainController
{
    private static $current_channel=null;
    private static $current_token=null;
    private static $products_fetched=0; // for pagination of products fetched
    private static $orders_fetched=0; // for pagination of orders fetched
    private static $order_loop_executed=0;

    private static function make_api_request($params)
    {
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";
        $accessToken = self::$current_token->access_token;
        $action = $params['action'];
        $method = $params['method'];
        $client = new \LazopClient(self::$current_channel->api_url,self::$current_channel->api_key,self::$current_channel->api_user);
        $request = new \LazopRequest($action, $method);
        foreach ($params['params'] as $k => $v) {
            $request->addApiParam($k, $v);
        }
        return $client->execute($request, $accessToken);
        //echo $resp; die();

    }

    public static function create_fresh_token($channel){
        self::$current_channel=$channel;
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";


        $c = new \LazopClient(self::$current_channel->api_url,self::$current_channel->api_key,self::$current_channel->api_user);
        // self::debug($c);
        $request = new \LazopRequest('/auth/token/create');
        //$request->addApiParam("code", '0_122925_piT2a3i0t5npwYLALDlKCKuB15527');
        $request->addApiParam("code", '0_122925_lWYhSLZuWJlNOgwgOFZREzjy1919');
        $response = $c->execute($request);
        $token=json_decode($response);
        self::debug($token);

    }

    /*
     * generation or refreshing of new token , lazada token expires in * days,
     */
    private static function generate_token($type)
    {
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";
        if($type=="refresh") { // refresh token

            $c = new \LazopClient(self::$current_channel->api_url,self::$current_channel->api_key,self::$current_channel->api_user);
           // self::debug($c);
            $request = new \LazopRequest('/auth/token/refresh');
            $request->addApiParam('refresh_token', self::$current_token->refresh_token);
            $response = $c->execute($request);
            $token=json_decode($response);
            // self::debug($token);
            if (isset($token->access_token)) {

                self::$current_token->access_token=$token->access_token;
                self::$current_token->refresh_token=$token->refresh_token;
                self::$current_token->expires_in=$token->expires_in;
                self::$current_token->refresh_expires_in=$token->refresh_expires_in;
                self::$current_token->last_api_call=strtotime("now");

            }
        }
        return;

    }

    private static function save_token_in_db()  // save new generated token in channel
    {
        return Yii::$app->db->createCommand()
            ->update('channels', ['auth_params' => json_encode(self::$current_token)],['id'=>self::$current_channel->id])
            ->execute();
    }

    private static function set_config($channel_detail=null)
    {
        if(!$channel_detail)
            return;

        self::$current_channel=$channel_detail;
        self::$current_token=json_decode($channel_detail->auth_params);
        // return;
        if (self::$current_token && isset(self::$current_token->access_token) && self::$current_token->access_token) {
            $token_generated_at=self::$current_token->last_api_call;
            $current_time=strtotime('now');
            if(($current_time-$token_generated_at) < 43200 )   // 12 hours
                return;

        }


        if(empty(self::$current_token))  // if the auth params field is  empty set  variables
            self::$current_token= json_decode('{"access_token":"","refresh_token":"","refresh_expires_in":0,"expires_in":0,"last_api_call":""}');

        $response=self::generate_token('refresh');  // generate new token
        self::save_token_in_db();  // save token in db
        return;
    }
    public static function Generate_Access_Token(){
        $channel=Channels::find()->where(['id'=>23])->one();
        self::set_config($channel);
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";
        $c = new \LazopClient(self::$current_channel->api_url,self::$current_channel->api_key,self::$current_channel->api_user);
        $request = new \LazopRequest('/auth/token/create');
        $request->addApiParam('code', $_GET['code']);
        $response = $c->execute($request);
        $token=json_decode($response);
        echo '<pre>';
        print_r($token);
        die;
        if (isset($token->access_token)) {

            self::$current_token->access_token=$token->access_token;
            self::$current_token->refresh_token=$token->refresh_token;
            self::$current_token->expires_in=$token->expires_in;
            self::$current_token->refresh_expires_in=$token->refresh_expires_in;
            self::$current_token->last_api_call=strtotime("now");

        }
        return;
    }
    public static function channelProducts($channel=null,$offset=0,$limit=100)
    {
        $response = self::GetAllProducts($channel->id,'all');

        if($response)
        {
            self::save_channel_products($response);
        }
        return ;
    }
    public static function GetProducts($channel_id,$filter='all',$offet,$sku_seller_list=[],$search=''){

        $channel=Channels::find()->where(['id'=>$channel_id])->one();
        self:: set_config($channel);
        //$channel = $channel_id;
        $ch = Channels::findOne(['id' => $channel_id,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/products/get';
        $customParams['params']['filter'] = $filter;
        $customParams['params']['sku_seller_list'] = json_encode($sku_seller_list);
        $customParams['params']['offset'] = $offet;
        $customParams['params']['options'] = '1';

        if ($search!='')
            $customParams['params']['search'] = $search;
        $response= self::make_api_request($customParams);
        $response = json_decode($response, true);
        return $response;
    }
    public static function GetAllProducts($channel_id, $filter,$ChildParentFormat=false,$sku_seller_list=[],$search=""){
        $response = self::GetProducts($channel_id,$filter,0,$sku_seller_list,$search);
        //self::debug($response);
        $Total_Products = $response['data']['total_products']+20;
        $All_Products = [];
        for ( $counter=0;$counter<=$Total_Products;$counter+20 )
        {
            $response = self::GetProducts($channel_id,$filter,$counter,$sku_seller_list,$search);

            if ( isset($response['data']['products']) ){
                foreach ( $response['data']['products'] as $value ){

                    if ( $ChildParentFormat == false ){
                        foreach ( $value['skus'] as $val1 ){
                            $val1['attributes']=$value['attributes'];
                            $All_Products[] = $val1;
                        }
                    }else{
                        $All_Products[] = $value;
                    }
                }
            }
            $counter += 20;
        }
        return $All_Products;
    }
    private static function save_channel_products($data)
    {
        //self::debug($data);
        if (isset($data))
        {
            $cat=CatalogUtil::getdefaultParentCategory();
            foreach ($data as $product)
            {
                $special_price=0;
                if($product['special_price']){
                    $current_date=date('Y-m-d H:i');
                    $special_time_end_date=date("Y-m-d H:i", strtotime($product['special_to_time']));
                    if($special_time_end_date >= $current_date)
                       $special_price=$product['special_price'];
                }
                $name="";
                if((isset($product['attributes']['name']))){
                    $name=$product['attributes']['name'];
                }elseif(isset($product['package_content'])){
                    $name=$product['package_content'];
                }
                $prepare=[
                    'category_id'=>isset($cat->id) ? $cat->id:0,
                    'sku'=>$product['SkuId'],  // saving in channel_products table
                    'channel_sku'=>$product['SellerSku'],  // sku for product table and channel_sku for channels products table
                    'variation_id'=>NULL,
                    'name'=>$name,
                    'image'=>isset($product['Images'][0]) ? $product['Images'][0]:NULL,
                    'cost'=>$special_price ? $special_price:$product['price'],
                    'ean' => NULL,
                    'rccp'=>$special_price ? $special_price:$product['price'],
                    'stock_qty'=>$product['quantity'],
                    'channel_id'=>self::$current_channel->id,
                    'is_live'=>$product['Status']=='active' ? 1:0 ,   // is active or is live
                ];
                $product_id=CatalogUtil::saveProduct($prepare);  //save or update product and get product id
                if($product_id)
                {
                    $prepare['product_id'] = $product_id;
                    CatalogUtil::saveChannelProduct($prepare);  // save , update channel product

                }
            }
        }
        return;

    }

    /**
     * update price of products
     */

    public static function updateChannelPrice($channel,$items,$unsync_skus)
    {
        if($channel && $items):

            self:: set_config($channel);
            $prepared_items=[];
            $index=$counter=0;
            foreach($items as $item)
            {
                if (in_array($item['channel_sku'],$unsync_skus)) // dont update excluded skus
                    continue;

                /* if (!in_array($item['channel_sku'],['GC1740/26','BHD274/03'])) // remove it testing
                     continue;*/

                if(fmod($counter,50)==0 && $counter > 0)  // batch update 50 per call
                    $index++;

                $prepared_items[$index][]='<Sku><SellerSku>'.$item['channel_sku'].'</SellerSku><Price>'.round($item['cost_price'], 2).'</Price><SalePrice>'.round($item['cost_price'], 2).'</SalePrice>   <SaleStartDate>'.date('Y-m-d').'</SaleStartDate> <SaleEndDate> '. date('Y-m-d', strtotime("+1 month")) .'</SaleEndDate></Sku> ';
                $counter++;
            }

            if($prepared_items)
                return  self::updateChannelPriceAction($prepared_items);

        endif;

        return "Failed to update";
    }
    public static function updateDealChannelPrice($channel,$items)
    {
        if($channel && $items):

            self:: set_config($channel);
            $prepared_items=[];
            $index=$counter=0;
            foreach($items as $item)
            {
                if(fmod($counter,50)==0 && $counter > 0)  // batch update 50 per call
                    $index++;

                $prepared_items[$index][]='<Sku><SellerSku>'.$item['channel_sku'].'</SellerSku><SalePrice>'.round($item['price'], 2).'</SalePrice>   <SaleStartDate>'.date('Y-m-d').'</SaleStartDate> <SaleEndDate> '. date('Y-m-d', strtotime("+3 month")) .'</SaleEndDate></Sku> ';
                $counter++;
            }

            if($prepared_items)
                return  self::updateChannelPriceAction($prepared_items);

        endif;

        return "Failed to update";
    }

    private static function updateChannelPriceAction($items)
    {
        $customParams['method'] = 'POST';
        $customParams['action'] = '/product/price_quantity/update';
        $db_log=[];
        foreach ($items as $k=>$item)
        {
            if(is_array($item)):
                $make_xml='<Request>';
                $make_xml .='<Product>';
                $make_xml .='<Skus>';
                foreach ($item as $subitem):
                    $make_xml .= $subitem;
                    //db log
                    $xml_to_php = simplexml_load_string($subitem, "SimpleXMLElement", LIBXML_NOCDATA);
                    $converted = json_encode($xml_to_php);
                    $list_log['bulk_sku_price'][]=json_decode($converted,TRUE);  // for db to track updates
                    ////
                endforeach;
                $make_xml .='</Skus>';
                $make_xml .='</Product>';
                $make_xml .='</Request>';
                //print_r($make_xml); die();
                $customParams['params']['payload']=$make_xml;
                $response=self::make_api_request($customParams);
                // $response="Updated stock and price";
                $db_log[]=[  // for db log
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];
            endif;

        }

        return $db_log;
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
        $stocks=[];
        $prepared_items=[];
        foreach ($stocklist as $value){
            if ( isset($gc_skus[$value['sku']]) ){
                $available = ($value['available'] < 0) ? 0 : $value['available'];
                $prepared_items[0][]='<Sku><SellerSku>'.$gc_skus[$value['sku']].'</SellerSku><Quantity>'.$available.'</Quantity> </Sku> ';
                //$stocks[$value['sku']] = ($value['available'] < 0) ? 0 : $value['available'];
            }
        }
        if($prepared_items)
            return  self::updateChannelStockAction($prepared_items);
    }
    // philips specific skus
    /*******
     * update stock
     **/
    public static function updateChannelStock($channel,$items,$unsync_skus)
    {

        if($channel && $items):

            self:: set_config($channel);
            $prepared_items=[];
            $index=$counter=0;
            foreach($items as $item)
            {
                if (in_array($item['sku'],$unsync_skus)) // dont update excluded skus
                    continue;

                if(fmod($counter,50)==0 && $counter > 0)  // batch update 50 per call
                    $index++;

                $prepared_items[$index][]='<Sku><SellerSku>'.$item['sku'].'</SellerSku><Quantity>'.$item['stock'].'</Quantity> </Sku> ';
                $counter++;
            }

            if($prepared_items)
                return  self::updateChannelStockAction($prepared_items);

        endif;

        return "Failed to update";
    }

    private static function updateChannelStockAction($items)
    {
        $customParams['method'] = 'POST';
        $customParams['action'] = '/product/price_quantity/update';
        $db_log=[];
        foreach ($items as $k=>$item)
        {
            if(is_array($item)):
                $make_xml='<Request>';
                $make_xml .='<Product>';
                $make_xml .='<Skus>';
                foreach ($item as $subitem):
                    $make_xml .= $subitem;
                    //db log
                    $xml_to_php = simplexml_load_string($subitem, "SimpleXMLElement", LIBXML_NOCDATA);
                    $converted = json_encode($xml_to_php);
                    $list_log['bulk_sku_stock'][]=json_decode($converted,TRUE);  // for db to track updates
                    ////
                endforeach;
                $make_xml .='</Skus>';
                $make_xml .='</Product>';
                $make_xml .='</Request>';
                // print_r($make_xml); die();
                $customParams['params']['payload']=$make_xml;
                $response=self::make_api_request($customParams);
                // $response="Updated stock and price";
                $db_log[]=[  // for db log
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];
            endif;

        }

        return $db_log;
    }
    public static function GetOrders($start_date, $end_date,$offset,$limit){
        $customParams['method'] = 'GET';
        $customParams['action'] = '/orders/get';
        //$customParams['params']['filter'] = $filter;
        $customParams['params']['offset'] = $offset;
        $customParams['params']['limit'] = $limit;

        /*$customParams['params']['update_after']=$start_date;
        $customParams['params']['update_before']=date('c',(strtotime('2019-12-10 23:59:59')));;*/

        $customParams['params']['created_after']=$start_date;
        $customParams['params']['created_before']=$end_date;;
        $customParams['params']['sort_direction']='DESC';
        $customParams['params']['sort_by']='updated_at';
        //$customParams['params']['sort_by']='updated_at';
        $response=self::make_api_request($customParams);
        $response=json_decode($response);
        return $response;
    }
    public static function GetAllOrders($start_date, $end_date,$limit){
        $offset = 0;
        $response = self::GetOrders($start_date,$end_date,$offset,$limit);

        //if ( $response->data->count==100 ){
        while( 1 ){

            $data = self::GetOrders($start_date,$end_date,$offset,$limit);
            if (!isset($data)){
                self::debug($data);
            }
            //if ( isset($data) && $data->data->orders ){
            if ( isset($data->data->orders) ){
                foreach ( $data->data->orders as $orders ){
                    $response->data->orders[]=$orders;
                }
            }else{
                break;
            }

            $offset+=$limit;
        }
        //}

        //self::debug($response);
        return $response;
    }
    /**
     * orders api
     */
    public static  function fetchOrdersApi($channel,$time_period=null,$limit=100)
    {

        if(++self::$order_loop_executed > 20 )  // to avoid too many loops of pagination
            die('loop breaked');


        $start_date= date('c',(strtotime('-3 months')));
        //$start_date= date('c',(strtotime('2019-05-29 00:00:00')));
        if($channel):
            self::set_config($channel);
            if ($time_period == "day")  // whole day 24 hours
                $start_date = date('c',(time()-(1440*60))); // for cron job // after 24 hour //
            elseif($time_period == "chunk")   // small period
                $start_date = date('c',(time()-(60*60))); // for cron job // last 12 min

            $end_date = date('c',(time()));


            $response = self::GetAllOrders($start_date,$end_date,$limit);

            //self::debug($response);
            if (isset($response->code) && $response->code=='IllegalAccessToken'){
                echo $response->message;
                die;
            }
            if(isset($response->data->orders) && !empty($response->data->orders))
            {
                self::orderData($response->data->orders); // save order data
            }

        endif;
        return;

    }
    public static function GetDocument($channel_id, $doc_type, $order_items_ids){
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        self::set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/order/document/get';
        $customParams['params']['doc_type'] = $doc_type;
        $customParams['params']['order_item_ids'] = $order_items_ids;
        $response = self::make_api_request($customParams);
        $response = json_decode($response,1);
        return $response;
    }
    public static function GetOrderDetail($orderId, $channelId){
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        self:: set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/order/get';
        $customParams['params']['order_id'] = $orderId;
        $response= self::make_api_request($customParams);
        return json_decode($response);
    }

    private static function orderData($data=null)
    {
        foreach ($data as $order)
        {
            $order_data =[
                'order_id'=>(gettype($order->order_id)=='double') ? number_format($order->order_id,0,'','') : $order->order_id,
                'order_no'=>(gettype($order->order_number)=='double') ? number_format($order->order_number,0,'','') : $order->order_number,
                'channel_id'=>self::$current_channel->id,
                'payment_method'=>$order->payment_method ,
                'order_total'=>$order->price,
                'order_created_at'=>gmdate('Y-m-d H:i:s',strtotime($order->created_at)),  // utc time
                'order_updated_at'=>gmdate('Y-m-d H:i:s',strtotime($order->updated_at)), // utc
                'order_status'=>implode(',',$order->statuses),
                'total_items'=>$order->items_count,
                'order_shipping_fee'=>$order->shipping_fee,
                'order_discount'=>$order->voucher,
                'cust_fname'=>$order->customer_first_name,
                'cust_lname'=>$order->customer_last_name,
                'full_response'=>'',//$apiresponse,
            ];

            $response=[
                'order_detail'=>$order_data,
                'items'=>self::orderItems($order->order_id),
                'customer'=>self::orderCustomerDetail($order),
                'channel'=>self::$current_channel, // need to get channel detail in orderutil
            ];
            //self::debug($response);
            OrderUtil::saveOrder($response);  // process data in db
        }
        return;
    }
    public static function SetInvoiceNumber($channel_id, $OrderItems, $InvoiceNumber){
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        self::set_config($channel);

        $resp = [];
        foreach ( $OrderItems as $ItemId ){
            $customParams['method'] = 'POST';
            $customParams['action'] = '/order/invoice_number/set';
            $customParams['params']['order_item_id']=$ItemId;
            $customParams['params']['invoice_number']=$InvoiceNumber;

            $response=self::make_api_request($customParams);
            $resp[] = $response;
        }

        return $resp;
    }
    public static function SetStatusToReadyToShip($channelId, $OrderItems, $DeliveryType, $TrackingNumber, $ShipmentProvider){

        $channel = Channels::find()->where(['id'=>$channelId])->one();
        self::set_config($channel);
        $customParams['method'] = 'POST';
        $customParams['action'] = '/order/rts';
        $customParams['params']['delivery_type']=$DeliveryType;
        $customParams['params']['order_item_ids']=$OrderItems;
        $customParams['params']['shipment_provider']=$ShipmentProvider;
        $customParams['params']['tracking_number']=$TrackingNumber;
        $response=self::make_api_request($customParams);
        return json_decode($response);
    }
    public static function SetStatusToPackedByMarketplace($channelId, $shipping_provider, $DeliverType, $OrderItemIds){
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        self::set_config($channel);
        $customParams['method'] = 'POST';
        $customParams['action'] = '/order/pack';
        $customParams['params']['shipping_provider']=$shipping_provider;
        $customParams['params']['delivery_type']=$DeliverType;
        $customParams['params']['order_item_ids']=$OrderItemIds;
        $response=self::make_api_request($customParams);
        return json_decode($response);
    }
    public static function GetShipmentProviders($channel_id, $orderId)
    {
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        self::set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/shipment/providers/get';
        $customParams['params']['order_id']=$orderId;

        $response=self::make_api_request($customParams);
        return json_decode($response);
    }
    public static function GetOrderItems($orderId, $channel_id){
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        self::set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/order/items/get';
        $customParams['params']['order_id']=$orderId;
        $response=self::make_api_request($customParams);
        return json_decode($response);
    }
    // order items
    private static function orderItems($order_id=null)
    {
        if (gettype($order_id)=='double'){
            $order_id = number_format($order_id,0,'','');
        }

        $items=[];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/order/items/get';
        $customParams['params']['order_id']=$order_id;
        $response=self::make_api_request($customParams);
        $response=json_decode($response);
        if(isset($response->data) && !empty($response->data))
        {
            foreach($response->data as $item)
            {

                $item_tax=isset($item->tax_amount) ? $item->tax_amount:0 ;
                $item_discount=$item->voucher_amount;
                $paid_price=$item->paid_price;
                $if_repeat=array_search($item->sku,array_column($items,'item_sku'));
                if($if_repeat!==false)  // if order item is repeated then just add one more quantity and skip iteration
                {
                    $items[$if_repeat]['quantity']= $items[$if_repeat]['quantity'] + 1;
                    $items[$if_repeat]['item_tax']= $items[$if_repeat]['item_tax'] + $item_tax;
                    $items[$if_repeat]['item_discount']= $items[$if_repeat]['item_discount'] + $item_discount;
                    $items[$if_repeat]['sub_total']= ($items[$if_repeat]['quantity'] * $items[$if_repeat]['paid_price']);
                    continue;
                }
                $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$item->sku,'channel_id'=>self::$current_channel->id)); // get id stored in channelproducts table against sku code sent
                $items[]=[
                    'order_id'=> $order_id,
                    'sku_id'=>(isset($sku_id) && $sku_id) ? $sku_id :"",
                    //'sku_code'=>$val->product_reference, //sku number
                    'order_item_id'=>(gettype($item->order_item_id)=='double') ? number_format($item->order_item_id,0,'','') : $item->order_item_id,
                    'item_status'=> $item->status,
                    'shop_sku'=>'',
                    'price'=>$item->item_price,
                    'paid_price'=>$paid_price,
                    'item_tax'=>$item_tax,
                    'shipping_amount'=>$item->shipping_amount,
                    'item_discount'=>$item_discount,
                    'sub_total'=>(1 * $paid_price),
                    'item_updated_at'=>gmdate('Y-m-d H:i:s',strtotime($item->updated_at)),
                    'item_created_at'=>gmdate('Y-m-d H:i:s',strtotime($item->created_at)),
                    'full_response'=>'',//json_encode($order_items),
                    'quantity'=>1,
                    'item_sku'=>$item->sku,
                    'fulfilled_by_warehouse' => ($item->shipping_type=='Own Warehouse') ? 'Own Warehouse' : '',
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
                    'fname'=>$data->address_billing->first_name,
                    'lname'=>$data->address_billing->last_name,
                    'address'=>$data->address_billing->address1,
                    'phone'=>$data->address_billing->phone ? $data->address_billing->phone:$data->address_billing->phone2,
                    'state'=> $data->address_billing->address3,
                    'city'=>$data->address_billing->city,
                    'country'=>$data->address_billing->country,
                    'postal_code'=>$data->address_billing->post_code,
                ],
                'shipping_address'=>[
                    'fname'=>$data->address_shipping->first_name,
                    'lname'=>$data->address_shipping->last_name,
                    'address'=>$data->address_shipping->address1,
                    'phone'=>$data->address_shipping->phone ? $data->address_billing->phone:$data->address_billing->phone2,
                    'state'=> $data->address_shipping->address3,
                    'city'=>$data->address_shipping->city,
                    'country'=>$data->address_shipping->country,
                    'postal_code'=>$data->address_shipping->post_code ,
                ],

            ];

        endif;

    }
    //////////////////////////////get fbl stock////////////////
    public static function GetFBLStock($channel,$seller_sku)
    {
        $fbl_stocks=0;
        self::set_config($channel);
        $auth_params = json_decode($channel->auth_params, true);
        $customParams['app_key'] = $channel->api_key;
        $customParams['app_secret'] = $channel->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/fbl/stocks/get';
        $customParams['params']['marketplace'] = 'LAZADA_MY';
        $customParams['params']['seller_sku'] = $seller_sku;
        //$customParams['params']['store_code'] = 'OMS-LAZADA-MY-W-1';
        $response = self::make_api_request($customParams);
        $result=json_decode($response);
       // self::debug($result);
        if(isset($result->data))
        {
            foreach($result->data as $store)
            {
                foreach($store->store_stocks as $stock)
                {
                    $fbl_stocks +=$stock->stocks->sellable->available;
                }
            }
        }
        return $fbl_stocks;
    }
    public static function GetFinanceTransactionDetails( $channel_id, $startDate, $endDate, $offset=0 ){

        $ch = Channels::findOne(['id' => $channel_id,'is_active'=>'1']);
        $channel = Channels::find()->where(['id'=>$channel_id])->one();
        self::set_config($channel);

        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/finance/transaction/detail/get';
        $customParams['params']['start_time'] = ($startDate);
        $customParams['params']['end_time'] = ($endDate);
        $customParams['params']['offset'] = ($offset);
        $response = self::make_api_request($customParams);
        $response = json_decode($response, true);
        return $response;
    }
    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)) {

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }

    public static function SetExtraColumns($data){
        $orderItems = [];
        foreach ( $data['data'] as $key=>$values ){
            $orderItems[] = $values['orderItem_no'];
        }
        $Sql = "SELECT oi.order_item_id,oi.paid_price, oi.shipping_amount from order_items oi where oi.order_item_id IN ("."'" . implode("','", $orderItems) . "'".")";
        $result = \Yii::$app->db->createCommand($Sql)->queryAll();
        $redefine=[];
        foreach ( $result as $value ){
            $redefine[$value['order_item_id']] = $value;
        }

        foreach ($data['data'] as $key=>$values){
            if ( isset($redefine[$values['orderItem_no']]) ){
                $data['data'][$key]['paid_price_ezcom'] = $redefine[$values['orderItem_no']]['paid_price'];
                $data['data'][$key]['shipping_amount_ezcom'] = $redefine[$values['orderItem_no']]['shipping_amount'];

                $data['data'][$key]['commission_amount_ezcom'] = ($redefine[$values['orderItem_no']]['paid_price'] * 5) / 100;
                $data['data'][$key]['commission_percentage_ezcom'] = '5%';
                $data['data'][$key]['transaction_fee_ezcom'] = ($redefine[$values['orderItem_no']]['paid_price'] * 2) / 100;
                $data['data'][$key]['fbl_fee_ezcom'] = 2.00;

                $total_fee = $data['data'][$key]['commission_amount_ezcom'] + $data['data'][$key]['transaction_fee_ezcom'] + $data['data'][$key]['fbl_fee_ezcom'];
                $data['data'][$key]['expected_receive_amount_ezcom'] = $redefine[$values['orderItem_no']]['paid_price'] - $total_fee;
            }
        }
        return $data;
    }
    public static function GetCompleteFinanceDetails( $channel_id, $startDate, $endDate ){
        $detail = self::GetFinanceTransactionDetails($channel_id,$startDate,$endDate);
        //echo '<pre>';print_r($detail);die;
        //return $detail;
        if ( count($detail['data']) == 500 ){
            $offset=500;
            while( 1 ){
                $moreDetail = self::GetFinanceTransactionDetails($channel_id,$startDate,$endDate, $offset);
                if ( isset($moreDetail['data']) && count($moreDetail['data'])>0 ){
                    foreach ( $moreDetail['data'] as $values ){
                        $detail['data'][]=$values;
                    }
                }else{
                    break;
                }
                $offset+=500;
            }
            return $detail;
        }else{
            return $detail;
        }
    }
    public static function GetFinanceDetail($channelId){
        $Sql = "SELECT * FROM finance_log WHERE channel_id = '".$channelId."' AND updated = 0;";
        return \Yii::$app->db->createCommand($Sql)->queryAll();
    }

    /****
     * @param $channel
     */
    public static function test($channel)
    {
        self:: set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/category/tree/get';
        $customParams['params']['options'] = '1';
        $response= self::make_api_request($customParams);
        echo "<pre>";
        print_r($response);
        die();
    }
    public static function test2($channel)
    {
        self:: set_config($channel);
        $customParams['method'] = 'GET';
        $customParams['action'] = '/category/attributes/get';
        $customParams['params']['primary_category_id'] = '10100798';
        $response= self::make_api_request($customParams);
        echo "<pre>";
        print_r($response);
        die();
    }
}