<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 12/21/2020
 * Time: 5:53 PM
 */

namespace backend\util;
use common\models\CustomersAddress;
use common\models\OrderItems;
use common\models\Orders;
use common\models\search\OrderItemsSearch;
use Yii;


class TestUtil
{

    public static function move_orders()
    {
        for($month=1;$month<=12;$month++):
            $query="SELECT * FROM `orders`  WHERE  year(order_created_at)=2019 AND MONTH(order_created_at) in(10,11,09,08,07,05,12)
        ORDER BY RAND() LIMIT 250";
            $connection = Yii::$app->db;
            $command = $connection->createCommand( $query);
            $result = $command->queryAll();
            // echo count($result); die();
            if($result)
            {
                foreach($result as $order)
                {
                    /////insert orders/////////
                    $month_s=$month < 10 ? "0".$month:$month;
                    $add_date="2020-".$month_s."-".date('d H:i:s',strtotime($order['order_created_at']));
                    $update_date="2020-".$month_s."-".date('d H:i:s',strtotime($order['order_updated_at']));
                    $order_id=self::save_order($order,$add_date,$update_date);
                    // echo $order_id; die();
                    if($order_id)
                    {
                        $customers="SELECT * FROM `customers_address` where order_id='".$order['id']."'";
                        $command = $connection->createCommand( $customers);
                        $customer = $command->queryone();
                        //  self::debug($customer);
                        self::save_customer($customer,$order_id,$add_date,$update_date);
                        $order_items="SELECT * FROM `order_items` where order_id='".$order['id']."'";
                        $command = $connection->createCommand( $order_items);
                        $order_items = $command->queryAll();
                        // self::debug($order_id);
                        self::save_order_items($order_items,$order_id,$add_date,$update_date);
                    }
                }

            }
        endfor;
    }

    private static function formula($percent,$main_sale,$current_sale)
    {
        $prcnt_amount=round(($percent/100)*$main_sale);
        $limit=round($main_sale-$prcnt_amount);
        if($current_sale >=$limit)
            return false;
        else
            return true;


    }

    public static function GetCanceledStatuses()
    {

        return '"'.implode('","',Yii::$app->params['cancel_statuses'] ).'"';

    }
    //replicate orders in previous year from current year
    public static function replicate_orders()
    {
        $year="2018"; // replicate to
        for($month=1;$month<=12;$month++):
            $query="SELECT * FROM `orders`   ORDER BY RAND()";
            $connection = Yii::$app->db;
            $command = $connection->createCommand( $query);
            $result = $command->queryAll();
            //echo count($result);
           // die();
            $current_month_sale=0;
            if($result)
            {
                foreach($result as $order)
                {
                    $query="SELECT round(sum(`order_total`)) as total_sale FROM `orders`  WHERE order_status NOT IN(".self::GetCanceledStatuses().") 
                              and year(`order_created_at`)='".($year+1)."' AND month(`order_created_at`)='".$month."'";
                    //echo $query;
                   // die();
                    $command = $connection->createCommand( $query);
                    $total_month_sale= $command->queryone();
                   // echo $total_month_sale['total_sale'];
                    //die();
                    $current_month_sale +=round($order['order_total']);
                    if(self::formula(12,$total_month_sale['total_sale'],$current_month_sale)===false){
                    continue;
                    }
                    /*echo "yes";
                    echo "<br/>";
                    echo "sale ".$current_month_sale;
                    echo "<br/>";*/
                  //  die('aja');
                    /////insert orders/////////
                    $month_s=$month < 10 ? "0".$month:$month;
                    $add_date=$year."-".$month_s."-".date('d H:i:s',strtotime($order['order_created_at']));
                    $update_date=$year."-".$month_s."-".date('d H:i:s',strtotime($order['order_updated_at']));
                  //  echo $add_date;
                  ///  echo "<br/>";
                  //  echo $update_date;
                  //  echo "<br/>";
                  //  die();
                    $order_id=self::save_order($order,$add_date,$update_date);
                     //echo $order_id; die();
                    if($order_id)
                    {
                        $customers="SELECT * FROM `customers_address` where order_id='".$order['id']."'";
                        $command = $connection->createCommand( $customers);
                        $customer = $command->queryone();
                        //  self::debug($customer);
                        self::save_customer($customer,$order_id,$add_date,$update_date);
                        $order_items="SELECT * FROM `order_items` where order_id='".$order['id']."'";
                        $command = $connection->createCommand( $order_items);
                        $order_items = $command->queryAll();
                        // self::debug($order_id);
                        self::save_order_items($order_items,$order_id,$add_date,$update_date);
                    }
                }

            }
        endfor;
    }

