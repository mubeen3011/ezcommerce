<?php
namespace backend\util;
use Yii;
/*use Codeception\Module\Yii1;
use common\models\Channels;
use yii\db\Exception;
use yii\web\Controller;
use backend\util\OrderUtil;*/

use common\models\ChannelsDetails;
use common\models\Products;
use common\models\ChannelsProducts;
use common\models\Category;
class CatalogUtil
{
        public static function getdefaultParentCategory()
        {
            return Category::find()->where(['is_active'=>1,'parent_id'=>NULL])->one();
        }

        public static function saveCategories($record=null)
        {

            if($record)
            {
                $category_exists=Category::findone(['name'=>$record['cat_name'],'parent_id'=>$record['parent_cat_id']]);
                if($category_exists)
                {

                    if($category_exists->parent_id==NULL && isset($record['channel'])) // only parent category to add in channel commission table
                    {
                       // below funtion will check if category added in channel details for commision and shipping charges
                       self::check_channel_details(array('cat_id'=>$category_exists->id,'channel_id'=>$record['channel']['id']));

                    }
                    $category_exists->is_active = isset($record['is_active']) ? $record['is_active']:0 ;
                    $category_exists->magento_cat_id = isset($record['magento_cat_id']) ? $record['magento_cat_id']:0;
                    $category_exists->parent_id = (isset($record['parent_cat_id'])  && $record['parent_cat_id']) ? $record['parent_cat_id'] :NULL;
                    $category_exists->update(false);
                    return $category_exists;

                }
                else
                {

                    $add = new Category();
                    $add->name = $record['cat_name'];
                    $add->is_active = isset($record['is_active']) ? $record['is_active']:0 ;
                    $add->magento_cat_id = isset($record['magento_cat_id']) ? $record['magento_cat_id']:0;
                    $add->parent_id = (isset($record['parent_cat_id'])  && $record['parent_cat_id']) ? $record['parent_cat_id'] :NULL;
                    $add->created_at = time();
                    $add->updated_at = time();
                    if($add->save(false))
                    {
                        if( $record['parent_cat_id']==0 && isset($record['channel']))
                        {
                            // below funtion will check if category added in channel details for commision and shipping charges
                            self::check_channel_details(array('cat_id'=>$add->id,'channel_id'=>$record['channel']['id']));
                        }
                        return $add;
                    }

                        return false;
                }

            }
            return false;
        }

        private static function check_channel_details($record) // for commission category have to be save in channel_details table
        {
            $commission_record=ChannelsDetails::findone(['channel_id'=>$record['channel_id'],'category_id'=>$record['cat_id']]);
            if(!$commission_record)
            {
                $commission_record= new ChannelsDetails();
                $commission_record->category_id=$record['cat_id'];
                $commission_record->channel_id=$record['channel_id'];
                $commission_record->commission=0;
                $commission_record->shipping=0;
                $commission_record->save(false);
            }
            return;
        }


