<?php

namespace backend\util;

use app\models\CountriesAndStates;
use backend\controllers\ApiController;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelSkuMissing;
use common\models\ChannelsProducts;
use common\models\ChannelsProductsArchive;
use common\models\CompetitivePricing;
use common\models\CostPrice;
use common\models\CronJobsLog;
use common\models\CrossCheckProductPrices;
use common\models\DealsMaker;
use common\models\DealsMakerSkus;
use common\models\ExcludedSkus;
use common\models\GeneralReferenceKeys;
use common\models\OrderItems;
use common\models\Orders;
use common\models\PoDetails;
use common\models\Pricing;
use common\models\ProductDetails;
use common\models\ProductRelationsSkus;
use common\models\Products;
use common\models\ProductsRelations;
use common\models\ProductStocks;
use common\models\Settings;
use common\models\StocksDefinations;
use common\models\StocksPo;
use common\models\Subsidy;
use common\models\TempCrawlResults;
use common\models\User;
use common\models\UserRoles;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockArchive;
use common\models\WarehouseStockList;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Yii;
use yii\base\ErrorException;
use yii\db\Exception;
use yii\db\Query;
use common\models\StockPriceResponseApi;
class HelpUtil
{
    public static function _refineResponse($response)
    {
        $response = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>', '', $response);
        $searchBadTags = ['ns2:'];
        $response = str_replace($searchBadTags, '', $response);
        $r = simplexml_load_string($response);
        $json = json_encode($r);
        $refine_data = json_decode($json, true);

        return $refine_data;
    }

    /*
     * Get Channels as array
     */
    public static function getChannels($include = [], $withMarketPlace = false)
    {
        if (!empty($include))
            $channels = Channels::find()->select(['id', 'name', 'marketplace'])->andWhere(['is_active' => 1])->andWhere(['in', 'id', $include])->orderBy('id')->asArray()->all();
        else
            $channels = Channels::find()->select(['id', 'name', 'marketplace'])->andWhere(['is_active' => 1])->orderBy('id')->asArray()->all();

        if ($withMarketPlace) {
            $channels = Channels::find()->andWhere(['is_active' => 1])->orderBy('id')->asArray()->all();
            $list = [];
            foreach ($channels as $ch) {
                $list[$ch['marketplace']][] = ['id' => $ch['id'], 'name' => $ch['name']];
            }

            return $list;
        }
        return $channels;
    }
    public static function GetChannelsProductsPrices($from,$to,$skuId, $daily_crawl_results){

        $Results = GraphsUtil::GetPriceArchiveSku($skuId,$from,$to);
        //self::debug($Results);
        $redefine_results = GraphsUtil::SetDataDateWiseIndex($Results);
        //self::debug($redefine_results);
        $prefixes = GraphsUtil::GetShopsPrefixesMarketplaceWise();
        //self::debug($prefixes);
        $daily_crawl_results = GraphsUtil::SetCrawlAndArchiveDataMarketplaceAndShopWise($prefixes,$redefine_results,$daily_crawl_results);

        $redefine = [];
        foreach ( $daily_crawl_results as $marketplace=>$detail ){

            foreach ( $detail as $index=>$firstDetail){

                $redefine[$marketplace][$index]=$firstDetail;
            }
        }
        $data = $redefine;
        foreach ( $redefine as $marketplace=>$set ){

            foreach( $redefine[$marketplace] as $key=>$value ){
                if($key!='dataset')
                    continue;
                $data[$marketplace]['dataset']=[];
                foreach ( $value as $key1=>$value1 ){
                    $data[$marketplace]['dataset'][]=$value1;
                }
            }
        }
        $final_version=[];
        foreach ( $data as $marketplace=>$datasets ){
            $final_version[$marketplace]=json_encode($datasets);
        }
        return $final_version;
    }
    public static function SetDataSetPriceCamparision($from,$to,$skuId){

        $daily_crawl_results = HelpUtil::GetCrawlResults($from, $to, $skuId);
        //self::debug($daily_crawl_results);
        $getChannelsProductsPrices = self::GetChannelsProductsPrices($from, $to, $skuId,$daily_crawl_results);

        return $getChannelsProductsPrices;
    }
    public static function GetCrawlResults($from, $to, $skuid){
        $dataset=[];
        $AllDates=HelpUtil::getAllDatesBetweenTwoDates($from,$to);
        //self::debug($AllDates);
        $sql = "SELECT * from temp_crawl_results tcr WHERE added_at between '".$from."' and '".$to."' AND tcr.sku_id = '".$skuid."'";

        $data = TempCrawlResults::findBySql($sql)->asArray()->all();
        foreach ( $data as $detail ){
            $dataset[$detail['marketplace']][$detail['added_at']] = $detail['price'];
        }
        $final_result=[];
        foreach ( $dataset as $marketplace=>$data ){
            foreach( $AllDates as $key=>$date ){
                if ( isset($dataset[$marketplace][$date]) ){
                    $final_result[$marketplace]['dataset'][$date]['period']=$date;
                    $final_result[$marketplace]['dataset'][$date]['crawl_lowest_price']=$dataset[$marketplace][$date];
                }else{
                    $final_result[$marketplace]['dataset'][$date]['period']=$date;
                    $final_result[$marketplace]['dataset'][$date]['crawl_lowest_price']=0;
                }
            }
        }
        //self::debug($final_result);
        return $final_result;
    }
    public static function SetCrawlLowestHighestAverage($action,$dataset){
        $prices=[];
        foreach ( $dataset['competitors_dataset'] as $detail ){
            $prices[] = $detail['price'];
        }
        if ( $action=='lowest' ){
            $min = min($prices);
            foreach ( $dataset['competitors_dataset'] as $detail ){
                if ( $detail['price']==$min ){
                    return $detail;
                }
            }
        }
        if ( $action=='highest' ){
            $min = max($prices);
            foreach ( $dataset['competitors_dataset'] as $detail ){
                if ( $detail['price']==$min ){
                    return $detail;
                }
            }
        }
        if ( $action=='average' ){
            $avg = array_sum($prices)/count($prices);
            return number_format($avg,2);
        }
        //self::debug($prices);
    }
    private  function get_json_decode_format($response)
    {
        if($response && is_string($response)){

            $converted=json_decode($response);
            return (json_last_error() === JSON_ERROR_NONE) ? $converted :$response;
        }
        return $response;
    }


    /*
    * Get Channels as array list with sku id index
    */
    /*
     * Get Channels as array
     */
    public static function getWarehouses($include = [], $withMarketPlace = false)
    {

        $warehouses = Warehouses::find()->andWhere(['is_active' => 1])->orderBy('id')->asArray()->all();
        $list = [];
        foreach ($warehouses as $ch) {
            $list[] = ['id' => $ch['id'], 'name' => $ch['name']];
        }

        return $list;
    }
    public static function getSkuList($by = 'id', $attr = [])
    {
        $list = [];
        $skus = Products::find()->orderBy('id')->asArray()->all();

        foreach ($skus as $s) {
            if (empty($attr)) {
                if ($by == 'id')
                    $list[$s['id']] = $s['sku'];
                else
                    $list[$s['sku']] = $s['id'];
            } else {
                foreach ($attr as $atr) {
                    $atr = ($atr == 'fbl') ? 'is_fbl' : $atr;
                    $atr = ($atr == 'rccp_cost') ? 'rccp' : $atr;
                    if ($by == 'id')
                        $list[$s['id']][$atr] = $s[$atr];
                    else
                        $list[$s['sku']][$atr] = $s[$atr];
                }
            }

        }

        return $list;
    }


    // get sku_id from db against the sku code provided ///

    public static function getSkuId($params=[])
    {
       // return "";  // for time being not used id
        if(isset($params['sku']))
        {
            $query = Products::find()->select(['id'])->where(['sku' => $params['sku'], 'channel_id' => $params['channel_id']])->one();
            return isset($query->id ) ? $query->id:"";
        }
        return "";
    }

    // get product_id from db against the sku code provided from channel products table ///

    public static function getChannelProductsProductId($params=[])
    {


        if(isset($params['sku']))
        {
            $query = ChannelsProducts::findone(
                [
                    'channel_sku' => (gettype($params['sku'])=='object') ? '' : $params['sku'],
                    'channel_id' => $params['channel_id']
                ]
            );
            return isset($query->product_id ) ? $query->product_id:"";
        }
        return "";
    }

    /*
     * Get SkU from STOCKS table
     */
    public static function getStkSkuList($by = 'id', $attr = [])
    {
        $list = [];
        $skus = ProductDetails::find()->orderBy('id')->asArray()->all();

        foreach ($skus as $s) {
            if (empty($attr)) {
                if ($by == 'id')
                    $list[$s['id']] = $s['isis_sku'];
                else
                    $list[$s['isis_sku']] = $s['id'];
            } else {
                foreach ($attr as $atr) {
                    if ($by == 'id')
                        $list[$s['id']][$atr] = $s[$atr];
                    else
                        $list[$s['isis_sku']][$atr] = $s[$atr];
                }
            }

        }

        return $list;
    }

    /*
     * Get Group Category Name from Cost Price
     */

    public static function getGroupCategory($categoryId = 0)
    {
        $category = Category::find()->select(['name'])->where(['id' => $categoryId])->asArray()->one();
        if ($category)
            return $category['name'];
        else
            return null;
    }

    // custom made query for pricing sheet
    public static function getSkuDetails($date)
    {
        ini_set('memory_limit', '-1');
        $result = self::GetPricingCompetitive($params = []);
        $refine = [];
        $exists = [];
        foreach ($result as $re) {
            if ($re['ao_margins'] == '100')
                continue;
            if (!isset($refine[$re['channel_id']][$re['sku_id']])) {
                $re['low_price'] = str_replace(',', '', $re['low_price']);

                $re['today_update'] = ($re['created_at'] == date('Y-m-d')) ? '1' : '0';
                $settings = Settings::find()->where(['name' => 'shipping_cost'])->one();
                $value = json_decode($settings->value, true);
                /*
             * Adding Shipping cost base on warehouse stock availability
             */
                $fblShip = $value['fbl_sc'] + $value['fbl_ppc'] + $value['fbl_wc'];
                $isisShip = $value['isis_sc'] + $value['isis_ppc'] + $value['isis_wc'];

                // check stock avaiable in FBL
                $is_fbl = 0;
                $stock = HelpUtil::getFblStock($re['sku_id'], $re['channel_id']);
                //self::debug($stock);
                if ($stock > 0) {
                    $is_fbl = 1;
                }

                $re['shipping'] = ($is_fbl == 1) ? $fblShip : $isisShip;


                $act = $re['cost'];
                $re['cost'] = HelpUtil::skuCostPrice($re['sku_name'], 1, $re['cost']);
//                die;
                $scost = $re['cost'];
                $re['cost'] = $re['cost'] + $re['extra_cost'];

                /*
             * Add both commission cost and payment gateway charges
             */

                $getCharges = DealsUtil::catChildParent($re['channel_id'],$re['sub_category']);

                if ( $getCharges==NULL ){
                    $re['commission'] = 0;
                    $re['pg_commission'] = 0;
                }else{
                    $re['commission'] = $getCharges['commission'];
                    $re['pg_commission'] = $getCharges['pg_commission'];
                }

                //self::debug($re);
                $corg = $re['commission'];
                $re['commission'] = (double)$re['commission'] + (double)$re['pg_commission'];


                //$re['subsidy']  = $params['subsidy'];
                $refine[$re['channel_id']][$re['sku_id']] = [
                    'extra_cost' => $re['extra_cost'],
                    'actual_cost' => $act,
                    'sku_cost' => $scost,
                    'low_price' => $re['low_price'],
                    'cost' => $re['cost'],
                    'rccp_cost' => $re['rccp'],
                    'sub_category' => $re['sub_category'],
                    'category' => $re['category'],
                    'commission' => $re['commission'],
                    'shipping' => $re['shipping'],
                    'margins' => $re['margins'],
                    'subsidy' => $re['subsidy'],
                    'base_price_at_zero_margin' => number_format(self::_formulaA($re), 2),
                    'base_price_before_subsidy' => number_format(self::_formulaF($re), 2),
                    'base_price_after_subsidy' => number_format(self::_formulaB($re), 2),
                    'gross_profit' => number_format(self::_formulaC($re), 2),
                    'sales_price' => number_format(self::_formulaD($re), 2),
                    'sales_margins' => number_format(self::_formulaE($re), 2) . '%',
                    'low_price_margins' => number_format(self::_formulaI($re, number_format(self::_formulaD($re), 2)), 2) . '%',
                    'today_update' => $re['today_update'],
                ];

            }

        }
        //self::debug($refine);
        if ($result) {
            // insert or update into AO PRICING table.
            //$include = [1, 2, 3, 5, 6, 9, 10, 11];
            $channelList = \backend\util\HelpUtil::getChannels();
            //self::debug($channelList);
            foreach ($channelList as $cl) {
                if (isset($refine[$cl['id']])) {
                    $skuList = $refine[$cl['id']];

                    foreach ($skuList as $k => $sk) {
                        if (isset($refine[$cl['id']][$k])) {
                            $p = Pricing::find()->where(['channel_id' => $cl['id'], 'sku_id' => $k, 'added_at' => $date])->one();
                            if (!$p) {
                                $p = new Pricing();
                            }
                            $p->sku_id = $k;
                            $p->sub_category = $sk['sub_category'];
                            $p->channel_id = $cl['id'];
                            $p->low_price = $sk['low_price'];
                            $p->base_price_at_zero_margins = $sk['base_price_at_zero_margin'];
                            $p->base_price_before_subsidy = $sk['base_price_before_subsidy'];
                            $p->base_price_after_subsidy = $sk['base_price_after_subsidy'];
                            $p->gross_profit = $sk['gross_profit'];
                            $p->sale_price = $sk['sales_price'];
                            $p->margins_low_price = $sk['sales_margins'];
                            $p->margin_sale_price = $sk['low_price_margins'];
                            $p->loss_profit_rm = '0';
                            $p->added_at = $date;
                            $p->is_update_today = $sk['today_update'];
                            $p->save(false);
                        }


                    }
                }
            }
        }

        return $refine;
    }

