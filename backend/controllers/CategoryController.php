<?php

namespace backend\controllers;

use app\components\PermissionComponent;
use backend\util\CategoryUtil;
use backend\util\HelpUtil;
use kcfinder\session;
use Yii;
use common\models\Category;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\filters\AccessControl;

/**
 * CategoryController implements the CRUD actions for Category model.
 */
class CategoryController extends MainController
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
     * Lists all Category models.
     * @return mixed
     */
    public function actionIndex()
    {
        $data=Category::find()->asArray()->all();
        $data=Yii::$app->permissionCheck->make_module_tree($data);
        $data=self::make_tree_display($data);

        return $this->render('index', [
            'data' => $data,
        ]);
    }



    private function make_tree_display($data,$spaces=null)
    {
        $out=NULL;
        $is_active=['No','Yes'];
        foreach($data as $v){
            $out .= '<tr>';
            $out .= '<td>'.$v['id'].'</td>';
            $out .= '<td>'.$spaces.$v['name'].'</td>';
            $out .= '<td>'.$v['parent_id'].'</td>';
            $out .= '<td>'.$is_active[$v['is_active']] .'</td>';
            $out .= '<td>'.
                      Html::a('', ['update', 'id' => $v['id']], ['class' => 'fa fa-edit','style'=>'color:#00B1D5'])
                        ."&nbsp&nbsp".
                    Html::a('', ['delete', 'id' => $v['id']], ['class' => 'fa fa-trash','style'=>'color:red','data' => [
                        'confirm' => 'Are you sure you want to delete this item?',
                        'method' => 'post',
                    ],])

                .'</td>';

            if(isset($v['children']) && is_array($v['children'])){
              //  $out .= '<tr>'.self::make_tree_display($v['children']).'</tr>';
                $space_count=$v['parent_id'] ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp-->':'&nbsp;&nbsp--> '; // check if 2nd level or 3rd level cat
                $out .= self::make_tree_display($v['children'],$space_count);
            }
            $out .= '</tr>';
        }

       return $out;
    }
    /**
     * Displays a single Category model.
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
     * Creates a new Category model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Category();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success','Added');
            return $this->redirect(['create']);
        }
        $categories=Category::find()->asArray()->all();
        $categories=HelpUtil::make_child_parent_tree($categories);
        return $this->render('create', [
            'model' => $model,
            'cat_list'=>$categories
        ]);
    }

    /**
     * Updates an existing Category model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success','Updated');
            return $this->redirect(['update', 'id' => $model->id]);
        }
        $categories=Category::find()->asArray()->all();
        $categories=HelpUtil::make_child_parent_tree($categories);

        return $this->render('update', [
            'model' => $model,
            'cat_list'=>$categories
        ]);
    }

    /**
     * Deletes an existing Category model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success','deleted');
        return $this->redirect(['index']);
    }

    public function actionDownloadCategriesCsv()
    {
        $list=Category::find()->with('parent')->asArray()->all();
        $download=CategoryUtil::downloadCsv($list);
    }

    /**
     * Finds the Category model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Category the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Category::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
