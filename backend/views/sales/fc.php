<?php

use common\models\Channels;
use yii\bootstrap\ActiveForm;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\web\View;

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\OrdersPaidStatusSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Financial Validation';
$this->params['breadcrumbs'][] = $this->title;
?>
<style type="text/css">
    .ui-draggable .ui-dialog-titlebar {
        cursor: none;
        display: none;
    }

    .ui-dialog-buttonpane {
        display: none;
    }

    .ui-dialog {
        width: 650px !important;
        z-index: 1050;
    }

    .form-label {
        font-size: 12px;
    }
</style>
<div class="orders-paid-status-index">

    <h3><?= Html::encode($this->title) ?></h3>
    <br/>
    <div class="col-md-3">
    <?php
    $form = ActiveForm::begin(['action' =>['/sales/upload-batch'] ,'options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="form-group">
        <label>Upload Orders Batch</label>
        <input type="file" name="csv" class="csv-file">
    </div>

    <div class="form-group">
        <?= Html::submitButton('Import', ['class' => 'btn btn-primary btn-fc-import']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    </div>
    <hr>

    <div class="col-md-12" style="margin-top: 15px;">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                ['attribute'=>'item_created_at','label'=>'Order Date','value'=>'order.order_created_at'],
                ['attribute'=>'item_sku','value'=>'item_sku'],
                ['attribute'=>'shop_name','label'=>'Shop Name','value'=>'order.channel.name', 'filter'=>ArrayHelper::map(Channels::find()->where(['is_active'=>'1'])->where(['in','id',['1','10']])->asArray()->all(), 'id', 'name'),],
                ['attribute'=>'qty','label'=>'Quantity','value'=>'qty'],
                ['attribute'=>'price','label'=>'Price (USD)','value'=>'price'],
                ['attribute'=>'order_id','value'=>'order.order_number'],
                ['attribute'=>'shop_sku','value'=>'shop_sku'],
                ['attribute'=>'item_status','value'=>'item_status'],
                ['label'=>'Paid Status','value'=>'is_paid','format'=>'boolean'],
                ['attribute'=>'stype','value'=>'stype'],
                ['attribute'=>'counter_days','label'=>'Days Since Order Date','value'=>'counter_days'],


            ],
        ]); ?>
    </div>
</div>
