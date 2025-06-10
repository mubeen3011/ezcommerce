<?php

use backend\controllers\StocksController;
use backend\util\HelpUtil;
use yii\helpers\Url;
use yii\web\View;
//use common\models\User;
$forcast = \backend\util\HelpUtil::getMonthSales();
$sign = ($forcast['prev'] < 0) ? "down" : "up";
$html = "";
$this->title = '';
$this->params['breadcrumbs'][] = 'Dashboard';
$userid= Yii::$app->user->identity;
$salesForcastData = \backend\util\GraphsUtil::getSalesForcast();
$salesMarketplace = \backend\util\GraphsUtil::getMarketplaceSalesForcast();
$NewOOSTest = HelpUtil::getOutOfStock();
$currency = Yii::$app->params['currency'];
//$aging = HelpUtil::getAgingStock();
//echo '<pre>';print_r($aging);die;
?>
    <style type="text/css">
        #margin_chart {
            width: 100%;
            height: 150px;
        }

        .card-body {
            height: 226px;
        }

        .total-sales {
            width: 100%;
            height: 400px;
        }
        .total-sales {
            position: relative; }
        .total-sales .chartist-tooltip {
            background: #55ce63; }
        .total-sales .ct-series-d .ct-bar {
            stroke: #6DDADA; }
        .total-sales .ct-series-b .ct-bar {
            stroke: blue; }
        .total-sales .ct-series-a .ct-bar {
            stroke: #1280ac; }
        .box {
            height: 120px !important;
        }

    </style>
<style type="text/css">
        #margin_chart {
            width: 100%;
            height: 150px;
        }

        .card-body {
            height: 226px;
        }

        #sales-donute{
            width: 100%;
            height: 400px;
        }
        . {
            position: relative; }
            #sales-donute.chartist-tooltip {
            background: #55ce63; }
            #sales-donute.ct-series-d .ct-bar {
            stroke: #6DDADA; }
            #sales-donute.ct-series-b .ct-bar {
            stroke: blue; }
            #sales-donute.ct-series-a .ct-bar {
            stroke: #1280ac; }
        .box {
            height: 120px !important;
        }

    </style>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" style="height: auto !important;">
                    <div class=" row">
                        <div class="col-md-4 col-sm-12">
                            <h3>Dashboard</h3>
                        </div>

                        <div class="col-md-8 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card card-inverse card-info">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white"><?=$currency?> <?= $totalRevenue['total_revenue']?></h1>
                                <h6 class="text-white">Sales Revenue</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card card-primary card-inverse">
                            <div class="box text-center">
                                <h1 class="font-light text-white"><?= $totalRevenue['total_customer']?></h1>
                                <h6 class="text-white">Customers</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card card-inverse card-success">
                            <div class="box text-center">
                                <h1 class="font-light text-white"><?=$currency?> <?= $totalRevenue['avg_tran_val']?></h1>
                                <h6 class="text-white">Average Transaction Value </h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card card-inverse card-info">
                            <div class="box text-center">
                            <h1 class="font-light text-white"></h1>
                            <h1 class="font-light text-white"><?= $totalRevenue['avg_item_per_customer']?></h1>
                                <h6 class="text-white">Average Item per Customer</h6>
                            </div>
                        </div>
                    </div>
                </div>
