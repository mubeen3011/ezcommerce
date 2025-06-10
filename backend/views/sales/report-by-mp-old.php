<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/31/2018
 * Time: 11:08 AM
 */

use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales Dashboard', 'url' => ['sales/dashboard']];
$this->params['breadcrumbs'][] = 'Sales Report by Marketplace - '. $mp;
$filter_date= isset($_GET['date']) ? base64_decode($_GET['date']) : "";
//echo '<pre>';print_r($avgSkuSales);die;
?>
<style type="text/css">
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


</style>
    <style>
        .panel-body {
            height: 350px !important;
        }

        #sales-target-div svg text {
            display: none;
        }
    </style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class=" row">
                    <div class="col-md-6 col-sm-12">
                        <h3>Sales Report by Marketplace - <?=$mp?></h3>
                    </div>
                    <div class="col-md-6 col-sm-12">
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
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <form id="dfilter" action="/sales/report-by-marketplace" method="get">
                            <label class="control-label">Date Range:</label>

                            <div class="input-group mb-3">
                                <input autocomplete="off" class="form-control input-daterange-datepicker" value="<?= ($filter_date!='') ? $filter_date : '' ?>" type="text" name="date" />
                                <div class="input-group-append">
                                       <span class="input-group-text">
                                           <span class="ti-calendar"></span>
                                       </span>
                                </div>
                                <input name="mp" type="hidden" value="<?=$_GET['mp']?>">
                            </div>
                        </form>
                    </div>
                </div>
                <div class="dashboard-sales col-md-12">
                    <h4><?= $this->title ?></h4>
                    <hr>
                    <div class="row">
                        <!-- Column -->
                        <div class="col-md-6 col-lg-3 col-xlg-3">
                            <div class="card card-inverse card-info">
                                <div class="box bg-info text-center">
                                    <h1 class="font-light text-white">$<?= $totalRevenue['total_revenue']?></h1>
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
                                    <h1 class="font-light text-white">$<?= $totalRevenue['avg_tran_val']?></h1>
                                    <h6 class="text-white">Average Transaction Value </h6>
                                </div>
                            </div>
                        </div>
                        <!-- Column -->
                        <div class="col-md-6 col-lg-3 col-xlg-3">
                            <div class="card card-inverse card-info">
                                <div class="box text-center">
                                    <h1 class="font-light text-white"><?= $totalRevenue['avg_item_per_customer']?></h1>
                                    <h6 class="text-white">Average Item per Customer</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading" style="font-size: 16px;">Sales by <?=$mp?> - <?=($date != '') ? 'from '.$date : date('F, Y')?></div>
                                <div class="panel-body" id="shop-chart-div"
                                     style="height: 380px !important;padding: 0 !important;"></div>
                            </div>
                        </div>
                    </div>
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
                    <div class="row">
                        <!-- column -->
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Monthly Sales By Category</h4>
                                    <ul class="list-inline text-right">
                                        <?php
                                        foreach ( $getCategorySales['categories'] as $catAlias=>$category_name ){
                                            ?>
                                            <li>
                                                <h6 style="font-size: 13px;">
                                                    <i class="fa fa-circle m-r-5 text-inverse" style="color: <?=$getCategorySales['colors'][$catAlias]?> !important"></i>
                                                    <?=$category_name?></h6>
                                            </li>
                                            <?php
                                        }
                                        ?>
                                    </ul>
                                    <div id="morris-area-chart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h4 class="card-title">Average Sku Report</h4>
                            <div class="clear"></div>
                            <div style="text-align: center">
                                <a target="_blank" href="/sales/average-sales-by-sku?view=skus&page=1&mp=<?=$_GET['mp']?>&record_per_page=10" style="font-size: 20px; ">
                                    Click here to see Sku Report
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    var currency ='<?=Yii::$app->params['currency']?>';
</script>
<script>
    var salesForcastA = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-1)])) ? $salesForcastData['refine'][(date('Y')-1)] : '', JSON_NUMERIC_CHECK)?>;
    var salesForcastB = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-2)])) ? $salesForcastData['refine'][(date('Y')-2)] : '', JSON_NUMERIC_CHECK)?>;
    var salesForcastD = <?=json_encode((isset($salesForcastData['refine'][date('Y')])) ? $salesForcastData['refine'][date('Y')] : '', JSON_NUMERIC_CHECK)?>;
    var minv ='<?=json_encode($salesForcastData['min'], JSON_NUMERIC_CHECK)?>';
    var maxv = '<?=json_encode($salesForcastData['max'] , JSON_NUMERIC_CHECK)?>';
    // category daily sales graph
    var dataset = <?=json_encode($getCategorySales['dataset'])?>;
    var categories = <?=json_encode($getCategorySales['categories'])?>;
    var colors = <?=json_encode($getCategorySales['colors'])?>;

</script>
<?php
$this->registerJsFile('/monster-admin/amcharts/amcharts.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/amcharts/serial.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
/*$this->registerJsFile('/monster-admin/js/aoa-sales-chart.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);*/
$date = isset($_GET['date']) ? $_GET['date']: '';
?>
<?php
$this->registerJs('
   var mpSales = '.$mpSales.';
    var clickEv = false;
    var postDate = "'.$date.'";
    var shopChart = AmCharts.makeChart("shop-chart-div", {
        "theme": "light",
        "type": "serial",
        "dataProvider": mpSales,
        "valueAxes": [{
    "unit": "",
            "position": "left",
            "title": "",
        }],
        "startDuration": 1,
        "graphs": [{
            "balloonText": "[[category]]: <b> "+currency + " [[value]]</b>",
            "fillAlphas": 0.9,
            "lineAlpha": 0.2,
            "title": "Sales",
            "type": "column",
            "clustered": false,
            "columnWidth": 0.5,
            "valueField": "sales",
            "labelText": currency + " [[value]]",
            "showHandOnHover": true,
            "fillColors":"#298CB4"
        }],
        "listeners": [{
            "event": "clickGraphItem",
            "method": function (e) {
            //var cat = encodeURI(e.item.category);
            var cat = e.item.category;
            location.href = encodeURI("/sales/report-by-shop?shop=" + cat.toLowerCase()+ "&date="+postDate);

    }
        }],
        "plotAreaFillAlphas": 0.1,
        "categoryField": "marketplace",
        "categoryAxis": {
    "gridPosition": "start"
        },
        "export": {
    "enabled": false
        }

    });
',
    View::POS_END);

?>

