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
$this->params['breadcrumbs'][] = 'Sales Report By ' . ucwords($type);
$currency = Yii::$app->params['currency'];
?>
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
                        <?php if ($type == 'monthly'): ?>
                            <h3>Sales Report by <?= ucwords($type) ?> - <?= ucwords($_GET['month']) ?>
                                ,<?= $_GET['year'] ?></h3>
                        <?php else: ?>
                            <h3>Sales Report by <?= ucwords($_GET['month']) ?> <?= ucwords($type) ?>
                                - <?= $_GET['year'] ?></h3>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12">
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
                <div class="dashboard-sales col-md-12">
                    <h4><?= $this->title ?></h4>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body" id="shop-chart-div"
                                     style="height: 380px !important;padding: 0 !important;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12"><h3><strong>Sales performance by units</strong></h3></div>
                    </div>
                    <div class="row">

                        <hr>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body dt-widgets">
                                    <h4 class="card-title">Low Performers</h4>
                                    <div class="panel panel-default">
                                        <div class="panel-body" style="overflow: auto;height: 300px">
                                            <table class="table table-bordered tbl-avg-sales-losses table-striped">
                                                <thead>
                                                <tr>
                                                    <td>SKU</td>
                                                    <td>Avg</td>
                                                    <td>Target To-date</td>
                                                    <td>Live</td>
                                                    <td>%</td>
                                                    <td>Selling Status</td>
                                                    <td>Lowest Price</td>
                                                    <td>Seller name</td>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $i = 1;
                                                if (isset($avgSkuSales['losses'])) {
                                                    foreach ($avgSkuSales['losses'] as $k => $v):
                                                        ?>
                                                        <tr>
                                                            <td><?= $v['sku'] ?></td>
                                                            <td><?= $v['avg_monthly'] ?> </td>
                                                            <td><?= $v['avg_todate_target'] ?> </td>
                                                            <td><?= $v['live'] ?> </td>
                                                            <td><?= $v['percentage'] ?> </td>
                                                            <td><?= $v['selling_status'] ?></td>
                                                            <td><?= isset($v['crawl_price']) ? $v['crawl_price'] : '' ?></td>
                                                            <td><?= isset($v['seller_name']) ? $v['seller_name'] : '' ?></td>
                                                        </tr>
                                                        <?php
                                                        $i++;
                                                    endforeach;
                                                } else { ?>
                                                    <tr>
                                                        <td colspan="5">no losses.</td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body dt-widgets">
                                    <div class="panel panel-default">
                                        <h4 class="card-title">High Performers</h4>
                                        <div class="panel-body" style="overflow: auto;height: 300px">
                                            <table class="table table-striped table-bordered dataTable no-footer tbl-avg-sales-earning">
                                                <thead>
                                                <tr>
                                                    <td>SKU</td>
                                                    <td>Avg</td>
                                                    <td>Target To-date</td>
                                                    <td>Live</td>
                                                    <td>%</td>
                                                    <td>Selling Status</td>
                                                    <td>Lowest Price</td>
                                                    <td>Seller name</td>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $i = 1;
                                                if (isset($avgSkuSales['earnings'])) {
                                                    foreach ($avgSkuSales['earnings'] as $k => $v):
                                                        ?>
                                                        <tr>
                                                            <td><?= $v['sku'] ?></td>
                                                            <td><?= $v['avg_monthly'] ?> </td>
                                                            <td><?= $v['avg_todate_target'] ?> </td>
                                                            <td><?= $v['live'] ?> </td>
                                                            <td><?= $v['percentage'] ?> </td>
                                                            <td><?= $v['selling_status'] ?> </td>
                                                            <td><?= isset($v['crawl_price']) ? $v['crawl_price'] : '' ?></td>
                                                            <td><?= isset($v['seller_name']) ? $v['seller_name'] : '' ?></td>
                                                        </tr>
                                                        <?php
                                                        $i++;
                                                    endforeach;
                                                } else { ?>
                                                    <tr>
                                                        <td colspan="5">no earning.</td>
                                                    </tr>
                                                <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="row">

                        <hr>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Sales performance by value</h4>
                                    <div class="panel panel-default">
                                        <div class="panel-body" style="overflow: auto;height: 300px">
                                            <table class="display dataTable no-footer nowrap table table-hover table-striped table-bordered dataTable tbl-value-">
                                                <thead>
                                                <tr>
                                                    <td>SKU</td>
                                                    <td>Sales</td>
                                                    <td>To-date Target (<?= $currency?>)</td>
                                                    <td>Value (<?=$currency?>)</td>
                                                    <td>Diff (<?= $currency?>)</td>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                $i = 1;

                                                foreach ($skuSales as $k => $v):
                                                    ?>
                                                    <tr>
                                                        <td><?= $v['sku'] ?></td>
                                                        <td><?= $v['sales'] ?> </td>
                                                        <td><?= number_format($v['todate-avg-value'], 2) ?> </td>
                                                        <td><?= number_format($v['value'], 2) ?> </td>
                                                        <td><?= number_format($v['diff'], 2) ?> </td>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>


                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<script>
    var currency ='<?= Yii::$app->params['currency']?>';
</script>

<?php
$this->registerJsFile('/monster-admin/amcharts/amcharts.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/amcharts/serial.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
/*$this->registerJsFile('/monster-admin/js/aoa-sales-chart.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);*/
$this->registerJs('
   var mpSales = ' . $mpSales . ';
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
    "balloonText": "[[category]]: <b> " +currency+"[[value]]</b>",
            "fillAlphas": 0.9,
            "lineAlpha": 0.2,
            "title": "Sales",
            "type": "column",
            "clustered": false,
            "columnWidth": 0.5,
            "valueField": "sales",
            "labelText": currency + " [[value]]",
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
if (isset($avgSkuSales['earnings']))
    $this->registerJs('$(\'.tbl-avg-sales-earning\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($avgSkuSales['losses']))
    $this->registerJs('$(\'.tbl-avg-sales-losses\').DataTable({
        dom: \'Bfrtip\',
        order: [[ 4, "asc" ]],
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($skuSales))
    $this->registerJs('$(\'.tbl-value-\').DataTable({
        dom: \'Bfrtip\',
        order: [[ 3, "desc" ]],
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($avgSkuSales['earnings']))
    $this->registerJs('$(\'.tbl-avg-sales-earning\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($starget))
    $this->registerJs('$(\'.sales-performance-by-shop\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
?>
<script>

</script>

