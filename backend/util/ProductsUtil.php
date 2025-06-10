<?php
namespace backend\util;
use common\models\Channels;
use common\models\CsvStockUpload;
use common\models\Products;
use common\models\Products360Fields;
use common\models\search\OrderItemsSearch;
use Yii;

class ProductsUtil
{
    private static $status_updated = "updated";
    private static $status_inserted = "inserted";
    private static $status_error = "error";
    private static function product_list_flter()
    {
        $cond = " WHERE 1=1 ";
        if (isset($_GET['sku']) AND !empty($_GET['sku'])){
            $searched_skus = '"'.implode('","',explode(',',$_GET['sku'])).'"'; // if multiple
            $cond .= "  AND p.sku IN ($searched_skus)";
        }

        if (isset($_GET['name']) AND !empty($_GET['name'])) {
            $name=$_GET['name'];
            $cond .= "  AND `p`.`name`='$name' ";
        }
        if (isset($_GET['barcode']) AND !empty($_GET['barcode'])) {
            $barcode=$_GET['barcode'];
            $cond .= "  AND `p`.`barcode`='$barcode' ";
        }
        if (isset($_GET['brand']) AND !empty($_GET['brand'])) {
            $brand=$_GET['brand'];
            $cond .= "  AND `p`.`brand`='$brand' ";
        }
        if (isset($_GET['style']) AND !empty($_GET['style'])) {
            $style=$_GET['style'];
            $cond .= "  AND `p`.`style`='$style' ";
        }

        if (isset($_GET['cost_price']) AND !empty($_GET['cost_price'])) {
            $cost=$_GET['cost_price'];
            $cond .= "  AND `p`.`cost`='$cost' ";
        }

        if (isset($_GET['rccp']) AND !empty($_GET['rccp'])) {
            $value=$_GET['rccp'];
            $cond .= "  AND `p`.`rccp`='$value' ";
        }

        if (isset($_GET['extra_cost']) AND !empty($_GET['extra_cost'])) {
            $value=$_GET['extra_cost'];
            $cond .= "  AND `p`.`extra_cost`='$value' ";
        }

        if (isset($_GET['promo_price']) AND !empty($_GET['promo_price'])) {
            $value=$_GET['promo_price'];
            $cond .= "  AND `p`.`promo_price`='$value' ";
        }

        if (isset($_GET['cat']) AND $_GET['cat']!=="") {
            $cat_id=$_GET['cat'];
            $child_list= HelpUtil::GetAllChildCategories($cat_id);
            if($child_list){
                array_push($child_list,$cat_id);
                $ids=implode($child_list,',');
            }else{
                $ids=$cat_id;
            }

            $cond .= "  AND `pc`.`cat_id` IN (".$ids.") ";
        }
        if (isset($_GET['stock_status']) AND !empty($_GET['stock_status'])) {
            $value=$_GET['stock_status'];
            $cond .= "  AND `p`.`stock_status`='$value' ";
        }
        if (isset($_GET['channel']) AND !empty($_GET['channel'])) {
            $value=$_GET['channel'];
            $cond .= "  AND `cp`.`channel_id`='$value' ";
        }

        if (isset($_GET['is_active']) AND in_array($_GET['is_active'],["1","0"])) {
          //  die('aja');
            $value=$_GET['is_active'];
            $cond .= "  AND `p`.`is_active`='$value' ";
        }

        return $cond;
    }

    private static function product_magento_attribute_list_flter()
    {
        $cond = " WHERE 1=1 ";
        if (isset($_GET['category']) AND !empty($_GET['category'])) {
            $category=$_GET['category'];
            $cond .= "  AND `product_magento_attribute`.`category`='$category' ";
        }
        if (isset($_GET['brand']) AND !empty($_GET['brand'])) {
            $brand=$_GET['brand'];
            $cond .= "  AND `product_magento_attribute`.`brand`='$brand' ";
        }
        if (isset($_GET['color']) AND !empty($_GET['color'])) {
            $color=$_GET['color'];
            $cond .= "  AND `product_magento_attribute`.`color`='$color' ";
        }
        if (isset($_GET['size']) AND !empty($_GET['size'])) {
            $size=$_GET['size'];
            $cond .= "  AND `product_magento_attribute`.`size`='$size' ";
        }
        if (isset($_GET['name']) AND !empty($_GET['name'])) {
            $name=$_GET['name'];
            $cond .= "  AND `product_magento_attribute`.`name`='$name' ";
        }
        if (isset($_GET['description']) AND !empty($_GET['description'])) {
            $description=$_GET['description'];
            $cond .= "  AND `product_magento_attribute`.`description`='$description' ";
        }
        if (isset($_GET['price']) AND !empty($_GET['price'])) {
            $price=$_GET['price'];
            $cond .= "  AND `product_magento_attribute`.`price`='$price' ";
        }
        if (isset($_GET['sku']) AND !empty($_GET['sku'])) {
            $sku=$_GET['sku'];
            $cond .= "  AND `product_magento_attribute`.`sku`='$sku' ";
        }
        if (isset($_GET['parent_sku']) AND !empty($_GET['parent_sku'])) {
            $parent_sku=$_GET['parent_sku'];
            $cond .= "  AND `product_magento_attribute`.`parent_sku`='$parent_sku' ";
        }
        return $cond;
    }

