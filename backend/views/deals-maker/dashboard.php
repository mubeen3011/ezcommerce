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
<div class="row">
    <!-- col -->
    <div class="col-lg-3 col-md-6">
        <div class="card bg-info">
            <div class="card-body">
                <div id="myCarousel" class="carousel slide" data-ride="carousel">
                    <!-- Carousel items -->
                    <div class="carousel-inner">
                        <div class="carousel-item active flex-column">
                            <h3 class="text-white font-light">Visit <br/> Deals</h3>
                            <div>
                                <a href="/deals-maker/historical-deals" class="btn btn-success waves-effect waves-light m-t-15 card-success">Historical</a>
                                <a href="/deals-maker" class="btn btn-primary waves-effect waves-light m-t-15 ">All Deals</a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- col -->
    <div class="col-lg-3 col-md-6">
      <div id="myCarousel4" class="carousel vert slide" data-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active flex-column">
                <div class="card bg-success">
                    <div class="card-body">
                        <h3 class="text-white font-light">SKU's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=sku&performance=top" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">High</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h3 class="text-white font-light">SKU's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=sku&performance=medium" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Medium</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-danger">
                    <div class="card-body">
                        <h3 class="text-white font-light">SKU's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=sku&performance=low" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Low</a>
                        </div>
                    </div>
                </div>   
            </div>
        </div>    
      </div>
    </div>
    <!-- col -->
    <div class="col-lg-3 col-md-6">
      <div id="myCarousel4" class="carousel vert slide" data-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active flex-column">
                <div class="card bg-success">
                    <div class="card-body">
                        <h3 class="text-white font-light">Category <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=category&performance=top" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">High</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h3 class="text-white font-light">Category <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=category&performance=medium" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Medium</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-danger">
                    <div class="card-body">
                        <h3 class="text-white font-light">Category <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=category&performance=low" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Low</a>
                        </div>
                    </div>
                </div>   
            </div>
        </div>    
      </div>
    </div>
    <!-- col -->
    <div class="col-lg-3 col-md-6">
      <div id="myCarousel4" class="carousel vert slide" data-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active flex-column">
                <div class="card bg-success">
                    <div class="card-body">
                        <h3 class="text-white font-light">Shop's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=shop&performance=top" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">High</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h3 class="text-white font-light">Shop's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=shop&performance=medium" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Medium</a>
                        </div>
                    </div>
                </div>   
            </div>
            <div class="carousel-item flex-column">
                <div class="card bg-danger">
                    <div class="card-body">
                        <h3 class="text-white font-light">Shop's <br/> Performance</h3>
                        <div>
                            <a href="/deals-maker/sku-performance?entitytype=shop&performance=low" class="btn btn-secondary b-0 waves-effect waves-light m-t-15">Low</a>
                        </div>
                    </div>
                </div>   
            </div>
        </div>    
      </div>
    </div>
</div>
    <!-- col -->
</div>
<!-- Row -->
<div class="row">
    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?='Active Deals'?></h3>
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

