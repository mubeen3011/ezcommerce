<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 8/26/2019
 * Time: 4:30 PM
 */
namespace backend\util;

use backend\controllers\MainController;
use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\Products;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use Yii;
use yii\db\Query;

class ThresholdUtil extends MainController
{
    public function getRecord()
    {
        $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        $filters=self::getRecordFilters();
        $subquery="";
        $offset="";
        $limit="LIMIT 20";
        if(isset($_GET['page'])){
            if($_GET['page']!='All'){
                $offset="OFFSET " .($_GET['page']*20);
                $limit="LIMIT 20";
            }
        }
        foreach($warehouses as $warehouse)
        {
            $warehouse['name'] = str_replace([' ' ,'-'],'_',$warehouse['name']);
            $subquery .="(SELECT wsl.available from warehouse_stock_list wsl WHERE wsl.warehouse_id='".$warehouse['id']."' AND wsl.sku=ts.sku) AS ".$warehouse['name']."_CurrentStock , ";
            $subquery .="(SELECT threshold_sales.status from threshold_sales WHERE threshold_sales.warehouse_id='".$warehouse['id']."' AND ts.sku=threshold_sales.sku) AS ".$warehouse['name']."_CurrentStatus , ";
            $subquery .="(SELECT (w.t1*threshold_sales.threshold) from threshold_sales  WHERE threshold_sales.warehouse_id='".$warehouse['id']."' AND ts.sku=threshold_sales.sku) AS ".$warehouse['name']."_T1 , ";
            $subquery .="(SELECT (w.t2*threshold_sales.threshold) from threshold_sales  WHERE threshold_sales.warehouse_id='".$warehouse['id']."' AND ts.sku=threshold_sales.sku) AS ".$warehouse['name']."_T2 ,";

        }
        $subquery=rtrim($subquery, ',');  // remove comma at the end
      //  echo $subquery; die();
        $subquery = ($subquery=='') ? '' : ', '.$subquery;
        $sql="SELECT `ts`.`sku` ,`p`.`is_active` as 'Active'
                $subquery
              From
                `threshold_sales` ts
              INNER JOIN
                `warehouses` w
               ON 
                `w`.`id` = `ts`.`warehouse_id`
                INNER JOIN products p 
               ON ts.sku = p.sku
               WHERE
                ".$filters['where']."
              GROUP BY
               `ts`.`sku`
               ".$filters['having']." $limit  $offset";
     //   echo $sql; die();
        $record = Yii::$app->db->createCommand($sql)->queryAll();
        //print_r($record); die();
        return $record;
    }

    public function getRecordFilters()
    {
        $where="1=1";

      //  print_r($_GET['is_active']); die();
        if(isset($_GET['sku']) && !empty($_GET['sku']))
        {
            $where .=" AND `ts`.`sku`='".$_GET['sku']."'";
        }
        if(isset($_GET['is_active']) &&  in_array($_GET['is_active'],array('1','0')))
        {
            $where .=" AND `p`.`is_active`='".$_GET['is_active']."'";
        }
        // having filters

        $warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        $having="";
        foreach($warehouses as $warehouse)
        {
            if(isset($_GET[$warehouse['name']."_CurrentStock"]) && !empty($_GET[$warehouse['name']."_CurrentStock"]))
            {
                $having_and=empty($having) ? "having ":" and ";
                $having .=$having_and. $warehouse['name']."_CurrentStock=" .$_GET[$warehouse['name']."_CurrentStock"];
            }
            if(isset($_GET[$warehouse['name']."_CurrentStatus"]) && !empty($_GET[$warehouse['name']."_CurrentStatus"]))
            {
                $having_and=empty($having) ? "having ":" and ";
                $having .=$having_and. $warehouse['name']."_CurrentStatus='" .$_GET[$warehouse['name']."_CurrentStatus"]."'";
            }
            if(isset($_GET[$warehouse['name']."-T1"]) && !empty($_GET[$warehouse['name']."-T1"]))
            {
                $having_and=empty($having) ? "having ":" and ";
                $having .=$having_and. $warehouse['name']."_T1=" .$_GET[$warehouse['name']."_T1"];
            }
            if(isset($_GET[$warehouse['name']."_T2"]) && !empty($_GET[$warehouse['name']."_T2"]))
            {
                $having_and=empty($having) ? "having ":" and ";
                $having .=$having_and. $warehouse['name']."_T2=" .$_GET[$warehouse['name']."_T2"];
            }
        }

        return $filter=['where'=>$where,'having'=>$having];
    }



    public static function getSKUActivestatuses()
    {
        return "<select name=\"is_active\" data-filter-type=\"operator\"
                                            class=\"filters-visible inputs-margin select-filter filter form-control \">
                                        <option class=\"\">Select</option>
                                        <option value=\"1\">Yes</option>
                                        <option value=\"0\">No</option>
                                    </select>";

    }

    public static function getSellingStatses()
    {

        //return '<select name="CurrentStock" data-filter-type="like" class="filters-visible inputs-margin select-filter filter form-control ">
                       return '   <option value="">Select</option>
                                        <option value="High">High</option>
                                        <option value="Medium">Medium</option>
                                        <option value="Slow">Slow</option>
                                        <option value="Not Moving">Not Moving</option>
                                        <option value="New">New</option>';
                                  //  </select>';
    }
}