    public static function getProductList()
    {
        $cond=self::product_list_flter();
        $cond .=" AND `p`.`sku`<>''";
        $query="SELECT p.id,p.parent_sku_id,p.sku,p.name,p.barcode,p.brand,p.style,p.cost,p.rccp,p.sub_category,p.selling_status,p.stock_status,p.promo_price,p.extra_cost,p.is_active,
                        p_parent.sku as parent_sku,
                       # (select group_concat(pc.cat_id) from product_categories pc where pc.product_id=p.id) as product_categories,
                       GROUP_CONCAT(IFNULL(pc.cat_id,'') SEPARATOR '@!') as product_categories,
                       GROUP_CONCAT(IFNULL(cp.channel_id,'') SEPARATOR '@!') as product_channels,
                       GROUP_CONCAT(IFNULL(cp.sku,'') SEPARATOR '@!') as channel_sku_id,
                       GROUP_CONCAT(IFNULL(cp.is_live,'') SEPARATOR '@!') as product_channels_is_live,
                       GROUP_CONCAT(IFNULL(cp.deleted,'') SEPARATOR '@!') as product_channels_deleted,
                       GROUP_CONCAT(IFNULL(cp.ean,'') SEPARATOR '@!') as product_channels_ean,
                       GROUP_CONCAT(IFNULL(cp.product_name,'') SEPARATOR '@!') as product_channels_name,
                       GROUP_CONCAT(IFNULL(cp.discounted_price,'') SEPARATOR '@!') as product_channels_discounted_price,
                       GROUP_CONCAT(IFNULL(cp.discounted_price_from,'') SEPARATOR '@!') as product_channels_discounted_price_from,
                       GROUP_CONCAT(IFNULL(cp.discounted_price_to,'') SEPARATOR '@!') as product_channels_discounted_price_to
                       
                FROM
                  `products` p
                LEFT JOIN `products` p_parent
                   ON p.parent_sku_id=p_parent.id
                LEFT JOIN `channels_products` cp
                LEFT JOIN product_categories pc ON pc.product_id=cp.product_id
                ON p.sku=cp.channel_sku 
                $cond
                  #`p`.`sku`='ADIH200BLKGLD14oz'
                 GROUP BY `p`.`sku` ,p_parent.id ";
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data

        $total_records = Yii::$app->db->createCommand($query)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $query .= " LIMIT " . $offset . ", $per_page";

        $produts_list=$ChannelList = Yii::$app->db->createCommand($query)->queryAll();
        $list=[];
        foreach($produts_list as $product){
            $list[]=[
                'id'=>$product['id'],
                'parent_sku_id'=>$product['parent_sku_id'],
                'parent_sku'=>$product['parent_sku'],
                'sku'=>$product['sku'],
                'name'=>$product['name'],
                'barcode'=>$product['barcode'],
                'brand'=>$product['brand'],
                'style'=>$product['style'],
                'cost'=>$product['cost'],
                'rccp'=>$product['rccp'],
                'category'=>explode('@!', $product['product_categories']),
                'selling_status'=>$product['selling_status'],
                'promo_price'=>$product['promo_price'],
                'extra_cost'=>$product['extra_cost'],
                'stock_status'=>$product['stock_status'],
                'is_active'=>$product['is_active'],
                'channels'=>self::arrange_product_list_channels($product),
                'p_360'=>self::get_360_info($product['sku']),
            ];

        }
        return ['data'=>$list,'total_records'=>$total_records];
    }

