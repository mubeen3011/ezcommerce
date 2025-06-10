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
use common\models\ChannelsProductsArchive;
use common\models\OrderItems;
use common\models\WarehouseStockList;
use Faker\Provider\DateTime;
use Yii;

class GraphsUtil
{

    public static function getSalesTarget($by)
    {
        $connection = Yii::$app->db;
        if ($by == 'year') {
            $sql = "SELECT SUM(target) as target FROM `channles_targets`
                    WHERE YEAR = '2018'; ";

            $command = $connection->createCommand($sql);
            $result = $command->queryOne();
            $target = $result['target'];

            $sql = "SELECT SUM(paid_price) as sales FROM sales_archive
                    WHERE DATE_FORMAT(order_date, \"%Y\") = '2018';";

            $command = $connection->createCommand($sql);
            $result = $command->queryOne();
            $sales = $result['sales'];

            $ret = ['target' => $target, 'sales' => $sales];
        }

        if ($by == 'category') {
            $sql = "SELECT SUM(sa.`paid_price`) as mcc FROM sales_archive sa
                    INNER JOIN `philips_cost_price` pcp ON pcp.id = sa.`sku_id`
                    WHERE DATE_FORMAT(sa.order_date, \"%Y\") = '2018' AND pcp.`main_category` = 'MCC'; 
                    ";

            $command = $connection->createCommand($sql);
            $result = $command->queryOne();
            $mcc = $result['mcc'];

            $sql = "SELECT SUM(sa.`paid_price`) as dap FROM sales_archive sa
                    INNER JOIN `philips_cost_price` pcp ON pcp.id = sa.`sku_id`
                    WHERE DATE_FORMAT(sa.order_date, \"%Y\") = '2018' AND pcp.`main_category` = 'DAP'; 
                    ";

            $command = $connection->createCommand($sql);
            $result = $command->queryOne();
            $dap = $result['dap'];

            $ret = ['mcc' => $mcc, 'dap' => $dap];
        }
        return $ret;
    }

    public static function getSalesRevenue($target)
    {
        $connection = Yii::$app->db;
        $sql = "SELECT SUM(sa.`paid_price`) AS sales ,DATE_FORMAT(sa.order_date, \"%Y\") AS `year` FROM sales_archive sa
                GROUP BY DATE_FORMAT(sa.order_date, \"%Y\")";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $d) {
            if ($d['year'] == date('Y'))
                $refine[] = ['year' => $d['year'], 'sales' => $d['sales'], 'target' => self::getPredictionSales($d['sales'])];
            else
                $refine[] = ['year' => $d['year'], 'sales' => $d['sales']];
        }

        return json_encode($refine);
    }

    public static function getWeeks()
    {
        $sql = 'SELECT distinct(WEEK(oi.item_created_at)+1) AS WEEK
FROM `order_items` oi
LEFT JOIN orders o ON o.id = oi.`order_id`
LEFT JOIN products p ON p.id = oi.sku_id
LEFT JOIN category c ON c.id = p.sub_category
WHERE 
(o.`order_status` = \'pending\' || o.`order_status` = \'shipped\') 
AND oi.item_created_at BETWEEN \'' . $_POST['quarters'] . '-' . $_POST['morris-month'] . '-01 00:00:00\' AND \'' . $_POST['quarters'] . '-' . $_POST['morris-month'] . '-31 23:59:59\'
GROUP BY WEEK(oi.item_created_at),o.channel_id
ORDER BY oi.item_created_at ASC;
';
        $RunSql = OrderItems::findBySql($sql)->asArray()->all();
        return json_encode($RunSql);

    }

    public static function getQuarterFromAndToMonth()
    {
        $Quarter = ['From' => '01', 'To' => '12'];
        if (isset($_POST['morris-quarter']) && $_POST['morris-quarter'] == '1')
            $Quarter = ['From' => '01', 'To' => '03'];
        if (isset($_POST['morris-quarter']) && $_POST['morris-quarter'] == '2')
            $Quarter = ['From' => '04', 'To' => '06'];
        if (isset($_POST['morris-quarter']) && $_POST['morris-quarter'] == '3')
            $Quarter = ['From' => '07', 'To' => '09'];
        if (isset($_POST['morris-quarter']) && $_POST['morris-quarter'] == '4')
            $Quarter = ['From' => '10', 'To' => '12'];
        return $Quarter;
    }

    public static function AddDaysInADate($date)
    {
        $date = strtotime($date);
        $date = strtotime("+6 day", $date);
        return date('Y-m-d', $date);
    }

    public static function getQuarterSales()
    {

        /**
         * get the months by quarter
         * */
        /*echo '<pre>';
        print_r($_POST);
        die;*/
        $QuarterMonths = self::getQuarterFromAndToMonth();
        $w = HelpUtil::getWarehouseDetail();

        $date_condition = '';
        if (isset($_POST['quarters']) && $_POST['quarters'] != '')
            $date_condition .= "AND oi.item_created_at between '" . $_POST['quarters'] . "-" . $QuarterMonths['From'] . "-01 00:00:00' AND '" . $_POST['quarters'] . "-" . $QuarterMonths['To'] . "-31 23:59:59'";
        else
            $date_condition .= "AND oi.item_created_at between '" . date('Y') . "-01-01 00:00:00' AND '" . date('Y') . "-12-31 23:59:59'";
        if (isset($_POST['morris-month']) && $_POST['morris-month'] != '')
            $date_condition = "AND oi.item_created_at between '" . $_POST['quarters'] . "-" . $_POST['morris-month'] . "-01 00:00:00' AND '" . $_POST['quarters'] . "-" . $_POST['morris-month'] . "-31 23:59:59'";

        $category = '';
        if (isset($_POST['morris-category']) && $_POST['morris-category'] != '')
            $category .= "AND c.map_with = '" . strtolower($_POST['morris-category']) . "'";
        $shop_cond = "AND o.channel_id IN (1,2,3)";
        if (isset($_POST['morris-shop']) && $_POST['morris-shop'] != '')
            $shop_cond = "AND o.channel_id IN (" . implode(',', $_POST['morris-shop']) . ")";
        $Default = 'QUARTER(oi.item_created_at) as QUARTER';
        $DefaultTime = 'QUARTER';
        if (isset($_POST['morris-quarter']) && $_POST['morris-quarter'] != '') {
            $Default = 'MONTHNAME(oi.item_created_at) as MONTHNAME';
            $DefaultTime = 'MONTHNAME';
        }
        if (isset($_POST['morris-month']) && $_POST['morris-month'] != '') {
            $Default = 'WEEK(oi.item_created_at)+1 as WEEK';
            $DefaultTime = 'WEEK';
        }
        if (isset($_POST['morris-week']) && $_POST['morris-week'] != '') {
            $week_start = new \DateTime();
            $week_start->setISODate($_POST['quarters'], $_POST['morris-week']);
            $WeekStartDate = $week_start->format('Y-m-d');
            $WeekEndDate = self::AddDaysInADate($week_start->format('Y-m-d'));
            $date_condition = "AND oi.item_created_at between '" . $WeekStartDate . " 00:00:00' AND '" . $WeekEndDate . " 23:59:59'";
            $Default = 'DATE(oi.item_created_at) as DATE';
            $DefaultTime = 'DATE';
        }

            $sql = "SELECT " . $Default . " ,SUM(oi.paid_price) AS sales,o.channel_id
                FROM `order_items` oi
                LEFT JOIN orders o ON o.id = oi.`order_id`
                LEFT JOIN products p ON p.id = oi.sku_id
                LEFT JOIN category c ON c.id = p.sub_category
                WHERE oi.`item_status` NOT IN(" . self::GetMappedCanceledStatuses() . ")
                AND oi.`fulfilled_by_warehouse` IN( $w)
                " . $date_condition . "
                " . $shop_cond . "
                " . $category . "
                GROUP BY " . $DefaultTime . "(oi.item_created_at),o.channel_id ORDER BY oi.item_created_at asc;
                ";

        $sales = OrderItems::findBySql($sql)->asArray()->all();
        $redine = self::modifyQuarterSales($sales, $DefaultTime);
        return $redine;
    }

    public static function modifyQuarterSales($sales, $Default)
    {
        $redefine_channel_prefixes = [];

        $redefine = [];
        $alphabets = 'a';

        foreach ($sales as $value) {
            $channelPrefix = HelpUtil::ChannelPrefixById($value['channel_id']);
            if($channelPrefix==false)
                continue;
            $value['channel_id'] = $channelPrefix;
            $redefine_channel_prefixes[$channelPrefix] = $channelPrefix;
            $redefine[$value[$Default]][] = [$Default => $value[$Default], 'sales' => $value['sales']];
        }
        $jsonFormat = [];
        $counter = 0;

        $ykeys = [];
        foreach ($redefine as $value) {
            $alphabets = 'a';
            foreach ($value as $kk => $vv) {

                foreach ($vv as $lkey => $innerval) {
                    $ykeys[] = $alphabets;
                    if ($lkey == $Default)
                        $jsonFormat[$counter]['y'] = $Default . ' ' . $innerval . ' (2018)';
                    elseif ($lkey == 'sales')
                        $jsonFormat[$counter][$alphabets++] = ceil($innerval);

                }

            }
            $counter++;
        }
        $redefine_ykeys = (array_flip($ykeys));
        $ykeys_final = array_flip($redefine_ykeys);

        //if (!empty($Weeks))

        //$temp = array_map('self::js_str', $ykeys_final);
        $QuarterGraphData = [
            'bars_data' => json_encode($jsonFormat),
            'yKeys' => json_encode($ykeys_final),
            'labels' => json_encode($redefine_channel_prefixes)
        ];
        return $QuarterGraphData;

    }

    public static function js_str($s)
    {
        return '"' . addcslashes($s, "\0..\37\"\\") . '"';
    }
    public static function GetCanceledStatuses(){

        return '"'.implode('","',Yii::$app->params['cancel_statuses'] ).'"';

    }
    public static function GetMappedCanceledStatuses(){

        return '"'.implode('","',Yii::$app->params['cancel_mapped_statuses'] ).'"';

    }
    public static function GetMappedStatuses(){

        return '"'.implode('","',Yii::$app->params['mapped_statuses'] ).'"';

    }
    public static function getMonthSales($dr = null)
    {
        $w =HelpUtil::getWarehouseDetail();
        if ($dr) {
            $dr = explode(' to ', $dr);
            if (is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] . " 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] . " 23:59:59");

                    $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)  ";

            endif;
        } else {

            $sd = HelpUtil::get_utc_time(date('Y-m-01') . " 00:00:00");
            $ed = HelpUtil::get_utc_time(date('Y-m-d') . " 23:59:59");
            //echo $sd; die();
                $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w) ";
        }

        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")  // if order count wise to display
            $stats_column=" count(distinct(`oi`.`order_id`)) ";
        else
            $stats_column=" SUM(`oi`.`sub_total`)";

        $connection = Yii::$app->db;

        $sql = "SELECT $stats_column AS sales,DATE(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) AS `date` 
                FROM 
                   `order_items` oi
                WHERE oi.`item_status` NOT IN(" . self::GetMappedCanceledStatuses() . ")
                $cond
             GROUP BY DATE(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "'))";

      //  echo $sql; die();
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $d) {
            $refine[] = ['date' => $d['date'], 'value' => round($d['sales'], 2)];
        }

        return json_encode($refine);
    }
    public static function getMarketplaceSales($mp = null, $dr = null)
    {
        $w =HelpUtil::getWarehouseDetail();
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
            $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
            $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)";


            endif;
        } else {
            $sd = HelpUtil::get_utc_time(date('Y-m-01') ." 00:00:00");
            $ed = HelpUtil::get_utc_time(date('Y-m-d') ." 23:59:59");

                $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at` <= '$ed' 
                    AND oi.`fulfilled_by_warehouse` IN ($w)";

        }

        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")  // if order count wise to display
            $stats_column=" count(distinct(`oi`.`order_id`))";
        else
            $stats_column=" FORMAT(SUM(oi.sub_total), 2) ";

        $connection = Yii::$app->getDb();
        if (is_null($mp)) {
            $sql = "SELECT $stats_column AS sales,c.marketplace FROM `order_items` oi
              INNER JOIN orders o ON o.id = oi.`order_id`
              INNER JOIN channels c ON c.`id` = o.`channel_id`
               WHERE oi.`item_status` NOT IN(".self::GetMappedCanceledStatuses().")
                $cond
                GROUP BY c.marketplace";
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            $refine = [];
            foreach ($result as $d) {
                $d['marketplace'] = ($d['marketplace'] == 'street') ? '11Street' : $d['marketplace'];
                $d['marketplace'] = ($d['marketplace'] == 'shop') ? 'Shop' : $d['marketplace'];
                //$refine[] = ['marketplace'=>ucwords($d['marketplace']),'sales'=>str_replace(',','',$d['sales']),'target'=>round($d['target'],2),];
                $refine[] = ['marketplace' => ucwords($d['marketplace']), 'sales' => str_replace(',', '', $d['sales'])];
            }
        } else {
            $sql = "SELECT
                  $stats_column AS sales,
                  c.name 
                FROM
                  `order_items` oi 
                  INNER JOIN orders o ON o.id = oi.`order_id`
                  INNER JOIN channels c 
                    ON c.`id` = o.`channel_id` 
                
                  WHERE o.order_status NOT IN(".self::GetCanceledStatuses().")
                  $cond
                  AND c.marketplace like '%{$mp}'
                GROUP BY c.name ";

            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            $refine = [];
            $sales = [];
            foreach ($result as $d) {

                $refine[] = ['marketplace' => ucwords($d['name']), 'sales' => str_replace(',', '', $d['sales'])];
                $sales[] = str_replace(',', '', $d['sales']);
            }

            //arsort($refine, SORT_NUMERIC);
            array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);

        }
        return json_encode($refine);
    }

    public static function getMonthlySales()
    {
        $w = HelpUtil::getWarehouseDetail();
        $mp = isset($_GET['mp']) ? $_GET['mp'] : 'all';
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
        $shop = isset($_GET['shop']) ? $_GET['shop'] : 'all';
        $cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
        $month = $_GET['month'];
        $cond = $join = "";
        $field = 'c.name';
        $connection = Yii::$app->getDb();
        if ($type == 'monthly') {
                $cond = " AND oi.`fulfilled_by_warehouse` IN ($w)
                AND MONTHNAME(CONVERT_TZ(o.`order_created_at`,'UTC','".Yii::$app->params['current_time_zone']."') ) = '$month'
                AND YEAR(CONVERT_TZ(o.`order_created_at`,'UTC','".Yii::$app->params['current_time_zone']."') ) = $year";
        } else {

            $cond = " AND oi.`fulfilled_by_warehouse` IN ($w) 
            AND QUARTER( CONVERT_TZ(o.`order_created_at`,'UTC','".Yii::$app->params['current_time_zone']."') ) = '$month'
            AND YEAR( CONVERT_TZ(o.`order_created_at`,'UTC','".Yii::$app->params['current_time_zone']."')) = $year";
        }

        if ($mp != 'all' && $shop == 'all') {
                $cond .= " AND marketplace like '%{$mp}'
                AND oi.`fulfilled_by_warehouse`IN ($w)";

        } else if ($cat == 'all' && $mp != 'all' ) {
            $join = "LEFT JOIN `products` p ON p.id = oi.`sku_id`
                LEFT JOIN category cat ON cat.id = p.`sub_category`";
            if($shop != 'all')

                    $cond .= " AND c.id = '{$shop}'
                    AND oi.`fulfilled_by_warehouse` IN ($w)";
                    $field = 'cat.name as `name`';
        }
        if ($cat != 'all') {
            $join = "LEFT JOIN `products` p ON p.id = oi.`sku_id`
                LEFT JOIN category cat ON cat.id = p.`sub_category`";

                $cond .= " AND cat.`name` = '$cat'
                AND oi.`fulfilled_by_warehouse` IN ($w)";

            if ($shop != 'all')
                $field = 'cat.name as `name`';
        }
        $sql = "SELECT 
                  FORMAT(SUM(oi.sub_total), 2) AS sales,
                  $field 
                FROM
                    `orders` o
                    INNER JOIN order_items oi 
                    ON o.id = oi.`order_id`
                    LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id` 
                    $join
                  WHERE oi.`item_status` NOT IN(".self::GetMappedCanceledStatuses().")
                  $cond
                GROUP BY `c`.`name`";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $d) {
            if ($d['name'] == null)
                continue;
            $refine[] = ['marketplace' => ucwords($d['name']), 'sales' => str_replace(',', '', $d['sales'])];
            $sales[] = str_replace(',', '', $d['sales']);
        }
        if($refine)
        array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);

