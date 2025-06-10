<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\UserRoles */

$this->title = 'Update User Roles';
$this->params['breadcrumbs'][] = ['label' => 'User Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="user-roles-update">


    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
