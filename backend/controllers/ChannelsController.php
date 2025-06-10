<?php

namespace backend\controllers;

use common\models\ExcludedSkus;
use common\models\ExcludedSkusLog;
use common\models\PhilipsCostPrice;
use common\models\WarehouseChannels;
use Yii;
use common\models\Channels;
use common\models\search\ChannelsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\web\UploadedFile;

/**
 * ChannelsController implements the CRUD actions for Channels model.
 */
class ChannelsController extends GenericGridController
{
    /**
     * @inheritdoc
     */
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
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Channels models.
     * @return mixed
     */
    public function actionIndex()
    {

        $where=isset($_GET) ? []:"1";
        if(isset($_GET['name']) && !empty($_GET['name']))
            $where[]=['name'=>$_GET['name']];
        if(isset($_GET['is_active']) && in_array($_GET['is_active'],['1','0']))
            $where[]=['is_active'=>$_GET['is_active']];
        if(isset($_GET['marketplace']) && !empty($_GET['marketplace']))
            $where[]=['marketplace'=>$_GET['marketplace']];
        if(isset($_GET['prefix']) && !empty($_GET['prefix']))
            $where[]=['prefix'=>$_GET['prefix']];


        $where=array_reduce($where,'array_merge',array());
        $data=Channels::find()->select(['id','name','is_active','prefix','marketplace'])->where($where)->asArray()->all();
        return $this->render('index', [
            'data' => $data,
        ]);
    }


    /**
     * Displays a single Channels model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if($request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'=> "Channels #".$id,
                'content'=>$this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer'=> Html::button('Close',['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                    Html::a('Edit',['update','id'=>$id],['class'=>'btn btn-primary','role'=>'modal-remote'])
            ];
        }else{
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new Channels model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */

