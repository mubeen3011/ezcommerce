<?php

namespace backend\controllers;

use common\models\Category;
use common\models\Channels;
use common\models\CostPriceLog;
use common\models\FocSkus;
use common\models\PhilipsCostPrice;
use common\models\ProductDetails;
use common\models\ProductRelationsSkus;
use common\models\Products;
use common\models\ProductsRelations;
use common\models\ProductStocks;
use common\models\Subsidy;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Yii;
use common\models\CostPrice;
use common\models\search\CostPriceSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * CostPriceController implements the CRUD actions for CostPrice model.
 */
class CostPriceController extends GenericGridController
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
     * Lists all CostPrice models.
     * @return mixed
     */
    public function actionIndex()
    {
        $skuList = CostPrice::find()->where(['<>','sub_category','167'])->orderBy('id asc')->with(['subCategory'])->limit('1')->asArray()->all();
        //echo $skuList->createCommand()->getRawSql();die;

        return $this->render('index', [
            'skuList' => $skuList,

        ]);
    }
    public function actionGeneric(){

        $StockStatuses="SELECT pcp.selling_status AS `key` , pcp.selling_status as `value`
            FROM products pcp
            WHERE pcp.selling_status IS NOT NULL AND pcp.selling_status <> ''
            GROUP BY pcp.selling_status";

        $StockStatuses = Products::findBySql($StockStatuses)->asArray()->all();
        $statuses = "select  IF(pcp.is_active=0 ,'No', 'Yes') as `value`,pcp.is_active as `key` from products pcp
          group by pcp.is_active order by `value`;";

        $statuses = Products::findBySql($statuses)->asArray()->all();

         $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/cost-price/generic-info',
                    'sortUrl' => '/cost-price/generic-info-sort',
                    'filterUrl' => '/cost-price/generic-info-filter',
                    'jsUrl'=>'/cost-price/generic',
                ],
                'thead'=>
                    [
                        'SKU' => [
                            'data-field' => 'pcp.sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.sku',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'number',
                            'input-type-class' => ''
                        ],
                        'Category' => [
                            'data-field' => 'c.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                         /*
                          * below commented code is so important, if any one wants to open the bundle parent and child, just remove the comments and
                          * also remove the # sign from the configparam query.
                          * */

                        /*'Bundle Name' => [
                            'data-field' => 'bundle_name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'bundle_name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Bundle Child' => [
                            'data-field' => 'bundle_name_child',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'bundle_name_child',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                        ,*/
                        'Stock Status' => [
                            'data-field' => 'pcp.selling_status',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.selling_status',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $StockStatuses,
                            'input-type-class' => ''
                        ],
                        'Name' => [
                            'data-field' => 'pcp.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Cost Price' => [
                            'data-field' => 'pcp.cost',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.cost',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Extra Cost' => [
                            'data-field' => 'pcp.extra_cost',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.extra_cost',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Rccp Cost' => [
                            'data-field' => 'pcp.rccp',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.rccp',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => '',
                        ],
                        'Promo Cost' => [
                            'data-field' => 'pcp.promo_price',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.promo_price',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => '',
                        ],
                        //pd.goodQty
                        'Stocks' => [
                            'data-field' => 'pd.goodQty',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pd.goodQty',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => '',
                        ],
                        //pd.goodQty
                        'Master Carton' => [
                            'data-field' => 'pcp.master_cotton',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.master_cotton',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => '',
                        ],
                        'Active' => [
                            'data-field' => 'pcp.is_active',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp.is_active',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'select',
                            'options' => $statuses,
                            'input-type-class' => '',
                        ]/*,
                        'Action' => [
                            'data-field' => 'pcp_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pcp_id',
                            'data-filter-type' => 'operator',
                            'label' => 'hidden',
                            'input-type' => 'hidden',
                            'input-type-class' => '',
                            'actions' => [
                                'edit' => '/deals-maker/update?'
                            ]
                        ]*/
                    ]
            ];
        $session = \Yii::$app->session;

        $officeSku = [];

        if ($session->has('sku-imported')) {
            $officeSku = $session->get('sku-imported');
        }

        $session->remove('sku-imported');
        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku,'config'=>$config]);
        $roleId = Yii::$app->user->identity->role_id;
        $Categories=Category::find()->where(['is_active'=>1])->andWhere('parent_id<>NULL')->asArray()->all();
        $Sku_List=$this->GetSkuList();
        return $this->render('generic-view',['gridview'=>$html,'roleId' => $roleId,'Categories'=>$Categories,'sku_list'=>$Sku_List]);
    }
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT 
                                 pcp.sku,
                                 c.name as category_name,
                                 #GROUP_CONCAT(DISTINCT pr_p.relation_name) AS bundle_name, 
                                 #GROUP_CONCAT(DISTINCT pr_c.relation_name) AS bundle_name_child,
                                 pcp.selling_status,
                                 pcp.name,
                                 pcp.cost,
                                 pcp.extra_cost,
                                 pcp.rccp,
                                 pcp.promo_price,
                                 pd.`stocks` AS goodqty,
                                 pcp.`master_cotton` AS master_cotton,
                                 pcp.is_active
                                FROM
                                 `products` pcp
                                LEFT JOIN product_details pd ON pd.sku_id = pcp.id
                                LEFT JOIN category c ON c.id = pcp.sub_category
                                #LEFT JOIN `product_relations_skus` prs_p ON prs_p.main_sku_id = pcp.id
                                #LEFT JOIN `product_relations_skus` prs_c ON prs_c.child_sku_id = pcp.id
                                #LEFT JOIN `products_relations` pr_p ON pr_p.id = prs_p.bundle_id
                                #LEFT JOIN `products_relations` pr_c ON pr_c.id = prs_c.bundle_id
                                where 1=1