    public static function getProductMagentoAttributeLists()
    {
        $cond=self::product_magento_attribute_list_flter();
        $query="SELECT * FROM `product_magento_attribute` $cond order by sku";
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data

        $total_records = Yii::$app->db->createCommand($query)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $query .= " LIMIT " . $offset . ", $per_page";

        $produts_list=$ChannelList = Yii::$app->db->createCommand($query)->queryAll();
        //echo "<pre>";print_r($produts_list);exit;
        $list=[];
        foreach($produts_list as $product){
            $list[]=[
                'category'=>$product['category'],
                'brand'=>$product['brand'],
                'color'=>$product['color'],
                'size'=>$product['size'],
                'name'=>$product['name'],
                'description'=>$product['description'],
                'price'=>$product['price'],
                'sku'=>$product['sku'],
                'parent_sku'=>$product['parent_sku'],
                'image_url'=>$product['image_url'],
            ];

        }
        return ['data'=>$list,'total_records'=>$total_records];
    }

    private static function arrange_product_list_channels($list)
    {
        $items=[];
        //print_r($list['product_channels']); die();
        if($list && $list['product_channels'])
        {
            $product_channels = explode('@!', $list['product_channels']);
            $product_channel_sku_id = explode('@!', $list['channel_sku_id']);
            $product_channels_is_live = explode('@!', $list['product_channels_is_live']);
            $product_channels_ean = explode('@!', $list['product_channels_ean']);
            $product_channels_name = explode('@!', $list['product_channels_name']);
            $product_channels_deleted = explode('@!', $list['product_channels_deleted']);
            $product_channels_discounted_price = explode('@!', $list['product_channels_discounted_price']);
            $product_channels_discounted_price_from = explode('@!', $list['product_channels_discounted_price_from']);
            $product_channels_discounted_price_to = explode('@!', $list['product_channels_discounted_price_to']);

        for($i=0;$i<count($product_channels);$i++)
        {
            $items[$product_channels[$i]]=[
                'channel_name'=>$product_channels[$i],
                'channel_sku_id'=>$product_channel_sku_id[$i],
                'is_live'=>$product_channels_is_live[$i],
                'deleted'=>$product_channels_deleted[$i],
                'ean'=>$product_channels_ean[$i],
                'name'=>$product_channels_name[$i],
                'discounted_price'=>$product_channels_discounted_price[$i],
                'discounted_price_from'=>$product_channels_discounted_price_from[$i],
                'discounted_price_to'=>$product_channels_discounted_price_to[$i],
            ];
        }
     }
     return $items;
    }

    public function get_360_info($sku)
    {
        $p_360=Products360Fields::find()->where(['sku'=>$sku])->asArray()->one();
        return $p_360;
    }

    /*
    * csv export
    */
    public static function export_csv($record,$categories,$channels)
    {
       // echo "<pre>";
        //print_r($categories); die();
        ///categories
        /*$cat_index=array_search('18', array_column($categories,'key'));
        $cat_name=$categories[$cat_index]['value'];
        print_r($cat_name); die();*/
        $list=[];
        $channels_list="";
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {
            /*$cat_index=array_search($rec['category'], array_column($categories,'key')); // find categeory index in array
             echo "<pre>";
             print_r($rec['category']); die();
            $cat_name=$categories[$cat_index]['value'];*/
            $cat_name="";
            ////////////////////////
            if($rec['category']){
                foreach ($rec['category'] as $cat)
                {
                    $cat=isset($categories[$cat]) ? $categories[$cat]['name']:"";
                    $cat_name .= ",".$cat;
                }
            }
            $cat_name=trim($cat_name,',');
            ///////////////////////////////////////////////
            $size=""; //product size specially for spl
            $system_sku=""; //product sku in client system specially for spl
            foreach($rec['channels'] as $channel)  // get channels list in which these items present
            {
                $channel_index=array_search($channel['channel_name'], array_column($channels,'id'));
                if($channels_list)
                    $channels_list .= " , " .$channels[$channel_index]['name'];
                else
                    $channels_list .=$channels[$channel_index]['name'];

                //////if single chanel selected then also add column of deleted
                if(isset($_GET['channel']) && $_GET['channel']){
                    $deleted_column="Deleted";
                    $deleted_value=$channel['deleted'];
                    $discounted_price_column='Discounted Price';
                    $discounted_price=$channel['discounted_price'];
                    $discounted_price_from_column='Discounted Price From';
                    $discounted_price_from=$channel['discounted_price_from'];
                    $discounted_price_to_column='Discounted Price To';
                    $discounted_price_to=$channel['discounted_price_to'];
                    $item_id_column="Item ID";
                    $item_id_value=$channel['channel_sku_id'];
                } else{
                    $deleted_column='';
                    $deleted_value='';
                    $item_id_column=$discounted_price_column=$discounted_price_from_column=$discounted_price_to_column='';
                    $item_id_value=$discounted_price=$discounted_price_from=$discounted_price_to='';
                }
            }



                if($header){
                    $list[]=['Sku' ,'System SKU','Size','Name','Parent Sku',$item_id_column,'Style','Cost','Rccp','Category','barcode','brand','Selling Status','Promo Price','Extra Cost','Stock Status','Is Active','Channels',$discounted_price_column,$discounted_price_from_column,$discounted_price_to_column,$deleted_column];
                }
                ///////get system sku and size for spl special
                if($rec['brand']=="adidas" && $rec['parent_sku_id']) {
                    $system_sku = self::get_sku_from_pattern_adidas($rec['sku'], '-', '_'); // eg MEN-ESSENCE M-FU8397_BLACK OR GREY_9.5 => FU8397
                    $size=self::get_size_from_sku($rec['sku']);// for sp
                }
                elseif(in_array($rec['brand'],['nike','pedro','under armour']) && $rec['parent_sku_id']){
                    $system_sku=self::get_sku_from_pattern_nike($rec['sku']);
                    $size=self::get_size_from_sku($rec['sku']);// for sp
                }


                $list[]=[$rec['sku'],$system_sku,$size,$rec['name'],$rec['parent_sku'],$item_id_value,$rec['style'],$rec['cost'],$rec['rccp'],$cat_name,"'".$rec['barcode']."'",$rec['brand'],$rec['selling_status'],$rec['promo_price'],$rec['extra_cost'],$rec['stock_status'],$rec['is_active'],$channels_list,$discounted_price,$discounted_price_from,$discounted_price_to,$deleted_value];
                $header=false;

            $channels_list=""; // clear for next loop

        }
        $file_name='products_report'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }

