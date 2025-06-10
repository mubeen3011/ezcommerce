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

class InventoryUtil extends MainController{


    public static function GetStocks($warehouses=[]){
        $w = HelpUtil::getWarehouseDetail($warehouses);
        $role = WarehouseUtil::GetUserRole();
        if ($role=='distributor'){
            $warehouse = WarehouseUtil::GetUserWarehouse();
            $Warehouses = Warehouses::find()->where(['is_active' => 1,'id'=>$warehouse->id])->asArray()->all();
        }else{
            if ($warehouses){
                if (in_array('all',$warehouses))
                    $Warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
                else
                    $Warehouses = Warehouses::find()->where(['is_active' => 1])->andWhere(['in', 'id', $warehouses])->asArray()->all();
            }
            else
                $Warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        }


        $Filters = self::WarehouseInventoryFilters();

        $Cases = '';
        /* Get the total cost of stocks accross all warehouses */
        $Cases .= "FORMAT( ( ";
        foreach ( $Warehouses as $Detail ){
            // make columns of warehouses stocks dynamically
            $Cases .= "sum(case when (wsl.warehouse_id = '".$Detail['id']."' AND available>0) then available else 0 END) +";

        }
        $Cases=rtrim($Cases, '+');
        $Cases .= " ) * p.cost,2)
                    AS total_cost,";

        /* Get the total number of stocks accross all warehouses */
        $Cases .= " ( ";
        foreach ( $Warehouses as $Detail ){
            // make columns of warehouses stocks dynamically
            $Cases .= "sum(case when (wsl.warehouse_id = '".$Detail['id']."' AND available>0) then available else 0 END) +";

        }
        $Cases=rtrim($Cases, '+');
        $Cases .= " ) AS total_stocks,";



        foreach ( $Warehouses as $Detail ){
            // make columns of warehouses stocks dynamically
            $Cases .= " SUM(case when wsl.warehouse_id = '".$Detail['id']."' then available else 0 END) ".str_replace(['-',' ','.'],'',$Detail['name']).",";
            $Cases .= " SUM(CASE WHEN (wsl.warehouse_id = '".$Detail['id']."' AND available > 0) THEN days_left_in_oos ELSE 0 END) ".str_replace(['-',' ','.'],'',$Detail['name'])."_Est_OOS,";
            // Get the number of days of stock we have in our warehouse
        }

        $Cases=rtrim($Cases, ',');



        $Query = "SELECT  wsl.sku,p.name as product_name,p.brand,p.selling_status,p.cost,
                  " . $Cases . "
                from warehouse_stock_list wsl
                LEFT JOIN warehouses w ON
                w.id = wsl.warehouse_id 
                LEFT JOIN products p on
                p.sku = wsl.sku
               # LEFT JOIN category c on
               # c.id = p.sub_category
                " . $Filters['WHERE'] . " AND w.is_active =  1
                AND w.id IN ($w)
                 group BY sku";
        $Query .= $Filters['HAVING'];
        $Query .= ' ORDER BY total_stocks DESC';



        if ($Warehouses){
            if ($_GET['page']!='All'){

                $record_per_page = (isset($_GET['record_per_page']))?$_GET['record_per_page']:10;
                $offset=($_GET['page']==1) ? 0 : ($_GET['page']-1)*10;
                if(isset($_GET['form-filter-used']))
                    $offset=0;
                $Paging = $Query . ' LIMIT ' . $record_per_page . ' OFFSET '.$offset;

            }else{
                $Paging = $Query;
            }

            $Result = Warehouses::findBySql($Paging )->asArray()->all();
            $Result_Count = WarehouseStockList::findBySql($Query)->asArray()->all();

            if (!isset($_GET['Search']['sku']))
                $Query.=self::WarehousesInventoryOffset();
        }else{
            $Result = [];
            $Result_Count = 0;
        }

        return ['total_records'=>$Result_Count,'result'=>$Result];

    }

