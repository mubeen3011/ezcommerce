<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Create User';
?>
<div class="row">
    <div class="col-12">
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?= Html::encode('Create User') ?></h3>
                <div class="user-create">
                    <?= $this->render('_form', [
                        'model' => $model,
                        'warehouses' => $warehouses,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>