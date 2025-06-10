<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Subsidy */

$this->title = 'Update Subsidy/Margin: ' .  $model->sku->sku . ' - ' . $model->channel->name;
$this->params['breadcrumbs'][] = ['label' => 'Subsidies', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="subsidy-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
