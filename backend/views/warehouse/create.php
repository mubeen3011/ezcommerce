<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\warehouses */

?>
<div class="row">
    <div class="col-12">
        <?php
        $this->title = '';
        $this->params['breadcrumbs'][] = ['label' => 'Administrator', 'url' => ['user/generic']];
        $this->params['breadcrumbs'][] = 'Warehouse Settings';
        ?>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3><?='Warehouse Settings'?></h3>
                <div class="warehouses-create">

                    <h1><?= Html::encode($this->title) ?></h1>

                    <?= $this->render('_form', [
                        'model' => $model,
                        'channels' => $channels,
                        'CS'=>$CS,
                        'pre_selected_zip' => []
                    ]) ?>

                </div>

            </div>
        </div>
    </div>

</div>
