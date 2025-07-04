<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Modules */

$this->title = 'Update Modules: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Modules', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="modules-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'modules'=>$modules,
        'controllers_list'=>$controllers_list
    ]) ?>

</div>
