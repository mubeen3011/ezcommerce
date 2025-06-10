<?php

namespace backend\controllers;

use Yii;
use common\models\Settings;
use common\models\search\SettingsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SettingsController implements the CRUD actions for Settings model.
 */
class SettingsController extends GenericGridController
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
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Settings models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SettingsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionGeneric(){
        $status = array(
            ['key'=>1,'value'=>'Yes'],
            ['key'=>0,'value'=>'No']
        );
        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/settings/generic-info',
                    'sortUrl' => '/settings/generic-info-sort',
                    'filterUrl' => '/settings/generic-info-filter',
                    'jsUrl'=>'/settings/generic',
                ],
                'thead'=>
                    [

                        'Name' => [
                            'data-field' => 's.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 's.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Action' => [
                            'data-field' => 'setting_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'setting_id',
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
                'FirstQuery'=>"select s.name,s.id as setting_id from settings s where on_screen = 1 ",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY name ASC',
            'SortOrderByColumnAlias' => 's',
        ];
        return $config;
    }

    /**
     * Displays a single Settings model.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Settings model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Settings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Settings model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->orgName = $model->name;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['/settings/generic']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Settings model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Settings model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Settings the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Settings::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}
