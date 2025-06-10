<?php
namespace backend\controllers;
use common\models\ProductRelationsSkus;
use common\models\Products;
use common\models\ProductsRelations;
use common\models\ThresholdSales;
use common\models\Warehouses;

/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/17/2018
 * Time: 4:26 PM
 */
class BundlesController extends GenericGridController {
    public function actionGeneric(){
        $BundleType="SELECT pr.relation_type AS `key`, pr.relation_type AS `value`
                     FROM products_relations pr
                     GROUP BY pr.relation_type;";
        $BundleType = Products::findBySql($BundleType)->asArray()->all();
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/bundles/generic-info',
                    'sortUrl' => '/bundles/generic-info-sort',
                    'filterUrl' => '/bundles/generic-info-filter',
                    'jsUrl'=>'/bundles/generic',
                ],
                'thead'=>
                    [
                        'Bundle Name' => [
                            'data-field' => 'pr.relation_name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pr.relation_name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Bundle Type' => [
                            'data-field' => 'pr.relation_type',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pr.relation_type',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $BundleType,
                            'input-type-class' => ''
                        ],
                        'Bundle Price' => [
                            'data-field' => 'pr.bundle_cost',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pr.bundle_cost',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                        ,
                        'Start Date' => [
                            'data-field' => 'pr.start_at',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pr.start_at',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => 'mydatepicker'
                        ],
                        'End Date' => [
                            'data-field' => 'pr.end_at',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pr.end_at',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => 'mydatepicker'
                        ],
                        'Status' => [
                            'data-field' => 'pr.is_active',
                            'label' => 'show',
                            'data-filter-field' => 'pr.is_active',
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
        $session->remove('sku-imported');
        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku,'config'=>$config]);;
        $roleId = \Yii::$app->user->identity->role_id;
        $Skus_List_Without_FOC = Products::find()->where(['is_active'=>1,'is_foc'=>0])->asArray()->all();
        $Foc_List=Products::find()->where(['is_foc'=>1,'is_active'=>1])->asArray()->all();
        return $this->render('generic-view',['gridview'=>$html,'roleId' => $roleId,'skus_without_foc'=>$Skus_List_Without_FOC,
            'foc_skus'=>$Foc_List]);
    }
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT pr.id,pr.relation_name, pr.relation_type, pr.bundle_cost, pr.start_at, pr.end_at,pr.is_active FROM products_relations pr
                               WHERE 1=1 ",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY pr.start_at DESC',
            'SortOrderByColumnAlias' => 'pr',
        ];
        return $config;
    }
    public function actionBundleDetail(){
        $Get_Main_Bundle = "SELECT * FROM products_relations pr 
                            WHERE pr.id = '".$_GET['bundle_id']."';";
        $Get_Detail = ProductsRelations::findBySql($Get_Main_Bundle)->asArray()->all();

        // for the skus related to the bundle

        $Get_Skus = "SELECT prs.*,p.sku,pd.stocks FROM product_relations_skus prs
                    INNER JOIN products p ON
                    p.id = prs.child_sku_id
                    LEFT JOIN product_details pd ON 
                    pd.sku_id = p.id
                    WHERE prs.bundle_id = ".$Get_Detail[0]['id'];
        $Get_Child_Detail = ProductRelationsSkus::findBySql($Get_Skus)->asArray()->all();
        $Parent_Stocks_Quantity = $this->exchange_values('sku_id','stocks',$Get_Child_Detail[0]['main_sku_id'],'product_details');
        $Main_Sku_Id = $this->exchange_values('id','sku',$Get_Child_Detail[0]['main_sku_id'],'products');
        echo $this->renderPartial('popup/bundle-popup-detail',['Parent_Stocks_Quantity'=>$Parent_Stocks_Quantity,'main_sku'=>$Main_Sku_Id,'child_skus'=>$Get_Child_Detail,'bundle_info'=>$Get_Detail]);
    }
    public function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }
    public function actionBundleType(){
        if (isset( $_GET['child_skus'] )){
            $Already_Child_Skus = explode(',',$_GET['child_skus']);
        }else{
            $Already_Child_Skus = [];
        }


        if ($_GET['bundle_type']=='FOC'){
            $Sku_List = Products::find()->where(['is_active'=>1,'is_foc'=>1])->asArray()->all();
        }elseif($_GET['bundle_type']=='FB'){
            $Sku_List = Products::find()->where(['is_active'=>1])->asArray()->all();
        }elseif($_GET['bundle_type']=='VB'){
            $Sku_List = Products::find()->where(['is_active'=>1])->asArray()->all();
        }
        echo $this->renderPartial('popup/bundle-type-dropdown',['Sku_List'=>$Sku_List]);
        die;
    }
    public function actionCheckBundleAlreadyExist(){
        $FindBundle=ProductsRelations::find()->where(['relation_name'=>trim($_GET['bundle_name'])])->asArray()->all();
        if ( empty($FindBundle) )
        {
            echo 1;
        }else{
            echo 0;
        }
    }
    private function CheckBundleAlreadyExist($data){
        $Main_Sku_Id = $this->exchange_values('sku','id',$data['main_sku'],'products');
        $sql="SELECT *
            FROM product_relations_skus
            WHERE main_sku_id = $Main_Sku_Id
            AND
            (
            ";
        $condition=[];
        foreach ( $data['child_skus'] as $key=>$value ){
            $Child_Sku_Id=$this->exchange_values('sku','id',$value,'products');
            $condition[] = " (child_sku_id = $Child_Sku_Id AND child_quantity = ".$_GET['Quantity'][$key].")";
        }
        $condition = implode(' OR ',$condition);
        $sql .= $condition.' )';
        $Search_Bundle = ProductRelationsSkus::findBySql($sql)->asArray()->all();
        if ( !empty($Search_Bundle) ){
            foreach ($Search_Bundle as $key) {
                $Bundle_Id = $key['bundle_id'];
                $isBundleActive = ProductsRelations::findBySql("SELECT relation_name FROM products_relations where is_active = 1 AND id = $Bundle_Id")
                    ->one();
                if (empty($isBundleActive)) {
                    continue;
                }else{
                        $CheckBundleCount = ProductRelationsSkus::findBySql("SELECT * FROM product_relations_skus where bundle_id = $Bundle_Id")
                            ->asArray()->all();
                        $Old_Bundle_Skus_Count = count($CheckBundleCount);
                        if ($Old_Bundle_Skus_Count == count($data['child_skus'])) {
                            $Bundle_Name = $this->exchange_values('id', 'relation_name', $Bundle_Id, 'products_relations');
                            return ['status' => 0, 'Message' => 'Bundle already exist with the name ' . $Bundle_Name . ' and with the same combination. You first need to DeActivate this bundle'];
                        }
                    }
            }
        }else{
            return [];
        }
    }
    public function actionAddBundle(){
        $CheckAlreadyExist = $this->CheckBundleAlreadyExist($_GET);
        $Status=['status'=>1];
        if (!empty($CheckAlreadyExist)){
            $Status=$CheckAlreadyExist;
        }else{
            $Add_Product_Relations = new ProductsRelations();
            $Add_Product_Relations->relation_type=strtoupper($_GET['product_type_bundle']);
            $Add_Product_Relations->relation_name=$_GET['bundle_name'];
            $Add_Product_Relations->is_active='1';
            $Add_Product_Relations->start_at=date('Y-m-d H:i:s',strtotime($_GET['start_date']));
            $Add_Product_Relations->end_at=date('Y-m-d H:i:s',strtotime($_GET['end_date']));
            $Add_Product_Relations->created_at=time();
            $Add_Product_Relations->updated_at=time();
            $Add_Product_Relations->created_by=\Yii::$app->user->identity->getId();
            $Add_Product_Relations->updated_by=\Yii::$app->user->identity->getId();
            $Add_Product_Relations->bundle_cost=$_GET['bundle_price'];
            $Add_Product_Relations->save();
            if (!empty($Add_Product_Relations->errors)){
                $Status['status']=0;
            }
            $Bundle_Id=\Yii::$app->db->lastInsertID;

            foreach ( $_GET['child_skus'] as $key=>$value ){
                $child_sku_id = $this->exchange_values('sku','id',$value,'products');
                $Sku_Type=$this->exchange_values('id','is_foc',$child_sku_id,'products');
                $Add_Skus_Bundle = new ProductRelationsSkus();
                $Add_Skus_Bundle->bundle_id = $Bundle_Id;
                $Add_Skus_Bundle->main_sku_id = $this->exchange_values('sku','id',$_GET['main_sku'],'products');
                $Add_Skus_Bundle->child_sku_id = $child_sku_id;
                $Add_Skus_Bundle->child_quantity = $_GET['Quantity'][$key];
                ( $Sku_Type==1 ) ? $Add_Skus_Bundle->child_type = 'FOC' : $Add_Skus_Bundle->child_type = 'ORDERABLE';
                $Add_Skus_Bundle->save();
                if ( !empty($Add_Skus_Bundle->errors) )
                {
                    $Status['status']=0;
                }
            }
            // Add as product in products table
            $FindProductBundle = Products::find()->where(['sku'=>$_GET['bundle_name']])->asArray()->all();

            if (!$FindProductBundle){
                $MainSkuCategoryId = $this->exchange_values('sku','sub_category',$_GET['main_sku'],'products');

                $addProduct = new Products();
                $addProduct->sku = $_GET['bundle_name'];
                $addProduct->name = $_GET['bundle_name'];
                $addProduct->cost = $_GET['bundle_price'];
                $addProduct->rccp = $_GET['bundle_price'];
                $addProduct->selling_status = 'Slow';
                $addProduct->stock_status = 'Slow';
                $addProduct->sub_category = $MainSkuCategoryId;
                $addProduct->created_at = time();
                $addProduct->updated_at = time();
                $addProduct->created_by = \Yii::$app->user->getId();
                $addProduct->updated_by = \Yii::$app->user->getId();
                $addProduct->save();
                //$this->debug($addProduct->errors);
                if ( !empty($addProduct->errors) ){
                    $this->debug($addProduct->errors);
                }
                // Now add the bundle threshold
                $Warehouses = Warehouses::find()->asArray()->all();
                foreach ( $Warehouses as $wDetail ){
                    $addThreshold = new ThresholdSales();
                    $addThreshold->warehouse_id = $wDetail['id'];
                    $addThreshold->product_id = $addProduct['id'];
                    $addThreshold->sku = $_GET['bundle_name'];
                    $addThreshold->sales = 0;
                    $addThreshold->threshold = 0;
                    $addThreshold->status = 'Not Moving';
                    $addThreshold->added_at = date('Y-m-d H:i:s');
                    $addThreshold->updated_at = date('Y-m-d H:i:s');
                    $addThreshold->save();
                }


            }
        }
        return json_encode($Status);
    }
    public function actionGetSkuCost(){
        $cost=$this->exchange_values('sku','cost',$_GET['sku'],'products');
        return json_encode(['cost'=>$cost]);
    }
    public function actionUpdateBundleStatus(){
        $FindBundle = ProductsRelations::findOne(trim($_GET['bundle_id']));
        if (empty($FindBundle)) {
            echo "notfound";
        } else {
            if($FindBundle->is_active == '0'){
                $alreadyExistBundle = self::CheckBundleAlreadyActivated(trim($_GET['bundle_id']));
                if (!empty($alreadyExistBundle)){
                    echo $alreadyExistBundle;return;
                }else {
                    $FindBundle->is_active = '1';
                }
            }else{
                $FindBundle->is_active = '0';
            }
            $FindBundle->update(false);
            if (!empty($FindBundle->errors)) {
                print_r($FindBundle->errors);
            } else
                echo "updated";
        }
        return;
    }
    private function CheckBundleAlreadyActivated($bundleId){
        $sql = "select * from product_relations_skus where bundle_id=$bundleId";
        $bundledSkus = ProductRelationsSkus::findBySql($sql)->asArray()->all();
        $bundleChildSkus = [];
        $counter=0;
        $bundleMainSku = "";
        if(isset($bundledSkus)){
            foreach ($bundledSkus as $key => $value){
                foreach ($value as $subkey => $subvalue) {
                    if($subkey=="main_sku_id"){
                        $bundleMainSku = $subvalue;
                    }
                    else if($subkey=="child_sku_id") {
                        $bundleChildSkus[$counter]["sku"]= $subvalue;
                    }
                    else if($subkey=="child_quantity") {
                        $bundleChildSkus[$counter]["quantity"] = $subvalue;
                    }
                }
                $counter++;
            }
        }
        $sql="SELECT *
            FROM product_relations_skus
            WHERE main_sku_id = $bundleMainSku
            AND bundle_id != $bundleId
            AND
            (
            ";
        $condition=[];
        foreach ( $bundleChildSkus as $key=>$value ){
            $childSku = 0;
            $childQuantity = 0;
            foreach ( $value as $subkey=>$subvalue ) {
                if($subkey=="sku")
                    $childSku = $subvalue;
                if($subkey=="quantity")
                    $childQuantity = $subvalue;
            }
            if($childSku!=0 && $childQuantity !=0)
            $condition[] = " (child_sku_id = $childSku AND child_quantity = " . $childQuantity . ")";
        }
        $condition = implode(' OR ',$condition);
        $sql .= $condition.' )';

        $Search_Bundle = ProductRelationsSkus::findBySql($sql)->asArray()->all();
        if ( !empty($Search_Bundle) ){
            foreach ($Search_Bundle as $key) {
                $Bundle_Id = $key['bundle_id'];
                $isBundleActive = ProductsRelations::findBySql("SELECT relation_name FROM products_relations where is_active = 1 AND id = $Bundle_Id")
                    ->one();
                if (empty($isBundleActive)) {
                    continue;
                }else{
                    $CheckBundleCount = ProductRelationsSkus::findBySql("SELECT * FROM product_relations_skus where bundle_id = $Bundle_Id")
                        ->asArray()->all();
                    $Old_Bundle_Skus_Count = count($CheckBundleCount);
                    if ($Old_Bundle_Skus_Count == count($bundleChildSkus)) {
                        $Bundle_Name = $this->exchange_values('id', 'relation_name', $Bundle_Id, 'products_relations');
                        return 'Bundle already exist with the name ' . $Bundle_Name . ' and with the same combination. You first need to DeActivate that bundle';
                    }
                }
            }
        }else{
            return "";
        }
    }
}