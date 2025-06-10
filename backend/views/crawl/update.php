<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\SkusCrawl */

$this->title = 'Update Sku Crawl Details: ';
$this->params['breadcrumbs'][] = ['label' => 'Sku Details', 'url' => ['crawl-add-sku']];
$this->params['breadcrumbs'][] = ['label' => $model->ID, 'url' => ['view', 'id' => $model->ID]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="channels-details-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'Update' => $Update
    ]) ?>

</div>
