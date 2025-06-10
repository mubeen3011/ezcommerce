<?php

namespace backend\controllers;

use common\models\CostPrice;
use common\models\CostPriceLog;
use Yii;
use common\models\SkuMarginSettings;
use common\models\search\SkuMarginSettingsSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SkuMarginSettingsController implements the CRUD actions for SkuMarginSettings model.
 */
class SkuMarginSettingsController extends Controller
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
     * Lists all SkuMarginSettings models.
     * @return mixed
     */
    public function actionIndex()
    {
        $skuList = CostPrice::find()->orderBy('id asc')->with(['subCategory'])->asArray()->all();
        $skuSettings = SkuMarginSettings::find()->asArray()->all();
        $settingList = [];
        foreach($skuSettings as $ss)
        {
            $settingList[$ss['sku_id']] = ['price'=>$ss['price'],'type'=>$ss['type']];
        }

        return $this->render('index', [
            'skuList' => $skuList,
            'settingList' => $settingList,
        ]);
    }

    /**
     * Displays a single SkuMarginSettings model.
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
     * Creates a new SkuMarginSettings model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SkuMarginSettings();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing SkuMarginSettings model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing SkuMarginSettings model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SkuMarginSettings model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return SkuMarginSettings the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SkuMarginSettings::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionSave()
    {
        $fields = Yii::$app->request->post();
        $skuId = $fields['sku_id'];
        $price = isset($fields['price_'.$skuId]) ? $fields['price_'.$skuId] : '';
        $type = isset($fields['type_'.$skuId]) ? $fields['type_'.$skuId] : '';

        if($price && $type)
        {
            $sms = SkuMarginSettings::find()->where(['sku_id'=>$skuId])->one();
            $sms->price = $price;
            $sms->type = $type;
            $sms->update(false);
        }
        echo json_encode(['success'=>true]);
    }
}
