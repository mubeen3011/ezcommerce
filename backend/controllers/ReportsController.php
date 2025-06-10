<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/21/2018
 * Time: 10:39 AM
 */
namespace backend\controllers;

use backend\util\ReportsUtil;
use Codeception\PHPUnit\ResultPrinter\Report;
use common\models\StockPriceResponseApi;
use yii\web\Controller;

class ReportsController extends GenericGridController {

    public function actionSkusCrawlReport(){

        $GetTodaySkusCrawled=ReportsUtil::GetSkusListToCrawl();
        return $this->render('index',['crawled_skus'=>$GetTodaySkusCrawled]);

    }

    public function actionStockSyncReport(){

        // For lazada shops

        $Shops=[];
        $Shops['Lazada'][] = ReportsUtil::LazadaBlipStockReport();
        //$Shops['Lazada'][] = ReportsUtil::NineONineStockReport();
        //$Shops['Lazada'][] = ReportsUtil::Deal4ULazadaStockReport();
        $Shops['Lazada'][] = ReportsUtil::AventLazadaStockReport();


        // For Street shops
        //$Shops['Street'][] = ReportsUtil::ElsStockReport();


        return $this->render('stock-sync-report',['Shops_Stock_Sync_Status'=>$Shops]);

    }
    public function actionFetchFailedSkus(){
        $Shop_Id = $this->exchange_values('name','id',$_GET['shop_name'],'channels');
        if ($Shop_Id==1)
            $Index='blipLazada';
        elseif ($Shop_Id==10)
            $Index='909Lazada';
        elseif ($Shop_Id==15)
            $Index='aventLazada';
        elseif ($Shop_Id==13)
            $Index='deal4ULazada';
        // Get the last log of stock update
        $Stock_Update_Log = json_decode(ReportsUtil::GetLastLogStockUpdate($Shop_Id));
        $Faild_Updates = [];

        foreach ( $Stock_Update_Log->$Index as $key=>$value ){
            $Response = json_decode($value);
            if ( $Response->code != '0' ){
                //$this->debug($Response);
                $Faild_Updates[]=['Sku'=>$Response->detail[0]->seller_sku,
                                  'message'=>$Response->message,
                                  'detail_message'=>$Response->detail[0]->message
                ];
            }
        }
        return json_encode($Faild_Updates);
    }
    public function exchange_values($from, $to, $value, $table)
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

    public function actionGeneric(){
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/reports/generic-info',
                    'sortUrl' => '/reports/generic-info-sort',
                    'filterUrl' => '/reports/generic-info-filter',
                    'jsUrl'=>'/reports/generic',
                ],
                'thead'=>
                    [
                        'SKU' => [
                            'data-field' => 'p.sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'p.sku',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Channel' => [
                            'data-field' => 'c.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Price should be' => [
                            'data-field' => 'ccpp.price_should_be',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ccpp.price_should_be',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Price is' => [
                            'data-field' => 'ccpp.price_is',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ccpp.price_is',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Difference' => [
                            'data-field' => 'ccpp.difference',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ccpp.difference',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Deal Name' => [
                            'data-field' => 'dm.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'dm.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Api Response' => [
                            'data-field' => 'ccpp.api_response',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ccpp.api_response',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                    ]
            ];
        $session = \Yii::$app->session;
        $officeSku = [];
        if ($session->has('sku-imported')) {
            $officeSku = $session->get('sku-imported');
        }

        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku,'config'=>$config]);
        $roleId = \Yii::$app->user->identity->role_id;

        //$this->debug($Foc_List);
        //$this->debug($Sku_List);

        return $this->render('generic-view',['gridview'=>$html,'roleId' => $roleId]);
    }

    public function actionConfigParams(){

        $config=[
            'query'=>[
                'FirstQuery'=>'
                SELECT p.`sku`,c.`name`,ccpp.`price_should_be`,ccpp.`price_is`, ccpp.difference,dm.name as deal_name,
                ccpp.deal_id,
                ccpp.api_response FROM cross_check_product_prices ccpp
                INNER JOIN `products` p ON
                p.`id` = ccpp.`sku_id`
                INNER JOIN `channels` c ON
                c.`id` = ccpp.`channel_id`
                LEFT join deals_maker dm ON 
                ccpp.deal_id = dm.id
                WHERE ccpp.`price_is` IS NOT NULL 
                AND ccpp.api_response NOT LIKE \'%"code":"0"%\' 
                AND ccpp.api_response NOT LIKE \'%Modify discount item success%\'
                AND ccpp.added_at  =\''.date('Y-m-d').'\' AND difference > 0
                
',
                'GroupBy' => 'GROUP BY ccpp.id',
            ],
            'OrderBy_Default'=>'ORDER BY ccpp.id DESC',
            'SortOrderByColumnAlias' => 'ccpp',
        ];
        return $config;
    }
    public function actionStocksProblemNotification(){


        $sql = "SELECT spra.`channel_id` FROM `stock_price_response_api` spra
                WHERE spra.`create_at` BETWEEN '".date('Y-m-d H:i:s', strtotime('-3 hours', time()))."' 
                AND '".date('Y-m-d H:i:s')."'
                GROUP BY spra.`channel_id` ";
        $results = StockPriceResponseApi::findBySql($sql)->asArray()->all();
        $single_index = [];
        foreach ( $results as $value ){
            $single_index[] = $value['channel_id'];
        }
        //$this->debug($single_index);
        $Channels_Should_Syn = [10,1,3,9,13,2,11,15,14,16];
        foreach ( $Channels_Should_Syn as $val ){
            $stk_prb_ch = [];
            if ( !in_array($val,$single_index) ){
                $stk_prb_ch[] = $val;
            }
        }
        //$this->debug($stk_prb_ch);
        if (!empty($stk_prb_ch)){
            $channels_ids = implode(',',$stk_prb_ch);
            $send_email=\Yii::$app->mailer->compose()
                ->setFrom('notifications@ezcommerce.io')
                ->setTo('abdullah.khan@axleolio.com')
                ->setTextBody('Hi, These are the channels which has stock syncing problem. '.$channels_ids)
                ->setSubject('Stock Sync Problem')
                ->send();
        }

        //$this->debug($sql);
    }

}