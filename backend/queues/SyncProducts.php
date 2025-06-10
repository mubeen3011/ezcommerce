<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 3/3/2019
 * Time: 7:42 AM
 */


namespace backend\queues;

use backend\util\Amazon360Util;
use backend\util\PrestashopUtil;
use backend\util\Product360Util;
use common\models\Product360Status;
use common\models\Products360Fields;
use yii\base\BaseObject;

class SyncProducts extends BaseObject implements \yii\queue\JobInterface
{
    public $productId;
    public $statusId;
    public $isUpdate = false;

    public function execute($queue)
    {

        $p3f = Products360Fields::find()->where(['id' => $this->productId])->one();
        $p3s = Product360Status::find()->where(['id' => $this->statusId])->one();
        if($p3s->shop->marketplace == 'amazon')
        {
            Amazon360Util::ceate_product_process($this->productId,$this->statusId,$this->isUpdate);
            return;
        }
        if ($p3s->shop->marketplace == 'lazada') {
            $info = json_decode($p3s->api_request, true);
            $data = ['info' => $info, 'images' => explode(',', $p3s->images)];

            /*Start Work for managing sku variations in one product*/
            $lzdFormSkus = Product360Util::getLazadaVariationSkus($data['info'],"");
            $shopResponseData = json_decode($p3s->api_response,true);
            $lzdShopSkus = Product360Util::getLazadaVariationSkus($shopResponseData,$data['info']['p360']['common_attributes']['product_sku']);
            $variationsSkus = Product360Util::RefineLzdVariation($lzdFormSkus,$lzdShopSkus);
            $variationData = [];
            if(isset($data['info']['p360']['variations'])) {
                $onlyVariations = [];
                foreach ($data['info']['p360']['variations'] as $val) {
                    $onlyVariations[] = $val;
                }
                $variationData = Product360Util::makeLzdVariationSeperate($onlyVariations, $variationsSkus);
            }
            $status = '';
            if(isset($data['info']['p360']['variations']) && $this->isUpdate) {
                if(isset($variationData['create'])){
                    $newpayload = Product360Util::genLzdXmlForAssociatedSku($data, $p3s->status,$variationData);
                    $status = Product360Util::callLzdCreateProduct($newpayload, $p3s->shop_id, false);
                    if (array_key_exists('data',$status) || (array_key_exists('code',$status) && $status['code'] == "0" )) {
                        if(isset($variationData['update'])){
                            $payload = Product360Util::genLzdXmlFile($data, $p3s->status, $variationData, $this->isUpdate);
                            $status = Product360Util::callLzdCreateProduct($payload, $p3s->shop_id, $this->isUpdate);
                            if (array_key_exists('data',$status) || (array_key_exists('code',$status) && $status['code'] == "0" )) {
                                if(isset($variationData['delete'])){
                                    $status = Product360Util::deleteProductFromLzd(json_encode($variationData['delete'],true),$p3s->shop_id);
                                }
                            }
                        }
                        else if(isset($variationData['delete'])){
                            $status = Product360Util::deleteProductFromLzd(json_encode($variationData['delete'],true),$p3s->shop_id);
                        }
                    }
                }else if(isset($variationData['update'])){
                    $payload = Product360Util::genLzdXmlFile($data, $p3s->status, $variationData, $this->isUpdate);
                    $status = Product360Util::callLzdCreateProduct($payload, $p3s->shop_id, $this->isUpdate);
                    if (array_key_exists('data',$status) || (array_key_exists('code',$status) && $status['code'] == "0" )) {
                        if(isset($variationData['delete'])){
                            $status = Product360Util::deleteProductFromLzd(json_encode($variationData['delete'],true),$p3s->shop_id);
                        }
                    }
                }else if(isset($variationData['delete'])){
                    $status = Product360Util::deleteProductFromLzd(json_encode($variationData['delete'],true),$p3s->shop_id);
                }
            }
            else {
                $payload = Product360Util::genLzdXmlFile($data, $p3s->status,$variationData,$this->isUpdate);
                $status = Product360Util::callLzdCreateProduct($payload, $p3s->shop_id, $this->isUpdate);
            }
            /*End Work for managing sku variations in one product*/
            if (array_key_exists('data',$status) || (array_key_exists('code',$status) && $status['code'] == "0" )) {
                //upload images to lazada
                $mainSkuImages = explode(',', $p3s->images);
                $mainSku = $data['info']['p360']['common_attributes']['product_sku'];
                $imgResponse = Product360Util::uploadLzdImageswithVariation($variationData,$mainSku,$mainSkuImages,$p3s->shop_id);
                // save api response to db and update status
                if (array_key_exists('data',$status))
                    $p3s->item_id = $status['data']['item_id'];

                if($p3s->status == 'Activating' || $p3s->status == 'Pending'){
                    $p3s->status = 'Activated';
                }
                if($p3s->status == 'DeActivating'){
                    $p3s->status = 'DeActivated';
                }

                $p3s->fail_reason = '';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
                $p3f->status = 'Publish';
                $p3f->save();
            } else {
                $p3s->status = 'Fail';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);

                /*failure message*/
                $failureReason = '';
                if(array_key_exists('detail',$status)){
                    foreach ($status['detail'] as $detail) {
                        $failureReason .= $detail['field'] . ': ' . str_replace(array("_"),array(" "),$detail['message']) . '. ';
                    }
                }
                else if(array_key_exists('message',$status)){
                    $failureReason .= $status["message"];
                }
                else{
                    $failureReason .= json_encode($status);
                }
                /*failure message*/

                $p3s->fail_reason = $failureReason;
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
            }
        }
        if ($p3s->shop->marketplace == 'shopee') {
            $info = json_decode($p3s->api_request, true);
            $data = ['info' => $info, 'images' => explode(',', $p3s->images)];

            if($this->isUpdate){
                /*Update Product Images*/
                $imgResponse = Product360Util::CallShopeeUpdateImages($p3s->shop_id, $data, $p3s->item_id);
                /*Update Product Variations*/
                $alreadyExistVar = json_decode($p3s->api_response,true);
                /*echo '<pre>';var_dump($alreadyExistVar);
                echo '-------------------------------------------------- data --------------------------';
                echo '<pre>';var_dump($data);
                die;*/
                $alreadyVar[] ='';
                if ( isset($data['info']['p360']['variations']) && $alreadyExistVar['item']['has_variation']==true){
                    $alreadyVar = $alreadyExistVar['item']['variations'];
                    $varResponse = Product360Util::CallShopeeUpdateVariations($p3s->shop_id, $data['info']['p360']['variations'], $p3s->item_id,$alreadyVar);
                }
                else if (isset($data['info']['p360']['variations']) && $alreadyExistVar['item']['has_variation']==false){
                    $varResponse = Product360Util::CallShopeeUpdateVariations($p3s->shop_id, $data['info']['p360']['variations'], $p3s->item_id,"");
                }
                else if ( !isset($data['info']['p360']['variations']) && $alreadyExistVar['item']['has_variation']==true ){
                    $alreadyVar = $alreadyExistVar['item']['variations'];
                    $varResponse = Product360Util::CallShopeeUpdateVariations($p3s->shop_id, "", $p3s->item_id,$alreadyVar);
                }
            }
            $status = Product360Util::callShopeeCreateProduct($data, $p3s->shop_id, $p3s->item_id, $this->isUpdate);

            if (isset($status['item_id'])) {
                $p3s->item_id = $status['item_id'];
                $p3s->status = 'Success';
                $p3s->fail_reason = '';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
                $p3f->status = 'Publish';
                $p3f->save();
            } else {
                $p3s->status = 'Fail';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);
                $p3s->fail_reason = $status['msg'];
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
            }
        }
        if ($p3s->shop->marketplace == 'prestashop') {
            $info = json_decode($p3s->api_request, true);
            $data = ['info' => $info, 'images' => explode(',', $p3s->images)];
            $status = PrestashopUtil::AddNewProductOnPrestashop($p3s,$data,$this->isUpdate);

            if (isset($status)) {
                $p3s->item_id = $status->products->product->id;
                $p3s->status = 'Success';
                $p3s->fail_reason = '';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
                $p3f->status = 'Publish';
                $p3f->save();
            } else {
                $p3s->status = 'Fail';
                if (!$this->isUpdate)
                    $p3s->api_response = json_encode($status);
                $p3s->fail_reason = $status['msg'];
                if (!$p3s->save())
                    var_dump($p3s->getErrors());
            }
        }
        $selectedstatus = Product360Util::UpdateStatusValue($p3f->id, $p3f->status);
        $p3f->status = $selectedstatus;
        $p3f->save();
    }
}