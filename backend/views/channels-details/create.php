<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\ChannelsDetails */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops Details', 'url' => ['generic']];
$this->params['breadcrumbs'][] = 'Create Shops Details';
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
                <h3><?= Html::encode('Create Shops Details') ?></h3>
                <div class="user-create">
                    <?= $this->render('_form', [
                        'model' => $model,
                        /*'categoryList' => $categoryList,*/
                        'type' => 'create',
                        'channels' => $channels
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