    /*
    * csv export
    */
    public static function export_magento_csv($record)
    {

        $list=[];
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {
            if($header){
                $list[]=['category' ,'brand','color','size','name', 'description', 'price', 'sku', 'parent sku', 'image url (if possible)'];
            }

            $list[]=[$rec['category'],$rec['brand'],$rec['color'],$rec['size'],$rec['name'],$rec['description'], $rec['price'], $rec['sku'], $rec['parent_sku'],$rec['image_url']];
            $header=false;
        }
        $file_name='products_report'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }


    public static function DownloadMissingImagesSkus($channel_id)
    {
        $list=[];
        $query="SELECT p.sku , p.name , p.image, p.brand,c.name as channel_name,cp.deleted
                FROM
                  products p
                INNER JOIN channels_products cp
                   ON cp.product_id=p.id 
                INNER JOIN channels c 
                  ON c.id=cp.channel_id
                WHERE 
                 cp.channel_id='".$channel_id."' AND  (p.image is NULL OR p.image ='')
                GROUP BY p.sku  ";
        $result=Yii::$app->db->createCommand($query)->queryAll();
        $header=true;
        if($result)
        {
            foreach($result as $product)
            {
                if($header)
                    $list[]=['sku','name','image','brand','channel','is_deleted'];
                else
                    $list[]=["'".$product['sku']."'",$product['name'],$product['image'],$product['brand'],$product['channel_name'],$product['deleted']];

                $header=false;
            }
        }
        $file_name='product_image_missing'.time().'.csv';
        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }

    public static function GetChildProductsSkus(){
        $sql = "SELECT * FROM products p
                WHERE p.parent_sku_id != 0 AND p.parent_sku_id IS NOT null;";
        return Products::findBySql($sql)->asArray()->all();
    }

    /**
     * count how many total of parenst and total of variations we have overall
     */
    public static function parents_and_variation_counts()
    {
            $parent=Products::find()->where(['parent_sku_id'=>0])->orWhere(['parent_sku_id'=>NULL])->orWhere(['parent_sku_id'=>''])->count();
            $variations=Products::findBySql("SELECT id FROM products WHERE parent_sku_id<>'' AND parent_sku_id<>0 AND parent_sku_id IS NOT NULL")->count();
            return [
                'parent_count'=>$parent,
                'variation_count'=>$variations
            ];
    }