    public static function _callLzdRequestMethod($params)
    {
        require_once __DIR__ . "/../../backend/util/lzdop/LazopSdk.php";

        $url = "https://auth.lazada.com/rest";
        $appkey = $params['app_key'];
        $appSecret = $params['app_secret'];
        $accessToken = $params['access_token'];
        $action = $params['action'];
        $method = $params['method'];
        $c = new \LazopClient($url, $appkey, $appSecret);
        $request = new \LazopRequest($action, $method);
        foreach ($params['params'] as $k => $v) {
            $request->addApiParam($k, $v);
        }
        return $c->execute($request, $accessToken);

    }
    public function GetLazadaSkuInfo( $channel_id , $sku_text ){

        $ch = Channels::findOne(['id' => $channel_id,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);

        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['params']['search'] = $sku_text;
        $customParams['params']['filter'] = 'live';
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['method'] = 'GET';
        $customParams['action'] = '/products/get';
        $get_detail = self::_callLzdRequestMethod($customParams);
        $product_detail = json_decode($get_detail);
        return $product_detail;
    }
    public static function calculatePrice4Subsidy($params, $start = true)
    {
        $response = [];
        $skuList = self::getSkuList('id', ['rccp_cost']);
        $skuNames = self::getSkuList('id');
        $date = date('Y-m-d');
        $values = self::_runQuery($params);
        if(empty($values))
            $values = self::_runQuery($params,true);
        $values = $values[0];

        if ($start)
            $values['low_price'] = $params['price_sell']; //+ ($params['price_sell'] * ($params['subsidy'] / 100));
        else{
            $values['low_price'] = (isset($params['activated_deal_price']) && $params['activated_deal_price'] != 0) ? $params['activated_deal_price']:$values['low_price'];
            $start=( isset($params['activated_deal_price']) && $params['activated_deal_price'] != 0 ) ? true : false;
        }


        // get fbl shipping value from settings

        $settings = Settings::find()->where(['name' => 'shipping_cost'])->one();
        $value = json_decode($settings->value, true);

        /*
        * Adding Shipping cost base on warehouse stock availability
        */

        $fblShip = $value['fbl_sc'] + $value['fbl_ppc'] + $value['fbl_wc'];
        $isisShip = $value['isis_sc'] + $value['isis_ppc'] + $value['isis_wc'];

        // check stock avaiable in FBL

        $is_fbl = 0;
        $stock = HelpUtil::getFblStock($params['sku_id'], $params['channel_id']);
        if ($stock > 0) {
            $is_fbl = 1;
        }
        $values['shipping'] = ($is_fbl == 1) ? $fblShip : $isisShip;


        $values['cost'] = $values['cost'] + $values['extra_cost'];

        /*
        * Add both commission cost and payment gateway charges
        */

        $corg = $values['commission'];
        $values['commission'] = (double)$values['commission'] + (double)$values['pg_commission'];

        $refine[$params['channel_id']][$params['sku_id']] = [
            'low_price' => $values['low_price'],
            'cost' => $values['cost'],
            'rccp_cost' => $values['rccp'],
            'sub_category' => $values['sub_category'],
            'category' => $values['category'],
            'commission' => $values['commission'],
            'shipping' => $values['shipping'],
            'margins' => $values['margins'],
            'subsidy' => $values['subsidy'],
            'base_price_at_zero_margin' => number_format(self::_formulaA($values), 2),
            'base_price_before_subsidy' => number_format(self::_formulaF($values), 2),
            'base_price_after_subsidy' => number_format(self::_formulaB($values), 2),
            'gross_profit' => number_format(self::_formulaC($values), 2),
            'sales_price' => ($start) ? number_format(self::_formulaD4deal($values), 2) : number_format(self::_formulaD($values), 2),
            'sales_margins' => number_format(self::_formulaE($values), 2) . '%',
            'low_price_margins' => number_format(self::_formulaI($values, ($start) ? number_format(self::_formulaD4deal($values), 2) : number_format(self::_formulaD($values), 2)), 2) . '%',
        ];

        $skuList = $refine[$params['channel_id']];
        foreach ($skuList as $k => $sk) {
            if (isset($refine[$params['channel_id']][$k])) {
                $p = Pricing::find()->where(['channel_id' => $params['channel_id'], 'sku_id' => $k, 'added_at' => $date])->one();
                if (!$p) {
                    $p = new Pricing();
                }
                $p->sku_id = $k;
                $p->category = $sk['category'];
                $p->sub_category = $sk['sub_category'];
                $p->channel_id = $params['channel_id'];
                $p->low_price = $sk['low_price'];
                $p->base_price_at_zero_margins = $sk['base_price_at_zero_margin'];
                $p->base_price_before_subsidy = $sk['base_price_before_subsidy'];
                $p->base_price_after_subsidy = $sk['base_price_after_subsidy'];
                $p->gross_profit = $sk['gross_profit'];
                $p->sale_price = $sk['sales_price'];
                $p->margins_low_price = $sk['sales_margins'];
                $p->margin_sale_price = $sk['low_price_margins'];
                $p->loss_profit_rm = '0';
                $p->added_at = $date;
                $p->is_update_today = '1';
                $p->save(false);

            }

            // update to channels
            $channel = Channels::find()->where(['id' => $params['channel_id'],'is_active'=>'1'])->one();
            $skuname = $skuNames[$params['sku_id']];
            if ($channel->marketplace == 'lazada') {
                $auth_params = json_decode($channel->auth_params, true);
                $customParams['app_key'] = $channel->api_key;
                $customParams['app_secret'] = $channel->api_user;
                $customParams['access_token'] = $auth_params['access_token'];
                $customParams['method'] = 'POST';
                $customParams['action'] = '/product/price_quantity/update';
                $price = str_replace(',', '', $p->sale_price);
                $postXML = "<Request>\r\n    <Product>\r\n        <Skus>\r\n          <Sku>\r\n                <SellerSku>" . $skuname . "</SellerSku>\r\n                <SalePrice>" . $price . "</SalePrice>\r\n                <SaleStartDate>" . date('Y-m-d') . "</SaleStartDate>\r\n                <SaleEndDate>" . date('Y-m-d', strtotime("+1 month")) . "</SaleEndDate>\r\n                <Price/>\r\n            </Sku>\r\n        </Skus>\r\n    </Product>\r\n</Request>";
                $customParams['params']['payload'] = $postXML;
                $response[$channel->name][$skuname] = self::_callLzdRequestMethod($customParams);
                // get the prodcut information from shop, and save the price in cross_check_product_prices for cros check
                $sku_info=self::GetLazadaSkuInfo($params['channel_id'],$skuname);
                ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$price,$response[$channel->name][$skuname],$sku_info,$params['deal_id']);
            }
            else if ($channel->marketplace == 'street') {

                $access = ['Content-Type: application/xml', 'openapikey: ' . $channel->api_key];

                $pd = ChannelsProducts::find()->select(['sku'])->where(['channel_sku' => $skuname, 'channel_id' => $params['channel_id']])->one();
                if ($pd) {
                    $prdId = $pd->sku;
                    $price = str_replace(',', '', $p->sale_price);
                    $rccp = $skuList[$params['sku_id']]['rccp_cost'];
                    $subsidy = $params['subsidy'];
                    $salePrice = $price + ($price * ($subsidy / 100));
                    $discountPrice = $rccp - $salePrice;
                    $monthDate = date('d/m/Y', strtotime("+1 month"));
                    $postData = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r\n<Product>\r\n<selPrc>" . $rccp . "</selPrc>\r\n<cuponcheck>Y</cuponcheck>\r\n<dscAmtPercnt>" . $discountPrice . "</dscAmtPercnt>\r\n<cupnDscMthdCd>01</cupnDscMthdCd>\r\n<cupnUseLmtDyYn>N</cupnUseLmtDyYn>\r\n<cupnIssEndDy>" . $monthDate . "</cupnIssEndDy>\r\n</Product>";
                    $apiUrlx = "http://api.11street.my/rest/prodservices/product/priceCoupon/$prdId";
                    $responsex = ApiController::_ApiCall($apiUrlx, $access, 'POST', '', $postData);
                    $refinex = ApiController::_refineResponse($responsex);

                    $response[$channel->name][$params['sku_id']] = $responsex;

                    // get the prodcut information from shop, and save the price in cross_check_product_prices for cros check
                    $sku_info = ApiController::GetStreetSkuInfo($params['channel_id'],$prdId);
                    ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$rccp,$response[$channel->name][$params['sku_id']],$sku_info,$params['deal_id']);
                }
            }
            else if ($channel->marketplace == 'shopee') {
                // Shopee needs campaign id, Lets set the campaign id.
                if ($params['channel_id'] == '2')
                    $discountId = '1007801477';
                elseif ($params['channel_id'] == '11')
                    //$discountId = '1012270682'; old one
                    $discountId = '1025860482';
                else if ($params['channel_id'] == '16')
                    //$discountId = '1007883093'; old one
                    $discountId = '1025841041';
                $pd = ChannelsProducts::find()->select(['sku'])->where(['channel_sku' => $skuname, 'channel_id' => $params['channel_id']])->one();
                $variation_id = ChannelsProducts::find()->select(['variation_id'])->where(['channel_sku' => $skuname, 'channel_id' => $params['channel_id']])->one();
                if ($pd) {
                    $items = [];
                    $prdId = $pd->sku;
                    $price = str_replace(',', '', $p->sale_price);
                    $items[] = ['item_id' => (int)$prdId, 'item_promotion_price' => (float)($price)];
                    if ( $variation_id->variation_id!='' ){
                        $variations = ['variation_id'=> (int) $variation_id->variation_id,'variation_promotion_price' => (float)$price];
                        $items[0]['variations'][] = $variations;
                    }
                    $apiKey = $channel->api_key;
                    $apiUser = explode('|', $channel->api_user);
                    $now = new  \DateTime();
                    $postFields = [
                        'discount_id' => (int)$discountId,
                        'items' => $items,
                        'partner_id' => (int)$apiUser[0],
                        'shopid' => (int)$apiUser[1],
                        'timestamp' => $now->getTimestamp()

                    ];
                    $url = "https://partner.shopeemobile.com/api/v1/discount/items/update";
                    $postFields = json_encode($postFields);
                    $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
                    $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];

                    $response['blipShopee'][$prdId] = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);

                    $sku_info=ApiController::GetShopeeSkuInfo($params['channel_id'],$items[0]['item_id']);
                    ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],(float)($price),$response['blipShopee'][$prdId],$sku_info,$params['deal_id']);

                }
            }
        }

        return $response;
    }

    public static function SyncDealPrices($params, $start = true){
//        self::debug($params);
        if ($start) // if deal is starting
            $price = $params['price_sell'];
        else {
            $cost_price = Products::find()->where(['id'=>$params['sku_id']])->all();
            $price = ($params['activated_deal_price']) != 0 ? $params['activated_deal_price'] : $cost_price[0]->cost;
        }

        $channel = Channels::find()->where(['id' => $params['channel_id'],'is_active' => 1])->one();

        if ( $channel->marketplace == 'prestashop' )
        {

            $ChannelProduct = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->all(); // Get the ids to update on shop

            $combinationId = ( isset($ChannelProduct[0]->variation_id) && $ChannelProduct[0]->variation_id!='' ) ? $ChannelProduct[0]->variation_id : 0;

            $response = [];
            if($start){

                if ($combinationId){
                    if ( $params['customer_type']=='B2C' ){
                       // die('come');
                        $customerGroup = PrestashopUtil::GetB2cCustomerGroups($channel->id);
                       // self::debug($customerGroup);
                        foreach ( $customerGroup as $groupId ){
                            $UpdatePrice = PrestashopUtil::CreateSpecificPrice($channel->id, $ChannelProduct[0]->sku, $combinationId, $groupId,['from'=>$params['from'],'to'=>$params['to']],$params['price_sell'],$params['discount_type'],$params['discount']);
                            $response['groupId-'.$groupId]=$UpdatePrice;
                        }
                    }else{
                        $customerGroup = PrestashopUtil::GetB2bCustomerGroups($channel->id);
                        foreach ( $customerGroup as $groupId ){
                            $UpdatePrice = PrestashopUtil::CreateSpecificPrice($channel->id, $ChannelProduct[0]->sku, $combinationId, $groupId,['from'=>$params['from'],'to'=>$params['to']],$params['price_sell'],$params['discount_type'],$params['discount']);
                            $response['groupId-'.$groupId]=$UpdatePrice;
                        }
                    }
                }else{
                    $response=['status'=>'SKU not found on the shop'];
                }

            }
            else{
                if ( $params['settings']!='' ){
                    $dk_settings=json_decode($params['settings'],true);
                    if ( isset($dk_settings['status']) && $dk_settings['status']=='SKU not found on the shop' ){
                        $response=['status'=>'SKU not found on the shop'];
                    }else{
                        foreach ( $dk_settings as $detail ){
                            if ( isset($detail['specific_price_id']) )
                            {
                                $response['groupId-'.$detail['specific_price_id']] = PrestashopUtil::DeleteSpecifcPrice($channel->id,$detail['specific_price_id']);
                            }
                        }
                    }

                }
            }

            $json_settings=json_encode($response);
            $updateSkuSetting = DealsMakerSkus::findOne($params['dm_sku_pk']);
            $updateSkuSetting->settings = $json_settings;
            $updateSkuSetting->update();

            ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$price,json_encode($response),$params,$params['deal_id']);
            //self::debug($response);
            return $response;

        }
       /* elseif($channel->marketplace == 'ebay')
        {
            $cp = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one(); // Get the ids to update on shop
            $xml = '';
            if($cp)
            {
                    $xml .= '<InventoryStatus>
                        <ItemID>'.$cp['sku'].'</ItemID>
                        <SKU>'.$cp['channel_sku'].'</SKU>
                        <StartPrice currencyID="USD">'.$price.'</StartPrice>
                      </InventoryStatus>';

                $api_response= EbayUtil::UpdatePrice($channel->id, $xml);
//                self::debug($api_response);
                ApiController::SaveCrossCheckPriceUpdate($params['sku_id'], $params['channel_id'], $price, $api_response, $params, $params['deal_id']);
                return $api_response;
            }
        }*/
        elseif($channel->marketplace == 'daraz')
        {
            $cp = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one(); // Get the ids to update on shop
            $combinationId = ($cp->variation_id!='') ? $cp->variation_id : 0;
            //self::debug($cp);
            if ($cp)
            {
                $counter=1; //to update 50 stock per call
                $body="";
                $list_log=array();  // what stock and skus requested from our side so to store in db log
                $total_skus=count($cp);

                    $body .='<Sku>
                                    <SellerSku>'.$cp['sku'].'</SellerSku>
                                    <Price>'.round($price).'</Price>
                                </Sku>';
                    $list_log['sku_stock'][]=array('sku'=>$cp['sku'],'price'=>$price);
                if($total_skus >=50 &&  fmod($counter,50) == 0 )  //reminder equals 0 then update 50 batch at once
                {
                    $response=DarazUtil::updateChannelStockPrice($channel,$body);
                    ApiController::SaveCrossCheckPriceUpdate($params['sku_id'], $params['channel_id'], $price, $response, $params, $params['deal_id']);
                    $db_log[]=[
                        'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                        'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                    ];
                    // $this->_saveResponse('price',$Channel_Detail['id'],$response,$list);
                    $body=$list_log="";
                }
                if($total_skus < 50 )
                {

                    $response=DarazUtil::updateChannelStockPrice($channel,$body);
//                    self::debug($response);
                    ApiController::SaveCrossCheckPriceUpdate($params['sku_id'], $params['channel_id'], $price, $response, $params, $params['deal_id']);
                    $db_log[]=[
                        'request'=>array($list_log,'additional_info'=>array('info'=>'bulk updated')),
                        'response'=> self::get_json_decode_format($response), // if json encoded will return decoded format else will returrn same
                    ];
                    //$this->_saveResponse('Stocks',$Channel_Detail['id'],$response,$list);

                }
                return $response;

            }
        }
        elseif($channel->marketplace == 'shopify')
        {
            $cp = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one(); // Get the ids to update on shop
            $combinationId = ($cp->variation_id!='') ? $cp->variation_id : 0;
            if($cp) {
                    $api_response = ShopifyUtil::updateDealChannelPrice($channel, array('sku' => $cp->channel_sku,'product_id'=>$cp->product_id ,'price' => $price,'variant_id' => $combinationId));
//                    self::debug($api_response);
                    ApiController::SaveCrossCheckPriceUpdate($params['sku_id'], $params['channel_id'], $price, $api_response, $params, $params['deal_id']);
                    return $api_response;
            }
        }
        elseif($channel->marketplace == 'bigcommerce')
        {

            $cp = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one(); // Get the ids to update on shop

            $combinationId = ($cp->variation_id!='') ? $cp->variation_id : 0; // If No Variation then Variation id=0
            if($cp)
            {
                $UnsyncSkus = self::_getExcludedSkus($channel->id,'Price');
                $api_response=BigCommerceUtil::UpdateDealChannelPrice($channel,array( 'sku_id'=>$cp->id,'channel_sku'=> $cp->sku,'channels_products_sku'=> $cp->sku,'variation_id'=>$combinationId,'price'=> $price), $UnsyncSkus);
                ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$price,$api_response,$params,$params['deal_id']);
                return $api_response;
            }
        }
        elseif ($channel->marketplace == 'lazada') {
            $cp = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one(); // Get the ids to update on shop
            if($cp)
            {
                $data  = [
                            [
                                'channel_sku'=> $cp->channel_sku,
                                'price'=> $price
                            ]
                         ];
                $api_response=LazadaUtil::UpdateDealChannelPrice($channel,$data);
                ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$price,$api_response,$params,$params['deal_id']);
                return $api_response;
            }else{
                $api_response = ['product not found on shop'];
                return $api_response;
            }

        }
        elseif ($channel->marketplace == 'shopee')
        {

            $discountId = json_decode($params['deal_extra_params'], true)['shopee_discount_id'];
            $pd = ChannelsProducts::find()->where(['product_id'=>$params['sku_id'],'channel_id'=>$channel->id])->one();
            $items = [];
            if ($pd)
            {

                $items[] = [
                    'item_id' => (int)$pd->sku,
                    'item_promotion_price' => (float)($price)
                ];

                if ( $pd->variation_id!='' ){
                    $variations = ['variation_id'=> (int) $pd->variation_id,'variation_promotion_price' => (float)$price];
                    $items[0]['variations'][] = $variations;
                }

                $data = [
                            //'discount_id'=>(integer) 1080070724,
                            'discount_id'=>(integer) $discountId,
                            'items' => $items
                        ];
                if($start) // if deal is starting
                    $api_response=ShopeeUtil::updateDiscountPrice($channel,$data);
                else{  // if deal is ending
                    $data = [
                        'discount_id'=>(integer) $discountId,
                        'item_id' => (int)$pd->sku
                    ];
                    if($pd->variation_id)
                        $data['variation_id']=(int) $pd->variation_id;

                    $api_response=ShopeeUtil::delete_discount_item($channel,$data);
                }

                ApiController::SaveCrossCheckPriceUpdate($params['sku_id'],$params['channel_id'],$price,$api_response,$params,$params['deal_id']);
                return $api_response;
            }else{
                $api_response = ['product not found on shop'];
                return $api_response;
            }
        }

    }

    public static function getSkuInfo($params, $isCheckCostPrice = false,$skip = false)
    {

        // get fbl shipping value from settings
        $settings = Settings::find()->where(['name' => 'shipping_cost'])->one();

        $value = json_decode($settings->value, true);

        $result = self::_runQuery($params,$skip);
        $refine = [];
        foreach ($result as $re) {
            $params['subsidy'] = isset($params['subsidy']) ? $params['subsidy'] : $re['subsidy'];
            $params['subsidy'] = (isset($params['subsidy']) && $params['subsidy'] != '') ? $params['subsidy'] : 0;
            $re['low_price'] = $params['price_sell'] + ($params['price_sell'] * ($params['subsidy'] / 100));

            /*
             * Adding Shipping cost base on warehouse stock availability
             */
            $fblShip = $value['fbl_sc'] + $value['fbl_ppc'] + $value['fbl_wc'];
            $isisShip = $value['isis_sc'] + $value['isis_ppc'] + $value['isis_wc'];

            $re['shipping'] = ($params['fbl'] == 1) ? $fblShip : $isisShip;


            $am = $re['ao_margins'];
            $re['ao_margins'] = isset($params['allowed_margin']) ? $params['allowed_margin'] : $re['ao_margins'];
            $act = $re['cost'];
            $re['cost'] = (isset($params['cost']) && $params['cost'] != "") ? $params['cost'] : $re['cost'];
            if ($isCheckCostPrice)
                $re['cost'] = HelpUtil::skuCostPrice($re['sku_name'], $params['qty'], $re['cost']);
            $scost = $re['cost'];
            $re['cost'] = $re['cost'] + $re['extra_cost'];

            /*
             * Add both commission cost and payment gateway charges
             */
            $corg = $re['commission'];
            $re['commission'] = (double)$re['commission'] + (double)$re['pg_commission'];


            //$re['subsidy']  = $params['subsidy'];
            $refine = [
                'low_price' => $re['low_price'],
                'cost' => $re['cost'],
                'extra_cost' => $re['extra_cost'],
                'actual_cost' => $act,
                'sku_cost' => $scost,
                'pg_commission' => $re['pg_commission'] . " %",
                'commission_org' => $corg . " %",
                'commission' => $re['commission'] . " %",
                'shipping' => $re['shipping'],
                'margins' => $re['ao_margins'],
                'margins_' => $am,
                'gross_profit' => number_format(self::_formulaC($re), 2),
                'sales_margins' => number_format(self::_formulaE($re), 2) . '%',
                'sales_margins_rm' => 'RM ' . number_format(self::_formulaG($re), 2),
                'sales_margins_qty_rm' => 'RM ' . number_format(self::_formulaG($re), 2) * (int) $params['qty'],
                'price_after_subsidy' => $re['low_price'],
                'subsidy' => $params['subsidy'],
                'sales_price' => number_format(self::_formulaD($re), 2),
                'customer_pays' => "RM " . round($re['low_price'] - ($re['low_price']) * ($params['subsidy'] / 100), 2),
                'stocks' => HelpUtil::getStocksInfoBySku($re['sku_id'], true)
            ];

        }

        return $refine;
    }
    private static function GetPricingCompetitive($params=[]){

        $sql = "(
                SELECT p.sku AS sku_name, cp.channel_id, cp.sku_id, p.`is_fbl` AS fbl, cp.low_price, p.`cost`, 
                p.extra_cost, p.`rccp`, p.`sub_category` AS 'category', 
                s.`margins`, s.ao_margins, s.`subsidy`,p.sub_category, cp.created_at
                FROM `competitive_pricing` cp
                INNER JOIN `products` p ON p.`id` = cp.`sku_id`
                INNER JOIN category c ON c.`id` = p.`sub_category`
                INNER JOIN subsidy s ON s.`sku_id` = p.`id` AND s.`channel_id` = cp.`channel_id`
                WHERE p.is_active = '1' AND p.cost != '' 
                AND cp.created_at = DATE(NOW())
                ORDER BY cp.created_at DESC
                )
                UNION
                (
                SELECT p.sku AS sku_name, '22' AS channel_id, p.id AS sku_id, p.`is_fbl` AS fbl, IFNULL(p.`promo_price`,p.rccp) AS low_price, p.`cost`,
                p.extra_cost, p.`rccp`, p.`sub_category` AS 'category',
                s.`margins`, s.ao_margins, s.`subsidy`,p.sub_category, CURDATE() AS created_at
                FROM `products` p
                INNER JOIN category c ON c.`id` = p.`sub_category`
                INNER JOIN subsidy s ON s.`sku_id` = p.`id` AND s.`channel_id` = 22
                WHERE p.is_active = '1' AND p.cost != ''
                AND p.`id` NOT IN (
                                                                                                        SELECT sku_id
                                                                                                        FROM `competitive_pricing`
                                                                                                                                     GROUP BY sku_id)
                ORDER BY p.selling_status DESC
                )";


        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }
    private static function _runQuery($params,$skip = false)
    {
        $connection = Yii::$app->db;
        if($skip)
        {
            $sql = "SELECT
              p.sku AS sku_name, 
              cd.channel_id,
              p.id AS sku_id,
              p.`is_fbl` as fbl,
              p.cost AS low_price,
              p.`cost`,
              p.extra_cost,
              p.`rccp`,
              p.`sub_category` AS 'category',
              c.`main_category_id` AS 'sub_category',
              cd.`commission`,
              cd.pg_commission,
              cd.`shipping`,
              s.`margins`,
              s.ao_margins,
              s.`subsidy`
            FROM
               `products` p 
              INNER JOIN category c 
                ON c.`id` = p.`sub_category` 
              INNER JOIN `channels_details` cd 
                 ON (cd.`category_id` = c.`group_category_id`  OR cd.`category_id`  = c.id) AND cd.`channel_id` = {$params['channel_id']}
              INNER JOIN subsidy s 
                ON s.`sku_id` = p.`id` AND s.`channel_id` = {$params['channel_id']}";
            if ($params)
                $sql .= " WHERE p.id = '" . $params['sku_id'] . "'
             GROUP BY p.id ";
            else
                $sql .= " WHERE p.is_active = '1' AND p.cost != '' AND p.sub_category <> '167'  ORDER BY cp.created_at DESC, p.selling_status DESC ";

        }
        else {
            $sql = "(SELECT
              p.sku AS sku_name, 
              cp.channel_id,
              cp.sku_id,
              p.`is_fbl` as fbl,
              cp.low_price,
              p.`cost`,
              p.extra_cost,
              p.`rccp`,
              p.`sub_category` AS 'category',
              c.`main_category_id` AS 'sub_category',
              cd.`commission`,
              cd.pg_commission,
              cd.`shipping`,
              s.`margins`,
              s.ao_margins,
              s.`subsidy`,
              cp.created_at
            FROM
              `competitive_pricing` cp 
              INNER JOIN `products` p 
                ON p.`id` = cp.`sku_id` 
              INNER JOIN category c 
                ON c.`id` = p.`sub_category` 
              INNER JOIN `channels_details` cd 
                ON cd.`category_id` = c.`group_category_id` AND cd.`channel_id` = cp.`channel_id`
              INNER JOIN subsidy s 
                ON s.`sku_id` = p.`id` AND s.`channel_id` = cp.`channel_id`";
            if ($params)
                $sql .= " WHERE p.id = '" . $params['sku_id'] . "' AND cp.`channel_id` = '" . $params['channel_id'] . "'
             GROUP BY cp.`sku_id` , cp.`channel_id`)";
            else {
                $sql .= " WHERE p.is_active = '1' AND p.cost != '' AND p.sub_category <> '167'  AND cp.created_at = DATE(NOW())  ORDER BY cp.created_at DESC)";
                $sql .= "UNION 
                        (SELECT 
                          p.sku AS sku_name,
                          '1' AS channel_id,
                          p.id AS sku_id,
                          p.`is_fbl` AS fbl,
                          IFNULL(p.`promo_price`,p.rccp) AS low_price,
                          p.`cost`,
                          p.extra_cost,
                          p.`rccp`,
                          p.`sub_category` AS 'category',
                          c.`main_category_id` AS 'sub_category',
                          cd.`commission`,
                          cd.pg_commission,
                          cd.`shipping`,
                          s.`margins`,
                          s.ao_margins,
                          s.`subsidy`,
                          CURDATE() AS created_at 
                        FROM
                          `products` p 
                          INNER JOIN category c 
                            ON c.`id` = p.`sub_category` 
                          INNER JOIN `channels_details` cd 
                            ON cd.`category_id` = c.`group_category_id` 
                            AND cd.`channel_id` = '1'
                          INNER JOIN subsidy s 
                            ON s.`sku_id` = p.`id` 
                            AND s.`channel_id` = 1
                        WHERE p.is_active = '1' 
                          AND p.cost != '' 
                          AND p.sub_category <> '167' 
                          AND p.`id` NOT IN (SELECT sku_id FROM `competitive_pricing` GROUP BY sku_id)
                        ORDER BY p.selling_status DESC )";
            }

        }

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }

    // generate formula  for base price zero margin / base price after subsidy
    private function _formulaA($re)
    {
        try {
            //   $formulaA = (($re['cost']/(1-($re['margins']/100)))+$re['shipping'])/(1-($re['commission']/100));
            $formulaA = (($re['cost'] / (1 - $re['ao_margins'] / 100)) + $re['shipping']) / (1 - $re['commission'] / 100);
            //$formulaA = number_format($formulaA,2);
            return $formulaA;
        } catch (ErrorException $er) {
            var_dump($re);
            die();
        }
    }

    private function _formulaF($re)
    {
        //   $formulaA = (($re['cost']/(1-($re['margins']/100)))+$re['shipping'])/(1-($re['commission']/100));
        $formulaA = (($re['cost'] / (1 - $re['ao_margins'] / 100)) + $re['shipping']) / (1 - $re['commission'] / 100);
        //$formulaA = number_format($formulaA,2);
        return $formulaA;
    }

    // generate formula for base price after subsidy
    private function _formulaB($re)
    {
        if (strpos($re['subsidy'], 'RM') === false)
            $subsidy = $re['subsidy'] / 100;
        else
            $subsidy = str_replace('RM ', '', $re['subsidy']);

        $formulaF = self::_formulaF($re);
        $formulaA = self::_formulaA($re);
        if ($re['channel_id'] == 1)
            $formulaB = $formulaF;
        else {
            $formulaB = $formulaF - ($formulaF * ($subsidy));
            //  $formulaB = number_format($formulaB,2);
        }
        return $formulaB;
    }

    // gross profit
    public static function _formulaC($re)
    {
        $formulaC = $re['low_price'] - (($re['low_price'] * ($re['commission'] / 100)) + $re['shipping']);
        // $formulaC = number_format($formulaC,2);
        return $formulaC;
    }

    private function _formulaH($re, $salePrice)
    {
        $formulaC = $salePrice - (($salePrice * ($re['commission'] / 100)) + $re['shipping']);
        // $formulaC = number_format($formulaC,2);
        return $formulaC;
    }


    // Sales price for channels
    private function _formulaD($re)
    {
        if (strpos($re['subsidy'], 'RM') === false)
            $subsidy = $re['subsidy'] / 100;
        else
            $subsidy = str_replace('RM ', '', $re['subsidy']);

        $formulaB = self::_formulaB($re);
        $formulaA = self::_formulaA($re);
        $formulaF = self::_formulaF($re);
        if ($re['channel_id'] != '1') {
            if ($formulaB < $re['low_price']) {
                $formulaD = ($re['low_price'] + 0.5) + (($subsidy) * $formulaF);
                // $formulaD = number_format($formulaD,2);
            } else {
                $formulaD = $formulaA;
                //   $formulaD = number_format($formulaD,2);
            }
        } else {
            if ($formulaF < $re['low_price']) {
                $formulaD = $re['low_price'] + 0.5;
                // $formulaD = number_format($formulaD,2);
            } else {
                $formulaD = $formulaA;
                // $formulaD = number_format($formulaD,2);
            }
        }


        return $formulaD;
    }

    private function _formulaD4deal($re)
    {
        if (strpos($re['subsidy'], 'RM') === false)
            $subsidy = $re['subsidy'] / 100;
        else
            $subsidy = str_replace('RM ', '', $re['subsidy']);

        $formulaB = self::_formulaB($re);
        $formulaA = self::_formulaA($re);
        $formulaF = self::_formulaF($re);
        /*if ($re['channel_id'] != '1') {
            if ($formulaB < $re['low_price']) {
                $formulaD = ($re['low_price']) + (($subsidy) * $formulaF);
                // $formulaD = number_format($formulaD,2);
            } else {
                $formulaD = $formulaA;
                //   $formulaD = number_format($formulaD,2);
            }
        } else {
            if ($formulaF < $re['low_price']) {
                $formulaD = $re['low_price'];
                // $formulaD = number_format($formulaD,2);
            } else {
                $formulaD = $formulaA;
                // $formulaD = number_format($formulaD,2);
            }
        }*/
        $formulaD = $re['low_price'];

        return $formulaD;
    }

    // margins in %
    private function _formulaE($re)
    {
        $formulaC = self::_formulaC($re);
        $formulaE = ($formulaC - $re['cost']) / abs($formulaC);
        return ($formulaE * 100);
    }

    private function _formulaI($re, $salePrice)
    {
        $salePrice = str_replace(',', '', $salePrice);
        $formulaH = self::_formulaH($re, $salePrice);
        $formulaI = ($formulaH - $re['cost']) / $formulaH;
        return ($formulaI * 100);
    }

    // margins in RM
    public static function _formulaG($re)
    {
        $formulaC = self::_formulaC($re);
        $formulaG = ($formulaC - $re['cost']);
        return $formulaG;
    }


    public static function getSubsidySkus()
    {
        /*echo '<pre>';
        print_r($_GET);
        die;*/
        $cond = "";
        if (isset($_GET['sku']) && $_GET['sku'] != '') {
            $cond .= " AND pcp.sku like '%" . $_GET['sku'] . "%'";
        }
        /*if( isset($_GET['category']) && $_GET['category']!='' ){
            $cond .= " AND cc.name like '%".$_GET['category']."%'";
        }
        if( isset($_GET['sub_category']) && $_GET['sub_category']!='' ){
            $cond .= " AND c.name like '%".$_GET['sub_category']."%'";
        }*/
        if (isset($_GET['selling_status']) && $_GET['selling_status'] != '') {
            $cond .= " AND pcp.selling_status ='" . $_GET['selling_status'] . "'";
        }
        if (isset($_GET['approved_margin']) && $_GET['approved_margin'] != '') {
            $str = $_GET['approved_margin'];
            preg_match_all('!\d+!', $str, $matches);
            $number = $matches[0][0];
            $operator = preg_replace('/[0-9]+/', '', $str);
            $cond .= " AND s.ao_margins " . $operator . " '" . $number . "'";
        }
        if (isset($_GET['base_margin']) && $_GET['base_margin'] != '') {
            $cond .= " AND s.margins " . $_GET['base_margin'] . "";
        }
        if (isset($_GET['sub_category']) && $_GET['sub_category'] != '') {
            $cond .= " AND c.id = " . $_GET['sub_category'];
        }
        if (isset($_GET['category']) && $_GET['category'] != '') {
            $cond .= " and cc.id = " . $_GET['category'];
        }

        // pagination setting
        if ($_GET['page'] == 1) {
            $limit = 0 * 100;
        } else {
            $limit = $_GET['page'] * 100;
        }
        if (isset($_GET['Channels'])) {
            $counter = 0;
            $empty = 0;
            foreach ($_GET['Channels'] as $key => $value) {
                if ($value['subsidy'] == '' AND $value['ao_margins'] == '' AND $value['margins'] == '' AND $value['start_date'] == ''
                    AND $value['end_date'] == '') {
                    if ($counter == 0) {
                        //$cond .= " AND ( s.channel_id = ".$key." AND ( 1 ) ";
                        //$empty=1;
                    } else {
                        //$cond .= " OR ( s.channel_id = ".$key." AND ( 1 ) )";
                        //$empty=1;
                    }

                } else {
                    $empty = 1;
                    $cond .= " AND ( s.channel_id = " . $key . " AND (";
                    $c_array = [];
                    foreach ($value as $keyzz => $valuezz) {
                        if ($valuezz != '') {
                            if ($keyzz == 'start_date' || $keyzz == 'end_date') {
                                $c_array[] = $keyzz . " =  '" . $valuezz . " '";
                            } else {
                                $c_array[] = $keyzz . " " . $valuezz . " ";
                            }

                        }
                    }

                    $cond .= implode(' OR ', $c_array) . ") ";
                    /*if( $counter==0 ){
                        $cond .= " AND ( s.channel_id = ".$key." AND ( 1 ) )";
                    }else{
                        $cond .= " OR ( s.channel_id = ".$key." AND ( 1 ) )";
                    }*/
                }
                $counter++;
            }
            if ($empty == 1)
                $cond .= ")";
        }
        $connection = Yii::$app->db;
        $sql = "SELECT 
              s.sku_id,
              pcp.`sku`,
              s.channel_id,
              s.subsidy,
              s.margins,
              s.ao_margins,
              c.name AS 'sub_category',
              cc.name AS 'main_category',
              pcp.selling_status,
              s.start_date,
              s.end_date
            FROM
              `subsidy` s 
              INNER JOIN `products` pcp 
                ON pcp.`id` = s.`sku_id` 
              INNER JOIN category c 
                ON c.`id` = pcp.`sub_category`
                LEFT JOIN category cc
                ON cc.id = c.group_category_id 
                where pcp.sub_category != '167' " . $cond . "
                 limit " . $limit . ",100";

        //echo $sql;die;

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $total_records = self::GetTotalRecords($cond);
        $refine = [];
        $skus = [];

        /*$UserFilter = [];
        if( isset($_GET['assigned']) && $_GET['assigned']!='' ){
            $UserFilter = ['role_id'=>2,'full_name'=>$_GET['assigned']];
        }else{
            $UserFilter = ['role_id'=>2];
        }*/
        $usersSku = User::find()->select(['id', 'skus', 'full_name'])->where(['role_id' => 2])->asArray()->all();
        foreach ($result as $re) {
            $skus[$re['sku_id']] = ['sku' => $re['sku'], 'c' => $re['sub_category'], 'mc' => $re['main_category'], 'ss' => $re['selling_status']];
            $refine[$re['channel_id']][$re['sku_id']] = [
                'subsidy' => $re['subsidy'],
                'ao_margins' => $re['ao_margins'],
                'margins' => $re['margins'],
                'start_date' => $re['start_date'],
                'end_date' => $re['end_date']

            ];
        }

        foreach ($usersSku as $us) {
            $user_id = $us['id'];
            $name = $us['full_name'];
            $s = explode(',', $us['skus']);
            if ($us['skus']) {
                foreach ($s as $v) {
                    $skus[$v]['name'] = $name;
                    $skus[$v]['user'] = $user_id;

                }
            }

        }


        $rex['skus'] = $skus;
        $rex['refine'] = $refine;
        $rex['total_records'] = $total_records;
        return $rex;
    }

    public function GetTotalRecords($cond)
    {
        $connection = Yii::$app->getDb();
        $sql = "SELECT 
              s.sku_id,
              pcp.`sku`,
              s.channel_id,
              s.subsidy,
              s.margins,
              s.ao_margins,
              c.name AS 'sub_category',
              cc.name AS 'main_category',
              pcp.selling_status,
              s.start_date,
              s.end_date
            FROM
              `subsidy` s 
              INNER JOIN `products` pcp 
                ON pcp.`id` = s.`sku_id` 
              INNER JOIN category c 
                ON c.`id` = pcp.`sub_category`
                LEFT JOIN category cc
                ON cc.id = c.group_category_id 
                where pcp.sub_category != '167' " . $cond . "
                ";
        //echo $sql;die;

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return count($result);
    }

    public static function callManageStocks($sku = null, $po = null, $view = "")
    {
        $sortBy = $cond = "";
        $type = Yii::$app->request->post('type');
        $pdq = Yii::$app->request->post('pdqs');
        $inner = $poThreshold = "";

        if ($po) {
            $poThreshold = ',pds.threshold AS pds_threshold,pds.er_qty';
            $inner = "INNER JOIN `po_details` pds ON pds.`sku` = pd.isis_sku AND pds.`parent_sku_id` = '0'  AND pds.po_id = '$po'";
        }

        if ($type == 'sort') {
            $field = Yii::$app->request->post('field');
            $sort = Yii::$app->request->post('sort');
            $sortBy .= " ORDER BY " . $field . " " . $sort;
        } else {
            $sortBy .= " ORDER BY  pd.`nc12` DESC";
        }

        if ($sku) {
            $cond .= " AND p.id = '" . $sku . "' ";
        }

        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            foreach ($filters as $ff) {
                $refineFilters[$ff['filter-field']] = $ff;
            }

            foreach ($refineFilters as $rf) {
                $field = $rf['filter-field'];
                $field = ($field == 'is_active') ? "ps." . $field : $field;
                $field = ($field == 'stock_status') ? "ps." . $field : $field;
                $ft = $rf['filterType'];
                $value = $rf['val'];
                if ($ft == 'like' && $value != "") {
                    $cond .= " AND " . $field . " " . $ft . " '" . $value . "%' ";
                }
                if ($ft == 'operator' && $value != "") {
                    // > operator
                    $value = trim($value);
                    if (strpos($value, '>') !== false && strpos($value, '=') === false) {
                        $value = str_replace('>', '', $value);
                        $cond .= " AND " . $field . " > " . (int)$value . " ";
                    }

                    // < operator
                    $value = trim($value);
                    if (strpos($value, '<') !== false && strpos($value, '=') === false) {
                        $value = str_replace('<', '', $value);
                        $cond .= " AND " . $field . " < " . (int)$value . " ";
                    }

                    // != operator
                    $value = trim($value);
                    if (strpos($value, '!=') !== false) {
                        $value = str_replace('!=', '', $value);
                        $cond .= " AND " . $field . " != " . (int)$value . " ";
                    }

                    // == operator
                    $value = trim($value);
                    if (strpos($value, '=') !== false && strpos($value, '>') === false && strpos($value, '<') === false) {
                        $value = str_replace('=', '', $value);
                        $cond .= " AND " . $field . " = " . (int)$value . " ";

                    }

                    // <= operator
                    $value = trim($value);
                    if (strpos($value, '<=') !== false) {
                        $value = str_replace('<=', '', $value);
                        $cond .= " AND " . $field . " <= " . (int)$value . " ";
                    }

                    // between operator
                    $value = trim(strtolower($value));
                    if (strpos($value, 'between') !== false && strpos($value, 'and') !== false) {
                        $value = str_replace('between', '', $value);
                        $value = explode('and', $value);
                        $min = $value[0];
                        $max = $value[1];
                        $cond .= " AND " . $field . " BETWEEN '" . (int)$min . "' AND  '" . (int)$max . "'";
                    }
                }
            }


        }
        if (Yii::$app->request->post('page_no') != '' && Yii::$app->request->post('page_no') != 'All') {
            $offset_records = (Yii::$app->request->post('records_per_page') * Yii::$app->request->post('page_no')) - Yii::$app->request->post('records_per_page');
            $sql_find_total_records = "SELECT
              pd.id AS 'stock_id',
              pd.`isis_sku` AS 'sku_id',
             IF(pd.`nc12` != '',pd.`nc12`,p.`tnc`) AS 'nc12',
              pd.stocks AS 'isis_stks',
              pd.fbl_99_stock AS '909_stks',
              pd.fbl_stock AS 'blip_stks',
              pd.fbl_pavent_stock AS 'avent_stks',
              IFNULL(pd.philips_stocks, 0) AS 'philips_stks',
              ps.is_active AS 'is_active',
              ps.stock_status AS 'stock_status',
              ps.blip_stock_status AS 'blip_stock_status',
              ps.f909_stock_status AS 'f909_stock_status',
              ps.avent_status AS 'avent_stock_status',
              p.cost AS 'cost_price',
              p.id AS 'sid',
              pd.sync_for AS 'for',
              `isis_threshold`,
              `isis_threshold_critical`,
              `fbl_blip_threshold`,
              `fbl_blip_threshold_critical`,
              `fbl_909_threshold`,
              `fbl_909_threshold_critical`,
              `fbl_avent_threshold`,
              `fbl_avent_threshold_critical`,
              `stock_order_status`,
              ps.stocks_intransit,
              ps.fbl_stocks_intransit,
              ps.fbl909_stocks_intransit,
              ps.avent_stocks_intransit
               $poThreshold
            FROM
              `product_stocks` ps 
             INNER JOIN `product_details` pd ON pd.id = ps.`stock_id`
             INNER JOIN `products` p ON p.sku = pd.isis_sku
             $inner
             WHERE  p.is_active = 1 $cond " . $sortBy;
            //echo $sql_find_total_records;die;

            $findtotalrecords = ProductDetails::findBySql($sql_find_total_records)->asArray()->all();
            $total_records = count($findtotalrecords);
            //echo $total_records;die;
            $pagination_condition = ' limit ' . $offset_records . ',' . Yii::$app->request->post('records_per_page');
        } else {
            if ($view == 'manage')
                $total_records = 10;

            $pagination_condition = '';
        }
        /*create po cateogry filter*/
        if (isset($_GET['category']) && $_GET['category'] == 'mcc') {
            $joinCat = " INNER JOIN category c on p.sub_category = c.id";
         //   $joinWhere = " AND c.map_with = 'mcc'";
        } elseif (isset($_GET['category']) && $_GET['category'] == 'dap') {
            $joinCat = " INNER JOIN category c on p.sub_category = c.id";
          //  $joinWhere = " AND c.map_with != 'mcc'";
        } else {
            $joinCat = "";
            $joinWhere = "";
        }
        /*create po cateogry filter ends*/

        $sql = "SELECT
              pd.id AS 'stock_id',
              pd.`isis_sku` AS 'sku_id',
             IF(pd.`nc12` != '',pd.`nc12`,p.`tnc`) AS 'nc12',
              pd.stocks AS 'isis_stks',
              pd.fbl_99_stock AS '909_stks',
              pd.fbl_stock AS 'blip_stks',
              pd.fbl_pavent_stock AS 'avent_stks',
              IFNULL(pd.philips_stocks, 0) AS 'philips_stks',
              ps.is_active AS 'is_active',
              ps.stock_status AS 'stock_status',
              ps.blip_stock_status AS 'blip_stock_status',
              ps.f909_stock_status AS 'f909_stock_status',
              ps.avent_status AS 'avent_stock_status',
              p.cost AS 'cost_price',
              p.id AS 'sid',
              pd.sync_for AS 'for',
              `isis_threshold`,
              `isis_threshold_critical`,
              `fbl_blip_threshold`,
              `fbl_blip_threshold_critical`,
              `fbl_909_threshold`,
              `fbl_909_threshold_critical`,
              `fbl_avent_threshold`,
              `fbl_avent_threshold_critical`,
              `stock_order_status`,
              ps.stocks_intransit,
              ps.fbl_stocks_intransit,
              ps.fbl909_stocks_intransit,
              ps.avent_stocks_intransit
              $poThreshold
            FROM
              `product_stocks` ps 
             INNER JOIN `product_details` pd ON pd.id = ps.`stock_id`
             INNER JOIN `products` p ON p.sku = pd.isis_sku
             $inner
             $joinCat
             WHERE p.is_active = 1 $cond $joinWhere GROUP BY pd.`isis_sku`  " . $sortBy . $pagination_condition;
        $pds = ProductDetails::findBySql($sql)->asArray()->all();
        //echo $sql;die;
        $refine = [];
        //echo '<pre>';print_r($pds);die;
        foreach ($pds as $p) {
            //deal check
            $dealISISQty = [];
            $dealFBLZDQty = [];
            $dealFBL909Qty = [];
            $dealNoISIS = '';
            $dealNoFBLZD = '';
            $dealNoFBL909 = '';
            $dealView = '';
            $deals = self::getDealSkuInfo($p['sid']);

            if (!empty($deals) && $view == "") {
                foreach ($deals as $d) {
                    if($d['prefix'] != 'BLP-LZD' && $d['prefix'] != '909-LZD' && $d['prefix'] != 'AV-LZD' )
                    {
                        $dealISISQty[] = $d['deal_target'];
                        $dealNoISIS .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == 'BLP-LZD' )
                    {
                        $dealFBLZDQty[] = $d['deal_target'];
                        $dealNoFBLZD .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == '909-LZD' || $d['prefix'] == 'AV-LZD' )
                    {
                        $dealFBL909Qty[] = $d['deal_target'];
                        $dealNoFBL909 .= " " . $d['deal_target'] . ' +';
                    }

                    $dealView = "<a target='_blank' style='color: #ffffff;' href='/deals-maker/view?id={$d['id']}'>D</a>";

                }

                $dealNoISIS = substr($dealNoISIS, 0, strlen($dealNoISIS) - 1);
                $dealNoFBLZD = substr($dealNoFBLZD, 0, strlen($dealNoFBLZD) - 1);
                $dealNoFBL909 = substr($dealNoFBL909, 0, strlen($dealNoFBL909) - 1);


            }

            $p['isis_threshold_critical_org'] = $p['isis_threshold_critical'];
            $p['isis_threshold_org'] = $p['isis_threshold'];
            $p['isis_threshold_critical'] = $p['isis_threshold_critical'] + array_sum($dealISISQty);
            $p['isis_threshold'] = $p['isis_threshold'] + array_sum($dealISISQty);
            // isis order stocks
            $p['isis_stks'] = ($p['stocks_intransit'] != '0') ? $p['stocks_intransit'] + $p['isis_stks'] : $p['isis_stks'];
            if ($p['isis_stks'] <= $p['isis_threshold_critical'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] = abs($p['isis_threshold_org'] - ($p['isis_stks'] - $p['isis_threshold_critical_org']));
            else if ($p['isis_stks'] <= $p['isis_threshold'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] = (ceil($p['isis_threshold_org'] / 7) * 3);
            else
                $p['isis_order_stocks'] = 0;

            $p['isis_order_stocks'] = $p['isis_order_stocks'] + array_sum($dealISISQty);

            $p['fbl_909_threshold_critical_org'] = $p['fbl_909_threshold_critical'];
            $p['fbl_909_threshold_org'] = $p['fbl_909_threshold'];
            $p['fbl_909_threshold_critical'] = $p['fbl_909_threshold_critical'] + array_sum($dealFBL909Qty);
            $p['fbl_909_threshold'] = $p['fbl_909_threshold'] + array_sum($dealFBL909Qty);
            // 909 lazada stocks
            $p['909_stks'] = ($p['fbl909_stocks_intransit'] != '0') ? $p['fbl909_stocks_intransit'] + $p['909_stks'] : $p['909_stks'];
            if ($p['909_stks'] <= $p['fbl_909_threshold_critical'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = abs($p['fbl_909_threshold_org'] - ($p['909_stks'] - $p['fbl_909_threshold_critical_org']));
            else if ($p['909_stks'] <= $p['fbl_909_threshold'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = (ceil($p['fbl_909_threshold_org'] / 7) * 3);
            else
                $p['fbl909_order_stocks'] = 0;

            $p['fbl909_order_stocks'] = $p['fbl909_order_stocks'] + array_sum($dealFBL909Qty);

            // avent lazada stocks
            $p['fbl_avent_threshold_critical_org'] = $p['fbl_avent_threshold_critical'];
            $p['fbl_avent_threshold_org'] = $p['fbl_avent_threshold'];
            $p['fbl_avent_threshold_critical'] = $p['fbl_avent_threshold_critical'] + array_sum($dealFBL909Qty);
            $p['fbl_avent_threshold'] = $p['fbl_avent_threshold'] + array_sum($dealFBL909Qty);
            $p['avent_stks'] = ($p['avent_stocks_intransit'] != '0') ? $p['avent_stocks_intransit'] + $p['avent_stks'] : $p['avent_stks'];
            if ($p['avent_stks'] <= $p['fbl_avent_threshold_critical'] && $p['avent_stks'] != '')
                $p['avent_order_stocks'] = abs($p['fbl_avent_threshold_org'] - ($p['avent_stks'] - $p['fbl_avent_threshold_critical_org']));
            else if ($p['avent_stks'] <= $p['fbl_avent_threshold'] && $p['avent_stks'] != '')
                $p['avent_order_stocks'] = (ceil($p['fbl_avent_threshold_org'] / 7) * 3);
            else
                $p['avent_order_stocks'] = 0;

            $p['avent_order_stocks'] = $p['avent_order_stocks'] + array_sum($dealFBL909Qty);


            $p['fbl_blip_threshold_critical_org'] = $p['fbl_blip_threshold_critical'];
            $p['fbl_blip_threshold_org'] = $p['fbl_blip_threshold'];
            $p['fbl_blip_threshold_critical'] = $p['fbl_blip_threshold_critical'] + array_sum($dealFBLZDQty);
            $p['fbl_blip_threshold'] = $p['fbl_blip_threshold'] + array_sum($dealFBLZDQty);
            // blip lazada stocks
            $p['blip_stks'] = ($p['fbl_stocks_intransit'] != '0') ? $p['fbl_stocks_intransit'] + $p['blip_stks'] : $p['blip_stks'];
            if ($p['blip_stks'] <= $p['fbl_blip_threshold_critical'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = abs($p['fbl_blip_threshold_org'] - ($p['blip_stks'] - $p['fbl_blip_threshold_critical_org']));
            else if ($p['blip_stks'] <= $p['fbl_blip_threshold'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = (ceil($p['fbl_blip_threshold_org'] / 7) * 3);
            else
                $p['fbl_order_stocks'] = 0;

            $p['fbl_order_stocks'] = $p['fbl_order_stocks'] + array_sum($dealFBLZDQty);

            $p['dealNoISIS'] = $dealNoISIS;
            $p['dealNoFBLZD'] = $dealNoFBLZD;
            $p['dealNoFBL909'] = $dealNoFBL909;
            $p['dealView'] = $dealView;
            $refine[] = $p;

        }
        if (isset($total_records)) {
            $refine['total_records'] = $total_records;
        }
        /*echo '';
        print_r($refine);
        die;*/
        return $refine;
    }
    public static function callChildManageSocks($product_detail_id){
        $view="";
        $connection = Yii::$app->getDb();
        $sql = "SELECT ps.avent_stocks_intransit,pd.fbl_pavent_stock AS 'avent_stks',ps.fbl_avent_threshold,
                ps.fbl_avent_threshold_critical,pd.id AS 'stock_id', pd.`isis_sku` AS 'sku_id', p.`parent_sku_id`, IF(pd.`nc12` != '',pd.`nc12`,p.`tnc`) 
                AS 'nc12', pd.stocks AS 'isis_stks', pd.fbl_99_stock AS '909_stks', pd.fbl_stock AS 'blip_stks', IFNULL(pd.philips_stocks, 0) AS 'philips_stks', ps.is_active AS 'is_active', ps.stock_status AS 'stock_status', ps.blip_stock_status AS 'blip_stock_status', ps.f909_stock_status AS 'f909_stock_status', p.cost AS 'cost_price', p.id AS 'sid', pd.sync_for AS 'for', `isis_threshold`, `isis_threshold_critical`, `fbl_blip_threshold`, `fbl_blip_threshold_critical`, `fbl_909_threshold`, `fbl_909_threshold_critical`, `stock_order_status`, ps.stocks_intransit, ps.fbl_stocks_intransit, ps.fbl909_stocks_intransit,
                ps.avent_status AS 'avent_stock_status'
                FROM `product_stocks` ps
                RIGHT JOIN `product_details` pd ON pd.id = ps.`stock_id`
                RIGHT JOIN `products` p ON p.sku = pd.isis_sku
                INNER JOIN category c ON p.sub_category = c.id
                WHERE p.is_active = 1
                AND c.map_with = 'mcc' 
                AND pd.id=".$product_detail_id."
                GROUP BY pd.`isis_sku`
                ORDER BY pd.`nc12` DESC";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        //echo '<pre>';print_r($pds);die;
        foreach ($result as $p) {
            //deal check
            $dealISISQty = [];
            $dealFBLZDQty = [];
            $dealFBL909Qty = [];
            $dealFBL909QtyAvent = [];

            $dealNoISIS = '';
            $dealNoFBLZD = '';
            $dealNoFBL909 = '';
            $dealNoFBL909Avent = '';
            $dealView = '';
            $deals = self::getDealSkuInfo($p['sid']);

            if (!empty($deals) && $view == "") {
                foreach ($deals as $d) {
                    if($d['prefix'] != 'BLP-LZD' && $d['prefix'] != '909-LZD' && $d['prefix'] != 'AV-LZD' )
                    {
                        $dealISISQty[] = $d['deal_target'];
                        $dealNoISIS .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == 'BLP-LZD' )
                    {
                        $dealFBLZDQty[] = $d['deal_target'];
                        $dealNoFBLZD .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == '909-LZD' )
                    {
                        $dealFBL909Qty[] = $d['deal_target'];
                        $dealNoFBL909 .= " " . $d['deal_target'] . ' +';
                    }
                    if ( $d['prefix'] == 'AV-LZD' ){
                        $dealFBL909QtyAvent[] = $d['deal_target'];
                        $dealNoFBL909Avent .= " " . $d['deal_target'] . ' +';
                    }
                    $dealView = "<a target='_blank' style='color: #ffffff;' href='/deals-maker/view?id={$d['id']}'>D</a>";

                }

                $dealNoISIS = substr($dealNoISIS, 0, strlen($dealNoISIS) - 1);
                $dealNoFBLZD = substr($dealNoFBLZD, 0, strlen($dealNoFBLZD) - 1);
                $dealNoFBL909 = substr($dealNoFBL909, 0, strlen($dealNoFBL909) - 1);


            }

            $p['isis_threshold_critical_org'] = $p['isis_threshold_critical'];
            $p['isis_threshold_org'] = $p['isis_threshold'];
            $p['isis_threshold_critical'] = $p['isis_threshold_critical'] + array_sum($dealISISQty);
            $p['isis_threshold'] = $p['isis_threshold'] + array_sum($dealISISQty);
            // isis order stocks
            $p['isis_stks'] = ($p['stocks_intransit'] != '0') ? $p['stocks_intransit'] + $p['isis_stks'] : $p['isis_stks'];
            if ($p['isis_stks'] <= $p['isis_threshold_critical'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] = abs($p['isis_threshold_org'] - ($p['isis_stks'] - $p['isis_threshold_critical_org']));
            else if ($p['isis_stks'] <= $p['isis_threshold'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] = (ceil($p['isis_threshold_org'] / 7) * 3);
            else
                $p['isis_order_stocks'] = 0;

            $p['isis_order_stocks'] = $p['isis_order_stocks'] + array_sum($dealISISQty);

            $p['fbl_909_threshold_critical_org'] = $p['fbl_909_threshold_critical'];
            $p['fbl_909_threshold_org'] = $p['fbl_909_threshold'];
            $p['fbl_909_threshold_critical'] = $p['fbl_909_threshold_critical'] + array_sum($dealFBL909Qty);
            $p['fbl_909_threshold'] = $p['fbl_909_threshold'] + array_sum($dealFBL909Qty);
            // 909 lazada stocks
            $p['909_stks'] = ($p['fbl909_stocks_intransit'] != '0') ? $p['fbl909_stocks_intransit'] + $p['909_stks'] : $p['909_stks'];
            if ($p['909_stks'] <= $p['fbl_909_threshold_critical'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = abs($p['fbl_909_threshold_org'] - ($p['909_stks'] - $p['fbl_909_threshold_critical_org']));
            else if ($p['909_stks'] <= $p['fbl_909_threshold'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = (ceil($p['fbl_909_threshold_org'] / 7) * 3);
            else
                $p['fbl909_order_stocks'] = 0;

            $p['fbl909_order_stocks'] = $p['fbl909_order_stocks'] + array_sum($dealFBL909Qty);


            $p['fbl_blip_threshold_critical_org'] = $p['fbl_blip_threshold_critical'];
            $p['fbl_blip_threshold_org'] = $p['fbl_blip_threshold'];
            $p['fbl_blip_threshold_critical'] = $p['fbl_blip_threshold_critical'] + array_sum($dealFBLZDQty);
            $p['fbl_blip_threshold'] = $p['fbl_blip_threshold'] + array_sum($dealFBLZDQty);
            // blip lazada stocks
            $p['blip_stks'] = ($p['fbl_stocks_intransit'] != '0') ? $p['fbl_stocks_intransit'] + $p['blip_stks'] : $p['blip_stks'];
            if ($p['blip_stks'] <= $p['fbl_blip_threshold_critical'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = abs($p['fbl_blip_threshold_org'] - ($p['blip_stks'] - $p['fbl_blip_threshold_critical_org']));
            else if ($p['blip_stks'] <= $p['fbl_blip_threshold'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = (ceil($p['fbl_blip_threshold_org'] / 7) * 3);
            else
                $p['fbl_order_stocks'] = 0;

            $p['fbl_order_stocks'] = $p['fbl_order_stocks'] + array_sum($dealFBLZDQty);


            // avent lazada stocks
            $p['fbl_avent_threshold_critical_org'] = $p['fbl_avent_threshold_critical'];
            $p['fbl_avent_threshold_org'] = $p['fbl_avent_threshold'];
            $p['fbl_avent_threshold_critical'] = $p['fbl_avent_threshold_critical'] + array_sum($dealFBL909Qty);
            $p['fbl_avent_threshold'] = $p['fbl_avent_threshold'] + array_sum($dealFBL909Qty);
            $p['avent_stks'] = ($p['avent_stocks_intransit'] != '0') ? $p['avent_stocks_intransit'] + $p['avent_stks'] : $p['avent_stks'];
            if ($p['avent_stks'] <= $p['fbl_avent_threshold_critical']) {
                $p['avent_order_stocks'] = abs($p['fbl_avent_threshold_org'] - ($p['avent_stks'] - $p['fbl_avent_threshold_critical_org']));
            }
            else if ($p['avent_stks'] <= $p['fbl_avent_threshold']) {
                $p['avent_order_stocks'] = (ceil($p['fbl_avent_threshold_org'] / 7) * 3);
            }
            else {
                $p['avent_order_stocks'] = 0;
            }

            $p['avent_order_stocks'] = $p['avent_order_stocks'] + array_sum($dealFBL909Qty);

            $p['dealNoISIS'] = $dealNoISIS;
            $p['dealNoFBLZD'] = $dealNoFBLZD;
            $p['dealNoFBL909'] = $dealNoFBL909;
            $p['dealNoFBL909Avent'] = $dealNoFBL909Avent;
            $p['dealView'] = $dealView;
            //echo '<pre>';print_r($p);die;
            $refine = $p;

        }
        return $refine;
    }
    public static function callManageStocksPO($sku = null, $po = null, $view = "")
    {
        $sortBy = $cond = "";
        $type = Yii::$app->request->post('type');
        $pdq = Yii::$app->request->post('pdqs');
        $inner = $poThreshold = "";

        if ($po) {
            $poThreshold = ',pds.final_order_qty,pds.threshold AS pds_threshold,pds.er_qty';
            $inner = "INNER JOIN `po_details` pds ON pds.`sku` = pd.isis_sku AND pds.`parent_sku_id` = '0'  AND pds.po_id = '$po'";
        }

        if ($type == 'sort') {
            $field = Yii::$app->request->post('field');
            $sort = Yii::$app->request->post('sort');
            $sortBy .= " ORDER BY " . $field . " " . $sort;
        } else {
            $sortBy .= " ORDER BY  pd.`nc12` DESC";
        }

        if ($sku) {
            $cond .= " AND p.id = '" . $sku . "' ";
        }
        //IF(pd.`nc12` != '',pd.`nc12`,p.`tnc`) AS 'nc12',
        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            foreach ($filters as $ff) {
                $refineFilters[$ff['filter-field']] = $ff;
            }

            foreach ($refineFilters as $rf) {
                $field = $rf['filter-field'];
                $field = ($field == 'is_active') ? "ps." . $field : $field;
                $field = ($field == 'stock_status') ? "ps." . $field : $field;
                $ft = $rf['filterType'];
                $value = $rf['val'];
                if ($ft == 'like' && $value != "") {
                    $cond .= " AND " . $field . " " . $ft . " '" . $value . "%' ";
                }
                if ($ft == 'operator' && $value != "") {
                    // > operator
                    $value = trim($value);
                    if (strpos($value, '>') !== false && strpos($value, '=') === false) {
                        $value = str_replace('>', '', $value);
                        $cond .= " AND " . $field . " > " . (int)$value . " ";
                    }

                    // < operator
                    $value = trim($value);
                    if (strpos($value, '<') !== false && strpos($value, '=') === false) {
                        $value = str_replace('<', '', $value);
                        $cond .= " AND " . $field . " < " . (int)$value . " ";
                    }

                    // != operator
                    $value = trim($value);
                    if (strpos($value, '!=') !== false) {
                        $value = str_replace('!=', '', $value);
                        $cond .= " AND " . $field . " != " . (int)$value . " ";
                    }

                    // == operator
                    $value = trim($value);
                    if (strpos($value, '=') !== false && strpos($value, '>') === false && strpos($value, '<') === false) {
                        $value = str_replace('=', '', $value);
                        $cond .= " AND " . $field . " = " . (int)$value . " ";

                    }

                    // <= operator
                    $value = trim($value);
                    if (strpos($value, '<=') !== false) {
                        $value = str_replace('<=', '', $value);
                        $cond .= " AND " . $field . " <= " . (int)$value . " ";
                    }

                    // between operator
                    $value = trim(strtolower($value));
                    if (strpos($value, 'between') !== false && strpos($value, 'and') !== false) {
                        $value = str_replace('between', '', $value);
                        $value = explode('and', $value);
                        $min = $value[0];
                        $max = $value[1];
                        $cond .= " AND " . $field . " BETWEEN '" . (int)$min . "' AND  '" . (int)$max . "'";
                    }
                }
            }


        }
        if (Yii::$app->request->post('page_no') != '' && Yii::$app->request->post('page_no') != 'All') {
            $offset_records = (Yii::$app->request->post('records_per_page') * Yii::$app->request->post('page_no')) - Yii::$app->request->post('records_per_page');
            $sql_find_total_records = "SELECT
              pd.id AS 'stock_id',
              pd.`isis_sku` AS 'sku_id',
              p.`tnc` AS 'nc12',
              pd.stocks AS 'isis_stks',
              pd.fbl_99_stock AS '909_stks',
              pd.fbl_stock AS 'blip_stks',
              pd.fbl_pavent_stock AS 'avent_stks',
              IFNULL(pd.philips_stocks, 0) AS 'philips_stks',
              ps.is_active AS 'is_active',
              ps.stock_status AS 'stock_status',
              ps.blip_stock_status AS 'blip_stock_status',
              ps.f909_stock_status AS 'f909_stock_status',
              ps.avent_status AS 'avent_stock_status',
              p.cost AS 'cost_price',
              p.id AS 'sid',
              pd.sync_for AS 'for',
              `isis_threshold`,
              `isis_threshold_critical`,
              `fbl_blip_threshold`,
              `fbl_blip_threshold_critical`,
              `fbl_909_threshold`,
              `fbl_909_threshold_critical`,
              `fbl_avent_threshold`,
              `fbl_avent_threshold_critical`,
              `stock_order_status`,
              ps.stocks_intransit,
              ps.fbl_stocks_intransit,
              ps.fbl909_stocks_intransit,
              ps.avent_stocks_intransit,
              IFNULL(ps.avent_stocks_intransit,0) AS avent_stocks_intransit
               $poThreshold
            FROM
              `product_stocks` ps 
             RIGHT JOIN `product_details` pd ON pd.id = ps.`stock_id`
             RIGHT JOIN `products` p ON p.sku = pd.isis_sku
             $inner
             WHERE  p.is_active = 1 $cond " . $sortBy;
            //echo $sql_find_total_records;die;

            $findtotalrecords = ProductDetails::findBySql($sql_find_total_records)->asArray()->all();
            $total_records = count($findtotalrecords);
            //echo $total_records;die;
            $pagination_condition = ' limit ' . $offset_records . ',' . Yii::$app->request->post('records_per_page');
        } else {
            if ($view == 'manage')
                $total_records = 10;

            $pagination_condition = '';
        }
        /*create po cateogry filter*/
        if (isset($_GET['category']) && $_GET['category'] == 'mcc') {
            $joinCat = " INNER JOIN category c on p.sub_category = c.id";
            $joinWhere = " AND c.map_with = 'mcc' AND c.is_active = 1";
        } elseif (isset($_GET['category']) && $_GET['category'] == 'dap') {
            $joinCat = " INNER JOIN category c on p.sub_category = c.id";
            $joinWhere = " AND c.map_with != 'mcc' AND c.is_active = 1";
        } else {
            $joinCat = "";
            $joinWhere = "";
        }
        /*create po cateogry filter ends*/
        //IF(pd.`nc12` != '',pd.`nc12`,p.`tnc`) AS 'nc12',
        $sql = "SELECT
              pd.id AS 'stock_id',
              pd.`isis_sku` AS 'sku_id',
              p.`parent_sku_id`,
              p.`master_cotton` as master_cotton,
              p.`tnc` AS 'nc12',
              pd.stocks AS 'isis_stks',
              pd.fbl_99_stock AS '909_stks',
              pd.fbl_stock AS 'blip_stks',
              pd.fbl_pavent_stock AS 'avent_stks',
              IFNULL(pd.philips_stocks, 0) AS 'philips_stks',
              ps.is_active AS 'is_active',
              ps.stock_status AS 'stock_status',
              ps.blip_stock_status AS 'blip_stock_status',
              ps.f909_stock_status AS 'f909_stock_status',
              ps.avent_status AS 'avent_stock_status',
              p.cost AS 'cost_price',
              p.id AS 'sid',
              pd.sync_for AS 'for',
              `isis_threshold`,
              `isis_threshold_critical`,
              `fbl_blip_threshold`,
              `fbl_blip_threshold_critical`,
              `fbl_909_threshold`,
              `fbl_909_threshold_critical`,
              `fbl_avent_threshold`,
              `fbl_avent_threshold_critical`,
              `stock_order_status`,
              ps.stocks_intransit,
              ps.fbl_stocks_intransit,
              ps.fbl909_stocks_intransit,
              IFNULL(ps.avent_stocks_intransit,0) AS avent_stocks_intransit
              $poThreshold
            FROM
              `product_stocks` ps 
             RIGHT JOIN `product_details` pd ON pd.id = ps.`stock_id`
             RIGHT JOIN `products` p ON p.sku = pd.isis_sku
             $inner
             $joinCat
             WHERE p.is_active = 1  AND p.parent_sku_id = 0 $cond $joinWhere GROUP BY pd.`isis_sku`  " . $sortBy . $pagination_condition;
        $pds = ProductDetails::findBySql($sql)->asArray()->all();
        $refine = [];
        //echo '<pre>';print_r($pds);die;
        foreach ($pds as $p) {
            //deal check
            $dealISISQty = [];
            $dealFBLZDQty = [];
            $dealFBL909Qty = [];
            $dealFBL909AventQty = [];
            $dealNoISIS = '';
            $dealNoFBLZD = '';
            $dealNoFBL909 = '';
            $dealNoFBL909Avent = '';
            $dealView = '';
            $deals = self::getDealSkuInfo($p['sid']);

            if (!empty($deals) && $view == "") {
                foreach ($deals as $d) {
                    if($d['prefix'] != 'BLP-LZD' && $d['prefix'] != '909-LZD' && $d['prefix'] != 'AV-LZD' )
                    {
                        $dealISISQty[] = $d['deal_target'];
                        $dealNoISIS .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == 'BLP-LZD' )
                    {
                        $dealFBLZDQty[] = $d['deal_target'];
                        $dealNoFBLZD .= " " . $d['deal_target'] . ' +';
                    }
                    if($d['prefix'] == '909-LZD' )
                    {
                        $dealFBL909Qty[] = $d['deal_target'];
                        $dealNoFBL909 .= " " . $d['deal_target'] . ' +';
                    }
                    if ( $d['prefix'] == 'AV-LZD' )
                    {
                        $dealFBL909AventQty[] = $d['deal_target'];
                        $dealNoFBL909Avent .= " " . $d['deal_target'] . ' +';
                    }

                    $dealView = "<a target='_blank' href='/deals-maker/view?id={$d['id']}'>D</a>";

                }

                $dealNoISIS = substr($dealNoISIS, 0, strlen($dealNoISIS) - 1);
                $dealNoFBLZD = substr($dealNoFBLZD, 0, strlen($dealNoFBLZD) - 1);
                $dealNoFBL909 = substr($dealNoFBL909, 0, strlen($dealNoFBL909) - 1);
                $dealNoFBL909Avent = substr($dealNoFBL909Avent, 0, strlen($dealNoFBL909Avent) - 1);

            }

            $p['isis_threshold_critical_org'] = $p['isis_threshold_critical'];
            $p['isis_threshold_org'] = $p['isis_threshold'];
            $p['isis_threshold_critical'] = $p['isis_threshold_critical'] + array_sum($dealISISQty);
            $p['isis_threshold'] = $p['isis_threshold'] + array_sum($dealISISQty);
            // isis order stocks
            $p['isis_stks'] = ($p['stocks_intransit'] != '0') ? $p['stocks_intransit'] + $p['isis_stks'] : $p['isis_stks'];
            if ($p['isis_stks'] <= $p['isis_threshold_critical'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] = ($p['isis_threshold_org'] - $p['isis_stks']) + $p['isis_threshold_critical_org'];
            else if ($p['isis_stks'] <= $p['isis_threshold'] && $p['isis_stks'] != '')
                $p['isis_order_stocks'] =  ($p['isis_threshold_org'] - $p['isis_stks']) + $p['isis_threshold_critical_org'];
            else
                $p['isis_order_stocks'] = 0;

            $p['isis_order_stocks'] = $p['isis_order_stocks'] + array_sum($dealISISQty);

            $p['fbl_909_threshold_critical_org'] = $p['fbl_909_threshold_critical'];
            $p['fbl_909_threshold_org'] = $p['fbl_909_threshold'];
            $p['fbl_909_threshold_critical'] = $p['fbl_909_threshold_critical'] + array_sum($dealFBL909Qty);
            $p['fbl_909_threshold'] = $p['fbl_909_threshold'] + array_sum($dealFBL909Qty);
            // 909 lazada stocks
            $p['909_stks'] = ($p['fbl909_stocks_intransit'] != '0') ? $p['fbl909_stocks_intransit'] + $p['909_stks'] : $p['909_stks'];
            if ($p['909_stks'] <= $p['fbl_909_threshold_critical'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = ($p['fbl_909_threshold_org'] - $p['909_stks']) + $p['fbl_909_threshold_critical_org'];
            else if ($p['909_stks'] <= $p['fbl_909_threshold'] && $p['909_stks'] != '')
                $p['fbl909_order_stocks'] = ($p['fbl_909_threshold_org'] - $p['909_stks']) + $p['fbl_909_threshold_critical_org'];
            else
                $p['fbl909_order_stocks'] = 0;

            $p['fbl909_order_stocks'] = $p['fbl909_order_stocks'] + array_sum($dealFBL909Qty);


            $p['fbl_blip_threshold_critical_org'] = $p['fbl_blip_threshold_critical'];
            $p['fbl_blip_threshold_org'] = $p['fbl_blip_threshold'];
            $p['fbl_blip_threshold_critical'] = $p['fbl_blip_threshold_critical'] + array_sum($dealFBLZDQty);
            $p['fbl_blip_threshold'] = $p['fbl_blip_threshold'] + array_sum($dealFBLZDQty);
            // blip lazada stocks
            $p['blip_stks'] = ($p['fbl_stocks_intransit'] != '0') ? $p['fbl_stocks_intransit'] + $p['blip_stks'] : $p['blip_stks'];
            if ($p['blip_stks'] <= $p['fbl_blip_threshold_critical'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = ($p['fbl_blip_threshold_org'] - $p['blip_stks']) + $p['fbl_blip_threshold_critical_org'];
            else if ($p['blip_stks'] <= $p['fbl_blip_threshold'] && $p['blip_stks'] != '')
                $p['fbl_order_stocks'] = ($p['fbl_blip_threshold_org'] - $p['blip_stks']) + $p['fbl_blip_threshold_critical_org'];
            else
                $p['fbl_order_stocks'] = 0;

            $p['fbl_order_stocks'] = $p['fbl_order_stocks'] + array_sum($dealFBLZDQty);

            // avent lazada stocks
            $p['fbl_avent_threshold_critical_org'] = $p['fbl_avent_threshold_critical'];
            $p['fbl_avent_threshold_org'] = $p['fbl_avent_threshold'];
            $p['fbl_avent_threshold_critical'] = $p['fbl_avent_threshold_critical'] + array_sum($dealFBL909AventQty);
            $p['fbl_avent_threshold'] = $p['fbl_avent_threshold'] + array_sum($dealFBL909AventQty);
            $p['avent_stks'] = ($p['avent_stocks_intransit'] != '0') ? $p['avent_stocks_intransit'] + $p['avent_stks'] : $p['avent_stks'];
            if ($p['avent_stks'] <= $p['fbl_avent_threshold_critical']) {
                $p['avent_order_stocks'] = ($p['fbl_avent_threshold_org'] - $p['avent_stks']) + $p['fbl_avent_threshold_critical_org'];
            }
            else if ($p['avent_stks'] <= $p['fbl_avent_threshold']) {
                $p['avent_order_stocks'] = ($p['fbl_avent_threshold_org'] - $p['avent_stks']) + $p['fbl_avent_threshold_critical_org'];
            }
            else {
                $p['avent_order_stocks'] = 0;
            }

            $p['avent_order_stocks'] = $p['avent_order_stocks'] + array_sum($dealFBL909AventQty);


            $p['dealNoISIS'] = $dealNoISIS;
            $p['dealNoFBLZD'] = $dealNoFBLZD;
            $p['dealNoFBL909'] = $dealNoFBL909;
            $p['dealNoFBL909Avent'] = $dealNoFBL909Avent;
            $p['dealView'] = $dealView;
            $refine[] = $p;

        }

        if (isset($total_records)) {
            $refine['total_records'] = $total_records;
        }

        return $refine;
    }

    public static function getGenericData($nolimit = 0, $config)
    {
        $Route = \Yii::$app->controller->module->requestedRoute;

        $sortBy = $cond = "";
        $type = Yii::$app->request->post('type');
        $pdq = Yii::$app->request->post('pdqs');


        if ($type == 'sort') {
            $field = Yii::$app->request->post('field');
            $sort = Yii::$app->request->post('sort');
            if ($field == 'grand_total')
                $sortBy .= " ORDER BY " . '' . " " . $field . " " . $sort;
            else if ($field == 'overall_performance')
                $sortBy .= " ORDER BY overall_performance " . $sort;
            else
                $sortBy .= " ORDER BY " . '' . "." . $field . " " . $sort;
        } else {
            $sortBy .= $config['OrderBy_Default'];
        }
        $valuex = $fieldx = '';
        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            //echo '<pre>';print_r($filters);die;
            foreach ($filters as $ff) {
                if ($ff['filter-field'] == 'created_at_finance_validation' || $ff['filter-field'] == 'shipping_type') {
                    continue;
                }

                if ($ff['filter-field'] == 'grand_total') {
                    if ($ff['val'] != '') {
                        $Grand_Total_having = 1;
                        $having_clauses = 'having grand_total ' . $ff['val'] . ' ';
                        continue;
                    } else
                        $having_clauses = '';

                }
                if ($ff['filter-field'] == 'overall_performance') {
                    if ($ff['val'] != '') {
                        $ff['val'] = preg_replace('/[^\w]/', '', $ff['val']);
                        $Grand_Total_having = 1;
                        if( $ff['val'] == 'Top' )
                        {
                            $having_clauses = 'having overall_performance >= 100 ';
                            continue;
                        }else if ( $ff['val'] == 'Medium' ){
                            $having_clauses = 'having overall_performance >= 70 AND overall_performance < 100 ';
                            continue;
                        }else if ( $ff['val'] == 'Low' ){
                            $having_clauses = 'having overall_performance < 70 ';
                            continue;
                        }

                    } else
                        $having_clauses = '';

                }
                if ($ff['filter-field'] == 'bundle_name') {
                    if ($ff['val'] != '') {
                        $Grand_Total_having = 1;
                        $bundle_name=$ff['val'];
                        $having_clauses = 'having bundle_name LIKE \'%' . $ff['val'] . '%\' ';
                        continue;
                    } else{
                        //$having_clauses = '';
                        $bundle_name='';
                    }
                }
                if ($ff['filter-field'] == 'bundle_name_child') {
                    if ($ff['val'] != '' AND $bundle_name=='') {
                        $Grand_Total_having = 1;
                        $having_clauses = 'having bundle_name_child LIKE \'%' . $ff['val'] . '%\' ';
                        continue;
                    }elseif ($ff['val'] != '' AND $bundle_name!='') {
                        $Grand_Total_having = 1;
                        $having_clauses = $having_clauses.' OR bundle_name_child LIKE \'%' . $ff['val'] . '%\' ';
                        continue;
                    } else{

                    }
                    //$having_clauses = '';
                }

                $refineFilters[$ff['filter-field']] = $ff;
            }

            foreach ($refineFilters as $rf) {


                $field = $rf['filter-field'];
                $ft = $rf['filterType'];
                $value = $rf['val'];
                if ($ft == 'like' && $value != "") {
                    $cond .= " AND " . $field . " " . $ft . " '" . $value . "%' ";
                }
                if ($ft == 'operator' && $value != "") {
                    if ($Route == 'sales/generic-info-filter' && $field == 'io.item_created_at')
                        continue;

                    // > operator
                    $value = trim($value);
                    if (strpos($value, '>') !== false && strpos($value, '=') === false) {
                        $value = str_replace('>', '', $value);
                        $cond .= " AND " . $field . " > '" . (int)$value . "' ";
                    }

                    // < operator
                    $value = trim($value);
                    if (strpos($value, '<') !== false && strpos($value, '=') === false) {
                        $value = str_replace('<', '', $value);
                        $cond .= " AND " . $field . " < '" . (int)$value . "' ";
                    }

                    // != operator
                    $value = trim($value);
                    if (strpos($value, '!=') !== false) {
                        $value = str_replace('!=', '', $value);
                        $cond .= " AND " . $field . " != '" . (int)$value . "' ";
                    }

                    // == operator
                    $value = trim($value);
                    if (strpos($value, '=') !== false && strpos($value, '>') === false && strpos($value, '<') === false) {
                        $value = str_replace('=', '', $value);
                        $cond .= " AND " . $field . " = '" . (int)$value . "' ";

                    }

                    // <= operator
                    $value = trim($value);
                    if (strpos($value, '<=') !== false) {
                        $value = str_replace('<=', '', $value);
                        $cond .= " AND " . $field . " <= '" . (int)$value . "' ";
                        if ($field == 'goodQty' && $value == '0') {
                            $valuex = $value;
                            $fieldx = $field;
                        }

                    }

                    // between operator
                    $value = trim(strtolower($value));
                    if (strpos($value, 'between') !== false && strpos($value, 'and') !== false) {
                        $value = str_replace('between', '', $value);
                        $value = explode('and', $value);
                        $min = $value[0];
                        $max = $value[1];
                        $cond .= " AND " . $field . " BETWEEN '" . (int)$min . "' AND  '" . (int)$max . "'";
                    }
                }
                if ($ft == 'between' && $value != "" && $_POST['pagename']=="SkuPerformance") {
                    $date = explode(' - ', $value);
                    $cond .= " AND " . $field . " between '" . $date[0] . "' AND '" . $date[1] . "'";
                }
                elseif ($ft == 'between' && $value != "" ) {
                    $date = date_create($value);
                    $dateformat = date_format($date, "Y-m-d");
                    if (strpos($field, 'start_date') !== false) {

                        $cond .= " AND " . $field . " between '" . $dateformat . "'";
                    } else {
                        $cond .= " AND '" . $dateformat . "'";
                    }
                }
            }

        }

        //echo $Grand_Total_having;die;
        if (isset($config['query']['LastQuery'])) {
            $having_clauses = $config['query']['LastQuery'];
        } elseif (isset($Grand_Total_having) && $Grand_Total_having == 1) {
        } else {
            echo 'else';
            $having_clauses = '';
        }
        //echo $having_clauses;die;
        /*  below code checks , to show all records or make paginations */
        if (Yii::$app->request->post('page_no') == 'All') {
            $offset_records = '';
        } else {
            $limit = ' limit ';
            $offset_records = (Yii::$app->request->post('records_per_page') * Yii::$app->request->post('page_no')) - Yii::$app->request->post('records_per_page') . ' , ' . Yii::$app->request->post('records_per_page');
            $offset_records = $limit . $offset_records;
        }
        // below code is to check how many total records are there without limit funtion in mysql
        $sql = $config['query']['FirstQuery'] . "   $cond
                " . $config['query']['GroupBy'] . " " . $having_clauses . $sortBy;

        if (strpos(Yii::$app->controller->module->requestedRoute, 'deals-maker') !== false) {
            $countSqlQuery = $config['query']['CountQuery'] . " $cond ";
            $pds = ProductDetails::findBySql($countSqlQuery)->asArray()->all();
            $total_records = $pds[0]["total_record"];
        }else{
            $pds = ProductDetails::findBySql($sql)->asArray()->all();
            $total_records = count($pds);
        }

        if ($nolimit) {
            $sql = $config['query']['FirstQuery'] . "   $cond
                " . $config['query']['GroupBy'] . " " . $having_clauses . $sortBy;


        } else {
            $sql = $config['query']['FirstQuery'] . "   $cond
                " . $config['query']['GroupBy'] . " " . $having_clauses . $sortBy . '  ' . $offset_records;

        }

        $pds = ProductDetails::findBySql($sql)->asArray()->all();
        //var_dump($sql); exit();

        $refine = [];
        if (($valuex != '' && $fieldx != '') || $pdq == '2') {
            foreach ($pds as $k => $v) {

                $refine[$k] = $v;
                if (strpos($v['isis_sku'], 'MY-') === false && strpos($v['isis_sku'], '/') !== false) {
                    $count = self::checkSameSKUQTY($v['isis_sku']);
                    if (count($count) > 0)
                        unset($refine[$k]);
                }
            }
        } else {
            $refine = $pds;
        }

        if ( (Yii::$app->controller->module->requestedRoute == 'deals-maker/generic-info' ||
            Yii::$app->controller->module->requestedRoute == 'deals-maker/generic-info-filter' ||
            Yii::$app->controller->module->requestedRoute == 'deals-maker/generic-info-sort') && $_POST['pagename']!="SkuPerformance" ) {
            foreach ($refine as $key => $value) {
                $DealmakerModel = new DealsMaker();
                $DealmakerModel->id = $value['dm_id'];
                $DealmakerModel->afterFindSkus();
                $refine[$key]['status_percentage'] = $DealmakerModel->progress;
            }
        }

        $refine['total_records'] = $total_records;
        return $refine;

    }
    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }
    public static function callAllStocks($nolimit = 0)
    {
        $sortBy = $cond = $having = "";
        $having .= "HAVING 1 = 1";
        $type = Yii::$app->request->post('type');
        $pdq = Yii::$app->request->post('pdqs');
        if (isset($pdq) && $pdq == '5') {
            $cond .= " AND stocks <= 0  AND fbl_stock = 0 AND fbl_99_stock = 0 ";
        }
        if (isset($pdq) && $pdq == '2') {
            $cond .= " AND stocks <= 0  ";
        }
        if (isset($pdq) && $pdq == '3') {
            $cond .= " AND fbl_stock = 0 and pd.is_fbl = 1 AND (sync_for = 2) ";
        }
        if (isset($pdq) && $pdq == '4') {
            $cond .= " AND fbl_99_stock = 0 and pd.is_fbl = 1 AND (sync_for = 3) ";
        }

        $field = Yii::$app->request->post('field');
        if ($type == 'sort' && ($field != 'avg_sales' && $field != 'total_stocks')) {
            $sort = Yii::$app->request->post('sort');
            $sortBy .= " ORDER BY pd." . $field . " " . $sort;
        } else {
            $sortBy .= " ORDER BY -pd.selling_status DESC, pd.selling_status ASC";
        }
        $valuex = $fieldx = '';
        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            foreach ($filters as $ff) {
                $refineFilters[$ff['filter-field']] = $ff;
            }

            foreach ($refineFilters as $rf) {
                $field = $rf['filter-field'];
                $ft = $rf['filterType'];
                $value = $rf['val'];
                if ($ft == 'like' && $value != "") {
                    if ($field=='c.name')
                        $cond .= " AND ".$field. " ".$ft. " '" . $value . "%' ";
                    else
                        $cond .= " AND pd." . $field . " " . $ft . " '" . $value . "%' ";
                }
                if ($ft == 'operator' && $value != "" && ($field != 'avg_sales' && $field != 'total_stocks')) {
                    // > operator
                    $value = trim($value);
                    if (strpos($value, '>') !== false && strpos($value, '=') === false) {
                        $value = str_replace('>', '', $value);
                        $cond .= " AND pd." . $field . " > '" . (int)$value . "' ";
                    }

                    // < operator
                    $value = trim($value);
                    if (strpos($value, '<') !== false && strpos($value, '=') === false) {
                        $value = str_replace('<', '', $value);
                        $cond .= " AND pd." . $field . " < '" . (int)$value . "' ";
                    }

                    // != operator
                    $value = trim($value);
                    if (strpos($value, '!=') !== false) {
                        $value = str_replace('!=', '', $value);
                        $cond .= " AND pd." . $field . " != '" . (int)$value . "' ";
                    }

                    // == operator
                    $value = trim($value);
                    if (strpos($value, '=') !== false && strpos($value, '>') === false && strpos($value, '<') === false) {
                        $value = str_replace('=', '', $value);
                        $cond .= " AND pd." . $field . " = '" . (int)$value . "' ";

                    }

                    // <= operator
                    $value = trim($value);
                    if (strpos($value, '<=') !== false) {
                        $value = str_replace('<=', '', $value);
                        $cond .= " AND pd." . $field . " <= '" . (int)$value . "' ";
                        if ($field == 'goodQty' && $value == '0') {
                            $valuex = $value;
                            $fieldx = $field;
                        }

                    }

                    // between operator
                    $value = trim(strtolower($value));
                    if (strpos($value, 'between') !== false && strpos($value, 'and') !== false) {
                        $value = str_replace('between', '', $value);
                        $value = explode('and', $value);
                        $min = $value[0];
                        $max = $value[1];
                        $cond .= " AND pd." . $field . " BETWEEN '" . (int)$min . "' AND  '" . (int)$max . "'";
                    }
                }
                else {
                    // > operator
                    $value = trim($value);
                    if (strpos($value, '>') !== false && strpos($value, '=') === false) {
                        $value = str_replace('>', '', $value);
                        $having .= " AND " . $field . " > '" . (int)$value . "' ";
                    }

                    // < operator
                    $value = trim($value);
                    if (strpos($value, '<') !== false && strpos($value, '=') === false) {
                        $value = str_replace('<', '', $value);
                        $having .= " AND " . $field . " < '" . (int)$value . "' ";
                    }

                    // != operator
                    $value = trim($value);
                    if (strpos($value, '!=') !== false) {
                        $value = str_replace('!=', '', $value);
                        $having .= " AND " . $field . " != '" . (int)$value . "' ";
                    }

                    // == operator
                    $value = trim($value);
                    if (strpos($value, '=') !== false && strpos($value, '>') === false && strpos($value, '<') === false) {
                        $value = str_replace('=', '', $value);
                        $having .= " AND " . $field . " = '" . (int)$value . "' ";

                    }

                    // <= operator
                    $value = trim($value);
                    if (strpos($value, '<=') !== false) {
                        $value = str_replace('<=', '', $value);
                        $having .= " AND " . $field . " <= '" . (int)$value . "' ";
                    }
                }
            }

        }
        if (Yii::$app->request->post('page_no') == 'All') {
            $offset_records = '';

        } else {
            $limit = ' limit ';
            $offset_records = (Yii::$app->request->post('records_per_page') * Yii::$app->request->post('page_no')) - Yii::$app->request->post('records_per_page') . ' , ' . Yii::$app->request->post('records_per_page');
            $offset_records = $limit . $offset_records;
        }

        $sd1 = date("Y-m-d", strtotime('first day of -6 months'));
        $ed1 = date("Y-m-d", strtotime('last day of last month'));

        // below code is to check how many total records are there without limit funtion in mysql
        $sql = "SELECT 
                    #CEIL(COUNT(oi.id) / 6) AS avg_sales, 
                    (IFNULL(goodQty, 0) + IFNULL(fbl_99_stock, 0) + IFNULL(fbl_stock, 0) + IFNULL(office_stocks, 0) + IFNULL(manual_stock, 0) + IFNULL(fbl_pavent_stock, 0) ) AS total_stocks,
                  pd.*,c.`name`
                FROM
                  `product_details` pd  LEFT JOIN `products` p ON p.id = pd.`sku_id` AND p.is_active = 1
                  LEFT JOIN category c ON c.`id` = p.`sub_category`
                  #INNER JOIN order_items oi ON oi.`sku_id` = pd.`sku_id`
                WHERE isis_sku IS NOT NULL  
                #AND (oi.`item_created_at` BETWEEN  '$sd1 00:00:00' AND  '$ed1 23:59:59' ) AND oi.item_status IN ('shipped','delivered','Processing','Processed','completed')
                  $cond
                GROUP BY pd.isis_sku $having $sortBy";

        $pds = ProductDetails::findBySql($sql)->asArray()->all();

        $total_records = count($pds);


        $sql = "SELECT 
                    #CEIL(COUNT(oi.id) / 3) AS avg_sales, 
                    (IFNULL(goodQty, 0) + IFNULL(fbl_99_stock, 0) + IFNULL(fbl_stock, 0) + IFNULL(office_stocks, 0) + IFNULL(manual_stock, 0) + IFNULL(fbl_pavent_stock, 0)) AS total_stocks,

                  pd.*,c.`name`
                FROM
                  `product_details` pd  LEFT JOIN `products` p ON p.id = pd.`sku_id` AND p.is_active = 1
                  LEFT JOIN category c ON c.`id` = p.`sub_category`
                  #INNER JOIN order_items oi ON oi.`sku_id` = pd.`sku_id`
                WHERE isis_sku IS NOT NULL  
                #AND (oi.`item_created_at` BETWEEN  '$sd1 00:00:00' AND  '$ed1 23:59:59' ) AND oi.item_status IN ('shipped','delivered','Processing','Processed','completed')  
                $cond
                GROUP BY pd.isis_sku $having $sortBy $offset_records";
        $pds = ProductDetails::findBySql($sql)->asArray()->all();
        $refine = [];
        if (($valuex != '' && $fieldx != '') || $pdq == '2') {
            foreach ($pds as $k => $v) {

                $refine[$k] = $v;
                if (strpos($v['isis_sku'], 'MY-') === false && strpos($v['isis_sku'], '/') !== false) {
                    $count = self::checkSameSKUQTY($v['isis_sku']);
                    if (count($count) > 0)
                        unset($refine[$k]);
                }
            }
        } else {
            $refine = $pds;
        }
        $refine['total_records'] = $total_records;
        return $refine;

    }


    public static function checkSameSKUQTY($sku)
    {
        $sql = "SELECT 
                  pd.*
                FROM
                  `product_details` pd where isis_sku is not null
                GROUP BY pd.isis_sku ";
        $pds = ProductDetails::findBySql($sql)->asArray()->all();
        $count = [];
        foreach ($pds as $v) {

            if ((substr($v['isis_sku'], 0, 6) == substr($sku, 0, 6)) && $v['stocks'] > 0) {
                $count[$v['isis_sku']] = +1;
            }
        }

        return $count;
    }

    public static function getChannelsProducts()
    {
        $sortBy = $cond = "";
        $type = Yii::$app->request->post('type');
        $sortBy .= " ORDER BY channel_sku desc";
        if ($type == 'sort') {
            $field = Yii::$app->request->post('field');
            $sort = Yii::$app->request->post('sort');
            if ($field == 'channel_sku' || $field == 'price')
                $sortBy .= " ORDER BY " . $field . " " . $sort;
        }

        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            foreach ($filters as $ff) {
                $refineFilters[$ff['filter-field']] = $ff;
            }

            foreach ($refineFilters as $rf) {
                $field = $rf['filter-field'];
                $ft = $rf['filterType'];
                $value = $rf['val'];
                if ($ft == 'like' && $value != "" && $field == 'channel_sku') {
                    $cond .= " AND " . $field . " " . $ft . " '" . $value . "%' ";
                }


            }
        }

        $sql = "SELECT 
              channel_sku,
              product_name,
              price,
              channel_id,
              stock_qty,
              last_update
            FROM
              `channels_products` cp WHERE 1 = 1 $cond
            $sortBy ";
        $pds = ChannelsProducts::findBySql($sql)->asArray()->all();

        $refine = [];
        foreach ($pds as $p) {
            if ($p['channel_id'] == 1)
                $refine[$p['channel_sku']]['blip_lazada_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 10)
                $refine[$p['channel_sku']]['909_lazada_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 3)
                $refine[$p['channel_sku']]['blip_11Street_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 9)
                $refine[$p['channel_sku']]['909_11Street_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 6)
                $refine[$p['channel_sku']]['blip_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 12)
                $refine[$p['channel_sku']]['philips_qty'] = $p['stock_qty'];

            if ($p['channel_id'] == 13)
                $refine[$p['channel_sku']]['deal4u_lazada_qty'] = $p['stock_qty'];

            $refine[$p['channel_sku']]['price'] = $p['price'];

        }

        // further refine
        /*$frefine = [];
        foreach($refine as $k=>$pd)
        {
            $frefine[$k]['blip_lazada_qty'] = isset($pd['blip_lazada_qty']) ? $pd['blip_lazada_qty'] : '0';
            $frefine[$k]['909_lazada_qty'] = isset($pd['909_lazada_qty']) ? $pd['909_lazada_qty'] : '0';
            $frefine[$k]['blip_11Street_qty'] = isset($pd['blip_11Street_qty']) ? $pd['blip_11Street_qty'] : '0';
            $frefine[$k]['909_11Street_qty'] = isset($pd['909_11Street_qty']) ? $pd['909_11Street_qty'] : '0';
            $frefine[$k]['blip_qty'] = isset($pd['blip_qty']) ? $pd['blip_qty'] : '0';
            $frefine[$k]['philips_qty'] = isset($pd['philips_qty']) ? $pd['philips_qty'] : '0';
            $frefine[$k]['price'] = $pd['price'];
        }

        $refine = $frefine;*/

        if ($type == 'sort') {
            $field = Yii::$app->request->post('field');
            $sort = Yii::$app->request->post('sort');
            if ($sort == 'asc') {
                $refine = self::_arraySort($refine, $field, SORT_ASC);
            } else {
                $refine = self::_arraySort($refine, $field, SORT_DESC);
            }

        }
        $refinex = [];
        if ($type == 'filter') {
            $filters = Yii::$app->request->post('filters');
            $refineFilters = [];
            //refining filters
            foreach ($filters as $ff) {
                $refineFilters[$ff['filter-field']] = $ff;
            }
            $rfCount = count($refineFilters);
            $fieldCount = 0;
            foreach ($refine as $k => $v) {
                foreach ($v as $l => $m) {
                    foreach ($refineFilters as $rf) {
                        $field = $rf['filter-field'];
                        $ft = $rf['filterType'];
                        $value = $rf['val'];

                        if ($ft == 'operator' && $value != "" && $field != 'price') {
                            $fieldCount++;
                            // > operator
                            $value = trim($value);
                            if (strpos($value, '>') !== false && $l == $field) {
                                $value = str_replace('>', '', $value);
                                if ($refine[$k][$field] > (int)$value) {
                                    $refinex[$k][$field] = $refine[$k][$l];
                                }
                            }
                            // < operator
                            $value = trim($value);
                            if (strpos($value, '<') !== false && $l == $field) {
                                $value = str_replace('<', '', $value);
                                if ($refine[$k][$field] < (int)$value) {
                                    $refinex[$k][$field] = $refine[$k][$l];
                                }
                            }

                            // != operator
                            $value = trim($value);
                            if (strpos($value, '!=') !== false && $l == $field) {
                                $value = str_replace('!=', '', $value);
                                if ($refine[$k][$field] < (int)$value) {
                                    $refinex[$k][$field] = $refine[$k][$l];
                                }
                            }

                            // between operator
                            $value = trim($value);
                            if (strpos($value, 'between') !== false && strpos($value, 'and') !== false && $l == $field) {
                                $value = str_replace('between', '', $value);
                                $value = explode('and', $value);
                                $min = $value[0];
                                $max = $value[1];
                                if ($refine[$k][$field] >= (int)$min && $refine[$k][$field] <= (int)$max) {

                                    $refinex[$k][$field] = $refine[$k][$l];
                                }
                            }

                        } /*else {
                            //echo $l . " : ".$refine[$k][$l]."<br/>";

                            if($field != 'channel_sku')
                            {
                                $refinex[$k][$field] = isset($refine[$k][$field]) ? $refine[$k][$field] : '0';
                            }
                        }*/

                    }

                }
                $refinex[$k]['price'] = $refine[$k]['price'];
            }
            if ($fieldCount > 0) {
                $refine = $refinex;
            }
        }
        return $refine;


    }


    private function _arraySort($array, $on, $order = SORT_ASC)
    {
        $new_array = array();
        $sortable_array = array();

        if (count($array) > 0) {
            foreach ($array as $k => $v) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        if ($k2 == $on) {
                            $sortable_array[$k] = $v2;
                        }
                    }
                } else {
                    $sortable_array[$k] = $v;
                }
            }

            switch ($order) {
                case SORT_ASC:
                    asort($sortable_array);
                    break;
                case SORT_DESC:
                    arsort($sortable_array);
                    break;
            }

            foreach ($sortable_array as $k => $v) {
                $new_array[$k] = $array[$k];
            }
        }

        return $new_array;
    }

    public static function generateOrdersCal($stocks = [], $poId = null, $filterss = true)
    {
        $threshold = '0';
        $settings = Settings::find()->where(['name' => 'stocks_high_threshold_alert'])->one();
        if ($settings) {
            $threshold = $settings->value;
        }
        $refine['a'] = [];
        $refine['b'] = [];
        $refine['c'] = [];
        $refine['d'] = [];
        $soA = $soB = $soC = $soD = [];
        //echo '<pre>';print_r($stocks);die;
        foreach ($stocks as $k => $sk) {

            $filters = $filterss;

            if($poId != null)
                $filters = false;


            if ($sk['stock_status'] != 'Do not order' /*&& $sk['stock_status'] != 'Not moving' && $sk['stock_status'] != 'Not Moving'*/ && $sk['stock_status'] && $filters) {
                $sk['isis_stks'] = $sk['isis_stks'];
                /*if ($sk['isis_stks'] < $sk['isis_threshold'] && $sk['isis_stks'] > $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['isis_threshold'];
                    $sk['threshold_org'] =  $sk['isis_threshold_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                } else if ($sk['isis_stks'] < $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['isis_threshold_critical'];
                    $sk['threshold_org'] =  $sk['isis_threshold_critical_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                } else if ($sk['isis_stks'] <= $threshold && isset($sk['isis_order_stocks']) && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $threshold;
                    $sk['threshold_org'] = $threshold;
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                }*/
                $sk['threshold'] = $sk['isis_threshold'];
                $sk['threshold_org'] =  $sk['isis_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoISIS']) ?  $sk['dealNoISIS'] : '0';
                if($sk['isis_order_stocks'] != 0){

                    if (strpos($sk['dealNo'], '+') !== false) {
                        $deal_no = explode('+',$sk['dealNo']);
                        $total_deal_no = array_sum($deal_no);
                    }else{
                        $total_deal_no = $sk['dealNo'];
                    }
                    $total = (int)$sk['threshold_org'] + (int)$total_deal_no;


                    if($total >= (int)$sk['isis_stks']) {
                        $soA[] = (int)$sk['isis_order_stocks'];
                        $refine['a'][$k] = $sk;
                    }
                }
            }
            else if ($filters === false) {

                $sk['isis_stks'] = $sk['isis_stks'];
                /*if ($sk['isis_stks'] < $sk['isis_threshold'] && $sk['isis_stks'] > $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['isis_threshold'];
                    $sk['threshold_org'] =  $sk['isis_threshold_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                } else if ($sk['isis_stks'] < $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['isis_threshold_critical'];
                    $sk['threshold_org'] =  $sk['isis_threshold_critical_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                } else if ($sk['isis_stks'] <= $threshold && isset($sk['isis_order_stocks']) && $sk['isis_order_stocks'] != '0') {
                    $sk['threshold'] = $threshold;
                    $sk['threshold_org'] =  $threshold;
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                    $refine['a'][$k] = $sk;
                } else {
                    $threshold = isset($sk['pds_threshold']) ? $sk['pds_threshold'] : '0';
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $sk['threshold'] = "<span style='color: #681818'>" . $threshold . "</span>";
                    $sk['threshold_org'] = "<span style='color: #681818'>" . $threshold . "</span>";
                    $refine['a'][$k] = $sk;
                }*/
                $sk['threshold'] = $sk['isis_threshold'];
                $sk['threshold_org'] =  $sk['isis_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoISIS']) ?  $sk['dealNoISIS'] : '0';
                if($sk['isis_order_stocks'] != 0 && $poId = null){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['isis_stks']) {
                        $soA[] = (int)$sk['isis_order_stocks'];
                        $refine['a'][$k] = $sk;

                    }
                } else {
                    $soA[] = (int)$sk['final_order_qty'];
                    $refine['a'][$k] = $sk;
                }



            }

            if (((int)$sk['blip_stks'] < 0 && $poId == null))
                $filters = false;
            else {
                $filters = $filterss;
            }

            if ($sk['blip_stock_status'] != 'Do not order' /*&& $sk['blip_stock_status'] != 'Not moving' && $sk['blip_stock_status'] != 'Not Moving'*/ && $sk['blip_stock_status'] != '' && $filters) {
                $sk['blip_stks'] = $sk['blip_stks'];
                /* if ($sk['blip_stks'] < $sk['fbl_blip_threshold'] && $sk['blip_stks'] > $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_blip_threshold'];
                     $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['b'][$k] = $sk;
                 } else if ($sk['blip_stks'] < $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_blip_threshold_critical'];
                     $sk['threshold_org'] = $sk['fbl_blip_threshold_critical_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['b'][$k] = $sk;
                 } else if ($sk['blip_stks'] <= $threshold && isset($sk['fbl_order_stocks']) && $sk['for'] == '2' && $sk['fbl_order_stocks'] != '0') {
                     $sk['threshold'] = $threshold;
                     $sk['threshold_org'] = $threshold;
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                     $refine['b'][$k] = $sk;

                 }*/
                $sk['threshold'] = $sk['fbl_blip_threshold'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBLZD']) ?  $sk['dealNoFBLZD'] : '0';
                if($sk['fbl_order_stocks'] != 0){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['blip_stks']) {
                        $soB[$k] = (int)$sk['fbl_order_stocks'];
                        $refine['b'][$k] = $sk;
                    }
                }
            }
            else if ($filters === false) {
                $sk['blip_stks'] = $sk['blip_stks'];
                /*if ($sk['blip_stks'] < $sk['fbl_blip_threshold'] && $sk['blip_stks'] > $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_blip_threshold'];
                    $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['b'][$k] = $sk;
                } else if ($sk['blip_stks'] < $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_blip_threshold_critical'];
                    $sk['threshold_org'] = $sk['fbl_blip_threshold_critical_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['b'][$k] = $sk;
                } else if ($sk['blip_stks'] <= $threshold && isset($sk['fbl_order_stocks']) && $sk['for'] == '2' && $sk['fbl_order_stocks'] != '0') {
                    $sk['threshold'] = $threshold;
                    $sk['threshold_org'] = $threshold;
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                    $refine['b'][$k] = $sk;

                } else {
                    $threshold = isset($sk['pds_threshold']) ? $sk['pds_threshold'] : '0';
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $sk['threshold'] = "<span style='color: #681818'>" . $threshold . "</span>";
                    $sk['threshold_org'] = "<span style='color: #681818'>" . $threshold . "</span>";
                    $refine['b'][$k] = $sk;
                }*/
                $sk['threshold'] = $sk['fbl_blip_threshold'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBLZD']) ? $sk['dealNoFBLZD'] : '0';
                if($sk['fbl_order_stocks'] != 0 && $poId = null){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['blip_stks']) {
                        $soB[$k] = (int)$sk['fbl_order_stocks'];
                        $refine['b'][$k] = $sk;
                    }
                } else {
                    $soB[$k] = (int)$sk['final_order_qty'];
                    $refine['b'][$k] = $sk;
                }
            }

            if (((int)$sk['909_stks'] < 0 && $poId == null))
                $filters = false;
            else {
                $filters = $filterss;
            }

            if ($sk['f909_stock_status'] != 'Do not order' /*&& $sk['f909_stock_status'] != 'Not moving' && $sk['f909_stock_status'] != 'Not Moving'*/
                && $sk['fbl909_order_stocks'] != '0' && $sk['f909_stock_status'] != '' && $filters) {
                $sk['909_stks'] = $sk['909_stks'];
                /*if ($sk['909_stks'] < $sk['fbl_909_threshold'] && $sk['909_stks'] > $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_909_threshold'];
                    $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;
                } else if ($sk['909_stks'] < $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                    $sk['threshold_org'] = $sk['fbl_909_threshold_critical_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;
                } else if ($sk['909_stks'] <= $threshold && isset($sk['fbl909_order_stocks']) && $sk['for'] == '3' && $sk['fbl909_order_stocks'] != 0) {
                    $sk['threshold'] = $threshold;
                    $sk['threshold_org'] = $threshold;
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;

                }*/
                $sk['threshold'] = $sk['fbl_909_threshold'];
                $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBL909']) ? $sk['dealNoFBL909'] : '0';
                if($sk['fbl909_order_stocks'] != 0 ){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['909_stks']) {
                        $soC[$k] = (int)$sk['fbl909_order_stocks'];
                        $refine['c'][$k] = $sk;
                    }
                }
            }
            else if ($filters === false) {
                $sk['909_stks'] = $sk['909_stks'];
                /* if ($sk['909_stks'] < $sk['fbl_909_threshold'] && $sk['909_stks'] > $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_909_threshold'];
                     $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 } else if ($sk['909_stks'] < $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                     $sk['threshold_org'] = $sk['fbl_909_threshold_critical_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 } else if ($sk['909_stks'] <= $threshold && isset($sk['fbl909_order_stocks']) && $sk['for'] == '3' && $sk['fbl909_order_stocks'] != 0) {
                     $sk['threshold'] = $threshold;
                     $sk['threshold_org'] = $threshold;
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;

                 } else {
                     $threshold = isset($sk['pds_threshold']) ? $sk['pds_threshold'] : '0';
                     $sk['threshold'] = "<span style='color: #681818'>" . $threshold . "</span>";
                     $sk['threshold_org'] = "<span style='color: #681818'>" . $threshold . "</span>";
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 }*/
                $sk['threshold'] = $sk['fbl_909_threshold'];
                $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBL909']) ? $sk['dealNoFBL909'] : '0';
                if($sk['fbl909_order_stocks'] != 0  && $poId = null){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['909_stks']) {
                        $soC[$k] = (int)$sk['fbl909_order_stocks'];
                        $refine['c'][$k] = $sk;
                    }
                } else {
                    $soC[$k] = (int)$sk['final_order_qty'];
                    $refine['c'][$k] = $sk;
                }
            }

            // avent stocks cal
            if (((int)$sk['avent_stks'] < 0 && $poId == null))
                $filters = false;
            else {
                $filters = $filterss;
            }

            if ($sk['avent_stock_status'] != 'Do not order' /*&& $sk['avent_stock_status'] != 'Not moving' && $sk['avent_stock_status'] != 'Not Moving'*/
                && $sk['avent_order_stocks'] != '0' && $sk['avent_stock_status'] != '' && $filters) {
                $sk['avent_stks'] = $sk['avent_stks'];
                /*if ($sk['909_stks'] < $sk['fbl_909_threshold'] && $sk['909_stks'] > $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_909_threshold'];
                    $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;
                } else if ($sk['909_stks'] < $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                    $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                    $sk['threshold_org'] = $sk['fbl_909_threshold_critical_org'];
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;
                } else if ($sk['909_stks'] <= $threshold && isset($sk['fbl909_order_stocks']) && $sk['for'] == '3' && $sk['fbl909_order_stocks'] != 0) {
                    $sk['threshold'] = $threshold;
                    $sk['threshold_org'] = $threshold;
                    $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                    $refine['c'][$k] = $sk;

                }*/
                $sk['threshold'] = $sk['fbl_avent_threshold'];
                $sk['threshold_org'] = $sk['fbl_avent_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBL909Avent']) ? $sk['dealNoFBL909Avent'] : '0';
                if($sk['avent_order_stocks'] != 0 ){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['avent_stks']) {
                        $soD[$k] = (int)$sk['avent_order_stocks'];
                        $refine['d'][$k] = $sk;
                    }
                }
            }
            else if ($filters === false) {
                $sk['avent_stks'] = $sk['avent_stks'];
                /* if ($sk['909_stks'] < $sk['fbl_909_threshold'] && $sk['909_stks'] > $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_909_threshold'];
                     $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 } else if ($sk['909_stks'] < $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                     $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                     $sk['threshold_org'] = $sk['fbl_909_threshold_critical_org'];
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 } else if ($sk['909_stks'] <= $threshold && isset($sk['fbl909_order_stocks']) && $sk['for'] == '3' && $sk['fbl909_order_stocks'] != 0) {
                     $sk['threshold'] = $threshold;
                     $sk['threshold_org'] = $threshold;
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['threshold'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;

                 } else {
                     $threshold = isset($sk['pds_threshold']) ? $sk['pds_threshold'] : '0';
                     $sk['threshold'] = "<span style='color: #681818'>" . $threshold . "</span>";
                     $sk['threshold_org'] = "<span style='color: #681818'>" . $threshold . "</span>";
                     $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                     $refine['c'][$k] = $sk;
                 }*/
                $sk['threshold'] = $sk['fbl_avent_threshold'];
                $sk['threshold_org'] = $sk['fbl_avent_threshold_org'];
                $sk['dealNo'] = ($sk['dealNoFBL909Avent']) ? $sk['dealNoFBL909Avent'] : '0';
                if($sk['avent_order_stocks'] != 0  && $poId = null){
                    $total = (int)$sk['threshold_org'] + (int)$sk['dealNo'];
                    if($total >= (int)$sk['avent_stks']) {
                        $soD[$k] = (int)$sk['avent_order_stocks'];
                        $refine['d'][$k] = $sk;
                    }
                } else {
                    $soD[$k] = (int)$sk['final_order_qty'];
                    $refine['d'][$k] = $sk;
                }
            }

        }
        //array_multisort($refine['a'],SORT_DESC, $soA);
        array_multisort($soA, SORT_DESC, $refine['a']);
        array_multisort($soB, SORT_DESC, $refine['b']);
        array_multisort($soC, SORT_DESC, $refine['c']);
        array_multisort($soD, SORT_DESC, $refine['d']);

        return $refine;
    }
    public static function generateOrderCalExtra($stocks = [])
    {
        $threshold = '0';
        $settings = Settings::find()->where(['name' => 'stocks_high_threshold_alert'])->one();
        if ($settings) {
            $threshold = $settings->value;
        }
        $refine = [];
        foreach ($stocks as $k => $sk) {

            $sk['threshold'] = $sk['isis_threshold'];
            $sk['threshold_org'] = $sk['isis_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoISIS']) ?  $sk['dealNoISIS'] : '0';
            $refine['a'][$k] = $sk;


            $sk['threshold'] = $sk['fbl_blip_threshold'];
            $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoFBLZD']) ? $sk['dealNoFBLZD'] : '0';
            $refine['b'][$k] = $sk;



            $sk['threshold'] = $sk['fbl_909_threshold'];
            $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoFBL909']) ? $sk['dealNoFBL909'] : '0';

        }

        return $refine;
    }
    public static function addBundle($Bundle,$poId,$Button_Clicked=''){
        /*echo '<pre>';
        print_r($_POST);
        die;*/
        $selectedSkus=[];
        foreach ($Bundle as $key=>$value){


            if ($key=='information'){
                $selectedSkus['information']=$Bundle['information'];
                continue;
            }

            $explode=explode('_',$key);
            $sku_id=$explode[2];
            $Bundle_id=$explode[1];
            if (!isset($explode[2])){
                echo '<pre>';print_r($explode);die;
            }
            $selectedSkus['information'][$explode[1]]['Sku_list'][$sku_id][$explode[0]]=$value;
        }
        /*echo '<pre>';
        print_r($selectedSkus);
        die;*/
        foreach ( $selectedSkus as $key=>$value ){
            foreach ( $value as $key1=>$val1 ){
                foreach ($val1['Sku_list'] as $key2=>$val2){
                    if (!isset($val1['bundle_id'])){
                        continue;
                    }

                    $CheckSkuAlreadyExist=PoDetails::findBySql("
                    SELECT * FROM po_details pod WHERE pod.po_id = ".$poId." AND 
                    pod.sku_id = ".self::exchange_values('id','sku_id',$key2,'product_details')."
                    AND pod.bundle = ".$val1['bundle_id']."
                    ")->asArray()->all();

                    if (empty($CheckSkuAlreadyExist)){
                        $pd = new PoDetails();
                        $pd->po_id = $poId;
                        $pd->sku = $val2['sku'];
                        $pd->nc12 = $val2['nc12'];
                        $pd->cost_price = $val2['cp'];
                        $pd->threshold = $val2['th'];
                        $pd->bundle=$val1['bundle_id'];
                        $pd->bundle_cost=$val1['bundle_cost'];
                        $pd->current_stock = $val2['cs'];
                        $pd->philips_stocks = $val2['ps'];
                        $pd->warehouse = $_POST['warehouse'];
                        $pd->bundle_quantity=$val1['quantity'];
                        // calculate order quanitty
                        //echo '<pre>';print_r($val1);die;
                        if ( $val1['type']!='VB' ){
                            if ($Button_Clicked=='Finalize' || $Button_Clicked=='Save'){
                                $OrderQuantity=self::calculateBundleSkuQuantity($val1['bundle_id'],$key2,$val1['final_quantity']);
                            }else{
                                $OrderQuantity=self::calculateBundleSkuQuantity($val1['bundle_id'],$key2,$val1['quantity']);
                            }
                        }else{

                            if ($Button_Clicked=='Finalize' || $Button_Clicked=='Save'){
                                if (!isset($val2['final']))
                                    $stock=$val1['quantity'];
                                else
                                    $stock=$val2['final'];
                                $OrderQuantity=$stock;
                            }else{
                                if (!isset($val2['stock']))
                                    $stock=$val1['quantity'];
                                else
                                    $stock=$val2['stock'];
                                $OrderQuantity=$stock;

                            }
                        }

                        $pd->order_qty=$OrderQuantity;
                        $pd->final_order_qty = $pd->order_qty;
                        $pd->sku_id = self::exchange_values('id','sku_id',$key2,'product_details');
                        $pd->save(false);
                    }else{
                        $pd = PoDetails::findOne($CheckSkuAlreadyExist[0]['id']);
                        $pd->po_id = $poId;
                        $pd->sku = $val2['sku'];
                        $pd->nc12 = $val2['nc12'];
                        $pd->cost_price = $val2['cp'];
                        $pd->threshold = $val2['th'];
                        $pd->bundle=$val1['bundle_id'];
                        $pd->bundle_cost=$val1['bundle_cost'];
                        $pd->current_stock = $val2['cs'];
                        $pd->philips_stocks = $val2['ps'];
                        $pd->warehouse = $_POST['warehouse'];
                        $pd->bundle_quantity=$val1['quantity'];
                        // calculate order quanitty

                        if ( $val1['type']!='VB' ){
                            if ($Button_Clicked=='Finalize' || $Button_Clicked=='Save'){
                                $OrderQuantity=self::calculateBundleSkuQuantity($val1['bundle_id'],$key2,$val1['final_quantity']);
                            }else{
                                $OrderQuantity=self::calculateBundleSkuQuantity($val1['bundle_id'],$key2,$val1['quantity']);
                            }
                        }else{

                            if ($Button_Clicked=='Finalize' || $Button_Clicked=='Save'){
                                if (!isset($val2['final']))
                                    $stock=$val1['final_quantity'];
                                else
                                    $stock=$val2['final'];
                                $OrderQuantity=$stock;
                            }else{
                                if (!isset($val2['stock']))
                                    $stock=$val1['quantity'];
                                else
                                    $stock=$val2['stock'];
                                $OrderQuantity=$stock;

                            }
                        }
                        $pd->order_qty=$OrderQuantity;
                        $pd->final_order_qty = $pd->order_qty;
                        $pd->sku_id = self::exchange_values('id','sku_id',$key2,'product_details');
                        $pd->save(false);
                    }
                    // code below to set the stock in transit values
                    if ( isset($_POST['button_clicked']) && $_POST['button_clicked']=='Finalize' ){
                        $warehouse = $_POST['warehouse'];
                        $pd = ProductDetails::find()->where(['isis_sku' => $val2['sku']])->one();

                        if($pd) {
                            $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                            if ($ps) {
                                $UpdateStockInTransit = ProductStocks::findOne($ps->id);
                                if ($warehouse == 'isis')
                                    $UpdateStockInTransit->stocks_intransit = (int) $OrderQuantity;
                                else if ($warehouse == 'blip')
                                    $UpdateStockInTransit->fbl_stocks_intransit = (int) $OrderQuantity;
                                else if ($warehouse == 'f909')
                                    $UpdateStockInTransit->fbl909_stocks_intransit = (int) $OrderQuantity;
                                $UpdateStockInTransit->update();
                            }

                        }
                    }
                }
            }

        }


        //die;
    }
    public static function calculateBundleSkuQuantity($Bundle_id,$Sku_id,$Bundle_Quantity){
        // actually $sku_id is product_detail id, now we will get the sku_id
        $sku_id = self::exchange_values('id','sku_id',$Sku_id,'product_details');
        $Bundle_Sku_Info = ProductRelationsSkus::find()->where(['child_sku_id'=>$sku_id,'bundle_id'=>$Bundle_id])->asArray()->all();
        if ( !isset($Bundle_Sku_Info[0]['child_quantity']) ) {
            $Sku_Quantity =1;
        }else{
            $Sku_Quantity = $Bundle_Sku_Info[0]['child_quantity'];
        }
        return $Sku_Quantity * $Bundle_Quantity;
    }
    public static function generateRandomPoCode($warehouse){
        if ($warehouse==1)
            $warehouse_name='IS';
        elseif ($warehouse==2)
            $warehouse_name='FBL-BLIP';
        elseif ($warehouse==3)
            $warehouse_name='FBL-AVENT';
        while(1){

            $rand_po_code = rand(1,999999999);
            $sql="SELECT * from product_stocks_po where po_code like '%".$rand_po_code."%'";
            $search_po_code = ProductStocks::findBySql($sql)->asArray()->all();
            if ( empty($search_po_code) ){
                $final_po_code=$rand_po_code;
                break;
            }
        }
        return 'A&O-'.strtoupper($_GET['category']).$final_po_code.$warehouse_name;
    }
    public static function generateInitialPo()
    {
        /*echo '<pre>';
        print_r($_POST);
        die;*/
        $selectedSkus = [];
        if ($_POST['warehouse'] == 'isis')
            $warehouse = '1';
        if ($_POST['warehouse'] == 'blip')
            $warehouse = '2';
        if ($_POST['warehouse'] == 'f909')
            $warehouse = '3';
        if ($_POST['warehouse'] == 'f909-4')
            $warehouse = '7';
        if ($_POST['warehouse'] == 'LZD-ORB')
            $warehouse = '8';
        if ($_POST['po_ship_to'] == '100289147')
            $warehouse = '4';
        if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'blip')
            $warehouse = '2';
        if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'f909')
            $warehouse = '3';
        if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'f909-4')
            $warehouse = '7';

        $poId = null;

        if ($_POST['po_status'] == 'New' && !isset($_POST['action'])) {
            $selectedSkus = isset($_POST['isis_po_skus']) ? $_POST['isis_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['blip_po_skus']) && empty($selectedSkus)) ? $_POST['blip_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_po_skus']) && empty($selectedSkus)) ? $_POST['f909_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_4_po_skus']) && empty($selectedSkus)) ? $_POST['f909_4_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['lzd_orb_po_skus']) && empty($selectedSkus)) ? $_POST['lzd_orb_po_skus'] : $selectedSkus;
            $po = new StocksPo();
            $po->po_code = $_POST['po_code'];
            //$po->po_seq = $_POST['po_seq'];
            $po->po_status = "Draft";
            $po->po_warehouse = $warehouse;
            $po->po_initiate_date = date('Y-m-d h:i:s', strtotime($_POST['po_date']));
            $po->po_bill = $_POST['po_bill_to'];
            $po->er_no = (isset($_POST['po_io_fbl'])) ? $_POST['po_io_fbl'] : '';
            $po->po_category = $_POST['category'];
            $po->po_seq = $_POST['po_seq'];
            $po->po_ship = $_POST['po_ship_to'];
            $po->remarks = $_POST['remarks'];
            $po->save();
            $poId = $po->id;
            //echo '<pre>';print_r($selectedSkus);die;

            foreach ($selectedSkus as $skuId) {
                if ($poId) {
                    /*
                     * */
                    //get the parent sku id
                    $Sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                    $Sku_Id = self::exchange_values('sku','id',$Sku,'products');
                    $Parent_Sku_Id = self::exchange_values('id','parent_sku_id',$Sku_Id,'products');

                    $pd = new PoDetails();
                    $pd->po_id = $poId;
                    $pd->sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                    $pd->nc12 = (isset($_POST['foc_nc12_' . $skuId])) ? $_POST['foc_nc12_' . $skuId] : $_POST['nc12_' . $skuId];
                    $pd->cost_price = (isset($_POST['foc_cp_' . $skuId])) ? $_POST['foc_cp_' . $skuId] : $_POST['cp_' . $skuId];
                    $pd->threshold = (isset($_POST['foc_th_' . $skuId])) ? $_POST['foc_th_' . $skuId] : $_POST['th_' . $skuId];
                    $pd->current_stock = (isset($_POST['foc_cs_' . $skuId])) ? $_POST['foc_cs_' . $skuId] : $_POST['cs_' . $skuId];
                    $pd->philips_stocks = (isset($_POST['foc_ps_' . $skuId])) ? $_POST['foc_ps_' . $skuId] : $_POST['ps_' . $skuId];
                    $pd->warehouse = $_POST['warehouse'];
                    //echo '<pre>';print_r($_POST);die;
                    $pd->order_qty = (isset($_POST['stock_' . $skuId])) ? $_POST['stock_' . $skuId] : $_POST['stock_' . $skuId];
                    if ($Parent_Sku_Id!='false'){
                        $pd->parent_sku_id = $Parent_Sku_Id;
                    }
                    $pd->final_order_qty = $pd->order_qty;
                    $pd->sku_id = $Sku_Id;
                    if (isset($_POST['foc_parent_sku_' . $skuId]))
                        $pd->parent_sku_id = $_POST['foc_parent_sku_' . $skuId];
                    $pd->save(false);


                }

            }

            if ( isset($_POST['bundle']) )
                self::addBundle($_POST['bundle'],$poId,'');

        }
        else if ($_POST['button_clicked'] == 'Finalize' ) {
            $status = 'Pending';
            if ($_POST['warehouse'] == 'isis')
                $warehouse = '1';
            if ($_POST['warehouse'] == 'blip')
                $warehouse = '2';
            if ($_POST['warehouse'] == 'f909')
                $warehouse = '3';
            if ($_POST['po_ship_to'] == '100289147') {
                $warehouse = '7';
                //$status = $_POST['po_status'];
            }
            if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'blip') {
                $warehouse = '2';
                //$status = $_POST['po_status'];
            }if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'f909') {
                $warehouse = '3';
                //  $status = $_POST['po_status'];
            }
            if ( ($_POST['po_ship_to'] == '100277760' || $_POST['po_ship_to'] == '100305127' ) && $_POST['warehouse'] == 'f909-4') {
                $warehouse = '7';
                //  $status = $_POST['po_status'];
            }

            $poId = $_POST['po_id'];
            $po = StocksPo::find()->where(['id' => $poId])->one();
            $po->po_status = $status;
            $po->po_final_date = date('Y-m-d h:i:s');
            $po->remarks = $_POST['remarks'];
            $po->update();
            $selectedSkus = isset($_POST['isis_po_skus']) ? $_POST['isis_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['blip_po_skus']) && empty($selectedSkus)) ? $_POST['blip_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_po_skus']) && empty($selectedSkus)) ? $_POST['f909_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['lzd_orb_po_skus']) && empty($selectedSkus)) ? $_POST['lzd_orb_po_skus'] : $selectedSkus;

            //add skus in po if new line item added.
            foreach ($selectedSkus as $skuId) {
                if ($poId) {
                    $Sku_Id = self::exchange_values('id','sku_id',$skuId,'product_details');
                    $Parent_Sku_Id = self::exchange_values('id','parent_sku_id',$Sku_Id,'products');
                    if(preg_match('/foc_/i',$skuId))
                        $pd = PoDetails::find()->where(['sku_id' => str_replace('foc_','',$skuId), 'po_id' => $poId])->asArray()->one();
                    else
                        $pd = PoDetails::find()->where(['sku_id' => $Sku_Id, 'po_id' => $poId])->asArray()->one();

                    if (!$pd) {
                        $pd = new PoDetails();
                        $pd->po_id = $poId;
                        $pd->sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                        $pd->nc12 = (isset($_POST['foc_nc12_' . $skuId])) ? $_POST['foc_nc12_' . $skuId] : $_POST['nc12_' . $skuId];
                        $pd->cost_price = (isset($_POST['foc_cp_' . $skuId])) ? $_POST['foc_cp_' . $skuId] : $_POST['cp_' . $skuId];
                        $pd->threshold = (isset($_POST['foc_th_' . $skuId])) ? $_POST['foc_th_' . $skuId] : $_POST['th_' . $skuId];
                        $pd->current_stock = (isset($_POST['foc_cs_' . $skuId])) ? $_POST['foc_cs_' . $skuId] : $_POST['cs_' . $skuId];
                        $pd->philips_stocks = (isset($_POST['foc_ps_' . $skuId])) ? $_POST['foc_ps_' . $skuId] : $_POST['ps_' . $skuId];
                        $pd->warehouse = $_POST['warehouse'];
                        $pd->order_qty = (isset($_POST['foc_stock_' . $skuId])) ? $_POST['foc_stock_' . $skuId] : $_POST['stock_' . $skuId];
                        $pd->final_order_qty = (isset($_POST['final_stock_foc_' . $skuId])) ? $_POST['final_stock_foc_' . $skuId] : $_POST['final_stock_' . $skuId];

                        $pd->sku_id = $Sku_Id;
                        if ($Parent_Sku_Id!='false'){
                            $pd->parent_sku_id = $Parent_Sku_Id;
                        }
                        if (isset($_POST['foc_parent_sku_' . $skuId]))
                            $pd->parent_sku_id = $_POST['foc_parent_sku_' . $skuId];
                        $pd->save(false);
                    }

                }
            }
            $pod = PoDetails::find()->where(['po_id' => $poId])->all();
            foreach ($pod as $item) {
                $Sku = $item->sku;
                $Sku_Id = self::exchange_values('sku','id',$Sku,'products');
                $pid = self::exchange_values('sku_id','id',$Sku_Id,'product_details');
                $Parent_Sku_Id = self::exchange_values('id','parent_sku_id',$Sku_Id,'products');
                $did = (isset($_POST['final_stock_' . $pid])) ? $_POST['final_stock_' . $pid] : null;
                $foc = (isset($_POST['final_stock_foc_' . $Sku_Id])) ? $_POST['final_stock_foc_' .$Sku_Id] : null;
                $did = ($did == null) ? $foc : $did;
                $pid = ($foc) ? 'foc_'.$Sku_Id : $pid;
                if ($did != null) {
                    if (in_array($pid, $selectedSkus)) {
                        $item->final_order_qty = (int)$did;
                        $item->is_finalize = '1';
                        if ($Parent_Sku_Id!='false'){
                            $item->parent_sku_id = $Parent_Sku_Id;
                        }
                        $item->update();
                        // update stock intransit
                        $sku = $item->sku;

                        $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                        if($pd) {
                            $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                            if ($ps) {
                                $item->current_stock = ((int)$item->current_stock < 0) ? 0 : (int)$item->current_stock;
                                if ($warehouse == 1)
                                    $ps->stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 2)
                                    $ps->fbl_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 3)
                                    $ps->avent_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 5)
                                    $ps->fbl_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 6)
                                    $ps->avent_stocks_intransit = (int)$item->final_order_qty;
                                $ps->save(false);
                            }
                        }
                    } else {
                        $item->is_finalize = '0';
                        $item->update();
                    }
                }
                else {
                    if (in_array($item->sku_id, $selectedSkus)) {
                        $item->order_qty = (isset($_POST['foc_stock_' . $item->sku_id])) ? $_POST['foc_stock_' . $item->sku_id] : $_POST['stock_' . $item->sku_id];
                        $item->final_order_qty = (int)(isset($_POST['final_stock_foc' . $item->sku_id])) ? $_POST['final_stock_foc' . $item->sku_id] : $_POST['final_stock_' . $item->sku_id];
                        $item->is_finalize = '1';
                        if ($Parent_Sku_Id!='false'){
                            $item->parent_sku_id = $Parent_Sku_Id;
                        }
                        $item->update();
                        // update stock intransit
                        $sku = $item->sku;
                        $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                        if($pd) {
                            $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                            if ($ps) {
                                $item->current_stock = ((int)$item->current_stock < 0) ? 0 : (int)$item->current_stock;
                                if ($warehouse == 1)
                                    $ps->stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 2)
                                    $ps->fbl_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 3)
                                    $ps->avent_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 5)
                                    $ps->fbl_stocks_intransit = (int)$item->final_order_qty;
                                else if ($warehouse == 6)
                                    $ps->avent_stocks_intransit = (int)$item->final_order_qty;
                                $ps->save(false);
                            }
                        }
                    } else {
                        $item->is_finalize = '1';
                        $item->update();
                    }
                }
            }
            if ( isset($_POST['bundle']) )
                self::addBundle($_POST['bundle'],$poId,'Finalize');

            if ($po->po_ship == '100278731' ) {
                if ($_SERVER['HTTP_HOST']=='philips.ezcommerce.io'){
                    ApiController::createER($poId);
                }
            }

        }

        else if (isset($_POST['button_clicked']) && $_POST['button_clicked'] == 'Save') {
            //echo '<pre>';print_r($_POST);die;
            $selectedSkus = isset($_POST['isis_po_skus']) ? $_POST['isis_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['blip_po_skus']) && empty($selectedSkus)) ? $_POST['blip_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_po_skus']) && empty($selectedSkus)) ? $_POST['f909_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_4_po_skus']) && empty($selectedSkus)) ? $_POST['f909_4_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['lzd_orb_po_skus']) && empty($selectedSkus)) ? $_POST['lzd_orb_po_skus'] : $selectedSkus;
            $po = StocksPo::findOne($_POST['po_id']);
            $po->po_code = $_POST['po_code'];
            //$po->po_seq = $_POST['po_seq'];
            $po->po_status = "Draft";
            $po->po_warehouse = $warehouse;
            $po->po_initiate_date = date('Y-m-d h:i:s', strtotime($_POST['po_date']));
            $po->po_bill = $_POST['po_bill_to'];
            $po->po_category = $_POST['category'];
            $po->er_no = (isset($_POST['po_io_fbl'])) ? $_POST['po_io_fbl'] : '';
            //echo $po->er_no;
            $po->po_ship = $_POST['po_ship_to'];
            $po->remarks = $_POST['remarks'];
            $po->save();
            $poId = $po->id;
            PoDetails::deleteAll(['po_id' => $poId]);
            foreach ($selectedSkus as $skuId) {
                if ($poId) {
                    //get the parent sku id
                    $Sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                    $Sku_Id = self::exchange_values('sku','id',$Sku,'products');
                    if (isset($_POST['foc_sku_' . $skuId])) {
                        $Parent_For_Non_Duplicate = explode('_','foc_sku_' . $skuId);
                        $Parent_For_Non_Duplicate = $Parent_For_Non_Duplicate[2];
                    }else{
                        $Parent_For_Non_Duplicate='';
                    }

                    $Parent_Sku_Id = self::exchange_values('id','parent_sku_id',$Sku_Id,'products');

                    //echo '<pre>';print_r($_POST);die;
                    if (isset($_POST['foc_parent_sku_'.$Parent_For_Non_Duplicate.'_' . $skuId])){
                        /*$Find_Sku_In_PO = PoDetails::find()->where(['po_id'=>$_POST['po_id'],'sku'=>$Sku])
                            ->andWhere(['parent_sku_id'=>$Parent_Sku_Id]);
                        die;*/
                    }
                    else
                        $Find_Sku_In_PO = PoDetails::find()->where(['po_id'=>$_POST['po_id'],'sku'=>$Sku])->asArray()->all();
                    // Find already exist and update , if not then create new.


                    if (empty($Find_Sku_In_PO)){
                        $pd = new PoDetails();
                        $pd->po_id = $poId;
                        $pd->sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                        $pd->nc12 = (isset($_POST['foc_nc12_' . $skuId])) ? $_POST['foc_nc12_' . $skuId] : $_POST['nc12_' . $skuId];
                        $pd->cost_price = (isset($_POST['foc_cp_' . $skuId])) ? $_POST['foc_cp_' . $skuId] : $_POST['cp_' . $skuId];
                        $pd->threshold = (isset($_POST['foc_th_' . $skuId])) ? $_POST['foc_th_' . $skuId] : $_POST['th_' . $skuId];
                        $pd->current_stock = (isset($_POST['foc_cs_' . $skuId])) ? $_POST['foc_cs_' . $skuId] : $_POST['cs_' . $skuId];
                        $pd->philips_stocks = (isset($_POST['foc_ps_' . $skuId])) ? $_POST['foc_ps_' . $skuId] : $_POST['ps_' . $skuId];
                        $pd->warehouse = $_POST['warehouse'];
                        $pd->order_qty = (isset($_POST['stock_' . $skuId])) ? $_POST['stock_' . $skuId] : $_POST['stock_' . $skuId];
                        if ($Parent_Sku_Id!='false'){
                            $pd->parent_sku_id = $Parent_Sku_Id;
                        }
                        $pd->final_order_qty =  (isset($_POST['foc_final_stock_' . $skuId])) ? $_POST['foc_final_stock_' . $skuId] : (isset($_POST['final_stock_' . $skuId])) ? $_POST['final_stock_' . $skuId] : $pd->order_qty ;
                        $pd->sku_id = $Sku_Id;
                        if (isset($_POST['foc_parent_sku_'.$Parent_Sku_Id.'_' . $skuId]))
                            $pd->parent_sku_id = $_POST['foc_parent_sku_'.$Parent_Sku_Id.'_' . $skuId];
                        $pd->save(false);

                    }
                    else{
                        echo (isset($_POST['foc_parent_sku_'.$Parent_For_Non_Duplicate.'_' . $skuId])) ? $_POST['final_stock_' .$_POST['foc_parent_sku_'.$Parent_For_Non_Duplicate.'_' . $skuId].'_'. $skuId] : "";
                        echo "</br>";
                        $pd = PoDetails::findOne($Find_Sku_In_PO[0]['id']);
                        $pd->po_id = $poId;
                        $pd->sku = (isset($_POST['foc_sku_' . $skuId])) ? $_POST['foc_sku_' . $skuId] : $_POST['sku_' . $skuId];
                        $pd->nc12 = (isset($_POST['foc_nc12_' . $skuId])) ? $_POST['foc_nc12_' . $skuId] : $_POST['nc12_' . $skuId];
                        $pd->cost_price = (isset($_POST['foc_cp_' . $skuId])) ? $_POST['foc_cp_' . $skuId] : $_POST['cp_' . $skuId];
                        $pd->threshold = (isset($_POST['foc_th_' . $skuId])) ? $_POST['foc_th_' . $skuId] : $_POST['th_' . $skuId];
                        $pd->current_stock = (isset($_POST['foc_cs_' . $skuId])) ? $_POST['foc_cs_' . $skuId] : $_POST['cs_' . $skuId];
                        $pd->philips_stocks = (isset($_POST['foc_ps_' . $skuId])) ? $_POST['foc_ps_' . $skuId] : $_POST['ps_' . $skuId];
                        $pd->warehouse = $_POST['warehouse'];
                        $pd->order_qty = (isset($_POST['stock_' . $skuId])) ? $_POST['stock_' . $skuId] : $_POST['stock_' . $skuId];
                        if ($Parent_Sku_Id!='false'){
                            $pd->parent_sku_id = $Parent_Sku_Id;
                        }
                        $pd->final_order_qty = (isset($_POST['final_stock_'.$Parent_For_Non_Duplicate. '_' . $skuId])) ? $_POST['final_stock_'.$Parent_For_Non_Duplicate. '_' . $skuId] : $_POST['final_stock_' . $skuId];
                        $pd->sku_id = $Sku_Id;
                        if (isset($_POST['foc_parent_sku_'.$Parent_For_Non_Duplicate.'_' . $skuId]))
                            $pd->parent_sku_id = $_POST['foc_parent_sku_'.$Parent_For_Non_Duplicate.'_' . $skuId];
                        $pd->save(false);
                    }




                }
            }
            if ( isset($_POST['bundle']) )
                self::addBundle($_POST['bundle'],$poId,'Save');
        }

        else if (isset($_POST['button_clicked']) && $_POST['button_clicked'] == 'Mark Shipped') {

            $selectedSkus = isset($_POST['isis_po_skus']) ? $_POST['isis_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['blip_po_skus']) && empty($selectedSkus)) ? $_POST['blip_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_po_skus']) && empty($selectedSkus)) ? $_POST['f909_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['f909_4_po_skus']) && empty($selectedSkus)) ? $_POST['f909_4_po_skus'] : $selectedSkus;
            $selectedSkus = (isset($_POST['lzd_orb_po_skus']) && empty($selectedSkus)) ? $_POST['lzd_orb_po_skus'] : $selectedSkus;
            $po = StocksPo::findOne($_POST['po_id']);
            $po->po_code = $_POST['po_code'];
            //$po->po_seq = $_POST['po_seq'];
            $po->po_status = "Shipped";
            $po->po_warehouse = $warehouse;
            $po->remarks = $_POST['remarks'];
            $po->save();
            $poId = $po->id;

            $pod = PoDetails::find()->where(['po_id' => $poId])->all();
            foreach ($pod as $item) {
                // update stock intransit
                $sku = $item->sku;
                $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                if($pd)
                {
                    $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                    if ($ps) {
                        if ($po->po_warehouse == 1)
                            $ps->stocks_intransit = '0';
                        else if ($po->po_warehouse == 2)
                            $ps->fbl_stocks_intransit = '0';
                        else if ($po->po_warehouse == 5)
                            $ps->fbl_stocks_intransit = '0';
                        else if ($po->po_warehouse == 3)
                            $ps->avent_stocks_intransit = '0';
                        else if ($po->po_warehouse == 6)
                            $ps->avent_stocks_intransit = '0';
                        $ps->save(false);
                    }
                }


            }
        }



    }

    public static function SkuInDeal($Sku_Id='',$Channel_Id){

        $cond='';
        if ($Sku_Id!=''){
            $cond = " AND dms.sku_id = ".$Sku_Id;
        }

        $sql = "SELECT dm.NAME,dm.start_date,dm.end_date,dm.`status` AS deal_status, dms.`status` AS sku_status,p.sku
                FROM deals_maker dm
                INNER JOIN deals_maker_skus dms ON
                dms.deals_maker_id = dm.id
                INNER JOIN products p ON
                p.id =dms.sku_id
                WHERE dm.`status` = 'active' AND dm.channel_id = ".$Channel_Id." AND dms.`status` = 'Approved' 
                ".$cond;


        $getSkuDetail = DealsMakerSkus::findBySql($sql)->asArray()->all();

        $_redefine = [];
        foreach ($getSkuDetail as $key=>$value){
            $_redefine[] = $value['sku'];
        }

        return $_redefine;
    }
    // check if selected po sku have active deal going on
    public static function getDealSkuInfo($skuId)
    {
        $sql = "SELECT dm.id,dms.deal_target,c.prefix,dm.name as deal_name
                FROM `deals_maker_skus` dms
                INNER JOIN `deals_maker` dm ON dm.id = dms.`deals_maker_id`
                INNER JOIN channels c ON c.id = dm.`channel_id`
                WHERE (dm.status = 'new' OR dm.status = 'active') AND dms.sku_id = '$skuId'
                AND (NOW( ) BETWEEN  (dm.`start_date` - INTERVAL 20 DAY) AND dm.`end_date`)";

        $connection = Yii::$app->db;

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();


        return $result;
    }

    public static function getTotalRecordsQuery($sql)
    {
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return count($result);
    }

    // check logged user request sku count
    public static function getRequesterDealCount($isAdmin = null, $view = false, $detail = false, $filters = '')
    {

        $limit = 10;
        $uid = Yii::$app->user->id;
        $cond = '';
        $connection = Yii::$app->db;
        //$cond= self::DealsDetailFilters();
        if ($view) {
            if (isset($_GET['page']) && $_GET['page'] != 'All')
                $offset = " limit " . 10 * ($_GET['page'] - 1) . ", 10";
            elseif (isset($_GET['page']) && $_GET['page'] == 'All')
                $offset = "limit 0,5000";
            else
                $offset = "";
            if (!$isAdmin)
                $cond = "AND dm.`requester_id` = '$uid'";

            if ($detail) {
                $sql = "SELECT 
                      (dms.actual_sales) as actual_sales,dms.deals_maker_id,dms.status,dms.sku_id,p.sku,c.`name` AS channel_name,dms.deal_price,dms.`deal_margin`,dms.`deal_margin_rm`,dm.`start_date`,dm.`end_date`,dm.`name`,dms.`status`,CAST(IFNULL(cp.`low_price`,(IFNULL(p.promo_price,p.rccp))) AS UNSIGNED ) AS low_price,dm.`status` as deal_status,(dms.deal_target) as deal_target
                    FROM
                      `deals_maker_skus` dms 
                      JOIN `deals_maker` dm 
                        ON dm.`id` = dms.`deals_maker_id` 
                       JOIN `products` p ON p.`id` = dms.`sku_id`
                       JOIN channels c ON c.id = dm.`channel_id`
                        LEFT JOIN competitive_pricing cp ON cp.`sku_id` = p.`id` 
                    WHERE dms.`status` IS NOT NULL " . $filters . "
                    GROUP BY deals_maker_id,sku_id
                     ORDER BY dms.id  ";
                //echo $sql;die;
                $Total_records = self::getTotalRecordsQuery($sql);


            } else {
                $sql = "SELECT 
                      sum(dms.actual_sales) as actual_sales,dms.deals_maker_id,dms.status,dms.sku_id,p.sku,c.`name` as channel_name,dms.deal_price,dms.`deal_margin`,dms.`deal_margin_rm`,cp.`low_price`,sum(dms.deal_target) as deal_target
                    FROM
                      `deals_maker_skus` dms 
                      JOIN `deals_maker` dm 
                        ON dm.`id` = dms.`deals_maker_id` 
                       JOIN `products` p ON p.`id` = dms.`sku_id`
                       LEFT JOIN competitive_pricing cp ON cp.`sku_id` = p.`id` AND cp.`created_at` BETWEEN dm.`start_date` AND dm.`end_date`
                       JOIN channels c ON c.id = dm.`channel_id`
                    WHERE dm.status <> 'expired' AND NOW() BETWEEN dm.`start_date` AND dm.`end_date` AND dms.`status` = 'Approved' 
                    $cond
                    GROUP BY p.`sku`";
                $Total_records = self::getTotalRecordsQuery($sql);
            }
            $sql .= $offset;
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            $data['result'] = $result;
            $data['total_records'] = $Total_records;
            return $data;
        } else {


            if (!$isAdmin)
                $cond = "AND dm.`requester_id` = '$uid' GROUP BY dm.`requester_id`";

            $sql = "SELECT COUNT(dms.id) AS cnt ,dm.`requester_id` FROM `deals_maker_skus` dms
                JOIN `deals_maker` dm ON dm.`id` = dms.`deals_maker_id`
                WHERE dm.status <> 'expired' AND NOW() BETWEEN dm.`start_date` AND dm.`end_date` AND dms.`status` = 'Approved' $cond
               ";
            $Total_records = self::getTotalRecordsQuery($sql);


            $command = $connection->createCommand($sql);
            $result = $command->queryOne();


            if ($result) {
                return $result['cnt'];
            } else
                return '0';
        }
    }

    public static function QueryAndSortWithIndex($sql, $index)
    {
        $list = [];
        $getResults = ProductDetails::findBySql($sql)->asArray()->all();
        foreach ($getResults as $key => $value) {
            $list[$value[$index]] = $value;
        }
        return $list;
    }



    // for presta and magento get stock inventory total
    public static function getCurrentStocktotal()
    {

        //////new one///////
        $sql="SELECT `wr`.name , sum(`wrsl`.`available` * `pr`.cost) as inventory
            FROM
                `warehouses` wr
            INNER JOIN 
                `warehouse_stock_list` wrsl
            ON 
                `wr`.id=`wrsl`.warehouse_id
             INNEr JOIN 
                `products`  pr
             ON
                `pr`.`sku`=`wrsl`.`sku`
            
            WHERE 1
            group by
                 `wrsl`.`warehouse_id`";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
       // var_dump($result);
     //   exit;
        //////new one///////
       /* $sql="SELECT SUM((whl.available * p.cost)) AS total_inventory FROM
                `warehouse_stock_list` whl
                INNER JOIN
                    `products` p
                ON
                    p.id=whl.product_id
                  ";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        return isset($result['total_inventory']) ? $result['total_inventory']:0;*/
       // die();
    }
    // get total stock value in RM
    public static function getCurrentStockValue($forStockDefination = false)
    {
        $data = $total = $office = $isis = $fbl909 = $fblblip = $category = $fbl_pavent_stock = $transit_stock = [];
        $sql = "SELECT 
                  pd.sku_id,
                  (
                    (
                      IFNULL(goodQty, 0) + IFNULL(fbl_99_stock, 0) + IFNULL(fbl_stock, 0) + IFNULL(fbl_pavent_stock, 0) + 
                      IFNULL(office_stocks, 0) 
                      #+ IFNULL(stocks_intransit, 0)
                    ) * p.cost
                  ) AS total_stock,
                  ((IFNULL(goodQty, 0)) * p.cost) AS isis_stock,
                  ((IFNULL(office_stocks, 0)) * p.cost) AS office_stock,
                  ((IFNULL(fbl_99_stock, 0)) * p.cost) AS fbl909_stock,
                  ((IFNULL(fbl_stock, 0)) * p.cost) AS fblblip_stock,
                  ((IFNULL(fbl_pavent_stock, 0)) * p.cost) AS fbl_pavent_stock,
                   #((IFNULL(stocks_intransit, 0)) * p.cost) AS transit_stock, 
                  c.`id` AS category
                FROM
                  `product_details` pd 
                  INNER JOIN `products` p 
                    ON p.`id` = pd.`sku_id` 
                  INNER JOIN category c ON c.id = p.`sub_category`
                  INNER JOIN `product_stocks` ps 
                    ON ps.`stock_id` = pd.`id` 
                WHERE p.is_active = 1 
                  AND p.sub_category != '167' ";

        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $poSotckInTransit = self::getCurrentStockInTransitPO();
        //echo '<pre>';print_r($poSotckInTransit);die;
        $transit_stock = [];
        if ($result) {
            if ($forStockDefination) {
                foreach ($result as $sk) {
                    $data[$sk['sku_id']]['stock_count'] = $sk['total_stock'];
                    if ($sk['total_stock'] > 30000) {
                        $data[$sk['sku_id']]['status'] = 'high';
                    } else if ($sk['total_stock'] >= 10000 && $sk['total_stock'] <= 30000) {
                        $data[$sk['sku_id']]['status'] = 'medium';
                    } else if ($sk['total_stock'] <= 10000) {
                        $data[$sk['sku_id']]['status'] = 'slow';
                    }
                }

            } else {
                foreach ($result as $sk) {
                    /*echo '<pre>';
                    print_r($sk);
                    die;*/
                    $total[] = $sk['total_stock'];
                    $office[] = $sk['office_stock'];
                    $isis[] = $sk['isis_stock'];
                    $fblblip[] = $sk['fblblip_stock'];
                    $fbl909[] = $sk['fbl909_stock'];
                    $fbl_pavent_stock[] = $sk['fbl_pavent_stock'];
                    //$transit_stock[] = $sk['transit_stock'];

                    if (!isset($poSotckInTransit[$sk['sku_id']]))
                        $transit_stock[] = 0;
                    else
                        $transit_stock[] = $poSotckInTransit[$sk['sku_id']]['total'];
                    if ($sk['category'] == 'mcc')
                        $category['mcc'][] = $sk['isis_stock'];
                    else
                        $category['dap'][] = $sk['isis_stock'];
                }
                $data = [
                    'total' => number_format(array_sum($total) + array_sum($transit_stock), 2),
                    'Office Stocks' => number_format(array_sum($office), 2),
                    'ISIS Stocks' => number_format(array_sum($isis), 2),
                    'FBL-Blip Stocks' => number_format(array_sum($fblblip), 2),
                    'FBL-909 Stocks' => number_format(array_sum($fbl909), 2),
                    'FBL-Avent Stocks' => number_format(array_sum($fbl_pavent_stock), 2),
                    'Stocks in Transit' => number_format(array_sum($transit_stock), 2),
                 //   'MCC' => number_format(array_sum($category['mcc']), 2),
                    'DAP' => number_format(array_sum($category['dap']), 2),

                ];
                arsort($data, SORT_NUMERIC);
            }

        }
        return $data;
    }

    public static function getStockSkus($channel_id)
    {
        $settings = Settings::find()->where(['name' => 'philips_stocks_check'])->one();
        $philipsStockCheckValue = $settings->value;
        $sql = "(SELECT 
                  isis_sku,
                  IF(pd.`stocks` < 0, 0, pd.stocks) AS goodQty,
                  pd.`philips_stocks`,
                  channel_id,
                  api_response,
                  marketplace,
                  cp.`sku` AS prd_id,
                  manual_stock
                FROM
                  `product_details` pd 
                  INNER JOIN `channels_products` cp 
                    ON cp.channel_sku = pd.`isis_sku` 
                  INNER JOIN `channels` c 
                    ON cp.`channel_id` = c.`id` 
                  INNER JOIN `products` p 
                    ON p.id = pd.`sku_id` 
                    AND p.`is_active` = 1 
                WHERE  pd.`sku_id` IS NOT NULL) 
                UNION
                ALL 
                (SELECT 
                  isis_sku,
                  IF(pd.`stocks` < 0, 0, pd.stocks)  AS goodQty,
                  pd.`philips_stocks`,
                  channel_id,
                  api_response,
                  marketplace,
                  cp.`sku` AS prd_id,
                  manual_stock
                FROM
                  `product_details` pd 
                  INNER JOIN `channels_products` cp 
                    ON cp.channel_sku = pd.`isis_sku` 
                  INNER JOIN `channels` c 
                    ON cp.`channel_id` = c.`id` 
                WHERE  pd.`sku_id` IS NULL) ; ";

        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $r) {
            //echo '<pre>';echo ;die;
            /*if($r['goodQty'] == 0 && $r['philips_stocks'] >= $philipsStockCheckValue)
                $r['goodQty'] = 1;
            else {
                $r['goodQty'] =  $r['goodQty'];
            }*/
            if ($r['marketplace'] == 'street') {
                if ($r['api_response'][0]!='<')
                    continue;
                $cpResp = self::_refineResponse($r['api_response']);
                if ($cpResp) {
                    if (isset($cpResp['ProductStock']['prdStckNo'])) {
                        $stockCode = $cpResp['ProductStock']['prdStckNo'];
                        $productNo = $cpResp['ProductStock']['prdNo'];
                        $r['stockCode'] = $stockCode;
                        $r['productNo'] = $productNo;
                        $refine[$r['channel_id']][] = $r;
                    } else {
                        foreach ($cpResp['ProductStock'] as $k) {

                            $colorCode = substr($k['mixDtlOptNm'], 0, 1);
                            $stockCode = $k['prdStckNo'];
                            $productNo = $k['prdNo'];
                            $r['stockCode'] = $stockCode;
                            $r['productNo'] = $productNo;
                            $qty [] = self::getSkuQty($r['isis_sku'] . $colorCode, $philipsStockCheckValue);
                        }

                        $r['goodQty'] = array_sum($qty);
                        $refine[$r['channel_id']][] = $r;
                    }
                }

            } else {
                $refine[$r['channel_id']][] = $r;
            }
        }

        return $refine;
    }

    public static function getStockSkusById($sku)
    {
        $settings = Settings::find()->where(['name' => 'philips_stocks_check'])->one();
        $philipsStockCheckValue = $settings->value;
        $sql = "(SELECT 
                  isis_sku,
                  IF(pd.`stocks` < 0, 0, pd.stocks) + pd.office_stocks + pd.manual_stock AS goodQty,
                  pd.`philips_stocks`,
                  channel_id,
                  api_response,
                  marketplace,
                  cp.`sku` AS prd_id
                FROM
                  `product_details` pd 
                  INNER JOIN `channels_products` cp 
                    ON cp.channel_sku = pd.`isis_sku` 
                  INNER JOIN `channels` c 
                    ON cp.`channel_id` = c.`id` 
                  INNER JOIN `philips_cost_price` pcp 
                    ON pcp.id = pd.`sku_id` 
                    AND pcp.`is_active` = 1 
                WHERE (
                    pd.`goodQty` >= 0 || pd.`fbl_stock` > 0 || pd.`fbl_99_stock` > 0
                  ) AND pd.isis_sku = '" . $sku . "' 
                  AND pd.`sku_id` IS NOT NULL) 
                UNION
                ALL 
                (SELECT 
                  isis_sku,
                  IF(pd.`stocks` < 0, 0, pd.stocks)  + pd.office_stocks + pd.manual_stock AS goodQty,
                  pd.`philips_stocks`,
                  channel_id,
                  api_response,
                  marketplace,
                  cp.`sku` AS prd_id 
                FROM
                  `product_details` pd 
                  INNER JOIN `channels_products` cp 
                    ON cp.channel_sku = pd.`isis_sku` 
                  INNER JOIN `channels` c 
                    ON cp.`channel_id` = c.`id` 
                WHERE (
                    pd.`goodQty` >= 0 || pd.`fbl_stock` > 0 || pd.`fbl_99_stock` > 0
                  ) AND pd.isis_sku = '" . $sku . "' 
                  AND pd.`sku_id` IS NULL) ; ";

        $connection = Yii::$app->db;

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $r) {
            /*if($r['goodQty'] == 0 && $r['philips_stocks'] >= $philipsStockCheckValue)
                $r['goodQty'] = 1;
            else {
                $r['goodQty'] =  $r['goodQty'];
            }*/
            if ($r['marketplace'] == 'street') {
                $cpResp = self::_refineResponse($r['api_response']);
                if ($cpResp) {
                    if (isset($cpResp['ProductStock']['prdStckNo'])) {
                        $stockCode = $cpResp['ProductStock']['prdStckNo'];
                        $productNo = $cpResp['ProductStock']['prdNo'];
                        $r['stockCode'] = $stockCode;
                        $r['productNo'] = $productNo;
                        $refine[$r['channel_id']][] = $r;
                    } else {
                        foreach ($cpResp['ProductStock'] as $k) {

                            $colorCode = substr($k['mixDtlOptNm'], 0, 1);
                            $stockCode = $k['prdStckNo'];
                            $productNo = $k['prdNo'];
                            $r['stockCode'] = $stockCode;
                            $r['productNo'] = $productNo;
                            $qty [] = self::getSkuQty($r['isis_sku'] . $colorCode, $philipsStockCheckValue);
                        }

                        $r['goodQty'] = array_sum($qty);
                        $refine[$r['channel_id']][] = $r;
                    }
                }

            } else {
                $refine[$r['channel_id']][] = $r;
            }
        }

        return $refine;
    }

    public static function getSkuQty($sku, $philipsStockCheckValue)
    {
        $sql = "
            SELECT goodQty as goodQty ,`philips_stocks` FROM `product_details` pd
            WHERE pd.`goodQty` >= 0 AND pd.`goodQty` IS NOT NULL AND pd.`sku_id` IS NOT NULL 
            AND isis_sku = '$sku'";
        $connection = Yii::$app->db;

        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        if ($result) {
            if ($result['goodQty'] == 0 && $result['philips_stocks'] >= $philipsStockCheckValue)
                return $result['philips_stocks'];
            else {
                return $result['goodQty'];
            }
        } else {
            return '0';
        }
    }
    public static function getDistMonthSales($dr = null){
    $userid = Yii::$app->user->identity;
    if($userid['role_id'] == 8){
        return;
    }
    $distributors_list = self::get_distributors();
    //self::debug($distributors_list);


    //self::debug($w);
    $dist_sale_list=[];
    foreach ( $distributors_list as $key=>$distributor_detail ){
        $w =HelpUtil::getWarehouseDetailByUserId($distributor_detail['id']);
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                $cond = " AND  oi.`item_created_at`  >= '$sd 00:00:00'
                    AND oi.`item_created_at`  <= '$ed 23:59:59' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)
                    AND w.user_id = u.id AND u.role_id = 8";

            endif;

        }
        else {
            $cond = " AND oi.`fulfilled_by_warehouse` IN ($w)
                    AND w.user_id = u.id AND u.role_id = 8
                    AND MONTH( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."')) = '".gmdate('m')."'
                    AND YEAR( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."')) = '".gmdate('Y')."'";
        }
        //self::debug($w);
        $cutSql = "SELECT SUM(oi.sub_total) AS current_total, u.username FROM `order_items` oi ,`warehouses` w, `user` u
        WHERE oi.item_status NOT IN (".GraphsUtil::GetMappedCanceledStatuses().") $cond
        GROUP BY u.id";
//echo $cutSql;die;

        $result = OrderItems::findBySql($cutSql)->asArray()->all();
        if ($result){
            $dist_sale_list[]=$result[0];
        }

    }
    //self::debug($dist_sale_list);
    return $dist_sale_list;
}

    public static function getMonthSales($dr = null)
    {
        $w =HelpUtil::getWarehouseDetail();
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                    $cond = " AND  oi.`item_created_at`  >= '$sd 00:00:00'
                    AND oi.`item_created_at`  <= '$ed 23:59:59' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)";

            endif;

        } else {
                $cond = " AND oi.`fulfilled_by_warehouse` IN ($w)
                    AND MONTH( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."')) = '".gmdate('m')."'
                    AND YEAR( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."')) = '".gmdate('Y')."'";
        }

        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")
            $stats_column=" count(distinct(`oi`.`order_id`)) ";
        else
            $stats_column=" SUM(`oi`.`sub_total`) ";

        $connection = Yii::$app->db;
        $cutSql = "SELECT $stats_column AS cur_total FROM `order_items` oi 
        WHERE oi.item_status NOT IN (".GraphsUtil::GetMappedCanceledStatuses().") $cond
       ";
       // echo $cutSql; die();
        $command = $connection->createCommand($cutSql);
        $result1 = $command->queryOne();

        $prvSql = "SELECT $stats_column AS prev_total FROM `order_items` oi
              WHERE oi.item_status NOT IN (" . GraphsUtil::GetMappedCanceledStatuses() . ")
              AND oi.`fulfilled_by_warehouse` IN ($w)
              AND MONTH( CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) = MONTH(CURRENT_DATE()  - INTERVAL 1 MONTH)
              AND YEAR( CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) = YEAR(CURRENT_DATE()  - INTERVAL 1 MONTH)";

        $command = $connection->createCommand($prvSql);
        $result2 = $command->queryOne();
       // self::debug($result2);
        $cf = self::numberFormat($result1['cur_total']);
        $cur = (isset($_GET['stats_by']) && $_GET['stats_by']=="orders") ? $result1['cur_total']:number_format($result1['cur_total'], 2);
        $pre = (isset($_GET['stats_by']) && $_GET['stats_by']=="orders") ? $result2['prev_total']:number_format($result2['prev_total'], 2);
        //self::debug($result2);
        if ($result2['prev_total'] && $result2['prev_total'] > 0){
            $prevforcast = (($result1['cur_total'] - $result2['prev_total']) / $result2['prev_total']) * 100;
            $prevforcast = number_format($prevforcast, 2);
        }else {
            $prevforcast = 0;
        }

       return $ret = [
            'cur' => $cur,
            'cf' => $cf,
            'prev' => $prevforcast
        ];
    }

    public static function getStocksInfoBySku($skuId, $full = false)
    {
        $sql = "SELECT (goodQty + office_stocks + fbl_99_stock + fbl_stock + fbl_pavent_stock + fbl_d4u_stock) AS current_stocks,office_stocks,goodQty AS isis_stocks,fbl_stock,fbl_99_stock,fbl_d4u_stock,fbl_pavent_stock FROM `product_details` WHERE sku_id = '$skuId'";
        $connection = Yii::$app->db;

        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        if ($result) {
            return ($full) ? $result : $result['current_stocks'];
        } else {
            return '0';
        }
    }

    public static function getActualSalesCountBySkuForDeal($skuId,$status,$dealObj)
    {
        $ds = date("Y-m-d 00:00:00",strtotime($dealObj['start_date']));
        $de = date("Y-m-d 23:59:59",strtotime($dealObj['end_date']));
        $channel = $dealObj['channel_id'];
        $sql = "SELECT COUNT(oi.quantity) AS `sales` FROM order_items  oi
                INNER JOIN orders o ON o.id = oi.`order_id`
                WHERE o.`channel_id` = '$channel' AND oi.sku_id = '$skuId'
                AND oi.`item_created_at` >= '$ds' AND oi.`item_created_at` <= '$de' 
                AND o.`order_status` IN ('shipped','pending')";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        if ($result && $status == 'Approved') {
            return $result['sales'];
        } else {
            return '0';
        }
    }
    // recursive  stocks
    public static function _recursiveCall($func, $v, $params, $limit, $depth = 0, $sales, $responseIndex)
    {
        if ($v->marketplace == 'shopee') {
            $params['offset'] = $limit * $depth;
            $response = ApiController::$func($v, $params);
            if (isset($response[$responseIndex])) {
                $saleDepth = $response[$responseIndex];
                $more = $response['more'];
                $sales = array_merge($sales, $saleDepth);
                if ($more == 1) {
                    return self::_recursiveCall($func, $v, $params, $limit, ++$depth, $sales, $responseIndex);
                } else {
                    return $sales;
                }
            }
        }
    }


    public static function numberFormat($n, $precision = 2)
    {
        if ($n < 1000000) {
            // Anything less than a million
            $n_format = number_format($n);
        } else if ($n < 1000000000) {
            // Anything less than a billion
            $n_format = number_format($n / 1000000, $precision) . 'M';
        } else {
            // At least a billion
            $n_format = number_format($n / 1000000000, $precision) . 'B';
        }

        return $n_format;
    }

    // upcoming deals
    public static function getUpcomingDeals()
    {
        $connection = Yii::$app->db;
        $sql = "SELECT DISTINCT(`name`) as name,DATEDIFF(start_date,NOW()) AS starts_in FROM `deals_maker` dm
                LEFT JOIN `deals_maker_skus` dms ON dms.`deals_maker_id` = dm.`id`
                WHERE dms.`status` = \"Approved\" AND dm.`status` != 'expired'
                AND start_date > NOW()";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        return $result;
    }

    public static function getSoonOos()
    {
        $sql="SELECT `wsl`.`sku`,`wsl`.`available`, `w`.`name` as warehouse_name,`wsl`.`stock_in_transit`,`ts`.`status`,
                ROUND(`wsl`.`available`/`ts`.`threshold`) as days
                FROM
                    `warehouse_stock_list` wsl
                INNER JOIN
                    `threshold_sales` ts
                 ON
                    `ts`.`sku`=`wsl`.`sku` AND `ts`.`warehouse_id`=`wsl`.`warehouse_id`
                INNER JOIN
                    `warehouses` w 
                ON 
                    `w`.`id`=`wsl`.`warehouse_id` AND `ts`.`warehouse_id`=`w`.`id`
                 WHERE
                    LOWER(`ts`.`status`)!='not moving' AND `wsl`.`available` > 0
                 HAVING days <=10";

        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        return  $command->queryAll();
    }


    // old name getNevMarginOrder
    public static function currentUnderStockOrder()
    {
        $w = HelpUtil::getWarehouseDetail();
            $sql = "SELECT 
                        w.NAME AS warehouse,
                        oi.item_sku AS sku,
                         c.prefix AS shop_name,
                         o.`order_number`,
                         oi.`item_updated_at`,
                         oi.`item_status`,
                         wrhl.available AS current_stock,
                         oi.quantity AS order_count,
                         wrhl.`updated_at` AS last_update
                        FROM products pd
                        INNER JOIN `warehouse_stock_list` wrhl ON wrhl.sku=pd.sku
                        INNER JOIN order_items oi ON oi.`item_sku` = pd.`sku`
                        INNER JOIN orders o ON o.id = oi.`order_id`
                        INNER JOIN channels c ON c.id = o.`channel_id`
                        INNER JOIN warehouse_channels whc ON whc.channel_id = c.id
                        INNER JOIN warehouses w ON w.id = whc.warehouse_id
                        WHERE wrhl.`available` <= 0 AND (oi.`item_status` = 'pending') 
                        AND oi.`fulfilled_by_warehouse` IN ($w) AND
                         oi.`item_updated_at` BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        return $result;
    }

    public static function getStockInTrans()
    {
        $list = [];
        $connection = Yii::$app->db;
        $sql = "SELECT sku,final_order_qty,er_no , (`pod`.`cost_price` * `pod`.`stock_in_transit`) as in_transit_amount 
                FROM 
                `po_details` pod 
                INNER JOIN `product_stocks_po` po ON po.id = pod.`po_id` 
                WHERE po.`po_status` IN ('Pending','Partial Shipped')";
      //  echo $sql; die();
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $re) {
            $list[$re['sku']] =array('final_order_qty'=>$re['final_order_qty'],'in_transit_amount'=>$re['in_transit_amount']);
        }

        return $list;
    }
