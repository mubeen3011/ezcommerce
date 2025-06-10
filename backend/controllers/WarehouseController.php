<?php

namespace backend\controllers;

use backend\util\HelpUtil;
use backend\util\WarehouseUtil;
use common\models\Channels;
use common\models\Products;
use common\models\WarehouseChannels;
use common\models\Warehouses;
use common\models\WarehouseStockList;
use common\models\WarehouseStockLog;
use common\models\Zipcodes;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * WarehouseController implements the CRUD actions for warehouses model.
 */
class WarehouseController extends GenericGridController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all warehouses models.
     * @return mixed
     */
    public function actionIndex()
    {
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/warehouse/generic-info',
                    'sortUrl' => '/warehouse/generic-info-sort',
                    'filterUrl' => '/warehouse/generic-info-filter',
                    'jsUrl'=>'/warehouse/index',
                ],
                'thead'=>
                    [
                        'ID' => [
                            'data-field' => 'w.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.id',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'hidden',
                            'input-type-class' => ''
                        ],
                        'Name' => [
                            'data-field' => 'w.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Prefix' => [
                            'data-field' => 'w.prefix',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.prefix',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Status' => [
                            'data-field' => 'w.is_active',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.is_active',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => [['key'=>1,'value'=>'Active'],['key'=>0,'value'=>'Inactive']],
                            'input-type-class' => ''
                        ],
                        'Channels' => [
                            'data-field' => 'channel_binded',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'channel_binded',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'hidden',
                            'input-type-class' => ''
                        ],
                        'T1' => [
                            'data-field' => 'w.t1',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.t1',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'T2' => [
                            'data-field' => 'w.t2',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.t2',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Transit Days' => [
                            'data-field' => 'w.transit_days',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.transit_days',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Action' => [
                            'data-field' => 'w.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'w.id',
                            'data-filter-type' => 'operator',
                            'label' => 'hidden',
                            'input-type' => 'hidden',
                            'input-type-class' => '',
                            'actions' => [
                                'edit' => '/warehouse/update?',
                            ]
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
        $roleId = Yii::$app->user->identity->role_id;
        return $this->render('generic-view',['gridview'=>$html,'roleId' => $roleId]);
    }

    public function actionConfigParams(){
        $w = HelpUtil::getWarehouseDetail();
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT w.id,w.name,w.prefix,w.is_active, GROUP_CONCAT(c.NAME) AS channel_binded,w.t1,w.t2,w.transit_days, w.id AS wid  
                                FROM warehouses w
                                LEFT JOIN warehouse_channels wc ON
                                w.id = wc.warehouse_id
                                LEFT JOIN channels c ON
                                c.id = wc.channel_id WHERE 1=1  AND w.id in($w)",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'GROUP BY w.name',
            'SortOrderByColumnAlias' => 'w',
        ];
        return $config;
    }

    /**
     * Displays a single warehouses model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $WarehouseChannels = WarehouseUtil::GetWarehouseChannelsNames($id);
        $WarehouseZipCodes = WarehouseUtil::GetWarehouseAssignZipCodes($id);
        $model=$this->findModel($id);
        return $this->render('view', [
            'model' => $model,
            'WarehouseChannels'=>$WarehouseChannels,
            'AssignedAreas'=>$WarehouseZipCodes
        ]);
    }

    /**
     * Creates a new warehouses model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Warehouses();

        if(Yii::$app->request->post())
        {
            $model->configuration=isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']):NULL;
            $model->settings = isset($_POST['Settings']) ? json_encode($_POST['Settings']) : '';
        }
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model->prefix=strtoupper(str_replace(' ','-',substr($model->name,0,3)).$model->id); // set prefix
            $model->update();
            $this->WarehouseChannelsMapping($model->id); // map warehouses with channels in bridge table

            if (isset($_POST['zip_codes']))
                WarehouseUtil::SaveZipCodes($model->id, $_POST['zip_codes']);
            else
                WarehouseUtil::SaveZipCodes($model->id, []);

            yii::$app->session->setFlash('success','created');
            return $this->redirect(['warehouse/index']);
        }
        $channels = Channels::find()->where(['is_active'=>1])->all();
        $CS = WarehouseUtil::GetCountryStates();

        return $this->render('create', [
            'model' => $model,
            'channels' => $channels,
            'CS' => $CS
        ]);
    }
    private function WarehouseChannelsMapping($wid){

        WarehouseChannels::deleteAll(['warehouse_id'=>$wid]);
        $post_data = Yii::$app->request->post();
        if ( isset($post_data['Warehouses']['channels']) ){
            foreach ( $post_data['Warehouses']['channels'] as $val ){
                $map_warehouse = new WarehouseChannels();
                $map_warehouse->channel_id = $val;
                $map_warehouse->warehouse_id = $wid;
                $map_warehouse->is_active = $post_data['Warehouses']['is_active'];
                $map_warehouse->added_at = date('Y-m-d H:i:s');
                $map_warehouse->updated_at = date('Y-m-d H:i:s');
                $map_warehouse->save();
            }

        }

    }
    /**
     * Updates an existing warehouses model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {

        $model = $this->findModel($id);
        $channels = HelpUtil::WarehouseGetChannels();

        if(Yii::$app->request->post())
        {
           // self::debug(Yii::$app->request->post());
            if ( $_POST['Warehouses']['warehouse']!='amazon-fba' && $_POST['Warehouses']['warehouse']!='lazada-fbl' ){
                $model->configuration=isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']):NULL;
            }

            //$this->debug($_POST);
            if ( isset($_POST['Settings']['StockListBadges'] )){
                $decode= json_decode($model->settings,1);
                $decode['StockListBadges']['DaysListBadges'] = $_POST['Settings']['StockListBadges']['DaysListBadges'];
                $decode['StockListBadges']['DaysLevelRanges'] = $_POST['Settings']['StockListBadges']['DaysLevelRanges'];
                $model->settings = json_encode($decode);
            }
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $this->WarehouseChannelsMapping($model->id);
            if ( !isset($_POST['zipcodes']) )
                WarehouseUtil::SaveZipCodes($model->id, []);
            else
                WarehouseUtil::SaveZipCodes($model->id, $_POST['zipcodes']);

            yii::$app->session->setFlash('success','Updated');
            return $this->redirect(['warehouse/index']);
        }

        $channel_mapping = $this->getList('channel_id','warehouse_channels',0,' WHERE warehouse_id = '.$id);


        $CS = WarehouseUtil::GetCountryStates();
        $pre_selected_zip = WarehouseUtil::GetWarehouseStatesAndZipForUpdate($model->id);


        return $this->render('update', [
            'model' => $model,
            'channels' => $channels,
            'c_w_mapping'=>array_keys($channel_mapping),
            'CS' => $CS,
            'pre_selected_zip'=>$pre_selected_zip
        ]);
    }
    public function actionGetStateZipCodes(){

        $response=[];
        $Zipcodes=Zipcodes::find()->where(['state_id'=>$_POST['state_id']])->orderBy(['city_name' => SORT_ASC])->asArray()->all();
        $html = $this->renderPartial('assign-orders-areas/state-zip-codes',['zipcodes'=>$Zipcodes,'state_id'=>$_POST['state_id']]);
        $response['content']=$html;
        return json_encode($response);

    }
    /**
     * Deletes an existing warehouses model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the warehouses model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return warehouses the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Warehouses::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public static function IsisStockSetFormat( $list ){

        $testFormat = [];
        foreach ( $list as $key=>$val ){
            /*$testFormat[$key]['sku'] = $val['storageClientSkuNo'];
            $testFormat[$key]['available'] = $val['availableQty'];*/
            $testFormat[$val['storageClientSkuNo']][] = $val['availableQty'];
        }

        $finalFormat = [];
        foreach ( $testFormat as $sku=>$stocksArr ){
            $detail=[];
            $detail['sku']=$sku;
            $detail['available']=max($stocksArr);
            $finalFormat[] = $detail;
        }

        return $finalFormat;
    }


    public static function SabStockSetFormat( $list ){

//        echo "<pre>"; print_r($list['d']['results'][0]);exit;
//
        $finalFormat = [];
        foreach($list['d']['results'][0] as $value) {
            echo $value['Material']." ". $value['Batch']." <br> ";
         exit;
            $detail=[];
            $detail['sku'] = $value['Material'];
            $detail['available']=$value('MatlWrhsStkQtyInMatlBaseUnit');
            $finalFormat[] = $detail;

        }


        return $finalFormat;
    }

    public static function WarehouseSaveStocks( $warehouse_id, $list ,$set_zero_stock)
    {
        if(empty($list))
            return;

        $response = [];
        $updateToskus = [];

        foreach ( $list as $key=>$value ){
            if(empty($value['sku']))
                continue;

            $findProduct = WarehouseStockList::find()->where(['sku'=>$value['sku'],'warehouse_id'=>$warehouse_id])->one();
            $updateToskus[] = $value['sku'];

            if ( !$findProduct ){

                $addInWh = new WarehouseStockList();
                $addInWh->warehouse_id = $warehouse_id;
                $addInWh->sku = $value['sku'];
                $addInWh->available = $value['available'];
                $addInWh->added_at = date('Y-m-d H:i:s');
                $addInWh->updated_at = date('Y-m-d H:i:s');
                $addInWh->save();

                if (empty($addInWh->errors))
                    $response['added'][$value['sku']] = 'SUCCESS';
                else
                    $response['added'][$value['sku']] = json_encode($addInWh->errors);


            }elseif($findProduct->available!=$value['available']){

                $findProduct->available = $value['available'];
                $findProduct->updated_at = date('Y-m-d H:i:s');
                $findProduct->update();
                if (empty($findProduct->errors))
                    $response['updated'][$value['sku']] = 'Successfully Updated';
                else
                    $response['updated'][$value['sku']] = json_encode($findProduct->errors);
            }

        }

        if($set_zero_stock){ // update if need
            self::SetZeroStock($warehouse_id,$updateToskus);// Update skus to 0 which api don't giving the data to update.
        }

        return $response;

    }
    public static function SetZeroStock($warehouseId,$updatedlist){
       // $IN = '"'.implode('","',$updatedlist ).'"';
        //$sql = 'SELECT * FROM warehouse_stock_list WHERE sku NOT  IN ('.$IN.') AND warehouse_id = '.$warehouseId;
        //echo $sql;die;
       /*WarehouseStockList::deleteAll(['and',
               [ 'warehouse_id'=>$warehouseId],
               ['not in', 'sku', $updatedlist]]
       );*/
        $GetResults = WarehouseStockList::find()->Where(['not in', 'sku', $updatedlist])->andWhere(['warehouse_id'=>$warehouseId])->asArray()->all();
       // self::debug($GetResults);
       // $GetResults = WarehouseStockList::findBySql($sql)->asArray()->all();
       // self::debug($GetResults);
        foreach ( $GetResults as $value ){
            $updateStock = WarehouseStockList::findOne($value['id']);
            if($updateStock->available > 0):
                $updateStock->available =0;
                $updateStock->updated_at = date('Y-m-d H:i:s');
                $updateStock->update();
            endif;
        }
        return;
       // die();
    }
    public static function saveLog( $warehouse_id, $response ){

        $saveLog = new WarehouseStockLog();
        $saveLog->warehouse_id = $warehouse_id;
        $saveLog->response = json_encode($response);
        $saveLog->added_at = date('Y-m-d H:i:s');
        $saveLog->save();


    }

    public static function SkuVaultStockSetFormat( $list ){

        $reFormat = [];
        foreach ($list->ItemQuantities as $key=>$val)
            $reFormat[]=['sku'=>$val->Sku,'available'=>$val->Quantity];


        return $reFormat;
    }


}
