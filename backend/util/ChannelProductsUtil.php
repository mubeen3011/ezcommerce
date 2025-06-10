<?php
namespace backend\util;
use common\models\Category;
use Yii;
class ChannelProductsUtil
{
    private static function filter()
    {
        $where="";
        if(isset($_GET['sku']) && $_GET['sku']!='')
            $where .=" AND `p`.`sku`='".$_GET['sku']."'";

        if(isset($_GET['name']) && $_GET['name']!='')
            $where .=" AND `p`.`name`='".$_GET['name']."'";

        if(isset($_GET['cost_price']) && $_GET['cost_price']!='')
            $where .=" AND `p`.`cost`='".$_GET['cost_price']."'";

        if(isset($_GET['rccp']) && $_GET['rccp']!='')
            $where .=" AND `p`.`rccp`='".$_GET['rccp']."'";

        if(isset($_GET['cat']) && $_GET['cat']!=''){
            $cat=Category::find()->select('id')->where(['parent_id'=>$_GET['cat']])->asArray()->column();
            if($cat){
                array_push($cat,$_GET['cat']);
                $cat_in=implode(',',$cat);
            }
             else
                $cat_in=$_GET['cat'];
            $where .=" AND `c`.`id` IN (".$cat_in.")";
        }


        if(isset($_GET['channel']) && $_GET['channel']!='')
            $where .=" AND `ch`.`id`='".$_GET['channel']."'";

        if(isset($_GET['stock_update_limit']) && $_GET['stock_update_limit']!='')
            $where .=" AND `cp`.`stock_update_percent`='".$_GET['stock_update_limit']."'";

        if(isset($_GET['shop_stock']) && $_GET['shop_stock']!='')
            $where .=" AND `cp`.`stock_qty`='".$_GET['shop_stock']."'";

        return $where;
    }

    public static function get_channel_products()
    {
        $where=self::filter();

        $sql="SELECT cp.* ,`p`.`name`,`p`.`sku`,`p`.`cost`,`p`.`rccp`,`c`.`name` as category,`ch`.`name` as channel_name
              FROM
                `channels_products` cp
              INNER JOIN 
                `products` p 
               ON 
                `cp`.`channel_sku`=`p`.`sku`
              LEFT JOIN 
                `category` c 
               ON `c`.`id`=`p`.`sub_category`
               INNER JOIN `channels` ch
               ON `cp`.`channel_id`=`ch`.`id`
              WHERE 1 $where
              ORDER BY `cp`.`channel_sku` ASC 
              ";

        $total_records = Yii::$app->db->createCommand($sql)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $sql .= "LIMIT " . $offset . ", $per_page";
        $list= Yii::$app->db->createCommand($sql)->queryAll();
        return ['total_records'=>$total_records,'products'=>$list];
    }

    public static function export_csv($products)
    {
        $list=[];
        $header=true;
        foreach($products as $product)
        {
            if($header){
                $list[]=['Sku' ,'Name','Cost','Rccp','Channel','Category','Stock','Stock update percent','Is Active'];
            }
            $list[]=[$product['sku'],$product['name'],$product['cost'],$product['rccp'],$product['channel_name'],$product['category'],$product['stock_qty'],$product['stock_update_percent'],$product['is_live']];
            $header=false;
        }
        $file_name='shop_products'.time().'.csv';

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