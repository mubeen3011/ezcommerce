<?php
/**
 * Created by PhpStorm.
 * User: Abdullah
 * Date: 5/4/2018
 * Time: 11:43 AM
 */
namespace backend\controllers;

use backend\util\HelpUtil;
use common\models\Settings;
use yii\web\Controller;

class CrmController extends Controller{

    public function Authenticate(){

        if ( isset($_GET['API_KEY']) ){
            $CRM_KEY = Settings::find()->where(['name'=>'CRM_KEY','value'=>$_GET['API_KEY']])->asArray()->all();
            if ( empty($CRM_KEY) ){
                return json_encode(
                    [
                        'status' => 'failed',
                        'details'=>['msg'=>'Api key is invalid or not found. Please try again later.']
                    ]
                );
            }else{
                return json_encode($CRM_KEY);
            }
        }else{
            return json_encode(
                [
                    'status' => 'failed',
                    'details'=>['msg'=>'Api key is invalid or not found. Please try again later.']
                ]
            );
        }

    }
    public function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }
    public function actionGetOrdersList(){
        $Authenticate = $this->Authenticate();
        $Authenticate = json_decode($Authenticate);
        //echo '<pre>';print_r($Authenticate);die;
        if ( !isset($Authenticate->status) ){
            $OrdersFromLast = '-1 day';
            $encryptionKey = Settings::GetDataEncryptionKey();
            $From = date('Y-m-d H:i:s' , strtotime($OrdersFromLast));
            $To = time()+36000; // add ten hours forward
            //$From = date('2020-10-13 H:i:s');
            $connection = \Yii::$app->db;
            $sql = "SELECT 
                o.`order_id`,
                o.`order_number`,
                c.`name` as channel_name,
                c.marketplace,
                o.order_shipping_fee,
                o.`channel_id`,
                AES_DECRYPT(ca.`shipping_fname`, '".$encryptionKey."') AS customer_fname, 
                AES_DECRYPT(ca.`shipping_lname`, '".$encryptionKey."') AS customer_lname, 
                AES_DECRYPT( ca.`billing_number` , '".$encryptionKey."') as phone,
                AES_DECRYPT( ca.`billing_email` , '".$encryptionKey."') as email,
                o.`order_total`,o.`order_created_at`,
                o.`order_updated_at`,
                o.`order_status`,
                AES_DECRYPT( ca.`shipping_city` , '".$encryptionKey."') as shipping_city,
                AES_DECRYPT( ca.`shipping_country` , '".$encryptionKey."') as shipping_country,
                AES_DECRYPT( ca.`shipping_address` , '".$encryptionKey."') as shipping_address,
                oi.`order_item_id`,
                oi.`shop_sku`,
                oi.`price`,
                oi.`paid_price`,
                oi.quantity,
                oi.`item_status`,
                oi.`item_sku`
                FROM orders o
                INNER JOIN `customers_address` ca ON o.`id` = ca.order_id
                INNER JOIN order_items oi ON oi.`order_id` = o.`id`
                INNER JOIN channels c ON c.id = o.channel_id
                WHERE o.`order_updated_at` BETWEEN '$From' AND '".date('Y-m-d H:i:s',$To)."'
                ORDER BY o.`id` DESC";
            //echo $sql;die;
            $getOrders = $connection->createCommand($sql);
            $result_data = $getOrders->queryAll();
            $redefineOrders=[];
            foreach ( $result_data as $key=>$value ) {
                $redefineOrders[$value['order_id']][] = $value;
            }
            return json_encode($redefineOrders);
        }
        else{
            return json_encode($Authenticate);
        }

    }
    public function actionGetProductList(){
        $Authenticate = $this->Authenticate();
        $Authenticate = json_decode($Authenticate);
        if ( !isset($Authenticate->status) ){
            $connection = \Yii::$app->db;
            $sql = "SELECT pcp.sku,pcp.name as sku_name,c.name as category_name
                    FROM products pcp
                    INNER JOIN category c ON c.id = pcp.sub_category";
            $getOrders = $connection->createCommand($sql);
            $result_data = $getOrders->queryAll();
            return json_encode($result_data);
        }else{
            return json_encode($Authenticate);
        }


    }
    public function actionGetCategoryList(){
        $Authenticate = $this->Authenticate();
        $Authenticate = json_decode($Authenticate);
        if ( !isset($Authenticate->status) ){
            $connection = \Yii::$app->db;
            $sql = "SELECT c1.name AS subcat, c2.name AS parent
                                FROM category c1
                                LEFT OUTER
                                JOIN category c2 ON c1.parent_id = c2.id";
            $getCategories = $connection->createCommand($sql);
            $result_data = $getCategories->queryAll();
            return json_encode($result_data);
        }else{
            return json_encode($Authenticate);
        }


    }
    public function actionCurrentUnderStockOrders(){
        $records=HelpUtil::getNevMarginOrder();
        return json_encode($records);
    }
    public function actionGetCustomerInformation(){
        $OrderId=$_GET['order_id'];
        $O_id = $this->exchange_values('order_id','id',$OrderId,'orders');
        $OrderCustomersAddress = $this->exchange_values('orders_id','customer_address_id',$O_id,'orders_customers_address');

        /**
         * get the customer information
         * */
        $connection = \Yii::$app->db;
        $sql = "select * from customers_address where id = ".$OrderCustomersAddress;
        $getOrders = $connection->createCommand($sql);
        $result_data = $getOrders->queryAll();
        $CustomerInfo=[
            'name'=>$result_data[0]['billing_fname'],
            'phone'=>$result_data[0]['billing_number'],
            'city'=>$result_data[0]['billing_city'],
            'country'=>$result_data[0]['billing_country'],
        ];

        return json_encode($CustomerInfo);

    }
    public function actionGetShops(){

        $Authenticate = $this->Authenticate();
        $Authenticate = json_decode($Authenticate);

        if ( !isset($Authenticate->status) ){
            $cond = '';

            if ( isset($_GET['marketplace']) && $_GET['marketplace']!='' ){
                $cond .= " AND c.marketplace = '".$_GET['marketplace']."'";
            }
            $connection = \Yii::$app->db;
            $Sql = "SELECT * FROM channels c WHERE c.is_active = 1 ".$cond;
            $results = $connection->createCommand($Sql);
            $result_data = $results->queryAll();
            return json_encode($result_data);
        }else{
            return json_encode($Authenticate);
        }
    }
}