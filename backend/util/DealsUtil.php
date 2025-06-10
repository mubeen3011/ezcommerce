<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/16/2018
 * Time: 10:44 AM
 */

namespace backend\util;

use common\models\Category;
use common\models\Channels;
use common\models\ChannelsCategoryMappings;
use common\models\ChannelsDetails;
use common\models\ChannelsProducts;
use common\models\CrossCheckProductPrices;
use common\models\DealsMaker;
use common\models\DealsMakerSkus;
use common\models\PossibleSalePrices;
use common\models\Settings;
use common\models\Subsidy;
use Yii;

class DealsUtil
{

    public static function getLatestAutoPriceMargins()
    {
        $settings = Settings::find()->where(['name' => 'auto_deal_margin_settings'])->one();
        $value = json_decode($settings->value, true);
        $margins_limit = $value['limit_margins'];
        $stocks_limit = $value['stock_limit_check'];
        $AutoDealSettings = (self::_callAutoDealSettings());
        $base_margin = '5';

        $connection = Yii::$app->db;
        $date = date("Y-m-d");
        $sql = "SELECT 
              p.`channel_id`,
              prd.id AS sku_id,
              prd.sku,
              p.`low_price`,
              p.`sale_price`,
              c.map_with,
              p.`margins_low_price`,
              smd.`selling_status`,
              smd.`stock_status`,
              smd.`aging_status`,
              smd.`allowed_margins` 
            FROM
              ao_pricing p 
              INNER JOIN `products` prd 
                ON prd.id = p.`sku_id` 
              INNER JOIN `sku_margins_definations` smd 
                ON smd.`sku_id` = prd.id 
              INNER JOIN category c ON c.id = prd.sub_category 
            WHERE p.added_at = \"$date\"
              AND prd.`is_active` = 1 AND ( p.`channel_id` = 1 OR p.`channel_id` = 3 ) AND c.`map_with` <> 'others'";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $v) {
            // refine margin lower price col with decimal
            $mlp = str_replace('%', '', $v['margins_low_price']);
            $mlp = (double)$mlp;

            if ($mlp <= $base_margin && $mlp >= $margins_limit) {
                $v['margins_low_price'] = $mlp;
                $diff = $mlp - $v['allowed_margins'];
                if ($diff <= 5) {
                    // params for price calculation base on approved margins
                    $params['sku_id'] = $v['sku_id'];
                    $params['channel_id'] = $v['channel_id'];
                    $params['price_sell'] = $v['low_price'];
                    $params['allowed_margin'] = $v['allowed_margins'];
                    $params['qty'] = 0;


                    $is_fbl = 0;
                    // calculate shipping cost and get fbl
                    $stock = HelpUtil::getFblStock($params['sku_id'], $params['channel_id']);
                    if ($stock > 0) {
                        $is_fbl = 1;
                    }
                    $params['fbl'] = $is_fbl;

                    $values = HelpUtil::getSkuInfo($params, true);
                    $v['val'] = $values;
                    $params['price_sell'] = str_replace(',', '', $v['val']['sales_price']);
                    unset($params['allowed_margin']);
                    $v['sg_val'] = HelpUtil::getSkuInfo($params);
                    $v['shop'] = self::_suggestedShopId($v['channel_id'], $v['map_with']);
                    if ($v['val']['stocks']['current_stocks'] >= $stocks_limit)
                        $refine[] = $v;

                }

            }

        }
        // PossibleSalePrices::deleteAll();
        $today = date('Y-m-d');
        foreach ($refine as $r) {
            $psp = PossibleSalePrices::find()->where(['sku' => $r['sku']])->andWhere(['like', 'created_dated', $today])->one();
            if (!$psp)
                $psp = new PossibleSalePrices();
            $psp->sugguested_shop_id = $r['shop'];
            $psp->sku = $r['sku'];
            $psp->cost_price = $r['val']['actual_cost'];
            $psp->margin_at_lp = $r['margins_low_price'];
            $psp->possible_margin = $r['allowed_margins'];
            $psp->low_price = $r['low_price'];
            $psp->suggested_selling_price = str_replace(',', '', $r['val']['sales_price']);
            $psp->margin_at_sp = str_replace('%', '', $r['sg_val']['sales_margins']);
            $psp->current_selling_price = str_replace(',', '', $r['sale_price']);
            $psp->margin_abs_diff = abs(($psp->margin_at_lp) - ($psp->margin_at_sp));
            $psp->channel_id = $r['channel_id'];
            $psp->save(false);
        }


    }

    private function _callAutoDealSettings()
    {
        $autoDealSettings = Settings::find()->where(['id' => 13])->asArray()->all();
        return json_decode($autoDealSettings[0]['value']);
    }

    private function _getAllowedMargin()
    {
        $admam = Settings::find()->where(['name' => 'auto_deal_margin_settings'])->asArray()->all();
        $margin = json_decode($admam[0]['value']);
        return $margin->weighted_avg_margins;
    }

    private function _suggestedShopId($channel_id, $main_category)
    {
        // get marketplace
        $ch = Channels::find()->select('marketplace')->where(['id' => $channel_id,'is_active'=>'1'])->asArray()->one();
        $mp = $ch['marketplace'];

        // get shops by marketplace
        $shops = Channels::find()->where(['marketplace' => $mp,'is_active'=>'1'])->all();
        foreach ($shops as $s) {

            $nos = $s->non_official_store;
            $ep = $s->adm_exposure;
            $ccm = ChannelsCategoryMappings::find()->where(['channel_id' => $s->id])->one();
            $cat_point = $ccm->$main_category;

            $total[$s->id][] = $nos + $ep + $cat_point;
        }
        $total = array_keys($total, max($total));
        return $total[0];
    }

    private static function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }

    public static function getPossibleSalePrices()
    {
        $today = date('Y-m-d');
        /**
         * $allow_margin will call the margin ranges from the settings table of auto deal maker
         * */
        $allow_margin = self::_getAllowedMargin();
        $ad7 = PossibleSalePrices::find()->where(['like', 'created_dated', $today])->andWhere(['<=', 'margin_at_sp', $allow_margin->range_1->allowed_margin])->andWhere(['possible_margin' => $allow_margin->range_1->allowed_margin . '.00'])->orderBy(['margin_at_sp' => SORT_ASC])->limit(5)->asArray()->all();
        $ad4 = PossibleSalePrices::find()->where(['like', 'created_dated', $today])->andWhere(['<=', 'margin_at_sp', $allow_margin->range_2->allowed_margin])->andWhere(['possible_margin' => $allow_margin->range_2->allowed_margin . '.00'])->orderBy(['margin_at_sp' => SORT_ASC])->limit(5)->asArray()->all();
        $ad1 = PossibleSalePrices::find()->where(['like', 'created_dated', $today])->andWhere(['<=', 'margin_at_sp', $allow_margin->range_3->allowed_margin])->andWhere(['possible_margin' => $allow_margin->range_3->allowed_margin . '.00'])->orderBy(['margin_at_sp' => SORT_ASC])->asArray()->all();
        $dmList = array_merge($ad7, $ad4, $ad1);
        return $dmList;
    }


    public static function makerAutoDeals()
    {
        $today = date('Y-m-d');
        // run query to get desc data
        // get 5 records which >= -7

        $dmList = self::getPossibleSalePrices();

        $megaDealSkus = $refineDmList = [];

        // check mega deal
        $megaDealSkus = self::getMegaDealSkus();

        foreach ($dmList as $l) {
            $refineDmList[$l['sugguested_shop_id']][] = $l;
        }
        $currentDate = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        $currentDateSub = date("Y-m-d");
        $nextDate = date("Y-m-d H:i:s", strtotime("+1 day"));
        $nextDateSub = date("Y-m-d", strtotime("+1 day"));

        // creating deals
        foreach ($refineDmList as $ch => $list) {
            // deal info
            $channel = Channels::find()->where(['id' => $ch,'is_active'=>'1'])->asArray()->one();
            $sd = $currentDate;
            $ed = $nextDate;
            $motivation = "system auto deal to close to lowest price form competitors";
            $approval = $requestor = '1';
            $status = "new";
            // creating deals
            $dm = new DealsMaker();
            $dm->noAuto = false;
            // put channel id name
            $dm->name = $channel['prefix'] . " Auto_Deal " . date('Y-m-d');
            $dm->channel_id = $ch;
            $dm->motivation = $motivation;
            $dm->requester_id = $requestor;
            $dm->status = $status;
            $dm->start_date = $sd;
            $dm->end_date = $ed;
            $dm->save(false);
            $dmid = $dm->id;

            foreach ($list as $l) {

                // check sku includes in mega deals
                if (isset($megaDealSkus[$ch]) && in_array($l['sku'], $megaDealSkus[$ch]))
                    continue;

                //adding subsidy
                $skuList = HelpUtil::getSkuList('sku');
                $skuId = $skuList[$l['sku']];
                /* $subsidy = Subsidy::find()->where(['channel_id' => $ch])->andWhere(['sku_id' => $skuId])->one();
                if($subsidy)
                {
                    $subsidy->ao_margins = $l['margin_at_sp'];
                    $subsidy->start_date = $currentDate;
                    $subsidy->end_date = $nextDate;
                    $subsidy->save(false);

                } else {
                    $subsidy = new Subsidy();
                    $subsidy->ao_margins = $l['margin_at_sp'];
                    $subsidy->channel_id = $ch;
                    $subsidy->subsidy = '0';
                    $subsidy->margins = '5';
                    $subsidy->sku_id = $skuId;
                    $subsidy->updated_by = '1';
                    $subsidy->start_date = $currentDate;
                    $subsidy->end_date = $nextDate;
                    $subsidy->save(false);

                }*/

                // adding in pricing table
                $params['sku_id'] = $skuId;
                $params['shop_id'] = $l['sugguested_shop_id'];
                $params['channel_id'] = $l['channel_id'];
                $params['price_sell'] = $l['suggested_selling_price'];
                $params['margins'] = $l['margin_at_sp'];
                $params['subsidy'] = '0';
                $params['qty'] = '1';
                $is_fbl = 0;
                // calculate shipping cost and get fbl
                $stock = HelpUtil::getFblStock($params['sku_id'], $params['channel_id']);
                if ($stock > 0 && $stock >= $params['qty']) {
                    $is_fbl = 1;
                }
                $params['fbl'] = $is_fbl;
                $response = HelpUtil::calculationsAutoDeals($params, $today);

                $dms = DealsMakerSkus::find()->where(['deals_maker_id' => $dmid, 'sku_id' => $skuId])->one();
                if (!$dms) {
                    $dms = new DealsMakerSkus();
                    $dms->sku_id = $skuId;
                    $dms->deals_maker_id = $dmid;
                    $dms->status = 'Pending';
                    $dms->approval_id = $approval;
                    $dms->requestor_reason = 'Competitive';
                    $dms->deal_target = '1';
                    $dms->deal_price = $response['sale_price'];
                    $dms->deal_margin = $response['margins'];
                    $dms->deal_margin_rm = $response['rm'];
                    $dms->save(false);
                }

            }
        }

    }

    public static function getTargetCountSkuWise($sku_id)
    {
        $connection = Yii::$app->db;
        $sql = "SELECT dm.`name`,dms.deal_target FROM `deals_maker_skus` dms
                INNER JOIN `deals_maker` dm ON dm.id = dms.`deals_maker_id`
                WHERE  (dm.status = 'new' OR dm.status = 'active')
                AND (NOW( ) BETWEEN  (dm.`start_date` - INTERVAL 10 DAY) AND dm.`end_date`)
                AND dms.`sku_id` = '" . $sku_id . "'";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }

    public static function getThresholdSku($sku, $warehouse)
    {
        $sku_id = self::exchange_values('sku', 'id', $sku, 'products');
        $product_stocks_id = self::exchange_values('sku_id', 'id', $sku_id, 'product_details');
        $connection = Yii::$app->db;
        $sql = "SELECT * FROM product_stocks where stock_id = " . $product_stocks_id;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $Data = [];

        foreach ($result as $key => $value) {
            if ($warehouse == 'isis') {
                $Data['T1'] = $value['isis_threshold'];
                $Data['T2'] = $value['isis_threshold_critical'];
            } else if ($warehouse == 'fbl-blip') {
                $Data['T1'] = $value['fbl_blip_threshold'];
                $Data['T2'] = $value['fbl_blip_threshold_critical'];
            } else if ($warehouse == 'fbl-909-avent') {
                $Data['T1'] = $value['fbl_avent_threshold'];
                $Data['T2'] = $value['fbl_avent_threshold_critical'];
            }
        }
        if (empty($Data)) {
            $Data['T1'] = 0;
            $Data['T2'] = 0;
        }

        return $Data;
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

    public static function GetShopSkus($channelId,$skuList=[]){
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        $cond = '';
        if ($skuList){
            $cond = " AND p.sku NOT IN ("."'" . implode ( "', '", $skuList ) . "'".")";
        }
        $sql = "SELECT cp.*, NULL AS variations
                    FROM products p
                    INNER JOIN channels_products cp ON cp.product_id=p.id
                    WHERE p.is_active=1 AND cp.channel_id=$channelId AND cp.variation_id != '' $cond
                    UNION
                    SELECT cp.*, COUNT(cp.id) AS variations FROM channels_products cp
                    INNER JOIN products p on p.id = cp.product_id
                    WHERE cp.channel_id = $channelId AND p.is_active=1 AND cp.is_live=1 $cond
                    GROUP BY cp.sku
                    HAVING variations = 1;";
        $results = ChannelsProducts::findBySql($sql)->asArray()->all();
        return $results;

    }
    public  static function activequery($shop,$connection,$cond='',$additionalSKus='',$additional_join=null)
    {
        $sql = "";
        if ( $cond!='' ){
            $sql .= "SELECT cp.channel_sku,p.id as sku_id, p.sku, p.cost,p.sub_category, null as variations
                    FROM products p
                    INNER JOIN channels_products cp ON cp.product_id=p.id
                    $additional_join
                    WHERE p.is_active=1 AND cp.channel_id=$shop AND cp.variation_id != '' AND cp.is_live=1 
                    $cond

                    UNION

                    SELECT cp.channel_sku,p.id AS sku_id,p.sku , p.cost, p.sub_category, COUNT(cp.id) AS variations FROM channels_products cp
                    INNER JOIN products p on p.id = cp.product_id
                    $additional_join
                    WHERE p.is_active=1 AND cp.is_live=1 AND cp.channel_id = $shop
                     $cond
                    GROUP BY cp.sku
                    HAVING variations = 1";
          //  echo $sql; die();
        }

        if ( gettype($additionalSKus)=='array' && !empty($additionalSKus) && $additionalSKus!='NoSkus' ){
            if ( $cond!='' ){
                $sql .= " UNION ";
            }
            $sql.= "
                    SELECT cp.channel_sku,p.id AS sku_id,p.sku, p.cost, p.sub_category, COUNT(cp.id) AS variations
                    FROM channels_products cp
                    INNER JOIN products p ON p.id = cp.product_id
                    WHERE p.is_active=1 AND cp.is_live=1 AND cp.channel_id = $shop AND p.id IN (".implode(',',$additionalSKus).")
                    GROUP BY cp.sku
                    HAVING variations = 1";
            //echo $sql; die();
        }
        if ($sql!=''){
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
        }else{
            $result = [];
        }

        $list = [];
        $refine = [];
        foreach ( $result as $detail ){
            if ( !in_array($detail['sku'],$list) ){
                $refine[] = $detail;
            }
            $list[] = $detail['sku'];

        }
        if ($refine){
            return $refine;
        }else{
            return [];
        }
    }
    // return all channel products that are live in seller center and base on category

    public static function getActiveSkus($cat=[],$shop,$additionalSKus=[])
    {
        $join = "";
        $cond = "";
        $channel = Channels::find()->where(['id'=>$shop])->one();
        $connection = Yii::$app->db;
       // self::debug($cat);
        if ( !empty($cat) ){
            if ( isset($cat[0]) && count($cat)==1 && $cat[0]=='all' ){
                $getAllCats = Category::find()->where(['is_active'=>1])->asArray()->all();
                $AllCatIds=[];
                foreach ( $getAllCats as $detail ){
                    $AllCatIds[] = $detail['id'];
                }
            }else{
                $AllCatIds = self::GetAllCategoriesByParent($cat);
                $AllCatIds = array_merge($AllCatIds,$cat);
                //self::debug($AllCatIds);
            }
            $join =" INNER JOIN `product_categories` pc ON pc.product_id=p.id
                     INNER JOIN category c on c.id=pc.cat_id ";
            $cond = " AND pc.cate_id IN (".implode(',',$AllCatIds).")";
        }
        $results = self::activequery($shop,$connection,$cond,$additionalSKus,$join);
        return $results;


    }

    public static function getStreetActiveSkus($cat,$shop)
    {
        $connection = Yii::$app->db;
        // getting last update date for 11street as API wont work
        $sql = "SELECT DATE(MAX(last_update)) as maxdate FROM channels_products WHERE channel_id = $shop";
        $command = $connection->createCommand($sql);
        $maxdret = $command->queryOne();
        $lastDate = $maxdret['maxdate'];
        $join = "";
        $cond = "";
        if(!empty($cat) &&  !in_array('all',$cat))
        {
            $cat = "'".implode("','",$cat)."'";
            $join = " INNER JOIN category c ON c.id = p.`sub_category`";
            $cond = " AND c.map_with IN ($cat)";
        }
        $sql = "SELECT channel_sku,p.id as sku_id,p.sku FROM `channels_products` cp
                INNER JOIN products p ON p.sku = cp.channel_sku AND p.`is_foc` != '1'
                $join
                WHERE channel_id = '$shop' AND (channel_sku != '' OR channel_sku IS NOT NULL) AND p.`is_active` = 1 AND cp.last_update LIKE '$lastDate%' $cond";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function setDealImportSummaryMessage($DealImportMessages){
        $AlreadyExistInDeal = (isset($DealImportMessages['AlreadyExistInDeal'])) ? count($DealImportMessages['AlreadyExistInDeal']) : 0;
        $DbErrors = (isset($DealImportMessages['DbErrors'])) ? count($DealImportMessages['DbErrors']) : 0;
        $SuccessFullyAdded = (isset($DealImportMessages['SuccessFullyAdded'])) ? count($DealImportMessages['SuccessFullyAdded']) : 0;
        $SuccessFullyUpdated = (isset($DealImportMessages['SuccessFullyUpdated'])) ? count($DealImportMessages['SuccessFullyUpdated']) : 0;
        $SkuNotFoundInEzCommerce = (isset($DealImportMessages['SkuNotFoundInEzCommerce'])) ? count($DealImportMessages['SkuNotFoundInEzCommerce']) : 0;
        if (isset($DealImportMessages['SkuNotFoundInEzCommerce'])){
            $NotFoundInEzCommerce=implode(',',$DealImportMessages['SkuNotFoundInEzCommerce']);
        }else{
            $NotFoundInEzCommerce='';
        }

        $ErrorInGettingMargin_index = (isset($DealImportMessages['ErrorInGettingMargin'])) ? count($DealImportMessages['ErrorInGettingMargin']) : 0;
        if (isset($DealImportMessages['ErrorInGettingMargin'])){
            $ErrorInGettingMargin=implode(',',$DealImportMessages['ErrorInGettingMargin']);
        }else{
            $ErrorInGettingMargin='';
        }
        //$MarginsNotGetting = (isset($DealImportMessages['ErrorInGettingMargin'])) ? count($DealImportMessages['ErrorInGettingMargin']) : 0;

        $message = '<p>
                    Import Summary <br />
                    <span style="color:green"><b>Sku successfully added in deal : '.$SuccessFullyAdded.' </b></span><br />
                    <span style="color:green"><b>Sku successfully Updated in deal : '.$SuccessFullyUpdated.' </b></span><br />
                    <span style="color:red"><b>Sku cannot import database errors : '.$DbErrors.' </b></span><br />
                    <span style="color:orange"><b>Sku already exist in deal : '.$AlreadyExistInDeal.'</b></span><br />
                    <span style="color:orange"><b>Skus not registered in Ezcommerce : '.$SkuNotFoundInEzCommerce.' , '.$NotFoundInEzCommerce.'</b></span><br />
                    <span style="color:red"><b>System Not Getting The Margins : '.$ErrorInGettingMargin_index.', '.$ErrorInGettingMargin.'  </b></span><br />
                    </p>';
        return $message;
    }
    public static function _dealStats($deal_id){

        $response = [];

        // GMV
        $response['GMV'] = 0;
        $Deal_Skus = DealsMakerSkus::find()->select(['deal_price','deal_target'])
            ->where(['deals_maker_id'=>$deal_id])
            ->andWhere(['>','deal_target','0'])
            ->asArray()
            ->all();
        foreach ($Deal_Skus as $val){
            $response['GMV'] += $val['deal_price'] * $val['deal_target'];
        }



        // Sum of Total Profit
        $response['Rm_Profit'] = 0;
        $Deal_Skus = $Deal_Skus = DealsMakerSkus::find()->select(['deal_margin_rm','deal_target'])
            ->where(['deals_maker_id'=>$deal_id])
            ->andWhere(['>','deal_target','0'])
            ->asArray()
            ->all();
        foreach ($Deal_Skus as $val){
            $response['Rm_Profit'] += $val['deal_margin_rm'] * $val['deal_target'];
        }


        // Total A&O Margin
        if ( $response['GMV'] == 0 ){
            $response['Total_Margin_Percentage'] = 0 * 100;
        }else{
            $response['Total_Margin_Percentage'] = ($response['Rm_Profit'] / $response['GMV']) * 100;
        }

        $response['Total_Margin_Percentage'] = number_format($response['Total_Margin_Percentage'],2);

        return $response;
    }

    /**
     * get deal which is active
     * $not_in_deal_id // mean deal should not be in this id
     */
    public static function get_nearest_active_deal($channel_id,$not_in_deal_id,$sku)
    {
        $connection = Yii::$app->db;
        $sql="SELECT dm.id ,dm.start_date,dm.end_date ,dms.deal_price
              FROM
                `deals_maker` dm
              INNER JOIN `deals_maker_skus` dms
              ON
                dm.id=dms.deals_maker_id
              WHERE
                `dm`.`id`<>'".$not_in_deal_id."'
                AND `dm`.`channel_id`='".$channel_id."'
                AND `dms`.`sku`='".$sku."'
                AND `dm`.`status`='active'
              ORDER BY `dm`.`start_date` DESC LIMIT 1";
        $command = $connection->createCommand($sql);
        return $command->queryOne();

    }
    public static function GetOtherActiveDeals($channelId,$dealId){
        $connection = Yii::$app->db;
        $sql = "SELECT Id, NAME, channel_id, start_date FROM deals_maker WHERE STATUS = 'active' AND channel_id = '$channelId' AND id != '$dealId' ORDER BY start_date desc";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }
    public static function GetSkuPriceOfClosestActiveDeal($activeDeals,$skuId){
        //$sku_text = self::exchange_values('id','sku',$skuId,'products');
        if(isset($activeDeals) /*&& $sku_text=='HD9623/11'*/) {
            foreach ($activeDeals as $val){
                $dskus = DealsMakerSkus::find()->where(['deals_maker_id' => $val["Id"]])->andWhere(['status' => 'Approved'])->andWhere(['sku_id' => $skuId])->asArray()->all();
                if(isset($dskus)){
                    foreach ($dskus as $subval){
                        return $subval["deal_price"];
                    }
                }
            }
        }
        return 0;
    }
    public static function MakeDropdownOptions($data, $value, $text){
        $html = '<option></option>';
        foreach ( $data as $detail ){
            $html .= '<option value="'.$detail[$value].'">'.$detail[$text].'</option>';
        }
        return $html;
    }

    public static function GetAllCategoriesByParent($parentCatIds=[]){

        $cat = [];
        //self::debug($parentCatIds);
        foreach ( $parentCatIds as $categoryId ){
            $categories = Category::find()->where(['is_active'=>1, 'parent_id'=>null,'id'=>$categoryId])->asArray()->all();
            $i=0;

            foreach($categories as $p_cat){
                $catIds = self::sub_categories($p_cat['id']);
                foreach ( $catIds as $catId ){
                    $cat[] = $catId;
                }
                $i++;
            }
        }

        return $cat;
    }

    public static function sub_categories($id,&$cat=[]){

        $categories = Category::find()->where(['is_active'=>1,'parent_id'=>$id])->asArray()->all();
        $i=0;
        foreach($categories as $p_cat){
            self::sub_categories($p_cat['id'],$cat);
            $cat[] = $p_cat['id'];
            $i++;
        }

        return $cat;
    }
    public static function _refineList($data){
        $list=[];
        foreach ( $data as $detail ){
            $list[] = $detail['sku_id'];
        }
        return $list;
    }
    public static function skusInADeal($dealId){
        $skus = DealsMakerSkus::find()->where(['deals_maker_id'=>$dealId])->asArray()->all();
        $list=[];
        foreach ($skus as $detail  ){
            $list[]=$detail['sku_id'];
        }
        return $list;
    }

    public static function catChildParent( $channelId, $catId )
    {
        $category = Category::find()->where(['id'=>$catId])->asArray()->all();

        // get commision and charges details
        $getCommission = ChannelsDetails::find()->where(['channel_id'=>$channelId,'category_id'=>$catId])->asArray()->all();

        if ( $getCommission ){
            $result = [
                'shipping_fee'=>$getCommission[0]['shipping'],
                'commission'=>$getCommission[0]['commission'],
                'pg_commission'=>$getCommission[0]['pg_commission'],
            ];
            return $result;
        }

        if ( !empty($category) && $category[0]['parent_id']=='' ){
            return self::catChildParent($channelId,$category[0]['parent_id']);
        }
        else if( !empty($category) && $category[0]['parent_id']!='' ){
            return self::catChildParent($channelId,$category[0]['parent_id']);
        }
    }
    public static function GetShopeeDiscountIdList($channelId){
        $channel = Channels::find()->where(['id'=>$channelId])->one();
        $OngoingDiscountList = ShopeeUtil::GetDiscountsList($channel,'ONGOING');
        $UpcomingDiscountList = ShopeeUtil::GetDiscountsList($channel,'UPCOMING');
        $OngoingDiscountList = json_decode($OngoingDiscountList,true);
        $UpcomingDiscountList = json_decode($UpcomingDiscountList,true);

        $discountList=[];

        foreach ( $OngoingDiscountList['discount'] as $discountDetail ){
            $discountList[] = $discountDetail;
        }

        foreach ( $UpcomingDiscountList['discount'] as $discountDetail ){
            $discountList[] = $discountDetail;
        }

        return $discountList;
    }
    public static function GetShopeeDiscountListDropdown($channelId){
        $discountList=DealsUtil::GetShopeeDiscountIdList($channelId);
        $dropDown = ' <div class="col-md-4 shopee-discount-dropdown">
                    <label class="control-label" for="dealsmaker-discount_type">Shopee Discount Id</label> <select class="form-control" name="DealsMaker[setting][shopee_discount_id]">';
        foreach ($discountList as $disDetail){
            $dropDown.='<option value="'.$disDetail['discount_id'].'">'.$disDetail['discount_name'].' - '.$disDetail['discount_id'].'</option>';
        }
        $dropDown.='</select></div>';
        return $dropDown;
    }
    public static function ChildCategoryToParent($cat_id){
        if ($cat_id==null){
            return '';
        }
        $sql = "SELECT * FROM category where id = $cat_id";
        $category = Category::findBySql($sql)->one();
        /*echo '<pre>';
        print_r($category);
        die;*/
        if ( $category->parent_id==null || $category->parent_id == '' ){
            return $category->id;
        }else{
            return self::ChildCategoryToParent($category->parent_id);
        }
    }
    public static function AutoApproveDealSku($deal_id){
        $set_approve_query = "UPDATE deals_maker_skus
                            SET STATUS = 'Approved' , approval_comments = 'auto approve by system'
                            WHERE deals_maker_id = $deal_id AND deal_margin >= 5";
        Yii::$app->db->createCommand($set_approve_query)->execute();

        $set_pending_query = "UPDATE deals_maker_skus
                            SET STATUS = 'Pending' , approval_comments = ''
                            WHERE deals_maker_id = $deal_id AND deal_margin <= 5";
        Yii::$app->db->createCommand($set_pending_query)->execute();
    }
}