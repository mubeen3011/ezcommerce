<?php
namespace backend\util;
use common\models\ChannelsProducts;
use common\models\EzcomToWarehouseSync;
use common\models\OrderItems;
use common\models\Orders;
use common\models\Products;
use common\models\QuickbookSales;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use common\models\WarehouseStockLog;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\Facades\Customer;
use Swagger\Client\Models\OrderItem;
use Yii;
use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Data\IPPPurchaseOrderItemLineDetail;
use QuickBooksOnline\API\Core\CoreConstants;
use QuickBooksOnline\API\Diagnostics\TraceLevel;
use QuickBooksOnline\API\PlatformService\PlatformService;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\SalesReceipt;

class QuickbookUtil
{
    private static $current_channel = null;
    private static $accessTokenValue = null;
    private static $quickbookRef = 68;
    private static $accounts=null; //accounts details  //specially use when we create products
    public static function configuration(){

        $dataService = DataService::Configure(array(
            'auth_mode'       => 'oauth2',
            'ClientID'        => self::$current_channel->ClientID,
            'ClientSecret'    => self::$current_channel->ClientSecret,
            'accessTokenKey'  => self::$current_channel->accessTokenKey,
            'refreshTokenKey' => self::$current_channel->refreshTokenKey,
            'QBORealmID'      => self::$current_channel->QBORealmID,
            'baseUrl'         => "production",
          //  'baseUrl'         => "development",
            'enableRequestLogging' => true,
            'loggingTraceLevel' => TraceLevel::Verbose,
        ));

        return $dataService;
    }


    public static function set_token($params)
    {

        self::$current_channel = json_decode($params['configuration']);

        $oauth2LoginHelper = new OAuth2LoginHelper(self::$current_channel->ClientID,self::$current_channel->ClientSecret);
        $accessTokenObj = $oauth2LoginHelper->refreshAccessTokenWithRefreshToken(self::$current_channel->refreshTokenKey);
        $access_token = $accessTokenObj->getAccessToken();
        $refreshTokenValue = $accessTokenObj->getRefreshToken();
       // $error = $oauth2LoginHelper->;
        //print_r($error);
        if(!$refreshTokenValue){
                LogUtil::add_log(['type'=>'ezcom-to-warehouse-product-sync','entity_type'=>'warehouse','entity_id'=>$params['id'],'additional_info'=>'token generation failed, failed to connect to api','log_type'=>'error']);
                die();

        }

        //$arr_conf = array();
        $arr_conf['ClientID'] = self::$current_channel->ClientID;
        $arr_conf['ClientSecret'] = self::$current_channel->ClientSecret;
        $arr_conf['accessTokenKey'] = $access_token;
        $arr_conf['refreshTokenKey'] =$refreshTokenValue;
        $arr_conf['QBORealmID'] = self::$current_channel->QBORealmID;

    //    echo "<pre>";print_r($arr_conf);exit;

        $updateWarehouse = Warehouses::findOne($params['id']);
        $updateWarehouse->configuration=json_encode($arr_conf);
        $updateWarehouse->update();
        self::$current_channel=(object)$arr_conf;

    }

    public static function countProducts($dataService)
    {
        $products = $dataService->Query("Select count(*) from Item");
        $error = $dataService->getLastError();
        if ($error) {
            return NULL;
        }
        return $products;
    }

    public static function getStock($params)
    {
        $errors=[];
        $products=[];
        self::set_token($params);
        $dataService = self::configuration();
        $total_products=self::countProducts($dataService);
        if(!$total_products)
            return ['status'=>'failure','error'=>'no products found'];
        $pages=ceil($total_products/500);
        for($i=0; $i < $pages;$i++)
        {
            $offset=($i*500);
            $products[] = $dataService->Query("Select * from Item STARTPOSITION $offset MAXRESULTS 500");
            $error = $dataService->getLastError();
            if ($error) {
                $errors[]=$error->getResponseBody();
            }
        }
       // self::debug($products);
        if($products)
            return  ['status'=>'success','products'=>$products];
        elseif($errors)
            return ['status'=>'failure','error'=>$errors];
        else
            return ['status'=>'failure','error'=>'no products found'];
    }

