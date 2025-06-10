<?php
namespace backend\controllers;
use backend\util\HelpUtil;
use common\models\Category;
use common\models\Channels;
use common\models\CostPrice;
use common\models\TempCrawlResults;
use Yii;
use common\models\CompetitivePricing;
use common\models\search\CompetitivePricingSearch;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * CompetitivePricingController implements the CRUD actions for CompetitivePricing model.
 */
class CompetitivePricingController extends Controller
{
    public $enableCsrfValidation = false;
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
     * Lists all CompetitivePricing models.
     * @return mixed
     */
    public function actionIndex()
    {
        $curChannel = Yii::$app->request->get('c');
        $curChannel = ($curChannel != '') ? $curChannel : '1';
        $searchModel = new CompetitivePricingSearch();
        $searchModel->channel_id = $curChannel;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CompetitivePricing model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new CompetitivePricing model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    private function QueryResultCount($Query){
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($Query);
        $result = $command->queryAll();
        return count($result);

    }
    public function actionCreate()
    {
        $connection = Yii::$app->getDb();
        $GetCategories = Category::find()->asArray()->all();
        ini_set("memory_limit","500M");

        $model = new CompetitivePricing();
        $archiveList=[];
        // check user access and assign skus or channel
        $insertDate = Yii::$app->request->post('insert_date');
        $insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
        if (false) {
            $userSkus = Yii::$app->user->identity->skus;
            if ($userSkus != '') {
                $insertDate = Yii::$app->request->post('insert_date');
                $insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
                $Query = "SELECT *
FROM `products` prd
INNER JOIN competitive_pricing cp on 
cp.sku_id = prd.id
WHERE (prd.`id` IN (".$userSkus.")) AND (prd.`is_active`='1') AND (prd.`sub_category` <> '167') AND cp.created_at = '".date('Y-m-d')."' ";
                $cond='';
                if( isset($_GET['channel']) ){
                    $counter=0;
                    $empty=0;
                    $a=0;
                    foreach ($_GET['channel'] as $key=>$value){
                        if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                        }else{
                            $empty = 1;
                            $Query .= " AND ( channel_id = ".$key." AND (";
                            $cond .= " AND ( channel_id = ".$key." AND (";
                            $c_array=[];
                            foreach ( $value as $keyzz=>$valuezz ){
                                if( $valuezz != '' ){
                                    if( $keyzz == 'seller_name' ){
                                        $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                    }elseif( $keyzz == 'low_price' ){
                                        $c_array[]= $keyzz." ".$valuezz." ";
                                    }

                                }
                            }

                            $Query .= implode(' OR ',$c_array).") ";
                            $cond.=implode(' OR ',$c_array).") ";
                            $a++;
                        }
                        $counter++;
                    }

                    if ($empty==1){
                        $Query .= ")";
                        $cond .= ")";
                    }
                    if ($a>1)
                        $Query .= "))";


                }
                if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                    $Query .= " AND prd.`sku` like '%".$_GET['sku_id_search']."%'";
                }
                if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                    $Query .= " AND prd.`sub_category` like '".$_GET['sub_category_search']."'";
                }
                if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                    $Query .= " AND prd.`selling_status` like '%".$_GET['selling_status_search']."%'";
                }
                if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                    $Query .= " AND prd.`name` like '%".$_GET['name_search']."%'";
                }
                $Query .= "GROUP BY cp.sku_id";
                $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');

                if ( !$total_records ){
                    $date = new \DateTime();
                    $date->sub(new \DateInterval('P1D'));
                    $Yesterday = $date->format('Y-m-d') . "\n";
                    $insertDate = $Yesterday;
                    $Query = "SELECT *
FROM `products` prd
INNER JOIN competitive_pricing cp on 
cp.sku_id = prd.id
WHERE (prd.`id` IN (".$userSkus.")) AND (prd.`is_active`='1') AND (prd.`sub_category` <> '167') AND cp.created_at = '".$Yesterday."' ";
                    $cond='';
                    if( isset($_GET['channel']) ){
                        $counter=0;
                        $empty=0;
                        $a=0;
                        foreach ($_GET['channel'] as $key=>$value){
                            if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                            }else{
                                $empty = 1;
                                $Query .= " AND ( channel_id = ".$key." AND (";
                                $cond .= " AND ( channel_id = ".$key." AND (";
                                $c_array=[];
                                foreach ( $value as $keyzz=>$valuezz ){
                                    if( $valuezz != '' ){
                                        if( $keyzz == 'seller_name' ){
                                            $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                        }elseif( $keyzz == 'low_price' ){
                                            $c_array[]= $keyzz." ".$valuezz." ";
                                        }

                                    }
                                }

                                $Query .= implode(' OR ',$c_array).") ";
                                $cond.=implode(' OR ',$c_array).") ";
                                $a++;
                            }
                            $counter++;
                        }

                        if ($empty==1){
                            $Query .= ")";
                            $cond .= ")";
                        }
                        if ($a>1)
                            $Query .= "))";


                    }
                    if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                        $Query .= " AND prd.`sku` like '%".$_GET['sku_id_search']."%'";
                    }
                    if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                        $Query .= " AND prd.`sub_category` like '".$_GET['sub_category_search']."'";
                    }
                    if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                        $Query .= " AND prd.`selling_status` like '%".$_GET['selling_status_search']."%'";
                    }
                    if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                        $Query .= " AND prd.`name` like '%".$_GET['name_search']."%'";
                    }
                    $Query .= "GROUP BY cp.sku_id";
                    $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');
                }
                //$Query .= " ORDER BY `selling_status`";
                /*first get the count of all the records*/
                $offset = 10 * ($_GET['page'] - 1);

                $Query .= " ORDER BY prd.`selling_status` limit ".$offset.",10";
                $command = $connection->createCommand($Query);

                $result = $command->queryAll();
                foreach ($result as $key=>$value){
                    if( $value['sub_category']==0 ){
                        $result[$key]['subCategory'] = ['id'=>'','name'=>'','main_category_id','is_active'=>'',
                            'created_at'=>'','updated_at'=>'','group_category_id'=>'','is_main'=>''];
                        continue;
                    }
                    $SubCatId=$value['sub_category'];
                    $CatDetail= Category::find()->where(['id'=>$SubCatId])->asArray()->all();
                    $result[$key]['subCategory'] = $CatDetail[0];
                }
                $skuList = $result;
                $sku_ids = [] ;

                foreach ( $skuList as $key=>$value ){
                    $sku_ids[] = $value['sku_id'];
                }
                //$skuList = CostPrice::find()->where(['in', 'id', explode(',', $userSkus)])->andWhere(['is_active'=>'1'])->andWhere(['<>','sub_category', '167'])->orderBy('selling_status asc')->with(['subCategory'])->limit($PerPageRecords.' OFFSET 0');

            }
            else {
                $userSkus = "0,1";
                $sku_ids = [1,2];
                $Query = "SELECT * FROM `products` prd
 INNER JOIN competitive_pricing cp on 
cp.sku_id = prd.id
 WHERE (prd.`id` IN (0, 1)) AND (`is_active`='1') AND (`sub_category` <> '167') AND cp.created_at = '".date('Y-m-d')."' ";
                if( isset($_GET['channel']) ){
                    $counter=0;
                    $empty=0;
                    $a=0;
                    $cond='';
                    foreach ($_GET['channel'] as $key=>$value){
                        if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                        }else{
                            $empty = 1;
                            $Query .= " AND ( channel_id = ".$key." AND (";
                            $cond .= " AND ( channel_id = ".$key." AND (";
                            $c_array=[];
                            foreach ( $value as $keyzz=>$valuezz ){
                                if( $valuezz != '' ){
                                    if( $keyzz == 'seller_name' ){
                                        $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                    }elseif( $keyzz == 'low_price' ){
                                        $c_array[]= $keyzz." ".$valuezz." ";
                                    }

                                }
                            }

                            $Query .= implode(' OR ',$c_array).") ";
                            $cond.=implode(' OR ',$c_array).") ";
                            $a++;
                        }
                        $counter++;
                    }

                    if ($empty==1){
                        $Query .= ")";
                        $cond .= ")";
                    }
                    if ($a>1)
                        $Query .= "))";


                }
                if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                    $Query .= " AND prd.`sku` like '%".$_GET['sku_id_search']."%'";
                }
                if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                    $Query .= " AND prd.`sub_category` like '".$_GET['sub_category_search']."'";
                }
                if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                    $Query .= " AND prd.`selling_status` like '%".$_GET['selling_status_search']."%'";
                }
                if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                    $Query .= " AND prd.`name` like '%".$_GET['name_search']."%'";
                }
                $Query .= "GROUP BY cp.sku_id";
                $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');
                if ( !$total_records ){
                    $date = new \DateTime();
                    $date->sub(new \DateInterval('P1D'));
                    $Yesterday = $date->format('Y-m-d') . "\n";
                    $insertDate = $Yesterday;
                    $Query = "SELECT * FROM `products` prd
 INNER JOIN competitive_pricing cp on 
cp.sku_id = prd.id
 WHERE (prd.`id` IN ('0', '1')) AND (`is_active`='1') AND (`sub_category` <> '167') AND cp.created_at = '".$Yesterday."' ";
                    $cond='';
                    if( isset($_GET['channel']) ){
                        $counter=0;
                        $empty=0;
                        $a=0;
                        foreach ($_GET['channel'] as $key=>$value){
                            if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                            }else{
                                $empty = 1;
                                $Query .= " AND ( channel_id = ".$key." AND (";
                                $cond .= " AND ( channel_id = ".$key." AND (";
                                $c_array=[];
                                foreach ( $value as $keyzz=>$valuezz ){
                                    if( $valuezz != '' ){
                                        if( $keyzz == 'seller_name' ){
                                            $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                        }elseif( $keyzz == 'low_price' ){
                                            $c_array[]= $keyzz." ".$valuezz." ";
                                        }

                                    }
                                }

                                $Query .= implode(' OR ',$c_array).") ";
                                $cond.=implode(' OR ',$c_array).") ";
                                $a++;
                            }
                            $counter++;
                        }

                        if ($empty==1){
                            $Query .= ")";
                            $cond .= ")";
                        }
                        if ($a>1)
                            $Query .= "))";


                    }
                    if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                        $Query .= " AND prd.`sku` like '%".$_GET['sku_id_search']."%'";
                    }
                    if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                        $Query .= " AND prd.`sub_category` like '".$_GET['sub_category_search']."'";
                    }
                    if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                        $Query .= " AND prd.`selling_status` like '%".$_GET['selling_status_search']."%'";
                    }
                    if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                        $Query .= " AND prd.`name` like '%".$_GET['name_search']."%'";
                    }
                    $Query .= "GROUP BY cp.sku_id";
                    $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');
                }
                //$Query .= " ORDER BY `selling_status`";
                /*first get the count of all the records*/
                $offset = 10 * ($_GET['page'] - 1);

                $Query .= " ORDER BY `selling_status` limit ".$offset.",10";
                $command = $connection->createCommand($Query);

                $result = $command->queryAll();
                foreach ($result as $key=>$value){
                    if( $value['sub_category']==0 ){
                        $result[$key]['subCategory'] = ['id'=>'','name'=>'','main_category_id','is_active'=>'',
                            'created_at'=>'','updated_at'=>'','group_category_id'=>'','is_main'=>''];
                        continue;
                    }
                    $SubCatId=$value['sub_category'];
                    $CatDetail= Category::find()->where(['id'=>$SubCatId])->asArray()->all();
                    $result[$key]['subCategory'] = $CatDetail[0];
                }
                $skuList = $result;

            }
        }
        else {
            $insertDate = Yii::$app->request->post('insert_date');
            $insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
            $Query="SELECT *
FROM `products`
INNER JOIN competitive_pricing cp on 
cp.sku_id = products.id
WHERE (`is_active`='1') AND (`sub_category` <> '167') AND cp.created_at = '".date('Y-m-d')."'
";
            $cond='';
            if( isset($_GET['channel']) ){
                $counter=0;
                $empty=0;
                $a=0;
                foreach ($_GET['channel'] as $key=>$value){
                    if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                    }else{
                        $empty = 1;
                        $Query .= " AND ( channel_id = ".$key." AND (";
                        $cond .= " AND ( channel_id = ".$key." AND (";
                        $c_array=[];
                        foreach ( $value as $keyzz=>$valuezz ){
                            if( $valuezz != '' ){
                                if( $keyzz == 'seller_name' ){
                                    $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                }elseif( $keyzz == 'low_price' ){
                                    $c_array[]= $keyzz." ".$valuezz." ";
                                }

                            }
                        }

                        $Query .= implode(' OR ',$c_array).") ";
                        $cond.=implode(' OR ',$c_array).") ";
                        $a++;
                    }
                    $counter++;
                }

                if ($empty==1){
                    $Query .= ")";
                    $cond .= ")";
                }
                if ($a>1)
                    $Query .= "))";


            }
            // echo $Query;die;
            if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                $Query .= " AND `sku` like '%".$_GET['sku_id_search']."%'";
            }
            if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                $Query .= " AND `sub_category` like '".$_GET['sub_category_search']."'";
            }
            if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                $Query .= " AND `selling_status` like '%".$_GET['selling_status_search']."%'";
            }
            if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                $Query .= " AND `name` like '%".$_GET['name_search']."%'";
            }
            $Query .= "GROUP BY cp.sku_id";
            $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');
            // run again if not get the data for today,

            if( !$total_records ){
                $date = new \DateTime();
                $date->sub(new \DateInterval('P1D'));
                $Yesterday = $date->format('Y-m-d') . "\n";
                $insertDate = $Yesterday;
                $insertDate = Yii::$app->request->post('insert_date');
                $insertDate = ($insertDate == '') ? $Yesterday : $insertDate;
                $Query="SELECT *
FROM `products`
INNER JOIN competitive_pricing cp on 
cp.sku_id = products.id
WHERE (`is_active`='1') AND (`sub_category` <> '167') AND cp.created_at = '".$Yesterday."'
";
                $cond='';
                if( isset($_GET['channel']) ){
                    $counter=0;
                    $empty=0;
                    $a=0;
                    foreach ($_GET['channel'] as $key=>$value){
                        if( $value['low_price'] == '' AND $value['seller_name'] == '' ){

                        }else{
                            $empty = 1;
                            $Query .= " AND ( channel_id = ".$key." AND (";
                            $cond .= " AND ( channel_id = ".$key." AND (";
                            $c_array=[];
                            foreach ( $value as $keyzz=>$valuezz ){
                                if( $valuezz != '' ){
                                    if( $keyzz == 'seller_name' ){
                                        $c_array[]= $keyzz." LIKE  '%".$valuezz."%'";
                                    }elseif( $keyzz == 'low_price' ){
                                        $c_array[]= $keyzz." ".$valuezz." ";
                                    }

                                }
                            }

                            $Query .= implode(' OR ',$c_array).") ";
                            $cond.=implode(' OR ',$c_array).") ";
                            $a++;
                        }
                        $counter++;
                    }

                    if ($empty==1){
                        $Query .= ")";
                        $cond .= ")";
                    }
                    if ($a>1)
                        $Query .= "))";


                }
                // echo $Query;die;
                if( isset($_GET['sku_id_search']) && $_GET['sku_id_search']!='' ){
                    $Query .= " AND `sku` like '%".$_GET['sku_id_search']."%'";
                }
                if( isset($_GET['sub_category_search']) && $_GET['sub_category_search']!='' ){
                    $Query .= " AND `sub_category` like '".$_GET['sub_category_search']."'";
                }
                if( isset($_GET['selling_status_search']) && $_GET['selling_status_search']!='' ){
                    $Query .= " AND `selling_status` like '%".$_GET['selling_status_search']."%'";
                }
                if( isset($_GET['name_search']) && $_GET['name_search']!='' ){
                    $Query .= " AND `name` like '%".$_GET['name_search']."%'";
                }
                $Query .= "GROUP BY cp.sku_id";
                $total_records=$this->QueryResultCount($Query.'  ORDER BY `selling_status`');

            }

            /*first get the count of all the records*/

            $offset = 10 * ($_GET['page'] - 1);

            if ( $_GET['page']=='All' )
                $Query .= " ORDER BY `selling_status`";
            else
                $Query .= " ORDER BY `selling_status` limit ".$offset.",10";
            //$Query .= " ORDER BY `selling_status`";
            $command = $connection->createCommand($Query);
            $result = $command->queryAll();
            foreach ($result as $key=>$value){
                if( $value['sub_category']==0 ){
                    $result[$key]['subCategory'] = ['id'=>'','name'=>'','main_category_id','is_active'=>'',
                        'created_at'=>'','updated_at'=>'','group_category_id'=>'','is_main'=>''];
                    continue;
                }
                $SubCatId=$value['sub_category'];
                $CatDetail= Category::find()->where(['id'=>$SubCatId])->asArray()->all();

                if( !isset($CatDetail[0]) || !isset($CatDetail[0]['name']) ){
                    unset($result[$key]);
                    continue;
                }
                $result[$key]['subCategory'] = $CatDetail[0];
            }
            $skuList = $result;
            $sku_ids = [] ;
            //echo $Query;die;
            foreach ( $skuList as $key=>$value ){
                $sku_ids[] = $value['sku_id'];
            }

        }

        $cp = CompetitivePricing::GetCompToday($insertDate,$insertDate,$sku_ids,$cond);

        foreach ($cp as $a) {

            $archiveList[$a['sku_id']][$a['channel_id']] = ['seller_name' => $a['seller_name'], 'low_price' => $a['low_price'], 'change_price' => $a['price_change'], 'keywords' => $a['keywords']];
        }

        if( isset($_GET['csv_import']) AND $_GET['csv_import']=='1' ){
            $archiveList = $archiveList + $prevArchiveList;
            //echo '<pre>';
            header('Content-Type: application/excel');
            header('Content-Disposition: attachment; filename="PriceComparision'.time().'.csv"');
            $data = array(
                'SKU,PRODUCT NAME,Lazada Manual Seller,Lazada Manual Price,Lazada Crawler Seller,Lazada Crawler Price,11street Manual Seller,11street Manual Price,11street Crawler Seller,11street Crawler Price'
            );

            foreach ( $skuList as $sl ){
                if( $sl['selling_status']!='High' ){
                    continue;
                }
                // lazada portion
                $sellerNameLazada = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['seller_name'] : '-';
                $lowPriceLazada = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['1'])) ? $archiveList[$sl['id']]['1']['low_price'] : '-';
                if( isset($skus_crawl_list[$sl['sku']][1]['seller_name']) ){
                    $crawler_seller_lazada= $skus_crawl_list[$sl['sku']][1]['seller_name'];
                }else{
                    $crawler_seller_lazada='-';
                }
                if( isset($skus_crawl_list[$sl['sku']][1]['price']) ){
                    $crawler_price_lazada= $skus_crawl_list[$sl['sku']][1]['price'];
                }else{
                    $crawler_price_lazada='-';
                }
                // lazada portion ends here
                // 11street portion
                $sellerName11street = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $archiveList[$sl['id']]['3']['seller_name'] : '-';
                $lowPrice11street = (isset($archiveList[$sl['id']]) && isset($archiveList[$sl['id']]['3'])) ? $archiveList[$sl['id']]['3']['low_price'] : '-';
                if( isset($skus_crawl_list[$sl['sku']][3]['seller_name']) ){
                    $crawler_seller_11street= $skus_crawl_list[$sl['sku']][3]['seller_name'];
                }else{
                    $crawler_seller_11street='-';
                }
                if( isset($skus_crawl_list[$sl['sku']][3]['price']) ){
                    $crawler_price_11street= $skus_crawl_list[$sl['sku']][3]['price'];
                }else{
                    $crawler_price_11street='-';
                }
                // 11street portion ends here
                $data[] = $sl['sku'].','.str_replace(',','',$sl['name']).','.$sellerNameLazada.','.$lowPriceLazada.','.$crawler_seller_lazada.','.$crawler_price_lazada.','.$sellerName11street.','.$lowPrice11street.','.$crawler_seller_11street.','.$crawler_price_11street;
            }
            //print_r($data);

            $fp = fopen('php://output', 'w');
            foreach ( $data as $line ) {
                $val = explode(",", $line);
                fputcsv($fp, $val);
            }
            fclose($fp);
            die;
        }
        return $this->render('create', [
            'model' => $model,
            'skuList' => $skuList,
            'archiveList' => $archiveList /*+ $prevArchiveList*/,
            'insertDate' => $insertDate,
            //'sku_crawl_list' => $skus_crawl_list,
            'category' => $GetCategories,
            'total_records' => $total_records,
            'pagination_pages' => ceil($total_records/10)
        ]);
        //return $this->render('create',['html'=>$html]);
    }
    public function actionCompetitivePricingAjax(){
        ini_set("memory_limit","500M");
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("select pcp.sku,tcr.* from temp_crawl_results  tcr
inner join products pcp on 
pcp.ID = tcr.sku_id
where tcr.added_at like '".date('Y-m-d')."%'");
        $result = $command->queryAll();
        $skus_crawl_list=array();
        foreach ( $result as $key=>$value ){
            $skus_crawl_list[$value['sku']][$value['channel_id']] = $value;
        }
        /*echo '<pre>';
        print_r($skus_crawl_list);
        die;*/

        $model = new CompetitivePricing();

        // check user access and assign skus or channel

        if (Yii::$app->user->identity->role->id != '1' && Yii::$app->user->identity->role->id != '6') {
            $userSkus = Yii::$app->user->identity->skus;
            if ($userSkus != '') {
                $skuList = CostPrice::find()->where(['in', 'id', explode(',', $userSkus)])->andWhere(['is_active'=>'1'])->andWhere(['<>','sub_category', '167'])->orderBy('selling_status asc')->with(['subCategory'])->limit($_POST['records_per_page'].' OFFSET 0')->asArray()->all();
                // check assigned channel
            } else {
                $userSkus = "0,1";
                $skuList = CostPrice::find()->where(['in', 'id', explode(',', $userSkus)])->andWhere(['is_active'=>'1'])->andWhere(['<>','sub_category', '167'])->orderBy('selling_status asc')->with(['subCategory'])->limit($_POST['records_per_page'].' OFFSET 0')->asArray()->all();
            }
        } else {
            $skuList = CostPrice::find()->where(['is_active'=>'1'])->andWhere(['<>','sub_category', '167'])->with(['subCategory'])->orderBy('selling_status asc')->limit($_POST['records_per_page'])->asArray()->all();
        }
        $archiveList = $prevArchiveList = [] ;
        $insertDate = Yii::$app->request->post('insert_date');
        $insertDate = ($insertDate == '') ? date('Y-m-d') : $insertDate;
        $prev = date('Y-m-d',strtotime('-1 day'));

        if(Yii::$app->user->identity->role->id  == 1 || Yii::$app->user->identity->role->id == '6')
        {
            $cp = CompetitivePricing::find()->where(['or',
                ['created_at' => $insertDate,],
                ['updated_at' => $insertDate,]
            ])->asArray()->all();

            $cpp = CompetitivePricing::find()->orderBy('created_at desc')->asArray()->all();

        } else {
            $cp = CompetitivePricing::find()->where(['created_by' => Yii::$app->user->id])->andWhere(['or',
                ['created_at' => $insertDate,],
                ['updated_at' => $insertDate,]
            ])->asArray()->all();

            $cpp = CompetitivePricing::find()->where(['created_by' => Yii::$app->user->id])->orderBy('created_at desc')->asArray()->all();
        }



        foreach ($cp as $a) {
            $archiveList[$a['sku_id']][$a['channel_id']] = ['seller_name' => $a['seller_name'], 'low_price' => $a['low_price'], 'change_price' => $a['price_change'], 'keywords' => $a['keywords']];
        }
        foreach ($cpp as $a) {
            if(!isset($archiveList[$a['sku_id']][$a['channel_id']]) && !isset($prevArchiveList[$a['sku_id']][$a['channel_id']]))
                $prevArchiveList[$a['sku_id']][$a['channel_id']] = ['seller_name' => $a['seller_name'], 'low_price' => $a['low_price'], 'change_price' => $a['price_change'], 'keywords' => $a['keywords']];
        }
        echo $this->renderAjax('_render-partials/_create_partial', [
            'model' => $model,
            'skuList' => $skuList,
            'archiveList' => $archiveList + $prevArchiveList,
            'insertDate' => $insertDate,
            'sku_crawl_list' => $skus_crawl_list
        ]);
    }
    /**
     * Updates an existing CompetitivePricing model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing CompetitivePricing model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the CompetitivePricing model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return CompetitivePricing the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CompetitivePricing::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionImport()
    {
        $cp = new CompetitivePricing();
        $cp->setScenario('import');

        if (isset($_POST['_csrf-backend'])) {
            $cp::importCSV();
            return $this->redirect(['create']);
        }

        return $this->render('import', ['cname' => '', 'cp' => $cp]);
    }


    // ajax save prices (auto)
    public function actionSavePrices()
    {
        $fields = Yii::$app->request->post();
        $include = [1, 2, 3];
        $channelList = \backend\util\HelpUtil::getChannels($include);
        $changeList = [];
        $lowSkuPriceList = [];
        $newdate = strtotime("-1 day", strtotime($fields['insert_date']));
        $prevDay = date("Y-m-d", $newdate);

        foreach ($channelList as $cl) {
            if ((isset($fields['low_price_' . $cl['id']]) && $fields['low_price_' . $cl['id']] != '') &&
                (isset($fields['seller_' . $cl['id']]) && $fields['seller_' . $cl['id']] != '' && strlen($fields['seller_' . $cl['id']]) > 2)) {
                $cp = CompetitivePricing::findOne(['created_by' => Yii::$app->user->id, 'created_at' => $fields['insert_date'], 'sku_id' => $fields['sku_id'], 'channel_id' => $cl['id']]);
                $last_lp = CompetitivePricing::findOne(['created_at' => $prevDay, 'sku_id' => $fields['sku_id'], 'channel_id' => $cl['id'], 'seller_name' => $fields['seller_' . $cl['id']]]);
                if ($cp) {
                    $cp->seller_name = $fields['seller_' . $cl['id']];
                    $cp->low_price = $fields['low_price_' . $cl['id']];
                    $cp->updated_at = $fields['insert_date'];
                    $cp->updated_by = Yii::$app->user->id;
                    $cp->price_change = ($last_lp && ($last_lp->low_price == $fields['low_price_' . $cl['id']])) ? 0 : 1;
                    $changeList['ch_' . $cl['id']] = ($cp->price_change == 1) ? 'Yes' : 'No';
                    $cp->keywords = $fields['kw_1'];
                    $cp->save(false);
                    $lowSkuPriceList[$fields['sku_id']][$fields['insert_date']][] = $fields['low_price_' . $cl['id']];
                } else {
                    $cp = new CompetitivePricing();
                    $cp->sku_id = $fields['sku_id'];
                    $cp->channel_id = $cl['id'];
                    $cp->seller_name = $fields['seller_' . $cl['id']];
                    $cp->low_price = $fields['low_price_' . $cl['id']];
                    $cp->created_at = $fields['insert_date'];
                    $cp->keywords = $fields['kw_1'];
                    $cp->created_by = Yii::$app->user->id;
                    $cp->price_change = ($last_lp && ($last_lp->low_price == $fields['low_price_' . $cl['id']])) ? 0 : 1;
                    $changeList['ch_' . $cl['id']] = ($cp->price_change == 1) ? 'Yes' : 'No';
                    $cp->save(false);
                    $lowSkuPriceList[$fields['sku_id']][$fields['insert_date']][] = $fields['low_price_' . $cl['id']];
                }

            }

        }

        if (!empty($lowSkuPriceList)) {
            $include = [5, 6, 9, 10, 11,13,15,14];
            $channelList = \backend\util\HelpUtil::getChannels($include);
            foreach ($channelList as $cl) {

                $cp = CompetitivePricing::findOne(['created_by' => Yii::$app->user->id, 'created_at' => $fields['insert_date'], 'sku_id' => $fields['sku_id'], 'channel_id' => $cl['id']]);
                $last_lp = CompetitivePricing::findOne(['created_at' => $prevDay, 'sku_id' => $fields['sku_id'], 'channel_id' => $cl['id'], 'seller_name' => $cl['name']]);
                if ($cp) {
                    $cp->seller_name = $cl['name'];
                    $cp->low_price = min($lowSkuPriceList[$fields['sku_id']][$fields['insert_date']]);
                    $cp->updated_at = $fields['insert_date'];
                    $cp->updated_by = Yii::$app->user->id;
                    $cp->price_change = ($last_lp && ($last_lp->low_price == min($lowSkuPriceList[$fields['sku_id']][$fields['insert_date']]))) ? 0 : 1;
                    $changeList['ch_' . $cl['id']] = ($cp->price_change == 1) ? 'Yes' : 'No';
                    $cp->save(false);
                } else {
                    $cp = new CompetitivePricing();
                    $cp->sku_id = $fields['sku_id'];
                    $cp->channel_id = $cl['id'];
                    $cp->seller_name = $cl['name'];
                    $cp->low_price = min($lowSkuPriceList[$fields['sku_id']][$fields['insert_date']]);
                    $cp->created_at = $fields['insert_date'];
                    $cp->created_by = Yii::$app->user->id;
                    $cp->price_change = ($last_lp && ($last_lp->low_price == min($lowSkuPriceList[$fields['sku_id']][$fields['insert_date']]))) ? 0 : 1;
                    $changeList['ch_' . $cl['id']] = ($cp->price_change == 1) ? 'Yes' : 'No';
                    $cp->save(false);
                }
            }
        }


        echo json_encode($changeList);
    }

    // line chart for each SKU
    public function actionSkuDetails()
    {
        $skuId = Yii::$app->request->get('sku');
        $skuList = HelpUtil::getSkuList('name');
        $include = [1, 2, 3,];
        $channelList = \backend\util\HelpUtil::getChannels($include);
        $today = date('Y-m-d');
        $week = date('Y-m-d', strtotime('-4 week'));
        $dataList = $pieDataList = [];

        // channels SKU low price line chart data
        $sql = "SELECT cp.created_at,cp.seller_name,cp.low_price,cp.channel_id,p.`sale_price`
                FROM `competitive_pricing` cp 
                INNER JOIN ao_pricing p ON p.`sku_id` = cp.`sku_id` AND cp.`created_at` = p.`added_at` AND cp.`channel_id` = p.`channel_id`
                WHERE cp.sku_id = {$skuList[$skuId]} AND cp.`created_at` BETWEEN '$week' AND '$today'";

        $cp = CompetitivePricing::findBySql($sql)->asArray()->all();
        foreach ($cp as $l) {
            $dataList[$l['channel_id']][] = ['price'=> $l['sale_price'] ,'seller' => $l['seller_name'], 'date' => $l['created_at'], 'value' => $l['low_price']];
        }


        // pie chart for channel sellers
        $sd = CompetitivePricing::find()->select(['COUNT(seller_name) AS scount', 'seller_name', 'channel_id', 'price_change'])->where(['sku_id' => $skuList[$skuId]])->andWhere(['between', 'created_at', $week, $today])->groupBy(['seller_name', 'channel_id'])->asArray()->all();
        foreach ($sd as $l) {
            $pieDataList[$l['channel_id']][] = ['seller' => $l['seller_name'], 'value' => $l['scount']];
        }

        if (empty($dataList)) {
            return $this->redirect(['create?page=1']);
        }

        return $this->render('sku_details',
            [
                'sku' => $skuId,
                'today' => $today,
                'week' => $week,
                'channelList' => $channelList,
                'dataList' => $dataList,
                'pieDataList' => $pieDataList,
                'normal_sku_detail_page'=>1
            ]
        );
    }

    public function actionCrawlSkuDetails(){

        $skuId = Yii::$app->request->get('sku');
        $skuList = HelpUtil::getSkuList('name');

        //$include = [22, 24];
        //echo '<pre>';print_r($_GET);die;
        //echo $skuId;die;
        //die;
        if ( !isset($_GET['Date_range']) ){
            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-4 week'));
        }else{
            $dateRange = explode(' to ',$_GET['Date_range']);
            $from = $dateRange[0];
            $to = $dateRange[1];
        }

        $dataList = [];

        // channels SKU low price line chart data
        $sql = "SELECT tcr.added_at AS created_at,tcr.marketplace,
tcr.seller_name,tcr.price as low_price,tcr.channel_id, tcr.price as sale_price
FROM temp_crawl_results tcr
INNER JOIN products pcp on
pcp.id = tcr.sku_id
WHERE tcr.added_at BETWEEN '$from' AND '$to' AND tcr.sku_id = {$skuList[$skuId]}";


        $cp = TempCrawlResults::findBySql($sql)->asArray()->all();
        /*echo '<pre>';
        print_r($cp);
        die;*/
        // make competitor data set
        $crawl_marketplaces=[];
        foreach ($cp as $l) {
            if ( $l['seller_name']!='-' ){
                $dataList[$l['marketplace']]['competitors_dataset'][] = ['price'=> $l['sale_price'] ,
                    'seller_name'=>$l['seller_name'],
                    'title' => 'Seller: '.$l['seller_name'].', Price '.$l['sale_price'],
                    'date' => $l['created_at']
                ];
                $crawl_marketplaces[$l['marketplace']]=$l['marketplace'];
            }else{
                $dataList[$l['marketplace']]['competitors_dataset'][] = ['price'=> $l['sale_price'] ,
                    'seller_name'=>$l['seller_name'],
                    'title' => 'Price '.$l['sale_price'],
                    'date' => $l['created_at']
                ];
                $crawl_marketplaces[$l['marketplace']]=$l['marketplace'];
            }

        }

        // make competitor and channels combination data set date wise.
        $price_camparision_dataset=HelpUtil::SetDataSetPriceCamparision($from,$to,$skuList[$skuId]);

        /*echo '<pre>';
        print_r($dataList);
        die;*/
        foreach ( $dataList as $marketplace=>$val ){
            $dataList[$marketplace]['competitors_dataset'] = json_encode($val);
            $dataList[$marketplace]['lowest_price'] = HelpUtil::SetCrawlLowestHighestAverage('lowest',$val);
            $dataList[$marketplace]['highest_price'] = HelpUtil::SetCrawlLowestHighestAverage('highest',$val);
            $dataList[$marketplace]['average_price'] = HelpUtil::SetCrawlLowestHighestAverage('average',$val);
        }
        /*echo '<pre>';
        print_r($dataList);
        die;*/
        return $this->render('sku_details',
            [
                'sku' => $skuId,
                'today' => $to,
                'week' => $from,
                'dataList' => $dataList,
                'price_camparision_dataset'=>$price_camparision_dataset
            ]
        );
    }
    // return SKUS base on Category ID
    public function actionSkuByCategory()
    {
        $categoryId = Yii::$app->request->get('category');
        $skus = Yii::$app->request->get('selected');
        if ($skus)
            $skus = json_decode($skus, true);
        else
            $skus = [];

        $skuList = CostPrice::find()->select(['id', 'sku'])->where(['sub_category' => $categoryId])->orderBy('id')->asArray()->all();
        return $this->renderAjax('_sku-list', ['response' => $skuList, 'skus' => $skus]);
    }
}
