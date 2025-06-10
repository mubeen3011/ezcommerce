<?php

use common\models\Sellers;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;


/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */
$this->title = '';
echo '<p class="hide"><a   href="/crawl/crawl-add-sku?sku='.$_GET['sku'].'">Edit SKU Product ids (For Crawl Purpose)</a></p>';
$this->params['breadcrumbs'][] = ['label' => 'Competitive Pricing', 'url' => ['create']];
$this->params['breadcrumbs'][] = 'SKU:'.$sku.' Lowest Price Analytics';
$comaprision_data = $price_camparision_dataset;
foreach ( $comaprision_data as $key=>$value ){
    $comaprision_data[$key]=json_decode($value,true);
}
/*echo '<pre>';
print_r($comaprision_data);
die;*/
?>

<div class="row">

    <div class="col-12">
        <div class="card">

            <div class="card-body">

                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Crawl Lowest Price Analysis</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12 hide">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <form action="">
                    <div class="row">

                        <table>
                            <tbody>
                            <tr style="margin-left: 80%;float: left;">

                                <td>
                                    <?php
                                    if ( isset($_GET['Date_range']) ){
                                        $dateRange=$_GET['Date_range'];
                                    }else{
                                        $dateRange = $week.' to '.$today;
                                    }
                                    ?>
                                    <input readonly class="form-control-lg input-daterange-datepicker" autocomplete="off" value="<?= $dateRange ?>" type="text" name="Date_range" />
                                    <input type="hidden" name="sku" value="<?=(isset($_GET['sku'])) ? $_GET['sku'] : ''?>" />
                                </td>
                                <td>
                                    <input type="submit" style="line-height: 1.1;" class="btn btn-lg btn-success" id="submit_report" value="Get Report">
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </form>
                <div id="displayBox" style="display: none;">
                    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                </div>

            </div>

        </div>

    </div>
    <?php
    if ( empty($dataList) ){
        ?>
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h1 align="center">No Crawling data available for this sku</h1>
            </div>
        </div>
    </div>

        <?php
    }
    foreach ( $dataList as $marketplace=>$detail ){
        ?>
        <div class="col-lg-12">
            <div class="card">

                <div class="card-body">
                    <div class="card-title">
                        <h3><?=$marketplace?></h3>
                        <span><?=$week?> - <?=$today?></span>
                        <br /><br />
                    </div>
                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-12">
                        <ul class="list-inline text-right">
                            <?php
                            foreach ( $comaprision_data[$marketplace]['graph_labels'] as $g_key=>$shop_name ){
                                ?>
                                <li>
                                    <h5>
                                        <i class="fa fa-circle m-r-5 text-inverse" style="color: <?=$comaprision_data[$marketplace]['colors'][$g_key]?> !important"></i>
                                        <?=$shop_name?></h5>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <div id="<?=$marketplace?>-morris-area-chart"></div>
                    </div>

                    <div class="row">
                        <!-- Column -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex flex-row">
                                        <div class="m-l-10 align-self-center">
                                            <h6 class="text-muted m-b-0"><span class="badge badge-success">Average Price</span></h6>
                                            <h3 class="m-b-0"><?=Yii::$app->params['currency'].$dataList[$marketplace]['average_price']?></h3>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Column -->
                        <!-- Column -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex flex-row">
                                        <div class="m-l-10 align-self-center">
                                            <h6 class="text-muted m-b-0"><span class="badge badge-primary">Lowest Price</span></h6>
                                            <h3 class="m-b-0"><?=Yii::$app->params['currency'].$dataList[$marketplace]['lowest_price']['price']?></h3>
                                            <h5 class="text-muted m-b-0"><?=$dataList[$marketplace]['highest_price']['seller_name']?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Column -->
                        <!-- Column -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex flex-row">
                                        <div class="m-l-10 align-self-center">
                                            <h6 class="text-muted m-b-0"><span class="badge badge-warning">Highest Price</span></h6>
                                            <h3 class="m-b-0"><?=Yii::$app->params['currency'].$dataList[$marketplace]['highest_price']['price']?></h3>
                                            <h5 class="text-muted m-b-0"><?=$dataList[$marketplace]['highest_price']['seller_name']?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php
    }
    ?>

</div>
<script>
    var channelListDataset=[];
    var marketplaceList=[];
    <?php
    foreach ( $price_camparision_dataset as $marketplace=>$data ){
        ?>
    channelListDataset['<?=$marketplace?>'] = <?=$data?>;
    //console.log(channelListDataset);
    marketplaceList.push('<?=$marketplace?>');
    <?php
    }
    ?>
</script>