    public static function SkuStockSetFormat( $list ){

        $reFormat = [];
        //foreach ($list as $key=>$val)
        //    $reFormat[]=['sku'=>$val->Sku,'available'=>($val->QtyOnHand) ? $val->QtyOnHand : 0];

        foreach ($list as $index=>$val){
            if(is_array($val))
            {
                foreach ($val as $key=>$product)
                    $reFormat[]=['sku'=>$product->Sku,'available'=>($product->QtyOnHand) ?$product->QtyOnHand : 0];
            } else{
                $reFormat[]=['sku'=>$val->Sku,'available'=>($val->QtyOnHand) ? $val->QtyOnHand : 0];
            }
        }
           // $reFormat[]=['sku'=>$val->Sku,'available'=>($val->QtyOnHand) ? $val->QtyOnHand : 0];


        return $reFormat;
    }

    /***
     * create customer in quickbook
     */
    public static function create_customer($data,$dataService)
    {
       // $dataService = self::configuration();
        $customer=$data['customer'];
        $email=$customer['shipping_email'] ? $customer['shipping_email']:$customer['billing_email'];
        $fname=$customer['billing_fname'] ? $customer['billing_fname'] :$customer['shipping_fname'];
        $lname=$customer['billing_lname'] ? $customer['billing_lname'] :$customer['shipping_lname'];
        if(empty($email))
            $email="abc_".$data['detail']['order_pk_id']."@test.com";

        $entities = $dataService->Query("SELECT * FROM Customer WHERE PrimaryEmailAddr = '" .$email . "'");
        if (!$entities) {
            $customerObj = Customer::create([
                "BillAddr" => [
                    "Line1" =>$customer['billing_address'] ? $customer['billing_address'] :$customer['shipping_address'],
                    "City" => $customer['billing_city'] ? $customer['billing_city']:$customer['shipping_city'] ,
                    "Country" => $customer['billing_country'] ?  $customer['billing_country']:$customer['shipping_country'],
                    "CountrySubDivisionCode" => "Pk",
                    "PostalCode" => $customer['billing_post_code'] ? $customer['billing_post_code']:$customer['shipping_post_code'],
                ],
                "Notes" => "",
                "Title" => "Mr",
                "GivenName" =>$fname ,
                "MiddleName" =>$lname ,
                "FamilyName" => $lname,
                "Suffix" => "",
                "FullyQualifiedName" => $fname. " " . $lname,
                "CompanyName" => "",
                "DisplayName" => $fname . " " . $lname . " " . time(),
                "PrimaryPhone" => [
                    "FreeFormNumber" => $customer['shipping_phone'] ? $customer['shipping_phone']:$customer['billing_phone'],
                ],
                "PrimaryEmailAddr" => [
                    "Address" => $email,
                ]
            ]);

            $resultingCustomerObj = $dataService->Add($customerObj);
            $error = $dataService->getLastError();
            if ($error) {
                return ['status'=>'failure','msg'=>$error->getResponseBody(),'customer_id'=>null];
            } else {
                return ['status'=>'success','msg'=>'','customer_id'=>$resultingCustomerObj->Id];
            }
        } else {
            $customer_id=null;
            if(isset($entities->Id))
                $customer_id=$entities->Id;
            elseif (isset($entities[0]->Id))
                $customer_id=$entities[0]->Id;

            return ['status'=>$customer_id ? 'success':'failure','msg'=>'','customer_id'=>$customer_id];
        }
    }

