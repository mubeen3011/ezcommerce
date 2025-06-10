<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Subsidy */

$this->title = 'Create Subsidy/Margin';
$this->params['breadcrumbs'][] = ['label' => 'Subsidies', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="subsidy-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
