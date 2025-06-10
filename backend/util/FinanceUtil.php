<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/10/2020
 * Time: 12:23 PM
 */
namespace backend\util;

class FinanceUtil {
    public static function SelectReleventColumns($table){

        $cols=[];
        // remove WHT columns
        foreach ($table as $colName=>$index){
            if ( (strpos($colName, ' - WHT ') !== false) || $colName=='lazada_sku' || $colName=='shipment_type' || $colName=='shipping_provider'
                || $colName=='paid_status' || $colName=='reference' || $colName=='shipping_speed' || $colName=='details' || $colName=='commission_percentage' ) {
                continue;
            }else{
                $cols[] = $colName;
            }
        }
        $cols = array_flip($cols);
        foreach ( $cols as $colName=>$index ){
            $cols[$colName]=" fr.`".$colName."` ";
        }
        $cols_str = implode(',',$cols);
        return $cols_str;

    }
    public static function GetReport( $filters, $getCompleteData=0 ){

        $table = HelpUtil::GetTableColumns('lazada_finance_report');
        $table = array_flip($table);
        $table = self::SelectReleventColumns($table);

        $Sql = "SELECT oi.item_created_at as order_create_date,".$table." FROM order_items oi
                INNER JOIN lazada_finance_report fr ON
                oi.order_item_id = fr.order_item_id
                WHERE 1=1 ";
        $cond = "";

        if ( $filters ){
            if ( isset($filters['channel']) ){
                $cond .= " AND fr.channel_id = ".$filters['channel'];
            }
            if ( isset($filters['Date_range']) ){
                $reformat = explode(' to ', $_GET['Date_range']);
                $from = $reformat[0];
                $to = $reformat[1];
                $cond .= " AND oi.item_created_at BETWEEN '".$from." 00:00:00' AND '".$to." 23:59:59' ";
            }

        }


        $Sql .= $cond;

        $totalRowsFound = \Yii::$app->db->createCommand($Sql)->queryAll();
        $totalRowsFound = count($totalRowsFound);


        if ($_GET['page'] == 'All' || $getCompleteData==1) {
            $cond .= "";
        } else {
            $offset = 10 * ($_GET['page'] - 1);
            $cond .= " LIMIT " . $offset . ",20 ;";
        }


        $Sql .= $cond;
        //echo $Sql;die;
        $result = \Yii::$app->db->createCommand($Sql)->queryAll();

        // Add total fee index
        foreach ( $result as $key=>$value ){
            $total_fee=0;
            foreach ( $value as $colname=>$col_val ){
                if(strpos($colname, ' - Amount') !== false){
                    $total_fee += $col_val;
                }
            }
            $result[$key]['total_feeses'] = $total_fee;
            $result[$key]['receiving_difference'] = $total_fee - $value['expected_receive_amount'];
            $result[$key]['receiving_difference'] = number_format($result[$key]['receiving_difference'],2);
        }
        //echo '<pre>';print_r($result);die;


        foreach ( $result as $key=>$value ){
            unset($result[$key]['id']);
            unset($result[$key]['channel_id']);
        }

        return ['report'=>$result,'total_records'=>$totalRowsFound];
    }

}