    /*****
     * @param $warehouse_id
     * @param $ezcom_orders
     * @return array
     * get orders already pushed to online warehouse
     */
    public static function getOrdersAlreadyPushedToWarehouse($warehouse_id,$ezcom_orders)
    {
        $pushed=[];
        if($ezcom_orders && is_array($ezcom_orders))
        {
            $order_ids=array_keys($ezcom_orders);
            $pushed_orders=EzcomToWarehouseSync::find()->select('ezcom_entity_id,third_party_entity_id,ezcom_status as pushing_time_status')->Where(['IN', 'ezcom_entity_id', $order_ids])->andWhere(['warehouse_id'=>$warehouse_id])->andWhere(['type'=>'order'])->asArray()->all();
            if($pushed_orders)
            {
                foreach($pushed_orders as $order)
                {
                    $pushed[$order['ezcom_entity_id']]=$order;
                }
            }
        }

        return $pushed;
    }

    public static function createMissingProduct($sku,$dataService)
    {
        $product=Products::find()->where(['sku'=>$sku])->asArray()->one();
        if($product)
        {
            $res=self::create_single_product($product,$dataService);
            if($res['status']=="success"){
                try{

                    $ezcom_to_warehouse_log=['warehouse_id'=>self::$current_channel->id,'type'=>'product','ezcom_entity_id'=>$product['sku'],'comment'=>'product synced to warehouse'];
                    LogUtil::ezcomToWarehouseSyncedLog($ezcom_to_warehouse_log); // save record of product created to online warehouse
                }catch (\Exception $e){}

                return $res['id'];  // product id of quickbook
            }

        }
        return NULL;

    }

    public static function prepareSalesLineItems($items,$dataService)
    {
        $list=[];
        foreach ($items as $index => $value)
        {
            $getSku = $dataService->Query("Select * from Item WHERE Sku = '" . $value['item_sku'] . "'");
            $error = $dataService->getLastError();
            if($error)
                continue;

            if(!$getSku) // if not present then create product in quickbook
            {
                $product_id=self::createMissingProduct($value['item_sku'],$dataService);
                if(empty($product_id))
                    continue;
            } else{
                $product_id=$getSku[0]->Id;
            }
                //continue;

            // Echo some formatted output
            $list[] = [
                "Id" =>0,
                //"Id" => $value['id'],
                //"LineNum" => 1,
                "Description" => $value['item_sku'],
                "Amount" => ($value['paid_price'] * $value['quantity']),
                "DetailType" => "SalesItemLineDetail",
                "SalesItemLineDetail" => [
                    "ItemRef" => [
                        "value" => $product_id,
                    ],
                    "UnitPrice" => $value['paid_price'],
                    "Qty" => $value['quantity'],
                    "TaxCodeRef" => [
                        "value" => "NON"
                    ]
                ]
            ];
        }

        return $list;
    }