    public function actionCreate()
    {
        $model = new Channels();
        if(Yii::$app->request->post())
        {
            $model->auth_params=isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']):NULL;
            $model->logo =UploadedFile::getInstance($model, 'logo');
            if($model->logo)
            {
                $image_name=str_replace(' ','_',$model->logo->baseName)."_".time() . '.' . $model->logo->extension;
                $model->logo->saveAs('logos/' . $image_name);
                $model->logo="logos/".$image_name;
            }


        }
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            yii::$app->session->setFlash('success','created');
            return $this->redirect(['channels/index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }



    /**
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model =  Channels::findOne(['id' => $id]);//$this->findModel($id);
        $warehouses_attached=WarehouseChannels::find()->where(['channel_id'=>$id])->with('warehouse')->asArray()->all();
        if(Yii::$app->request->post())
        {
            $current_image = $model->logo;
            $model->auth_params=isset($_POST['configuration']) && !empty($_POST['configuration']) ? json_encode($_POST['configuration']):NULL;

            //if(isset($_FILES['logo'])){
                 $model->logo =UploadedFile::getInstance($model, 'logo');
                 if($model->logo){
                     $image_name=str_replace(' ','_',$model->logo->baseName)."_".time() . '.' . $model->logo->extension;
                     $model->logo->saveAs('logos/' . $image_name);
                     $model->logo="logos/".$image_name;
                     if($current_image && file_exists($current_image))
                         unlink($current_image);
                 }else{
                     $model->logo=$current_image;
                 }

           // }

        }
        if ($model->load($request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Record updated");
            return $this->redirect(['update', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'attached_warehouses'=>$warehouses_attached
            ]);
        }

    }

    public function actionToggleActive()
    {
        $result=NULL;
        if(isset($_POST['channel_id']) && $_POST['channel_id'] && isset($_POST['current_state']))
        {
            $result= Yii::$app->db->createCommand()
                ->update('channels', ['is_active' =>$_POST['current_state']],['id'=>$_POST['channel_id']])
                ->execute();
        }
        return json_encode(['status'=>$result ?'success':'failure' ,'msg'=>$result ? 'Updated':'Failed to update'  ]);
    }

    public function actionStockUploadLimitToggle()
    {
        $wc=WarehouseChannels::findOne(['id'=>$_POST['pk_id']]);
        if($wc)
        {
            $wc->stock_upload_limit_applies=$_POST['action_value'];
            $wc->update();
            return $this->asJson(['status'=>'success','msg'=>'updated']);
        }
        return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }
    /**
     * Delete an existing Channels model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing Channels model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post( 'pks' )); // Array or selected records primary keys
        foreach ( $pks as $pk ) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>'#crud-datatable-pjax'];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Channels model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Channels the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Channels::findOne(['id' => $id,'is_active'=>'1'])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    public function actionExcludedSkus(){

        $DealsMakerChannels="SELECT c.id as `key`,c.name as `value` FROM channels  c where c.is_active=1 group by c.id";
        $ChannelList = Channels::findBySql($DealsMakerChannels)->asArray()->all();
        //$category="SELECT c.id as `key`,c.name as `value` FROM category c group by c.id";
        //$CategoryList = Channels::findBySql($category)->asArray()->all();
        //$users="SELECT u.id as `key`,u.full_name as `value` FROM user u group by u.id";
        //$UsersList = User::findBySql($users)->asArray()->all();
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/channels/generic-info',
                    'sortUrl' => '/channels/generic-info-sort',
                    'filterUrl' => '/channels/generic-info-filter',
                    'jsUrl'=>'/channels/generic',
                ],
                'thead'=>
                    [

                        'Shop Name' => [
                            'data-field' => 'c.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.id',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $ChannelList,
                            'input-type-class' => ''
                        ],
                        '<span style="color: #479dff;">unsync stock skus</span>' => [
                            'data-field' => 'es.sku_stocks',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'es.sku_stocks',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        '<span  style="color: #479dff;">Unsync Stocks Status</span>' => [
                            'data-field' => 'es.stocks_sync',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'es.stocks_sync',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                        ,
                        '<span style="color: #019642;">unsync price skus</span>' => [
                            'data-field' => 'es.sku_price',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'es.sku_price',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        '<span style="color: #019642;">Unsync Price status</span>' => [
                            'data-field' => 'es.price_sync',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'es.price_sync',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Reason' => [
                            'data-field' => 'es.reason',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'es.reason',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ]
                        ,
                        'Action' => [
                            'data-field' => 'c.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'c.id',
                            'data-filter-type' => 'operator',
                            'label' => 'hidden',
                            'input-type' => 'hidden',
                            'input-type-class' => '',
                            'actions' => [
                                'edit' => '/deals-maker/update?'
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
        $sql2= "Select id,sku from products;";
        $run2=PhilipsCostPrice::findBySql($sql2)->asArray()->all();
        $redefine=[];
        foreach ( $run2 as $key=>$value )
            $redefine['skus'][]=$value['sku'];
        return $this->render('excluded-skus',['gridview'=>$html,'roleId' => $roleId,'skus_list'=>$redefine]);
    }

    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"SELECT c.name,es.sku_stocks,es.stocks_sync, es.sku_price, es.price_sync,es.reason,c.id
FROM excluded_skus es
RIGHT JOIN channels c on
es.shop_id = c.id 
where 1=1 AND c.is_active = 1 ",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY es.sku_stocks DESC',
            'SortOrderByColumnAlias' => 'es',
        ];
        return $config;
    }
    public function actionGetExcludedSkus(){
        $sql = "select * from excluded_skus where shop_id = ".$_GET['shop_id'];
        $run=Channels::findBySql($sql)->asArray()->all();
        if (!empty($run)){
            $redefine = ['excluded'=>$run[0]];
            $explodePriceSkus = explode(',',$redefine['excluded']['sku_price']);
            $explodeStockSkus = explode(',',$redefine['excluded']['sku_stocks']);
            $redefine['excluded']['sku_price']=($explodePriceSkus);
            $redefine['excluded']['sku_stocks']=($explodeStockSkus);
        }else{
            $redefine = [];
            $redefine['excluded']['stocks_sync']=0;
            $redefine['excluded']['price_sync']=0;
            $redefine['excluded']['sku_price']=[];
            $redefine['excluded']['sku_stocks']=[];
        }

        return json_encode($redefine);
    }
    private function addExcludedSkusLog(){
        $addLog = new ExcludedSkusLog();
        $addLog->shop_id=$_GET['shop_id'];
        $addLog->reason=$_GET['reason'];
        if (isset($_GET['stock_unsync']))
            $addLog->stocks_sync='1';
        else
            $addLog->stocks_sync='0';
        if (isset($_GET['price_unsync']))
            $addLog->price_sync='1';
        else
            $addLog->price_sync='0';
        $addLog->added_at = date('Y-m-d h:i:s');
        $addLog->sku_stocks = $_GET['stock_skus_list'];
        $addLog->sku_price = $_GET['price_skus_list'];
        $addLog->save();
    }
    public function actionUpdateExcludedSkus(){
        //$this->debug($_GET);
        $findShop = ExcludedSkus::findOne(['shop_id'=>$_GET['shop_id']]);
        //$this->debug($findShop);
        $this->addExcludedSkusLog();
        if ( empty($findShop) ){
            $addExcludedSkus = new ExcludedSkus();
            $addExcludedSkus->shop_id=$_GET['shop_id'];
            if ( isset($_GET['price_unsync']) )
                $addExcludedSkus->price_sync = '1';
            else
                $addExcludedSkus->price_sync = '0';
            if (isset($_GET['stock_unsync']))
                $addExcludedSkus->stocks_sync='1';
            else
                $addExcludedSkus->stocks_sync='0';
            $addExcludedSkus->sku_stocks = $_GET['stock_skus_list'];
            $addExcludedSkus->sku_price = $_GET['price_skus_list'];
            $addExcludedSkus->added_at = date('Y-m-d h:i:s');
            $addExcludedSkus->reason = $_GET['reason'];
            $addExcludedSkus->save();
            if ( empty($addExcludedSkus->errors) )
                return 1;
            else
                return 0;
        }
        else{
            $updateExcludedSkus = ExcludedSkus::findOne(['shop_id'=>$_GET['shop_id']]);
            $updateExcludedSkus->price_sync = isset($_GET['price_unsync']) ? '1' : '0' ;
            $updateExcludedSkus->stocks_sync = isset($_GET['stock_unsync']) ? '1' : '0' ;
            $updateExcludedSkus->sku_stocks = $_GET['stock_skus_list'];
            $updateExcludedSkus->sku_price = $_GET['price_skus_list'];
            $updateExcludedSkus->reason = $_GET['reason'];
            $updateExcludedSkus->update();
            if (empty($updateExcludedSkus->errors))
                return 1;
            else
                return 0;
        }

    }
}