    public static function WarehouseInventoryFilters(){

        // where clauses
        $where = " WHERE 1=1 ";

        if (isset($_GET['Search']['sku']) && $_GET['Search']['sku']!='' && $_GET['Search']['sku']!='Search SKU') {
            $where .= "  AND wsl.sku IN ('" . $_GET['Search']['sku'] . "')";
        }
        if (isset($_GET['Search']['category']) && $_GET['Search']['category']!='' && $_GET['Search']['category']!='Search Category') {
            $where .= "  AND c.id IN ('" . $_GET['Search']['category'] . "')";
        }
        if (isset($_GET['Search']['brand']) && $_GET['Search']['brand']!='' && $_GET['Search']['brand']!='Search brand') {
            $where .= "  AND p.brand = '" . $_GET['Search']['brand'] . "'";
        }
        if (isset($_GET['Search']['selling_status']) && $_GET['Search']['selling_status']!='' && $_GET['Search']['selling_status']!='Search Status') {
            $where .= "  AND p.selling_status = '" . $_GET['Search']['selling_status'] . "'";
        }
        if (isset($_GET['Search']['product_name']) && $_GET['Search']['product_name']!='' && $_GET['Search']['product_name']!='Search Product_name') {
            $where .= "  AND p.name LIKE '%" . $_GET['Search']['product_name'] . "%'";
        }
        // having clauses
        $havingCon = [];
        if ( isset($_GET['Search']['having']) ){
            foreach ( $_GET['Search']['having'] as $cName=>$condition ){
                $condition = trim($condition);
                if ( $condition!='' ){

                    $firstChar = substr($condition,0,1);
                    $secondChar = substr($condition,1,2);

                    if ($firstChar == '>' || $firstChar == '<') {
                        if($secondChar == '='){
                            $condition = $condition;
                        }else if($secondChar != '='){
                            $condition = $condition;
                        }
                    }else if($firstChar == '='){
                        $condition = substr($condition, 1);
                        $condition = '=' .$condition;
                    }else{
                        $condition = '=' . $condition;
                    }


                    if($cName == 'total_cost'){
                        $havingCon[] = 'p.cost * total_stocks'.$condition;
                    }else {
                        $havingCon[] = $cName . $condition;
                    }
                }
            }
        }


        if ( $havingCon ){
            $having = ' having '.implode(' AND ',$havingCon);
        }else{
            $having = '';
        }

        $cond['WHERE'] = $where;
        $cond['HAVING'] = $having;
        return $cond;

    }

