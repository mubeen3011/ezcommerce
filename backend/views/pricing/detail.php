<?php

use common\models\CompetitivePricing;
use kartik\daterange\DateRangePicker;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\PricingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


$date = Yii::$app->request->post('date');
$date = ($date == '') ? date('Y-m-d') : $date;
if( isset($_GET['date']) ){
    $date = $_GET['date'];
}

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Suggested Detail Pricing Sheet - '.$_GET['sku'];
$todayDate = date('m/d/Y');
$yesterdayDate = date('m/d/Y', strtotime("-1 days"));
$channelList = \backend\util\HelpUtil::getChannels();
?>

    <style>
        .filters-visible{
            display: none;
        }
        .control-label {
            padding: 0px;
        }
        .form-group-margin{
            margin-bottom: 10px;
        }
        .form-control{
            min-height:24px;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="card">

                <div class="card-body">
                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12">
                            <h3>Suggested Detail Pricing Sheet - <?=$_GET['sku']?></h3>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="alert alert-info" style="margin: 0px;">  <?='Selected Date: '.$date?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">  </button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>

                    <div style="padding: 15px;" class="pricing-index">
                        <div class="row grid-container">
                            <div id="example23_wrapper" class="table-parent" style="width:100%;">
                                <div class="row">
                                    <?php
                                    foreach ($skus as $key => $value) :?>

                                        <?php foreach ($channelList as $cl): ?>


                                            <?php if( !in_array($cl['id'],$user_columns) ){ continue; } ?>
                                            <?php
                                            ?>

                                            <div class="col-lg-4 col-sm-12">
                                                <div class="card" style="margin-bottom: 3px">
                                                    <div class="card-body dt-widgets" style="height: 408px;">
                                                        <div class="table-responsive">
                                                            <table class="display nowrap table table-hover table-striped table-bordered dataTable no-footer">
                                                                <tbody><tr><td class="dl-more-td" colspan="2"><h4><?=$cl['name']?></h4></td></tr>
                                                                <tr><td class="dl-more-td"><label>Average Sales</label></td><td class="dl-more-td"><strong><?= $value['as'] ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label>Selling Status</label></td><td class="dl-more-td"><strong><?= $value['ss'] ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label>Suggested Price</label></td><td class="dl-more-td"><strong><?= isset($channelSkuList[$cl['id']][$key]['low_price']) ? $channelSkuList[$cl['id']][$key]['low_price'] : '' ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label>- Margins at Suggested Price %</label></td><td class="dl-more-td"><strong><?=$margins_low_price='';?>
                                                                            <?php isset($channelSkuList[$cl['id']][$key]['margins_low_price']) ? $margins_low_price=$channelSkuList[$cl['id']][$key]['margins_low_price'] : $margins_low_price='' ?>
                                                                            <?php
                                                                            if ($margins_low_price<=-5)
                                                                                echo '<span style="color: red;">'.$margins_low_price.'</span>';
                                                                            else if( $margins_low_price>-5 && $margins_low_price<5 )
                                                                                echo '<span style="color: orange;">'.$margins_low_price.'</span>';
                                                                            else if( $margins_low_price > 5 )
                                                                                echo '<span style="color: green;">'.$margins_low_price.'</span>';

                                                                            ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label> - Sale Price</label></td><td class="dl-more-td"><strong><?= isset($channelSkuList[$cl['id']][$key]['sale_price']) ? $channelSkuList[$cl['id']][$key]['sale_price'] : '' ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label>- Margins at Sale Price %</label></td><td class="dl-more-td"><strong><?= isset($channelSkuList[$cl['id']][$key]['margin_sale_price']) ? $channelSkuList[$cl['id']][$key]['margin_sale_price'] : '' ?></strong></td></tr>
                                                                <tr><td class="dl-more-td"><label>- Loss / Profit in RM</label></td><td class="dl-more-td"><strong><?= isset($channelSkuList[$cl['id']][$key]['loss_profit_rm']) ? $channelSkuList[$cl['id']][$key]['loss_profit_rm'] : '' ?></strong></td></tr>
                                                                </tbody></table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>