    /****
     * @return array parent and variation counts in marketplace
     */
    public static function parents_and_variation_counts_marketplace($market_place=null,$channel=null)
    {
        $where="";
        if($market_place)
        {
            $channels=Channels::find()->select('id')->where(['marketplace'=>$market_place])->asArray()->all();  // al channels in marketpalces
            $channel_ids=array_column($channels,'id');
            $where .= " AND `cp`.`channel_id` IN (".implode($channel_ids,',').")";
        }
        if($channel)
        {
            $channel_id=Channels::find()->select('id')->where(['name'=>$channel])->scalar();  //
            if($channel_id)
                 $where .= " AND `cp`.`channel_id` IN (".$channel_id.")";
        }
        if(!$channel && !$market_place) {
            return [
                'parent_count' => 0,
                'variation_count' => 0
            ];
        }

        $sql="SELECT count(distinct(`p`.`sku`)) as parents,
                (
                  SELECT count(distinct(`p`.`sku`))
                  FROM 
                    `products` p 
                  INNER JOIN `channels_products` cp
                  ON  `cp`.`product_id`=`p`.`id`
                  WHERE
                      `p`.`parent_sku_id` iS NOT NULL AND `p`.`parent_sku_id`<>''  AND `p`.`parent_sku_id`<>''
                       $where )  as variations 
                       
              FROM 
                `products` p 
              INNER JOIN `channels_products` cp
              ON  `cp`.`product_id`=`p`.`id`
              WHERE 
                  ( `p`.`parent_sku_id` iS  NULL OR `p`.`parent_sku_id`='' OR `p`.`parent_sku_id`='' )
                 $where
                 ";
        $connection = Yii::$app->db;
        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        return [
            'parent_count'=> $result['parents'],
            'variation_count'=>$result['variations']
        ];
    }

    public static function getProductDimensionsAndWeight($sku)
    {
        $product=Products::find()->select('dimensions')->where(['sku'=>$sku])->scalar();
        if($product)
        {
            $dimensions=json_decode($product);
            if($dimensions)
            {
                $weight_parts=explode('.',$dimensions->weight);  // 5.2 =>5 pound 2 oz
                return [
                    'width'=>$dimensions->width,
                    'height'=>$dimensions->height,
                    'length'=>$dimensions->length,
                    'weight'=>$dimensions->weight,
                    'weight_lb_part'=>$weight_parts[0],
                    'weight_oz_part'=>isset($weight_parts[1]) ? $weight_parts[1]:0 ,
                ];
            }

        }
    }

    public static function update_stock_by_sku($list,$warehouse_id)
    {
        $status = null;
        $updated_count=0;
        $error_list=array();
        $i = 0;
        foreach ($list as $value){
            if($i != 0) {
                $sku_status = "";
                $sku_sheet = $value[0];
                $stock = $value[1];
                $sku = trim($sku_sheet, "'");  // trim single comma at the end
                $sku = trim($sku);
                $findStock = \common\models\WarehouseStockList::find()->where(['sku' => $sku, 'warehouse_id' => $warehouse_id])->one();

                if ($findStock) {
                    if ($findStock->available == $stock)
                        continue;

                    $findStock->available = $stock;
                    $findStock->updated_at = date('Y-m-d H:i:s');
                    $findStock->update();
                    if (!empty($findStock->errors)) {
                        $error_list[] = $sku;
                        $status = self::$status_error;
                    } else {
                        $updated_count++;
                        $status = self::$status_updated;
                    }

                    self::csv_stock_upload_logs($sku, $stock, $warehouse_id, $status);

                } else {
                    $addNew = new \common\models\WarehouseStockList();
                    $addNew->sku = $sku;
                    $addNew->available = $stock;
                    $addNew->warehouse_id = $warehouse_id;
                    $addNew->added_at = date('Y-m-d H:i:s');
                    $addNew->updated_at = date('Y-m-d H:i:s');
                    $addNew->save();
                    if (!empty($addNew->errors)) {
                        $error_list[] = $sku;
                        $status = self::$status_error;
                    } else {
                        $updated_count++;
                        $status = self::$status_inserted;
                    }
                    self::csv_stock_upload_logs($sku, $stock, $warehouse_id, $status);
                }

            }
            $i++;
        }
        return ['status'=>'success',
            'msg'=>'Uploaded',
            'not_updated_list'=>$error_list,
            'updated_list'=>$updated_count,
        ];
    }


