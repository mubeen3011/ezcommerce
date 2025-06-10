<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/19/2019
 * Time: 1:35 PM
 */

namespace backend\util;


use backend\controllers\ApiController;
use common\models\Channels;
use common\models\Product360MarketplaceCategories;
use common\models\Product360Status;
use common\models\Products;
use common\models\Products360Fields;
use phpDocumentor\Reflection\Types\Integer;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

class Product360Util
{

    // lazada

    // get lazada shop brands
    public static function getLzdCategory($sid, $selected = '')
    {
        $pcat = Product360MarketplaceCategories::findOne(['marketplace' => 'lazada']);
        if ( isset($pcat->category_api_resp) )
            $response = json_decode($pcat->category_api_resp, true);
        if (isset($response['data'])) {

            self::_recursiveLzdCategoryTree($response['data'], '', $selected);
        }
    }
    // prestashop category
    public static function GetCategory($shopid, $selected = '')
    {
        $pcat = Product360MarketplaceCategories::find()->where(['channel_id' => $shopid])->asArray()->all();
        foreach ($pcat as $key=>$value){
            if ($value['marketplace']=='prestashop'){
                if ( isset($value['category_api_resp']) )
                    $response = json_decode($value['category_api_resp'], true);
                if (isset($response['category']['category'])) {
                    self::PrestashopCategories($response, '', $selected);
                }
            }
        }

    }
    // prestashop category
    public static function PrestashopCategories( $categories,$sep='',$selected ){

        foreach ( $categories['category']['category'] as $key => $value ){
            $select = ($value['id'] == trim($selected)) ? 'selected' : '';
            echo '<option ' . $select . ' value="' . $value['id'] . '">' . $sep . $value['name']['language'] . '</option>';
        }

    }
    // safe product images into file system
    public static function saveImages($image)
    {
        $img = UploadedFile::getInstanceByName('file');
        $imageName = \Yii::$app->security->generateRandomString(8) . '.' . $img->extension;
        //$result = $s3->upload($imageName, $img->tempName);
        $path = 'product_images/' . $_POST['uqid']. '/mainimage';
        FileHelper::createDirectory($path);
        chmod($path, 0777);
        $img->saveAs($path . '/' . $imageName);
        if (isset($_POST['sid']) && $_POST['sid'] != '') {
            //$p3f = Products360Fields::findOne($_POST['pid']);
            $p3s = Product360Status::findOne($_POST['sid']);
            $images = explode(',', $p3s->images);
            $images[] = $_POST['uqid'] . '/mainimage/' . $imageName;
            $p3s->images = implode(',', $images);
            $p3s->save();
        }

    }

    //get images to save into db from folder
    public static function getImages($unqId)
    {
        $dir = 'product_images/' . $unqId . '/mainimage/';
        try{
            $images = preg_grep('/^([^.])/', scandir($dir, 1));
            // self::debug($images);
            return $images;
        } catch(\Exception $ex){
            return "";
        }


    }
    // save variation images
    public static function saveVariationImages($image,$name)
    {
        $img = UploadedFile::getInstanceByName($name);
        $imageName = \Yii::$app->security->generateRandomString(8) . '.' . $img->extension;
        //$result = $s3->upload($imageName, $img->tempName);
        $path = 'product_images/' . $_POST['uqid'].'/'.$name;
        /*if ( file_exists($path) ){

        }else{
            for ($counter=1;$counter<=20;$counter++){
                $path = 'product_images/' . $_POST['uqid'].'/variation-'.$counter;
                if ( !file_exists($path) ){
                    $path = 'product_images/' . $_POST['uqid'].'/variation-'.$counter;
                    break;
                }
            }
        }*/


        FileHelper::createDirectory($path);
        chmod($path, 0777);
        $img->saveAs($path . '/' . $imageName);
        if (isset($_POST['sid']) && $_POST['sid'] != '') {
            self::debug($_POST['pid']);
            /*$p3s = Product360Status::findOne($_POST['sid']);
            $images = explode(',', $p3s->images);
            $images[] = $_POST['uqid'] . '/' . $imageName;
            $p3s->images = implode(',', $images);
            $p3s->save();*/
        }

    }
    //get images to save into db from folder
    public static function getVariationImages($unqId,$variation_folder)
    {
        $dir = 'product_images/' . $unqId . '/'.$variation_folder.'/';
        if (file_exists(\Yii::getAlias('@web') . $dir)) {
            $images = preg_grep('/^([^.])/', scandir($dir, 1));
            foreach ($images as $key=>$val){
                $images[$key] = $unqId.'/'.$variation_folder.'/'.$val;
            }
        }else{
            $images =[];
        }
        return $images;
    }
    private static function _recursiveLzdCategoryTree($level, $sep = '', $selected)
    {
        foreach ($level as $l) {

            if (isset($l['leaf']) && $l['leaf'] == true) {
                $select = ($l['category_id'] == trim($selected)) ? 'selected' : '';
                echo '<option ' . $select . ' value="' . $l['category_id'] . '">' . $sep . $l['name'] . '</option>';
            } else if (isset($l['children']))
                self::_recursiveLzdCategoryTree($l['children'], $sep . $l['name'] . '->', $selected);
            else
                self::_recursiveLzdCategoryTree($l, $sep . $l['name'] . '->', $selected);
        }
    }

