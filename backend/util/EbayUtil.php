<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 9/12/2019
 * Time: 12:10 PM
 */
namespace backend\util;

use backend\controllers\ApiController;
use common\models\Channels;
use common\models\ChannelsProducts;
use Yii;

class EbayUtil {

    private static $api_url = '';
    private static $current_channel;
    private static $api_key = '';
    public static $counter=0;
    private static $current_order_page=1;

    public static function GetChannelApiByChannelId($channelId){

        $channels = Channels::find()->andWhere(['is_active' => 1,'id'=>$channelId])->one();

        if(!empty($channels)){
            self::$api_url = $channels->api_url;
            self::$api_key = $channels->api_key;
        }

    }
    public static function GetAPIAccessRules ( $channel_id ){
        self::GetChannelApiByChannelId( $channel_id );

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                    <GetApiAccessRulesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                      <!-- (No call-specific Input fields) -->
                      <RequesterCredentials>
                        <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                      </RequesterCredentials>
                      <!-- Standard Input Fields -->
                       <Version>859</Version>
                      <ErrorLanguage>en_US</ErrorLanguage>
                      <WarningLevel>High</WarningLevel>
                    </GetApiAccessRulesRequest>';

        /*$Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                            <ItemID>'.$item_id.'</ItemID>
                            '.$variation.'
                        </GetItemRequest>';*/

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: GetApiAccessRules",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            die($err);
            return "cURL Error #:" . $err;
        } else {

            $response = simplexml_load_string($response);
            //echo json_encode($response);die;
            return json_encode($response);
        }
    }
    public static function GetAllItemIds( $channel_id ){

        $EntriesPerpage = 10;
        $PageNumber = 1;

        $Products = self::GetMyeBaySelling($channel_id, $EntriesPerpage, $PageNumber);
        $Products = json_decode($Products);
      //  self::debug($Products);
        if (!isset($Products->ActiveList->PaginationResult->TotalNumberOfPages))
            self::debug($Products);

        $TotalPages = $Products->ActiveList->PaginationResult->TotalNumberOfPages; // get the total pages count
       // $TotalPages = 1;
        $Items_List = [];

        for ( $PageNumber=1 ; $PageNumber <= $TotalPages ; $PageNumber++ ){

            $Products = self::GetMyeBaySelling($channel_id, $EntriesPerpage, $PageNumber);
            $Products = json_decode($Products);

            foreach ( $Products->ActiveList->ItemArray->Item as $value ) {

                $Items_List[] = $value->ItemID;

            }
        }
        return $Items_List;
    }
    public static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    /// get channel products
    public static function ChannelProducts($channel=null,$single_item=null)
    {

    }

    /////get ebay category///
    /// /
    public static function getEbaycategories($channel)
    {
        die('in progress');
        self::GetChannelApiByChannelId( $channel->id );
        $curl = curl_init();

        $Req_Xml='<?xml version="1.0" encoding="utf-8"?>
                    <GetCategoriesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                     <RequesterCredentials>
                             <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                       </RequesterCredentials>
                      
                      
        
                      <ErrorLanguage>en_US</ErrorLanguage>
                      <DetailLevel>ReturnAll</DetailLevel>
                        <LevelLimit>1</LevelLimit>
                      <WarningLevel>High</WarningLevel>
                    </GetCategoriesRequest>';


        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 8ce986ef-c083-f148-beea-b61f6a24fe28",
                "x-ebay-api-call-name: GetCategories",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            //  die($err);
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            echo  json_encode($response); die();
        }
    }

    public static function GetAllProducts($channel_id){

        /*$Item = self::GetItemDetail($channel_id, '283985223454');
        $Item = json_decode($Item);
        self::debug($Item);*/
        //////////////////////////////////////////////////////
        $ItemList = self::GetAllItemIds($channel_id);
        $ItemDetail = [];

        foreach ( $ItemList as $ItemId ){

            $Item = self::GetItemDetail($channel_id, $ItemId);
            $Item = json_decode($Item);
            $ItemDetail[] = $Item;

        }
        return $ItemDetail;
    }
    public static function GetProductStock( $Channel, $ItemId, $Sku="" ){
        $stock = NULL;  // to return stock available
        $ItemDetail = self::GetProductDetail($Channel->id, $ItemId, $Sku);
        $ItemDetail = json_decode($ItemDetail);

        if ( isset( $ItemDetail->Item->Variations->Variation->Quantity ) )
            $stock= $ItemDetail->Item->Variations->Variation->Quantity - (isset($ItemDetail->Item->Variations->Variation->SellingStatus->QuantitySold) ? $ItemDetail->Item->Variations->Variation->SellingStatus->QuantitySold:0);
        elseif ( isset( $ItemDetail->Item->Quantity ) )
            $stock = $ItemDetail->Item->Quantity - (isset($ItemDetail->Item->SellingStatus->QuantitySold) ? $ItemDetail->Item->SellingStatus->QuantitySold:0);


        return $stock;
    }
    public static function GetProductDetail( $channel_id, $item_id, $sku="" ){

        self::GetChannelApiByChannelId( $channel_id );

        $curl = curl_init();

        if ($sku=="")
            $variation = "";
        else
            $variation = "<VariationSKU>$sku</VariationSKU>";

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                            <ItemID>'.$item_id.'</ItemID>
                            '.$variation.'
                        </GetItemRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: GetItem",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            die($err);
            return "cURL Error #:" . $err;
        } else {

            $response = simplexml_load_string($response);
            // echo json_encode($response);
            return json_encode($response);
        }

    }
    public static function GetItemDetail( $channel_id, $ItemId ){

        self::GetChannelApiByChannelId($channel_id);

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                             <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                              <!--Enter an ItemID-->
                          <ItemID>'.$ItemId.'</ItemID>
                        </GetItemRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 8ce986ef-c083-f148-beea-b61f6a24fe28",
                "x-ebay-api-call-name: GetItem",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            return json_encode($response);
        }


    }
    public static function UpdatePrice ( $channel, $xml ){

        //first check if sku is not included in active deal , $db_product_primary_key is only sent from apicontroller to differentiate whether to check or not

        self::GetChannelApiByChannelId($channel);

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                    <ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                      <!-- Call-specific Input Fields -->
                      '.$xml.'
                      <RequesterCredentials>
                        <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                      </RequesterCredentials>
                      <ErrorLanguage>en_US</ErrorLanguage>
                      <WarningLevel>High</WarningLevel>
                    </ReviseInventoryStatusRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 8ce986ef-c083-f148-beea-b61f6a24fe28",
                "x-ebay-api-call-name: ReviseInventoryStatus",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            return json_encode($response);
        }

    }

    public static function SetRequestForPrice($data)
    {

        $xml = [];
        $counter=1;
        $steps = 0;
        foreach($data as $list)
        {
            if ( $counter==5 ){
                $counter=1;
                $steps++;
            }
            $xml[$steps][] = '<InventoryStatus>
                                <ItemID>'.$list['channels_products_sku'].'</ItemID>
                                <SKU>'.$list['channel_sku'].'</SKU> 
                                 <StartPrice currencyID="USD">'.$list['rccp'].'</StartPrice>
                                </InventoryStatus>';

            $counter++;
        }
        foreach ( $xml as $key=>$value ){
            $xml[$key] = implode('',$value);
        }
        return $xml;
    }

    public static function SetRequest($stocklist){

        $xml = [];
        $counter=1;
        $steps = 0;
        foreach($stocklist as $list)
        {
            if ( $counter==5 ){
                $counter=1;
                $steps++;
            }
            $list['stock'] = ($list['stock'] > 3) ? 3:$list['stock']; //did because we have sell limitation on ebay we can't push full stock

            $xml[$steps][] = '<InventoryStatus>
                                <ItemID>'.$list['sku_id'].'</ItemID>
                                <Quantity>'.$list['stock'].'</Quantity>
                                <SKU>'.$list['sku'].'</SKU>  
                                </InventoryStatus>';

            $counter++;
        }
        foreach ( $xml as $key=>$value ){
            $xml[$key] = implode('',$value);
        }
        return $xml;
    }
    public static function UpdateStock ( $channel, $Xml ){

        self::GetChannelApiByChannelId($channel);

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                    <ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                      <!-- Call-specific Input Fields -->
                      '.$Xml.'
                      <RequesterCredentials>
                        <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                      </RequesterCredentials>
                      <ErrorLanguage>en_US</ErrorLanguage>
                      <WarningLevel>High</WarningLevel>
                    </ReviseInventoryStatusRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 8ce986ef-c083-f148-beea-b61f6a24fe28",
                "x-ebay-api-call-name: ReviseInventoryStatus",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {

            return json_encode("cURL Error #:" . $err);

        } else {

            $response = simplexml_load_string($response);
            return json_encode($response);

        }

    }


    public static function fetchOrdersApi( $channel, $time_period )
    {
        // date_default_timezone_set('UTC'); // to get orders in utc
        $from = strtotime(date("Y-m-d H:i:s") .' -3 month' ); // will bring last 3 months orders//
        $current_time=date("Y-m-d\TH:i:s",time());
        $from= gmdate('Y-m-d\TH:i:s\Z',$from);
        ////
        //  $current_time=date("2020-08-10\TH:i:s");
        // $from= gmdate('2020-08-01\TH:i:s\Z');
        ///
        $filter ="<CreateTimeFrom>".$from."</CreateTimeFrom>";
        $filter .="<CreateTimeTo>". gmdate('Y-m-d\TH:i:s\Z',strtotime($current_time)). "</CreateTimeTo>";
        if ($time_period == "day")  // whole day 24 hours
        {

            $from = strtotime(date("Y-m-d H:i:s")  ) - (1440*60); // for cron job // after 24 hour //
            $from= gmdate('Y-m-d\TH:i:s\Z',$from);
            $filter ="<CreateTimeFrom>".$from."</CreateTimeFrom>";
            $filter .="<CreateTimeTo>". gmdate('Y-m-d\TH:i:s\.\Z',strtotime($current_time)). "</CreateTimeTo>";
        }
        else if($time_period == "chunk")
        {

            $from = strtotime(date("Y-m-d H:i:s") ) - (65*60); // for cron job // after 20 min
            $from= gmdate('Y-m-d\TH:i:s\Z',$from);
            $filter ="<CreateTimeFrom>".$from."</CreateTimeFrom>";
            $filter .="<CreateTimeTo>".gmdate('Y-m-d\TH:i:s\Z',strtotime($current_time))."</CreateTimeTo>";
        }
        $filter .="<Pagination><EntriesPerPage>100</EntriesPerPage><PageNumber>".self::$current_order_page."</PageNumber></Pagination>";
        $response=self::GetOrders($channel->id, $filter);

        if($response)
        {
            //echo $response;die();
            $res=json_decode($response);
            // self::debug($res);
            self::OrderData($channel,$res);
            if(isset($res->HasMoreOrders) && $res->HasMoreOrders && self::$current_order_page <=30) // less than 30 mean to avoid too many loops
            {
                self::$current_order_page++;
                self::fetchOrdersApi($channel, $time_period);
            }


        }
        //  date_default_timezone_set('Asia/Kuala_Lumpur'); // to reset time to local zone
        return $response;

    }

    private static function OrderData($channel,$data)
    {

        self::$current_channel=$channel;

        if(isset($data->OrderArray->Order))
        {
            $raw_data=array();

            if(!is_array($data->OrderArray->Order))
                $raw_data[]=$data->OrderArray->Order;
            else
                $raw_data=$data->OrderArray->Order;




            foreach($raw_data as $order)
            {
                // date_default_timezone_set('Asia/kuala_lumpur'); // store in american time
                // $order_created_at=date("Y-m-d H:i:s",strtotime($order->CreatedTime));
                // $order_updated_at=date("Y-m-d H:i:s",strtotime($order->CheckoutStatus->LastModifiedTime));
                $order_data =array(
                    'order_id'=>$order->OrderID,
                    'order_no'=>isset($order->ExtendedOrderID) ? $order->ExtendedOrderID:$order->OrderID,
                    'channel_id'=>self::$current_channel->id,
                    'payment_method'=>$order->CheckoutStatus->PaymentMethod,
                    'order_total'=>$order->Subtotal, //$order->AmountPaid, // subtotal is excluded tax , amount paid is included tax
                    'order_created_at'=>$order->CreatedTime,  //by default it is utc
                    'order_updated_at'=>$order->CheckoutStatus->LastModifiedTime,
                    'order_status'=>$order->OrderStatus,
                    'total_items'=>is_array($order->TransactionArray->Transaction) ? count($order->TransactionArray->Transaction) : 1,
                    'order_shipping_fee'=>$order->ShippingServiceSelected->ShippingServiceCost,
                    'order_discount'=>$order->AmountSaved,
                    'cust_fname'=>!(is_array($order->TransactionArray->Transaction)) ? $order->TransactionArray->Transaction->Buyer->UserFirstName
                        : $order->TransactionArray->Transaction[0]->Buyer->UserFirstName,
                    'cust_lname'=>!(is_array($order->TransactionArray->Transaction)) ? $order->TransactionArray->Transaction->Buyer->UserLastName
                        : $order->TransactionArray->Transaction[0]->Buyer->UserLastName,
                    'full_response'=>'',//$apiresponse,
                );

                $response=[
                    'order_detail'=>$order_data,
                    'items'=>self::OrderItems( $order ),  //get order items
                    'channel' => $channel,
                    'customer'=>self::orderCustomerDetail($order,$channel)
                ];
                //self::debug($response);
                OrderUtil::saveOrder($response);  // process data in db
            }

            //die();
        }
        return;



    }
    private static function orderCustomerDetail($order=null,$channel)
    {
        if(gettype($order->ShippingAddress->Name)=='object'){
            // echo "<pre>";
            // print_r($order);
            $name[0]=isset($order->TransactionArray->Transaction->Buyer->UserFirstName) ? $order->TransactionArray->Transaction->Buyer->UserFirstName:'user';
            $name[1]=isset($order->TransactionArray->Transaction->Buyer->UserLastName) ? $order->TransactionArray->Transaction->Buyer->UserLastName:'user';
        }else{
            $name=explode(' ',$order->ShippingAddress->Name );
        }


        return [
            'billing_address'=>[
                'fname'=>isset($name[0]) ? $name[0]: $order->ShippingAddress->Name ,
                'lname'=>isset($name[1]) ? $name[1]: $order->ShippingAddress->Name ,
                'address'=>(isset($order->ShippingAddress->Street1) && gettype($order->ShippingAddress->Street1)!='object') ? $order->ShippingAddress->Street1 : '' ,
                'state'=> (isset($order->ShippingAddress->StateOrProvince) && gettype($order->ShippingAddress->StateOrProvince)!='object') ? $order->ShippingAddress->StateOrProvince : '',
                'phone'=> (isset($order->ShippingAddress->Phone) && gettype($order->ShippingAddress->Phone)!='object') ? $order->ShippingAddress->Phone:'',
                'city'=>(isset($order->ShippingAddress->CityName) && gettype($order->ShippingAddress->CityName)!='object') ? $order->ShippingAddress->CityName : '',
                'country'=>(isset($order->ShippingAddress->CountryName) && gettype($order->ShippingAddress->CountryName)!='object') ? $order->ShippingAddress->CountryName : '' ,
                'postal_code'=>(isset($order->ShippingAddress->PostalCode) && gettype($order->ShippingAddress->PostalCode)!='object') ? $order->ShippingAddress->PostalCode : '' ,
            ],
            'shipping_address'=>[
                'fname'=>isset($name[0]) ? $name[0]: $order->ShippingAddress->Name ,
                'lname'=>isset($name[1]) ? $name[1]: $order->ShippingAddress->Name,
                'address'=>(isset($order->ShippingAddress->Street1) && gettype($order->ShippingAddress->Street1)!='object') ? $order->ShippingAddress->Street1 : '' ,
                'state'=> (isset($order->ShippingAddress->StateOrProvince) && gettype($order->ShippingAddress->StateOrProvince)!='object') ? $order->ShippingAddress->StateOrProvince : '',
                'phone'=> (isset($order->ShippingAddress->Phone) && gettype($order->ShippingAddress->Phone)!='object') ? $order->ShippingAddress->Phone:NULL,
                'city'=>(isset($order->ShippingAddress->CityName) && gettype($order->ShippingAddress->CityName)!='object') ? $order->ShippingAddress->CityName : '' ,
                'country'=>(isset($order->ShippingAddress->CountryName) && gettype($order->ShippingAddress->CountryName)!='object' ) ? $order->ShippingAddress->CountryName : '',
                'postal_code'=>(isset($order->ShippingAddress->PostalCode) && gettype($order->ShippingAddress->PostalCode)!='object') ? $order->ShippingAddress->PostalCode : '' ,
            ],

        ];

    }

    private static function OrderItems($order)
    {


        $items=array();
        $raw_data=array();
        if(!is_array($order->TransactionArray->Transaction))
            $raw_data[]=$order->TransactionArray->Transaction;
        else
            $raw_data=$order->TransactionArray->Transaction;


        foreach ($raw_data as $val ){
            //$Stock = NULL;
            $Item_Sku="";
            if(isset($val->Variation->SKU))
            {
                $Item_Sku=$val->Variation->SKU;
            }elseif(isset($val->Item->SKU)){
                $Item_Sku=$val->Item->SKU;
            }
            $Item_Id = $val->Item->ItemID;
            if ($Item_Sku!="")
            {
                //$Stock = self::GetProductStock(self::$current_channel,$Item_Id,$Item_Sku);
                $SkuId = HelpUtil::exchange_values('channel_sku','product_id',$Item_Sku,'channels_products');
                if ( $SkuId == 'false' )
                {
                    $_GET['ItemId'] = $Item_Id;
                    ApiController::_callEbayShopProducts(self::$current_channel);// add the product if it is not in our system.
                    $SkuId = HelpUtil::exchange_values('channel_sku','product_id',$Item_Sku,'channels_products');
                }
            }
            else
            {
                $SkuId = 0;
            }
            // date_default_timezone_set('Asia/kuala_lumpur'); // store in american time
            // $created_at=date("Y-m-d H:i:s",strtotime($val->CreatedDate));
            // $updated_at=date("Y-m-d H:i:s",strtotime($order->CheckoutStatus->LastModifiedTime));
            $price=$val->TransactionPrice;
            $qty=$val->QuantityPurchased;
            $items[]=array(
                'order_id'=> $order->OrderID,
                'sku_id' => $SkuId,
                //'sku_code'=>$val->product_reference, //sku number
                'order_item_id'=>$val->OrderLineItemID,
                'item_status'=>$order->OrderStatus,
                'shop_sku'=>'',
                'price'=>$price,
                'paid_price'=>$val->TransactionPrice,
                'shipping_amount' =>isset($val->ActualShippingCost) ? $val->ActualShippingCost:0 ,
                'item_discount'=>0,
                'sub_total'=>($qty * $price),
                'item_created_at'=>$val->CreatedDate,
                'item_updated_at'=>$order->CheckoutStatus->LastModifiedTime,
                'full_response'=>'',//json_encode($order_items),
                'quantity'=>$qty,
                'item_sku'=>$Item_Sku ,
                'stock_after_order' =>""
            );
        }
        return $items;





    }
    public static function organizeChannelProducts($data,$single_item=null)
    {

        $items=NULL;
        $products=json_decode($data);

        if(isset($products->items))
            $items=$products->items;
        else
            $items[]=$products;


        if($items)
        {
            $cat=CatalogUtil::getdefaultParentCategory();
            foreach($items as $product)
            {

                $data=[
                    //'parent_sku_id'=>0,  // product table column
                    'category_id'=>isset($cat->id) ? $cat->id:0,
                    'sku'=>$product->id,  // saving in channel_products table
                    'channel_sku'=>$product->sku,  // sku for product table and channel_sku for channels products table
                    'name'=>$product->name,
                    'cost'=>$product->price,
                    'rccp'=>$product->price,
                    'stock_qty'=>self::getItemStock($product->sku),
                    'channel_id'=>self::$current_channel->id,
                    'is_live'=>$product->status==2 ? 0:1     // is active or is live
                ];

                $product_id=CatalogUtil::saveProduct($data);  //save or update product and get product id

                if($product_id)
                {

                    $data['product_id'] = $product_id;
                    CatalogUtil::saveChannelProduct($data);  // save update channel product
                    if($single_item)  // if requested for single item then return product id and stock
                    {

                        return [
                            'product_id'=> $product_id,
                            'stock'=> $data['stock_qty']
                        ];
                    }
                }

            }

        }
        return;

    }
    public static function GetOrderTransactions( $channel_id , $filter='' ){
        self::GetChannelApiByChannelId($channel_id);

        $curl = curl_init();


        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                             
                              <OrderIDArray>
                                <OrderID>223601540788-2318625642012</OrderID>
                                <!-- ... more OrderID values allowed here ... -->
                              </OrderIDArray>
                              <!-- Standard Input Fields -->
                              <DetailLevel>ReturnAll</DetailLevel>
                              <!-- ... more DetailLevel values allowed here ... -->
                             <SortingOrder>Descending</SortingOrder>
                            <IncludeFinalValueFee>true</IncludeFinalValueFee>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                        </GetOrdersRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: GetOrders",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err)
        {
            //die($err);
            return "cURL Error #:" . $err;
        }
        else
        {
            $response = simplexml_load_string($response);
            // echo json_encode($response);
            // die();
            return json_encode($response);
        }
    }
    public static function GetAllOrders( $channel_id, $EntriesPerPage, $from, $to ){

        $current_time=date("Y-m-d\TH:i:s",time());

        $filter = '<CreateTimeFrom>'.$from.'</CreateTimeFrom>
                        <CreateTimeTo>'.$to.'</CreateTimeTo>
                          <Pagination>
                            <EntriesPerPage>'.$EntriesPerPage.'</EntriesPerPage>
                            <PageNumber>1</PageNumber>
                          </Pagination>';

        $orders = EbayUtil::GetOrders($channel_id,$filter);
        $orders = json_decode($orders);

        $total_orders = [];
        if (( $orders->HasMoreOrders == true || $orders->HasMoreOrders == 'true' )){
            $page = 1;
            while( 1 ){
                $filter = '<CreateTimeFrom>'.$from.'</CreateTimeFrom>
                        <CreateTimeTo>'.gmdate('Y-m-d\TH:i:s\Z',strtotime($current_time)).'</CreateTimeTo>
                          <Pagination>
                            <EntriesPerPage>100</EntriesPerPage>
                            <PageNumber>'.$page.'</PageNumber>
                          </Pagination>';

                $orders = EbayUtil::GetOrders($channel_id,$filter);
                $orders = json_decode($orders);
                if ( $orders->HasMoreOrders == false || $orders->HasMoreOrders == 'false' ){
                    var_dump($orders->HasMoreOrders);
                    break;
                }else{
                    foreach ( $orders->OrderArray->Order as $OrdDetail ){
                        $total_orders[] = $OrdDetail;
                    }
                    //$total_orders = array_merge($total_orders,$orders->OrderArray->Order);
                }
                $page++;
            }
        }
        return $total_orders;
    }
    public static function GetOrders( $channel_id, $filter ){


        self::GetChannelApiByChannelId($channel_id);

        $curl = curl_init();


        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                             ' . $filter . '
                             <SortingOrder>Descending</SortingOrder>
                            <IncludeFinalValueFee>true</IncludeFinalValueFee>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                        </GetOrdersRequest>';


        //echo $Req_Xml;
        // die();
        /*$Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetOrdersRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                            <CreateTimeFrom>2019-08-31T02:48:20.000Z</CreateTimeFrom>
                            <CreateTimeTo>2019-08-31T02:48:20.000Z</CreateTimeTo>
                            <IncludeFinalValueFee>true</IncludeFinalValueFee>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                        </GetOrdersRequest>';*/
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: GetOrders",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);

        if ($err)
        {
            //die($err);
            return "cURL Error #:" . $err;
        }
        else
        {
            $response = simplexml_load_string($response);
            // echo json_encode($response);
            //die();
            return json_encode($response);
        }
    }
    public static function GetMyeBaySelling($channel_id, $EntriesPerPage, $PageNumber){
        self::GetChannelApiByChannelId($channel_id);

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                            </RequesterCredentials>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                            <ActiveList>
                                <Sort>TimeLeft</Sort>
                                <Pagination>
                                    <EntriesPerPage>'.$EntriesPerPage.'</EntriesPerPage>
                                    <PageNumber>'.$PageNumber.'</PageNumber>
                                </Pagination>
                            </ActiveList>
                        </GetMyeBaySellingRequest>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: GetMyeBaySelling",
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            return json_encode($response);
        }

    }
    public static function GetTransactionIdByOrderItemId( $OrderItemId ){
        $item = explode('-',$OrderItemId);
        return end($item);
    }
    public static function MarkOrderShipped($channel_id, $orderLineItemIdEbay, $handOffPackageToCourierDateTime=''){
        self::GetChannelApiByChannelId($channel_id);

        $channel_auth_params = Channels::find()->where(['id'=>$channel_id])->one()->auth_params;
        if ( $channel_auth_params == '' ){
            echo 'Please set the auth parameters first then try again';
            die;
        }else{
            $channel_auth_params = json_decode($channel_auth_params);
        }
        $TransactionId = self::GetTransactionIdByOrderItemId($orderLineItemIdEbay);

        // set the shippingDate for the order, The date on which seller will handover package to courier.
        $shipment = '';
        if ( $handOffPackageToCourierDateTime!='' ){
            $shipment = '<Shipment>
                            <ShippedTime>'.$handOffPackageToCourierDateTime.'</ShippedTime>
                          </Shipment>';
        }

        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <CompleteSale xmlns="urn:ebay:apis:eBLBaseComponents">
                         <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                          </RequesterCredentials>
                          <!-- Call-specific Input Fields -->
                          <ItemID>'.$orderLineItemIdEbay.'</ItemID>
                          '.$shipment.'
                          <Shipped>true</Shipped>
                          <TransactionID>'.($TransactionId).'</TransactionID>
                          <!-- Standard Input Fields -->
                          <ErrorHandling>FailOnError</ErrorHandling>
                          <ErrorLanguage>en_US</ErrorLanguage>
                          <Version>859</Version>
                          <WarningLevel>High</WarningLevel>
                        </CompleteSale>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: CompleteSale",
                "x-ebay-api-compatibility-level: 859",
                "X-EBAY-API-CERT-NAME: ".$channel_auth_params->Cert_ID,
                "X-EBAY-API-DEV-NAME: ".$channel_auth_params->Dev_ID,
                "X-EBAY-API-APP-NAME: ".$channel_auth_params->App_ID,
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            return json_encode($response);
        }
    }
    public static function UpdateTracking($channel_id, $orderLineItemIdEbay, $TrackingNumber, $ShippingCarrierUsed){

        self::GetChannelApiByChannelId($channel_id);

        $channel_auth_params = Channels::find()->where(['id'=>$channel_id])->one()->auth_params;
        if ( $channel_auth_params == '' ){
            echo 'Please set the auth parameters first then try again';
            die;
        }else{
            $channel_auth_params = json_decode($channel_auth_params);
        }
        $TransactionId = self::GetTransactionIdByOrderItemId($orderLineItemIdEbay);
        //echo $TransactionId;die;
        $curl = curl_init();

        $Req_Xml = '<?xml version="1.0" encoding="utf-8"?>
                        <CompleteSale xmlns="urn:ebay:apis:eBLBaseComponents">
                         <RequesterCredentials>
                                <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                          </RequesterCredentials>
                          <!-- Call-specific Input Fields -->
                          <ItemID>'.$orderLineItemIdEbay.'</ItemID>
                          <OrderLineItemID>'.$orderLineItemIdEbay.'</OrderLineItemID>
                          <Shipment>
                            <ShipmentTrackingDetails>
                                <ShipmentTrackingNumber>'.$TrackingNumber.'</ShipmentTrackingNumber>
                                <ShippingCarrierUsed>'.$ShippingCarrierUsed.'</ShippingCarrierUsed>
                            </ShipmentTrackingDetails>
                            <!-- ... more ShipmentTrackingDetails nodes allowed here ... -->
                          </Shipment>
                          <TransactionID>'.($TransactionId).'</TransactionID>
                          <!-- Standard Input Fields -->
                          <ErrorHandling>FailOnError</ErrorHandling>
                          <ErrorLanguage>en_US</ErrorLanguage>
                          <Version>859</Version>
                          <WarningLevel>High</WarningLevel>
                        </CompleteSale>';

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Req_Xml,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "postman-token: 1afdb250-128d-9602-c995-2c0e4a621538",
                "x-ebay-api-call-name: CompleteSale",
                "x-ebay-api-compatibility-level: 859",
                "X-EBAY-API-CERT-NAME: ".$channel_auth_params->Cert_ID,
                "X-EBAY-API-DEV-NAME: ".$channel_auth_params->Dev_ID,
                "X-EBAY-API-APP-NAME: ".$channel_auth_params->App_ID,
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            $response = simplexml_load_string($response);
            return json_encode($response);
        }
    }
    public static function MarkOrderShipAndUpdateTracking( $channelId, $channel_order_item_id, $TrackingNumber, $CourierName ){
        $UpdateTrackingNumber = EbayUtil::CompleteSale($channelId, $channel_order_item_id, $TrackingNumber, $CourierName);
        $UpdateTrackingNumber = json_decode($UpdateTrackingNumber);
        if ( $UpdateTrackingNumber->Ack == 'Success' ){
            $ShopUpdated['item_sku'] = 'TrackingNumber Successfully Updated To Ebay';
        }
        else{
            $ShopUpdated['item_sku'] = $UpdateTrackingNumber->Errors->LongMessage;
        }
        return $UpdateTrackingNumber;
    }
    public static function MarkOrderShipAndUpdateShipmentDetail($Items,$ChannelId, $TrackingNumber,$CourierName){
        $ShopUpdated = [];
        foreach ( $Items as $ItemDetail ) {
            $UpdateTrackingNumber = EbayUtil::CompleteSale($ChannelId,$ItemDetail['order_item_id'],$TrackingNumber,$CourierName);
            $UpdateTrackingNumber = json_decode($UpdateTrackingNumber);
            if ( $UpdateTrackingNumber->Ack == 'Success' ){
                $ShopUpdated[$ItemDetail['item_sku']] = 'TrackingNumber Successfully Updated To Ebay';
            }
            else{

                $ShopUpdated[$ItemDetail['item_sku']] = $UpdateTrackingNumber->Errors->LongMessage;
            }
        }
        return $ShopUpdated;
    }

    private static function make_api_call($fields,$api_name)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => array(
                "authorization: ".self::$api_key,
                "cache-control: no-cache",
                "content-type: application/xml",
                "x-ebay-api-call-name: ". $api_name,
                "x-ebay-api-compatibility-level: 967",
                "x-ebay-api-siteid: 0"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
        {
            //die($err);
            return "cURL Error #:" . $err;
        }
        else
        {
            $response = simplexml_load_string($response);
            // echo json_encode($response);
            // die();
            return json_encode($response);
        }
    }

    private static function sale_price_template($deal)
    {
        $start_date= strtotime($deal->start_date);
        $end_date= strtotime($deal->end_date);
        $start_date= gmdate('Y-m-d\TH:i:s\Z',$start_date);
        $end_date= gmdate('Y-m-d\TH:i:s\Z',$end_date);
        $req_xml ='<?xml version="1.0" encoding="utf-8"?>
                                <SetPromotionalSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                  <RequesterCredentials>
                                    <eBayAuthToken>'.self::$api_key.'</eBayAuthToken>
                                  </RequesterCredentials>
                                  <WarningLevel>High</WarningLevel>
                                  <Action>Add</Action>
                                  <PromotionalSaleDetails>
                                    <PromotionalSaleName>'.$deal->name.'</PromotionalSaleName>
                                    <DiscountType>Percentage</DiscountType>
                                    <DiscountValue>'.$deal->discount.'</DiscountValue>
                                    <PromotionalSaleType>PriceDiscountOnly</PromotionalSaleType>
                                    <PromotionalSaleStartTime>'.$start_date.'</PromotionalSaleStartTime>
                                    <PromotionalSaleEndTime>'.$end_date.'</PromotionalSaleEndTime>
                                  </PromotionalSaleDetails>
                                </SetPromotionalSaleRequest>';
        return $req_xml;
    }

    private static function sale_price_skus_template($channel,$promotional_sale_id,$skus)
    {
        $item_ids="";
        foreach($skus as $sku)
        {
            $list_id=ChannelsProducts::find()->select('sku')->where(['channel_sku'=>$sku['sku'],'channel_id'=>$channel->id])->scalar();
            if($list_id)
                $item_ids .='<ItemID>'.$list_id.'</ItemID>';
        }
        $template='<?xml version="1.0" encoding="utf-8"?>
                    <SetPromotionalSaleListingsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                      <Action>Add</Action>
                      <PromotionalSaleID> long </PromotionalSaleID>
                      <PromotionalSaleItemIDArray>
                        '.$item_ids.'
                      </PromotionalSaleItemIDArray>
                      <ErrorLanguage>en_US</ErrorLanguage>
                      <Version>859</Version>
                      <WarningLevel>High</WarningLevel>
                    </SetPromotionalSaleListingsRequest>';
        return $template;
    }

    /**
     * update sale price on marketplace
     */
    public static function updateSalePrices($channel,$deal,$skus,$start_deal=true)
    {
        self::GetChannelApiByChannelId($channel->id);
        $item_ids="";
        foreach($skus as $sku)
        {
            $list_id=ChannelsProducts::find()->select('sku')->where(['channel_sku'=>$sku['sku'],'channel_id'=>$channel->id])->scalar();
            if($list_id)
                $item_ids .='<ItemID>'.$list_id.'</ItemID>';
        }
        print_r($item_ids); die();
        $sales_creation_response=""; // api call when sales creation
        $listing_creation_response=""; // api call when adding skus to that sales created
        $promotional_sale_id=NULL;
        if(strtolower($deal->discount_type)=="percentage")
        {
            if($start_deal) {  // if deal is starting
                $req_xml = self::sale_price_template($deal); // make xml format create sale
                $response = self::make_api_call($req_xml, 'SetPromotionalSale');
                if ($response) {
                    $sales_creation_response = json_decode($response);
                    if (isset($sales_creation_response['PromotionalSaleID'])) {
                        $promotional_sale_id = $sales_creation_response['PromotionalSaleID'];
                        $sku_xml = self::sale_price_skus_template($channel, $promotional_sale_id, $skus);  // make xml format for skus
                        $response2 = self::make_api_call($sku_xml, 'SetPromotionalSaleListings');
                        if ($response2) {
                            $listing_creation_responsee = json_decode($response);
                        }
                    }
                }
                return ['sales_api' => $sales_creation_response, 'sku_adding_in_sale_api' => $listing_creation_response];
            } // start deal

        }
    }
}