<?php

namespace backend\controllers;

use backend\queues\SyncProducts;
use backend\util\HelpUtil;
use backend\util\PrestashopUtil;
use backend\util\Product360Util;
use common\models\Channels;
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
use yii\filters\AccessControl;

class Product360Controller extends \yii\web\Controller
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
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }
    public function beforeAction($action)
    {
        if ($action == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionManage()
    {
       // print_r($_POST); die();
        $fields = $images = [];
        $isDisable = $isUpdate = false;
        $shops = Channels::find()->select(['id', 'name', 'prefix', 'marketplace'])->where(['is_active'=>'1','enable_product360' => 1])->orderBy('marketplace ASC')->asArray()->all();
        // adding new product or draft
        if (\Yii::$app->request->post()) {
            $Check_Sku_Duplicate = Products::find()->where(['sku'=>$_POST['p360']['common_attributes']['product_sku']])->asArray()->all();
            if (empty($Check_Sku_Duplicate)){

                $System_Sku_id = $this->actionAddSkuInSystem();

            }else{

                $System_Sku_id = $Check_Sku_Duplicate[0]['id'];

            }
            //$this->debug($_POST['p360']);
            //echo $System_Sku_id;die;

            /*echo '<pre>';
            print_r(\Yii::$app->request->post());
            die;*/
            $list = [];
            // save images
            if (isset($_FILES)) {
                $list = Product360Util::getImages($_POST['uqid']);
                $list = $_POST['uqid'] . '/mainimage/' . implode(',' . $_POST['uqid'] . '/mainimage/', $list);
                //$this->debug($list);
            }
            $variationData = [];
            if ( isset($_POST['p360']['variations']) ){

                foreach ($_POST['p360']['variations'] as $key=>$val){
                    $images = Product360Util::getVariationImages($_POST['uqid'],'variation-'.$key);
                    $_POST['p360']['variations'][$key]['images'] = $images;
                }

                //$this->debug($_POST);

            }
            //$this->debug($_POST['p360']);
            $postVars = \Yii::$app->request->post('p360');
            $saveBtn = \Yii::$app->request->post('save');

            // set the absolute url for ckeditor images.

            $_POST['p360']['common_attributes']['product_short_description'] = str_replace('src="/kcfinder/upload/images','src="'.$_SERVER['HTTP_ORIGIN'] .'/kcfinder/upload/images',$_POST['p360']['common_attributes']['product_short_description']);
            if (isset($_POST['p360']['lzd_attributes']['normal']['description'])){
                $_POST['p360']['lzd_attributes']['normal']['description'] = str_replace('src="/kcfinder/upload/images','src="'.$_SERVER['HTTP_ORIGIN'] .'/kcfinder/upload/images',$_POST['p360']['lzd_attributes']['normal']['description']);
            }
            // set the absolute url for ckeditor images.
            
            //check if product draft or new
            if ($_POST['pid'] != '')
                $p3d = Products360Fields::findOne($_POST['pid']);
            else
                $p3d = new Products360Fields();
            $p3d->images = $list;
            $p3d->product_id = $System_Sku_id;
            $p3d->status = (ucwords($saveBtn) != 'Draft') ? 'Pending' : $saveBtn;
            $p3d->name = trim($postVars['common_attributes']['product_name']);
            $p3d->sku = trim($postVars['common_attributes']['product_sku']);
            $p3d->category = trim($postVars['sys_category']);
            $p3d->product_info = json_encode($_POST);
            if ($p3d->save()) {
                // push to queue as per shop selected
                if (isset($_POST['p360']['shop']) && $saveBtn != 'Update') {
                    foreach ($_POST['p360']['shop'] as $prefix) {
                        $shop = Channels::find()->where(['prefix' => $prefix,'is_active'=>'1'])->one();
                        $p3s = new Product360Status();
                        $p3s->images = $list;
                        $p3s->product_360_fieldS_id = $p3d->id;
                        $p3s->shop_id = $shop->id;
                        $p3s->api_request = json_encode($_POST);
                        if($saveBtn == 'Draft'){
                            $p3s->status= 'Draft';
                        }
                        $p3s->save();
                    if($saveBtn != 'Draft'){
                        \Yii::$app->queue->push(new SyncProducts([
                            'productId' => $p3d->id,
                            'statusId' => $p3s->id
                        ]));
                    }

                    }
                } else {
                    $p3s = Product360Status::findOne($_GET['shop']);
                    $p3s->status = 'Pending';
                    $p3s->images = $list;
                    $p3s->api_request = json_encode($_POST);
                    $p3s->save();
                    
                    if($p3s->item_id != NULL){
                        \Yii::$app->queue->push(new SyncProducts([
                        'productId' => $p3d->id,
                        'statusId' => $_GET['shop'],
                        'isUpdate' => true
                        ]));
                    }
                    else{
                        \Yii::$app->queue->push(new SyncProducts([
                        'productId' => $p3d->id,
                        'statusId' => $_GET['shop']
                        ]));
                    }
                }
            }
            $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
            $p3d->status = $selectedstatus;
            $p3d->save();

            $this->redirect('all?page=1');
        }
        // check if draft exists
        $id = \Yii::$app->request->get('id');
        $item = \Yii::$app->request->get('item');
        if (isset($id)) {
            $p3d = Products360Fields::findOne($id);
            //$this->debug($p3d);
            // fetch from api
            // if($p3d->status == 'Draft'){
            //     $fields = json_decode($p3d->product_info, true);
            //     $images = explode(',', $p3d->images);
            //     $isUpdate = true;
            // }
            // else{
            $p3s = Product360Status::findOne($_GET['shop']);
            $ch = Channels::findOne(['id' => $p3s->shop_id,'is_active'=>'1']);
            if ($p3s->status == 'Success' || $p3s->status == 'Activated' || $p3s->status == 'DeActivated') {
                $isDisable = $isUpdate = true;
                $marketplace = $p3s->shop->marketplace;
                //echo $marketplace;die;
                if ($marketplace == 'lazada') {
                    $pinfo = json_decode($p3s->api_request, true);
                    $ReSyncProduct = $this->actionProductSyncSc($_GET['item'],$_GET['channel_id']=1);
                    $images = explode(',', $p3s->images);
                    if ($item == null){
                        $fields = json_decode($p3s->api_request, true);
                        $fields['p360']['shop'] =  [$ch->prefix];
                    }
                    else {
                        $fields = Product360Util::getLazadaProductItem($_GET['item'], $p3s->shop_id, $pinfo);
                        if (!$fields) {
                            // product has been deleted
                            $p3s->status = 'Deleted';
                            $p3s->fail_reason = 'Product deleted.';
                            $p3s->save();
                            $this->redirect('all?page=1');
                        }else if(isset($fields['save_response'])){
                            $p3s->api_response = $fields['save_response'];
                            $p3s->save();
                        }
                    }
                }
                if ($marketplace == 'shopee') {
                    $pinfo = json_decode($p3s->api_request, true);
                    //$this->debug($pinfo);
                    $images = explode(',', $p3s->images);
                    //$this->debug($images);
                    //echo $item;die;
                    if ($item == null){
                        $fields = json_decode($p3s->api_request, true);
                        $fields['p360']['shop'] =  [$ch->prefix];
                    }
                    else {

                        $fields = Product360Util::getShopeeProductItem($p3s->item_id, $p3s->shop_id, $pinfo);
                        //$this->debug($fields);
                        //$this->debug($fields);die;
                        if (!$fields) {
                            //product has been deleted
                            $p3s->status = 'Deleted';
                            $p3s->fail_reason = 'Product deleted.';
                            $p3s->save();
                            $this->redirect('all?page=1');
                        }else if(isset($fields['save_response'])){
                            $p3s->api_response = $fields['save_response'];
                            $p3s->save();
                        }

                    }
                }
                if ($marketplace == 'prestashop') {
                    $pinfo = json_decode($p3s->api_request, true);
                    //$this->debug($pinfo);
                    $images = explode(',', $p3s->images);
                    //$this->debug($images);
                    //echo $item;die;
                    if ($item == null){
                        $fields = json_decode($p3s->api_request, true);
                        $fields['p360']['shop'] =  [$ch->prefix];
                    }
                    else {
                        $fields = Product360Util::getPrestaProductItem($p3s->item_id, $p3s->shop_id, $pinfo);
                        //$this->debug($fields);
                        //$this->debug($fields);die;
                        if (!$fields) {
                            //product has been deleted
                            $p3s->status = 'Deleted';
                            $p3s->fail_reason = 'Product deleted.';
                            $p3s->save();
                            $this->redirect('all?page=1');
                        }else if(isset($fields['save_response'])){
                            $p3s->api_response = $fields['save_response'];
                            $p3s->save();
                        }

                    }
                }
            }
            elseif ($p3s->status == 'Deleted') {
                $this->redirect('all?page=1');
            }
            else {
                $isDisable = $isUpdate = true;
                //$isDisable = false;
                $fields = json_decode($p3s->api_request, true);
                $fields['p360']['shop'] =  [$ch->prefix];
                $images = explode(',', $p3s->images);
            }

        }
        //$this->debug($fields);

        return $this->render('manage', ['shops' => $shops, 'fields' => $fields, 'images' => $images, 'isDisable' => $isDisable, 'isUpdate' => $isUpdate,
        'status'=>(isset($p3s->status)) ? $p3s->status : '']);
    }

    public function actionUpload()
    {
        if (isset($_FILES)) {
            Product360Util::saveImages($_FILES);
        }
    }
    public function actionDeleteImg()
    {
        $img = \Yii::$app->request->post('img');
        $id = \Yii::$app->request->post('sid');
        $filename = \Yii::getAlias('@app') . '/web/product_images/' . $img;
        if (file_exists(\Yii::getAlias('@web') . $filename)) {
            //$p3f = Products360Fields::findOne($id);
            $p3s = Product360Status::findOne($id);

            $images = explode(',', $p3s->images);
            $delKey = array_search($img, $images);
            unset($images[$delKey]);
            $p3s->images = implode(',', $images);
            if ($p3s->save()) {
                unlink($filename);
                echo "done";
            }


        }
    }

    public function actionAll()
    {
        $pfields = new Products360Fields();
        $data = $pfields->GetResults();
       // echo "<pre>";
       // print_r($pfields); die();
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
        $list .= "<thead><tr><td>Shop</td><td>Status</td><td>Item Id</td><td>Edit</td><td>Active/Deactive</td><td>Delete</td><td>Copy to Other Shops</td></tr></thead>";
        $head = '<h5><strong>' . $pname . '</strong> Sync in following marketplace shops:</h5>';
        foreach ($result as $re) {

            $list .= "<tr><td>" . $re['prefix'] . "</td>";
            $list .= '<td><span class="badge badge-pill ' . \backend\util\HelpUtil::getBadgeClass($re['status']) . '">
                                            ' . $re['status'] . '</span>';
            if (trim($re['status']) == 'Fail' || trim($re['status']) == 'Deleted') {
                $list .= "<br/><small>" . $re['fail_reason'] . "</small>";
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
                $list .= "<td colspan='3'>Cannot update delete while product is still pending sync.</td>";
            else if ($re['status'] == 'Deleted')
                $list .= "<td colspan='3'>Cannot update/delete as product deleted from this shop.</td>";

            else if($re['status'] == 'Fail' || $re['status'] == 'Success' || $re['status'] == 'Activated' || $re['status'] == 'DeActivated' || $re['status'] == 'Draft'){
                 if($re['item_id']!= NULL){

                    $list .= "<td><a href='" . Url::to('/product-360/manage?id=' . $pid . '&shop=' . $re['id'] . '&item=' . $re['item_id']) . "'><span class=\"fa fa-pencil\"></span></a></td>";

                    if($re['status'] == 'Success' || $re['status'] == 'Activated'){
                        
                        $list .= "<td><a href='javascript:;' onclick='javascript:UpdatProductStatus(" . $re['id'] . ")' style='cursor: pointer'>DeActivate</a></td>";
                    }
                    else if($re['status'] == 'DeActivated'){
                        $list .= "<td><a href='javascript:;' onclick='javascript:UpdatProductStatus(" . $re['id'] . ")' style='cursor: pointer'>Activate</a></td>";
                    }else{
                        $list .= "<td>Cannot change status as product failed</td>";
                    }
                    $list .= "<td><a href='javascript:;' onclick='javascript:deleteproduct(" . $re['id'] . ")' style='cursor: pointer'><span class=\"fa fa-trash\"></span></a></td>";
                }
                else{
                    $list .= "<td><a href='" . Url::to('/product-360/manage?id=' . $pid . '&shop=' . $re['id'] . '&item=' . $empty) . "'><span class=\"fa fa-pencil\"></span></a></td>";
                    $list .= "<td>Cannot change status as product not sync yet</td>";
                    $list .= "<td><a href='javascript:;' onclick='javascript:deleteproduct(" . $re['id'] . ")' style='cursor: pointer'><span class=\"fa fa-trash\"></span></a></td>";   
                    }
            }
            if($copyShops == []){
                 $list .= "<td> not shops to copy </td></tr>";
            }else{

            $list .= "<td><input type='hidden' value='" . $re['id'] . "'/><select name='select[shops][]' class='select2 form-control inputs-margin'
            multiple='multiple'>";
            foreach ($copyShops as $key) {
                if($key['marketplace']==$re['marketplace']){
                   $list .= "<option value=". $key['prefix'] .">". $key['name'] ."</option>";
                }
            }
            $list .= "</select> <a href='javascript:;' onclick='javascript:CopyProduct(this)' style='cursor: pointer'>Copy Product</a></td></tr>";
            }
        }
        $list .= "</table>";
        echo json_encode(['msg' => $head . $list]);
    }

    public function actionGetProductShopsBySku()
    {
        $params = $_POST;
        $pid = $params['pid'];

        $result = Product360Status::getProductShops($pid);
        $checkbox = "<h6>For which Shops are you updating?</h6>";
        foreach ($result as $re) {
            if ($re['status'] == 'Success') {
                $checkbox .= '<div class="row">
                            <div class="col-md-3">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" data-item="' . $re['item_id'] . '"
                                        data-pid="' . $pid . '"
                                        class="custom-control-input"
                                        id="customRadio' . ($re['id'] * 2) . '"
                                          name="selected_shop"
                                          value="' . $re['id'] . '">
                                        <label class="custom-control-label" for="customRadio' . ($re['id'] * 2) . '">
                                               ' . $re['name'] . '
                                        </label>
                                </div>
                            </div>
                        </div>';
            } else {
                $checkbox .= "- " . $re['name'] . " <small>Not sync. yet</small><br>";
            }

        }


        echo json_encode(['msg' => $checkbox]);
    }

    // cron action to fetch category API response and save into database table
    public function actionFetchCategoryResponse()
    {
        $shops = Channels::find()->where(['is_active'=>'1','enable_product360' => 1])->groupBy(['marketplace'])->all();
        $pass = false;
        foreach ($shops as $ch) {
            if ($ch->marketplace == 'lazada') {
                $auth_params = json_decode($ch->auth_params, true);
                $customParams['app_key'] = $ch->api_key;
                $customParams['app_secret'] = $ch->api_user;
                $customParams['access_token'] = $auth_params['access_token'];
                $customParams['method'] = 'GET';
                $customParams['action'] = '/category/tree/get';
                $customParams['params'] = [];
                $response = ApiController::_callLzdRequestMethod($customParams);
                $response = json_decode($response, true);
                if (isset($response['data'])) {
                    $pass = true;
                }
            }
            if ($ch->marketplace == 'prestashop'){
                $response = json_decode(PrestashopUtil::GetCategories());
                if (isset($response)){
                    $pass = true;
                }
            }
            if ($ch->marketplace == 'shopee') {
                $apiKey = $ch->api_key;
                $apiUser = explode('|', $ch->api_user);
                $now = new  \DateTime();
                $postFields = [
                    'partner_id' => (int)$apiUser[0],
                    'shopid' => (int)$apiUser[1],
                    'timestamp' => $now->getTimestamp(),
                ];
                $url = "https://partner.shopeemobile.com/api/v1/item/category/get";
                $postFields = json_encode($postFields);
                $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
                $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];

                $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);

                $response = json_decode($response, true);
                if (isset($response['category'])) {
                    $pass = true;
                }
            }

            if ($ch->marketplace == 'street') {
                $access = ['openapikey: ' . $ch->api_key];
                $apiUrlx = "http://api.11street.my/rest/cateservice/category";
                $responses = ApiController::_ApiCall($apiUrlx, $access);
                $response = ApiController::_refineResponse($responses);
                if (isset($response['category'])) {
                    $pass = true;
                }
            }
            //save into table
            if ($pass) {
                $pcat = Product360MarketplaceCategories::findOne(['marketplace' => $ch->marketplace]);
                if (!$pcat)
                    $pcat = new Product360MarketplaceCategories();
                $pcat->marketplace = $ch->marketplace;
                $pcat->channel_id = $ch->id;
                $pcat->category_api_resp = json_encode($response);
                $pcat->last_update = date('Y-m-d h:i:s');
                $pcat->save();
            }
        }
    }

    //fetch shopee attributes by category id
    public function actionShopeeAttributes()
    {
        ob_start();
        $catId = \Yii::$app->request->get('cat_id');
        $selAttr = json_decode(\Yii::$app->request->get('attr'),true);

        $attributes = Product360Util::GetShopeeAttributes($catId);

        $attrList = [];
        if($selAttr)
        {
            foreach ($selAttr as $key=>$attrs){
                if ( $key === 'shpe_logistics' || $key === 'brand_attr_id' || $key==='variations' ){
                    continue;
                }
                // we are using is_array because when updating a product that is alreayd on store, We get the runtime values and it comes in this format.
                if (is_array($attrs)){
                    $attrList[] = $attrs['attribute_id'].'-'.$attrs['attribute_value'];
                }else{
                    $attrList[] = $attrs;
                }
            }

        }

        echo $this->renderPartial('_render-partials/shopee/attributes',['attributes'=>$attributes,'attrList'=>$attrList]);


    }
         //fetch shopee attributes by category id
    public function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }
    public function actionPrestaAttributes(){
        ob_start();
        $catId = \Yii::$app->request->get('cat_id');
        $p3sId = \Yii::$app->request->get('status_id');

        if ( isset($_GET['channel_id']) && $_GET['channel_id']!='undefined' ){
            $channel_id = \Yii::$app->request->get('channel_id');
        }else{
            $channel_id = HelpUtil::exchange_values('id','shop_id',$p3sId,'product_360_status');
        }

        $attributes = json_decode(PrestashopUtil::Attributes($channel_id));
        $prestaAttrValues = [];
        $pullAttrFromLive = ['Success','Activated','DeActivated'];
        if($p3sId != 'undefined' && $p3sId != '' ){
            $p3s = Product360Status::findOne($p3sId);
            //$channel_id = HelpUtil::exchange_values('id','shop_id',$p3sId,'product_360_status');
            $PrestaJson = json_decode($p3s->api_request,true);

            if(array_key_exists('lzd_attributes',$PrestaJson['p360'])){
                $prestaattrValues = $PrestaJson['p360']['presta_attributes'];
            }
        }


        $key = 0;
        $skipattr = ["id_manufacturer","id_supplier","id_category_default","new","cache_default_attribute","id_default_image","id_default_combination","id_tax_rules_group","position_in_category","manufacturer_name","quantity","type","id_shop_default","reference","supplier_reference","location","width","height","depth","weight","quantity_discount","cache_is_pack","cache_has_attachments","is_virtual","ecotax","minimal_quantity","unity","unit_price_ratio","customizable","text_fields","uploadable_files","redirect_type","id_product_redirected","available_date","show_price","indexed","advanced_stock_management","date_add","date_upd","pack_stock_type","meta_description","meta_keywords","meta_title","link_rewrite","description","available_now","available_later","associations","price","name"];

        if ( isset($_GET['status_id']) && $_GET['status_id']!='undefined' ){
            $s360_data = Product360Status::find()->where(['id'=>$_GET['status_id']])->asArray()->all();
            if ( $s360_data[0]['status']=='Activated' || $s360_data[0]['status']=='Success' ){
                $SavedData = json_decode($s360_data[0]['api_response'],true);
            }else{
                $SavedData = json_decode($s360_data[0]['api_request'],true);
            }
        }

        //$this->debug($SavedData);
        //$this->debug($lastSaveData);
        foreach ($attributes as $attrKey=>$attr) {
            if (in_array($attrKey,$skipattr))
                continue;


            if (true) {

                $attrname = null;
                $selectAttrValue = (isset($SavedData)) ? $SavedData : '';
                if ( isset($attr->required) && $attr->required == true )
                {

                    echo ' <div class="form-group">
                                <label>' . $attr['label'] . '</label><small> * Presta specific</small>
                                <input type="text" class="form-control form-control-line" required
                                name="p360[presta_attributes][normal]['. $attrname .']"
                                value="'.$SavedData['p360']['presta_attributes']['normal'][$attrKey].'">
                                </div>';
                }
                else
                {


                    if ( $attrKey=="description_short" ){
                        echo ' <div class="form-group">
                            <label>' . $attrKey . '</label><small> * Presta specific</small>
                            <textarea id="editor3" class="form-control editor" rows="3"
                            name="p360[presta_attributes][normal]['. $attrKey .']"
                            value=' . $SavedData['p360']['presta_attributes']['normal'][$attrKey] . '></textarea>
                            </div>';
                    }
                    elseif ( $attrKey=="condition" ){
                        $options = ['new'=>'New','used'=>'Used','refurbished'=>'Refurbished'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);

                    }
                    elseif ( $attrKey=="on_sale" ){
                        $options = [0 => 'No', 1 => 'Yes'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);
                    }
                    elseif ($attrKey=="online_only"){
                        $options = [0 => 'No', 1 => 'Yes'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);
                    }
                    elseif ( $attrKey=='available_for_order' ){

                        $options = [1=>'Yes',0=>'No'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);

                    }
                    elseif ( $attrKey=='active' ){
                        $options = [1=>'Yes',0=>'No'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);
                    }
                    elseif ( $attrKey=='visibility' ){
                        $options = ['both'=>'Everywhere','catalog'=>'Catalog only','search'=>'Search only','none'=>'Nowhere'];
                        echo $this->PrestaField($options,$attrKey,'select',$selectAttrValue);
                    }
                    elseif ($attrKey=='ean13'){
                        echo $this->PrestaField([],$attrKey,'number',$selectAttrValue);

                    }
                    elseif ($attrKey=='upc'){
                        echo $this->PrestaField([],$attrKey,'number',$selectAttrValue);
                    }
                    else{
                        if ( $attrKey=='wholesale_price' || $attrKey =='additional_shipping_cost' ){
                            $input_type = 'number';
                        }else{
                            $input_type = 'text';
                        }
                        echo $this->PrestaField([],$attrKey,$input_type,$selectAttrValue);

                    }
                }
            }
            $key++;
        }
        $options = [1=>'Fedex'];
        echo $this->PrestaField($options,'carriers','select','');

    }
    public function PrestaField($option=[],$name,$input_type,$selectAttrValue){
        if ( $input_type == 'select' ){
            $dropdown = '<div class="form-group">
                    <label>' . ucwords(str_replace('_',' ',$name)) . '</label>
                        <small>Presta specific</small>
                            <select name="p360[presta_attributes][normal]['. $name . ']" class="form-control select2 form-control-line">
                                <option value="">Select ' . $name . '</option>';

            $selectedValue = isset($selectAttrValue['p360']['presta_attributes']['normal'][$name]) ? $selectAttrValue['p360']['presta_attributes']['normal'][$name] : '';

            foreach ($option as $key=>$opt) {
                $sel = ($selectedValue != '' && $key == $selectedValue) ? 'selected' : '';
                $dropdown .= "<option " . $sel . " value='". $key . "'>" . $opt . "</option>";
            }
            //die;
            $dropdown .= '</select></div>';
            return $dropdown;
        }
        else if ( $input_type == 'text' || $input_type == 'number' ){

            if ($name=='ean13')
                $attr = 'maxlength="13"';
            else if ( $name == 'upc' )
                $attr = 'maxlength="12"';
            else
                $attr = '';

            $selectedValue = (isset($selectAttrValue['p360']['presta_attributes']['normal'][$name])) ? $selectAttrValue['p360']['presta_attributes']['normal'][$name] : '';

            $placeholder = '';
            if ($name=='delivery_in_stock')
                $placeholder = 'Delivered within 3-4 days';
            else if ($name=='delivery_out_stock')
                $placeholder = 'Delivered within 5-7 days';

            $input_type = ' <div class="form-group">
                                <label>' . ucwords(str_replace('_',' ',$name)) . '</label><small> * Presta specific</small>
                                <input '.$attr.' type="'.$input_type.'" class="form-control form-control-line" value="'.$selectedValue.'"
                                name="p360[presta_attributes][normal]['. $name .']" placeholder="'.$placeholder.'">
                                </div>';
            return $input_type;
        }
    }
    public function actionLzdAttributes()
    {
        ob_start();
        $catId = \Yii::$app->request->get('cat_id');
        $p3sId = \Yii::$app->request->get('status_id');
        $attributes = Product360Util::callLzdCategoryAttributes($catId);
        $lzdattrValues = [];
        $pullAttrFromLive = ['Success','Activated','DeActivated'];
        if($p3sId != 'undefined' && $p3sId != '' ){
            $p3s = Product360Status::findOne($p3sId);
            $lzdJson = json_decode($p3s->api_request,true);

            if(array_key_exists('lzd_attributes',$lzdJson['p360'])){
                $lzdattrValues = $lzdJson['p360']['lzd_attributes'];
            }
        }
        // var_dump(json_encode($attributes,true));
        // if($lzdattrValues == []){
        //     var_dump("no empty");
        // }else{
        //     var_dump("empty");
        // }
        // exit();

        $key = 0;

        foreach ($attributes['data'] as $attr) {

            if ($attr['name'] != 'brand' && $attr['name'] != 'name' && $attr['name'] != 'short_description' && $attr['name'] != 'model' && $attr['name'] != 'warranty_type' && $attr['name'] != 'SellerSku' && $attr['name'] != 'color_family' && $attr['name'] != 'special_price' && $attr['name'] != 'quantity' && $attr['name'] != 'price' && $attr['name'] != 'package_length' && $attr['name'] != 'package_height' && $attr['name'] != 'package_weight' && $attr['name'] != 'package_width' && $attr['name'] != 'special_from_date' && $attr['name'] != 'special_to_date' && $attr['name'] != 'color_thumbnail' && $attr['name'] != '__images__' ) {

                $attrname = null;
                $selectAttrValue = '';
                if($attr['attribute_type']=="sku"){
                    $attrname = '"p360[lzd_attributes][sku]['. $attr['name']. '] "';
                    if($lzdattrValues != [] && (array_key_exists($attr['name'],$lzdattrValues['sku']))){
                        $selectAttrValue = $lzdattrValues['sku'][$attr['name']];
                    }
                }else{
                    $attrname = '"p360[lzd_attributes][normal]['. $attr['name']. '] "';
                    if($lzdattrValues != [] && (array_key_exists($attr['name'],$lzdattrValues['normal']))){
                        $selectAttrValue = $lzdattrValues['normal'][$attr['name']];
                    }
                }
                /*echo '<pre>';
                print_r($selectAttrValue);
                die;*/

              if ($attr['is_mandatory'] == '1')
              {
                  if($attr['input_type']=="singleSelect" || $attr['input_type']=="multiSelect")
                  {
                      echo '<div class="form-group">
                            <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                            <select name='. $attrname . ' class="form-control select2 form-control-line required">
                            <option value="">Select ' . $attr['name'] . '</option>';
                      $options = $attr['options'];
                      $sel = "";
                      foreach ($options as $opt) {
                          $sel = ($selectAttrValue != '' && $opt['name'] == $selectAttrValue) ? 'selected' : '';
                          echo "<option " . $sel . " value='". $opt['name'] . "'>" . $opt['name'] . "</option>";
                      }
                      echo '</select></div>';
                  }
                  else if($attr['name']=="description")
                  {
                      echo ' <div class="form-group">
                            <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                            <textarea id="editor2" class="form-control editor" rows="3"
                            name='. $attrname .'
                            value=' . $selectAttrValue . '></textarea>
                            </div>';
                  }
                  else if ( $attr['name']=="description_ms" )
                  {
                      echo ' <div class="form-group">
                            <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                            <textarea id="editor3" class="form-control editor" rows="3"
                            name='. $attrname .'
                            value=' . $selectAttrValue . '></textarea>
                            </div>';
                  }
                  else
                      {
                          echo ' <div class="form-group">
                                <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                                <input type="text" class="form-control form-control-line"
                                name='. $attrname .'
                                value=' . $selectAttrValue . '>
                                </div>';
                      }
              }
              else
              {
                  if($attr['input_type']=="singleSelect" || $attr['input_type']=="multiSelect")
                  {
                      echo '<div class="form-group">
                            <label>' . $attr['label'] . '</label>
                            <small> Lazada specific</small>
                            <select name='. $attrname . ' class="form-control select2 form-control-line">
                            <option value="">Select ' . $attr['name'] . '</option>';
                      $options = $attr['options'];
                      $sel = "";
                      foreach ($options as $opt) {
                        //$sel = in_array($opt, $attrList) ? 'selected' : '';
                          $sel = ($selectAttrValue != '' && $opt['name'] == $selectAttrValue) ? 'selected' : '';
                          echo "<option " . $sel . " value='". $opt['name'] . "'>" . $opt['name'] . "</option>";
                      }
                      echo '</select></div>';
                  }
                  else if($attr['name']=="description")
                  {
                      echo ' <div class="form-group">
                            <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                            <textarea id="editor2" class="form-control editor" rows="3"
                            name='. $attrname .'>'. $selectAttrValue .'</textarea>
                            </div>';
                  }
                  else if ( $attr['name']=="description_ms" ){
                      echo ' <div class="form-group">
                            <label>' . $attr['label'] . '</label><small> * Lazada specific</small>
                            <textarea id="editor3" class="form-control editor" rows="3"
                            name='. $attrname .'
                            value=' . $selectAttrValue . '></textarea>
                            </div>';
                  }
                  else
                      {
                          echo ' <div class="form-group">
                                <label>' . $attr['label'] . '</label>
                                <small> Lazada specific</small>
                                <input type="text" class="form-control form-control-line"
                                name='. $attrname .'
                                value=' . $selectAttrValue . '>
                                </div>';
                      }
              }
            }
        $key++;
    }
//exit();
    }

    public function actionDeleteProduct()
    {
        $id = \Yii::$app->request->post('sid');
        $p3s = Product360Status::findOne($id);
        $p3d = Products360Fields::findOne($p3s->product_360_fieldS_id);

        if(isset($p3s->item_id)){
            if ($p3s->shop->marketplace == 'lazada') {
                //var_dump("lazada");exit();
               $apiResponse = json_decode($p3s->api_response,true);
                $skuLists = [];
                if(isset($apiResponse['data']['sku_list'])) {
                    foreach ($apiResponse['data']['sku_list'] as $key => $value) {
                        $skuLists[] = $value['seller_sku'];
                    }
                }else if(isset($apiResponse['data']['skus'])){
                    $skuLists = Product360Util::getLazadaVariationSkus($apiResponse,"");
                }
                if(count($skuLists)<=0){
                    echo "No Sku's Available for delete";
                }
                $status = Product360Util::deleteProductFromLzd(json_encode($skuLists,true),$p3s->shop_id);
                if(array_key_exists('code',$status) && $status['code'] == "0" ){
                    $p3s->status = 'Deleted';
                    $p3s->fail_reason = 'Product Deleted';
                    if (!$p3s->save()){
                        var_dump($p3s->getErrors());
                        echo "Product deleted from Lazada but database error while deleting.";
                    }
                    else{
                        $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
                        $p3d->status = $selectedstatus;
                        $p3d->save();
                        echo "deleted";
                    }
                }
                else{
                    //var_dump($status);
                    echo "Lazada Api Error Occured while deleting";
                }
            }
            if ($p3s->shop->marketplace == 'shopee') {
                //var_dump("shopee");exit();
                $status = Product360Util::deleteProductFromShopee($p3s->item_id,$p3s->shop_id);
                if(array_key_exists('item_id',$status)){
                    $p3s->status = 'Deleted';
                    $p3s->fail_reason = 'Product Deleted';
                    if (!$p3s->save()){
                        var_dump($p3s->getErrors());
                        echo "Product deleted from Shopee but database error while deleting.";
                    }else{
                        $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
                        $p3d->status = $selectedstatus;
                        $p3d->save();
                        echo "deleted";
                    }
                }
                else{
                    //var_dump($status);
                    echo "Shopee Api Error Occured while deleting";
                }
            }
            
        }
        else{
            $p3s->status = 'Deleted';
            $p3s->fail_reason = 'Product Deleted localy';
            if (!$p3s->save()){
                var_dump($p3s->getErrors());
                echo "Database error while deleting.";
            }else{
                $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
                $p3d->status = $selectedstatus;
                $p3d->save();
                echo "deleted";
            }
        }
    }
    public function actionUpdateProductStatus()
    {
        $id = \Yii::$app->request->post('sid');
        $p3s = Product360Status::findOne($id);

        if($p3s->item_id!= NULL || !isset($p3s->item_id)){
            if ($p3s->shop->marketplace == 'lazada') {
                if ($p3s->status == 'Activated' || $p3s->status == 'Success') {
                    $p3s->status = 'DeActivating';
                    $p3s->save();
                 \Yii::$app->queue->push(new SyncProducts([
                    'productId' => $p3s->product_360_fieldS_id,
                    'statusId' => $p3s->id,
                    'isUpdate' => true
                    ])); 
                 echo  "Product is queued for Deactivation";
                }else if ($p3s->status == 'DeActivated') {
                    $p3s->status = 'Activating';
                    $p3s->save();
                 \Yii::$app->queue->push(new SyncProducts([
                    'productId' => $p3s->product_360_fieldS_id,
                    'statusId' => $p3s->id,
                    'isUpdate' => true
                    ])); 
                    echo  "Product is queued for Activation";
                }else{
                    echo  "Product already queued";
                }
                
            }
            if ($p3s->shop->marketplace == 'shopee') {

                if ($p3s->status == 'Activated' || $p3s->status == 'Success') {
                    $status = True;
                    
                }else if ($p3s->status == 'DeActivated') {
                    $status = false;
                   
                }else{
                    echo  "Product already queued";
                }
                $response = Product360Util::UpdateProductStatusFromShopee($p3s->item_id,$p3s->shop_id,$status);
                if(array_key_exists('success', $response)){
                    if($status == True)
                        $p3s->status = 'DeActivated';
                    else
                        $p3s->status = 'Activated';
                    $p3s->save();

                    $p3d = Products360Fields::findOne($p3s->product_360_fieldS_id);
                    $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
                    $p3d->status = $selectedstatus;
                    $p3d->save();

                    echo  "Product status changed successfully";
                }else if(array_key_exists('failed', $response)){
                    $message = $response['failed'][0]['item_id'] . ': ' . $response['failed'][0]['error_description'];
                     echo  $message;
                }else{
                    echo "Error Occured while updating status";
                }
            }
            
        }
        else{
            echo "Product doesn't exist on market place.";
        }
    }
    public function actionGetShopeeSalesInformationView(){
        return $this->renderPartial('_render-partials/shopee/sales-information');
    }
    public function actionTestCase(){
        $data = [
            'name'=>['Red','White'],
            'price' => ['100','100'],
            'stock' => ['1','1'],
            'sku' => ['scf000','scf222']
        ];


    }
    public function actionCheckSku(){
        $Product_Sku = Product360Util::GetSkuList($_GET['product_sku']);
        $Info_Decode = json_decode($Product_Sku);
        // check system sku
        $Check_Sku_Duplicate = Products::find()->where(['sku'=>$_GET['product_sku']])->asArray()->all();
        $In_Sys_Exist = '';
        if (!empty($Check_Sku_Duplicate)){
            $In_Sys_Exist = " Sku is already in system";
        }

        if (empty($Info_Decode) && empty($Check_Sku_Duplicate)){
            $Msg = "";
            echo $Msg;
        }else{
            if ( !empty($Check_Sku_Duplicate) )
                $Msg = "Sku already exist in the System";
            else
                $Msg = "Sku already exist on ".implode(',',$Info_Decode);

            echo $Msg;
        }
    }
    private function actionUpdteSkuInSystem(){
        $_GET['cost_price']=number_format($_POST['p360']['common_attributes']['product_cprice'],2,'.', '');
        $_GET['rcp']=number_format($_POST['p360']['common_attributes']['product_price'],2,'.', '');
        $_GET['margin']=5.00;
        $_GET['subsidy']=0.00;
        $ps =  new Products();
        $ps->sku = $_POST['p360']['common_attributes']['product_sku'];
        $ps->cost = (double)($_GET['cost_price']);
        $ps->name = $_POST['p360']['common_attributes']['product_short_description'];
        $ps->tnc = '';
        $ps->rccp = $_GET['rcp'];
        $ps->cost = $_GET['cost_price'];
        $ps->extra_cost = 0.00;
        $ps->created_at=time();
        $ps->updated_at=time();
        /*if ( $_GET['promo_price'] != '' ){
            $ps->promo_price=$_GET['promo_price'];
        }*/
        /*if($_GET['product_type']=='FOC'){
            $ps->is_foc=1;
            $ps->is_orderable=0;
        }*/
        $ps->is_foc=0;
        $ps->is_orderable=1;
        $ps->sub_category = $_POST['p360']['sys_category'] ;
        $ps->created_by=\Yii::$app->user->identity->getId();
        $ps->save(false);
    }
    private function actionAddSkuInSystem(){

        $_GET['cost_price']=number_format($_POST['p360']['common_attributes']['product_cprice'],2,'.', '');
        $_GET['rcp']=number_format($_POST['p360']['common_attributes']['product_price'],2,'.', '');
        $_GET['margin']=5.00;
        $_GET['subsidy']=0.00;


        $Parent_Sku_Id = $this->AddSkuToProductDetails($_POST['p360']['common_attributes']['product_sku'],$_GET['cost_price'],$_GET['rcp'],$_GET['subsidy']);

        if ( isset($_POST['p360']['variations']) ){

            foreach ( $_POST['p360']['variations'] as $key=>$value ){
                if ( $key=='prestashop' )
                    continue;
                $this->AddSkuToProductDetails($value['sku'],$value['price'],$value['rccp'],$_GET['subsidy'],$Parent_Sku_Id);
            }
        }
    }
    private function AddSkuToProductDetails($Sku,$cprice,$rcp,$subsidy,$Parent_sku_id=0){

        $ps =  new Products();
        $ps->sku = $Sku;
        $ps->cost = (double)($cprice);
        $ps->parent_sku_id = $Parent_sku_id;
        $ps->name = $_POST['p360']['common_attributes']['product_short_description'];
        $ps->tnc = '';
        $ps->rccp = $rcp;
        $ps->cost = $cprice;
        $ps->extra_cost = 0.00;
        $ps->created_at=time();
        $ps->updated_at=time();
        $ps->is_foc=0;
        $ps->is_orderable=1;
        $ps->sub_category = $_POST['p360']['sys_category'] ;
        $ps->created_by=\Yii::$app->user->identity->getId();
        $ps->save(false);
        $Sku_Id=$ps->id;

        $insert_Product_Detail = new ProductDetails();
        $insert_Product_Detail->sku_id= HelpUtil::exchange_values('sku','id',$Sku,'products');
        $insert_Product_Detail->isis_sku=$_POST['p360']['common_attributes']['product_sku'];
        $insert_Product_Detail->parent_isis_sku='0';
        $insert_Product_Detail->last_update=date('Y-m-d h:i:s');
        //if($_GET['product_type']=='FOC'){
        if(0){
            $insert_Product_Detail->is_fbl='1';
        }
        $insert_Product_Detail->sync_for=1;
        $insert_Product_Detail->save();
        if (!empty($insert_Product_Detail->errors))
            $this->debug($insert_Product_Detail->errors);
        else{
            //if ($_GET['product_type']!='FOC'){
                /*
                 * Var Array @product get the product detail
                 *
                 * */
                $product = $this->getList('sku','products',0," WHERE sku = '".$Sku."'");
                $T1 = $this->ManualWeeklyThresholdSetByUnitPrice($cprice);
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
                $Add_Threshold->created_by = \Yii::$app->user->identity->getId();
                $Add_Threshold->updated_by = \Yii::$app->user->identity->getId();
                $Add_Threshold->save();
        }
        $cp = Products::find()->where(['sku'=>$Sku])->one();
        if($cp)
        {
            $ch = Channels::find()->where(['is_active'=>'1'])->all();
            foreach ($ch as $c)
            {
                $sub = Subsidy::find()->where(['sku_id'=>$cp->id])->andWhere(['channel_id'=>$c->id])->one();
                if(!$sub)
                    $sub = new subsidy();
                $sub->sku_id = $cp->id;
                $sub->subsidy = 0.00;
                $sub->margins = 5.00;
                $sub->ao_margins = 5.00;
                $sub->start_date = date('Y-m-d h:i:s');
                $sub->end_date = date('Y-m-d h:i:s',strtotime('+ 1 month'));
                $sub->channel_id = $c->id;
                $sub->updated_by = '1';
                $sub->save(false);
            }

        }
        return $Sku_Id;
    }
    /*
     * Return Threshold T1
     *
     * @param integer $Price Decides what the threshold will be
     * */
    public function ManualWeeklyThresholdSetByUnitPrice($Price){

        if ($Price > 0 && $Price <= 150)
            $Threshold = 5;
        elseif ($Price > 150 && $Price < 300)
            $Threshold = 3;
        else
            $Threshold = 1;

        return $Threshold;
    }
    /*
     * Return Threshold T2
     *
     * @param
     * */
    public function GetThresholdCritical($T1,$Status){

        if ($T1 == 3)
            $T2 = 2;
        else if ($T1 == 2)
            $T2 = 1;
        else if ($T1 == 1)
            $T2 = 1;
        else{
            $Daily = $T1 / 7;
            $T2 = ceil($Daily) * 3;
        }
        if ($Status=='Not Moving' || $Status=='New')
            $T2=0;

        return $T2;
    }
    /*
     * Return the table data
     *
     * @param string $Index the Array index name.
     * @param string $Table The table name.
     * @param string $Value Sets the where clause of $Index
     * @param boolean $Multi Sets the array multiple array inside an index.
     */

    public function getList($Index,$Table,$Multi=0,$Where=""){

        $query = " SELECT * FROM ".$Table.$Where;
        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand($query);

        $result = $command->queryAll();
        $response = [];

        foreach ( $result as $value ){
            if ($Multi==0){
                $response[$value[$Index]]=$value;
            }else{
                $response[$value[$Index]][]=$value;
            }
        }
        return $response;

    }
    public function actionCopyProductShops()
    {
        $s3id = \Yii::$app->request->post('sid');
        $shops = \Yii::$app->request->post('shoplist');
        $p3ss = Product360Status::findOne($s3id);
        $p3d = Products360Fields::findOne($p3ss->product_360_fieldS_id);

        if(isset($shops)){
            foreach ($shops as $prefix) {
                        
                        $fields = json_decode($p3ss->api_request, true);
                        $shop = Channels::find()->where(['prefix' => $prefix,'is_active'=>'1'])->one();
                        $fields['p360']['shop'] =  [$shop->prefix];
                        $apiRequest = json_encode($fields,true);

                        $p3s = new Product360Status();
                        $p3s->images = $p3ss->images;
                        $p3s->product_360_fieldS_id = $p3ss->product_360_fieldS_id;
                        $p3s->shop_id = $shop->id;
                        $p3s->api_request = $apiRequest;
                        
                        if (!$p3s->save()){
                            var_dump($p3s->getErrors());
                            echo "Database error while deleting.";
                        }else{

                            \Yii::$app->queue->push(new SyncProducts([
                                'productId' => $p3s->product_360_fieldS_id,
                                'statusId' => $p3s->id
                            ]));
                        }

                    }

            $selectedstatus = Product360Util::UpdateStatusValue($p3d->id, $p3d->status);
            $p3d->status = $selectedstatus;
            $p3d->save();
            echo "Product successfully copies on selected shops";

        }
    }
    public function actionUploadVariationImages(){
        if (isset($_FILES)) {
            foreach ( $_FILES as $key=>$value )
                $key=$key;

            Product360Util::saveVariationImages($_FILES,$key);
        }
    }
    public function actionProductSyncSc($search=''){
        $_GET['item_id'] = $search;
        if ( in_array($_GET['channel_id'],[1,15]) ){
            Product360Util::SyncProduct360Lazada();
        }
        elseif ( in_array($_GET['channel_id'],2) ){
            Product360Util::SyncProduct360Shopee();
        }
    }

    public function actionAddPrestaProduct(){
        $api_request = '{"_csrf-backend":"C3T8T4lfeApATwwvqPWaK6eh3f7BV3kqsF71lMVSi5VgGKUpsAAAbBN5SH7gkdRixPiru6ZlSkjIGbz2lAHSww==","uqid":"5d539fd12159d","pid":"","p360":{"shop":["PST-USBOX"],"lzd_category":"","presta_category":"184","shope_category":"","street_category":"","sys_category":"169","common_attributes":{"product_name":"TEST PRODUCT REQUIREMENETS","product_sku":"REQUIRE111","product_color":"Green","product_short_description":"<p>REQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSISREQUIREMENT OF ANALYSIS<\/p>\r\n","product_price":"2500","product_cprice":"2100","product_qty":"22","package_weight":"1","package_length":"22","package_width":"1","package_height":"12","brand":"","special_from_date":"","special_to_date":""},"lzd_attributes":{"normal":{"warranty_type":""}},"shopee_attributes":{"shpe_logistics":""},"presta_attributes":{"normal":{"ean13":"983984948894","upc":"38848939844","wholesale_price":"100","additional_shipping_cost":"100","active":"0","available_for_order":"0","condition":"used","visibility":"search","description_short":"<p>DSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD FDSFA SDF ASD FASD F<\/p>\r\n","carriers":"1"}},"variations":{"1":{"type":{"Color":"Ivory"},"sku":"REQUIRE999","price":"200","stock":"11","rccp":"100","prestashop":{"ean_no":"32333099494","size":"Medium"},"images":["5d539fd12159d\/variation-1\/xSbWRRIJ.jpg"]}}},"save":"Publish"}';
        $info = json_decode($api_request, true);
        $data = ['info' => $info, 'images' => ''];
        $presshop = PrestashopUtil::AddNewProductOnPrestashop(16,$data,false);
        //$attr = PrestashopUtil::Attributes(16);
    }
}
