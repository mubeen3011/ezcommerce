<?php
namespace backend\util;

use common\models\EzcomToWarehouseSync;
use common\models\Products;
use common\models\Warehouses;

use Yii;

class SageUtil
{
    private static $current_channel = null;
    private static $hubspotRef = 69;
    private static $url_sage = "https://api.accounting.sage.com/v3.1/";

    public static function set_api($params, $url, $headers, $token, $req = "POST")
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $req);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($token != true) {
            return ["status" =>$httpcode, "response" => json_decode($response)];
        }else{
            return json_decode($response);
        }

    }

    private static function makeGetApiCall($requestUrl,$headers)
    {
        $ch = curl_init($requestUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($httpcode == "200" || $httpcode == "201") {
            return ["status" => "success", "msg" => $httpcode, "response" => json_decode($result)];
        }else {
            return ["status" => "fail", "msg" => $httpcode, "response" => ""];
        }
        }

    public static function set_token($params)
    {
        self::$current_channel = json_decode($params['configuration']);
        $fields = 'client_id='.self::$current_channel->client_id.'&client_secret='.self::$current_channel->client_secret.'&grant_type=refresh_token'.'&refresh_token='.self::$current_channel->refresh_token;
        $url = "https://oauth.accounting.sage.com/token";

        $headers = array('Content-Type: application/x-www-form-urlencoded',
            "Authorization: Bearer" . self::$current_channel->refresh_token
        );
        $access_token = self::set_api($fields, $url, $headers, true);
        //echo "<pre>";print_r($access_token);exit;
        $arr_conf = array();
            $arr_conf['client_id'] = self::$current_channel->client_id;
            $arr_conf['client_secret'] = self::$current_channel->client_secret;
            $arr_conf['access_token'] = $access_token->access_token;
            $arr_conf['refresh_token'] = ($access_token->refresh_token) ? $access_token->refresh_token : '';
            $arr_conf['time'] = '';
            $updateWarehouse = Warehouses::findOne($params['id']);
            $updateWarehouse->configuration=json_encode($arr_conf);
            $updateWarehouse->update();
            return self::$current_channel=(object)$arr_conf;
    }

    public static function getTotalStock(){

        $url = self::$url_sage."stock_items";
        $headers = self::getHeader();
        $stock_response = self::makeGetApiCall($url, $headers);

        $get_total_stock = (array)$stock_response['response'];
           $total = (array)$get_total_stock['$total'];
            return $total[0];
    }

    public static function getStock($params)
    {
        self::set_token($params);

        $total_stock =  self::getTotalStock();
        $total_page = ceil($total_stock / 20);
        $url = self::$url_sage."stock_item";
        $get_stock = [];
        for ($startPage=1; $startPage<=$total_page; $startPage++) {
            $url_list = $url."?page=".$startPage;
            $headers = self::getHeader();
            $stock_response = self::makeGetApiCall($url_list, $headers);
            $get_stock[] = (array)$stock_response;
        }

        $stock_data = [];
        $data = [];
        foreach ($get_stock as $key=>$val){

            //echo $val['status'];exit;
            if (isset($val['status']) && $val['status'] == "success") {

                $res = (array)$val['response'];
                $stock_count = count($res['$items']);
                for ($i = 0; $i < $stock_count; $i++) {
                    $get_stock_path = (array)$res['$items'][$i];
                    $paths = substr($get_stock_path['$path'], 12);
                    $headers = self::getHeader();
                    $response = self::makeGetApiCall($url.$paths, $headers);
                   // echo "<pre>";print_r($response);exit;
                    $stock_data['status'] = $response['status'];
                    $data[] = $response['response'];
                    $stock_data['stock_data'] = $data;
                }

            } else {
                $stock_data['status'] = 'failure';
                $stock_data['error'] = $stock_response['status'];
            }
        }
            return $stock_data;
    }


    public static function SkuStockSetFormat( $list ){

        $reFormat = [];
        foreach ($list['stock_data'] as $key=>$val){
            $reFormat[]=['sku'=> $val->item_code,'available'=>($val->quantity_in_stock) ? $val->quantity_in_stock : 0];
        }
        return $reFormat;
    }


    public static function syncProductsToWarehouse($warehouse_id,$SystemProducts)
    {
        self::set_token($warehouse_id);
        //$SystemProducts = WarehouseUtil::getWarehouseProductsNotSynced($warehouse_id['id']);

        $error_log=[];
        $success_log=[];
        foreach ($SystemProducts as $syskey=>$prdval ){
                $addProduct = SageUtil::createProduct($prdval); // Create product on Sage

            if(isset($addProduct['status']) && $addProduct['status'] == "201"){
                $ezcom_to_warehouse_log=['warehouse_id'=>$warehouse_id['id'],'status'=>'synced','sku'=>$prdval['sku'],'third_party_id'=>$addProduct['response']->id,'comment'=>'product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
            }
            else {
                $ezcom_to_warehouse_log=['warehouse_id'=>$warehouse_id['id'],'status'=>'failed','sku'=>$prdval['sku'],'response'=>$addProduct['response'][0],'comment'=>'failed product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
                $error_log[$prdval['sku']] = $addProduct['response'][0];  // error msg
            }
        }
        //echo "<pre>";print_r($error_log);exit;
        return ['error_log'=>$error_log,'success_log'=>$success_log];
    }

    public static function createProduct($params){

        $headers = self::getHeader();
        $url = self::$url_sage."products";

        $arr = array();
        $arr['description'] = $params['name'];

        $arr['sales_ledger_account_id'] = self::getSalesLedgerAccountId();//ae60b1716d2311eb867f0ee73a5c6c6b
        $arr['purchase_ledger_account_id'] = self::getPurchaseLedgerAccountId();//ae606ce46d2311eb867f0ee73a5c6c6b
        $arr['item_code'] = $params['sku'];
        $arr['notes'] = '';
        $arr['sales_tax_rate_id'] = '';
        $arr['usual_supplier_id'] = '';
        $arr['purchase_tax_rate_id'] = '';
        $arr['cost_price'] = $params['rccp'];
        $arr['source_guid'] = '';
        $arr['purchase_description'] ='';
        $arr['active'] = true;
        $arr['catalog_item_type_id'] ="PRODUCT";
        $data[] = ["price_name" => "Selling Price", "price" => $params['rccp'], "price_includes_tax" => false, "product_sales_price_type_id" => "a7ef38123f4e41adb9ca5fbf60ed13b5"];
        $arr['sales_prices'] = $data;
        $datas['product'] = $arr;
        $response = self::set_api(json_encode($datas), $url, $headers, false);
        return $response;
    }

    public static function add_log($log,$warehouse_id,$type)
    {

        $data=[];
        if(isset($log['error_log']) && $log['error_log'])
        {
            foreach($log['error_log'] as $entity=>$error)
            {
                 $message = (array)$error;
                $data=['type'=>$type,  //'ezcom-to-warehouse-product-sync','ezcom-to-warehouse-order-sync'
                    'entity_type'=>'warehouse',
                    'entity_id'=>$warehouse_id,
                    'request'=>$entity, // sku or order_id
                    'response'=>$message['$message'],
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

    public function syncSalesToWarehouse($time_period, $warehouse)
    {
        $error_log=[];
        $success_log=[];
        $url = self::$url_sage."sales_invoices";
        self::set_token($warehouse);
        $orders=OrderUtil::getWarehouseOrders($warehouse['id'],$time_period); // get warehouse orders
        $pushed_orders=self::getOrdersAlreadyPushedToWarehouse($warehouse['id'],$orders);  // already pushed orders to quick book
       // echo "<pre>";print_r($orders);exit;
        foreach ($orders as $order_id => $value)
        {

            if(isset($pushed_orders[$order_id]) && $pushed_orders[$order_id]['pushing_time_status']==$value['detail']['order_status'])
            {
                continue; // same status as previuos one uploaded
            }elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['pending','deliver','delivered','complete','completed','shipped'])){

                $res = self::updateSaleReceipt($pushed_orders[$order_id]['third_party_entity_id'], $value);

                if($res['status']=="failure")
                    $error_log[$value['detail']['order_pk_id']]=$res['status'];
                continue;
            }elseif(isset($pushed_orders[$order_id]) && in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){

                $del=self::deleteSaleReceipt($pushed_orders[$order_id]['third_party_entity_id']);
                if($del['status']=="failure")
                    $error_log[$value['detail']['order_pk_id']]=$del['status'];

                continue;
            } elseif(in_array($value['detail']['order_status'],['cancelled','cancel','canceled'])){
                continue; // if new order and not already uploaded and status is cancel then no need to upload

            }
            else {
                $paid_amount = 0;
                $invoice_line = array();
                foreach ($value['order_items'] as $order_item_id => $order_item) {
                    $order_items = array();
                    $order_items['description'] = $order_item['item_sku'];
                    $order_items['ledger_account_id'] = self::getSalesLedgerAccountId();
                    $order_items['unit_price'] = $order_item['paid_price'];
                    $product_id = self::getProduct($order_item['item_sku']);
                    $order_items['product_id'] = $product_id;
                    $order_items['quantity'] = $order_item['quantity'];
                    $order_items['tax_rate_id'] = self::getTaxRateId(); //"c47c749ec4b04b88b11644a0d80d60ee"
                    $order_items['total_amount'] = $order_item['sub_total'];
                    $paid_amount += $order_item['paid_price'];
                    array_push($invoice_line, $order_items);
                }

                $date = date('Y-m-d');//date('Y-m-d', strtotime($value['detail']['order_created_at']));
                $contact_id = self::createContact($value['customer']['billing_email'], $value['customer']);
                $order_data = array(
                    'contact_id' => $contact_id,
                    'date' => $date,
                    'reference' => $value['detail']['order_number'],
                    'invoice_lines' => $invoice_line,
                );

                $sales_invoice = ['sales_invoice' => $order_data];
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    "Authorization: Bearer " . self::$current_channel->access_token
                );

                $response = self::set_api(json_encode($sales_invoice), $url, $headers, false);
                if(isset($response['status']) && $response['status'] == "201"){
                    $status = self::mapOrderStatus($value['detail']['order_status']);
                    if($status != "UNPAID") {
                        $payment_recipe_id = self::createPaymentContact($contact_id, $paid_amount, $response['response']->id);
                    }
                    $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse['id'], 'type' => 'order', 'ezcom_entity_id' => $value['detail']['order_pk_id'], 'third_party_entity_id' => $response['response']->id, 'ezcom_status' => $value['detail']['order_status'], 'comment' => 'Order synced to warehouse'];
                    LogUtil::ezcomToWarehouseSyncedLog($ezcom_to_warehouse_log); // save record of product created to online warehouse
                    $success_log[$value['detail']['order_pk_id']]=$response['response']->id; // for general log
                } else {
                    return ['status'=>'failure','msg'=>$response];
                }

            }
        }
        return ['error_log'=>$error_log,'success_log'=>$success_log];
    }

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

    public static  function deleteSaleReceipt($sale_recipe_id){

          $url = self::$url_sage."sales_invoices/".$sale_recipe_id;
         //   exit;
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer ".self::$current_channel->access_token
        );

        $data['void_reason']= "please delete this invoice";
        $response = self::set_api(json_encode($data), $url, $headers,false, "DELETE");

        if(isset($response['status']) && $response['status'] == "200"){
            return ['status'=>'success','msg'=>'sales receipt deleted'];
        } else {
            return ['status'=>'failure','msg'=>$response];
        }


    }

    public static  function updateSaleReceipt($sale_recipe_id, $order){

       $paid_amount = 0;
        foreach ($order['order_items'] as $order_item_id => $order_item) {
            $paid_amount += $order_item['paid_price'];
        }
        $contact_id = self::createContact($order['customer']['billing_email'], $order['customer']);
        $response = self::createPaymentContact($contact_id,$paid_amount,$sale_recipe_id);
        return $response;
    }



    public static function mapOrderStatus($order_status)
    {
        $completed_orders = ['shipped','deliver', 'delivered', 'completed','complete'];
        $canceled_orders = ['cancelled', 'canceled', 'cancel'];
        $pending_orders = ['pending'];

        if(in_array(strtolower($order_status),$completed_orders))
            return "PAID";
        if(in_array(strtolower($order_status),$canceled_orders))
            return "VOID";
        if(in_array(strtolower($order_status),$pending_orders))
            return "UNPAID";

        return $order_status;
    }



    public static function getProduct($item_name = null)
    {
        $url = self::$url_sage."products?search=".$item_name;
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer ".self::$current_channel->access_token
        );

        $response = self::makeGetApiCall($url, $headers);

        if(isset($response['status']) && $response['status'] == "200"){
            $array = (object)$response['response'];
            $get_arr= (array)$array;

            $item_ids = (array)$get_arr['$items'];
            if($item_ids){

                foreach ($item_ids as $item_id){
                    $item_url = self::$url_sage."products/".$item_id->id;
                    $headerss = array(//'Content-Type: application/x-www-form-urlencoded',
                        'Accept: application/json',
                        'Content-Type: application/json',
                        "Authorization: Bearer ".self::$current_channel->access_token
                    );

                    $responsed = self::makeGetApiCall($item_url, $headerss);
                    if(isset($responsed['status']) && $responsed['status'] == "200"){
                        if($responsed['response']->item_code == $item_name){
                            return $responsed['response']->id;
                        }
                    }
                }

            }
        }
    }

    public static function createContact($email, $customer_detail){

        $url = self::$url_sage."contacts?email=".$email;
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer ".self::$current_channel->access_token
        );

        $response = self::makeGetApiCall($url, $headers);
        if(isset($response['status']) && $response['status'] == "200"){
            $array = (object)$response['response'];
            $get_arr= (array)$array;

            if((array)$get_arr['$items']) {
                $item_id = (array)$get_arr['$items'][0];
            }else{
                $item_id = "";
            }
            if(!empty($item_id)) {
                return $item_id['id'];
            }else{

                $headers = self::getHeader();
                $url = self::$url_sage."contacts";
                $arr = array();
                $arr['name'] = $customer_detail['shipping_fname'];
                $arr['contact_type_ids'] =  ["CUSTOMER"];
                $data = ["address_line_1" => $customer_detail['shipping_address'], "city" => $customer_detail['shipping_city'], "postal_code" => $customer_detail['shipping_post_code'], "country_id" => $customer_detail['shipping_country'], "name" => $customer_detail['shipping_fname']];
                $data1 = ["email" => $customer_detail['billing_email']] ;
                $arr['delivery_address'] = $data;
                $arr['main_contact_person'] = $data1;
                $datas['contact'] = $arr;
                $response = self::set_api(json_encode($datas), $url, $headers, false);
                if(isset($response['status']) && $response['status'] == "201") {
                    return $response['response']->id;
                }
            }
        }

    }

    public static function createPaymentContact($contact_id, $amount, $invoice_id){

                $headers = self::getHeader();
                $url = self::$url_sage."contact_payments";
                $arr = array();
                $arr['transaction_type_id'] = "CUSTOMER_RECEIPT";
                $arr['payment_method_id'] = "CASH";
                $arr['bank_account_id'] = self::getBankAccountId();//"85aa5bfbccc3473bba50a0e38760a24a";
                $arr['contact_id'] = $contact_id;
                $arr['date'] =  date('Y-m-d');
                $arr['total_amount'] =  $amount;

                $data[] = ["artefact_id" => $invoice_id, "amount" => $amount] ;
                $arr['allocated_artefacts'] = $data;
                $datas['contact_payment'] = $arr;
                //echo json_encode($datas);exit;
                $response = self::set_api(json_encode($datas), $url, $headers, false);
                //  return $response['response']->id;
                    if(isset($response['status']) && $response['status'] == "201"){
                        return ['status'=>'success','msg'=>$response['response']];
                    } else {
                        return ['status'=>'failure','msg'=>"status can't change"];
                    }

    }

    public static function getHeader(){

        return $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer ".self::$current_channel->access_token
        );

    }

    public static function getSalesLedgerAccountId()
    {
        $url = self::$url_sage."ledger_accounts?visible_in=sales";
        $headers = self::getHeader();
        $access_token = self::makeGetApiCall($url, $headers);

        $array = $access_token;

        $res = (object)$array['response'];
        $items= (array)$res;
        $item = (array)$items['$items'];
        return $item[2]->id;

    }

    public static function getPurchaseLedgerAccountId()
    {

        $url = self::$url_sage."ledger_accounts?visible_in=expenses";
        $headers = self::getHeader();
        $access_token = self::makeGetApiCall($url, $headers);

        $array = $access_token;

        $res = (object)$array['response'];
        $items= (array)$res;
        $item = (array)$items['$items'];
        return $item[0]->id;

    }

    public static function getBankAccountId()
    {
        $url = self::$url_sage."bank_accounts";
        $headers = self::getHeader();
        $access_token = self::makeGetApiCall($url, $headers);

        $array = $access_token;

        $res = (object)$array['response'];
        $items= (array)$res;
        $item = (array)$items['$items'];
        return $item[1]->id;

    }


    public static function getTaxRateId()
    {
        $url = self::$url_sage."tax_rates";
        $headers = self::getHeader();
        $access_token = self::makeGetApiCall($url, $headers);
        $array = $access_token;

        $res = (object)$array['response'];
        $items= (array)$res;
        $item = (array)$items['$items'];
        return $item[0]->id;

    }

