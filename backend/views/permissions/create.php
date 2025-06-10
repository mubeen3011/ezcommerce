<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model common\models\Permissions */

$this->title = 'Create Permissions';
$this->params['breadcrumbs'][] = ['label' => 'Permissions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$roleList = ArrayHelper::map($roles, 'id', 'name');
?>
<div class="permissions-create">


    <?= $this->render('_form', [
        'model' => $model,
        'roles' => $roleList,
    ]) ?>

</div>
