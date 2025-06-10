<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 1/23/2018
 * Time: 1:36 PM
 */

namespace backend\controllers;


use backend\util\AmazonSellerPartnerUtil;
use backend\util\AmazonUtil;
use backend\util\BackmarketUtil;
use backend\util\BlueExUtil;
use backend\util\CourierUtil;
use backend\util\CronUtil;
use backend\util\DarazUtil;
use backend\util\DealsUtil;
use backend\util\DolibarrUtil;
use backend\util\EbayUtil;
use backend\util\FedExUtil;
use backend\util\GlobalMobileUtil;
use backend\util\HelpUtil;
use backend\util\HubspotUtil;
use backend\util\IsisUtil;
use backend\util\BigCommerceUtil;
use backend\util\LazadaUtil;
use backend\util\LCSUtil;
use backend\util\OrderUtil;
use backend\util\PrestashopUtil;
use backend\util\MagentoUtil;
use backend\util\ProductsUtil;
use backend\util\PurchaseOrderUtil;
use backend\util\QuickbookUtil;
use backend\util\RecordUtil;
use backend\util\SageUtil;
use backend\util\SalesUtil;
use backend\util\SapBusinessOneLocalUtil;
use backend\util\SapBusinnesOneLocalUtil;
use backend\util\SapUtil;
use backend\util\ShopeeUtil;
use backend\util\ShopifyUtil;
use backend\util\SkuVault;
use backend\util\SplWarehouseUtil;
use backend\util\TCSUtil;
use backend\util\UpsUtil;
use backend\util\UspsUtil;
use backend\util\WalmartUtil;
use backend\util\WarehouseUtil;
use backend\util\WishUtil;
use backend\util\WoocommerceUtil;
use Codeception\Template\Api;
use common\models\BulkOrderShipment;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\ChannelsProductsArchive;
use common\models\ClaimItems;
use common\models\CompetitivePricing;
use common\models\CostPrice;
use common\models\Couriers;
use common\models\DealsMaker;
use common\models\DealsMakerSkus;
use common\models\GeneralReferenceKeys;
use common\models\GlobalMobileCsvRecords;
use common\models\LazadaFinanceReport;
use common\models\OrderItems;
use common\models\Orders;
use common\models\OrderShipment;
use common\models\OrderShipmentHistory;
use common\models\PhilipsCostPrice;
use common\models\PoDetails;
use common\models\Pricing;
use common\models\Product360Status;
use common\models\ProductDetails;
use common\models\ProductDetailsArchive;
use common\models\Products;
use common\models\ProductsArchive;
use common\models\Products360Fields;
use common\models\ProductStocks;
use common\models\SetProductNew;
use common\models\Settings;
use common\models\SkusCrawl;
use common\models\SkuStockSales;
use common\models\StockPriceResponseApi;
use common\models\StocksDefinations;
use common\models\StocksPo;
use common\models\Subsidy;
use common\models\SubsidyArchive;
use common\models\TempCrawlResults;
use common\models\ThirdPartyOrders;
use common\models\ThirdPartyOrdersLog;
use common\models\ThresholdSales;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockArchive;
use common\models\WarehouseStockList;
use common\models\Zipcodes;
use http\Exception;
use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;

class CronController extends MainController
{
    public function actionAlerts()
    {
        // stocks alerts ISIS
        $this->_highStocksAlerts();
        // stocks alerts FBL Blip
        $this->_highStocksAlertsBlip();
        // stocks alerts FBL 909
        $this->_highStocksAlerts909();

    }


    public function actionStockArchive()
    {
        // stocks archives
        $this->_makeStocksArchive();
    }

    public function actionProductArchive()
    {
        // Products Archives
        $this->_makeProductsArchive();
    }

    public function actionChannelsStockArchive()
    {
        // stocks archives
        $this->_makeChannelProductStocksArchive();
    }

    private function _highStocksAlerts()
    {
        $threshold = "";
        $settings = Settings::find()->where(['name' => 'stocks_high_threshold_alert'])->one();
        if ($settings) {
            $threshold = $settings->value;
        }
        $alertList = [];
        /*$stocks = HelpUtil::callAllStocks();
        foreach ($stocks as $k=>$sk) {
            if ($sk['selling_status'] != '' && $sk['selling_status'] == 'High' && $threshold != "") {
                if ($sk['stocks'] <= $threshold ){
                    $alertList[$k] = $sk;
                    $count = HelpUtil::checkSameSKUQTY($sk['isis_sku']);
                    if(count($count) > 0)
                        unset($alertList[$k]);
                }

            }
        }*/
        $stocks = HelpUtil::callManageStocks();
        $levelA = $levelB = $levelC = [];
        foreach ($stocks as $k => $sk) {
            if ($sk['stock_status'] != 'Do not order' && $sk['stock_status'] != 'Not moving') {
                if ($sk['isis_stks'] < $sk['isis_threshold'] && $sk['isis_stks'] > $sk['isis_threshold_critical'] && $sk['isis_order_stocks'] != '0') {
                    $levelA[$k] = $sk;
                } else if ($sk['isis_stks'] < $sk['isis_threshold_critical'] && $sk['isis_stks'] != '0' && $sk['isis_order_stocks'] != '0') {
                    $levelB[$k] = $sk;
                } else if ($sk['isis_stks'] <= $threshold && isset($sk['isis_order_stocks']) && $sk['isis_order_stocks'] != '0') {
                    $levelC[$k] = $sk;
                    $count = HelpUtil::checkSameSKUQTY($sk['sku_id']);
                    if (count($count) > 0)
                        unset($levelC[$k]);
                }
            }
        }
        $alertList['A'] = $levelA;
        $alertList['B'] = $levelB;
        $alertList['C'] = $levelC;
        if (count($levelA) > 0 || count($levelB) > 0) {
            $to = $from = "";
            $settings = Settings::find()->where(['name' => 'alerts_email_address'])->one();
            if ($settings) {
                $to = $settings->value;
            }
            // email to concern persons
            $subject = "Stocks Alert: Daily Stocks threshold forecast for ISIS.";
            $email = explode(',', $to);
            $this->_sendEmail($email, $subject, $alertList, 'stocks-alert');
        }
    }



    private function _makeStocksArchive()
    {
        $now = date('Y-m-d h:i:s');
        echo "started at " . $now . PHP_EOL;
        $sql = "SELECT `wsl`.`warehouse_id`,`wsl`.`sku`,`wsl`.`available` ,`p`.`cost`
                FROM
                    `warehouse_stock_list` wsl
                LEFT JOIN
                    `products` p
                    ON 
                    `p`.`sku`=`wsl`.`sku`";

        $result=Yii::$app->db->createCommand($sql)->queryAll();

        if($result){
            foreach ($result as $item) {
                $model=new WarehouseStockArchive();
                $model->warehouse_id = $item['warehouse_id'];
                $model->sku = $item['sku'];
                $model->stock = $item['available'];
                $model->price = $item['cost'];
                $model->date_archive = $now;
                $model->save(false);
            }
        }

        echo "Finished at " . date('Y-m-d H:i:s');

    }

    private function _makeProductsArchive()
    {
        $now = date('Y-m-d h:i:s');
        echo "started at " . $now . PHP_EOL;
        $connection = Yii::$app->db;
        $sql = "SELECT * FROM products ";

        $result=Yii::$app->db->createCommand($sql)->queryAll();

        if($result){
            foreach ($result as $item) {

                $model=new ProductsArchive();
                $model->product_id = $item['id'];
                $model->sku = $item['sku'];
                $model->product_name = $item['name'];
                $model->cost_price = $item['cost'];
                $model->rccp = $item['rccp'];
                $model->promo_price = (isset($item['promo_price']) && $item['promo_price']!= null) ? $item['promo_price'] : '0';
                $model->sub_category = (isset($item['sub_category']) && $item['sub_category']!= null) ? $item['sub_category'] : '1';
                $model->selling_status = $item['selling_status'];
                $model->barcode = $item['barcode'];
                $model->ean = (isset($item['ean']) && $item['ean']!= null) ? $item['ean'] : '0';
                $model->stock_status = $item['stock_status'];
                $model->is_active = $item['is_active'];
                $model->archived_at = $now;
                $model->archived_by = $item['created_by'];
                $model->save(false);
            }
        }

        echo "Finished at " . date('Y-m-d H:i:s');


    }
    public function actionChannelsProductsArchive(){
        $Sql = "SELECT * FROM channels_products";
        $Data = ChannelsProducts::findBySql($Sql)->asArray()->all();
        $response = ['failed'=>0,'success'=>0,'already_added'=>0];

        foreach ( $Data as $Detail ){

            $Sql = "SELECT * FROM channels_products_archive cpa WHERE cpa.sku='".$Detail['sku']."' 
            AND cpa.channel_id = ".$Detail['channel_id']." AND cpa.product_id=".$Detail['product_id']." AND cpa.channel_sku='".$Detail['channel_sku']."'
            AND date_archive like '".date('Y-m-d')."%'";

            $findArchive = ChannelsProductsArchive::findBySql($Sql)->asArray()->all();

            if ( !$findArchive ){
                $addArchive = new ChannelsProductsArchive();
                $addArchive->sku=$Detail['sku'];
                $addArchive->variation_id = $Detail['variation_id'];
                $addArchive->channel_sku=$Detail['channel_sku'];
                $addArchive->channel_id=$Detail['channel_id'];
                $addArchive->product_id=$Detail['product_id'];
                $addArchive->price=$Detail['price'];
                $addArchive->stock_qty=$Detail['stock_qty'];
                $addArchive->ean=$Detail['ean'];
                $addArchive->date_archive=date('Y-m-d H:i:s');
                $addArchive->product_name = $Detail['product_name'];
                $addArchive->created_at = time();
                $addArchive->updated_at=time();
                $addArchive->created_by=1;
                $addArchive->updated_by=1;
                $addArchive->save();
                if ( empty($addArchive->errors) ){
                    $response['success'] += 1;
                }else{
                    $response['failed'] += 1;
                }
            }else{
                $response['already_added'] += 1;
            }
        }
        echo '<h1>Cron job Response : </h1>';
        $this->debug($response);
    }
    private function _makeChannelProductStocksArchive()
    {
        $now = date('Y-m-d h:i:s');
        $connection = Yii::$app->db;
        $sql = "SELECT * FROM `channels_products`
                ORDER BY last_update DESC";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        foreach ($result as $re) {
            $model = new ChannelsProductsArchive();
            unset($re['id']);
            unset($re['last_update']);
            foreach ($re as $k => $v) {
                $model->$k = $v;
            }
            $model->date_archive = $now;
            $model->save(false);
        }

