<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\ChannelsDetails */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops Details', 'url' => ['generic']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update Shops Details: ' . $model->id;
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
                <h3><?= Html::encode('Update Shops Details: ' ) ?></h3>
                <div class="user-update">
                    <?= $this->render('_form', [
                        'model' => $model,
                        'type' => 'update',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>
