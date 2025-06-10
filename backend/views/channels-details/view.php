<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\ChannelsDetails */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops Details', 'url' => ['generic']];
$this->params['breadcrumbs'][] = $model->id;
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
                <h3><?= Html::encode($model->id) ?></h3>
                <p>
                    <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Are you sure you want to delete this item?',
                            'method' => 'post',
                        ],
                    ]) ?>
                </p>
                <br>
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id',
                        ['value'=>$model->channel->name,'attribute'=>'channel_id'],
                        ['value'=>$model->category->name,'attribute'=>'category_id'],
                        'commission',
                        'pg_commission',
                        'created_at:date',
                        'updated_at:date',
                        ['value'=>$model->updatedBy->full_name,'attribute'=>'updated_by'],
                    ],
                ]) ?>
            </div>
        </div>
    </div>

</div>