        public static function saveProduct($record=null)
        {

            if($record)
            {
                $product_exists = Products::findone(['sku'=>$record['channel_sku']]);

                if($product_exists)
                {
                    $product_exists->name=$record['name'];
                    if(isset($record['marketplace']) && $record['marketplace']=="magento"){}else {
                        $product_exists->parent_sku_id = (isset($record['parent_sku_id']) && $record['parent_sku_id'] != 0) ? $record['parent_sku_id'] : NULL;
                    }

                    if ( isset($record['ean']) && !empty($record['ean']))
                        $product_exists->ean = $record['ean'] ?  $record['ean']:NULL;

                    if(isset($record['dimensions_magento']))  // incase of magento
                        $product_exists->dimensions=$record['dimensions_magento'];

                    if(isset($record['brand']) && $record['brand'])
                        $product_exists->brand= $record['brand'];

                    if(isset($record['image']) && $record['image'] && $record['image']!='')
                        $product_exists->image= $record['image'];

                   // $product_exists->sub_category = (isset($record['category_id']) && $record['category_id'])? $record['category_id']:null;  //optional
                    //$product_exists->ean=(isset($record['ean']) && !empty($record['ean'])) ? $record['ean']:0;
                    // $product_exists->fulfilled_by=isset($record['fulfilled_by']) ? $record['fulfilled_by']:null ; // for amazon to check fulfilled by amazon or client
                 //   $product_exists->image = isset($record['image'])? $record['image']:NULL;
                    $product_exists->cost = $record['cost'];
                    $product_exists->rccp = isset($record['rccp']) ? $record['rccp']:$record['cost']; //optional
                    $product_exists->created_at=time();
                    $product_exists->updated_at = time();
                    $product_exists->deleted = self::is_product_deleted($record['channel_sku']);
                    $product_exists->update(false);
                    return $product_exists->id;
                }
                else
                {

                    $add = new Products();
                    $add->sku = $record['channel_sku'];
                    if(isset($record['marketplace']) && $record['marketplace']=="magento"){}else{
                      $add->parent_sku_id=(isset($record['parent_sku_id']) && $record['parent_sku_id']!=0) ? $record['parent_sku_id']:NULL ;
                    }
                    if ( isset($record['ean']) && !empty($record['ean']))
                        $add->ean = $record['ean'] ?  $record['ean']:NULL;

                    $add->name = $record['name'];
                    $add->image = isset($record['image'])? $record['image']:NULL;
                    $add->cost = $record['cost'];
                  //  $add->ean=(isset($record['ean']) && !empty($record['ean'])) ? $record['ean']:0;
                    $add->channel_id = isset($record['channel_id']) ? $record['channel_id']:"";  // optional
                    $add->rccp = isset($record['rccp']) ? $record['rccp']:$record['cost']; //optional
                  //  $add->sub_category = (isset($record['category_id']) && $record['category_id'])? $record['category_id']:null;  //optional
                   // $add->fulfilled_by=isset($record['fulfilled_by']) ? $record['fulfilled_by']:null ; // mostly for amazon
                    if(isset($record['dimensions_magento']))  // incase of magento
                        $add->dimensions=$record['dimensions_magento'];

                    if(isset($record['brand']) &&  $record['brand'])
                        $add->brand= $record['brand'];

                    $add->created_at=time();
                    $add->updated_at = time();
                    $add->created_by = 1;
                    $add->updated_by = 1;
                   if($add->save(false))
                        return $add->id;
                   else
                        return false;

                }
            }
            return false;

        }
            //****** if child channel products is deleted so parents product also delete ******//
        public static function is_product_deleted($sku = null){

            $sql = "SELECT deleted FROM channels_products WHERE channel_sku = '".$sku."' ORDER BY channel_sku";
            $rows=Yii::$app->db->createCommand($sql)->queryAll();

            $count_is_deleted = 0;
            if($rows){
                 $count_row = count($rows); //get all channel products count

                 foreach ($rows as $row){
                     $count_is_deleted += $row['deleted']; // channel products is_deleted count
                 }
                //if both are same count so product will be deleted
                if($count_row == $count_is_deleted){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }

        }

        public static function savechannelProduct($record=null)
        {
            if($record)
            {
                $product_exists = ChannelsProducts::findone(['channel_id'=>$record['channel_id'],'channel_sku'=>$record['channel_sku'],'product_id'=>$record['product_id']]);
                if($product_exists)
                {
                     $product_exists->stock_qty=$record['stock_qty'];
                     if(isset($record['sku']) && $record['sku'] )
                         $product_exists->sku=$record['sku'];

                    if(isset($record['discounted_price'])){
                        $product_exists->discounted_price=$record['discounted_price'] ? $record['discounted_price']:NULL ;
                    }

                    if (isset($record['asin']))
                        $product_exists->asin = $record['asin'] ?  $record['asin']:NULL;


                    if(isset($record['discount_from_date']))
                        $product_exists->discounted_price_from=$record['discount_from_date'] ? $record['discount_from_date']:NULL;

                    if(isset($record['discount_to_date']))
                        $product_exists->discounted_price_to=$record['discount_to_date'] ? $record['discount_to_date']:NULL;

                     $product_exists->price=$record['cost'];
                     $product_exists->variation_id = (isset($record['variation_id']) && $record['variation_id']) ? $record['variation_id'] :NULL ;
                     $product_exists->ean=(isset($record['ean']) && !empty($record['ean'])) ? $record['ean']:NULL;
                     $product_exists->product_name = $record['name'];
                     $product_exists->is_live = isset($record['is_live']) ? $record['is_live'] :1;
                     $product_exists->deleted=0;
                     $product_exists->fulfilled_by=isset($record['fulfilled_by']) ? $record['fulfilled_by']:null ; // mostly for amazon
                     $product_exists->save(false);
                    return $product_exists;
                }
                else
                {
                    $add = new ChannelsProducts();
                    $add->sku = isset($record['sku']) ? $record['sku']:"";
                    $add->variation_id = (isset($record['variation_id']) && $record['variation_id']) ? $record['variation_id'] :NULL ;
                    $add->ean=(isset($record['ean']) && !empty($record['ean'])) ? $record['ean']:NULL;
                    $add->channel_sku = $record['channel_sku'];
                    $add->channel_id = $record['channel_id'];
                    $add->product_id = $record['product_id'];
                    $add->price = $record['cost'];
                    if(isset($record['discounted_price']))
                        $add->discounted_price=$record['discounted_price'] ? $record['discounted_price']:NULL;

                    if(isset($record['dicount_from_date']))
                        $add->discounted_price_from=$record['discount_from_date'] ? $record['discount_from_date']:NULL;

                    if(isset($record['discount_to_date']))
                        $add->discounted_price_to=$record['discount_to_date'] ? $record['discount_to_date']:NULL;

                    if (isset($record['asin']))
                        $add->asin = $record['asin'] ?  $record['asin']:NULL;

                   // $Stock = $ProductDetail->quantity;
                    $add->stock_qty = $record['stock_qty'];
                    $add->last_update = date('Y-m-d H:i:s');
                    $add->product_name = $record['name'];
                    $add->is_live = isset($record['is_live']) ? $record['is_live'] :1;
                    $add->deleted=0;
                    $add->fulfilled_by=isset($record['fulfilled_by']) ? $record['fulfilled_by']:null ; // mostly for amazon
                    if($add->save(false))
                        return $add;
                    else
                        return $add;
                }
            }
            return false;
        }

        // stock fetched from shop/marketplace and update it in channel product
        public static function save_shop_stock($stock,$channel_id) // stock array =>['sku'=>stock]
        {
            foreach ($stock as $sku=>$stock) {
                if(!$stock){
                    continue;
                }
                Yii::$app->db->createCommand()
                    ->update('channels_products', ['stock_qty' =>$stock], ['channel_id'=>$channel_id,'channel_sku'=>$sku])
                    ->execute();
            }
            return;
        }

        /******
         * get parent of category
         */

        public static function get_category_parent($cat_id)
        {
            $categories=Category::find()->asArray()->all();
            if ($categories) {
               return self::loop_to_parent($categories,$cat_id);
            }
        }

    public static function loop_to_parent(array $elements, $cat_id = 0,&$result=[])
    {
        foreach ($elements as $element) {
            if ($element['id'] == $cat_id) {
                if(empty($element['parent_id']))
                    return $result=$element;
                else
                     self::loop_to_parent($elements, $element['parent_id'],$result);

            }
        }
        return $result;
    }


}
