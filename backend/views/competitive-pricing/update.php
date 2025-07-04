<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */

$this->title = 'Update Competitive Pricing: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Competitive Pricings', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="competitive-pricing-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
