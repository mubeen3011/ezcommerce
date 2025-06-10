<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 1/7/2021
 * Time: 5:09 PM
 */

namespace backend\util;


use common\models\GlobalMobileCsvProducts;
use common\models\GlobalMobilesCatMapping;

class GlobalMobileUtil
{
    public static function debug($data)
    {
        echo "<pre>";
        print_r($data);
        die();
    }
        ///csv columns swap and save and download
    public static function SaveProductCsv($list,$download=false)
    {
        $file_name='gm_products_'.time().'.csv';
        if(!is_dir('product_csv_swapper')) //create the folder if it's not already exists
            mkdir('product_csv_swapper',0755,TRUE);

        $fp = fopen('product_csv_swapper/'.$file_name, 'w');
        //print_r($fp); die();
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $filepath='product_csv_swapper/'.$file_name;
        if($download===false)  // if need only download
              return ['status'=>'success','file'=>$filepath];

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
          //  unlink($filepath);
            die();

        }
    }

    // eg XL/XG-40-42" => XL/XG
    public static function get_name_pattern($size=null,$color=null,$product_name)
    {
       // $input="XL/XG 40-42";
        if($size)
            $size=str_replace("'","",$size);

        $arr = explode("/", $size, 2);
        if($arr && is_array($arr)){
            $first_charcater=$arr[0];
            if(isset($arr[1])){
                $sec_character=preg_replace('/[^a-z]/i','',$arr[1]); // replace everyhing except alphabet
                $size_pattern=$first_charcater."/".$sec_character;
            }

        }
       // print_r($size_pattern); die();


        if(isset($size_pattern) && $size_pattern)
        {
            if (strpos($product_name, $size_pattern) !== false) {
                $product_name=str_replace($size_pattern,'',$product_name);
            }
        } elseif(isset($first_charcater) && strlen($first_charcater) >=2)
        {
            $product_name=str_replace($first_charcater,'',$product_name);
        } elseif(isset($first_charcater) && $color){
            $product_name=str_replace($first_charcater ." " .$color,'',$product_name);
        }

        if($color && strpos($product_name, $color) !== false)
             $product_name=str_replace($color,'',$product_name);

        return $product_name;

    }

    public static function  make_parent_sku($desc,$brand)
    {
        if($desc){
            $only_alphabet=preg_replace('/[^a-z]/i','',$desc); // replace everyhing except alphabet
            $short_string=substr($only_alphabet,0,10);
            $brand_alphabets=preg_replace('/[^a-z]/i','',$brand); // replace everyhing except alphabet
            if($brand_alphabets){
                $final=$short_string."_".array_sum(unpack("C*", $desc))."_".$brand_alphabets;
                $final= substr($final,0,49);
                return $final;
            }
            else
                return $short_string."_".array_sum(unpack("C*", $desc));
        }
        return "";
    }
    public static function map_global_mobile_cat($category_list,$product)
    {
        if($category_list){
            foreach($category_list as $cat)
            {
                if(($cat['client_main_cat']==$product['parent_category']) && ($cat['client_sub_cat1']==$product['sub_category']))
                    return  ['main_cat'=>$cat['mapped_main_cat'],'sub_cat1'=>$cat['mapped_sub_cat1'],'sub_cat2'=>$cat['mapped_sub_cat2'],'sub_cat3'=>$cat['mapped_sub_cat3']];
            }
        }

        return  ['main_cat'=>$product['parent_category'],'sub_cat1'=>$product['sub_category'],'sub_cat2'=>'','sub_cat3'=>''];
    }



    /******covert csv to module format***/
    public static function convert_csv($csv_id)
    {
        $header=true;
        $list=[];
        $previuos=[];
        $mapped_categories=GlobalMobilesCatMapping::find()->asArray()->all(); /// mapp category and update
        $item_list=GlobalMobileCsvProducts::find()->where(['csv_id'=>$csv_id])->orderBy([
            'detail_description' => SORT_ASC,
            'color'=>SORT_ASC
        ])->asArray()->all();
        //self::debug($item_list);

        if($item_list)
        {
            $record_count=0; // to detect last iteration in the loop
            $record_length=count($item_list);

            foreach ($item_list as $value)
            {
                if($header)
                {
                    $list[]=['Product name','SKU','Color','Size','Brand','Summary','Description','Image 1','Image 2','Image 3',
                        'Image 4','Image 5','Image 6','Price Tax Excluded','Price Tax Included','Quantity','EAN-13 or JAN barcode', 'Weight','Length','Height',
                        'Width','Carrier Selection','Special Price Fields','Meta title','Meta keywords','Meta description','Friendly URL','Main Category','Sub Category 1','Sub Category 2',
                        'Sub Category 3','Sub Category 4','Sub Category 5','Sub Category 6','Sub Category 7'];
                }
                ////add parent sku at the end of same variation
                $current_product_parent_pattern=self::get_name_pattern($value['size'],$value['color'],$value['detail_description']);
                $value['detail_description']=$current_product_parent_pattern;
                if($previuos && ($previuos['detail_description']!=$current_product_parent_pattern))
                {
                    $current_parent_sku=self::make_parent_sku($previuos['detail_description'],$previuos['brand']);
                    $list[]=["NEW ".HelpUtil::shorten_name($previuos['detail_description'],60)." FAST SHIPPING!","'".$current_parent_sku."'",'','',$previuos['brand'],'','','','','',
                        '','','','','',0,'','','','',
                        '','','','','','','',$previuos['parent_category'],$previuos['sub_category'],$previuos['sub_category2'],
                        $previuos['sub_category3'],'','','',''];
                }
                $category=self::map_global_mobile_cat($mapped_categories,$value);
                $value['parent_category']=$category['main_cat'];
                $value['sub_category']=$category['sub_cat1'];
                $value['sub_category2']=$category['sub_cat2'];
                $value['sub_category3']=$category['sub_cat3'];
                $value['upc_scan']=trim($value['upc_scan'],"'");
                ///summary
                $summary="<ul>";
                if($value['bullet_1']){
                    $summary .="<li>";
                    $summary .=$value['bullet_1'];
                    $summary .="</li>";
                    //$summary .=PHP_EOL;
                }
                if($value['bullet_2']){
                    $summary .="<li>";
                    $summary .=$value['bullet_2'];
                    $summary .="</li>";
                  //  $summary .=PHP_EOL;
                }
                if($value['bullet_3']){
                    $summary .="<li>";
                    $summary .=$value['bullet_3'];
                    $summary .="</li>";
                   // $summary .=PHP_EOL;
                }
                if($value['bullet_4']){
                    $summary .="<li>";
                    $summary .=$value['bullet_4'];
                    $summary .="</li>";
                  //  $summary .=PHP_EOL;
                }
                if($value['bullet_5']){
                    $summary .="<li>";
                    $summary .=$value['bullet_5'];
                    $summary .="</li>";
                   // $summary .=PHP_EOL;
                }
                if($summary=="<ul>")  // if no bullet point found
                    $summary="";
                elseif($summary)
                    $summary .="</ul>"; // end of summary

               // $description=$summary ." ".$value['detail_description']; // before
                $description="<p>".$value['description']."</p>"; // after new requirement changes
                ///
                $list[]=["NEW ". HelpUtil::shorten_name($value['detail_description'],60)." FAST SHIPPING!","'".$value['upc_scan']."'",$value['color'],$value['size'],$value['brand'],$summary,$description,$value['picture_1'],$value['picture_2'],$value['picture_3'],
                    $value['picture_4'],$value['picture_5'],$value['picture_6'],$value['sell_price'],$value['sell_price'],0,"'".$value['upc_scan']."'",$value[ 'weight'],$value[ 'length'],$value[ 'height'],
                    $value['width'],'','','','','','',$value['parent_category'],$value['sub_category'],$value['sub_category2'],
                    $value['sub_category3'],'','','',''];

                $header=false;
                // $rewamp_name=GlobalMobileUtil::get_name_pattern($value['Size'],$value['Color'],$value['Detail Description']);
                //$value['detail_description']=$current_product_parent_pattern;
                //$valu
                $previuos=$value;

                ////if last item then at the end add parent
                if($record_count==($record_length-1))
                {
                    $current_parent_sku=self::make_parent_sku($previuos['detail_description'],$previuos['brand']);
                    $list[]=["NEW ".HelpUtil::shorten_name($previuos['detail_description'],60)." FAST SHIPPING!","'".$current_parent_sku."'",'','',$previuos['brand'],'','','','','',
                        '','','','','',0,'','','','',
                        '','','','','','','',$previuos['parent_category'],$previuos['sub_category'],$previuos['sub_category2'],
                        $previuos['sub_category3'],'','','',''];

                }
                $record_count++;

            }
            if($list)
                return self::SaveProductCsv($list);
            else
                return ['status'=>'failure','msg'=>'failed to download'];
        }
    }

    /****************
     * check if images on server available
     */
    public static function check_images_availibility($csv_id)
    {
        $products=GlobalMobileCsvProducts::find()->where(['csv_id'=>$csv_id,'images_checked'=>0])->asArray()->all();
        //self::debug($products);
        if($products)
        {
            foreach ($products as $product)
            {
                $valid_images=[];
                for($i=1;$i<=8;$i++)
                {
                    if ($product['picture_'.$i] && @exif_imagetype($product['picture_'.$i])) {
                        $valid_images[]=$product['picture_'.$i];
                    }
                }
                self::store_valid_images($product['id'],$valid_images);
            }
        }
        return;

        //self::debug($products);
    }

    public static function store_valid_images($product_id,$images)
    {
        $product=GlobalMobileCsvProducts::findOne(['id'=>$product_id]);
        if($product):
        $product->images_checked=1;
        $product->picture_1=isset($images[0]) ? $images[0]:NULL;
        $product->picture_2=isset($images[1]) ? $images[1]:NULL;
        $product->picture_3=isset($images[2]) ? $images[2]:NULL;
        $product->picture_4=isset($images[3]) ? $images[3]:NULL;
        $product->picture_5=isset($images[4]) ? $images[4]:NULL;
        $product->picture_6=isset($images[5]) ? $images[5]:NULL;
        $product->picture_7=isset($images[6]) ? $images[6]:NULL;
        $product->picture_8=isset($images[7]) ? $images[7]:NULL;
        $product->update();
        endif;

    }

    /**********
     * assign images to those variation siblings which has no image ,
     * pick image from close variation have same name and color and assign
     */
    public static function refill_closest_variation_images($csv_id)
    {
        $products=GlobalMobileCsvProducts::find()->where(
                ['csv_id'=>$csv_id,'images_checked'=>1,
                  'picture_1'=>null,'picture_2'=>null,'picture_3'=>null,'picture_4'=>null,
                  'picture_5'=>null,'picture_6'=>null,'picture_7'=>null,'picture_8'=>null
                ])
            ->asArray()->all();
        if($products)
        {
            foreach ($products as $product)
            {
                $sql="SELECT `id`,`picture_1`,`picture_2`,`picture_3`,`picture_4`,`picture_5`,`picture_6`,`picture_7`,`picture_8` 
                        FROM `global_mobile_csv_products` WHERE
                            `detail_description`='".str_replace("'", "\'", $product['detail_description'])."' AND `color`='".str_replace("'", "\'", $product['color'])."'
                             AND COALESCE(`picture_1`,`picture_2`,`picture_3`,`picture_4`,`picture_5`,`picture_6`,`picture_7`,`picture_8`) IS NOT NULL";
                $near_by=GlobalMobileCsvProducts::findBySql($sql)->asArray()->one();
                if($near_by)
                {
                        $new_product=GlobalMobileCsvProducts::findOne(['id'=>$product['id']]);
                        $new_product->picture_1=$near_by['picture_1'];
                        $new_product->picture_2=$near_by['picture_2'];
                        $new_product->picture_3=$near_by['picture_3'];
                        $new_product->picture_4=$near_by['picture_4'];
                        $new_product->picture_5=$near_by['picture_5'];
                        $new_product->picture_6=$near_by['picture_6'];
                        $new_product->picture_7=$near_by['picture_7'];
                        $new_product->picture_8=$near_by['picture_8'];
                        $new_product->update();
                }
            }
        }
        return;
    }
}