    public static function get_sku_inventory($sku_list)
    {
        if(is_array($sku_list))
            $skus = '"'.implode('","',$sku_list ).'"';
        else
            $skus="'".$sku_list."'";

        $sql="SELECT SUM(`wsl`.`available`) as available,`wsl`.`id`,`wsl`.`stock_in_transit`,`wsl`.`updated_at`,`w`.`name`
                FROM warehouse_stock_list wsl 
                INNER JOIN 
                  warehouses w
                ON
                  w.id=wsl.warehouse_id
                WHERE sku IN(".$skus.")
                GROUP BY
                w.id";
        return WarehouseStockList::findBySql($sql)->asArray()->all();

    }
    public static function GetPrices(){

        $Channels = Channels::find()->where(['is_active' => 1])->asArray()->all();
        $Filters = self::WarehousessInventoryFilters();

        $Cases = '';

        foreach ( $Channels as $Detail ){

            $Cases .= "sum(case when channel_id = '".$Detail['id']."' then CAST(price as DECIMAL(10,2)) else 0 END) ".str_replace(['-',' ','.'],'',$Detail['name']).",";

        }

        $Cases=rtrim($Cases, ',');

        $Query = "SELECT  cp.channel_sku,
                  ".$Cases."
                from channels_products cp
                INNER JOIN channels c ON
                c.id = cp.channel_id 
                ".$Filters."
                 group BY channel_sku";

        $Result_Count = WarehouseStockList::findBySql($Query)->asArray()->all();

        if (!isset($_GET['Search']['sku']))
            $Query.=self::WarehousesInventoryOffset();
        $Result = WarehouseStockList::findBySql($Query)->asArray()->all();



        return ['total_records'=>$Result_Count,'result'=>$Result];

    }
    private static function WarehousessInventoryFilters(){

        $cond = " WHERE 1=1 and w.is_active=1";

        if (isset($_GET['Search']['sku']) && $_GET['Search']['sku']!='Search SKU') {

            $cond .= "  AND wsl.sku IN ('" . $_GET['Search']['sku'] . "')";

        }

        return $cond;

    }
    private static function WarehousesInventoryOffset(){
        $cond = '';
        if ($_GET['page'] == 'All') {
            $cond .= '';
        } else {
            $offset = 10 * ($_GET['page'] - 1);
            $cond .= " LIMIT " . $offset . ",10 ;";
        }
        return $cond;
    }
    public static function WarehouseSkuList(){
        $w = HelpUtil::getWarehouseDetail();
        $GetSkus = ChannelsProducts::findBySql("SELECT sku from warehouse_stock_list wsl INNER JOIN warehouses w ON
                                                     w.id = wsl.warehouse_id WHERE w.is_active = 1 AND w.id IN ($w) GROUP BY wsl.sku")->asArray()->all();

        return $GetSkus;

    }
    public static function GetWarehouseStocks( $warehouse_id ){

        $Sql = " SELECT p.sku as sku, wsl.available as available FROM warehouse_stock_list wsl 
                 INNER JOIN warehouses w on w.id=wsl.warehouse_id
                 INNER JOIN products p on p.id = wsl.product_id";

        //$Filters = self::ChannelsInventoryFilters();
        $warehouse_stocks = WarehouseStockList::findBySql($Sql)->asArray()->all();

        //$Result_Count = ChannelsProducts::findBySql($Query)->asArray()->all();

        return $warehouse_stocks;

    }


    //update stock in wharehouse
    public static function updateStock($params)    // format ['data' => array(0=>sku,'1'=>stock,'2'=>price), 'record (optional)' => 'single', 'api_to_call(optional)' => 'api name to call'];
    {
        $warehouse_stock= WarehouseStockList::findone(['sku' =>$params['sku']]);

        if($warehouse_stock)
        {
            // below query will check if stock sent and already available are mismatched will update else not
            $sql="update `warehouse_stock_list` 
                        INNER JOIN 
                            `warehouse_channels`
                         ON 
                            `warehouse_channels`.`warehouse_id`=`warehouse_stock_list`.`warehouse_id` 
                        set 
                            `warehouse_stock_list`.`available`='".$params['stock']."' ,
                            `warehouse_stock_list`.`updated_at`='".date('Y-m-d H:i:s')."' 
                         where 
                             `warehouse_stock_list`.`sku`='".$params['sku']."'
                                and `warehouse_channels`.`channel_id`='".$params['channel_id']."'
                                and `warehouse_stock_list`.`available` <> '".$params['stock']."'";
            if(Yii::$app->db->createCommand($sql)->execute())
            {
                return true;
            }


        }

        return false;

    }

    public function updateStock_newss($params)
    {
        if($params)
        {
            $sql="UPDATE `warehouse_stock_list`
                        SET
                            `available`=available + ".$params['stock'].",
                            `updated_at`='".date('Y-m-d H:i:s')."'
                        WHERE
                            `sku`='".$params['sku']."' AND `warehouse_id`='".$params['warehouse_id']."'";

            return  Yii::$app->db->createCommand($sql)->execute();
        }
    }

    public function updateStock_new($params)
    {
        $stock=WarehouseStockList::find()->where(['sku'=>$params['sku'],'warehouse_id'=>$params['warehouse_id']])->one();
        if($stock)
        {
            $log=['stock_before'=>$stock->available,'stock_after'=>($stock->available + $params['stock']),'stock_in_pending'=>$stock->stock_in_pending];
            $stock->available=($stock->available + $params['stock']);
            $stock->updated_at=date('Y-m-d H:i:s');
            if($stock->update())
                return $log;
            else
                return false;
        }
        elseif($params['add_if_not_present']=="yes")
        {
            $stock= new WarehouseStockList();
            $stock->sku=$params['sku'];
            $stock->warehouse_id=$params['warehouse_id'];
            $stock->available=$params['stock'];
            $stock->added_at=date('Y-m-d H:i:s');
            $stock->updated_at=date('Y-m-d H:i:s');
            if($stock->save())
                return ['stock_before'=>0,'stock_after'=>$params['stock'],'stock_in_pending'=>0];
            else
                return false;
        }

        return false;
    }

    public static function changeStock($params)
    {
        $sql="UPDATE `warehouse_stock_list` 
                INNER JOIN
                    `products` 
                ON
                    `products`.`id`=`warehouse_stock_list`.`product_id`
                SET
                    `warehouse_stock_list`.`available` = available - ".$params['qty']."
                WHERE
                    `products`.`sku`='".$params['sku']."'";

        return  Yii::$app->db->createCommand($sql)->execute();

    }

    public static function get_stock_by_product_id($sku, $channel_id)
    {

        $stocks_sql = "SELECT wsl.warehouse_id,w.name AS warehouse_name,wsl.available FROM warehouse_channels wc 
                        INNER JOIN channels c ON 
                        c.id = wc.channel_id
                        INNER JOIN warehouse_stock_list wsl ON 
                        wsl.warehouse_id = wc.warehouse_id
                        INNER JOIN warehouses w ON
                        w.id = wsl.warehouse_id
                        WHERE wsl.sku='".$sku."' AND wc.channel_id = $channel_id AND w.is_active=1 AND wc.is_active=1 AND c.is_active=1
                        GROUP BY wsl.warehouse_id";
        // echo $stocks_sql;die;
        $results = WarehouseStockList::findBySql($stocks_sql)->asArray()->all();
        $stocks = [];
        if ( $results ){
            foreach ( $results as $detail ){
                $stocks[$detail['warehouse_name']]=$detail['available'];
            }
            $stocks['total_stocks'] = array_sum($stocks);
        }else{
            //$stocks['total_stocks']['message'] = 'There is no warehouse connected with the channel <br /> Or sku not found in any warehouse.';
            $stocks['total_stocks']=0;
        }
        return $stocks;
    }
    public static function getWarehouses(){
        $role = WarehouseUtil::GetUserRole();
        if ($role=='distributor'){
            $warehouse = WarehouseUtil::GetUserWarehouse();
            $warehouses = Warehouses::find()->andWhere(['is_active' => 1,'id'=>$warehouse->id])->orderBy('id')->asArray()->all();
        }else{
            $warehouses = Warehouses::find()->andWhere(['is_active' => 1])->orderBy('id')->asArray()->all();
        }
        $list = [];
        foreach ($warehouses as $ch) {
            $list[] = ['id' => $ch['id'], 'name' => $ch['name']];
        }

        return $list;
    }

    public static function notManagedByEzcomFilter()
    {
        $cond = " AND 1=1 ";
        if (isset($_GET['sku']) AND !empty($_GET['sku'])){
            $searched_skus = '"'.implode('","',explode(',',$_GET['sku'])).'"'; // if multiple
            $cond .= "  AND p.sku IN ($searched_skus)";
        }

        if (isset($_GET['name']) AND !empty($_GET['name'])) {
            $name=$_GET['name'];
            $cond .= "  AND `p`.`name`='$name' ";
        }
        if (isset($_GET['cost_price']) AND !empty($_GET['cost_price'])) {
            $cost=$_GET['cost_price'];
            $cond .= "  AND `p`.`cost`='$cost' ";
        }

        if (isset($_GET['rccp']) AND !empty($_GET['rccp'])) {
            $value=$_GET['rccp'];
            $cond .= "  AND `p`.`rccp`='$value' ";
        }
        if (isset($_GET['is_active']) AND in_array($_GET['is_active'],["1","0"])) {
            //  die('aja');
            $value=$_GET['is_active'];
            $cond .= "  AND `p`.`is_active`='$value' ";
        }
        if (isset($_GET['channel']) AND !empty($_GET['channel'])) {
            $value=$_GET['channel'];
            $cond .= "  AND `cp`.`channel_id`='$value' ";
        }
        return $cond;
    }

    /***************stock/inventory not sync to channels or manage by ezcom**************/
    public static function notManagedByEzcom()
    {
        $cond=self::notManagedByEzcomFilter();
        $query="SELECT `p`.`id`,`p`.`name`,`p`.sku,`p`.`rccp`,`p`.`cost` ,p.parent_sku_id,p.is_active,`p`.`brand`,
              GROUP_CONCAT(IFNULL(cp.product_name,'') SEPARATOR '@!') as product_channels_name,
              GROUP_CONCAT(IFNULL(cp.sku,'') SEPARATOR '@!') as channel_sku_id,
              GROUP_CONCAT(IFNULL(cp.channel_id,'') SEPARATOR '@!') as product_channels,
              GROUP_CONCAT(IFNULL(cp.is_live,'') SEPARATOR '@!') as product_channels_is_live,
              GROUP_CONCAT(IFNULL(cp.deleted,'') SEPARATOR '@!') as product_channels_deleted
              FROM
                products p
              INNER JOIN channels_products cp
                ON p.id=cp.product_id  
              WHERE 
                p.sku NOT IN('SELECT sku From warehouse_stock_list') $cond
             GROUP BY p.sku";
        $total_records = Yii::$app->db->createCommand($query)->query()->count();
        //pagination
        $per_page =isset($_GET['record_per_page']) ? $_GET['record_per_page']:10;
        $page=isset($_GET['page']) ? $_GET['page']:1;
        $offset = 10 * ($page - 1);
        $query .= " LIMIT " . $offset . ", $per_page";

        $produts_list=$ChannelList = Yii::$app->db->createCommand($query)->queryAll();
        $list=[];
        foreach($produts_list as $product){
            $list[]=[
                'id'=>$product['id'],
                'parent_sku_id'=>$product['parent_sku_id'],
                'sku'=>$product['sku'],
                'name'=>$product['name'],
                'brand'=>$product['brand'],
                'cost'=>$product['cost'],
                'rccp'=>$product['rccp'],
                'is_active'=>$product['is_active'],
                'channels'=>self::arrange_inventory_list_channels($product)
            ];
        }
        return ['data'=>$list,'total_records'=>$total_records];
    }
    private static function arrange_inventory_list_channels($list)
    {
        $items=[];
        //print_r($list['product_channels']); die();
        if($list && $list['product_channels'])
        {
            $product_channels = explode('@!', $list['product_channels']);
            $product_channel_sku_id = explode('@!', $list['channel_sku_id']);
            $product_channels_is_live = explode('@!', $list['product_channels_is_live']);
            $product_channels_name = explode('@!', $list['product_channels_name']);
            $product_channels_deleted = explode('@!', $list['product_channels_deleted']);

            for($i=0;$i<count($product_channels);$i++)
            {
                $items[$product_channels[$i]]=[
                    'channel_name'=>$product_channels[$i],
                    'channel_sku_id'=>$product_channel_sku_id[$i],
                    'is_live'=>$product_channels_is_live[$i],
                    'deleted'=>$product_channels_deleted[$i],
                    'name'=>$product_channels_name[$i],
                ];
            }
        }
        return $items;
    }

    /*
   * csv export
   */
    public static function export_csv_not_managed_by_ezcom($record,$channels)
    {
        // echo "<pre>";
        //print_r($categories); die();
        ///categories
        /*$cat_index=array_search('18', array_column($categories,'key'));
        $cat_name=$categories[$cat_index]['value'];
        print_r($cat_name); die();*/
        $list=[];
        $channels_list="";
        $header=true;
        foreach($record['data'] as $k=>$rec)
        {

            foreach($rec['channels'] as $channel)  // get channels list in which these items present
            {
                $channel_index=array_search($channel['channel_name'], array_column($channels,'id'));
                if($channels_list)
                    $channels_list .= " , " .$channels[$channel_index]['name'];
                else
                    $channels_list .=$channels[$channel_index]['name'];

                //////if single chanel selected then also add column of deleted
                if(isset($_GET['channel']) && $_GET['channel']){
                    $deleted_column="Deleted";
                    $deleted_value=$channel['deleted'];
                    $is_live_column="Is Live";
                    $is_live_value=$channel['is_live'];
                    $item_id_column="Item ID";
                    $item_id_value=$channel['channel_sku_id'];
                } else{
                    $deleted_column=$is_live_column='';
                    $deleted_value=$is_live_value='';
                    $item_id_column= $item_id_value='';
                }
            }



            if($header){
                $list[]=['Sku' ,'Name',$item_id_column,'Cost','Rccp','Channels',$is_live_column,$deleted_column];
            }


            $list[]=["'".$rec['sku']."'",$rec['name'],$item_id_value,$rec['cost'],$rec['rccp'],$channels_list,$is_live_value,$deleted_value];
            $header=false;

            $channels_list=""; // clear for next loop

        }
        $file_name='products_report'.time().'.csv';

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