<!--###########################################-->
    <?php
    if($userid['role_id'] != 8 && !empty($distributorsale)) {

        ?>
        <h2>Distributors Sale</h2>
        <div class="row">
            <!------------------------------------------>
            <?php

            foreach ($distributorsale as $dist_sale) {
                if (!is_array($dist_sale))
                    continue;
                ?>
                <div class="col-md-6 col-lg-2 col-xlg-2">
                    <div class="card card-inverse card-info">
                        <div class="box bg-info ?> text-center">

                            <h2 class="font-light text-white"
                                style="font-size: 18px;"> <?=$currency ?> <?= \backend\util\HelpUtil::number_format_short($dist_sale['total_sale']) . "" ?></h2>
                            <h6 class="text-white"><?= $dist_sale['username']; ?></h6>

                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }
        ?>
<!--    #########################################-->
                <!-- Row -->
    <div class="row">
        <!-- sales analytics -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" style="height: auto !important;">
                    <ul class="list-inline pull-right">
                        <li>
                            <h6 class="text-muted"><i class="fa fa-circle m-r-5" style="color:#12a0f8"></i><?= (date('Y')-2);?></h6>
                        </li>
                        <li>
                            <h6 class="text-muted"><i class="fa fa-circle m-r-5" style="color: blue"></i><?= (date('Y')-1);?></h6>
                        </li>
                        <li>
                            <h6 class="text-muted"><i class="fa fa-circle m-r-5" style="color:#1280ac"></i><?= (date('Y'));?></h6>
                        </li>
                    </ul>
                    <h4 class="card-title">Total Revenue</h4>
                    <div class="clear"></div>
                    <div class="total-sales"></div>
                </div>
            </div>
        </div>
    </div>

    <div class = "row">
        <div class="col-md-12 col-sm-12 center">
            <h3>Top Sellers by Marketplace</h3>
        </div>
    <?php
    foreach ($salesMarketplace['refine'] as $d=>$sm):
    ?>
    <div class="col-md-3"  >
        <div class="card" >
            <div class="card-body" style=" height: auto !important;">
               <div id="carouselExampleSlidesOnly" class="carousel slide" data-ride="carousel">
                   <div class="carousel-inner">  
                       <div class="carousel-item active" >
                       <center>  <img   width ="175" height="131" style=" border:1px solid black;" src="..\..\images\<?= $d?>.jpg" onerror="src='../../images/no_image.jpg';"></img></center>
                            <h6 align="center" style="margin:10px;" class="card-title"><?=ucwords($d)?></h6>
                            <h6 align="center"  class="m-b-0"><?=HelpUtil::number_format_short(array_sum($salesMarketplace['sales'][$d]))?> <small>sales</small></h6>
                            </div>
                       </div>
                   </div>
                </div>
            </div>
       </div>
    <?php
        endforeach;
        ?>
</div>
    

    <div class="row">
        <?php
        $k=0;
        if(1==1): // remove after donut chart seettings according to new integration
        foreach ($salesMarketplace['refine'] as $d=>$sm): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body"  style="height: auto !important;">
                        <h4 class="card-title"><?=ucwords($d)?></h4>
                        <h5 class="m-b-0"><?=HelpUtil::number_format_short(array_sum($salesMarketplace['sales'][$d]))?> <small>sales</small></h5>
                        <!-- Row -->
                        <div class="row m-t-40">
                            <div class="col-md-12">
                                <div id="sales-donute-<?=$d?>" style="width:100%; height:300px;"></div>
                                <!-- <div class="round-overlap"><i class="mdi mdi-cart"></i></div>-->
                            </div>

                        </div>
                        <!-- Row -->
                    </div>
                </div>
            </div>
            <?php
            $k++;

        endforeach;
        endif;
        ?>
    </div>
    <hr>
    <h2>Inventory</h2>
    <div class="row">
        <!------------------------------------------>
        <div class="col-md-4 col-lg-2 col-xlg-2">
            <div class="card card-inverse card-success">
                <div onclick="location.href = '/inventory/warehouses-inventory-stocks?page=1'" class="box text-center" style="cursor: pointer;" id="stocks_value">
                    <h1 class="font-light text-white" style="font-size: 20px;">
                        <b><?=$currency?> <?=HelpUtil::number_format_short($warehouses_Stocks['Total_inventory_amount'])."" ?></b></h1>
                    <h6 class="text-white">Total Inventory</h6>
                </div>
            </div>
        </div>
        <!------------------------------------------>
        <?php

        foreach ($warehouses_Stocks['warehouse_stocks'] as $ware_houses) {
            if (!is_array($ware_houses))
                continue;
            ?>
            <div class="col-md-6 col-lg-2 col-xlg-2">
                <div class="card card-inverse card-info">
                    <div class="box bg-info ?> text-center">

                        <h2 class="font-light text-white" style="font-size: 18px;"> <?= $currency?> <?=\backend\util\HelpUtil::number_format_short($ware_houses['total_inventory_stocks'])."" ?></h2>
                        <h6 class="text-white"><?= $ware_houses['warehouse_name'];?></h6>

                    </div>
                </div>
            </div>
            <?php
        }
        ?>

        <!------------------------------------------>
        <div class="col-md-6 col-lg-2 col-xlg-2">
            <div class="card card-inverse card-info">
                <div class="box bg-info ?> text-center">

                    <h2 class="font-light text-white" style="font-size: 18px;">
                        <?=$currency?> <?= (isset($stocksTrans) && is_array($stocksTrans)) ? \backend\util\HelpUtil::number_format_short(array_sum(array_column($stocksTrans,'in_transit_amount'))) : 0 ?>
                    </h2>
                    <h6 class="text-white">Stocks in Transit</h6>
                </div>
            </div>
        </div>


    </div>
    <h2>Issues & Challenges</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-inverse card-info">
                <div class="box text-center" style="cursor: pointer;" id="stocks_value">
                    <h1 class="font-light text-white" style="font-size: 20px;">
                        <b>Out of stock in WH</b></h1>
                    <h3 class="text-white"><?= $NewOOSTest ?> SKUs</h3>
                </div>
            </div>
        </div>

    </div>
    <script>
        var salesForcastA = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-1)])) ? $salesForcastData['refine'][(date('Y')-1)] : '', JSON_NUMERIC_CHECK)?>;
        var salesForcastB = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-2)])) ? $salesForcastData['refine'][(date('Y')-2)] : '', JSON_NUMERIC_CHECK)?>;
        var salesForcastD = <?=json_encode((isset($salesForcastData['refine'][date('Y')])) ? $salesForcastData['refine'][date('Y')] : '', JSON_NUMERIC_CHECK)?>;
        var minv ='<?=json_encode($salesForcastData['min'], JSON_NUMERIC_CHECK)?>';
        var maxv = '<?=json_encode($salesForcastData['max'] , JSON_NUMERIC_CHECK)?>';
        var salesMarketplace = <?=json_encode($salesMarketplace['refine'])?>;



    </script>
<?php

$this->registerJsFile(
    '@web/monster-admin/js/toastr.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);

$this->registerJsFile('/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/echarts/echarts-all.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/js/aoa-main-chart.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/monster-admin/assets/plugins/css-chart/css-chart.css',['depends' => [\frontend\assets\AppAsset::className()]]);


?>