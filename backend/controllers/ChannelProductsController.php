<?php

namespace backend\controllers;
use common\models\Products;
use yii;

use common\models\Channels;
use common\models\ChannelsProducts;
use common\models\Category;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class ChannelProductsController extends \yii\web\Controller
{
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
    public function actionIndex()
    {
        $products= \backend\util\ChannelProductsUtil::get_channel_products();
        $dd_categories=Category::find()->where(['is_active'=>1])->asArray()->all();
        $dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
        $categories =\backend\util\HelpUtil::dropdown_3_level($dd_categories);
        $sku_list=Products::find()->select('sku')->asArray()->all();
        $data=[
            'products'=>$products['products'],
            'total_records'=>$products['total_records'],
            'sku_list'=>$sku_list, // for drop down filer
            'categories'=>$categories,
            'channels'=>Channels::find()->where(['is_active'=>1])->asArray()->all()
            ];
        /*echo "<pre>";
        print_r($products); die();*/
        return $this->render('index',$data);
    }

    public function actionUpdateProductStockPercent()
    {
        $fields = Yii::$app->request->post();
        if(isset($fields['channel_pk_id']) && isset($fields['percent_value']))
        {
            $fields['percent_value']=$fields['percent_value']?$fields['percent_value']:NULL;
            $cp = ChannelsProducts::find()->where(['id'=>$fields['channel_pk_id']])->one();
            $cp->stock_update_percent = $fields['percent_value'];
            if($cp->save(false))
                return $this->asJson(['status'=>'success','msg'=>'Updated']);

        }
        return $this->asJson(['status'=>'failure','msg'=>'Failed to update']);
    }

    public function actionExportCsv()
    {

        $_GET['record_per_page']=20000; // to fetch all not by particular page
        $products= \backend\util\ChannelProductsUtil::get_channel_products();
        return  \backend\util\ChannelProductsUtil::export_csv($products['products']);
    }

}