    public static function getLazadaVariationSkus($data,$mainSku){
        $variationList = [];
        if(isset($data['p360'])) {
            //$variationList[] = $data['p360']['common_attributes']['product_sku'];
            if (isset($data['p360']['variations'])) {
                foreach ($data['p360']['variations'] as $key => $val) {
                    foreach ($val as $subkey => $subval) {
                        if ($subkey == 'sku') {
                            $variationList[] = $subval;
                        }
                    }
                }
            }
        }
        if(isset($data['data'])) {
            if (isset($data['data']['skus'])) {
                foreach ($data['data']['skus'] as $key => $val) {
                    foreach ($val as $subkey => $subval) {
                        if ($subkey == 'SellerSku' && $subval != $mainSku) {
                            $variationList[] = $subval;
                        }
                    }
                }
            }
        }
        return $variationList;
    }
    public static function RefineLzdVariation($form_variation,$shop_variations){
        $f_vals = [];
        $s_vals = [];
        if($form_variation!='' && count($form_variation)>0) {
            $f_vals = $form_variation;
        }
        if($shop_variations!='' && count($shop_variations)>0) {
            $s_vals = $shop_variations;
        }
        $variations = [];
        foreach( $f_vals as $value ){
            if ( !in_array($value,$s_vals) ){
                $variations['create'][] = $value;
            }
            // for udpate sku
            else{
                $variations['update'][] = $value;
            }
        }
        // for delete
        foreach ( $s_vals as $value ){
            if ( !in_array($value,$f_vals) ){
                $variations['delete'][] = $value;
            }
        }
        return $variations;
    }
    public static function makeLzdVariationSeperate($formdata,$variations){
        $varSkuDataList = [];
        if(isset($formdata) && count($formdata)>0){
            if(isset($variations['create'])){
                foreach ($variations['create'] as $key){
                    $index = array_search($key, array_column($formdata, 'sku'));
                    $varSkuDataList["create"][] = $formdata[$index];
                }
            }
            if(isset($variations['update'])){
                $counter = 0;
                foreach ($variations['update'] as $key){
                    $index = array_search($key, array_column($formdata, 'sku'));
                    $varSkuDataList["update"][] = $formdata[$index];
                }
            }
        }
        if(isset($variations['delete'])){
            foreach ($variations['delete'] as $key){
                $varSkuDataList["delete"][] = $key;
            }
        }
        return $varSkuDataList;
    }
    //generate Lazada payload xml for product create param
    Public static function genLzdXmlForAssociatedSku($data,$status,$variationData)
    {
        $skuattrs = '';
        /*Start Sku Common Attributes for parent and variation sku in this request*/
        $lzdcommonvariation =
            '<special_from_date>' . $data['info']['p360']['common_attributes']['special_from_date'] . '</special_from_date> 
             <special_to_date>' . $data['info']['p360']['common_attributes']['special_to_date'] . '</special_to_date>
             <package_length>' . $data['info']['p360']['common_attributes']['package_length'] . '</package_length>
             <package_height>' . $data['info']['p360']['common_attributes']['package_height'] . '</package_height>
             <package_weight>' . $data['info']['p360']['common_attributes']['package_weight'] . '</package_weight>
             <package_width>' . $data['info']['p360']['common_attributes']['package_width'] . '</package_width>';

        if(isset($data['info']['p360']['lzd_attributes']['sku'])) {
            foreach ($data['info']['p360']['lzd_attributes']['sku'] as $key => $val) {
                if ($val != '')
                    $skuattrs .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        if($status=="Activating" || $status=="Pending")
            $skuattrs .= "<active>true</active>";
        else if($status=="DeActivating")
            $skuattrs .= "<active>false</active>";
        /*End Sku Common Attributes for parent and variation sku in this request*/

        /*Start Push variation sku's Data*/
        $lzdAssociatedSku='';
        foreach ($variationData['create'] as $key => $val) {
            $lzdAssociatedSku .= '<Sku>';
            foreach ($val as $subkey => $subval) {
                if ($subkey == 'type') {
                    $lzdAssociatedSku .= "<color_family>" . $subval['Color'] . "</color_family>";
                }
//                else if ($subkey == 'images') {
//                    $lzdAssociatedSku .= '<images>';
//                    foreach ($subval as $imgkey => $imgval) {
//                        $lzdAssociatedSku .= "<image>" . $imgval . "</image>";
//                    }
//                    $lzdAssociatedSku .= '</images>';
//                }
                else if ($subkey == 'sku') {
                    $lzdAssociatedSku .= "<SellerSku>" . $subval . "</SellerSku>";
                } else if ($subkey == 'stock') {
                    $lzdAssociatedSku .= "<quantity>" . $subval . "</quantity>";
                } else if ($subkey == 'rccp') {
                    $lzdAssociatedSku .= "<special_price>" . $subval . "</special_price>";
                } else if ($subkey == 'price') {
                    $lzdAssociatedSku .= "<price>" . $subval . "</price>";
                }
//                else {
//                    $lzdAssociatedSku .= "<" . $subkey . ">" . $subval . "</" . $subkey . ">";
//                }
            }
            $lzdAssociatedSku .= $lzdcommonvariation;
            $lzdAssociatedSku .= $skuattrs;
            $lzdAssociatedSku .= '</Sku>';
        }
        /*End Push variation sku's Data*/
        $xml = '<?xml version="1.0" encoding="UTF-8" ?> 
            <Request><Product>
            <PrimaryCategory>'
            . $data['info']['p360']['lzd_category'] .
            '</PrimaryCategory>         
             <SPUId></SPUId>         
             <AssociatedSku>' . $data['info']['p360']['common_attributes']['product_sku'] . '</AssociatedSku>               
             <Skus>
                  '. $lzdAssociatedSku .'
             </Skus>     
             </Product>
             </Request>';
        $xml = str_replace(array("&"),array("&amp;"),$xml);
        $xml = trim(preg_replace('/\s\s+/', ' ', $xml));
        return $xml;
    }
    Public static function genLzdXmlFile($data,$status,$variationData,$isUpdate)
    {
        $normalattrs = '';
        $skuattrs = '';
        /*Start Normal Attributes Common for every sku in this request*/
        if(isset($data['info']['p360']['lzd_attributes']['normal'])){
            foreach($data['info']['p360']['lzd_attributes']['normal'] as $key => $val) {
                if($val!=''){
                    if($key=="description")
                        $normalattrs .= "<" .$key . ">" . htmlentities($val) . "</" .$key . ">";
                    else
                        $normalattrs .= "<" .$key . ">" . $val. "</" .$key . ">";
                }
            }
        }
        /*End Normal Attributes Common for every sku in this request*/

        /*Start Sku Common Attributes for parent and variation sku in this request*/
        $lzdcommonvariation =
            '<special_from_date>' . $data['info']['p360']['common_attributes']['special_from_date'] . '</special_from_date> 
             <special_to_date>' . $data['info']['p360']['common_attributes']['special_to_date'] . '</special_to_date>
             <package_length>' . $data['info']['p360']['common_attributes']['package_length'] . '</package_length>
             <package_height>' . $data['info']['p360']['common_attributes']['package_height'] . '</package_height>
             <package_weight>' . $data['info']['p360']['common_attributes']['package_weight'] . '</package_weight>
             <package_width>' . $data['info']['p360']['common_attributes']['package_width'] . '</package_width>';

        if(isset($data['info']['p360']['lzd_attributes']['sku'])) {
            foreach ($data['info']['p360']['lzd_attributes']['sku'] as $key => $val) {
                if ($val != '')
                    $skuattrs .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        if($status=="Activating" || $status=="Pending")
            $skuattrs .= "<active>true</active>";
        else if($status=="DeActivating")
            $skuattrs .= "<active>false</active>";
        /*End Sku Common Attributes for parent and variation sku in this request*/

        /*Start Push variation sku's Data*/
        $lzdvariation = '';
        $lzdAssociatedSku = '<AssociatedSku></AssociatedSku>  ';
        if(isset($data['info']['p360']['variations'])) {
            if($isUpdate){
                if(isset($variationData['update'])){
                    foreach ($variationData['update'] as $key => $val) {
                        $lzdvariation .= '<Sku>';
                        foreach ($val as $subkey => $subval) {
                            if ($subkey == 'type') {
                                $lzdvariation .= "<color_family>" . $subval['Color'] . "</color_family>";
                            } else if ($subkey == 'sku') {
                                    $lzdvariation .= "<SellerSku>" . $subval . "</SellerSku>";
                            } else if ($subkey == 'stock') {
                                $lzdvariation .= "<quantity>" . $subval . "</quantity>";
                            } else if ($subkey == 'rccp') {
                                $lzdvariation .= "<special_price>" . $subval . "</special_price>";
                            } else if ($subkey == 'price') {
                                $lzdvariation .= "<price>" . $subval . "</price>";
                            }
                        }
                        $lzdvariation .= $lzdcommonvariation;
                        $lzdvariation .= $skuattrs;
                        $lzdvariation .= '</Sku>';
                    }
                }
            }else {
                if(isset($variationData['create'])){
                    foreach ($variationData['create'] as $key => $val) {
                        $lzdvariation .= '<Sku>';
                        foreach ($val as $subkey => $subval) {
                            if ($subkey == 'type') {
                                $lzdvariation .= "<color_family>" . $subval['Color'] . "</color_family>";
                            } else if ($subkey == 'sku') {
                                $lzdvariation .= "<SellerSku>" . $subval . "</SellerSku>";
                            } else if ($subkey == 'stock') {
                                $lzdvariation .= "<quantity>" . $subval . "</quantity>";
                            } else if ($subkey == 'rccp') {
                                $lzdvariation .= "<special_price>" . $subval . "</special_price>";
                            } else if ($subkey == 'price') {
                                $lzdvariation .= "<price>" . $subval . "</price>";
                            }
                        }
                        $lzdvariation .= $lzdcommonvariation;
                        $lzdvariation .= $skuattrs;
                        $lzdvariation .= '</Sku>';
                    }
                }
            }
        }
        /*End Push variation sku's Data*/
        $xml = '<?xml version="1.0" encoding="UTF-8" ?> 
            <Request><Product>
            <PrimaryCategory>'
            . $data['info']['p360']['lzd_category'] .
            '</PrimaryCategory>         
             <SPUId></SPUId>         
             '. $lzdAssociatedSku .'         
             <Attributes>             
                 <name>' . $data['info']['p360']['common_attributes']['product_name'] . '</name>
                 <short_description>' . htmlentities($data['info']['p360']['common_attributes']['product_short_description']) . '</short_description>
                 <brand>' . $data['info']['p360']['common_attributes']['brand'] . '</brand>             
                 <model>' . $data['info']['p360']['common_attributes']['product_sku'] . '</model>'
            . $normalattrs .
            '</Attributes>         
             <Skus>
             <Sku>
                 <SellerSku>' . $data['info']['p360']['common_attributes']['product_sku'] . '</SellerSku> 
                 <color_family>' . $data['info']['p360']['common_attributes']['product_color'] . '</color_family>  
                 <price>' . $data['info']['p360']['common_attributes']['product_price'] . '</price>
                 <special_price>' . $data['info']['p360']['common_attributes']['product_cprice'] . '</special_price> 
                 <quantity>' . $data['info']['p360']['common_attributes']['product_qty'] . '</quantity>'
            . $lzdcommonvariation .
            ' '
            . $skuattrs .
            '</Sku>
                  '. $lzdvariation .'
             </Skus>     
             </Product>
             </Request>';

        $xml = str_replace(array("&"),array("&amp;"),$xml);
        $xml = trim(preg_replace('/\s\s+/', ' ', $xml));
        return $xml;
    }

    // call create product Lazada API

    public static function callLzdCreateProduct($payload, $sid, $isUpdate)
    {
        $ch = Channels::findOne(['id' => $sid,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = '';
        if ($isUpdate)
            $customParams['action'] = '/product/update';
        else
            $customParams['action'] = '/product/create';
        $customParams['params']['payload'] = $payload;
        //var_dump($payload);exit();
        $response = ApiController::_callLzdRequestMethod($customParams);
        //var_dump($response);
        $response = json_decode($response, true);
        return $response;
    }
    // upload product images into lazada seller center
    public static function uploadLzdImageswithVariation($variationData,$mainSku,$mainSkuImages,$shopid){
        $skuwithImages = "";
        $requestXml = "";
        $skuListwithMainImages = [];
        $skuListwithMainImages[] .= $mainSku;
        if(isset($variationData['create'])){
            foreach ($variationData['create'] as $key=>$val){
                $sellerSku = "";
                $skuImages = [];
                foreach ($val as $subkey => $subval) {
                    if ($subkey == 'sku') {
                        $sellerSku = $subval;
                    }
                    if ($subkey == 'images') {
                        $skuImages = $subval;
                    }
                }
                if(count($skuImages)>0)
                    $skuwithImages .= self::callLzdUploadImages($shopid,$skuImages,$sellerSku);
                else
                    $skuListwithMainImages[] .= $sellerSku;
            }
        }
        if(isset($variationData['update'])){
            foreach ($variationData['update'] as $key=>$val){
                $sellerSku = "";
                $skuImages = [];
                foreach ($val as $subkey => $subval) {
                    if ($subkey == 'sku') {
                        $sellerSku = $subval;
                    }
                    if ($subkey == 'images') {
                        $skuImages = $subval;
                    }
                }
                if(count($skuImages)>0)
                    $skuwithImages .= self::callLzdUploadImages($shopid,$skuImages,$sellerSku);
                else
                    $skuListwithMainImages[] .= $sellerSku;
            }
        }
        if(isset($skuListwithMainImages)){
            foreach ($skuListwithMainImages as $key){
                $skuwithImages .= self::callLzdUploadImages($shopid,$mainSkuImages,$key);
            }
        }
        if($skuwithImages!=""){
            $requestXml.= "<Request><Product><Skus>" . $skuwithImages . "</Skus>   </Product> </Request>";
            return self::_callLzdSetImages($shopid,$requestXml);
        }else{
            return "No images to upload";
        }
    }
    public static function callLzdUploadImages($sid, $images, $sellerSku)
    {
        $ch = Channels::findOne(['id' => $sid,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = '';
        $customParams['action'] = '/image/upload';
        $customParams['params'] = [];
        $imageList = "";
        $skuXml = "";
        foreach ($images as $img) {
            $customParams['file_params']['image'] = dirname(__DIR__) . '/web/product_images/' . $img;
            $response = ApiController::_callLzdRequestMethod($customParams);
            $response = json_decode($response, true);
            //var_dump($response);
            if (isset($response['data']['image'])) {
                $imageList .= "  <Image>" . $response['data']['image']['url'] . "</Image>  ";
            }
        }
        if($imageList!=""){
            $skuXml .="<Sku>";
            $skuXml .= "<SellerSku>" . $sellerSku . "</SellerSku>";
            $skuXml .= "<Images>" . $imageList . "</Images>";
            $skuXml .="</Sku>";
        }
        return $skuXml;
    }
    private  static function makeLzdImageRequestXml($imageList,$skuList){
        $imgList = "";
        $skuXml = "";
        $requestXml = "";
        foreach ($imageList as $imgs) {
            $imgList .= "  <Image>" . $imgs . "</Image>  ";
        }
        foreach ($skuList as $sku){
            $skuXml.="<Sku>";
            $skuXml .= "<SellerSku>" . $sku . "</SellerSku>";
            $skuXml .= "<Images>" . $imgList . "</Images>";
            $skuXml.="</Sku>";
        }
        $requestXml.= "<Request><Product><Skus>" . $skuXml . "</Skus>   </Product> </Request>";
        return $requestXml;
    }
    // set images into product sku lazada
    private static function _callLzdSetImages($sid, $payloadXml){
        $ch = Channels::findOne(['id' => $sid,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = '';
        $customParams['action'] = '/images/set';
        $customParams['params']['payload'] = $payloadXml;
        //var_dump($customParams);
        $response = ApiController::_callLzdRequestMethod($customParams);
        $response = json_decode($response, true);
        //var_dump($response);
        return $response;
    }

    // get lazada product info for update
    public static function getLazadaProductItem($item_id, $sid, $pinfo)
    {
        $ch = Channels::findOne(['id' => $sid,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/product/item/get';
        $customParams['params']['item_id'] = (int)$item_id;
        $response = ApiController::_callLzdRequestMethod($customParams);
        $jsonResponse = $response;
        $response = json_decode($response, true);
        if ($response['code'] == 208)
            return false;
        $refine = [];
        $refine['p360']['lzd_category'] = $response['data']['primary_category'];
        $refine['p360']['common_attributes']['product_name'] = $response['data']['attributes']['name'];
        $refine['p360']['common_attributes']['product_sku'] = $response['data']['skus'][0]['SellerSku'];
        $refine['p360']['common_attributes']['brand'] = $response['data']['attributes']['brand'];
        $refine['p360']['common_attributes']['product_short_description'] = $response['data']['attributes']['short_description'];
        $refine['p360']['common_attributes']['product_price'] = $response['data']['skus'][0]['price'];
        $refine['p360']['common_attributes']['product_cprice'] = $response['data']['skus'][0]['special_price'];
        $refine['p360']['common_attributes']['product_qty'] = $response['data']['skus'][0]['Available'];
        //$refine['p360']['common_attributes']['product_color'] = $response['data']['skus'][0]['color_family'];
        $refine['p360']['common_attributes']['package_height'] = $response['data']['skus'][0]['package_height'];
        $refine['p360']['common_attributes']['package_length'] = $response['data']['skus'][0]['package_length'];
        $refine['p360']['common_attributes']['package_width'] = $response['data']['skus'][0]['package_width'];
        $refine['p360']['common_attributes']['package_weight'] = $response['data']['skus'][0]['package_weight'];
        $refine['p360']['common_attributes']['special_from_date'] = (isset($response['data']['skus'][0]['special_from_time'])) ? $response['data']['skus'][0]['special_from_time'] : '';
        $refine['p360']['common_attributes']['special_to_date'] = (isset($response['data']['skus'][0]['special_to_time'])) ? $response['data']['skus'][0]['special_to_time'] : '';
        // $refine['p360']['lazada_attributes']['normal']['warranty_type'] = $response['data']['attributes']['warranty_type'];
        $refine['p360']['shop'] =  [$ch->prefix];
        //$refine['p360']['org_shop'] = $pinfo['p360']['shop'];
        $refine['p360']['sys_category'] = $pinfo['p360']['sys_category'];
        $refine['uqid'] = $pinfo['uqid'];

        foreach ($response['data']['attributes'] as $key  => $val) {
            if($key !='name' && $key !='brand' && $key !='model' && $key !='short_description')
            {
                $refine['p360']['lzd_attributes']['normal'][$key] = $val;
            }
        }
        foreach ($response['data']['skus'][0] as $key  => $val) {
            if($key !='SellerSku' && $key !='price' && $key !='special_price' && $key !='Available' && $key !='package_height' && $key !='package_length' && $key !='package_width' && $key !='package_weight' && $key != 'special_from_date' && $key != 'special_to_date')
            {
                $refine['p360']['lzd_attributes']['sku'][$key] = $val;
            }
            if($key =='color_family'){
                $refine['p360']['common_attributes']['product_color'] = $val;
            }
        }
        /*Load lazada variations*/
        $lzdFormSkus = Product360Util::getLazadaVariationSkus($pinfo,"");
        $variationsSkus = Product360Util::RefineLzdVariation($lzdFormSkus,'');
        $variationData = [];
        if(isset($pinfo['p360']['variations'])) {
            $onlyVariations = [];
            foreach ($pinfo['p360']['variations'] as $val) {
                $onlyVariations[] = $val;
            }
            $variationData = Product360Util::makeLzdVariationSeperate($onlyVariations, $variationsSkus);
        }
        //echo '<pre>'; print_r($variationData);exit();

        if(count($response['data']['skus'])>1)
        {
            $lazadaVariations = [];
            $counter = 0;
            foreach ($response['data']['skus'] as $key => $val) {
                if ($key == 0)
                    continue;
                foreach ($val as $subkey => $subval) {
                    if ($subkey == "color_family") {
                        $lazadaVariations[$counter]['type']['Color'] = $subval;
                    }
                    if ($subkey == "price") {
                        $lazadaVariations[$counter]['price'] = $subval;
                    }
                    if ($subkey == "special_price") {
                        $lazadaVariations[$counter]['rccp'] = $subval;
                    }
                    if ($subkey == "quantity") {
                        $lazadaVariations[$counter]['stock'] = $subval;
                    }
                    if ($subkey == "SellerSku") {
                        $lazadaVariations[$counter]['sku'] = $subval;
                        if(isset($variationData) && count($variationData)>0){
                            $index = array_search($subval, array_column($variationData["create"], 'sku'));
                            if(!$index)
                                $index = 0;
                            $lazadaVariations[$counter]['images'] = $variationData["create"][$index]["images"];
                        }

                    }
                    if ($subkey == "Images") {
                        if(!isset($lazadaVariations[$counter]['images']) && count($subval)>0){
                            foreach ($subval as $key){
                                $lazadaVariations[$counter]['images'][] = $key;
                            }
                        }
                    }
                }
                $counter++;
            }
            $refine['p360']['variations'] = $lazadaVariations;
        }
        $refine['save_response'] = $jsonResponse;
//        echo '<pre>';
//        print_r($refine);
//        exit();
        return $refine;
    }
    // Shopee
    //get shopee shop category
    public static function getShopeCategory($sid, $selected = '')
    {
        $pcat = Product360MarketplaceCategories::findOne(['marketplace' => 'shopee']);
        if (isset($pcat->category_api_resp))
            $response = json_decode($pcat->category_api_resp, true);
        if (isset($response['category'])) {

            self::_recursiveShopeCategoryTree($response['category'], '', $selected);
        }
    }

    private static function _recursiveShopeCategoryTree($level, $sep = '', $selected)
    {
        $catename = self::refineShopeeCat($level);
        foreach ($level as $l) {

            if (isset($l['has_children']) && $l['has_children'] < 1 && isset($catename[$l['parent_id']])) {
                $select = ($l['category_id'] == trim($selected)) ? 'selected' : '';
                echo '<option ' . $select . ' value="' . $l['category_id'] . '">' . $catename[$l['parent_id']] . '->' . $l['category_name'] . '</option>';
            }
        }
    }

    private static function refineShopeeCat($level)
    {
        $categoryList = [];
        $previous['cid'] = 0;
        $previous['cname'] = "";
        foreach ($level as $l) {
            if ($l['parent_id'] == 0 && $l['has_children'] == 1) {
                $categoryList[$l['category_id']] = $l['category_name'];
                $previous['cid'] = $l['category_id'];
                $previous['cname'] = $l['category_name'];
            }
            if ($l['parent_id'] != 0 && $l['has_children'] == 1 && $previous['cid'] == $l['parent_id']) {
                $categoryList[$l['category_id']] = $previous['cname'] . "->" . $l['category_name'];
            }
        }
        return $categoryList;
    }
    public static function getPrestaProductItem($item, $shop_id, $pinfo){
        
    }
    public static function getShopeeProductItem($item, $shop_id, $pinfo)
    {
        $ch = Channels::findOne(['id' => $shop_id,'is_active'=>'1']);
        $apiKey = $ch->api_key;
        $apiUser = explode('|', $ch->api_user);
        $now = new  \DateTime();
        $url = "https://partner.shopeemobile.com/api/v1/item/get";
        $postFields = [
            'item_id' => (int) $item,
            'partner_id' => (int)$apiUser[0],
            'shopid' => (int)$apiUser[1],
            'timestamp' => $now->getTimestamp(),
        ];
        $postFields = json_encode($postFields);
        $postFields = str_replace("\/", "/", $postFields);
        $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
        $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
        $jsonResponse = $response;
        $response = json_decode($response, true);


        /*local variation images*/
        $lzdFormSkus = Product360Util::getLazadaVariationSkus($pinfo,"");
        $variationsSkus = Product360Util::RefineLzdVariation($lzdFormSkus,'');
        $variationData = [];
        if(isset($pinfo['p360']['variations'])) {
            $onlyVariations = [];
            foreach ($pinfo['p360']['variations'] as $val) {
                $onlyVariations[] = $val;
            }
            $variationData = Product360Util::makeLzdVariationSeperate($onlyVariations, $variationsSkus);
        }
        /*local variation images*/

        if(!array_key_exists('item', $response))
            return false;

        $refine = [];
        $refine['p360']['shope_category'] = $response['item']['category_id'];
        $refine['p360']['common_attributes']['product_name'] = $response['item']['name'];
        $refine['p360']['common_attributes']['product_sku'] = $response['item']['item_sku'];
        foreach ($response['item']['attributes'] as $attr)
        {
            $refine['attr'][] = $attr;
        }
        $refine['p360']['common_attributes']['product_short_description'] = $response['item']['description'];
        $refine['p360']['common_attributes']['product_price'] = $response['item']['original_price'];
        $refine['p360']['common_attributes']['product_cprice'] = $response['item']['original_price']-1;
        $refine['p360']['common_attributes']['product_qty'] = $response['item']['stock'];
        $refine['p360']['common_attributes']['product_color'] = "not in use for shopee";
        //$refine['p360']['shopee_variation_id'] = $response['item']['variations'][0]['variation_id'];
        $refine['p360']['common_attributes']['package_height'] = $response['item']['package_height'];
        $refine['p360']['common_attributes']['package_length'] = $response['item']['package_length'];
        $refine['p360']['common_attributes']['package_width'] = $response['item']['package_width'];
        $refine['p360']['common_attributes']['package_weight'] = $response['item']['weight'];

        $refine['p360']['shopee_attributes']['shpe_logistics'] = $response['item']['logistics'][0]['logistic_id'];
        foreach ( $response['item']['attributes'] as $attr_values ){
            $refine['p360']['shopee_attributes'][] = $attr_values['attribute_id'].'-'.$attr_values['attribute_value'];
        }
        $refine['p360']['shop'] = [$ch->prefix];
        $refine['p360']['sys_category'] = $pinfo['p360']['sys_category'];
        $refine['p360']['shopee_item_id'] = (int)$item;
        $refine['uqid'] = $pinfo['uqid'];
        if(isset($response['item']['variations'])) {
            $lazadaVariations = [];
            $counter = 0;
            foreach ($response['item']['variations'] as $key => $val) {
                foreach ($val as $subkey => $subval) {
                    if ($subkey == "name") {
                        $lazadaVariations[$counter]['type']['Color'] = $subval;
                    }
                    if ($subkey == "price") {
                        $lazadaVariations[$counter]['price'] = $subval;
                        $lazadaVariations[$counter]['rccp'] = $subval-1;
                    }
                    if ($subkey == "stock") {
                        $lazadaVariations[$counter]['stock'] = $subval;
                    }
                    if ($subkey == "variation_sku") {
                        $lazadaVariations[$counter]['sku'] = $subval;
                        if(isset($variationData) && count($variationData)>0){
                            $index = array_search($subval, array_column($variationData["create"], 'sku'));
                            if(!$index)
                                $index = 0;
                            $lazadaVariations[$counter]['images'] = $variationData["create"][$index]["images"];
                        }

                    }
                }
                $counter++;
            }
            $refine['shopee_variations'] = $lazadaVariations;
            //$refine['shopee_variations'] = $response['item']['variations'];
            $refine['save_response'] = $jsonResponse;
        }
        return $refine;
    }

    // call create product Shopee API

    public static function callShopeeCreateProduct($data, $sid, $itemId, $isUpdate)
    {
        //$isUpdate = false;
        $v = Channels::find()->where(['id' => $sid,'is_active'=>'1'])->one();
        $apiKey = $v->api_key;
        $apiUser = explode('|', $v->api_user);
        $now = new  \DateTime();

        // fetch images for shopee item
        $imgList = array();
        if(!$isUpdate){
            /*Images upload*/
            foreach ($data['images'] as $img) {
                array_push($imgList, array('url' => (string)Yii::$app->params['web_url'].'/product_images/'.$img));
            }
//            array_push($imgList, array('url' => (string)'https://assets.pernod-ricard.com/nz/media_images/test.jpg'));
//            array_push($imgList, array('url' => (string)'https://vastphotos.com/files/uploads/photos/10342/high-resolution-landscape-photo-l.jpg'));
//            array_push($imgList, array('url' => (string)'https://vastphotos.com/files/uploads/photos/10306/high-resolution-mountains-and-lakes-m.jpg'));
        }
        $logistics = array(
            'logistic_id' => (int)$data['info']['p360']['shopee_attributes']['shpe_logistics'],
            'enabled' => true
        );
        unset($data['info']['p360']['shopee_attributes']['shpe_logistics']);
        if (isset($data['info']['p360']['shopee_attributes'])) {
            foreach ($data['info']['p360']['shopee_attributes'] as $attr) {
                if (!is_array($attr)){
                    if(isset($attr) && $attr!=""){
                        $attrs = explode('-', $attr,2);
                        $attributes[] = ['attributes_id' => (int)$attrs[0], 'value' => (string)$attrs[1]];
                    }
                }
            }
        }
        if($isUpdate){
            $postFields = [
                'category_id' => (int)$data['info']['p360']['shope_category'],
                'name' => (string)$data['info']['p360']['common_attributes']['product_name'],
                'description' => (string)$data['info']['p360']['common_attributes']['product_short_description'],
                'price' => (float)$data['info']['p360']['common_attributes']['product_price'],
                'stock' => (int)$data['info']['p360']['common_attributes']['product_qty'],
                'item_sku' => (string)$data['info']['p360']['common_attributes']['product_sku'],
                'attributes' => $attributes,
                'weight' => (float)$data['info']['p360']['common_attributes']['package_weight'],
                'package_length' => (int)$data['info']['p360']['common_attributes']['package_length'],
                'package_width' => (int)$data['info']['p360']['common_attributes']['package_width'],
                'package_height' => (int)$data['info']['p360']['common_attributes']['package_height'],
                'logistics' => [$logistics],
                'partner_id' => (int)$apiUser[0],
                'shopid' => (int)$apiUser[1],
                'timestamp' => $now->getTimestamp(),
            ];
        }
        else
        {
            $postFields = [
                'category_id' => (int)$data['info']['p360']['shope_category'],
                'name' => (string)$data['info']['p360']['common_attributes']['product_name'],
                'description' => (string)$data['info']['p360']['common_attributes']['product_short_description'],
                'price' => (float)$data['info']['p360']['common_attributes']['product_price'],
                'stock' => (int)$data['info']['p360']['common_attributes']['product_qty'],
                'item_sku' => (string)$data['info']['p360']['common_attributes']['product_sku'],
                'attributes' => $attributes,
                'images' => (array)$imgList,
                'weight' => (float)$data['info']['p360']['common_attributes']['package_weight'],
                'package_length' => (int)$data['info']['p360']['common_attributes']['package_length'],
                'package_width' => (int)$data['info']['p360']['common_attributes']['package_width'],
                'package_height' => (int)$data['info']['p360']['common_attributes']['package_height'],
                'logistics' => [$logistics],
                'partner_id' => (int)$apiUser[0],
                'shopid' => (int)$apiUser[1],
                'timestamp' => $now->getTimestamp(),
            ];
            /*Add Variation at the time of create product*/
            if (isset($data['info']['p360']['variations'])){
                $variations = self::_refineShopeeVariations($data['info']['p360']['variations']);
                $postFields['variations'] = $variations;
            }
        }
        // Set the item id and URL if we are updating the product that was already created before.
        if ($isUpdate){
            $postFields['item_id']= (int)$itemId;
            $url = "https://partner.shopeemobile.com/api/v1/item/update";
        }else{
            $url = "https://partner.shopeemobile.com/api/v1/item/add";
        }
        $postFields = json_encode($postFields);
        $postFields = str_replace("\/", "/", $postFields);
        $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
        //var_dump($postFields);
        $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
        //var_dump($response);
        $response = json_decode($response, true);

        return $response;
    }

    public static function CallShopeeUpdateImages($sid,$data,$item_id){
        $v = Channels::find()->where(['id' => $sid,'is_active'=>'1'])->one();
        $apiKey = $v->api_key;
        $apiUser = explode('|', $v->api_user);
        $now = new  \DateTime();
        $imgList = array();
        if(array_key_exists('images', $data)){
            foreach ($data['images'] as $img) {
                array_push($imgList, (string)Yii::$app->params['web_url'].'/product_images/'.$img);
            }
//        array_push($imgList, array('url' => (string)'https://homepages.cae.wisc.edu/~ece533/images/arctichare.png'));
//        array_push($imgList, array('url' => (string)'https://vastphotos.com/files/uploads/photos/10342/high-resolution-landscape-photo-l.jpg'));
//        array_push($imgList, array('url' => (string)'https://vastphotos.com/files/uploads/photos/10306/high-resolution-mountains-and-lakes-m.jpg'));

            $url = "https://partner.shopeemobile.com/api/v1/item/img/update";
            $postFields = [
                'item_id' => (int)$item_id,
                'images' => (array)$imgList,
                'partner_id' => (int)$apiUser[0],
                'shopid' => (int)$apiUser[1],
                'timestamp' => $now->getTimestamp(),
            ];
            $postFields = json_encode($postFields);
            $postFields = str_replace("\/", "/", $postFields);
            $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
            $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];
            //var_dump($postFields);
            $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
            $response = json_decode($response, true);
            //var_dump($response);
            return $response;
        }
    }
    public static function RefineShopVariation($form_variation,$shop_variations){
        $f_vals = [];
        $s_vals = [];
        $counter = 1;
        if($form_variation!='') {
            foreach ($form_variation as $value) {
                $f_vals[$counter] = $value['sku'];
                $counter++;
            }
        }
        $counter = 1;
        if($shop_variations!='') {
            foreach ($shop_variations as $value) {
                $s_vals[] = $value['variation_sku'];
                $counter++;
            }
        }
        $variations = [];
        $create = 1;
        $update = 1;
        foreach( $f_vals as $value ){
            if ( !in_array($value,$s_vals) ){
                $variations['create'][$create] = $value;
            }
            // for udpate sku
            else{
                $variations['update'][$update] = $value;
            }
            $create++;
            $update++;
        }
        // for delete
        $delete = 1;
        foreach ( $s_vals as $value ){
            if ( !in_array($value,$f_vals) ){
                $variations['delete'][$delete] = $value;
            }
            $delete++;
        }
        return $variations;
    }
    public static function CallShopeeUpdateVariations($channel_id,$data,$item_id,$p3s_variations){
        $variations = self::RefineShopVariation($data,$p3s_variations);
        $addVarList = [];
        $updateVarList = [];
        $deleteList = [];
        if(isset($variations['create'])){

            foreach ($variations['create'] as $key=>$val){
                $index = array_search($val, array_column($data, 'sku'));
                $addVarList[] = $data[$index+1];
            }
            $addvariations = self::_refineShopeeVariations($addVarList);
            $adddedVar = ShopeeUtil::AddShopeeVariations($channel_id,$addvariations,$item_id);

        }
        if(isset($variations['update'])){
            $counter = 0;
            foreach ($variations['update'] as $key){
                $index = array_search($key, array_column($data, 'sku'));
                $updateVarList[] = $data[$index];
                $varId = array_search($key, array_column($p3s_variations, 'variation_sku'));
                $updateVarList[$counter]['variation_id'] = $p3s_variations[$varId]['variation_id'];
                $updateVarList[$counter]['item_id'] = $item_id;
                $counter++;
            }
            $updatevariationPrice = self::_refineShopeeVariations($updateVarList,'price');
            $updatevariationStock = self::_refineShopeeVariations($updateVarList,'stock');
            $varPrice = ShopeeUtil::UpdateShopeeVariationsPrice($channel_id,$updatevariationPrice);
            $varStock = ShopeeUtil::UpdateShopeeVariationsStock($channel_id,$updatevariationStock);
        }
        if(isset($variations['delete'])){
            foreach ($variations['delete'] as $key){
                $varId = array_search($key, array_column($p3s_variations, 'variation_sku'));
                $deleteList[]['variation_id'] = $p3s_variations[$varId]['variation_id'];
                $adddedVar = ShopeeUtil::DeleteShopeeVariations($channel_id,$p3s_variations[$varId]['variation_id'],$item_id);
            }
        }
    }

    public static function _refineShopeeVariations($data,$updateType=""){
        $refine = [];
        $counter=0;
        foreach ($data as $key => $val) {
            foreach ($val as $subkey => $subval) {
                if($subkey == 'type'){
                    $refine[$counter]["name"] = $subval['Color'];
                }else if ( $subkey=='images' ){
                    continue;
                }
                else{
                    $refine[$counter][$subkey] = $subval;
                }
            }
            $counter++;
        }
        $variations = [];
        foreach ( $refine as $val ){
            if(isset($val['variation_id']) && $updateType=='price'){
                $variations [] = array(
                    'price' => (float)$val['price'],
                    'variation_id' => (int)$val['variation_id'],
                    'item_id' => (int)$val['item_id']
                );
            }else if(isset($val['variation_id']) && $updateType=='stock'){
                $variations [] = array(
                    'stock' => (int)$val['stock'],
                    'variation_id' => (int)$val['variation_id'],
                    'item_id' => (int)$val['item_id']
                );
            }else{
                $variations [] = array(
                    'name' => (string)$val['name'],
                    'stock' => (int)$val['stock'],
                    'price' => (float)$val['price'],
                    'variation_sku' => (String)$val['sku'],
                );
            }
        }
        return $variations;
    }

    // 11 Street
    public static function getStreetCategory($sid, $selected = '')
    {
        $pcat = Product360MarketplaceCategories::findOne(['marketplace' => 'street']);
        $response = json_decode($pcat->category_api_resp, true);
        if (isset($response['category'])) {

            self::_recursiveStreetCategoryTree($response['category'], '', $selected);
        }
    }

    private function _recursiveStreetCategoryTree($level, $sep = '', $selected)
    {
        $catename = self::refineStreetCat($level);
        foreach ($level as $l) {

            if (isset($l['depth']) && $l['depth'] == 3) {
                $select = ($l['dispNo'] == trim($selected)) ? 'selected' : '';
                echo '<option ' . $select . ' value="' . $l['dispNo'] . '">' . $catename[$l['parentDispNo']] . '->' . $l['dispNm'] . '</option>';
            }
        }
    }

    private function refineStreetCat($level)
    {
        $categoryList = [];
        $previous['cid'] = 0;
        $previous['cname'] = "";
        foreach ($level as $l) {
            if ($l['parentDispNo'] == 0 && $l['depth'] < 3) {
                $categoryList[$l['dispNo']] = $l['dispNm'];
                $previous['cid'] = $l['dispNo'];
                $previous['cname'] = $l['dispNm'];
            }
            if ($l['parentDispNo'] != 0 && $l['depth'] < 3 && $previous['cid'] == $l['parentDispNo']) {
                $categoryList[$l['dispNo']] = $previous['cname'] . "->" . $l['dispNm'];
            }
        }
        return $categoryList;
    }

    public static function GetShopeeAttributes($catId)
    {
        $ch = Channels::findOne(['id' => 2,'is_active'=>'1']);
        $apiKey = $ch->api_key;
        $apiUser = explode('|', $ch->api_user);
        $now = new  \DateTime();
        $postFields = [
            'category_id' => (int)$catId,
            'partner_id' => (int)$apiUser[0],
            'shopid' => (int)$apiUser[1],
            'timestamp' => $now->getTimestamp(),
        ];
        //var_dump($postFields);
        $url = "https://partner.shopeemobile.com/api/v1/item/attributes/get";
        $postFields = json_encode($postFields);
        $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];

        $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);


        //var_dump($response);
        //exit();
        $response = json_decode($response, true);

        //self::debug($response);

        return $response;
    }
    public static function debug($data){
        echo '<pre>';
        print_r($data);
        die;
    }

    public static function callLzdCategoryAttributes($catId)
    {
        $ch = Channels::findOne(['id' => 1,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'GET';
        $customParams['action'] = '/category/attributes/get';
        $customParams['params']['primary_category_id'] = $catId;
        $response = ApiController::_callLzdRequestMethod($customParams);
        //var_dump($response);
        $response = json_decode($response, true);
        return $response;
    }
    public static function deleteProductFromLzd($skuList,$shopid)
    {
        $ch = Channels::findOne(['id' => $shopid,'is_active'=>'1']);
        $auth_params = json_decode($ch->auth_params, true);
        $customParams['app_key'] = $ch->api_key;
        $customParams['app_secret'] = $ch->api_user;
        $customParams['access_token'] = $auth_params['access_token'];
        $customParams['method'] = 'POST';
        $customParams['action'] = '/product/remove';
        $customParams['params']['seller_sku_list'] = $skuList;
        //var_dump($customParams);
        $response = ApiController::_callLzdRequestMethod($customParams);
        //var_dump($response);
        $response = json_decode($response, true);
        return $response;
    }
    public static function deleteProductFromShopee($itemId,$shopid)
    {
        $ch = Channels::findOne(['id' => $shopid,'is_active'=>'1']);
        $apiKey = $ch->api_key;
        $apiUser = explode('|', $ch->api_user);
        $now = new  \DateTime();
        $postFields = [
            'item_id' => (int)$itemId,//2243506725,
            'partner_id' => (int)$apiUser[0],
            'shopid' => (int)$apiUser[1],
            'timestamp' => $now->getTimestamp(),
        ];
        //var_dump($postFields);
        $url = "https://partner.shopeemobile.com/api/v1/item/delete";
        $postFields = json_encode($postFields);
        $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];

        $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
        $response = json_decode($response, true);
        return $response;
    }
    public static function UpdateProductStatusFromShopee($itemId,$shopid,$status)
    {
        $listitems[] = array(
            'item_id' => (int)$itemId,
            'unlist' => $status,
        );

        $ch = Channels::findOne(['id' => $shopid,'is_active'=>'1']);
        $apiKey = $ch->api_key;
        $apiUser = explode('|', $ch->api_user);
        $now = new  \DateTime();
        $postFields = [
            'items' => (array)$listitems,
            'partner_id' => (int)$apiUser[0],
            'shopid' => (int)$apiUser[1],
            'timestamp' => $now->getTimestamp(),
        ];
        $url = "https://partner.shopeemobile.com/api/v1/items/unlist";
        $postFields = json_encode($postFields);
        $authKey = hash_hmac('sha256', $url . '|' . $postFields, $apiKey);
        $access = ['Content-Type:application/json', 'Authorization:' . $authKey . ''];

        $response = ApiController::_ApiCall($url, $access, 'POST', "", $postFields);
        $response = json_decode($response, true);
        return $response;
    }

    public static function UpdateStatusValue($productid,$status){
        /*Update main table status*/
        $p3status = Product360Status::find()->where(['product_360_fieldS_id' => $productid])->all();
        $productStatus = $status;
        //var_dump($productid);
        foreach ($p3status as $key) {
            if(($productStatus == "Publish") && ($key->status == "Activated" || $key->status == "DeActivated"))
                $productStatus = "Publish";

            else if(($productStatus == "Pending") && ($key->status == "Pending" || $key->status == "Activating" || $key->status == "DeActivating"))
                $productStatus = "Pending";

            else if(($productStatus == "Fail") && $key->status == "Fail")
                $productStatus = "Fail";

            else if(($productStatus == "Deleted") && $key->status == "Deleted")
                $productStatus = "Deleted";

            else if(($productStatus == "Draft") && $key->status == "Draft")
                $productStatus = "Draft";

            else if(($productStatus == "Publish" || $productStatus == "Partially Publish" || $productStatus == "Draft") && ($key->status == "Pending" || $key->status == "Activating" || $key->status == "DeActivating" || $key->status == "Fail" || $key->status == "Deleted" || $key->status == "Draft"))
                $productStatus = "Partially Publish";

            else if(($productStatus == "Pending") && $key->status == "Fail")
                $productStatus = "Partially Publish";

            else if(($productStatus == "Fail") && $key->status == "Pending")
                $productStatus = "Partially Publish";
            else if(($productStatus == "Partially Fail") && ($key->status == "Pending" || $key->status == "Fail"))
                $productStatus = "Partially Fail";

            //else if(($productStatus == "Pending" || $productStatus == "Partially Fail" || $productStatus == "Deleted") && $key->status == "Fail")
            //    $productStatus = "Partially Fail";
        }
        return $productStatus;
        /*Update main table status*/
    }
    /*
     * It return all skus available on the shops
     * */
    public static function GetSkuList($product_sku){
        // Get all shop where sku exists
        $Sql = "SELECT c.`prefix` FROM `products_360_fields` pf
                INNER JOIN `product_360_status` ps ON
                ps.`product_360_fieldS_id` = pf.`id`
                INNER JOIN `channels` c ON
                c.`id` = ps.`shop_id`
                WHERE pf.`sku` = '".$product_sku."' AND ps.`status` != 'Deleted';";
        $response = Product360Status::findBySql($Sql)->asArray()->all();
        $shops=[];
        foreach ( $response as $val ){
            $shops[] = $val['prefix'];
        }
        return json_encode($shops);
    }
    public static function RemoveDirectories(Array $directories_links){
        $result = [];
        foreach ( $directories_links as $directory ){
            $result[] = FileHelper::removeDirectory($directory);
        }
        return $result;
    }
    public static function SyncProduct360Lazada(){


        $channel_id = $_GET['channel_id'];
        //$Products = LazadaUtil::GetProducts(1,'all',0);
        //$this->debug($Products);

        $GetAllProducts = LazadaUtil::GetProductItem($channel_id,'',$_GET['item_id']);
        //self::debug($GetAllProducts);
        //$this->debug($GetAllProducts);
        //$Products = $Product['data']['products'];
        //$this->debug($GetAllProducts);
        foreach ( $GetAllProducts['data']['skus'] as $p_s_key=>$p_s_detail ) :

            if ($p_s_key==0){
                /*echo '<pre>';
                print_r($p_s_detail);*/
                $Sku = $p_s_detail['SellerSku'];



                // Check already exist in ezcommerce
                $SkuCheck = Products::find()->where(['sku'=>$Sku])->asArray()->all();

                if ( empty($SkuCheck) ) :
                    continue;
                endif;



                // Check in product 360 tables
                $Sku360Field = Products360Fields::find()->where(['product_id'=>$SkuCheck[0]['id']])->asArray()->all();


                // dump product details in products_360_fields table.
                if ( empty($Sku360Field) ) :
                    $AddField = new Products360Fields();
                    $AddField->product_id=$SkuCheck[0]['id'];
                    $AddField->status = 'Publish';
                    $AddField->name = $SkuCheck[0]['name'];
                    $AddField->sku = $SkuCheck[0]['sku'];
                    $AddField->category = $SkuCheck[0]['sub_category'];
                    $AddField->save(false);
                    $Sku360Field[0]['id'] = $AddField->id;
                    //die;
                endif;

                //echo '<pre>';
                //print_r($value);
                $GetAttributes = LazadaUtil::GetCategoryAttributes($channel_id,$GetAllProducts['data']['primary_category']);

                $attr = [];
                $skipAttr = ['name','short_description','model','color_family','SellerSku','quantity','special_from_date','special_from_date',
                    'special_to_date','package_weight','package_length','package_width','package_height','__images__','',''];
                $attrSku = ['package_content'=>'','tax_class'=>'default'];

                foreach ($GetAttributes['data'] as $AttrVal){
                    if ( in_array($AttrVal['name'],$skipAttr) )
                        continue;
                    $attr[$AttrVal['name']] = '';
                }
                foreach ( $GetAllProducts['data']['attributes'] as $k=>$val ){
                    if ( !isset($attr[$k]) )
                        continue;
                    $attr[$k] = $val;
                }



                $CheckStatus = Product360Status::find()->where(['product_360_fieldS_id'=>$Sku360Field[0]['id']])->andWhere(['shop_id'=>$channel_id])->asArray()->all();

                if ( empty( $CheckStatus ) ){
                    $uqid = uniqid();
                    $path = 'product_images/' . $uqid.'/mainimage';
                    FileHelper::createDirectory($path);
                    chmod($path, 0777);
                    $images = [];
                    foreach ($p_s_detail['Images'] as $Images_Links){
                        if ( $Images_Links=="" )
                            continue;
                        $image_parts = explode('/',$Images_Links);
                        $content = file_get_contents($Images_Links);
                        file_put_contents($path.'/'.end($image_parts), $content);
                        $images[] = $uqid.'/mainimage/'.end($image_parts);
                    }


                    $request = [
                        'uqid' => $uqid,
                        'p360' => [
                            'lzd_attributes' => [ 'normal' => $attr, 'sku' => $attrSku ],
                            'sys_category' => $SkuCheck[0]['sub_category'],
                            'lzd_category' => $GetAllProducts['data']['primary_category']
                        ]
                    ];
                    $AddStatus = new Product360Status();
                    $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];
                    $AddStatus->status = ( $p_s_detail['Status']=='active' ) ? 'Success' : 'DeActivated';
                    $AddStatus->shop_id = $channel_id;
                    $AddStatus->show = 1;
                    $AddStatus->api_response = json_encode($GetAllProducts);
                    $AddStatus->api_request = json_encode($request);
                    $AddStatus->item_id = $GetAllProducts['data']['item_id'];
                    $AddStatus->images = implode(',',$images);
                    $AddStatus->save();
                    if (!empty($AddStatus->errors))
                        self::debug($AddStatus->errors);

                }else{
                    // remove all directores first
                    $uqid = json_decode($CheckStatus[0]['api_request'],true)['uqid'];
                    $AlreadyExsitDirectories=FileHelper::findDirectories($path = 'product_images/' . $uqid.'');
                    self::RemoveDirectories($AlreadyExsitDirectories);

                    $path = 'product_images/' . $uqid.'/mainimage';
                    FileHelper::createDirectory($path);
                    chmod($path, 0777);
                    $images = [];
                    foreach ($p_s_detail['Images'] as $Images_Links){
                        if ( $Images_Links=="" )
                            continue;
                        $image_parts = explode('/',$Images_Links);
                        $content = file_get_contents($Images_Links);
                        file_put_contents($path.'/'.end($image_parts), $content);
                        $images[] = $uqid.'/mainimage/'.end($image_parts);
                    }


                    $request = [
                        'uqid' => $uqid,
                        'p360' => [
                            'lzd_attributes' => [ 'normal' => $attr, 'sku' => $attrSku ],
                            'sys_category' => $SkuCheck[0]['sub_category'],
                            'lzd_category' => $GetAllProducts['data']['primary_category']
                        ]
                    ];
                    $AddStatus = Product360Status::findOne($CheckStatus[0]['id']);
                    $AddStatus->product_360_fieldS_id = $Sku360Field[0]['id'];
                    $AddStatus->status = ( $p_s_detail['Status']=='active' ) ? 'Success' : 'DeActivated';
                    $AddStatus->shop_id = $channel_id;
                    $AddStatus->show = 1;
                    $AddStatus->api_response = json_encode($GetAllProducts);
                    $AddStatus->api_request = json_encode($request);
                    $AddStatus->item_id = $GetAllProducts['data']['item_id'];
                    $AddStatus->images = implode(',',$images);
                    $AddStatus->save();
                    if (!empty($AddStatus->errors))
                        self::debug($AddStatus->errors);
                }


            }
            else{
                //$this->debug($AddStatus);die;
                if (!isset($AddStatus))
                    continue;

                $getStatusRow = Product360Status::find()->where(['id'=>$AddStatus['id']])->asArray()->all();

                $Request_Data = $getStatusRow[0]['api_request'];
                $Request_Data = json_decode($Request_Data,true);
                //echo $Request_Data['uqid'];die;

                $path = 'product_images/' . $Request_Data['uqid'].'/variation-'.$p_s_key;
                FileHelper::createDirectory($path);
                chmod($path, 0777);
                $images = [];
                foreach ($p_s_detail['Images'] as $Images_Links){
                    if ( $Images_Links=="" )
                        continue;
                    $image_parts = explode('/',$Images_Links);
                    $content = file_get_contents($Images_Links);
                    file_put_contents($path.'/'.end($image_parts), $content);
                    $images[] = $uqid.'/variation-'.$p_s_key.'/'.end($image_parts);
                }
                //print_r($Request_Data);
                //print_r($p_s_detail);
                //print_r($images);

                $Request_Data['p360']['variations'][$p_s_key]['type']['Color'] = $p_s_detail['color_family'];
                $Request_Data['p360']['variations'][$p_s_key]['price'] = $p_s_detail['price'];
                $Request_Data['p360']['variations'][$p_s_key]['rccp'] = $p_s_detail['special_price'];
                $Request_Data['p360']['variations'][$p_s_key]['stock'] = $p_s_detail['Available'];
                $Request_Data['p360']['variations'][$p_s_key]['sku'] = $p_s_detail['SellerSku'];
                $Request_Data['p360']['variations'][$p_s_key]['images'] = $images;

                $UpdateStatus = Product360Status::findOne($getStatusRow[0]['id']);
                $UpdateStatus->api_request = json_encode($Request_Data);
                $UpdateStatus->update();

                //$this->debug($Request_Data);
            }
            //echo '<br />';
            //echo '-----------------xxxxxxxxxxxxxxxxxxxxx--------------------xxxxxxxxxxxxxxxxxxxxxxxxxxx------------------------';
            //echo '<br />';


        endforeach;
        //die;
    }
    public static function SyncProduct360Shopee(){

    }

    public  static function getProductDimensionsAndWeight($sku)
    {
        $p3f=Products360Fields::find()->select('product_info')->where(['sku'=>$sku])->scalar();
        if($p3f)
        {
            $json=json_decode($p3f);
            return [
                 'weight'=> isset($json->p360->common_attributes->package_weight) ? $json->p360->common_attributes->package_weight:0,
                 'length'=> isset($json->p360->common_attributes->package_length) ? $json->p360->common_attributes->package_length:0,
                 'width'=> isset($json->p360->common_attributes->package_width) ? $json->p360->common_attributes->package_width:0,
                 'height'=> isset($json->p360->common_attributes->package_height) ? $json->p360->common_attributes->package_height:0,
            ];
        }

    }
}