<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/21/2018
 * Time: 10:48 AM
 */
namespace backend\util;

use common\models\SkusCrawl;
use common\models\StockPriceResponseApi;
use common\models\TempCrawlResults;

class ReportsUtil{

    public static function GetSkusListToCrawl(){
        $List=SkusCrawl::findBySql("SELECT skus.*,pcp.sku,c.name FROM skus_crawl skus
                                        INNER JOIN products pcp ON
                                        pcp.id = skus.sku_id
                                        INNER JOIN channels c ON 
                                        c.id = skus.channel_id")->asArray()->all();


        $Crawled_List = TempCrawlResults::find()->where(['added_at'=>date('Y-m-d')])->asArray()->all();
        $redefine_skus = [];
        foreach ( $Crawled_List as $key=>$value ){
            $redefine_skus[$value['sku_id']][$value['channel_id']]=$value;
        }


        foreach ($List as $key=>$value){
            if ( isset($redefine_skus[$value['sku_id']][$value['channel_id']]) ){
                $List[$key]['Crawl_Detail'] = $redefine_skus[$value['sku_id']][$value['channel_id']];
            }else{
                $List[$key]['Crawl_Detail'] = [];
            }
        }

        return $List;
    }

    public static function LazadaBlipStockReport(){

        $Get_Last_Sync = StockPriceResponseApi::findBySql("SELECT * from stock_price_response_api WHERE channel_id = 1 AND type like 'Stock%'
                                                                ORDER  BY  create_at desc limit 1")->asArray()->all();
        if ( isset($Get_Last_Sync[0]['response']) )
            $LazadaBlip_Report = json_decode($Get_Last_Sync[0]['response']);
        $FinalReport = [];
        $FinalReport['Successfully_Updated']=0;
        $FinalReport['Failed_Updated']=0;
        $FinalReport['Total_Skus']=0;
        $FinalReport['Last_Updated']=(isset($Get_Last_Sync[0]['create_at'])) ? $Get_Last_Sync[0]['create_at'] : '';
        $FinalReport['Shop_Name'] = self::exchange_values('id','name',(isset($Get_Last_Sync[0]['channel_id'])) ? $Get_Last_Sync[0]['channel_id'] : '',
            'channels');
        if ( isset($LazadaBlip_Report) ){
            foreach ( $LazadaBlip_Report->blipLazada as $key=>$value ){
                $Sku_Stock_Update = json_decode($value);
                $FinalReport['Total_Skus']+=1;
                if ( $Sku_Stock_Update->code == 0 ){
                    $FinalReport['Successfully_Updated']+=1;
                }else{
                    $FinalReport['Failed_Updated']+=1;
                }
            }
        }
        return $FinalReport;

    }
    public static function NineONineStockReport(){

        $Get_Last_Sync = StockPriceResponseApi::findBySql("SELECT * from stock_price_response_api WHERE channel_id = 10 AND type like 'Stock%'
                                                                ORDER  BY  create_at desc limit 1")->asArray()->all();
        if ( isset($Get_Last_Sync[0]['response']) ){
            $Report = json_decode($Get_Last_Sync[0]['response']);
        }


        $FinalReport = [];
        $FinalReport['Successfully_Updated']=0;
        $FinalReport['Failed_Updated']=0;
        $FinalReport['Total_Skus']=0;
        $FinalReport['Last_Updated']=(isset($Get_Last_Sync[0]['create_at'])) ? $Get_Last_Sync[0]['create_at'] : '';
        $FinalReport['Shop_Name'] = self::exchange_values('id','name',(isset($Get_Last_Sync[0]['channel_id'])) ? $Get_Last_Sync[0]['channel_id'] : '','channels');
        if ( isset($Report->{'909Lazada'}) ){
            foreach ( $Report->{'909Lazada'} as $key=>$value ){
                $Sku_Stock_Update = json_decode($value);
                $FinalReport['Total_Skus']+=1;
                if ( $Sku_Stock_Update->code == 0 ){
                    $FinalReport['Successfully_Updated']+=1;
                }else{
                    $FinalReport['Failed_Updated']+=1;
                }
            }
        }
        return $FinalReport;

    }
    public static function Deal4ULazadaStockReport(){

        $Get_Last_Sync = StockPriceResponseApi::findBySql("SELECT * from stock_price_response_api WHERE channel_id = 13 AND type like 'Stock%'
                                                                ORDER  BY  create_at desc limit 1")->asArray()->all();
        if ( isset($Get_Last_Sync[0]['response']) ){
            $Report = json_decode($Get_Last_Sync[0]['response']);
        }
        $FinalReport = [];
        $FinalReport['Successfully_Updated']=0;
        $FinalReport['Failed_Updated']=0;
        $FinalReport['Total_Skus']=0;
        $FinalReport['Last_Updated']=(isset($Get_Last_Sync[0]['create_at'])) ? $Get_Last_Sync[0]['create_at'] : '';
        $FinalReport['Shop_Name'] = self::exchange_values('id','name',(isset($Get_Last_Sync[0]['channel_id'])) ? $Get_Last_Sync[0]['channel_id'] : '','channels');
        if ( isset($Report->{'deal4ULazada'}) ){
            foreach ( $Report->{'deal4ULazada'} as $key=>$value ){
                $Sku_Stock_Update = json_decode($value);
                $FinalReport['Total_Skus']+=1;
                if ( $Sku_Stock_Update->code == 0 ){
                    $FinalReport['Successfully_Updated']+=1;
                }else{
                    $FinalReport['Failed_Updated']+=1;
                }
            }
        }

        return $FinalReport;

    }
    public static function AventLazadaStockReport(){

        $Get_Last_Sync = StockPriceResponseApi::findBySql("SELECT * from stock_price_response_api WHERE channel_id = 15 AND type like 'Stock%'
                                                                ORDER  BY  create_at desc limit 1")->asArray()->all();

        if ( isset($Get_Last_Sync[0]['response']) )
            $Report = json_decode($Get_Last_Sync[0]['response']);
        $FinalReport = [];
        $FinalReport['Successfully_Updated']=0;
        $FinalReport['Failed_Updated']=0;
        $FinalReport['Total_Skus']=0;
        $FinalReport['Shop_Name'] = self::exchange_values('id','name',(isset($Get_Last_Sync[0]['channel_id'])) ? $Get_Last_Sync[0]['channel_id'] : '','channels');
        $FinalReport['Last_Updated']=(isset($Get_Last_Sync[0]['create_at'])) ?  $Get_Last_Sync[0]['create_at'] : '';
        if ( isset($Report->{'aventLazada'}) ){
            foreach ( $Report->{'aventLazada'} as $key=>$value ){
                $Sku_Stock_Update = json_decode($value);
                $FinalReport['Total_Skus']+=1;
                if ( $Sku_Stock_Update->code == 0 ){
                    $FinalReport['Successfully_Updated']+=1;
                }else{
                    $FinalReport['Failed_Updated']+=1;
                }
            }
        }

        return $FinalReport;

    }
    public static function ElsStockReport(){

        $Get_Last_Sync = StockPriceResponseApi::findBySql("SELECT * from stock_price_response_api WHERE channel_id = 3 AND type like 'Stock%'
                                                                ORDER  BY  create_at desc limit 1")->asArray()->all();


        $Report = new \SimpleXMLElement($Get_Last_Sync[0]['response']);
        self::debug($Report);
        $FinalReport = [];
        $FinalReport['Successfully_Updated']=0;
        $FinalReport['Failed_Updated']=0;
        $FinalReport['Total_Skus']=0;
        $FinalReport['Shop_Name'] = self::exchange_values('id','name',$Get_Last_Sync[0]['channel_id'],'channels');
        $FinalReport['Last_Updated']=$Get_Last_Sync[0]['create_at'];
        foreach ( $Report->{'aventLazada'} as $key=>$value ){
            $Sku_Stock_Update = json_decode($value);
            $FinalReport['Total_Skus']+=1;
            if ( $Sku_Stock_Update->code == 0 ){
                $FinalReport['Successfully_Updated']+=1;
            }else{
                $FinalReport['Failed_Updated']+=1;
            }
        }
        return $FinalReport;

    }
    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
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
    public  static function GetLastLogStockUpdate($Shop_ID){
        $Get_Log = StockPriceResponseApi::find()->where(['channel_id'=>$Shop_ID])->orderBy('create_at DESC')->limit(1)
            ->asArray()->all();
        return $Get_Log[0]['response'];
    }

}