<?php

namespace backend\controllers;

use backend\util\GlobalMobileUtil;
use backend\util\GraphsUtil;
use backend\util\HelpUtil;
use backend\util\InventoryUtil;
use backend\util\ProductsUtil;
use backend\util\WarehouseUtil;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\EzcomToWarehouseProductSync;
use common\models\GlobalMobileCsvProducts;
use common\models\GlobalMobileCsvRecords;
use common\models\GlobalMobilesCatMapping;
use common\models\ProductCategories;
use common\models\Products;
use common\models\SetProductNew;
use common\models\Warehouses;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class ProductsController extends MainController
{
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
            ]
        ];
    }
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $products=ProductsUtil::getProductList();
      //  self::debug($products);
        //echo "<pre>";
        //print_r($products); die();
        $channels=Channels::find()->where(['is_active'=>'1'])->asArray()->all();
       // $Categories=Category::find()->where(['is_active'=>1])->andWhere('parent_id<>NULL')->asArray()->all();
        $dd_categories=Category::find()->where(['is_active'=>1])->asArray()->all();
        $dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
        $categories =\backend\util\HelpUtil::dropdown_3_level($dd_categories);
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
        $styles=Products::find()->select('style')->distinct()->asArray()->all();
        return $this->render('all',['products'=>$products['data'],'total_records'=>$products['total_records'],'channels'=>$channels,'categories'=>$categories,'brands'=>$brands, 'styles'=>$styles]);
    }

    //update category of product
    public function actionUpdateProductCategory()
    {
        if(isset($_POST['product_id']) && isset($_POST['cat']) && $_POST['product_id'] && $_POST['cat'])
        {
            $cat_list=$_POST['cat'];
            ProductCategories::deleteAll(['product_id' => $_POST['product_id']]);
            foreach ($cat_list as $cat_id)
            {
                $insert=new ProductCategories();
                $insert->product_id=$_POST['product_id'];
                $insert->cat_id=$cat_id;
                $insert->save();
            }
             return $this->asJson(['status'=>'success','msg'=>'updated']);

        }
        return $this->asJson(['status'=>'failure','msg'=>'failed to update']);

    }

    /**
     * update barcode
     */
    public function actionUpdateBarcode()
    {
        if(isset($_POST['id']) && isset($_POST['barcode']) && $_POST['id'])
        {
            $barcode=$_POST['barcode'] ? $_POST['barcode']:NULL;
            $update= Yii::$app->db->createCommand()
                ->update('products', ['barcode' =>  trim($barcode)], ['id'=>$_POST['id']])
                ->execute();
            if($update)
                return $this->asJson(['status'=>'success','msg'=>'updated']);

        }
        return $this->asJson(['status'=>'failure','msg'=>'failed to update']);
    }

    /***
     * update extra cost price
     */
    public function actionUpdateExtraPrice(){
        $fields = Yii::$app->request->post();
        if(isset($fields['extra_price']) && isset($fields['sku']))
        {
            $cp = Products::find()->where(['sku'=>$fields['sku']])->one();
            $cp->extra_cost = $fields['extra_price'];
            if($cp->save(false))
                return $this->asJson(['status'=>'success','msg'=>'Updated']);

        }
        return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }
    //activate deactivate product
    public function actionActivateDeactivateProduct()
    {
        if(isset($_POST['sku']) && isset($_POST['option']) && $_POST['sku'] && in_array($_POST['option'],['1','0']))
        {
            $update= Yii::$app->db->createCommand()
                ->update('products', ['is_active' =>  $_POST['option']], ['sku'=>$_POST['sku']])
                ->execute();
            if($update)
                return $this->asJson(['status'=>'success','msg'=>'updated']);

        }
        return $this->asJson(['status'=>'failure','msg'=>'failed to update']);

    }

    /*
     * $id is needed
     */

    public function actionDetail($sku)
    {
       // echo $sku; die();
        $parent=[];
        $variations=[];
      //  $siblings=[];
        $sku_list=[];
        $product=Products::findOne(['sku'=>$sku]);
        $channels=ChannelsProducts::find()->where(['channel_sku'=>$sku])->with('channel')->asArray()->all();
        if($product)
        {
            if($product->parent_sku_id){  // if product is variation find its siblings and parent
                $parent=Products::findone(['id'=>$product->parent_sku_id]);
                $variations=Products::find()->where(['parent_sku_id'=>$product->parent_sku_id])->asArray()->all();
            }else{ // if product is parent
                $variations=Products::find()->where(['parent_sku_id'=>$product->id])->asArray()->all();
                $sku_list=array_column($variations,'sku');
            }

            if(!$product->rccp){  // incase of parent often rccp is not set
                $product->rccp=isset($variations[0]['rccp']) ? $variations[0]['rccp']:0 ;
                $product->cost=isset($variations[0]['cost']) ? $variations[0]['cost']:0;
            }
        }
       // if($variations)
         //   $sku_list=array_column($variations,'sku');

        $sku_list[]=$sku;
        $inventory=InventoryUtil::get_sku_inventory($sku_list);
        $sales=GraphsUtil::get_sku_sale($sku_list);
        $_GET['sku_list']=$sku_list;  // for sales SalesGraphByShop();
        //self::debug($_GET);
        $monthly_graph=GraphsUtil::SalesGraphByShop();
        $total_sales=GraphsUtil::total_sales();
        $first_order=GraphsUtil::first_order_date();
        //self::debug($monthly_graph);
        return $this->render('product_detail',['product'=>$product,
                                                    'p_channels'=>$channels,
                                                    'inventory'=>$inventory,
                                                    'sales'=>$sales,
                                                    'monthly_sales_graph'=>$monthly_graph,
                                                    'total_sales'=>$total_sales,
                                                    'first_order'=>$first_order,
                                                    'parent'=>$parent,
                                                    'variations'=>$variations ,
        ]);
    }

    public function actionDownloadMissingImagesSkus()
    {
        $channel_id=Yii::$app->request->post('channel_id');
        return ProductsUtil::DownloadMissingImagesSkus($channel_id);
    }

    /**
     * @return mixed export products in csv
     */
    public function actionExportCsv()
    {

        $_GET['record_per_page']=25000; // to fetch all not by particular page
        $data=ProductsUtil::getProductList();
        $categories=[];
        $cat_list=Category::find()->where(['is_active'=>1])->asArray()->all();
        foreach($cat_list as $cat)
        {
            $categories[$cat['id']]=$cat;
        }
       // self::debug($categories);
       // $dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
        //$categories =\backend\util\HelpUtil::dropdown_3_level($dd_categories);
        $channels=Channels::find()->where(['is_active'=>'1'])->asArray()->all();
        return  ProductsUtil::export_csv($data,$categories,$channels);
    }

    public function actionExportMagentoCsv()
    {

        $_GET['record_per_page']=25000; // to fetch all not by particular page
        $data=ProductsUtil::getProductMagentoAttributeLists();

        return  ProductsUtil::export_magento_csv($data);
    }

    public function actionChildParentMapping(){

        $skus = Products::find()->select(['id','sku'])->where(['is', 'parent_sku_id', null])->orWhere(['parent_sku_id'=>0])->asArray()->all();

        return $this->render('child-parent-mapping/index',['skus'=>$skus]);
    }
    public function actionChildParentMappingSave(){

        $status=[];
        $status['success']=0;
        $status['failed']=0;
        foreach ( $_POST['child-skus'] as $skuId ){
            $updateProduct = Products::findOne($skuId);
            $updateProduct->parent_sku_id = $_POST['parent-skus'];
            $updateProduct->update();
            if ( ($updateProduct->errors) ){
                $status['failed']++;
            }else{
                $status['success']++;
            }
        }

        Yii::$app->session->setFlash('success', ($status['success'])." Skus Mapping Saved");
        if ( $status['failed']>0 ){
            Yii::$app->session->setFlash('failed', ($status['failed'])." Skus count not mapped with Parent Sku.");
        }

        $this->redirect('/products/child-parent-mapping');
    }

    /****
     * set product as new  // magento presta new label
    ****/
    public function actionSetProductNew()
    {
        $channels=Channels::find()->where(['is_active'=>'1'])->asArray()->all();
        return $this->render('set_product_new',['skus'=>'','title'=>'Set Product New','channels'=>$channels]);
    }
    public function actionSetProductNewPost()
    {
       //  die('come');
        //$session = Yii::$app->session;
        $error_list=array();
        $already_exists=[];
        $updated_count=0;
        if(!isset($_POST['channel']) || !$_POST['channel'])
            return $this->asJson(['status'=>'failure','msg'=>'select channel','updated_list'=>$updated_count]);

        if (isset( $_FILES['csv']) && $_FILES['csv']['size'] > 0  )
        {
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
            if ($ext!='csv')
                return $this->asJson(['status'=>'failure','msg'=>'only csv files allowed','updated_list'=>$updated_count]);

            // csv to json
            $json = $this->CsvToJson($_FILES['csv']['tmp_name']);
            //self::debug($json);
            $json = json_decode($json);
           // self::debug($json);
            if(empty($json))
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);

            foreach ( $json as $value )
            {
                if(!isset($value->sku) || !isset($value->set_new_from) || !isset($value->set_new_to))
                    continue;

                $sku=trim($value->sku,"'");   // remove commas on start and end if have
                $set_new_from=trim($value->set_new_from,"'");
                $set_new_from=date('Y-m-d H:i:s',strtotime($set_new_from));
                $set_new_to=trim($value->set_new_to,"'");
                $set_new_to=date('Y-m-d H:i:s',strtotime($set_new_to));
                $record=SetProductNew::findOne(['sku'=>$sku,'action'=>$_POST['action'],'set_new_from'=>$set_new_from,'set_new_to'=>$set_new_to,'channel_id'=>$_POST['channel'],'updated'=>0,'error_in_update'=>0]);
                if($record)
                    $already_exists[]=$sku;
                else{
                    $new_rec=new SetProductNew();
                    $new_rec->sku=$sku;
                    $new_rec->channel_id=$_POST['channel'];
                    $new_rec->set_new_from=$set_new_from;
                    $new_rec->set_new_to=$set_new_to;
                    $new_rec->action=$_POST['action'];
                    $new_rec->added_by=Yii::$app->user->identity->role_id;
                    $new_rec->added_at=date('Y-m-d H:i:s');
                    if(!$new_rec->save())
                        $error_list[]=$sku;
                    else
                        $updated_count ++;
                }



            }

            return $this->asJson(
                    ['status'=>$updated_count > 0 ? 'success':'failure',
                    'msg'=>'',
                    'not_updated_list'=>$error_list,
                    'already_exists'=>$already_exists,
                    'updated_list'=>$updated_count,
                ]
            );


            // $this->redirect(array('/cost-price/generic'));
        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$updated_count]);
        }
    }


    public function actionCsvColumnsSwapper()
    {
        $csv_id=0;
        $updated=0;
        $errors=[];
        if (isset( $_FILES['csv']) && $_FILES['csv']['size'] > 0  )
        {
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
            //self::debug($ext);
            if ($ext!='csv'){
                return $this->asJson(['status'=>'failure','msg'=>'Only CSV Allowed','not_updated_list'=>$errors,'updated_list'=>$updated]);
            }
            // csv to json
            $item_list= $this->CsvToJson($_FILES['csv']['tmp_name'],false);

            // self::debug( $item_list);

            if(empty( $item_list)){
                return $this->asJson(['status'=>'failure','msg'=>'Check file format or content','not_updated_list'=>$errors,'updated_list'=>$updated]);
            }
            //////////////////////////
            $headers_should_be=['UPC scan','Description','Condition','Physical Counts','Inventory','Sales (Qty.)','Unit Cost','Sell Price','MSRP','Wholesale Price','Man. part#','Parent Category','Sub-Category','Brand','Color','Size','Category','Detail Description','Bullet 1','Bullet 2','Bullet 3','Bullet 4','Bullet 5','ASIN','Weight','Length','Height','Width','NAV','Ebay','Amazon','Presta','Amazon cr','Website','Stock list updated','EZCom','Remarks','Picture 1','Picture 2','Picture 3','Picture 4','Picture 5','Picture 6','Picture 7','Picture 8','id'];
            if($headers_should_be!=array_keys($item_list[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Csv Column Headers mismatched check CSV','not_updated_list'=>$errors,'updated_list'=>$updated]);
            }
            $sort_by_name = array_column( $item_list, 'Detail Description');  // if sorting in php
            array_multisort($sort_by_name, SORT_ASC,   $item_list);  // sort by name  // if sorting in php
           // self::debug( $item_list);
            /// //////////////////
            ////////////// save csv
            $img = UploadedFile::getInstanceByName('csv');
            $imageName = date('Y-m-d-H-i-s-').$_FILES['csv']['name'];
            //$result = $s3->upload($imageName, $img->tempName);
            $path = 'product_csv_swapper/';
            try{
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                    chmod($path, 0777);
                    $csv_path=null; // for database storage
                    if($img->saveAs($path . $imageName))
                        $csv_path=$path.$imageName;
            } catch (\Exception $e) { }
            $csv_record=new GlobalMobileCsvRecords();
            $csv_record->input_csv_name=$_FILES['csv']['name'];
            $csv_record->input_csv_location=$csv_path;
            $csv_record->csv_added_at=date('Y-m-d H:i:s');
            $csv_record->csv_updated_at=date('Y-m-d H:i:s');
            $csv_record->save();
            $csv_id=$csv_record->id;
            $website_url="http://54.145.139.107/product_image/";
            /// ////////////

            foreach ( $item_list as $value)
            {
                $upc=trim($value['UPC scan'],"'");
                if(empty($upc))
                    continue;

                $product=new GlobalMobileCsvProducts();
                $product->csv_id=$csv_id;
                $product->upc_scan=$upc;
                $product->description=$value['Description'];
                $product->condition=$value['Condition'];
                $product->physical_counts=$value['Physical Counts'];
                $product->inventory=$value['Inventory'];
                $product->sales_qty=$value['Sales (Qty.)'];
                $product->unit_cost=$value['Unit Cost'];
                $product->sell_price=$value['Sell Price'];
                $product->msrp=$value['MSRP'];
                $product->wholesale_price=$value['Wholesale Price'];
                $product->man_part_no=$value['Man. part#'];
                $product->parent_category=$value['Parent Category'];
                $product->sub_category=$value['Sub-Category'];
                $product->brand=$value['Brand'];
                $product->color=$value['Color'];
                $product->size=$value['Size'];
                $product->category=$value['Category'];
                $product->detail_description=$value['Detail Description'];
                $product->bullet_1=$value['Bullet 1'];
                $product->bullet_2=$value['Bullet 2'];
                $product->bullet_3=$value['Bullet 3'];
                $product->bullet_4=$value['Bullet 4'];
                $product->bullet_5=$value['Bullet 5'];
                $product->asin=$value['ASIN'];
                $product->weight=$value['Weight'];
                $product->length=$value['Length'];
                $product->height=$value['Height'];
                $product->width=$value['Width'];
                $product->nav=$value['NAV'];
                $product->ebay=$value['Ebay'];
                $product->amazon=$value['Amazon'];
                $product->presta=$value['Presta'];
                $product->amazon_cr=$value['Amazon cr'];
                $product->website=$value['Website'];
                $product->stock_list_updated=$value['Stock list updated'];
                $product->ezcom=$value['EZCom'];
                $product->remarks=$value['Remarks'];
                $product->picture_1=$website_url.$upc.".jpg";
                $product->picture_2=$website_url.$upc."_1.jpg";
                $product->picture_3=$website_url.$upc."_2.jpg";
                $product->picture_4=$website_url.$upc."_3.jpg";
                $product->picture_5=$website_url.$upc."_4.jpg";
                $product->picture_6=$website_url.$upc."_5.jpg";
                $product->picture_7=$website_url.$upc."_6.jpg";
                $product->picture_8=$website_url.$upc."_7.jpg";
              //  for($pic=2;$pic<=7;$pic++)
              //      $product->picture_.$pic=$website_url.$upc."_".$pic.".jpg";

                $product->added_at=date('Y-m-d H:i:s');
                $product->updated_at=date('Y-m-d H:i:s');
                if(!$product->save()){
                    $error[]=$upc;
                }else{
                    $updated++;
                }

            }
           // echo $success ." uploaded";
           // echo "<br/>";
          //  self::debug($error);
            return $this->asJson(['status'=>'success','msg'=>'Csv tranformation in progress','not_updated_list'=>$errors,'updated_list'=>$updated]);
            Yii::$app->session->setFlash('success','');


        }else{
            $records=GlobalMobileCsvRecords::find()->asArray()->all();
            return $this->render('csv_column_swapper',['title'=>'Global Mobiles Content Management','downloads'=>$records]);
        }

    }



    public function actionValidateImages()
    {
        if (isset( $_FILES['csv']) && $_FILES['csv']['size'] > 0  )
        {
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
            //self::debug($ext);
            if ($ext!='csv'){
                return $this->asJson(['status'=>'failure','msg'=>'only csv files allowed']);
            }

            $item_list= $this->CsvToJson($_FILES['csv']['tmp_name'],false);
            $sort_by_name = array_column( $item_list, 'Detail Description');
            array_multisort($sort_by_name, SORT_ASC,   $item_list);  // sort by name
             //self::debug( $item_list);
            if(empty( $item_list)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content']);
            }
            $list=[];
          //  $count=0;
            foreach ( $item_list as $value)
            {
                //if($count > 300)
              //      continue;

                $list[$value['UPC scan']]=['image1'=>$value['Picture 1'],'image2'=>$value['Picture 2'],'image3'=>$value['Picture 3'],
                    'image4'=>$value['Picture 4'],'image5'=>$value['Picture 5'],'image6'=>$value['Picture 6'],'image7'=>$value['Picture 7'],'image8'=>$value['Picture 8']];
             //   $count++;
            }
            //ech
            //echo( count($list)); die();
            if($list)
                return $this->asJson(['status'=>'success','msg'=>'','data'=>$list]);

        }
             return $this->asJson(['status'=>'failure','msg'=>'failed to process']);

    }
    /*************** specifically for global mobile client************/
    public function actionCategoryMapping()
    {
        $errors=[];
        $updated=0;
        if (isset( $_FILES['csv']) && $_FILES['csv']['size'] > 0  )
        {
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
            //self::debug($ext);
            if ($ext!='csv'){
                return $this->asJson(['status'=>'failure','msg'=>'Only CSV Allowed','not_updated_list'=>$errors,'updated_list'=>$updated]);
            }
            // return $this->asJson(['status'=>'failure','msg'=>'only csv files allowed','updated_list'=>$updated_count]);

            // csv to json
            $cats= $this->CsvToJson($_FILES['csv']['tmp_name'],false);
           // self::debug(array_keys($cats[0]));
            if(empty($cats)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','not_updated_list'=>$errors,'updated_list'=>$updated]);
            }
            $csv_headers=array_keys($cats[0]);
            if(!in_array('Client Main Category',$csv_headers))
                return $this->asJson(['status'=>'failure','msg'=>'Client Main Category header missing','not_updated_list'=>$errors,'updated_list'=>$updated]);

            if(!in_array('Client Sub Category',$csv_headers))
                return $this->asJson(['status'=>'failure','msg'=>'Client Sub Category header missing','not_updated_list'=>$errors,'updated_list'=>$updated]);

            if(!in_array('Sub Category 1',$csv_headers))
                return $this->asJson(['status'=>'failure','msg'=>'Sub Category 1 header missing','not_updated_list'=>$errors,'updated_list'=>$updated]);

            if(!in_array('Sub Category 2',$csv_headers))
                return $this->asJson(['status'=>'failure','msg'=>'Sub Category 2 header missing','not_updated_list'=>$errors,'updated_list'=>$updated]);

            if(!in_array('Sub Category 3',$csv_headers))
                return $this->asJson(['status'=>'failure','msg'=>'Sub Category 3 header missing','not_updated_list'=>$errors,'updated_list'=>$updated]);


            foreach ($cats as $cat)
            {
                $record=GlobalMobilesCatMapping::findOne(['client_main_cat'=>$cat['Client Main Category'],'client_sub_cat1'=>$cat['Client Sub Category']]);
                if(!$record)
                {
                    $record=new GlobalMobilesCatMapping();
                    $record->client_main_cat=$cat['Client Main Category'];
                    $record->client_sub_cat1=$cat['Client Sub Category'];
                    $record->mapped_main_cat=$cat['Main Category'];
                    $record->mapped_sub_cat1=$cat['Sub Category 1'];
                    $record->mapped_sub_cat2=$cat['Sub Category 2'];
                    $record->mapped_sub_cat3=$cat['Sub Category 3'];
                    $record->updated_at=date('Y-m-d H:i:s');
                    $record->added_at=date('Y-m-d H:i:s');
                    if(!$record->save()){
                        $errors[]=$record->client_main_cat ."->".$record->client_sub_cat1 ;
                    }
                    else
                        $updated++;
                }else{
                    $record->mapped_main_cat=$cat['Main Category'];
                    $record->mapped_sub_cat1=$cat['Sub Category 1'];
                    $record->mapped_sub_cat2=$cat['Sub Category 2'];
                    $record->mapped_sub_cat3=$cat['Sub Category 3'];
                    $record->updated_at=date('Y-m-d H:i:s');
                    if(!$record->update()){
                        $errors[]=$record->client_main_cat ."->".$record->client_sub_cat1 ;
                    }
                    else
                        $updated++;

                }
            }
            return $this->asJson(['status'=>'success','msg'=>'updated','not_updated_list'=>$errors,'updated_list'=>$updated]);
        } else{
            return $this->asJson(['status'=>'failure','msg'=>'CSV File required','not_updated_list'=>$errors,'updated_list'=>$updated]);
        }
    }
    public function actionCsvColumnsSwapperPost()
    {
       // $updated_count=0;
        die('in progress');

    }

    /*********products synced to third party warhouse************/

    public function actionProductSyncToWarehouse()
    {

        $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        $product = ProductsUtil::productsToWarehouseProductsList();
        $failed_sync=EzcomToWarehouseProductSync::find()->where(['status'=>'failed'])->count();
       // self::debug($failed_sync);
        return $this->render('products-warehouse-sync', [
                'products' => $product['data'],
                'warehouses'=>$warehouses,
                'failed_sync_count'=>$failed_sync,
                'total_records' => $product['total_records'],
                ]
        );
    }

    /************************export product sync to warehouse*******************************************/
    public function actionExportProductSyncToWarehouse()
    {
        $_GET['record_per_page']=30000; // to fetch all not by particular page
        $data = ProductsUtil::productsToWarehouseProductsList();
        //self::debug($data);
        return  ProductsUtil::export_product_warehouse_sync_csv($data);

    }
    /*******************products that are not yet assign to be push/sync to any third party warehouse************/

    public function actionProductsNotAssignedToWarehouse()
    {
        $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        $product = ProductsUtil::productsNotAssignedToWarehouse();
        $failed_sync=EzcomToWarehouseProductSync::find()->where(['status'=>'failed'])->count();
        //self::debug($product);
        return $this->render('non-assigned-warehouse-products', [
                'products' => $product['data'],
                'warehouses'=>$warehouses,
                'failed_sync_count'=>$failed_sync,
                'total_records' => $product['total_records'],
            ]
        );
    }

    /**********export not assigned products to warehouse***********/
    public function actionExportNotAssignedWarehouseProducts()
    {
        $_GET['record_per_page']=30000; // to fetch all not by particular page
        $data = ProductsUtil::productsNotAssignedToWarehouse();
        return  ProductsUtil::export_not_assigned_Warehouse_products($data);
    }

    /*************assign third party warehouse to product **********/
    public function actionAssignWarehouseToProduct()
    {
        if(isset($_POST['sku']) && isset($_POST['warehouse_id']) && $_POST['sku'] && $_POST['warehouse_id']):
            $insert=new EzcomToWarehouseProductSync();
            $insert->sku = $_POST['sku'];
            $insert->warehouse_id = $_POST['warehouse_id'];
            $insert->status = "pending";
            $insert->created_at=date('Y-m-d H:i:s');
            $insert->save();
            return $this->asJson(['status'=>'success','msg'=>'updated']);
        endif;
        return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }

    /***************get duplicate products / same sku assigned to multiple warehouses******************/
    public function actionDuplicateAssignedWarehouseProducts()
    {
        $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        $products=ProductsUtil::duplicateAssignedWarehouseProducts();
        $failed_sync=EzcomToWarehouseProductSync::find()->where(['status'=>'failed'])->count();
       // self::debug($products);
        return $this->render('duplicate-warehouse-assigned-products', [
            'products' => $products['data'],
            'warehouses'=>$warehouses,
            'failed_sync_count'=>$failed_sync,
            'total_records' => $products['total_records'],
        ]);
    }

    /**********export duplicate assigned warehouses/same sku assigned to multiple warehouses***********/
    public function actionExportDuplicateAssignedWarehouseProducts()
    {
        $_GET['record_per_page']=30000; // to fetch all not by particular page
        $data = ProductsUtil::duplicateAssignedWarehouseProducts();
        return  ProductsUtil::exportDuplicateAssignedWarehouseProducts($data);
    }

    /******************delete duplicate warehouse assigned if status still pending*************/
    public function actionDelDuplicateAssignWarehouse()
    {
        if(isset($_POST['pk_id'])  && $_POST['pk_id'])
        {
            $query = Yii::$app->db->createCommand("DELETE FROM  ezcom_to_warehouse_product_sync  WHERE id='".$_POST['pk_id']."'")->execute();
            return $this->asJson(['status'=>'success','msg'=>'updated']);
        } else{
            return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
        }
    }

    /************product sync to third party warehouse csv import*************/
    public function actionCsvUploadProductWarehouseSync()
    {
        $error_list=array();
        $inserted_count = 0;
        ////if file uploaded
        if (isset( $_FILES['csv']) && $_FILES['csv']['size'] > 0  )
        {
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);
            if ($ext!='csv'){
                return $this->render('csv-upload-products-warehouse',['status'=>'failure',
                        'msg'=>'only csv files allowed',
                        'not_inserted_list'=>$error_list,
                        'inserted_count'=>$inserted_count,
                    ]
                );
            }

            // csv to json
            $json = $this->CsvToJson($_FILES['csv']['tmp_name']);

            $json = json_decode($json);
            //self::debug($json);
            if(empty($json)){
                return $this->render('csv-upload-products-warehouse',['status'=>'failure',
                        'msg'=>'check file format OR content',
                        'not_inserted_list'=>$error_list,
                        'inserted_count'=>$inserted_count,
                    ]
                );
            }
            foreach ( $json as $value ){
                $warehouse_id=trim($value->warehouse_id,"'");   // remove commas on start and end if have
                $sku=trim($value->sku,"'");   // remove commas on start and end if have

                $product_sync_exist = EzcomToWarehouseProductSync::find()->where(['sku'=>$sku, 'warehouse_id' =>$warehouse_id])->asArray()->exists();
                //  echo "<pre>";print_r($product_sync_exist);exit;
                if(!$product_sync_exist) {

                    $insert=new EzcomToWarehouseProductSync();
                    $insert->sku = $sku;
                    $insert->warehouse_id = $warehouse_id;
                    $insert->status = "pending";
                    $insert->created_at = date('Y-m-d H:i:s');
                    // echo "<pre>";print_r($insert);exit;
                    if (!$insert->save())
                        $error_list[] = $sku;
                    else
                        $inserted_count++;
                }else{
                    $error_list[] = $sku;
                }


            }

            return $this->render('csv-upload-products-warehouse',['status'=>'success',
                    'msg'=>'CSV Uploaded',
                    'not_inserted_list'=>$error_list,
                    'inserted_count'=>$inserted_count,
                ]
            );

        }else{
            return $this->render('csv-upload-products-warehouse');
        }
        ///


    }

    /*******************************/

    //update category of product
    public function actionUpdateProductSyncWarehouse()
    {
        if(isset($_POST['pk_id']) && isset($_POST['new_warehouse_id']) && $_POST['pk_id'] && $_POST['new_warehouse_id'])
        {
            if($_POST['new_warehouse_id']=="-1") // this mean unassign the product from warehouse
            {
                $if_status_pending=EzcomToWarehouseProductSync::findOne(['id'=>$_POST['pk_id'],'status'=>'pending']);
                if($if_status_pending)
                {
                    EzcomToWarehouseProductSync::deleteAll(['id'=>$_POST['pk_id']]);
                    return $this->asJson(['status'=>'success','msg'=>'updated']);
                }

            } else{  // update warehouse
                $query = Yii::$app->db->createCommand("UPDATE ezcom_to_warehouse_product_sync SET warehouse_id=".$_POST['new_warehouse_id']." WHERE id='".$_POST['pk_id']."'")->execute();
                return $this->asJson(['status'=>'success','msg'=>'updated']);
            }


        }
        else{

            $insert=new EzcomToWarehouseProductSync();
            $insert->sku = $_POST['sku'];
            $insert->warehouse_id = $_POST['warehouse_id'];
            $insert->status = "pending";
            $insert->created_at = date('Y-m-d H:i:s');
            $insert->save();
            return $this->asJson(['status'=>'success','msg'=>'updated']);
        }
        return $this->asJson(['status'=>'failure','msg'=>'failed to update']);
    }


    public function actionProductMagentoAttributeLists()
    {
        $products=ProductsUtil::getProductMagentoAttributeLists();
      //  echo "<pre>";print_r($products);exit;
        return $this->render('magento-attribute-list',['products'=>$products['data'],'total_records'=>$products['total_records']]);
    }


}