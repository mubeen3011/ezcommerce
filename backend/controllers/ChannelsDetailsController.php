<?php

namespace backend\controllers;

use backend\util\CategoryUtil;
use common\models\Category;
use common\models\Channels;
use common\models\User;
use Yii;
use common\models\ChannelsDetails;
use common\models\search\ChannelsDetailsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ChannelsDetailsController implements the CRUD actions for ChannelsDetails model.
 */
class ChannelsDetailsController extends GenericGridController
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
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all ChannelsDetails models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ChannelsDetailsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('generic', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionGeneric()
    {
        $DealsMakerChannels="SELECT c.id as `key`,c.name as `value` FROM channels c where c.is_active = 1 group by c.id";
        $ChannelList = ChannelsDetails::findBySql($DealsMakerChannels)->asArray()->all();
        $categories="SELECT c.id as `key`,c.name as `value` FROM category c group by c.id";
        $CategoryList = ChannelsDetails::findBySql($categories)->asArray()->all();
        $users="SELECT u.id as `key`,u.full_name as `value` FROM user u group by u.id";
        $UsersList = User::findBySql($users)->asArray()->all();
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/channels-details/generic-info',
                    'sortUrl' => '/channels-details/generic-info-sort',
                    'filterUrl' => '/channels-details/generic-info-filter',
                    'jsUrl'=>'/channels-details/generic',
                ],
                'thead'=>
                    [

                        'Shop Name' => [
                            'data-field' => 'cd.channel_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'cd.channel_id',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $ChannelList,
                            'input-type-class' => ''
                        ],
                        'Category Name' => [
                            'data-field' => 'cat.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'cat.id',
                            'data-filter-type' => 'operator',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $CategoryList,
                            'input-type-class' => ''
                        ],
                        'Commision' => [
                            'data-field' => 'cd.commission',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'commission',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Payment Cost' => [
                            'data-field' => 'cd.pg_commission',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'pg_commission',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'shipping' => [
                            'data-field' => 'cd.shipping',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'shipping',
                            'label' => 'show',
                            'data-filter-type' => 'operator',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Updated by' => [
                            'data-field' => 'u.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'u.id',
                            'data-filter-type' => 'like',
                            'label' => 'show',
                            'input-type' => 'select',
                            'options' => $UsersList,
                            'input-type-class' => ''
                        ]
                        ,
                        'Action' => [
                            'data-field' => 'cd.id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'cd.id',
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
        return $this->render('generic-view',['gridview'=>$html,'roleId' => $roleId]);
    }
    public function actionConfigParams(){
        $config=[
            'query'=>[
                'FirstQuery'=>"select  

c.name as channel_name,
cat.name as cat_name,
cd.commission,
cd.pg_commission,
cd.shipping,
u.full_name as updated_by,
cd.id as cdid,
c.id as channel_id
from channels_details cd
inner join channels c on
c.id = cd.channel_id
inner join category cat on
cat.id = cd.category_id
inner join user u on
u.id = cd.updated_by where 1=1 ",
                'GroupBy' => ''
            ],
                'OrderBy_Default'=>'ORDER BY cdid DESC',
            'SortOrderByColumnAlias' => 'cd',
        ];
        return $config;
    }

    /**
     * Displays a single ChannelsDetails model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new ChannelsDetails model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new ChannelsDetails();
        /*$categoryList = Category::find()->where(['is_active'=>1])->asArray()->all();
        $categoryList = $this->_MakeDropDownList($categoryList,'id','name');*/
        $Channels = Channels::find()->where(['is_active'=>1])->asArray()->all();
        $Channels = $this->_MakeDropDownList($Channels,'id','name');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['generic', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
                /*'categoryList' => $categoryList,*/
                'channels' => $Channels
            ]);
        }
    }
    public function GetChannelsCategoryComissionsNotDefined( $channel_id ){
        $channel_commissions = CategoryUtil::GetChannelComissions($channel_id);
        $channel_commissions = $this->_GetIndexFromQueryData($channel_commissions,'category_id');
        $categories = Category::find()->where(['NOT IN','id',$channel_commissions])->asArray()->all();
        return $categories;
    }
    public function actionGetCategoryList(){
        $cat_list = $this->GetChannelsCategoryComissionsNotDefined($_GET['channel_id']);
        $cats_detail= $this->_MakeDropDownList($cat_list,'id','name');
        $dropdown='';
        $dropdown .='<option value="">Select Category</option>';
        foreach ( $cats_detail as $key=>$value ){
            $dropdown .= "<option value='$key'>$value</option>";
        }
        return $dropdown;
    }
    /**
     * Updates an existing ChannelsDetails model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save(false)) {
            return $this->redirect(['generic', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing ChannelsDetails model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['generic']);
    }

    /**
     * Finds the ChannelsDetails model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return ChannelsDetails the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ChannelsDetails::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
