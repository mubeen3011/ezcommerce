<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Sellers */

$this->title = 'Create Sellers';
$this->params['breadcrumbs'][] = ['label' => 'Sellers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-12">
    </div>
    <div class="col-12">
        <div class="card">

            <div class="card-body">
                <h3><?= Html::encode($this->title) ?></h3>
                <div class="user-create">
                    <?= $this->render('_form', [
                        'model' => $model,
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

</div>