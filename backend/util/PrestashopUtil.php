<?php
/**
 * Created by PhpStorm.
 * User: ABDULLAH
 * Date: 8/02/2019
 * Time: 3:04 PM
 */
namespace backend\util;

use backend\controllers\MainController;
use backend\util\HelpUtil;
use Codeception\Module\Yii1;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelSkuMissing;
use common\models\ChannelsProducts;
use common\models\GeneralReferenceKeys;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrderShipment;
use common\models\Products;
use yii\db\Exception;
use yii\web\Controller;
use backend\util\OrderUtil;

class PrestashopUtil {
    private static $debugMode = false;
    private static $api_url = '';
    private static $api_key = '';
    const _REDUCTION_TYPE = 'amount';
    const _FROM_QUANTITY = '1';
    private static $fetched_skus=[]; // store skus list which are fetched // specificaly used to delete products from ezcom as well which are deleted on platform
    public static function AddLibrary(){
        $Controllers = ['product-360','cron','api','sales','courier','test'];
        if ( in_array(\Yii::$app->controller->uniqueId,$Controllers) )
            return '../library/PrestaShop-Library/PSWebServiceLibrary.php';
        else
            return 'backend/library/PrestaShop-Library/PSWebServiceLibrary.php';  // for cmd

    }

    public function __construct($id, Module $module, array $config = [])
    {
        parent::__construct($id, $module, $config);
    }

    /**
     * @param $system_status
     * map system courier status with market place status
     *
     */
    public static function map_system_courier_marketplace_statuses($system_status)
    {
        if(strtolower($system_status)=="completed")
            return "delivered";
        else
            return $system_status;
    }