        echo "archived";

    }

    private function _sendEmail($email, $subject, $result, $view)
    {
        try {
            $respond = \Yii::$app->mailer->compose($view, ['result' => $result])
                ->setFrom("notifications@ezcommerce.io")
                ->setTo($email)
                ->setSubject($subject)
                ->send();
        } catch (\Swift_TransportException $st) {
            print_r($st);
        }
    }

    public function actionCalculatePricing()
    {
        ini_set('memory_limit', '-1');
        $date = date('Y-m-d');
        $udpate = HelpUtil::getSkuDetails($date);
    }

    public function actionUpdateDealMakerSkuSubsidy()
    {
        $today = strtotime(date('Y-m-d H:i'));
        $dealMakerSkus = DealsMakerSkus::find()->where(['status' => 'Approved'])->all();
        foreach ($dealMakerSkus as $v) {
            $sdate = $v->dealsMaker->start_date;
            $edate = $v->dealsMaker->end_date;

            if ($today >= strtotime($sdate) && $today <= strtotime($edate)) {
                $subsidy = Subsidy::findOne(['sku_id' => $v->sku_id, 'channel_id' => $v->dealsMaker->channel_id]);
                if ($subsidy) {
                    $subsidy->start_date = $sdate;
                    $subsidy->end_date = $edate;
                    $subsidy->ao_margins = $v->deal_margin;
                    $subsidy->subsidy = ($v->deal_subsidy) ? $v->deal_subsidy : $subsidy->subsidy;
                    $subsidy->save(false);
                }
            }

        }
    }

    public function actionCheckSubsidyDates()
    {
        $connection = Yii::$app->db;
        $today = date("Y-m-d H:i:s");
        $sql = "SELECT * FROM `subsidy` WHERE '$today' > end_Date";
        $command = $connection->createCommand($sql);
        $re = $command->queryAll();
        if ($re) {
            foreach ($re as $v) {
                $s = Subsidy::findOne(['id' => $v['id']]);

                // archive
                $model = new SubsidyArchive();
                $model->ao_margins = $s->ao_margins;
                $model->margins = $s->margins;
                $model->subsidy = $s->subsidy;
                $model->start_date = $s->start_date;
                $model->end_date = $s->end_date;
                $model->channel_id = $s->channel_id;
                $model->sku_id = $s->sku_id;
                $model->date_archive = $today;
                $model->save(false);

                //$s->subsidy = '0';
                $s->ao_margins = $model->margins;
                //$s->margins = '0';
                $s->start_date = $today;
                $s->end_date = date('Y-m-d', strtotime('+1 month'));
                $s->save(false);
            }


        }

    }

    public function actionSubsidyPriceUpdate()
    {
        echo date('Y-m-d H:i:s');
        $response = [];
        // update price base on Subsidy
       $today = date('Y-m-d H:i');
        //$today = date('Y-m-d');

        $deals = DealsMaker::find()->where(['LIKE', 'start_date', $today])->andWhere(['status' => 'new'])->all();
        //$deals = DealsMaker::find()->where(['id'=>64])->andWhere(['status' => 'new'])->all();
      // self::debug($deals);
        foreach ($deals as $d) {
            $dskus = DealsMakerSkus::find()->where(['deals_maker_id' => $d->id])->andWhere(['status' => 'Approved'])->asArray()->all();
            $channel = Channels::findone(['id' => $d->channel_id,'is_active' => 1]);
            if($channel->marketplace=="amazon")  //amazon mws  //old
            {
                $api_response= AmazonUtil::updateSalePrices($channel,$d,$dskus ,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="amazonspa") // amazon seller partner api //new
            {
               // self::debug($dskus);
                $api_response=AmazonSellerPartnerUtil::updateSalePrices($channel,$d,$dskus,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            else if($channel->marketplace=="ebay"){
                die('in progress');
                $api_response=EbayUtil::updateSalePrices($channel,$d,$dskus ,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="magento")
            {

                $api_response= MagentoUtil::updateSalePrices($channel,$d,$dskus ,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="daraz")
            {
               // die('come');
                $api_response= DarazUtil::updateSalePrices($channel,$d,$dskus ,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="woocommerce")
            {
                $api_response= WoocommerceUtil::updateSalePrices($channel,$d,$dskus ,true);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;

            }

            else if ($channel->marketplace=="walmart")
            {
                // Nested - First delete all promotions if already running on skus
                $delete_item_chunks=WalmartUtil::deal_item_chunks($dskus,$d,false);
                $feedIds=WalmartUtil::updateSalePricesInBulk($channel,$delete_item_chunks);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$feedIds,'Deal Start: Delete Promotions on SKUS'); // save response from api


                // Update new deal price
                $make_item_chunks = WalmartUtil::deal_item_chunks($dskus,$d,true);
                $feedIds= WalmartUtil::updateSalePricesInBulk($channel,$make_item_chunks);

                // wait for 40 seconds to let walmart update their feed, Min they take 20s we put 40 max
                sleep(40);
                // get feed responses sku wise
                if ( isset($feedIds['feedlist']) )
                    $getFeedDetails = WalmartUtil::GetAllFeedDetails($channel,$feedIds['feedlist']);
                else
                    $getFeedDetails = $feedIds;

                // save feed in response
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$getFeedDetails,'Deal Start: Add Promotions to walmart skus'); // save response from api
                $response[$d->id][] = $getFeedDetails;
            }
            else {
                foreach ($dskus as $dk) {
                    //$this->debug($dk);
                    $params['deal_id']=$dk['deals_maker_id'];
                    $params['sku_id'] = $dk['sku_id'];
                    $params['channel_id'] = $d->channel_id;
                    $params['customer_type'] = $d->customer_type;
                    $params['from'] = $d->start_date;
                    $params['to'] = $d->end_date;
                    $params['settings'] = $dk['settings'];
                    $params['discount_type'] = $d->discount_type;
                    $params['discount'] = $d->discount;
                    $params['deal_extra_params'] = $d->extra_params;
                    $params['dm_sku_pk'] = $dk['id'];
                    $params['price_sell'] = $dk['deal_price'];
                    $params['subsidy'] = $dk['deal_subsidy'];
                    $params['qty'] = $dk['deal_target'];
                    $api_response = HelpUtil::SyncDealPrices($params);

                    // save response from api
                    HelpUtil::SaveApiResponseOFDealSkuPriceUpdate($dk['sku_id'],$d->channel_id,$d->id,$api_response);

                    $response[$d->id][] = $api_response;

                }
            }  // else

            \Yii::$app->db->createCommand("UPDATE deals_maker SET status=:status,deal_start_update=:deal_start_update, start_date=:start_date WHERE id=:id")
                ->bindValue(':id', $d->id)
                ->bindValue(':deal_start_update', 1)
                ->bindValue(':status', 'active')
                ->bindValue(':start_date',$d->start_date)
                ->execute();
        }

        // update price base on ending deals
        $deals = DealsMaker::find()->where(['LIKE', 'end_date', $today])->all();

        //$this->debug($deals);
        foreach ($deals as $d) {
            $dskus = DealsMakerSkus::find()->where(['deals_maker_id' => $d->id])->andWhere(['status' => 'Approved'])->asArray()->all();
            $channel = Channels::findone(['id' => $d->channel_id,'is_active' => 1]);
           // echo "<pre>";
           // print_r($dskus); die();
            if($channel->marketplace=="amazon")
            {
                $api_response= AmazonUtil::updateSalePrices($channel,$d,$dskus,false );
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="amazonspa") // amazon seller partner api //new
            {
                // self::debug($dskus);
                $api_response=AmazonSellerPartnerUtil::updateSalePrices($channel,$d,$dskus,false);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="magento")
            {
                $api_response= MagentoUtil::updateSalePrices($channel,$d,$dskus ,false);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="daraz")
            {
                // die('come');
                $api_response= DarazUtil::updateSalePrices($channel,$d,$dskus ,false);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            elseif($channel->marketplace=="woocommerce")
            {
                $api_response= WoocommerceUtil::updateSalePrices($channel,$d,$dskus ,false);
                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$api_response); // save response from api
                $response[$d->id][] = $api_response;
            }
            else if ($channel->marketplace=="walmart")
            {
                $make_item_chunks = WalmartUtil::deal_item_chunks($dskus,$d,false);
                $feedIds= WalmartUtil::updateSalePricesInBulk($channel,$make_item_chunks);
                sleep(40);
                if ( isset($feedIds['feedlist']) )
                    $getFeedDetails = WalmartUtil::GetAllFeedDetails($channel,$feedIds['feedlist']);
                else
                    $getFeedDetails = $feedIds;

                HelpUtil::SaveApiResponseOFDealSkuPriceUpdate(0,$d->channel_id,$d->id,$getFeedDetails,'Deal End: Delete Promotions'); // save response from api
                WalmartUtil::activate_nested_deal($d,$dskus);
                $response[$d->id][] = $getFeedDetails;
            }
            else {
                $otherActiveDeals = DealsUtil::GetOtherActiveDeals($d->channel_id,$d->id);
                foreach ($dskus as $dk)
                {
                    //$this->debug($dk);
                    $params['deal_id']=$dk['deals_maker_id'];
                    $params['sku_id'] = $dk['sku_id'];
                    $params['channel_id'] = $d->channel_id;
                    $params['dm_sku_pk'] = $dk['id'];
                    $params['price_sell'] = $dk['deal_price'];
                    $params['deal_extra_params'] = $d->extra_params;
                    $params['subsidy'] = $dk['deal_subsidy'];
                    $params['qty'] = $dk['deal_target'];
                    $params['settings']=$dk['settings'];
                    $params['activated_deal_price'] = DealsUtil::GetSkuPriceOfClosestActiveDeal($otherActiveDeals,$dk['sku_id']);
                    $response[$d->id][] = HelpUtil::SyncDealPrices($params, false);

                }}

            \Yii::$app->db->createCommand("UPDATE deals_maker SET status=:status,deal_end_update=:deal_end_update, end_date=:end_date WHERE id=:id")
                ->bindValue(':id', $d->id)
                ->bindValue(':deal_end_update', 1)
                ->bindValue(':status', 'expired')
                ->bindValue(':end_date',$d->end_date)
                ->execute();
            $_GET['deal_id'] = $d->id;
            $_GET['status'] = 'expired';
            $UpdateActualSalesCount=$this->actionUpdateSalesItemsByDeals();
        }
        echo date('Y-m-d H:i:s');
    }

    public function actionUpdateSaleCategory()
    {
        echo date('Y-m-d H:i:s');
        echo "<br>";
        if(isset($_GET['shop-prefix']) && isset($_GET['option-type'])) {

            $channel = Channels::findone(['prefix' => $_GET['shop-prefix'], 'is_active' => 1]);
            if ($channel->marketplace == "magento") {
                if($_GET['option-type'] == "set-sale") {
                    $api_response = MagentoUtil::setSaleCategory($channel);
                    MagentoUtil::sale_cat_modification_log($api_response, $_GET['option-type']);

                }elseif ($_GET['option-type'] == "unset-sale"){
                    $api_response=MagentoUtil::unsetSaleCategory($channel);
                    MagentoUtil::sale_cat_modification_log($api_response, $_GET['option-type']);

                }else{
                    echo "option-type param required";
                }
            }

        } else{
            echo "Check missing params";
        }
        echo date('Y-m-d H:i:s');
    }

    public function actionDeleteChannelProduct()
    {
        echo date('Y-m-d H:i:s');
        echo "<br>";
        if(isset($_GET['shop-prefix'])) {

            $channel = Channels::findone(['prefix' => $_GET['shop-prefix'], 'is_active' => 1]);
            if ($channel->marketplace == "magento")
            {
                    $products = MagentoUtil::getProductsToDelete($channel);
                    $api_response = MagentoUtil::deleteChannelProducts($channel,$products);
            }
        } else{
            echo "Check missing params";
        }
        echo date('Y-m-d H:i:s');
    }

    public function actionSubsidyPriceForceFullyEndDeal(){
        $deals = DealsMaker::find()->where(['deal_end_update'=>0])->andWhere(['status'=>'expired'])->limit(1)->all();
        foreach ($deals as $d) {
            $dskus = DealsMakerSkus::find()->where(['deals_maker_id' => $d->id])->andWhere(['status' => 'Approved'])->asArray()->all();
            foreach ($dskus as $dk) {
                $params['deal_id']=$dk['deals_maker_id'];
                $params['sku_id'] = $dk['sku_id'];
                $params['channel_id'] = $d->channel_id;
                $params['price_sell'] = $dk['deal_price'];
                $params['fbl'] = ($d->channel->marketplace == 'lazada') ? '1' : '0';
                $params['subsidy'] = $dk['deal_subsidy'];
                $params['qty'] = $dk['deal_target'];
                if ($_SERVER['HTTP_HOST']=='philips.ezcommerce.io')
                    $response[$d->id][] = HelpUtil::calculatePrice4Subsidy($params, false);
                else
                    $response[$d->id][] = HelpUtil::SyncDealPrices($params, false);

            }
            \Yii::$app->db->createCommand("UPDATE deals_maker SET status=:status,deal_end_update=:deal_end_update, end_date=:end_date WHERE id=:id")
                ->bindValue(':id', $d->id)
                ->bindValue(':status', 'expired')
                ->bindValue(':deal_end_update', 1)
                ->bindValue(':end_date',$d->end_date)
                ->execute();
        }
    }
    public function actionSubsidyPriceForceFullyStartDeal(){
        $deals = DealsMaker::findBySql("SELECT * FROM `deals_maker` dm
        WHERE dm.`deal_start_update` = 0 AND (dm.`status` = 'new' || dm.`status` = 'active')
        AND dm.start_date < '".date('Y-m-d H:i:s')."' AND dm.end_date > '".date('Y-m-d H:i:s')."';")->limit(1)->all();
        foreach ($deals as $d) {
            $dskus = DealsMakerSkus::find()->where(['deals_maker_id' => $d->id])->andWhere(['status' => 'Approved'])->asArray()->all();
            foreach ($dskus as $dk) {
                $params['deal_id']=$dk['deals_maker_id'];
                $params['sku_id'] = $dk['sku_id'];
                $params['channel_id'] = $d->channel_id;
                $params['price_sell'] = $dk['deal_price'];
                $params['fbl'] = ($d->channel->marketplace == 'lazada') ? '1' : '0';
                $params['subsidy'] = $dk['deal_subsidy'];
                $params['qty'] = $dk['deal_target'];
                if ($_SERVER['HTTP_HOST']=='philips.ezcommerce.io')
                    $response[$d->id][] = HelpUtil::calculatePrice4Subsidy($params);
                else
                    $response[$d->id][] = HelpUtil::SyncDealPrices($params);
            }
            // mark the column deal_start_update = 1,
            \Yii::$app->db->createCommand("UPDATE deals_maker SET status=:status,deal_start_update=:deal_start_update, start_date=:start_date WHERE id=:id")
                ->bindValue(':id', $d->id)
                ->bindValue(':status', 'active')
                ->bindValue(':deal_start_update', 1)
                ->bindValue(':start_date',$d->start_date)
                ->execute();
        }
    }
    public function actionUpdateDealsExpired()
    {
        $today = date("Y-m-d H:i:s");
        $condition_expire = ['and',
            ['<', 'end_date', $today]
        ];
        DealsMaker::updateAll(['status' => 'expired'], $condition_expire);

        $condition_active = ['and',
            ['<=', 'start_date', $today],
            ['>=', 'end_date', $today]
        ];
        $re = DealsMaker::updateAll(['status' => 'active'], $condition_active);
        $settings = Settings::find()->where(['name' => 'last_deals_expire_update'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }
    }

    private function _saveResponse($type, $channel_id, $response)
    {
        $saveResponse = new StockPriceResponseApi();
        $saveResponse->type = $type . ' - ' . $_SERVER['HTTP_HOST'];
        $saveResponse->channel_id = $channel_id;
        $saveResponse->response = json_encode($response);
        $saveResponse->create_at = date('Y-m-d H:i:s');
        $saveResponse->save(false);
        if (!empty($saveResponse->errors))
            $this->debug($saveResponse->errors);
        return $saveResponse;
    }


    public function actionSalesFetch()
    {
        ini_set('MAX_EXECUTION_TIME', -1);
        echo "started at " . date('h:i:s') . "<br/>";

        if(isset($_GET['shop-prefix']))  // for presta magento and other new // for single store
            $this->fetchSalesApi($_GET['shop-prefix']);
        else
            die('invalid params');

       // date_default_timezone_set('Asia/kuala_lumpur');
        echo "finished at " . date('h:i:s');
        $settings = Settings::find()->where(['name' => 'last_sales_fetch_api_update'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }
    }



    private function fetchSalesApi($shop_prefix)
    {
        $channel = Channels::findone(['is_active' => '1', 'prefix' => $shop_prefix]);
        if($channel):

          $time_period=isset($_GET['time_period']) ? $_GET['time_period']:"chunk"; // all , day , chunk=>20 minutes
            if (strtolower($channel->marketplace)=="prestashop") {
                PrestashopUtil::fetchOrdersApi($channel,$time_period);  // util to fetch order data from api

            }  elseif(strtolower($channel->marketplace)=="magento")  {

                MagentoUtil::fetchOrdersApi($channel,$time_period);  // util to fetch order data from api

            } elseif ( strtolower($channel->marketplace)=="ebay" ) {

                EbayUtil::fetchOrdersApi($channel,$time_period);

            }  elseif ( strtolower($channel->marketplace)=="shopify") {

                ShopifyUtil::fetchOrdersApi($channel,$time_period);

            }  elseif (strtolower($channel->marketplace)=="daraz") {

                DarazUtil::fetchOrdersApi($channel,$time_period);

            } elseif (strtolower($channel->marketplace)=="amazon") { // amazon old version //mws api
                \backend\util\AmazonUtil::fetchOrdersApi($channel,$time_period);

            } elseif(strtolower($channel->marketplace)=="amazonspa"){  // amazon new api version ,// seller partner api

                AmazonSellerPartnerUtil::fetchOrdersApi($channel,$time_period);

            }elseif (strtolower($channel->marketplace)=="walmart") {

                WalmartUtil::fetchOrdersApi($channel,$time_period);
            } elseif(strtolower($channel->marketplace)=="lazada") {

                LazadaUtil::fetchOrdersApi($channel,$time_period);
            } elseif(strtolower($channel->marketplace)=="shopee") {

               ShopeeUtil::fetchOrdersApi($channel,$time_period);
            }
            elseif(strtolower($channel->marketplace)=="bigcommerce") {
                $time_period=isset($_GET['time_period']) ? $_GET['time_period']:"all"; // if time_period is not given
                BigCommerceUtil::fetchOrdersApi($channel,$time_period);
            } elseif(strtolower($channel->marketplace)=="backmarket"){
                BackmarketUtil::fetchOrdersApi($channel,$time_period);

            }
            elseif(strtolower($channel->marketplace)=="woocommerce"){
                WoocommerceUtil::fetchOrdersApi($channel,$time_period);

            }
            elseif(strtolower($channel->marketplace)=="wish"){
                WishUtil::fetchOrdersApi($channel,$time_period);

            }



        endif;
    }



    // check ER status and clear stock in transit
    public function actionCheckErStatus()
    {
        $po = StocksPo::find()->select(['id', 'er_no'])->where(['po_status' => 'Pending'])->asArray()->all();
        foreach ($po as $p) {
            $erNo = $p['er_no'];
            if ($erNo) {
                $status = ApiController::checkERStatus($erNo);
                if (strtolower($status) == 'received' && $status) {
                    $po = StocksPo::find()->where(['id' => $p['id']])->one();
                    $po->po_status = 'Shipped';
                    $po->update();

                    $pod = PoDetails::find()->where(['po_id' => $p['id']])->all();
                    foreach ($pod as $item) {
                        // update stock intransit
                        $sku = $item->sku;
                        $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                        $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                        if ($ps) {
                            if ($po->po_warehouse == 1)
                                $ps->stocks_intransit = '0';
                            else if ($po->po_warehouse == 2)
                                $ps->fbl_stocks_intransit = '0';
                            else if ($po->po_warehouse == 5)
                                $ps->fbl_stocks_intransit = '0';
                            else if ($po->po_warehouse == 3)
                                $ps->fbl909_stocks_intransit = '0';
                            else if ($po->po_warehouse == 6)
                                $ps->fbl909_stocks_intransit = '0';
                            $ps->save(false);
                        }

                    }
                } else if (strtolower($status) == 'receiving' && $status) {
                    $po = StocksPo::find()->where(['id' => $p['id']])->one();
                    $po->po_status = 'Partial Shipped';
                    $po->update();

                    $pod = PoDetails::find()->where(['po_id' => $p['id']])->all();
                    foreach ($pod as $item) {
                        // update stock intransit
                        $sku = $item->sku;
                        $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                        $ps = ProductStocks::find()->where(['stock_id' => $pd->id])->one();
                        if ($ps) {
                            if ($po->po_warehouse == 1)
                                $ps->stocks_intransit = '0';
                            else if ($po->po_warehouse == 2)
                                $ps->fbl_stocks_intransit = '0';
                            else if ($po->po_warehouse == 5)
                                $ps->fbl_stocks_intransit = '0';
                            else if ($po->po_warehouse == 3)
                                $ps->fbl909_stocks_intransit = '0';
                            else if ($po->po_warehouse == 6)
                                $ps->fbl909_stocks_intransit = '0';
                            $ps->save(false);
                        }

                    }
                }
            }
        }
    }

    public function actionUpdateErQty()
    {
        echo date('Y-m-d H:i:s');
        $porders = StocksPo::findBySql("SELECT `id`, `er_no`,psp.po_initiate_date
        FROM `product_stocks_po` psp
        WHERE psp.po_warehouse = 1 AND psp.po_initiate_date > DATE_SUB(NOW(), INTERVAL 18 DAY) 
        AND (psp.po_status = 'Pending' OR psp.po_status = 'Partial Shipped')")->asArray()->all();
        foreach ($porders as $po) {
            $erNo = $po['er_no'];
            if ($erNo && $erNo != '') {
                $erDetails = ApiController::fetchER($erNo);
                if ($erDetails['success'] == '1') {
                    $details = $erDetails['returnObject']['erDetailViewList'];
                    foreach ($details as $d) {
                        $pod = PoDetails::find()->where(['po_id' => $po['id'], 'sku' => $d['storageClientSkuNo']])->one();
                        if ($pod) {
                            $pod->er_qty = $d['recvQty'];
                            $pod->update();
                        }
                    }
                }
            }
        }
        echo '<br />';
        echo date('Y-m-d H:i:s');
        die;
    }

    // update ER base on ERno
    public function actionUpdateEr()
    {
        $po = StocksPo::find()->select(['id', 'er_no'])->where(['<>', 'er_no', ""])->asArray()->all();
        foreach ($po as $p) {
            $erNo = $p['er_no'];

            $poCode = ApiController::checkERStatus($erNo, "code");
            $po = StocksPo::find()->where(['id' => $p['id']])->one();
            $po->po_code = $poCode;
            $po->update();
        }
    }

    // generate dynamic table for inventory reports
    public function actionGenStockReportTbl()
    {
        $connection = Yii::$app->db;
        $sql = "DROP TABLE IF EXISTS stocks_report;
CREATE TABLE stocks_report AS
(SELECT DATE(a.date_archive) as date_archive,a.isis_sku,a.stocks,a.selling_status
FROM product_details_archive AS a
        WHERE a.stocks <>
      ( SELECT b.stocks
        FROM product_details_archive AS b
        WHERE a.isis_sku = b.isis_sku
          AND DATE(a.date_archive) > DATE(b.date_archive)
          AND b.`selling_status` = \"Slow\"
        ORDER BY DATE(b.date_archive) DESC
        LIMIT 1
      ) AND a.`selling_status` = \"Slow\")
UNION ALL 
(SELECT DATE(a.date_archive) as date_archive,a.isis_sku,a.stocks,a.selling_status
FROM product_details_archive AS a
WHERE a.stocks <>
      ( SELECT b.stocks
        FROM product_details_archive AS b
        WHERE a.isis_sku = b.isis_sku
          AND DATE(a.date_archive) > DATE(b.date_archive)
          AND b.`selling_status` = \"High\"
        ORDER BY DATE(b.date_archive) DESC
        LIMIT 1
      ) AND a.`selling_status` = \"High\")
UNION ALL
(SELECT DATE(a.date_archive) as date_archive,a.isis_sku,a.stocks,a.selling_status
FROM product_details_archive AS a
WHERE a.stocks <>
      ( SELECT b.stocks
        FROM product_details_archive AS b
        WHERE a.isis_sku = b.isis_sku
          AND DATE(a.date_archive) > DATE(b.date_archive)
          AND b.`selling_status` = \"Medium\"
        ORDER BY DATE(b.date_archive) DESC
        LIMIT 1
      ) AND a.`selling_status` = \"Medium\");";


        $command = $connection->createCommand($sql);
        $result = $command->execute();

    }




    public function actionFetchProductStockIsisInfo()
    {
        // log this job in db
        echo date('Y-m-d H:i:s');
        ini_set('MAX_EXECUTION_TIME', -1);
        // echo "started at " . date('h:i:s') . "<br/>";
        $products = ApiController::fetchIsis();
        if ($products == 'Error') {
            $settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
            if ($settings) {
                $settings->value = "API ERROR - " . date('Y-m-d h:i:s');
                if (!$settings->update())
                    print_r($settings->getErrors());
            }
        } else {
            $settings = Settings::find()->where(['name' => 'isis_products'])->one();
            //$settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
            if ($settings) {
                $settings->value = $products;
                if (!$settings->update())
                    print_r($settings->getErrors());
            }
            //avoid CPU exhaustion, adjust as necessary
            //echo "finished at " . date('h:i:s');
            $settingss = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
            if ($settingss) {
                $settingss->value = date('Y-m-d h:i:s');
                if (!$settingss->save())
                    print_r($settingss->getErrors());
            }
        }
        echo "";
        echo date('Y-m-d H:i:s');
    }

    public function actionSaveProductStockIsisInfo()
    {
        //strtotime('-1 hour')
        $Log = $this->GetCronLog(date('Y-m-d H:i:s', strtotime('-3 hours')),date('Y-m-d H:i:s'),'cron/save-product-stock-isis-info',1,1);
        if (!empty($Log)){
            die;
        }
        ini_set('MAX_EXECUTION_TIME', -1);
        echo "started at " . date('h:i:s') . "<br/>";
        $settings = Settings::find()->where(['name' => 'isis_products'])->one();
        if ($settings->value != '') {
            $skuArray = HelpUtil::getSkuList("sku", ['stock_status', 'id']);
            $products = json_decode($settings->value, true);
            $list=[];
            foreach ($products as $p) {
                $sku = $p['storageClientSkuNo'];
                $sku = str_replace('�', '', $sku);
                $sku = trim($sku);
                $sku = str_replace('_', '/', $sku);
                $code='';
                if ( isset($p['skuNo']) )
                    $code=$p['skuNo'];

                $stock = $p['availableQty'];

                /**
                 * we are making the $list, the reason is we are getting two skus from isis api at the same time and the last one is the wrong one
                 * thats the reason i'm making this array to get the maximum value when we try to save in product_details.
                 * */

                $list[$sku]['goodQty'][]=$p['goodQty'];
                $list[$sku]['availableQty'][]=$p['availableQty'];



                $query = "SELECT id FROM `products` WHERE sku LIKE '$sku'";
                $result = Products::findBySql($query)->asArray()->one();

                if ($result) {
                    $pd = ProductDetails::find()->where(['isis_sku' => $sku])->one();
                    if (!$pd)
                        $pd = new ProductDetails();
                    else {
                        $p['goodQty'] = max($list[$sku]['goodQty']);
                        $p['allocatingQty'] = $p['allocatingQty'];
                        $p['processingQty'] = $p['processingQty'];
                        $p['damagedQty'] = $p['damagedQty'];
                        $stock = max($list[$sku]['availableQty']);
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
                        $p['goodQty'] = max($list[$sku]['goodQty']);
                        $p['allocatingQty'] = $p['allocatingQty'];
                        $p['processingQty'] = $p['processingQty'];
                        $p['damagedQty'] = $p['damagedQty'];
                        $stock = max($list[$sku]['availableQty']);
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


        echo "finished at " . date('h:i:s');

    }


    /*
     *  track shipping status from couriers and update in marketplace
     */

    public function actionTrackUpdateShippings()
    {


        echo "started at : " . date('Y-m-d H:i:s') . "<br/>";
        $list=CourierUtil::getTrackingList(); // list to track
        //self::debug($list);
        if($list):
            foreach($list as $item)
            {
                $courier=Couriers::findOne(['id'=>$item['courier_id']]);
                   // echo "<pre>";print_r($courier);exit;
                if($courier)
                {
                   // $courier_response['courier_status']="delivered";
                    /**** track shipping status against tracking number****/
                    if(strtolower($courier->type)=="ups")
                       $courier_response= UpsUtil::trackShipping($courier,$item['tracking_number']);
                    elseif(strtolower($courier->type)=="usps")
                        $courier_response= UspsUtil::trackShipping($courier,$item['tracking_number']);
                    elseif(strtolower($courier->type)=="fedex")
                        $courier_response=FedExUtil::trackShipping($courier,$item['tracking_number']);
                    elseif(strtolower($courier->type)=="lcs")
                        $courier_response=LCSUtil::trackShipping($courier,$item['tracking_number']);
                    elseif(strtolower($courier->type)=="blueex")
                        $courier_response=BlueExUtil::trackShipping($courier,$item['tracking_number']);
                    elseif(strtolower($courier->type)=="tcs")
                        $courier_response=TCSUtil::trackShipping($courier,$item['tracking_number']);

                    if(!isset($courier_response['courier_status']))
                       die('courier response failure'); // return;

                   // echo "<pre>";print_r($courier_response);exit;
                    /****update status in local db ****/

                    $system_status=CourierUtil::update_local_db_shipping_status($item,$courier_response['courier_status']);
                    if(!$system_status)
                        continue;
                    /****update  shipping status in marketplace***/
                    $channel=Channels::findOne(['id'=>$item['channel_id']]);
                    if($channel->marketplace=="prestashop")
                    {
                        $system_status = PrestashopUtil::map_system_courier_marketplace_statuses($system_status); // map our status with marketplace status
                        if (in_array($system_status, ['delivered', 'canceled', 'refunded'])){
                            $system_status= PrestashopUtil::check_remaining_order_items_status($item,$system_status);
                            if($system_status)
                            PrestashopUtil::SetOrderStatus($item['channel_id'], $item['marketplace_db_order_id'], ucfirst($system_status));
                        }
                    }
                    elseif($channel->marketplace=="woocommerce")
                    {
                        $system_status = WoocommerceUtil::map_system_courier_marketplace_statuses($system_status); // map our status with marketplace status
                       if (in_array($system_status, ['completed', 'cancelled', 'refunded'])){
                                WoocommerceUtil::SetOrderStatus($item['channel_id'], $item['marketplace_db_order_id'], $system_status);
                        }
                    }
                    elseif($channel->marketplace=='ebay')
                         continue;

                   // $marketplace_update=CourierUtil::update_marketplace_shipping_status($channel,$system_status);

                }
            }
        endif;
        echo "ended at : " . date('Y-m-d H:i:s') . "<br/>";
           // $response=CourierUtil::trackShipping($list); // update marketplace shipping status, local db shipping status
    }




    // update thresholds

    public function actionUpdateThreshold()
    {
        echo date('H:i:s').'<br />';
        if ( isset($_GET['name']) ){
            $warehouse = Warehouses::find()->where(['name'=>$_GET['name'],'is_active'=>1])->one();
        }else{
            $warehouse = Warehouses::find()->where(['is_active'=>1])->all();
        }
        if (empty($warehouse)){
            echo '<h2>There is no warehouse found or maybe it is inactive.</h2>';
            die;
        }
        foreach ( $warehouse as $warehouseDetail ){
            $WareHouseSales = CronUtil::GetWarehouseThresholds($warehouseDetail); // Get all the sales of warehouse
            CronUtil::SaveThresholds($warehouseDetail, $WareHouseSales); // Save all thresholds
            CronUtil::NoSalesThresholds($warehouseDetail,$WareHouseSales); // Save 0 threshold which we don't have any sales
        }

        echo date('H:i:s').'<br />';
    }

    public function actionUpdateOutOfStockForcast(){
        $warehouse = Warehouses::find()->where(['is_active'=>1])->all();

        foreach ( $warehouse as $warehouseDetail ){
            $WareHouseThresholds = ThresholdSales::find()->where(['warehouse_id'=>$warehouseDetail->id])->all(); // Get all the sales of warehouse
            WarehouseStockList::updateAll(['days_left_in_oos' => '0'], 'warehouse_id = '.$warehouseDetail->id);
            foreach ( $WareHouseThresholds as $thresholdDetail ){
                CronUtil::UpdateThresholdForcast($thresholdDetail['sku'],$warehouseDetail->id,$thresholdDetail['threshold']);
            }
            // mark the upcoming approx stock 0 if no sales found
            CronUtil::NoSalesForcastOutOfStock($WareHouseThresholds,$warehouseDetail);
        }
    }
    /////update selling status of each channel and product overall
    public function actionUpdateSellingStatus()
    {
        echo "Started at : " . date('Y-m-d H:i:s').PHP_EOL;
        ///////if job is weekly or other than monthly then from now to previuos 3 months
        if(isset($_GET['calculate_from_current_day'])){
            $start_date = date("Y-m-d", strtotime('-3 months'));
            $end_date =date("Y-m-d");
        } else{
            $start_date = date("Y-m-d", strtotime('first day of -3 months'));
            $end_date = date("Y-m-d", strtotime('last day of last month'));
        }

       echo "<br/>";
        echo $start_date;
        echo "<br/>";
        echo $end_date;
        die();
        $channels=Channels::find()->where(['is_active'=>'1'])->all();
        if($channels)
        {
            $global_sku=array();  // to set status of overall product irespective of marketplace and  channel
            foreach($channels as $channel)
            {
                $sales=CronUtil::getSellingSTock($channel->id,$start_date,$end_date,'by_channel'); // get sku and qty sold by chanel
                if($sales)
                {

                    $global_sku= CronUtil::updateSellingStatus($channel->id,$sales,$global_sku); // update sales against each sku
                    $reset_items=array_column($sales,'item_sku');
                    CronUtil::resetUnsoldStockStatus($channel->id,$reset_items); // reset sales to zero which are not sold channel wise
                }

            }

            if($global_sku) // set product status over all (slow , medium ,not moving , high)
            {
                CronUtil::updateProductGlobalStatus($global_sku);
                CronUtil::resetUnsoldProductGlobalStatus($global_sku); // reset to not moving which are not sold last 3 months
            }
        }
        echo "Ended at : " . date('Y-m-d H:i:s');

    }

    public function actionChannelMonthlyTargetCalulcation()  // per month sales of every channel
    {
        echo "Started at : " . date('Y-m-d H:i:s').PHP_EOL;
        $last_year=date("Y",strtotime("-1 year"));
        $channels=Channels::find()->where(['is_active'=>'1'])->all();
        if($channels)
        {
            $data=array();
            foreach($channels as $channel)
            {
               $sales= CronUtil::getMonthlySale($channel->id,$last_year);
               if($sales)
               {
                    for($i=1; $i<=12;$i++)
                    {
                        if(!in_array($i,array_column($sales,'month')))
                        {
                            $sales[]=array('total_sales'=>0,'month'=>$i,'year'=>$last_year,'channel_id'=>$channel->id);
                        }
                    }
                   array_multisort(array_column($sales, 'month'), SORT_ASC, $sales);
                    CronUtil::saveMonthlySale($sales,$channel->id);
               }


            }


        }
        echo "Ended at : " . date('Y-m-d H:i:s');
    }

    public function actionGenStockSalesTbl()
    {
        $connection = Yii::$app->db;
        $sql = "SELECT po.po_final_date as `date`,p.`sku`,pos.`cost_price`,p.id AS sku_id,IF(pos.final_order_qty IS NULL OR pos.final_order_qty = 0 ,pos.order_qty,pos.final_order_qty) AS qty,pos.warehouse
                FROM `po_details` pos 
                INNER JOIN `product_stocks_po` po ON po.id = pos.po_id
                INNER JOIN `products` p ON p.sku = pos.sku
                WHERE po_status = 'shipped' AND po_final_date > '2018-02-28' AND po.is_active = 1 AND pos.parent_sku_id = 0 AND cost_price IS NOT NULL
                ORDER BY sku_id ASC, po_final_date ASC";

        $command = $connection->createCommand($sql);
        $re = $command->queryAll();
        $refine = [];
        $salesLeft = [];
        $firstDate = [];
        foreach ($re as $k) {

            if (!isset($firstDate[$k['sku_id']][0])) {
                $firstDate[$k['sku_id']][0] = $k['date'];
            }

            // check sales
            $sql = "SELECT 
                  COUNT(oi.id) AS sales
                FROM
                  `order_items` oi 
                  INNER JOIN orders o 
                    ON o.`id` = oi.`order_id` 
                WHERE oi.`sku_id` = '{$k['sku_id']}' 
                  AND (
                    oi.`item_status` = 'delivered' || oi.`item_status` = 'ready_to_ship' || oi.`item_status` = 'Shipping in Progress' || oi.`item_status` = 'Payment Complete' || oi.`item_status` = 'Shipped' || oi.`item_status` = 'completed'
                  ) 
                  AND oi.`item_updated_at` >=  '{$firstDate[$k['sku_id']][0]}'
                GROUP BY oi.`sku_id` ";

            $command = $connection->createCommand($sql);
            $re2 = $command->queryOne();
            $sales = ($re2['sales']) ? $re2['sales'] : 0;
            if ($sales > $k['qty'])
                $k['sales'] = $k['qty'];
            else
                $k['sales'] = $sales;
            $salesLeft[$k['sku_id']][] = $sales - $k['sales'];
            //$salesx = $sales +  isset($salesLeft[$k['sku_id']]) ?  array_sum($salesLeft[$k['sku_id']]) : 0;
            $salesx = array_sum($salesLeft[$k['sku_id']]) + $sales;
            if ($salesx > $k['qty'])
                $k['sales'] = $k['qty'];
            else if ($sales > 0)
                $k['sales'] = $salesx;
            else
                $k['sales'] = $sales;
            //$k['salel'] = array_sum($salesLeft[$k['sku_id']]);
            $refine[$k['sku_id']][] = $k;

        }

        SkuStockSales::deleteAll();

        foreach ($refine as $k => $list) {
            foreach ($list as $k) {

                $ss = new SkuStockSales();
                $ss->sku_id = $k['sku_id'];
                $ss->sales = $k['sales'];
                $ss->po_qty = $k['qty'];
                $ss->po_date = $k['date'];
                $ss->to_pick = (($k['qty'] > $k['sales']) && $k['sales'] != 0) ? 1 : 0;
                $ss->price = $k['cost_price'];
                $ss->save(false);
            }
        }
    }

    // stock defination
    public function actionStockDefinitionUpdate()
    {
        $data = [];
        $asDefination = HelpUtil::getAgingStocks(true);
        $ssDefination = HelpUtil::getSellingStatusForSkus();
        $csDefination = HelpUtil::getCurrentStockValue(true);
        foreach ($asDefination as $sku => $d) {
            $data[$sku]['as'] = strtolower($d['status']);
        }
        foreach ($ssDefination as $sku => $d) {
            $data[$sku]['ss'] = strtolower($d['status']);
        }
        foreach ($csDefination as $sku => $d) {
            $data[$sku]['cs'] = strtolower($d['status']);
        }

        foreach ($data as $sku => $d) {
            $sd = StocksDefinations::find()->where(['sku_id' => $sku])->one();
            if (!$sd)
                $sd = new StocksDefinations();
            $sd->sku_id = $sku;
            $sd->aging_status = isset($data[$sku]['as']) ? $data[$sku]['as'] : "slow";
            $sd->stock_status = isset($data[$sku]['cs']) ? $data[$sku]['cs'] : "slow";
            $sd->selling_status = isset($data[$sku]['ss']) ? $data[$sku]['ss'] : "slow";
            $sd->save(false);
        }
        $settings = Settings::find()->where(['name' => 'last_stock_defination_update'])->one();
        if ($settings) {
            $settings->value = date('Y-m-d h:i:s');
            $settings->update();
        }

    }

    // run auto deals sales price sugguestions
    public function actionAutoDealsSuggestions()
    {
        DealsUtil::getLatestAutoPriceMargins();
    }

    // update sales items base on Mega deal start and end date
    public function actionUpdateSalesItemsByDeals()
    {
        if ( isset($_GET['deal_id']) )
            $where = ['id' => $_GET['deal_id'],'is_sale_updated'=>0 ];
        else if(isset($_GET['status']) &&( $_GET['status'] =='active' || $_GET['status'] =='expired')){
            $where = ['status' => $_GET['status'],'is_sale_updated'=>0];
        }else{
            echo "you have to provide deals with a active/expired status";
            return;
        }
        $deals = DealsMaker::find()->where($where)->asArray()->all();
        //var_dump(count($deals)); exit();
        if(count($deals) > 0) {
            echo "started at " . date('h:i:s') . "<br/>";
            foreach ($deals as $d) {
                $dmSkus = DealsMakerSkus::find()->where(['deals_maker_id' => $d['id'], 'status' => 'Approved'])->asArray()->all();
                foreach ($dmSkus as $s) {
                    $oi = OrderItems::find()->joinWith('order o')->where(['o.channel_id' => $d['channel_id']])
                        ->andWhere(['NOT LIKE', 'o.order_id', 'old-'])
                        ->andWhere(['>=', 'o.order_created_at', $d['start_date']])
                        ->andWhere(['<=', 'o.order_created_at', $d['end_date']])
                        ->andWhere(['deal_id' => NULL])
                        ->andWhere(['sku_id' => $s['sku_id']])->asArray()->all();

                    foreach ($oi as $item) {
                        OrderItems::updateAll(['deal_id' => $d['id']], ['id' => $item['id']]);
                    }

                }
                $SkuDealsSalesCountUpdate = $this->actionUpdateActualSalesInDeals($d['id'],$d['status']);
            }
            echo "ended at " . date('h:i:s') . "<br/>";
        }
        else{
            echo "No deal found";
        }
    }

    public function actionBundleSkus()
    {

        $list = Products::findBySql("SELECT pcp.`id`,LEFT(pcp.`sku` , 15) AS skus FROM `products` pcp
WHERE pcp.`is_active` = 1 AND pcp.`sub_category` != '167' #AND pcp.sku like '%SCF796/00%'
AND pcp.skipped = 0 AND pcp.parent IS NULL
ORDER BY skus ASC;")->asArray()->all();
        $clean_list_view = [];
        $BundleDeal = [];
        foreach ($list as $key => $value) {
            $clean_list_view[$value['id']] = $value['skus'];
        }
        return $this->render('skus_mapping', ['skus_list' => $clean_list_view]);
    }

    public function runQuery($sql)
    {
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
    }

    public function actionUpdateChildSkus()
    {
        /**
         * update the parent sku 0 first
         * */
        $connection = Yii::$app->db;
        if (isset($_GET['skip']) && $_GET['skip'] == 1) {
            $connection->createCommand()
                ->update('philips_cost_price', ['skipped' => 1], 'id = ' . $_GET['parent_sku_id'])
                ->execute();
            foreach ($_GET['child_sku_ids'] as $key => $value) {
                $connection->createCommand()
                    ->update('philips_cost_price', ['skipped' => 1], 'id = ' . $key)
                    ->execute();
            }
        } else {
            $connection->createCommand()
                ->update('philips_cost_price', ['parent' => 0], 'id = ' . $_GET['parent_sku_id'])
                ->execute();
            foreach ($_GET['child_sku_ids'] as $key => $value) {
                $connection->createCommand()
                    ->update('philips_cost_price', ['parent' => $_GET['parent_sku_id']], 'id = ' . $key)
                    ->execute();
            }
        }
    }

    // Update subsidy method
    public function actionSubsidyUpdate()
    {
        $GetSubsidy = Subsidy::findBySql('SELECT * FROM subsidy s
WHERE s.sku_id IN ("4","5","6","12","14","19","20","21","34","35","38","42","45","65","68","70","84","89","90","93","95","100","102","107","134","135","136","137","141","147","159","173","180","183","185","186","191","192","201","204","242","259","281","285","286","287","289","300","305","306","309","416","470","634","661","679","680","888")
AND s.channel_id IN (1,2);')->asArray()->all();
        // set -5 subsidy margin
        foreach ($GetSubsidy as $key => $value) {
            $UpdateSubsidy = Subsidy::findOne($value['id']);
            $UpdateSubsidy->ao_margins = '-5';
            $UpdateSubsidy->start_date = date('Y-m-d');
            $UpdateSubsidy->update();
            if (!empty($UpdateSubsidy->errors))
                $this->debug($UpdateSubsidy->errors);


        }
    }

    //
    public function actionRemoveDuplicateOrders()
    {

        $Find_Duplicate_Orders = Orders::findBySql("SELECT o.order_id, COUNT(o.order_id) AS order_count, o.order_created_at AS created_on FROM orders o
                                                        WHERE o.order_created_at BETWEEN '2018-01-01 00:00:00' AND '" . date('Y-m-d h:i:s') . "'
                                                        GROUP BY o.order_id
                                                        HAVING order_count > 1;")->asArray()->all();
        $this->debug($Find_Duplicate_Orders);die;
        $To_Be_Deleted = [];
        foreach ($Find_Duplicate_Orders as $Key => $Value) {
            $Get_Order_Detail = Orders::findBySql("SELECT * FROM orders o WHERE o.order_id = '" . $Value['order_id'] . "'
                                                        ORDER BY o.updated_at ASC")->asArray()->all();
            // $Get_Order_Detail will always have the ascending order by updated_at, So every time latest row will come at the end.
            echo '<pre>';
            print_r($Get_Order_Detail);
            $Real_Order_Index = count($Get_Order_Detail) - 1;
            foreach ($Get_Order_Detail as $Nested_Key => $Nested_Value) {
                if ($Nested_Key == $Real_Order_Index) {
                    continue;
                } else {
                    $To_Be_Deleted[] = $Nested_Value['id'];
                }
            }
        }
        //$this->debug($To_Be_Deleted);
        if (!empty($To_Be_Deleted)) {
            $Order_Ids = implode(',', $To_Be_Deleted);
            $Delete_Duplicated_Orders = Orders::findBySql("DELETE FROM orders WHERE id IN (" . $Order_Ids . ")")->asArray()->all();
            $Delete_Duplicated_Orders_Items = OrderItems::findBySql("DELETE FROM order_items WHERE order_id IN (" . $Order_Ids . ")")->asArray()->all();
            echo "DELETE FROM order_items WHERE order_id IN (" . $Order_Ids . ")";
            die;
        }
        die;
    }
    public function actionLogClaims(){

        /*
         * Reason of making this cron job.
         *
         * This cron job create or update claims of orders. Specially when seller money or inventory is stucking some where.
         * Lets suppose seller shipped an order to customer doorstep but for some reason customer rejected.
         * So ideally it should come back in to the seller inventory, But think if it doesn't come ? Loss of money ? Right ?
         *
         * So when customer will return the order from door order status might be "Returned" then we will pick those type of orders and then we make claims
         * in ezcommerce.
         *
         * On those claims relevent team make sure that the order was returned back to seller inventory and was not missed somewhere.
         * */

        echo 'Started Time : '.date('H:i:s').'<br />';
        //$this->debug($_GET);
        if ( $_GET['marketplace']=='prestashop' )
        {
            /*
             * We pull orders of every last 24 hours to check the claims.
             * We make claims on particular statuses which is set in common/params.php with the name PrestashopClaimsStatuses
             * So only on those statuses our system will make claims on prestashop
             * We dont get reason for the canceled or returned order items in prestashop.
             * */

            $response = CronUtil::LogClaimsPrestashop();

        }elseif ( $_GET['marketplace']=='ebay' )
        {
            /*
             * Shopee Claims
             * Let's suppose we have an order ABCD123
             * Under that order we have 5 items, But we only have the status of the whole order, not by the item wise
             * That is why in shopee the order item status will always be same as the order status.
             *
             * */
            $response = CronUtil::LogClaimsEbay();
        }
        elseif ( $_GET['marketplace']=='lazada' )
        {
            CronUtil::LogClaimsLazada();
        }
        elseif ( $_GET['marketplace']=='shopee' )
        {
            CronUtil::LogClaimsShopee();
        }
        //echo '<pre>';print_r($response);echo '</pre>';
        echo 'Started Time : '.date('H:i:s').'<br />';
        die;
    }
    public function actionGetClaims(){
        echo CronUtil::getClaims($_GET['marketplace']);
        die;
    }
    public function actionUpdateClaimCrmStatus(){

        if ($_GET['marketplace']=='Lazada'){
            $UpdateClaim = ClaimItems::updateAll(array( 'case_in_crm' => 1 ), ' marketplace = \'Lazada\' AND order_item_id = \''.$_GET['order_item_id'].'\' ' );
            echo json_encode($UpdateClaim);
        }elseif ($_GET['marketplace']=='Shopee'){
            $UpdateClaim = ClaimItems::updateAll(array( 'case_in_crm' => 1 ), ' marketplace = \'Shopee\' AND order_item_id = \''.$_GET['order_item_id'].'\' ' );
            echo json_encode($UpdateClaim);
        }

    }
    public function actionCrossCheckPriceEmailNotification(){

        // Get the today list of price update from cross_check_product_prices table for cross checking
        $list = CronUtil::getCrossCheckPrices();
        $csv = "Sku,Channel,Price Should be,Price is,Difference,Added At \n";//Column headers
        foreach ($list as $record){
            $csv.= $record['sku'].','.$record['channel_name'].','.$record['price_should_be'].','.$record['price_is']
                .','.$record['difference'].','.$record['added_at']."\n"; //Append data to csv
        }

        $csv_handler = fopen ('Email-Attachments/price-cross-check/price-update-'.date('Y-m-d').'.csv','w');
        fwrite ($csv_handler,$csv);
        fclose ($csv_handler);

        $send_email=Yii::$app->mailer->compose('@common/mail/layouts/cross-check-price-update-notification', ['list'=>$list])
            ->attach('Email-Attachments/price-cross-check/price-update-'.date('Y-m-d').'.csv')
            ->setFrom('notifications@ezcommerce.io')
            ->setTo('abdullah.khan@axleolio.com')
            ->setCc(['zaheera.begum@axleolio.com','mastan.shaik@axleolio.com','Pieter.anthonius@axleolio.com','laurens.albers@axleolio.com','mujtaba.kiani@axleolio.com'])
            ->setSubject('Product Update Prices - Cross Check')
            ->send();
        die;

    }

    public function actionCrawledPriceSkuNotification(){
        $result = CronUtil::getCrawledSkus();
        $send_email=Yii::$app->mailer->compose('@common/mail/layouts/crawled-price-sku-notification', ['list'=>$result])
            ->setFrom('notifications@ezcommerce.io')
            ->setTo('abdullah.khan@axleolio.com')
            ->setCc(['mastan.shaik@axleolio.com'])
            ->setSubject('Crawled Skus Marketplace')
            ->send();
        //echo $send_email;die;
        die;
    }

    public function actionUpdateActualSalesInDeals($deal_id,$deal_status)
    {
        $progress = [];
        $deals = DealsMaker::getDealById($deal_id);
        foreach($deals as $d) {
            $progress[$d['id']]['target'] = 0;
            $progress[$d['id']]['sales'] = 0;

            $dmSkus = DealsMakerSkus::find()->where(['deals_maker_id' => $d['id'], 'status' => 'Approved'])->all();
            if (count($dmSkus) > 0) {
                foreach ($dmSkus as $k => $v) {
                    $as = HelpUtil::getActualSalesCountBySkuForDeal($v->sku_id, $v->status, $d);
                    $dms = DealsMakerSkus::find()->where(['id' => $v->id])->one();

                    $progress[$d['id']]['target'] += $dms->deal_target;
                    $progress[$d['id']]['sales'] += $as;

                    $dms->actual_sales = $as;
                    if (!$dms->save(false)) {
                        var_dump($dms->getErrors());
                    }
                }

                $progressTotal = 0;
                if ($d['status'] == 'active') {
                    if ($progress[$d['id']]['target'] == 0) {
                        $progressTotal = 100;
                    } else if ($progress[$d['id']]['sales'] == 0) {
                        $progressTotal = 0;
                    } else {
                        $expiry_date = strtotime($d['end_date']);
                        $now = time();
                        $start_date = strtotime($d['start_date']);
                        $Deal_total_days = round(($expiry_date - $start_date) / (60 * 60 * 24));
                        $Deal_today_day = round(($now - $start_date) / (60 * 60 * 24));
                        $Deal_today_day = ($Deal_today_day <= 0) ? 1 : $Deal_today_day;
                        /*Active Deal Formula
                        (Actual Sales to Date/(Total Sales Target/Totals Days of Sales))*Days*/
                        $onestep = ($progress[$d['id']]['target'] / $Deal_total_days);
                        $second = $onestep * $Deal_today_day;
                        $third = $progress[$d['id']]['sales'] / $second;
                        $progressTotal = ($third * 100);
                    }
                    $result = DealsMaker::updateDealProgress($d['id'], $progressTotal,0);

                }
                if ($d['status'] == 'expired') {
                    $progressTotal = 0;
                    if ($progress[$d['id']]['target'] == 0) {
                        $progressTotal = 100;
                    } else if ($progress[$d['id']]['sales'] == 0) {
                        $progressTotal = 0;
                    } else {
                        /*Active Deal Formula
                        (Actual Sales/Total Sales Target)*100 */
                        $onestep = ($progress[$d['id']]['sales'] / $progress[$d['id']]['target']);
                        $progressTotal = ($onestep * 100);
                    }
                    $result = DealsMaker::updateDealProgress($d['id'], $progressTotal,1);
                }
            }else{
                if($deal_status=="expired")
                    $result = DealsMaker::updateDealProgress($d['id'], 0,1);
            }
        }
    }
    public function actionCloneDeal(){
        $deal_id = $_GET['deal_id'];
        $GetDeal=DealsMaker::find()->where(['id'=>$deal_id])->asArray()->all();
        //$this->debug($GetDeal);
        $createDeal = new DealsMaker();
        $createDeal->noAuto=false;
        $createDeal->name=$GetDeal[0]['name'].' - 2';
        $createDeal->channel_id=$GetDeal[0]['channel_id'];
        $createDeal->start_date=date('Y-m-d h:i:s',strtotime('+1 year'));
        $createDeal->end_date=date('Y-m-d h:i:s',strtotime('+1 year'));
        $createDeal->requester_id=$GetDeal[0]['requester_id'];
        $createDeal->motivation=$GetDeal[0]['motivation'];
        $createDeal->status='new';
        $createDeal->pre_approve=$GetDeal[0]['pre_approve'];
        $createDeal->category=$GetDeal[0]['category'];
        $createDeal->created_by=$GetDeal[0]['created_by'];
        $createDeal->updated_by=$GetDeal[0]['updated_by'];
        $createDeal->budget=$GetDeal[0]['budget'];
        $createDeal->save();
        $Clone_deal_id = $createDeal->id;
        $getDealSkus = DealsMakerSkus::find()->where(['deals_maker_id'=>$deal_id])->asArray()->all();
        foreach ( $getDealSkus as $value ){
            $addSkus = new DealsMakerSkus();
            $addSkus->sku_id=$value['sku_id'];
            $addSkus->deals_maker_id=$Clone_deal_id;
            $addSkus->deal_price=$value['deal_price'];
            $addSkus->deal_subsidy=$value['deal_subsidy'];
            $addSkus->deal_target=$value['deal_target'];
            $addSkus->deal_margin=$value['deal_margin'];
            $addSkus->deal_margin_rm=$value['deal_margin_rm'];
            $addSkus->requestor_reason=$value['requestor_reason'];
            $addSkus->approval_id=$value['approval_id'];
            $addSkus->status=$value['status'];
            $addSkus->approval_comments=$value['approval_comments'];
            $addSkus->actual_sales=$value['actual_sales'];
            $addSkus->save();

        }
        //$createDeal->name=$GetDeal->
    }
    public function actionArchiveAoPricing(){

        $Current_Date = date('Y-m-d');
        $Last_Three_Month_Date = date('Y-m-d', strtotime("-3 month"));

        $Archive = Pricing::find()->where(['added_at'=>$Last_Three_Month_Date])->asArray()->all();
        $Total_Archive = count($Archive);
        if ($Total_Archive==0){
            echo 'Already Archived For the date : '.$Last_Three_Month_Date.'. Try it tomorrow. Cheers !!!';
            die;
        }
        $connection = Yii::$app->getDb();


        $command = $connection->createCommand("INSERT INTO ao_pricing_archive SELECT * FROM ao_pricing WHERE added_at = :added_at",
            [':added_at' => $Last_Three_Month_Date]);


        try {
            $result = $command->execute();
        }
        catch(\yii\db\Exception $e) {
            $result = 'Message: ' .$e->getMessage();
        }

        if ( $Total_Archive == $result ){

            $Delete_From_Ao_Pricing = $connection->createCommand("DELETE FROM ao_pricing
                                                                      WHERE added_at = :added_at ",
                [':added_at' => $Last_Three_Month_Date]);
            $Delete_Result = $Delete_From_Ao_Pricing->execute();

            $send_email=Yii::$app->mailer->compose()
                ->setFrom('notifications@ezcommerce.io')
                ->setTo('abdullah.khan@axleolio.com')
                ->setTextBody('Hi, Ao Pricing archive is successfuly archived with '.$result.' records.
                And '.$Delete_Result.' Deleted from the ao_pricing of '.$Current_Date.' date')
                ->setSubject('Ao Pricing Archive SuccessFully Done')
                ->send();
        }else{
            $send_email=Yii::$app->mailer->compose()
                ->setFrom('notifications@ezcommerce.io')
                ->setTo('abdullah.khan@axleolio.com')
                ->setTextBody('Hi, Error is coming when attempting to archive for Ao Pricing. Here is the error detail'.$result)
                ->setSubject('Ao Pricing Archive Failed')
                ->send();
        }
        die;
    }
    public function actionArchiveCompetitivePricing(){

        $Current_Date = date('Y-m-d');
        $Last_Three_Month_Date = date('Y-m-d', strtotime("-3 month"));

        $Archive = CompetitivePricing::find()->where(['created_at'=>$Last_Three_Month_Date])->asArray()->all();
        $Total_Archive = count($Archive);
        if ($Total_Archive==0){
            echo '<h2>Already Archived For the date : '.$Last_Three_Month_Date.'. Try it tomorrow. Cheers !!!</h2>';
            die;
        }
        $connection = Yii::$app->getDb();


        $command = $connection->createCommand("INSERT INTO competitive_pricing_archive SELECT * FROM competitive_pricing WHERE created_at = :created_at",
            [':created_at' => $Last_Three_Month_Date]);


        try {
            $result = $command->execute();
        }
        catch(\yii\db\Exception $e) {
            $result = 'Message: ' .$e->getMessage();
        }

        if ( $Total_Archive == $result ){

            $Delete_From_Ao_Pricing = $connection->createCommand("DELETE FROM competitive_pricing
                                                                      WHERE created_at = :created_at ",
                [':created_at' => $Last_Three_Month_Date]);
            $Delete_Result = $Delete_From_Ao_Pricing->execute();

            $send_email=Yii::$app->mailer->compose()
                ->setFrom('notifications@ezcommerce.io')
                ->setTo('abdullah.khan@axleolio.com')
                ->setTextBody('Hi, Competitive archive is successfuly archived with '.$result.' records.
                And '.$Delete_Result.' Deleted from the ao_pricing of '.$Current_Date.' date')
                ->setSubject('Ao Pricing Archive SuccessFully Done')
                ->send();
        }else{
            $send_email=Yii::$app->mailer->compose()
                ->setFrom('notifications@ezcommerce.io')
                ->setTo('abdullah.khan@axleolio.com')
                ->setTextBody('Hi, Error is coming when attempting to archive for Competitive archive. Here is the error detail'.$result)
                ->setSubject('Competitive archive Archive Failed')
                ->send();
        }
        die;
    }
    public function actionRunMe(){
        $awb = 2.2550119123295E+14;
        $var = number_format($awb,0,'','');
        var_dump($var);
    }
    public function actionSyncProduct360Lazada(){
        if (!isset($_GET['channel_id'])){
            echo '<h2>Channel id is required, 1=> Lazada Blip & 15 for Avent';
            die;
        }

        $channel_id = $_GET['channel_id'];
        //$Products = LazadaUtil::GetProducts(1,'all',0);
        //$this->debug($Products);
        $GetAllProducts = LazadaUtil::GetAllProducts($channel_id,'all',true,[],(isset($_GET['search']) ? $_GET['search'] : ''));
        //$this->debug($GetAllProducts);
         //$Products = $Product['data']['products'];
        //$this->debug($GetAllProducts);
        foreach ( $GetAllProducts as $value ) :

            $uqid = uniqid();


            $value = LazadaUtil::GetProductItem($channel_id,'',$value['item_id'])['data'];


            foreach ( $value['skus'] as $p_s_key=>$p_s_detail ) :
                if ($p_s_key==0){
                    /*echo '<pre>';
                    print_r($p_s_detail);*/
                    $Sku = $p_s_detail['SellerSku'];



                    // Check already exist in ezcommerce
                    $SkuCheck = Products::find()->where(['sku'=>$Sku])->asArray()->all();
                    if ( !empty($SkuCheck) ) :
                        echo '<h4>Product ('.$Sku.') Already exist in our Ezcommerce. And the product is '.$SkuCheck[0]['is_active'].'</h4>';
                    else:
                        echo 'Ohhhhhh !!!! Product not found in ezcommerce for the sku id : "'.$Sku.'". Im skipping this sku. Bye';
                        echo '<br />';
                        continue;

                    endif;

                    // Check in product 360 tables
                    $Sku360Field = Products360Fields::find()->where(['product_id'=>$SkuCheck[0]['id']])->asArray()->all();
                    if ( !empty($Sku360Field) ) :
                        echo '<h4>Product Already exist in Product360 tables</h4>';
                    endif;

                    // dump product details in products_360_fields table.
                    if ( empty($Sku360Field) ) :
                        $AddField = new Products360Fields();
                        $AddField->product_id=$SkuCheck[0]['id'];
                        $AddField->status = 'Publish';
                        $AddField->name = $SkuCheck[0]['name'];
                        $AddField->sku = $SkuCheck[0]['sku'];
                        $AddField->category = $SkuCheck[0]['sub_category'];
                        $AddField->save(false);
                        $Sku360Field[0]['id'] = $AddField->id;
                        //die;
                    endif;

                    echo '<pre>';
                    //print_r($value);
                    $GetAttributes = LazadaUtil::GetCategoryAttributes($channel_id,$value['primary_category']);

                    $attr = [];
                    $skipAttr = ['name','short_description','model','color_family','SellerSku','quantity','special_from_date','special_from_date',
                        'special_to_date','package_weight','package_length','package_width','package_height','__images__','',''];
                    $attrSku = ['package_content'=>'','tax_class'=>'default'];

                    foreach ($GetAttributes['data'] as $AttrVal){
                        if ( in_array($AttrVal['name'],$skipAttr) )
                            continue;
                        $attr[$AttrVal['name']] = '';
                    }
                    foreach ( $value['attributes'] as $k=>$val ){
                        if ( !isset($attr[$k]) )
                            continue;
                        $attr[$k] = $val;
                    }



                    $CheckStatus = Product360Status::find()->where(['product_360_fieldS_id'=>$Sku360Field[0]['id']])->andWhere(['shop_id'=>$channel_id])->asArray()->all();

                    if ( empty( $CheckStatus ) ){
                        $path = 'product_images/' . $uqid.'/mainimage';
                        FileHelper::createDirectory($path);
                        chmod($path, 0777);
                        $images = [];
                        foreach ($p_s_detail['Images'] as $Images_Links){
                            if ( $Images_Links=="" )
                                continue;
                            $image_parts = explode('/',$Images_Links);
                            $content = file_get_contents($Images_Links);
                            file_put_contents($path.'/'.end($image_parts), $content);
                            $images[] = $uqid.'/mainimage/'.end($image_parts);
                        }


                        $request = [
                            'uqid' => $uqid,
                            'p360' => [
                                'lzd_attributes' => [ 'normal' => $attr, 'sku' => $attrSku ],
                                'sys_category' => $SkuCheck[0]['sub_category'],
                                'lzd_category' => $value['primary_category']
                            ]
                        ];
                        $AddStatus = new Product360Status();
                        $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];
                        $AddStatus->status = ( $p_s_detail['Status']=='active' ) ? 'Success' : 'DeActivated';
                        $AddStatus->shop_id = $channel_id;
                        $AddStatus->show = 1;
                        $AddStatus->api_response = json_encode($value);
                        $AddStatus->api_request = json_encode($request);
                        $AddStatus->item_id = $value['item_id'];
                        $AddStatus->images = implode(',',$images);
                        $AddStatus->save();
                        if (!empty($AddStatus->errors))
                            $this->debug($AddStatus->errors);

                    }else{
                        // remove all directores first
                        $uqid = json_decode($CheckStatus[0]['api_request'],true)['uqid'];
                        $AlreadyExsitDirectories=FileHelper::findDirectories($path = 'product_images/' . $uqid.'');
                        $this->RemoveDirectories($AlreadyExsitDirectories);

                        $path = 'product_images/' . $uqid.'/mainimage';
                        FileHelper::createDirectory($path);
                        chmod($path, 0777);
                        $images = [];
                        foreach ($p_s_detail['Images'] as $Images_Links){
                            if ( $Images_Links=="" )
                                continue;
                            $image_parts = explode('/',$Images_Links);
                            $content = file_get_contents($Images_Links);
                            file_put_contents($path.'/'.end($image_parts), $content);
                            $images[] = $uqid.'/mainimage/'.end($image_parts);
                        }


                        $request = [
                            'uqid' => $uqid,
                            'p360' => [
                                'lzd_attributes' => [ 'normal' => $attr, 'sku' => $attrSku ],
                                'sys_category' => $SkuCheck[0]['sub_category'],
                                'lzd_category' => $value['primary_category']
                            ]
                        ];
                        $AddStatus = Product360Status::findOne($CheckStatus[0]['id']);
                        $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];
                        $AddStatus->status = ( $p_s_detail['Status']=='active' ) ? 'Success' : 'DeActivated';
                        $AddStatus->shop_id = $channel_id;
                        $AddStatus->show = 1;
                        $AddStatus->api_response = json_encode($value);
                        $AddStatus->api_request = json_encode($request);
                        $AddStatus->item_id = $value['item_id'];
                        $AddStatus->images = implode(',',$images);
                        $AddStatus->save();
                        if (!empty($AddStatus->errors))
                            $this->debug($AddStatus->errors);
                    }


                }
                else{
                    //$this->debug($AddStatus);
                    if (!isset($AddStatus))
                        $this->debug($p_s_detail);
                    $getStatusRow = Product360Status::find()->where(['id'=>$AddStatus['id']])->asArray()->all();

                    $Request_Data = $getStatusRow[0]['api_request'];
                    $Request_Data = json_decode($Request_Data,true);
                    //echo $Request_Data['uqid'];die;

                    $path = 'product_images/' . $Request_Data['uqid'].'/variation-'.$p_s_key;
                    FileHelper::createDirectory($path);
                    chmod($path, 0777);
                    $images = [];
                    foreach ($p_s_detail['Images'] as $Images_Links){
                        if ( $Images_Links=="" )
                            continue;
                        $image_parts = explode('/',$Images_Links);
                        $content = file_get_contents($Images_Links);
                        file_put_contents($path.'/'.end($image_parts), $content);
                        $images[] = $uqid.'/variation-'.$p_s_key.'/'.end($image_parts);
                    }
                    echo '<pre>';
                    //print_r($Request_Data);
                    //print_r($p_s_detail);
                    //print_r($images);

                    $Request_Data['p360']['variations'][$p_s_key]['type']['Color'] = $p_s_detail['color_family'];
                    $Request_Data['p360']['variations'][$p_s_key]['price'] = $p_s_detail['price'];
                    $Request_Data['p360']['variations'][$p_s_key]['rccp'] = $p_s_detail['special_price'];
                    $Request_Data['p360']['variations'][$p_s_key]['stock'] = $p_s_detail['Available'];
                    $Request_Data['p360']['variations'][$p_s_key]['sku'] = $p_s_detail['SellerSku'];
                    $Request_Data['p360']['variations'][$p_s_key]['images'] = $images;

                    $UpdateStatus = Product360Status::findOne($getStatusRow[0]['id']);
                    $UpdateStatus->api_request = json_encode($Request_Data);
                    $UpdateStatus->update();

                    //$this->debug($Request_Data);
                }
                echo '<br />';
                echo '-----------------xxxxxxxxxxxxxxxxxxxxx--------------------xxxxxxxxxxxxxxxxxxxxxxxxxxx------------------------';
                echo '<br />';
            endforeach;
        endforeach;
        die;
    }
    public function actionSyncProduct360Shopee(){
        if (!isset($_GET['channel_id'])){
            echo '<h2>Channel id is required for example 2 for shopee';
            die;
        }

        $channel_id = $_GET['channel_id'];
        //$Products = LazadaUtil::GetProducts(1,'all',0);
        //$this->debug($Products);
        $GetAllProducts = json_decode(ShopeeUtil::GetAllItemsList($channel_id));
        if ( isset($_GET['item_id']) && $_GET['item_id']!='' ){
            $FilterProducts = [];
            foreach ( $GetAllProducts as $value ){
                if ( $value->item_id == '2582219392' )
                    $FilterProducts[] = $value;
            }
        }

        //$Products = $Product['data']['products'];
        //$this->debug($GetAllProducts);
        foreach ( $GetAllProducts as $value ) :

            $uqid = uniqid();

            $value = ShopeeUtil::GetItemDetail($channel_id,$value->item_id );
            /*$value = ShopeeUtil::GetItemDetail($channel_id,2537126791 );*/
            //$this->debug($value);



            //foreach ( $value['item'] as $p_s_key=>$p_s_detail ) :


            $Sku = $value['item']['item_sku'];


            // Check already exist in ezcommerce
            $SkuCheck = Products::find()->where(['sku'=>$Sku])->asArray()->all();
            if ( !empty($SkuCheck) ) :
                echo '<h4>Product ('.$Sku.') Already exist in our Ezcommerce. And the product is '.$SkuCheck[0]['is_active'].'</h4>';
            else:
                echo 'Ohhhhhh !!!! Product not found in ezcommerce for the sku id : "'.$Sku.'". Im skipping this sku. Bye';
            echo '<br />';
            continue;

            endif;

            // Check in product 360 tables
            $Sku360Field = Products360Fields::find()->where(['product_id'=>$SkuCheck[0]['id']])->asArray()->all();
            if ( !empty($Sku360Field) ) :
                echo '<h4>Product Already exist in Product360 tables</h4>';
            endif;

            // dump product details in products_360_fields table.
            if ( empty($Sku360Field) ) :
                $AddField = new Products360Fields();
                $AddField->product_id=$SkuCheck[0]['id'];
                $AddField->status = 'Publish';
                $AddField->name = $SkuCheck[0]['name'];
                $AddField->sku = $SkuCheck[0]['sku'];
                $AddField->category = $SkuCheck[0]['sub_category'];
                $AddField->save(false);
                $Sku360Field[0]['id'] = $AddField->id;
                //die;
            endif;

            echo '<pre>';
            //print_r($value);


            $attr = [];
            $attr['shpe_logistics'] = $value['item']['logistics'][0]['logistic_id'];
            foreach ( $value['item']['attributes'] as $key=>$val ){
                $attr[] = $val['attribute_id'].'-'.$val['attribute_value'];
            }
            //$this->debug($attr);



                    $CheckStatus = Product360Status::find()->where(['product_360_fieldS_id'=>$Sku360Field[0]['id']])->andWhere(['shop_id'=>$channel_id])->asArray()->all();

                    if ( empty( $CheckStatus ) ){
                        $path = 'product_images/' . $uqid.'/mainimage';
                        FileHelper::createDirectory($path);
                        chmod($path, 0777);
                        $images = [];
                        foreach ($value['item']['images'] as $Images_Links){
                            if ( $Images_Links=="" )
                                continue;
                            $image_parts = explode('/',$Images_Links);
                            $content = file_get_contents($Images_Links);
                            file_put_contents($path.'/'.end($image_parts), $content);
                            $images[] = $uqid.'/mainimage/'.end($image_parts);
                        }


                        $request = [
                            'uqid' => $uqid,
                            'p360' => [
                                'shopee_attributes' => $attr,
                                'sys_category' => $SkuCheck[0]['sub_category'],
                                'shope_category' => $value['item']['category_id']
                            ]
                        ];
                        $AddStatus = new Product360Status();
                        $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];

                        if ($value['item']['status']=='NORMAL')
                            $status = 'Activated';
                        elseif ( $value['item']['status']=='UNLIST' )
                            $status = 'DeActivated';

                        $AddStatus->status = $status;
                        $AddStatus->shop_id = $channel_id;
                        $AddStatus->show = 1;
                        $AddStatus->api_response = json_encode($value);
                        $AddStatus->api_request = json_encode($request);
                        $AddStatus->item_id = $value['item']['item_id'];
                        $AddStatus->images = implode(',',$images);
                        $AddStatus->save();
                        if (!empty($AddStatus->errors))
                            $this->debug($AddStatus->errors);

                    }
                    else{
                        // remove all directores first
                        $uqid = json_decode($CheckStatus[0]['api_request'],true)['uqid'];
                        $AlreadyExsitDirectories=FileHelper::findDirectories($path = 'product_images/' . $uqid.'');
                        $this->RemoveDirectories($AlreadyExsitDirectories);

                        $path = 'product_images/' . $uqid.'/mainimage';
                        FileHelper::createDirectory($path);
                        chmod($path, 0777);
                        $images = [];
                        foreach ($value['item']['images'] as $Images_Links){
                            if ( $Images_Links=="" )
                                continue;
                            $image_parts = explode('/',$Images_Links);
                            $content = file_get_contents($Images_Links);
                            file_put_contents($path.'/'.end($image_parts), $content);
                            $images[] = $uqid.'/mainimage/'.end($image_parts);
                        }


                        $request = [
                            'uqid' => $uqid,
                            'p360' => [
                                'shopee_attributes' => $attr,
                                'sys_category' => $SkuCheck[0]['sub_category'],
                                'shope_category' => $value['item']['category_id']
                            ]
                        ];
                        $AddStatus = Product360Status::findOne($CheckStatus[0]['id']);
                        $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];

                        if ($value['item']['status']=='NORMAL')
                            $status = 'Activated';
                        elseif ( $value['item']['status']=='UNLIST' )
                            $status = 'DeActivated';

                        $AddStatus->status = $status;
                        $AddStatus->shop_id = $channel_id;
                        $AddStatus->show = 1;
                        $AddStatus->api_response = json_encode($value);
                        $AddStatus->api_request = json_encode($request);
                        $AddStatus->item_id = $value['item']['item_id'];
                        $AddStatus->images = implode(',',$images);
                        $AddStatus->save();
                        if (!empty($AddStatus->errors))
                            $this->debug($AddStatus->errors);
                    }


                $counter = 1;
                foreach ( $value['item']['variations'] as $variation_data ){
                    //$this->debug($value['item']);
                    //$this->debug($AddStatus);
                    if (!isset($AddStatus))
                        echo 'Status row not found, How to add variation if i will not have the id of already created status row ?????? lol';
                    $getStatusRow = Product360Status::find()->where(['id'=>$AddStatus['id']])->asArray()->all();

                    $Request_Data = $getStatusRow[0]['api_request'];
                    $Request_Data = json_decode($Request_Data,true);
                    //echo $Request_Data['uqid'];die;



                    $Request_Data['p360']['variations'][$counter]['type']['Color'] = $variation_data['name'];
                    $Request_Data['p360']['variations'][$counter]['price'] = $variation_data['price'];
                    $Request_Data['p360']['variations'][$counter]['rccp'] = $variation_data['original_price'];
                    $Request_Data['p360']['variations'][$counter]['stock'] = $variation_data['stock'];
                    $Request_Data['p360']['variations'][$counter]['sku'] = $variation_data['stock'];
                    $Request_Data['p360']['variations'][$counter]['images'] = [];

                    $UpdateStatus = Product360Status::findOne($getStatusRow[0]['id']);
                    $UpdateStatus->api_request = json_encode($Request_Data);
                    $UpdateStatus->update();

                    //$this->debug($Request_Data);
                    $counter++;
                }

                echo '<br />';
                echo '-----------------xxxxxxxxxxxxxxxxxxxxx--------------------xxxxxxxxxxxxxxxxxxxxxxxxxxx------------------------';
                echo '<br />';
            //endforeach;
            //die;
        endforeach;
        die;
    }
    private function categoryExist($cat_name){
        $getCat = Category::find()->where(['name'=>$cat_name])->asArray()->all();
        if (isset($getCat[0]['id'])){
            return $getCat;
        }else{
            return 0;
        }
    }

    public static function actionPullCategoriesApi()
    {
        echo "started at  ". date('Y-m-d H:i:s') . "<br/>";
        if(isset($_GET['channel-name']))
        {
            $channel = Channels::findone(['name'=>$_GET['channel-name'],'is_active' => 1]);
            if($channel)
            {
              if($channel->marketplace=="magento")
              {
                    MagentoUtil::ChannelCategories($channel);
              }
              if($channel->marketplace=="prestashop")
              {
                    PrestashopUtil::pullPrestashopCategories($channel);
              }
              if($channel->marketplace == "ebay")
              {
                    EbayUtil::getEbaycategories($channel);
              }
              if($channel->marketplace == "shopify")
              {
                    ShopifyUtil::ChannelCategories($channel);
              }
             if($channel->marketplace == "daraz")
             {
                 DarazUtil::channelCategories($channel);
             }
            }
        }
        echo "ended at " .date('Y-m-d H:i:s');
        return;

    }

   /* public function actionPullPrestashopCategories(){

        echo 'Start time : '.date('h:i:s');
        $channel_id = 16;
        $category = PrestashopUtil::GetCategories($channel_id);
        $category = json_decode($category);



        foreach ( $category->category->category as $key=>$value ){



            $find_cat = Category::find()->where(['name'=>$value->name->language])->one();

            if (!$find_cat){
                $create_category = new Category();
                $create_category->name = $value->name->language;
                $create_category->is_active = ($value->active == 1) ? 1 : 0;
                $create_category->main_category_id = 0;
                $create_category->created_at = time();
                $create_category->updated_at = time();
                $create_category->save();
                $Parent_category = $create_category['id'];
            }else{
                $Parent_category = $find_cat->id;
            }

            $find_ref_keys = GeneralReferenceKeys::find()->where(['table_name'=>'category','key'=>'presta_category_id','table_pk'=>$Parent_category,
                'channel_id'=>$channel_id])->all();

            if( !$find_ref_keys ){
                // add reference key
                $add_presta_cat_id = new GeneralReferenceKeys();
                $add_presta_cat_id->channel_id = $channel_id;
                $add_presta_cat_id->table_name = 'category';
                $add_presta_cat_id->table_pk = $Parent_category;
                $add_presta_cat_id->key = 'presta_category_id';
                $add_presta_cat_id->value = $value->id;
                $add_presta_cat_id->added_at = date('Y-m-d H:i:s');
                $add_presta_cat_id->save();
            }

            if ( isset($value->associations->category->category) ){
                foreach ( $value->associations->category->category as $category_id ){

                    $presta_category_id = (gettype($category_id) == 'object') ? $category_id->id : $category_id;
                    $child_category = PrestashopUtil::GetCategories($channel_id,['filter[id]'=>$category_id]);
                    $child_category = json_decode($child_category);

                    if (!isset($child_category->category->category->name->language)){
                        continue;
                    }
                    $child_category_name = $child_category->category->category->name->language;


                    $find_child_cat = Category::find()->where(['name'=>$child_category_name])->one();

                    if (!$find_child_cat){
                        $create_child_category = new Category();
                        $create_child_category->name = $child_category_name;
                        $create_child_category->is_active = ($child_category->category->category->active == 1) ? 1 : 0;
                        $create_child_category->main_category_id = $Parent_category;
                        $create_child_category->created_at = time();
                        $create_child_category->updated_at = time();
                        $create_child_category->save();
                        if (empty($create_child_category->errors))
                            $this->debug($create_child_category->errors);
                        $child_cat_id=$create_child_category['id'];
                    }else{
                        $child_cat_id = $find_child_cat->id;
                    }
                    $find_child_reference = GeneralReferenceKeys::find()->where(['table_name'=>'category','key'=>'presta_category_id','table_pk'=>$child_cat_id,
                        'channel_id'=>$channel_id])->all();
                    if (!$find_child_reference){
                        $add_presta_cat_id = new GeneralReferenceKeys();
                        $add_presta_cat_id->channel_id = $channel_id;
                        $add_presta_cat_id->table_name = 'category';
                        $add_presta_cat_id->table_pk = $child_cat_id;
                        $add_presta_cat_id->key = 'presta_category_id';
                        $add_presta_cat_id->value = $presta_category_id;
                        $add_presta_cat_id->added_at = date('Y-m-d H:i:s');
                        $add_presta_cat_id->save();
                    }


                }
            }


        }


    }*/

    public function actionPrestaCategories(){
        $json = '{"products":{"product":{"id":"784","id_manufacturer":"0","id_supplier":"0","id_category_default":"0","new":{},"cache_default_attribute":"0","id_default_image":"2072","id_default_combination":{"@attributes":{"notFilterable":"true"}},"id_tax_rules_group":"0","position_in_category":{"@attributes":{"notFilterable":"true"}},"manufacturer_name":{"@attributes":{"notFilterable":"true"}},"quantity":"0","type":"simple","id_shop_default":"1","reference":"PRODUCT360","supplier_reference":{},"location":{},"width":"8.000000","height":"11.000000","depth":"12.000000","weight":"11.000000","quantity_discount":"0","ean13":"0900292939239","isbn":{},"upc":"92290293","cache_is_pack":"0","cache_has_attachments":"0","is_virtual":"0","state":"1","additional_delivery_times":"0","delivery_in_stock":{"language":{"@attributes":{"id":"1"}}},"delivery_out_stock":{"language":{"@attributes":{"id":"1"}}},"on_sale":"1","online_only":"0","ecotax":"0.000000","minimal_quantity":"0","low_stock_threshold":"0","low_stock_alert":"0","price":"2500.000000","wholesale_price":"1999.000000","unity":"10","unit_price_ratio":"0.000000","additional_shipping_cost":"10.00","customizable":"0","text_fields":"0","uploadable_files":"0","active":"1","redirect_type":{},"id_type_redirected":"0","available_for_order":"0","available_date":"0000-00-00","show_condition":"0","condition":"refurbished","show_price":"1","indexed":"1","visibility":"both","advanced_stock_management":"1","date_add":"2019-08-19 04:55:47","date_upd":"2019-08-19 04:55:47","pack_stock_type":"0","meta_description":{"language":{"@attributes":{"id":"1"}}},"meta_keywords":{"language":{"@attributes":{"id":"1"}}},"meta_title":{"language":{"@attributes":{"id":"1"}}},"link_rewrite":{"language":"testing-product-details"},"name":{"language":"TESTING PRODUCT DETAILS"},"description":{"language":"<p>Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0Hello man how are you\u00a0<\/p>\n"},"description_short":{"language":"<p>Hello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are youHello mr how are you<\/p>\n"},"available_now":{"language":{"@attributes":{"id":"1"}}},"available_later":{"language":{"@attributes":{"id":"1"}}},"associations":{"category":{"@attributes":{"nodeType":"category","api":"category"},"category":{"id":"177"}},"images":{"@attributes":{"nodeType":"image","api":"images"},"image":[{"id":"2072"},{"id":"2073"},{"id":"2074"}]},"combinations":{"@attributes":{"nodeType":"combination","api":"combinations"}},"product_option_values":{"@attributes":{"nodeType":"product_option_value","api":"product_option_values"}},"product_features":{"@attributes":{"nodeType":"product_feature","api":"product_features"},"product_feature":{"id":"0","id_feature_value":"0"}},"tags":{"@attributes":{"nodeType":"tag","api":"tags"},"tag":{"id":"0"}},"stock_availables":{"@attributes":{"nodeType":"stock_available","api":"stock_availables"},"stock_available":{"id":"1805","id_product_attribute":"0"}},"accessories":{"@attributes":{"nodeType":"product","api":"products"}},"product_bundle":{"@attributes":{"nodeType":"product","api":"products"}}}}}}';
        $this->debug(json_decode($json));
        $Tax_Groups=json_decode(PrestashopUtil::GetTaxRuleGroups(16));
    }
    public function actionDeletePrestaImage(){
        $deleteImage = PrestashopUtil::DeletePrestashopProductImages(16,787,2310);
        $this->debug($deleteImage);
    }

    public function actionWarehouseStockSync(){
        echo "started at " . date('Y-m-d H:i:s') . "<br/>";

        if(isset($_GET['prefix']) && isset($_GET['prefix']))
            $warehouse = Warehouses::find()->where(['prefix'=>$_GET['prefix'],'is_active'=>1])->all();
        elseif (isset($_GET['warehouse']) && isset($_GET['name']))
            $warehouse = Warehouses::find()->where(['warehouse'=>$_GET['warehouse'],'is_active'=>1,'name'=>$_GET['name']])->all();
        else
            $warehouse = Warehouses::find()->where(['warehouse'=>$_GET['warehouse'],'is_active'=>1])->all();


        if ($warehouse)
        {

            foreach ($warehouse as $value) {

                if ( $value->warehouse == 'istoreisend'  ){

                    $sku_list   = IsisUtil::GetSkuStocks( $value->id ); // Get list from istoreisend

                    $setFormat  = WarehouseController::IsisStockSetFormat($sku_list); // set the format to our standard

                    $saveStocks = WarehouseController::WarehouseSaveStocks($value->id,$setFormat,true); // save or update
                    WarehouseController::saveLog($value->id, $saveStocks); // save log


                } elseif ( $value->warehouse == 'skuvault'  ){
                    // get warehouse unique code of SkuVault generated from our General reference key table first.
                    // $getSkuVaultWarehouseId = GeneralReferenceKeys::find()->where(['table_name'=>'warehouses','table_pk'=>$value->id,'key'=>'SkuVault_Warehouse_Id'])->one();
                    $code=json_decode($value['configuration']);
                    if(!isset($code->code))
                        die('Failed to get credentials');

                    $params =[];
                    $params['WarehouseId'] = $code->code;
                    $sku_list   = SkuVault::getWarehouseItemQuantities( $value->id, $params ); // Get list from SkuVault

                    $warehouse_stock  = WarehouseController::SkuVaultStockSetFormat($sku_list); // set the format to our standard
                    //$this->debug($warehouse_stock);
                    if($warehouse_stock) // to handle pending stock qty for redford
                    {
                        $all_warehouses_stock= SkuVault::getAllWarehouseItemQuantities( $value->id, $params ); // get stock of all warehouses collective

                        $all_Warehouses_pending_stock=SkuVault::make_pending_stock_list($all_warehouses_stock);
                        $warehouse_stock=SkuVault::make_final_stock_list($warehouse_stock ,$all_Warehouses_pending_stock);
                    }

                    $saveStocks = WarehouseController::WarehouseSaveStocks($value->id,$warehouse_stock,true); // save or update
                    WarehouseController::saveLog($value->id, $saveStocks); // save log

                }
                elseif ( $value->warehouse == 'lazada-fbl' ){

                    // We only pull stock numbers from Fbl we don't push it anywhere
                    $Channel = WarehouseUtil::GetFblWarehousesChannels();
                    //$this->debug($Channel);

                    foreach ( $Channel as $ChannelDetail ){

                        // get products stock of ezcommerce
                        // get Lazada fbl stocks
                        $LazadaFblStocks=WarehouseUtil::GetLazadaProductsStocksFbl($ChannelDetail['channel_id']);
                      //  self::debug($LazadaFblStocks);
                        // update stocks of FBL into ezcomm
                        $saveStocks = WarehouseController::WarehouseSaveStocks($ChannelDetail['warehouse_id'],$LazadaFblStocks,true); // save or update
                        WarehouseController::saveLog($ChannelDetail['warehouse_id'], $saveStocks); // save log

                    }
                    break;
                }
                elseif ( $value->warehouse == 'amazon-fba' )
                {
                    $Channel = WarehouseUtil::GetFbaWarehousesChannels($value->id);
                   // $this->debug($Channel);
                    foreach ( $Channel as $ChannelDetail )
                    {
                        if($ChannelDetail['marketplace']=='amazonspa')  // new amazon api //seller partner api
                        {
                            $channel = Channels::findone(['id'=>$ChannelDetail['channel_id'],'is_active'=>1]);
                            //self::debug($channel);
                            $amz_stock=AmazonSellerPartnerUtil::getFbaInventory($channel);
                            //$this->debug($amz_stock);
                            $saveStocks = WarehouseController::WarehouseSaveStocks($ChannelDetail['warehouse_id'],$amz_stock,true); // save or update
                            WarehouseController::saveLog($ChannelDetail['warehouse_id'], $saveStocks); // save log
                        }
                        else{  // old amazon api //mws
                            // get Amazon fba stocks
                            $AmazonFbaStocks=WarehouseUtil::GetAmazonProductsStocksFba($ChannelDetail['channel_id']);
                            $saveStocks = WarehouseController::WarehouseSaveStocks($ChannelDetail['warehouse_id'],$AmazonFbaStocks,true); // save or update
                            WarehouseController::saveLog($ChannelDetail['warehouse_id'], $saveStocks); // save log
                        }

                    }

                }
                elseif ( $value->warehouse == 'dolibarr' ){

                    $sku_list   = DolibarrUtil::GetAllSkuStocks( $value ); // Get list from dolibarr
                    if ( empty($sku_list) ) // if dolibarr array is empty then mark all stock=0
                    {
                        $update=WarehouseStockList::findOne(['warehouse_id'=>$value->id]);
                        $update->available='0';
                        $update->update();
                    }

                    $setFormat  = DolibarrUtil::DolibarrStockSetFormat($sku_list); // set the format to our standard
                    $saveStocks = WarehouseController::WarehouseSaveStocks($value->id,$setFormat,true); // save or update
                    WarehouseController::saveLog($value->id, $saveStocks); // save log
                }
                elseif ( $value->warehouse == 'quickbooks')
                {
                       // die('come');
                    $sku_list = QuickbookUtil::getStock($value); // get list qiuckbooks
                   // self::debug($sku_list);
                    if($sku_list['status']=='success')
                    {
                        $setFormat  = QuickbookUtil::SkuStockSetFormat($sku_list['products']); // set the format to our standard
                        $saveStocks = WarehouseController::WarehouseSaveStocks($value->id, $setFormat,true); // save or update
                        WarehouseController::saveLog($value->id, $saveStocks); // save log
                    }elseif(isset($sku_list['error']))
                         WarehouseController::saveLog($value->id, $sku_list['error']); // save log
                }
                elseif ( $value->warehouse == 'sage')
                {
                    $sku_list = SageUtil::getStock($value); // get list qiuckbooks
                    if ($sku_list['status']=='success')
                    {

                        $setFormat  = SageUtil::SkuStockSetFormat($sku_list); // set the format to our standard
                        $saveStocks = WarehouseController::WarehouseSaveStocks($value->id, $setFormat,true); // save or update
                        WarehouseController::saveLog($value->id, $saveStocks); // save log
                    }elseif(isset($sku_list['error'])){
                        WarehouseController::saveLog($value->id, $sku_list['error']); // save log
                    }

                }
                elseif($value->warehouse == 'sap_business_one_local')
                {
                       $sku_list= SapBusinessOneLocalUtil::getStock($value); // first get all sku list froom waarehouse //SAP
                       if($sku_list)
                       {
                           // 2nd step check if any sku deleted from online warehouse and its present in ezcom then set its stock to 0
                           WarehouseController::SetZeroStock($value->id,$sku_list);
                           $offset=SapBusinessOneLocalUtil::get_next_offset(); // get offset to get products next batch
                           //3rd step get stock qty of skus from warehouse
                           $stock_list=SapBusinessOneLocalUtil::getStockQuantity($sku_list,$offset);
                           if($stock_list)
                           {
                               //4th step save products
                                $saveStocks = WarehouseController::WarehouseSaveStocks($value->id,$stock_list,false); // save or update
                                SapBusinessOneLocalUtil::set_next_offset(100); // increment next offset
                                WarehouseController::saveLog($value->id, $saveStocks); // save log
                           }

                       }

                       // die('come');



                }
//                elseif ( $value->warehouse == 'sap' ){
//
//                    $sku_list   = SapUtil::GetAllSkuStocks( $value ); // Get list from Sab
//                    $setFormat  = WarehouseController::SabStockSetFormat($sku_list);
//                    echo "<pre>";print_r($setFormat);exit;
//                    $saveStocks = WarehouseController::WarehouseSaveStocks($value->id,$setFormat,true); // save or update
//                    WarehouseController::saveLog($value->id, $saveStocks); // save log
//
//                }

            }
        }
        else{
            echo '<h2>Warehouse not found, Maybe it is inactive or name missmatching</h2>';
        }

        echo "ended at " . date('Y-m-d H:i:s');
    }

    /*****
     * push order to warehouse
    ***/
    public function actionSyncSalesToWarehouse()
    {
        echo date('H:i:s').'<br />';
        if(!isset($_GET['prefix']))
            die('prefix required');

        if(!isset($_GET['time_period_in_minutes']))
            die('time_period_in_minutes required');

        if(!is_numeric($_GET['time_period_in_minutes']))
            die('time_period_in_minutes should be numeric');

        $warehouse = Warehouses::find()->where(['is_active'=>1,'prefix'=>$_GET['prefix']])->asArray()->one();
        if($warehouse)
        {
            //self::debug($warehouse);
            if($warehouse['warehouse'] == 'quickbooks')
            {
              //  die('come');
                $db_log = QuickbookUtil::syncSalesToWarehouse($_GET['time_period_in_minutes'],$warehouse);
                QuickbookUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-order-sync'); // general log

            }elseif($warehouse['warehouse'] == 'sage'){

                $db_log = SageUtil::syncSalesToWarehouse($_GET['time_period_in_minutes'],$warehouse);
                SageUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-order-sync'); // general log
                //$db_log = SageUtil::syncOnlineSale($time_period, $value);
            }
            elseif($warehouse['warehouse'] == 'hubspot'){

                $db_log = HubspotUtil::syncSalesToWarehouse($_GET['time_period_in_minutes'],$warehouse);
                HubspotUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-order-sync'); // general log
                //$db_log = SageUtil::syncOnlineSale($time_period, $value);
            }
        }
        echo '<br />'.date('H:i:s').'<br />';
    }

    public function actionSyncWarehouseProducts()
    {
        echo date('H:i:s').'<br />';
        if(!isset($_GET['prefix']))
            die('prefix required');

        $warehouse = Warehouses::find()->where(['is_active'=>1,'prefix'=>$_GET['prefix']])->asArray()->one();
        //self::debug($warehouse);
        if($warehouse)
        {
            if ($warehouse['warehouse'] == 'skuvault')
            {
                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($warehouse,'ean');
                $db_log = SkuVault::createProductAndItemsInWarehouses( $warehouse['id'],$products_to_push);
                SkuVault::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-product-sync');
            }else if ( $warehouse['warehouse'] == 'dolibarr')
            {
                $config_ware=json_decode($warehouse['configuration'],true);
                if (!isset($config_ware['consider_product_unique_column']))
                    die('consider_product_unique_column is not set in configuration column of this warehouse');

                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($warehouse,$config_ware['consider_product_unique_column']);
                $db_log=DolibarrUtil::CreateProductsOnWarehouse( $warehouse,$products_to_push);
                DolibarrUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-product-sync');
            } elseif($warehouse['warehouse'] == 'quickbooks')
            {
                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($warehouse,'ean');
                $db_log = QuickbookUtil::syncProductsToWarehouse($warehouse,$products_to_push);
                QuickbookUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-product-sync');

            } else if( $warehouse['warehouse'] == 'sage' )
            {
                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($warehouse);
                $db_log = SageUtil::syncProductsToWarehouse($warehouse,$products_to_push);
                SageUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-product-sync');
            }

            else if( $warehouse['warehouse'] == 'hubspot' ){
                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($warehouse);
                $db_log = HubspotUtil::syncProductsToWarehouse($warehouse,$products_to_push);
                HubspotUtil::add_log($db_log, $warehouse['id'],'ezcom-to-warehouse-product-sync');

            }
        }
        echo '<br />'.date('H:i:s').'<br />';
    }


    public function actionSyncUpdateWarehouseProducts(){
        echo date('H:i:s').'<br />';
        $Warehouses = Warehouses::find()->where(['is_active'=>1,'name'=>$_GET['name']])->asArray()->all();
        foreach ( $Warehouses as $key => $value ){
            if ( $value['warehouse'] == 'skuvault'  ){

            }else if ( $value['warehouse'] == 'dolibarr' ){
                $config_ware=json_decode($value['configuration'],true);
                if (!isset($config_ware['consider_product_unique_column']))
                    die('consider_product_unique_column is not set in configuration column of this warehouse');

                $products_to_push=WarehouseUtil::getProductsNotSyncedTOWarehouse($value,$config_ware['consider_product_unique_column']);
                DolibarrUtil::UpdateProductOnWarehouse($value,$products_to_push);
            }
        }
        echo '<br />'.date('H:i:s').'<br />';
    }
    public function actionCreateSkuVaultItemTest(){
        $params = [
            'WarehouseId'=>'191568',
            'Reason' => 'Add',
            'Sku' => 'ADIACC052K-BG',
            'LocationCode' => 'US',
            'Quantity' => '1',
        ];
        //$this->debug(SkuVault::getLocations(32));
        $createItem = SkuVault::addItem(32,$params);
        $this->debug($createItem);
    }

    /****
     * particularly made for spl/magento shop to get stock from online warehouses
     */
    public function actionGetChannelWarehouseStock()
    {
       // return;
       // ini_set('MAX_EXECUTION_TIME', -1);
        //ini_set('max_input_time', -1);
        //ini_set('memory_limit', '1536M');
        echo "Started at " . date('Y-m-d H:i:s') ;
        $channel_prefix=yii::$app->request->get('prefix');
        $marketplace=yii::$app->request->get('marketplace');
        if($channel_prefix && $marketplace)
        {
            $channel=Channels::findone(['prefix'=>$channel_prefix]);
            if($channel)
            {
               // $pagination=SplWarehouseUtil::pagination_for_stock_fetching($channel);
               // $limit=4;
                if($channel->prefix=="SPL-MGT" || $channel->prefix=="PST-PEDRO"):
                $warehouses=WarehouseChannels::find()->select('warehouse_id')->where(['channel_id'=>$channel->id,'is_active'=>1])
                 //   ->limit($limit)->offset($pagination['offset'])
                ->asArray()->all();
                //self::debug($warehouses);
                foreach($warehouses as $wh)
                {
                    $warehouse=Warehouses::findone(['id'=>$wh['warehouse_id'],'is_active'=>1]);
                    if($warehouse):
                        $response= SplWarehouseUtil::getWarehouseStock($warehouse,$channel);
                        if($response)
                            WarehouseController::saveLog($warehouse->id, $response); // save log
                    endif;
                }
              //  SplWarehouseUtil::update_pagination_for_stock_fetching($channel,$limit);
                endif;
            }

        }
       // echo "done";
        echo "Ended at " . date('Y-m-d H:i:s') ;

    }

    /****
     * get stock available on shop online
     */
    public function actionGetChannelStock()
    {

        echo "Started at " . date('Y-m-d H:i:s') ."<br/>";
        $channel_prefix=yii::$app->request->get('prefix');
        $marketplace=yii::$app->request->get('marketplace');
        $response=[];
        if($channel_prefix && $marketplace)
        {

            $channel=Channels::findone(['prefix'=>$channel_prefix]);
            if($channel &&  $channel->marketplace=="magento")
            {
                $pagination=Settings::findone(['name'=>'magento_shop_stock_pagination']);  // call stock by pagination to avoid load
                $pagination=json_decode($pagination->value);
                $last_ordered=OrderUtil::getLastOrderedDistinctSkus(50,$channel->id); // skus which are last recently  ordered
                $products=ChannelsProducts::find()->where(['channel_id'=>$channel->id])
                            ->where(['NOT IN','channel_sku',array_column($last_ordered,'channel_sku')])
                            ->limit(100)->offset($pagination->next_offset)->asArray()->all();
                if($last_ordered)
                    $response[]=MagentoUtil::getItemStock($channel,$last_ordered); // update stock call
                 if($products)
                     $response[]=MagentoUtil::getItemStock($channel,$products); // update stock call

                    /****below is the setting for pagination for next stock fetch call **/
              //  if($products) {
                    $total_products = ChannelsProducts::find()->where(['channel_id' => $channel->id])->count();

                    $rounds_completed = $pagination->round_completed;
                    if ($pagination->next_offset >= $total_products) {
                        $next_offset = 0;
                        $rounds_completed = $pagination->round_completed = ($pagination->round_completed + 1);  // 1 round completed of fetching all stocks
                    } else {
                        $next_offset = ($pagination->next_offset + 100);
                    }
                    $settings = [
                        'total_products' => $total_products,
                        'next_offset' => $next_offset,
                        'round_completed' => $rounds_completed
                    ];
                    Yii::$app->db->createCommand()
                        ->update('settings', ['value' => json_encode($settings)], ['name' => 'magento_shop_stock_pagination'])
                        ->execute();
               // }

            }

        }

        echo "Ended at " . date('Y-m-d H:i:s') ."<br/>";

    }

    public function actionUpdateSellQuantityItem(){
        $params = [
            'WarehouseId'=>'191568',
            'Reason' => 'Add',
            'Sku' => 'ADISBP01Black/WhiteSTD',
            'LocationCode' => 'US',
            'Quantity' => '1',
        ];
        $createItem = SkuVault::SetItemQuantity(32,$params);
        $this->debug($createItem);
    }
    public function actionCreateBulkProductsTest(){

        $products = [];
        $products['Items'][] = ['Sku'=>'Sku/111','Description'=>'Sku 111 champion','Brand'=>'adidas','Classification'=>'General','Supplier'=>'Double D Imports SAS'];
        $products['Items'][] = ['Sku'=>'Sku/222','Description'=>'Sku 2 champion','Brand'=>'adidas','Classification'=>'General','Supplier'=>'Double D Imports SAS'];



        $createBulkProducts = SkuVault::createProducts(32,$products);
    }
    /*public function actionSyncSalesToWarehouse()
    {
        echo date('H:i:s').'<br />';
        $Warehouses = Warehouses::find()->where(['is_active'=>1,'warehouse'=>$_GET['warehouse']])->asArray()->all();
        if(!isset($_GET['to']))
            die('destination warehouse required');

        $to=$_GET['to']; // to which warehouse
        //$this->debug($Warehouses);
        foreach ( $Warehouses as $key => $value ){
            if ( $to == 'dolibarr' ){

                $ezcom_sales=SalesUtil::GetSales($value['id']); // get sales from ezcom
               // $this->debug($ezcom_sales);
                $create_sales_data=DolibarrUtil::GetOrdersToCreateOnDolibarr($ezcom_sales,$value);
                $create_sale_on_warehouse=DolibarrUtil::CreateSaleOnWarehouse($value,$create_sales_data['not_exist']); // create sale order
                $update_sale_on_warehouse=DolibarrUtil::UpdateSaleOnWarehouse($value,$create_sales_data['already_exist']); // update sale order
            }
        }
        echo '<br />'.date('H:i:s').'<br />';
    }*/
    public static function actionSyncSalesToSkuvault()  // sync all orders to sku vault
    {

        echo "started at ". date('Y-m-d H:i:s') . "<br/>";
        $time_period=Yii::$app->request->get('time_period');  // requested params  // // equivalent to: $id = isset($_GET['channel_name']) ? $_GET['channel_name'] : null;
        $time_period= $time_period ? $time_period:'chunk';   //if not speicfied then chunk
        $db_log=SkuVault::syncOnlineSale($time_period);
        if($db_log)  // request response log to check
            self::_saveResponse('SkuVaultOrders',null,$db_log);

        echo "Ended at ". date('Y-m-d H:i:s');
    }
    

    public static function actionSyncSaleToSage()  // sync all orders to sku vault
    {
        echo "started at " . date('Y-m-d H:i:s') . "<br/>";
        $time_period = $_GET['time_period'];  // requested params  // // equivalent to: $id = isset($_GET['channel_name']) ? $_GET['channel_name'] : null;
        $prefix = $_GET['prefix'];
        $time_period = $time_period ? $time_period : 'chunk';   //if not speicfied then chunk

        if (isset($time_period) && isset($prefix)) {
            $channel = Warehouses::find()->where(['prefix' => $prefix, 'is_active' => 1])->all();

            if ($channel) {
                foreach ($channel as $value) {

                    $db_log = SageUtil::createProductAndItemsInWarehouses($time_period, $value);
                }
            }
            echo "Ended at " . date('Y-m-d H:i:s');
        }
    }

    public static function actionSyncSaleToHubspot()  // sync all orders to sku vault
    {
        echo "started at " . date('Y-m-d H:i:s') . "<br/>";
        $time_period = $_GET['time_period'];  // requested params  // // equivalent to: $id = isset($_GET['channel_name']) ? $_GET['channel_name'] : null;
        $prefix = $_GET['prefix'];
        $time_period = $time_period ? $time_period : 'chunk';   //if not speicfied then chunk

        if (isset($time_period) && isset($prefix)) {
            $channel = Warehouses::find()->where(['prefix' => $prefix, 'is_active' => 1])->all();

            if ($channel) {
                foreach ($channel as $value) {
                    echo "mobeen";exit;
                    $db_log = HubspotUtil::syncOnlineSale($time_period, $value);
                }
            }
            echo "Ended at " . date('Y-m-d H:i:s');
        }
    }



/*    public static function actionSyncProductToQuickbook()  // sync all orders to sku vault
    {
        echo "started at " . date('Y-m-d H:i:s') . "<br/>";
        if(!isset($_GET['prefix']))
            die('prefix required');

        $prefix = $_GET['prefix'];

        if (isset($time_period) && isset($prefix))
        {
            $warehouses = Warehouses::find()->where(['prefix' => $prefix, 'is_active' => 1])->all();
            //self::debug($warehouse);
            if ($warehouses)
            {
                foreach ($warehouses as $warehouse)
                {
                    $db_log = QuickbookUtil::syncOnlineProductsQuickbook($warehouse);
                    QuickbookUtil::add_log($db_log,$warehouse['id']);
                    if(isset($db_log['success_log']) && $db_log['success_log'])
                    {
                        QuickbookUtil::skus_synced_log($db_log['success_log'],$warehouse['id']);
                    }
                   // self::debug($db_log);
                }
            }
            echo "Ended at " . date('Y-m-d H:i:s');
        }
    }*/

    public static function actionSyncProductToHubspot()  // sync all orders to sku vault
    {
        echo "started at " . date('Y-m-d H:i:s') . "<br/>";
        if(!isset($_GET['prefix']))
            die('prefix required');

        $prefix = $_GET['prefix'];
        $time_period = $_GET['time_period'];
        if (isset($time_period) && isset($prefix))
        {
            $warehouses = Warehouses::find()->where(['prefix' => $prefix, 'is_active' => 1])->all();
            //self::debug($warehouse);
            if ($warehouses)
            {
                foreach ($warehouses as $warehouse)
                {
                    $db_log = HubspotUtil::syncOnlineProductsHubspot($warehouse);
                  //  QuickbookUtil::add_log($db_log,$warehouse['id']);
                  //  if(isset($db_log['success_log']) && $db_log['success_log'])
                   // {
                    //    QuickbookUtil::skus_synced_log($db_log['success_log'],$warehouse['id']);
                  //  }
                    // self::debug($db_log);
                }
            }
            echo "Ended at " . date('Y-m-d H:i:s');
        }
    }




    public static function actionGetMarketplaceStock() // primarily for amazon
    {
        echo "started at " .date('Y-m-d H:i:s') ."<br/>";
        if(isset($_GET['channel-name']) && isset($_GET['destination-storage-warehouse']) && isset($_GET['warehouse-type']))
        {
            $channel=Channels::find()->where(['name'=>$_GET['channel-name'],'is_active'=>1])->one();
            $warehouse=Warehouses::find()->where(['name'=>$_GET['destination-storage-warehouse'],'is_active'=>1,'warehouse'=>$_GET['warehouse-type']])->one();
            //$this->debug($warehouse)
            //echo '<pre>';print_r($warehouse);die;
            if($channel && $warehouse)
            {
                $product_list=array();
                $returned_stock=array();
                if($channel->marketplace=="amazon")
                {
                    $channel_products=ChannelsProducts::find()->where(['channel_id'=>$channel->id,'fulfilled_by'=>'FBA','is_live'=>1])->all();
                   //print_r($channel_products); die();
                    if($channel_products)
                    {
                        $count=0;
                        foreach($channel_products as $product)
                        {
                            $product_list[]=$product['channel_sku'];
                            if(fmod(++$count,50)==0)  //50 per request
                            {

                                $amazon_stock= AmazonUtil::getFbaStock ($channel,$product_list);
                                if($amazon_stock){ $returned_stock[]=$amazon_stock; }
                                $product_list=array();
                                //break;
                            }

                        }
                        if($product_list) // remaining which left from 50 stack update at once
                        {

                            $amazon_stock= AmazonUtil::getFbaStock ($channel,$product_list);
                            if($amazon_stock){ $returned_stock[]=$amazon_stock;}
                        }
                        if($returned_stock && is_array($returned_stock))
                        {
                            $fetched_items=call_user_func_array('array_merge',$returned_stock); // merge all indexes to one index
                            WarehouseController::WarehouseSaveStocks($warehouse->id,$fetched_items,false);
                            if(isset($_GET['update-live-warehouse-stock'])) // if have to update stock on live warehouse as well
                            {
                                SkuVault::updateBulkStock($warehouse,$fetched_items); // update skuvault fba stock as well
                            }

                        }

                    }

                }

            }


        }
        else {
            echo "check missing parameters ! Failure update".PHP_EOL;
        }

        echo "Ended at " .date('Y-m-d H:i:s') ."<br/>";

    }

    public function actionFetchPoRecievedQty(){

        $PoList = WarehouseUtil::GetPos();
        foreach ( $PoList as $Detail ){

            $SkuList = PoDetails::find()->where(['po_id'=>$Detail['po_id']])->asArray()->all();

            $result = PurchaseOrderUtil::UpdateRecievedQtySkus($Detail['po_id'],$Detail['warehouse_id'],$Detail['po_code'],$SkuList,$Detail['warehouse']);
            // update the status of PO
            $UpdatePoStatus = StocksPo::findOne(['id'=>$Detail['po_id']]);
            $UpdatePoStatus->po_status = $result['status'];
            $UpdatePoStatus->update();
            sleep(2);
        }

        /*
         *Update the stock In Transit for the whole warehouse
         * */

        $StockInTransitList = WarehouseUtil::GetPOSStockInTransit();
        WarehouseUtil::UpdateWarehouseStockInTransit($StockInTransitList);

    }

    /**
     *  archive sales of particular month , sku wise
     */

    public function actionMonthlySkuSalesArchive()
    {
           echo "started_at:" . date('Y-m-d H:i:s'). "<br/>";

            //$year=gmdate('Y'); // utc format
            $year='2019'; // utc format
            //$month=gmdate('F'); // utc format
            $month='january'; // utc format
           $record= SalesUtil::getMonthlySkuSales($year,$month);
           $response=SalesUtil::saveMonthlySkuSales($record);

           echo "Updated : ". $response['updated'] . "<br/>";
           echo "Inserted : ". $response['inserted'] . "<br/>";
           echo "ended_at:" . date('Y-m-d H:i:s'). "<br/>";
    }
    public function actionTrackFedexPackage(){

        $package = FedExUtil::TrackShipment(1,'999999999999');
        if ( isset($package->CompletedTrackDetails->TrackDetails->StatusDetail->Description) )
            echo $package->CompletedTrackDetails->TrackDetails->StatusDetail->Description;

        $this->debug($package);

    }



    public function actionCourierTrackingUpdate(){

        $Items = OrderUtil::GetShippedItems();
        $updated_on_marketplace=[]; // flag check for prestashop if order has same tracking number so no need to update for all items
        foreach ( $Items as $ItemsDetail ){
            if ( $ItemsDetail['marketplace']=='magento' ){
              //  using  function actionUpdateTrackingToMarketplace for magento
                continue;
            }
            else if ( $ItemsDetail['marketplace']=='prestashop' ){
                //using  function actionUpdateTrackingToMarketplace for presta
                continue;
                /*if(isset($updated_on_marketplace[$ItemsDetail['channel_order_number']]) && in_array($ItemsDetail['tracking_number'],$updated_on_marketplace[$ItemsDetail['channel_order_number']]))  // if same tracking is updated in loop for same order no need to update again
                { } else {
                $updated_on_marketplace[$ItemsDetail['channel_order_number']][]=$ItemsDetail['tracking_number'];
                $dimensions = json_decode($ItemsDetail['dimensions'], true);
              $MarkOrderShipped = PrestashopUtil::SetOrderStatus( $ItemsDetail['channel_id'],                       $ItemsDetail['channel_order_id'], 'Shipped' );
                $UpdateTrackingInfo = PrestashopUtil::UpdateTrackingInfo($ItemsDetail['channel_id'],$ItemsDetail['channel_order_id'], $ItemsDetail['tracking_number'],$ItemsDetail['amount_inc_taxes'],$ItemsDetail['amount_exc_taxes'],$dimensions['weight']);
                }*/
            }
            elseif ( $ItemsDetail['marketplace']=='ebay' )
            {
                $sellerHandOffPackageToCourier= gmdate('Y-m-d\TH:i:s\Z',strtotime(date($ItemsDetail['shipping_date']))); // shipping date on which seller will hand over parcel to courier
                $UpdateTrackingInfo = EbayUtil::UpdateTracking($ItemsDetail['channel_id'], $ItemsDetail['channel_order_item_id'], $ItemsDetail['tracking_number'],$ItemsDetail['courier_name']);
                $UpdateOrderShipped = EbayUtil::MarkOrderShipped( $ItemsDetail['channel_id'], $ItemsDetail['channel_order_item_id'], $sellerHandOffPackageToCourier );
            }
            elseif ( $ItemsDetail['marketplace']=='walmart' ){
               // continue;
                $params = [];
                $params['channelId'] = $ItemsDetail['channel_id'];
                $params['customer_order_id'] = $ItemsDetail['channel_order_id']; // it is produced by walmart for custmers
                $params['channel_order_number'] = $ItemsDetail['channel_order_number'];  // change from above id used for walmart
                $params['channel_order_item_id'] = $ItemsDetail['channel_order_item_id'];
                $params['order_item_id_PK'] = $ItemsDetail['order_item_id_PK'];
                $params['tracking_number'] = $ItemsDetail['tracking_number'];
                $params['courier_name'] = $ItemsDetail['courier_name'];
                $params['shipping_date'] = $ItemsDetail['shipping_date'];
                $UpdateTrackingAndMarkItemShipped = WalmartUtil::ShipOrderLines($params);
                // response will be  => ['status'=>'success || failure','response'=>'apiresponse(xml format)']
            }
            //die('oops');
            $updateTracking = OrderShipment::findOne($ItemsDetail['order_shipment_id']);
            $updateTracking->is_tracking_updated = 1;
            $updateTracking->update();
        }


    }

    /**
     * for magento,presta update tracking to marketplace
     */
    public function actionUpdateTrackingToMarketplace()
    {
        echo "Started at:" . Date('Y-m-d H:i:s') . "<br/>";
        if (!isset($_GET['marketplace']))
            die('marketplace name  required');

        $channels = Channels::find()->where(['marketplace' => $_GET['marketplace']])->asArray()->all();
        if (!$channels)
            die('NO channel found');

        /********************************************************
         ********************** magento***********************
         *******************************************************/
        if (trim($_GET['marketplace']) == "magento")
        {
            foreach ($channels as $channel) {
                $items = OrderUtil::GetShippedItems($channel['id']);
                $items = MagentoUtil::arrangeOrderForShipment($items); // arrange items order wise
              //  self::debug($items);
                $items = MagentoUtil::append_invoice_record((object)$channel, $items);  // check how many invoices created already // before ship have t create invoice
                $response = MagentoUtil::createInvoice((object)$channel, $items);
                $response = MagentoUtil::createShipment((object)$channel, $items); // create shipment and send email
                MagentoUtil::updateOrderShipmentLocalDb($items, $response);  // local db update order_shipment
               // self::debug($response);
               // MagentoUtil::notify_customer_shipping((object)$channel,$items,$response);  // send email to customer about shipping

            }
        }
        /********************************************************
         ********************** prestashop************************
         *******************************************************/

        else if(trim($_GET['marketplace']) == "prestashop")
        {
            foreach ($channels as $channel) {
                $items = OrderUtil::GetShippedItems($channel['id']);
                $items=PrestashopUtil::arrangeOrderForShipment($items); // arrange items order wise
                $items= PrestashopUtil::OrderShipmentCompleteOrPartial($items);//check if order is shipping complete or partial // to avoid repeating tracking number and status
                //echo "<pre>";
                //print_r($items); die();
                $response= PrestashopUtil::updateShipmentAndTracking((object)$channel,$items);
                PrestashopUtil::updateOrderShipmentLocalDb($items, $response);
            }
        }

        /********************************************************
         ********************** Backmarket************************
         *******************************************************/
        else if(trim($_GET['marketplace']) == "backmarket")
        {
            foreach ($channels as $channel)
            {
                $items = OrderUtil::GetShippedItems($channel['id']);
                $items=PrestashopUtil::arrangeOrderForShipment($items); // arrange items order wise
               // self::debug($items);
                $response= BackmarketUtil::updateShipmentAndTracking((object)$channel,$items);
                BackmarketUtil::updateOrderShipmentLocalDb($items, $response);  // local db update order_shipment
               // self::debug($response);
            }
        }

        /********************************************************
         ********************** Woocommerce************************
         *******************************************************/
        else if(trim($_GET['marketplace']) == "woocommerce")
        {
            foreach ($channels as $channel)
            {
                $items = OrderUtil::GetShippedItems($channel['id']);
                $items=WoocommerceUtil::arrangeOrderForShipment($items); // arrange items order wise

                //  self::debug($items);
                $response= WoocommerceUtil::updateShipmentAndTracking((object)$channel,$items);
                WoocommerceUtil::updateOrderShipmentLocalDb($items, $response);  // local db update order_shipment
                // self::debug($response);
            }
        }
        echo "Ended at:" . Date('Y-m-d H:i:s') ."<br/>";
    }


    /*****
     * remanage /reassign sale category if products has discounted price or if not then remove sale cat
     * specially for prestashop
     * @specially pedro
     */
    public function actionRemanageSaleCategory()
    {
        echo "Started @". Date('Y-m-d H:h:s')."<br/>";
       $request=yii::$app->request;
       $channel= $request->get('channel');
       $marketplace= $request->get('marketplace');
       if($channel && $marketplace)
       {
           $channel = Channels::find()->where(['marketplace'=>$marketplace,'name'=>$channel,'is_active'=>1])->one();
           if($channel->marketplace=="prestashop")
            $response=PrestashopUtil::re_manage_sale_category($channel);

           if(isset($response) && $response){
               $this->_saveResponse('sale_cat_update',$channel->id,$response);
           }

       } else{
         echo 'Unable to process! Channel And marketplace are required parameters'.'<br/>';
       }
        echo "Ended @". Date('Y-m-d H:h:s')."<br/>";
    }
    public function actionCreateShipment(){
        $channels = Channels::find()->where(['id'=>18])->one();
        $carrierslist=MagentoUtil::createShipment($channels);
        /*$updatestock = MagentoUtil::TestUpdateStock($channels);
        $this->debug($updatestock);*/

    }
    public function actionUpdateWalmart(){
        $channel = Channels::find()->where(['id'=>21])->one();
        $walmart = WalmartUtil::ShipOrderLines($channel);
    }
    public function actionDivideCron(){
        $from = '2020-01-01 00:00:00';
        $to = '2020-01-01 00:15:00';
        $startTime = strtotime($from);
        $currentTime = time();
        echo $from.'<br />';
        echo $to .'<br />';
        echo '<br />';
        echo '<br />';
        $timeSlots=[];
        for( $startTime; $startTime<=$currentTime; $startTime+=900 ){
            $curr=$startTime;
            $from= date('Y-m-d H:i:s',strtotime('+15 minutes',($curr-900)));
            $to = date('Y-m-d H:i:s',strtotime('+15 minutes',($startTime)));
            $timeSlots[] = $from.'|||'.$to;
        }
        $channels = ['Shopee'];
        $sql='';
        foreach ( $channels as $channelName ){
            foreach ($timeSlots as $timestlo){
                $explode=explode('|||',$timestlo);
                //echo $timestlo;die;
                $from = $explode[0];
                $to = $explode[1];
                $link = 'http://aoa-latest.localhost/cron/sales-fetch?channel=shopee&channel_name='.$channelName.'&from='.$from.'&to='.$to;
                //echo $link;die;
                $sql.= "INSERT INTO sales_fetch_crons (channel, link, status) VALUES ('$channelName','$link','New');";
                //echo $sql;die;

            }
        }
        //echo $sql;die;
        $result=Yii::$app->db->createCommand($sql)->queryAll();
        //$this->debug($timeSlots);
        die;
    }
    public function actionRefetchSales(){
        $start=time();
        // if already running
        $sql = "SELECT * FROM sales_fetch_crons WHERE status = 'Processing' AND channel = '".$_GET['channel_name']."'";
        $result=Yii::$app->db->createCommand($sql)->queryAll();
        if (!empty($result)){
            echo 'Already job is running, Job id is '.$result[0]['id'];
            die;
        }

        $sql = "SELECT * FROM sales_fetch_crons WHERE status = 'New' AND channel = '".$_GET['channel_name']."' ORDER BY id LIMIT 1";
        $result=Yii::$app->db->createCommand($sql)->queryAll();

        $updateprocess="UPDATE sales_fetch_crons SET status = 'Processing' WHERE id=".$result[0]['id'];
        Yii::$app->db->createCommand($updateprocess)->execute();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => str_replace(' ','%20',$result[0]['link']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 3000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 93b9f110-32d1-f7ac-ce05-feebd70b2e3a"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            $seconds = time() - $start;
            $updateprocess="UPDATE sales_fetch_crons SET status = 'Completed', time_taken_seconds='$seconds' WHERE id=".$result[0]['id'];
            Yii::$app->db->createCommand($updateprocess)->execute();
            echo $response;
        }
    }
    public function actionRefreshCron(){
        $updateprocess="UPDATE sales_fetch_crons SET status = 'New' WHERE status='Processing'";
        Yii::$app->db->createCommand($updateprocess)->execute();
        die;
    }
    public function actionUpdateProducts(){
        $sql ="SELECT * FROM products";
        $results = Yii::$app->db->createCommand($sql)->queryAll();
        foreach ( $results as $pDetail ){
            //$this->debug($pDetail);
            $product= Yii::$app->db->createCommand("SELECT * from products_bk WHERE sku = '".$pDetail['sku']."'")->queryAll();
            foreach ( $product as $psdetail){
                $updateQuery = "UPDATE products_bk set ean = '".$pDetail['tnc']."', barcode = '".$pDetail['barcode']."' WHERE id = ".$psdetail['id'];
                Yii::$app->db->createCommand($updateQuery)->execute();
                //die;
            }
        }
    }
    public function actionGetDiscountList(){
        $channel = Channels::find()->where(['id'=>24])->one();
        $response = ShopeeUtil::GetDiscountsList($channel);
    }
    public function actionLazadaFinance(){

        echo date('H:i:s');
        echo '<br />';
        $log=['Inserted'=>0,'Updated'=>0];
        $channel = Channels::find()->where(['marketplace'=>'lazada'])->asArray()->all();
        foreach ( $channel as $channelDetail ){
            if (isset($_GET['from']) && isset($_GET['to'])){
                $startDate = $_GET['from'];
                $endDate = $_GET['to'];
            }else{
                $startDate = date('Y-m-d');
                $endDate = date('Y-m-d');
            }
            $detail = LazadaUtil::GetFinanceDetail($channelDetail['id']);
            if (empty($detail)){
                echo $channelDetail['name'].' : All records updated succesfully';
                echo '<br />';
                //die;
            }
            $sql ="";
            foreach ( $detail as $value ){
                $sql .= "UPDATE finance_log set updated = 2 WHERE id = ".$value['id'].";";
            }
            Yii::$app->db->createCommand($sql)->execute();
            $detailredefine = [];
            foreach ($detail as $key=>$value){
                $detailredefine['data'][$key] = json_decode($value['data'],true);
                $detailredefine['data'][$key]['finance_log_id']=$value['id'];
            }
            if (isset($_GET['debug'])){
                //$this->debug($detail);
                echo 'Total records '.count($detailredefine['data']);
                echo '<br />';
                die;
            }
            $redefinedetail = [];
            if ( empty($detailredefine )){
                continue;
            }
            foreach ( $detailredefine['data'] as $value ){
                if (!isset($value['orderItem_no'])){
                    continue;
                }
                $redefinedetail['data'][]=$value;
            }
            $detail = LazadaUtil::SetExtraColumns($redefinedetail);
            $crossCounter=0;
            //$this->debug($detail);
            $SqlUpdate = "";
            foreach ( $detail['data'] as $keyu=>$trxDetail ){
                $crossCounter++;
                $table = HelpUtil::GetTableColumns('lazada_finance_report');

                if ( strlen($trxDetail['fee_name'].' - Amount') > 64 ){
                    $ccol = substr($trxDetail['fee_name'],0,55);
                    $ccol = $ccol.' - Amount';
                }else{
                    $ccol = $trxDetail['fee_name'].' - Amount';
                }

                if ( !in_array($ccol,$table) ){

                    //echo

                    $columnName = $trxDetail['fee_name'];
                    $afterColumn = end($table);



                    $AmountColumn = $trxDetail['fee_name'].' - Amount';
                    $comment = $AmountColumn;
                    if ( strlen($AmountColumn) > 64 ){
                        $columnName = substr($trxDetail['fee_name'],0,55);
                        $AmountColumn = $columnName.' - Amount';
                    }

                    $Alter = "ALTER TABLE `lazada_finance_report`
                          ADD COLUMN `$AmountColumn` DOUBLE(11,2) NULL DEFAULT '0'
                          COMMENT '".$comment."'
                          AFTER `$afterColumn`;";
                    Yii::$app->db->createCommand($Alter)->execute();

                    //$table[] = $AmountColumn;

                    $VATColumn = $trxDetail['fee_name'].' - VAT in Amount';
                    $comment = $VATColumn;
                    if ( strlen($VATColumn) > 64 ){
                        $columnName = substr($trxDetail['fee_name'],0,48);
                        $VATColumn = $columnName.' - VAT in Amount';
                    }
                    $Alter = "ALTER TABLE `lazada_finance_report`
                          ADD COLUMN `$VATColumn` DOUBLE(11,2) NULL DEFAULT '0'
                          COMMENT '".$comment."'
                          AFTER `$AmountColumn`;";
                    Yii::$app->db->createCommand($Alter)->execute();

                    //$table[] = $VATColumn;


                    $whtColumn = $trxDetail['fee_name'].' - WHT Amount';
                    $comment = $whtColumn;

                    if ( strlen($whtColumn) > 64 ){

                        $columnName = substr($trxDetail['fee_name'],0,51);
                        $whtColumn = $columnName.' - WHT Amount';
                    }
                    $Alter = "ALTER TABLE `lazada_finance_report`
                          ADD COLUMN `$whtColumn` DOUBLE(11,2) NULL DEFAULT '0'
                          COMMENT '".$comment."'
                          AFTER `$VATColumn`;";
                    //echo $Alter;
                    //echo '<br/>';
                    //die;
                    Yii::$app->db->createCommand($Alter)->execute();

                    //$table[] = $whtColumn;

                    $whtIncColumn = $trxDetail['fee_name'].' - WHT INCA';
                    $comment = $whtIncColumn;
                    if ( strlen($whtIncColumn) > 64 ){
                        $columnName = substr($trxDetail['fee_name'],0,53);
                        $whtIncColumn = $columnName.' - WHT INCA';
                    }
                    $Alter = "ALTER TABLE `lazada_finance_report`
                          ADD COLUMN `$whtIncColumn` VARCHAR(25) NULL DEFAULT '-'
                          COMMENT '".$comment."' 
                          AFTER `$whtColumn`;";
                    Yii::$app->db->createCommand($Alter)->execute();

                    //$table[] = $whtIncColumn;
                }

                $findItem = LazadaFinanceReport::find()->where(['order_item_id'=>$trxDetail['orderItem_no']])->one();

                if (!isset($trxDetail['paid_price_ezcom'])){
                    continue;
                }

                if ( !$findItem ){



                    $FeeNameColAmount=$trxDetail['fee_name'].' - Amount';
                    if ( strlen($FeeNameColAmount) > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 9).' - Amount';
                        $FeeNameColAmount = $colName;
                    }

                    $FeeNameColVAT=$trxDetail['fee_name'].' - VAT in Amount';
                    if ( strlen($FeeNameColVAT) > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 16).' - VAT in Amount';
                        $FeeNameColVAT = $colName;
                    }
                    $FeeNameColWHT=$trxDetail['fee_name'].' - WHT Amount';
                    if ( strlen($FeeNameColWHT) > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 13).' - WHT Amount';
                        $FeeNameColWHT = $colName;
                    }
                    $FeeNameColWHTinc=$trxDetail['fee_name'].' - WHT INCA';
                    if ( strlen($FeeNameColWHTinc) > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 11).' - WHT INCA';
                        $FeeNameColWHTinc = $colName;
                    }

                    $shippingprovider=($trxDetail['shipping_provider']!='null') ? $trxDetail['shipping_provider'] : null;

                    $InsertQuery="INSERT INTO `lazada_finance_report` (
                              `channel_id`, `order_id`, `order_item_id`, `seller_sku`,
                              `lazada_sku`, `item_status`, `paid_price`,`commission_amount`,
                              `commission_percentage`,`transaction_fee`,`fbl_fee`,`expected_receive_amount`,
                              `shipping_amount`,`shipment_type`, `shipping_provider`, `paid_status`,
                              `reference`,`shipping_speed`, `details`, `transaction_date`,
                              `".$FeeNameColAmount."`,`".$FeeNameColVAT."`, `".$FeeNameColWHT."`, `".$FeeNameColWHTinc."`) 
                              VALUES 
                              (".$channelDetail['id'].", '".$trxDetail['order_no']."', '".$trxDetail['orderItem_no']."', '".$trxDetail['seller_sku']."', 
                              '".$trxDetail['lazada_sku']."', '".$trxDetail['orderItem_status']."',".$trxDetail['paid_price_ezcom'].",".$trxDetail['commission_amount_ezcom'].",
                              '".$trxDetail['commission_percentage_ezcom']."', '".$trxDetail['transaction_fee_ezcom']."',".$trxDetail['fbl_fee_ezcom'].",".$trxDetail['expected_receive_amount_ezcom'].",
                              ".$trxDetail['shipping_amount_ezcom'].",'".$trxDetail['shipment_type']."', '".$shippingprovider."', '".$trxDetail['paid_status']."',
                              '".$trxDetail['reference']."','".$trxDetail['shipping_speed']."', '".str_replace("'","\'",$trxDetail['details'])."', '".date('Y-m-d', strtotime($trxDetail['transaction_date']))."',
                              ".$trxDetail['amount'].",".$trxDetail['VAT_in_amount'].", ".$trxDetail['WHT_amount'].", '".$trxDetail['WHT_included_in_amount']."')";

                    Yii::$app->db->createCommand($InsertQuery)->execute();
                    $log['Inserted']+=1;
                }
                else{

                    $FeeNameColAmount=$trxDetail['fee_name'].' - Amount';
                    if ( strlen($trxDetail['fee_name'].' - Amount') > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 9).' - Amount';
                        $FeeNameColAmount = $colName;
                    }
                    $FeeNameColVAT=$trxDetail['fee_name'].' - VAT in Amount';
                    if ( strlen($trxDetail['fee_name'].' - VAT in Amount') > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 16).' - VAT in Amount';
                        $FeeNameColVAT = $colName;
                    }
                    $FeeNameColWHT=$trxDetail['fee_name'].' - WHT Amount';
                    if ( strlen($trxDetail['fee_name'].' - WHT Amount') > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 13).' - WHT Amount';
                        $FeeNameColWHT = $colName;
                    }
                    $FeeNameColWHTinc=$trxDetail['fee_name'].' - WHT INCA';
                    if ( strlen($trxDetail['fee_name'].' - WHT INCA') > 64 ){
                        $colName = substr( $trxDetail['fee_name'], 0, 64 - 11).' - WHT INCA';
                        $FeeNameColWHTinc = $colName;
                    }
                    // Add amounts
                    $Amount=0;
                    foreach ( $findItem as $feeName=>$feeValue ){
                        if (strpos($feeName, ' - Amount') !== false) {
                            $Amount+=$feeValue;
                        }
                    }

                    $recieving_differece = $Amount - $findItem->expected_receive_amount;

                    $UpdateQuery = "UPDATE lazada_finance_report set 
                `receiving_difference`=".$recieving_differece.",
                `".$FeeNameColAmount."`=".$trxDetail['amount'].",
                `".$FeeNameColVAT."`=".$trxDetail['VAT_in_amount'].",
                `".$FeeNameColWHT."`=".$trxDetail['WHT_amount'].",
                `".$FeeNameColWHTinc."`='".$trxDetail['WHT_included_in_amount']."'
                WHERE id = $findItem->id";
                    Yii::$app->db->createCommand($UpdateQuery)->execute();
                    $log['Updated']+=1;
                }

                $SqlUpdate .= "UPDATE finance_log set updated = 1 WHERE id = ".$trxDetail['finance_log_id'].";";


            }
            Yii::$app->db->createCommand($SqlUpdate)->execute();
            //die;
            echo '<pre>';
            echo $crossCounter;
            print_r($log);

            echo date('H:i:s');
        }



    }
    public function actionInsertTrx(){
        echo date('H:i:s');
        echo '<br />';
        $log=['Inserted'=>0,'Updated'=>0];
        $channel = Channels::find()->where(['marketplace'=>'lazada'])->asArray()->all();
        foreach ( $channel as $channelDetail ){
            if (isset($_GET['from']) && isset($_GET['to'])){
                $startDate = $_GET['from'];
                $endDate = $_GET['to'];
            }else{
                $startDate = date('Y-m-d' , strtotime('-1 day'));
                $endDate = date('Y-m-d',strtotime('-1 day'));
            }

            $detail = LazadaUtil::GetCompleteFinanceDetails($channelDetail['id'], $startDate, $endDate);

            //$this->debug($detail);
            $bulkInsert = [];
            foreach ($detail['data'] as $values){
                $bulkInsert[] = ['data'=>json_encode($values),'updated'=>0,'channel_id'=>$channelDetail['id']];
            }
            if (isset($_GET['debug'])){
                echo '<br />';
                echo count($bulkInsert);
                die;
            }
            echo '<br />';
            //echo count($bulkInsert);
            Yii::$app->db
                ->createCommand()
                ->batchInsert('finance_log', ['data','updated','channel_id'],$bulkInsert)
                ->execute();
        }
        echo date('H:i:s');
    }
    public function Feed(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://philips.ezcommerce.io/cron/products-feed",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 6cbc0847-95c7-3290-539f-74f54af75a6e"
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
    public function actionProductsNotFound(){
        $data = $this->Feed();
        $data = json_decode($data,true);
        $notfound=[];
        //$this->debug($data);
        foreach ( $data as $detail ){
            $findProduct = Products::find()->where(['sku'=>$detail['sku']])->one();
            if (!$findProduct){
                $notfound[]=$detail;
            }
        }
        echo '<pre>';
        foreach ($notfound as $detail){
           // $this->debug($detail);

            $categoryfind=Category::find()->where(['name'=>$detail['name']])->asArray()->all();
            if ( count($categoryfind)==0 ){
                $categoryId=168;
                /*echo 'no category found';
                print_r($detail);
                die;*/
            }
            elseif (count($categoryfind)>1){
                foreach ($categoryfind as $catdetail){
                    if ( $catdetail['parent_id']!='' ){
                        $categoryId=$catdetail['id'];
                        break;
                    }
                }
                //echo $categoryId;
                if (!isset($categoryId)){
                    echo 'multiple categoires found';
                    print_r($categoryfind);
                    die;
                }

            }else{
                $categoryId = $categoryfind[0]['id'];
            }

            $createProduct = new Products();
            $createProduct->sku = $detail['sku'];
            $createProduct->name = $detail['product_name'];
            $createProduct->cost=$detail['cost'];
            $createProduct->rccp=$detail['rccp'];
            $createProduct->promo_price=$detail['promo_price'];
            $createProduct->is_orderable=($detail['is_foc']==0) ? 1 : 0;
            $createProduct->is_foc = ($detail['is_foc']==1) ? 1 : 0;
            $createProduct->selling_status = $detail['selling_status'];
            $createProduct->barcode = $detail['barcode'];
            $createProduct->ean = $detail['ean'];
            $createProduct->stock_status = $detail['stock_status'];
            $createProduct->is_active=$detail['product_is_active'];
            $createProduct->extra_cost=$detail['extra_cost'];
            $createProduct->sub_category=$categoryId;
            $createProduct->master_cotton=$detail['master_cotton'];
            $createProduct->created_at=time();
            $createProduct->updated_at=time();
            $createProduct->created_by=Yii::$app->user->getId();
            $createProduct->save();
            //$this->debug($createProduct);
            //$this->debug($detail);
        }
        //$this->debug($notfound);
    }
    public function actionUpdatePdata(){
        $data = $this->Feed();
        $data = json_decode($data,true);
        foreach ( $data as $detail ){

            //$this->debug($detail);
            $findProduct = Products::findOne(['sku'=>$detail['sku']]);
            if ($detail['name']==''){
                $categoryId=168;
            }
            if ( $findProduct->sub_category=='' ){
                $categoryfind=Category::find()->where(['name'=>$detail['name']])->asArray()->all();
                //$this->debug($categoryfind);
                if ( count($categoryfind)==0 ){
                    $categoryId=168;
                    /*echo 'no category found';
                    print_r($detail);
                    die;*/
                }
                elseif (count($categoryfind)>1){
                    foreach ($categoryfind as $catdetail){
                        if ( $catdetail['parent_id']!='' ){
                            $categoryId=$catdetail['id'];
                            break;
                        }
                    }
                    //echo $categoryId;
                    if (!isset($categoryId)){
                        echo 'multiple categoires found';
                        print_r($categoryfind);
                        die;
                    }

                }else{
                    $categoryId = $categoryfind[0]['id'];
                }
                $findProduct->sub_category=$categoryId;
            }
            $findProduct->cost=$detail['cost'];
            $findProduct->rccp=$detail['rccp'];
            $findProduct->promo_price=$detail['promo_price'];
            $findProduct->extra_cost=$detail['extra_cost'];
            $findProduct->master_cotton=$detail['master_cotton'];
            $findProduct->is_foc=$detail['is_foc'];
            $findProduct->is_orderable=($detail['is_foc']) ? 0 : 1;
            if (isset($categoryId)){
                $findProduct->sub_category = $categoryId;
            }

            $findProduct->selling_status=$detail['selling_status'];
            $findProduct->barcode=$detail['barcode'];
            $findProduct->ean=$detail['ean'];
            $findProduct->stock_status=$detail['stock_status'];
            $findProduct->name=$detail['product_name'];
            $findProduct->created_by=1;
            $findProduct->update();
            if ($findProduct->errors){
                echo '<pre>';
                print_r($detail);
                //echo $categoryId;
                echo 'hello';
                print_r($findProduct->errors);
                die;
            }
        }
        //$this->debug($data);
    }
    public function actionProductParentChild(){
        $data = $this->Feed();
        $data = json_decode($data,true);
        $redefine = [];
        //$this->debug($data);
        foreach ( $data as $detail ){
            $redefine[$detail['id']]=$detail;
        }

        foreach ( $redefine as $detail ){

            if ($detail['parent_sku_id']!='' && $detail['parent_sku_id']!='0'){
                //echo '<pre>';
                //print_r($detail);
                $updateProduct = Products::findOne(['sku'=>$detail['sku']]);
                $updateProduct->parent_sku_id = HelpUtil::exchange_values('sku','id',$redefine[$detail['parent_sku_id']]['sku'],'products');
                $updateProduct->update();
                //print_r($redefine[$detail['parent_sku_id']]);
                //die;
            }

        }
        //$this->debug($redefine);
    }
    public function CrawlLinks(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://philips.ezcommerce.io/cron/crawler-skus",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 6cbc0847-95c7-3290-539f-74f54af75a6e"
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
    public function actionGetCrawlLinks(){
        $links = $this->CrawlLinks();
        $links = json_decode($links,1);
        //$this->debug($links);
        foreach ( $links as $value ){
            //$this->debug($value);
            $skuId = HelpUtil::exchange_values('sku','id',$value['sku'],'products');
            //echo $value['sku'];
            echo $skuId;//die;
            if ($value['channel_id']=='1')
                $channelid='22';
            if ($value['channel_id']=='2')
                $channelid='24';

            $addSkusCrawl = new SkusCrawl();
            $addSkusCrawl->sku_id=$skuId;
            $addSkusCrawl->channel_id=$channelid;
            $addSkusCrawl->product_ids=$value['product_ids'];
            $addSkusCrawl->created_at = time();
            $addSkusCrawl->updated_at = time();
            $addSkusCrawl->save();
            if ($addSkusCrawl->errors){
                $this->debug($addSkusCrawl->errors);
            }
            //$addSkusCrawl->created_by =
        }
    }
    public function GetSubsidies(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://philips.ezcommerce.io/cron/subsidy-feed",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 6cbc0847-95c7-3290-539f-74f54af75a6e"
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
    public function actionSaveSubsidies(){
        $subsidies = $this->GetSubsidies();
        $subsidies = json_decode($subsidies,1);
        //$this->debug($subsidies);
        foreach ( $subsidies as $value ){
            //$this->debug($value);
            $skuId = HelpUtil::exchange_values('sku','id',$value['sku'],'products');
            //echo $value['sku'];
            //echo $skuId;//die;
            if ($value['channel_id']=='1')
                $channelid='22';
            if ($value['channel_id']=='2')
                $channelid='24';
            if ($value['channel_id']=='15')
                $channelid='23';

            $findSubsidy = Subsidy::find()->where(['sku_id'=>$skuId,'channel_id'=>$channelid,'start_date'=>$value['start_date'],'end_date'=>$value['end_date']])
                ->asArray()->all();
            //var_dump();die;
            if ( !$findSubsidy ){


                $addSkusCrawl = new Subsidy();
                $addSkusCrawl->sku_id=$skuId;
                $addSkusCrawl->channel_id=$channelid;
                $addSkusCrawl->subsidy=$value['subsidy'];
                $addSkusCrawl->margins=$value['margins'];
                $addSkusCrawl->created_at=$value['created_at'];
                $addSkusCrawl->updated_at=$value['updated_at'];
                $addSkusCrawl->created_by=(Yii::$app->user->getId()==NULL) ? 1 : Yii::$app->user->getId();
                $addSkusCrawl->updated_by=(Yii::$app->user->getId()==NULL) ? 1 : Yii::$app->user->getId();
                $addSkusCrawl->ao_margins=$value['ao_margins'];
                $addSkusCrawl->start_date=$value['start_date'];
                $addSkusCrawl->end_date=$value['end_date'];
                $addSkusCrawl->save();
                if ($addSkusCrawl->errors){
                    $this->debug($addSkusCrawl->errors);
                }
            }


            //$addSkusCrawl->created_by =
            //die;
        }
    }
    private function GetCrawledData(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://philips.ezcommerce.io/cron/crawled-results",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 473e356b-7a9f-0c43-8c92-be5818854fe8"
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
    public function actionSaveCrawledData(){
        $data = $this->GetCrawledData();
        $data = json_decode($data,true);
        $response=[];
        foreach ( $data as $key=>$value ){
            $skuId = $this->exchange_values('sku','id',$value['sku'],'products');
            if ( $value['channel_id'] == '2' ){
                $channelId = '24';
                $marketplace='shopee';
            }
            elseif ($value['channel_id']=='1'){
                $channelId='22';
                $marketplace='lazada';
            }
            $Find = TempCrawlResults::find()->where(['sku_id'=>$skuId,'channel_id'=>$channelId,'marketplace'=>$marketplace,'added_at'=>$value['added_at']])->one();
            if ( !$Find ){
                $addRecord = new TempCrawlResults();
                $addRecord->sku_id = $skuId;
                $addRecord->product_id = $value['product_id'];
                $addRecord->channel_id = $channelId;
                $addRecord->marketplace = $marketplace;
                $addRecord->product_name = $value['product_name'];
                $addRecord->price = $value['price'];
                $addRecord->seller_name = $value['seller_name'];
                $addRecord->added_at = $value['added_at'];
                $addRecord->created_at = $value['created_at'];
                $addRecord->updated_at = $value['updated_at'];
                $addRecord->created_by = 1;
                $addRecord->updated_by = 1;
                $addRecord->save();
            //    $response[]='Added Successfuly';
            }else{
          //      $response[]='Duplicate Record, Not Added';
            }
            //$this->debug($response);
        }
        //$this->debug($response);
    }
    public function actionGetOrdersFromOld(){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://philips.ezcommerce.io/cron/get-orders",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "postman-token: 47470789-cbdd-9d9c-d734-00423fbabb85"
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
    public function actionUpdateOrdersTimes(){
        $ordersData = $this->actionGetOrdersFromOld();
        $ordersData = json_decode($ordersData,true);
        $new_data = [];
        $refetch_orders_from_api = [];
        $errors_when_updating_orders = [];
        $erros_when_updating_items = [];
        foreach ( $ordersData as $key=>$value ){
            $new_data[$value['order_id']][] = $value;
        }
        $counter=1;
        foreach ( $new_data as $order_id=>$order_detail ){

            //$this->debug($order_detail);
            $order_created_at = $order_detail[0]['order_created_at'];
            $order_updated_at = $order_detail[0]['order_updated_at'];

            $updateOrderTimeZone = Orders::findOne(['order_id'=>$order_id]);

            //echo $order_id_pk;die;
            if ( $updateOrderTimeZone ){
                continue;
                $order_id_pk = $updateOrderTimeZone->id;
                $updateOrderTimeZone->order_created_at = $order_created_at;
                $updateOrderTimeZone->order_updated_at = $order_updated_at;
                $updateOrderTimeZone->timezone_updated = 1;
                $updateOrderTimeZone->update();
                if ( !empty($updateOrderTimeZone->errors) ){
                    $errors_when_updating_orders [] = $updateOrderTimeZone->errors;
                    //$this->debug($updateOrderTimeZone->errors);
                }
                foreach ( $order_detail as $key=>$item_detail ){
                    $item_created_at = $item_detail['item_created_at'];
                    $item_updated_at = $item_detail['item_updated_at'];
                    //echo $item_detail['order_item_id'];
                    //die;
                    $updateOrderItemTimeZone = OrderItems::findOne(['order_item_id'=>$item_detail['order_item_id'],'order_id'=>$order_id_pk]);
                    if ($updateOrderItemTimeZone){
                        $updateOrderItemTimeZone->item_created_at = $item_created_at;
                        $updateOrderItemTimeZone->item_updated_at = $item_updated_at;
                        $updateOrderItemTimeZone->timezone_updated = 1;
                        $updateOrderItemTimeZone->update();
                        if ( !empty($updateOrderItemTimeZone->errors) ){
                            $erros_when_updating_items[] = $updateOrderItemTimeZone->errors;
                            //$this->debug($updateOrderItemTimeZone->errors);
                        }
                    }else{
                        //$refetch_orders_from_api[] = $order_id;
                    }

                }

            }else{
                $refetch_orders_from_api[] = $order_id;
            }
            echo $order_id .' --- '.$counter;
            echo '<br />';
            //die;
            $counter++;
        }
        echo '<h1>Order not found</h1>';
        echo json_encode($refetch_orders_from_api);

        echo '<h1>Error when updating order</h1>';
        echo json_encode($errors_when_updating_orders);

        echo '<h1>errors when updating items.</h1>';
        echo json_encode($erros_when_updating_items);
        die;

        //$this->debug($new_data);
    }
    public function actionUpdateTimeZone(){
        $Order = Orders::findBySql("SELECT * FROM orders WHERE order_id = '241711286407547' AND timezone_updated = 0")->asArray()->all();

        $this->debug($Order);
        foreach ( $Order as $key1=>$value1 ){
            echo $Order[0]['order_created_at'];
            echo '<br />';

            //date_default_timezone_set('Asia/Kuala_Lumpur');
            $order_created_at = date('Y-m-d H:i:s',strtotime('+8 hours',strtotime($Order[0]['order_created_at'])));
            $order_updated_at = date('Y-m-d H:i:s',strtotime('+8 hours',strtotime($Order[0]['order_updated_at'])));

            $updateOrderTimeZone = Orders::findOne(['order_id'=>$value1['order_id']]);
            $updateOrderTimeZone->order_created_at = $order_created_at;
            $updateOrderTimeZone->order_updated_at = $order_updated_at;
            $updateOrderTimeZone->timezone_updated = 1;
            $updateOrderTimeZone->update();
            if ( !empty($updateOrderTimeZone->errors) ){
                $this->debug($updateOrderTimeZone->errors);
            }else{
                $Order_Items = Orders::findBySql("SELECT * FROM order_items WHERE order_id = '".$Order[0]['id']."' AND timezone_updated = 0")->asArray()->all();
                foreach ( $Order_Items as $key=>$value ){
                    $item_created_at = date('Y-m-d H:i:s',strtotime('+8 hours',strtotime($value['item_created_at'])));
                    $item_updated_at = date('Y-m-d H:i:s',strtotime('+8 hours',strtotime($value['item_updated_at'])));

                    $updateOrderItemTimeZone = OrderItems::findOne($value['id']);
                    $updateOrderItemTimeZone->item_created_at = $item_created_at;
                    $updateOrderItemTimeZone->item_updated_at = $item_updated_at;
                    $updateOrderItemTimeZone->timezone_updated = 1;
                    $updateOrderItemTimeZone->update();
                    if ( !empty($updateOrderItemTimeZone->errors) ){
                        $this->debug($updateOrderItemTimeZone->errors);
                    }
                }
            }
            //die;
        }

    }
    public function actionFeedDetail(){
        $channel = Channels::find()->where(['id'=>21])->one();
        $response = WalmartUtil::getFeedDetail($channel,'118B47C3034C464FA99BDE2A9E408715@AREBAgA');
        $this->debug($response);
    }
    public function actionSkuPromotions(){
        $channel = Channels::find()->where(['id'=>21])->one();
        $response = WalmartUtil::getSkuPromotions($channel,'ADIPHG01_M_WHT_RED');
        $response = json_decode($response,true);
        $this->debug($response);
    }
    public function actionDeleteSkuPromotion(){
        $xml=[];
        $xml[] = WalmartUtil::deletePromotionXml('ADIPHG01_M_WHT_RED','2020-07-10T08:00:00.762Z','2020-07-10T08:02:00.762Z');
        $xml_chunks = array_chunk($xml,1000);
        $channel = Channels::find()->where(['id'=>21])->one();
        $response = WalmartUtil::updateSalePricesInBulk($channel,$xml_chunks);
        //$this->debug($response);
    }
    public function actionSendCustomerData(){
        echo "started_at:" . date('Y-m-d H:i:s'). "<br/>";
        if(!isset($_GET['client']))
            die('client name required');

        $ordersData =  CronUtil::getDelieveredOrdersData($_GET['client']);
      //  self::debug($ordersData);
        CronUtil::export_customers_csv($ordersData);

        echo "ended_at:" . date('Y-m-d H:i:s'). "<br/>";
    }
    public function actionKillQuery(){
        $CurrentProcessesAfterKill = CronUtil::KillQuery();
    }
    public function actionFetchSpiritCombatStocks(){
        // get warehouse settings
        $warehouseSettings = Warehouses::find()->where(['name'=>'spiritcombatsports','warehouse'=>'magento-warehouse'])->one();
        $warehouseSettings = $warehouseSettings->settings;
        $warehouseSettings = json_decode($warehouseSettings,true);
        //$this->debug($warehouseSettings);

        $get_sku_list = HelpUtil::getSpiritSportsSkus();
        //$this->debug($get_sku_list);

        foreach ( $get_sku_list as $value ){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $warehouseSettings['url']."rest/V1/stockItems/".$value['sku'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer ".$warehouseSettings['access_token'],
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: bb5b2541-0cd8-37f5-ae36-f02d4139394a"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                //self::debug();
                $result = json_decode($response);
                if (isset($result->qty)){
                    // update stock
                    $updateSkuStock = WarehouseStockList::findOne([$value['id']]);
                    $updateSkuStock->available=$result->qty;
                    $updateSkuStock->update();
                    //die;
                }
            }
        }
    }
    public function actionPullSpiritCombatStocks(){
        echo date('H:i:s');
        echo '<br />';

        // get warehouse settings
        $warehouseSettings = Warehouses::find()->where(['name'=>'spiritcombatsports','warehouse'=>'magento-warehouse'])->one();
        $warehouseSettings = $warehouseSettings->settings;
        $warehouseSettings = json_decode($warehouseSettings,true);


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $warehouseSettings['url']."rest/V1/products/?searchCriteria[pageSize]=500&searchCriteria[currentPage]=1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer ".$warehouseSettings['access_token'],
                "cache-control: no-cache",
                "content-type: application/json",
                "postman-token: bb5b2541-0cd8-37f5-ae36-f02d4139394a"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $result = json_decode($response,true);
        foreach( $result['items'] as $key=>$value ){
            //echo $value['sku'];die;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $warehouseSettings['url']."rest/V1/stockItems/".$value['sku'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer ".$warehouseSettings['access_token'],
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "postman-token: bb5b2541-0cd8-37f5-ae36-f02d4139394a"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            $stock_response=json_decode($response,true);
            if (isset($stock_response['qty'])){
                // update stock
                $findStock = WarehouseStockList::find()->where(['warehouse_id'=>$warehouseSettings->id,'sku'=>$value['sku']])->one();
                if ($findStock){
                    $updateSkuStock = WarehouseStockList::findOne($findStock->id);
                    $updateSkuStock->available=$stock_response['qty'];
                    $updateSkuStock->update();
                }
                else{
                    $createStock = new WarehouseStockList();
                    $createStock->warehouse_id=$warehouseSettings->id;
                    $createStock->sku=$value['sku'];
                    $createStock->available = $stock_response['qty'];
                    $createStock->added_at=date('Y-m-d H:i:s');
                    $createStock->updated_at=date('Y-m-d H:i:s');
                    $createStock->save();
                }
                //die;
            }
        }
        //echo $response;die;
        //curl_close($curl);
        echo date('H:i:s');
    }
    public function actionPushOrdersToThirdPartyPlatform(){
        // get orders
        $orders = OrderUtil::GetThirdPartyOrders();
        //$this->debug($orders);
        $order_success_push=[];
        if (!$orders){
            echo 'No new orders found';
        }
        foreach ( $orders as $orderid => $orderDetail ){
            if ( $orderDetail['OrderDetail']['warehouse-type'] == 'magento-warehouse' ){
                $token='';
                // get access token
                $warehouse_detail = Warehouses::find()->where(['id'=>$orderDetail['OrderDetail']['warehouse_id']])->one();
                if ($warehouse_detail){
                    if ( $this->isJson($warehouse_detail->settings) ){
                        $warehouse_settings = json_decode($warehouse_detail->settings,true);
                        if (isset($warehouse_settings['access_token'])){
                            $token = $warehouse_settings['access_token'];
                        }
                    }
                }
                if ($token==''){
                    echo 'Token not found for the warehouse '.$orderDetail['OrderDetail']['warehouse_id'];
                    continue;
                }
                // create the guest-cart
                $get_cart_id = MagentoUtil::CreateGuestCart($warehouse_settings);
                //$this->debug($get_cart_id);
                //$this->debug($get_cart_id);

                if ( $get_cart_id['status'] == 0 ){
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],'Curl Error',$get_cart_id['error'],0);
                    continue;
                }
                else if ($this->isJson($get_cart_id['token'])){
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],'CREATE THE GUEST CART',$get_cart_id,0);
                    continue;
                }else{
                    $guest_checkout_id = str_replace('"','',$get_cart_id['token']);
                }

                // add product in cart
                $error_when_adding_in_cart=0;
                foreach ( $orderDetail['OrderItems'] as $item_detail ){
                    $item=MagentoUtil::AddItemsInGuestCheckout($guest_checkout_id,$warehouse_settings,$item_detail);
                    $item=json_decode($item,1);
                    if ( isset($item['message']) && $item['message']!='' ){
                        $error_when_adding_in_cart=1;
                        MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],'ADD PRODUCT IN CART',json_encode($item),0);
                        break;
                    }
                }
                if ($error_when_adding_in_cart){
                    continue;
                }

                // get estimate shipping cost
                $estimate_shipping = MagentoUtil::GuestCartEstimateShippingCost($guest_checkout_id,$orderDetail['CustomerInformation'],$warehouse_settings);
                $estimate_shipping = json_decode($estimate_shipping,true);
                if ( isset($response['message']) && $response['message']!='' ){
                    $error_type='NOT ABLE TO GET ESTIMATE SHIPPING';
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],$error_type,$estimate_shipping,0);
                    continue;
                }

                // add shipping & billing information in cart
                $warehouse_settings['shipping_method_code'] = $estimate_shipping[0]['method_code'];
                $warehouse_settings['shipping_carrier_code'] = $estimate_shipping[0]['carrier_code'];
                $response = MagentoUtil::AddGuestCartOrderShippingDetails($guest_checkout_id,$orderDetail['CustomerInformation'],$warehouse_settings);
                $response = json_decode($response,true);
                if ( isset($response['message']) && $response['message']!='' ){
                    $error_type='ADD SHIPPING INFORMATION IN CART';
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],$error_type,json_encode($response),0);
                    continue;
                }

                // select payment method and order now
                $orderId = MagentoUtil::GuestCheckoutSelectPaymentMethodAndOrderNow($guest_checkout_id,$warehouse_settings);
                $orderId = json_decode($orderId,true);

                if ( isset($orderId['message']) && $orderId['message']!='' ){
                    $error_type='SELECT PAYMENT METHOD AND ORDER NOW';
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],$error_type,$orderId,0);

                }else{
                    // make entry in third party orders table
                    $third_party_order_detail = MagentoUtil::GetOrderDetail($orderId,$warehouse_settings);
                    MagentoUtil::CreateThirdPartyEntry($orderDetail['OrderDetail']['order_id'],$orderId,$third_party_order_detail);
                    MagentoUtil::CreateThirdPartyLog($orderDetail['OrderDetail']['order_id'],'',$orderId,1);
                    $order_success_push[]=1;
                }
            }
        }
        // send log email to developers if third party orders not pushed successfully

        echo '<br />'.count($order_success_push).' New orders pushed to Third party';
    }
    public function actionGetThirdPartyOrderStatus(){

        $result = OrderUtil::GetThirdPartyPushedOrders();

        foreach ( $result as $value ){
            if ( $value['warehouse']=='magento-warehouse' ){
                $warehouse_settings = json_decode($value['warehouse_settings'],true);
                $orderDetail = MagentoUtil::GetOrderDetail($value['thirdparty_order_id'],$warehouse_settings);
                $orderDetail = json_decode($orderDetail,true);
                //$this->debug($orderDetail);
                $tracking_number = MagentoUtil::GetTrackingNumbersOfOrder($value['thirdparty_order_id'],$warehouse_settings);
                $mapped_status= OrderUtil::mapOrderStatus((string)$orderDetail['status']); // marketplace status mapped with our statuses

                // update order item
                $update_order_item = OrderItems::findOne(['order_id'=>$value['ezcomm_order_id'],'item_sku'=>$value['item_sku']]);
                $update_order_item->item_status = $mapped_status;
                $update_order_item->item_market_status = $orderDetail['status'];
                $update_order_item->tracking_number = implode(',',$tracking_number);
                $update_order_item->item_updated_at = date('Y-m-d H:i:s');
                $update_order_item->update();
                //die;

                if ($mapped_status=='completed'){
                    $update_order_item = OrderItems::findOne(['order_id'=>$value['ezcomm_order_id'],'item_sku'=>$value['item_sku']]);
                    // check if already exist
                    $shipment = OrderShipment::find()->where(['order_item_id'=>$update_order_item->id])->asArray()->all();

                    if (!$shipment){
                        $create_order_shipment = new OrderShipment();
                        $create_order_shipment->order_item_id=$update_order_item->id;
                        $create_order_shipment->amount_inc_taxes=(isset($orderDetail['shipping_amount']) && $orderDetail['shipping_amount']!='') ? $orderDetail['shipping_amount'] : 0.00;
                        $create_order_shipment->amount_exc_taxes=(isset($orderDetail['shipping_amount']) && $orderDetail['shipping_amount']!='') ? $orderDetail['shipping_amount'] : 0.00;
                        $create_order_shipment->extra_charges=0.00;
                        $create_order_shipment->system_shipping_status='completed';
                        $create_order_shipment->courier_shipping_status ='completed';
                        $create_order_shipment->is_completed=0;
                        $create_order_shipment->is_tracking_updated=0;
                        $create_order_shipment->shipping_date=date('Y-m-d H:i:s');
                        $create_order_shipment->added_at=date('Y-m-d H:i:s');
                        $create_order_shipment->updated_at=date('Y-m-d H:i:s');
                        $create_order_shipment->save();
                        if ($create_order_shipment->errors){
                            //$this->debug($create_order_shipment->errors);
                        }
                    }
                }
                elseif ($mapped_status=='canceled'){
                    $update_order_item = OrderItems::findOne(['order_id'=>$value['ezcomm_order_id'],'item_sku'=>$value['item_sku']]);
                    // check if already exist
                    $shipment = OrderShipment::find()->where(['order_item_id'=>$update_order_item->id])->one();

                    if (!$shipment){
                        $create_order_shipment = new OrderShipment();
                        $create_order_shipment->order_item_id=$update_order_item->id;
                        $create_order_shipment->amount_inc_taxes=(isset($orderDetail['shipping_amount']) && $orderDetail['shipping_amount']!='') ? $orderDetail['shipping_amount'] : 0.00;
                        $create_order_shipment->amount_exc_taxes=(isset($orderDetail['shipping_amount']) && $orderDetail['shipping_amount']!='') ? $orderDetail['shipping_amount'] : 0.00;
                        $create_order_shipment->extra_charges=0.00;
                        $create_order_shipment->system_shipping_status='canceled';
                        $create_order_shipment->courier_shipping_status ='canceled';
                        $create_order_shipment->is_completed=0;
                        $create_order_shipment->is_tracking_updated=0;
                        $create_order_shipment->shipping_date=date('Y-m-d H:i:s');
                        $create_order_shipment->added_at=date('Y-m-d H:i:s');
                        $create_order_shipment->updated_at=date('Y-m-d H:i:s');
                        $create_order_shipment->save();
                        if ($create_order_shipment->errors){
                            //$this->debug($create_order_shipment->errors);
                        }
                    }else{
                        $shipment->system_shipping_status='canceled';
                        $shipment->courier_shipping_status='canceled';
                        $shipment->updated_at=date('Y-m-d H:i:s');
                        $shipment->update();
                    }
                }else{
                    // otherwise make log of it
                    $add_log = OrderShipmentHistory::find()->where(['order_item_id'=>$update_order_item->id,'courier_status'=>(string)$orderDetail['status']])->one();
                    if (!$add_log){
                        $add_log = new OrderShipmentHistory();
                        $add_log->order_item_id=$update_order_item->id;
                        $add_log->system_status = $mapped_status;
                        $add_log->courier_status = (string) $orderDetail['status'];
                        $add_log->tracking_number = implode(',',$tracking_number);
                        $add_log->added_at = date('Y-m-d H:i:s');
                        $add_log->save();
                    }
                }

                // check all order item statuses and set as completed if all items are shipped or completed
                $order_items = OrderItems::find()->where(['order_id'=>$value['ezcomm_order_id']])->asArray()->all();
                $item_statues = [];
                foreach ( $order_items as $oi_detail ){
                    $item_statues[] = $oi_detail['item_status'];
                }

                if ( !in_array('canceled',$item_statues) && !in_array('pending',$item_statues) ){
                    // update order as completed
                    $update_order = Orders::findOne($value['ezcomm_order_id']);
                    $update_order->order_status = 'completed';
                    $update_order->order_updated_at = date('Y-m-d H:i:s');
                    $update_order->update();
                }

            }
        }
    }
    public function actionSendEmailThirdPartyUnpushedOrdersLog(){
        $email= MagentoUtil::GetThirdPartyOrderLog();

    }

    /****
     * set product status to new on marketplace
     */
    public function actionSetProductNew()
    {

        echo "started @".date('Y-m-d H:i:s');
        echo "<br/>";
        $products=SetProductNew::find()->where(['updated'=>0,'error_in_update'=>0])->with('channel')->asArray()->all();
        //self::debug($products);
        foreach($products as $product)
        {
            /**************if magento******************/
            if($product['channel']['marketplace']=="magento")
            {
                $response=MagentoUtil::set_product_as_new((object)$product['channel'],$product);
                if(isset($response['status'])){
                     Yii::$app->db->createCommand()
                        ->update('set_product_new', ['updated' =>  $response['updated'],'error_in_update'=>$response['error'],'message'=>$response['msg']], ['sku'=>$product['sku'],'channel_id'=>$product['channel']['id']])
                        ->execute();
                }
            }
            /**************if prestashop******************/
            if($product['channel']['marketplace']=="prestashop")
            {
                $pk_id=ChannelsProducts::findOne(['channel_sku'=>$product['sku'],'channel_id'=>$product['channel']['id'],'deleted'=>0]);
                if($pk_id)
                {
                        $product['pk_sku']=$pk_id['sku'];
                        $response=PrestashopUtil::set_product_as_new((object)$product['channel'],$product);
                        //self::debug($response);
                        if(isset($response['status']))
                        {
                            Yii::$app->db->createCommand()
                                ->update('set_product_new', ['updated' =>  $response['updated'],'message'=>$response['msg'],'error_in_update'=>$response['error']], ['sku'=>$product['sku'],'channel_id'=>$product['channel']['id']])
                                ->execute();
                        }
                } else{
                    Yii::$app->db->createCommand()
                        ->update('set_product_new', ['updated' => 0,'error_in_update'=>1,'message'=>'product not exists in ezcom'], ['sku'=>$product['sku'],'channel_id'=>$product['channel']['id']])
                        ->execute();
                }

            }

        }
        echo "Ended @". date('Y-m-d H:i:s');
        echo "<br/>";
    }

    /****************will check images on server from raw csv
     * and convert raw csv given by client to module format
     specially for global mobile client**************/
    public function actionGlobalMobileCsvSwapper()
    {
        echo "Started @". date('Y-m-d H:i:s');
        echo "<br/>";
        $records=GlobalMobileCsvRecords::find()->where(['csv_processed'=>0])->andWhere(['csv_output'=>null])->asArray()->all();
        if($records)
        {
            foreach($records as $record)
            {
                GlobalMobileUtil::check_images_availibility($record['id']); // check from server if images are available
                GlobalMobileUtil::refill_closest_variation_images($record['id']); // assign to images to variation from there siblings if not have based on color and same title
                $res=GlobalMobileUtil::convert_csv($record['id']); // convert csv according to new format
                if(isset($res['file'])){
                    Yii::$app->db->createCommand()
                        ->update('global_mobile_csv_records', ['csv_output' =>  $res['file'],'csv_ouput_added_at'=>date('Y-m-d H:i:s'),'csv_processed'=>1], ['id'=>$record['id']])
                        ->execute();
                }
            }

        }
        echo "Ended @". date('Y-m-d H:i:s');
        echo "<br/>";
    }

    /********get images of amazon products / new seller partner api *******/
    public function actionGetAmazonMissingImages()
    {
        if(isset($_GET['prefix']) && $_GET['prefix'])
        {
            $channel = Channels::find()->where(['prefix'=>$_GET['prefix'],'is_active'=>'1'])->one();
            if($channel)
            {
                    AmazonSellerPartnerUtil::getProductImage($channel);
            }

        }else{
            die('prefix required');
        }
    }
    /***
     * execute bulk shipment queue
     */
    public function actionExecuteBulkShipmentQueue()
    {
        echo " Started @" .Date('Y-m-d H:i:s');
        $orders_list=BulkOrderShipment::find()->where(['status'=>'pending'])->asArray()->all();
        //echo "<pre>";
        //print_r($orders_list); die();
        if($orders_list):
            foreach($orders_list as $row)
            {
                $order = Orders::findone(['id' => $row['order_id']]);
                $channel=Channels::findone(['id'=>$order->channel_id]);
                $courier = Couriers::findone(['id' => $row['courier_id']]);
                $items = OrderItems::find()->where(['order_id' => $row['order_id'], 'item_status' => 'pending'])
                    ->andWhere(['IS NOT', 'fulfilled_by_warehouse', null])
                    ->andWhere(['IS', 'tracking_number', null])
                    ->asArray()->all();
                if(!$items)
                    continue;

                $warehouse=Warehouses::findOne(['id'=>$items[0]['fulfilled_by_warehouse']]);
                $customer = OrderUtil::GetCustomerDetailByPk($row['order_id']);
                //$address=self::address_input_validate($customer); // match state and country abbrevation
                // print_r($address); die();
                ////

                $params = [//'shipper' => CourierUtil::bulk_shipping_shipper_address($warehouse),
                    // 'customer' => CourierUtil::bulk_shipping_customer_address($address) ,
                    //'package' => $package,
                    //'package_type' => $package_type,
                    //'service' => $service,
                    'order_number'=>$order->order_number,
                    'order'=>$order, // for invoice generation
                    'order_items'=>$items  //for invoice generation
                ];
                /******************LCS Courier***************/
                if($courier->type=="lcs")
                {
                    /******validate address***/
                    $params['customer']=LCSUtil::bulk_shipping_customer_address_validation($customer);
                    $params['shipper']=LCSUtil::bulk_shipping_shipper_address_validation($warehouse);
                    $params['shipper']['name']=(isset($channel->name) && $channel->name=="pedro") ? "Pedro pakistan":$params['shipper']['name'];
                    if(isset($params['customer']['status']) && $params['customer']['status']=="failure") { // update status failure
                        Yii::$app->db->createCommand()
                            ->update('bulk_order_shipment', ['status' =>  'failed','comment'=>$params['customer']['error'],'updated_at'=>date('Y-m-d H:i:s')], ['id'=>$row['id']])
                            ->execute();
                        continue;
                    }

                    $params['package']=['length'=>'1','width'=>'1','height'=>'1','weight'=>count($items)];
                    $params['shipping_charges']=$order->order_shipping_fee; // charges of shipping
                    if(in_array(strtolower($order->payment_method),['sample_gateway','hbl pay','online']) && $order->order_market_status!='pending_payment')
                        $params['amount_to_collect']=0.00;
                    else
                        $params['amount_to_collect']=$order->order_total;

                    $params['instructions']='n/a';
                    $response=LCSUtil::submitShipping($courier,$params);
                    $params['grand_total']=$params['amount_to_collect']; // for invoice generation
                    $params['extra_shipping_charges']=$params['shipping_charges']; // for invoice generation to show

                }
                /******************Blue Ex courier***************/
                if($courier->type=="blueex")
                {
                    /******validate address***/
                    $params['customer']=BlueExUtil::bulk_shipping_customer_address_validation($customer);
                    $params['shipper']=BlueExUtil::bulk_shipping_shipper_address_validation($warehouse);
                    $params['shipper']['name']=(isset($channel->name) && $channel->name=="pedro") ? "Pedro pakistan":$params['shipper']['name'];
                    if(isset($params['customer']['status']) && $params['customer']['status']=="failure") { // update status failure
                        Yii::$app->db->createCommand()
                            ->update('bulk_order_shipment', ['status' =>  'failed','comment'=>$params['customer']['error'],'updated_at'=>date('Y-m-d H:i:s')], ['id'=>$row['id']])
                            ->execute();
                        continue;
                    }
                    $params['order_total']=$params['order']->order_total;
                    $response=BlueExUtil::submitShipping($courier,$params);
                    $params['grand_total']=$params['order_total']; // for invoice generation
                }
                if(isset($response) && $response['status']!='failure'):

                    /****************generate label and process record************/
                    $packing_slip=CourierUtil::generate_order_invoice($params);
                    $response['packing_slip']=$packing_slip; // merge packing slip in response array
                    // update tracking number and shipping status in local database
                    $order_items_pk=array_column($items,'id');
                    OrderUtil::updateOrderTrackingAndShippingStatus($order_items_pk,$response['label'],$response['tracking_number'],$courier->id ,'shipped');
                    OrderUtil::UpdateOrderStatus( $order->channel_id, $order->order_number); // update main order table status based on order item status

                    $response['system_shipping_status']='shipped'; //
                    $response['courier_shipping_status']='shipped'; //

                    /// embed order item_ids into response , algo to handle fedex multi package issue for generic function
                    $response_final=[];
                    foreach($items as $item)
                    {
                        $response['order_item_id']=$item['id'];
                        $response_final[]=$response;
                    }
                    //// add order shipment detail to order shipment table
                    CourierUtil::addOrderShipmentDetail($response_final);
                    /***update bulk order shipment table record*****/
                    Yii::$app->db->createCommand()
                        ->update('bulk_order_shipment', ['status' =>  'completed','comment'=>$response['system_shipping_status'],'updated_at'=>date('Y-m-d H:i:s')], ['id'=>$row['id']])
                        ->execute();
                else:
                    $comment=isset($response['error']) ? $response['error']:"Failed to process shipping";
                    Yii::$app->db->createCommand()
                        ->update('bulk_order_shipment', ['status' =>  'failed','comment'=>$comment,'updated_at'=>date('Y-m-d H:i:s')], ['id'=>$row['id']])
                        ->execute();
                endif;
            } // for loop ending
        endif;
        echo " Ended @" .Date('Y-m-d H:i:s');
    }

}