public static function getWarehouseDetail($warehouses=[])
{

    $userid = Yii::$app->user->identity;
    $id = $userid['id'];
    $role_name=UserRoles::find()->select('name')->where(['id'=>$userid['role_id']])->scalar(); // get role of logged in person
    if(strtolower($role_name) == 'distributor') {
        if ($warehouses){
            if (in_array('all',$warehouses))
                $sql=Warehouses::find()->select('id')->where(['user_id'=>$id])->asArray()->all();
            else
                $sql=Warehouses::find()->select('id')->where(['user_id'=>$id])->andWhere(['in', 'id', $warehouses])->asArray()->all();
        }
        else
            $sql=Warehouses::find()->select('id')->where(['user_id'=>$id])->asArray()->all();
        $ids=array_column($sql,'id'); // fetch id column
        $ids= implode($ids,',');  // comma seperated make
        if($ids)
            return $ids;
        else
            return 0;

    }
    else{
        if ($warehouses){
            if (in_array('all',$warehouses))
                $sql = Warehouses::find()->select('id')->asArray()->all();
            else
                $sql = Warehouses::find()->select('id')->andWhere(['in', 'id', $warehouses])->asArray()->all();
        }
        else
            $sql = Warehouses::find()->select('id')->asArray()->all();
        $ids=array_column($sql,'id'); // fetch id column
        return implode($ids,',');  // comma seperated make
    }

}
    public static function getWarehouseDetailByUserId($userId)
    {
        $connection = Yii::$app->getDb();
        $data='';
        $sql = "SELECT w.`id` FROM warehouses w WHERE w.`user_id` = '$userId';";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $it => $i) {
            $data .=strval($i['id']);
            $data .=',';
        }
        $data=substr($data ,0 ,-1);

        if($data == NULL || $data == '')
        {
            return 0;
        }
        else {
            return $data;
        }

    }
   /* public static function getDistributorSale()
    {
        $distributor = "SELECT SUM(oi.paid_price * oi.quantity) AS total_sale, u.username AS username
                        FROM 
                          order_items oi
                        INNER JOIN 
                          warehouses w ON w.id = oi.fulfilled_by_warehouse
                        INNER JOIN user u
                          ON u.id = w.user_id
                        WHERE
                          u.role_id = 8 AND oi.item_status NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                        GROUP BY u.id";
        //echo $distributor; die();
        $data = Warehouses::findBySql($distributor)->asArray()->all();

        return $data;
    }*/

    public static function distributor_sale($channel_ids=[]) /// parameter array of channel ids
    {
        $where="";
        if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
            $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";

        if($channel_ids)
            $where .= " AND `o`.`channel_id` IN (".$channel_ids.")";
        //
        $userid = Yii::$app->user->identity;
        $role_name=UserRoles::find()->select('name')->where(['id'=>$userid['role_id']])->scalar();
        if(strtolower($role_name)=='distributor') // if logged_in user is distributor
        {
            return false;
            //$where .= " AND u.id='".$userid['id']."'";
        }
        $distributor = "SELECT SUM(oi.sub_total) AS sales, u.username AS dealer_name,
                           count(distinct(o.id)) as orders ,
                           min(o.order_updated_at) as minimum,
                         DATEDIFF(CURDATE(), min(o.order_updated_at)) AS first_order_days_passed
                        FROM 
                          order_items oi
                          INNER JOIN orders o 
                          ON o.id=oi.order_id
                        INNER JOIN 
                          warehouses w ON w.id = oi.fulfilled_by_warehouse
                        INNER JOIN user u
                          ON u.id = w.user_id
                         INNER JOIN `user_roles` ur
                          ON u.role_id=ur.id
                        WHERE
                          LOWER(ur.name)='distributor' AND oi.item_status NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                          $where
                        GROUP BY
                        u.id";
       // echo $distributor; die();
        $data = Warehouses::findBySql($distributor)->asArray()->all();
        return $data;
    }
    /*public static function getTotalRevenueForMainDashboard()
    {
        $filters = "'pending','shipped','completed'";
        $where ="";
        if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
        {
            $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";
        }
        $w = HelpUtil::getWarehouseDetail();
        $order_cus_count = "SELECT COUNT(o.id) as order_count,  count(DISTINCT(o.customer_fname),2) as customer_count
                                    from orders o, order_items oi
                                  where oi.`order_id` = o.`id`
                                  AND oi.fulfilled_by_warehouse IN ($w) AND o.order_status IN ($filters)
                                  $where";
        $total_price_items = "SELECT sum(paid_price * quantity) as total_items_price, sum(quantity) as total_items
                                FROM order_items oi where oi.fulfilled_by_warehouse IN ($w) AND item_status IN ($filters)
                                ";
        $orders = Orders::findBySql($order_cus_count)->asArray()->all();
        $orders_count = $orders[0]["order_count"];
        $customer_count =$orders[0]["customer_count"] ;

        $order_items = Orders::findBySql($total_price_items)->asArray()->all();
        $total_revenu = $order_items[0]["total_items_price"];
        $total_items = $order_items[0]["total_items"];

        $avg_tran_val =round(($orders_count!=0) ? $total_revenu/$orders_count : 0,2);
        $avg_item_per_customer =round(($customer_count)?$total_items/$customer_count : 0,2) ;

        $data=[
            'total_revenue' =>$total_revenu ,// self::number_format_short($total_revenu, 2),
            'total_customer' => $customer_count,
            'avg_tran_val' => self::number_format_short($avg_tran_val, 2),
            'avg_item_per_customer' => $avg_item_per_customer
        ];
        return $data;
    }*/
