<?php

namespace backend\controllers;

use backend\util\HelpUtil;
use Yii;
use common\models\Modules;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ModulesController implements the CRUD actions for Modules model.
 */
class ModulesController extends MainController
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
            ]
        ];
    }

    /**
     * Lists all Modules models.
     * @return mixed
     */
    public function actionIndex()
    {
        /*$dataProvider = new ActiveDataProvider([
            'query' => Modules::find(),
        ]);*/
        $modules=Modules::find()->asArray()->all();

        return $this->render('index', [
            'data' =>($modules) ? Yii::$app->permissionCheck->make_module_tree($modules):NULL,
        ]);
    }

    /**
     * Displays a single Modules model.
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
     * Creates a new Modules model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Modules();
        if(Yii::$app->request->post())
        {
            $model->load(Yii::$app->request->post());
            if(!$model->validate() || !$model->save())
            {
                $errors = $model->getFirstErrors();
                return json_encode(array('status'=>'failure','msg'=>reset($errors)));
            }  else {
                    Yii::$app->session->setFlash('success','Module added');
                    return json_encode(array('status'=>'success','msg'=>'Added'));
                }
        }

        $modules=Modules::find()->asArray()->all();
        return $this->render('create', [
            'model' => $model,
            'modules'=>($modules) ? Yii::$app->permissionCheck->make_module_tree($modules):NULL,
            'controllers_list'=>HelpUtil::getControllersAndActions(),
        ]);
    }

    /**
     * Updates an existing Modules model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
       die('not allowed yet');
        $model = $this->findModel($id);
        $modules=Modules::find()->asArray()->all();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'modules'=>($modules) ? Yii::$app->permissionCheck->make_module_tree($modules):NULL,
            'controllers_list'=>HelpUtil::getControllersAndActions(),
        ]);
    }

    /**
     * Deletes an existing Modules model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success','Deleted');
        return $this->redirect(['index']);
    }

    /**
     * Finds the Modules model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Modules the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Modules::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
