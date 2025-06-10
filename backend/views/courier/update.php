<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Couriers', 'url' => ['/courier']];
$this->params['breadcrumbs'][] = 'Update Courier Detail';
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?= Html::encode('Update Courier Details') ?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div class="user-update">
                    <?= $this->render('_form', [
                        'model' => $model,
                        'warehouses' => $warehouses,
                        'attached_warehouses' => $attached_warehouses,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>