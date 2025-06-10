<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\SkuMarginSettings */

$this->title = 'Create Sku Margin Settings';
$this->params['breadcrumbs'][] = ['label' => 'Sku Margin Settings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sku-margin-settings-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
