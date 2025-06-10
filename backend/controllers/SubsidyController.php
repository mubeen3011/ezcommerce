<?php

namespace backend\controllers;

use backend\util\HelpUtil;
use common\models\Category;
use common\models\User;
use Yii;
use common\models\Subsidy;
use common\models\search\SubsidySearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * SubsidyController implements the CRUD actions for Subsidy model.
 */
class SubsidyController extends Controller
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
     * Lists all Subsidy models.
     * @return mixed
     */
    public function actionIndex()
    {
        $curChannel = Yii::$app->request->get('c');
        $curChannel = ($curChannel != '') ? $curChannel : '2';
        $searchModel = new SubsidySearch();
        $searchModel->channel_id = $curChannel;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Subsidy model.
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
     * Creates a new Subsidy model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Subsidy();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Subsidy model.
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
     * Deletes an existing Subsidy model.
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
     * Finds the Subsidy model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Subsidy the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Subsidy::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionSkus()
    {
        /*$this->debug($_GET);*/
        $subsidy = HelpUtil::getSubsidySkus();
        $Categories=Category::findBySql("select * from category where main_category_id is null;")->asArray()->all();
        return $this->render('skus', ['subsidy' => $subsidy['refine'], 'skus' => $subsidy['skus'], 'total_records' => $subsidy['total_records']
        ,'category'=>$Categories]);
    }
    public function actionGetSubCategories(){
        $Categories=Category::findBySql("select * from category where main_category_id is null;")->asArray()->all();
    }
    public function actionGetAllSubCategories(){
        $cat_id=$_GET['catid'];
        $SubCategories=Category::find()->where(['main_category_id'=>$cat_id])->asArray()->all();
        $html = '<option></option>';
        foreach ( $SubCategories as $value ){
            $html .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
        }
        echo $html;
        /*echo '<pre>';
        print_r($SubCategories);
        die;*/
    }
    public function actionPaginationSetting(){
        $page = $_GET['page'];
        $reverse = $page - 3;
        if( $reverse < 1 ){
            $reverse = 1;
        }
        $forward = $page + 3;
        if( $forward > 100 ){
            $forward  = 100;
        }
        for ( $i=$reverse;$i<=$forward;$i++ ){
            echo $i;
            echo '<br />';
        }
    }
    public function actionAssign()
    {
        $userId = Yii::$app->request->post('user');
        $skus = Yii::$app->request->post('users_skus');
        $asku = explode(',', $skus);


        // remove selected SKU from other Users
        $users = User::find()->where(['role_id' => 2])->andWhere(['<>', 'id', $userId])->all();
        foreach ($users as $user) {

            $userSkus = $user->skus;
            foreach ($asku as $sk) {
                $userSkus = str_replace($sk . ',', '', $userSkus);
                $userSkus = str_replace($sk, '', $userSkus);
            }

            $user->skus = $userSkus;
            $user->update(false);


        }

        $s = [];
        $model = User::findOne(['id' => $userId]);
        if ($model->skus) {
            $s = explode(',', $model->skus);
            foreach ($asku as $sk) {
                if (!in_array($sk, $s)) {
                    $s[] = $sk;
                }
            }
        } else {
            foreach ($asku as $sk) {
                $s[] = $sk;
            }
        }
        $model->skus = implode(',', $s);
        $model->update(false);


        $this->redirect(['skus']);
    }


    public function actionSaveRecord()
    {
        $fields = Yii::$app->request->post();
        $skuId = $fields['csku'];


        $include = [1, 2, 3, 5, 6, 9, 10, 11];
        $channelList = HelpUtil::getChannels($include);

        foreach($channelList as $cl)
        {
            $subsidy = Subsidy::find()->where(['sku_id'=>$skuId,'channel_id'=>$cl['id']])->one();
            if(!$subsidy)
                $subsidy = new Subsidy();

            $subsidy->subsidy = isset($fields['subsidy_'.$cl['id']]) ? $fields['subsidy_'.$cl['id']] : '0';
            $subsidy->ao_margins = isset($fields['aom_'.$cl['id']]) ? $fields['aom_'.$cl['id']] : '0';
            $subsidy->margins = isset($fields['m_'.$cl['id']]) ? $fields['m_'.$cl['id']] : '0';
            $subsidy->start_date = isset($fields['sd_'.$cl['id']]) ? $fields['sd_'.$cl['id']] : '0';
            $subsidy->end_date = isset($fields['ed_'.$cl['id']]) ? $fields['ed_'.$cl['id']] : '0';
            $subsidy->sku_id = $skuId;
            $subsidy->channel_id = $cl['id'];
            $subsidy->updated_by = Yii::$app->user->id;
            $subsidy->save(false);
        }

        echo json_encode(['success'=>1]);
    }
}
