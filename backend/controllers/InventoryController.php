<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 8/26/2019
 * Time: 4:27 PM
 */

namespace backend\controllers;

use backend\util\HelpUtil;
use backend\util\InventoryUtil;
use backend\util\ProductsUtil;
use backend\util\WarehouseUtil;
use common\models\Category;
use common\models\ChannelsProducts;
use common\models\Products;
use common\models\Warehouses;
use common\models\WarehouseStockList;

class InventoryController extends GenericGridController {

    public function actionWarehousesInventoryStocks(){
       //$this->debug($_GET);
        $Warehouses_Stocks = InventoryUtil::GetStocks(); // Get stocks of all warehouses
       // self::debug($Warehouses_Stocks);
        $WarehousesSkuList = InventoryUtil::WarehouseSkuList(); // Get all skus by channel
      //  self::debug($WarehousesSkuList);
        $Warehouses = InventoryUtil::getWarehouses(); // Simple get channels
        $Categories = Category::find()->where(['is_active'=>1])->all();
        $brands=Products::find()->select('brand')->distinct()->asArray()->all();
        //self::debug($brands);
        $Badges = WarehouseUtil::StockListBadges();
        $StockLevel =WarehouseUtil::StockListLevels();
      // $this->debug($Warehouses_Stocks );
        $Selling_status = \Yii::$app->params['selling_status'];
        return $this->render('warehouses-inventory-stocks',
                        [
                            'warehouses_stocks'=>$Warehouses_Stocks['result'],
                            'sku_list'=>$WarehousesSkuList,
                            'total_records'=>count($Warehouses_Stocks['total_records']),
                            'warehouses'=>$Warehouses,
                            'categories'=>$Categories,
                            'brands'=>$brands,
                            'selling_status'=>$Selling_status,
                            'StockBadges' => $Badges,
                            'StockLevel' => $StockLevel
                        ]
        );

    }
    public function actionDownloadStockReport(){
        $warehouses = $_GET['warehouses'];
        $_GET=json_decode($_GET['get_elements'],1);
        $Warehouses_Stocks = InventoryUtil::GetStocks($warehouses); // Get stocks of all warehouses

        //self::debug($Warehouses_Stocks);
        $list=[];
        $channels_list="";
        $header=true;

        // create header of csv file
        if( $Warehouses_Stocks ) {
            $headers=$Warehouses_Stocks['total_records'][0];
            foreach ( $headers as $headerName=>$vl ){
                $list[0][] = $headerName;
                if($headerName=='product_name')
                {
                    $list[0][]='system_sku';
                    $list[0][]='size';
                }
            }
        }

        foreach($Warehouses_Stocks['total_records'] as $k=>$rec)
        {
           // self::debug($rec);
            foreach ( $rec as $index_name=>$vals ){
                $list[$k+1][] = $vals;
                if($index_name=='product_name')
                {
                    $real_sku=""; //system sku specially for spl and pedro
                    if($rec['brand']=="adidas")
                        $real_sku=ProductsUtil::get_sku_from_pattern_adidas($rec['sku'],'-','_'); // eg MEN-ESSENCE M-FU8397_BLACK OR GREY_9.5 => FU8397
                    elseif(in_array($rec['brand'],['nike','pedro','under armour']))
                        $real_sku=ProductsUtil::get_sku_from_pattern_nike($rec['sku']);

                    $size=ProductsUtil::get_size_from_sku($rec['sku']);// for spl
                    $list[$k+1][] = $real_sku;
                    $list[$k+1][] = $size;
                }
            }
        }

        $file_name='stock_list_report'.time().'.csv';

        if(!is_dir('csv')) //create the folder if it's not already exists
            mkdir('csv',0755,TRUE);

        $fp = fopen('csv/'.$file_name, 'w');

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
    public function actionStockList(){

        $skuList = HelpUtil::GetSkuFilterDropdown();
        $warehouseList = HelpUtil::GetWarehouseFilterDropdown();

        $warehouses = Warehouses::find()->all();
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/inventory/generic-info',
                    'sortUrl' => '/inventory/generic-info-sort',
                    'filterUrl' => '/inventory/generic-info-filter',
                    'jsUrl'=>'/inventory/generic'
                ],
                'thead'=>
                    [
                        'Warehouse' => [
                            'data-field' => 'w.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'options' => $warehouseList,
                            'input-type' => 'select',
                            'input-type-class' => 'ci-warehouse-search'
                        ],
                        'SKU' => [
                            'data-field' => 'wsl.sku',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'wsl.sku',
                            'data-filter-type' => 'like',
                            'options' => $skuList,
                            'label' => 'show',
                            'input-type' => 'select',
                            'input-type-class' => 'ci-sku-search'
                        ],
                        'Available' => [
                            'data-field' => 'wsl.available',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'wsl.available',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                    ]
            ];
        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq,'config'=>$config]);
        $roleId = \Yii::$app->user->identity->role_id;

        return $this->render('stock-list',['gridview'=>$html,'roleId' => $roleId,'warehouses'=>$warehouses]);
    }
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT w.NAME AS warehouse_name,wsl.sku AS sku,wsl.available AS available
FROM warehouse_stock_list wsl
INNER JOIN warehouses w ON w.id=wsl.warehouse_id
WHERE w.is_active = 1
",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY wsl.available DESC',
            'SortOrderByColumnAlias' => 'wsl',
        ];
        return $config;
    }

    public function actionCsvStockList(){

        $List = ChannelsProducts::find()->asArray()->all();
        foreach ( $List as $value ){

            $addCsvStock = new WarehouseStockList();
            $addCsvStock->warehouse_id = 1;
            $addCsvStock->product_id = $value['product_id'];
            $addCsvStock->available = $value['stock_qty'];
            $addCsvStock->added_at = date('Y-m-d H:i:s');
            $addCsvStock->save();

        }

    }
    public function actionWarehouseUnlistedSkus(){
        $userid=\Yii::$app->user->identity;
        $id=$userid['id'];
        if($userid['role_id'] == 8){
            $Warehouses = Warehouses::find()->where(['is_active' => 1,'user_id' =>$id])->asArray()->all();
        }else {
            $Warehouses = Warehouses::find()->where(['is_active' => 1])->asArray()->all();
        }
        $unListedSkus = [];
        $SkuList = [];

        if (isset($_GET['warehouse']) && $_GET['warehouse']!=''){
            $SkuList = WarehouseUtil::GetWarehouseSkus();
            $WarehouseChannels = WarehouseUtil::GetWarehouseChannels($_GET['warehouse']);
            $unListedSkus = WarehouseUtil::GetUnlistedSkus();
        }
        else
            $WarehouseChannels = [];

        return $this->render('warehouse-unlisted-skus',['warehouses'=>$Warehouses,'unListedSkus'=>$unListedSkus,'warehouseSkus'=>$SkuList,
            'warehouseChannels'=>$WarehouseChannels]);

    }
}