<?php

namespace backend\controllers;

//use app\models\SalesSheetFiltersColumns;
use backend\util\HelpUtil;
use common\models\Category;
use common\models\Channels;
use common\models\CompetitivePricing;
use common\models\SalesSheetFiltersColumns;
use common\models\SkuMarginSettings;
use Yii;
use common\models\Pricing;
use common\models\search\PricingSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PricingController implements the CRUD actions for Pricing model.
 */
class PricingController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Pricing models.
     * @return mixed
     */
    private function SetSalesColumns($columns = []){
        if( !empty($columns) ){
            $model = SalesSheetFiltersColumns::deleteAll('user_id = :user_id', [':user_id' => Yii::$app->user->identity->getId()]);
        }
        foreach ( $columns as $key=>$value ){
            $Insert= new SalesSheetFiltersColumns();
            $Insert->user_id = Yii::$app->user->identity->getId();
            $Insert->channel_id = $value;
            $Insert->save();
        }
        $GetColumns=SalesSheetFiltersColumns::find()->where(['user_id'=>Yii::$app->user->identity->getId()])->asArray()->all() ;
        if( empty($GetColumns) ){
            $Insert= new SalesSheetFiltersColumns();
            $Insert->user_id = Yii::$app->user->identity->getId();
            $Insert->channel_id = 1;
            $Insert->save();
            $GetColumns = SalesSheetFiltersColumns::find()->where(['user_id'=>Yii::$app->user->identity->getId()])->asArray()->all() ;
        }else{
            //return $GetColumns;
        }
        $redifine = [];
        foreach ( $GetColumns as $key=>$value )
            $redifine[]=$value['channel_id'];
        return $redifine;
    }
    public function AddFilters(){
        $cond='';
        if( isset($_GET['selling_status']) && $_GET['selling_status']!='' ){
            $cond .= ' AND pcp.selling_status = "'.$_GET['selling_status'].'" ';
        }
        if( isset($_GET['competitor_top']) && $_GET['competitor_top']!='' ){
            $cond .= ' AND pcp.competitor_top = "'.$_GET['competitor_top'].'" ';
        }
        if( isset($_GET['cost_price']) && $_GET['cost_price']!='' ){
            $cp_val=str_replace(' ', '', $_GET['cost_price']);
            preg_match_all('!\d+!', $cp_val, $matches);
            if( $cp_val[0]=='>' ){
                $cond .= ' AND pcp.cost >'.$matches[0][0];
            }if( $cp_val[0]=='<' ){
                $cond .= ' AND pcp.cost <'.$matches[0][0];
            }
            if( $cp_val[0]=='=' ){
                $cond .= ' AND pcp.cost ='.$matches[0][0];
            }
            //$cond .= ' AND pcp.cost_price = "'.$_GET['cost_price'].'" ';
        }
        if( isset($_GET['lowest_price']) && $_GET['lowest_price']!='' ){
            $cp_val=str_replace(' ', '', $_GET['lowest_price']);
            preg_match_all('!\d+!', $cp_val, $matches);
            if( $cp_val[0]=='>' ){
                $cond .= ' AND low_price >'.$matches[0][0];
            }if( $cp_val[0]=='<' ){
                $cond .= ' AND low_price <'.$matches[0][0];
            }
            if( $cp_val[0]=='=' ){
                $cond .= ' AND low_price ='.$matches[0][0];
            }
            //$cond .= ' AND pcp.cost_price = "'.$_GET['cost_price'].'" ';
        }
        if( isset($_GET['loss_profit_rm']) && $_GET['loss_profit_rm']!='' ){
            $cp_val=str_replace(' ', '', $_GET['loss_profit_rm']);
            preg_match_all('!\d+!', $cp_val, $matches);
            if( $cp_val[0]=='>' ){
                $cond .= ' AND ap.loss_profit_rm >'.$matches[0][0];
            }if( $cp_val[0]=='<' ){
                $cond .= ' AND ap.loss_profit_rm <'.$matches[0][0];
            }
            if( $cp_val[0]=='=' ){
                $cond .= ' AND ap.loss_profit_rm ='.$matches[0][0];
            }
            //$cond .= ' AND pcp.cost_price = "'.$_GET['cost_price'].'" ';
        }
        if( isset($_GET['margin_at_lowest_price']) && $_GET['margin_at_lowest_price']!='' ){
            $cp_val=str_replace(' ', '', $_GET['margin_at_lowest_price']);
            preg_match_all('!\d+!', $cp_val, $matches);
            if( $cp_val[0]=='>' ){
                $cond .= ' AND ap.margins_low_price >'.$matches[0][0];
            }if( $cp_val[0]=='<' ){
                $cond .= ' AND ap.margins_low_price <'.$matches[0][0];
            }
            if( $cp_val[0]=='=' ){
                $cond .= ' AND ap.margins_low_price ='.$matches[0][0];
            }
            //$cond .= ' AND pcp.cost_price = "'.$_GET['cost_price'].'" ';
        }
        if( isset($_GET['margin_at_sale_price']) && $_GET['margin_at_sale_price']!='' ){
            $cp_val=str_replace(' ', '', $_GET['margin_at_sale_price']);
            preg_match_all('!\d+!', $cp_val, $matches);
            if( $cp_val[0]=='>' ){
                $cond .= ' AND ap.margin_sale_price >'.$matches[0][0];
            }if( $cp_val[0]=='<' ){
                $cond .= ' AND ap.margin_sale_price <'.$matches[0][0];
            }
            if( $cp_val[0]=='=' ){
                $cond .= ' AND ap.margin_sale_price ='.$matches[0][0];
            }
            //$cond .= ' AND pcp.cost_price = "'.$_GET['cost_price'].'" ';
        }
        if( isset($_GET['skuid']) && $_GET['skuid']!='' ){
            $cond .= " AND pcp.sku LIKE \"%".$_GET['skuid']."%\" ";
        }
        if( isset($_GET['Channels']) ){
            $counter=0;
            $c_sk=0;
            foreach ($_GET['Channels'] as $key=>$value){
                /* I've commented some of the conditions below, becasue we hide some columns on the front. If we reopen it then these conditions
                will be un commented and filters will start working on the new columns too */
                if( $value['low_price'] == '' /*AND $value['margins_low_price'] == ''*/ AND $value['sale_price'] == '' /* AND $value['margin_sale_price']== ''
                    AND $value['loss_profit_rm'] == ''*/ ){

                    // nothing to do in this block

                }else{
                    $c_sk++;
                    $cond .= " AND ( channel_id = ".$key." AND (";
                    $c_array=[];
                    foreach ( $value as $keyzz=>$valuezz ){
                        if( $valuezz != '' ){
                            $c_array[]= $keyzz." ".$valuezz." ";
                        }
                    }
                    $cond .= implode(' AND ',$c_array).") ";

                }
                $counter++;
            }
            if ($c_sk>0)
                $cond .= ")";
        }
        return $cond;
    }

    public function actionIndex()
    {

        $crawled_products = HelpUtil::findCrawlSkus();
        //echo '<pre>';print_r($crawled_products);die;

        /**
         * Set the column views, As per user settings
         */

        if( isset($_GET['filters']['column']) ){
            $UserColumns=$this->SetSalesColumns($_GET['filters']['column']);
        }else{
            $UserColumns=$this->SetSalesColumns();
        }
        /**
         * Set the column views, As per user settings -------- ENDS HERE
         */

        /**
         * Get the channel ids from ao_pricing to make the checkboxes in the filters modal in views
         */

        $connection = Yii::$app->getDb();
        /*$Sql = "select c.id,c.name from ao_pricing ap inner join channels c on c.id = ap.channel_id group by ap.channel_id;";
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($Sql);
        $channelList = $command->queryAll();*/

        /**
         * Get the channel ids from ao_pricing to make the checkboxes in the filters modal in views -- ENDS HERE
         */

        $show = isset($_GET['show']) ? Yii::$app->request->get('show') : 'all';
        $_GET['show'] = $show;
        $date = Yii::$app->request->post('date');
        $date = ($date == '') ? date('Y-m-d') : $date;

        /**
         * Filters conditions below
         */

        if( isset($_GET['date']) && $_GET['date']!='' ){
            $originalDate = $_GET['date'];
            $Date = date("Y-m-d", strtotime($originalDate));
        }else{
            $Date=date("Y-m-d");
        }
        $cond=$this->AddFilters();

        /**
         * Filters conditions below -- ENDS HERE
         */

        if ($show == 'all'){

            /**
             * Query is running to count rows in order to make pagination
             */

            $total_rows_count = Pricing::PaginationCountRows($UserColumns,$Date,$cond);

            /**
             * Below condition will run if we don't get the records on todays date. Then we will show the records on previous date.
             */

            if (!$total_rows_count && !isset($_GET['skuid']) && !isset($_GET['date']))
            {
                //$this->redirect('/pricing/index?show=all&page_no=1&'.'');
                $date = new \DateTime();
                $date->sub(new \DateInterval('P1D'));
                $Date = $date->format('m/d/Y');
                $this->redirect('/pricing/index?show=all&page_no=1&date='.$Date);
                $total_rows_count = Pricing::PaginationCountRows($UserColumns,$Date,$cond);
            }
            $total_pages = ceil($total_rows_count / 50);

            if( isset($_GET['limit']) && $_GET['page_no']!='All' ) {
                $limit=$_GET['limit'];
            }else if ( $_GET['page_no']!='All' ){
                $limit='0,50';
            }else if ($_GET['page_no']=='All'){
                $limit='0,500000';
            }
            /* real query that shows the data on view */
            $skuList = Pricing::GetSkuList($UserColumns,$Date,$cond,$limit);

        }


        $refine = [];
        $skus = [];

        $c_names=$this->GetCategoryList();
        foreach ($skuList as $re) {

            if ($re['low_price'] != '')
                $skus[$re['sku_id']] = [
                    'sku' => $re['sku'],
                    'sc' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                    'ss' => $re['selling_status'],
                    'cp' => $re['cost'],
                    'as' => $re['avg_sales']
                ];

            $refine[$re['channel_id']][$re['sku_id']] = [

                'sub_category' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                'low_price' => $re['low_price'],
                'base_price_at_zero_margins' => $re['base_price_at_zero_margins'],
                'base_price_before_subsidy' => $re['base_price_before_subsidy'],
                'base_price_after_subsidy' => $re['base_price_after_subsidy'],
                'gross_profit' => $re['gross_profit'],
                'sale_price' => $re['sale_price'],
                'margin_sale_price' => $re['margin_sale_price'],
                'margins_low_price' => $re['margins_low_price'],
                'loss_profit_rm' => $re['loss_profit_rm']
            ];
        }
        return $this->render('sheet', ['channelSkuList' => $refine, 'date' => $date, 'skus' => $skus,'user_columns'=>$UserColumns,
            'total_pages'=>$total_pages,'crawl_results'=>$crawled_products]);
    }
    public  function GetCategoryList(){
        $CatList=Category::find()->select(['id','name'])->asArray()->all();
        $namelist=$this->redefine_category($CatList);
        return $namelist;
    }
    public function redefine_category($data){
        $list=[];
        foreach ( $data as $key=>$value ){
            $list[$value['id']] = $value['name'];
        }
        //$this->debug($list);
        return $list;
    }
    public function actionDetail(){
        $UserColumns=$this->SetSalesColumns();
        $skuList = Pricing::GetSkuList($UserColumns,date('Y-m-d',strtotime($_GET['date'])),'','0,100',$_GET['sku']);
        $c_names=$this->GetCategoryList();
        foreach ($skuList as $re) {

            if ($re['low_price'] != '')
                $skus[$re['sku_id']] = [
                    'sku' => $re['sku'],
                    'sc' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                    'ss' => $re['selling_status'],
                    'cp' => $re['cost'],
                    'as' => $re['avg_sales']
                ];

            $refine[$re['channel_id']][$re['sku_id']] = [
                'sub_category' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                'low_price' => $re['low_price'],
                'base_price_at_zero_margins' => $re['base_price_at_zero_margins'],
                'base_price_before_subsidy' => $re['base_price_before_subsidy'],
                'base_price_after_subsidy' => $re['base_price_after_subsidy'],
                'gross_profit' => $re['gross_profit'],
                'sale_price' => $re['sale_price'],
                'margin_sale_price' => $re['margin_sale_price'],
                'margins_low_price' => $re['margins_low_price'],
                'loss_profit_rm' => $re['loss_profit_rm']
            ];
        }
        $date=date('m/d/Y',strtotime($_GET['date']));
        return $this->render('detail', ['channelSkuList' => $refine, 'date' => $date, 'skus' => $skus,'user_columns'=>$UserColumns]);
    }
    public function actionPricingSku(){
        //$this->debug($_GET);
        $UserColumns=$this->SetSalesColumns();
        $Date = date('Y-m-d',strtotime($_GET['date']));
        $limit=100;
        $skuList = Pricing::GetSkuList($UserColumns,$Date,$cond='',$limit,$_GET['sku']);
        $c_names=$this->GetCategoryList();
        foreach ($skuList as $re) {

            if ($re['low_price'] != '')
                $skus[$re['sku_id']] = [
                    'sku' => $re['sku'],
                    'c' => $c_names[$re['category']],
                    'sc' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                    'ss' => $re['selling_status'],
                    'cp' => $re['cost'],
                    'as' => $re['avg_sales']
                ];

            $refine[$re['channel_id']][$re['sku_id']] = [
                'category' => $c_names[$re['category']],
                'sub_category' => isset($c_names[$re['sub_category']]) ? $c_names[$re['sub_category']] : '',
                'low_price' => $re['low_price'],
                'base_price_at_zero_margins' => $re['base_price_at_zero_margins'],
                'base_price_before_subsidy' => $re['base_price_before_subsidy'],
                'base_price_after_subsidy' => $re['base_price_after_subsidy'],
                'gross_profit' => $re['gross_profit'],
                'sale_price' => $re['sale_price'],
                'margin_sale_price' => $re['margin_sale_price'],
                'margins_low_price' => $re['margins_low_price'],
                'loss_profit_rm' => $re['loss_profit_rm']
            ];
        }
        //$this->debug($refine);
        echo $this->renderPartial('_render-partial/extra-info-tr',['channelSkuList' => $refine,'user_columns'=>$UserColumns,'skus' => $skus]);
    }
    public function actionSalesExport()
    {
        $date = Yii::$app->request->post('date');
        $date = isset($date) ? $date : date('Y-m-d');
      //  $date = strtotime($date);
        return $this->render('export',['date'=>$date]);
    }

    public function actionExport()
    {

        $chId = Yii::$app->request->get('chid');
        $date = Yii::$app->request->get('date');
        $show = Yii::$app->request->get('show');
        $date = date('Y-m-d', $date);

        $channel = Channels::find()->where(['id' => $chId,'is_active'=>'1'])->one();

        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=sales-sheet-" . strtolower($channel->name) . "-" . $date . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        if($show == 'all')
        {
            $pricing = Pricing::find()->joinWith(['sku' => function ($q) {
                $q->joinWith('subCategory');
            }])->where(['channel_id' => $chId])->andWhere(['added_at' => $date])->all();
        } else {
            $pricing = Pricing::find()->joinWith(['sku' => function ($q) {
                $q->joinWith('subCategory');
            }])->where(['channel_id' => $chId])->andWhere(['added_at' => $date, 'is_update_today'=>'1'])->all();
        }

        echo "SKU,Category,Sale Price,Rccp,Discount";
        echo "\n";
        //$this->debug($pricing);
        foreach ($pricing as $p) {
            try {
                $groupCatId = $p->sku->subCategory->group_category_id;
                $gcn = HelpUtil::getGroupCategory($groupCatId);
                $p->sale_price = str_replace(',', '', $p->sale_price);
                $rccp = str_replace(',', '', $p->sku->rccp);
                $discount = ($p->sku->rccp) - ($p->sale_price);
                if ($discount < 0)
                    $discount = 0;
                echo $p->sku->sku . ',' . $gcn . ',' . $p->sale_price . ',' . $rccp . ',' . $discount;
                echo "\n";
            } catch( yii\base\ErrorException $ex)
            {
                //$error [] = $p->sku->id;
            }
        }
        die();

    }
    public function actionGetPricingExtraInformation(){

    }
}
