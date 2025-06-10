<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\SkusCrawl */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sku Details', 'url' => ['crawl-add-sku']];
$this->params['breadcrumbs'][] = 'Create Sku Crawl Details';

?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">


                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Create Sku Crawl Details</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>


                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>


                <div class="channels-details-create">

                    <h1><?= Html::encode($this->title) ?></h1>

                    <?= $this->render('_form', [
                        'model' => $model,
                        'Update' => false
                    ]) ?>

                </div>

            </div>
        </div>
    </div>
</div>