    // make api call to cms
    private static function makeApiCall($channel,$params)
    {
        self::GetChannelApiByChannelId($channel->id);
        if(self::$api_url && self::$api_key)
        {
                /*foreach($params as $k=>$v)
                {
                    echo "calling $k=>$v";
                }
                echo "<hr/>";*/

            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                 //   $opt['date']='1';//&date=1

                /*foreach ($filters as $key=>$value){
                    $opt[$key] = $value;
                }*/
                $xml_response = $webservice->get($params);
                return [
                        'type'=>'success',
                        'data'=>$xml_response,
                        'msg'=>'fetched'
                       ];


            }
            catch ( \PrestaShopWebserviceException $ex )
            {
                return [
                        'type'=>'failure',
                        'data'=>'',
                        'msg'=>$ex->getMessage(),
                       ];
            }
        }
        else
        {
            return [
                    'type'=>'failure',
                    'data'=>'',
                    'msg'=>"ChannelDeactivated",
                   ];
        }
    }

    /*private static function mapOrderStatus($order_status) // map if it is pending or delivered or cancelled
    {
        $shipped_orders = ['delivered', 'shipped'];
        $canceled_orders = ['cancelled', 'canceled','refunded'];
        $pending_orders = ['awaiting check','awaiting paypal payment', 'payment accepted','processing in progress','remote payment accepted','payment error',
            'awaiting check payment','amazon pay - authorized'];
        if(in_array(strtolower($order_status),$shipped_orders))
        {
            return "shipped";
        }
        if(in_array(strtolower($order_status),$canceled_orders))
        {
            return "canceled";
        }
        if(in_array(strtolower($order_status),$pending_orders))
        {
            return "pending";
        }
        return $order_status;
    }*/
    public static function GetChannelApiByChannelId($channelId){

        require_once (self::AddLibrary());

        $channels = Channels::findone(['is_active' => 1,'id'=>$channelId]);
       // self::debug($channels);

        if(!empty($channels)){
            self::$api_url = $channels->api_url;
            self::$api_key = $channels->api_key;
        }

    }
    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;

    }
    public static function GetCategoryDetail( $channelId, $filters=[] ){
        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                $opt['resource'] = 'category';
                $opt['display'] = 'full';
                foreach ($filters as $key => $value) {
                    $opt[$key] = $value;
                }
                $xml = $webservice->get($opt);
                return json_encode($xml);
            } catch (\PrestaShopWebserviceException $ex) {
                return 'error ' . $ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }
    }
    public static function GetCategories($channelId,$filters=[]){
        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                $opt['resource'] = 'category';
                $opt['display'] = 'full';

                foreach ($filters as $key => $value) {
                    $opt[$key] = $value;
                }

                $xml = $webservice->get($opt);
                return json_encode($xml);
            } catch (\PrestaShopWebserviceException $ex) {
                return 'error ' . $ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }
    }
    public static function GetProducts($channelId,$filters=[]){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
                $opt['resource'] = 'products';
                $opt['display']='full';
                $opt['link_rewrite']='true';
                foreach ($filters as $key=>$value){
                    $opt[$key] = $value;
                }
              //  self::debug($webservice);
                $xml = $webservice->get($opt);
               // self::debug($xml);
                return json_encode($xml);
            }catch ( \PrestaShopWebserviceException $ex ){
                //self::debug($ex->getMessage());
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }
    }

    public static function Attributes($channelId){
        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $opt = array('resource' => 'products');

        $xml = $webService->get(array('url' => self::$api_url . '/api/products?schema=synopsis'));
        $resources = $xml->children()->children();
        return json_encode($resources);
        }else{
            return "ChannelDeactivated";
        }
    }

    public static function UpdateStocks($channelId, $StockId, $Stock)
    {


        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'stock_availables');
                $opt['id'] = $StockId;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                $resources->quantity=$Stock;
                $opt['putXml'] = $xml->asXML();
                $xml = $webservice->edit($opt);
                return $xml;
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function UpdateOrderStatus( $channelId, $StatusId, $OrderId ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'orders');
                $opt['id'] = $OrderId;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                $resources->current_state=$StatusId;
                $resources->sendemail = 1;
                $opt['putXml'] = $xml->asXML();
                $xml = $webservice->edit($opt);
                return $xml;
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }

    public static function UpdateOrderShippingNumber($channelId, $CreateShipment, $OrderId)
    {

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'orders');
                $opt['id'] = $OrderId;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                $resources->shipping_number = $CreateShipment->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
                $resources->delivery_date = date('Y-m-d H:i:s', strtotime($CreateShipment->CompletedShipmentDetail->OperationalDetail->DeliveryDate));
                //$resources->invoice_date = date('Y-m-d H:i:s', strtotime($CreateShipment->CompletedShipmentDetail->OperationalDetail->DeliveryDate));
                //$resources->total_shipping = $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount;
                //$resources->total_shipping_tax_incl = $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount;
                //$resources->total_shipping_tax_excl = $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount;
                $opt['putXml'] = $xml->asXML();
                $xml = $webservice->edit($opt);
                return $xml;
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function GetAddresses( $channelId, $AddressId ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'addresses');
                $opt['id'] = $AddressId;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                //$resources->current_state=$StatusId;
                $opt['putXml'] = $xml->asXML();
                $xml = $webservice->get($opt);
                return $xml;
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function GetOrderStatusesList( $channelId, $Status ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'order_states');
                //$opt['id'] = 111;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                //$resources->quantity=$Stock;
                $opt['putXml'] = $xml->asXML();
                $xml = $webservice->get($opt);
                return $xml;
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function GetOrder(){

    }
    public static function GetStockQuantity( $channelId, $StockId ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'stock_availables');
                $opt['id'] = $StockId;
                $xml = $webservice->get($opt);
                $resources = $xml->children()->children();
                $xml = $webservice->get($opt);
                return json_encode($xml);
            }catch ( \PrestaShopWebserviceException $ex ){
                return 'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function AddNewProductOnPrestashop($p3s,$data,$isUpdate){
        self::GetChannelApiByChannelId($p3s->shop_id);

        $response = [];
        if(self::$api_url!="" && self::$api_key!="") {

            try {

                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
                $opt = array('resource' => 'products');
                if ( $isUpdate && $p3s->item_id != '' ){
                    $opt['id']=$p3s->item_id;
                    $xml = $webservice->get($opt);
                    $resources = $xml->children()->children();
                    $resources = self::PreparePrestashopAddProductRequest($resources,$data,$isUpdate);
                    $opt['putXml'] = $xml->asXML();
                    $xml = json_decode(json_encode($webservice->edit($opt)));
                    // Delete image on shop
                     foreach ($xml->product->associations->images->image as $image){
                        // Delete call
                        self::DeletePrestashopProductImages($p3s->shop_id,$p3s->item_id,$image->id);
                    }

                }else{
                    echo 'INSERT INSERT INSERT';
                    $xml = $webservice->get(array('url' => self::$api_url . '/api/products?schema=synopsis'));
                    $resources = $xml->children()->children();
                    $resources = self::PreparePrestashopAddProductRequest($resources,$data,$isUpdate);
                    $opt['postXml'] = $xml->asXML();
                    $xml = json_decode(json_encode($webservice->add($opt)));
                }
                $productId = $xml->product->id;
                $imageUploadResponse = self::AddPrestashopProductImages($p3s->shop_id,$data['images'],$productId,$isUpdate);
                $p_detail = PrestashopUtil::GetProducts($p3s->shop_id,[ 'filter[id]' => $productId ]);
                return json_decode($p_detail);

            }
            catch (\PrestaShopWebserviceException $ex) {

                echo 'Other error: <br/>' . $ex->getMessage();

            }

        }else{
            return "ChannelDeactivated";
        }
    }
    private static function PreparePrestashopAddProductRequest($resources,$data,$isUpdate){
        //information section
        $resources->type = 'Standard product';
        $resources->active = ($data['info']['p360']['presta_attributes']['normal']['active'] == '1') ? '1' : '0';
        $resources->name->language[0][0] = $data['info']['p360']['common_attributes']['product_name']; //'Test name language';
        $resources->reference = $data['info']['p360']['common_attributes']['product_sku'];//'MRABD420';
        $resources->ean13 = $data['info']['p360']['presta_attributes']['normal']['ean13'];//'1234567893214';
        $resources->upc = $data['info']['p360']['presta_attributes']['normal']['upc'];//'123456789321';
        $resources->visibilty = $data['info']['p360']['presta_attributes']['normal']['visibility'];//'both';
        $resources->available_for_order = $data['info']['p360']['presta_attributes']['normal']['available_for_order']; //true;
        $resources->show_price =true;
        $resources->online_only =false;
        $resources->condition = $data['info']['p360']['presta_attributes']['normal']['condition'];//'new';
        $resources->description->language[0][0] = $data['info']['p360']['common_attributes']['product_short_description']; //'<p>desscription language</p>';
        $resources->description_short->language[0][0] = $data['info']['p360']['presta_attributes']['normal']['description_short'];//'description short language';
        //prices section
        $resources->wholesale_price = $data['info']['p360']['presta_attributes']['normal']['wholesale_price']; //100;
        $resources->price = $data['info']['p360']['common_attributes']['product_price'];//'1000';
        $resources->on_sale = true;
        $resources->unit_price_ratio = '10';
        $resources->unity = '10';
        //association
        $resources->associations->categories->category->id = $data['info']['p360']['presta_category'];//170;
        //shipping
        $resources->width = $data['info']['p360']['common_attributes']['package_width'];//2;
        $resources->height =$data['info']['p360']['common_attributes']['package_height'];//2;
        $resources->depth = $data['info']['p360']['common_attributes']['package_length'];//2;
        $resources->weight= $data['info']['p360']['common_attributes']['package_weight'];//5;
        $resources->additional_shipping_cost = 10;
        $resources->state= 1;
        //available carriers no available
        //combinations
        //Quantities
        //$resources->quantity =100;
        $resources->advanced_stock_management = true;
        //images
        //features
        $resources->associations->product_features->product_feature->id = 0;
        //suppliers
        //$resources->supplier_reference = '';

        unset($resources->quantity);
        unset($resources->manufacturer_name);
        //$resources->link_rewrite = 'blabla';
        return $resources;
    }

    public static function DeletePrestashopProductImages($shopId,$productId,$imageId){

        self::GetChannelApiByChannelId($shopId);

        if (self::$api_url!="" && self::$api_key!=""){
            try{
                $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key,true);
                $xml = $webService->get(array('url' => self::$api_url.'api/images/products/'.$productId.'/'.$imageId.'?ps_method=DELETE'));
                $resources = $xml->children()->children();
                $opt = array('resource' => 'images');
                $xml = $webService->delete($opt);
            }
            catch (\PrestaShopWebserviceException $ex) {
                echo 'Other error: <br/>'.$ex->getMessage();
            }
        }
    }
    public static function UpdateProductStock($shopId,$ProductId,$Quantity){

        self::GetChannelApiByChannelId($shopId);
        $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key,true);

        try{
            $opt = array('resource' => 'products');
            // Define the resource id to modify
            $opt['id'] = $ProductId;
            // Call the web service, recuperate the XML file
            $xml = $webService->get($opt);
            self::debug($xml);
            // Retrieve resource elements in a variable (table)
            $resources = $xml->children()->children();
            //echo '<pre>';print_r($resources);die;
            $resources->quantity = $Quantity;
            $resources->id = $ProductId;
            $opt['putXml'] = $xml->asXML();
            $xml = json_decode(json_encode($webService->edit($opt)));
            self::debug($xml);
        }catch ( \PrestaShopWebserviceException $ex ){
            echo $ex->getMessage();
        }


    }
    private static function AddPrestashopProductImages($channelId,$images,$productId,$isUpdate){

        self::GetChannelApiByChannelId($channelId);

        if(self::$api_url!="" && self::$api_key!="") {
            foreach ( $images as $value ){
                try {
                    $urlImage = self::$api_url . '/api/images/products/' . $productId .'/';
                    $image_path = getcwd().'\backend\web\product_images\\'.$value;
                    $image_mime = 'image/png';
                    $args['image'] = $image_path;
                    $args['image'] = new \CURLFile($image_path, $image_mime);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
                    curl_setopt($ch, CURLOPT_URL, $urlImage);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_USERPWD, self::$api_key.':');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
                    $result = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if (200 == $httpCode){
                        echo 'Product image was successfully created.';
                    }
                }
                catch (\PrestaShopWebserviceException $ex){
                    echo 'Other error: <br/>' . $ex->getMessage();
                }
            }

        }
        else{
            return "ChannelDeactivated";
        }

    }
    public static function GetTaxRuleGroups($channelId){
        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $opt = array('resource' => 'tax_rule_groups');

            // list of tax rule with only id's
            $xml = $webService->get(array('url' => self::$api_url.'api/tax_rule_groups'));
            $resources = json_encode($xml->children()->children());
            $tax_rules_list = json_decode($resources);
            $TaxGroups = [];
            foreach ( $tax_rules_list->tax_rule_group as $key=>$value ){
                foreach ( $value as $tax_id )
                    $tax_id = $tax_id->id;

                // get detail information of tax rule
                $xml = $webService->get(array('url' => self::$api_url.'api/tax_rule_groups/'.$tax_id));
                $Tax_Group_Detail = json_decode(json_encode($xml->children()->children()));
                $TaxGroups[] = $Tax_Group_Detail;
            }
            return json_encode($TaxGroups);
        }else{
            return "ChannelDeactivated";
        }
    }
    public static function GetStockAvailable( $channelId ,$Stock_Available_Id ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $opt = array('resource' => 'stock_availables');

            // list of tax rule with only id's
            $opt['id'] = $Stock_Available_Id;
            $xml = $webService->get($opt);
            return json_encode($xml);
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function CombinationDetail( $channelId, $Combination_Id ){

        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $opt = array('resource' => 'combinations');
            // list of tax rule with only id's
            $opt['id'] = $Combination_Id;
            $xml = $webService->get($opt);
            return json_encode($xml);
        }else{
            return "ChannelDeactivated";
        }

    }
    public static function GetStockId( Array $Combination , $Combination_id ){

        foreach ( $Combination as $value ){

            if ( isset($value->id_product_attribute) && $value->id_product_attribute == $Combination_id )
            {
                return $value->id;
            }
        }

    }
    public static function GetEan( $Channel_id,$Ean13 ){

        $Sql = " SELECT * FROM general_reference_keys grk INNER JOIN channels_products cp ON cp.id = grk.table_pk WHERE cp.channel_id = ".$Channel_id." AND 
        grk.key= 'ean13' AND grk.table_name = 'channels_products' AND grk.value = ".$Ean13;

        $connection = \Yii::$app->db;
        $Results = $connection->createCommand($Sql);
        return $Results->queryAll();

    }
    public static function GetProductDetail( $channelId, $ProductId){

        self::GetChannelApiByChannelId($channelId);

        if(self::$api_url!="" && self::$api_key!="") {
            try{

                $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                $opt = array('resource' => 'products');
                $opt['id'] = $ProductId;
                $xml = $webService->get($opt);
                return json_encode($xml);

            }
            catch (\PrestaShopWebserviceException $ex) {

                echo 'Other error: <br/>' . $ex->getMessage();

            }
        }

    }
    public static function GetProductCombinationDetail( $channelId, $combinationId ){
        self::GetChannelApiByChannelId($channelId);

        if(self::$api_url!="" && self::$api_key!="") {
            try{

                $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                $opt = array('resource' => 'combinations');
                $opt['id'] = $combinationId;
                $xml = $webService->get($opt);
                return json_encode($xml);

            }
            catch (\PrestaShopWebserviceException $ex) {

                echo 'Other error: <br/>' . $ex->getMessage();

            }
        }
    }
    public static function UpdatePrice( $channelId, $ProductId, $Price, $CombinationId='',$db_product_primary_key=null )
    {

        //first check if sku is not included in active deal , $db_product_primary_key is only sent from apicontroller to differentiate whether to check or not
        if(isset($db_product_primary_key))
        {
            $check_deal_sku = HelpUtil::SkuInDeal($db_product_primary_key, $channelId);
            if ($check_deal_sku)
            {
                return 'sku is in active deal, failed update';
            }
        }
        self::GetChannelApiByChannelId($channelId);
        $ProductPrice = json_decode(PrestashopUtil::GetProductDetail($channelId, $ProductId));
        if(!isset($ProductPrice->product->price)){
            echo "<hr>";
            echo $ProductId ." -> " . $CombinationId;
            echo "<hr>";
            return "product price failed to get";
        }
        $ProductPrice = $ProductPrice->product->price;

        if(self::$api_url!="" && self::$api_key!="") {

            if ( $CombinationId != '' ){

                try{

                    $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                    $opt = array('resource' => 'combinations');
                    $opt['id'] = $CombinationId;
                    $xml = $webService->get($opt);

                    $resources = $xml->children()->children();

                    // Because of there is always a decrement & increment in combination products so we will do this calculation.
                    $p = number_format((float) $Price - $ProductPrice,2,'.','');

                    $resources->price = $p;
                    $opt['putXml'] = $xml->asXML();
                    $xml = $webService->edit($opt);
                    return json_encode($xml);

                }
                catch (\PrestaShopWebserviceException $ex) {

                    return 'Other error: <br/>' . $ex->getMessage();

                }

            }elseif ( $ProductId ){
                try{
                    $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
                    $opt = array('resource' => 'products');
                    // list of tax rule with only id's
                    $opt['id'] = $ProductId;
                    $xml = $webService->get($opt);
                    $resources = $xml->children()->children();

                    unset($resources->quantity);
                    unset($resources->manufacturer_name);

                    $resources->price = $Price;

                    $opt['putXml'] = $xml->asXML();
                    $xml = $webService->edit($opt);

                    return json_encode($xml);
                }catch (\PrestaShopWebserviceException $ex) {

                    return 'Other error: <br/>' . $ex->getMessage();

                }
            }

        }else{

            return "ChannelDeactivated";

        }



    }
    public static function GetOrders($channel,$DaysTime){

        $current_time=date("Y-m-d H:i:s");
        $opt['resource'] = 'orders';  // which api to call
        $opt['display']='full';
        $opt['sort']='id_DESC';

        $from = date("Y-m-d H:i:s", strtotime($DaysTime)); // for cron job // after 24 hour //
        $opt['filter[date_upd]']="[$from,$current_time]"; // filter between two dates according  to update date
        $opt['date']=1;

 //       self::debug($channel);
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        $api_response = json_encode($api_response);
        $api_response = json_decode($api_response);
        return $api_response;
    }
    // api to fetch order data from presta
    public static  function fetchOrdersApi($channel,$time_period)
    {

        //date_default_timezone_set($channel->default_time_zone ? $channel->default_time_zone:'America/New_York');  // fetch american time
        $current_time=date("Y-m-d H:i:s");
        $opt['resource'] = 'orders';  // which api to call
        $opt['display']='full';
        $opt['sort']='id_DESC';
      //  $opt['filter[customer_message]']="[]";
        if ($time_period == "day")  // whole day 24 hours
        {
            $from = strtotime(date("Y-m-d H:i:s") ) - (1440*60); // for cron job // after 24 hour //
            $from = date("Y-m-d H:i:s", $from);
            $opt['filter[date_upd]']="[$from,$current_time]"; // filter between two dates according  to update date
           // $opt['filter[id]']="568"; // filter between two dates according  to update date
            //$opt['filter[date_upd]']="[2019-08-19 22:57:18 ,2019-08-23 23:23:48]"; // filter between two dates according  to update date
            $opt['date']=1;
        }
        else if($time_period == "chunk")  // if
        {
            $from = strtotime(date("Y-m-d H:i:s") ) - (60*60); // for cron job // after 40 min //
            $from = date("Y-m-d H:i:s", $from);
            $opt['filter[date_upd]']="[$from,$current_time]"; // filter between two dates according  to update date
            //$opt['filter[date_upd]']="[2019-08-19 22:57:18 ,2019-08-23 23:23:48]"; // filter between two dates according  to update date
            $opt['date']=1;
        }


        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        //self::debug($api_response);
        if($api_response['type']=="success" && $api_response['data'])
        {
            $json_format=json_encode($api_response['data'],true);
            self::orderData($json_format,$channel);  // get orderdetail , order items , order customer detail
        }
        //date_default_timezone_set('Asia/Kuala_Lumpur');
        return $api_response;

    }

    /****
     * get order /messages or notes
     */
    public static function order_notes($channel,$order_id)
    {
        $opt = [
            'resource' => 'messages',
            'display'=>'full',
            'filter[id_order]'=>$order_id
            ];
        $api_response=json_encode( self::makeApiCall($channel,$opt) );  // calling api request method getting response json
        $api_response = json_decode($api_response);
        if(isset($api_response->data->messages->message))
            return $api_response->data->messages->message;
        else
            return null;
    }

    /****************
     * get order cart rule
     * it gives cart rule attached to order , for copon getting
     */
    public static function get_order_coupon($channel,$order_id)
    {
        $opt = [
            'resource' => 'order_cart_rule',
            'display'=>'full',
            'filter[id_order]'=>$order_id
        ];
        $api_response=json_encode( self::makeApiCall($channel,$opt) );  // calling api request method getting response json
        $api_response = json_decode($api_response);
        if(isset($api_response->type) && $api_response->type=='success')
        {
            if(isset($api_response->data->order_cart_rules->order_cart_rule))
            {
               return self::get_order_coupon_code($channel,$api_response->data->order_cart_rules->order_cart_rule->id_cart_rule);

            }
        }
        return null;
    }

    public static function get_order_coupon_code($channel,$cart_id)
    {
        $opt = [
            'resource' => 'cart_rules',
            'id'=>$cart_id
        ];
        $api_response=json_encode( self::makeApiCall($channel,$opt) );  // calling api request method getting response json
        $api_response = json_decode($api_response);
        if(isset($api_response->type) && $api_response->type=='success')
        {
            if(isset($api_response->data->cart_rule))
            {
                return $api_response->data->cart_rule->code;

            }
        }
        return null;
    }



    public static function getCustomerType($channel, $customerId){
        $opt = [
            'resource' => 'customers',
            'id' =>$customerId,

        ];
        $api_response=json_encode( self::makeApiCall($channel,$opt) );  // calling api request method getting response json
        $api_response = json_decode($api_response);
        //self::debug($api_response);
        if ( isset($api_response->data->customer) ){
            if (isset($api_response->data->customer->b2b_business_registration_no) &&  gettype($api_response->data->customer->b2b_business_registration_no)!='object' )
            {
                return 'B2B';
            }
        }
        return 'B2C';  // else return b2c


    }
    //main order detail
    private static function orderData($apiresponse=null,$channel)
    {

       // $response=array();
        $data=json_decode($apiresponse);
        if(isset($data->orders->order))  // object named orders
        {
            $data= is_array($data->orders->order) ? $data->orders->order:$data->orders;   // api returning object if single entry , returns array if multiple
            //self::debug($data);
            foreach($data as $order)
            {
                $order_added_at=HelpUtil::get_utc_time($order->date_add);
                $order_updated_at=HelpUtil::get_utc_time($order->date_upd);
                //////////below temporary date check applied because after that date amazon api will handle orders and stock
                /*$target_date=gmdate('Y-m-d H:i:s',strtotime('2021-03-24 04:27:03'));
                if($channel->name=="globalmobile" && $order_added_at > $target_date){
                    echo "continued";
                    echo "<br/>";
                    continue;
                }*/

                ////////////////////////////////
                $order_status=self::getOrderStatus($order->current_state,$channel);  //get order status
                $order->current_state=$order_status;  // to use it in order items
                $order_items= self::OrderItems($order,$channel);  //get order items
                $customer_detail=self::orderCustomerDetail($order,$channel); // get order customer detail
                $B2bOrB2c = 'B2C';
                if (isset($order->id_customer) && gettype($order->id_customer)!='object')
                    $B2bOrB2c = self::getCustomerType($channel, $order->id_customer);


                $order_note=self::order_notes($channel,$order->id);  // any note message by customer
                $coupon_code=self::get_order_coupon($channel,$order->id);
                $order_data =array(
                                    'order_id'=>$order->id,
                                    'order_no'=>$order->reference,
                                    'channel_id'=>$channel->id,
                                    'payment_method'=>$order->payment,
                                    'order_total'=>$order->total_paid_real,
                                    'order_created_at'=>$order_added_at,
                                    'order_updated_at'=>$order_updated_at,
                                    'customer_type' => $B2bOrB2c,
                                    'order_status'=>$order_status,
                                    'total_items'=>count($order_items),
                                    'order_shipping_fee'=>$order->total_shipping,
                                    'order_discount'=>$order->total_discounts_tax_excl,
                                    'cust_fname'=>$customer_detail['shipping_address']['fname'],
                                    'cust_lname'=>$customer_detail['shipping_address']['lname'],
                                    'full_response'=>'',//$apiresponse,
                                    'order_note'=>isset($order_note->message) ? $order_note->message:NULL,
                                    'coupon_code'=>$coupon_code ? trim($coupon_code):NULL,
                                );

                $response=[
                                 'order_detail'=>$order_data ,
                                 'items'=>$order_items ,
                                 'customer'=>$customer_detail,
                                 'channel'=>$channel, // need to get channel detail in orderutil
                          ];
               // self::debug($response);
               OrderUtil::saveOrder($response);  // process data in db

            }

        }

        return;

    }

    private static function get_utc_date($date)
    {
        date_default_timezone_set('America/new_york');
        $raw=$date;
        $time_stamp=strtotime($raw);
        date_default_timezone_set('UTC');
        return date('Y-m-d H:i:s',$time_stamp);
    }

    // order items
    private static function orderItems($order=null,$channel)
    {
        $items=array();

        if($order)
        {
            $order_items=$order->associations->order_rows->order_row;
            $ord = [];
            if ( !is_array($order_items) ){
                $ord[] = $order_items;
                $order_items = $ord;
            }


            foreach($order_items as $val)
            {
                $LeftStock="";
                $sku_id= HelpUtil::getChannelProductsProductId(array('sku'=>$val->product_reference,'channel_id'=>$channel->id)); // get id stored in channelproducts table against sku code sent

                /*Get the remaining stock from the shop*/
                $sku = $val->product_reference;
                /*$channels_product_id = ChannelsProducts::find()->where(
                    [
                        'channel_id'=>$channel->id,
                        'channel_sku'=>(gettype($sku)=='object') ? '' : $sku
                    ])->asArray()->all();
                if ( !empty($channels_product_id) ){
                    $cpid = $channels_product_id[0]['id'];
                    $StockId = GeneralReferenceKeys::find()->where(['channel_id'=>$channel->id,'table_name'=>'channels_products','table_pk'=>$cpid
                        ,'key'=>'stock_available_id'])->asArray()->all();

                    if (isset($StockId[0]['value'])){
                        $GetAvailableStocks = self::GetStockQuantity($channel->id,$StockId[0]['value']);
                        $GetAvailableStocks = json_decode($GetAvailableStocks);
                        $LeftStock = $GetAvailableStocks->stock_available->quantity;
                    }

                }*/
                /*Get the remaining stock from the shop*/
                $item_discount=0;
               /* if($val->product_price != $val->unit_price_tax_excl)
                {
                    $item_discount =number_format(($val->product_price - $val->unit_price_tax_excl),2);
                }*/

                $order_added_at=self::get_utc_date($order->date_add);
                $order_updated_at=self::get_utc_date($order->date_upd);
                $qty=$val->product_quantity;
                $paid_price=isset($val->unit_price_tax_excl) ? $val->unit_price_tax_excl:$val->product_price;
                $items[]=array(
                    'order_id'=>$order->id,
                    'sku_id'=>$sku_id ? $sku_id :"",
                    // 'product_id' => $val->product_id,
                    'order_item_id'=>$val->product_id,
                    'item_status'=>$order->current_state,
                    'shop_sku'=>'',
                    'price'=>$val->product_price,
                    'paid_price'=>$paid_price,
                    'shipping_amount'=> 0 ,//$order->total_shipping,
                    'item_discount'=>$item_discount,
                    'sub_total'=>($qty * $paid_price),
                    'item_updated_at'=>$order_updated_at,
                    'item_created_at'=>$order_added_at,
                    'full_response'=>'',//json_encode($order_items),
                    'quantity'=>$qty,
                    'item_sku'=>(array) $val->product_reference ? $val->product_reference:"",
                   // 'stock_after_order' =>isset($LeftStock) ? $LeftStock  :""
                );
            }
        }

        return $items;

    }

    // order customer data
    private static function orderCustomerDetail($order=null,$channel)
    {


            $shipping_address = self::getShippingBillingAddress($order->id_address_delivery,$channel);  // function returning shipping address
            $billing_address =($order->id_address_delivery==$order->id_address_invoice) ? $shipping_address: self::getShippingBillingAddress($order->id_address_invoice,$channel);   // function returning billing address
            $address= [
                      'shipping_address'=>$shipping_address,
                      'billing_address'=>($order->id_address_delivery==$order->id_address_invoice)  ? $shipping_address:$billing_address,

                    ];
             if(isset($order->id_customer))  // get customer
             {
               $customer=self::get_customer_detail($channel,$order->id_customer);
                if(isset($customer->email) && gettype($customer->email)!="object")
                {
                    $address['shipping_address']['email']=$customer->email;
                    $address['billing_address']['email']=$customer->email;
                }
             }
             return $address;

    }

    ////get customer detail
    public static function get_customer_detail($channel,$id)
    {
        $opt = [
            'resource' => 'customers',
            'id' =>$id,

        ];
        $api_response=json_encode( self::makeApiCall($channel,$opt) );  // calling api request method getting response json
        $api_response = json_decode($api_response);
        if(isset($api_response->data->customer))
            return $api_response->data->customer;
        else
            return false;
    }

    // get shipping or billing address
    private static function getShippingBillingAddress($address_id,$channel)
    {
        $opt = [
                  'resource' => 'addresses',
                  'id' =>$address_id,

                ];
        $address=array();
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
       // self::debug($api_response);
         if($api_response['type']=="success" && $api_response['data'])
        {

            $json=json_encode($api_response['data'],true);
            //echo $json; die();
            $data= json_decode($json);
            $phone=NULL;
            if(gettype($data->address->phone_mobile)=='string')
                    $phone=$data->address->phone_mobile;
            elseif(gettype($data->address->phone)=='string')
                $phone=$data->address->phone;

            if($data)
            {

                    $address=[
                             'fname'=>$data->address->firstname,
                             'lname'=>$data->address->lastname,
                             'address'=>$data->address->address1,
                             'state'=>$data->address->id_state ? self::getShippingState($channel,$data->address->id_state):"",
                             'city'=>$data->address->city,
                             'phone'=>$phone,
                             'country'=>$data->address->id_country ? self::getShippingCountry($channel,$data->address->id_country):"",
                             'postal_code'=>$data->address->postcode,
                             ];

             }

        }
        return $address;
    }

    // get order status
    private static function getOrderStatus($order_status_id,$channel)
    {
        $opt = [
                'resource' => 'order_states',
                'id' =>$order_status_id,
                //'display'    => '[name]'
             ];
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        //print_r($api_response); die();
        if($api_response['type']=="success" && $api_response['data'])
        {

            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            return isset($data->order_state->name->language) ? $data->order_state->name->language:"";

        }
        return "";
    }
    ///get order current status id
    public static function getOrderCurrentStatus($channel,$presta_order_id)
    {
        $opt = [
            'resource' => 'orders',
            'display' => 'full',
            'filter[id]' =>$presta_order_id, // order id of prestashop database
        ];
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        if($api_response && isset($api_response['type'])){
            if($api_response['type']=="success"){
                return $api_response['data']->orders->order->current_state;
            }
        }
        return 0;
    }

    public static function getStatesList($channel){
        $opt = [
            'resource' => 'order_states',
            'display'    => 'full'
        ];
        //self::debug($opt);
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        if($api_response['type']=="success" && $api_response['data'])
        {

            $result = [];
            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            //self::debug($data);
            if ( !empty($data->order_states->order_state )){

                foreach ( $data->order_states->order_state as $value ){
                    $result[$value->id] = $value->name->language;
                }
                return $result;
            }else{
                return [];
            }


            //return isset($data->order_state->name->language) ? $data->order_state->name->language:"";

        }
        return ['error'=>'request failed'];
    }
    public static function orderDetail($channelId, $orderId){
        //echo $orderId;die;
        $opt = [
            'resource' => 'order_details',
            'id' =>$orderId,
        ];
        $api_response=self::makeApiCall($channelId,$opt);  // calling api request method getting response json
        //self::debug($api_response);
        if($api_response['type']=="success" && $api_response['data'])
        {

            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            return isset($data->order_state->name->language) ? $data->order_state->name->language:"";

        }
        return "";
    }

    public static function getOrderCarriers($channel,$presta_order_id)
    {
        $opt = [
            'resource' => 'order_carriers',
            'display' => 'full',
            'filter[id_order]' =>$presta_order_id, // order id of prestashop database
        ];
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        if($api_response) {
            $api_response = json_encode($api_response);
            return json_decode($api_response);
        }

        return ;
    }

    public static function updateOrderCarrierTracking($channelId, $prestaShopOrderId, $ShippingRatesExlTax=null,$ShippingRatesIncTax=null, $TrackingNumber, $Weight=null)
    {

        $api_response= self::getOrderCarriers($channelId,$prestaShopOrderId);

        if(isset($api_response->type) && $api_response->type=="success" && $api_response->data)
        {

            $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
            $opt = array('resource' => 'order_carriers');
            $opt['id'] = $api_response->data->order_carriers->order_carrier->id;
            $xml = $webservice->get($opt);
            $resources = $xml->children()->children();
            if($Weight)
                $resources->weight=($Weight);
            if($ShippingRatesExlTax)
                 $resources->shipping_cost_tax_excl=$ShippingRatesExlTax;
            if($ShippingRatesIncTax)
                $resources->shipping_cost_tax_incl=$ShippingRatesIncTax;

            $resources->date_add = date('Y-m-d H:i:s');
            $resources->tracking_number = $TrackingNumber;
            $opt['putXml'] = $xml->asXML();
            $xml = $webservice->edit($opt);
            $json = json_encode($xml);
            $data = json_decode($json);
            return $data;
        }
        return "failed to update ";
    }

    /***
     * @param $channelId
     * @param $prestaShopOrderId
     * @param $ShippingRatesExlTax
     * @param $ShippingRatesIncTax
     * @param $TrackingNumber
     * @param null $Weight
     * @return mixed|string
     * add new courier tracking number
     */
    public static function addOrderCarrierTracking($channelId, $prestaShopOrderId, $ShippingRatesExlTax=null,$ShippingRatesIncTax=null, $TrackingNumber, $Weight=null)
    {

        $api_response= self::getOrderCarriers($channelId,$prestaShopOrderId);
       // echo "<pre>";
       // print_r($api_response); die();

        if(isset($api_response->type) && $api_response->type=="success" && $api_response->data)
        {
            $carrier_id=104; // hardcoded default
            if(isset($api_response->data->order_carriers)){
                if(is_array($api_response->data->order_carriers->order_carrier)){
                    $carrier_id=$api_response->data->order_carriers->order_carrier[0]->id_carrier;
                } else {
                    $carrier_id=$api_response->data->order_carriers->order_carrier->id_carrier;
                }
            }
           // echo $carrier_id; die();
            $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
            $opt = array('resource' => 'order_carriers');
            //  $opt['id'] = $api_response->data->order_carriers->order_carrier->id;
            ////////////////////////////
            $xml = $webservice->get(array('url' => self::$api_url . 'api/order_carriers?schema=synopsis'));
            // $resources = $xml->children()->children();
            /// //////////////////////////
            // $xml = $webservice->get($opt);
            $resources = $xml->children()->children();
            if($Weight)
                $resources->weight=($Weight);

            $resources->id_order=$prestaShopOrderId;
            $resources->id_carrier=$carrier_id;

            if($ShippingRatesExlTax)
                $resources->shipping_cost_tax_excl=$ShippingRatesExlTax;
            if($ShippingRatesIncTax)
                $resources->shipping_cost_tax_incl=$ShippingRatesIncTax;

            $resources->date_add = date('Y-m-d H:i:s');
            $resources->tracking_number = $TrackingNumber;
            $opt['postXml'] = $xml->asXML();
            $xml = $webservice->add($opt);
            $json = json_encode($xml);
            $data = json_decode($json);
            return $data;
        }
        return "failed to update";
    }

    public static function Carriers($channelId){

        $opt = [
            'resource' => 'carriers',
            'display' => 'full',
        ];
        $api_response=self::makeApiCall($channelId,$opt);  // calling api request method getting response json

        if($api_response['type']=="success" && $api_response['data'])
        {

            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            //self::debug($data);
            $carriers = [];
            if ( isset($data->carriers->carrier) ){
                foreach ( $data->carriers->carrier as $value ){
                    $carriers[$value->id] = $value->name;
                }
            }

            return $carriers;

        }
        return [];
    }

    public static function getStateNameById($channelId, $stateId){

        $opt = [
            'resource' => 'order_states',
            'id' =>$stateId,
            //'display'    => '[name]'
        ];
        return $api_response=self::makeApiCall($channelId,$opt);  // calling api request method getting response json
    }
    private static function getShippingCountry($channel,$id)
    {
        $opt = [
                'resource' => 'countries',
                'id' =>$id,
              ];
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        if($api_response['type']=="success" && $api_response['data'])
        {

            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            return isset($data->country->iso_code) ? $data->country->iso_code:"";
            /*if($data)
            {
                return $data->country->iso_code;
            }*/
        }
        return "";
    }

    private static function getShippingState($channel,$id)
    {
        $opt = [
                'resource' => 'states',
                'id' =>$id,

            ];
        $api_response=self::makeApiCall($channel,$opt);  // calling api request method getting response json
        if($api_response['type']=="success" && $api_response['data'])
        {

            $json = json_encode($api_response['data'],true);
            $data = json_decode($json);
            return isset($data->state->name) ? $data->state->name:"";

        }
        return "";
    }

    ///////////////////////////////////////////////////////////////
    /// //////////////////////get category////////////////
    /// /////////////////////////////////////////////////////////////
    ///
    public static function pullPrestashopCategories($channel)
    {
        $categories = PrestashopUtil::GetCategories($channel->id/*,['filter[id]'=>'170']*/);
        $categories = json_decode($categories);
       if(isset($categories->categories->category))
       {

           foreach ($categories->categories->category as $key=>$value )
            {

               if(!in_array($value->id,array(1,2))) //dont need root and Home  category
                {
                    // find if in general reference key parent id is stored already then use for next saving category
                    $find_parent = GeneralReferenceKeys::findone(['table_name'=>'category',
                                                                'key'=>'presta_category_id',
                                                                'value'=>$value->id_parent,
                                                                'channel_id'=>$channel->id]);
                                                                     /*)->orderBy(
                                                                        'added_at DESC'
                                                                    )->one();*/

                   /* if($value->id==184)
                    {
//                        die();
                        self::debug($find_parent);

                    }*/

                    $record['cat_name']=$value->name->language;
                    $record['is_active']=1;
                    $record['parent_cat_id']=isset($find_parent->table_pk) ? $find_parent->table_pk:0;
                    $record['channel']=$channel;
                    $response=CatalogUtil::saveCategories($record);
                    /// save general reference keys of new added category
                    $find_ref_keys = GeneralReferenceKeys::findone(['table_name'=>'category',
                                                                    'key'=>'presta_category_id',
                                                                    'table_pk'=>$response['id'],
                                                                    'channel_id'=>$channel->id]
                                                                    );

                    if(!$find_ref_keys)
                    {
                        $add_presta_cat_id = new GeneralReferenceKeys();
                        $add_presta_cat_id->channel_id = $channel->id;
                        $add_presta_cat_id->table_name = 'category';
                        $add_presta_cat_id->table_pk = $response['id'];
                        $add_presta_cat_id->key = 'presta_category_id';
                        $add_presta_cat_id->value = $value->id;
                        $add_presta_cat_id->added_at = date('Y-m-d H:i:s');
                        $add_presta_cat_id->save(false);
                    }
                }

            }
       }
    }
    /**********get discount rules  // discounts of all products****/
    private static function get_all_products_discounts($channel)
    {
        $opt = array('resource' => 'specific_prices');
        $opt['display']= '[id_product,id_product_attribute,reduction,from,to,reduction_type]';
       $opt['filter[to]'] = '>=['.date('Y-m-d H:i:s').']';
       // $opt['filter[to]'] = '>=[2021-01-31 00:00:00]';
       // $opt['filter[id_product]'] = '[183]';
        $opt['filter[id_group]'] = '[0|3]';
        $opt['sort'] = '[from_DESC]';
        $resources = self::makeApiCall($channel,$opt);
        //self::debug($resources['data']->specific_prices);
        return isset($resources['data']->specific_prices) ? $resources['data']->specific_prices:NULL;
    }

    private static function rearrange_product_discounts($data=null)
    {
        $result=[];
        //self::debug((array)$data);
        if(isset($data->specific_price) && $data->specific_price)
        {
            foreach($data->specific_price as $key=>$sku)
            {
                $sku=(array)$sku;
                if(isset($result[$sku['id_product']][$sku['id_product_attribute']]))  // $result[parent_sku_id][child_sku_id]
                {
                    if($result[$sku['id_product']][$sku['id_product_attribute']]['from'] < $sku['from'])
                        $result[$sku['id_product']][$sku['id_product_attribute']]=$sku;
                }else{
                    $result[$sku['id_product']][$sku['id_product_attribute']]=$sku;
                }

                /*if(isset($result[$sku->id_product][$sku->id_product_attribute]))  // $result[parent_sku_id][child_sku_id]
                {
                    if($result[$sku->id_product][$sku->id_product_attribute]['from'] < $sku->from)
                        $result[$sku->id_product][$sku->id_product_attribute]=$sku;
                }else{
                    $result[$sku->id_product][$sku->id_product_attribute]=$sku;
                }*/
            }
        }
        return $result;
    }
    /***************************************Channelproducts**********************/
    public static function ChannelProducts($channel,$filters=[])
    {

        $parent_products=self::get_all_parent_products($channel,$filters);  // get all parent products
       // self::debug($parent_products);
        if(isset($filters['filter[id]'])) // mean product id sent and single sku to fetch  , for combination replace this with id_product , // id_product is parent product id
        {
            $filters['filter[id_product]']=$filters['filter[id]'];
            unset($filters['filter[id]']);
        }
        $combinations=self::get_all_combinations($channel,$filters);  //  get all variations
       //self::debug($combinations);
        $combinations=self::rearrange_variations($combinations);   // rearrange parent wise e.g [parent_product_id]=>[child_data]
       // self::debug($combinations);
        $discounts=self::get_all_products_discounts($channel);
        // self::debug($discounts);
        $discounts=self::rearrange_product_discounts($discounts);
       // self::debug($discounts);
        $products=self::organize_channel_products($channel,$parent_products->product,$combinations, $discounts);
        return;

    }

    private static function  rearrange_variations($combinations)
    {
       // self::debug($combinations);
        $result=[];
        if(isset($combinations->combination) && $combinations->combination)
        {
            foreach ($combinations->combination as $combination){

                if(!isset($combination->id_product) || !$combination->id_product )
                    continue;

                if(gettype($combination->reference)=='object')
                    continue;

                /*************Image*********/
                $image=NULL;
                if(isset($combination->associations->images))
                {
                    if(isset($combination->associations->images->image)){
                        $images=$combination->associations->images->image;
                        if(is_array($images))
                            $image=$images[0]->id;
                        else
                            $image=$images->id;
                    }

                }
                /*************Image*********/

                $result[$combination->id_product][]=[ // id_product is parent product id
                    'id'=>$combination->id,
                    'sku'=>$combination->reference,
                    'api_price'=>$combination->price,
                    'reduction_price'=>$combination->real_price, // giving reduction amount , how much discount or reduction applied on original price
                    'discounted_price'=>round($combination->disc_price,2),   // discounted price
                    'ean'=>(isset($combination->ean13) && gettype($combination->ean13)!='object') ? $combination->ean13:NULL,
                    'image'=>$image,
                    'price'=>round(($combination->real_price + $combination->disc_price),2),
                    'quantity'=>isset($combination->quantity) ? $combination->quantity:0,
                ];
            }
        }
        return $result;
      //  self::debug($result);
    }

    private static function organize_channel_products($channel,$parent_products,$combinations, $discounts)
    {
        //$data=[];

        if(is_object($parent_products))  // if single product then not have array
            $raw_data[]=$parent_products;
        else
            $raw_data=$parent_products;

        if($raw_data)
        {
            foreach($raw_data as $product):
                if ( is_object($product->reference)){ // if sku is missing    // in missing case presta gives object
                    HelpUtil::SkuEmptyOnShopLog($channel->id,'Product Id : '.$product->id.' - Sku field is empty');
                    continue;
                }
                /******product cat******/
                $cat=CatalogUtil::getdefaultParentCategory();
                $system_cat_id=isset($cat->id) ? $cat->id:0;
                if(strtolower($channel->name)=="pedro")
                    $system_cat_id=NULL;

                /*****************main parent image***/
                $image=NULL;
                if(isset($product->link_rewrite) && gettype($product->link_rewrite)=="object" && isset($product->id_default_image) && $product->id_default_image && is_string($product->id_default_image))
                {
                    $image=rtrim($channel->api_url,'/')."/".$product->id_default_image."-home_default/".$product->link_rewrite->language.".jpg";
                }

                /********dimensions***********/
                $dimensions=""; // weight height width length
                /**********************/
                    $stock_id='';
                    if(!isset($product->associations->combinations->combination))
                    {
                        if(isset($product->associations->stock_availables->stock_available->id))
                            $stock_id=$product->associations->stock_availables->stock_available->id;
                        elseif(isset( $product->associations->stock_availables->stock_available[0]->id))
                             $stock_id=$product->associations->stock_availables->stock_available[0]->id;

                    }

                if(isset($product->width) && $product->width && isset($product->height) && $product->height && isset($product->depth) && $product->depth)
                {
                    $dimensions=[
                        'width'=>round($product->width,2),
                        'height'=>round($product->height,2),
                        'length'=>round($product->depth,2),
                        'weight'=>round($product->weight,2),
                    ];
                }

                self::$fetched_skus[]=trim($product->reference); // del those skus which are deleted from platform

                $data=[
                    'category_id'=>$system_cat_id,
                    'sku'=>$product->id,  // saving in channel_products table
                    'channel_sku'=>trim($product->reference),  // sku for product table and channel_sku for channels products table
                    'name'=>isset($product->name->language) ? $product->name->language:"" ,
                    'cost'=>isset($product->price) ? $product->price:0 ,
                    'image'=>$image,
                    'rccp'=>isset($product->price) ? $product->price:0,
                    'ean' =>(isset($product->ean13) && gettype($product->ean13)!='object') ? $product->ean13:"",
                    'stock_qty'=>isset($product->quantity) ? $product->quantity:0 ,
                    'channel_id'=>$channel->id,
                    'is_live'=>$product->active,    // is active or is live
                    'brand'=>(isset($product->manufacturer_name) && gettype($product->manufacturer_name)!='object') ? strtolower($product->manufacturer_name):NULL,     // brand,
                    //'marketplace'=>'prestashop',
                ];
                if($dimensions)
                    $data['dimensions']=json_encode($dimensions);

                $product_id=CatalogUtil::saveProduct($data);  //save or update product and get product id
                if($product_id)
                {
                    $data['product_id'] = $product_id;
                    $channel_product=CatalogUtil::saveChannelProduct($data);  // save update channel product
                    if($stock_id)  // save this id it will be used for stock update in prestashop
                        self::update_general_reference_key('channels_products',$channel_product->id,'stock_available_id',$stock_id,$channel);
                }

                /***********if products have child******************/
                if(isset($combinations[$product->id]))
                {
                   // $combinations=;
                    foreach($combinations[$product->id] as $combination)
                    {
                        /****child image***********/
                        $child_image=NULL;
                        if($combination['image'])
                        {
                            $child_image=rtrim($channel->api_url,'/')."/".$combination['image']."-home_default/".$product->link_rewrite->language;
                            // now check if png exists or jpg exists on server
                            $child_image=$child_image.".jpg";
                            /*if(@getimagesize($child_image.".jpg"))
                                $child_image=$child_image.".jpg";
                            elseif(@getimagesize($child_image.".png"))
                                $child_image=$child_image.".png";*/
                        }
                        /*************/
                        // Get the StockId by combination id.
                        $stock_id_c = PrestashopUtil::GetStockId($product->associations->stock_availables->stock_available, $combination['id']);
                       // echo "<hr>";
                     //   print_r($combination['id']);
                        //////check if discount
                       if(isset($discounts[$product->id][$combination['id']]) && $discounts[$product->id][$combination['id']])
                           $discount=$discounts[$product->id][$combination['id']];
                       elseif(isset($discounts[$product->id][0]) && $discounts[$product->id][0])
                           $discount=$discounts[$product->id][0];
                       else
                           $discount=NULL;
                        ///
                        ///
                         $discounted_price=$discounted_from_date=$discounted_to_date="";
                         if((isset($combination['discounted_price']) && $combination['discounted_price'] && ($combination['discounted_price']!=$combination['price'])))
                         {
                             $discounted_price=$combination['discounted_price'];
                             $discounted_from_date=isset($discount['from']) ? $discount['from']:"";
                             $discounted_to_date=isset($discount['to']) ? $discount['to']:"";
                         }

                        self::$fetched_skus[]=trim($combination['sku']); // del those skus which are deleted from platform
                        $child_data = [
                            'parent_sku_id'=>$product_id ? $product_id:"",
                            'category_id' => $system_cat_id,
                            'sku' => $product->id,  // saving in channel_products table
                            'channel_sku' => $combination['sku'],  // sku for product table and channel_sku for channels products table
                            'variation_id'=>$combination['id'],
                            'name' => isset($product->name->language) ? $product->name->language : "",
                            'cost' => $combination['price'],
                            'image' => $child_image,
                            'rccp' => $combination['price'],
                            'ean' => $combination['ean'],
                            'stock_qty' =>$combination['quantity'],
                            'channel_id' => $channel->id,
                            'is_live' => $product->active,    // is active or is live
                            'brand' => (isset($product->manufacturer_name) && gettype($product->manufacturer_name) != 'object') ? strtolower($product->manufacturer_name) : NULL,     // brand,
                            //'marketplace'=>'prestashop',
                            'discounted_price'=>$discounted_price,
                            'discount_from_date'=>$discounted_from_date,
                            'discount_to_date'=>$discounted_to_date
                        ];
                     //   if($combination['sku']=='PM1-65110218_Black_45')
                       //   self::debug($child_data);

                        if($dimensions)
                            $child_data['dimensions']=json_encode($dimensions);

                        $c_product_id=CatalogUtil::saveProduct($child_data);  //save or update product and get product id
                        if($c_product_id)
                        {
                            $child_data['product_id'] = $c_product_id;
                            $channel_product_c= CatalogUtil::saveChannelProduct($child_data);  // save update channel product
                            if($stock_id_c)  // save this id it will be used for stock update in prestashop
                                self::update_general_reference_key('channels_products',$channel_product_c->id,'stock_available_id',$stock_id_c,$channel);
                        }

                    }
                }  // if combination
                /**************************************************/


            endforeach;
            ProductsUtil::remove_deleted_skus(self::$fetched_skus,$channel->id); // delete skus which are deleted from platform
        }

    }

    /*private static function manage_variation_discount($discount,$product)
    {

    }*/

    public static function update_general_reference_key($table,$table_pk_id,$key,$value,$channel)
    {
        $findStockId = GeneralReferenceKeys::findOne(['table_name'=>$table,'table_pk'=>$table_pk_id,'key'=>$key]);
        if (!$findStockId){
            $addStockId = new GeneralReferenceKeys();
            $addStockId->channel_id = $channel->id;
            $addStockId->table_name = $table;
            $addStockId->table_pk = $table_pk_id;
            $addStockId->key = $key;
            $addStockId->value = $value;
            $addStockId->added_at = date('Y-m-d H:i:s');
            $addStockId->save();
        }elseif($findStockId->value!=$value){
            $findStockId->value = $value;
            $findStockId->update();
        }
        return;
    }

    /***************************************Channelproducts**********************/

    public static function actionPullPrestashopProducts($channel,$filter=[])
    {
        $filter['filter[active]']=1;
        $presta_products = json_decode(PrestashopUtil::GetProducts($channel->id,/*[ 'filter[active]' => 1 ]*/$filter));
        //self::debug($presta_products);
        if (isset($presta_products->products->product)){
            $products = (gettype($presta_products->products->product)=='object') ? $presta_products->products : $presta_products->products->product;
        }else{
            $products = [];
        }

        $prd=[];
        $counter = 0;
        $cat=CatalogUtil::getdefaultParentCategory();
        $system_cat_id=isset($cat->id) ? $cat->id:0;
        if(strtolower($channel->name)=="pedro")
            $system_cat_id=NULL;
        //self::debug($products);
        foreach ($products as $key=>$value){

            // skip it if sku field is empty
            if ( is_object($value->reference) && json_encode($value->reference)=='{}' ){ // sku must be filled other wise skip it
                HelpUtil::SkuEmptyOnShopLog($channel->id,'Product Id : '.$value->id.' - Sku field is empty');
                continue;
            }

            $prd[$counter]['name'] = $value->name->language;
            $prd[$counter]['cost'] = $value->price;
            $prd[$counter]['rccp'] = $value->price;
            $prd[$counter]['is_active'] = $value->active;
            $prd[$counter]['brand'] = (isset($value->id_manufacturer) && $value->id_manufacturer!=0 && gettype($value->manufacturer_name)!='object') ? strtolower($value->manufacturer_name):NULL;
            $dimensions=""; // weight height width length
            if(isset($value->width) && $value->width && isset($value->height) && $value->height && isset($value->depth) && $value->depth)
            {
                    $dimensions=[
                        'width'=>round($value->width,2),
                        'height'=>round($value->height,2),
                        'length'=>round($value->depth,2),
                        'weight'=>round($value->weight,2),
                    ];
            }
            $prd[$counter]['dimensions'] =($dimensions) ? json_encode($dimensions):$dimensions;

            /*$presta_cat_detail = json_decode(PrestashopUtil::GetCategoryDetail($channel->id,['filter[id]'=>$value->id_category_default]));

            $cat_find_sys = Category::find()->where(['name'=>$presta_cat_detail->categories->category->name->language])->one();
            //self::debug($cat_find_sys);

            if ($cat_find_sys){
                $system_catid = $cat_find_sys->id;
            }else{
                $createCat = HelpUtil::CreateCategory($presta_cat_detail->categories->category->name->language,0);
                $system_catid = $createCat;
            }*/
            $main_image=NULL;
            if(isset($value->link_rewrite) && gettype($value->link_rewrite)=="object" && isset($value->id_default_image) && $value->id_default_image && is_string($value->id_default_image))
            {
                $main_image=$channel->api_url.$value->id_default_image."-home_default/".$value->link_rewrite->language.".jpg";
            }

            self::$fetched_skus[]=trim($value->reference); // del those skus which are deleted from platform

            $prd[$counter]['image'] =$main_image;
            $prd[$counter]['category_id'] =$system_cat_id;// isset($cat->id) ? $cat->id:0; //$system_catid;
            $prd[$counter]['sku'] = trim($value->reference);
            $prd[$counter]['channel_id'] = $channel->id;
            $prd[$counter]['presta_pid'] = $value->id;
            $prd[$counter]['channel_id'] = $channel->id;
            $prd[$counter]['quantity'] = (!isset($value->associations->combinations->combination)) ? $value->quantity : '';
            $prd[$counter]['presta_stock_available_id'] = (!isset($value->associations->combinations->combination)) ? $value->associations->stock_availables->stock_available->id : '';

            // if product has combination/variations
            if ( isset($value->associations->combinations->combination) ){

                $child_counter=0;
                foreach ( $value->associations->combinations->combination as $child_products ){

                    $child_pid = ( isset($child_products->id) ) ? $child_products->id : $child_products;
                    $Combination_detail = PrestashopUtil::CombinationDetail($channel->id,$child_pid);
                    $Combination_detail = json_decode($Combination_detail);
                    $child_image=NULL;
                    if(isset($Combination_detail->combination->associations->images->image) && $main_image)
                    {
                        if(is_array($Combination_detail->combination->associations->images->image))
                            $child_image=$channel->api_url.$Combination_detail->combination->associations->images->image[0]->id."-home_default/".$value->link_rewrite->language;
                        else
                           $child_image=$channel->api_url.$Combination_detail->combination->associations->images->image->id."-home_default/".$value->link_rewrite->language;

                        // now check if png exists or jpg exists on server
                        if(@getimagesize($child_image.".jpg"))
                            $child_image=$child_image.".jpg";
                        elseif(@getimagesize($child_image.".png"))
                            $child_image=$child_image.".png";
                        else
                            $child_image=NULL;
                    }
                    // Get the StockId by combination id.
                    $StockId = PrestashopUtil::GetStockId($value->associations->stock_availables->stock_available, $Combination_detail->combination->id);

                    if ( is_object($Combination_detail->combination->reference) && json_encode($Combination_detail->combination->reference)=='{}' ){ // sku must be filled other wise skip it
                        HelpUtil::SkuEmptyOnShopLog($channel->id,'Product Id : '.$value->id.' , Combination id : '.$Combination_detail->combination->id.' - Sku field is empty');
                        continue;
                    }

                    self::$fetched_skus[]=trim($Combination_detail->combination->reference); // del those skus which are deleted from platform
                    $prd[$counter]['childs'][$child_counter]['name'] = $value->name->language;
                    $prd[$counter]['childs'][$child_counter]['cost'] = $value->price;
                    $prd[$counter]['childs'][$child_counter]['rccp'] = $value->price;
                    $prd[$counter]['childs'][$child_counter]['is_active'] = $value->active;
                    $prd[$counter]['childs'][$child_counter]['category_id'] = $system_cat_id;//isset($cat->id) ? $cat->id:0;//$system_catid;
                    $prd[$counter]['childs'][$child_counter]['presta_variation_id'] = $Combination_detail->combination->id;
                    $prd[$counter]['childs'][$child_counter]['presta_stock_quantity'] = $Combination_detail->combination->quantity;
                    $prd[$counter]['childs'][$child_counter]['presta_stock_available_id'] = $StockId;
                    $prd[$counter]['childs'][$child_counter]['brand'] = (isset($value->id_manufacturer) &&  $value->id_manufacturer!=0 && gettype($value->manufacturer_name)!='object') ? strtolower($value->manufacturer_name):NULL;
                    $prd[$counter]['childs'][$child_counter]['image'] = $child_image;
                    if (gettype($Combination_detail->combination->ean13)=='object'){
                        $ean = '';
                    }else{
                        $ean = $Combination_detail->combination->ean13;
                    }
                    $prd[$counter]['childs'][$child_counter]['presta_ean'] = $ean;
                    $prd[$counter]['childs'][$child_counter]['sku'] = trim($Combination_detail->combination->reference);
                    $prd[$counter]['childs'][$child_counter]['channel_id'] = $channel->id;
                    /*$dimension_child="";
                    if(isset($value->width) && $value->width && isset($value->height) && $value->height && isset($value->depth) && $value->depth)
                    {
                        $dimension_child=[
                            'width'=>round($value->width,2),
                            'height'=>round($value->height,2),
                            'length'=>round($value->depth,2),
                            'weight'=>round($value->weight,2),
                        ];
                    }*/
                    $prd[$counter]['childs'][$child_counter]['dimensions'] = ($dimensions) ? json_encode($dimensions):$dimensions;
                    $child_counter++;
                }
            }
            $counter++;
        }
        //self::debug($prd);
        self::saveProducts($prd);
        ProductsUtil::remove_deleted_skus(self::$fetched_skus,$channel->id); // delete skus which are deleted from platform
      //  echo "<pre>";
       // print_r(self::$fetched_skus);
        return $presta_products;
    }

    public static function saveProducts($prd){

        foreach ($prd as $value){

            $findSku = Products::find()->where(['sku'=>$value['sku']])->one();
            if ( !$findSku ){
                $addProduct = new Products();
                $addProduct->sku = $value['sku'];
                $addProduct->name = $value['name'];
                $addProduct->cost = $value['cost'];
                $addProduct->is_active = $value['is_active'];
                $addProduct->rccp = $value['rccp'];
                $addProduct->image = isset($value['image'])? $value['image']:NULL;
                $addProduct->sub_category = $value['category_id'];
                $addProduct->created_at=time();
                $addProduct->updated_at = time();
                $addProduct->created_by = 1;
                $addProduct->updated_by = 1;
                $addProduct->brand = $value['brand'];
                if($value['dimensions']) // if dimensions is not empty
                {
                    $addProduct->dimensions = $value['dimensions'];
                }
                $addProduct->save();
                if (!empty($addProduct->errors))
                    self::debug($addProduct->errors);
                $ParentskuId = $addProduct['id'];
            }else{
                $ParentskuId = $findSku->id;
                $findSku->is_active = $value['is_active'];
                if($value['dimensions']) // if dimensions is not empty
                {
                    $findSku->dimensions = $value['dimensions'];
                }
                if(isset($value['image']) && $value['image'])
                    $findSku->image = $value['image'];

                $findSku->brand = $value['brand'];
                if($value['image'])
                  $findSku->image =$value['image'];
               // $findSku->sub_category =  $value['category_id'];
                $findSku->update(false);
            }
            $findChannelPrd = ChannelsProducts::findOne(['sku'=>trim($value['presta_pid']),'channel_id'=>$value['channel_id'],'channel_sku'=>$value['sku']]);
            if ( !$findChannelPrd ){
                $AddPrdChannels = new ChannelsProducts();
                $AddPrdChannels->sku = trim($value['presta_pid']);
                $AddPrdChannels->variation_id = '';
                $AddPrdChannels->channel_sku = $value['sku'];
                $AddPrdChannels->channel_id = $value['channel_id'];
                $AddPrdChannels->product_id = $ParentskuId;
                $AddPrdChannels->price = $value['cost'];
                $AddPrdChannels->stock_qty = $value['quantity'];
                $AddPrdChannels->last_update = date('Y-m-d H:i:s');
                $AddPrdChannels->product_name = $value['name'];

                if( isset($value['active']) ){
                    $is_active = $value['active'];
                }else if ( isset($value['is_active']) ){
                    $is_active = $value['is_active'];
                }else{
                    $is_active = 0;
                }

                $AddPrdChannels->is_live = $is_active;

                $AddPrdChannels->save();
                if (!empty($AddPrdChannels->errors))
                    self::debug($AddPrdChannels->errors);
            }
            else{
                // find stock id if available
                if ($value['presta_stock_available_id']!=''){
                    // find stock id
                    //self::debug($value);
                    $findStockId = GeneralReferenceKeys::findOne(['table_name'=>'channels_products','table_pk'=>$findChannelPrd->id,'key'=>'stock_available_id']);
                    //$findStockId = GeneralReferenceKeys::findOne(['table_pk'=>4711]);
                    if (!$findStockId){
                        $addStockId = new GeneralReferenceKeys();
                        $addStockId->channel_id = $findChannelPrd->channel_id;
                        $addStockId->table_name = 'channels_products';
                        $addStockId->table_pk = $findChannelPrd->id;
                        $addStockId->key = 'stock_available_id';
                        $addStockId->value = $value['presta_stock_available_id'];
                        $addStockId->added_at = date('Y-m-d H:i:s');
                        $addStockId->save();
                    }

                }
                //self::debug($value);
                if ($findChannelPrd){
                    $findChannelPrd->stock_qty = $value['quantity'];
                    $findChannelPrd->price = $value['cost'];
                    $findChannelPrd->update();
                }
            }
            if ( isset($value['childs']) ){
                //self::debug($value['childs']);
                foreach ( $value['childs'] as $child_detail ){

                    $findSku = Products::find()->where(['sku'=>trim($child_detail['sku'])])->one();
                    //$ParentskuId = '';
                    if (!$findSku){
                        $addChildProduct = new Products();
                        $addChildProduct->sku = $child_detail['sku'];
                        $addChildProduct->parent_sku_id = $ParentskuId;
                        $addChildProduct->name = $child_detail['name'];
                        $addChildProduct->is_active = $child_detail['is_active'];
                        $addChildProduct->cost = $child_detail['cost'];
                        $addChildProduct->image = isset($child_detail['image'])? $child_detail['image']:NULL;
                        $addChildProduct->ean = $child_detail['presta_ean'];
                        $addChildProduct->rccp = $child_detail['rccp'];
                        $addChildProduct->channel_id = $child_detail['channel_id'];
                        $addChildProduct->sub_category = $child_detail['category_id'];
                        $addChildProduct->created_at=time();
                        $addChildProduct->updated_at = time();
                        $addChildProduct->created_by = 1;
                        $addChildProduct->updated_by = 1;
                        $addChildProduct->brand = $child_detail['brand'];
                        if($child_detail['dimensions']) // if dimensions is not empty
                        {
                            $addChildProduct->dimensions = $child_detail['dimensions'];
                        }
                        $addChildProduct->save();
                        if (!empty($addChildProduct->errors))
                            self::debug($addChildProduct->errors);
                        $ChildSkuId = $addChildProduct['id'];
                    }else{
                        $ChildSkuId = $findSku->id;
                        $updateChildSku = Products::findOne($ChildSkuId);
                        $updateChildSku->parent_sku_id = $ParentskuId;
                        $updateChildSku->is_active=$child_detail['is_active'];
                        $updateChildSku->ean = $child_detail['presta_ean'];
                        $updateChildSku->brand = $child_detail['brand'];
                        if($child_detail['dimensions']) // if dimensions is not empty
                        {
                            $updateChildSku->dimensions = $child_detail['dimensions'];
                        }
                        if(isset($child_detail['image']) && $child_detail['image'])
                            $updateChildSku->image = isset($child_detail['image'])? $child_detail['image']:NULL;

                        //$updateChildSku->sub_category=$child_detail['category_id'];
                        $updateChildSku->parent_sku_id = $ParentskuId;
                        $updateChildSku->update(false);
                    }

                    $findChannelPrd = ChannelsProducts::findOne(['channel_sku'=>$child_detail['sku'],'channel_id'=>$child_detail['channel_id']]);
                    if ( !$findChannelPrd ){
                        $AddPrdChannels = new ChannelsProducts();
                        $AddPrdChannels->sku = $value['presta_pid'];
                        $AddPrdChannels->variation_id = $child_detail['presta_variation_id'];
                        $AddPrdChannels->channel_sku = $child_detail['sku'];
                        $AddPrdChannels->channel_id = $child_detail['channel_id'];
                        $AddPrdChannels->product_id = $ChildSkuId;
                        $AddPrdChannels->price = $child_detail['cost'];
                        $AddPrdChannels->ean = $child_detail['presta_ean'];
                        $AddPrdChannels->stock_qty = $child_detail['presta_stock_quantity'];
                        $AddPrdChannels->last_update = date('Y-m-d H:i:s');
                        $AddPrdChannels->product_name = $child_detail['name'];
                        $AddPrdChannels->is_live = (isset($value['active'])) ? $value['active'] : 1;
                        $AddPrdChannels->save();
                        if (!empty($AddPrdChannels->errors))
                            self::debug($AddPrdChannels->errors);

                        self::AddGeneralReferenceKey('channels_products', $AddPrdChannels['id'], 'stock_available_id', $value['channel_id'],
                            $child_detail['presta_stock_available_id']);

                        self::AddGeneralReferenceKey('channels_products', $AddPrdChannels['id'], 'ean13', $child_detail['channel_id'],
                            $child_detail['presta_ean']);
                    }
                    else{
                        $ChannelPrdId = $findChannelPrd->id;
                        $findChannelPrd->stock_qty = $child_detail['presta_stock_quantity'];
                        $findChannelPrd->price = $child_detail['cost'];
                        $findChannelPrd->ean = $child_detail['presta_ean'];
                        $findChannelPrd->last_update = date('Y-m-d H:i:s');
                        $findChannelPrd->update();

                        $StockAvailableId = GeneralReferenceKeys::findOne(['table_name'=>'channels_products','table_pk'=>$ChannelPrdId,
                            'key'=>'stock_available_id']);
                        if ( $StockAvailableId ){
                            $StockAvailableId->value = (string) $child_detail['presta_stock_available_id'];
                            $StockAvailableId->update();
                        }

                        $Ean13 = GeneralReferenceKeys::findOne(['table_name'=>'channels_products','table_pk'=>$ChannelPrdId,
                            'key'=>'ean13']);

                        if ( $Ean13 ){
                            $Ean13->value = (string) $child_detail['presta_ean'];
                            $StockAvailableId->update();
                        }
                    }

                }
            }

        }
    }

    /*
     * update tracking number on presta
     * if shipping status sent then update else not
     */
    public static function updateOrderTrackingAndShippingStatus($channel,$order,$courier,array $tracking_detail,$shipping_status=null)
    {
        //$tracking_detail; // tracking_number , total_charges , total_charges_incl_taxes,total_charges_excl_charges,weight
       $detail= self::updateOrderCarrierTracking($channel, $order->order_id, $tracking_detail['total_charges_excl_taxes'],$tracking_detail['total_charges_incl_taxes'],$tracking_detail['tracking_number'], $tracking_detail['weight']);
       if($detail && $shipping_status) // if shipping status sent then update in presta
            self::updateMarketplaceShippingStatus($channel,$shipping_status,$order->order_id);

       return ;
    }

    /*
     *  update shipping status on presta cms
     * channel object , shipping status , order_id of presta cms database
     */

    public static function updateMarketplaceShippingStatus($channel,$shipping_status,$channel_order_id)
    {
        $shipping_status=ucfirst(strtolower($shipping_status));
        $order_status_id=self::PrestaGetOrderStatusId($channel,$shipping_status); // get the id of order status from presta
        if($order_status_id)
           $response= PrestashopUtil::UpdateOrderStatus($channel->id,$order_status_id,$channel_order_id);
        if(isset($response->order))
                return ['status'=>'success','msg'=>'updated','data'];

    }
    public static function SetOrderStatus($channelId, $channel_order_id, $status){

        $Channel = Channels::find()->where(['id'=>$channelId])->one();
        $PrestaShippedId = PrestashopUtil::PrestaGetOrderStatusId($Channel,$status);
        $current_order_status=self::getOrderCurrentStatus($Channel,$channel_order_id);
        if($PrestaShippedId==$current_order_status){  // if status sending and current order status same
            return "Status same";
        }
        //echo $channel_order_id;die;
        $UpdateOrderStatus = PrestashopUtil::UpdateOrderStatus($Channel->id,$PrestaShippedId,$channel_order_id);
        $UpdateOrderStatus = json_encode($UpdateOrderStatus);
        $UpdateOrderStatus = json_decode($UpdateOrderStatus);
        //self::debug($UpdateOrderStatus);
        if ( isset($UpdateOrderStatus->order->id) ){
            $ShopUpdated[] = 'OrderStatus Successfully Updated To '.$Channel->name;
        }else{
            $ShopUpdated[] = 'Something went wrong when updating OrderStatus To '.$Channel->name;
        }
        return $ShopUpdated;
    }
    public static function UpdateTrackingInfo( $channelId, $channel_order_id, $TrackingNumber, $ChargesWithTaxes, $ChargesWithoutTaxes, $TotalWeight ){

        $Channel = Channels::find()->where(['id'=>$channelId])->one();

        $ShopUpdated = [];

        $UpdateShippingDetails = PrestashopUtil::updateOrderCarrierTracking($Channel,$channel_order_id,$ChargesWithoutTaxes,
            $ChargesWithTaxes,$TrackingNumber,$TotalWeight); // update tracking number only

        $UpdateTrackingNumber = json_encode($UpdateShippingDetails);
        $UpdateTrackingNumber = json_decode($UpdateTrackingNumber);
        if ( isset($UpdateTrackingNumber->order_carrier->id) ){
            $ShopUpdated[] = 'TrackingNumber And Other Details Successfully Updated To '.$Channel->name;
        }else{
            $ShopUpdated[] = 'Something went wrong when updating TrackingNumber to '.$Channel->name;
        }
        return $ShopUpdated;
    }
    public static function MarkOrderShipAndUpdateShipmentDetail($CreateShipment,$CarrierName, $Channel, $channel_order_id, $Weight)
    {

        $ShopUpdated = [];
        if ( $CarrierName=='FedEx' ){
            $TrackingNumber = $CreateShipment->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
            $ShippingRatesIncTaxes = $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetChargeWithDutiesAndTaxes->Amount;
            $ShippingRatesExlTaxes = $CreateShipment->CompletedShipmentDetail->ShipmentRating->ShipmentRateDetails->TotalNetFedExCharge->Amount;
        }


        $UpdateShippingDetails = PrestashopUtil::updateOrderCarrierTracking($Channel,$channel_order_id,$ShippingRatesExlTaxes,
            $ShippingRatesIncTaxes,$TrackingNumber,array_sum($Weight)); // update tracking number only

        $UpdateTrackingNumber = json_encode($UpdateShippingDetails);
        $UpdateTrackingNumber = json_decode($UpdateTrackingNumber);
        if ( isset($UpdateTrackingNumber->order_carrier->id) ){
            $ShopUpdated[] = 'TrackingNumber And Other Details Successfully Updated To '.$Channel->name;
        }else{
            $ShopUpdated[] = 'Something went wrong when updating TrackingNumber to '.$Channel->name;
        }

        $PrestaShippedId = PrestashopUtil::PrestaGetOrderStatusId($Channel,'Shipped');
        $UpdateOrderStatus = PrestashopUtil::UpdateOrderStatus($Channel->id,$PrestaShippedId,$channel_order_id);
        $UpdateOrderStatus = json_encode($UpdateOrderStatus);
        $UpdateOrderStatus = json_decode($UpdateOrderStatus);
        if ( isset($UpdateOrderStatus->order->id) ){
            $ShopUpdated[] = 'OrderStatus Successfully Updated To '.$Channel->name;
        }else{
            $ShopUpdated[] = 'Something went wrong when updating OrderStatus To '.$Channel->name;
        }
        return $ShopUpdated;
    }
    public static function AddGeneralReferenceKey($Table_Name,$Table_PK,$Key,$Channel_id,$Value){
        $AddStockAvailablefield = new GeneralReferenceKeys();
        $AddStockAvailablefield->table_name = $Table_Name;
        $AddStockAvailablefield->table_pk = $Table_PK;
        $AddStockAvailablefield->key = $Key;
        $AddStockAvailablefield->channel_id = $Channel_id;
        $AddStockAvailablefield->value = (string) $Value;
        $AddStockAvailablefield->added_at = date('Y-m-d H:i:s');
        $AddStockAvailablefield->save();
        if (!empty($AddStockAvailablefield->errors))
            self::debug($AddStockAvailablefield->errors);
        else
            return $AddStockAvailablefield;
    }



    /***
     * update shipment and tracking number
     */
    public static function updateShipmentAndTracking($channel,$orders)
    {
        //echo self::getOrderCurrentStatus($channel,246); die();
        $response=[];
        foreach($orders as $order_pk_id=>$order)
        {
                 /*****update tracking******/
                $response[$order_pk_id]=self::updateTrackingProcess($channel,$order);
                /*****update status******/
                $response[$order_pk_id]=self::updateOrderStatusProcess($channel, $order['marketplace_order_id'], $order['shipping_status_to_send']);
        }
        return $response;
    }

    private static function updateOrderStatusProcess($channel, $channel_order_id, $status)
    {

        $PrestaShippedId = PrestashopUtil::PrestaGetOrderStatusId($channel,$status);
        $current_order_status=self::getOrderCurrentStatus($channel,$channel_order_id);
        if($PrestaShippedId==$current_order_status){  // if status sending and current order status same
            return "Status same";
        }
        //echo $channel_order_id;die;
        $UpdateOrderStatus = PrestashopUtil::UpdateOrderStatus($channel->id,$PrestaShippedId,$channel_order_id);
        $UpdateOrderStatus = json_encode($UpdateOrderStatus);
        $UpdateOrderStatus = json_decode($UpdateOrderStatus);
        //self::debug($UpdateOrderStatus);
        if ( isset($UpdateOrderStatus->order->id) ){
            return  'OrderStatus Successfully Updated To '.$channel->name;
        }
        return 'Something went wrong when updating OrderStatus To '.$channel->name;
    }

    private static function updateTrackingProcess($channel,$order)
    {
        $response=[];
        if($order['order_shipping_in']=="partial_order_same_tracking"){ // shipping few items in order with same tracking
            /// add new tracking number
            $response[]=  self::addOrderCarrierTracking($channel,$order['marketplace_order_id'],null,null,$order['unique_tracking_numbers'][0],null);
        }
        if($order['order_shipping_in']=="partial_order_different_tracking"){ // shipping few items in order with diff tracking
            /// add new tracking number
            foreach($order['unique_tracking_numbers'] as $tracking_number){
                $response[]=  self::addOrderCarrierTracking($channel,$order['marketplace_order_id'],null,null,$tracking_number,null);
            }

        }
        if($order['order_shipping_in']=="whole_order_same_tracking"){  // shipping whole order with same tracking number
            ///update tracking number
            self::updateOrderCarrierTracking($channel,$order['marketplace_order_id'],null,null,$order['unique_tracking_numbers'][0],null);
        }
        if($order['order_shipping_in']=="whole_order_different_tracking"){  // shipping whole order with diff tracking number
            ///add tracking number
            foreach($order['unique_tracking_numbers'] as $tracking_number){
                $response[]=   self::addOrderCarrierTracking($channel,$order['marketplace_order_id'],null,null,$tracking_number,null);
            }
        }
        return $response;
    }

    /**
     * update order shipment local databse if tracking updated , called from croncontroller
     */
    public static function updateOrderShipmentLocalDb($orders,$api_response,$tracking_update=true)
    {
        foreach($orders as $order_pk_id=>$order){
            foreach($order['items'] as $order_item)
            {
                $updateTracking = OrderShipment::findOne($order_item['order_shipment_id']);
                if($tracking_update){
                    $updateTracking->is_tracking_updated = 1;
                }

                $updateTracking->marketplace_tracking_response=isset($api_response[$order_pk_id]) ? json_encode($api_response[$order_pk_id]):null;
                $updateTracking->update();
            }
        }
        return;
    }

    public static function PrestaGetOrderStatusId($channel,$status){

        $statesListPrestashop = PrestashopUtil::getStatesList($channel);
        $statesListPrestashop = array_flip($statesListPrestashop);
        if ( isset($statesListPrestashop[$status]) ){ // check the requested status if it's available
            return $statesListPrestashop[$status];
        }
        else if ( isset($statesListPrestashop['Shipped']) ) // default status shipped if status not matched
        {
            return $statesListPrestashop['Shipped'];
        }
        else // if shipped also not matched then return null
        {
            return '';
        }
    }
    public static function GetCustomerGroups($channelId){
        self::GetChannelApiByChannelId($channelId);
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        $opt = array('resource' => 'groups');
        $opt['display']= 'full';
        $resources = self::makeApiCall($channel,$opt);
        $resources = json_encode($resources);
        $resources = json_decode($resources);
        return $resources;
    }
    public static function GetB2bCustomerGroups($channelId){

        $customerGroups = self::GetCustomerGroups($channelId);
        $b2bGroups = [];
        foreach ( $customerGroups->data->groups->group as $detail ){
            if ( $detail->name->language=='B2B' ){
                $b2bGroups[] = $detail->id;
            }
        }
        return $b2bGroups;
    }
    public static function GetB2cCustomerGroups($channelId){
        // it will return all groups except b2b
        $customerGroups = self::GetCustomerGroups($channelId);
        $b2cGroups=[];
        foreach ( $customerGroups->data->groups->group as $detail ){
            if ( $detail->name->language!='B2B' ){
                $b2cGroups[] = $detail->id;
            }
        }
        return $b2cGroups;
    }
    public static function GetPriceOfProduct($channelId, $productId, $combinationId){
        $pprice=0;
        if ( $combinationId!=0 ){
            $pdetail = self::GetProductCombinationDetail($channelId,$combinationId);
            $product_detail = self::GetProductDetail($channelId,$productId);
            $pdetail = json_decode($pdetail);
            $product_detail=json_decode($product_detail);
            if ( isset($pdetail->combination->price) && $pdetail->combination->price>0 ){
                $pprice=$pdetail->combination->price;
                $pprice += $product_detail->product->price;
            }
            else{
                if ( isset($product_detail->product->price) ){
                    $pprice=$product_detail->product->price;
                }
            }
        }else{
            $product_detail = self::GetProductDetail($channelId,$productId);
            $product_detail=json_decode($product_detail);
            if ( isset($product_detail->product->price) ){
                $pprice=$product_detail->product->price;
            }
        }
        return $pprice;
    }
    public static function CreateSpecificPrice( $channelId, $productId, $combinationId=0, $customerGroupId, $dates, $dealPrice, $discount_type, $discount ){


        // get the product or combination price.
        $pprice = self::GetPriceOfProduct($channelId,$productId,$combinationId);
      //  self::debug($pprice);
        /*echo '<br />';
        echo $productId;
        echo '<br />';
        echo $combinationId;
        echo '<br />';
        echo $pprice;
        die;*/
        $dealPrice = ((double)$pprice - (double)$dealPrice);
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        // get prestashop shop id
        if ($channel->auth_params==NULL){
            echo 'Error <br />';
            echo $channel->name.' channel column auth_params is NULL. Please set the value and then try again';
            die;
        }
        $prestashopShopId = json_decode($channel->auth_params,true);
        $prestashopShopId = $prestashopShopId['prestashop_shop_id'];

        $response=[];
      //  die('come22');
        try{

            $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
            $opt = array('resource' => 'specific_prices');
            $xml = $webservice->get(array('url' => self::$api_url . 'api/specific_prices?schema=synopsis'));
            $resources = $xml->children()->children();
            $resources->id_shop = $prestashopShopId;
            $resources->id_cart = 0;
            $resources->id_product =$productId;
            $resources->id_product_attribute=$combinationId;
            $resources->id_currency='0';
            $resources->id_country ='0';
            $resources->id_group =$customerGroupId;
            $resources->id_customer='0';
            $resources->price='-1';
            $resources->from_quantity = self::_FROM_QUANTITY;
            if ($discount_type=='Percentage'){
                $resources->reduction = ($discount/100);
                $resources->reduction_type = 'percentage';
            }else{
                $resources->reduction=$dealPrice;
                $resources->reduction_type = 'amount';
            }
            $resources->reduction_tax='0';
            $resources->from=date('Y-m-d' , strtotime($dates['from']));
            $resources->to=date('Y-m-d', strtotime('+1 day',strtotime($dates['to'])));
            $opt['postXml'] = $xml->asXML();
            $result=json_encode($webservice->add($opt));
            $result= json_decode($result);
           // self::debug($result);
            $response['status']=1;
            $response['specific_price_id']=$result->specific_price->id;
            $response['msg']='Discount succesfuly created';

        }catch (\PrestaShopWebserviceException $e){
            if ($e->getCode()==0){
                $response['status']=0;
                $response['msg']='Cannot create discount, Maybe there is already discount created between the same dates.';
            }
        }

        return $response;
    }
    public static function DeleteSpecifcPrice($channelId,$prestashopDiscountId){
        self::GetChannelApiByChannelId($channelId);
        $response = [];
        try{
            $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
            $opt = array('resource' => 'specific_prices');
            $opt['id'] = $prestashopDiscountId;
            $webservice->delete($opt);
            $response['status']='Success';
            $response['msg']='Successfuly Deleted';
        }catch (\PrestaShopWebserviceException $e){
            $response['status']='Failed';
            $response['msg']='Record was not found may be it was already deleted';

        }
        return $response;
    }
    public static function GetSpecificPrices( $channelId, $prestaProductId ){
        self::GetChannelApiByChannelId($channelId);
        $channel = Channels::find()->where(['id'=>'16'])->one();
        $opt = array('resource' => 'specific_prices');
        $opt['display']= 'full';
        $opt['filter[id_product]'] = '['.$prestaProductId.']';
        $resources = self::makeApiCall($channel,$opt);
        return $resources;
    }

    /**arrange order for shipment , called from cron controller**/
    public static function arrangeOrderForShipment($item_list)
    {
        $order=[];
        $items=[];
        foreach($item_list as $item){
            $items[$item['order_id_PK']][]=$item;
            $order[$item['order_id_PK']]=[
                'marketplace_order_id'=>$item['channel_order_id'],
                'items'=>$items[$item['order_id_PK']]
            ];
        }
        return $order;
    }

    /***
     * @param $orders check the items we are shipping is complete order or partial
     * called from cron
     */
    public function OrderShipmentCompleteOrPartial($orders)
    {
        foreach($orders as $index=>&$order)
        {
            $genuine_order_items=OrderItems::find()->select(['item_sku','id'])->where(['order_id'=>$index])->asArray()->all(); // get items of orders
            $genuine_order_skus=array_column($genuine_order_items,'item_sku');  // item skus in genuine order
            $genuine_order_item_ids=array_column($genuine_order_items,'id');  // item ids in genuine order
            $shipping_order_skus=array_column($order['items'],'item_sku'); // items sku in order that is ready for shipping
            $shipping_order_item_ids=array_column($order['items'],'order_item_id_PK'); // item ids of order_item tabe in order that is ready for shipping
            $tracking_numbers=array_column($order['items'],'tracking_number');
            $unique_tracking_numbers=array_unique($tracking_numbers);
            $tracking_number_status=(count($unique_tracking_numbers) === 1 ) ? "same":"different";  // check if tracking numbers in order items are same for all or different
           // self::debug($tracking_numbers);

            /*echo "<pre>";
            print_r($genuine_order_item_ids);
            echo "<hr/>";
            print_r($shipping_order_item_ids);
            die();*/
            if(count($genuine_order_skus)==count($shipping_order_skus)) { /**if whole order shipping**/
               $order_shipping_in="whole_order_".$tracking_number_status."_tracking";
               $shipping_status_to_send='Shipped';
            } else { // not shipping all items in order // or shipping few items of particular order
                $order_shipping_in="partial_order_".$tracking_number_status."_tracking";
                $missing_item_status_completed=self::is_status_of_missing_item_completed($genuine_order_item_ids,$shipping_order_item_ids); // check if missing item is also shipped or cmpleted then overall status to be shipped
                if($missing_item_status_completed)
                    $shipping_status_to_send='Shipped';
                else
                    $shipping_status_to_send='Partially Shipped';
            }
            $order['order_shipping_in']=$order_shipping_in;
            $order['shipping_status_to_send']=$shipping_status_to_send;
            $order['unique_tracking_numbers']=$unique_tracking_numbers;

        }
        return $orders;

    }

    /***
     *
     */
    private static function is_status_of_missing_item_completed($genuine_order_items,$shipping_items)
    {
        $missing_item=array_diff($genuine_order_items,$shipping_items);  // get  item which is present in order but not shiping currently
        foreach($missing_item as $index=>$item_id)
        {
                $status_genuine=OrderItems::find()->select('item_status')->where(['id'=>$item_id])->scalar();
                $status_shipment=OrderShipment::find()->select('system_shipping_status')->where(['order_item_id'=>$item_id])->scalar();
                if($status_shipment){
                    $status_genuine=$status_shipment;
                }
                if(!in_array(strtolower($status_genuine),['shipped','completed','delivered','complete'])){
                    return false;
                }
        }
        return true;
    }

    /*****
     * for status update to marketplace when courier status changes
     * after courier status changes check status of remaining items and
     */
    public static function check_remaining_order_items_status($items,$courier_status)
    {
        //first check if full order was shipped or half
        $genuine_order_items=OrderItems::find()->select(['item_sku','id'])->where(['order_id'=>$items['order_pk_id']])->asArray()->all();
        $genuine_order_item_ids=array_column($genuine_order_items,'id');  // item ids in genuine order
        $shipped_item_ids=explode(',',$items['order_item_ids']);
        if(count($genuine_order_item_ids)==count($shipped_item_ids)) { /**if whole order shipping**/
            return $courier_status;
        } elseif(strtolower($courier_status)=="delivered") {

            $missing_item_status_completed=self::is_status_of_missing_item_delivered($genuine_order_item_ids,$shipped_item_ids); // check if missing item is also  cmpleted then overall status to be delivered
            if($missing_item_status_completed)
                return $courier_status;
            else
                return 'Partially Shipped';
        } elseif(strtolower($courier_status)=="canceled" || strtolower($courier_status)=="refunded") {

            $missing_item_status_completed=self::is_status_of_missing_item_canceled($genuine_order_item_ids,$shipped_item_ids); // check if missing item is also  cmpleted then overall status to be delivered
            if($missing_item_status_completed)
                return $courier_status;
            else
                return 'Partially Shipped';
        }
        return false;
    }

    private static function is_status_of_missing_item_delivered($genuine_order_items,$shipping_items)
    {
        $missing_item=array_diff($genuine_order_items,$shipping_items);  // get  item which is present in order but not shiping currently
        foreach($missing_item as $index=>$item_id)
        {
            $status_genuine=OrderItems::find()->select('item_status')->where(['id'=>$item_id])->scalar();
            $status_shipment=OrderShipment::find()->select('system_shipping_status')->where(['order_item_id'=>$item_id])->scalar();
            if($status_shipment){
                $status_genuine=$status_shipment;
            }
            if(!in_array(strtolower($status_genuine),['completed','delivered','complete'])){
                return false;
            }
        }
        return true;
    }

    private static function is_status_of_missing_item_canceled($genuine_order_items,$shipping_items)
    {
        $missing_item=array_diff($genuine_order_items,$shipping_items);  // get  item which is present in order but not shiping currently
        foreach($missing_item as $index=>$item_id)
        {
            $status_genuine=OrderItems::find()->select('item_status')->where(['id'=>$item_id])->scalar();
            $status_shipment=OrderShipment::find()->select('system_shipping_status')->where(['order_item_id'=>$item_id])->scalar();
            if($status_shipment){
                $status_genuine=$status_shipment;
            }
            if(!in_array(strtolower($status_genuine),['canceled','cancelled','refunded','refund','returned','return'])){
                return false;
            }
        }
        return true;
    }
    /******************************************************get parent products********************************/
    public static function get_all_parent_products($channel,$filters=[])
    {
        self::GetChannelApiByChannelId($channel->id);
        if(self::$api_url && self::$api_key)
        {
            $params="";
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            if($filters && is_array($filters))
            {
                foreach($filters as $key=>$filter)
                    $params .="&".$key."=".$filter;
            }
            //print_r($params);
            //die();
            $xml = $webService->get(array('url' => self::$api_url . '/api/products?display=full&price[discounted_price][use_reduction]=1'.$params));
            $products = $xml->children()->children();
            $xml   = json_encode($products);
            return json_decode($xml);
        }
        return false;
    }
    /******************************************************get combinations********************************/
    public static function get_all_combinations($channel,$filters=[])
    {
        self::GetChannelApiByChannelId($channel->id);
        if(self::$api_url && self::$api_key)
        {
            $params="";
            if($filters && is_array($filters))
            {
                foreach($filters as $key=>$filter)
                    $params .="&".$key."=".$filter;
            }
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $xml = $webService->get(array('url' => self::$api_url . '/api/combinations?display=full&price[real_price][only_reduction]=1&price[disc_price][use_reduction]=1'.$params));
            $products = $xml->children()->children();
            $xml   = json_encode($products);
            return json_decode($xml);
        }
        return false;
    }

    /*****
     * parentwise arrange combinations
     */
    public static function rearrange_combinations($combinations)
    {
       // self::debug($combinations);
        $result=[];
        if($combinations)
        {
            foreach ($combinations as $combination){
                if(!isset($combination->id_product))
                    continue;

                $result[$combination->id_product][]=[
                    'id'=>$combination->id,
                    'sku'=>$combination->reference,
                    'api_price'=>$combination->price,
                    'reduction_price'=>$combination->real_price, // giving reduction amount , how much discount or reduction applied on original price
                    'discounted_price'=>$combination->disc_price   // discounted price
                ];
            }
        }
        return $result;
    }

    public static function assign_combination_to_parent($parent_products,$combinations)
    {
        $result=[];
        foreach($parent_products as $product)
        {
            if(!$product->state) // if state 0 mean deleted
                continue;

            $result[]=[
                        'id'=>$product->id,
                        'sku'=>$product->reference,
                        'default_cat'=>$product->id_category_default,
                        'price'=>$product->price,
                        'discounted_price'=>$product->discounted_price,
                        'categories'=>$product->associations->categories->category,
                        'combinations'=>isset($combinations[$product->id]) ? $combinations[$product->id]:[],
            ];
        }
        return $result;
    }

    public static function get_all_categories()
    {

    }
    public static function cat_heirarchy()
    {
        $cat['women']=[
            '11'=>['id'=>11,'name'=>'women','child_ids'=>[]],
            '16'=>['id'=>16,'name'=>'shoes','child_ids'=>[47,48,49,50,51,52,53,54,55]],
            '17'=>['id'=>17,'name'=>'bags','child_ids'=>[57,58,59,60,61,62,63,64,65]],
            '18'=>['id'=>18,'name'=>'accessories','child_ids'=>[67,68,69,70,71,72]],
            '73'=>['id'=>73,'name'=>'new arrivals','child_ids'=>[74,75,76,77,78,79,80]],
            ];
        $cat['men']=[
            //'id'=>12,
            '12'=>['id'=>12,'name'=>'men','child_ids'=>[]],
            '19'=>['id'=>19,'name'=>'shoes','child_ids'=>[23,24,25,41,42,43,44,45]],
            '20'=>['id'=>20,'name'=>'bags','child_ids'=>[27,28,29,37,38,39,40]],
            '21'=>['id'=>21,'name'=>'accessories','child_ids'=>[31,32,33,34,35,36]],
            '81'=>['id'=>81,'name'=>'new arrivals','child_ids'=>[82,83,84,85,86]],
        ];
        $cat['sale']=['id'=>15];
        $cat['sale']['women']=[
          //  'id'=>102,
            'women'=>['id'=>102,'child_ids'=>[]],
            'shoes'=>['id'=>104,'child_ids'=>[]],
            'bags'=>['id'=>105,'child_ids'=>[]],
            'accessories'=>['id'=>106,'child_ids'=>[]],
        ];
        $cat['sale']['men']=[
            //'id'=>103,
            'men'=>['id'=>103,'child_ids'=>[]],
            'shoes'=>['id'=>108,'child_ids'=>[]],
            'bags'=>['id'=>109,'child_ids'=>[]],
            'accessories'=>['id'=>110,'child_ids'=>[]],
            ];
        return $cat;
    }

    public static function decide_cat_algo_for_sale($product)
    {
        $categories=self::cat_heirarchy();
        $cat_already_assigned=[];
        $cat_to_add=[];
        if(isset($product['categories']) && $product['categories'])
        {
            foreach($product['categories'] as $cat)
            {
                $cat_already_assigned[$cat->id]=$cat->id;
            }
            /***********/
            foreach($product['categories'] as $cat)
            {
                /**********check if this product  in women cat*******/
                foreach($categories['women'] as $index=>$val){
                    if($cat->id==$index || in_array($cat->id,$val['child_ids']))
                    {
                        if(!isset($cat_already_assigned[$categories['sale']['id']]))
                         $cat_to_add[]=$categories['sale']['id'];

                        if(!isset($cat_already_assigned[$categories['sale']['women']['women']['id']]))
                             $cat_to_add[]=$categories['sale']['women']['women']['id'];

                        if($val['name']=="new arrivals")  // because in sale no new arrivals dropdown
                            continue;

                        if(!isset($cat_already_assigned[$categories['sale']['women'][$val['name']]['id']]))
                         $cat_to_add[]=$categories['sale']['women'][$val['name']]['id'];
                    }
                }
                /**********check if this in men*********/
                foreach($categories['men'] as $index=>$val){
                    if($cat->id==$index || in_array($cat->id,$val['child_ids']))
                    {
                        if(!isset($cat_already_assigned[$categories['sale']['id']]))
                            $cat_to_add[]=$categories['sale']['id'];

                        if(!isset($cat_already_assigned[$categories['sale']['men']['men']['id']]))
                            $cat_to_add[]=$categories['sale']['men']['men']['id'];

                        if($val['name']=="new arrivals") // because in sale no new arrivals dropdown
                            continue;

                        if(!isset($cat_already_assigned[$categories['sale']['men'][$val['name']]['id']]))
                            $cat_to_add[]=$categories['sale']['men'][$val['name']]['id'];
                    }
                }
            }

        }
        return array_unique($cat_to_add);


    }

    public static function decide_cat_algo_for_non_sale($product)
    {
        $categories=self::cat_heirarchy();
        $cat_to_remove=[];
        if(isset($product['categories']) && $product['categories'])
        {
            /***********/
            foreach($product['categories'] as $cat)
            {
               /* if(!isset($cat->id)){
                    continue;
                }*/
                if($cat->id==$categories['sale']['id'])
                    $cat_to_remove[]=$cat->id;

                /**********check if this product  in women cat*******/
                foreach($categories['sale']['women'] as $index=>$val){
                    if($cat->id==$val['id'] || in_array($cat->id,$val['child_ids']))
                    {
                        $cat_to_remove[]=$cat->id;
                    }
                }
                /**********check if this in men*********/
                foreach($categories['sale']['men'] as $index=>$val){
                    if($cat->id==$val['id'] || in_array($cat->id,$val['child_ids']))
                    {
                        $cat_to_remove[]=$cat->id;
                    }
                }
            }

        }
        return array_unique($cat_to_remove);


    }

    public static function decide_and_assign_category($products)
    {
        $response=['sales_cat_assigned'=>[],'sales_cat_removed'=>[]];
        foreach($products as $product)
        {
            $is_discounted_product=self::is_product_has_discount($product); // check if product has discount
            if($is_discounted_product)  // if discounted product then check if category of sale assigned
            {
                $cats_to_assign=self::decide_cat_algo_for_sale($product);
                if($cats_to_assign)  // if we have to add new sale category to product
                {
                    $response['sales_cat_assigned'][]= self::auto_assign_category('add',$cats_to_assign,$product['id']);
                }

            }
            else // if not discounted product then make sure  category of sale is not  assigned
            {
                $cats_to_remove=self::decide_cat_algo_for_non_sale($product);
                if($cats_to_remove){
                    $response['sales_cat_removed'][]= self::auto_assign_category('remove',$cats_to_remove,$product['id']);
                }

            }


        }
        return $response;
    }


    public static function is_product_has_discount($product)
    {
        if($product['discounted_price']!=$product['price'] && $product['discounted_price'] && $product['discounted_price'] < $product['price'])
        {
            return true;
        }
        else // check combination if any child have discount
        {
            foreach($product['combinations'] as $child){
                if(($child['reduction_price'] && $child['reduction_price'] > 0) || $child['api_price'] < 0)
                    return true;
            }
        }
        return false;
    }
    /****************************************************************************************************************/
    /***********************************assign sales category to products having special prices***********************/

    public static function re_manage_sale_category($channel)
    {
        $parent_products=self::get_all_parent_products($channel);
        //self::debug($parent_products);
        $combinations=self::get_all_combinations($channel);
        //self::debug($combinations);
        $combinations=self::rearrange_combinations($combinations->combination);
        $products=self::assign_combination_to_parent($parent_products->product,$combinations);
        //self::debug($products);
        $response=self::decide_and_assign_category($products); // check if have to assign sale categories or have to remve saale categry
        return $response;
    }

    public static function auto_assign_category($type="add",$cat_list,$product_id)
    {
        if(self::$api_url && self::$api_key)
        {
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'products','id'=>$product_id);
                $xml = $webservice->get($opt);
                $products = $xml->children()->children();
                //self::debug($products);
                if($type=="add")
                {
                    foreach($products->associations->categories->category as $category){
                        $cat_list[]=(int)$category->id;
                    }
                }
                elseif($type=="remove")
                {
                    $new_list=[];
                    foreach($products->associations->categories->category as $category){
                        if(!in_array($category->id,$cat_list)){
                            $new_list[]=(int)$category->id;
                        }
                    }
                    $cat_list=$new_list;
                }else{
                    return $product_id." unknown action";
                }
                unset($products->manufacturer_name);
                unset($products->quantity);
                unset($products->associations->categories->category);

// Create new categories
                unset($products->associations->categories);

// Create new categories

                $categories = $products->associations->addChild('categories');
                $cat_list=array_unique($cat_list);
                 foreach ($cat_list as $cat_id) {
                     $category = $categories->addChild('category');
                    $category->addChild('id', $cat_id);
                }
              //  self::debug( $categories);
                //$opt['putXml'] = $xml->asXML();
                //$xml = $webservice->edit($opt);
                // self::debug($xml);
                $xml_response = $webservice->edit(array('resource' => 'products', 'id' => $product_id, 'putXml' => $xml->asXML()));
              return $product_id ." cat_updated";
            }catch ( \PrestaShopWebserviceException $ex ){
                return  $product_id.'error '.$ex->getMessage();
            }
        }else{
            return "ChannelDeactivated";
        }

    }

    public static function get_image($channelId)
    {
        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!="") {
            $webService = new \PrestaShopWebservice(self::$api_url, self::$api_key, self::$debugMode);
            $xml = $webService->get(array('url' => self::$api_url . 'api/images/products/323/2006'));
            self::debug($xml);
            $products = $xml->children()->children();
            $xml   = json_encode($products);
            return json_decode($xml);
        }else{
            return "ChannelDeactivated";
        }
    }
    /************************set product as new***********************/
    public static function set_product_as_new($channel=null,$sku)
    {
        self::GetChannelApiByChannelId($channel->id);
        try
        {
        $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);
        $opt = array('resource' => 'products','id'=>'187');
        $xml = $webservice->get($opt);
        $products = $xml->children()->children();
          //  self::debug($products);
            if($sku['action']=='remove')
              $products->date_add=date('Y-m-d H:i:s',strtotime('-3 months'));
            else
                $products->date_add=$sku['set_new_from'];

        unset($products->quantity);
        unset($products->manufacturer_name);
        //self::debug($products);
        $opt['putXml'] = $xml->asXML();
        $res = $webservice->edit($opt);
        if(isset($res->product))
             return ['status'=>'success','msg'=>'updated','error'=>0,'updated'=>1];

        }catch ( \PrestaShopWebserviceException $ex ){
            return ['status'=>'failure','msg'=>$ex->getMessage(),'error'=>1,'updated'=>0];
        }
        return ['status'=>'failure','msg'=>'Failed to update','error'=>1,'updated'=>0];
    }
    /*public static function assign_cat($channelId)
    {


        self::GetChannelApiByChannelId($channelId);
        if(self::$api_url!="" && self::$api_key!=""){
            try {
                $webservice = new \PrestaShopWebservice(self::$api_url,self::$api_key,self::$debugMode);

                $opt = array('resource' => 'products','id'=>172,'output_format'=>'JSON');
                $xml = $webservice->get($opt);
                $products = $xml->children()->children();
                foreach($products->associations->categories->category as $category){
                    echo $category->id;
                    echo "<br/>";
                }
                self::debug($products->associations->categories);
                //$categories = $products->associations->addChild('categories');
                unset($products->manufacturer_name);
                unset($products->quantity);
                unset($products->associations->categories->category);

// Create new categories
                unset($products->associations->categories);

// Create new categories
                $categories = $products->associations->addChild('categories');

               // for ($i=0;$i<4;$i++) {
                    $category = $categories->addChild('category');
                    $category->addChild('id', 12);
                    $category = $categories->addChild('category');
                    $category->addChild('id', 9);
                    $category = $categories->addChild('category');
                    $category->addChild('id', 15);
                    self::debug( $categories);
                //}
                //$opt['putXml'] = $xml->asXML();
                //$xml = $webservice->edit($opt);
               // self::debug($xml);
                $xml_response = $webservice->edit(array('resource' => 'products', 'id' => 172, 'putXml' => $xml->asXML()));
                self::debug($xml_response);
            }catch ( \PrestaShopWebserviceException $ex ){
                echo 'error '.$ex->getMessage(); die();
            }
        }else{
            return "ChannelDeactivated";
        }

    }*/



}