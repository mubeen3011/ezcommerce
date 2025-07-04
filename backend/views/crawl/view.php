.<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\SkusCrawl */

$this->title = $model->ID;
$this->params['breadcrumbs'][] = ['label' => 'Sku Crawl Details', 'url' => ['crawl-add-sku']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="channels-details-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->ID], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->ID], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <br>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'ID',
            ['value'=>$model->channel_id,'attribute'=>'channel_id'],
            ['value'=>$model->sku_id,'attribute'=>'sku_id'],
            'product_ids',
        ],
    ]) ?>

</div>
