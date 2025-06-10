<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['user/generic']];
$this->params['breadcrumbs'][] = 'View: '. ucwords($model->username) . ' profile';
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
                <h3><?= Html::encode('View: '. ucwords($model->username) . ' profile') ?></h3>
                <div class="user-view">


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
                    <br/>
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            ['value'=>$model->role->name,'attribute'=>'role_id'],
                            'username',
                            'full_name',
                            'email:email',

                        ],
                    ]) ?>

                </div>
            </div>
        </div>
    </div>

</div>

