<?php

namespace backend\controllers;


use app\models\PoErcList;
use app\models\PoImportTemp;
use app\models\ProductStocksPo;
use backend\util\DealsUtil;
use backend\util\InventoryUtil;
use backend\util\ProductsUtil;
use backend\util\PurchaseOrderUtil;
use backend\util\SkuVault;
use backend\util\ThresholdUtil;
use backend\util\WarehouseUtil;
use common\models\Category;
use common\models\Channels;
use common\models\CostPrice;
use common\models\PoThresholds;
use common\models\ProductCategories;
use common\models\ProductDetailsArchive;
use common\models\ProductRelationsSkus;
use common\models\Products;
use common\models\ProductsRelations;
use common\models\StockPriceResponseApi;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use common\models\WarehouseStockLog;
use Faker\Provider\DateTime;
use Mpdf\Tag\P;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use backend\util\HelpUtil;
use common\models\PoDetails;
use common\models\ProductDetails;
use common\models\ProductStocks;
use common\models\Settings;
use common\models\StocksPo;
use Predis\Command\HashLength;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


class StocksController extends GenericGridController
{
    public $data =[];
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if ($action->id == 'stock-info' || $action->id == 'stock-info-sort' || $action->id == 'stock-info-filter' ||
            $action->id == 'products-info' || $action->id == 'products-info-sort' || $action->id == 'products-info-filter' ||
            $action->id == 'manage-info' || $action->id = 'manage-info-sort' || $action->id = 'manage-info-filter'

        ) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($view = null)
    {
        $pds = HelpUtil::callAllStocks();
        if ($view == 'fbl')
            return $this->render('fbl', ['pdlist' => $pds]);
        else
            return $this->render('index', ['pdlist' => $pds]);
    }

    public function actionAll()
    {
        $session = Yii::$app->session;
        $officeSku = [];
        if ($session->has('sku-imported')) {
            $officeSku = $session->get('sku-imported');
        }
        $session->remove('sku-imported');
        $pdq = \Yii::$app->request->get('pdqs');
        return $this->render('all', ['pdq' => $pdq, 'officeSku' => $officeSku]);
    }


    public function actionManage()
    {
        $data=ThresholdUtil::getRecord();
      //  self::debug($data);
        $statuses=[
                    'active_statuses'=>ThresholdUtil::getSKUActivestatuses(),
                    'selling_statuses'=>ThresholdUtil::getSellingStatses(),
        ];
        return $this->render('manage', ['sku_list'=>$data,'statuses'=>$statuses]);
    }

