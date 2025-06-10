<?php

use common\models\Channels;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\web\View;
use backend\controllers;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\DealsMakerSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'DealMaker', 'url' => ['/deals-maker/dashboard/']];
$this->params['breadcrumbs'][] = 'Deals Maker';
$sku_count=\backend\util\HelpUtil::getRequesterDealCount(1);

//$ExpiredDeals = \backend\controllers\DealsMakerController::ExpiredDeals();

?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Deals Dashboard</h3>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Row -->
<!-- col -->
<!-- Row -->
<div class="row">
    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='SKU Based Performance'?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= Yii::$app->session->getFlash('DealCsvImport') ?>
                    </div>
                </div>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

                <?=$gridview?>
            </div>

        </div>

    </div>
</div>
<script>
    var setVal = '<?=$sku_count?>';
</script>