    private static function save_order($detail,$add_date,$update_date)
    {
        if($detail['channel_id']==16) //presta
        {
            $range = range('A', 'Z');
            $replace= $range[array_rand($range)].$range[array_rand($range)].$range[array_rand($range)].$range[array_rand($range)].$range[array_rand($range)];
            $order_number=substr_replace($detail['order_number'],$replace,0,5);
        } else{
            $digits = 5;
            $replace=rand(pow(10, $digits-1), pow(10, $digits)-1);
            $order_number=substr_replace($detail['order_number'],$replace,0,5);
        }
        $already=Orders::findOne(['order_id'=>$order_number]);
        if($already)
            $order_number=str_shuffle($order_number);

        $orders = new Orders();
        $orders->channel_id = $detail['channel_id'];
        $orders->order_id = $order_number;
        $orders->order_number = $order_number;
        $orders->customer_fname = $detail['customer_fname'];
        $orders->customer_lname = $detail['customer_lname'];
        $orders->payment_method = $detail['payment_method'];
        $orders->order_total =round( $detail['order_total'],2);
        $orders->order_created_at = $add_date;
        $orders->order_updated_at = $update_date;
        $orders->customer_type=$detail['customer_type']; // customer_type will only have B2B or B2C
        $orders->coupon_code=$detail['coupon_code'];
        $orders->order_status =$detail['order_status'] ; // marketplace status mapped with our statuses
        $orders->order_market_status = $detail['order_market_status'];  // original marketplace status
        $orders->order_count = $detail['order_count'];
        $orders->order_shipping_fee = $detail['order_shipping_fee'];
        $orders->order_discount = $detail['order_shipping_fee'];
        $orders->full_response = $detail['full_response'];
        $orders->created_at =strtotime($add_date);
        $orders->updated_at = strtotime($update_date);
        $orders->is_update = $detail['is_update'];   //cron job
        $orders->created_by = 33;  //cron job
        $orders->updated_by = 33;   //cron job
        if ($orders->save(false))
            return  $orders->id;
        else
            return null;
    }

    private static function save_customer($cust,$new_order_id,$add_date,$update_date)
    {
        $ca=new CustomersAddress();
        $ca->shipping_fname = $cust['shipping_fname'];
        $ca->shipping_lname = $cust['shipping_lname'];
        $ca->shipping_number =$cust['shipping_number'];
        $ca->shipping_email = $cust['shipping_email'];
        $ca->shipping_address = $cust['shipping_address'];
        $ca->shipping_state =$cust['shipping_state'];
        $ca->shipping_city = $cust['shipping_city'];
        $ca->shipping_country = $cust['shipping_country'];
        $ca->shipping_post_code =$cust['shipping_post_code'];
        $ca->billing_fname =$cust['billing_fname'];
        $ca->billing_lname = $cust['billing_lname'];
        $ca->billing_number = $cust['billing_number'];
        $ca->billing_email = $cust['billing_email'];
        $ca->billing_address = $cust['billing_address'];
        $ca->billing_state =$cust['billing_state'];
        $ca->billing_city = $cust['billing_city'];
        $ca->billing_country = $cust['billing_country'];
        $ca->billing_postal_code = $cust['billing_postal_code'];
        $ca->created_at = strtotime($add_date);
        $ca->updated_at = strtotime($update_date);
        $ca->created_by = $cust['created_by'];
        $ca->updated_by = $cust['updated_by'];
        $ca->order_id = $new_order_id;
        $ca->save(false);
        return;
    }

    private static function save_order_items($items,$new_order_id,$add_date,$update_date)
    {
        //$item="";
        foreach($items as $item):
            $items = new OrderItems();
            $items->order_id = $new_order_id;
            $items->sku_id = $item['sku_id'];
            $items->order_item_id = $item['order_item_id'];
            $items->item_status =$item['item_status'];
            $items->item_market_status = $item['item_market_status']; // original marketplace status
            $items->shop_sku = $item['shop_sku'];
            $items->price = $item['price'];
            $items->paid_price = $item['paid_price'];
            $items->shipping_amount = $item['shipping_amount'];
            $items->item_discount = $item['item_discount'];
            $items->sub_total=$item['sub_total'];
            $items->fulfilled_by_warehouse = $item['fulfilled_by_warehouse'];
            $items->item_created_at = $add_date;
            $items->item_updated_at = $update_date;
            $items->quantity = $item['quantity'];
            $items->tracking_number = $item['tracking_number'];
            $items->item_tax = $item['item_tax'];
            $items->item_sku = $item['item_sku'];
            $items->full_response = $item['full_response'];
            $items->created_by = 33;  //cron job
            $items->updated_by = 33;   //cron job
            $items->created_at = strtotime($add_date);
            $items->updated_at = strtotime($update_date);

            if (!$items->save(false))
                print_r($items->getErrors());
        endforeach;
        return ;
    }
}