",
                'GroupBy' => 'GROUP BY pcp.id'
            ],
            'OrderBy_Default'=>'ORDER BY pcp.id DESC',
            'SortOrderByColumnAlias' => 'pcp',
        ];
        return $config;
    }

//update category of product
public function actionUpdateProductCategory()
{
    if(isset($_POST['sku']) && isset($_POST['cat']) && $_POST['sku'] && $_POST['cat'])
    {
       $update= Yii::$app->db->createCommand()
            ->update('products', ['sub_category' =>  $_POST['cat']], ['sku'=>$_POST['sku']])
            ->execute();
       if($update)
       {
           return json_encode(array('status'=>'success','msg'=>'updated'));
       }
    }
    return json_encode(array('status'=>'failure','msg'=>'failed to update'));

}


    public function GetSkuList(){
        $sql= 'SELECT pcp.sku,pcp.stock_status,pcp.name,pcp.cost,pcp.rccp,pd.goodQty as goodqty,pd.manual_stock,pcp.is_active
FROM `products` pcp
INNER JOIN product_details pd on
pd.sku_id = pcp.id
WHERE pcp.`sub_category` <> 167';
        $GetSkusList=Products::findBySql($sql)->asArray()->all();
        return $GetSkusList;
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
    public function actionNewUpdateSkuImport()
    {
        $New_Added=0;
        $Updated_Skus=0;
        $Error_On_Add=0;
        $Update_Not_Found = 0;
        $Update_Not_Found_Skus = '';
        $Error_On_Update=0;
        $Error_Desc_On_Update = '';
        if (isset($_POST['_csrf-backend'])) {

            $csvFile = $_FILES['csv'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {

                $json = $this->CsvToJson($_FILES['csv']['tmp_name']);
                $csvdata = json_decode($json);
                if (trim($csvdata[0][0])=='MODEL' && trim($csvdata[0][1])=='RCP' && trim($csvdata[0][2])=='Promo RCP' && trim($csvdata[0][3])=='Cost Price' ){
                    $csv_format = 1;
                    foreach ( $csvdata as $key=>$value ){

                        if ( $key==0 || json_encode($value)=='[null]')
                            continue;

                        $ps = Products::find()->where(['sku'=>trim($value[0])])->one();
                        if ( $ps ){
                            $ps->rccp = (double) $value[1];
                            $ps->promo_price = (double) $value[2];
                            $ps->cost = (double) $value[3];
                            $ps->update(false);
                            if (empty($ps->errors)){
                                $Updated_Skus++;
                            }
                            else{
                                $Error_On_Update++;
                                $Error_Desc_On_Update .= $value[0].', ';
                            }


                        }else if (!$ps){
                            $Update_Not_Found_Skus .= $value[0].', ';
                            $Update_Not_Found++;
                        }

                    }
                }else{
                    $csv_format = 0;
                }


                /*while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {

                    if ($row == 0) {
                        $row++;
                        continue;
                    }
                    if($getData[0]!='')
                    {


                        //assign foc to main asku
                        $ps = CostPrice::find()->where(['sku'=>trim($getData[0])])->one();
                        if(!$ps)
                        {
                            $ps =  new CostPrice();
                            $ps->sku = $getData[0];
                            $ps->cost = number_format($getData[5],2);
                            $ps->without_gst_cost = number_format($getData[5],2);
                            $ps->name = $getData[2];
                            $ps->promo_price = number_format($getData[4],2);
                            $ps->nc12 = $getData[1];
                            $ps->rccp_cost = number_format($getData[3],2);
                            $ps->without_gst_rccp_cost = number_format($getData[3],2);
                            $ps->sub_category = $this->exchange_values('name','id',$getData[6],'category');
                            $ps->save(false);
                            if (empty($ps->errors)){
                                $New_Added++;
                            }else{
                                $Error_On_Add++;
                            }
                        }

                        // add subsidy and margins
                        $cp = CostPrice::find()->where(['sku'=>$getData[0]])->one();
                        if($cp)
                        {
                            $ch = Channels::find()->where(['is_active'=>'1'])->all();
                            foreach ($ch as $c)
                            {

                                $sub = Subsidy::find()->where(['sku_id'=>$cp->id])->andWhere(['channel_id'=>$c->id])->one();
                                if(!$sub)
                                $sub = new subsidy();
                                $sub->sku_id = $cp->id;
                                $sub->subsidy = $getData[8];
                                $sub->margins = $getData[7];
                                $sub->ao_margins = $getData[7];
                                $sub->start_date = date('Y-m-d h:i:s');
                                $sub->end_date = date('Y-m-d h:i:s',strtotime('+ 1 month'));
                                $sub->channel_id = $c->id;
                                $sub->updated_by = '1';
                                $sub->save(false);
                                if (empty($sub->errors)){
                                    $Updated_Skus++;
                                }else{
                                    $Error_On_Update++;
                                }

                            }

                        }else {
                            echo $getData[0]."<br/>";
                        }

                    }

                }*/
            }
        }
        if ( $csv_format ){
            $alert_msg = "CSV file successfully uploaded <br /><br />";
            if ( $Updated_Skus )
                $alert_msg .= "<p style='color: green;'><b>".$Updated_Skus . "</b> Successfully updated"." <br /></p>";
            if ( $Error_On_Update ){
                $alert_msg .= "<p style='color: red;'><b>".$Error_On_Update."</b> Skus Update Error</p> <br />";
                $alert_msg .= "<br />".$Error_Desc_On_Update.'<br />';
            }
            if ( $Update_Not_Found )
                $alert_msg .= "<p style='color: red;'><b>".$Update_Not_Found."</b> Skus not found in the system.</p>";

            if ( $Update_Not_Found )
                $alert_msg .= "<p style='color: red;'>".$Update_Not_Found_Skus."</p>";

            $alert_msg .= "<br /><br />Note : Please verify the prices by searching the SKU.";
        }else{
            $alert_msg = "<br />CSV file unable to upload. Please re check the CSV columns and follow the pattern according to Sample csv file.
            <br /><a href='/sample-files/sku_import_sample.csv'>Click Here</a> to download the sample csv data file.";
        }

        Yii::$app->session->setFlash('success', $alert_msg);

        return $this->redirect(['generic']);
    }
    public function actionAddNewSku(){
        $Check_Sku_Duplicate = Products::find()->where(['sku'=>$_GET['sku_model']])->asArray()->all();
        if (!empty($Check_Sku_Duplicate)){
            echo 'Duplicate';
            die;
        }
        $_GET['cost_price']=number_format($_GET['cost_price'],2);
        $_GET['rcp']=number_format($_GET['rcp'],2);
        $_GET['margin']=number_format($_GET['margin'],2);
        $_GET['subsidy']=number_format($_GET['subsidy'],2);

        $ps =  new Products();
        $ps->sku = $_GET['sku_model'];
        $ps->cost = ($_GET['cost_price']);
        $ps->name = $_GET['product_description'];
        $ps->tnc = isset($_GET['n_c']) ? $_GET['n_c'] : '';
        $ps->rccp = $_GET['rcp'];
        $ps->cost = $_GET['cost_price'];
        if ( $_GET['extra_cost']=='' )
            $_GET['extra_cost'] = 0.00;
        $ps->extra_cost = $_GET['extra_cost'];
        $ps->created_at=time();
        $ps->updated_at=time();
        if ( $_GET['promo_price'] != '' ){
            $ps->promo_price=$_GET['promo_price'];
        }
        if($_GET['product_type']=='FOC'){
            $ps->is_foc=1;
            $ps->is_orderable=0;
        }
        $ps->sub_category = ($_GET['product_type']=='FOC') ? 167 : $_GET['sub_category'] ;
        $ps->created_by=Yii::$app->user->identity->getId();
        $ps->save(false);
        $Sku_Id=$ps->id;

        if (empty($ps->errors)){
            $this->AddSkuToProductDetails($Sku_Id);
            echo 1;
        }else{
            //$this->debug($ps->errors);
            echo 0;
        }
        $cp = Products::find()->where(['sku'=>$_GET['sku_model']])->one();
        if($cp)
        {
            $ch = Channels::find()->where(['is_active'=>'1'])->all();
            foreach ($ch as $c)
            {

                $sub = Subsidy::find()->where(['sku_id'=>$cp->id])->andWhere(['channel_id'=>$c->id])->one();
                if(!$sub)
                    $sub = new subsidy();
                $sub->sku_id = $cp->id;
                $sub->subsidy = $_GET['subsidy'];
                $sub->margins = $_GET['margin'];
                $sub->ao_margins = $_GET['margin'];
                $sub->start_date = date('Y-m-d h:i:s');
                $sub->end_date = date('Y-m-d h:i:s',strtotime('+ 1 month'));
                $sub->channel_id = $c->id;
                $sub->updated_by = '1';
                $sub->save(false);
                /*if (empty($sub->errors)){
                    $Updated_Skus++;
                }else{
                    $Error_On_Update++;
                }*/

            }

        }
    }
    private function AddSkuToProductDetails($sku_id){
        $insert_Product_Detail = new ProductDetails();
        $insert_Product_Detail->sku_id=$sku_id;
        $insert_Product_Detail->isis_sku=$_GET['sku_model'];
        $insert_Product_Detail->parent_isis_sku='0';
        $insert_Product_Detail->last_update=date('Y-m-d h:i:s');
        if($_GET['product_type']=='FOC'){
            $insert_Product_Detail->is_fbl='1';
        }
        $insert_Product_Detail->sync_for=1;
        $insert_Product_Detail->save();
        if (!empty($insert_Product_Detail->errors))
            $this->debug($insert_Product_Detail->errors);
        else{
            if ($_GET['product_type']!='FOC'){
                /*
                 * Var Array @product get the product detail
                 *
                 * */
                $product = $this->getList('sku','products',0," WHERE sku = '".$_GET['sku_model']."'");
                $T1 = $this->ManualWeeklyThresholdSetByUnitPrice($product[$_GET['sku_model']]['cost']);
                $T2 = $this->GetThresholdCritical($T1,'New');


                $Add_Threshold = new ProductStocks();
                $Add_Threshold->stock_id = $insert_Product_Detail->id;
                $Add_Threshold->is_active = 1;
                $Add_Threshold->isis_threshold = $T1;
                $Add_Threshold->isis_threshold_critical = $T2;
                $Add_Threshold->fbl_blip_threshold = $T1;
                $Add_Threshold->fbl_blip_threshold_critical = $T2;
                $Add_Threshold->fbl_909_threshold = $T1;
                $Add_Threshold->fbl_909_threshold_critical = $T2;
                $Add_Threshold->datetime_updated = date('Y-m-d H:i:s');
                $Add_Threshold->stock_status = 'New';
                $Add_Threshold->blip_stock_status = 'New';
                $Add_Threshold->f909_stock_status = 'New';
                $Add_Threshold->stocks_intransit = 0;
                $Add_Threshold->fbl_stocks_intransit = 0;
                $Add_Threshold->fbl909_stocks_intransit = 0;
                $Add_Threshold->avent_status = 'New';
                $Add_Threshold->fbl_avent_threshold=$T1;
                $Add_Threshold->fbl_avent_threshold_critical = $T2;
                $Add_Threshold->avent_stocks_intransit = 0;
                $Add_Threshold->created_by = Yii::$app->user->identity->getId();
                $Add_Threshold->updated_by = Yii::$app->user->identity->getId();
                $Add_Threshold->save();
            }
        }
    }
    public function actionSkuDuplicateCheck(){
        $sku=$_GET['sku_model'];
        $Check_Sku = Products::find()->where(['sku'=>$sku])->asArray()->all();
        if (empty($Check_Sku)){
            echo 'Unique';
        }else{
            echo 'Duplicate';
        }
    }
    public function actionUpdateManualStock(){
        $update = ProductDetails::findOne(['isis_sku'=>$_GET['sku']]);
        $update->manual_stock=$_GET['value'];
        $update->update();
        if ( empty($update->errors) )
            return json_encode(['status'=>1,'sku'=>$_GET['sku'],'value'=>$_GET['value']]);
        else
            return json_encode(['status'=>0,'sku'=>$_GET['sku'],'value'=>$_GET['value']]);
    }
    /**
     * Displays a single CostPrice model.
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
     * Creates a new CostPrice model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CostPrice();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing CostPrice model.
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
     * Deletes an existing CostPrice model.
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
     * Finds the CostPrice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return CostPrice the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CostPrice::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionSave()
    {
        $fields = Yii::$app->request->post();
        $skuId = $fields['sku'];
        $active = isset($fields['active']) ? $fields['active'] : '';
        if($fields)
        {
            $cp = Products::find()->where(['sku'=>$skuId])->one();

            $cp->is_active = $active;
            $cp->save(false);

        }
        echo json_encode(['success'=>true]);
    }

    /*public function actionUpdateExtraPrice(){
        $fields = Yii::$app->request->post();
        $skuId = $fields['sku'];
        $extra_price = isset($fields['extra_price']) ? $fields['extra_price'] : '';
        if($fields)
        {
            $cp = Products::find()->where(['sku'=>$skuId])->one();
            $cp->extra_cost = $extra_price;
            $cp->save(false);

        }
        echo json_encode(['success'=>true]);
    }*/
    public function actionUpdateMasterCotton(){
        $fields = Yii::$app->request->post();
        $skuId = $fields['sku'];
        $extra_price = isset($fields['master_cotton']) ? $fields['master_cotton'] : '';
        if($fields)
        {
            $cp = Products::find()->where(['sku'=>$skuId])->one();
            $cp->master_cotton = ($extra_price);
            $cp->save(false);

        }
        echo json_encode(['success'=>true]);
    }
    public function actionSearchSkuForMapping(){
        $status=[];
        $SearchSku=ProductDetails::findBySql("SELECT * FROM product_details where isis_sku like '".$_GET['sku_model']."%'")->asArray()->all();
        if (count($SearchSku)>6){
            $status['status']=0;
            $status['msg'] =count($SearchSku).' records matching. Please go more specific keyword.';
            return json_encode($status);
        }elseif( count($SearchSku)==0 ){
            $status['status']=0;
            $status['msg'] ='Sorry, There is no sku matching with this name.';
            return json_encode($status);
        }
        else{
            return json_encode($SearchSku);
        }
    }
    public function actionUpdateSkuMapping(){
        //$this->debug($_GET);
        $errors=[];
        $Parent_sku=$_GET['parent'][0];
        foreach ($_GET['child'] as $key=>$value){
            $UpdateMapping=ProductDetails::findOne(['isis_sku'=>$value]);
            $UpdateMapping->parent_isis_sku=$Parent_sku;
            $UpdateMapping->update();
            if ( !empty($UpdateMapping->errors) ){
                $errors[]=$UpdateMapping->errors;
            }
        }
        $UpdateMapping=ProductDetails::findOne(['isis_sku'=>$Parent_sku]);
        $UpdateMapping->parent_isis_sku='0';
        $UpdateMapping->update();
        if ( !empty($UpdateMapping->errors) ){
            $errors[]=$UpdateMapping->errors;
        }
        return json_encode($errors);
    }

    public function actionNC(){
        //They want to use same 12nc no for diff sku's
        echo 'true'; die;
        //end that why no need to check condition
        if( isset($_GET['currentsku']) && $_GET['currentsku']!='')
            $Find_N_C = Products::find()->where(['tnc'=>$_GET['n_c']])->andWhere(['<>','sku', $_GET['currentsku']])->asArray()->all();
        else
            $Find_N_C = Products::find()->where(['tnc'=>$_GET['n_c']])->asArray()->all();
        if (empty($Find_N_C)){
            echo 'true';
        }else{
            echo 'false';
        }
    }

    public function actionGetSkuData(){
        $sku =  \Yii::$app->request->post('skuname');
        $skuDetail = Products::find()->where(['sku'=>$sku])->one();
        if(!empty($skuDetail)) {
            $obj = new \stdClass();
            $obj->sku = $skuDetail['sku'];
            $obj->cost_price = $skuDetail['cost'];
            $obj->rccp = $skuDetail['rccp'];
            $obj->extra_cost = $skuDetail['extra_cost'];
            $obj->promo_price = $skuDetail['promo_price'];
            $obj->is_orderable = $skuDetail['is_orderable'];
            $obj->is_foc = $skuDetail['is_foc'];
            $obj->tnc = $skuDetail['tnc'];
        }else{
            echo "NotFound";
        }
        return json_encode($obj);
    }
    public function actionUpdateSku(){
        $ps = Products::find()->where(['sku'=>$_GET['dropdownSkus']])->one();
        if (empty($ps)){
            echo 'NotFound';
            die;
        }
        //$_GET['update_cost_price']=number_format($_GET['update_cost_price'],2);
        //$_GET['update_rcp']=number_format($_GET['update_rcp'],2);

        $ps->tnc = isset($_GET['update_tnc']) ? $_GET['update_tnc'] : '';
        $ps->rccp = $_GET['update_rcp'];
        $ps->cost = $_GET['update_cost_price'];
        $ps->extra_cost = $_GET['update_extra_cost'];
        $ps->updated_at=time();
        if ( $_GET['update_promo_price'] != '' ){
            $ps->promo_price=$_GET['update_promo_price'];
        }
        $ps->updated_by =Yii::$app->user->identity->getId();
        $ps->save(false);
        if (empty($ps->errors)){
            echo 1;
        }else{
            echo 0;
        }
    }
}
