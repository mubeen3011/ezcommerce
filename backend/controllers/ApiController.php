<?php

namespace backend\controllers;

use backend\util\Amazon360Util;
use backend\util\AmazonSellerPartnerUtil;
use backend\util\AmazonspUtil;
use backend\util\AmazonUtil;
use backend\util\BackmarketUtil;
use backend\util\CatalogUtil;
use backend\util\BigCommerceUtil;
use backend\util\DarazUtil;
use backend\util\EbayUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\PrestashopUtil;
use backend\util\MagentoUtil;
use backend\util\ShopeeUtil;
use backend\util\ShopifyUtil;
use backend\util\WalmartUtil;
use backend\util\WishUtil;
use backend\util\WoocommerceUtil;
use common\models\Channels;
use common\models\ChannelSkuMissing;
use common\models\ChannelsPricing;
use common\models\ChannelsProducts;
use common\models\CrossCheckProductPrices;
use common\models\ExcludedSkus;
use common\models\GeneralReferenceKeys;
use common\models\Orders;
use common\models\PoDetails;
use common\models\Pricing;
use common\models\Product360Status;
use common\models\ProductDetails;
use common\models\Products;
use common\models\Products360Fields;
use common\models\Settings;
use common\models\StockPriceResponseApi;
use common\models\StocksPo;
use common\models\Subsidy;
use common\models\WarehouseStockList;
use Mpdf\Tag\P;
use Mpdf\Tag\Pre;
use Yii;

