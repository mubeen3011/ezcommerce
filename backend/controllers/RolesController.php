<?php

namespace backend\controllers;

use Yii;
use common\models\UserRoles;
use common\models\search\UserRolesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RolesController implements the CRUD actions for UserRoles model.
 */
class RolesController extends GenericGridController
{
    /**
     * @inheritdoc
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
     * Lists all UserRoles models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserRolesSearch();
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
                    'defualtUrl' => '/roles/generic-info',
                    'sortUrl' => '/roles/generic-info-sort',
                    'filterUrl' => '/roles/generic-info-filter',
                    'jsUrl'=>'/roles/generic',
                ],
                'thead'=>
                    [

                        'Name' => [
                            'data-field' => 'ur.name',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ur.name',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Status' => [
                            'data-field' => 'ur.status',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'ur.status',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'select',
                            'options'=>$status,
                            'input-type-class' => ''
                        ],
                        'Action' => [
                            'data-field' => 'role_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'role_id',
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
                'FirstQuery'=>"select ur.name,ur.status,ur.id as role_id from user_roles ur where 1=1 ",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY name ASC',
            'SortOrderByColumnAlias' => 'ur',
        ];
        return $config;
    }
    /**
     * Displays a single UserRoles model.
     * @param integer $id
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
     * Creates a new UserRoles model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UserRoles();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing UserRoles model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing UserRoles model.
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
     * Finds the UserRoles model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return UserRoles the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserRoles::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
