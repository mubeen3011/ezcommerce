<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 6/1/2021
 * Time: 2:44 PM
 */
namespace backend\util;
// util for sap business one local setup
use common\models\Settings;

class SapBusinessOneLocalUtil
{
    private static $current_warehouse=NULL;
    private static function config($warehouse)
    {
        if(!self::$current_warehouse)
            self::$current_warehouse=$warehouse;

        return self::$current_warehouse;
    }

    private static function get_config()
    {
        $config=json_decode(self::$current_warehouse->configuration);
        return $config;
    }

    private static function get_call($request_url,$headers=null)
    {
        $ch = curl_init($request_url);
        if($headers)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); //bk
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err){
            return ['status'=>'failure','data'=>'','error'=>json_encode($err)];
        }
        return ['status'=>'success','data'=>$result,'error'=>''];
    }

    private static function post_call()
    {

    }
    private static function debug($data)
    {
        echo "<pre>";
        print_r($data);
        exit;
    }

    private static function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private static function isXml($output){
        if(substr($output, 0, 5) == "<?xml") {
            return true;
        } else {
            return false;
        }
    }

    /*********get stock ********/
    public static function getStock($warehouse)
    {
        self::config($warehouse);
        $config=self::get_config();
       $list=[];
        $request_url=rtrim($config->api_link,'/').'/api/data/get_items_list';
        $product_list=self::get_call($request_url);
        if($product_list['status']=='failure'){
            LogUtil::add_log(['type'=>'warehouse-to-ezcom-stock-sync','entity_type'=>'warehouse','entity_id'=>self::$current_warehouse->id,'additional_info'=>'products fetch failed SAPb1local','response'=>$product_list['error'],'log_type'=>'error']);
            return $product_list;
        }
        if(self::isJson($product_list['data']))
        {
            $products=json_decode($product_list['data']);
            //self::debug((array)$products);
            //$list= self::getStockQuantity($products);
        }
        elseif(self::isXml($product_list['data']))
        {
            $xml = simplexml_load_string($product_list['data'], "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            //$products= json_decode($json,TRUE);
            //$list= self::getStockQuantity($products);
        }
        if(isset($products) && !empty($products))
        {
            foreach ($products as $product)
            {
                $list[]=$product->ItemSku;
            }
        }
        //return ['status'=>'success','data'=>$list];
        return $list;
    }

    /*************get stock qty ********/
    public static function getStockQuantity($product_list,$offset=0,$limit=100)
    {
        $config=self::get_config();
        // self::debug($config);
        $data=[];
        $count=0;
        if($product_list)
        {
            for($i=$offset;$i<=($offset+$limit);$i++)
            {
                $request_url=rtrim($config->api_link,'/').'/api/data/get_item_detail?itemCode='.$product_list[$i];
                $response=self::get_call($request_url);
                if($response['status']=='success')
                {
                    if(self::isJson($response['data']))
                    {
                        $res=json_decode($response['data']);
                    }elseif(self::isXml($response['data']))
                    {
                        $xml = simplexml_load_string($response['data'], "SimpleXMLElement", LIBXML_NOCDATA);
                        $json = json_encode($xml);
                        $res= json_decode($json,TRUE);
                    }

                    $qty=(($res->ItemQuantity) && ($res->ItemQuantity > 0) ) ? $res->ItemQuantity:0;
                    $data[]=['sku'=> $product_list[$i],'available'=>$qty];
                }elseif($response['status']=='failure'){
                    LogUtil::add_log(['type'=>'warehouse-to-ezcom-stock-sync','entity_type'=>'warehouse','entity_id'=>self::$current_warehouse->id,'additional_info'=>'products stock fetch of single product failed SAPb1local ('.$product->ItemSku.')','response'=>$response['error'],'log_type'=>'error']);
                }
                if(++$count > 105) // just extra check
                    break;

            }
        }
        return $data;
    }

    /**************sap business one local has alot of skus
     * so fetching stock in chunks, below func gives next ofsset to fetch**************/
    public static function get_next_offset()
    {
        $next_offset=0;
        $record=Settings::find()->where(['name'=>'sap_b1_local'])->one();
        //self::debug($record);
        if($record)
        {
            $next_offset=$record->value;
        }
        return $next_offset;

    }

    public static function set_next_offset($limit=100)
    {
        $record=Settings::find()->where(['name'=>'sap_b1_local'])->one();
        if($record)
        {
            $new_val=((int)$record->value + $limit);
            $record->value=(string)$new_val;
            $record->update();
        }else{
            $record=new Settings();
            $record->name='sap_b1_local';
            $record->value='0';
            $record->save();
        }
        return;
    }
}
