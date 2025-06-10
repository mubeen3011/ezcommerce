<?php

namespace backend\controllers;

use common\models\Modules;
use common\models\UserRoles;
use Yii;
use common\models\Permissions;
use yii\data\ActiveDataProvider;
use yii\rbac\Permission;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PermissionsController implements the CRUD actions for Permissions model.
 */
class PermissionsController extends MainController
{
    /**
     * {@inheritdoc}
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
     * Lists all Permissions models.
     * @return mixed
     */
    public function actionIndex()
    {
        /*$dataProvider = new ActiveDataProvider([
            'query' => Permissions::find(),
        ]);*/
        $roleId = Yii::$app->user->identity->role_id;
        /*$modules=\common\models\Permissions::find()->select('modules.id,modules.parent_id,modules.name,modules.icon,modules.id,modules.controller_id,modules.view_id,modules.params,permissions.create,permissions.update,permissions.view,permissions.delete')
            ->innerJoin('modules', '`modules`.`id` = `permissions`.`module_id`')
            ->orderBy(['modules.display_order'=>SORT_ASC])->asArray()->all();*/
        $modules=Modules::find()->asArray()->all();
        return $this->render('index', [
          //  'dataProvider' => $dataProvider,
            'roles' => UserRoles::find()->asArray()->all(),
            'modules' => Yii::$app->permissionCheck->make_module_tree($modules),
        ]);
    }

    public function actionGetRolePermissions()
    {
        $status=['status'=>'failure','list'=>'','msg'=>'NO permissions found!'];
        if(isset($_POST['role_id']))
        {
            $permisions=Permissions::find()->where(['role_id'=>$_POST['role_id']])->asArray()->all();
            if($permisions)
            {
                $status=['status'=>'success','list'=>$permisions];
            }
        }
        return $this->asJson($status);
    }

    public function actionUpdateRolePermissions()
    {
        $status=['status'=>'failure','msg'=>'Failed to update']; //default msg
        if(isset($_POST['module_id']) && isset($_POST['action_name']) && isset($_POST['role_id']))
        {
            $action_name=$_POST['action_name'];
            $permission=Permissions::find()->where(['role_id'=>$_POST['role_id'],'module_id'=>$_POST['module_id']])->one();
            if($permission)
            {

                $permission->$action_name=$_POST['action_value'];
                if ($permission->update())
                    $status=['status'=>'success','msg'=>'updated'];
                 else
                    $status=['status'=>'failure','msg'=>'failed to update'];


            }
            else
            {
                $permission=new Permissions();
                $permission->module_id=$_POST['module_id'];
                $permission->role_id=$_POST['role_id'];
                $permission->$action_name=$_POST['action_value'];
                if ($permission->save())
                    $status=['status'=>'success','msg'=>'updated'];
                else
                    $status=['status'=>'failure','msg'=>'failed to update'];
            }


        }
        return $this->asJson($status);

    }




    /**
     * Displays a single Permissions model.
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
     * Creates a new Permissions model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Permissions();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'roles' => UserRoles::find()->where(['status'=>'1'])->asArray()->all(),
        ]);
    }

    /**
     * Updates an existing Permissions model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Permissions model.
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
     * Finds the Permissions model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Permissions the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Permissions::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