    public static function update_stock_by_barcode($list,$warehouse_id)
    {
        $status = null;
        $updated_count=0;
        $error_list=array();
        $i = 0;
        foreach ($list as $value){

            if($i != 0){
                $bar_code = $value[0];
                $stock = $value[1];
                $barcode = trim($bar_code, "'");  // trim single comma at the end
                $sku = Products::find()->select('sku')->where(['barcode' => trim($barcode)])->scalar();


                if (!$sku) {
                    $error_list[] = $barcode;
                    continue;
                }

                $findStock = \common\models\WarehouseStockList::find()->where(['sku' => $sku, 'warehouse_id' => $warehouse_id])->one();


                if ($findStock) {
                    if ($findStock->available == $stock)
                        continue;

                    $findStock->available = $stock;
                    $findStock->updated_at = date('Y-m-d H:i:s');
                    $findStock->update();
                    if (!empty($findStock->errors)) {
                        $error_list[] = $barcode;
                        $status = self::$status_error;
                    } else {
                        $updated_count++;
                        $status = self::$status_updated;
                    }
                    self::csv_stock_upload_logs($sku, $stock, $warehouse_id, $status);
                } else {
                    $addNew = new \common\models\WarehouseStockList();
                    $addNew->sku = $sku;
                    $addNew->available = $stock;
                    $addNew->warehouse_id = $warehouse_id;
                    $addNew->added_at = date('Y-m-d H:i:s');
                    $addNew->updated_at = date('Y-m-d H:i:s');
                    $addNew->save();
                    if (!empty($addNew->errors)) {
                        $error_list[] = $barcode;
                        $status = self::$status_error;
                    } else {
                        $updated_count++;
                        $status = self::$status_inserted;
                    }
                    self::csv_stock_upload_logs($sku, $stock, $warehouse_id, $status);
                }

            }
            $i++;
        }
         return ['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$updated_count,
                ];


    }

    public static function csv_stock_upload_logs($sku = null, $available_stock = null, $warehouse_id = null, $status = null){

            $csv_upload_stock = new CsvStockUpload();
            $csv_upload_stock->sku = $sku;
            $csv_upload_stock->available = $available_stock;
            $csv_upload_stock->warehouse_id = $warehouse_id;
            $csv_upload_stock->status = $status;
            $csv_upload_stock->added_at = date('Y-m-d H:i:s');
            $csv_upload_stock->updated_at = date('Y-m-d H:i:s');
            if($csv_upload_stock->save()){
                return true;
            }else{
                return false;
            }

    }

    public static function redefineProductsForDolibarrWarehouse($products){
        $products_redefine=[];
        $special_characters=['$','*','\'',']','[',"'",';','/',',','"',':','?','>','<','|',' '];

        foreach ($products as $key=>$value){
            $product_detail=$value;
            $sku = str_replace($special_characters,'_',$value['sku']);
            $product_detail['sku']=$sku;
            $products_redefine[$sku]=$product_detail;
        }

        return $products_redefine;
    }
    public static function GetAllProducts($warehouse){

        $config_ware=json_decode($warehouse['configuration'],true);
        if (!isset($config_ware['consider_product_unique_column']))
        {echo 'consider_product_unique_column is not set in configuration column of this warehouse';die;}

        if ($config_ware['consider_product_unique_column']=='ean'){
            $sql = "SELECT p.sku,p.name,p.cost,p.ean,p.barcode,p.brand FROM products p where p.is_active=1 AND p.parent_sku_id != 0 AND p.ean!='0' AND p.ean!=''";
            $get_products=Products::findBySql($sql)->asArray()->all();
            return $get_products;
        }
        else if ($config_ware['consider_product_unique_column']=='barcode'){
            $sql = "SELECT p.sku,p.name,p.cost,p.ean,p.barcode,p.brand FROM products p where p.is_active=1 AND p.parent_sku_id != 0 AND p.barcode!='' AND p.barcode!='0'";
            $get_products=Products::findBySql($sql)->asArray()->all();
            return $get_products;
        }
        else{
            $sql = "SELECT p.sku,p.name,p.cost,p.ean,p.barcode,p.brand FROM products p where p.is_active=1 AND p.parent_sku_id != 0";
            $get_products=Products::findBySql($sql)->asArray()->all();
            return $get_products;
        }
    }