    /**********test function********/
    public static function get_receipt($warehouse)
    {
        self::set_token($warehouse);
        $dataService = self::configuration();
        $sales_recipe = $dataService->FindbyId('SalesReceipt',1023);
        //$sales_recipe = $dataService->FindbyId('SalesReceipt',72);
        self::debug($sales_recipe);
    }
    public static function syncSalesToWarehouse($time_period, $warehouse)
    {
        $error_log=[];
        $success_log=[];
        self::set_token($warehouse);
        $dataService = self::configuration();
        $orders=OrderUtil::getWarehouseOrders($warehouse['id'],$time_period); // get warehouse orders
        $pushed_orders=self::getOrdersAlreadyPushedToWarehouse($warehouse['id'],$orders);  // already pushed orders to quick book
       //   self::debug($orders);
        foreach ($orders as $order_id => $value)
        {
            if(isset($pushed_orders[$order_id]) && $pushed_orders[$order_id]['pushing_time_status']==$value['detail']['order_status'])
            {
              continue; // same status as previuos one uploaded
            } elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['pending','deliver','delivered','complete','completed','shipped'])){
               continue;
            }elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){
               ///delete sale recpt
               // die('come');
                $del=self::deleteSaleReceipt($pushed_orders[$order_id]['third_party_entity_id'],$dataService);
               // self::debug($del);
                if($del['status']=="failure")
                    $error_log[$value['detail']['order_pk_id']]=$del['msg'];

                continue;
            } elseif(in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){
                continue; // if new order and not already uploaded and status is cancel then no need to upload

            }else{

                $customer=self::create_customer($value,$dataService); ////create customer
                $items=self::prepareSalesLineItems($value['order_items'],$dataService);  // prepare line items , order items
                //self::debug($items);
                if(isset($customer['customer_id']) && $customer['customer_id'] && $items)
                {

                    $salesReceiptObj = SalesReceipt::create([
                        "Line" => $items,
                        "CustomerRef" => [
                            "value" => $customer['customer_id'],
                        ],
                        'PrivateNote'=>'Order Number: '.$value['detail']['order_number']
                    ]);
                    $resultingSalesReceiptObj = $dataService->Add($salesReceiptObj);
                    $error = $dataService->getLastError();
                    if ($error) {
                        $error_log[$value['detail']['order_pk_id']]=$error->getResponseBody();
                    } else {
                        $ezcom_to_warehouse_log=['warehouse_id'=>$warehouse['id'],'type'=>'order','ezcom_entity_id'=>$value['detail']['order_pk_id'],'third_party_entity_id'=>$resultingSalesReceiptObj->Id,'ezcom_status'=>$value['detail']['order_status'],'comment'=>'Order synced to warehouse'];
                        LogUtil::ezcomToWarehouseSyncedLog($ezcom_to_warehouse_log); // save record of product created to online warehouse
                        $success_log[$value['detail']['order_pk_id']]=$resultingSalesReceiptObj->Id; // for general log
                        //self::debug($resultingSalesReceiptObj);
                    }
                } else{
                    $error_log[$value['detail']['order_pk_id']]= " Customer or Items Missing";
                }
            }
        }
        return ['error_log'=>$error_log,'success_log'=>$success_log];

    }


    public static  function deleteSaleReceipt($sale_recipe_id,$dataService ){
       // $dataService = self::configuration();
        $sales_recipe = $dataService->FindbyId('SalesReceipt', $sale_recipe_id);
        if(empty($sales_recipe))
            return ['status'=>'success','msg'=>'already deleted'];

        $resultingObj = $dataService->Delete($sales_recipe);
        $error = $dataService->getLastError();
        if ($error) {
            return ['status'=>'failure','msg'=>$error->getResponseBody()];
        } else {
            return ['status'=>'success','msg'=>'sales receipt deleted'];

        }
    }


    private static function debug($data)
    {
        echo "<pre>";
        print_r($data);
        die();
    }

    public static function syncProductsToWarehouse($warehouse,$product_items)
    {
        //self::debug($product_items);
        $error_log=[];
        $success_log=[];
        self::set_token($warehouse);
        $dataService = self::configuration();
     //   $product_items = WarehouseUtil::getWarehouseProductsNotSynced($warehouse['id']);
       // self::debug($product_items);
        foreach ($product_items as $key => $product)
        {
            $response=self::create_single_product($product,$dataService);
            if($response['status']=='success')
            {
                $ezcom_to_warehouse_response=['warehouse_id'=>$warehouse['id'],'status'=>'synced','sku'=>$product['sku'],'third_party_id'=>$response['id'],'comment'=>'product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_response);
            }
            elseif($response['status']=='failure')
            {
                $ezcom_to_warehouse_response=['warehouse_id'=>$warehouse['id'],'status'=>'failed','sku'=>$product['sku'],'response'=>$response['msg'],'comment'=>'failed product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_response);
                $error_log[$product['sku']]=$response['msg'];  // error msg
            }


        }
        return ['error_log'=>$error_log,'success_log'=>$success_log];
    }
    /****
     * single product creation if
    **/
    public static function create_single_product($product,$dataService)
    {
        $dateTime = new \DateTime('NOW');
        $productcheck = $dataService->Query("Select * from Item where Sku = '".$product['sku']."'");
       // self::debug($productcheck);
        if(!$productcheck)
        {
            self::product_asscoiated_accounts($dataService);  // to create product we need to associate ids of accounts
            if(!isset(self::$accounts['Cost of Goods Sold']) || !isset(self::$accounts['Sales of Product Income']) || !isset(self::$accounts['Inventory Asset']))
            {
                return ['status'=>'failure','msg'=>'Account ids association failed in product creation','id'=>NULL];
            }
            $name=substr($product['name'],0,84);
            $name=$name."_".$product['ean'];

            $Item = Item::create([
                "Name" => $name,
                "UnitPrice" => $product['rccp'],
                "Sku" => $product['sku'],
                "IncomeAccountRef" => [
                    "value" => self::$accounts['Sales of Product Income']->Id,  //32 for production //79 for sandbox
                    "name" => "Sales of Product Income"
                ],
                "ExpenseAccountRef" => [
                    "value" => self::$accounts['Cost of Goods Sold']->Id,  //33 for production
                   // "value" => "80",  //80 for sandbox
                    "name" => "Cost of Goods Sold"
                ],
                "AssetAccountRef" => [
                    "value" => self::$accounts['Inventory Asset']->Id,  //34 for production
                   // "value" => "81",  //81 for sandbox
                    "name" => "Inventory Asset"
                ],
                "Type" => "Inventory",
                "TrackQtyOnHand" => true,
                "QtyOnHand" => 0,
                "InvStartDate" => $dateTime
            ]);

            $resultingObj = $dataService->Add($Item);
            $error = $dataService->getLastError();
            if ($error) {
                return ['status'=>'failure','msg'=>$error->getResponseBody(),'id'=>NULL];
            } else {
                return ['status'=>'success','msg'=>'','id'=>$resultingObj->Id];
            }
        }
    }


    public static function product_asscoiated_accounts($dataService)
    {
        if(self::$accounts)
            return self::$accounts;

        /*self::set_token($warehouse);
        $dataService = self::configuration();*/
        $response=self::GetAccountsDetail($dataService);
        $accounts=[];
        if($response['status']=='success')
        {
            foreach ($response['data'] as $account)
            {
                $accounts[$account->Name]=$account;
            }
        }
        if($accounts)
        {
            self::$accounts=$accounts;

        }
        return $accounts;
       // self::debug(self::$accounts['Cost of Goods Sold']->Id);

    }

    public static function GetAccountsDetail($dataService)
    {
        $accounts = $dataService->Query("select * from Account where Name IN('Sales of Product Income','Cost of Goods Sold','Inventory Asset')");
        $error=$dataService->getLastError();
        if ($error)
            return ['status'=>'failure','msg'=>$error->getResponseBody()];
        else
            return ['status'=>'success','data'=>$accounts];


    }


    public static function add_log($log,$warehouse_id,$type)
    {
        $data=[];
        if(isset($log['error_log']) && $log['error_log'])
        {
            foreach($log['error_log'] as $entity=>$error)
            {
                $data=['type'=>$type,  //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                        'entity_type'=>'warehouse',
                        'entity_id'=>$warehouse_id,
                        'request'=>$entity, // sku or order_id
                        'response'=>$error,
                        'additional_info'=>NULL,
                        'log_type'=>'error',
                        'url'=>NULL];
                LogUtil::add_log($data);
            }
        }

        if(isset($log['success_log']) && $log['success_log'])
        {
            $total_items=count($log['success_log']);
            $data=['type'=>$type, //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                'entity_type'=>'warehouse',
                'entity_id'=>$warehouse_id,
                'request'=>$total_items ." requested",
                'response'=>$total_items ." added",
                'additional_info'=>'count of sum of entities added in log',
                'log_type'=>'info',
                'url'=>NULL];
            LogUtil::add_log($data);
        }
        return;

    }



    /**********
    * make log of that skus which are synced
     ***/
   /* public static function skus_synced_log($db_log=null,$warehouse_id)
    {
        if($db_log)
        {
            foreach ($db_log as $sku=>$index)
            {
                $log=new EzcomToWarehouseSync();
                $log->warehouse_id=$warehouse_id;
                $log->type='product';
                $log->ezcom_entity_id=$sku;
                $log->comment='product created to warehouse';
                $log->created_at=date('Y-m-d H:i:s');
                $log->save();
            }
        }
        return;
    }*/





}
