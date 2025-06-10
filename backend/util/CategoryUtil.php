<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 6/15/2020
 * Time: 2:36 PM
 */
namespace backend\util;

use common\models\ChannelsDetails;

class CategoryUtil
{

    public static function GetChannelComissions($channelId){

        $channel_commissions = ChannelsDetails::find()->where(['channel_id'=>$channelId])->asArray()->all();
        return $channel_commissions;

    }

    public static function downloadCsv($data)
    {
        $header=true;
        $list=[];
        if($data)
        {
           // if($header)
                $list[]=['id','name','parent_cat_id','parent_cat_name'];

           // $header=false;
            foreach($data as $cat)
            {
                $parent_cat_name=isset($cat['parent']['name']) ? $cat['parent']['name']:'';
                $list[]=[$cat['id'],$cat['name'],$cat['parent_id'],$parent_cat_name];

            }
        }
        $file_name='product_cats'.time().'.csv';
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