    /*******
     * get sku from pattern
     * for example
     * MEN-ESSENCE M-FU8397_BLACK OR GREY_9.5 => FU8397
     */
    public static function get_sku_from_pattern_adidas($string,$start,$end)
    {
        if(substr_count($string, '-') ===1 && substr_count($string, '_') ===0) //if string contains 1 dash
        {
            return strtok($string, '-');
        }
        $string = ' ' . $string;
        $ini = strrpos($string, $start);
        if ($ini ===false) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    /*******
     * get sku from pattern for nike specially for spl
     * for example
     * 10909-090_small => 10909-090
     * cd9599-100-s  =>cd9599-100
     */
    public static function get_sku_from_pattern_nike($string)
    {
        if (strpos($string, '_') !== false) {
            return strtok($string, '_');  //for example 10909-090_small => 10909-090
        } else{
            $explode=explode('-',$string);
            $frst=isset($explode[0]) ? $explode[0] :"";
            $scnd=isset($explode[1]) ? $explode[1] :"";
            if($frst && $scnd)
                return $frst."-".$scnd;
        }
        return "";
    }

    public static function get_size_from_sku($string)
    {
        $pos= strrpos($string,'_');
        if(!$pos)
            $pos= strrpos($string,'-');

        if($pos)
            return substr($string,$pos+1);
    }
    /*
    ** remove skus from ez com which are deleted from online platform
     */
    public static function remove_deleted_skus($list,$channel_id)
    {
        if($list && $channel_id)
        {
            Yii::$app->db->createCommand()
                ->update('channels_products',
                    ['deleted' => 1,'is_live'=>0],['AND',
                        'channel_id = '.$channel_id,
                        ['NOT IN', 'channel_sku', $list]]
                )
                ->execute();

        }

      //  print_r($res);
        return;
    }

    /****************************************PRODUCT SYNC TO WAREHOUSE PORTION
    ***************************************************************/

   /*
    * products to warehouse syncing filter
   */

    private static function product_warehouse_sync_flter()
    {
        $cond = " WHERE 1=1 ";
        if (isset($_GET['product_sku']) AND !empty($_GET['product_sku'])){
            $searched_skus = '"'.implode('","',explode(',',$_GET['product_sku'])).'"'; // if multiple
            $cond .= "  AND p.sku IN ($searched_skus)";
        }

        if (isset($_GET['product_name']) AND !empty($_GET['product_name'])) {
            $searched_names = '"'.implode('","',explode(',',$_GET['product_name'])).'"'; // if multiple
            //$name=$_GET['product_name'];
            $cond .= "  AND `p`.`name` IN ($searched_names) ";
        }

        if (isset($_GET['fulfilled_by']) AND !empty($_GET['fulfilled_by'])) {
            $warehouse_id =$_GET['fulfilled_by'];
            $cond .= "  AND `epc`.`warehouse_id`='$warehouse_id' ";
        }

        if (isset($_GET['product_status']) AND !empty($_GET['product_status'])) {
            $status =$_GET['product_status'];
            $cond .= "  AND `epc`.`status`='$status' ";
        }

        return $cond;
    }

    /*
      * products to warehouse get products list
      */
    public static function productsToWarehouseProductsList()
    {
        //product not sync
        $cond=self::product_warehouse_sync_flter();
        $sql = "SELECT epc.id as pk_id,epc.response as api_response ,p.sku ,p.name as product_name,w.name as warehouse_name, 
                epc.warehouse_id as warehouse, epc.status
                  FROM
                products p
                  INNER JOIN ezcom_to_warehouse_product_sync epc
                  ON p.sku = epc.sku
                   INNER JOIN warehouses w on w.id=epc.warehouse_id
                $cond";

        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data

        $total_records = Yii::$app->db->createCommand($sql)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $sql .= " LIMIT " . $offset . ", $per_page";

        $produts_list=$ChannelList = Yii::$app->db->createCommand($sql)->queryAll();

       /* $list=[];
        foreach($produts_list as $product){
            $list[]=[
                'sku'=>$product['sku'],
                'product_name'=>$product['product_name'],
                'warehouse'=>$product['warehouse_id'],
                'warehouse_name'=>$product['warehouse_name'],
                'status'=>$product['status'],
            ];

        }*/
        return ['data'=>$produts_list,'total_records'=>$total_records];

    }

    /**************products not assigned to any third party warehouse to push****************/
    public static function productsNotAssignedToWarehouse()
    {
        $cond="";

        if (isset($_GET['product_sku']) AND !empty($_GET['product_sku'])){
            $searched_skus = '"'.implode('","',explode(',',$_GET['product_sku'])).'"'; // if multiple
            $cond .= "  AND p.sku IN ($searched_skus)";
        }

        if (isset($_GET['product_name']) AND !empty($_GET['product_name'])) {
            $searched_names = '"'.implode('","',explode(',',$_GET['product_name'])).'"'; // if multiple
            //$name=$_GET['product_name'];
            $cond .= "  AND `p`.`name` IN ($searched_names) ";
        }

        if((isset($_GET['parent_or_child']) AND !empty($_GET['parent_or_child'])))
        {
            $type=$_GET['parent_or_child'];
            if($type=="parent")
                $cond .= "  AND (`p`.`parent_sku_id` IS NULL OR `p`.`parent_sku_id`='0') ";
            if($type=="child")
                $cond .= "  AND `p`.`parent_sku_id` IS NOT NULL AND `p`.`parent_sku_id`<>'0' ";
        }

        $sql= "SELECT p.name ,p.sku,p.barcode FROM products p 
               Where p.sku NOT IN (SELECT `sku` from `ezcom_to_warehouse_product_sync`)
               AND p.is_active=1
               $cond";
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data

        $total_records = Yii::$app->db->createCommand($sql)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $sql .= " LIMIT " . $offset . ", $per_page";

        $products_list=Yii::$app->db->createCommand($sql)->queryAll();
        return ['data'=>$products_list,'total_records'=>$total_records];
    }

    /****************export products which are not assigned to any warehouse to sync ************/
    public static function export_not_assigned_Warehouse_products($record)
    {

        // echo "<pre>";print_r($record);exit;
        $list=[];
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {

            if($header){
                $list[]=['Product Name' ,'SKU','Barcode'];
            }
                $list[] = [$rec['name'], $rec['sku'], $rec['barcode']];

            $header=false;

        }
        $file_name='products_non_warehouse'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }
    /*********get products which are assigned duplicate / mean same sku assign to multiple warehouses*******/
    public static function duplicateAssignedWarehouseProducts()
    {
        $cond=" WHERE 1=1 ";

        if (isset($_GET['product_sku']) AND !empty($_GET['product_sku'])){
            $searched_skus = '"'.implode('","',explode(',',$_GET['product_sku'])).'"'; // if multiple
            $cond .= "  AND p.sku IN ($searched_skus)";
        }

        if (isset($_GET['product_name']) AND !empty($_GET['product_name'])) {
            $searched_names = '"'.implode('","',explode(',',$_GET['product_name'])).'"'; // if multiple
            //$name=$_GET['product_name'];
            $cond .= "  AND `p`.`name` IN ($searched_names) ";
        }


        $sql = "SELECT p.name,ecp.sku,GROUP_CONCAT(ecp.id SEPARATOR '@!') as pk_ids,GROUP_CONCAT(w.name SEPARATOR '@!') as warehouses,GROUP_CONCAT(ecp.status) as statuses,
                  COUNT(*) c 
                    FROM 
                      `ezcom_to_warehouse_product_sync` ecp
                    INNER JOIN 
                      products p ON p.sku=ecp.sku
                    INNER JOIN warehouses w ON w.id=ecp.warehouse_id   
                    $cond  
                   GROUP BY ecp.sku  HAVING c > 1";
       // echo $sql; die();
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data

        $total_records = Yii::$app->db->createCommand($sql)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $sql .= " LIMIT " . $offset . ", $per_page";

        $produts_list=$ChannelList = Yii::$app->db->createCommand($sql)->queryAll();

        $list=[];
        // echo "<pre>";print_r($produts_list);exit;
        foreach($produts_list as $product){
            $list[]=[
                'sku'=>$product['sku'],
                'product_name'=>$product['name'],
                'pk_ids'=>explode('@!',$product['pk_ids']),
                'warehouses'=>explode('@!',$product['warehouses']),
                'statuses'=>explode(',',$product['statuses']),
            ];

        }

        return ['data'=>$list,'total_records'=>$total_records];
    }

    /****************export products which are assigned duplicate to warehouse / mean same sku to multiple warehouses to push ************/
    public static function exportDuplicateAssignedWarehouseProducts($record)
    {

         //echo "<pre>";print_r($record);exit;
        $list=[];
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {

            if($header){
                $list[]=['Product Name' ,'SKU','Warehouses','Sync Statuses'];
            }
            $list[] = [$rec['product_name'], $rec['sku'], implode(',',$rec['warehouses']),implode(',',$rec['statuses'])];

            $header=false;

        }
        $file_name='products_duplicate_assigned_wh'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }

    private static function arrange_product_list_warehouse($list)
    {
        $items=[];
        $sku = $list['sku'];

        $sql = "SELECT p.sku ,p.name as product_name, epc.warehouse_id, epc.status
        FROM
        products p
        INNER JOIN ezcom_to_warehouse_product_sync epc
        ON p.sku = epc.sku where p.sku = '$sku'";

        $produts_list_multiple = $ChannelList = Yii::$app->db->createCommand($sql)->queryAll();


        return $produts_list_multiple;
    }

    /****************export products which are sync to thirdparty warehouse pending, failed , synced ************/
    public static function export_product_warehouse_sync_csv($record)
    {

        // echo "<pre>";print_r($record);exit;
        $list=[];
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {

            if($header){
                $list[]=['Product Name' ,'SKU','Warehouse Id', 'Warehouse Name', 'Sync Status'];
            }

                $list[] = [$rec['sku'], $rec['product_name'], $rec['warehouse'], $rec['warehouse_name'], $rec['status']];


            $header=false;

        }
        $file_name='product_sync_warehouse_report'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='csv/'.$file_name;
        if(file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
            unlink($filepath);
            die();

        }
    }
}