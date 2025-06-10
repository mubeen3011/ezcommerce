<?php

namespace backend\controllers;

use backend\queues\SyncProducts;
use backend\util\Amazon360Util;
use backend\util\AmazonUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\PrestashopUtil;
use backend\util\Product360Util;
use common\models\Category;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\Product360History;
use common\models\Product360MarketplaceCategories;
use common\models\Product360Status;
use common\models\ProductDetails;
use common\models\Products;
use common\models\Products360Fields;
use common\models\Product360VariationShopee;
use common\models\ProductStocks;
use common\models\Subsidy;
use Mpdf\Tag\P;
use PHPUnit\Framework\Constraint\IsFalse;
use yii\helpers\Url;
use yii;
use yii\filters\AccessControl;
class ProductSyncController extends MainController
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

    public $response="";
    private $adding_master_content=0; // if master content creation
    public function beforeAction($action)
    {
        if ($action == 'upload') {
            $this->enableCsrfValidation = false;
        }
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
    public function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    /***
     * convert product to 360 which has to be edit and not addded from 360
     */
    private function convert_product_to_360($product_id=null)
    {
       // $product_id='5923';
        $prepare=['uqid'=>time()];
        if($product_id){
            $check_parent=Products::findone(['id'=>$product_id]);
            if($check_parent->parent_sku_id)
                $parent=Products::findone(['id'=>$check_parent->parent_sku_id]);
            else
                $parent=$check_parent;

            if($parent->dimensions)
            {
                $dimensions=json_decode($parent->dimensions);
            }

            $prepare['p360']['sys_category']=$parent->sub_category;
            $prepare['p360']['common_attributes']=['product_name'=>$parent->name,
                                                    'product_sku'=>$parent->sku,
                                                    'ean'=>$parent->ean,
                                                    'product_short_description'=>'',
                                                    'product_price'=>$parent->rccp,
                                                    'product_cprice'=>$parent->cost,

                                        ];
            if(isset($dimensions->weight))
            {
                $prepare['p360']['common_attributes']['package_weight']=$dimensions->weight;
                $prepare['p360']['common_attributes']['package_height']=$dimensions->height;
                $prepare['p360']['common_attributes']['package_width']=$dimensions->width;
                $prepare['p360']['common_attributes']['package_length']=$dimensions->length;
            }


            /**
             * check variation
             */
            $childs=Products::find()->where(['parent_sku_id'=>$parent->id])->asArray()->all();
            if($childs){
                $prepare['p360']['variationtheme']='ColorSize';
                foreach ($childs as $child){
                    $prepare['p360']['variations'][]=['Color'=>'','Size'=>'',
                                                'price'=>$child['cost'],
                                                'stock'=>'',
                                                'sku'=>$child['sku'],
                                                'product-id'=>$child['ean'],
                                                'product-id-type'=>'EAN',
                                                'images'=>[],
                                            ];
                }

            }
            $prepare['Save']='Draft';
            $master_channel=Channels::find(['is_active'=>1])->one();
            $prepare['p360']['shop'][]=$master_channel->prefix;
            $this->adding_master_content=1;
            $p3d = new Products360Fields();
            $p3d->images = "xx";
            $p3d->product_id = $parent->id;
            $p3d->status = 'draft';
            $p3d->name = $parent->name;
            $p3d->sku = $parent->sku;
            $p3d->category = $parent->sub_category;
            $p3d->product_info = json_encode($prepare);
            $p3d->is_master_content =$this->adding_master_content;
            if ($p3d->save()) {
                $shop = Channels::find()->where(['prefix' => $master_channel->prefix, 'is_active' => '1'])->one();
                $p3s = new Product360Status();
                $p3s->images = "";
                $p3s->product_360_fieldS_id = $p3d->id;
                $p3s->shop_id = $shop->id;
                $p3s->api_request = json_encode($prepare);
                $p3s->status = 'draft';
                $p3s->save();
                return ['field_id'=>$p3d->id,'status_id'=>$p3s->id];
            }

            return;
        }
    }

    public function actionManage()
    {
     // echo "<pre>";
     //print_r($_POST); die();
        /**
         * if product editing which is not made from product 360
         * */
        $roleId = Yii::$app->user->identity->role_id; // who is updating or creating

        if(isset($_GET['edit_non_360']) && isset($_GET['product_id']))
        {
            $check_parent=Products::findone(['id'=>$_GET['product_id']]);
            if($check_parent->parent_sku_id)  // if variation or child sent then fetch parent
                $_GET['product_id']=$check_parent->parent_sku_id;

            $field=Products360Fields::findOne(['product_id'=>$_GET['product_id']]);
            if($field){
                $_GET['id']=$field->id;
                $table_status_id=Product360Status::findOne(['product_360_fieldS_id'=>$field->id]);
                $_GET['shop']=$table_status_id->id;
            }else{ // add this product in fields and status table
              $added=$this->convert_product_to_360($_GET['product_id']);
              $_GET['id']=$added['field_id'];
              $_GET['shop']=$added['status_id'];
            }

          //  print_r($_GET); die();
        }
        $fields = $images = [];
        $isDisable = $isUpdate = false; //uci
        $shops = Channels::find()->select(['id', 'name', 'prefix', 'marketplace'])->where(['is_active'=>'1','enable_product360' => 1])->orderBy('marketplace ASC')->asArray()->all();
        ///if data is posted
        if (\Yii::$app->request->post())
        {

            if(!isset($_POST['p360']['shop'])){ //if no shop selected then mean master content creation in progress
                $master_channel=Channels::find(['is_active'=>1])->one();
                $_POST['p360']['shop'][]=$master_channel->prefix;
                $this->adding_master_content=1;
                unset($_POST['p360']['amazon-attributes']); // remove it when generic implementation
                //$this->asJson(['status'=>'failure','msg'=>'select shop']);
            }


           /* $Check_Sku_Duplicate = Products::findone(['sku'=>$_POST['p360']['common_attributes']['product_sku']]);
            if (!$Check_Sku_Duplicate)
                $System_Sku_id = $this->actionAddSkuInSystem();
            else
                $System_Sku_id = $Check_Sku_Duplicate['id'];*/
            $System_Sku_id = $this->actionAddSkuInSystem();

            $list = [];
            // save images
           if (isset($_FILES)) {
                $list = Product360Util::getImages($_POST['uqid']);
                if($list)
                 $list = $_POST['uqid'] . '/mainimage/' . implode(',' . $_POST['uqid'] . '/mainimage/', $list);
            }

            $variationData = [];
            if ( isset($_POST['p360']['variations']) ){

                foreach ($_POST['p360']['variations'] as $key=>$val){
                    $images = Product360Util::getVariationImages($_POST['uqid'],'variation-'.$key);
                    $_POST['p360']['variations'][$key]['images'] = $images;
                }


            }

           // if(empty($list))
              // return $this->asJson(['status'=>'failure','msg'=>'images required with valid sizes']);



            $postVars = \Yii::$app->request->post('p360');
            $saveBtn = \Yii::$app->request->post('save');
            //$saveBtn ='Draft';
            if(/*$saveBtn != 'Update' &&*/ !isset($_POST['status_pk_id'])){
                $check_duplicate=Products360Fields::findOne(['sku'=>$postVars['common_attributes']['product_sku']]);
                if($check_duplicate)
                    return $this->asJson(['status'=>'failure','msg'=>'already SKU exists']);
            }

            /// rem after generic
             $current_status=(ucwords($saveBtn) != 'Draft') ? 'Pending' : $saveBtn;
            if($saveBtn == 'Update Draft')
            {
                $saveBtn="Update";
                $current_status="Draft";
            }

            /// if product update then make its history for revesion purpose
                if (isset($_POST['pid']) && $_POST['pid'] != '')
                    self::actionMakeProductHistory($_POST['pid']);

            ///

            ///
            if (isset($_POST['pid']) && $_POST['pid'] != '')
                 $p3d = Products360Fields::findOne($_POST['pid']);
            else
                $p3d = new Products360Fields();
            $p3d->images = $list;
            $p3d->product_id = $System_Sku_id;
            $p3d->status = $current_status;
            $p3d->name = trim($postVars['common_attributes']['product_name']);
            $p3d->sku = trim($postVars['common_attributes']['product_sku']);
            $p3d->category = trim($postVars['sys_category']);
            $p3d->product_info = json_encode($_POST);
            $p3d->is_master_content =$this->adding_master_content;
            if($roleId!=1 ) { //if not admin then admin approval set to pending // after admin approval product will update
                $p3d->admin_status=$current_status=="Draft" ? "draft":"pending";
            }
            if ($p3d->save())
            {
                // push to queue as per shop selected
                if (isset($_POST['p360']['shop']) && /*$saveBtn != 'Update'*/ !isset($_POST['status_pk_id']))
                {
                    foreach ($_POST['p360']['shop'] as $prefix)
                    {
                        $shop = Channels::find()->where(['prefix' => $prefix,'is_active'=>'1'])->one();
                        $p3s = new Product360Status();
                        $p3s->images = $list;
                        $p3s->product_360_fieldS_id = $p3d->id;
                        $p3s->shop_id = $shop->id;
                        $p3s->api_request = json_encode($_POST);
                        if(strtolower($shop->marketplace)=='amazon') // amazon require 4 5 steps to complete update
                        {
                            $steps_amazon=Amazon360Util::step_to_follow_template(isset($_POST['p360']['variations']) ? 1:0);
                            $p3s->steps_to_follow=json_encode($steps_amazon);
                        }
                        if($saveBtn == 'Draft'){
                            $p3s->status= 'Draft';
                          //  $this->response=['status'=>'success','msg'=>'Operation successfull'];
                        }
                        $p3s->save();
                        if($saveBtn != 'Draft' &&  $roleId==1 ){
                            /*\Yii::$app->queue->push(new SyncProducts([
                                'productId' => $p3d->id,
                                'statusId' => $p3s->id
                            ]));*/
                       /// $this->response=   Amazon360Util::ceate_product_process($p3d->id,$p3s->id,false);

                        }

                    }
                    $this->response=['status'=>'success','msg'=>'Operation successfull'];
                }
                else
                    {
                    //  echo "<pre>";
                    //    print_r($_POST); die();
                    $p3s = Product360Status::findOne($_POST['status_pk_id']);
                    //generic remove
                        if (isset($_POST['p360']['shop']) && /*$saveBtn == 'Update'*/ isset($_POST['status_pk_id'])) {
                            foreach ($_POST['p360']['shop'] as $prefix)
                                $shop = Channels::findone(['prefix' => $prefix, 'is_active' => '1']);
                        }
                        //
                       // print_r($_POST['p360']['shop']); die();
                    $p3s->status = $current_status;
                    $p3s->images = $list;
                    if(isset($shop->id)) {
                        $p3s->shop_id = $shop->id; // generic remove
                    }
                    $p3s->api_request = json_encode($_POST);
                    if($p3s->shop->marketplace == 'amazon') // amazon require 4 5 steps to complete update
                    {
                        $steps_amazon=Amazon360Util::step_to_follow_template(isset($_POST['p360']['variations']) ? 1:0);
                        $p3s->steps_to_follow=json_encode($steps_amazon);
                    }
                    $p3s->save();

                   /* if($p3s->item_id != NULL){
                        \Yii::$app->queue->push(new SyncProducts([
                            'productId' => $p3d->id,
                            'statusId' => $_POST['status_pk_id'],
                            'isUpdate' => true
                        ]));
                    }
                    else{
                        \Yii::$app->queue->push(new SyncProducts([
                            'productId' => $p3d->id,
                            'statusId' => $_POST['status_pk_id']
                        ]));
                    }*/
                   if($roleId==1) { // if admin then update on channels
                       /// $this->response=Amazon360Util::ceate_product_process($p3d->id,$p3s->id,true);
                        }
                        $this->response=['status'=>'success','msg'=>'Operation successfull'];
                }
            } else
                {
                    $error=$p3d->getErrors();
                    $this->response=['status'=>'failure','msg'=>$error];
                }
            ///////////////


            /// ////////////
            $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
            $p3d->status = $selectedstatus;
            $p3d->save();

            return $this->asJson($this->response);
            //$this->redirect('all?page=1');
        }
         // /////////
        // check if draft exists
        $id = \Yii::$app->request->get('id');
        $item = \Yii::$app->request->get('item');
        if (isset($id)) {
            $p3d = Products360Fields::findOne($id);
            $p3s = Product360Status::findOne($_GET['shop']);
            $ch = Channels::findOne(['id' => $p3s->shop_id,'is_active'=>'1','id']);
          // echo "<pre>";
          //  print_r($ch);die();
            if ($p3s->status == 'Success' || $p3s->status == 'Activated' || $p3s->status == 'DeActivated') {
                $isDisable = $isUpdate = true;
                $marketplace = $p3s->shop->marketplace;
                //echo $marketplace;die;
                if ($marketplace == 'amazon') {
                    $pinfo = json_decode($p3s->api_request, true);
                    $images = explode(',', $p3s->images);
                    if ($item == null){
                        $fields = json_decode($p3s->api_request, true);
                        $fields['p360']['shop'] =  [$ch->prefix];
                        $fields['p360']['admin_status'] =  $p3d->admin_status;
                        $fields['p360']['current_status'] =  $p3d->status;
                    }
                }
            }
            elseif ($p3s->status == 'Deleted') {
                yii::$app->session->setFlash('failure','product deleted');
                $this->redirect('all?page=1');
            }
            else {
                $isDisable = $isUpdate = true;
                //$isDisable = false;
                $fields = json_decode($p3s->api_request, true);
                $fields['p360']['shop'] =  [$ch->prefix];
                $fields['p360']['admin_status'] =  $p3d->admin_status;
                $fields['p360']['current_status'] =  $p3d->status;
                $images = explode(',', $p3s->images);
            }

        }
       // $this->debug($fields);
        ///
        $categories = Category::find()->where(['is_active' => 1])->andWhere(['parent_id' => NULL])->asArray()->all();
        return $this->render('manage',['shops' => $shops,'fields' => $fields, 'images' => $images, 'categories'=>$categories,'isDisable' => $isDisable, 'isUpdate' => $isUpdate,'status'=>'']);
    }

    /***
     * admin approval action
     */
    public function actionApproveProduct()
    {

        $field_id=Yii::$app->request->post('field_id');
        if($field_id){
            $p3d = Products360Fields::findOne($field_id);
            $p3d->admin_status="approved";
            if($p3d->save()){
                yii::$app->session->setFlash('success','Updated');
                return   $this->asJson(['status'=>'success','msg'=>'updated']);
            }
        }
        return   $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }

    public function actionRejectProductApproval()
    {
        $field_id=Yii::$app->request->post('field_id');
        $status=Yii::$app->request->post('status');
        if($field_id && $status)
        {
            if($status=="not_restore") {
                $p3d = Products360Fields::findOne($field_id);
                $p3d->admin_status = "reject";
                if ($p3d->save()) {
                    yii::$app->session->setFlash('success', 'Updated');
                    return $this->asJson(['status' => 'success', 'msg' => 'updated']);
                }
            } elseif($status=="restore"){
                $response=self::recover_previuos_360_product_version($field_id);
                return $this->asJson($response);
            }
        }
        return   $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }

    public function actionMakeProductHistory($field_id)
    {
            //$field_id="89";
            $fields=Products360Fields::find()->where(['id'=>$field_id])->asArray()->one();
            $status=Product360Status::find()->where(['product_360_fieldS_id'=>$field_id])->asArray()->all();
            if($fields['admin_status']=="approved" && $fields && $status) {
                Product360History::deleteAll(['field_id_360' => $field_id]);
                $new_history=new Product360History();
                $new_history->fields_360=json_encode($fields);
                $new_history->status_360=json_encode($status);
                $new_history->field_id_360=$field_id;
                $new_history->save(false);
            }
            return;
    }

    /***
     * it will recover last updated from history table to current 360 tables
     */
    private function recover_previuos_360_product_version($field_id)
    {
        $history=Product360History::find()->where(['field_id_360'=>$field_id])->asArray()->one();
        $old_fields="";
        $old_statuses=[];
        if($history)
        {
           $fields=json_decode($history['fields_360']);
           $statuses=json_decode($history['status_360']);
                //echo "<pre>";
                //print_r();
           $p3f=Products360Fields::findOne($field_id);
            $old_fields=$p3f; // have to put this in history and below it will replace from history to live
            $old_statuses=Product360Status::find()->where(['product_360_fieldS_id'=>$field_id])->asArray()->all();
           $p3f->product_info=$fields->product_info;
           $p3f->created_by=$fields->created_by;
           $p3f->updated_by=$fields->updated_by;
           $p3f->images=$fields->images;
           $p3f->name=$fields->name;
           $p3f->sku=$fields->sku;
           $p3f->category=$fields->category;
           $p3f->is_master_content=$fields->is_master_content;
           $p3f->admin_status=$fields->admin_status;
           if($p3f->save())
           {
               foreach($statuses as $status)
               {
                    $p3s=Product360Status::findOne($status->id);
                    $p3s->status=$status->status;
                    $p3s->fail_reason=$status->fail_reason;
                    $p3s->shop_id=$status->shop_id;
                    $p3s->show=$status->show;
                    $p3s->api_response=$status->api_response;
                    $p3s->api_request=$status->api_request;
                    $p3s->images=$status->images;
                    $p3s->steps_to_follow=$status->steps_to_follow;
                    $p3s->transaction_response=$status->transaction_response;
                    $p3s->save(false);
               }

               /////save the overrided data to history
               if($old_fields && $old_statuses) {
                   Product360History::deleteAll(['field_id_360' => $field_id]);
                   $new_history=new Product360History();
                   $new_history->fields_360=json_encode($fields);
                   $new_history->status_360=json_encode($status);
                   $new_history->field_id_360=$field_id;
                   $new_history->save(false);
               }
               ///
               return ['status'=>'success','msg'=>'Updated'];
           }

        }
        return ['status'=>'failure','msg'=>'No previuos history found'];
    }

    public function actionAll()
    {
        $pfields = new Products360Fields();
        $data = $pfields->GetResults();
        return $this->render('all', [
            'data' => $data['products'],
            'total_records' => $data['TotalRecords']
        ]);
    }


    public function actionGetProductShops()
    {
        $params = $_POST;
        $pid = $params['pid'];
        $p3f = Products360Fields::findOne($pid);
        $pname = $p3f->name;
        $pstatus = $p3f->status;
        $result = Product360Status::getProductShops($pid);
        $copyShops = Product360Status::getShopsToCopyProduct($pid);

        //var_dump($copyShops); exit();
        $list = "<table class='table'>";
        $list .= "<thead><tr><td>Shop</td><td>Status</td><td>Item Id</td><td>Edit</td></tr></thead>";
        $head = '<h5><strong>' . $pname . '</strong> Synced in following marketplace shops:</h5>';
        foreach ($result as $re) {

            $list .= "<tr><td>" . $re['prefix'] . "</td>";
            $list .= '<td><span class="badge badge-pill ' . \backend\util\HelpUtil::getBadgeClass($re['status']) . '">
                                            ' . $re['status'] . '</span>';
            if (trim($re['status']) == 'Fail' || trim($re['status']) == 'Deleted') {
                $list .= "<br/><small>Check Error Log for full detail  </small>";
                $list .= "<br/><textarea cols='30' rows='2'>Error message is too long check DB log, Fail to update feed  </textarea>";
            }
            if($re['item_id']!= NULL)
                $list .= "</td><td>" . $re['item_id'] . "</td>";
            else
                $list .= "</td><td> not generated yet </td>";
            $empty = '';

            if ($re['status'] == 'Pending'){
                $list .= "<td colspan='2'>Cannot update while product is still pending sync.</td>";
                $list .= "<td><a href='javascript:;' onclick='javascript:deleteproduct(" . $re['id'] . ")' style='cursor: pointer'><span class=\"fa fa-trash\"></span></a></td>";
            }
            if ($re['status'] == 'Activating' || $re['status'] == 'DeActivating')
                $list .= "<td colspan='3'>Not allowed yet.</td>";
            else if ($re['status'] == 'Deleted')
                $list .= "<td colspan='3'>Cannot update/delete as product deleted from this shop.</td>";

            else if($re['status'] == 'Fail' || $re['status'] == 'Success' || $re['status'] == 'Activated' || $re['status'] == 'DeActivated' || $re['status'] == 'Draft'){
                if($re['item_id']!= NULL){

                    $list .= "<td><a href='" . Url::to('/product-sync/manage?id=' . $pid . '&shop=' . $re['id'] . '&item=' . $re['item_id'] .'&v='.time()) . "'><span class=\"fa fa-edit\"></span></a></td>";

                }
                else{
                    $list .= "<td><a href='" . Url::to('/product-sync/manage?id=' . $pid . '&shop=' . $re['id'] . '&item=' . $empty) .'&v='.time(). "'><span class=\"fa fa-edit\"></span></a></td>";

                }
            }

        }
        $list .= "</table>";
        echo json_encode(['msg' => $head . $list]);
    }

    private static function get_failure_reason($reason)
    {
        $retutn="";
            $report=json_decode($reason);
            if($report && is_array($report))
            {
                foreach($report as $key=>$val)
                {
                    $retutn .=isset($val['ResultDescription']) ? $val['ResultDescription'] . " -- ":"";
                }
            }
            return $retutn;

    }

    public function actionUploadVariationImages()
    {
        //print_r($_POST); die();
        if (isset($_FILES)) {
            foreach ( $_FILES as $key=>$value )
                $key=$key;

            Product360Util::saveVariationImages($_FILES,$key);
        }
    }

    /**
     * add product to db
     */
    private function actionAddSkuInSystem(){
        $cost_price= $_POST['p360']['common_attributes']['product_cprice'];
        $rcp= $_POST['p360']['common_attributes']['product_price'];
        $_GET['cost_price']=number_format($cost_price ? $cost_price:0,2,'.', '');
        $_GET['rcp']=number_format($rcp ? $rcp:0,2,'.', '');
        $_GET['margin']=5.00;
        $_GET['subsidy']=0.00;


        $Parent_Sku_Id = $this->AddSkuToProductDetails($_POST['p360']['common_attributes']['product_sku'],$_GET['cost_price'],$_GET['rcp'],$_GET['subsidy']);

        if ( isset($_POST['p360']['variations']) ){

            foreach ( $_POST['p360']['variations'] as $key=>$value ){
                /*if($key=='variationtheme')
                    continue;*/
                $this->AddSkuToProductDetails($value['sku'],$value['price'],$_GET['rcp'],$_GET['subsidy'],$Parent_Sku_Id);
            }
        }
        return $Parent_Sku_Id;
    }

    private function AddSkuToProductDetails($Sku,$cprice,$rcp,$subsidy,$Parent_sku_id=0)
    {
        $dimensions="";
        if($_POST['p360']['common_attributes']['package_weight'] || $_POST['p360']['common_attributes']['package_width'] )
        {
            $dimensions=['width'=>$_POST['p360']['common_attributes']['package_width'] ,
                          'height'=>$_POST['p360']['common_attributes']['package_height'] ? $_POST['p360']['common_attributes']['package_height']:0 ,
                          'length'=>$_POST['p360']['common_attributes']['package_length'],
                          'weight'=>$_POST['p360']['common_attributes']['package_weight'] ? $_POST['p360']['common_attributes']['package_weight'] :0,
            ];
        }
        $dimensions=$dimensions ? json_encode($dimensions):$dimensions;
        $ps = Products::findone(['sku'=>$Sku]);
        if($ps){
            $ps->sku = $Sku;
            $ps->cost = (double)($cprice);
            $ps->parent_sku_id = $Parent_sku_id;
            $ps->name = $_POST['p360']['common_attributes']['product_name'];
            $ps->rccp = $rcp;
            $ps->cost = $cprice;
            $ps->updated_at=time();
            if($dimensions)
               $ps->dimensions=$dimensions;

            $ps->sub_category = $_POST['p360']['sys_category'] ;
            $ps->created_by=\Yii::$app->user->identity->getId();
            $ps->save(false);
        }
        else {
        $ps =  new Products();
        $ps->sku = $Sku;
        $ps->cost = (double)($cprice);
        $ps->parent_sku_id = $Parent_sku_id;
        $ps->name = $_POST['p360']['common_attributes']['product_name'];
        $ps->rccp = $rcp;
        $ps->cost = $cprice;
         if($dimensions)
             $ps->dimensions=$dimensions;

        $ps->extra_cost = 0.00;
        $ps->created_at=time();
        $ps->updated_at=time();
        $ps->is_foc=0;
        $ps->is_orderable=1;
        $ps->sub_category = $_POST['p360']['sys_category'] ;
        $ps->created_by=\Yii::$app->user->identity->getId();
        $ps->save(false);}
        $Sku_Id=$ps->id;

        ////add to channel products as well if shop slected
        if(!$this->adding_master_content) { // if master content creation and no shop selected
            $add=ChannelsProducts::findOne(['channel_sku'=>$Sku,'channel_id'=>19]);
            if($add){
                $add->sku = $Sku;
                //$add->variation_id = (isset($_POST['product_id']) && $record['variation_id']) ? $record['variation_id'] :NULL ;
                $add->ean = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $_POST['product_id'] : 0;
                $add->channel_sku = $Sku;
                $add->channel_id = 19; // for now is amazon
                $add->product_id = $Sku_Id;
                $add->price = $cprice;
                $add->last_update = date('Y-m-d H:i:s');
                $add->product_name = $_POST['p360']['common_attributes']['product_name'];
                $add->save(false);
            }
            else {
                $add = new ChannelsProducts();
                $add->sku = $Sku;
                //$add->variation_id = (isset($_POST['product_id']) && $record['variation_id']) ? $record['variation_id'] :NULL ;
                $add->ean = (isset($_POST['product_id']) && !empty($_POST['product_id'])) ? $_POST['product_id'] : 0;
                $add->channel_sku = $Sku;
                $add->channel_id = 19; // for now is amazon
                $add->product_id = $Sku_Id;
                $add->price = $cprice;
                $add->stock_qty = 0;
                $add->last_update = date('Y-m-d H:i:s');
                $add->product_name = $_POST['p360']['common_attributes']['product_name'];
                $add->is_live = 0;
                $add->fulfilled_by = null; // mostly for amazon
                $add->save(false);
            }
        }
        /*if($add->save(false))
            return $add;
        else
            return $add;*/
        ///
        return $Sku_Id;
    }

    public function actionTest()
    {
        $json= '{"product_add":{"status":"pending","feed_id":"","response":"","error":"","require":1},"product_variation_made":{"status":"pending","feed_id":"","response":"","error":"","require":1},"product_images":{"status":"pending","feed_id":"","response":"","error":"","require":1},"product_price":{"status":"pending","feed_id":"","response":"","error":"","require":1},"product_stock":{"status":"pending","feed_id":"","response":"","error":"","require":1},"product_variation_mapped":{"status":"pending","feed_id":"","response":"","error":"","require":1}}';
        $response=[
                    'product_add'=>['feed_id'=>'bilal','status'=>'_submited_'],
                ];
        $jhing=json_decode($json);
        foreach($jhing as $key=>$value)
        {
            if(!isset($response[$key]))
            continue;
                $value->feed_id=isset($response[$key]['feed_id']) ? $response[$key]['feed_id']:$value->feed_id;
        }
        echo json_encode($jhing);
    }
    public function actionTest2()
    {
        Amazon360Util::ceate_product_process(6,4);
    }
}
