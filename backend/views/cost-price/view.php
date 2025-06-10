<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\CostPrice */

$this->title = 'View '.$model->sku;
$this->params['breadcrumbs'][] = ['label' => 'Cost Prices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cost-price-view">

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
            'sku',
            'name',
            'cost',
            'rccp_cost',
            'sub_category',
            ['value'=>$model->subCategory->name,'attribute'=>'sub_category'],
            'created_at:date',
        ],
    ]) ?>

</div>
