<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/11/2020
 * Time: 11:33 AM
 */
namespace backend\util;
use common\models\Settings;
use Yii;

class OrderShipmentUtil
{
    public function getShipmentRecords()
    {
        $add_select="";
        $join="";
        $cond = " WHERE 1=1 ";
        /*******if order shipping status is in progress******/
        if (isset($_GET['order_status']) AND in_array($_GET['order_status'],['in_progress','bulk_shipping_failed']))
        {
            $add_select .=" boc.status as bulk_ship_status,";
            $join .=" INNER JOIN `bulk_order_shipment` boc
                      ON o.id=boc.order_id";
            if($_GET['order_status']=="in_progress")
                 $cond .=" AND boc.status='pending'";
            elseif($_GET['order_status']=="bulk_shipping_failed")
                $cond .=" AND boc.status='failed'";
        } else{
            $join .="  INNER JOIN order_shipment os
                    ON oi.id=os.order_item_id";
            $cond .=" AND os.system_shipping_status IN('shipped','completed')";
        }
        $query = "SELECT 
                        c.marketplace as marketplace,c.id as channel_id,c.name as channel_name,
                        o.order_number,o.order_status,o.order_market_status,o.id as order_id_pk,
                        o.order_total,
                        (CONVERT_TZ(o.order_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) as order_created_at,
                        (CONVERT_TZ(o.order_updated_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) as order_updated_at,
                        o.customer_type,$add_select
                    GROUP_CONCAT(IFNULL(oi.id,'') SEPARATOR '@!') as order_item_id_pk,
                    GROUP_CONCAT(IFNULL(oi.item_status , '') SEPARATOR '@!') as item_status,
                    GROUP_CONCAT(IFNULL(oi.item_market_status,'') SEPARATOR '@!') as item_market_status,
                    GROUP_CONCAT(IFNULL(oi.item_created_at,'') SEPARATOR '@!') as item_created_at,
                    GROUP_CONCAT(IFNULL(p.name,'')  SEPARATOR '@!' ) as product_name,
                    GROUP_CONCAT(IFNULL(p.image,'') SEPARATOR '@!') as product_image,
                    GROUP_CONCAT(IFNULL(oi.item_sku,'') SEPARATOR '@!') as sku, 
                    GROUP_CONCAT(IFNULL(oi.fulfilled_by_warehouse,'') SEPARATOR '@!') as fulfilled_by_warehouse, 
                    GROUP_CONCAT(IFNULL(oi.tracking_number,'') SEPARATOR '@!') as tracking_number,
                    GROUP_CONCAT(IFNULL(oi.shipping_label,'') SEPARATOR '@!') as shipping_label ,
                    GROUP_CONCAT(IFNULL(cr.type,'') SEPARATOR '@!') as courier_type,
                    GROUP_CONCAT(IFNULL(cr.id,'') SEPARATOR '@!') as item_courier_id
                    FROM  order_items oi
                    INNER JOIN  orders o 
                    ON  o.id = oi.order_id
                    LEFT JOIN products p
                    ON   p.id = oi.sku_id
                    INNER JOIN channels c 
                    ON c.id = o.channel_id 
                    LEFT JOIN channels_products cp 
                    ON cp.channel_sku=oi.item_sku  and o.channel_id=cp.channel_id
                    LEFT JOIN couriers cr 
                    ON cr.id = oi.courier_id
                    $join
                    " . $cond. " 
                    GROUP BY oi.order_id 
                    order by oi.item_created_at desc
                    ";
        $total_records= Yii::$app->db->createCommand($query)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $query .= "LIMIT " . $offset . ", $per_page";
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 1000000')->execute(); // this query is used to increase group concatitantion character limit for maximum data
        $get_record = Yii::$app->db->createCommand($query)->queryAll();
        $orders = $items = [];
        foreach ($get_record as $v){

            $orders[$v['order_id_pk']]=[
                'order_id_pk'=>$v['order_id_pk'],
                'order_number'=>$v['order_number'],
                'order_total'=>$v['order_total'],
                'order_status'=>$v['order_status'],
                'order_market_status'=>$v['order_market_status'],
                'channel_id'=>$v['channel_id'],
                'channel_name'=>$v['channel_name'],
                'marketplace'=>$v['marketplace'],
                'order_created_at'=>$v['order_created_at'],
                'order_updated_at'=>$v['order_updated_at'],
                'customer_type'=>$v['customer_type'],
                'items'=>self::arrange_items($v),
            ];
            /***if join with address table then below fields will come else not*/
            if(isset($v['shipping_city']))
                $orders[$v['order_id_pk']]['shipping_city']=$v['shipping_city'];
            if(isset($v['shipping_address']))
                $orders[$v['order_id_pk']]['shipping_address']=$v['shipping_address'];
            if(isset($v['shipping_number']))
                $orders[$v['order_id_pk']]['shipping_number']=$v['shipping_number'];
            if(isset($v['shipping_fname']))
                $orders[$v['order_id_pk']]['shipping_fname']=$v['shipping_fname'];
            if(isset($v['bulk_ship_status']))
                $orders[$v['order_id_pk']]['bulk_ship_status']=$v['bulk_ship_status'];

        }

        return ['orders'=>$orders,'total_records'=>$total_records];
    }

    private function arrange_items($v)
    {
        $items=[];
        if($v)
        {
            $item_id=explode('@!',$v['order_item_id_pk']);
            $item_name=explode('@!',$v['product_name']);
            $item_image=explode('@!',$v['product_image']);
            $item_sku=explode('@!',$v['sku']);
            $item_status=explode('@!',$v['item_status']);
            $item_market_status=explode('@!',$v['item_market_status']);
            $item_created_at=explode('@!',$v['item_created_at']);
            $tracking_number=explode('@!',$v['tracking_number']);
            $courier_type=explode('@!',$v['courier_type']);
            $courier_id=explode('@!',$v['item_courier_id']);
            $fulfilled_by_warehouse=explode('@!',$v['fulfilled_by_warehouse']);
            for($i=0;$i<count($item_id);$i++)
            {
                $items[]=[
                    'item_id_pk'=>$item_id[$i],
                    'item_name'=>isset($item_name[$i]) ? $item_name[$i]:'x' ,
                    'item_image'=>isset($item_image[$i]) ? $item_image[$i]:'x' ,
                    'item_sku'=>isset($item_sku[$i]) ? $item_sku[$i]:'x',
                    'item_status'=>isset($item_status[$i]) ? $item_status[$i]:'x',
                    'item_market_status'=>isset($item_market_status[$i]) ? $item_market_status[$i]:'x',
                    'item_created_at'=>isset($item_created_at[$i]) ? $item_created_at[$i]:'x',
                    'tracking_number'=>isset($tracking_number[$i]) ? $tracking_number[$i]:'x',
                    'courier_type'=>isset($courier_type[$i]) ? $courier_type[$i]:'x',
                    'courier_id'=>isset($courier_id[$i]) ? $courier_id[$i]:'x',
                    'fulfilled_by_warehouse'=>isset($fulfilled_by_warehouse[$i]) ? $fulfilled_by_warehouse[$i]:'x',
                ];
            }
            // print_r($items); die();
        }
        return $items;

    }

    /******
     * lcs load sheets of shipped orders
     */
    public function loadsheets()
    {
        $query="SELECT ols.*,c.name as courier_name
                FROM
                 `order_load_sheets` ols 
                LEFT JOIN
                `couriers` c
                ON `ols`.`courier_id`=`c`.`id`
                order by created_at DESC";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    /****
     * sheet pending orders
     */
    public function sheetPendingOrders()
    {
        $encryptionKey = Settings::GetDataEncryptionKey();
        $query="SELECT o.id,o.order_number ,o.order_created_at,o.order_total ,
                AES_DECRYPT(o.customer_fname,'".$encryptionKey."' ) as cust_fname,
                o.customer_lname as cust_lname,
                GROUP_CONCAT(IFNULL(os.added_at,'') SEPARATOR '@!') as items_shipped_at
                FROM
                  order_shipment os
                INNER JOIN 
                  order_items oi 
                ON `os`.`order_item_id`=`oi`.`id`
                INNER JOIN 
                    `couriers` c
                ON c.id=oi.courier_id
                INNER JOIN 
                  `orders` o 
                ON o.id=oi.order_id  
                WHERE `o`.`id` NOT IN (SELECT `order_id` FROM `load_sheet_order_list`)
                AND `os`.system_shipping_status IN ('shipped','completed') 
                AND c.type='lcs' AND os.exclude_from_load_sheet='0'
                GROUP BY o.id
                  ";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    /***
     * get load sheet packing slips
     */
    public function getLoadsheetSlips($sheet_pk_id)
    {
        $query="SELECT distinct(`os`.`packing_slip`) as slip 
                FROM 
                  `order_shipment` os
                INNER JOIN 
                    `order_items` oi 
                ON oi.id=os.order_item_id
                INNER JOIN 
                    `orders` o 
                ON  o.id=oi.order_id 
                WHERE o.id IN (SELECT order_id from load_sheet_order_list where load_sheet_id='".$sheet_pk_id."')";
        return Yii::$app->db->createCommand($query)->queryAll();
    }

}