//    public static function createProductAndItemsInWarehouses($warehouse_id, $value)
//    {
//        $conf = self::set_token($value);
//
//            //  $url = "https://api.accounting.sage.com/v3.1/sales_invoices/artefact_statuses/UNPAID";
//        $url = "https://api.accounting.sage.com/v3.1/bank_accounts";
//      //  https://api.accounting.sage.com/v3.1/payment_methods
//        //https://api.accounting.sage.com/v3.1/contacts/{key}
//
//        $headers = array(//'Content-Type: application/x-www-form-urlencoded',
//            'Accept: application/json',
//            'Content-Type: application/json',
//            "Authorization: Bearer ".self::$current_channel->access_token
//        );
//        $access_token = self::makeGetApiCall($url, $headers);
//            echo "<pre>";print_r($access_token);exit;
//        // $access_token = self::set_api(json_encode($data), $url, $headers);
//       // $access_token = self::makeGetApiCall($url, $headers);
// //       echo json_encode($access_token);exit;
//
//        $array = $access_token;
//      //  echo "<pre>";print_r($array);exit;
//        $path = (object)$array['response'];
//        $path2= (array)$path;
//        $path3 = (array)$path2['$items'][0];
//  //      echo "<pre>"; print_r($path3['$path']);exit;
//         echo $url.$paths = substr($path3['$path'],17);
//       // exit;
//        $headerss = array(//'Content-Type: application/x-www-form-urlencoded',
//            'Accept: application/json',
//            'Content-Type: application/json',
//            "Authorization: Bearer ".self::$current_channel->access_token
//        );
//
//        $access_tokens = self::makeGetApiCall($url.$paths, $headerss);
//        //$product_detail = json_decode($access_tokens, true);exit;
//       // echo "<pre>";print_r($access_tokens);exit;
//        echo "<pre>";print_r($access_tokens);exit;
//
//
//
//    }

}
