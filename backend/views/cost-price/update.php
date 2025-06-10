<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\CostPrice */

$this->title = 'Update Cost Price: ' . $model->sku;
$this->params['breadcrumbs'][] = ['label' => 'Cost Prices', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->sku, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="cost-price-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
