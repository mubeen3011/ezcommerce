<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\CostPrice */

$this->title = 'Add Product';
$this->params['breadcrumbs'][] = ['label' => 'Cost Prices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="cost-price-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
