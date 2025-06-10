<?php

namespace backend\controllers;
use backend\util\CourierUtil;
use backend\util\FedExUtil;
use backend\util\HelpUtil;
use backend\util\LazadaUtil;
use backend\util\LCSUtil;
use backend\util\MagentoUtil;
use backend\util\OrderShipmentUtil;
use backend\util\OrderUtil;
use backend\util\PrestashopUtil;
use backend\util\Product360Util;
use backend\util\ProductsUtil;
use backend\util\ShopeeUtil;
use backend\util\UspsUtil;
use common\models\BulkOrderShipment;
use common\models\Channels;
use common\models\Couriers;
use common\models\LoadSheetOrderList;
use common\models\OrderItems;
use common\models\OrderLoadSheets;
use common\models\Orders;
use common\models\OrderShipment;
use common\models\Products;
use common\models\search\OrderItemsSearch;
use common\models\Settings;
use common\models\WarehouseCouriers;
use common\models\Warehouses;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii;
use backend\util\UpsUtil;
use yii\web\UploadedFile;
class OrderShipmentController extends  MainController
{
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
            ]
        ];
    }

    public function actionIndex()
    {
        $object= new \backend\util\OrderShipmentUtil();
        $data=$object->getShipmentRecords();
        return $this->render('index', [
            'orders' => $data['orders'],
            'total_records' => $data['total_records'],
        ]);
    }


    /****
     * for  LCS will generate load sheets of shipped orders
     */
    public function actionLoadSheet()
    {
        $object= new \backend\util\OrderShipmentUtil();
        $data=$object->loadsheets();
        return $this->render('load_sheets', [
            'data' => $data,
           // 'total_records' => $data['total_records'],
        ]);
    }

    /***
     * orders whose sheets are to be generated
     */
    public function actionSheetPendingOrders()
    {
        $object= new \backend\util\OrderShipmentUtil();
        $data=$object->sheetPendingOrders();  // currently getting for lcs only
       // echo "<pre>";
       // print_r($data); die();
        $modal_content= $this->renderPartial('sheet_pending_orders',['data'=>$data]);
        return $this->asJson(['status'=>'success','msg'=>'record found','data'=>$modal_content]);
    }

    /****
     * @return yii\web\Response
     * get detail of that order which is in bulk progress
     */
    public function actionGetBulkOrderProgressDetail()
    {
        $order_id = yii::$app->request->post('order_id'); //table pk order ids // currently getting for lcs only
        if(!$order_id)
            return $this->asJson(['status'=>'failure','msg'=>'Failed to get','data'=>'Failed to get']);

        $detail=BulkOrderShipment::findOne(['order_id'=>$order_id]);
        $order=Orders::findOne(['id'=>$order_id]);
        $courier=Couriers::findOne(['id'=>$detail->courier_id]);
        $modal_content= $this->renderPartial('order-shipment-progress',['data'=>$detail,'order'=>$order,'courier'=>$courier]);
        return $this->asJson(['status'=>'success','msg'=>'record found','data'=>$modal_content]);
    }

    public function actionShippingQueueOrderChange()
    {
        $id = yii::$app->request->post('id');
        $action = yii::$app->request->post('action');
        if($id && $action)
        {
          if($action=="remove"){
              BulkOrderShipment::find()->where(['id'=>$id])->one()->delete();
              return $this->asJson(['status'=>'success','msg'=>'Removed from queue']);
          } elseif($action=="retry"){
              Yii::$app->db->createCommand()
                  ->update('bulk_order_shipment', ['status'=>'pending','comment'=>NULL], ['id'=>$id])
                  ->execute();
              return $this->asJson(['status'=>'success','msg'=>'ReQueued']);
          }

        }
        return $this->asJson(['status'=>'failure','msg'=>'Failed to process']);
    }

    /****
     * generate load sheet of shipped orders for lcs
     */
    public function actionGenerateLoadSheet()
    {
        $order_ids = yii::$app->request->post('order_ids'); //table pk order ids // currently getting for lcs only
        if(!$order_ids)
            return $this->asJson(['status'=>'failure','msg'=>'record found']);
        else
        {
            $courier=Couriers::findOne(['type'=>'lcs']);
            $tracking_numbers=CourierUtil::get_tracking_numbers_of_order($order_ids);
            /*echo "<pre>";
            print_r($tracking_numbers); die();*/
            if($tracking_numbers){
                $tracking_numbers=array_column($tracking_numbers,'tracking_number');
                $response=LCSUtil::generate_load_sheet($courier,$tracking_numbers);
                //echo "<pre>";
               // print_r($response); die();
                if($response['status']=='success' && isset($response['sheet_id']))
                {
                    $sheet=new \common\models\OrderLoadSheets();
                    $sheet->sheet_id=$response['sheet_id'];
                    $sheet->created_at=date('Y-m-d H:i:s');
                    $sheet->created_by=Yii::$app->user->identity->role_id;
                    $sheet->courier_id=$courier->id;
                    $sheet->save();
                    $sheet_pk_id=$sheet->id;
                    foreach($order_ids as $order_id){
                        $list=new LoadSheetOrderList();
                        $list->load_sheet_id=$sheet_pk_id;
                        $list->order_id=$order_id;
                        $list->save();

                    }

                }
                return $this->asJson($response);
            }
        }
        return $this->asJson(['status'=>'failure','msg'=>'failed to process']);
    }

    /******
     * downloadload sheet
     */
    public function actionDownloadSheet()
    {
        $sheet_id=yii::$app->request->get('sheet_id');
        $courier_id=yii::$app->request->get('courier');
        if(!$sheet_id && !$courier_id)
            die('sheet id required');

        $filepath='/order_load_sheets/'.$sheet_id .".pdf";
        if(file_exists($filepath)) {
            $this->download_util($filepath);

        } else{
            $courier=Couriers::findOne(['id'=>$courier_id]);
            $file=LCSUtil::download_load_sheet($courier,$sheet_id);
            if($file['status']=='success'){
                $filepath='order_load_sheets/'.$file['name'] ;
                $this->download_util($filepath);
            } else{
                echo "<pre>";
                print_r($file);
            }

        }
    }

    public function actionDownloadsheetInvoices()
    {

        ////////////////////////
        $sheet_pk_id=yii::$app->request->get('sheet_pk_id');
        if(!$sheet_pk_id)
            die('sheet id required');

        $challan_id=OrderLoadSheets::findOne(['id'=>$sheet_pk_id]);
        $object= new OrderShipmentUtil();
        $slips=$object->getLoadsheetSlips($sheet_pk_id);

        $zip=new \ZipArchive();
        $zip_name = $challan_id->sheet_id.".zip"; // Zip name
        $zip->open($zip_name,  \ZipArchive::CREATE);
        foreach ($slips as $slip) {
            $path = 'shipping-labels/'.$slip['slip'];
            if(file_exists($path)){
                $zip->addFromString(basename($path),  file_get_contents($path));
            }
        }
        $zip->close();
        $this->download_util($zip_name);

    }

    private function download_util($filepath,$delete_from_server=true)
    {
       // die($filepath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush(); // Flush system output buffer
        readfile($filepath);
        if($delete_from_server)
            unlink($filepath);
        die();
    }
}