<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\UserRoles */

$this->title = 'Create User Roles';
$this->params['breadcrumbs'][] = ['label' => 'User Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-roles-create">


    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
