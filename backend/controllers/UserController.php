<?php

namespace backend\controllers;

use common\models\Warehouses;
use Yii;
use common\models\User;
use common\models\search\UserSearch;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends  GenericGridController
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
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionGeneric()
    {

        $config =
            ['UrlSetting'=>
                [
                    'defualtUrl' => '/user/generic-info',
                    'sortUrl' => '/user/generic-info-sort',
                    'filterUrl' => '/user/generic-info-filter',
                    'jsUrl'=>'/user/generic',
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
                        'Username' => [
                            'data-field' => 'u.username',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'u.username',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Fullname' => [
                            'data-field' => 'u.fullname',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'u.fullname',
                            'label' => 'show',
                            'data-filter-type' => 'like',
                            'input-type' => 'text',
                            'input-type-class' => ''
                        ],
                        'Action' => [
                            'data-field' => 'uuser_id',
                            'data-sort' => 'desc',
                            'data-filter-field' => 'uuser_id',
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
                'FirstQuery'=>"select ur.name,u.username,u.full_name,u.id as uuser_id from user u
inner join user_roles ur on
ur.id = u.role_id",
                'GroupBy' => ''
            ],
            'OrderBy_Default'=>'ORDER BY u.id ASC',
            'SortOrderByColumnAlias' => 'u',
        ];
        return $config;
    }
    /**
     * Displays a single User model.
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
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();
        if(Yii::$app->request->post())
        {
            $model->repeat_password=Yii::$app->request->post('repeat_password');
        }
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            if(isset($_POST['warehouse']) && $_POST['warehouse'] > 0)  // if distributor selected and assigned warehouse
            {
                Yii::$app->db->createCommand()->update('warehouses',['user_id'=>$model->id],['id'=>$_POST['warehouse']])->execute();
            }
            return $this->redirect(['view', 'id' => $model->id]);
        }
        else
        {
            return $this->render('create', [
                'model' => $model,
                'warehouses'=>Warehouses::find()->where(['is_active'=>1,'user_id'=>null])->asArray()->all(),
            ]);
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id,$password_expired=false)
    {
        $model = $this->findModel($id);
        $model->scenario='UPDATE_PROFILE';
        if(Yii::$app->request->post())
        {
            $model->repeat_password=Yii::$app->request->post('repeat_password');

        }
        if ($model->load(Yii::$app->request->post())) {
            if($model->update_password!=""){
                $comparePassword = User::ValidateLastFourPasswordsAndCompareNewPassword($model->id,$model->update_password);
                if(!$comparePassword){
                    Yii::$app->session->setFlash('success', "<h4 style='color: red;'>You cannot use your last 4 passwords.</h4>");
                    return $this->redirect(['update', 'id' => $model->id]);
                }
            }
            if($model->save())
                Yii::$app->session->setFlash('success', "<h4 style='color: green;'>Profile updated successfully.</h4>");
            else
                Yii::$app->session->setFlash('success', "<h4 style='color: red;'>Error While updating profile.</h4>");
            return $this->redirect(['update', 'id' => $model->id]);
        }
        else {
            if($password_expired=="true")
                Yii::$app->session->setFlash('success', "<h4 style='color: red;'>Your password is expired kindly update your password</h4>");
            return $this->render('update', ['model' => $model,]);
        }
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->db->createCommand()->update('warehouses',['user_id'=>NULL],['user_id'=>$id])->execute(); // if warehouse assigned to any user delete that
        $this->findModel($id)->delete();
        return $this->redirect(['generic']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