    public function exchange_values($from, $to, $value, $table)
    {
        $connection = \Yii::$app->db;
        $get_all_data_about_detail = $connection->createCommand("select " . $to . " from " . $table . " where " . $from . " ='" . $value . "'");
        $result_data = $get_all_data_about_detail->queryAll();
        //return $result_data[0][$to];
        if (isset($result_data[0][$to])) {
            return $result_data[0][$to];
        } else {
            return 'false';
        }
    }
    public function actionAddSkuThreshold(){

        $getPricelist = Products::find()->where(['id'=>$_GET['skuid']])->asArray()->all();
        if ( !isset($getPricelist[0]['cost']) )
        {
            echo 3;
            die;
        }
        $Threshold = 0;
        if ($getPricelist[0]['cost'] < 150)
            $Threshold=5;
        elseif ($getPricelist[0]['cost'] >= 151 && $getPricelist[0]['cost'] <= 300 )
            $Threshold=4;
        elseif ($getPricelist[0]['cost'] >= 301 && $getPricelist[0]['cost'] <= 500 )
            $Threshold=3;
        elseif ($getPricelist[0]['cost'] >= 501 && $getPricelist[0]['cost'] <= 850 )
            $Threshold=2;
        elseif ($getPricelist[0]['cost'] >= 851 )
            $Threshold=1;

        $AddPStock=new ProductStocks();
        $AddPStock->stock_id=$this->exchange_values('sku_id','id',$_GET['skuid'],'product_details');
        $AddPStock->is_active=1;
        $AddPStock->isis_threshold=$Threshold;
        $AddPStock->isis_threshold_critical = $Threshold;
        $AddPStock->fbl_blip_threshold = $Threshold;
        $AddPStock->fbl_blip_threshold_critical=$Threshold;
        $AddPStock->fbl_909_threshold = $Threshold;
        $AddPStock->fbl_909_threshold_critical = $Threshold;
        $AddPStock->datetime_updated=date('Y-m-d h:i:s');
        $AddPStock->stock_status='slow';
        $AddPStock->blip_stock_status='slow';
        $AddPStock->f909_stock_status='slow';
        $AddPStock->stocks_intransit=0;
        $AddPStock->fbl_stocks_intransit=0;
        $AddPStock->fbl909_stocks_intransit=0;
        $AddPStock->save();
        if ( empty($AddPStock->errors) )
        {
            echo 1;
        }else
            echo 0;
    }
    public function actionManageInfo()
    {
        $pds = HelpUtil::callManageStocks(null,null,"manage");

        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('_manageInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_manageInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }

    public function actionManageInfoSort()
    {
        $pds = HelpUtil::callManageStocks(null,null,"manage");
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('_manageInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_manageInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }

    public function actionManageInfoFilter()
    {
        $pds = HelpUtil::callManageStocks(null,null,"manage");
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('_manageInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_manageInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }


    //ajax call for stocks info and filters
    public function actionStockInfo()
    {
        $pds = HelpUtil::callAllStocks();
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('_stocksInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_stocksInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }

    public function actionStockInfoSort()
    {
        $pds = HelpUtil::callAllStocks();
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data=[];
        $this->data['tbody']= $this->renderAjax('_stocksInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_stocksInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }

    public function actionStockInfoFilter()
    {
        $pds = HelpUtil::callAllStocks();
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data=[];
        $this->data['tbody']= $this->renderAjax('_stocksInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']= $this->renderAjax('_stocksInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no'),'records_per_pages'=>\Yii::$app->request->post('records_per_page'),
            'total_records'=>$pds['total_records']]);
        echo $this->data['tbody'].'|'.$this->data['tfoot'];
    }


    public function actionProducts()
    {

        return $this->render('products-all');
    }

    public function actionProductsInfo()
    {
        $products = HelpUtil::getChannelsProducts();
        echo $this->renderAjax('_productsInfo', ['stocks' => $products]);
    }

    public function actionProductsInfoSort()
    {
        $products = HelpUtil::getChannelsProducts();
        echo $this->renderAjax('_productsInfo', ['stocks' => $products]);
    }

    public function actionProductsInfoFilter()
    {
        $products = HelpUtil::getChannelsProducts();
        echo $this->renderAjax('_productsInfo', ['stocks' => $products]);
    }

    public function actionExportStocks()
    {
        $date = date('YmdHis');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=stocks-sheet-" . $date . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "SKU,Selling Status,Stocks,Good,Damaged,Allocating,Processing,FBL-Blip,FBL-909";
        echo "\n";

        $stocks = HelpUtil::callAllStocks(1);
        foreach ($stocks as $pd) {
            $fbl909 = isset($pd['fbl_99_stock']) ? $pd['fbl_99_stock'] : '0';
            $fblblip = isset($pd['fbl_stock']) ? $pd['fbl_stock'] : '0';
            echo $pd['isis_sku'] . "," . $pd['selling_status'] . "," . $pd['stocks'] . ",";
            echo $pd['goodQty'] . "," . $pd['damagedQty'] . "," . $pd['allocatingQty'] . ",";
            echo $pd['processingQty'] . "," . $fblblip . "," . $fbl909;
            echo "\n";
        }

    }



    public function actionAddRemoveStocks()
    {
        if($_POST) // if form submitted
        {
            if(!isset($_POST['warehouse_id'])  ||$_POST['warehouse_id'] <=0 || !isset($_POST['sku']) || !isset($_POST['qty']) || $_POST['qty'] <=0)
            {
                echo json_encode(['status'=>'failure','msg'=>$_POST['warehouse_id'] <=0 ? 'select warehouse':'Check Missing params']);
            }
            else
            {
                $qty=$_POST['action']=="add" ? $_POST['qty']:(-$_POST['qty']);  // if add qty then plus else minus
                $stock=array('warehouse_id'=>$_POST['warehouse_id'],'stock'=>$qty,'sku'=>$_POST['sku'],'add_if_not_present'=>'yes');
                $response=InventoryUtil::updateStock_new($stock);
                if($response)
                {
                    $log=['warehouse_id'=>$_POST['warehouse_id'],'sku'=>$_POST['sku'],
                        'note'=>$_POST['reason'] ? $_POST['reason']:NULL,
                        'qty'=>$qty,'type'=>'manual_transaction','stock_before'=>$response['stock_before'],
                        'stock_after'=>$response['stock_after'],'stock_pending_before'=>$response['stock_in_pending'],
                        'stock_pending_after'=>$response['stock_in_pending'],'status'=>$_POST['action']];
                    WarehouseUtil::add_stock_log($log); // stock_depletion_log table
                    Yii::$app->session->setFlash('success', "Successfully updated");
                    echo json_encode(['status'=>'success','msg'=>'Action Performed']);

                }
                else
                {
                    Yii::$app->session->setFlash('failure', "Failed to update");
                    echo json_encode(['status'=>'failure','msg'=>'Failed to update']);
                }

            }

        }
        else
        {
            $warehouses = $this->GetWarehouse();

            return $this->render('add-remove',[
                'title'=>'Add-Remove Stock',
                'warehouses' => $warehouses
                ]
            );
        }


    }
    public function GetWarehouse(){
        $role = WarehouseUtil::GetUserRole();
        if ($role=='distributor'){
            $getWarehouse = WarehouseUtil::GetUserWarehouse();
            $warehouses = Warehouses::find()->where(['is_active'=>'1','id'=>$getWarehouse])->all();
        }else{
            $warehouses = Warehouses::find()->where(['is_active'=>'1'])->all();
        }
        return $warehouses;
    }
    public function actionImportOfficeStocks()
    {
        //var_dump($_POST); die();

        //////general stock updation module ////////////////
        if (isset($_POST['general_stock']) && isset($_POST['warehouse_id']))
        {
            if($_POST['warehouse_id'] <=0 )
            {
                return $this->asJson(['status'=>'failure','msg'=>'Select warehouse','updated_list'=>0]);
            }

            $warehouse_id= $_POST['warehouse_id'];
            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>0]);

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }


            if ($_FILES['csv']["size"] > 0)
            {
                $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
                $sheetData = $spreadsheet->getActiveSheet()->toArray();

                if(empty($sheetData)){
                    return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>0]);
                }

                if($_POST['by']=="barcode")
                {
                    $headers_should_be=['0'=>'barcode','1'=>'stock','2'=>'id'];  // id is given by csv to json converter we need barcode and stock header
                    if($headers_should_be!=array_values($sheetData[0]))
                    {
                        return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','not_updated_list'=>[],'updated_list'=>0]);
                    }

                    $response=ProductsUtil::update_stock_by_barcode($sheetData,$warehouse_id);
                } else
                {
                    $headers_should_be=['0'=>'sku','1'=>'stock','2'=>'id']; // id is given by csv to json converter we need sku and stock header
                    if($headers_should_be!=array_values($sheetData[0]))
                    {
                        return $this->asJson(['status'=>'failure','msg'=>'check csv or xlsx headers','not_updated_list'=>[],'updated_list'=>0]);
                    }
                    $response= ProductsUtil::update_stock_by_sku($sheetData,$warehouse_id);
                }
                return $this->asJson($response);


            }
            else
            {
                return $this->asJson(['status'=>'failure','msg'=>'Failed to update, check file','updated_list'=>0]);
            }


            //$this->redirect(array('/inventory/stock-list'));
        }

        else
        {
            $role = WarehouseUtil::GetUserRole();
            if ($role=='distributor'){
                $getWarehouse = WarehouseUtil::GetUserWarehouse();
                $warehouse = Warehouses::find()->where(['is_active'=>'1','id'=>$getWarehouse->id])->all();
            }else{
                $warehouse = Warehouses::find()->where(['is_active'=>'1'])->all();
            }
            $action_general_stock_import = '/stocks/import-office-stocks';
            $action_general_price_import = '/stocks/update-price';
            $channels=Channels::find()->distinct()->asArray()->all();
           // self::debug($channels);
            return $this->render('import-stocks',[
                    'title'=>'Update Stocks & Price Section',
                    'action_general_stock_import'=>$action_general_stock_import,
                    'action_general_price_import'=>$action_general_price_import,
                    'warehouses' => $warehouse,
                    'channels' => $channels,
                    'role' => $role]
            );
        }

    }

    public function actionUpdatePrice()
    {

        $error_list=array();
        $updated_count=0;

        $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if (isset($_FILES['csv']) && in_array($_FILES['csv']['type'], $file_mimes) && $_FILES['csv']['size'] > 0) {

            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>$updated_count]); //check if csv and xlsx file not exist

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }

            $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            $column=$_POST['update_column'];

            if(empty($sheetData)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);
            }

            $headers_should_be=['0'=>'sku','1'=>'price'];  // if Sheet Headers missmatched
            if($headers_should_be!=array_values($sheetData[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','updated_list'=>$updated_count]);
            }

            $i = 0;
            foreach ($sheetData as $value) {
                if ($i != 0) {
                    $sku = trim($value[0], "'");
                    $price = $value[1];
                    $findSku = Products::find()->where(['sku'=>$sku])->one();

                    if ($findSku){
                        if($findSku->$column==$price) // if same then no need to update
                            continue;

                        $findSku->$column = $price;
                        $findSku->updated_at = time();
                        $findSku->update(false);
                        if (!empty($findSku->errors))
                            $error_list []= $sku;
                        else
                            $updated_count++;
                    }else{
                        $error_list []= $sku;
                    }
                }
                $i++;
            }


            return $this->asJson(['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$updated_count,
                ]
            );

        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$updated_count]);
        }

    }

    public function actionMapBarcodeSku()
    {
        $error_list=array();
        $updated_count=0;

        $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if (isset($_FILES['csv']) && in_array($_FILES['csv']['type'], $file_mimes) && $_FILES['csv']['size'] > 0) {

            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>$updated_count]);

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }

            $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            if(empty($sheetData)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);
            }

            $headers_should_be=['0'=>'sku','1'=>'barcode'];  // if Sheet Headers missmatched
            if($headers_should_be!=array_values($sheetData[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','updated_list'=>$updated_count]);
            }

                $i = 0;
                foreach ($sheetData as $value) {
                    if ($i != 0) {
                        $sku = trim($value[0], "'");   // remove commas on start and end if have
                        $barcode = trim($value[1], "'");   // remove commas on start and end if have
                        $update=Yii::$app->db->createCommand()
                            ->update('products', ['barcode' =>trim($barcode)]  , ['sku'=>$sku])
                            ->execute();
                        if(!$update)
                            $error_list[]=$sku;
                        else
                            $updated_count ++;
                    }
                    $i++;
                }


            return $this->asJson(['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$updated_count,
                ]
            );

        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$updated_count]);
        }
    }

    public function actionMapStyleSku()
    {
        $error_list=array();
        $updated_count=0;

        $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if (isset($_FILES['csv']) && in_array($_FILES['csv']['type'], $file_mimes) && $_FILES['csv']['size'] > 0) {

            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>$updated_count]);

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }

            $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();



            if(empty($sheetData)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);
            }

            $headers_should_be=['0'=>'sku','1'=>'style'];  // if Sheet Headers missmatched
            if($headers_should_be!=array_values($sheetData[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','updated_list'=>$updated_count]);
            }

               $i = 0;
               foreach ($sheetData as $value) {
                    if ($i != 0) {
                        $sku = trim($value[0], "'");   // remove commas on start and end if have
                        $style = trim($value[1], "'");   // remove commas on start and end if have
                        $update=Yii::$app->db->createCommand()
                            ->update('products', ['style' =>trim($style)] , ['sku'=>$sku])
                            ->execute();
                        if(!$update)
                            $error_list[]=$sku;
                        else
                            $updated_count ++;
                    }
                    $i++;
                }


            return $this->asJson(['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$updated_count,
                ]
            );

        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$updated_count]);
        }

    }

    public function actionMapProductCategorySku()
    {
        $error_list=array();
        $inserted_count=0;
        $updated_count=0;

        $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if (isset($_FILES['csv']) && in_array($_FILES['csv']['type'], $file_mimes) && $_FILES['csv']['size'] > 0) {

            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>$updated_count]);

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }

            $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            if(empty($sheetData)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);
            }

            $headers_should_be=['0'=>'sku','1'=>'category_id', '2'=>'category_name']; // if Sheet Headers missmatched
            if($headers_should_be!=array_values($sheetData[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','updated_list'=>$updated_count]);
            }

                $i = 0;
                foreach ($sheetData as $value) {
                    if ($i != 0) {
                        if(isset($value[0]))
                        {
                            $sku= $value[0];
                            $category_id = $value[1];
                            $get_product = Products::find()->where(['sku' => $sku])->asArray()->one();

                            if(!$get_product)
                            {
                                $error_list=$sku;
                                continue;
                            }
                             $product_id = $get_product['id'];

                            $cate_id_arr = explode(',', $category_id);
                            if($cate_id_arr)
                                ProductCategories::deleteAll(['product_id' => $product_id]);

                            foreach ($cate_id_arr as $cate_id) {

                                $product_category_exist = ProductCategories::find()->where(['product_id' => $product_id, 'cat_id' => $cate_id])->asArray()->exists();

                                if(!$product_category_exist) {
                                    $sku = trim($sku, "'");   // remove commas on start and end if have
                                    $insert=new ProductCategories();
                                    $insert->product_id=$product_id;
                                    $insert->cat_id=$cate_id;
                                    if (!$insert->save())
                                        $error_list[] = $sku;
                                    else
                                        $inserted_count++;
                                }else{
                                    $error_list []= $sku;
                                }
                            }
                        }
                    }
                    $i++;
                }
            return $this->asJson(['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$inserted_count,
                ]
            );
        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$inserted_count]);
        }

    }


    public function actionUploadImagesLink()
    {
        $error_list=array();
        $updated_count=0;

        $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if (isset($_FILES['csv']) && in_array($_FILES['csv']['type'], $file_mimes) && $_FILES['csv']['size'] > 0) {

            $ext = pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION);

            if( !(($ext == "xlsx") || ($ext == "csv") ))
                return $this->asJson(['status'=>'failure','msg'=>'only csv and xlsx files allowed','updated_list'=>$updated_count]);

            if ('csv' == $ext) {
                $reader = new Csv();
            } else {
                $reader = new Xlsx();
            }

            $spreadsheet = $reader->load($_FILES['csv']['tmp_name']);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();

            if(empty($sheetData)){
                return $this->asJson(['status'=>'failure','msg'=>'check file format OR content','updated_list'=>$updated_count]);
            }

            $headers_should_be=['0'=>'sku','1'=>'image'];  // if Sheet Headers missmatched
            if($headers_should_be!=array_values($sheetData[0]))
            {
                return $this->asJson(['status'=>'failure','msg'=>'Sheet Headers missmatched!','updated_list'=>$updated_count]);
            }


            $i = 0;
                foreach ($sheetData as $value) {
                    if ($i != 0) {
                        $sku=trim($value[0],"'");   // remove commas on start and end if have
                        $image=trim($value[1],"'");   // remove commas on start and end if have
                        $update=Yii::$app->db->createCommand()
                            ->update('products', ['image' =>trim($image)]  , ['sku'=>$sku])
                            ->execute();
                        if(!$update)
                            $error_list[]=$sku;
                        else
                            $updated_count ++;
                    }
                    $i++;
                }


            return $this->asJson(['status'=>'success',
                    'msg'=>'Uploaded',
                    'not_updated_list'=>$error_list,
                    'updated_list'=>$updated_count,
                ]
            );

        }
        else
        {
            return $this->asJson(['status'=>'failure','msg'=>'Failed to fetch file','updated_list'=>$updated_count]);
        }
    }


    public function actionAddProductLineBundle()
    {

        return $this->renderAjax('_render-partials/_product-line-bundle', ['warehouse' => $_POST['for']]);
    }

    public function actionPo()
    {
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/stocks/generic-info',
                    'sortUrl' => '/stocks/generic-info-sort',
                    'filterUrl' => '/stocks/generic-info-filter',
                    'jsUrl'=>'/stocks/po',
                ],
                'thead'=>
                    [
                        'PO Date' => [
                            'data-field' => 'psp.po_initiate_date',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'psp.po_initiate_date',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'PO No' => [
                            'data-field' => 'psp.po_code',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'psp.po_code',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Warehouse' => [
                            'data-field' => 'w.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.name',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Status' => [
                            'data-field' => 'psp.po_status',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'psp.po_status',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'ER/IO No' => [
                            'data-field' => 'psp.er_no',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'psp.er_no',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Grand Total' => [
                            'data-field' => 'grand_total',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'grand_total',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'id' => [
                            'data-field' => 'purchase_orderid',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'purchase_orderid',
                            'data-filter-type' => 'operator',
                            'visibility' => false,
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                    ]
            ];
        $session = \Yii::$app->session;
        $officeSku = [];
        if ($session->has('sku-imported')) {
            $officeSku = $session->get('sku-imported');
        }
        $session->remove('sku-imported');
        $pdq = \Yii::$app->request->get('pdqs');
        $html = $this->renderAjax('../generic-grid/all', ['pdq' => $pdq, 'officeSku' => $officeSku,'config'=>$config]);
        $total=PurchaseOrderUtil::GetTotalAmountOfShippedPO();
        return $this->render('orders_all',['gridview'=>$html,'total'=>$total]);

    }
    public function actionConfigParams(){

        $role = WarehouseUtil::GetUserRole();
        $where = '';
        if ($role=='distributor'){
            $getWarehouse = WarehouseUtil::GetUserWarehouse();
            $where = ' AND w.id = '.$getWarehouse->id;
        }
        $having_clause = '';
        $config=[
            'query'=>[
                'FirstQuery'=>'SELECT DATE(psp.po_initiate_date) AS po_initiate_date,psp.po_code,w.name,psp.po_status,psp.er_no,
                                IF(psp.po_status=\'Shipped\', SUM(po_d.cost_price * po_d.order_qty), NULL) AS grand_total,
                                psp.id AS purchase_orderid, w.id as po_warehouseId
                                FROM product_stocks_po psp
                                INNER JOIN warehouses w ON
                                w.id = psp.warehouse_id
                                LEFT JOIN po_details po_d ON
                                po_d.po_id = psp.id
                                WHERE 1=1
'.$where,
                'GroupBy' => 'GROUP BY po_d.po_id'
            ],
            'OrderBy_Default'=>'ORDER BY psp.po_initiate_date DESC',
            'SortOrderByColumnAlias' => 'psp',
        ];
        return $config;
    }
    private function getParentSumStock($sku){

        $sql="SELECT * FROM product_details pd
              WHERE pd.parent_isis_sku = '".$sku."' OR pd.isis_sku = '".$sku."';";
        $getDetail=ProductDetails::findBySql($sql)->asArray()->all();
        $sum = 0;
        foreach ( $getDetail as $key=>$value ) {
            $sum+= (int) $value['stocks'];
        }
        return $sum;
    }
    private function getParentSku( $sku ){
        //echo 'Is there any parent of this sku ? '.$sku;
        $sql="select pd.* from product_details pd
            WHERE pd.isis_sku = '".$sku."'";
        //if( $sku=='SCF796/00P' ){echo $sql;die;}
        $IsParent=ProductDetails::findBySql($sql)->asArray()->all();

        $ParentDetail=[];
        if ( !empty($IsParent) &&  isset($IsParent[0]['parent_isis_sku']) && $IsParent[0]['parent_isis_sku']!='0' ){
            $ParentSkuId=$IsParent[0]['parent_isis_sku'];
            $ParentSkuDetail=ProductDetails::find()->where(['isis_sku'=>$ParentSkuId])->asArray()->all();

            //$this->debug($ParentSkuDetail);
            $ParentDetail['isis_sku']=$ParentSkuDetail[0]['isis_sku'];

            $ParentDetail['sku_id']=$ParentSkuDetail[0]['sku_id'];
            $ParentDetail['nc12']=$ParentSkuDetail[0]['nc12'];
            //$ParentDetail['stocks']=$this->getParentSumStock($ParentSkuId);
        }else{
        }
        return $ParentDetail;
    }
    private function actiongetParentSkuDetail($sku){
        $skuDetails=[];
        $checkParentExist = $this->getParentSku($sku);
        //$this->debug($checkParentExist);
        if (!empty($checkParentExist)){
            return $checkParentExist;
        }else{
            return [];
        }

    }
    public function actionPoPrint()
    {
        $this->layout = false;
        $poId = Yii::$app->request->get('poid');
        $poObj = StocksPo::find()->where(['id' => $poId])->one();
        $filename = str_replace(' ','',$poObj->po_code);


        Yii::$app->response->format = Yii\web\Response::FORMAT_RAW;

        $headers = Yii::$app->response->headers;
        $headers->add('Content-Description', 'File Transfer');
        $headers->add('Content-type', 'application/msexcel; charset=utf-8');
        $headers->add('Content-Disposition', 'attachment;Filename=' . $filename . '.xls');
        //$pod = PoDetails::find()->where(['po_id' => $poId])->asArray()->all();
        //$print_po_list = [];

        $pod_foc_bundle = PoDetails::findBySql("SELECT pod.* FROM product_stocks_po psp
                                INNER JOIN po_details pod ON
                                pod.po_id = psp.id
                                WHERE psp.id = ".$poId." AND (pod.bundle IN
                                    (
                                        SELECT pr.id FROM products_relations pr WHERE pr.relation_type IN ('FOC')
                                    )
                                OR pod.bundle IS null)")->asArray()->all();

        $pod_vb_bundle = PoDetails::findBySql("SELECT pod.* FROM product_stocks_po psp
                                INNER JOIN po_details pod ON
                                pod.po_id = psp.id
                                WHERE psp.id = ".$poId." AND (pod.bundle IN
                                    (
                                        SELECT pr.id FROM products_relations pr WHERE pr.relation_type IN ('VB')
                                    )
                                AND pod.bundle IS NOT NULL)")->asArray()->all();
        // FOR fixed bundle
        $pod_fixed_bundle = PoDetails::findBySql("SELECT *
                            FROM product_stocks_po psp
                            INNER JOIN po_details pod ON
                             pod.po_id = psp.id
                             INNER JOIN products_relations pr on
                             pr.id = pod.bundle
                            WHERE psp.id = ".$poId." AND (pod.bundle IN
                                                                (
                                                                    SELECT pr.id
                                                                    FROM products_relations pr
                                                                    WHERE pr.relation_type IN ('FB')
                                                                )
                                                                OR pod.bundle IS NULL
                            )")->asArray()->all();

        $counter=0;

        //$this->debug($pod_foc_bundle);
        foreach ( $pod_foc_bundle as $key=>$value ){
            // The code commented below is to show the total number of order for child and parent and show sum in the parent one.

            /*$info=($this->actiongetParentSkuDetail($value['sku']));
            if (!empty($info)){
                $pod[$key]['sku_id']=$info['sku_id'];
                $pod[$key]['sku']=$info['isis_sku'];
                $pod[$key]['nc12']=$info['nc12'];
            }*/
            if ($value['parent_sku_id']==0 || $value['cost_price']==0.00)
            {
                $pod_refine['FOC'][$counter][$value['sku']]=$value;
                $pod_refine['FOC'][$counter][$value['sku']]['name']=$this->exchange_values('sku','name',$value['sku'],'products');
                $pod_refine['FOC'][$counter][$value['sku']]['po_code']=$this->exchange_values('id','po_code',$value['po_id'],'product_stocks_po');
                $pod_refine['FOC'][$counter][$value['sku']]['ship_to']=$this->exchange_values('id','po_ship',$value['po_id'],'product_stocks_po');
                $pod_refine['FOC'][$counter][$value['sku']]['sold_to']=$this->exchange_values('id','po_bill',$value['po_id'],'product_stocks_po');
                $pod_refine['FOC'][$counter][$value['sku']]['po_finalize_date']=$this->exchange_values('id','po_finalize_date',$value['po_id'],'product_stocks_po');
            }
            $counter++;
        }

        $counter=0;

        foreach ( $pod_vb_bundle as $key=>$value ){
            // The code commented below is to show the total number of order for child and parent and show sum in the parent one.

            /*$info=($this->actiongetParentSkuDetail($value['sku']));
            if (!empty($info)){
                $pod[$key]['sku_id']=$info['sku_id'];
                $pod[$key]['sku']=$info['isis_sku'];
                $pod[$key]['nc12']=$info['nc12'];
            }*/
            if ($value['parent_sku_id']==0 || $value['cost_price']==0.00)
            {
                $pod_refine['VB'][$counter][$value['sku']]=$value;
                $pod_refine['VB'][$counter][$value['sku']]['name']=$this->exchange_values('sku','name',$value['sku'],'products');
                $pod_refine['VB'][$counter][$value['sku']]['po_code']=$this->exchange_values('id','po_code',$value['po_id'],'product_stocks_po');
                $pod_refine['VB'][$counter][$value['sku']]['ship_to']=$this->exchange_values('id','po_ship',$value['po_id'],'product_stocks_po');
                $pod_refine['VB'][$counter][$value['sku']]['sold_to']=$this->exchange_values('id','po_bill',$value['po_id'],'product_stocks_po');
                $pod_refine['VB'][$counter][$value['sku']]['po_finalize_date']=$this->exchange_values('id','po_finalize_date',$value['po_id'],'product_stocks_po');
            }
            $counter++;
        }

        //$pod=$this->POprintParentChild($pod);
        //$counter=0;
        $bundle_ids=[];
        //$this->debug($pod_fixed_bundle);

        foreach ( $pod_fixed_bundle as $key=>$value ){
            if (!in_array($value['bundle'],$bundle_ids)){

                $pod_refine['FB'][$value['bundle']][$value['relation_name']]=$value;

                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['sku']='';
                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['cost_price']=$value['bundle_cost'];
                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['nc12']=$value['relation_name'];
                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['name']='';

                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['po_code']=$this->exchange_values('id','po_code',$value['po_id'],'product_stocks_po');
                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['ship_to']=$this->exchange_values('id','po_ship',$value['po_id'],'product_stocks_po');

                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['sold_to']=$this->exchange_values('id','po_bill',$value['po_id'],'product_stocks_po');
//echo $this->exchange_values('id','po_final_date',$value['po_id'],'product_stocks_po');die;
                $pod_refine['FB'][$value['bundle']][$value['relation_name']]['po_finalize_date']=$this->exchange_values('id','po_finalize_date',$value['po_id'],'product_stocks_po');
                //die;

                $bundle_ids[]=$value['bundle'];
            }
            //die;
            $pod_refine['FB'][$value['bundle']][$value['sku']]=$value;
            $pod_refine['FB'][$value['bundle']][$value['sku']]['name']=$this->exchange_values('sku','name',$value['sku'],'products');
            $pod_refine['FB'][$value['bundle']][$value['sku']]['po_code']=$this->exchange_values('id','po_code',$value['po_id'],'product_stocks_po');
            $pod_refine['FB'][$value['bundle']][$value['sku']]['ship_to']=$this->exchange_values('id','po_ship',$value['po_id'],'product_stocks_po');

            $pod_refine['FB'][$value['bundle']][$value['sku']]['sold_to']=$this->exchange_values('id','po_bill',$value['po_id'],'product_stocks_po');
            $pod_refine['FB'][$value['bundle']][$value['sku']]['po_finalize_date']=$this->exchange_values('id','po_finalize_date',$value['po_id'],'product_stocks_po');
            //$counter++;
        }


        //$this->debug($pod_refine);
        return $this->render('po-print', ['po' => $poObj, 'pod' => $pod_refine]);

    }
    private function POprintParentChild($pod){
        $modify=[];
        foreach ( $pod as $key=>$value ){
            $modify[$value['sku']][]=$value;
        }
        foreach ( $modify as $key1=>$value1 ){
            $orderQty=0;
            $costPrice=0;
            foreach ($value1 as $key2=>$value2){
                $orderQty+=$value2['order_qty'];
                $costPrice+=$value2['cost_price'];
            }
            $modify[$key1]=$value2;
            $modify[$key1]['cost_price']=$costPrice;
            $modify[$key1]['name']=$this->exchange_values('sku','name',$value2['sku'],'products');
            $modify[$key1]['order_qty']=$orderQty;
        }
        //$this->debug($modify);
        return $modify;
    }
    public function redefineBundlePO($bundle){
        //$this->debug($bundle);
        $redine_bundle=[];
        foreach ( $bundle as $key=>$value ){
            $redine_bundle[$value['bundle']]['Bundle_info']=ProductsRelations::find()->where(['id'=>$value['bundle']])->asArray()->all();
            $mainSKU_id = $this->exchange_values('bundle_id','main_sku_id',$value['bundle'],'product_relations_skus');
            $redine_bundle[$value['bundle']]['Bundle_info'][0]['main_sku']=$this->exchange_values('id','sku',$mainSKU_id,'products');

            //  $redine_bundle[$value['bundle']]['Bundle_info'][0]['main_sku']=$this->exchange_values('$red');
            $data = HelpUtil::callManageStocksPO($value['sku_id']);
            $refine = HelpUtil::generateOrderCal($data);
            $value['extra_information'] = $refine;
            $redine_bundle[$value['bundle']]['Sku_list'][]=$value;

        }
        return $redine_bundle;
    }
    // purchase orders screen
    public function getFocMainSkus(){
        $sql="SELECT p.sku FROM products_relations pr
            INNER JOIN product_relations_skus prs ON
            prs.bundle_id = pr.id
            INNER JOIN products p ON
            p.id = prs.main_sku_id
            WHERE pr.relation_type = 'FOC' AND pr.is_active = 1 AND pr.end_at > NOW()
            GROUP BY p.sku;";
        $skus_list = ProductsRelations::findBySql($sql)->asArray()->all();
        $list=[];
        foreach ($skus_list as $value){
            $list[]=$value['sku'];
        }
        //$this->debug($list);
        return $list;
    }
    public function actionCreatePo(){

        $Warehouses = WarehouseUtil::PoWarehouse();

        return $this->render('create-po' , ['warehouses'=>$Warehouses]);
    }

    public function actionPoDetail(){

        $role = WarehouseUtil::GetUserRole();
        if ($role=='distributor'){
            $getWarehouse = WarehouseUtil::GetUserWarehouse();
            if ($_GET['warehouseId']!=$getWarehouse->id)
                $this->redirect('/stocks/po');
        }

        $warehouseDetail = Warehouses::find()->where(['id'=>$_GET['warehouseId']])->one();
        if ( isset($_GET['poId']) ){
            $PoDetail = StocksPo::find()->where(['id' => $_GET['poId']])->one();
            $GetSkus  = WarehouseUtil::GetPoDetail($_GET['poId']);
            //$this->debug($GetSkus);
            $ParentChildRelation = WarehouseUtil::SkuParentChildTreePoSaved($GetSkus);
            //$this->debug($ParentChildRelation);
            $PoSkuList = $ParentChildRelation;
            $PoSkuList = WarehouseUtil::Bundles($PoSkuList,$_GET['poId']);
            //$this->debug($PoSkuList);

        }else{
            $PoDetail = null;
            $WarehouseSales = WarehouseUtil::GetThresholdsForPO($_GET['warehouseId']); // Get thresholds for warehouse in order to make PO
            $CurrentStocks  = WarehouseUtil::GetCurrentStocks($_GET['warehouseId'], $WarehouseSales); // get the current stock of warehouse
            $DealsTargets   = WarehouseUtil::GetDealsTargetsForPO($_GET['warehouseId'],$CurrentStocks); // get the deals target for shops connected with channels
            $SuggestedOrderQty = WarehouseUtil::GetSuggestedOrderQtyPO($DealsTargets); // Put the index of suggested order qty
            //$this->debug($SuggestedOrderQty);
            $ParentChildRelation = WarehouseUtil::ShuffleSkuTypesPo($SuggestedOrderQty,$_GET['warehouseId']);
            //$this->debug($ParentChildRelation);
            $PoSkuList = $ParentChildRelation; // Parent child relation done
            $PoSkuList = WarehouseUtil::Bundles($PoSkuList);
            //$this->debug($PoSkuList);
        }
        if(isset($PoDetail)){
            $PoStatus = $PoDetail->po_status;
            $PoCode = $PoDetail->po_code;
        }else{
            $PoStatus = "";
            $PoCode = WarehouseUtil::GetPoCode($_GET['warehouseId'],(isset($_GET['poId'])) ? $_GET['poId'] : '');
        }

        return $this->render('po-detail',[
            'po'=>$PoDetail,
            'PoCode'=>$PoCode,
            'warehouseDetail'=>$warehouseDetail,
            'status'=>$PoStatus,
            'PoSkuList' => $PoSkuList,
            'LineItem'=>0
        ]);
    }
    public function actionAddBundleDetails()
    {
        $BundleInfo = ProductsRelations::find()->where(['id'=>$_POST['bundle']])->asArray()->one();
        $Product = Products::find()->where(['sku'=>$BundleInfo['relation_name']])->asArray()->one();
        $warehouse = Warehouses::find()->where(['id'=>$_POST['warehouseId']])->one();

        $WarehouseSales = WarehouseUtil::GetThresholdsForPO($_POST['warehouseId'],[$Product['id']]);
        $CurrentStocks  = WarehouseUtil::GetCurrentStocks($_POST['warehouseId'], $WarehouseSales); // get the current stock of warehouse
        //$this->debug($CurrentStocks);
        $DealsTargets   = WarehouseUtil::GetDealsTargetsForPO($_POST['warehouseId'],$CurrentStocks,[$Product['id']]); // get the deals target for shops connected with channels
        //$this->debug($DealsTargets  );
        $SuggestedOrderQty = WarehouseUtil::GetSuggestedOrderQtyPO($DealsTargets,true,$_POST['sku_quantity']); // Put the index of suggested order qty
        //$this->debug($SuggestedOrderQty);
        $ParentChildRelation = WarehouseUtil::ShuffleSkuTypesPo($SuggestedOrderQty);
        $PoSkuList = $ParentChildRelation; // Parent child relation done
        $PoSkuList = WarehouseUtil::Bundles($PoSkuList);
        //$this->debug($PoSkuList);

        return $this->renderPartial('purchase_order/po-bundle-detail',['SkuDetail' => $PoSkuList['bundles'][0],'status'=>'','warehouse'=>$warehouse,'po'=>null,'LineItem'=>1]);
        //$this->debug($PoSkuList['bundles'][0]);
        //return $PoSkuList['bundles'][0];
    }

    public function actionAddSkuDetails()
    {
        $skuIds=[];

        $ChildProducts = Products::find()->where(['parent_sku_id'=>$_POST['sku']])->asArray()->all();
        foreach ($ChildProducts as $skuDetail){
            $skuIds[]=$skuDetail['id'];
        }
        $skuIds[] = $_POST['sku'];
        //$this->debug($skuIds);

        $warehouseDetail = Warehouses::find()->where(['id'=>$_POST['warehouseId']])->one();
        $WarehouseSales = WarehouseUtil::GetThresholdsForPO($_POST['warehouseId'],$skuIds); // Get thresholds for warehouse in order to make PO

        $CurrentStocks  = WarehouseUtil::GetCurrentStocks($_POST['warehouseId'], $WarehouseSales); // get the current stock of warehouse

        $DealsTargets   = WarehouseUtil::GetDealsTargetsForPO($_POST['warehouseId'],$CurrentStocks,[$_POST['sku']]); // get the deals target for shops connected with channels
        $SuggestedOrderQty = WarehouseUtil::GetSuggestedOrderQtyPO($DealsTargets,true,$_POST['sku_quantity']); // Put the index of suggested order qty
        //$this->debug($SuggestedOrderQty);
        $ParentChildRelation = WarehouseUtil::SkuParentChildTreePoSaved($SuggestedOrderQty);

        $PoSkuList = $ParentChildRelation;

        //$this->debug($PoSkuList);

        return $this->renderPartial('purchase_order/po-sku-list',['PoSkuList' => $PoSkuList,'status'=>'','warehouse'=>$warehouseDetail,'po'=>null,'LineItem'=>1]);
        //return $this->renderAjax('_product-line-details', ['refine' => $child_parent_, 'isPO' => $isPO]);
    }
// add line product
    public function actionAddProductLine()
    {
        $AlreadyExist = (isset($_POST['already_in_list_sku'])) ? $_POST['already_in_list_sku'] : [];
        $ProductsList = WarehouseUtil::AddLineItemPo($_POST['warehouseId'],$AlreadyExist);
        return $this->renderAjax('_product-line', ['ProductList' => $ProductsList,'warehouseId'=>$_POST['warehouseId']]);

    }
    public function actionSavePo(){

        $Skus = $_POST;
        //$this->debug($Skus);
        $PoID = PurchaseOrderUtil::SavePurchaseOrder($Skus);
        PurchaseOrderUtil::SavePurchaseOrderSkus($PoID,$Skus);
        $response = WarehouseUtil::UpdateStockInTransit($Skus); // update the stock in transit values
        $PushToWarehouseAPI = WarehouseUtil::CreatePo($Skus); // push po to third party warehouse API
        if ( $PoID ){
            $this->redirect('/stocks/po-detail?warehouseId='.$Skus['warehouseId'].'&poId='.$PoID);
        }
    }
    public function actionOrders($poId = null)
    {
        $pds = HelpUtil::callManageStocksPO(null, $poId);

        //$this->debug($pds);
        //$this->debug($_POST);
        $refine = [];
        $filters = true;
        if ($poId) {
            $filters = false;
            $poObj = StocksPo::find()->where(['id' => $poId])->one();
            $pod = PoDetails::find()->where(['po_id' => $poId,'bundle'=>null])->all();
            //$poBundles=PoDetails::find()->where(['po_id' => $poId])->andWhere(['not', ['bundle' => null]]);
            $poBundles=PoDetails::findBySql("select * from po_details pod where pod.po_id=".$poId." AND pod.bundle != ''")->asArray()->all();
            $poBundles=$this->redefineBundlePO($poBundles);
            //$this->debug($poBundles);
            $podObJ=[];
            //$this->debug($pod);
            foreach ($pod as $sk) {
                $sk->sku = trim($sk->sku);
                $podObJ['name'][] = $sk->sku;
                $podObJ['id'][$sk->sku][] = $sk->id;
                $podObJ['final_qty'][$sk->sku][] = $sk->final_order_qty;
                $podObJ['order_qty'][$sk->sku][] = $sk->order_qty;
                $podObJ['current_stock'][$sk->sku][] = $sk->current_stock;
                $podObJ['philips_stocks'][$sk->sku][] = $sk->philips_stocks;
                $podObJ['nc12'][$sk->sku][] = $sk->nc12;
                $podObJ['is_finalize'][$sk->sku][] = $sk->is_finalize;
                $podObJ['er_qty'][$sk->sku][] = (empty($sk->er_qty)) ? '0' : $sk->er_qty ;
            }
            //die;

        } else {
            $poObj = null;
            $podObJ = [];
            $poBundles=[];
        }


        $refine = HelpUtil::generateOrdersCal($pds, $poId, $filters);
        // post values
        if (isset($_POST['warehouse'])) {
            //$this->debug($_POST);
            HelpUtil::generateInitialPo();
            $Last_Added_PO = isset( $_POST['po_id'] ) ? $this->getLastPoId($_POST['po_id']) : $this->getLastPoId();
            $this->redirect('/stocks/orders?poId='.$Last_Added_PO[0]['id'].'&warehouse='.$Last_Added_PO[0]['po_warehouse']);
        }
        $parentSkus=$this->getSkusWithParent();
        $remodifySkus=$this->reModifywithSkuId($parentSkus);
        $child_parent_=$this->reModifySkuWithMapping($remodifySkus,$refine);
        if (!empty($poBundles)){
            $bundle_ids_array = $this->getBundleIds($poBundles);
        }else{
            $bundle_ids_array= [];
        }
        //$this->debug($child_parent_);
        return $this->render('orders_isis', [
            //'refine' => $refine,
            'refine' => $child_parent_,
            'po' => $poObj, 'pod' => $podObJ,'parentsku_mapping'=>$remodifySkus,
            'po_bundles'=>$poBundles,
            'bundle_ids'=>implode(',',$bundle_ids_array),
            'foc_sku_list' => $this->getFocMainSkus()
        ]);

    }
    public function getBundleIds($bundle_info){
        $ids=[];
        foreach ( $bundle_info as $key=>$value ){
            $ids[]=$value['Bundle_info'][0]['id'];
        }
        return $ids;
    }
    public function getLastPoId($poId=''){
        $sql = "select * from product_stocks_po ";
        if ($poId!=''){
            $sql .= 'where id = '.$poId;
        }
        $sql .= ' order by id desc limit 1';
        $getPoDetail = ProductStocks::findBySql($sql)->asArray()->all();
        return $getPoDetail;
    }


    private function getSkuQuantityInBundle($Bundle_id,$Sku_text){
        $Sku_id = $this->exchange_values('sku','id',$Sku_text,'products');
        $sql = "SELECT * FROM product_relations_skus prs where prs.bundle_id = ".$Bundle_id." AND child_sku_id = ".$Sku_id;
        $Sku_Bundle_Info = ProductRelationsSkus::findBySql($sql)->asArray()->all();
        return $Sku_Bundle_Info[0]['child_quantity'];
    }
    private function reModifyBundleSkus($Bundle_Skus,$warehouse){
        //$this->debug($Bundle_Skus);
        $ref_modify = [];
        foreach ( $Bundle_Skus as $key=>$value ){
            foreach ($value as $key1=>$value1){
                $ref_modify[$key1][]=$value1[0];
            }
        }
        return $ref_modify;
    }
    private function reModifySkuWithMapping( $remodifySkus , $refine ){

        $new_modify=[];

        foreach ( $refine as $key=>$value ){
            foreach ( $value as $key1=>$value1 ){
                $parent_sku_id = $this->exchange_values('sku','id',$value1['sku_id'],'products');
                //echo $parent_sku_id;die;
                //$FindChilds = ProductDetails::find()->where(['parent_sku_id'=>$parent_sku_id])->asArray()->all();

                $FindChilds = ProductDetails::findBySql("SELECT pd.* FROM products p
                                                            INNER JOIN product_details pd ON
                                                            p.id = pd.sku_id
                                                            WHERE p.parent_sku_id = ".$parent_sku_id.";
                                                            ")->asArray()->all();
                if (empty($FindChilds)){
                    $new_modify[$key][$key1]['Parent'][] = $value1;
                }else{
                    $new_modify[$key][$key1]['Parent'][] = $value1;
                    //$this->debug($FindChilds);
                    foreach ($FindChilds as $val1){
                        //$this->debug(HelpUtil::callChildManageSocks($val1['id']));
                        $extra_information = HelpUtil::callChildManageSocks($val1['id']);

                        if (!empty($extra_information)){
                            $new_modify[$key][$key1]['Child'][] = HelpUtil::callChildManageSocks($val1['id']);
                        }
                        if ( isset($new_modify[$key][$key1]['Child']) ){

                            foreach ($new_modify[$key][$key1]['Child'] as $key2=>$val2){

                                //echo $key2;die;
                                $settings = Settings::find()->where(['name' => 'stocks_high_threshold_alert'])->one();
                                if ($settings) {
                                    $threshold = $settings->value;
                                }
                                //foreach ($val2 as $k => $sk) {
                                if (!isset($val2['isis_threshold']))
                                    continue;
                                if ($key=='a'){
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold'] = $val2['isis_threshold'];
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold_org'] = $val2['isis_threshold_org'];
                                    $new_modify[$key][$key1]['Child'][$key2]['dealNo'] = ($val2['dealNoISIS']) ?  $val2['dealNoISIS'] : '0';
                                    //$new_modify[$key][$key1]['Child'] = $sk;
                                }


                                if ($key=='b'){
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold'] = $val2['fbl_blip_threshold'];
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold_org'] = $val2['fbl_blip_threshold_org'];
                                    $new_modify[$key][$key1]['Child'][$key2]['dealNo'] = ($val2['dealNoFBLZD']) ? $val2['dealNoFBLZD'] : '0';
                                    //$new_modify[$key][$key1]['Child'] = $sk;
                                }

                                if ($key=='c'){
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold'] = $val2['fbl_909_threshold'];
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold_org'] = $val2['fbl_909_threshold_org'];
                                    $new_modify[$key][$key1]['Child'][$key2]['dealNo'] = ($val2['dealNoFBL909']) ? $val2['dealNoFBL909'] : '0';
                                    //   $new_modify[$key][$key1]['Child'] = $sk;
                                }

                                if (isset($key) && $key=='d'){
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold'] = $val2['fbl_avent_threshold'];
                                    $new_modify[$key][$key1]['Child'][$key2]['threshold_org'] = $val2['fbl_avent_threshold_org'];
                                    $new_modify[$key][$key1]['Child'][$key2]['dealNo'] = ($val2['dealNoFBL909Avent']) ? $val2['dealNoFBL909Avent'] : '0';
                                    //   $new_modify[$key][$key1]['Child'] = $sk;
                                }

                                //}
                            }
                        }

                    }
                }
            }
            //$new_modify_2=$new_modify;
            /*foreach ($new_modify as $key2=>$val2){
                foreach ($val2 as $key3=>$val3){
                    if ( isset($val3['Child']) ){
                        $new_modify[$key2][$key3]['Child']=HelpUtil::generateOrdersCal($val3['Child'],null,false);
                    }

                }
            }*/
        }
        //$this->debug($new_modify);
        return $new_modify;
        // $this->debug($new_modify);
    }
    private function reModifywithSkuId($data){
        $modify=[];
        foreach ( $data as $key=>$value ){
            $Parent_Sku = $this->exchange_values('id','sku',$value['parent_sku_id'],'products');
            $modify[$value['sku']]=$Parent_Sku;
        }
        return $modify;
    }
    private function getSkusWithParent(){
        $sql='SELECT * FROM products p
              WHERE p.parent_sku_id != 0';
        $findSkus=Products::findBySql($sql)->asArray()->all();
        return $findSkus;
    }
    // delete orders
    public function actionDeletePo()
    {
        $poId = Yii::$app->request->get('poId');
        $warehouse = Yii::$app->request->get('warehouse');
        $pod = PoDetails::find()->where(['po_id' => $poId])->all();
        foreach ($pod as $item) {
            // update stock intransit
            $ps = ProductStocks::find()->where(['stock_id' => $item->sku_id])->one();
            if ($ps) {
                if ($warehouse == 1)
                    $ps->stocks_intransit = '0';
                else if ($warehouse == 2)
                    $ps->fbl_stocks_intransit = '0';
                else if ($warehouse == 3)
                    $ps->fbl909_stocks_intransit = '0';
                $ps->save(false);
            }

        }
        PoDetails::deleteAll(['po_id' => $poId]);
        StocksPo::updateAll(['is_active' => '0'], ['id' => $poId]);

        $this->redirect('/stocks/po');


    }
    public function actionGenericGrid(){
        $pds = HelpUtil::callAllStocks();
        $pdq = \Yii::$app->request->post('pdqs');
        $pagination_total_pages = ceil($pds['total_records']/\Yii::$app->request->post('records_per_page'));
        $this->data['tbody']= $this->renderAjax('_stocksInfo', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        $this->data['tfoot']=$this->renderAjax('_stocksInfoFooter', ['stocks' => $pds, 'pdq' => $pdq,'pagination_pages'=>$pagination_total_pages,
            'page_no'=>Yii::$app->request->post('page_no')]);
        echo $this->data['tbody'].$this->data['tfoot'];
    }

    public function actionDashboard()
    {

        $warehouses_Stocks = HelpUtil::WarehousesStocks();

        //$this->debug($warehouses_Stocks);
        $stocksTrans = HelpUtil::getStockInTrans();

        $upcomingDeal = HelpUtil::getUpcomingDeals();

        $oosIsis = HelpUtil::getOssInIsis();

        $soon_out_of_stock = HelpUtil::getSoonOos();

        $agging = HelpUtil::getAgingStock();

        // $NewOOSTest = $this->actionGetProductsDetailsStock();
        //$NewOOSTest = HelpUtil::getOutOfStock('list');
        $current_under_stock_order= HelpUtil::currentUnderStockOrder();

        //$monthlyStock = \backend\util\GraphsUtil::getStock();
        return $this->render('dashboard', ['upcomingDeal' => $upcomingDeal,
                                                //'oos_test'=>$NewOOSTest,
                                                'oosIsis' => $oosIsis,
                                                'soon_out_of_stock' => $soon_out_of_stock,
                                                'aging' => $agging,
                                                'current_under_stock_order' => $current_under_stock_order,
                                                'stocksTrans'=>$stocksTrans,
                                                'warehouses_Stocks'=>$warehouses_Stocks
                                                //'monthlyStock'=>$monthlyStock
                                              ]);
    }

    public function actionStockNotManagedByEzcom()
    {
        $products=InventoryUtil::notManagedByEzcom();
        $channels=Channels::find()->where(['is_active'=>'1'])->asArray()->all();
       // self::debug($products);
        return $this->render('stock_not_managed_by_ezcom',['products'=>$products['data'],'total_records'=>$products['total_records'],'channels'=>$channels]);
        //die('come');
    }
    public function actionStockNotManagedByEzcomExportCsv()
    {

        $_GET['record_per_page']=25000; // to fetch all not by particular page
        $data=InventoryUtil::notManagedByEzcom();
        $channels=Channels::find()->where(['is_active'=>'1'])->asArray()->all();
        return  InventoryUtil::export_csv_not_managed_by_ezcom($data,$channels);
    }

    public function GetOosSkusCurrent(){
        $getCurrentSkus="SELECT pd.isis_sku FROM product_details pd
                        INNER JOIN `products` p ON
                        p.`id` = pd.`sku_id`
                        WHERE pd.goodQty=0 AND p.`is_active` = 1 AND p.`sub_category` <> 167";
        $getResults=ProductDetails::findBySql($getCurrentSkus)->asArray()->all();
        $sku_list=[];
        foreach ($getResults as $key=>$value){
            $sku_list[] = $value['isis_sku'];
        }
        return $sku_list;
    }
    public function GetSkuListInfo(){
        $getCurrentSkus="SELECT pd.`isis_sku`,p.`selling_status` FROM product_details pd
                        INNER JOIN `products` p ON
                        p.`id` = pd.`sku_id`
                        WHERE pd.goodQty=0 AND p.`is_active` = 1 AND p.sub_category!=167";
        $getResults=ProductDetails::findBySql($getCurrentSkus)->asArray()->all();
        return $getResults;
    }
    public function RecursiveReverseOOs($Records){
        $list=[];
        foreach ($Records as $key=>$value){
            if ( $value['goodQty'] <= 0 ){
                $dateTime=date_create($value['date_archive']);
                $date= date_format($dateTime,"Y-m-d");
                $list[$date]=$value['goodQty'];
            }else{
                break;
            }
        }
        return $list;
    }
    public function SortWithSkuIndex($sql){
        $list=[];
        $getResults=ProductDetails::findBySql($sql)->asArray()->all();
        foreach ( $getResults as $key=>$value ){
            $list[$value['isis_sku']][] = $value;
        }
        return $list;
    }
    public function actionGetProductsDetailsStock(){
        $from= date('Y-m-d', strtotime("-60 days"));

        $sku_list_oos=[];

        $oos_skus=$this->GetOosSkusCurrent();

        $sql2="SELECT * FROM product_details_archive pda
                    WHERE pda.`date_archive` >= '".$from."' and
                    pda.`isis_sku` IN ('".implode('\',\'',$oos_skus)."') order by pda.id desc";
        $sku_list=$this->SortWithSkuIndex($sql2);

        foreach ( $sku_list as $key=>$value ){
            $result=$this->RecursiveReverseOOs( $value );
            if (!empty($result))
                $sku_list_oos[$key]['days']=count($result);
            else
                $sku_list_oos[$key]['days']=0;


        }

        foreach ( $this->GetSkuListInfo() as $key=>$value ){
            $sku_list_oos[$value['isis_sku']]['selling_status']=$value['selling_status'];
        }

        $get_product_detalis="SELECT * FROM `product_details` pd
                              INNER JOIN `products` p ON
                              p.`id` = pd.`sku_id`
                              WHERE p.`sub_category` != '167' AND pd.`goodQty` = 0 AND p.`is_active` = 1";
        $result_product_details= $this->ModifyArr(ProductDetails::findBySql($get_product_detalis)->asArray()->all(),'isis_sku');

        foreach ($sku_list_oos as $key=>$value){
            if ((!isset($sku_list_oos[$key]) || !isset($result_product_details[$key])))
                continue;
            $sku_list_oos[$key]['stocks'] = $result_product_details[$key]['stocks'];
            $sku_list_oos[$key]['philips_stock'] = $result_product_details[$key]['philips_stocks'];
            $sku_list_oos[$key]['parent_isis_sku'] = $result_product_details[$key]['parent_isis_sku'];
        }
        /**
         * Now check the sku variations and remove the skus which has atleast 1 qty in any variation
         * */

        $already_checked_skus=[];
        $oos_new_list=[];
        foreach ( $sku_list_oos as $sku=>$value ){
            if (in_array($sku,$already_checked_skus))
                continue;
            $checkOos=$this->getParentChaildProduct($sku);

            foreach ($checkOos['skus'] as $val)
                $already_checked_skus[]=$val;
            if ($checkOos['qty']>0)
                continue;
            $parentsku=$checkOos['parent_sku'];
            if ( ($checkOos['parent_sku'])=='' )
                $parentsku=$value['parent_isis_sku'];

            $oos_new_list[$parentsku][]=$value;
            $oos_new_list[$parentsku]['stocks']=$checkOos['qty'];
        }
        $oos_new_list_near_oos=[];
        foreach ( $oos_new_list as $key=>$value ){
            $lowest_days=array();
            $check_oos=$this->CheckChildsAndParentOOS($key);
            if ($check_oos=='in stock')
                continue;
            foreach ( $value as $key1=>$value1 ){
                if ( isset($value1['days']) ){
                    $lowest_days[]=$value1['days'];
                    $oos_new_list_near_oos[$key]=$value1;
                }
            }
            if (empty($lowest_days))
                $oos_new_list_near_oos[$key]['days']=0;
            else
                $oos_new_list_near_oos[$key]['days']=min($lowest_days);
        }
        return $oos_new_list_near_oos;
    }
    private function CheckChildsAndParentOOS($parent_sku){
        $sql="SELECT * FROM product_details pd
              WHERE pd.isis_sku = '".$parent_sku."' or pd.parent_isis_sku = '".$parent_sku."';";
        $getSkus=ProductDetails::findBySql($sql)->asArray()->all();
        $stock='out of stock';
        foreach ($getSkus as $value){
            if ($value['stocks']>0)
                $stock='in stock';
        }
        return $stock;
    }
    private function getParentChaildProduct($sku){


        $sql="SELECT * FROM product_details pd
                INNER JOIN `products` p ON
                p.`id` = pd.sku_id
              WHERE (pd.isis_sku = '".$sku."' OR pd.parent_isis_sku = '".$sku."') AND pd.isis_sku is not null";
        /*if ($sku=='BHH811/03')
            $sql;die;*/
        $getProducts=ProductDetails::findBySql($sql)->asArray()->all();
        $qty_and_skus=$this->getTotalISISStock($getProducts);
        return $qty_and_skus;
    }
    private function getTotalISISStock($skus_product_Details){
        $qty=0;
        $skus=[];
        $parent_sku='';
        foreach ( $skus_product_Details as $key=>$value ){
            $qty+=$value['stocks'];
            $skus[]=$value['isis_sku'];
            if ($value['parent_isis_sku']=='0')
                $parent_sku=$value['isis_sku'];
        }
        return ['qty'=>$qty,'skus'=>$skus,'parent_sku'=>$parent_sku];
    }
    private function ModifyArr($arr,$index){
        $data=[];
        foreach ( $arr as $key=>$value ) {
            $data[ $value[$index] ] = $value;
        }
        return $data;
    }


    public function actionReport()
    {
        $orderSkus = HelpUtil::getSkuActualStockNumber();

        $stocksSkus = HelpUtil::getCurrentStockInfo();

        return $this->render('stocks_report', ['orderSkus' => $orderSkus,'stocksSkus'=>$stocksSkus]);
    }
    public function actionOfflinePosImport(){
        $ErcList=PoImportTemp::find()->asArray()->all();
        //$this->debug($ErcList);
        $Pos_List=  array();
        foreach ( $ErcList as $key=>$value ){
            $Pos_List[$value['po_number']][]=$value;
        }
        //$this->debug($Pos_List);
        foreach ( $Pos_List as $key=>$value ){
            // $this->debug($value);
            foreach ($value as $key_inner=>$value_inner){

                //$this->debug($value_inner);
                $Check_Already_Exist = StocksPo::find()->where(['po_code'=>$value_inner['po_number']])->asArray()->all();
                if (empty($Check_Already_Exist)){
                    $Create_Po = new StocksPo();
                    $Create_Po->po_warehouse='1';
                    $Create_Po->po_initiate_date=$value_inner['po_date'];
                    $Create_Po->po_status='Shipped';
                    $Create_Po->po_final_date=$value_inner['po_date'];
                    $Create_Po->po_code=$value_inner['po_number'];
                    $Create_Po->po_ship=$value_inner['ship_to'];
                    $Create_Po->po_bill=$value_inner['bill_to'];
                    $Create_Po->is_active='1';
                    $Create_Po->po_final_date=$value_inner['po_date'];
                    $Create_Po->save();
                    if( !empty($Create_Po->errors) ){
                        $this->debug($Create_Po->errors);
                    }
                    $Find_Po_id = StocksPo::find()->where(['po_code'=>$value_inner['po_number']])->asArray()->all();
                    $Create_Po_Details = new PoDetails();
                    $Create_Po_Details->po_id=$Find_Po_id[0]['id'];
                    $Create_Po_Details->sku_id=(int) $this->exchange_values('sku','id',$value_inner['sku'],'products');
                    $Create_Po_Details->sku=$value_inner['sku'];
                    $Create_Po_Details->nc12=$value_inner['nc'];
                    $Create_Po_Details->cost_price=str_replace(',','',$value_inner['unit_price']);
                    $Create_Po_Details->order_qty=$value_inner['quantity'];
                    $Create_Po_Details->final_order_qty=$value_inner['quantity'];
                    $Create_Po_Details->warehouse='isis';
                    $Create_Po_Details->parent_sku_id=(int) $this->exchange_values('isis_sku','parent_isis_sku',$value_inner['sku'],'product_details');
                    $Create_Po_Details->is_finalize=1;
                    $Create_Po_Details->save();
                    if( !empty($Create_Po_Details->errors) ){
                        $this->debug($Create_Po_Details->errors);
                    }
                }else{
                    $Find_Po_id = StocksPo::find()->where(['po_code'=>$value_inner['po_number']])->asArray()->all();
                    $Create_Po_Details = new PoDetails();
                    $Create_Po_Details->po_id=$Find_Po_id[0]['id'];
                    $Create_Po_Details->sku_id=(int) $this->exchange_values('sku','id',$value_inner['sku'],'products');
                    $Create_Po_Details->sku=$value_inner['sku'];
                    $Create_Po_Details->nc12=$value_inner['nc'];
                    $Create_Po_Details->cost_price=str_replace(',','',$value_inner['unit_price']);
                    $Create_Po_Details->order_qty=$value_inner['quantity'];
                    $Create_Po_Details->final_order_qty=$value_inner['quantity'];
                    $Create_Po_Details->warehouse='isis';
                    $Create_Po_Details->parent_sku_id=(int) $this->exchange_values('isis_sku','parent_isis_sku',$value_inner['sku'],'product_details');
                    $Create_Po_Details->is_finalize=1;
                    $Create_Po_Details->save();
                    if( !empty($Create_Po_Details->errors) ){
                        $this->debug($Create_Po_Details->errors);
                    }
                }

            }
        }
        //$this->debug($Pos_List);
        /*$erDetails = ApiController::fetchER('ERC647767');
        $this->debug($erDetails);*/
    }

    public function actionGetSkuStock()
    {
        $sku=Yii::$app->request->post('sku');
        $warehouse_id=Yii::$app->request->post('warehouse_id');
        if($sku && $warehouse_id)
        {
            $result=WarehouseStockList::find()->select('SUM(available) as stock,SUM(stock_in_pending) as pending')->where(['sku'=>$sku,'warehouse_id'=>$warehouse_id])->asArray()->one();
            if($result)
            {
                return $this->asJson(['status'=>'success','sku'=>$sku,'stock'=>$result['stock'],'pending_stock'=>$result['pending']]);
            }
            return $this->asJson(['status'=>'success','sku'=>$sku,'stock'=>0,'pending_stock'=>0]);
        }
        return $this->asJson(['status'=>'failure','sku'=>'-','stock'=>'-','pending_stock'=>'-']);

    }

    public function actionGetSkuExtraInformation(){

        if ($_GET['poId']=='null'){
            return $this->GetCurrentSkuExtraDetail();
        }else{
            return $this->GetSavedSkuExtraDetail($_GET['poId'],$_GET['sku_id'],$_GET['bundle']);
        }

    }
    public function GetSavedSkuExtraDetail($PoId,$SkuId, $bundle){

        if ( $bundle!='false' ){
            $SkuDetailPO = PoDetails::find()->where(['po_id'=>$PoId,'sku_id'=>$SkuId,'bundle'=>$bundle])->asArray()->all();
        }else{
            $SkuDetailPO = PoDetails::find()->where(['po_id'=>$PoId,'sku_id'=>$SkuId])->asArray()->all();
        }

        $Extra_Info=[];
        $Extra_Info['basicInfo']['cost_price']=$SkuDetailPO[0]['cost_price'];
        $Extra_Info['basicInfo']['tnc']=$SkuDetailPO[0]['p_unique_code'];

        $DealTarget = json_decode($SkuDetailPO[0]['deals_target_json'],1);

        if ( !empty($DealTarget) ) {
            foreach ($DealTarget as $key=>$d) {

                $Extra_Info['dealInfo'][$key]['name']=$d['deal_name'];
                $Extra_Info['dealInfo'][$key]['target']=$d['deal_target'];

            }
        }

        $Threshold_list=PoThresholds::find()->where(['po_id'=>$PoId,'po_details_id'=>$SkuDetailPO[0]['id']])->asArray()->all();
        $Threshold = [];
        //$this->debug($Threshold_list);
        foreach ( $Threshold_list as $val ){
            $Threshold[$val['name']] = $val['value'];
        }
        $Extra_Info['thresholds'] = $Threshold;

        $Extra_Info['calculationInfo']['total_deal_target']=$SkuDetailPO[0]['total_deals_target'];

        $Extra_Info['calculationInfo']['Current_Stocks']=$SkuDetailPO[0]['current_stock'];

        $Extra_Info['calculationInfo']['Suggested_order_qty']=$SkuDetailPO[0]['suggested_order_qty'];
        return $this->renderPartial('_extra-information-partial-sku',['info'=>$Extra_Info]);
    }
    public function GetCurrentSkuExtraDetail(){
        $Extra_Info=[];
        $Extra_Info['basicInfo']['cost_price']=$this->exchange_values('id','cost',$_GET['sku_id'],'products');
        $Extra_Info['basicInfo']['tnc']=$this->exchange_values('id','ean',$_GET['sku_id'],'products');
        $Warehouse = Warehouses::find()->where(['id'=>$_GET['warehouse']])->one();
        $Extra_Info['basicInfo']['transit_days'] = $Warehouse->transit_days;
        $Sku_Id = $_GET['sku_id'];
        $sku = $this->exchange_values('id', 'sku',$Sku_Id,'products');

        $DealTarget = WarehouseUtil::GetDealsTargetForSku($_GET['warehouse'],[$Sku_Id]);

        $Total_Deal_Target = 0;

        if ( !empty($DealTarget) ) {
            foreach ($DealTarget as $key=>$d) {

                $Extra_Info['dealInfo'][$key]['name']=$d['deal_name'];
                $Extra_Info['dealInfo'][$key]['target']=$d['deal_target'];
                $Total_Deal_Target += $d['deal_target'];

            }
        }

        $Threshold_list=WarehouseUtil::GetThresholds($_GET['warehouse'],[$Sku_Id]);
        $Threshold = [];
        foreach ( $Threshold_list as $val ){
            $Threshold['t1'] = $val['t1'];
            $Threshold['t2'] = $val['t2'];
        }
        $Extra_Info['thresholds'] = $Threshold;

        $Extra_Info['calculationInfo']['total_deal_target']=$Total_Deal_Target;

        $curStock = WarehouseStockList::find()->where(['warehouse_id'=>$_GET['warehouse'],'sku'=>$sku])->asArray()->all();//get current stock of sku

        if (empty($curStock))
            $curStock = 0;
        else
            $curStock = $curStock[0]['available'];

        $Extra_Info['calculationInfo']['Current_Stocks']=$curStock;

        $soq = array_sum($Threshold) - $curStock;

        $soq  = $soq + $Total_Deal_Target;
        $Extra_Info['calculationInfo']['Suggested_order_qty']=$soq;
        return $this->renderPartial('_extra-information-partial-sku',['info'=>$Extra_Info]);
    }
    public function actionFetchEr(){
        $er_info=ApiController::fetchER($_GET['er']);
        echo json_encode($er_info);
        die;
    }
    public function actionTempInsertProductDetail(){
        $sql = "SELECT * FROM `products`  p";
        $get_products_list = Products::findBySql($sql)->asArray()->all();
        foreach ($get_products_list as $key=>$value){
            $find_sku = "select * from product_details where isis_sku like '".$value['sku']."'";
            $find_sku_detail = ProductDetails::findBySql($find_sku)->asArray()->all();
            if ( empty($find_sku_detail) ){
                $insert_Product_Detail = new ProductDetails();
                $insert_Product_Detail->sku_id=$value['id'];
                $insert_Product_Detail->isis_sku=$value['sku'];
                $insert_Product_Detail->parent_isis_sku='0';
                $insert_Product_Detail->last_update=date('Y-m-d h:i:s');
                $insert_Product_Detail->is_fbl=$value['is_fbl'];
                $insert_Product_Detail->sync_for=1;
                $insert_Product_Detail->save();
                if (!empty($insert_Product_Detail->errors))
                    $this->debug($insert_Product_Detail->errors);
                //$this->debug($value);
            }
        }
    }
    public function actionUpdateIoEr(){
        $updateEr_IO = StocksPo::findOne($_GET['po_id']);
        $updateEr_IO->er_no = $_GET['er-io'];
        $updateEr_IO->update();
        if (empty($updateEr_IO->errors))
            echo 1;
        else
            echo 0;
    }

    /*
     * Import FBL IO file for inbounded qty check
     */
    public function actionFblIoImport()
    {
        if (isset($_POST)) {


            $csvFile = $_FILES['fileToSave'];
            $filename = $csvFile["tmp_name"];
            if ($csvFile["size"] > 0) {
                $file = fopen($filename, "r");
                $row = 0;

                $csv_unmatched_skus = [];
                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    if ($row == 0) {
                        $row++;
                        continue;
                    }

                    $sku = trim($getData[7]);
                    $pname = trim($getData[8]);
                    $qty = $getData[14];
                    $ioNumber = $getData[0];
                    $poDetails = PoDetails::find()->joinWith('po')->where(['product_stocks_po.er_no'=>$ioNumber,'product_stocks_po.id'=>$_POST['po_id']])->andWhere(['sku'=>$sku])->one();
                    if($poDetails)
                    {
                        $poDetails->er_qty = $qty;
                        $poDetails->update();
                    }else{
                        $csv_unmatched_skus[] = [
                          'sku' => $sku,
                            'pname'=>$pname,
                            'qty' =>$qty,
                            'io_number'=>$ioNumber
                        ];
                    }

                }
                //$this->debug($csv_unmatched_skus);
                /**
                 * Some times we have different seller_sku in csv, So this will run for to match sku with name.
                 * */
                $get_po_skus = "SELECT * FROM po_details pod
                                WHERE pod.po_id = ".$_POST['po_id']." AND er_qty IS NULL AND pod.final_order_qty != 0";
                $er_empty_skus = PoDetails::findBySql($get_po_skus)->asArray()->all();
                foreach ( $er_empty_skus as $key=>$value ){
                    foreach ( $csv_unmatched_skus as $key1=>$value1 ){
                        $csv_p_name = $value1['pname'];

                        if (strpos($csv_p_name, $value['sku']) !== false) {
                            $update_er = PoDetails::findOne($value['id']);
                            $update_er->er_qty=$value1['qty'];
                            $update_er->update();
                        }
                    }
                }


                /*if (!empty($poDetails)){
                    $update_po_status = StocksPo::findOne($_POST['po_id']);
                    $update_po_status->po_status='Shipped';
                    $update_po_status->update();
                }*/
            }
        }
        echo "1";

    }
    public function actionExportStockArchive(){
        ini_set('memory_limit','2000M');

        $Query = "SELECT * FROM product_details_archive pda
                  WHERE pda.date_archive BETWEEN '".$_GET['from']."' AND '".$_GET['to']."'";
        $Results = ProductDetailsArchive::findBySql($Query)->asArray()->all();

        $AllDates=HelpUtil::getAllDatesBetweenTwoDates($_GET['from'],$_GET['to']);


        $Archive = [];
        foreach ( $Results as $key=>$value ){
            //$this->debug($value);
            $Date = date('Y-m-d',strtotime($value['date_archive']));
            $Archive[$value['isis_sku']][$Date] = $value['stocks'];
        }
        foreach ( $Archive as $key=>$value ){
            //$this->debug($value);
            foreach ($AllDates as $chk_date_exist){
                if (!isset($value[$chk_date_exist])){
                    //$this->debug($Archive[$key]);
                    $Archive[$key][$chk_date_exist]='N/A';
                    //$this->debug($Archive[$key]);
                    //$Archive[$key][$chk_date_exist]=0;
                }
            }
            //$this->debug($Archive[$key]);
            $Archive[$key]= HelpUtil::sortArrDateIndex($Archive[$key]);
            //$this->debug($value);
        }
        //$this->debug($Archive);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="stocks-archive.csv"');
        $csv_header_column = array
        (
            "Sku,".implode(',',$AllDates)
        );

        foreach ( $Archive as $key=>$value ) {
            $csv_header_column[] = $key.','.implode(',',$value);
        }

        $file = fopen("php://output","wb");

        foreach ($csv_header_column as $line)
        {
            fputcsv($file,explode(',',$line));
        }

        fclose($file);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="stocks-archive.csv"');
    }
    public function actionSkuMakeSlow(){
        $json = '';
        $json_decode = json_decode($json);
        foreach ($json_decode as $key=>$value){
            $sku_id = $this->exchange_values('sku','id',$value->sku,'products');
            $product_detail_id = $this->exchange_values('sku_id','id',$sku_id,'product_details');
            $stock_pk_id = $this->exchange_values('stock_id','id',$product_detail_id,'product_stocks');

            $Update_stock_status = ProductStocks::findOne($stock_pk_id);
            $Update_stock_status->blip_stock_status = 'Slow';
            $Update_stock_status->update();
            if ( $Update_stock_status->errors )
                $this->debug($Update_stock_status);
//            $this->debug($stock_pk_id);

        }
    }
    public function actionUpdateStockInTransit(){
        $list = "select * from product_stocks_po psp inner join po_details pod on psp.id=pod.po_id where psp.po_status = 'Pending'";
        $get_pending_po = ProductStocks::findBySql($list)->asArray()->all();
        $warehouse = [
            3 => 'avent_stocks_intransit',
            2 => 'fbl_stocks_intransit',
            1 => 'stocks_intransit',
            7 => 'fbl909_stocks_intransit'
        ];
        foreach ($get_pending_po as $val){
            //$this->debug($val);
            $pd_detail = $this->exchange_values('sku_id','id',$val['sku_id'],'product_details');
            $product_stock = ProductStocks::find()->where(['stock_id'=>$pd_detail])->asArray()->all();
            //$this->debug($product_stock);
            if ( !isset($product_stock[0]['id']) )
                continue;

            $findStock = ProductStocks::findOne($product_stock[0]['id']);

            if ( $val['po_warehouse']==3 ){
                $findStock->avent_stocks_intransit = $val['final_order_qty'];
            }else if ( $val['po_warehouse']==2 ){
                $findStock->fbl_stocks_intransit = $val['final_order_qty'];
            }else if ( $val['po_warehouse']==1 ){
                $findStock->stocks_intransit = $val['final_order_qty'];
            }else if ( $val['po_warehouse']==7 ){
                $findStock->fbl909_stocks_intransit = $val['final_order_qty'];
            }
            $findStock->update();
        }
    }
    public function actionUpdateFinalOrderQuantity(){
        $findOne = PoDetails::findOne(['po_id'=>$_GET['po_id'],'sku_id'=>$_GET['sku']]);
        $findOne->final_order_qty = $_GET['qty'];
        $findOne->update();
    }
    public function actionTestJson(){
        $json = '{
"Sku":"ActiveSupplierTest001",
"Description":"Test API action",
"Classification":"Test Classification",
"Supplier":"Test Supplier",
"Brand":"Test Brand",
"Code":"78345483931",
"SupplierInfo":[
{
"SupplierName":"Test Supplier",
"IsActive":true,
"IsPrimary":true
}
]
}
';
        $arr = json_decode($json,1);
        $createProduct = SkuVault::createProduct(32,$arr);
        $this->debug($createProduct);
        //$this->debug($arr);
        //echo str_replace('&','<br />',urldecode(http_build_query($arr)));
        die;

    }
}
