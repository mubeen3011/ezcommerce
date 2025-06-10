<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/2/2020
 * Time: 3:53 PM
 */
namespace backend\util;
use Yii;
class DolibarrUtil{
    private static $warehouse=null;
    private static $warehouse_config=null;

    private static function set_config($warehouse)
    {
        self::$warehouse=$warehouse;
        self::$warehouse_config=json_decode($warehouse['configuration'],1);
        return;
    }
    public static function makePostApiCall( $url,$data){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "dolapikey: ".self::$warehouse_config['Dolapikey'],
                "postman-token: ce0f68ad-4460-7d65-b08e-31051e5e8e1f"
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
    public static function makeDeleteApiCall( $url ){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');


        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Dolapikey: '.self::$warehouse_config['Dolapikey'];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            return 'Error:' . curl_error($ch);
        }else{
            return $result;
        }

    }
    public static function makePutApiCall( $url,$data){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "dolapikey: ".self::$warehouse_config['Dolapikey'],
                "postman-token: ce0f68ad-4460-7d65-b08e-31051e5e8e1f"
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

    public static function makeGetApiCall($url){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"auth\"\r\n\r\nd5cc8d5f004d16990007707c00000001201c03764e0b4ea2c1fd562b276fd7b72a51b3\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"fields[DESCRIPTION]\"\r\n\r\nSolid Top Cap & Spark Arrestor Prevents Direct Rainfall & Animal Access To Flue\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"id\"\r\n\r\n1343\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--",
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "cache-control: no-cache",
                "content-type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW",
                "dolapikey: ".self::$warehouse_config['Dolapikey'],
                "postman-token: ded9e968-9ad4-2b88-a736-b384d83aed2b"
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
    public static function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
    public static function UpdateProductOnWarehouse($warehouse,$systemProducts){
      //  $systemProducts = ProductsUtil::GetAllProducts($warehouse); // get ezcomm products list
        $dolibarr_products = self::GetAllProducts($warehouse); // get dolibarr products list
        $redefine_system_products = ProductsUtil::redefineProductsForDolibarrWarehouse($systemProducts); // redefine system skus bec dolibarr change $*\][';/,... signs to _
        $get_pro_list_to_create_on_dolibarr = self::GetProductsToUpdateOnDolibarr($redefine_system_products,$dolibarr_products,$warehouse); // get products needs to create on dolibarr
        self::UpdateProductDolibarr($get_pro_list_to_create_on_dolibarr,$warehouse);
    }
    public static function validateSku($sku){
        $special_characters=['$','*','\'',']','[',"'",';','/',',','"',':','?','>','<','|',' '];
        $sku = str_replace($special_characters,'_',$sku);
        return $sku;
    }
    public static function CreateProductsOnWarehouse($warehouse,$systemProducts){
        self::set_config($warehouse);
        //$systemProducts = ProductsUtil::GetAllProducts($warehouse); // get ezcomm products list
        $dolibarr_products = self::GetAllProducts($warehouse); // get dolibarr products list
        $redefine_system_products = ProductsUtil::redefineProductsForDolibarrWarehouse($systemProducts); // redefine system skus bec dolibarr change $*\][';/,... signs to _
        $get_pro_list_to_create_on_dolibarr = self::GetProductsToCreateOnDolibarr($redefine_system_products,$dolibarr_products,$warehouse); // get products needs to create on dolibarr

        return  self::CreateProductDolibarr($get_pro_list_to_create_on_dolibarr,$warehouse); // create products on dolibarr
    }
    public static function UpdateProductDolibarr( $products, $warehouse ){
        $config_ware=json_decode($warehouse['configuration'],true);
        if (!isset($config_ware['consider_product_unique_column']))
        {echo 'consider_product_unique_column is not set in configuration column of this warehouse';die;}
        foreach ( $products as $value ){
            $json = '{"cost_price":"'.$value['price'].'","price":"'.$value['price'].'"}';
            $response=self::makePutApiCall(rtrim(self::$warehouse_config['api_url'],'/').'/index.php/products/'.$value['dolibarr_product_id'],$json);

        }
    }
    public static function CreateProductDolibarr( $products, $warehouse ){

        $error_log = [];
        $success_log = [];
        $config_ware=json_decode($warehouse['configuration'],true);
        if (!isset($config_ware['consider_product_unique_column']))
            die('consider_product_unique_column is not set in configuration column of this warehouse');


        foreach ( $products as $value ){
            $json = '{"ref":"'.$value['sku'].'_test5","label":"'.$value['name'].'","status":1,"status_buy":1,"price":"'.$value['cost'].'","cost_price":"'.$value['cost'].'","accountancy_code_buy":"'.$value[$config_ware['consider_product_unique_column']].'","customcode":"'.$value['brand'].'","finished":"1"}';
            $response=self::makePostApiCall(rtrim(self::$warehouse_config['api_url'],'/').'/index.php/products',$json);
            if($response)
                $response=json_decode($response);

            if(isset($response->error))
            {
                $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse['id'], 'status' => 'failed', 'sku' => $value['sku'], 'response' => $response->error->message,'comment' => 'failed product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
                $error_log[$value['sku']] = $response->error->message;  // error msg
            }elseif($response)
            {
                $ezcom_to_warehouse_log = ['warehouse_id' => $warehouse['id'], 'status' => 'synced', 'sku' => $value['sku'], 'third_party_id' => $response,'comment' => 'product synced to warehouse'];
                WarehouseUtil::ezcomToWarehouseProductSyncResponse($ezcom_to_warehouse_log);
            }
            return ['error_log' => $error_log, 'success_log' => $success_log];
        }
    }
    public static function GetProductsToUpdateOnDolibarr($sys_products, $dolibarr_products, $warehouse){
        $config_ware=json_decode($warehouse['configuration'],true);
        if (!isset($config_ware['consider_product_unique_column']))
        {echo 'consider_product_unique_column is not set in configuration column of this warehouse';die;}

        $update_products_dolibarr=[];

        foreach ( $dolibarr_products as $key=>$value ){
            $sku = $value['ref'];
            if (isset($sys_products[$sku])){
                if ( $value['price']!=$sys_products[$sku]['cost'] || $value['cost_price']!=$sys_products[$sku]['cost'] ){

                    $sku_detail = [];
                    $sku_detail['sku']=$value['ref'];
                    $sku_detail['dolibarr_product_id']=$value['id'];
                    $sku_detail['price']=$sys_products[$sku]['cost'];
                    $update_products_dolibarr[]=$sku_detail;
                }
            }
        }

        return $update_products_dolibarr;
    }
    public static function GetProductsToCreateOnDolibarr($sys_products, $dolibarr_products, $warehouse){
        $config_ware=json_decode($warehouse['configuration'],true);
        if (!isset($config_ware['consider_product_unique_column']))
        {echo 'consider_product_unique_column is not set in configuration column of this warehouse';die;}
        $dolibarr_sku_list=[];
        foreach ( $dolibarr_products as $value ){

            $dolibarr_sku_list[]=$value['ref'];
        }

        $create_products_dolibarr=[];
        foreach ( $sys_products as $value ){
            if (!in_array($value[ 'sku' ],$dolibarr_sku_list)){
                $create_products_dolibarr[]=$value;
            }
        }

        return $create_products_dolibarr;
    }
    /*public static function StopIfApiHasError($dolibarr_products){

        if (!self::isJson($dolibarr_products)){
            echo 'Dolibarr API error: '.$dolibarr_products;
            die;
        }else if ( self::isJson($dolibarr_products) ){
            $dolibarr_products = json_decode($dolibarr_products,true);
            if ( isset($dolibarr_products['error']) && $dolibarr_products['error']['code']!=404 ){
                echo '<pre>';print_r($dolibarr_products);die;
            }
            elseif ( isset($dolibarr_products['error']) && $dolibarr_products['error']['code']==404 ){
                return [];
            }
            else{
                return $dolibarr_products;
            }
        }
        else{
            return json_decode($dolibarr_products,true);
        }
    }*/
    public static function GetAllProducts($warehouse,$filter=''){
        if ($warehouse)
            self::set_config($warehouse);

        $i=0;
        $product_list=[];
        while ( 1 ){
            if (self::$warehouse_config['api_url']==''){
                echo 'Warehouse configuration column is required to run this api. Dolibarr';
                die;
            }
            $products=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/products?limit=1000&page='.$i.'&sqlfilters='.$filter);
            $products=json_decode($products,true);

            if ( isset($products['error']) ){
                return ($product_list);
                break;
            }
            foreach ( $products as $value ){
                $product_list[]=$value;
            }
            $i++;
        }

    }
    public static function GetAllSkuStocks($warehouse){
        if ($warehouse)
            self::set_config($warehouse);

        if (self::$warehouse_config['warehouse_id']=='all'){
            $product_detail=self::GetAllProducts($warehouse);
        }else{
            $product_detail=self::makeGetApiCall(self::$warehouse_config['api_url'].'stock_list.php?warehouse_id='.self::$warehouse_config['warehouse_id']);
            $product_detail=json_decode($product_detail,true);
        }

        return $product_detail;
    }
    /*public static function GetAllSkuStocks($warehouse){
        if ($warehouse)
            self::set_config($warehouse);

        $get_all_products = self::GetAllProducts($warehouse);
        return $get_all_products;
    }*/
    public static function GetProductDetail($warehouse,$id){
        if ($warehouse)
            self::set_config($warehouse);

        $product_detail=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/products/'.$id);
        $product_detail=json_decode($product_detail,true);
        return $product_detail;
    }
    public static function DolibarrStockSetFormat($sku_list){
        $stock_list=[];
        foreach ( $sku_list as $value ){
            $stock=[];
            $stock['available']=($value['stock_reel']=='') ? 0 : $value['stock_reel'];
            $stock['sku']=$value['ref'];
            $stock_list[]=$stock;
        }
        return $stock_list;
    }
    public static function GetAllThirdParty($warehouse,$filter=''){
        if ($warehouse)
            self::set_config($warehouse);

        $i=0;
        $third_party_list=[];
        while ( 1 ){
            if (self::$warehouse_config['api_url']==''){
                echo 'Warehouse configuration column is required to run this api. Dolibarr';
                die;
            }
            $third_parties=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/thirdparties?limit=1000&page='.$i.$filter);
            $third_parties=json_decode($third_parties,true);

            if ( isset($third_parties['error']) ){
                return ($third_party_list);
                break;
            }
            foreach ( $third_parties as $value ){
                $third_party_list[]=$value;
            }
            $i++;
        }
    }
    public static function CreateThirdParty($warehouse,$json){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/thirdparties',$json);
    }
    public static function GetAllOrders($warehouse,$filters=''){
        if ($warehouse)
            self::set_config($warehouse);

        $i=0;
        $product_list=[];
        while ( 1 ){
            if (self::$warehouse_config['api_url']==''){
                echo 'Warehouse configuration column is required to run this api. Dolibarr';
                die;
            }
            $products=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/orders?limit=1000&page='.$i.'&sqlfilters='.$filters);
            $products=json_decode($products,true);
            if ( isset($products['error']) ){
                return ($product_list);
                break;
            }
            //echo '<pre>';var_dump($products);die;
            foreach ( $products as $value ){
                $product_list[]=$value;
            }
            $i++;
        }
    }
    public static function GetOrdersToCreateOnDolibarr($ezcom_orders,$warehouse){
        $filter=[];

        foreach ( $ezcom_orders as $key=>$value ){
            $filter[]= "'".urlencode($value['order_details']['order_number'])."'";
        }
        $filter=array_chunk($filter,10);
        $already_exsit_orders=[];
        foreach ( $filter as $key=>$value ){
            $filter='t.ref_client%20IN%20('.implode('%2C',$value).')';
            $dolibarr_orders=self::GetAllOrders($warehouse,$filter);
            if ( $dolibarr_orders ){
                foreach ( $dolibarr_orders as $dol_val ){
                    $already_exsit_orders[]=$dol_val['ref_client'];
                }
            }
        }
        $already_exist=[];
        foreach ( $ezcom_orders as $key=>$value ){
            if (in_array($value['order_details']['order_number'],$already_exsit_orders)){
                $already_exist[]=$ezcom_orders[$key];
                unset($ezcom_orders[$key]);
            }
        }
        return ['already_exist'=>$already_exist,'not_exist'=>$ezcom_orders];
    }
    public static function GetOrderDetail($warehouse,$id){
        if ($warehouse)
            self::set_config($warehouse);

        $order=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/orders/'.$id);
        $order_detail=json_decode($order,true);
        return $order_detail;
    }
    public static function CreateOrder($warehouse, $json){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/orders',$json);
    }
    public static function CreateOrGetThirdPartyCustomer($warehouse,$value){
        $filter="&sqlfilters=t.email%3D'".$value['customer_details']['email']."'%20OR%20t.phone%3D'".urlencode($value['customer_details']['phone'])."'";
        $search_customer_by_email_or_phone=self::GetAllThirdParty($warehouse,$filter); // search third party customer in dolibarr warehouse
        if ($search_customer_by_email_or_phone && !isset($search_customer_by_email_or_phone['error']))
            return $search_customer_by_email_or_phone[0]['id'];

        if ( !$search_customer_by_email_or_phone ){
            $json = '{"email":"'.$value['customer_details']['email'].'","phone":"'.$value['customer_details']['phone'].'","name":"'.$value['customer_details']['customer_fname'].' '.$value['customer_details']['customer_lname'].'","address":"'.$value['customer_details']['shipping_address'].'","town":"'.$value['customer_details']['shipping_city'].'","client":"1","zipcode":"'.$value['customer_details']['shipping_post_code'].'"}';
            $create_third_party=self::CreateThirdParty($warehouse,$json);
            if (gettype($create_third_party)=='string'){
                return $create_third_party;
            }
        }
    }
    public static function addLineItemsInSalesOrderDolibarr($warehouse,$json,$sale_order_id){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/orders/'.$sale_order_id.'/lines',$json);
    }
    public static function ValidateOrder($warehouse,$sale_order_id){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/orders/'.$sale_order_id.'/validate','');
    }
    public static function CreateShipment($warehouse,$json){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/shipments',$json);
    }
    public static function CreateShipmentLineitems($warehouse,$json){
        if ($warehouse)
            self::set_config($warehouse);
        return self::makePostApiCall(self::$warehouse_config['api_url'].'add-shipment-line-items.php',$json);
    }
    public static function ValidateShipment($warehouse,$shipment_id){
        if ($warehouse)
            self::set_config($warehouse);

        $json='{"notrigger": 0}';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/shipments/'.$shipment_id.'/validate',$json);
    }
    public static function GetAllWarehouses($warehouse,$filter=''){
        if ($warehouse)
            self::set_config($warehouse);

        $i=0;
        $warehouse_list=[];
        while ( 1 ){
            if (self::$warehouse_config['api_url']==''){
                echo 'Warehouse configuration column is required to run this api. Dolibarr';
                die;
            }
            $warehouses=self::makeGetApiCall(self::$warehouse_config['api_url'].'index.php/warehouses?limit=1000&page='.$i.'&sqlfilters='.$filter);
            $warehouses=json_decode($warehouses,true);

            if ( isset($warehouses['error']) ){
                return ($warehouse_list);
                break;
            }
            foreach ( $warehouses as $value ){
                $warehouse_list[]=$value;
            }
            $i++;
        }

    }
    public static function ChooseRandomWarehouse($warehouse){

        $warehouses=DolibarrUtil::GetAllWarehouses($warehouse,'');
        $list=[];
        foreach ($warehouses as $val){
            $list[]=$val['id'];
        }
        //echo '<pre>';print_r($list);die;
        return $list[ rand(0,count($list)) ];
    }
    public static function addLineitemsSalesOrder($warehouse,$order_items,$create_sales_order){
        foreach ( $order_items['order_items'] as $item_value ){ // add line items

            $validate_sku=self::validateSku($item_value['item_sku']);
            $dolibarr_product_detail = self::GetAllProducts($warehouse,urlencode('t.ref=')."'".urlencode($validate_sku)."'");
          //  echo self::$warehouse_config['api_url'].'index.php/products?limit=10&page=1&sqlfilters='.urlencode('t.ref=')."'".urlencode($validate_sku)."'";
          // echo "<br/>";
            if ($dolibarr_product_detail){
                $json = '{"fk_product":"'.$dolibarr_product_detail[0]['id'].'","subprice":"'.$item_value['paid_price'].'","qty":"'.$item_value['quantity'].'","product_ref":"'.$validate_sku.'","ref":"'.$validate_sku.'"}';
                self::addLineItemsInSalesOrderDolibarr($warehouse,$json,$create_sales_order);
            }
        }
    }
    public static function addShipmentLineitems($warehouse, $order_detail, $create_shipment){
        $items = [];
        if (isset(self::$warehouse_config['warehouse_id'])){
            if (self::$warehouse_config['warehouse_id']=='all'){
                $warehouse_id = 2;//self::ChooseRandomWarehouse($warehouse);
            }else{
                echo "yes";
                echo "<br/>";
                $warehouse_id = self::$warehouse_config['warehouse_id'];
            }
        }
        //echo $warehouse_id;die;
        foreach ($order_detail['lines'] as $value){
            $item_detail = [];
            $item_detail['shipment_id']=$create_shipment;
            $item_detail['item_line_id']=$value['id'];
            $item_detail['warehouse_id']=$warehouse_id;
            $item_detail['qty']=$value['qty'];
            $item_detail['rang']=0;
            $items[]=$item_detail;
        }
        self::CreateShipmentLineitems($warehouse,json_encode($items));
    }
    public static function CreateSaleOnWarehouse($warehouse,$sales){
        if ($warehouse)
            self::set_config($warehouse);

        foreach ( $sales as $key=>$value ){
            if(in_array($value['order_details']['order_status'],['canceled','cancelled','refunded','cancel','returned']))
                continue;

            $get_or_create_customer = self::CreateOrGetThirdPartyCustomer($warehouse,$value); // get customer id

            $sales_order_json = '{"ref_client":"'.$value['order_details']['order_number'].'","socid":"'.$get_or_create_customer.'","date":"'.strtotime($value['order_details']['order_created_at']).'","date_livraison":"'.(strtotime($value['order_details']['order_created_at'])+(7*86400)).'","shipping_method_id":"2","demand_reason_id":"1","note_public":"Payment Method:'.$value['order_details']['payment_method'].', Coupon Code: '.$value['order_details']['coupon_code'].', Channel Name: '.$value['order_details']['channel_name'].'"}';
            $create_sales_order = self::CreateOrder($warehouse,$sales_order_json); // create sale order on dolibarr

            self::addLineitemsSalesOrder($warehouse,$value,$create_sales_order); // add line items under sale order

            $validate_order = self::ValidateOrder($warehouse,$create_sales_order); // mark order as validate

            $order_detail = self::GetOrderDetail($warehouse,$create_sales_order); // get saleOrder detail dolibarr

            $json = '{"socid": "'.$get_or_create_customer.'","ref_customer": "'.$value['order_details']['order_number'].'","origin_id": "'.$create_sales_order.'","brouillon": 1,"entrepot_id": null,"origin_type":"SALE_ORDER","tracking_number": "","date_delivery": '.(strtotime($value['order_details']['order_created_at'])+(7*86400)).',"date": "","date_expedition": "","sizeW":"null","sizeH":"null","sizeS":"null","weight":"null","shipping_method_id":"2"}';
            $create_shipment = self::CreateShipment($warehouse,$json); // create shipment
           // $ez_items=self::arrange_items($value['order_items']);
            self::addShipmentLineitems($warehouse,$order_detail,$create_shipment);
            self::ValidateShipment($warehouse,$create_shipment);
        }

    }

    private static function arrange_items($items)
    {
        $list=[];
        foreach($items as $item){
            $sku=self::validateSku($item['item_sku']);
            $list[$sku]=$item;
        }
        return $list;

    }
    public static function DeleteShipment($warehouse){
        if ($warehouse)
            self::set_config($warehouse);


    }
    public static function DeleteShipmentsOfSaleOrder($warehouse,$saleOrderId){

        $order_detail = self::GetOrderDetail($warehouse,$saleOrderId);
        /*echo '<pre>';
        print_r($order_detail);
        die;*/
        $order_number = $order_detail['ref_client'];

        if ( isset($order_detail['linkedObjectsIds']) ){

            foreach ( $order_detail['linkedObjectsIds'] as $shipment_id ){
                foreach ( $shipment_id as $id=>$value ){
                    $response= self::makeDeleteApiCall(self::$warehouse_config['api_url'].'index.php/shipments/'.$value);
                    echo '<pre>';
                    var_dump($response);
                    die;
                }
            }
        }
        $json='["'.$order_number.'"]';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'delete-shipment-under-sale-order.php',$json);
    }
    public static function OrderClose( $warehouse, $sale_order_id ){
        if ($warehouse)
            self::set_config($warehouse);

        $json='{"notrigger": 0}';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/orders/'.$sale_order_id.'/close',$json);
    }
    public static function ShipmentClose( $warehouse, $order_number ){
        if ($warehouse)
            self::set_config($warehouse);

        $json='{"order_number":"'.$order_number.'"}';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'close-shipment.php',$json);
    }
    public static function StockMovements($warehouse,$json){
        if ($warehouse)
            self::set_config($warehouse);

        return self::makePostApiCall(self::$warehouse_config['api_url'].'index.php/stockmovements',$json);
    }
    public static function OrderCancel($warehouse, $order_number){
        if ($warehouse)
            self::set_config($warehouse);
        $json='{"order_number":"'.$order_number.'"}';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'mark-order-cancel.php',$json);
    }
    public static function GetShipments($warehouse, $order_number){
        if ($warehouse)
            self::set_config($warehouse);

        $json='{"order_number":"'.$order_number.'"}';
        return self::makePostApiCall(self::$warehouse_config['api_url'].'get-shipments.php',$json);
    }

    public static function UpdateSaleOnWarehouse($warehouse,$sales){
        if ($warehouse)
            self::set_config($warehouse);

        foreach ( $sales as $key=>$value ){
            if ( $value['order_details']['order_status']=='canceled' ){
                $filter="t.ref_client%3D('".urlencode($value['order_details']['order_number'])."')";
                $dolibarr_orders=self::GetAllOrders($warehouse,$filter);
                if ($dolibarr_orders[0]['statut']!=-1){
                    $shipments=json_decode(self::GetShipments($warehouse,$value['order_details']['order_number']),true);

                    $line_items=[];
                    foreach ( $dolibarr_orders[0]['lines'] as $line_value ){
                        $line_items[$line_value['id']]=$line_value;
                    }
                    foreach ( $shipments as $ship_key=>$ship_value ){
                        $shipments[$ship_key]['product_id']=$line_items[$ship_value['order_line_item_id']]['fk_product'];
                    }
                    foreach ( $shipments as $s_val ){
                        $json='{
  "product_id": '.$s_val['product_id'].',
  "warehouse_id": '.$s_val['warehouse_id'].',
  "qty": '.$s_val['qty'].',
  "lot": "string",
  "movementcode": "string",
  "movementlabel": "Order Canceled",
  "price": "string",
  "dlc": "'.date('Y-m-d').'",
  "dluo": "'.date('Y-m-d').'"
}';
                        self::StockMovements($warehouse,$json);
                    }

                    //echo '<pre>';print_r($shipments);print_r($dolibarr_orders);die;
                    self::DeleteShipmentsOfSaleOrder($warehouse,$dolibarr_orders[0]['id']);
                    self::OrderCancel($warehouse,$value['order_details']['order_number']);
                    //die;
                }

            }
            else if ( $value['order_details']['order_status']=='shipped' || $value['order_details']['order_status']=='completed' )
            {
                $filter="t.ref_client%3D('".urlencode($value['order_details']['order_number'])."')";
                $dolibarr_orders=self::GetAllOrders($warehouse,$filter);

                $order_number = $dolibarr_orders[0]['ref_client'];
                self::ShipmentClose($warehouse,$order_number); // close shipment, order is delivered
            }
        }
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
}