class ApiController extends MainController
{
    public static function _ApiCall($apiUrl, $authorizeHead = [], $method = 'GET', $curl_port = "", $post_fields = "")
    {
        $curl_url = sprintf("%s", $apiUrl);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => $curl_port,
            CURLOPT_URL => $curl_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 6000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $authorizeHead,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER =>0
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err . ' call from:' . $apiUrl;
        } else {
            return $response;
        }


    }

    public function actionGenerateLazadaNewAccessToken()
    {
        if(isset($_POST)){
            echo "<pre>";
            print_r($_POST);
        }
        elseif(isset($_GET)) {
            echo "<pre>";
            print_r($_GET);
        }
    }
    public function actionGenerateShopeeToken()
    {
        if(isset($_POST)){
            echo "<pre>";
            print_r($_POST);
        }
        elseif(isset($_GET)){
            echo "<pre>";
            print_r($_GET);
        }

    }
    public function _refineResponse($response)
    {
        if (trim($response)!='Error Page!!!'){
            $response = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '', $response);
            $searchBadTags = ['ns2:'];
            $response = str_replace($searchBadTags, '', $response);
            $r = simplexml_load_string($response);
            $json = json_encode($r);
            $refine_data = json_decode($json, true);
            return $refine_data;
        }

    }

    /*
     * 11 Street API Calls
     */

    private function _generateLazadaEndUrl($url, $customParams, $isStocks = false)
    {
        $now = new  \DateTime();
        if ($isStocks) {
            $parameters = array(
                'UserID' => $customParams['user'],
                'Version' => '1.0',
                'Action' => $customParams['action'],
                'Format' => 'JSON',
                'Timestamp' => $now->format(\DateTime::ISO8601)
            );
        } elseif ($customParams['action'] == 'GetOrders') {
            $parameters = array(
                'UserID' => $customParams['user'],
                'Version' => '1.0',
                'Action' => $customParams['action'],
                'Format' => 'JSON',
                'Limit' => '100',
                'Offset' => $customParams['offset'],
                'SortBy' => 'created_at',
                'SortDirection' => 'DESC',
                'CreatedAfter' => $customParams['CreatedAfter'],
                'Timestamp' => $now->format(\DateTime::ISO8601)
            );
        } elseif ($customParams['action'] == 'GetOrder') {
            $parameters = array(
                'UserID' => $customParams['user'],
                'Version' => '1.0',
                'Action' => $customParams['action'],
                'Format' => 'JSON',
                'Timestamp' => $now->format(\DateTime::ISO8601)
            );
        } elseif ($customParams['action'] == 'GetOrderItems') {
            $parameters = array(
                'UserID' => $customParams['user'],
                'Version' => '1.0',
                'Action' => $customParams['action'],
                'Format' => 'JSON',
                'OrderId' => $customParams['OrderId'],
                'Timestamp' => $now->format(\DateTime::ISO8601)
            );
        } else {
            $parameters = array(
                'UserID' => $customParams['user'],
                'Version' => '1.0',
                'Action' => $customParams['action'],
                'Filter' => 'all',
                'Options' => '1',
                'Limit' => $customParams['limit'],
                'Offset' => $customParams['offset'],
                'Format' => 'JSON',
                'Timestamp' => $now->format(\DateTime::ISO8601)
            );
        }

        ksort($parameters);

        $encoded = array();
        foreach ($parameters as $name => $value) {
            $encoded[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        $concatenated = implode('&', $encoded);

        $api_key = $customParams['key'];

        $parameters['Signature'] =
            rawurlencode(hash_hmac('sha256', $concatenated, $api_key, false));

        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $url = $url . "?" . $queryString;

        return $url;
    }

    public static function callIsis()
    {
        $skuArray = HelpUtil::getSkuList("sku", ['stock_status', 'id']);
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        $authSession = $refine['returnObject']['BondSession'];

        // call stock info from ISIS
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsInvEntityBean/doQueryStorageClientInventoryPage.wms";
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n\t<BondParamList>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>clientInvQuery</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsStorageClientInventoryQuery>\r\n\t\t\t\t\t<country>MALAYSIA</country>\r\n\t\t\t\t\t<storageClientNo>SSL0333</storageClientNo>\r\n\t\t\t\t</WmsStorageClientInventoryQuery>\r\n\t\t\t</paramValue>\r\n\t\t</BondParam>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>pageData</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsPageData>\r\n\t\t\t\t\t<currentLength>900</currentLength>\r\n\t\t\t\t\t<currentPage>1</currentPage>\r\n\t\t\t\t\t<validateTotalSize>true</validateTotalSize>\r\n\t\t\t\t</WmsPageData>\r\n\t\t\t</paramValue>    \r\n\t   </BondParam>\r\n\t</BondParamList>";
        $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);

        $refine = self::_refineResponse($response);
        $products = $refine['returnObject']['WmsPageData']['currentPageData']['WmsStorageClientInventoryView'];
        $list = [];

        foreach ($products as $p) {
            $sku = $p['storageClientSkuNo'];
            $sku = str_replace('�', '', $sku);
            $sku = trim($sku);
            $sku = str_replace('_', '/', $sku);
            $code = $p['skuNo'];
            $stock = $p['availableQty'];
            $query = "SELECT id FROM `products` WHERE sku LIKE '$sku'";
            $result = Products::findBySql($query)->asArray()->one();
            if ($result) {
                $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                if (!$pd)
                    $pd = new ProductDetails();
                else {
                    $p['goodQty'] = $p['goodQty'];
                    $p['allocatingQty'] = $p['allocatingQty'];
                    $p['processingQty'] = $p['processingQty'];
                    $p['damagedQty'] = $p['damagedQty'];
                    $stock = $p['availableQty'];
                }
                $pd->isis_sku = $sku;
                $pd->stock_from = '1';
                $pd->sku_id = $result['id'];
                $pd->stocks = $stock;
                $pd->sku_code = $code;
                $pd->goodQty = $p['goodQty'];
                $pd->damagedQty = $p['damagedQty'];
                $pd->allocatingQty = $p['allocatingQty'];
                $pd->processingQty = $p['processingQty'];
                $pd->last_update = date('Y-m-d h:i:s');
                $pd->selling_status = $skuArray[$sku]['stock_status'];
                $pd->sync_for = '1';
                $pd->save(false);
            } else {
                $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                if (!$pd)
                    $pd = new ProductDetails();
                else {
                    $p['goodQty'] = $p['goodQty'];
                    $p['allocatingQty'] = $p['allocatingQty'];
                    $p['processingQty'] = $p['processingQty'];
                    $p['damagedQty'] = $p['damagedQty'];
                    $stock = $p['availableQty'];
                }
                $pd->stock_from = '1';
                $pd->isis_sku = $sku;
                $pd->stocks = $stock;
                $pd->sku_code = $code;
                $pd->goodQty = $p['goodQty'];
                $pd->damagedQty = $p['damagedQty'];
                $pd->allocatingQty = $p['allocatingQty'];
                $pd->processingQty = $p['processingQty'];
                $pd->last_update = date('Y-m-d h:i:s');
                $pd->sync_for = '1';
                $pd->save(false);

            }
        }
    }

    public static function fetchIsis()
    {
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        if(isset($refine['returnObject']['BondSession']))
        {
            $authSession = $refine['returnObject']['BondSession'];

            // call stock info from ISIS
            $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsInvEntityBean/doQueryStorageClientInventoryPage.wms";
            $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
            $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n\t<BondParamList>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>clientInvQuery</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsStorageClientInventoryQuery>\r\n\t\t\t\t\t<country>MALAYSIA</country>\r\n\t\t\t\t\t<storageClientNo>SSL0333</storageClientNo>\r\n\t\t\t\t</WmsStorageClientInventoryQuery>\r\n\t\t\t</paramValue>\r\n\t\t</BondParam>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>pageData</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsPageData>\r\n\t\t\t\t\t<currentLength>1000</currentLength>\r\n\t\t\t\t\t<currentPage>1</currentPage>\r\n\t\t\t\t\t<validateTotalSize>false</validateTotalSize>\r\n\t\t\t\t</WmsPageData>\r\n\t\t\t</paramValue>    \r\n\t   </BondParam>\r\n\t</BondParamList>";

            $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
            $refine = self::_refineResponse($response);

            $total_page = $refine['returnObject']['WmsPageData']['totalPage'];
            for ($pg=1;$pg<=$total_page;$pg++){
                $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n\t<BondParamList>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>clientInvQuery</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsStorageClientInventoryQuery>\r\n\t\t\t\t\t<country>MALAYSIA</country>\r\n\t\t\t\t\t<storageClientNo>SSL0333</storageClientNo>\r\n\t\t\t\t</WmsStorageClientInventoryQuery>\r\n\t\t\t</paramValue>\r\n\t\t</BondParam>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>pageData</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsPageData>\r\n\t\t\t\t\t<currentLength>1000</currentLength>\r\n\t\t\t\t\t<currentPage>$pg</currentPage>\r\n\t\t\t\t\t<validateTotalSize>false</validateTotalSize>\r\n\t\t\t\t</WmsPageData>\r\n\t\t\t</paramValue>    \r\n\t   </BondParam>\r\n\t</BondParamList>";
                $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
                $prd_response = self::_refineResponse($response);
                foreach ( $prd_response['returnObject']['WmsPageData']['currentPageData']['WmsStorageClientInventoryView'] as $val ){
                    $data[] = $val;
                }
            }

            return json_encode($data);
        } else {
            return "Error";
        }
    }

    # fetch Order status from ISIS
    public static function fetchIsisOrderStatus()
    {
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        if(isset($refine['returnObject']['BondSession']))
        {
            $authSession = $refine['returnObject']['BondSession'];

            // call stock info from ISIS
            $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsWebApiOrderBean/doGetWebApiOrder.wms";
            $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
            $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n\t<BondParamList>\r\n\t\t<BondParam>\r\n\t\t\t<paramName>orderId</paramName>\r\n\t\t\t<paramValue>\r\n\t\t\t\t<WmsWebApiOrderIdView>\r\n\t\t\t\t\t<orderId>1812100037456HF</orderId>\r\n\t\t\t\t\t<storageClientNo>SSL0343</storageClientNo>\r\n\t\t\t\t<orderOrigin>SHOPEE</orderOrigin>\r\n\t\t\t\t</WmsWebApiOrderIdView>\r\n\t\t\t</paramValue>\r\n\t\t</BondParam>\r\n\t</BondParamList>";
            $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);

            $refine = self::_refineResponse($response);
            print_r($refine);die();
            $products = $refine['returnObject']['WmsPageData']['currentPageData']['WmsStorageClientInventoryView'];
            $list = [];
            return json_encode($products);
        } else {
            return "Error";
        }
    }

    #region GET ALL PRODUCTS FROM CHANNELS


    public function actionCallChannelsProducts()
    {
        // $rustart = getrusage();
        $filter = (isset($_GET['filter'])) ?  $_GET['filter'] : 'all';
        echo "started at " . date('h:i:s') . "<br/>";
        if (isset($_GET['shop-prefix']) && $_GET['shop-prefix'])
        {
            $channel = Channels::find()->where(['prefix'=>$_GET['shop-prefix'],'is_active' => 1])->one();
            ////////////////////////////////
            switch ($channel->marketplace)
            {
                case "prestashop":
                    ChannelSkuMissing::deleteAll(['channel_id'=>$channel->id]);
                    if (isset($_GET['presta_product_id'])){
                        $filters=['filter[id]' => '['.$_GET['presta_product_id'].']'];
                        PrestashopUtil::ChannelProducts($channel,$filters);
                    }else{
                        PrestashopUtil::ChannelProducts($channel);
                    }
                    break;

                case "magento":
                    MagentoUtil::ChannelProducts($channel);
                    break;

                case "ebay":
                    $Channels = Channels::find()->where(['prefix'=>$_GET['shop-prefix'],'is_active' => 1])->asArray()->one();
                    $this->_callEbayShopProducts($Channels['id']);
                    break;

                case "amazon":
                    AmazonUtil::ChannelProducts($channel);
                    break;

                case "amazonspa":
                    AmazonSellerPartnerUtil::ChannelProducts($channel);
                    break;

                case "walmart":
                    WalmartUtil::ChannelProducts($channel);
                    break;

                case "lazada":
                    LazadaUtil::ChannelProducts($channel);
                    break;

                case "shopee":
                    ShopeeUtil::ChannelProducts($channel);
                    break;

                case "bigcommerce":
                    BigCommerceUtil::channelProducts($channel);
                    break;

                case "backmarket":
                    BackmarketUtil::channelProducts($channel);
                    break;

                case "shopify":
                    ShopifyUtil::ChannelProducts($channel);
                    break;

                case "daraz":
                    DarazUtil::ChannelProducts($channel);
                    break;

                case "woocommerce":
                    WoocommerceUtil::ChannelProducts($channel);
                    break;

                case "wish":
                    WishUtil::ChannelProducts($channel);
                    break;

                default:
                    echo "No marketplace found";
                    break;
            }

        }else{
            die('shop prefix required');
        }
        echo "finished at " . date('h:i:s');
        $settings = Settings::find()->where(['name' => 'last_products_api_update'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }
    }

    public function actionFetchProductMagentoInfo()
    {
        $filter = (isset($_GET['filter'])) ?  $_GET['filter'] : 'all';
        echo "started at " . date('h:i:s') . "<br/>";
        if (isset($_GET['shop-prefix']) && $_GET['shop-prefix'])
        {
            $channel = Channels::find()->where(['prefix'=>$_GET['shop-prefix'],'is_active' => 1])->one();
            ////////////////////////////////
            switch ($channel->marketplace)
            {
                case "magento":
                    MagentoUtil::ProductMagentoInfo($channel);
                    break;

                default:
                    echo "No marketplace found";
                    break;
            }

        }else{
            die('shop prefix required');
        }
        echo "finished at " . date('h:i:s');
        $settings = Settings::find()->where(['name' => 'last_products_api_update'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }
    }



    public static function _callEbayShopProducts($channel_id){
       // die('come');
        //echo date('H:i:s');
        if ( isset($_GET['ItemId']) ) // if we have the filters
        {
            $data = [];
            $AllEbayProducts = EbayUtil::GetProductDetail($channel_id,$_GET['ItemId']);
            $data[] = json_decode($AllEbayProducts);
            $AllEbayProducts = $data;
        }
        else
        {
            $AllEbayProducts = EbayUtil::GetAllProducts($channel_id); // for all products
           // self::debug($AllEbayProducts);
            ChannelSkuMissing::deleteAll(['channel_id'=>$channel_id]); // delete sku empty field log first
        }

        $variations = [];

        $counter = 0;
        foreach ( $AllEbayProducts as $key=>$Detail ) {

            $cat_id=null;
            ///////////////for category saving
            if(isset($Detail->Item->PrimaryCategory->CategoryName))
            {

                $category=explode(':',$Detail->Item->PrimaryCategory->CategoryName);
                for($i = 0  ;$i <= (count($category)-1); $i++)
                {
                    if($category[$i] != "Sporting Goods")
                    {

                        $response=  CatalogUtil::saveCategories(array('cat_name'=>$category[$i],'parent_cat_id'=>$cat_id,'is_active'=>'1','channel'=>array('id'=>$channel_id)));
                        $cat_id=isset($response->id) ? $response->id:0;

                    }
                }

            }

            /// ////////////
            if (isset($Detail->Item->Variations->Variation->StartPrice)) {
                /*if (!isset($Detail->Item->Variations->Variation->VariationProductListingDetails->UPC))
                    continue;*/ // comment by bk

                if (!isset($Detail->Item->Variations->Variation->SKU)){
                    HelpUtil::SkuEmptyOnShopLog($channel_id,'Item Id : '.$Detail->Item->ItemID.' - SKU field is empty');
                }

                $qty_solda=isset($Detail->Item->Variations->Variation->QuantitySold) ?  $Detail->Item->Variations->Variation->QuantitySold:0;
                $qtya=$Detail->Item->Variations->Variation->Quantity;
                $qtya=($qtya-$qty_solda);
                $variations[$key]['product_name'] = $Detail->Item->Title;
                $variations[$key]['item_id'] = $Detail->Item->ItemID;
                $variations[$key]['cat_id'] = $cat_id;
                $variations[$key]['variation'][$counter]['price'] = $Detail->Item->Variations->Variation->StartPrice;
                $variations[$key]['variation'][$counter]['quantity'] = $qtya;
                $variations[$key]['variation'][$counter]['SKU'] = (isset($Detail->Item->Variations->Variation->SKU)) ? $Detail->Item->Variations->Variation->SKU : '';
                if (isset($Detail->Item->Variations->Variation->VariationProductListingDetails->UPC)) {
                    $variations[$key]['variation'][$counter]['upc'] = $Detail->Item->Variations->Variation->VariationProductListingDetails->UPC;
                } else {
                    $variations[$key]['variation'][$counter]['upc'] = '';
                }

            }
            else if (isset($Detail->Item->Variations->Variation)) {
                $variations[$key]['product_name'] = $Detail->Item->Title;
                $variations[$key]['parent_sku'] = (isset($Detail->Item->SKU) && $Detail->Item->SKU!='') ? $Detail->Item->SKU : '';
                $variations[$key]['StartPrice'] = (isset($Detail->Item->StartPrice) && $Detail->Item->StartPrice!='') ? $Detail->Item->StartPrice : '';
                $variations[$key]['item_id'] = $Detail->Item->ItemID;
                $variations[$key]['cat_id'] = $cat_id;
                foreach ($Detail->Item->Variations->Variation as $var_key => $variation_detail) {
                    $variation_name = '';

                    $NameList = [];
                    if ( is_object($variation_detail->VariationSpecifics->NameValueList) ){
                        $NameList[] = $variation_detail->VariationSpecifics->NameValueList;
                    }else{
                        $NameList = $variation_detail->VariationSpecifics->NameValueList;
                    }
                    foreach ( $NameList as $subkey=>$subvalue ){
                        $variation_name .= $subvalue->Value;
                    }

                    if (!isset($variation_detail->SKU)){
                        HelpUtil::SkuEmptyOnShopLog($channel_id,'Item Id : '.$Detail->Item->ItemID.' - "'.$variation_name.'" Variation sku field is empty');
                    }
                    $qty_sold=isset($variation_detail->SellingStatus->QuantitySold) ?  $variation_detail->SellingStatus->QuantitySold:0;
                    $qty=$variation_detail->Quantity;
                    $qty=($qty-$qty_sold);
                    $variations[$key]['variation'][$counter]['price'] = $variation_detail->StartPrice;
                    $variations[$key]['variation'][$counter]['quantity'] = $qty;
                    $variations[$key]['variation'][$counter]['upc'] = (isset($variation_detail->VariationProductListingDetails->UPC)) ? $variation_detail->VariationProductListingDetails->UPC : '';
                    $variations[$key]['variation'][$counter]['SKU'] = (isset($variation_detail->SKU)) ? $variation_detail->SKU : '';
                    $counter++;
                }
            }
            else{
                $qty_soldc=isset($Detail->Item->QuantitySold) ?  $Detail->Item->QuantitySold:0;
                $qtyc=$Detail->Item->Quantity;
                $qtyc=($qtyc-$qty_soldc);
                $variations[$key]['product_name'] = $Detail->Item->Title;
                $variations[$key]['item_id'] = $Detail->Item->ItemID;
                $variations[$key]['cat_id'] = $cat_id;
                $variation_name = $Detail->Item->Title;


                if (!isset($Detail->Item->SKU)){
                    HelpUtil::SkuEmptyOnShopLog($channel_id,'Item Id : '.$Detail->Item->ItemID.' - "'.$variation_name.'" Variation sku field is empty');
                }

                $variations[$key]['parent']['price'] = $Detail->Item->StartPrice;
                $variations[$key]['parent']['quantity'] = $qtyc;
                $variations[$key]['parent']['upc'] = (isset($Detail->Item->UPC)) ? $Detail->Item->UPC : '';
                $variations[$key]['parent']['SKU'] = (isset($Detail->Item->SKU)) ? $Detail->Item->SKU : '';
                $counter++;
            }


        }
        foreach ( $variations as $key=>$detail ){
            $detail['product_name'];
            $detail['item_id'];
            if (isset($detail['variation'])){
                foreach ( $detail['variation'] as $variation_detail ){

                    // check if parent product already exist or not.
                    if (isset($detail['parent_sku'])){
                        $findParentProduct = Products::find()->where(['sku'=>$detail['parent_sku']])->one();
                        if (!$findParentProduct){
                            $pd = [];
                            $pd['channel_sku'] = $detail['parent_sku'];
                            $pd['name'] = $detail['product_name'];
                            $pd['cost'] = $detail['StartPrice'];
                            $pd['category_id'] = $detail['cat_id'];
                            CatalogUtil::saveProduct($pd);
                        }
                    }



                    $record = [];
                    $record['channel_sku'] = (isset($variation_detail['SKU'])) ? $variation_detail['SKU'] : $variation_detail['upc'];
                    $record['cost'] = $variation_detail['price'];
                    $record['stock_qty'] = $variation_detail['quantity'];
                    $record['name'] = $detail['product_name'];
                    $record['category_id'] = $detail['cat_id'];
                    $record['ean'] = (isset($variation_detail['upc']) && $variation_detail['upc']!='') ? $variation_detail['upc'] : '';
                    $record['channel_id'] = $channel_id;
                    if ( isset($detail['parent_sku']) ){
                        $parent_sku_id = HelpUtil::exchange_values('sku','id',$detail['parent_sku'],'products');
                        if ( $parent_sku_id!='false' ){
                            $record['parent_sku_id'] = $parent_sku_id;
                        }
                    }

                    $record['sku'] = $detail['item_id'];
                    $record['variation_id'] = ''; // there is no variation unique id in ebay api.

                    $product_detail = HelpUtil::GetProductIdByUPC($variation_detail['upc']);
                    if ( empty($product_detail) ){ // if product not found then create new one
                        $ProductId = CatalogUtil::saveProduct($record);
                    }else{
                        $ProductId = CatalogUtil::saveProduct($record);
                        $ProductId = $product_detail[0]['id'];
                    }

                    $record['product_id'] = $ProductId;
                    CatalogUtil::savechannelProduct($record);
                }
            }
            else if ( isset($detail['parent']) ){

                $record = [];
                $record['channel_sku'] = (isset($detail['parent']['SKU'])) ? $detail['parent']['SKU'] : $variations[$key]['parent']['upc'];
                $record['cost'] = $detail['parent']['price'];
                $record['stock_qty'] = $detail['parent']['quantity'];
                $record['name'] = $variations[$key]['product_name'];
                $record['category_id'] = $variations[$key]['cat_id'];
                $record['ean'] = (isset($detail['parent']['upc']) && $detail['parent']['upc']!='') ? $detail['parent']['upc'] : '';
                $record['channel_id'] = $channel_id;
                $record['sku'] = $variations[$key]['item_id'];
                $record['variation_id'] = ''; // there is no variation unique id in ebay api.
//ADIBAC0112

                $product_detail = HelpUtil::GetProductIdByUPC($detail['parent']['upc']);

                if ( empty($product_detail) ){ // if product not found then create new one
                    $ProductId = CatalogUtil::saveProduct($record);
                }else{
                    $ProductId = $product_detail[0]['id'];
                }

                $record['product_id'] = $ProductId;
                CatalogUtil::savechannelProduct($record);
            }

        }
        echo '<br />Ended at'.date('H:i:s');

    }
    private function actionDeleteOldRecords($presta_product_id=[]){

        foreach ( $presta_product_id as $val ):

            $GetRecords = ChannelsProducts::find()->where(['sku'=>$val])->asArray()->all();
            ChannelsProducts::deleteAll(['sku'=>$val]);
            foreach ( $GetRecords as $value ):
                GeneralReferenceKeys::deleteAll(['channel_id'=>$value['channel_id'],'table_name'=>'channels_products','table_pk'=>$value['id']]);

            endforeach;

        endforeach;


    }
    public function _callPrestaShopProducts($channel,$ps_product_id='')
    {
        echo 'Started At : '.date('H:i:s');
        ChannelSkuMissing::deleteAll(['channel_id'=>$channel->id]);

        if ($ps_product_id!='')
            $filters=['filter[id]' => '['.$ps_product_id.']'];
        else
            $filters=[];
        PrestashopUtil::ChannelProducts($channel,$filters);

        //PrestashopUtil::actionPullPrestashopProducts($channel,$filters); // Import all products of prestashop in to our system.
        echo '<br />';
        echo 'Ended at :'. date('H:i:s');

    }





    public function actionDeleteLogsData()
    {
        echo "started at " .date('Y-m-d H:i:s') ."<br/>";
        HelpUtil:: DeleteApiResponseLogs();
        HelpUtil::DeleteCronJobsLogs();
        HelpUtil::DeleteWareHouseStocksLogs();
        HelpUtil::OptimizeLogTables();
        //HelpUtil:: DeleteISISLogs();
        echo "Ended at " .date('Y-m-d H:i:s') ."<br/>";
    }



    private function _insertRecordsForShops($response, $channel)
    {

        $response = json_decode($response, true);
        foreach ($response['result'] as $re) {
            if (!preg_match('/Phased Out/', $re['seller_sku']) && !preg_match('/Phased out/', $re['seller_sku']) && $re['seller_sku'] != "Phased Out" && $re['seller_sku'] != "Phased out") {
                $cp = ChannelsProducts::find()->where(['sku' => $re['sku'], 'channel_id' => $channel])->one();
                if (!$cp)
                    $cp = new ChannelsProducts();

                $cp->channel_id = $channel;
                $cp->product_name = $re['name'];
                $cp->channel_sku = $re['seller_sku'];
                $cp->sku = $re['sku'];
                $cp->stock_qty = $re['stocks'];
                $cp->price = $re['price'];
                $cp->last_update = date('Y-m-d H:i:s');
                $cp->save(false);
            }

        }
    }





    public function _getExcludedSkus($Shop_id, $Module)
    {
        if ($Module == 'Stock') {
            $Stock = ExcludedSkus::findBySql("SELECT * FROM excluded_skus WHERE shop_id = " . $Shop_id . " AND stocks_sync = 1")->asArray()->all();
            if (isset($Stock[0]['sku_stocks']))
                $response = explode(',', str_replace(' ', '', $Stock[0]['sku_stocks']));
            else
                $response = [];
        }
        if ($Module == 'Price') {
            $Price = ExcludedSkus::findBySql("SELECT * FROM excluded_skus WHERE shop_id = " . $Shop_id . " AND price_sync = 1")->asArray()->all();
            if (isset($Price[0]['sku_price']))
                $response = explode(',', str_replace(' ', '', $Price[0]['sku_price']));
            else
                $response = [];
        }

        $result = $response;
        return $result;
    }



    private function getChannelProducsSkus($channel)
    {
        $cp = ChannelsProducts::find()->where(['channel_id' => $channel])->asArray()->all();
        $skus = [];
        foreach ($cp as $key => $value) {
            $skus[] = $value['sku'];
        }
        return $skus;
    }

    private function _saveResponse($type, $channel_id, $response,$request=null)
    {
        $saveResponse = new StockPriceResponseApi();
        $saveResponse->type = $type . ' - ' . $_SERVER['HTTP_HOST'];
        $saveResponse->channel_id = $channel_id;
        $saveResponse->response = json_encode($response);
        $saveResponse->create_at = date('Y-m-d H:i:s');
        $saveResponse->request = $request;
        $saveResponse->save(false);
        if (empty($saveResponse->errors))
            return $saveResponse;
        else
            return $saveResponse->errors;
    }

    public function actionUpdateGcStocks(){
        if (isset($_GET['shop_name']) && $_GET['shop_name'] =='Lazada' ){
            $channel = Channels::find()->where(['name'=>$_GET['shop_name']])->one();
            LazadaUtil::updateGcStocks($channel);
        }
        elseif (isset($_GET['shop_name']) && $_GET['shop_name'] =='Shopee' ){
            $channel = Channels::find()->where(['name'=>$_GET['shop_name']])->one();
            ShopeeUtil::updateGcStocks($channel);
        }
    }


    public function actionSyncStocks()
    {

        echo 'Started at : '.date('H:i:s');
        $db_log=array(); // to store request and response in db log
        $updated_before_minutes=null;
        $current_channel_id=0;
        if (isset($_GET['shop-prefix']))
            $Channels = Channels::find()->where(['prefix'=>$_GET['shop-prefix'],'is_active'=>'1'])->asArray()->all();
        else
            die('shop prefix required');

        if(isset($_GET['updated_before_minutes'])){  // sync stock only that which is updated before x minutes sent in params

            $updated_before_minutes=$_GET['updated_before_minutes'];
            if(!is_numeric($updated_before_minutes))
                die('minutes should be numeric');

            $updated_before_minutes=abs(trim($_GET['updated_before_minutes']));
        }


        if(!$Channels)
            echo PHP_EOL.'<h1>channel not found</h1>' . PHP_EOL;

        //echo "<pre>";print_r($Channels);exit;
        foreach ($Channels as $ChannelsValue)
        {
            /*$updated_recently=false; // get only that stock which is recently updated , //60 minutes ago , specially for magento // for other marketplaces check if updated_at updating fine in db
            $updated_before=NULL;
            if($ChannelsValue['marketplace'] == 'magento'){
                $updated_before="-75";  // x minutes before updated
                $updated_recently=true; // only get stock which is updated x minutes ago
            }
            if(strtolower($ChannelsValue['name'])=='pedro'){
                $updated_before="-95"; // x minutes before updated
                $updated_recently=true; // only get stock which is updated x minutes ago
            }
            if(strtolower($ChannelsValue['name'])=='ebaygmobile'){
                $updated_before="-130"; // x minutes before updated
                $updated_recently=true; // only get stock which is updated x minutes ago
            }*/
            $current_channel_id=$ChannelsValue['id'];
            $stocklist = HelpUtil::getStock($ChannelsValue,$updated_before_minutes); // Get stocks from warehouse on which stock upload limit applies
            // self::debug($stocklist);
            $stocklist= HelpUtil::stock_percent_to_update($ChannelsValue,$stocklist ); // if channel applied stock update limit , filter that
            // self::debug($stocklist);
            // $full_stock_warehouse = HelpUtil::getStock($ChannelsValue['id'],$updated_recently,0); // Get stocks from warehouse on which stock upload limit not applies
            //if($full_stock_warehouse)
            //    $stocklist= HelpUtil::merge_stock_to_upload($stocklist,$full_stock_warehouse );

            // self::debug($stocklist);
            if(!$stocklist) {continue;}

            if ($ChannelsValue['marketplace'] == 'prestashop')
            {

                foreach ($stocklist as $StockValues )
                {
                    $GetStockId = GeneralReferenceKeys::find()->where(['table_name'=>'channels_products','channel_id'=>$ChannelsValue['id'],
                        'table_pk'=>$StockValues['channels_product_pk_id'],'key'=>'stock_available_id'])->asArray()->all();
                    $Stock = $StockValues['stock'];
                    if ( isset($GetStockId[0]['value']) ){
                        $Stock_Id = $GetStockId[0]['value'];
                        // Update stocks
                        $response = PrestashopUtil::UpdateStocks($ChannelsValue['id'],$Stock_Id,$Stock);
                        $db_log[]=[
                            'request'=>array('sku'=>$StockValues['sku'],'stock'=>$Stock,'additional_info'=>array('general_ref_key'=>$Stock_Id)),
                            'response'=>self::get_json_decode_format($response),
                        ];
                    }
                }

            }
            elseif($ChannelsValue['marketplace']=="magento")
            {
                foreach($stocklist as $list)
                {
                    //if($list['sku']=="339003-007-misc") { // for test purpose

                    $response = MagentoUtil::updateChannelStock((object)$ChannelsValue, array('stock' => $list['stock'], 'sku' => $list['sku'])); // update stock on live store
                    $db_log[] = [
                        'request' => array('sku' => $list['sku'], 'stock' => $list['stock'], 'additional_info' => array()),
                        'response' => self::get_json_decode_format($response),
                    ];

                    // }

                }
                //  echo "<pre>";
                //  print_r($db_log);

            }
            elseif ($ChannelsValue['marketplace']=="ebay")
            {
                $xml = EbayUtil::SetRequest($stocklist);
                foreach ( $xml as $key=>$value ){
                    $response = EbayUtil::UpdateStock($ChannelsValue['id'], $value); // update stock on live store
                    $db_log[]=[
                        'request'=>array($value),
                        'response'=>self::get_json_decode_format($response),
                    ];
                }


            }
            elseif ($ChannelsValue['marketplace']=="shopify")  {

                foreach($stocklist as $list) {
                    $response=ShopifyUtil::updateChannelStock($ChannelsValue, array('channel_product_pk_id' =>$list['channel_product_pk_id'],'stock'=>$list['stock']));
                   // self::debug($response);
                    //  $this->_saveResponse('Stocks',$ChannelsValue['id'],$response,$list['stock']);
                    $db_log[]=[
                        'request'=>array('sku'=>$list['sku'],'stock'=>$list['stock'],'additional_info'=>array()),
                        'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                    ];
                }


            }
            elseif ($ChannelsValue['marketplace']=="daraz") {

                $counter=1; //to update 50 stock per call
                $body="";
                $list_log=array();  // what stock and skus requested from our side so to store in db log
                $total_skus=count($stocklist);

                foreach($stocklist as $list)
                {
                    $body .='<Sku>
                                    <SellerSku>'.$list['sku'].'</SellerSku>
                                    <Quantity>'.$list['stock'].'</Quantity>
                                </Sku>';
                    $list_log['bulk_sku_stock'][]=array('sku'=>$list['sku'],'stock'=>$list['stock']);
                    if($total_skus >=50 &&  fmod($counter,50) == 0 )  //reminder equals 0 then update 50 batch at once
                    {
                        $response=DarazUtil::updateChannelStockPrice((object)$ChannelsValue,$body);
                        // $this->_saveResponse('Stocks',$ChannelsValue['id'],$response,$list);
                        $db_log[]=[
                            'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                            'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                        ];

                        $body=$list_log="";
                    }
                    $counter++;
                }

                if($total_skus < 50 )
                {
                    $response=DarazUtil::updateChannelStockPrice((object)$ChannelsValue,$body);
                    //  $this->_saveResponse('Stocks',$ChannelsValue['id'],$response,$list);
                    $db_log[]=[
                        'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                        'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                    ];

                }
                //self::debug($db_log);



            }
            elseif ($ChannelsValue['marketplace']=="amazon") {

                $listing=array();
                foreach($stocklist as $list) {

                    if($list['fulfilled_by']=='FBM') {
                        $listing[] = array($list['sku'] => $list['stock']);
                    }
                }
                $to_send = array_reduce($listing, 'array_merge', array());
                $list_log['bulk_sku_stock']=$to_send;
                $response=  \backend\util\AmazonUtil::updateChannelStock($ChannelsValue,array('stock'=>$to_send));
                $db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                ];


            }
            elseif($ChannelsValue['marketplace']=="amazonspa"){
               // self::debug($stocklist);
                $response=AmazonSellerPartnerUtil::updateChannelStock((object)$ChannelsValue,$stocklist);
                $db_log= [
                    'request' => array('skus' => $stocklist, 'additional_info' => 'bulk update feed'),
                    'response' => self::get_json_decode_format($response),
                ];
            }
            elseif ($ChannelsValue['marketplace']=="walmart") {
                $body="";
                foreach($stocklist as $list) {
                    $body .= '<inventory>
                                    <sku>' . $list['sku'] . '</sku>
                                    <quantity>
                                        <unit>EACH</unit>
                                        <amount>'. $list['stock'] . '</amount>
                                    </quantity>
                                </inventory>';
                    $list_log['bulk_sku_stock'][] = array('sku' => $list['sku'], 'stock' => $list['stock']);
                }
                $response=WalmartUtil::updateChannelStock($ChannelsValue,$body);
                $db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                    'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                ];

            }
            elseif ($ChannelsValue['marketplace']=="lazada") {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($ChannelsValue['id'],'Stock');
                $db_log= LazadaUtil::updateChannelStock((object)$ChannelsValue,$stocklist,$UnsyncSkus);

            }
            elseif($ChannelsValue['marketplace']=="shopee")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($ChannelsValue['id'],'Stock');
                $db_log= ShopeeUtil::updateChannelStock((object)$ChannelsValue,$stocklist,$UnsyncSkus);
            }
            elseif($ChannelsValue['marketplace'] == 'bigcommerce')
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($ChannelsValue['id'],'Stock');
                $db_log= BigCommerceUtil::updateChannelStock((object)$ChannelsValue,$stocklist,$UnsyncSkus);
            }
            elseif($ChannelsValue['marketplace'] == 'backmarket')
            {
                foreach($stocklist as $list)
                {
                    $response=BackmarketUtil::updateChannelStock((object)$ChannelsValue,['stock'=>$list['stock'],'sku' => $list['sku_id']]);
                    $db_log[] = [
                        'request' => array('sku' => $list['sku'], 'stock' => $list['stock'], 'additional_info' => array()),
                        'response' => self::get_json_decode_format($response),
                    ];


                }
            }
            elseif($ChannelsValue['marketplace']=="woocommerce")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($ChannelsValue['id'],'Stock');
                $db_log = WoocommerceUtil::updateChannelStock((object)$ChannelsValue, $stocklist, $UnsyncSkus); // update stock on live store

            }

            elseif($ChannelsValue['marketplace']=="wish")
            {

                $UnsyncSkus = HelpUtil::_getExcludedSkus($ChannelsValue['id'],'Stock');
                $db_log = WishUtil::updateChannelStock($ChannelsValue, $stocklist, $UnsyncSkus); // update stock on live store

            }

        }

        // if error
        if($db_log){
            $this->_saveResponse('Stocks',$current_channel_id,$db_log);
        }
        echo '<br />';
        echo 'Ended at : '.date('H:i:s');
        die;
    }

    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)){

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }


    public function actionSyncPrices()
    {
        echo 'Started at : '.date('H:i:s');
        $db_log=array(); // to store request and response in db log
        $current_channel_id=0;
        if (isset($_GET['shop-prefix']))
            $Channels = Channels::find()->where(['prefix'=>$_GET['shop-prefix'],'is_active'=>'1'])->asArray()->all();
        else
            die('shop name required');
        foreach ($Channels as $Channel_Detail)
        {
            $current_channel_id=$Channel_Detail['id'];
            $updated_recently=false; // get only that stock which is recently updated , specially for magento
            if(strtolower($_GET['shop-prefix']) == 'spl-mgt'){
                $updated_recently=true; // only get stock which is updated recently
            }
            $PricesJson = HelpUtil::PriceUpdateProducts($Channel_Detail['id'],$updated_recently);
            // echo "<pre>";
            // print_r($PricesJson); die();
            if ($Channel_Detail['marketplace'] == 'prestashop')
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                foreach ($PricesJson as $pd)
                {
                    if ( in_array($pd['sku'],$UnsyncSkus) ){
                        continue;
                    }

                    $response = PrestashopUtil::UpdatePrice($pd['channel_id'],$pd['channels_products_sku'],$pd['rccp'],$pd['variation_id'],$pd['product_id']); //,$pd['product_id'] is sent to check if sku is in active deal or not
                    $db_log[]=[
                        'request'=>array('sku'=>$pd['channels_products_sku'],'price'=>$pd['rccp'],'additional_info'=>array('variation_id'=>$pd['variation_id'])),
                        'response'=>self::get_json_decode_format($response),
                    ];
//                     $log = $this->_saveResponse('Price', $pd['channel_id'], json_decode($up),json_encode($pd));
                }

            }
            elseif($Channel_Detail['marketplace']=="magento")
            {
                //die('come');
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                //  self::debug($PricesJson);
                foreach ($PricesJson as $pd)
                {
                    if ( in_array($pd['sku'],$UnsyncSkus) ){
                        continue;
                    }

                    $response= MagentoUtil::updateChannelPrice($Channel_Detail, array('product_id'=>$pd['product_id'],'sku'=>$pd['sku'],'price'=>$pd['rccp'],'check_deal_sku'=>'no')); // check_deal_sku => if sku is in currently active deal then skip price update
                    $db_log[]=[
                        'request'=>array('sku'=>$pd['sku'],'price'=>$pd['cost_price'],'additional_info'=>array()),
                        'response'=>self::get_json_decode_format($response),
                    ];
                    //$log = $this->_saveResponse('Price', $pd['channel_id'], json_decode($resp),json_encode($pd));
                }

            }
            elseif($Channel_Detail['marketplace']=="shopify")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');

                foreach ($PricesJson as $pd)
                {
                    if ( in_array($pd['sku'],$UnsyncSkus) ){
                        continue;
                    }

                    $response=ShopifyUtil::updateChannelPrice($Channel_Detail, array('product_id'=>$pd['product_id'],'variant_id'=>$pd['variation_id'],'price'=>$pd['cost_price'],'check_deal_sku'=>'yes'));
                    $db_log[]=[
                        'request'=>array('sku'=>$pd['sku'],'price'=>$pd['cost_price'],'additional_info'=>array('variation_id'=>$pd['variation_id'])),
                        'response'=>self::get_json_decode_format($response),
                    ];
                    // $log = $this->_saveResponse('Price', $pd['channel_id'], json_decode($resp),json_encode($pd));
                }

            }
            elseif ($Channel_Detail['marketplace']=="ebay")
            {

               // die; // for security reason when after every thing goes well then we will turn it on.
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $xml = '';

                /*foreach ($PricesJson as $pd)
                {
                    if ( in_array($pd['sku'],$UnsyncSkus) ){
                        continue;
                    }
                    if(isset($pd['product_id']))
                    {
                        $check_deal_sku = HelpUtil::SkuInDeal($pd['product_id'], $channel);
                        if ($check_deal_sku)
                        {
                            continue;
                        }

                    }

                    $xml .= '<InventoryStatus>
                                <ItemID>'.$pd['channels_products_sku'].'</ItemID>
                                <SKU>'.$pd['channel_sku'].'</SKU>
                                <StartPrice currencyID="USD">'.$pd['rccp'].'</StartPrice>
                            </InventoryStatus>';

                }*/
                $xml = EbayUtil::SetRequestForPrice($PricesJson);
                //self::debug($xml);
                foreach ( $xml as $key=>$value ){
                    $response = EbayUtil::UpdatePrice($Channel_Detail['id'], $value); // update stock on live store
                    $db_log[]=[
                        'request'=>array($value),
                        'response'=>self::get_json_decode_format($response),
                    ];
                }
               /* $response= EbayUtil::UpdatePrice($Channel_Detail['id'], $xml); // check_deal_sku => if sku is in currently active deal then skip price update
                $db_log[]=[
                    'request'=>array($response),
                    'response'=>self::get_json_decode_format($response),
                ];*/

                //  $log = $this->_saveResponse('Price', $pd['channel_id'], json_decode($resp),json_encode($pd));

            }
            elseif($Channel_Detail['marketplace']=="daraz")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');

                if($PricesJson):
                    $counter=1; //to update 50 stock per call
                    $body="";
                    $list_log=array();  // what stock and skus requested from our side so to store in db log
                    $total_skus=count($PricesJson);

                    foreach($PricesJson as $list)
                    {
                        if ( in_array($list['sku'],$UnsyncSkus) ){
                            continue;
                        }

                        $body .='<Sku>
                                    <SellerSku>'.$list['sku'].'</SellerSku>
                                    <Price>'.round($list['rccp']).'</Price>
                                </Sku>';
                        $list_log['sku_stock'][]=array('sku'=>$list['sku'],'price'=>$list['rccp']);
                        if($total_skus >=50 &&  fmod($counter,50) == 0 )  //reminder equals 0 then update 50 batch at once
                        {
                            $response=DarazUtil::updateChannelStockPrice((object)$Channel_Detail,$body);
                            $db_log[]=[
                                'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                                'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                            ];
                            // $this->_saveResponse('price',$Channel_Detail['id'],$response,$list);
                            $body=$list_log="";
                        }
                        $counter++;
                    }

                    if($total_skus < 50 )
                    {

                        $response=DarazUtil::updateChannelStockPrice((object)$Channel_Detail,$body);
                        $db_log[]=[
                            'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                            'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                        ];
                        //$this->_saveResponse('Stocks',$Channel_Detail['id'],$response,$list);

                    }

                endif;
            }
            elseif($Channel_Detail['marketplace']=="amazon")
            {

                $price_list=array();
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');

                foreach ($PricesJson as $pd)
                {
                    if (in_array($pd['sku'],$UnsyncSkus))
                        continue;

                    $price_list[]=array($pd['channel_sku']=>$pd['rccp']);

                }
                $tosend=array_reduce($price_list,'array_merge',array());
                $response=\backend\util\AmazonUtil::updateChannelPrice($Channel_Detail,array('prices'=>$tosend));
                $db_log[]=[
                    'request'=>array($price_list,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];

            }
            elseif($Channel_Detail['marketplace']=="amazonspa")
            {
                $price_list=array();
                $unsync_skus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');

                foreach ($PricesJson as $pd)
                {
                    if (in_array($pd['sku'],$unsync_skus))
                        continue;

                    $sku_in_deal= HelpUtil::SkuInDeal($pd['product_id'],$Channel_Detail['id']);  // if sku is in active deal
                    if($sku_in_deal)
                        continue;

                    $price_list[]=array('sku'=>$pd['sku'],'price'=>$pd['rccp']);
                }
               // self::debug($price_list);
                $response=AmazonSellerPartnerUtil::updateChannelPrice((object)$Channel_Detail,$price_list);
                $db_log[]=[
                    'request'=>array($price_list,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];
                //self::debug($db_log);

            }
            elseif($Channel_Detail['marketplace']=="walmart") {

                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $item_list="";
                foreach($PricesJson as $list)
                {
                    if ( in_array($list['sku'],$UnsyncSkus) ){
                        continue;
                    }
                    $item_list .='<Price>
                                    <itemIdentifier>
                                      <sku>'.$list['sku'].'</sku>
                                    </itemIdentifier>
                                    <pricingList>
                                      <pricing>
                                        <currentPrice>
                                          <value currency="USD" amount="'.$list['cost_price'].'"/>
                                        </currentPrice>
                                      </pricing>
                                    </pricingList>
                                 </Price>';
                    $list_log['sku_price'][]=array('sku'=>$list['sku'],'price'=>$list['cost_price']);

                }

                $response=WalmartUtil::updateChannelPrice($Channel_Detail,$item_list);
                $db_log[]=[
                    'request'=>array($list_log,'additional_info'=>array()),
                    'response'=>self::get_json_decode_format($response),
                ];

            } //walmart section end
            elseif($Channel_Detail['marketplace']=="lazada")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $db_log= LazadaUtil::updateChannelPrice((object)$Channel_Detail,$PricesJson,$UnsyncSkus);

            } // lazada section end
            elseif($Channel_Detail['marketplace']=="shopee")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $db_log= ShopeeUtil::updateChannelPrice((object)$Channel_Detail,$PricesJson,$UnsyncSkus);
            }
            elseif($Channel_Detail['marketplace']=="bigcommerce")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $db_log= BigCommerceUtil::updateChannelPrice((object)$Channel_Detail,$PricesJson,$UnsyncSkus);
            }
            elseif($Channel_Detail['marketplace']=="backmarket")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                foreach($PricesJson as $list)
                {
                    if ( in_array($list['sku'],$UnsyncSkus) ){
                        continue;
                    }
                    $response= BackmarketUtil::updateChannelPrice((object)$Channel_Detail,['sku'=>$list['channels_products_sku'],'price'=>$list['rccp']]);
                    $db_log[]=[
                        'request'=>array(['sku'=>$list['sku'],'price'=>$list['rccp']],'additional_info'=>array()),
                        'response'=>self::get_json_decode_format($response),
                    ];
                }

            }

            elseif($Channel_Detail['marketplace']=="woocommerce")
            {
                $UnsyncSkus = HelpUtil::_getExcludedSkus($Channel_Detail['id'],'Price');
                $db_log= WoocommerceUtil::updateChannelPrice((object)$Channel_Detail, $PricesJson, $UnsyncSkus); // check_deal_sku => if sku is in currently active deal then skip price update
            }

        }
        // if error
        if($db_log){
            $this->_saveResponse('Price',$current_channel_id,$db_log);
        }
        echo '<br />Ended at : '.date('H:i:s');
        die;
    }





    public static function SaveCrossCheckPriceUpdate( $sku_id,$channel_id,$price,$update_response,$get_product_response,$deal_id='' ){
        $saveCrossCheck=new CrossCheckProductPrices();
        $saveCrossCheck->sku_id=$sku_id;
        $saveCrossCheck->channel_id=$channel_id;
        $saveCrossCheck->price_should_be=$price;

        // below is for lazada
        /* if ( isset($get_product_response->data->products[0]->skus[0]->special_price) && $get_product_response->data->products[0]->skus[0]->special_price!=0 )
         {
             $saveCrossCheck->price_is=$get_product_response->data->products[0]->skus[0]->special_price;
         }elseif ( isset($get_product_response->data->products[0]->skus[0]->price) )
         {
             $saveCrossCheck->price_is=$get_product_response->data->products[0]->skus[0]->price;
         }
         // Lazda ends here
         // For shopee
         //echo
         if ( isset($get_product_response->item->price) ){
             $saveCrossCheck->price_is=$get_product_response->item->price;
         }
         // For Sreet
         if ( isset($get_product_response->selPrc) ){
             $saveCrossCheck->price_is=$get_product_response->selPrc;
         }*/
        if ( isset($get_product_response['price_sell']) ){
            $saveCrossCheck->price_is=$get_product_response['price_sell'];
        }
        // calculate the difference of prices
        if ($saveCrossCheck->price_is!=''){
            $saveCrossCheck->difference = $price - $saveCrossCheck->price_is;
        }

        if ($deal_id!=''){
            $saveCrossCheck->deal_id=$deal_id;
        }

        $saveCrossCheck->api_response=$update_response;
        $saveCrossCheck->added_at = date('Y-m-d');
        $saveCrossCheck->save();
        if (!empty($GetLazadaSkuInfo)){
            echo '<pre>';
            print_r($saveCrossCheck);
            die;
        }

    }




    public static function fetchShopSales($obj, $params)
    {
        $apiKey = $obj->api_key;
        $url = $obj->api_user;
        $ed = strtotime($params['end_datetime']);
        $apiUrlx = $url . "api/get-orders.php?param_auth=$apiKey&param_limit=$ed";
        $responsex = self::_ApiCall($apiUrlx);
        $response = json_decode($responsex, true, 512, JSON_BIGINT_AS_STRING);
        return $response;
    }

    public static function fetchShopSalesItems($obj, $params)
    {
        $apiKey = $obj->api_key;
        $url = $obj->api_user;
        $orderId = $params['OrderId'];
        $apiUrlx = $url . "api/get-orders-items.php?param_auth=$apiKey&order_id=" . $orderId;
        $responsex = self::_ApiCall($apiUrlx);
        $response = json_decode($responsex, true, 512, JSON_BIGINT_AS_STRING);
        return $response;
    }


    public static function getSkusForErc($po_id){
        // get single skus
        $Single_Sku_sql = "SELECT 
                      pod.sku,
                      pod.final_order_qty,
                  p.`is_foc`,
                  pod.sku AS details
                    FROM
                      `po_details` pod 
                      INNER JOIN products p 
                        ON p.id = pod.`sku_id` 
                    WHERE 
                    pod.`sku_id` NOT IN 
                      (SELECT 
                        a.id 
                      FROM
                        products a 
                        JOIN products b 
                          ON a.id = b.parent_sku_id) 
                  AND pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS  NULL";
        $Single_Result = PoDetails::findBySql($Single_Sku_sql)->asArray()->all();
        // get FB skus
        $Fb_sku_sql = "SELECT 
                  pr.relation_name as sku,
                  pod.final_order_qty,
                  '0' AS `is_foc` ,
                  GROUP_CONCAT(pod.sku SEPARATOR ' + ') AS details
                FROM
                  `po_details` pod 
                  
                  INNER JOIN `products_relations` pr
                    ON pr.id = pod.bundle AND relation_type = 'FB'
                WHERE 
                   pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS NOT NULL 
                  GROUP BY bundle";
        $Fb_Result = PoDetails::findBySql($Fb_sku_sql)->asArray()->all();
        // get FOC skus
        $FOC_sku_sql = "SELECT 
                 pod.sku,
                  pod.final_order_qty,
                  p.`is_foc`,
                  pod.sku AS details
                FROM
                  `po_details` pod 
                  INNER JOIN `products_relations` pr
                    ON pr.id = pod.bundle AND relation_type = 'FOC'
                    INNER JOIN products p 
                    ON p.id = pod.`sku_id` 
                WHERE 
                   pod.`po_id` = '$po_id'
                  AND final_order_qty != '0' AND bundle IS NOT NULL ";
        $FOC_result = PoDetails::findBySql($FOC_sku_sql)->asArray()->all();
        return array_merge($Single_Result,$Fb_Result,$FOC_result);
    }

    #region ER Api for Stocks PO
    public static function createER($podId)
    {
        //get PO details
        $poDoc = StocksPo::findOne(['id' => $podId])->po_code;
        $remarks = StocksPo::findOne(['id' => $podId])->remarks;
        $poDate = StocksPo::findOne(['id' => $podId])->po_final_date;

        // login ISIS API
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        $authSession = $refine['returnObject']['BondSession'];

        // add ER
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/addErForStorageClientWebApi";
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $postFields = [
            'erDate' => date("d/m/Y h:i:s", strtotime($poDate)),
            'erType' => strtoupper("Purchase Order"),
            'docNo' => $poDoc,
            'storageClientNo' => "SSL0333",
            'remark' => $remarks,
            'supplierNo' => "",
        ];
        $postFields = json_encode($postFields);

        $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);

        $refine = json_decode($response, true);
        if (isset($refine['returnObject'])) {

            $po = StocksPo::findOne(['id' => $podId]);
            $po->er_no = $refine['returnObject'];
            $po->save(false);

            // add ER details

            $poSkus = self::getSkusForErc($podId);

            foreach ($poSkus as $ps) {

                $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ErDetail/addErDetailForStorageClientWebApi";
                $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
                $port = "5191";
                $postFields = [
                    'dataVersion' => 1,
                    'erNo' => $refine['returnObject'],
                    'storageClientNo' => "SSL0333",
                    'skuDesc' => $ps['details'],
                    'storageClientSkuNo' => $ps['sku'],
                    'erQty' => $ps['final_order_qty'],
                    'recvPrintLabel' => true,
                    'scanSerialNo' => false,
                ];
                $postFields = json_encode($postFields);
                $responsex = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
                $refinex[$ps['sku']] = json_decode($responsex, true);
            }
        }
    }

    #region ER API for er qty
    public static function fetchER($erNo)
    {
        // login ISIS API
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        $authSession = $refine['returnObject']['BondSession'];
        // fetch ER
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/getErForStorageClientWebApi";
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];

        $postFields = [
            'erForStorageClientWebApiQuery' => [
                "storageClientNo" => "SSL0333",
                "erNo" => $erNo,
            ]
        ];
        $postFields = $erNo;//json_encode($postFields);
        $response = self::_ApiCall($apiUrl, $access, 'POST', $port, $postFields);
        return json_decode($response, true);
    }

    public static function checkERStatus($ERNo, $type = "status")
    {
        // login
        $postFields = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<BondParamList>\n\t <BondParam> \n\t\t <paramName>userNo</paramName> \n\t\t <paramValue> \n\t\t\t<BondString><str>SSL0333</str></BondString> \n\t\t </paramValue> \n\t\t </BondParam> \n\t <BondParam> \n\t\t <paramName>userPassword</paramName> \n\t\t <paramValue>\n\t\t\t<BondString><str>1e9d41efc51feec7deeb0ec911a9cbea</str></BondString>\n\t\t </paramValue> \n\t </BondParam> \n </BondParamList>";
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/WebAPI/WmsPublicBean/login.wms";
        $port = "5191";
        $response = self::_ApiCall($apiUrl, [], 'POST', $port, $postFields);
        $refine = self::_refineResponse($response);
        $authSession = $refine['returnObject']['BondSession'];
        // list of ER
        $apiUrl = "https://istoreisend-wms.com:5191/IsisWMS-War/Json/ExpectedReceipt/queryErForStorageClientWebApiPage";
        $postFields = [
            'erForStorageClientWebApiQuery' => [
                "storageClientNo" => "SSL0333",
                "erNo" => $ERNo,
                "orderBy" => "erDate desc",
            ],
            'pageData' => [
                "currentLength" => 20
            ]
        ];
        $postFields = json_encode($postFields);
        $access = ['Content-Type:application/json', 'Authorization: Basic ' . base64_encode($authSession['sessionId'] . ":" . $authSession['sessionPassword'])];
        $response = self::_ApiCall($apiUrl, $access, 'POST', "5191", $postFields);
        $response = json_decode($response, true);
        if (isset($response['success']) && $response['success']) {
            $robj = $response['returnObject']['currentPageData'];
            foreach ($robj as $obj) {
                if ($type == "status")
                    $status = $obj['erStatus'];
                else if ($type == "code")
                    $status = $obj['docNo'];
                return $status;
            }
        }
        return false;
    }



    public function actionGetMappedStatus($api_order_status)
    {
        $shippedStatus = ['to_confirm_receive', 'requested', 'judging', 'processing', 'delivered', 'reversed', 'self collect', 'complete', 'completed'];
        $cancelStatus = ['unpaid', 'cancelled', 'invalid', 'to_return', 'in_cancel', 'accepted', 'refund_paid', 'closed', 'seller_dispute', 'missing orders', 'canceled', 'refunded', 'expired', 'failed', 'returned', 'reversed', 'delivery failed', 'canceled by customer'];
        $pendingStatus = ['ready_to_ship', 'retry_ship', 'exchange', 'pending', 'processed', 'processing', 'returned', 'reversed', 'ready_to_ship', 'in transit'];
        $status = '';
        if (in_array(strtolower($api_order_status), $shippedStatus))
            $status = 'shipped';
        elseif (in_array(strtolower($api_order_status), $cancelStatus))
            $status = 'cancelled';
        elseif (in_array(strtolower($api_order_status), $pendingStatus))
            $status = 'pending';
        return $status;
    }



    function UpdateOrderStatus($order_id, $status)
    {
        $model = Orders::findOne(['order_number' => $order_id]);
        $model->order_status = $status;
        $model->update();  // equivalent to $model->update();
        if (empty($model->errors))
            return $status;
        else
            return $model->errors;
    }

    /*public function actionProductsyncStatuscheck_old()
    {*/
    //////////////////////////
    /* $jhing=array (
     0 =>
         array (
             'FeedSubmissionId' => '78677018393',
             'FeedType' => '_POST_PRODUCT_DATA_',
             'SubmittedDate' => '2020-05-11T08:50:25+00:00',
             'FeedProcessingStatus' => 'Complete',
         ),
     1 =>
         array (
             'FeedSubmissionId' => '78678018393',
             'FeedType' => '_POST_PRODUCT_PRICING_DATA_',
             'SubmittedDate' => '2020-05-11T08:50:27+00:00',
             'FeedProcessingStatus' => 'Complete',
         ),
     2 =>
         array (
             'FeedSubmissionId' => '78679018393',
             'FeedType' => '_POST_INVENTORY_AVAILABILITY_DATA_',
             'SubmittedDate' => '2020-05-11T08:50:29+00:00',
             'FeedProcessingStatus' => 'Complete',
         ),
     3 =>
         array (
             0 => '',
         ));

     self::productsync_status_update($jhing);

     /// ///////////////////////////////////
     die();*/
    //////////////////////////////////

    /* $jayParsedAry = [
         "product_feed" => [
             "FeedSubmissionId" => "78985018395",
             "FeedType" => "_POST_PRODUCT_DATA_",
             "SubmittedDate" => "2020-05-13T02:24:53+00:00",
             "FeedProcessingStatus" => "_SUBMITTED_"
         ],
         "price_feed" => [
             "FeedSubmissionId" => "78986018395",
             "FeedType" => "_POST_PRODUCT_PRICING_DATA_",
             "SubmittedDate" => "2020-05-13T02:24:54+00:00",
             "FeedProcessingStatus" => "_SUBMITTED_"
         ],
         "stock_feed" => [
             "FeedSubmissionId" => "78987018395",
             "FeedType" => "_POST_INVENTORY_AVAILABILITY_DATA_",
             "SubmittedDate" => "2020-05-13T02:24:56+00:00",
             "FeedProcessingStatus" => "_SUBMITTED_"
         ]
     ];

     die();*/
    ////////////////////////////
    //$status="";
    /*  echo "started  at " . date('Y-m-d H:i:s');
      if(isset($_GET['channel_id']))
      {
          $channel=Channels::findOne(['id'=>$_GET['channel_id'],'is_active'=>1]);
          $p3s=Product360Status::find()->where(['shop_id'=>$_GET['channel_id'],'status'=>['Pending','Activating']])->andWhere(['not', ['transaction_response' => null]])->asArray()->all();
          if($p3s && $channel)
          {
             foreach($p3s as $record)
             {
                 if ($channel->marketplace == 'amazon')
                 {
                     $response=  Amazon360Util::check_feed_results($channel, json_decode($record['transaction_response']));
                     $status=self::productsync_status_update($response);
                     $fail_info=Amazon360Util::check_failure_reason($response);
                     $p3s_update=Product360Status::findOne(['id'=>$record['id']]);
                     $p3d=Products360Fields::findOne(['id'=>$record['product_360_fieldS_id']]);
                     if($response)
                         $p3s_update->transaction_response=json_encode($response);
                     if($status=='Fail')
                         $p3s_update->status='Fail';
                     if($status=='Activated')
                     {
                         $p3s_update->status='Activated';
                         $p3d->status='Publish';
                         $p3d->save(false);
                     }

                     if($status=='Partially Fail')
                     {
                          $p3d->status='Partially Fail';
                          $p3d->save(false);
                     }
                     if($fail_info)
                         $p3s_update->fail_reason=json_encode($fail_info);
                     if($status)
                          $p3s_update->save(false);
                 }

             }
          }
      }
      echo "<br/>";
      echo "Ended at " . date('Y-m-d H:i:s');

  }*/

    /* private static function productsync_status_update_old($response)
     {
         $status="";
         $result=false;
         if($response)
         {

             foreach ($response as $key=>$val)
             {
                 if(!isset($val['FeedType']))
                     continue;

                 if($val['FeedProcessingStatus']=='_SUBMITTED_') // result not ready yet
                     continue;

                 if(strtolower($val['FeedProcessingStatus'])=='error')
                 {
                     if($val['FeedType']=='_POST_PRODUCT_DATA_')
                     {
                         $status='Fail';
                         return $status;
                     }
                      else
                         $status='Partially Fail';
                 }
                 $result=true;
             }
             if(empty($status) && $result)
                 $status='Activated';
         }
         return $status;

     }*/

    public function actionProductsyncStatuscheck()
    {

        ////////////////////////////
        $status="";
        echo "started  at " . date('Y-m-d H:i:s');
        if(isset($_GET['channel_id']))
        {
            $channel=Channels::findOne(['id'=>$_GET['channel_id'],'is_active'=>1]);
            $p3s=Product360Status::find()->where(['shop_id'=>$_GET['channel_id'],'status'=>['Pending','Activating']])->andWhere(['not', ['steps_to_follow' => null]])->asArray()->all();
            if($p3s && $channel)
            {
                foreach($p3s as $record)
                {
                    if ($channel->marketplace == 'amazon')
                    {
                        $response=  Amazon360Util::check_feed_results($channel, json_decode($record['steps_to_follow']));
                        $p3s_update=Product360Status::findOne(['id'=>$record['id']]);
                        $p3d=Products360Fields::findOne(['id'=>$record['product_360_fieldS_id']]);
                        if($response) {
                            $p3s_update->steps_to_follow = json_encode($response);
                            $p3s_update->save(false);
                            /**
                             * check if any feed is result awaiting
                             */
                            $feed_awaiting=Amazon360Util::feed_statuses_check(json_decode($record['steps_to_follow']));
                            if($feed_awaiting['awaiting_result_step'] > 0)
                                continue; // will continue cron after specific time
                            elseif($feed_awaiting['error_step'] > 0) {
                                $status = self::productsync_error_status_update(json_decode($record['steps_to_follow']));
                            }
                            elseif($feed_awaiting['pending_step'] > 0) { // complete step of feed submission
                                Amazon360Util::ceate_product_process($p3d->id,$p3s_update->id,false);
                                continue;
                            }
                            else
                                $status=self::productsync_success_status_update(json_decode($record['steps_to_follow']));

                            if($status=='Fail')
                            {
                                $p3s_update->status='Fail';
                                $p3d->status='Fail';
                            }

                            if($status=='Activated')
                            {
                                $p3s_update->status='Activated';
                                $p3d->status='Publish';
                            }

                            if($status=='Partially Fail')
                            {
                                $p3s_update->status='Partially Fail';
                                $p3d->status='Partially Fail';

                            }
                            if($status)
                            {
                                $fail_info=Amazon360Util::check_failure_reason(json_decode($record['steps_to_follow']));
                                if($fail_info)
                                    $p3s_update->fail_reason=json_encode($fail_info);

                                $p3d->save(false);
                                $p3s_update->save(false);
                            }

                        }
                    }

                }
            }
        }
        echo "<br/>";
        echo "Ended at " . date('Y-m-d H:i:s');

    }

    private static function productsync_error_status_update($feeds_array)
    {
        $status="";
        // $result=false;
        if($feeds_array)
        {

            foreach ($feeds_array as $feed)
            {
                if($feed->status=='error')
                {
                    if($feed->feed_name=='_POST_PRODUCT_DATA_' || $feed->feed_name=='_POST_PRODUCT_RELATIONSHIP_DATA_')
                    {
                        $status='Fail';
                        return $status;
                    }
                    else
                        $status='Partially Fail';
                }
                //$result=true;
            }
            // if(empty($status) && $result)
            // $status='Activated';
        }
        return $status;

    }

    private static function productsync_success_status_update($feeds_array)
    {
        $status="";
        $complete=0;
        $pending=0;
        $error=0;
        if($feeds_array)
        {

            foreach ($feeds_array as $feed)
            {
                if($feed->status=='complete' && $feed->require=='1')
                    $complete=($complete+1);
                if($feed->status=='pending' && $feed->require=='1')
                    $pending=($pending+1);
                if($feed->status=='error' && $feed->require=='1')
                    $error=($error+1);

                //$result=true;
            }
            if($complete > 0 && $error <= 0 && $pending<=0)
                $status='Activated';
        }
        return $status;

    }
    public function actionChannelMarketplace(){
        $marketplace = HelpUtil::exchange_values('id','marketplace',$_POST['channelid'],'channels');
        return $marketplace;
    }

}

