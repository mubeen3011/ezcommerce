<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Settings */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Settings', 'url' => ['settings/generic']];
$this->params['breadcrumbs'][] = 'Update';
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
                <h3><?= Html::encode('Update Settings') ?></h3>
                <div class="user-update">
                    <?= $this->render('_form', [
                        'model' => $model,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>
