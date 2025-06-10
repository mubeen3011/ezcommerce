<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Channels */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops', 'url' => ['generic']];
$this->params['breadcrumbs'][] = ['label' => $model->id];
$this->params['breadcrumbs'][] = 'Create Shops Details: ';
?>



<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?= Html::encode('Create Shops Details: ' . $model->id) ?></h3>
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
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>