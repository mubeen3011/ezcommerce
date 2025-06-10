<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/16/2018
 * Time: 10:44 AM
 */

namespace backend\util;

use common\models\Category;
use common\models\Channels;
use common\models\Orders;
use common\models\SalesTargets;
use common\models\SalesTargetDetail;
use common\models\Settings;
use common\models\SkuSalesTarget;
use Faker\Provider\DateTime;
use Yii;

class SalesUtil
{
    /**
     * @return string
     */

    private static function _select_monthly_query($get_target_table='yes',$get_current_sales_table='yes',$get_prev_sales_table='yes',$sales_portion='yes',$stock_portion='yes')
    {
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $select="";
        for($i=0;$i<12;$i++)
        {
            //// target record
            if($get_target_table=='yes')
            {
                if($sales_portion=='yes')
                    $select .= 'IFNULL(SUM(`sst`.'.$month[$i].'_sales_target),0) as '.$month[$i].'_sales_target,';
                if($stock_portion=='yes')
                    $select .= 'IFNULL(SUM(`sst`.'.$month[$i].'_qty_target),0) as '.$month[$i].'_qty_target,';
            }

            // previuos year sales record
            if($get_prev_sales_table=='yes')
            {
                if($sales_portion=='yes')
                 $select .= 'IFNULL(SUM(`sa`.' . $month[$i] . '_sales),0) as ' . $month[$i] . '_sales,';
                if($stock_portion=='yes')
                $select .= 'IFNULL(SUM(`sa`.' . $month[$i] . '_qty_sold),0) as ' . $month[$i] . '_qty_sold,';
            }

            // current year sales
            if($get_current_sales_table=='yes')
            {
                if($sales_portion=='yes')
                 $select .= 'IFNULL(SUM(`current_sales`.' . $month[$i] . '_sales),0) as ' . $month[$i] . '_current_sales,';
                if($stock_portion=='yes')
                    $select .= 'IFNULL(SUM(`current_sales`.' . $month[$i] . '_qty_sold),0) as ' . $month[$i] . '_current_qty_sold,';
            }
        }
        return $select;
    }

    /**
     * @return string
     *  if only target then select only from target table ,
     * @$sales_detail_only mean only pick monetory value not qty/stock
     * $only_target means only target table not archive table
     */

