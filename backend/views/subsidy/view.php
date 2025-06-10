<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\Subsidy */

$this->title = 'View ' . $model->sku->sku . ' - ' . $model->channel->name;
$this->params['breadcrumbs'][] = ['label' => 'Subsidies', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subsidy-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
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
            'id',
            ['value'=>$model->sku->sku,'attribute'=>'sku_id'],
            ['value'=>$model->channel->name,'attribute'=>'channel_id'],
            'subsidy',
            'created_at:date',
            'updated_at:date',
            ['value'=>$model->updatedBy->full_name,'attribute'=>'updated_by'],
        ],
    ]) ?>

</div>
