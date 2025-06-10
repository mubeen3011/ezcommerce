<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Category */

/*$this->title = 'Create Category';
$this->params['breadcrumbs'][] = ['label' => 'Categories', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;*/
//$this->title = 'sd';
$this->params['breadcrumbs'][] = ['label' => 'Categories', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id];
$this->params['breadcrumbs'][] = 'Create Category: ';
?>
<div class="row">
<div class="col-2">
</div>
    <div class="col-8">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h3><?= Html::encode('Create Category ' . $model->id) ?></h3>
                    </div>
                    <div class="col-md-6">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div class="category-create">
                    <?= $this->render('_form', [
                        'model' => $model,
                        'cat_list' => $cat_list,
                    ]) ?>

                </div>

            </div>
        </div>
    </div>
</div>