    private static function _select_quarterly_query($get_target_table='yes',$get_current_sales_table='yes',$get_prev_sales_table='yes',$sales_portion='yes',$stock_portion='yes')
    {
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $select="";

        if($get_target_table=='yes')
        {
            $table='sst';
            if($sales_portion=='yes') {
                $prefix = 'sales_target';
                $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $prefix . ' ,';
            } if($stock_portion=='yes') {
                $prefix = 'qty_target';
                $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $prefix . ' ,';
            }
        }

        // previuos year sales
        if($get_prev_sales_table=='yes')
        {
            $table = 'sa';
            if($sales_portion=='yes') {
                $prefix = 'sales';
                $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $prefix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $prefix . ' ,';
            } if($stock_portion=='yes') {
            $prefix = 'qty_sold';
            $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $prefix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $prefix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $prefix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $prefix . ' ,';
        }
        }

        // current year sales
        if($get_current_sales_table=='yes')
        {
            $table = 'current_sales';
            if($sales_portion=='yes') {
                $prefix = 'sales';
                $postfix = 'current_sales';
                $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $postfix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $postfix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $postfix . ' ,';
                $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $postfix . ' ,';
            } if($stock_portion=='yes') {
            $prefix = 'qty_sold';
            $postfix = 'current_qty_sold';
            $select .= 'IFNULL(SUM(' . $table . '.january_' . $prefix . ') + SUM(' . $table . '.february_' . $prefix . ') + SUM(' . $table . '.march_' . $prefix . '),0) as first_quarter_' . $postfix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.april_' . $prefix . ') + SUM(' . $table . '.may_' . $prefix . ') + SUM(' . $table . '.june_' . $prefix . '),0) as second_quarter_' . $postfix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.july_' . $prefix . ') + SUM(' . $table . '.august_' . $prefix . ') + SUM(' . $table . '.september_' . $prefix . '),0) as third_quarter_' . $postfix . ' ,';
            $select .= 'IFNULL(SUM(' . $table . '.october_' . $prefix . ') + SUM(' . $table . '.november_' . $prefix . ') + SUM(' . $table . '.december_' . $prefix . '),0) as fourth_quarter_' . $postfix . ' ,';
        }
        }


        return $select;
    }

    /**
     * @param null $get_variable
     * @return string
     * returns if any operator appended to get variable in filter
     */
    private function get_operator($get_variable=null)
    {

        if($get_variable)
        {
            $operator=substr($get_variable,0,2);

            if(in_array($operator,['>=','<=']))  // if any of these 2 char  operator sent
                return $operator;

            $operator=substr($get_variable,0,1);
            if(in_array($operator,['=','>','<']))  // if any of these single operator sent
                return $operator;

        }
        return "="; // default will be equal
    }



    /**
     * @param $month
     * @return string
     * having query for filters of target detail page for target monthly based fo sales monetory value
     */
    private static function _make_having_monthly()
    {
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $having="";
        $and= ""; // for multiple having
        /////////////////
        if(isset($_GET['view_type']) && $_GET['view_type']=='stock') // if stock wise filter
        {
            $prefix='_qty_target';  // by default target column prefix
            if(isset($_GET['search-scale']) && $_GET['search-scale']=='prior') // if previous year sales selected
                $prefix='_qty_sold';
        } else {
            $prefix='_sales_target';  // by default target column prefix
            if(isset($_GET['search-scale']) && $_GET['search-scale']=='prior') // if previous year sales selected
                $prefix='_sales';
        }
        /// ///////////////
        // month filters handling
        for($m=0; $m < 12; $m++)
        {
            if(isset($_GET['search-month-'.$month[$m]]) && $_GET['search-month-'.$month[$m]]!=''){
                if($having && !$and)  // put and between multiple having
                    $and =" AND ";
                if(!$having)  // put having in start
                    $having=" Having ";

                $operator=self::get_operator($_GET['search-month-'.$month[$m]]); // get operator (=,>=,<=,>,>)
                $having .= $and . " `".$month[$m].$prefix."`$operator'".str_replace($operator,'',$_GET['search-month-'.$month[$m]])."'";  // make full column name
            }

        }
        /// if total of rows search query sent

        if(isset($_GET['search-total']) && $_GET['search-total']!='')
        {
            if($having && !$and)  // put and between multiple having
                $and =" AND ";
            if(!$having)  // put having in start
                $having=" Having ";

            $operator=self::get_operator($_GET['search-total']); // get operator (=,>=,<=,>,>)
            $sum_months="";
            for($m=0; $m < 12; $m++) // sum of all months
            {
                $sum_months .= $month[$m].$prefix ;
                if($m < 11)
                    $sum_months .=" + ";
            }
            $having .=$and. " ( ".$sum_months ." ) " .$operator.str_replace($operator,'',$_GET['search-total']);
        }

        return $having;
    }



    /**
     * @return string
     * for quarter sales having
     */
    public static  function _make_having_quarterly()
    {
        $quarter=['first_quarter','second_quarter','third_quarter','fourth_quarter'];
        $having="";
        $and= ""; // for multiple having
        ////////////
        if(isset($_GET['view_type']) && $_GET['view_type']=='stock') // if stock wise filter
        {
            $prefix='_qty_target';  // by default target column prefix
            if(isset($_GET['search-scale']) && $_GET['search-scale']=='prior') // if previous year sales selected
                $prefix='_qty_sold';
        } else {
            $prefix='_sales_target';  // by default target column prefix
            if(isset($_GET['search-scale']) && $_GET['search-scale']=='prior') // if previous year sales selected
                $prefix='_sales';
        }
        /// /////////
        // quarter filters handling
        for($m=0; $m < 4; $m++)
        {
            if(isset($_GET['search-month-'.$quarter[$m]]) && $_GET['search-month-'.$quarter[$m]]!=''){
                if($having && !$and)  // put and between multiple having
                    $and =" AND ";
                if(!$having)  // put having in start
                    $having=" Having ";

                $operator=self::get_operator($_GET['search-month-'.$quarter[$m]]); // get operator (=,>=,<=,>,>)
                $having .= $and . " `".$quarter[$m].$prefix."`$operator'".str_replace($operator,'',$_GET['search-month-'.$quarter[$m]])."'";  // make full column name
            }

        }
        /// if total of rows search query sent

        if(isset($_GET['search-total']) && $_GET['search-total']!='')
        {
            if($having && !$and)  // put and between multiple having
                $and =" AND ";
            if(!$having)  // put having in start
                $having=" Having ";

            $operator=self::get_operator($_GET['search-total']); // get operator (=,>=,<=,>,>)
            $sum_quarter="";
            for($m=0; $m < 4; $m++) // sum of all quarters
            {
                $sum_quarter .= $quarter[$m].$prefix ;
                if($m < 3)
                    $sum_quarter .=" + ";
            }
            $having .=$and. " ( ".$sum_quarter ." ) " .$operator.str_replace($operator,'',$_GET['search-total']);
        }

        return $having;
    }



    /*
     * get sales target list
     */
    public static function getSalesAndTarget($target_id)
    {

        $where="1=1";
        $having="";
        if(isset($_GET['channel_id']) && !empty($_GET['channel_id']))
            $where .=" AND `sst`.`channel_id`='".$_GET['channel_id']."'";

        if(isset($_GET['sku']) && !empty($_GET['sku']))
            $where .=" AND `sst`.`sku`='".$_GET['sku']."'";

        if(isset($_GET['display_view']) && $_GET['display_view']=='quarterly') {  // make quarterly  select and having
            $select =  self::_select_quarterly_query();
            $having .= self:: _make_having_quarterly();

        } else { // make monthly  select and having
             $select =  self::_select_monthly_query();
             $having .= self::_make_having_monthly();  // monthly
         }



        $sql="SELECT 
                
                  $select
                  `sst`.`sku`,`sst`.`markup`,`st`.`year`,`p`.`image`,`p`.`name` as product_name
                FROM
                  `sku_sales_target` sst
                INNER JOIN 
                    `sales_targets` st
                 ON   
                  `sst`.`target_id`=`st`.`id` 
                LEFT JOIN
                `sku_sales_archive` sa
                ON 
                  `sst`.`sku`=`sa`.`sku` AND `sst`.`channel_id`=`sa`.`channel_id` AND `sa`.`year`=`st`.`year_compared`
                LEFT JOIN
                  `sku_sales_archive` current_sales
                ON 
                  `sst`.`sku`=`current_sales`.`sku`  
                   AND `current_sales`.`year`=`st`.`year`
                   AND `sst`.`channel_id`=`current_sales`.`channel_id`
                 LEFT JOIN 
                  `products` p 
                 ON 
                  `sst`.`sku`=`p`.`sku`
                 WHERE
                 $where
                  /*`std`.`sku`='0U-014X-8XHX' */
                  AND `sst`.`target_id`='".$target_id."'
                /*AND `sa`.`year`=`st`.`year_compared`*/
                GROUP BY(`sst`.`sku`)
                $having";
//echo $sql; die();
        $total_records = Yii::$app->db->createCommand($sql)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:20;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 20 * ($page - 1);
        $sql .= " LIMIT " . $offset . ", $per_page";

        $records=Yii::$app->db->createCommand($sql)->queryAll();
        return [
            'total_records'=>$total_records,
            'records'=>$records,
        ];
    }

    private static function _overall_total_yearly_select($month)
    {
        $select="";
        for($i=0;$i<12;$i++)
        {
            $select .= 'SUM(`sst`.'.$month[$i].'_sales_target) ';
            if($i<11)
                $select .=" + ";

        }
        $select .=" as total_sales_target ,";
        for($i=0;$i<12;$i++)
        {
            $select .= 'SUM(`sst`.'.$month[$i].'_qty_target) ';
            if($i<11)
                $select .=" + ";

        }
        $select .=" as total_qty_target ,";

        for($i=0;$i<12;$i++)
        {
            $select .= 'SUM(`sa`.'.$month[$i].'_sales) ';
            if($i<11)
                $select .=" + ";

        }

        $select .=" as total_prior_sales ,";
        for($i=0;$i<12;$i++)
        {
            $select .= 'SUM(`sa`.'.$month[$i].'_qty_sold) ';
            if($i<11)
                $select .=" + ";

        }

        $select .=" as total_prior_qty_sold ";
        return $select;
    }


    /***
     * get overall sum
     */

    public static function OverallTotal($target_id,$type)
    {
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        if($type=='yearly')
            $select=self::_overall_total_yearly_select($month);
        if($type=='monthly')
            $select=self::_select_monthly_query();


        $where="1=1";
        if(isset($_GET['channel_id']) && !empty($_GET['channel_id']))
            $where .=" AND `sst`.`channel_id`='".$_GET['channel_id']."'";
        if(isset($_GET['sku']) && !empty($_GET['sku']))
            $where .=" AND `sst`.`sku`='".$_GET['sku']."'";

        $sql="SELECT 
                  $select
                FROM
                  `sku_sales_target` sst
                INNER JOIN 
                    `sales_targets` st
                 ON   
                  `sst`.`target_id`=`st`.`id` 
                LEFT JOIN
                `sku_sales_archive` sa
                ON 
                  `sst`.`sku`=`sa`.`sku` AND `sst`.`channel_id`=`sa`.`channel_id` AND `sa`.`year`=`st`.`year_compared`
                 WHERE
                 $where
                  /*`std`.`sku`='0U-014X-8XHX' */
                  AND `sst`.`target_id`='".$target_id."'
                  /*AND `sa`.`year`=`st`.`year_compared`*/";
       // echo $sql; die();
        return Yii::$app->db->createCommand($sql)->queryAll();
    }
    /***
     * get  monthly sales based on skus
     */

    public static function getMonthlySkuSales($year,$month)
    {

        $sql="SELECT `oi`.`item_sku` as sku,(SUM(`oi`.`paid_price` * `oi`.`quantity`)) as avg_sales ,
                    SUM(`oi`.`quantity`) as qty_sold,MONTHNAME(`oi`.`item_created_at`) as month, Year(`oi`.`item_created_at`) as year
                     ,o.channel_id
                FROM
                     `order_items` oi
                INNER JOIN
                    `orders` o
                ON 
                  `oi`.`order_id`=`o`.`id`	   
               WHERE 
                    MONTHNAME(`oi`.`item_created_at`)='$month'
                    AND 
                    Year(`oi`.`item_created_at`)='$year'
                    AND 
                   `oi`.`item_status` NOT IN(".'"'.implode('","',Yii::$app->params['cancel_statuses'] ).'"'.")
                GROUP BY
                    `oi`.`item_sku`, o.channel_id
                ORDER BY 
                    qty_sold desc";

        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    /**
     * insert monthly sku sales ito db
     */
    public static function saveMonthlySkuSales($record)
    {
        $updated_rec=$inserted_rec=0; //count how many updated and inserted
        if($record)
        {
           // $month=strtolower($month);
            foreach($record as $item)
            {
                $sql="SELECT `id` FROM `sku_sales_archive`
                      where
                        `sku`='".$item['sku']."'
                       AND
                        `channel_id`='".$item['channel_id']."'
                        AND 
                        `year`='".$item['year']."'";
                $count=Yii::$app->db->createCommand($sql)->query()->count();
                if($count > 0)
                {
                    $updated=yii::$app->db->createCommand()->update('sku_sales_archive',
                                                    [  'sku'=>$item['sku'],
                                                       'channel_id'=>$item['channel_id'],
                                                        'year'=>$item['year'],
                                                        strtolower($item['month']).'_sales'=>$item['avg_sales'] ? $item['avg_sales']:0,
                                                        strtolower($item['month']).'_qty_sold'=>$item['qty_sold'] ? $item['qty_sold']:0,
                                                    ],
                                                    ['sku'=>$item['sku'],'channel_id'=>$item['channel_id'],'year'=>$item['year']])->execute();
                    if($updated)
                        $updated_rec++;
                }

                else
                {
                    $inserted=yii::$app->db->createCommand()->insert('sku_sales_archive',
                                                                ['sku'=>$item['sku'],
                                                                    'channel_id'=>$item['channel_id'],
                                                                    'year'=>$item['year'],
                                                                    strtolower($item['month']).'_sales'=>$item['avg_sales'] ? $item['avg_sales']:0,
                                                                    strtolower($item['month']).'_qty_sold'=>$item['qty_sold'] ? $item['qty_sold']:0,
                                                                ])->execute();
                    if($inserted)
                        $inserted_rec++;
                }

            }
        }
        return['updated'=>$updated_rec,'inserted'=>$inserted_rec];
    }



   /*
    * fetch sales archive according to filtered given
    */
   public static function fetchSalesArchiveAccordingToTarget($target=null)
   {
       if($target):
          // $select=self::makeSalesArchiveFilter($target);
           $where=" `ssa`.`year`='".$target->prior_year."'";  // which year sales to pick
           $where .=" AND  `p`.`is_active`='1'";  // which year sales to pick
            $sql="SELECT ssa.*,`p`.`sub_category` as cat_id
                    FROM
                         `sku_sales_archive` ssa
                         INNER JOIN 
                          `products` p 
                         ON 
                          `p`.`sku`=`ssa`.`sku`
                        WHERE
                         $where
                         /*`sku`='0U-014X-8XHX'*/
                         GROUP BY `ssa`.`id`";
           // echo $sql; die();
           return yii::$app->db->createCommand($sql)->queryAll();
       endif;

       return false;

   }

    /**
     *  marketplace wise fetch channels and its markup
     */
    public static function MarketplaceChannelsAndMarkups($marketplaces)
    {
        $channels=[];
        $query=Channels::find()->select(['id','name','marketplace'])->where(['is_active'=>1])->asArray()->all();
        foreach ($query as $channel)
        {

            $index_of_markup=array_search($channel['marketplace'],$marketplaces['marketplace_names']);
             $channels[$channel['id']]=$marketplaces['markup_values'][$index_of_markup];

        }
        return $channels;
    }

   /**
    * save sales target sku based
    */
    public static function SaveSalesTargetSkuBased($target,$sales,$markups)
    {
       // print_r($markups); die();
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $count=0;
        if($target && $sales):
            foreach($sales as $sale)
            {
                if($target->type=="overall")
                    $markup=$markups; // direct single value sent in param
                if(in_array($target->type,['marketplace','channel'])) // if channelwise or marketplace wise markup set
                    $markup=$markups[$sale['channel_id']]; // index has channel id and value has markup value

                if($target->type=="category") { // if categorywise set
                    if($sale['cat_id'])
                        $markup = $markups[$sale['cat_id']]; // index has cat id and value has markup value
                    else
                        continue; // if category not set then skip to make target
                }

                if($target->cal_type=='month')
                 $item=self::_monthly_sku_target_set($markup,$sale);
                elseif($target->cal_type=='year')
                    $item=self::_yearly_sku_target_set($markup,$sale);
                elseif($target->cal_type=='quarter')
                    $item=self::_quarterly_sku_target_set($markup,$sale,$target->cal_subtype);

                $model=new SkuSalesTarget();
                $model->sku=$item['sku'];
                $model->markup=$markup;
                $model->channel_id=$item['channel_id'];
                $model->target_id=$target->id;
                for($i=0; $i<12 ;$i++)  // every month
                {
                     $sales_target=$month[$i].'_sales_target';
                     $sales_qty=$month[$i].'_qty_target';
                     $model->$sales_target=$item[$month[$i].'_sales_target'];
                     $model->$sales_qty=$item[$month[$i].'_qty_target'];
                }
                if(!$model->save())
                    continue;
                else
                    $count++;
            }
        endif;
        return $count;
    }

    private function _monthly_sku_target_set($markup,$item)
    {
        $average_sale=0;
        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        for($i=0; $i<12 ;$i++)
        {
            $markup_stock=ceil($markup * ($item[$month[$i].'_qty_sold']/100));
            if($item[$month[$i].'_qty_sold'] > 0 )
              $average_sale= ($item[$month[$i].'_sales']/$item[$month[$i].'_qty_sold']) ;

            $item[$month[$i].'_sales_target']=$item[$month[$i].'_sales'] + ($average_sale * $markup_stock);
            $item[$month[$i].'_qty_target']=$item[$month[$i].'_qty_sold'] + $markup_stock;
        }
        return $item;

    }

    private function _yearly_sku_target_set($markup,$item)
    {

        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $sum_qty=0;
        $sum_sales=0;
        $average_sale=0;
        for($i=0; $i<12 ;$i++)
        {
            $sum_qty += $item[$month[$i].'_qty_sold'];
            $sum_sales += $item[$month[$i].'_sales'];
        }
        $average_stock=($sum_qty/12);
        $markup_stock=($markup * ($average_stock/100)); //stock percent increased
        if($sum_qty > 0)
         $average_sale=($sum_sales/$sum_qty);

        $stock_target=ceil($markup_stock + $average_stock); // percent increased value + average stock of quarter
        for($t=0;$t<12;$t++)
        {
            $item[$month[$t].'_sales_target']=($average_sale * $stock_target);
            $item[$month[$t].'_qty_target']=$stock_target;
        }

        return $item;
    }

    private function _quarterly_sku_target_set($markup,$item,$quart)
    {

        $month=['january','february','march','april','may','june','july','august','september','october','november','december'];
        $quarter=[
            'first'=>['january','february','march'],
            'second'=>['april','may','june'],
            'third'=>['july','august','september'],
            'fourth'=>['october','november','december']
        ];

        $sum_qty=0;
        $sum_sales=0;
        for($i=0; $i<3 ;$i++)
        {
            $sum_qty += $item[$quarter[$quart][$i].'_qty_sold'];
            $sum_sales += $item[$quarter[$quart][$i].'_sales'];
        }

        $average_stock=($sum_qty/3);
        $markup_stock=($markup * ($average_stock/100)); //stock percent increased
        if($sum_qty > 0)
         $average_sale=($sum_sales/$sum_qty);
        else
            $average_sale=0;

        $stock_target=ceil($markup_stock + $average_stock); // percent increased value + average stock of quarter
        for($t=0;$t<12;$t++)
        {
            $item[$month[$t].'_sales_target']=($average_sale * $stock_target);
            $item[$month[$t].'_qty_target']=$stock_target;
        }



        return $item;
    }



   /*
    * insert sales target in db
    */
     public static function insertSalesTarget()
     {
         $if_approved_exists=SalesTargets::findone(['status'=>'approved','year'=>date('Y')]); // check if already approved exists then make draft new target

         $target=new SalesTargets();
         $target->year=date('Y');
         $target->year_compared=$_POST['prior_year'];
         $target->status=$if_approved_exists ? 'draft':'pending';
         $target->calculation_type=$_POST['calculation_type'];
         $target->calculation_subtype=Yii::$app->request->post('calculation_subtype',null);
         $target->type=Yii::$app->request->post('apply_to',null); // marketplacewise ,channel wise, cat wise
         $target->created_by=Yii::$app->user->id;
         $target->updated_by=Yii::$app->user->id;
         if($target->validate() && $target->save())
         {
             return $target;
         }
         else
         {
             $errors = $target->getFirstErrors();
             return ['status'=>'failure','error'=>'1','msg'=>reset($errors)];
         }
     }
    /*
   * insert sales target detail in db
   */
    public static function insertSalesTargetDetail($target)
    {
        $record=[];
        $errors="";
        if($target->type=='overall')
            $record=['markup'=>(array)$_POST['markup'],'type'=>null,'type_id'=>null,'type_value'=>null];
        else
            $record=['markup'=>$_POST['markup'],'type'=>$target->type,'type_id'=>isset($_POST['markup_for_id']) ? $_POST['markup_for_id']:NULL,'type_value'=>$_POST['markup_for_name']];

        //print_r($record); die();
        for($i=0;$i<count($record['markup']);$i++)
        {
           // die('jhinga mai');
            $detail= new SalesTargetDetail();
            $detail->target_id=$target->id;
            $detail->markup=$record['markup'][$i];
            $detail->type=$record['type'] ;
            $detail->type_id=isset($record['type_id'][$i]) ? $record['type_id'][$i]:NULL;
            $detail->type_value=isset($record['type_value'][$i]) ? $record['type_value'][$i]:NULL;
            if(!$detail->save())
            {
                $errors = $detail->getFirstErrors();
                continue;
            }
        }
        if($errors)
            return ['status'=>'failure','error'=>'1','msg'=>reset($errors)];

        return;

    }

    /**
     *
     */
    private static function _make_where_condition_sales_target($params)
    {
        $where="1=1";
        if(isset($params['target_id']) && !empty($params['target_id'])) {
            $where .= " AND `sst`.`target_id`='" . $params['target_id'] . "'";
        }
        elseif(isset($params['year']) && !empty($params['year']))
        {
            $query=SalesTargets::findone(['year'=>$params['year'],'status'=>'approved']);
            $target_id=$query ? $query->id:NULL;
            if($target_id)
                $where .=" AND `sst`.`target_id`='".$target_id."'";
            else
                return ['status'=>'failure','msg'=>'no approved target exists','error'=>1];
        }
        else
            return ['status'=>'failure','msg'=>'Target id or year required','error'=>1];

        if(isset($params['channel_id']) && !empty($params['channel_id']) && $params['channel_id']!='all') {
            $where .=" AND `sst`.`channel_id`='".$params['channel_id']."'";
        }
        elseif(isset($params['marketplace']) && !empty($params['marketplace']) && $params['marketplace']!='all')
        {
          //  die('die');
            $channels=Channels::find()->where(['marketplace'=>$params['marketplace']])->select(['id'])->asArray()->all();
            //$ids= ;
            if($channels)
                $where .=" AND `sst`.`channel_id` IN(".implode(',',(array_column($channels,'id'))).")";
            else
                return ['status'=>'failure','msg'=>'No channel found in marketplace','error'=>'1'];
        }

        if(isset($params['sku']) && !empty($params['sku']) )
            $where .=" AND `sst`.`sku`='".$params['sku']."'";



        return $where;
    }
    /**
     * get sales target only
     */
    public static function getSalesTarget($params)
    {
      // print_r($params); die();
        if(!$params)
            return false;

        if($params['type']=="monthly")
            $select=self::_select_monthly_query('yes','no','no','yes','no'); //target table and get only sales not stock

        if($params['type']=="quarterly")
            $select=self::_select_quarterly_query('yes','no','no','yes','no'); // target table and get only sales not stock

        $select=rtrim($select,', '); // trim end comma

        $where=self::_make_where_condition_sales_target($params);

        if(isset($where['error']))
            return $where;

        $sql="SELECT $select
                FROM 
                  `sku_sales_target` sst
                 WHERE
                  $where";
      //  echo $sql; die();
       $response=Yii::$app->db->createCommand($sql)->queryAll();
       return $response;

    }

    /**
     * convert post data of sales dashboard  according to  getSalesTarget function
     */
    public static function refine_sales_dashboard_filter($inputs)
    {
        $params=[];

            if(isset($inputs['y_type']) && !empty($inputs['y_type']))
                $params['type']=$inputs['y_type'];
            else
                $params['type']='monthly';

            if(isset($inputs['year']) && !empty($inputs['year']))
                $params['year']=$inputs['year'];
            else
                $params['year']=date('Y');

            if(isset($inputs['marketplace']) && !empty($inputs['marketplace']))
                $params['marketplace']=$inputs['marketplace'];

            if(isset($inputs['shops']) && !empty($inputs['shops']))
                $params['shops']=$inputs['shops'];

        return $params;
    }

    /*
    * sales and target mapping for graph at sales dashboard quarterly
    */
    private static function _map_sales_and_targets_quarterly($targets,$sales)
    {
        //print_r($sales); die();
        $response=['Quarter 1'=>['sales'=>0,'target'=>0],'Quarter 2'=>['sales'=>0,'target'=>0],'Quarter 3'=>['sales'=>0,'target'=>0],'Quarter 4'=>['sales'=>0,'target'=>0]];

        foreach ($sales as $sale)
            $response[ucwords($sale->monthly)]['sales']=$sale->sales;

        //print_r($response); die();
        $targets=array_values($targets[0]);  // targets already 4 values in array
        $sales=array_column($response,'sales');
        return [
            'display'=>['Quarter 1','Quarter 2','Quarter 3','Quarter 4'], // quarter names
            'sales'=>$sales,
            'targets'=>$targets,
            'max_sales'=>max(array_merge($targets,$sales)) + 200 ,// for graph
        ];
    }
    /*
     * sales and target mapping for graph at sales dashboard monthly
     */
    private static function _map_sales_and_targets_monthly($targets,$sales)
    {
        //print_r($targets); die();
        $response=['january'=>['sales'=>0,'target'=>0],'february'=>['sales'=>0,'target'=>0],'march'=>['sales'=>0,'target'=>0],'april'=>['sales'=>0,'target'=>0],'may'=>['sales'=>0,'target'=>0],'june'=>['sales'=>0,'target'=>0],'july'=>['sales'=>0,'target'=>0],'august'=>['sales'=>0,'target'=>0],'september'=>['sales'=>0,'target'=>0],'october'=>['sales'=>0,'target'=>0],'november'=>['sales'=>0,'target'=>0],'december'=>['sales'=>0,'target'=>0]];

        foreach ($sales as $sale)
            $response[strtolower($sale->monthly)]['sales']=$sale->sales;

       /* foreach ($targets[0] as $month=>$target) {
            $month_name = str_replace('_sales_target', '', strtolower($month));
            $response[$month_name]['target'] =$target;
        }*/
        $targets=array_values($targets[0]); // already 12 values in array so pick values
        $sales=array_column($response,'sales');
        return [
            'display'=>['Jan','Feb','Mar','Apr','May','June','Jul','Aug','Sep','Oct','Nov','Dec'], // month names
            'sales'=>$sales,
            'targets'=>$targets,
            'max_sales'=>max(array_merge($targets,$sales)) + 200 ,// for graph
        ];
    }
    /*
     * sales and target mapping for graph at sales dashboard
     */

    public static function map_sales_and_targets($type,$targets,$sales)
    {

      //  print_r($sales); die();
        if($type=="monthly" )
           return  self::_map_sales_and_targets_monthly($targets,$sales);
        elseif($type=="quarterly" )
            return  self::_map_sales_and_targets_quarterly($targets,$sales);

        return false;

    }

    /***
     * set markups for categories and its child
     */

    public  static function markup_category_tree($markups,array $elements, $parentId = 0,$markup=0)
    {
        $result = array();
        foreach ($elements as $element) {
            $element['markup']=isset($markups[$element['id']]) ?  $markups[$element['id']] : $markup; // if markup set for parent
            if ($element['parent_id'] == $parentId) {
                $children = self::markup_category_tree($markups,$elements, $element['id'],$element['markup']);
                if ($children) {
                    $element['children'] = $children;
                }
                $result[] = $element;
            }

        }
        return $result;
    }

    /**
     * @param $categories
     * @param array $result
     * @return array
     * index of cat and markup its value
     */
    public static function get_id_markup_cat($categories,&$result=[])
    {
        foreach ($categories as $cat)
        {
            $result[$cat['id']]=$cat['markup'];
            if(isset($cat['children']) && is_array($cat['children']))
                 self::get_id_markup_cat($cat['children'],$result);

        }
        return $result;
    }

    public static function GetCategorySales($mp=[],$categories=[],$shops=[],$date){
        $w =HelpUtil::getWarehouseDetail();
        $cond='';
      //  var_dump($date);die;
        if ($date) {
            $date = explode(' to ', $date);
            if(is_array($date)):
                $sd = HelpUtil::get_utc_time($date[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($date[1] ." 23:59:59");

                $sd = HelpUtil::get_utc_time(date('Y-m-01 00:00:00', strtotime($date[0])));
                $ed = HelpUtil::get_utc_time(date('Y-m-t 23:59:59', strtotime($date[1])));

                $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)";


            endif;
        } else {
            $sd = HelpUtil::get_utc_time(date('Y-m-01') ." 00:00:00");
            $ed = HelpUtil::get_utc_time(date('Y-m-t') ." 23:59:59");
            $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)";

        }
        if ($mp){
            $cond .= " AND c.marketplace IN ("."'" . implode ( "', '", $mp ) . "'".") ";
        }
        if ($shops){
            $cond .= " AND c.name IN ("."'" . implode ( "', '", $shops ) . "'".") ";
        }
        if ($categories){
            $cond .= " AND pc.cat_id IN ("."'" . implode ( "', '", $categories ) . "'".") ";
        }

        $sql = "SELECT DATE_FORMAT(oi.item_created_at, '%Y-%m') AS order_date, 
                CAST(SUM(oi.paid_price * oi.quantity) AS UNSIGNED INTEGER) AS sales_per_day
                FROM orders AS o
                JOIN order_items AS oi ON o.id = oi.order_id
                JOIN products p ON p.id = oi.sku_id
                INNER JOIN product_categories pc ON p.id = pc.product_id
                INNER JOIN category cat ON cat.id = pc.`cat_id`
                JOIN channels c ON c.id = o.channel_id
               #JOIN category cat ON cat.id = p.sub_category
                WHERE o.order_status NOT IN (".GraphsUtil::GetCanceledStatuses().")
                $cond
                GROUP BY MONTH(oi.item_created_at)";

        $Sales = Orders::findBySql($sql)->asArray()->all();
        $redefine_sale = [];
        foreach ( $Sales as $value ){
            $redefine_sale[$value['order_date']] = $value['sales_per_day'];
        }

        if ($date){
            $AllMonths=HelpUtil::getAllMonthsBetweenTwoDates($date[0],$date[1]);
        }else{
            $AllMonths=HelpUtil::getAllMonthsBetweenTwoDates(date('Y-m-01') ." 00:00:00",date('Y-m-t') ." 23:59:59");
        }


        $final_list=[];
        foreach ( $AllMonths as $key=>$YearMonth ){
            $detail=[];
            if ( isset($redefine_sale[$YearMonth]) ){
                $detail['period']=$YearMonth;
                $detail['sale']=$redefine_sale[$YearMonth];
            }else{
                $detail['period']=$YearMonth;
                $detail['sale']=0;
            }
            $final_list[]=$detail;
        }
        return $final_list;
    }
    public static function GetAverageSkuSales($marketplace=[],$shop=[],$date){
        return '';
    }
    public static function GetSales($warehouse_id){
        $OrdersFromLast = '-30 minutes';
        $encryptionKey = Settings::GetDataEncryptionKey();
        $From = date('Y-m-d H:i:s' , strtotime($OrdersFromLast));
        $To = time(); // add ten hours forward
        $sql="  SELECT 
                o.`order_id`,
                o.`order_number`,
                c.`name` as channel_name,
                c.marketplace,
                o.order_shipping_fee,
                o.payment_method,
                o.coupon_code,
                o.`channel_id`,
                o.`payment_method`,
                o.`coupon_code`,
                AES_DECRYPT(ca.`shipping_fname`, '".$encryptionKey."') AS customer_fname, 
                AES_DECRYPT(ca.`shipping_lname`, '".$encryptionKey."') AS customer_lname, 
                AES_DECRYPT( ca.`billing_number` , '".$encryptionKey."') as phone,
                AES_DECRYPT( ca.`billing_email` , '".$encryptionKey."') as email,
                o.`order_total`,o.`order_created_at`,
                o.`order_updated_at`,
                o.`order_status`,
                AES_DECRYPT( ca.`shipping_city` , '".$encryptionKey."') as shipping_city,
                AES_DECRYPT( ca.`shipping_post_code` , '".$encryptionKey."') as shipping_post_code,
                AES_DECRYPT( ca.`shipping_country` , '".$encryptionKey."') as shipping_country,
                AES_DECRYPT( ca.`shipping_address` , '".$encryptionKey."') as shipping_address,
                oi.`order_item_id`,
                oi.`shop_sku`,
                oi.`price`,
                oi.`paid_price`,
                oi.quantity,
                oi.`item_status`,
                oi.`item_sku`,
                oi.fulfilled_by_warehouse
                FROM orders o
                INNER JOIN `customers_address` ca ON o.`id` = ca.order_id
                INNER JOIN order_items oi ON oi.`order_id` = o.`id`
                INNER JOIN channels c ON c.id = o.channel_id
                WHERE o.`order_updated_at` BETWEEN '$From' AND '".date('Y-m-d H:i:s',$To)."' AND oi.fulfilled_by_warehouse=$warehouse_id 
                ORDER BY o.`id` DESC";

        $getOrders = \Yii::$app->db->createCommand($sql);
        $result_data = $getOrders->queryAll();
        $redefineOrders=[];
        foreach ( $result_data as $key=>$value ) {
            $redefineOrders[$value['order_id']][] = $value;
        }
        $order_data=[];
        foreach ( $redefineOrders as $order_id=>$value ){
            $customer_details=[];
            $customer_details['customer_fname']=$value[0]['customer_fname'];
            $customer_details['customer_lname']=$value[0]['customer_lname'];
            $customer_details['phone']=$value[0]['phone'];
            $customer_details['email']=$value[0]['email'];
            $customer_details['shipping_city']=$value[0]['shipping_city'];
            $customer_details['shipping_post_code']=$value[0]['shipping_post_code'];
            $customer_details['shipping_country']=$value[0]['shipping_country'];
            $customer_details['shipping_address']=$value[0]['shipping_address'];
            $order_data[$order_id]['customer_details']=$customer_details;

            $order_detail=[];
            $order_detail['order_id']=$value[0]['order_id'];
            $order_detail['order_number']=$value[0]['order_number'];
            $order_detail['channel_name']=$value[0]['channel_name'];
            $order_detail['marketplace']=$value[0]['marketplace'];
            $order_detail['order_shipping_fee']=$value[0]['order_shipping_fee'];
            $order_detail['channel_id']=$value[0]['channel_id'];
            $order_detail['payment_method']=$value[0]['payment_method'];
            $order_detail['coupon_code']=$value[0]['coupon_code'];
            $order_detail['order_total']=$value[0]['order_total'];
            $order_detail['order_created_at']=$value[0]['order_created_at'];
            $order_detail['order_updated_at']=$value[0]['order_updated_at'];
            $order_detail['order_status']=$value[0]['order_status'];
            $order_data[$order_id]['order_details']=$order_detail;

            foreach ( $value as $innerKey=>$innerValue ){
                $order_item_detail = [];
                $order_item_detail['order_item_id']=$innerValue['order_item_id'];
                $order_item_detail['shop_sku']=$innerValue['shop_sku'];
                $order_item_detail['price']=$innerValue['price'];
                $order_item_detail['paid_price']=$innerValue['paid_price'];
                $order_item_detail['quantity']=$innerValue['quantity'];
                $order_item_detail['item_status']=$innerValue['item_status'];
                $order_item_detail['item_sku']=$innerValue['item_sku'];
                $order_item_detail['fulfilled_by_warehouse']=$innerValue['fulfilled_by_warehouse'];
                $order_data[$order_id]['order_items'][]=$order_item_detail;
            }
        }
        return $order_data;
    }
}