public static function getTotalRevenueForDashboard($marketplace=[], $shopName=[],$dr = null)
{
    $filters = "'pending','shipped','completed'";
    $w = HelpUtil::getWarehouseDetail();
    $join = '';
    $where = '';

    if ($marketplace){
        $where .= " AND c.marketplace in ("."'" . implode ( "', '", $marketplace ) . "'".")";
    }
    if ($shopName){
        $where .= " AND c.name in ("."'" . implode ( "', '", $shopName ) . "'".")";
    }
    if ($dr) {
        $dr = explode(' to ', $dr);
        if(is_array($dr)):
            $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
            $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

            $where .= " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' ";


        endif;
    } else {
        $sd = HelpUtil::get_utc_time(date('Y-m-01') ." 00:00:00");
        $ed = HelpUtil::get_utc_time(date('Y-m-d') ." 23:59:59");

        $where .= " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' ";

    }
    $order_cus_count = "SELECT COUNT(o.id) AS order_count, COUNT(DISTINCT(o.customer_fname),2) AS customer_count
                        FROM orders o
                        INNER JOIN order_items oi ON
                        oi.order_id = o.id 
                        INNER JOIN channels c on 
                        c.id = o.channel_id
                        where oi.`order_id` = o.`id` AND oi.fulfilled_by_warehouse IN ($w) AND o.order_status IN ($filters) $where;";

    $total_price_items = "SELECT SUM(paid_price * quantity) AS total_items_price, SUM(quantity) AS total_items
                          FROM order_items oi
                          INNER JOIN orders o ON
                          o.id = oi.order_id 
                          INNER JOIN channels c on 
                          c.id = o.channel_id
                          where oi.fulfilled_by_warehouse IN ($w) AND item_status IN ($filters) $where;";

    $orders = Orders::findBySql($order_cus_count)->asArray()->all();
    $orders_count = $orders[0]["order_count"];
    $customer_count =$orders[0]["customer_count"] ;

    if ($customer_count==1){
        $order_cus_count = "SELECT COUNT(o.id) AS order_count, COUNT(o.customer_fname) AS customer_count
                        FROM orders o
                        INNER JOIN order_items oi ON
                        oi.order_id = o.id 
                        INNER JOIN channels c on 
                        c.id = o.channel_id
                        where oi.`order_id` = o.`id` AND oi.fulfilled_by_warehouse IN ($w) AND o.order_status IN ($filters) $where;";
        $orders = Orders::findBySql($order_cus_count)->asArray()->all();
        $customer_count =$orders[0]["customer_count"] ;
    }

    $order_items = Orders::findBySql($total_price_items)->asArray()->all();
    $total_revenu = $order_items[0]["total_items_price"];
    $total_items = $order_items[0]["total_items"];

    $avg_tran_val =round(($orders_count!=0) ? $total_revenu/$orders_count : 0,2);
    $avg_item_per_customer =round(($customer_count)?$total_items/$customer_count : 0,2) ;

    $data=[
        'total_revenue' => self::number_format_short($total_revenu, 2),
        'total_customer' => $customer_count,
        'avg_tran_val' => self::number_format_short($avg_tran_val, 2),
        'avg_item_per_customer' => $avg_item_per_customer
    ];
    return $data;
}
 private function get_consuctive_oos($item_sku,$warehouse_id)
    {
        $count=0;
        $from= date('Y-m-d', strtotime("-60 days"));  // last 2 months
        $query=WarehouseStockArchive::find()->orderBy(['id' => SORT_DESC])->where(['and', "date_archive>='$from'", "sku='$item_sku'","warehouse_id = '$warehouse_id'"])->all();
        if($query){
            foreach($query as $item){
                if($item['stock'] <= 0)
                    $count++;
                else
                    break;
            }
        }
        return $count; // returns for how many consuctive days stock remain 0
    }
    //
    public static function getOutOfStock($type=null)  //count or list
    {
        $w=HelpUtil::getWarehouseDetail();
        $list=array(); // return;
        if($type=="list")
        {
                $sql="SELECT wsl.sku,wsl.available,w.name AS warehouse_name, th.`status`,wsl.warehouse_id
                    FROM warehouse_stock_list wsl
                    JOIN warehouses w
                    ON w.id = wsl.warehouse_id
                    left JOIN products p 
                    ON p.sku = wsl.sku
                    left JOIN threshold_sales th
                    ON th.product_id=p.id AND wsl.warehouse_id = th.warehouse_id 
                    WHERE 1=1
                    AND w.id IN ($w)
                    AND wsl.available <=0 AND w.is_active = 1
                    #AND wsl.sku = '0U-014X-8XHX'
                    ORDER BY wsl.sku";


            $result = Yii::$app->db->createCommand($sql)->queryAll();
            if($result){
                foreach($result as $item){
                    $list[]=array(
                        'qty'=>$item['available'],
                        'sku'=>$item['sku'],
                        'selling_status'=>$item['status'],
                        'warehouse'=>$item['warehouse_name'],
                        'oos_days'=> self::get_consuctive_oos($item['sku'],$item['warehouse_id']),
                    );
                }
            }
            return $list;
        }
        else
         {
                 $sql = "SELECT count(*) as count FROM warehouses w inner join 
                        warehouse_stock_list wsl on wsl.warehouse_id = w.id WHERE w.is_active = 1 AND wsl.available <= 0
                        AND w.id IN ($w) ";

            $result = Yii::$app->db->createCommand($sql)->queryOne();
            return $result['count'];
        }

    }

    public static function getOssInIsis()
    {
        $connection = Yii::$app->db;
        $sql = "SELECT sr.*,pd.stocks,pd.philips_stocks
                FROM `stocks_report` sr
                INNER JOIN `products` p ON p.sku = sr.isis_sku
                INNER JOIN product_details pd on pd.sku_id = p.id
                WHERE p.is_active = 1 AND p.sub_category != '167'
                ORDER BY date_archive DESC;
                ";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = $refinex = [];
        foreach ($result as $re) {

            if (!isset($refine[$re['isis_sku']])) {
                $refine[$re['isis_sku']] = $re;
            }

        }
        // get only less or equal 0 stocks array
        foreach ($refine as $k => $r) {
            if ($r['stocks'] <= 0) {
                $days = round(abs(time() - strtotime($r['date_archive'])) / 86400);
                $refinex[$k] = ['days' => $days, 'selling_status' => $r['selling_status'], 'stocks' => $r['stocks'], 'phq' => $r['philips_stocks']];
            }

        }
        arsort($refinex, SORT_NUMERIC);

        return $refinex;
    }



    public static function getAgingStock()
    {
        $w = HelpUtil::getWarehouseDetail();
        $data=array();
        $final_data=array();

            $sql="SELECT `whs`.`available` as current_stock,`wsa`.`stock`,`whs`.`sku`,`wsa`.`date_archive`,`w`.`name` as warehouse_name,
                    `p`.`cost`
                FROM
                    `warehouse_stock_list` whs
                INNER JOIN
                    `warehouse_stock_archive` wsa
                ON
                    `wsa`.`sku`=`whs`.`sku` 
                INNER JOIN `products` p 
                ON  
                    `wsa`.`sku`=`p`.`sku`
                INNER JOIN
                    `warehouses` w 
                ON
                    `whs`.`warehouse_id`=`w`.`id` and  `wsa`.`warehouse_id`=`w`.`id`
                 WHERE
                    `whs`.`available` > 0
                    AND 
                    `w`.`id` IN ($w)
                 ORDER  BY `wsa`.`date_archive` DESC";

            $result=Yii::$app->db->createCommand($sql)->queryAll();
            if($result)
            {
                foreach($result as $res)
                {
                    $data[$res['sku']][$res['warehouse_name']][]=$res;
                }
            }

            if($data)
            {
                $final_data= self::aging_stock_prepare($data);
            }

            return $final_data;
    }

    private static function aging_stock_prepare($data)
    {
        $final_data=array();
        foreach($data as $key=>$val)
        {

            foreach($val as $warehouse=>$record)
            {
                if(count($record) < 2){ continue ;}  // if not more than 10 days stock maintained then skip
                else
                {
                    for($i=0;$i<count($record);$i++)
                    {
                        if($i > 0 && $record[$i]['stock']!=$record[$i-1]['stock'])
                        {
                            if($i >= 10) // if for atleast 10 days constant
                            {
                                $final_data[]=array(
                                    'sku'=>$record[$i]['sku'],
                                    'days'=> round(abs(time() - strtotime($record[$i]['date_archive'])) / 86400),
                                    'warehouse'=>$record[$i]['warehouse_name'],
                                    'stock'=>$record[$i]['current_stock'],
                                    'value'=>($record[$i]['current_stock'] * $record[$i]['cost']),
                                );
                                break;
                            }
                            break;
                        }
                        if(count($record)==($i+1)) // if last and stock constant
                        {
                            $final_data[]=array(
                                'sku'=>$record[$i]['sku'],
                                'days'=> round(abs(time() - strtotime($record[$i]['date_archive'])) / 86400),
                                'warehouse'=> $record[$i]['warehouse_name'],
                                'stock'=> $record[$i]['current_stock'],
                                'value'=>($record[$i]['current_stock'] * $record[$i]['cost']),
                            );
                        }

                    }
                }

            }
        }
        return $final_data;
    }



    // get SKU cost price from PO and sales data
    public static function skuCostPrice($sku, $qty, $price)
    {
        $connection = Yii::$app->db;
        $sql = "SELECT p.`sku`,ss.po_date,ss.`price`,ss.`po_qty`,ss.`sales`,pd.`stocks` AS stock_in_hand ,ss.`to_pick`,ss.`sku_id`
                FROM `sku_stock_sales` ss
                INNER JOIN products p ON p.id = ss.`sku_id`
                INNER JOIN `product_details` pd ON pd.isis_sku = p.sku 
                WHERE p.sku = '{$sku}' 
                ORDER BY p.sku ASC , po_date ASC";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $total = count($result) - 1;
        $qtyDalta[0] = null;
        $previous['stock'] = 0;
        $previous['cost'] = 0;
        if ($result) {
            foreach ($result as $k => $re) {
                $prev = null;
                // scenario 1
                if (($k != $total) && ((float)$re['sales'] == (float)$re['po_qty'])) {
                    continue;
                } else if ((float)$re['sales'] < (float)$re['po_qty']) {
                    // print_r($qtyDalta);
                    if (isset($qtyDalta[0])) {
                        $inside = 1;
                        $diff = $qtyDalta[1] + $re['po_qty'];
                        $target = $diff;
                        $qtyDalta[1] = $diff;
                    } else {
                        $inside = 0;
                        $diff = $re['po_qty'] - $re['sales'];
                        $target = $qty - $diff;
                        $target = ($target < 0) ? $target * -1 : $target;
                        // $inside = (($target != 0) && ($qty > $target)) ? 1 : 0;
                        $qtyDalta[0] = $target;
                        $qtyDalta[1] = $diff;
                    }
                    if (($diff > $qty) && $inside == 0) {
                        // check if prev is greater then chosen price and have stocks (50% of target)
                        $price = $re['price'];
                        if ($price != 0 && $price < $previous['cost'] && (abs($qty / 2)) <= $previous['stock'])
                            $price = $previous['cost'];
                        break;
                    } else if ($inside == 0 && $target == 0) {
                        // check if prev is greater then chosen price and have stocks (50% of target)
                        $price = $re['price'];
                        if ($price != 0 && $price < $previous['cost'] && (abs($qty / 2)) <= $previous['stock'])
                            $price = $previous['cost'];
                        break;
                    } else if (($target >= $qty) && ($inside == 1)) {
                        // check if prev is greater then chosen price and have stocks (50% of target)
                        $price = $re['price'];
                        if ($price != 0 && $price < $previous['cost'] && (abs($qty / 2)) <= $previous['stock'])
                            $price = $previous['cost'];
                        break;
                    } else if ($k == $total && ((int)$re['stock_in_hand']) >= (int)$qty) {
                        // check if prev is greater then chosen price and have stocks (50% of target)
                        $price = $re['price'];
                        if ($price != 0 && $price < $previous['cost'] && (abs($qty / 2)) <= $previous['stock'])
                            $price = $previous['cost'];
                        break;
                    }

                } else if ($k == $total && ((int)$re['stock_in_hand']) >= (int)$qty) {
                    $price = $re['price'];
                    break;
                }
                $previous['stock'] = $re['po_qty'] - $re['sales'];
                $previous['cost'] = $re['price'];
            }
        }
        return $price;


    }

    public static function getFblStock($sku_id, $channel_id)
    {
        $sku = self::exchange_values('id','sku',$sku_id,'products');
        $connection = Yii::$app->db;
        $sql = "SELECT wsl.available FROM warehouses w
                INNER JOIN warehouse_channels wc ON
                wc.warehouse_id = w.id
                INNER JOIN channels c ON 
                c.id = wc.channel_id
                INNER JOIN warehouse_stock_list wsl ON
                wsl.warehouse_id = w.id
                WHERE w.warehouse = 'lazada-fbl' AND c.id= $channel_id AND wsl.sku='".$sku."';";
        //echo $sql;die;

        $command = $connection->createCommand($sql);
        $result = $command->queryOne();

        $ret = (isset($result['available'])) ? $result['available'] : 0;
        return $ret;

    }

    public static function getLatestQtyBySku($sku)
    {
        $query = "SELECT IFNULL(SUM(quantity),'0') AS qty FROM `order_items` 
                JOIN orders o ON o.id = order_items.`order_id`
                JOIN channels c ON c.id = o.`channel_id`
                        WHERE item_status NOT IN ('canceled','Canceled by Customer','cancelled','failed','invalid','returned','unpaid')
                        AND (item_created_at BETWEEN '2018-11-22 00:00:00' AND '2018-11-26 23:59:59' ) AND item_sku = '$sku' AND c.id = '15'";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($query);
        $result = $command->queryOne();
        return $result['qty'];


    }

    public static function getSkuActualStockNumber()
    {
        $sql = "SELECT 
              oi.`item_sku`,
             oi.`quantity`,
              CASE
               WHEN oi.`full_response` LIKE '%Own Warehouse%' THEN 'FBL'
               WHEN oi.`full_response` NOT LIKE '%Own Warehouse%' THEN 'ISIS'
               END
              AS Warehouse,
              c.prefix AS shop 
            FROM
              order_items oi 
              JOIN orders o 
                ON o.id = oi.`order_id` 
              JOIN channels c 
                ON c.id = o.`channel_id` 
               JOIN `product_details` pd ON pd.`isis_sku` = oi.item_sku
            WHERE oi.`item_status` IN (
                'Payment Complete',
                'pending',
                'Processing',
                'ready_to_ship',
                'Shipping in Progress',
                'to_confirm_receive'
              ) 
              AND item_created_at >= DATE_ADD(CURDATE(), INTERVAL - 14 DAY) ";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $data = $refine = [];
        foreach ($result as $re) {
            if ($re['Warehouse'] == 'ISIS')
                $data[$re['item_sku']]['isis_stocks'][] = $re['quantity'];
            else {
                if ($re['shop'] == 'BLP-LZD') {
                    $data[$re['item_sku']]['blip_fbl_stocks'][] = $re['quantity'];
                }
                if ($re['shop'] == 'D4U-LZD') {
                    $data[$re['item_sku']]['d4u_fbl_stocks'][] = $re['quantity'];
                }
                if ($re['shop'] == 'AV-LZD') {
                    $data[$re['item_sku']]['avent_fbl_stocks'][] = $re['quantity'];
                }
                if ($re['shop'] == '909-LZD') {
                    $data[$re['item_sku']]['909_fbl_stocks'][] = $re['quantity'];
                }
            }
        }

        foreach ($data as $k => $d) {
            $refine[$k]['isis_stocks'] = isset($d['isis_stocks']) ? array_sum($d['isis_stocks']) : 0;
            $refine[$k]['blip_fbl_stocks'] = isset($d['blip_fbl_stocks']) ? array_sum($d['blip_fbl_stocks']) : 0;
            $refine[$k]['avent_fbl_stocks'] = isset($d['avent_fbl_stocks']) ? array_sum($d['avent_fbl_stocks']) : 0;
            $refine[$k]['909_fbl_stocks'] = isset($d['909_fbl_stocks']) ? array_sum($d['909_fbl_stocks']) : 0;
            $refine[$k]['d4u_fbl_stocks'] = isset($d['d4u_fbl_stocks']) ? array_sum($d['blip_fbl_stocks']) : 0;
        }

        return $refine;
    }

    public static function getCurrentStockInfo(){
        $sql = "SELECT 
              TRIM(`isis_sku`) AS sku,
              p.cost,
              (IFNULL(goodQty, 0) + office_stocks) AS isis_stocks,
              IFNULL(`fbl_stock`, 0) AS blip_fbl_stocks,
              IFNULL(`fbl_99_stock`, 0) AS 909_fbl_stocks,
              IFNULL(`fbl_pavent_stock`, 0) AS avent_fbl_stocks,
              IFNULL(`fbl_d4u_stock`, 0) AS d4u_fbl_stocks,
              (
                (IFNULL(goodQty, 0) + office_stocks) + IFNULL(`fbl_stock`, 0) + IFNULL(`fbl_99_stock`, 0) + IFNULL(`fbl_pavent_stock`, 0) + IFNULL(`fbl_d4u_stock`, 0)
              ) AS total_stocks,
              FORMAT((
                (IFNULL(goodQty, 0)+ office_stocks) + IFNULL(`fbl_stock`, 0) + IFNULL(`fbl_99_stock`, 0) + IFNULL(`fbl_pavent_stock`, 0) + IFNULL(`fbl_d4u_stock`, 0)
              ) * p.cost,2) AS total_stocks_value
            FROM
              product_details pd 
              INNER JOIN products p 
                ON p.sku = pd.`isis_sku` 
            WHERE p.`sub_category` != '167' 
            HAVING total_stocks > 0 ";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        return $result;

    }

    // get selling status defination per SKU
    public static function getSellingStatusForSkus()
    {
        $connection = Yii::$app->db;
        $sql = "SELECT pd.`sku_id`,IFNULL(ps.`blip_stock_status`,0) AS blip_stock_status,IFNULL(ps.`f909_stock_status`,0) AS f909_stock_status,IFNULL(ps.`stock_status`,0) AS isis_stock_status FROM `product_stocks` ps 
                INNER JOIN `product_details` pd ON pd.id = ps.`stock_id`
                INNER JOIN products p ON p.id = pd.`sku_id`
                where p.is_active = 1 AND p.sub_category != '167'
                ";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $data = [];
        $ps = new ProductStocks();
        foreach ($result as $re)
        {
            $total = 0;
            if($re['blip_stock_status'] != "0")
                $total = $total+1;
            if($re['f909_stock_status'] != "0")
                $total = $total+1;
            if($re['isis_stock_status'] != "0")
                $total = $total+1;

            $re['blip_stock_status'] = str_replace(' ','_',$re['blip_stock_status']);
            $re['f909_stock_status'] = str_replace(' ','_',$re['f909_stock_status']);
            $re['isis_stock_status'] = str_replace(' ','_',$re['isis_stock_status']);
            $weight = $ps->getStockStatusWeight($re['isis_stock_status']) + $ps->getStockStatusWeight($re['f909_stock_status']) + $ps->getStockStatusWeight($re['blip_stock_status']);
            $weight = Ceil($weight / $total);

            if($weight == 4 || $weight == 5)
                $data[$re['sku_id']]['status'] = 'High';
            if($weight == 2 || $weight == 3)
                $data[$re['sku_id']]['status'] = 'Medium';
            if($weight == 0 || $weight == 1)
                $data[$re['sku_id']]['status'] = 'Slow';

        }

        return $data;
    }

    // get auto margins from table
    public static function getSkuMarginsDefinition()
    {
        $smd = StocksDefinations::find()->all();
        return $smd;
    }
    /* method to short the table header name*/
    public static function getThShorthand( $name ){
        if (strlen($name)>9)
            return substr($name,0,11).'...';
        else
            return $name;
    }

    public static function ChannelPrefixByName( $name ){
        $getprefix = Channels::find()->where(['name'=>$name,'is_active'=>'1'])->select('prefix')->asArray()->all();
        if ( isset($getprefix[0]['prefix']) )
            return $getprefix[0]['prefix'];
        else
            return '';
   }

    public static function ChannelPrefixById( $id ){
         $channel = Channels::findOne(['id'=>$id,'is_active'=>'1']);
         if(isset($channel))
             return $channel->prefix;
         else
             return false;
    }

    //calculation for auto deals
    public static function calculationsAutoDeals($params,$date)
    {
        $values = self::_runQuery($params);

        $values = $values[0];
        // get fbl shipping value from settings
        $fblShip = 0;
        $settings = Settings::find()->where(['name' => 'shipping_cost'])->one();
        $value = json_decode($settings->value, true);
        if($values['low_price'] <= $value['selling_cost'])
            $fblShip = $value['min_sc'] + $value['min_ppc'];
        else
            $fblShip = $value['max_sc'] + $value['max_ppc'];
        //  $values['shipping'] = $values['shipping'];
        $values['low_price'] = $params['price_sell'];
        $values['ao_margins'] = $params['margins'];
        $values['cost'] = HelpUtil::skuCostPrice($values['sku_name'],$params['qty'],$values['cost']);

        $refine[$params['shop_id']][$params['sku_id']] = [
            'low_price' => $values['low_price'],
            'cost' => $values['cost'],
            'rccp_cost' => $values['rccp_cost'],
            'sub_category' => $values['sub_category'],
            'category' => $values['category'],
            'commission' => $values['commission'],
            'shipping' => $values['shipping'],
            'margins' => $values['margins'],
            'subsidy' => $values['subsidy'],
            'base_price_at_zero_margin' => number_format(self::_formulaA($values), 2),
            'base_price_before_subsidy' => number_format(self::_formulaF($values), 2),
            'base_price_after_subsidy' => number_format(self::_formulaB($values), 2),
            'gross_profit' => number_format(self::_formulaC($values), 2),
            'sales_price' =>  number_format(self::_formulaD($values), 2),
            'sales_margins' => number_format(self::_formulaE($values), 2) . '%',
            'low_price_margins' => number_format(self::_formulaI($values, number_format(self::_formulaD($values), 2)), 2) . '%',
            'sales_margins_rm' => number_format(self::_formulaG($values), 2),
        ];

        // $skuList = $refine[$params['shop_id']];

        /*foreach ($skuList as $k => $sk) {
            if (isset($refine[$params['shop_id']][$k])) {
                $p = Pricing::find()->where(['channel_id' => $params['shop_id'], 'sku_id' => $k, 'added_at' => $date])->one();
                if (!$p) {
                    $p = new Pricing();
                }
                $p->sku_id = $k;
                $p->category = $sk['category'];
                $p->sub_category = $sk['sub_category'];
                $p->channel_id = $params['shop_id'];
                $p->low_price = $sk['low_price'];
                $p->base_price_at_zero_margins = $sk['base_price_at_zero_margin'];
                $p->base_price_before_subsidy = $sk['base_price_before_subsidy'];
                $p->base_price_after_subsidy = $sk['base_price_after_subsidy'];
                $p->gross_profit = $sk['gross_profit'];
                $p->sale_price = $sk['sales_price'];
                $p->margins_low_price = $sk['sales_margins'];
                $p->margin_sale_price = str_replace('RM ','',$sk['sales_margins_rm']);
                $p->loss_profit_rm = '0';
                $p->added_at = $date;
                $p->is_update_today = '1';
                $p->save(false);
                $ret = ['sale_price'=>$p->sale_price,'margins'=>$p->margins_low_price,'rm'=>$p->margin_sale_price];
            }
        }*/
        $ret = ['sale_price'=>$refine[$params['shop_id']][$params['sku_id']]['sales_price'],'margins'=>$refine[$params['shop_id']][$params['sku_id']]['sales_margins'],'rm'=>$refine[$params['shop_id']][$params['sku_id']]['sales_margins_rm']];
        return $ret;
    }
    public static function getCurrentStockSum__via_parent_skuId($Sku_id){
        $sql="select SUM(pd.goodQty) AS quantity from product_details pd
              WHERE pd.isis_sku = '".$Sku_id."' OR pd.parent_isis_sku='".$Sku_id."'; ";
        $sum=ProductDetails::findBySql($sql)->asArray()->all()[0]['quantity'];
        return $sum;
    }
    public static function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        //return $result_data[0][$to];
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }
    public static function ToolTip($content,$glyphicon_color='white'){
        return '<span class="mytooltip tooltip-effect-2" style="float: right;">
                    <span class="tooltip-item2">
                        <i class="mdi mdi-information-outline" style="color: '.$glyphicon_color.';float: right;"></i>
                    </span>
                    <span class="tooltip-content4 clearfix">
                        <span class="tooltip-text2">
                            '.$content.'
                        </span>
                        </span>
                                    </span>';
    }

    public static function _getExcludedSkus($Shop_id, $Module)
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

    private function getLzdSkusFor80()
    {
        $list = ['GC9660/36',
            'FC6404/01',
            'HP8233/03',
            'FC8794/01',
            'HD2139/60',
            'HD2145/62',
            'BHD029/03',
            'GC507/60',
            'GC2998BRD',
            'HD2137',
            'HD9630/99',
            'FC8776/01',
            'SCD290/11',
            'HR7759/91',
            'MG7710/15',
            'HD3129/60',
            'BRE632/00',
            'HP8230/03',
            'GC4933BRD',
            'FC6167/01',
            'MG7730/15',
            'MG3730/15',
            'HD4902/60',
            'HP8316/00',
            'AT600/15',
            'MG1100/16',
            'HD9015/30',
            'HD3038/03',
            'GC5039BRD',
            'SCF875/01',
            'HR1600/01'];

        return $list;
    }

    private function getShopeeSkusFor80()
    {
        $list = ['GC507/60',
            'HP8233/03',
            'HP8108/03',
            'S1070/05 ',
            'HD4902/60',
            'HR1600/01',
            'SCF285/01',
            'BHD029/03',
            'GC2998BRD',
            'GC3920BRD',
            'SCD371/00',
            'MG7730/15',
            'GC3929/66',
            'SCD290/11',
            'HR2874P'];

        return $list;
    }

    //GC SKUS -- which ISIS add 'A" for single item
    private static function getGcSkus()
    {
        $list = [
            'GC2998/86A'=>'GC2998/86',
            'GC3920/26A'=>'GC3920/26',
            'GC5030/20A'=>'GC5030/20',
            'GC2678/36A'=>'GC2678/36',
            'GC2145/26A'=>'GC2145/26',
            'GC3929/66A'=>'GC3929/66',
            'GC4933/80A'=>'GC4933/80',
            'GC2670/26A'=>'GC2670/26',
            'GC5039/30A'=>'GC5039/30'
        ];

        return $list;
    }

    // get channels products by channel_id
    public static function getShopeeProducts($sid)
    {


        $sql = "SELECT sku AS itemNo, channel_sku AS sku FROM channels_products WHERE channel_id = '$sid' AND channel_sku != ''";

        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $stockList = [];
        $shopStockList = [];
        $settings = Settings::find()->where(['name' => 'isis_products'])->one();
        if($settings->value != '')
        {
            $products = json_decode($settings->value,true);
            // below line is to take stock value more than 0, Inactive & active case in isis api problem
            $Stock_List = [];
            foreach($products as $p)
            {
                $sku = $p['storageClientSkuNo'];
                $sku = str_replace('�', '', $sku);
                $sku = trim($sku);
                $Stock_List[$sku]['qty'][] = $p['availableQty'];
                $stockList[$sku]['qty'] = max($Stock_List[$sku]['qty']);
            }
            // check which sku has variations and which has not.
            $Items = ShopeeUtil::GetItemsHasVariations($sid);
            $Items = json_decode($Items);

            foreach($result as $ret)
            {
                $excluded_skus = self::_getExcludedSkus($sid,'Stock');
                /*if ( in_array($ret['sku'],$excluded_skus) ){
                    continue;
                }*/
                //continue;

                if ( in_array($ret['itemNo'],$Items) )
                    continue;

                if(isset($stockList[$ret['sku']]))
                {
                    $stock = ((int)$stockList[$ret['sku']]['qty'] < 0 ) ? 0 : (int)$stockList[$ret['sku']]['qty'];
                    if ( in_array($ret['sku'],$excluded_skus) ){
                        $stock = 0;
                    }
                    // 80% logic
                    /*if(in_array($ret['sku'],self::getShopeeSkusFor80()))
                    {
                        if ($stock != 0)
                            $stock = floor($stock * 0.8);
                        $stock = ((int)$stock <= 0) ? 0 : (int)$stock;
                    }*/
                    $Skip_skus = [];
                    if ( in_array($ret['sku'],$Skip_skus) )
                        continue;


                    $shopStockList[] = ['item_id' =>  (int)$ret['itemNo'] , 'stock' => (int)$stock];

                } else {
                    foreach(self::getGcSkus() as $k=>$v)
                    {
                        if (isset($stockList[$k]['qty'])){
                            if($ret['sku'] == $v)
                            {
                                $stock = ( (int)$stockList[$k]['qty'] < 0 ) ? 0 : (int)$stockList[$k]['qty'];
                                if ($stock != 0)
                                    $stock = floor($stock * 0.8);
                                $stock = ((int)$stock <= 0) ? 0 : (int)$stock;
                                $shopStockList[] = ['item_id' =>  (int)$ret['itemNo'] , 'stock' => (int)$stock];
                            }
                        }

                    }
                }
            }
            $chunk = ceil(count($shopStockList) / 50);
            return self::_partition($shopStockList, $chunk);
        }
    }
    public static function UnSyncSkus($sid){

        if ( $sid == 1 ){
            $exSKUS = self::_getExcludedSkus($sid, 'Stock');
            $exSKUS = array_merge($exSKUS, ['KABUNDLE1',
                'KABUNDLE2',
                'KABUNDLE3',
                'KABUNDLE4',
                'KABUNDLE5',
                'KABUNDLE6',
                'KABUNDLE7',
                'KABUNDLE8',
                'PCBUNDLE1',
                'PCBUNDLE2',
                'PCBUNDLE3',
                'PCBUNDLE4',
                'PCBUNDLE5',
                'MXBUNDLE1',
                'MXBUNDLE2',
                'MXBUNDLE3',
                'MXBUNDLE4',
                'MXBUNDLE5',
                'MXBUNDLE6',
                'MXBUNDLE7',
                'PCBUNDLE6',
                'PCBUNDLE7',
                'OHBUNDLE1', 'MY7045X125IRBOARD', 'MY-70CUTLERYSET', 'MY-70STEAMIRBOARDV2', 'MY-OHCSPARKLYTOY', 'MY-70COOLER10QT']);
        }
        elseif ( $sid == 15 ){
            $exSKUS = self::_getExcludedSkus($sid, 'Stock');
        }
        return $exSKUS;
    }


    private static function _refineAvailable_Quantity($data){
        $_refine = [];
        foreach ( $data as $value ){
            $_refine[$value['SellerSku']]['quantity'] = $value['quantity'];
            $_refine[$value['SellerSku']]['available'] = $value['Available'];
        }
        return $_refine;
    }
    private static function _partition(Array $list, $p) {
        $listlen = count($list);
        $partlen = floor($listlen / $p);
        $partrem = $listlen % $p;
        $partition = array();
        $mark = 0;
        for($px = 0; $px < $p; $px ++) {
            $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
            $partition[$px] = array_slice($list, $mark, $incr);
            $mark += $incr;
        }
        return $partition;
    }
    public function random_color_part() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    public static function random_color($index) {
        $Colors = [
            'FFD480','80BFFF'
        ];
        //$rand_Color=array_rand($Colors,1);
        //print_r($rand_Color);die;
        return $Colors[$index];
    }

    public static function generateOrderCal($stocks = [])
    {
        $threshold = '0';
        $settings = Settings::find()->where(['name' => 'stocks_high_threshold_alert'])->one();
        if ($settings) {
            $threshold = $settings->value;
        }
        $refine = [];
        foreach ($stocks as $k => $sk) {
            /* if ($sk['isis_stks'] < $sk['isis_threshold'] && $sk['isis_stks'] > $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                 $sk['threshold'] = $sk['isis_threshold'];
                 $sk['threshold_org'] = $sk['isis_threshold_org'];
                 $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_org'] . ' +' . $sk['dealNo'] : '';
                 $refine['a'][$k] = $sk;
             } else if ($sk['isis_stks'] < $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                 $sk['threshold'] = $sk['isis_threshold_critical'];
                 $sk['threshold_org'] = $sk['isis_threshold_critical_org'];
                 $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                 $refine['a'][$k] = $sk;
             } else if ($sk['isis_stks'] <= $threshold && isset($sk['isis_order_stocks']) && $sk['isis_order_stocks'] != '0') {
                 $sk['threshold_org'] = $threshold;
                 $sk['threshold'] = $threshold;
                 $refine['a'][$k] = $sk;
                 $count = HelpUtil::checkSameSKUQTY($sk['sku_id']);
                 if (count($count) > 0)
                     unset($refine['a'][$k]);
             } else {
                 $sk['threshold'] = $sk['isis_threshold_critical'];
                 $sk['threshold_org'] = $sk['isis_threshold_critical_org'];
                 $sk['dealNo'] = ($sk['dealNo']) ? $sk['isis_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';
                 $refine['a'][$k] = $sk;
             }*/
            $sk['threshold'] = $sk['isis_threshold'];
            $sk['threshold_org'] = $sk['isis_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoISIS']) ?  $sk['dealNoISIS'] : '0';
            $refine['a'][$k] = $sk;

            /*if ($sk['blip_stks'] < $sk['fbl_blip_threshold'] && $sk['blip_stks'] > $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                $sk['threshold'] = $sk['fbl_blip_threshold'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_org'] . ' +' . $sk['dealNo'] : '';
                $refine['b'][$k] = $sk;
            } else if ($sk['blip_stks'] < $sk['fbl_blip_threshold_critical'] && $sk['fbl_order_stocks'] != '0') {
                $sk['threshold'] = $sk['fbl_blip_threshold_critical'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_critical_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';

                $refine['b'][$k] = $sk;
            } else if ($sk['blip_stks'] <= $threshold && isset($sk['fbl_order_stocks']) && $sk['for'] == '2' && $sk['fbl_order_stocks'] != '0') {
                $sk['threshold'] = $threshold;
                $sk['threshold_org'] = $threshold;
                $refine['b'][$k] = $sk;
                $count = HelpUtil::checkSameSKUQTY($sk['sku_id']);
                if (count($count) > 0)
                    unset($refine['b'][$k]);
            } else {
                $sk['threshold'] = $sk['fbl_blip_threshold_critical'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_critical_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';

                $refine['b'][$k] = $sk;
            }*/
            $sk['threshold'] = $sk['fbl_blip_threshold'];
            $sk['threshold_org'] = $sk['fbl_blip_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoFBLZD']) ? $sk['dealNoFBLZD'] : '0';
            $refine['b'][$k] = $sk;
            /*if ($sk['909_stks'] < $sk['fbl_909_threshold'] && $sk['909_stks'] > $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                $sk['threshold'] = $sk['fbl_909_threshold'];
                $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_org'] . ' +' . $sk['dealNo'] : '';

                $refine['c'][$k] = $sk;
            } else if ($sk['909_stks'] < $sk['fbl_909_threshold_critical'] && $sk['fbl909_order_stocks'] != '0') {
                $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                $sk['threshold_org'] = $sk['fbl_909_threshold_critical_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_909_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';

                $refine['c'][$k] = $sk;
            } else if ($sk['909_stks'] <= $threshold && isset($sk['fbl909_order_stocks']) && $sk['for'] == '3' && $sk['fbl909_order_stocks'] != 0) {
                $sk['threshold'] = $threshold;
                $refine['c'][$k] = $sk;
                $count = HelpUtil::checkSameSKUQTY($sk['sku_id']);
                if (count($count) > 0)
                    unset($refine['c'][$k]);
            } else {
                $sk['threshold'] = $sk['fbl_909_threshold_critical'];
                $sk['threshold_org'] = $sk['fbl_blip_threshold_critical_org'];
                $sk['dealNo'] = ($sk['dealNo']) ? $sk['fbl_blip_threshold_critical_org'] . ' +' . $sk['dealNo'] : '';

                $refine['c'][$k] = $sk;
            }*/
            $sk['threshold'] = $sk['fbl_909_threshold'];
            $sk['threshold_org'] = $sk['fbl_909_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoFBL909']) ? $sk['dealNoFBL909'] : '0';
            $refine['c'][$k] = $sk;

            $sk['threshold'] = $sk['fbl_avent_threshold'];
            $sk['threshold_org'] = $sk['fbl_avent_threshold_org'];
            $sk['dealNo'] = ($sk['dealNoFBLZD']) ? $sk['dealNoFBLZD'] : '0';
            $refine['d'][$k] = $sk;

        }

        return $refine;
    }
    public static function getSkuIdByStockId($stock_id){
        $sku_id = self::exchange_values('id','sku_id',$stock_id,'product_details');
        return $sku_id;
    }
    public static function FocItemBySku($stock_id){

        $sql="SELECT * FROM products_relations pr
                                            INNER JOIN product_relations_skus prs ON
                                            prs.bundle_id = pr.id
                                            WHERE pr.relation_type = 'FOC' AND prs.main_sku_id = ".self::getSkuIdByStockId($stock_id)." AND pr.end_at >= '".date('Y-m-d')."'
                                            AND pr.is_active = 1";
        //echo $sql;die;
        $focSkus = ProductsRelations::findBySql($sql)->asArray()->all();
        if ( empty($focSkus) ){
            return 0;
        }else{
            return 1;
        }
    }

    public static function number_format_short($n, $precision = 2)
    {
        $n = str_replace(',','',$n);
        $n = ($n) ? (float)$n : 0;
        try {
            if ($n < 900) {
                // 0 - 900
                $n_format = number_format($n, $precision);
                $suffix = '';
            } else if ($n < 900000) {
                // 0.9k-850k
                $n_format = number_format($n / 1000, $precision);
                $suffix = 'K';
            } else if ($n < 900000000) {
                // 0.9m-850m
                $n_format = number_format($n / 1000000, $precision);
                $suffix = 'M';
            } else if ($n < 900000000000) {
                // 0.9b-850b
                $n_format = number_format($n / 1000000000, $precision);
                $suffix = 'B';
            } else {
                // 0.9t+
                $n_format = number_format($n / 1000000000000, $precision);
                $suffix = 'T';
            }
            if ($precision > 0) {
                $dotzero = '.' . str_repeat('0', $precision);
                $n_format = str_replace($dotzero, '', $n_format);
            }
            return $n_format . $suffix;
        } catch(yii\base\ErrorException $ex)
        {

        }
    }
    /*
     * We have many screens where we do have statuses, like shipped, canceled, partial shipped ETC. So we show all those statuses in colors
     * Below method is to get those class, All statuses are in the array we just need to give status to this method then this will return the badge.
     * */
    public static function getBadgeClass($Status){
        $Statuses = [
            'Draft' => 'badge-draft',
            'Partial Shipped'  => 'badge-success',
            'Shipped'  => 'badge-success',
            'Publish'  => 'badge-success',
            'Completed' =>'badge-success',
            'Success'  => 'badge-success',
            'shipped'  => 'badge-success',
            'delivered'  => 'badge-success',
            'shipped,delivered'  => 'badge-success',
            'completed' => 'badge-success',
            'Shipping in Progress' => 'badge-warning',
            'Preparing for Shipment' => 'badge-warning',
            'Pending' => 'badge-warning',
            'Processed' => 'badge-warning',
            'to_confirm_receive' => 'badge-warning',
            'complete' => 'badge-success',
            'unpaid' => 'badge-danger',
            'cancelled' => 'badge-danger',
            'invalid' => 'badge-danger',
            'to_return' => 'badge-danger',
            'in_cancel' => 'badge-danger',
            'refund_paid' => 'badge-danger',
            'closed' => 'badge-danger',
            'seller_dispute' => 'badge-danger',
            'returned' => 'badge-danger',
            'reversed' => 'badge-danger',
            'missing orders' => 'badge-danger',
            'canceled' => 'badge-danger',
            'Fail' => 'badge-danger',
            'Deleted' => 'badge-dark',
            'refunded' => 'badge-danger',
            'canceled by customer' => 'badge-danger',
            'CANCELLED' => 'badge-danger',
            'TO_RETURN' => 'badge-danger',
            'delivery failed' => 'badge-danger',
            'failed' => 'badge-danger',
            'Failed' => 'badge-danger',
            'expired' => 'badge-danger',
            'ready_to_ship' => 'badge-warning',
            'retry_ship' => 'badge-warning',
            'exchange' => 'badge-warning',
            'pending' => 'badge-warning',
            'in transit' => 'badge-warning',
            'Activated' => 'badge-success',
            'DeActivated' => 'badge-danger',
            'Activating' => 'badge-warning',
            'DeActivating' => 'badge-warning',
            'Partially Publish'  => 'badge-secondary',
            'Partially Fail'  => 'badge-danger',

        ];
        if (isset($Statuses[$Status]))
            return $Statuses[$Status];
        else
            return '';

    }
    public static function getAllDatesBetweenTwoDates($from,$to){
        $begin = new \DateTime($from);
        $end = new \DateTime($to.' +1 day');

        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($begin, $interval, $end);
        $Dates = [];
        foreach ($period as $dt) {
            $Dates[] = $dt->format("Y-m-d");
        }
        return $Dates;
    }

    public static function getAllMonthsBetweenTwoDates($date1, $date2) {
        $time1  = strtotime($date1);
        $time2  = strtotime($date2);
        $my     = date('mY', $time2);

        $months = array(date('Y-m', $time1));

        while($time1 < $time2) {
            $time1 = strtotime(date('Y-m-d', $time1).' +1 month');
            if(date('mY', $time1) != $my && ($time1 < $time2))
                $months[] = date('Y-m', $time1);
        }

        $months[] = date('Y-m', $time2);
        return $months;
    }
    public static function sortArrDateIndex($arr){
        $new_array = [];
        //$timestamp_arr=[];
        foreach ( $arr as $key=>$value ){
            $timestamp = strtotime($key);
            $new_array[$timestamp] = $value;
        }

        $sort_by_timestamp = self::ksortTimestamp($new_array);
        //echo '<pre>';print_r();//die;
        //echo '<pre>';print_r(ksort($new_array));//die;
        //$sorts = sort($timestamp_arr);
        //    echo '<pre>';print_r($sorts);die;
        //echo '<pre>';print_r($sort);die;
        $final = [];
        foreach ( $sort_by_timestamp as $key=>$value ){
            $final[date('Y-m-d',$key)]=$value;
        }
        //echo '<pre>';print_r($final);die;
        return $final;
    }
    public static function ksortTimestamp($array){
        ksort($array);
        return $array;
    }
    public static function GetCronLogQuery($start_datatime,$end_datetime,$job_link,$limit,$running){

        if ($running==0)
            $run_cond="";
        elseif ($running==1)
            $run_cond=" AND cjl.`end_datetime` IS NULL ";

        $sql = "SELECT * FROM `cron_jobs_log` cjl
                WHERE cjl.`job_link` LIKE '%".$job_link."%' AND cjl.`start_datetime` BETWEEN '".$start_datatime."' AND '".$end_datetime."'
                $run_cond
                ORDER BY cjl.`id` DESC LIMIT ".$limit;
        $Log = CronJobsLog::findBySql($sql)->asArray()->all();
        return $Log;
    }
    public static function GetLzdProducts($channel_id){

        $ch = Channels::findOne(['id' => $channel_id,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/products/get';
        $customParams['params']['limit'] = '20';
        $response = self::_callLzdRequestMethod($customParams);
        $response = json_decode($response, true);
        $Skus = [];

        for ( $i=20; $i<=$response['data']['total_products']; $i+=20 ){
            $customParams['params']['offset'] = $i;
            $json_decode = json_decode(self::_callLzdRequestMethod($customParams));

            foreach ($json_decode->data->products as $val){
                foreach ($val->skus as $val2){
                    $Skus[] = $val2->SellerSku;
                }
            }
        }

        return $Skus;
    }
    public static function GetFocSkus(){
        $sql = "SELECT sku FROM products WHERE is_foc = 1";
        $GetFocProducts = Products::findBySql($sql)->asArray()->all();
        $foc = [];
        foreach ( $GetFocProducts as $val ){
            $foc[] = $val['sku'];
        }
        return $foc;
    }
    public static function GetOrderItems($order_id){
        $OrderId = self::exchange_values('order_id','id',$order_id,'orders');
        $GetOrderItems = OrderItems::find()->where(['order_id'=>$OrderId])->asArray()->all();
        return $GetOrderItems;
    }
    public static function PriceUpdateProducts($channel_id,$updated_recently=false){
       // echo date_default_timezone_get(); die();
        $query = new Query();
        $where['channels_products.channel_id']=$channel_id;
        if($updated_recently){  // have to pick those which are recently updated
            $difference=strtotime('-18 HOURS');
            $after_date=date('Y-m-d H:i:s',$difference);
            $where['from_unixtime(products.updated_at)>']=$after_date;
            //die('come');
        }

        $query->select(['products.id as product_id','products.sku as sku', 'products.cost as cost_price', 'products.rccp as rccp',
                        'products.promo_price as promo_price', 'channels_products.sku as channels_products_sku', 'channels_products.variation_id as variation_id',
                         'channels_products.channel_sku as channel_sku','channels_products.channel_id as channel_id'])
            ->from('products')
            ->join(	'INNER JOIN',
                'channels_products',
                'products.id =channels_products.product_id'
            )->where($where)->andWhere(['channels_products.deleted'=>'0']);
        $command = $query->createCommand();
        $Products = $command->queryAll();
        return $Products;

    }

    //depricated
   /* public static function StockUpdateProducts($channel_id){

        $connection = Yii::$app->db;
        $sql = "SELECT p.id AS `product_id`, p.`sku` AS `sku`, p.`cost` AS `cost_price`, p.`rccp` AS `rccp`,
                 p.`promo_price` AS `promo_price`, cp.`sku` AS `channels_products_sku`,
                 cp.`variation_id` AS `variation_id`, cp.`channel_sku` AS `channel_sku`, 
                 cp.`channel_id` AS `channel_id`, cp.`id` AS `channels_product_pk_id`, wsl.available AS available_stocks
                FROM products `p`
                INNER JOIN channels_products cp ON p.id =cp.product_id
                INNER JOIN warehouse_stock_list wsl ON wsl.sku = p.sku
                INNER JOIN warehouse_channels whc ON whc.channel_id = cp.channel_id
                INNER JOIN warehouses w ON w.id = wsl.warehouse_id
                WHERE cp.`channel_id`='".$channel_id."' AND whc.is_active = 1 AND w.is_active =1";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    } */
    public static function GetProductIdByUPC($upc){
        if ($upc!=''){
            $sql = "SELECT p.*
                    FROM general_reference_keys grk
                    INNER JOIN channels_products cp ON
                    cp.id = grk.table_pk
                    INNER JOIN products p ON
                    p.id = cp.product_id
                    WHERE grk.table_name = 'channels_products' AND grk.value = '$upc' AND p.is_active = 1
                    ;";
            $connection = Yii::$app->db;
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            return $result;
        }else{
            return [];
        }

    }

    //////////////////////////////////function to get sku and stock to update///////////
    public static function getStock($channel,$updated_before_minutes=null)
    {
        $channel_id=$channel['id'];
        $UnsyncSkus = HelpUtil::_getExcludedSkus($channel_id,'Stock');
        $where = '';
        $extra_join='';
        if ( !empty($UnsyncSkus) ){
            $result = "'" . implode ( "', '", $UnsyncSkus ) . "'";
            $where .= " AND ws.sku NOT IN ($result)";
        }
        $where .= " AND w.warehouse NOT IN ('lazada-fbl','amazon-fba')"; // we do not take this stock because it is fulfilled by marketplaces

        if($updated_before_minutes){ // pick stock updated before x minutes
            $time_slot="-".$updated_before_minutes;
           // $difference=strtotime('-65 minutes');
            $difference=strtotime($time_slot .' minutes');
            $after_date=date('Y-m-d H:i:s',$difference);
            $where .=" AND ws.sku IN(SELECT  sku FROM warehouse_stock_list WHERE warehouse_stock_list.updated_at >= '".$after_date."' GROUP BY warehouse_stock_list.sku)";
            ///above query will check if stock change for particular sku even in 1 warehouse then take stock of that sku from all warehouse to sum

        }
        /*if($channel['marketplace']=='magento'){  // temporary for few days
            $where .=" AND `p`.`brand` IN('nike')";
            $extra_join .=" INNER JOIN `products` p ON p.sku=ws.sku "; /// for few days for spl
        }*/

        $query="SELECT cp.id as channel_product_pk_id ,cp.sku AS sku_id,cp.variation_id AS variation_id,`cp`.`channel_sku` AS sku, `cp`.`stock_update_percent`,
                 floor(SUM(CASE WHEN wc.stock_upload_limit_applies=1 THEN (`ws`.`available`) ELSE 0 END)) AS stock,
 		         SUM(CASE WHEN wc.stock_upload_limit_applies=0 THEN (`ws`.`available`) ELSE 0 END) AS stock_without_limit,
                 `cp`.`fulfilled_by`,cp.`id` AS `channels_product_pk_id`
                FROM 
                    warehouse_stock_list ws
                INNER JOIN 
                    channels_products cp
                ON
                    ws.sku = cp.channel_sku
                INNER JOIN 
                    warehouse_channels wc
                ON
                    ws.warehouse_id = wc.warehouse_id AND wc.channel_id=cp.channel_id
                INNER JOIN 
                    warehouses w ON w.id = ws.warehouse_id
                 $extra_join   
                WHERE 
                    wc.channel_id = $channel_id AND cp.channel_id = $channel_id 
                    AND wc.is_active = 1  AND w.is_active =1 
                     $where
                 GROUP BY cp.channel_sku
                 ORDER BY ws.updated_at desc";
      //  echo $query; die();
        $results = Yii::$app->db->createCommand($query)->queryall();
        $skus=[];
        foreach ( $results as $val ){
            if ($val['stock']<0){
                $val['stock']=0;
            }
            $skus[]=$val;
        }
        return $skus;
    }

    /***
     * calculate stock percent limit at shop
    */

    public static function stock_percent_to_update($channel,$stocklist)
    {
        $percent_value=($channel['stock_update_percent'] && $channel['stock_update_percent'] > 0) ? $channel['stock_update_percent']:100;
        if($channel && $stocklist)
        {
            foreach($stocklist as &$stock){
                if(!$stock['stock'] || $stock['stock'] <= 0)
                {
                    $stock['stock']=$stock['stock'] ? $stock['stock']:0;
                    $stock['stock']=($stock['stock'] + $stock['stock_without_limit']);
                    continue;
                }
                if($stock['stock_update_percent'] && $stock['stock_update_percent'] > 0){  // first priority of percent from channel individual product
                    $stock['stock']=floor(($stock['stock_update_percent']/100) * $stock['stock']);
                    /////for compaign of spl
                       // if(strtolower($channel['name'])=="spl" && $stock['stock']<=3)
                       //     $stock['stock']=0;
                    ///
                    $stock['stock']=($stock['stock'] + $stock['stock_without_limit']);
                }
                 else{
                     $stock['stock']=floor(($percent_value/100) * $stock['stock']); //  channel overall stock percent
                     /////for compaign of spl
                   //  if(strtolower($channel['name'])=="spl" && $stock['stock']<=3)
                    //     $stock['stock']=0;
                     ///
                     $stock['stock']=($stock['stock'] + $stock['stock_without_limit']);
                 }

            }
        }
        return $stocklist;
    }

    /******
     * @param $stocklist
     * @param $full_stock_list
     * this function is used to merge stock of inclusive stock limit  plus stok exclusive limit
     */
    public function merge_stock_to_upload($stocklist,$full_stock_list)
    {
        foreach($full_stock_list as $index=>$value)
        {
            $found= array_search($value['sku'],array_column($stocklist, 'sku'));
            if($found!==false)
            {
                unset($full_stock_list[$index]);
                $stocklist[$found]['stock'] +=$value['stock'];
            }

        }
        return array_merge($stocklist,$full_stock_list);
       // return $merged;
        //echo "<pre>";
        //print_r($full_stock_list); die();
        /*$unique = array_unique($merged, SORT_REGULAR);

// then, get the data which duplicate with

        $diffCellUniq = array_diff_key($merged, $unique);

// so print the result
        echo "<pre>";
        print_r($diffCellUniq);
        die();*/
    }

    public static function SkuEmptyOnShopLog($channel_id,$msg){

        $AddLog = new ChannelSkuMissing();
        $AddLog->channel_id = $channel_id;
        $AddLog->details = $msg;
        $AddLog->added_at = date('Y-m-d H:i:s');
        $AddLog->updated_at = date('Y-m-d H:i:s');
        $AddLog->save();

    }

    public static function GetSkuFilterDropdown(){

        $ProductSkus="SELECT whsl.sku AS `key` , whsl.sku as `value`
                        FROM warehouse_stock_list whsl ";
        $ProductSkus = Products::findBySql($ProductSkus)->asArray()->all();
        return $ProductSkus;
    }
    public static function GetWarehouseFilterDropdown(){

        $Warehouse="SELECT w.name AS `key` , w.name as `value`
                        FROM warehouses w ";
        $Warehouse = Products::findBySql($Warehouse)->asArray()->all();
        return $Warehouse;
    }
    public static function WarehousesStocks(){

        $data = [];
        $w = HelpUtil::getWarehouseDetail();
        $warehouses = Warehouses::findBySql("SELECT * FROM warehouses w WHERE w.is_active AND `id` IN ($w)")->asArray()->all();

        $connection = Yii::$app->db;
        $query = "SELECT w.name AS warehouse_name,SUM(p.cost*wsl.available) AS total_inventory_stocks
                    FROM products p
                    INNER JOIN warehouse_stock_list wsl ON wsl.sku = p.sku
                    INNER JOIN warehouses w ON w.id = wsl.warehouse_id
                    WHERE wsl.warehouse_id IN ($w)
                    GROUP BY warehouse_id;";
        $command = $connection->createCommand($query);
        $result = $command->queryAll();
        $data['warehouse_stocks'] = $result;
        $data['Total_inventory_amount']=0;
        foreach ( $data['warehouse_stocks'] as $value ){
            $data['Total_inventory_amount'] += $value['total_inventory_stocks'];
        }
        return $data;

    }
    public static function get_utc_time($date,$time_zone=null)
    {

        date_default_timezone_set(isset($time_zone) ? $time_zone : Yii::$app->params['current_time_zone']);
        $raw=$date;
        $time_stamp=strtotime($raw);
        //date_default_timezone_set('UTC');
        return gmdate('Y-m-d H:i:s',$time_stamp);
    }
    public static function get_utc_time_walmart_deal($date,$time_zone=null,$secondsStatus='', $seconds='')
    {

        date_default_timezone_set(isset($time_zone) ? $time_zone : Yii::$app->params['current_time_zone']);
        $raw=$date;
        $time_stamp=strtotime($raw);
        if ($secondsStatus=='ADD' && $seconds!=''){
            $time_stamp+=$seconds;
        }
        //$time_stamp
       // date_default_timezone_set('UTC');
        return gmdate('Y-m-d\TH:i:s.762\Z',$time_stamp);
    }
    public static function GetProductsForWarehouseSync($warehouse_id){

        $sql = "SELECT p.sku as Sku,p.NAME as Description,p.ean AS Code 
                FROM products p
                INNER JOIN channels_products cp ON p.id = cp.product_id
                WHERE p.ean != 0 and p.is_active = 1
                AND cp.channel_id IN (SELECT channel_id FROM warehouse_channels wc WHERE warehouse_id = $warehouse_id)
                GROUP BY p.sku;";

        $getProducts = Products::findBySql($sql)->asArray()->all();
        return $getProducts;
    }
    public static function WarehouseGetChannels(){

        $sql = "SELECT c.* FROM `channels` c
                WHERE 
                        c.`id`  AND c.`is_active` = 1;";
        $channels = Channels::findBySql($sql)->asArray()->all();
        return $channels;

    }
    public static function getCurrentStockInTransitPO()
    {
        // psp.po_warehouse IS not in 8 because warehouse 8 is lazada outright and we do not take it into consderation in stock in transit.

        $sql = "SELECT psp.`id`,
              pod.`sku_id`,
              pod.`sku`,
              SUM( pod.`cost_price` * pod.`final_order_qty`) AS total
              FROM `product_stocks_po` psp
              INNER JOIN po_details pod ON
              psp.`id` = pod.`po_id`
              WHERE psp.`po_status` = 'Pending'
              GROUP BY pod.`sku_id`;";
        $result = self::QueryAndSortWithIndex($sql, 'sku_id');
        return $result;
    }
    public static function CreateCategory($name,$parent){
        $create = new Category();
        $create->name = $name;
        $create->main_category_id=$parent;
        $create->is_active = 1;
        $create->is_main = 0;
        $create->save();
        if (!$create->errors){
            return $create['id'];
        }else{
            $cat = Category::find()->where(['name'=>'Unknown'])->one();
            return $cat->id;
        }

    }
    public static function GetGeneralReferenceKey($cid, $tname, $tpk, $key){

        $get = GeneralReferenceKeys::find()->where(['channel_id'=>$cid,'table_name'=>$tname,'table_pk'=>$tpk,'key'=>$key])->one();
        return $get->value;
    }

    //////////////////////////Start Delete Logs Functions/////////////////////////////////////////////////
    public static function DeleteISISLogs(){
        $log_date = date('Y-m-d', strtotime("-7 days"));
        $model = Yii::$app->db->createCommand('DELETE FROM isis_logs WHERE log_date < :log_date');
        $model->bindParam(':log_date', $log_date);
        $model->execute();
    }
    public static function DeleteApiResponseLogs(){
        $log_date = date('Y-m-d', strtotime("-7 days"));
        StockPriceResponseApi::deleteAll('create_at < :created_at', [':created_at' => $log_date . '%']);
    }
    public static function DeleteCronJobsLogs(){
        $log_date = date('Y-m-d', strtotime("-7 days"));
        $model = Yii::$app->db->createCommand('DELETE FROM cron_jobs_log WHERE start_datetime < :log_date');
        $model->bindParam(':log_date', $log_date);
        $model->execute();
    }
    public static function DeleteWareHouseStocksLogs(){
        $log_date = date('Y-m-d', strtotime("-7 days"));
        $model = Yii::$app->db->createCommand('DELETE FROM warehouse_stock_log WHERE sku IS NULL AND added_by = 0 AND added_at  < :log_date');
        $model->bindParam(':log_date', $log_date);
        $model->execute();
    }
    public static function OptimizeLogTables(){
        $model = Yii::$app->db->createCommand('OPTIMIZE TABLE stock_price_response_api,warehouse_stock_log,cron_jobs_log');
        $model->execute();
    }
    ///////////////////////////END Delete Logs Functions/////////////////////////////////////////////////

    public static function getControllersAndActions() //get list of controllers and actions
    {
        $path = Yii::$app->controllerPath;
        $controllerlist = [];
        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && substr($file, strrpos($file, '.') - 10) == 'Controller.php') {
                    $controllerlist[] = $file;
                }
            }
            closedir($handle);
        }
        asort($controllerlist);
        $fulllist = [];
        foreach ($controllerlist as $controller):
            $handle = fopen('../controllers/' . $controller, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (preg_match('/public function action(.*?)\(/', $line, $display)):
                        if (strlen($display[1]) > 2):
                            // $fulllist[substr($controller, 0, -4)][] = $display[1];//strtolower($display[1]);
                            $controller=strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', str_replace('Controller','',$controller)));
                            $fulllist[substr($controller, 0, -4)][] =strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $display[1]));//strtolower($display[1]);
                        endif;
                    endif;
                }
            }
            fclose($handle);
        endforeach;
        return $fulllist;

    }
    public static function getCountryStateShortCode($name){
        // search with name
        $SearchByName = \common\models\CountriesAndStates::find()->where(['name'=>$name])->asArray()->all();
        if ( $SearchByName ){
            return $SearchByName[0]['shortcode'];
        }else{
            return $name;
        }

    }
    public static function AccessAllowed($ControllerName, $MethodName){

        $role_id = Yii::$app->user->identity->role_id;
        $ShipNowModuleId = \common\models\Modules::find()->where(['controller_id'=>'/'.$ControllerName,'update_id'=>'/'.$MethodName])->one();
        if ($ShipNowModuleId){
            $GetShipNowUpdateBit = \common\models\Permissions::find()->where(['module_id'=>$ShipNowModuleId->id,'role_id'=>$role_id])->one();

            if ( isset($GetShipNowUpdateBit->update) && $GetShipNowUpdateBit->update==1 ){
                $AllowedUpdateUrl = $ShipNowModuleId->allowed_update_ids;
                $AllowedUpdateUrl = json_decode($AllowedUpdateUrl);
                if ( in_array($ControllerName.'/'.$MethodName, $AllowedUpdateUrl) ){
                    $Permission = 1;
                }else{
                    $Permission = 0;
                }
                //echo '<pre>';print_r($AllowedUpdateUrl);die;
            }else{
                $Permission = 0;
            }
        }else{
            $Permission = 0;
        }

        return $Permission;
    }

    public static function make_child_parent_tree(array $elements, $parentId = 0)
    {
        $result = array();

        foreach ($elements as $element) {

            if ($element['parent_id'] == $parentId) {
                $children = self::make_child_parent_tree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $result[] = $element;
            }
        }
        // print_r($result); die();
        return $result;
    }

    public static function dropdown_3_level($elements)
    {
        $options=array();
        foreach ($elements as $element)
        {
            $options[]=array('key'=>$element['id'],'value'=>$element['name'],'space'=>'');
            if(isset($element['children']))
            {
                foreach ($element['children'] as $element1)
                {
                    $options[]=array('key'=>$element1['id'],'value'=>$element1['name'],'space'=>' -- ');
                    if(isset($element1['children']))
                    {
                        foreach ($element1['children'] as $element2)
                        {
                            $options[]=array('key'=>$element2['id'],'value'=>$element2['name'],'space'=>'  &nbsp;&nbsp;--- ');
                        }
                    }
                }
            }
        }
        return $options;
    }
    public static function GetDistributorList(){

        $Sql = "SELECT u.id as user_id, u.full_name
                FROM user u INNER JOIN user_roles ur ON ur.id=u.role_id WHERE ur.name= 'Distributor' AND u.status=10 AND ur.status=1";
        $results = User::findBySql($Sql)->asArray()->all();
        return $results;

    }
    public static function GetRole(){
        $roleId = Yii::$app->user->identity->role_id;
        $Role = UserRoles::find()->where(['id'=>$roleId])->one();
        return strtolower($Role->name);
    }
    public static function GetChannelsWarehouses(){
        $Sql = "SELECT wc.warehouse_id,w.name FROM warehouse_channels wc
                INNER JOIN channels c ON
                c.id = wc.channel_id
                INNER JOIN warehouses w ON
                w.id = wc.warehouse_id
                GROUP BY w.id;";
        $results = WarehouseChannels::findBySql($Sql)->asArray()->all();
        return $results;
    }
    public static function ChannelsWarehouses(){
        $results = self::GetChannelsWarehouses();
        $warehouses = [];
        $warehouses['']='Select Default Warehouse';
        foreach ($results as $value){
            $warehouses[$value['warehouse_id']] = $value['name'];
        }

        return $warehouses;
    }
    public static function GetShippingLabelsSalesScreen($orders){
        $itemIds=[];
        foreach ($orders as $orderId=>$detail){
            foreach( $orders[$orderId]['items'] as $itemDetail ){
                $itemIds[]=$itemDetail['item_id_pk'];
            }
        }
        $shippingLabel = self::GetShippingLabels($itemIds);
        return $shippingLabel;
    }
    public static function GetShippingLabels($ItemIds){
        $labels=[];
        if ( $ItemIds ){
            $Sql = "SELECT id,shipping_label from order_items where id IN (".implode(',',$ItemIds).") AND courier_id IS NOT NULL";
            $getLabels = OrderItems::findBySql($Sql)->asArray()->all();
            foreach( $getLabels as $value ){
                $labels[$value['id']]=$value['shipping_label'];
            }
        }

        return $labels;
    }
    public static function GetTableColumns($tableName){
        $sql = "SHOW COLUMNS FROM $tableName;";
        $describeTable = Yii::$app->db->createCommand($sql)->queryAll();
        $columns=[];
        foreach( $describeTable as $values ){
            $columns[] = $values['Field'];
        }
        return $columns;
    }
    public static function ApplyMarketplaceCharges($channelId, $skuId, $priceSell=0, $subsidy=0, $fbl, $cost=''){


        $productDetail = Products::find()->where(['id'=>$skuId])->one();
        $getisis_charges= Settings::find()->where(['name'=>'shipping_cost'])->one();
        $getisis_charges = json_decode($getisis_charges->value,true);

        //self::debug($getisis_charges);

        $getCharges = DealsUtil::catChildParent($channelId,$productDetail->sub_category);
        if ( $getCharges==NULL ){
            $channelCharges = [
                'shipping_fee'=>0,
                'commission'=>0,
                'pg_commission'=>0,
            ];
        }else{
            $channelCharges = $getCharges;
        }

        if ($cost==''){
            $costPrice=$productDetail->cost;
        }else{
            $costPrice=$cost;
        }

        $salePrice=$priceSell;


        if ($subsidy!=0){
            $salePrice = (($salePrice * $subsidy) / 100) + $salePrice;
        }

        $comission_amount = ($salePrice * $channelCharges['commission']) / 100;
        $pg_amount = ($salePrice * $channelCharges['pg_commission']) / 100;
        if ($fbl==0){
            $shipping_amount = $getisis_charges['isis_ppc'] + $getisis_charges['isis_wc'];
        }else{
            $shipping_amount = $channelCharges['shipping_fee'];
        }

        $extra_cost = $productDetail->extra_cost;
        $total_feeses = ($comission_amount + $pg_amount + $shipping_amount + $extra_cost);
        $sku_final_price_after_fees = $costPrice + $total_feeses;

        $detail = [];
        $detail['sku_cost'] = $costPrice;
        $detail['actual_cost'] = $productDetail->cost;
        $detail['extra_cost'] = $productDetail->extra_cost;
        $detail['commission_per'] = $channelCharges['commission'];
        $detail['payment_per'] = $channelCharges['pg_commission'];
        $detail['shipping_cost'] = $shipping_amount;
        $detail['gross_profit'] = number_format($salePrice - $total_feeses,2);


        $detail['price_after_subsidy'] = $salePrice;

        $detail['margin_per'] = number_format((($salePrice - $sku_final_price_after_fees) / $salePrice) * 100,2);
        $detail['margin_amount'] = number_format($salePrice - $sku_final_price_after_fees,2);
        $detail['margins_with_quantity_rm'] = '';
        $detail['subsidy'] = $subsidy;
        $detail['competitive_price_sell'] = '';
        return $detail;
    }
    public static function generate_random_color_part() {
        return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
    }

    public static function get_random_color() {
        return self::generate_random_color_part() . self::generate_random_color_part() . self::generate_random_color_part();
    }
    public static function get_distributors(){
        $sql = "SELECT * FROM user u
                WHERE u.role_id = 8;";
        return User::findBySql($sql)->asArray()->all();
    }
    public static function findCrawlSkus(){
        $to = date('Y-m-d');
        $from = date('Y-m-d', strtotime('-4 week'));
        $sql = "SELECT p.sku,cp.* FROM products p
                INNER JOIN competitive_pricing cp ON
                cp.sku_id = p.id
                WHERE cp.created_at BETWEEN '$from' AND '$to';";

        $crawl_products = CompetitivePricing::findBySql($sql)->asArray()->all();
        $result = [];
        foreach ( $crawl_products as $key=>$value ){
            $result[$value['sku']][]=$value;
        }
        return $result;
    }
    public static function get_categories_array($parent = 0)
    {
        $html = '';
        $query = Category::find()->where(['parent_id'=>$parent])->asArray()->all();
        foreach( $query as $value )
        {
            $current_id = $value['id'];
            $html .= ',' . $value['id'];
            $has_sub = NULL;
            $has_sub = Category::find()->where(['parent_id'=>$current_id])->asArray()->all();
            if($has_sub)
            {
                $html .= self::get_categories_array($current_id);
            }
            $html .= '';
        }
        $html .= '';
        return $html;
    }
    public static function GetAllChildCategories($parent=0){
        $result=self::get_categories_array($parent);
        $result = ltrim($result,',');
        $categories = explode(',',$result);
        if ($categories[0]==''){
            return [];
        }else{
            return $categories;
        }

    }

    public static function get_marketplace_channel_ids($marketplace_name=null)
    {
        if($marketplace_name)
        {
            if($marketplace_name) {  //if arketplace sent

                $channels=Channels::find()->select('id')->where(['marketplace'=>$marketplace_name])->asArray()->all();  // al channels in marketpalces
                $channel_ids=array_column($channels,'id'); // channels inside marketplace
                return  $channel_ids ? implode($channel_ids,','):false;
            }
        }
        return false;
    }
    public static function SaveApiResponseOFDealSkuPriceUpdate($sku_id,$channel_id,$deal_id,$api_response,$comments=''){
        $saveResponse = new CrossCheckProductPrices();
        $saveResponse->sku_id = $sku_id;
        $saveResponse->channel_id = $channel_id;
        $saveResponse->deal_id = $deal_id;
        $saveResponse->api_response = json_encode($api_response);
        $saveResponse->comments = $comments;
        $saveResponse->added_at = date('Y-m-d');
        $saveResponse->save();
    }
    public static function getSpiritSportsSkus(){
        $sql = " SELECT wsl.*
                 FROM warehouse_stock_list wsl
                 INNER JOIN warehouses w ON w.id = wsl.warehouse_id
                 WHERE w.name = 'spiritcombatsports' ";
        return WarehouseStockList::findBySql($sql)->asArray()->all();
    }

    public static function shorten_name($string,$characters=60)
    {
        $short_string=substr($string,0,$characters);
        return $short_string;
    }


}