        return json_encode($refine);
    }

    public static function getYearlySales()
    {
        $w = HelpUtil::getWarehouseDetail();
        $year = (isset($_POST['filter']['year']) && $_POST['filter']['year'] != 'all') ? $_POST['filter']['year'] : date('Y');
        $type = isset($_POST['filter']['y_type']) ? $_POST['filter']['y_type'] : 'monthly';
        $martketplace = isset($_POST['filter']['marketplace']) ? $_POST['filter']['marketplace'] : 'all';
        $shops = isset($_POST['filter']['shops']) ? $_POST['filter']['shops'] : 'all';
        $category = isset($_POST['filter']['cat']) ? $_POST['filter']['cat'] : 'all';
        $brand = isset($_POST['filter']['brand']) ? $_POST['filter']['brand'] : 'all';
        $connection = Yii::$app->getDb();
        $col = $cond = $grp = $join = $order_by= "";
        if ($type == 'monthly') {
            $col = "MONTHNAME( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') ) AS 'month'";
            $grp = "GROUP BY MONTH(CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') )";
            $order_by = "Order BY MONTH(CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') )";

        } else {

            $col = "QUARTER( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') ) AS 'month'";
            $grp = "GROUP BY QUARTER( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') )";
            $order_by = "Order BY QUARTER( CONVERT_TZ(oi.item_created_at,'UTC','".Yii::$app->params['current_time_zone']."') )";
        }

        if ($martketplace != 'all') {
                $cond .= " AND c.`marketplace` = '{$martketplace}' ";

        }

        if ( $shops != 'all') {
                $cond .= " AND c.`id` = '{$shops}' ";
        }

        if ($category != 'all') {

            $childCategories = HelpUtil::GetAllChildCategories($category);
            $childCategories[] = $category;
            //self::debug($childCategories);
            $join = " INNER JOIN `products` p ON p.id = oi.`sku_id`
                       INNER JOIN product_categories pc ON p.id = pc.product_id
                       INNER JOIN category cat ON cat.id = pc.`cat_id`
                        ";

            $cond .= " AND pc.`cat_id` IN (".implode(',',$childCategories).") ";

        }
        if($brand!='all'){
            $join = " LEFT JOIN `products` p ON p.id = oi.`sku_id`";
            $cond .= " AND p.`brand`='".$brand."'";
        }

        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")
            $stats_column=" count(distinct(`oi`.`order_id`)) ";
        else
            $stats_column=" FORMAT(SUM(`oi`.`sub_total`), 0) ";

            $cond .= " AND oi.`fulfilled_by_warehouse` IN ($w)
            AND  YEAR( CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "') ) = '$year'";

            $sql = "SELECT 
              $stats_column AS sales,
              $col
            FROM
              order_items oi
              LEFT JOIN orders o 
                ON o.`id` = oi.`order_id` 
              LEFT JOIN channels c 
              ON c.`id` = o.`channel_id`
                $join
                
              WHERE oi.`item_status` NOT IN(".self::GetMappedCanceledStatuses().") 
              $cond
              $grp 
              $order_by";
            //echo $sql;die;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = $sales = [];
        foreach ($result as $d) {
            if ($type == 'monthly')
                $cat = ucwords($d['month']);
            else
                $cat = 'Quarter ' . ucwords($d['month']);
            $refine[] = ['monthly' => $cat, 'sales' => str_replace(',', '', $d['sales'])];
            $sales[] = str_replace(',', '', $d['sales']);
        }
        return $ret = ['json' => json_encode($refine), 'totalSales' => array_sum($sales)];
    }

    public static function getShopSales($shop = null, $dr = null)
    {
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
            $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
            $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");
            $cond = " AND  oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at`  <= '$ed' ";
            endif;
        } else {
            $cond = " AND MONTH( oi.`item_created_at` ) = '".gmdate('m')."'
                    AND YEAR( oi.`item_created_at` ) = '".gmdate('Y')."'";
        }
        $connection = Yii::$app->getDb();
        if (is_null($shop)) {
            $sql = "SELECT FORMAT(SUM(paid_price), 2) AS sales,c.marketplace FROM `order_items` oi
              LEFT JOIN orders o ON o.id = oi.`order_id`
              LEFT JOIN channels c ON xc.`id` = o.`channel_id`
                /*WHERE (o.`order_status` = 'pending' || o.`order_status` = 'shipped') */
                WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().")
                $cond
                GROUP BY c.marketplace";
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            $refine = [];
            foreach ($result as $d) {
                $d['marketplace'] = ($d['marketplace'] == 'street') ? '11Street' : $d['marketplace'];
                $d['marketplace'] = ($d['marketplace'] == 'shop') ? 'Shop' : $d['marketplace'];
                //$refine[] = ['marketplace'=>ucwords($d['marketplace']),'sales'=>str_replace(',','',$d['sales']),'target'=>round($d['target'],2),];
                $refine[] = ['marketplace' => ucwords($d['marketplace']), 'sales' => str_replace(',', '', $d['sales'])];
            }
        } else {
            $sql = "SELECT 
                  FORMAT(SUM(paid_price), 2) AS sales,
                  cat.`name` as category 
                FROM
                  `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id`
                  INNER JOIN `products` p ON p.id = oi.`sku_id` AND p.`is_active` = 1 
                  INNER JOIN product_categories pc ON p.id = pc.product_id 
                  INNER JOIN category cat ON cat.id = pc.`cat_id`
                   WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().") 
                  $cond
                  AND c.name = '{$shop}'
                GROUP BY cat.`name`";
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();

            $refine = [];
            foreach ($result as $d) {
                $refine[] = [
                            'marketplace' => strtoupper($d['category']),
                            'sales' => str_replace(',', '', $d['sales']),
                            //'cat_id' => str_replace(',', '', $d['cat_id'])
                        ];
                $sales[] = str_replace(',', '', $d['sales']);
            }

            //arsort($refine, SORT_NUMERIC);
          //  print_r($sales); die();
            if(isset($sales))
            {
                array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);
            }

        }
        $json = mb_convert_encoding($refine, "UTF-8");
        return json_encode($json, true);
    }

    public static function getMainCategories()
    {
        /*$GetCats = Category::findBySql("SELECT map_with
                                            FROM category c
                                            WHERE c.map_with IS NOT NULL AND is_active = 1
                                            GROUP BY c.map_with;")->asArray()->all();*/
        $GetCats = Category::findBySql("SELECT name
                                            FROM category c
                                            WHERE c.parent_id IS NOT NULL AND is_active = 1
                                            GROUP BY c.name;")->asArray()->all();
          return $GetCats;
    }

    public  static function getMarketPlaces()
    {
        $GetMarketPlaces = Channels::findBySql("SELECT c.marketplace FROM channels c WHERE 
c.marketplace IS NOT NULL AND c.is_active=1 GROUP BY c.marketplace;")->asArray()->all();
        return $GetMarketPlaces;
    }

    public
    static function getShops()
    {
        $GetShops = Channels::find()->andWhere(['is_active' => 1])->select(['id', 'name', 'marketplace', 'prefix'])->asArray()->all();
        $redefine_shops = [];
        foreach ($GetShops as $key => $value) {
            $redefine_shops[$value['marketplace']][] = $value;
        }

        return $redefine_shops;
    }

    public
    static function getPredictionSales($sales)
    {
        $avg = $sales / date('z');
        $cuYear = date('Y');
        $future = strtotime('31 December ' . $cuYear);
        $now = time();
        $timeleft = $future - $now;
        $daysleft = round((($timeleft / 24) / 60) / 60);
        $predictionSales = $avg * $daysleft;
        $predictionSales = $predictionSales + $sales;
        $predictionSales = round($predictionSales, 2);
        return $predictionSales;
    }

    private static function _getLiveAvgBySku($mp, $date_cond, $type = 'marketplace',$from = 1)
    {
        $connection = Yii::$app->db;
        if($mp == 'all')
            $condx = "";
        else
            $condx =$mp ?  " AND marketplace LIKE '$mp%' ":"";
        if ($type == 'marketplace') {
            $sql = "SELECT 
                  ROUND(SUM(oi.`quantity`)) AS `live`,
                  oi.`item_sku` AS sku
                FROM
                  `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id`
                  LEFT JOIN channels_products cp on cp.channel_sku = oi.item_sku  and c.id=cp.channel_id
                /*WHERE (
                    o.`order_status` = 'pending' || o.`order_status` = 'shipped'
                  ) */
                    WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().")
                  $date_cond
                  $condx
                  AND cp.is_live = 1
                GROUP BY oi.item_sku ";
          //  echo $sql; die();
        } else {
            if($from == 1) {
               /* $mp = ($mp == 'blip-lazada') ? 'lazada' : $mp;
                $mp = ($mp == 'blip-shopee') ? 'shopee' : $mp;
                $mp = ($mp == 'blip-11street') ? '11Street' : $mp;*/
                $cond = " AND c.name = '$mp'";
            } else {
                $cond = " AND c.id = '$mp'";
            }
            $sql = "SELECT 
                  ROUND(SUM(oi.`quantity`)) AS `live`,
                  oi.`item_sku` AS sku
                FROM
                  `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id`
                 LEFT JOIN channels_products cp on cp.channel_sku = oi.item_sku  and c.id=cp.channel_id
                /*WHERE (
                    o.`order_status` = 'pending' || o.`order_status` = 'shipped'
                  ) */
                   WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().")
                  $date_cond
                  $cond
                  AND cp.is_live = 1
                GROUP BY oi.item_sku ";
           // echo $sql; die();
        }
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $re) {
            $refine[$re['sku']] = $re['live'];
        }

        return $refine;

    }
    public static function getMonthsInQuery(){
        $date = date('Y-01-01 00:59:59');
        $end_date = date('Y-12-31 23:59:59');
        $monthWiseData=[];
        while (strtotime($date) <= strtotime($end_date)) {
            $monthDigit= date("m",strtotime($date));
            $monthName=date("M",strtotime($date));
            $year = date("Y",strtotime($date));
            $monthWiseData['months_sum_with_alias'][] = "sum(if(month(oi.item_created_at) = $monthDigit, oi.`quantity`, 0)) AS `".$monthName." $year (Units)`";
            $monthWiseData['months_sum_without_alias'][] = "sum(if(month(oi.item_created_at) = $monthDigit, oi.`quantity`, 0))";
            $date = date ("Y-m-d", strtotime("+1 month", strtotime($date)));
        }

        return $monthWiseData;
    }
    public static function getLast3MonthsInQuery(){
        $sd1 = date("Y-m-d", strtotime('first day of -3 months',time()));
        $ed1 = date("Y-m-d", strtotime('last day of last month',time()));

        $query="";
        $last3month='';
        $cyear=date('Y');
        $year_count=0;
        while (strtotime($sd1) <= strtotime($ed1)) {

            $monthDigit= date("m",strtotime($sd1));
            $year = date("Y",strtotime($sd1));


            if ($year==$cyear){
                $last3month .= "SUM(if(MONTH(oi.item_created_at) = $monthDigit, oi.`quantity`, 0)) +";
                $year_count++;
            }else{
                $last3month .= "";
            }
            $sd1 = date ("Y-m-d", strtotime("+1 month", strtotime($sd1)));

        }
        //echo $last3month;die;
        if ( $last3month=='' ){
            //$last3month .= " 0 AS `total_units_sales_last_".$year_count."_months`, ";
            $last3month .= " 0 AS `avg_units_sales_last_".$year_count."_months`, ";
            $last3month .= " CASE ";
            $last3month .= " WHEN 0 < 10 THEN 'Low' ";
            $last3month .= " END AS selling_status, ";
            $query.=$last3month;
        }else{
            $last3month = rtrim($last3month,'+');
            //$query .= $last3month." AS `total_units_sales_last_".$year_count."_months`, ";
            if ( $year_count!=0 ){
                $query .= " ROUND (  ($last3month) / $year_count , 2 ) AS `avg_units_sales_last_".$year_count."_months`, ";
                $query .= "CASE ";
                $query .= " WHEN ROUND ( ($last3month) / $year_count , 2 ) < 10 THEN 'Low'";
                $query .= " WHEN ROUND ( ($last3month) / $year_count , 2 ) < 25 THEN 'Medium'";
                $query .= " WHEN ROUND ( ($last3month) / $year_count , 2 ) > 25 THEN 'High'";
                $query .= " END AS selling_status, ";
            }
        }
        return $query;
    }
    public static function getAvgMonthlySkus($mp=[],$shops=[], $dr=null)
    {
        $w = HelpUtil::getWarehouseDetail();
        $cond='';
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                $sd1 = date("Y-m-d", strtotime('first day of -3 months',strtotime($ed)));
                $ed1 = date("Y-m-d", strtotime('last day of last month',strtotime($ed)));

                //echo $sd;die;

                //echo $sd;die;
	            $cond .= "
	            AND  oi.`item_created_at` >= '".date('Y-01-01')." 00:00:00'
	            AND oi.`item_created_at`  <= '".date('Y-12-31')." 23:59:59' 
	            AND oi.`fulfilled_by_warehouse` IN ($w)";

            endif;
        }else
            {
                $sd1 = date("Y-m-d", strtotime('first day of -3 months'));
                $ed1 = date("Y-m-d", strtotime('last day of last month'));

                $cond .= "AND oi.`fulfilled_by_warehouse` IN ($w)
                AND ( oi.`item_created_at` ) >= '".date('Y-01-01')." 00:00:00'
                AND ( oi.`item_created_at` ) <= '".date('Y-12-31')." 23:59:59'";
            }
        if ( $mp ){
            $cond .= " AND c.marketplace IN ("."'" . implode ( "', '", $mp ) . "'".") ";
        }
        if ( $shops ){
            $cond .= " AND c.name IN ("."'" . implode ( "', '", $shops ) . "'".") ";
        }
        if ( isset($_GET['Search']['sku']) && $_GET['Search']['sku']!='' ){
            $cond .= " AND p.sku = '".$_GET['Search']['sku']."'";
        }
        $having="";
        if ( !empty($_GET['Search']['Dynamic_Params']) ){
            $having_con = [];
            /*echo '<pre>';
            print_r($_GET);
            die;*/
            foreach ( $_GET['Search']['Dynamic_Params'] as $index=>$value ){

                if ( $value!="" && $index=='selling_status')
                {
                    //echo $value.'aaa';die;
                    $having_con[] = " `$index` = '$value'";
                }
                elseif ($value!=""){
                    $having_con[] = " `$index`  $value";
                }
            }
            /*echo '<prE>';
            print_r($having_con);
            die;*/
            if ($having_con){
                $having = "HAVING ".implode(' AND ',$having_con);
            }

        }
        $monthWiseData = self::getMonthsInQuery();
        $last3Months = self::getLast3MonthsInQuery();
        //echo $monthWiseData;die;
        //echo '<pre>';print_r(explode(',',$monthWiseData));die;
        $connection = Yii::$app->db;
        $sql = "SELECT oi.`item_sku` AS sku,
                ".implode(' + ',$monthWiseData['months_sum_without_alias'])." AS total_units_sales,
                $last3Months
                ".implode(',',$monthWiseData['months_sum_with_alias'])."
                
                FROM `order_items` oi
                LEFT JOIN orders o ON o.id = oi.`order_id` 
                LEFT JOIN channels c ON c.`id` = o.`channel_id`
                LEFT JOIN products p ON p.id = oi.sku_id
                LEFT JOIN threshold_sales ts ON ts.product_id = p.id
                LEFT JOIN channels_products cp on cp.channel_sku = oi.item_sku and c.id = cp.channel_id 
                WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().") $cond
                GROUP BY oi.item_sku
                $having";

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        $final = [];
        $final['total_records'] = count($result);

        if (isset($_GET['page'])){
            $offset = ($_GET['page']==1) ? 0 : $_GET['page'] * 10;
            $record_per_page = (isset($_GET['record_per_page']) && $_GET['record_per_page']!='') ? $_GET['record_per_page'] : 10;
            $sql .= " LIMIT $record_per_page OFFSET ".$offset;
            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
        }
        $final['records'] = $result;

        return $final;
    }
    public static function getAvgMonthlySkusByType()
    {

        $mp = isset($_GET['mp']) ? $_GET['mp'] : 'all';
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
        $shop = isset($_GET['shop']) ? $_GET['shop'] : 'all';
        $cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
        $month = $_GET['month'];
        $cond = $condc  = $join = "";
        $connection = Yii::$app->getDb();
        if ($type == 'monthly') {
            $condc = " AND MONTHNAME( oi.`item_created_at` ) = '$month'
                AND YEAR( oi.`item_created_at` ) = $year";
            $nmonth = date("m", strtotime($month));
            $l3month = $nmonth - 3;
            $l1month = $nmonth - 1;
            $sd1 = date("Y-m-d", strtotime("$year-$l3month-01"));
            $ed1 = date("Y-m-d", strtotime("$year-$l1month-01"));
        } else {
            $condc = " AND QUARTER( oi.`item_created_at` ) = '$month'
                AND YEAR( oi.`item_created_at` ) = $year";
            switch ($month)
            {
                case 1:
                    $l3month = 1;
                    $l1month = 3;
                    break;
                case 2:
                    $l3month = 4;
                    $l1month = 6;
                    break;
                case 3:
                    $l3month = 7;
                    $l1month = 9;
                    break;
                case 4:
                    $l3month = 10;
                    $l1month = 12;
                    break;
            }
            $sd1 = date("Y-m-d", strtotime("-3 months", strtotime("$year-$l1month-01")));
            $ed1 = date("Y-m-d", strtotime("-1 months", strtotime("$year-$l3month-01")));


        }

        if ($cat == 'all') {
            $join = " LEFT JOIN category cat ON cat.id = p.`sub_category`";
            //$cond .= " AND c.id = '{$shop}'";
        }
        if ($cat != 'all') {
            $join = " LEFT JOIN category cat ON cat.id = p.`sub_category`";
            $cond .= " AND cat.`name` = '$cat'";

        }




        $monthCount = (int)abs((strtotime($sd1) - strtotime($ed1)) / (60 * 60 * 24 * 30));
        $condx = "";
        if ($shop == 'all') {
            if($mp == 'all')
                $condx = "";
            else
                $condx = " AND marketplace LIKE '$mp%' ";
            $liveSkus = self::_getLiveAvgBySku($mp, $condc,'marketplace',2);
            $sql = "SELECT 
                  ((SUM(oi.`quantity`) / 3)) AS `avg_monthly`,
                  oi.`item_sku` AS sku,p.selling_status
                FROM
                  `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id`
                  LEFT JOIN products p on p.id = oi.sku_id 
                  $join
                /*WHERE (
                    o.`order_status` = 'pending' || o.`order_status` = 'shipped'
                  ) */
                   WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().")
                  AND p.is_active = 1
                  AND  oi.`item_created_at`  >= '$sd1 00:00:00'
                    AND oi.`item_created_at`  <= '$ed1 23:59:59' 
                  $condx $cond
                GROUP BY oi.item_sku ORDER BY avg_monthly DESC";
        } else {
            $liveSkus = self::_getLiveAvgBySku($shop, $condc, 'shop',2);
            $sql = "SELECT 
                  ((SUM(oi.`quantity`) / 3)) AS `avg_monthly`,
                  oi.`item_sku` AS sku,p.selling_status 
                FROM
                  `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id`
                  LEFT JOIN products p on p.id = oi.sku_id 
                  $join
                /*WHERE (
                    o.`order_status` = 'pending' || o.`order_status` = 'shipped'
                  ) */
                   WHERE oi.`item_status` NOT IN(".self::GetCanceledStatuses().")
                  AND p.is_active = 1
                  AND  oi.`item_created_at`  >= '$sd1 00:00:00'
                    AND oi.`item_created_at`  <= '$ed1 23:59:59' 
                  AND c.id = '$shop' $cond
                GROUP BY oi.item_sku ORDER BY avg_monthly DESC";
        }
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        $refine = [];
        $getLatestCrawlPrice = self::getLatestCrawlPrices();
        foreach ($result as $re) {

            $liveCnt = isset($liveSkus[$re['sku']]) ? $liveSkus[$re['sku']] : 0;
            $re['avg_todate_target'] = 0;
            if ($liveCnt == 0 && $re['avg_monthly'] > 0)
                $re['percentage'] = 0;
            else {
                if ($re['avg_monthly'] == 0 && $liveCnt > 0) {
                    $re['percentage'] = 0;
                } else {
                    $re['avg_todate_target'] = floor(($re['avg_monthly'] / 30) * date('d'));
                    if ($re['avg_todate_target'] > 0) {
                        $re['percentage'] = ($liveCnt / $re['avg_todate_target']) * 100;
                        $re['percentage'] = round($re['percentage'] - 100, 2);
                    } else {
                        $re['percentage'] = 0;
                    }

                }

            }

            $re['avg_monthly'] = round($re['avg_monthly']);
            $re['live'] = $liveCnt;
            if (isset($getLatestCrawlPrice[$re['sku']])) {
                $re['crawl_price'] = $getLatestCrawlPrice[$re['sku']]['price'];
                $re['seller_name'] = $getLatestCrawlPrice[$re['sku']]['seller_name'];
            }

            if ($re['percentage'] <= 0)
                $refine['losses'][] = $re;
            else if ($re['percentage'] > 1)
                $refine['earnings'][] = $re;

            //arsort($refine, SORT_NUMERIC);
        }

        return $refine;
    }

    public static function getLatestCrawlPrices()
    {
        if ((isset($_GET['mp']) && $_GET['mp'] == 'lazada') || (isset($_GET['shop']) && ($_GET['shop'] == 'avent-lazada' || $_GET['shop'] == 'blip-lazada' || $_GET['shop'] == 'deal4u-lazada' ||
                    $_GET['shop'] == '909-lazada')))
            $channel = 1;
        else {
            $channel = 2;
        }
        $connection = Yii::$app->db;
        /*$sql = "SELECT *
                FROM temp_crawl_results
                WHERE ID IN (
                SELECT ID
                FROM temp_crawl_results
                WHERE added_at = (
                SELECT MAX(added_at)
                FROM temp_crawl_results)
                    )
                    AND channel_id = ".$channel."
                GROUP BY product_id
                ORDER BY ID DESC
                ";*/
        $date = date('Y-m-j');
        $newdate = strtotime('-1 day', strtotime($date));
        $yesterday = date('Y-m-j', $newdate);

        $newdate = strtotime('-2 day', strtotime($date));
        $dayBeforeYesterday = date('Y-m-j', $newdate);

        $sql = "SELECT t1.*
                FROM temp_crawl_results t1
                where t1.channel_id = " . $channel . " AND ( t1.added_at = '" . $date . "' OR t1.added_at = '" . $yesterday . "' OR t1.added_at = '" . $dayBeforeYesterday . "' )
                ";
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        $sql2 = "select p.id,p.sku from products p";
        $command2 = $connection->createCommand($sql2);
        $result2 = $command2->queryAll();
        $redefine = [];
        foreach ($result2 as $key => $value) {
            $redefine[$value['id']] = $value['sku'];
        }
        foreach ($result as $key => $value) {
            $result[$key]['sku'] = $redefine[$value['sku_id']];
        }
        $more_redefine = [];
        foreach ($result as $key => $value) {
            $more_redefine[$value['sku']] = $value;
        }
        return $more_redefine;
    }

    public static function getSkuSales($mp, $dr, $type = "marketplace")
    {
        $w = HelpUtil::getWarehouseDetail();
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                $cond = " oi.`fulfilled_by_warehouse` In ($w) 
                    AND oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at`  <= '$ed' ";
            endif;
        } else {
            $cond = "oi.`fulfilled_by_warehouse` In ($w) 
                    AND MONTH( oi.`item_created_at` ) = '".gmdate('m')."'
                    AND YEAR( oi.`item_created_at` ) = '".gmdate('Y')."'";
        }
        $d1 = date("Y-m-d", strtotime('first day of -3 months'));
        $d2 = date("Y-m-d", strtotime('last day of last month'));
        $monthCount = (int)abs((strtotime($d1) - strtotime($d2)) / (60 * 60 * 24 * 30));
        $connection = Yii::$app->db;
        if ($type == 'marketplace') {
            $sql = "SELECT  ((SUM(oi.`quantity`) / $monthCount)) AS `avg_monthly`,oi.`item_sku`,
                COUNT(oi.sku_id) AS sales,o.order_created_at AS `date`,REPLACE(FORMAT(prd.cost,2), ',', '') AS price
                FROM orders o
                RIGHT JOIN order_items oi ON oi.`order_id` = o.id
               # RIGHT JOIN ao_pricing p ON p.`sku_id` = oi.`sku_id` AND o.`channel_id` = p.`channel_id`
                RIGHT JOIN channels c ON c.id = o.`channel_id`
                RIGHT JOIN products prd on prd.id = oi.sku_id 
                WHERE $cond 
                 AND prd.is_active = 1 
               # AND p.`added_at` = (DATE_FORMAT((NOW() - INTERVAL 4 DAY), '%Y-%m-%d'))
                AND c.marketplace LIKE '$mp%'  AND oi.sku_id IS NOT NULL
                GROUP BY oi.`sku_id` ORDER BY sales DESC;";

        } else {
            /*$mp = ($mp == 'blip-lazada') ? 'lazada' : $mp;
            $mp = ($mp == 'blip-shopee') ? 'shopee' : $mp;
            $mp = ($mp == 'blip-11street') ? '11Street' : $mp;*/
            $sql = "SELECT  ((SUM(oi.`quantity`) / $monthCount)) AS `avg_monthly`,oi.`item_sku`,
                COUNT(oi.sku_id) AS sales,o.order_created_at AS `date`, REPLACE(FORMAT(prd.cost,2), ',', '') AS price
                FROM orders o
                RIGHT JOIN order_items oi ON oi.`order_id` = o.id
                #RIGHT JOIN ao_pricing p ON p.`sku_id` = oi.`sku_id` AND o.`channel_id` = p.`channel_id`
                RIGHT JOIN channels c ON c.id = o.`channel_id`
                RIGHT JOIN products prd on prd.id = oi.sku_id 
                WHERE $cond 
                 AND prd.is_active = 1 
               # AND p.`added_at` = (DATE_FORMAT((NOW() - INTERVAL 4 DAY), '%Y-%m-%d'))
                AND c.name = '$mp'  AND oi.sku_id IS NOT NULL
                GROUP BY oi.`sku_id` ORDER BY sales DESC;";
        }
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $re) {
            $value = $re['price'] * $re['sales'];
            $todateAvg = floor(($re['avg_monthly'] / 30) * date('d'));
            $todateAvgValue = $re['price'] * $todateAvg;
            $diff = $value - $todateAvgValue;
            $diff = number_format($diff, 2, '.', '');
            if ($re['price'] > 1)
                $refine[] = ['tavg' => $todateAvg, 'sku' => $re['item_sku'], 'sales' => $re['sales'], 'value' => ($value), 'todate-avg-value' => ($todateAvgValue), 'diff' => ($diff)];
        }
        return $refine;
    }

    public static function getSkuSalesByType()
    {
        $mp = isset($_GET['mp']) ? $_GET['mp'] : 'all';
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $type = isset($_GET['type']) ? $_GET['type'] : 'monthly';
        $shop = isset($_GET['shop']) ? $_GET['shop'] : 'all';
        $cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
        $month = $_GET['month'];
        $cond = $condc  = $join = "";
        $connection = Yii::$app->getDb();
        if ($type == 'monthly') {
            $condc = " AND MONTHNAME( oi.`item_created_at` ) = '$month'
                AND YEAR( oi.`item_created_at` ) = $year";
            $nmonth = date("m", strtotime($month));
            $l3month = $nmonth - 3;
            $l1month = $nmonth;
        } else {
            $condc = " AND QUARTER( oi.`item_created_at` ) = '$month'
                AND YEAR( oi.`item_created_at` ) = $year";
            switch ($month)
            {
                case 1:
                    $l3month = 1;
                    $l1month = 3;
                    break;
                case 2:
                    $l3month = 4;
                    $l1month = 6;
                    break;
                case 3:
                    $l3month = 7;
                    $l1month = 9;
                    break;
                case 4:
                    $l3month = 10;
                    $l1month = 12;
                    break;
            }


        }

        if ($cat == 'all') {
            $join = " LEFT JOIN category cat ON cat.id = p.`sub_category`";
            //$cond .= " AND c.id = '{$shop}'";
        }
        if ($cat != 'all') {
            $join = " LEFT JOIN category cat ON cat.id = p.`sub_category`";
            $cond .= " AND cat.`name` = '$cat'";

        }


        $sd1 = date("Y-m-d", strtotime("$year-$l3month-01"));
        $ed1 = date("Y-m-d", strtotime("$year-$l1month-01"));

        $monthCount = (int)abs((strtotime($sd1) - strtotime($ed1)) / (60 * 60 * 24 * 30));
        $condx = "";
        if ($shop == 'all') {
            if ($mp == 'all')
                $condx = "";
            else
                $condx = " AND marketplace LIKE '$mp%' ";
            $sql = "SELECT  ((SUM(oi.`quantity`) / $monthCount)) AS `avg_monthly`,oi.`item_sku`,COUNT(oi.sku_id) AS sales,o.order_created_at AS `date`,FORMAT(p.sale_price,2) AS price
                FROM orders o
                RIGHT JOIN order_items oi ON oi.`order_id` = o.id
                RIGHT JOIN ao_pricing p ON p.`sku_id` = oi.`sku_id` AND o.`channel_id` = p.`channel_id`
                RIGHT JOIN channels c ON c.id = o.`channel_id`
                RIGHT JOIN products prd on prd.id = oi.sku_id 
                $join
                WHERE 
                prd.is_active = 1 $condc 
                AND p.`added_at` = (DATE_FORMAT((NOW() - INTERVAL 20 DAY), '%Y-%m-%d'))
                AND oi.sku_id IS NOT NULL $condx $cond 
                GROUP BY oi.`sku_id` ORDER BY sales DESC;";
        } else {
            $sql = "SELECT  ((SUM(oi.`quantity`) / $monthCount)) AS `avg_monthly`,oi.`item_sku`,COUNT(oi.sku_id) AS sales,o.order_created_at AS `date`,FORMAT(p.sale_price,2) AS price
                FROM orders o
                RIGHT JOIN order_items oi ON oi.`order_id` = o.id
                RIGHT JOIN ao_pricing p ON p.`sku_id` = oi.`sku_id` AND o.`channel_id` = p.`channel_id`
                RIGHT JOIN channels c ON c.id = o.`channel_id`
                RIGHT JOIN products prd on prd.id = oi.sku_id
                $join 
                WHERE 
                prd.is_active = 1 $condc 
                AND p.`added_at` = (DATE_FORMAT((NOW() - INTERVAL 20 DAY), '%Y-%m-%d'))
                AND c.id = '$shop' $cond  AND oi.sku_id IS NOT NULL
                GROUP BY oi.`sku_id` ORDER BY sales DESC;";
        }
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $re) {
            $value = $re['price'] * $re['sales'];
            $todateAvg = floor(($re['avg_monthly'] / 30) * date('d'));
            $todateAvgValue = $re['price'] * $todateAvg;
            $diff = $value - $todateAvgValue;
            $diff = number_format($diff, 2, '.', '');
            if ($re['price'] > 1)
                $refine[] = ['tavg' => $todateAvg, 'sku' => $re['item_sku'], 'sales' => $re['sales'], 'value' => ($value), 'todate-avg-value' => ($todateAvgValue), 'diff' => ($diff)];
        }
        return $refine;
    }


    public static function getSalesTargetReveiw($mp, $dr, $type = 'marketplace')
    {
        $w = HelpUtil::getWarehouseDetail();
        if ($dr) {
            $dr = explode(' to ', $dr);
            if(is_array($dr)):
                $sd = HelpUtil::get_utc_time($dr[0] ." 00:00:00");
                $ed = HelpUtil::get_utc_time($dr[1] ." 23:59:59");

                $cond = " oi.`fulfilled_by_warehouse` In ($w)
                    AND oi.`item_created_at`  >= '$sd'
                    AND oi.`item_created_at`  <= '$ed' ";
            endif;
        } else {

            $cond = " oi.`fulfilled_by_warehouse` In ($w)
                    AND MONTH( oi.`item_created_at` ) = '".gmdate('m')."'
                    AND YEAR( oi.`item_created_at` ) = '".gmdate('Y')."'";
        }
        $m = gmdate('m') * 1;
        $connection = Yii::$app->db;
        if ($type == 'marketplace') {
            $sql = "SELECT c.name,ct.target as target,SUM(oi.paid_price) AS sales
                FROM channels c 
                INNER JOIN `channles_targets` ct ON ct.`channel_id` = c.`id`
                INNER JOIN orders o ON o.`channel_id` = c.`id`
                INNER JOIN order_items oi ON oi.`order_id` = o.`id`
                WHERE $cond
                AND ct.`month` = '$m' AND c.`marketplace` = '$mp' GROUP BY c.`id`";
        } else {
            $sql = "SELECT c.name,ct.target as target,SUM(oi.paid_price) AS sales
                FROM channels c 
                INNER JOIN `channles_targets` ct ON ct.`channel_id` = c.`id`
                INNER JOIN orders o ON o.`channel_id` = c.`id`
                INNER JOIN order_items oi ON oi.`order_id` = o.`id`
                WHERE $cond
                AND ct.`month` = '$m' AND c.name = '$mp' GROUP BY c.`id`";
        }

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $refine = [];
        foreach ($result as $re) {
            $refine[] = ['channel' => $re['name'], 'target' => number_format($re['target']), 'sales' => number_format($re['sales'])];
        }
        return $refine;
    }

    // get average day sales from last 3 and 6 months
    public static function getAverageDaySales($lastMonths = 3)
    {
        $w = HelpUtil::getWarehouseDetail();

        $sd = date("Y-m-d", strtotime('first day of -' . $lastMonths . ' months'));
        $ed = date("Y-m-d ", strtotime('last day of last month'));
        $sd = HelpUtil::get_utc_time($sd ." 00:00:00");
        $ed = HelpUtil::get_utc_time($ed ." 23:59:59");
        $datediff = strtotime($ed ) - strtotime($sd);
        $days = round($datediff / (60 * 60 * 24));

        $connection = Yii::$app->db;

           $sql = "SELECT 
                  ROUND(SUM(oi.sub_total) / $days,2) AS `avg_daily`
                FROM
                   `order_items` oi 
                  LEFT JOIN orders o 
                    ON o.id = oi.`order_id` 
                  LEFT JOIN channels c 
                    ON c.`id` = o.`channel_id` 
                   WHERE oi.`item_status` NOT IN(".self::GetMappedCanceledStatuses().")
                  AND oi.`fulfilled_by_warehouse` IN ($w)
                  AND  oi.`item_created_at`  >= '$sd'
                  AND oi.`item_created_at`  <= '$ed'";

        $command = $connection->createCommand($sql);
        $ret = $command->queryOne();
        return $ret['avg_daily'];
    }

    public static function get_sku_sale($sku_list)
    {
        if(is_array($sku_list))
            $skus = '"'.implode('","',$sku_list ).'"';
        else
            $skus="'".$sku_list."'";

        $connection = Yii::$app->db;
       $sql="SELECT
              FORMAT(SUM(oi.sub_total), 0) AS sales,sum(oi.`quantity`) as total_qty_ordered,max(date(`oi`.`item_updated_at`)) as last_ordered,min(date(`oi`.`item_updated_at`)) as first_ordered
            FROM
              `order_items` oi
            WHERE 
            `oi`.`item_sku` IN(".$skus.") AND oi.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")";
        $command = $connection->createCommand($sql);
        $result = $command->queryone();
        return $result;


    }

    public static function getSalesForcast($marketplace=[],$shop=[])
    {
        $where='';
        if ($marketplace){
            $where .= " AND c.marketplace IN ("."'" . implode ( "', '", $marketplace ) . "'".") ";
        }
        if ($shop){
            $where .= " AND c.name IN ("."'" . implode ( "', '", $shop ) . "'".") ";
        }
        if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
        {
            $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";
        }
        if(isset($_GET['sku']) && $_GET['sku'])
            $where .= " AND `oi`.`item_sku`='".$_GET['sku']."'";

        $w =HelpUtil::getWarehouseDetail();
        $connection = Yii::$app->db;

            $sql = "SELECT
              FORMAT(SUM(oi.sub_total), 0) AS sales,
              MONTHNAME(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) AS 'month',
              YEAR(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) AS 'year'
            FROM
              `order_items` oi 
              INNER JOIN orders o ON o.id = oi.`order_id`
              INNER JOIN channels c 
                ON c.`id` = o.`channel_id` 
              
               INNER JOIN warehouses w
               ON w.`id` = oi.`fulfilled_by_warehouse`
            WHERE (
                oi.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                AND
                w.`id` IN ($w)
              ) 
            AND YEAR(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) > YEAR(CURDATE()- interval 3 year) 
            $where
            GROUP BY MONTHNAME(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')),
              YEAR(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) ORDER BY MONTH(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "'))";

        //echo $sql;die;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();

        $months=['january','february','march','april','may','june','july','august','september','october','november','december'];

        $refine = $ret = $sales = [];
        foreach ($result as $d) {
            $sales[] = str_replace(',', '', $d['sales']);

            $month_index=in_array(strtolower($d['month']),$months)? array_search(strtolower($d['month']),$months):"-1";
            $refine[$d['year']][array_search(strtolower($d['month']),$months)]= ['meta' => $d['year'], 'value' => str_replace(',', '', $d['sales'])];


        }



        /// ///////////////////////
        // adding dummy months data into current year

       if ( isset($refine) ){
            $main_array=array();
            foreach($refine as $k=>$v)
            {
                $sub_array=array();
                for ($i =0; $i <= 11; $i++) {
                    if(!isset($refine[$k][$i])) {
                        $sub_array[$k][$i] = ['meta' => $k, 'value' => 0];
                     //  $sales[$i] = 0; // remove this if graph do no works
                    }
                    else {
                        $sub_array[$k][$i] = ['meta' => $refine[$k][$i]['meta'], 'value' => $refine[$k][$i]['value']];
                     //   $sales[$i] = $refine[$k][$i]['value']; // remove this if graph do no works
                    }

                }
                $refine[$k]=$sub_array[$k];
            }


        }
        if (empty($sales))
            $sales[]=0;
        //array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);
        $ret['refine'] = $refine;
        $ret['max'] = max($sales);
        $ret['min'] = min($sales);
        return $ret;
    }

    /***
     * filter
     */
    private static function monthly_sales_graph_by_shop_filter()
    {
        $where="";
        if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
             $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";

        if(isset($_GET['filter_graph_year']) && !empty($_GET['filter_graph_year']))
            $where .= " AND YEAR(CONVERT_TZ(oi.item_created_at,'UTC', '" . Yii::$app->params['current_time_zone'] . "')) = '".$_GET['filter_graph_year']."'";

        if(isset($_GET['mp']) && $_GET['mp']!='') // marketplace
            $where .= " AND `c`.`marketplace` ='".$_GET['mp']."'";

        if(isset($_GET['shop']) && $_GET['shop']!='') // shop name
            $where .= " AND `c`.`name` ='".$_GET['shop']."'";

        if(isset($_GET['filter_brand']) && !empty($_GET['filter_brand']))
            $where .= " AND `p`.`brand`='".$_GET['filter_brand']."'";

        if(isset($_GET['filter_style']) && !empty($_GET['filter_style']))
            $where .= " AND `p`.`style`='".$_GET['filter_style']."'";

        if(isset($_GET['sku_list']) && !empty($_GET['sku_list']))
        {
            if(is_array($_GET['sku_list'])){
                $skus = '"'.implode('","',$_GET['sku_list'] ).'"';
                $where .= " AND `oi`.`item_sku` IN(".$skus.")";
            }

        }
        elseif(isset($_GET['sku']) && !empty($_GET['sku']))
            $where .= " AND `oi`.`item_sku`='".$_GET['sku']."'";


        if(isset($_GET['filter_cat']) && !empty($_GET['filter_cat']))
        {
            $parent=$_GET['filter_cat'];
           $list= HelpUtil::GetAllChildCategories($parent);
          // self:self::debug(implode($list,','));
           // $get_all_child=Category::find()->select('id')->where(['parent_id'=>$_GET['filter_cat']])->asArray()->all();
            //self::debug($get_all_child);
          //  $ids=array_column($get_all_child,'id');
           // $ids= array_merge($parent,$ids);
            array_push($list,$parent);
            $ids=implode($list,',');
         //   self::debug($ids);
            //$ids= array_merge($parent,$ids);
            if($ids)
                $where .= " AND `pc`.`cat_id` IN (".$ids.")";
        }




        return $where;
    }
/**
 * sales by shop/marketplace graph for daashboard
*/
    public static function SalesGraphByShop($marketplace=[],$shop=[])
    {
        $additional_join="";
        $warehouses=HelpUtil::getWarehouseDetail();
        if(isset($_GET['filter_graph_calendar_sort']) && !empty($_GET['filter_graph_calendar_sort']))
        {
            if($_GET['filter_graph_calendar_sort']=="quarterly")
                return self::SalesGraphByShop_quarterly();  // quarter query and data
        }
        $where=self::monthly_sales_graph_by_shop_filter(); // get filters


        $select_by="name"; // select by channel name default
        if(isset($_GET['filter_graph_display_by']) && !empty($_GET['filter_graph_display_by']))
        {
            if($_GET['filter_graph_display_by']=="marketplace")
                $select_by="marketplace";
        }
        if((isset($_GET['filter_cat']) || isset($_GET['filter_brand'])) && (!empty($_GET['filter_cat']) || !empty($_GET['filter_brand']))) // if category filter sent then join with product as well
        {
            $additional_join .=" INNER JOIN `products` p ON `p`.`id`=`oi`.`sku_id` 
                  INNER JOIN product_categories pc ON p.id = pc.product_id 
                  INNER JOIN category cat ON cat.id = pc.`cat_id`";
        }
        /*if(isset($_GET['filter_brand']) && !empty($_GET['filter_brand']) && !$additional_join) // if category filter sent then join with product as well
        {

            $additional_join .=" INNER JOIN `products` p ON `p`.`id`=`oi`.`sku_id`";
        }*/

        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")
            $stats_column=" count(distinct(`oi`.`order_id`)) ";
        else
            $stats_column=" FORMAT(SUM(`oi`.`sub_total`), 0) ";

        $connection = Yii::$app->db;

        $sql = "SELECT
                 $stats_column AS sales,c.$select_by as channel,
                 MONTHNAME(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) AS 'month'
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              INNER JOIN
                channels `c` 
                ON 
                    `c`.`id` = `o`.`channel_id`
              $additional_join
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ") 
                AND oi.fulfilled_by_warehouse IN ($warehouses)
            $where
            GROUP BY
                MONTHNAME(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')),
                c.name
            ORDER BY 
                MONTH(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')),
                c.name
                ";

      // echo $sql;die;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
       // print_r($result);exit;

        $months=['january','february','march','april','may','june','july','august','september','october','november','december'];

        $refine = $ret =  [];
        $labels=[]; // its label fro graph to store shops/marketplace name
        $colors=['#298CB4', '#2f3d4a', '#55ce63','#7460EE','#FF2D74','#F9821C'];  //colors of graph
        $sales=[];
        foreach ($result as $d) {
            $sales[] = str_replace(',', '', $d['sales']);
            //$refine[$d['year']][array_search(strtolower($d['month']),$months)]= ['meta' => $d['year'], 'value' => str_replace(',', '', $d['sales'])];
            $refine[array_search(strtolower($d['month']),$months)][$d['channel']]=str_replace(',', '', $d['sales']) ;

            if (!in_array($d['channel'],$labels))
                array_push($labels,$d['channel']);

        }

        // adding dummy months data into current year

        if ( isset($refine) ){
            $main_array=array();
            foreach($refine as $k=>$v)
            {
                for ($i =0; $i < count($months); $i++) {
                    if(!isset($refine[$i]))
                        $refine[$i]= ['y'=>substr(ucfirst($months[$i]),'0','3')];
                    else
                        $refine[$i]=array_merge(['y'=>substr(ucfirst($months[$i]),'0','3')],$refine[$i]);
                }
            }

        }

        ksort($refine);
        sort($labels);
        $ret= [
            'data'=>$refine,
            'labels'=>$labels,
            'colors'=>$colors,
           // 'max_value'=> ($sales && is_array($sales)) ? max($sales):15000,  //maximum value of sale
        ];
       /*echo "<pre>";
        print_r($ret); die();*/
        return $ret;

    }

    /**
     * sales by shop/marketplace graph for daashboard
     */
    public static function SalesGraphByShop_quarterly($marketplace=[],$shop=[])
    {
        $additional_join="";
        $warehouses=HelpUtil::getWarehouseDetail();
        if(isset($_GET['filter_graph_calendar_sort']) && !empty($_GET['filter_graph_calendar_sort']))
        {
            if($_GET['filter_graph_calendar_sort']=="monthly")
                return self::SalesGraphByShop();  // quarter query and data
        }
        $where=self::monthly_sales_graph_by_shop_filter(); // get filters


        $select_by="name"; // select by channel name default
        if(isset($_GET['filter_graph_display_by']) && !empty($_GET['filter_graph_display_by']))
        {
            if($_GET['filter_graph_display_by']=="marketplace")
                $select_by="marketplace";
        }
        if(isset($_GET['filter_cat']) && !empty($_GET['filter_cat'])) // if category filter sent then join with product as well
        {
            $additional_join .=" INNER JOIN `products` p ON `p`.`id`=`oi`.`sku_id` 
                                 INNER JOIN `product_categories` pc ON `p`.`id` = `pc`.`product_id` 
                                 INNER JOIN `category` cat ON `cat`.`id` = `pc`.`cat_id`";

        }
        if(isset($_GET['filter_brand']) && !empty($_GET['filter_brand'])) // if category filter sent then join with product as well
        {
            $additional_join .=" INNER JOIN `products` p ON `p`.`id`=`oi`.`sku_id`";
        }
        if(isset($_GET['filter_style']) && !empty($_GET['filter_style'])) // if style filter sent then join with product as well
        {
            $additional_join .=" INNER JOIN `products` p ON `p`.`id`=`oi`.`sku_id`";
        }
        if(isset($_GET['stats_by']) && $_GET['stats_by']=="orders")
            $stats_column=" count(distinct(`oi`.`order_id`)) ";
        else
            $stats_column=" FORMAT(SUM(`oi`.`sub_total`), 0) ";

        $connection = Yii::$app->db;

        $sql = "SELECT
                 $stats_column AS sales,c.$select_by as channel,
                 QUARTER(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')) AS 'quarter'
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              INNER JOIN
                channels `c` 
                ON 
                    `c`.`id` = `o`.`channel_id`
                 $additional_join   
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                 AND oi.fulfilled_by_warehouse IN ($warehouses)
            $where
            GROUP BY
                QUARTER(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')),
                c.name
            ORDER BY 
                QUARTER(CONVERT_TZ(oi.item_created_at,'UTC','" . Yii::$app->params['current_time_zone'] . "')),
                c.name
                ";

       // echo $sql;die;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        //  echo "<pre>";
       // print_r($result); die();

        $quarter=[1,2,3,4];
        $quarter_name=['Quarter 1','Quarter 2','Quarter 3','Quarter 4'];

        $refine = $ret =  [];
        $labels=[]; // its label fro graph to store shops/marketplace name
        foreach ($result as $d) {
            $sales[] = str_replace(',', '', $d['sales']);
            //$refine[$d['year']][array_search(strtolower($d['month']),$months)]= ['meta' => $d['year'], 'value' => str_replace(',', '', $d['sales'])];
            $refine[array_search(strtolower($d['quarter']),$quarter)][$d['channel']]=str_replace(',', '', $d['sales']) ;

            if (!in_array($d['channel'],$labels))
                array_push($labels,$d['channel']);

        }
        /* echo "<pre>";
         print_r($refine); die();*/

        // adding dummy

        if ( isset($refine) ){
            $main_array=array();
            foreach($refine as $k=>$v)
            {
                for ($i =0; $i < count($quarter); $i++) {
                    if(!isset($refine[$i]))
                        $refine[$i]= ['y'=>$quarter_name[$i]];
                    else
                        $refine[$i]=array_merge(['y'=>$quarter_name[$i]],$refine[$i]);
                }
            }

        }

        ksort($refine);
        sort($labels);
        //echo "<pre>";
       // print_r($refine); die();
        $ret= [
            'data'=>$refine,
            'labels'=>$labels,
        ];
        return $ret;

    }
    /***
     * sales contribution by marketplace /shop
     */
   public static function sales_contributions($by="marketplace")
   {

       $warehouses = HelpUtil::getWarehouseDetail(); // if distributor logged in get his warehouses else all
       $where='';
       if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
            $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";

       if(isset($_GET['mp']) && $_GET['mp']!='') // marketplace
           $where .= " AND `c`.`marketplace`='".$_GET['mp']."'";

       if(isset($_GET['shop']) && $_GET['shop']!='') // shop name
           $where .= " AND `c`.`name`='".$_GET['shop']."'";

       if($by=="shop")
            $channel_column= " `c`.`name` "; // channel name
       else
           $channel_column= " `c`.`marketplace` ";

       $connection = Yii::$app->db;
       $sql = "SELECT
                sum(oi.sub_total) AS sales,$channel_column as channel, c.logo ,count(DISTINCT(o.id)) as orders,count(DISTINCT(o.customer_fname),2) as customers
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              INNER JOIN
                channels `c` 
                ON 
                    `c`.`id` = `o`.`channel_id`
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")  
               AND oi.fulfilled_by_warehouse IN ($warehouses)
             
            $where
            GROUP BY
                $channel_column
            ORDER BY 
               sales DESC,
                c.name
                ";

       //echo $sql;die;
       $command = $connection->createCommand($sql);
       $result = $command->queryAll();
       $return=[];
       if($result)
       {

           $sales=[];
           $total_revenue=array_sum(array_column($result,'sales'));
           $total_orders=array_sum(array_column($result,'orders'));
           $total_customers=array_sum(array_column($result,'customers'));
           foreach($result as $row)
           {
                $sales[]=[
                    'sales'=>$row['sales'],
                    'orders'=>$row['orders'],
                    'customers'=>$row['customers'],
                    'channel'=>$row['channel'],
                    'channel_logo'=>$row['logo'],
                    'percent_in_total_sale'=>round(($row['sales']/$total_revenue)*100),
                ];
           }
           $return=[
                   'total_revenue'=>$total_revenue,
                   'total_orders'=>$total_orders,
                   'total_customers'=>$total_customers,
                   'sales'=>$sales,
               ];
       }
       return $return;

   }

    /***
     * overall total sales
     */
   public static function total_sales()
   {
       $warehouses = HelpUtil::getWarehouseDetail(); // if distributor logged in get his warehouses else all
       $connection = Yii::$app->db;
       $sql = "SELECT
                sum(oi.sub_total) AS sales, count(DISTINCT(o.id)) as orders
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              INNER JOIN
                channels `c` 
                ON 
                    `c`.`id` = `o`.`channel_id`
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")  
               AND oi.fulfilled_by_warehouse IN ($warehouses)
             
            ORDER BY 
               sales DESC,
                c.name
                ";

       //echo $sql;die;
       $command = $connection->createCommand($sql);
       $result = $command->queryone();
       return $result;
   }

    /***
     * brand wise sales
     */
   public static function brands_sales()
   {
       $warehouses = HelpUtil::getWarehouseDetail(); // if distributor logged in get his warehouses else all
       $where='';
       if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
           $where .= " AND `o`.`customer_type`='".$_GET['customer_type']."'";

       $connection = Yii::$app->db;
       $sql = "SELECT
                sum(oi.sub_total) AS sales,`p`.`brand`, count(DISTINCT(o.id)) as orders,count(DISTINCT(o.customer_fname),2) as customers
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              LEFT JOIN
                products `p` 
                ON 
                    `oi`.`item_sku` = `p`.`sku`
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")  
               AND oi.fulfilled_by_warehouse IN ($warehouses)
             
            $where
            GROUP BY
                `p`.`brand`
            ORDER BY 
               sales DESC
                ";
       $command = $connection->createCommand($sql);
       $result = $command->queryAll();
       $return=[];
       if($result)
       {
           foreach($result as $row)
           {
               $first_order=self::brand_first_order($row['brand']);
               $return[]=[
                   'first_order'=>$first_order,
                   'stats'=>$row
               ];
           }
       }
       return $return;
   }

    /***
     * first order of brand
     */
    public static function brand_first_order($brand)
    {
        $connection = Yii::$app->db;
        $sql="SELECT `oi`.`item_updated_at` as first_order_date
                  FROM 
                    order_items oi
                  LEFT JOIN 
                    products p
                  ON 
                    oi.item_sku=p.sku    
                  WHERE 
                    `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                    AND `p`.`brand`='".str_replace("'", "\'", $brand)."'
                  ORDER BY 
                    item_updated_at ASC 
                  LIMIT 1";
        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        if($result)
        {
            $first_order=$result['first_order_date'];
            $now = time();
            $f_d_str = strtotime($first_order);
            $datediff = $now - $f_d_str;
            $days_passed=round($datediff / (60 * 60 * 24)); // total days passed since first order
            $weeks_passed=round($days_passed / 7);
            $months_passed=round($days_passed / 30.417);
            return [
                'first_order_date'=>$first_order,
                'days_passed'=>$days_passed,
                'weeks_passed'=>($weeks_passed > 0 && $weeks_passed) ? $weeks_passed:1 ,
                'months_passed'=>($months_passed > 0 && $months_passed) ? $months_passed:1,
            ];

        }
        return [];
    }

    /***
     * filter for top performers
     */
   private static function top_performers_filter()
   {
       $where="";
       if(isset($_GET['customer_type']) && in_array($_GET['customer_type'],['b2b','b2c']))
            $where .= " AND `customer_type`='".$_GET['customer_type']."'";

       if(isset($_GET['mp']) && $_GET['mp']!="")  // marketplace by name
           $where .= " AND c.`marketplace`='".$_GET['mp']."'";

       if(isset($_GET['shop']) && $_GET['shop']!="")  // shop by name
           $where .= " AND c.`name`='".$_GET['shop']."'";

       return $where;
   }
    /***
     * top 10 performers products
     */
    public static function top_performers($by=null,$stock_fetch='no')
    {
        $warehouses = HelpUtil::getWarehouseDetail(); // if distributor logged in get his warehouses else all
        $where='';
        $where=self::top_performers_filter();
        if($by=="shop")
            $by_shop_or_marketplace="`c`.`name`"; ///// by shop / channel
        else
            $by_shop_or_marketplace="`c`.`marketplace`"; ///// by marketplace

        $connection = Yii::$app->db;
        $sql = "SELECT
                 sum(oi.sub_total) AS sales,
                 $by_shop_or_marketplace as channel ,p.sku,p.name,p.image,p.parent_sku_id,p.id,
                 parentp.name as parent ,parentp.sku as parent_sku ,parentp.image as parent_image
            FROM
              `order_items` `oi` 
              INNER JOIN 
                orders `o` 
                ON 
                    `o`.`id` = `oi`.`order_id`
              INNER JOIN
                channels `c` 
                ON 
                    `c`.`id` = `o`.`channel_id` 
                    
              INNER JOIN 
                    `products` p 
               ON 
                   `p`.`sku`=`oi`.`item_sku`
              LEFT join `products` parentp 
                ON `p`.`parent_sku_id`=`parentp`.`id`   
            WHERE 
                `oi`.`item_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                AND oi.fulfilled_by_warehouse IN ($warehouses)
                $where
            GROUP BY
                `oi`.`item_sku` ,
                $by_shop_or_marketplace
            ORDER BY 
               sales DESC,
                #c.name 
                parentp.name 
            ";
       // echo $sql; die();
            /*****pagination****/
            /*$total_top_performers = Yii::$app->db->createCommand($sql)->query()->count();
            $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
            $page=isset($_GET['page']) ? $_GET['page']:1;
            $offset = 10 * ($page - 1);
            $sql .= "LIMIT " . $offset . ", $per_page";*/
            /*****pagination****/


            $command = $connection->createCommand($sql);
            $result = $command->queryAll();
            // return;
           // echo "<pre>";
           // print_r($result); die();
        $make=[];
        foreach($result as $item) {
            $stock = 0; // for dashboard no need to show
            if ($stock_fetch == "yes")
            {
                $item_stock = WarehouseStockList::find()->where(['sku' => $item['sku']])->sum('available'); //stock overall all channels all warehouses
                $stock = $item_stock ? $item_stock : 0;
            }
            if(isset($make[$item['parent_sku_id']]))  // if already index set by parent product
            {
                $make[$item['parent_sku_id']]['sales']=$make[$item['parent_sku_id']]['sales'] + $item['sales'];  // add sales of child product sales to sales variable inside parent index and outside children array
              //  echo $item['parent']." -> " .$make[$item['parent_sku_id']]['stock'] ."<br/>";
                 //   die('aja');
               // $make[$item['parent_sku_id']]['stock'] +=$stock;
                $make[$item['parent_sku_id']]['children'][$item['sku']][$item['channel']]=$item;  // add item to children of parent index
                $make[$item['parent_sku_id']]['children'][$item['sku']]['stock']=$stock; //stock overall all channels all warehouses
               // die('aja');
            } elseif($item['parent_sku_id'] ){ // if not set parent index then it will make // will execute once for parent
                $make[$item['parent_sku_id']]=['name'=>$item['parent'],'sku'=>$item['parent_sku'],'image'=>$item['parent_image'],'sales'=>$item['sales']];
                $make[$item['parent_sku_id']]['children'][$item['sku']][$item['channel']]=$item;
                $make[$item['parent_sku_id']]['children'][$item['sku']]['stock']=$stock; //stock overall all channels all warehouses

            } elseif(isset($make[$item['id']])) { // if product has not child and it is itself parent and if index is made already in $make

                $make[$item['id']]['sales']=$make[$item['id']]['sales'] + $item['sales'];
                $make[$item['id']]['stock']=$stock; //stock
                if(!isset($make[$item['id']]['children']))
                     $make[$item['id']]['markets'][$item['channel']]=$item; // same item can be in multiple markets./channels

            } else { // if product is itself parent and yet not stored in $make put it there
                $make[$item['id']] = ['name' => $item['name'],'sku'=>$item['sku'],'image'=>$item['parent_image'], 'sales' => $item['sales'],'stock'=>$stock];
                $make[$item['id']]['markets'][$item['channel']] = $item; // same item can be in multiple markets./channels
            }
        }

        array_multisort( array_column( $make, 'sales' ), SORT_DESC, SORT_NUMERIC, $make ); // sort by sales descending
        $total_sales=array_column($make,'sales'); // get all values of sales column
        $sales_sum=array_sum($total_sales); // sum all the sales values it is sum of all the sales of items

        //////selecting shops and marketpalces
        $where_channel="";
        if(isset($_GET['mp']) && $_GET['mp']!="")  // marketplace
            $where_channel .= " AND `marketplace`='".$_GET['mp']."'";

        if(isset($_GET['shop']) && $_GET['shop']!="")  // shop
            $where_channel .= " AND `name`='".$_GET['shop']."'";

        if($by=="shop")
            $marketplaces="SELECT DISTINCT(`name`) FROM `channels` where is_active=1 $where_channel";
        else
            $marketplaces="SELECT DISTINCT(marketplace) FROM `channels` where is_active=1 $where_channel";

        $marketplaces= $connection->createCommand($marketplaces)->queryColumn();
        return ['total_sales'=>$sales_sum,
                'products'=>$make ,//array_splice($make,0,10),
                'marketplaces'=>$marketplaces,
              //  'total_top_performers'=>$total_top_performers
        ];
    }

    /**
     * it will return date of first order placed
     */
    public static function first_order_date($year=null,$market_place=null,$channel=null)
    {
            $connection = Yii::$app->db;
            $where="";
            if($year) // if year sent then order of that year
                $where .=" AND year(order_updated_at) = $year";
            if($market_place) {  //if arketplace sent

                $channels=Channels::find()->select('id')->where(['marketplace'=>$market_place])->asArray()->all();  // al channels in marketpalces
                $channel_ids=array_column($channels,'id'); // channels inside marketplace
                if($channel_ids)
                    $where .=" AND `channel_id` in (".implode($channel_ids,',').")";
            }
            if($channel){
                $channel_id=Channels::find()->where(['name'=>$channel])->select('id')->scalar();
                if($channel_id)
                  $where .=" AND `channel_id` in (".$channel_id.")";
            }

            $sql="SELECT `order_updated_at` as first_order_date
                  FROM 
                    orders
                  WHERE 
                    `order_status` NOT IN  (" . GraphsUtil::GetMappedCanceledStatuses() . ")
                    $where
                  ORDER BY 
                    order_updated_at ASC 
                  LIMIT 1";
        $command = $connection->createCommand($sql);
        $result = $command->queryOne();
        if($result)
        {
            $first_order=$result['first_order_date'];
            $now = time();
            $f_d_str = strtotime($first_order);
            $datediff = $now - $f_d_str;
            $days_passed=round($datediff / (60 * 60 * 24)); // total days passed since first order
            $weeks_passed=round($days_passed / 7);
            $months_passed=round($days_passed / 30.417);
            return [
                'first_order_date'=>$first_order,
                'days_passed'=>$days_passed,
                'weeks_passed'=>($weeks_passed > 0 && $weeks_passed) ? $weeks_passed:1 ,
                'months_passed'=>($months_passed > 0 && $months_passed) ? $months_passed:1,
            ];

        }
        return [];

    }
    /*public static function make_category_tree(array $elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element['parent_sku_id'] == $parentId) {
                $children = self::make_category_tree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[$element['id']] = $element;
                unset($elements[$element['id']]);
            }
        }
        return $branch;
        return $result;
    }*/
public static function getStock()
{
    $connection = Yii::$app->db;
  
    $sql ="select sum(available)  as sales,month(added_at) AS month from warehouse_stock_list GROUP BY month(added_at)";
     $command = $connection->createCommand($sql);
    $result = $command->queryAll();

    
    $months=['january','february','march','april','may','june','july','august','september','october','november','december'];

    $refine = $ret = $sales = [];
    $monthly_records = [];
    foreach ($result as $d) {

        $monthly_records  = $d['month'];
        $sales[] = str_replace(',', '', $d['sales']);

        $month_index=in_array(strtolower($d['month']),$months)? array_search(strtolower($d['month']),$months):"";
        
       $refine[array_search(strtolower($d['sales']),$sales)]= ['meta' => $d['month'], 'value' => str_replace(',', '', $d['sales'])];
           }

    

    if (empty($sales))
        $sales[]=0;
    //array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);
    $ret['refine'] = $refine;
    
    $ret['max'] = max($sales);
    $ret['min'] = min($sales);

    

    return $ret;


    
}
    public  static function getMarketplaceSalesForcast()
    {
        $w = HelpUtil::getWarehouseDetail();
        $connection = Yii::$app->db;
        $year = date('Y');

        $sql = "SELECT SUM(oi.sub_total) AS sales ,c.`marketplace`,cat.`name` AS category  ,cat.`id` as cat_id                
            FROM `order_items` oi 
            JOIN orders o 
            ON o.id = oi.`order_id` 
            JOIN channels c 
            ON c.`id` = o.`channel_id` 
            JOIN products p 
            ON p.id = oi.`sku_id`
            JOIN category cat ON cat.id = p.`sub_category`
            WHERE 
            oi.`item_status` NOT IN(" . self::GetMappedCanceledStatuses() . ")
            AND  YEAR(CONVERT_TZ(oi.`item_created_at`,'UTC','" . Yii::$app->params['current_time_zone'] . "')) > YEAR(CURDATE()- INTERVAL 3 YEAR)
            AND c.`is_active` = 1 
            AND oi.`fulfilled_by_warehouse` IN ($w)
            GROUP BY c.`marketplace`, cat_id order by sales desc ";

                /*cat.name";*/
         //print_r($sql); die();
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
      //  echo "<pre>";
       // print_r($result); die();
        $refine = $ret = $sales = [];
        foreach ($result as $d) {
            $sales[$d['marketplace']][] = str_replace(',', '', $d['sales']);
            //get parent cat
           $parent_cat= CatalogUtil::get_category_parent($d['cat_id']);
           $parent_cat=isset($parent_cat['name']) ? $parent_cat['name']: $d['category'];
           if ( isset($refine[$d['marketplace']][$parent_cat]) ){
               $refine[$d['marketplace']][$parent_cat] += str_replace(',', '', $d['sales']);
           }else{
               $refine[$d['marketplace']][$parent_cat] = str_replace(',', '', $d['sales']);
           }

        }
        //echo '<pre>';print_r($refine);die;
        //array_multisort($sales, SORT_DESC, SORT_NUMERIC, $refine);
        $ret['refine'] = $refine;
        $ret['sales'] = $sales;
      //  print_r($ret); die();
        return $ret;
    }
    public static function debug($d){
        echo '<pre>';
        print_r($d);
        die;
    }
    public static function GetPriceArchiveSku($skuId, $from, $to){
        $sku = HelpUtil::exchange_values('id','sku',$skuId,'products');
        $Sql = "SELECT c.marketplace,c.prefix,cpa.date_archive,cpa.price FROM channels c 
                LEFT JOIN channels_products_archive cpa ON
                c.id = cpa.channel_id
                WHERE cpa.channel_sku = '".$sku."' AND c.is_active=1 AND cpa.date_archive BETWEEN '".$from." 00:00:00' AND '".$to." 23:59:59';";
        $Results = ChannelsProductsArchive::findBySql($Sql)->asArray()->all();
        return $Results;
    }
    public static function SetDataDateWiseIndex($data){
        $redefine_results=[];
        foreach ( $data as $key=>$value ){
            $prefix=str_replace('-','_',$value['prefix']);
            $date = date('Y-m-d', strtotime($value['date_archive']));
            $redefine_results['dataset'][$date][$prefix]=$value['price'];
        }
        return $redefine_results;
    }
    public static function GetShopsPrefixesMarketplaceWise(){
        $channelsPrefixesSql = "SELECT c.marketplace,c.prefix FROM channels c
                                WHERE c.is_active = 1;";
        $channelsPrefixesSqlResults = Channels::findBySql($channelsPrefixesSql)->asArray()->all();
        $prefixes=[];
        foreach ( $channelsPrefixesSqlResults as $key=>$value ){
            $prefix = str_replace('-','_',$value['prefix']);
            $prefixes[$value['marketplace']][]=$prefix;
        }
        return $prefixes;
    }
    public static function SetCrawlSkuGraphLabels($dat){
        echo 'hello';
        self::debug($dat);
    }
    public static function SetCrawlAndArchiveDataMarketplaceAndShopWise($prefixx,$redefine_results,$daily_crawl_results){

        foreach ( $prefixx as $marketplace=>$prefixes ){

            $daily_crawl_results[$marketplace]['graph_points']=$prefixes;
            $daily_crawl_results[$marketplace]['graph_points'][]='crawl_lowest_price';
            foreach( $prefixes as $key=>$prefix ){

                if (isset($daily_crawl_results[$marketplace]['dataset'])){
                    foreach ( $daily_crawl_results[$marketplace]['dataset'] as $key1=>$detail )
                    {
                        if ( isset($redefine_results['dataset'][$detail['period']][$prefix]) ){
                            $daily_crawl_results[$marketplace]['dataset'][$detail['period']][$prefix]=$redefine_results['dataset'][$detail['period']][$prefix];
                        }else{
                            $daily_crawl_results[$marketplace]['dataset'][$detail['period']][$prefix]=0;
                        }
                    }

                }

            }
        }


        foreach ( $daily_crawl_results as $key=>$value ){
            if ($value['graph_points']){
               $labels = [];
               $colors = [];
               foreach ( $value['graph_points'] as $key1=>$value1 ){
                   $colors[] = '#'.HelpUtil::get_random_color();
                   if ( $value1=='crawl_lowest_price' ){
                       $labels[] = 'Crawl Lowest Price';

                   }else{
                       $labels[] = HelpUtil::exchange_values('prefix','name',str_replace('_','-',$value1),'channels');
                   }
               }
               $daily_crawl_results[$key]['graph_labels']=$labels;
                $daily_crawl_results[$key]['colors']=$colors;
            }
        }
        return $daily_crawl_results;
    }
}