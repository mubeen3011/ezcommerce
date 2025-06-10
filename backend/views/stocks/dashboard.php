<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/16/2018
 * Time: 10:45 AM
 */

use yii\web\View;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Dashboard';
$numbers = \backend\util\HelpUtil::getCurrentStocktotal();
$currency = Yii::$app->params['currency'];

?>
    <style>
        .box {
            height: 150px !important;
        }

        #sales-target-div svg text {
            display: none;
        }

        .dt-widgets {
            height: 450px;
            overflow-y: auto;
        }
    </style>
<!--    <style type="text/css">-->
<!--        #margin_chart {-->
<!--            width: 100%;-->
<!--            height: 100px;-->
<!--        }-->
<!--        .card-body {-->
<!--            height: 226px;-->
<!--        }-->
<!--        .total-sales {-->
<!--            width: 100%;-->
<!--            height: 300px;-->
<!--        }-->
<!--        .total-sales {-->
<!--            position: relative; }-->
<!--        .total-sales.chartist-tooltip {-->
<!--            background: #55ce63; }-->
<!--        .total-sales .ct-series-d .ct-bar {-->
<!--            stroke: #6DDADA; }-->
<!--        .total-sales .ct-series-b .ct-bar {-->
<!--            stroke: blue; }-->
<!--        .total-sales .ct-series-a .ct-bar {-->
<!--            stroke: #1280ac; }-->
<!--        .box {-->
<!--            height: 120px !important;-->
<!--        }-->
<!---->
<!--    </style>-->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3><?= 'Inventory Dashboard' ?></h3>

                    <h4>Stocks Value</h4>

                    <div class="row">
                        <!------------------------->
                        <div style="margin-right: -28px;" class="col-md-4 col-lg-2 col-xlg-2">
                            <div class="card card-inverse card-success">
                                <div class="box text-center" style="cursor: pointer;" id="stocks_value">
                                    <?php echo \backend\util\HelpUtil::ToolTip("It's the total of All warehouses including Stock in Transit."); ?>
                                    <h3 class="font-light text-white" style="font-size: 20px;">
                                        <b><?=$currency?> <?= (isset($numbers)) ? \backend\util\HelpUtil::number_format_short($warehouses_Stocks['Total_inventory_amount']) : 0 ?></b></h3>
                                    <h6 class="text-white">Total</h6>
                                </div>
                            </div>
                        </div>
                        <!------------------------->
                        <?php

                        foreach ($warehouses_Stocks['warehouse_stocks'] as $ware_houses) {
                            if (!is_array($ware_houses))
                                continue;
                            ?>
                            <div style="margin-right: -28px;" class="col-md-6 col-lg-2 col-xlg-2">
                                <div class="card card-inverse card-info">
                                    <div class="box bg-info ?> text-center">

                                        <h2 class="font-light text-white" style="font-size: 18px;"> <?=$currency ?> <?=\backend\util\HelpUtil::number_format_short($ware_houses['total_inventory_stocks'])."" ?></h2>
                                        <h6 class="text-white"><?= $ware_houses['warehouse_name'];?></h6>

                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        ?>

                        <!------------------------->
                        <div style="margin-right: -28px;" class="col-md-4 col-lg-2 col-xlg-2">
                            <div class="card card-inverse card-info">
                                <div class="box text-center" style="cursor: pointer;" id="stocks_value">
                                    <?php echo \backend\util\HelpUtil::ToolTip("Sum of pending and partially shipped P'O."); ?>
                                    <h3 class="font-light text-white" style="font-size: 20px;">
                                        <b><?=Yii::$app->params['currency'] ?> <?= (isset($stocksTrans) && is_array($stocksTrans)) ? \backend\util\HelpUtil::number_format_short(array_sum(array_column($stocksTrans,'in_transit_amount'))) : 0 ?></b></h3>
                                    <h6 class="text-white">Stocks in Transit</h6>
                                </div>
                            </div>
                        </div>

                    </div>


                </div>
            </div>
        </div>
<!--        <div class="col-lg-12">-->
<!--            <div class="card">-->
<!--                 <h4 class="card-title">Monthly Stock</h4>-->
<!--                <div class="total-sales"></div>-->
<!--            </div>-->
<!--         </div>-->
        <!--Negative margin skus-->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">Current Under Stock Orders</h4>
                    <div class="table-responsive ">
                        <table id="dt-1"
                               class="display nowrap table table-hover table-striped table-bordered negative-margin-skus">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Shop name</th>
                                <th>Warehouse name</th>
                                <th>Order #</th>
                                <th>Updated at</th>
                                <th>Order status</th>
                                <th>Order Quantity</th>
                                <th>warehouse Qty</th>
                                <th>Days</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (empty($current_under_stock_order)){
                                ?>
                                <tr>
                                    <td colspan="13" align="center">There is no any current under stock orders right now</td>
                                </tr>
                                <?php
                            }
                            foreach ($current_under_stock_order as $key => $value):

                                ?>
                                <tr>
                                    <td><?= $value['sku'] ?></td>
                                    <td><?= $value['shop_name'] ?></td>
                                    <td><?= $value['warehouse'] ?></td>
                                    <td><?= $value['order_number'] ?></td>
                                    <td><?= $value['item_updated_at'] ?></td>
                                    <td><?= $value['item_status'] ?></td>
                                    <td><?= $value['order_count'] ?></td>
                                    <td><?= $value['current_stock'] ?></td>
                                    <td>
                                        <?php
                                        $now = strtotime($value['item_updated_at']); // or your date as well
                                        $your_date = strtotime($value['last_update']);
                                        $datediff = $your_date - $now;
                                        echo round($datediff / (60 * 60 * 24));;
                                        ?>
                                    </td>
                                </tr>
                                <?php
                            endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!--To be out of stock soon-->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">To be out of stock soon</h4>
                    <div class="table-responsive">
                        <table id="dt-3" class="display nowrap table table-hover table-striped table-bordered to-be-out-of-stock-soon">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Warehouse</th>
                                <th>Expected Ending Date</th>
                                <th>Stocks in Transit Qty</th>
                                <th>Selling status</th>
                                <th>Warehouse  Qty</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (empty($soon_out_of_stock)){
                                ?>
                                <tr>
                                    <td colspan="13" align="center">There is no any to be out of stock right now</td>
                                </tr>
                                <?php
                            }
                            ?>
                            <?php
                            foreach ($soon_out_of_stock as $k => $v):
                                ?>
                                <tr>
                                    <td><?= $v['sku'] ?></td>
                                    <td><?= $v['warehouse_name'] ?></td>
                                    <td><?= date("d/m/Y", strtotime("+" . $v['days'] . " days")) ?></td>
                                    <td><?= $v['stock_in_transit'] ; ?></td>
                                    <td><?=ucwords($v['status']) ?></td>
                                    <td><?= $v['available'] ?></td>
                                </tr>
                                <?php
                            endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!--<div class="col-lg-6">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">Out of stock in warehouse </h4>
                   <br/>
                    <div class="table-responsive">
                        <table id="dt-4"
                               class="display nowrap table table-hover table-striped table-bordered out-of-stock-in-isis-test">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>OOS</th>
                                <th>Selling status</th>
                                <th>Qty</th>
                                <th>Warehouse</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
/*                            $i = 1;
                            foreach ($oos_test as $oitems):
                                if (!isset($oitems['oos_days']) || $oitems['oos_days']<=2){
//                                    continue;
                                }
                                */?>
                                <tr>
                                    <td><?/*= $oitems['sku']; */?></td>
                                    <td><?/*= $oitems['oos_days'] ." days"; */?></td>
                                    <td><?/*= $oitems['selling_status'] ; */?></td>
                                    <td><?/*= $oitems['qty']; */?></td>
                                    <td><?/*= $oitems['warehouse']; */?></td>
                                </tr>
                                <?php
/*                                $i++;
                            endforeach;*/?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>-->
        <!--aging stock-->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body dt-widgets">
                    <h4 class="card-title">Aging Stocks</h4>

                    <div class="table-responsive ">
                        <table id="dt-1"
                               class="display nowrap table table-hover table-striped table-bordered aging-stock">
                            <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Stock</th>
                                <th>days</th>
                                <th>warehouse</th>
                                <th>value <small>(<?=Yii::$app->params['currency']?>)</small></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (empty($aging)){
                                ?>
                                <tr>
                                    <td colspan="13" align="center">There is no any to be out of stock right now</td>
                                </tr>
                                <?php
                            }
                            ?>
                            <?php
                            $i = 1;
                            foreach ($aging as $key=>$val):
                                ?>
                                <tr>

                                    <td><?= $val['sku'] ?></td>
                                    <td><?= $val['stock'] ?></td>
                                    <td><?= $val['days'] ?></td>
                                    <td><?= $val['warehouse'] ?></td>
                                    <td><?= $val['value'] ?></td>
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
<?php
if (!empty($aging))
    $this->registerJs('$(\'.aging-stock\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($oosIsis))
    $this->registerJs('$(\'.out-of-stock-in-isis-test\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
/*if (isset($oos_test))
    $this->registerJs('$(\'.out-of-stock-in-isis-test\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);*/
if (!empty($soon_out_of_stock))
    $this->registerJs('$(\'.to-be-out-of-stock-soon\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($upcomingDeal))
    $this->registerJs('$(\'.upcoming-deals\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
if (!empty($current_under_stock_order))
    $this->registerJs('$(\'.negative-margin-skus\').DataTable({
        dom: \'Bfrtip\',
        buttons: [
            \'csv\'
        ]
    });', View::POS_END);
$this->registerJs("
$('#stocks_value').click(function(){
    window.location.href = \"/inventory/warehouses-inventory-stocks?page=1\";
})
");

//$this->registerJsFile(
//    '@web/monster-admin/js/toastr.js',
//    ['depends' => [\backend\assets\AppAsset::className()]]
//);
//
//$this->registerJsFile('/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerJsFile('/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerJsFile('/monster-admin/assets/plugins/echarts/echarts-all.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerJsFile('/monster-admin/js/aoa-monthlyStock.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/monster-admin/assets/plugins/css-chart/css-chart.css',['depends' => [\frontend\assets\AppAsset::className()]]);


?>
<!--<script>-->
<!--var StockValueA=--><?//=json_encode((isset($monthlyStock['refine'])) ? $monthlyStock['refine'] : '', JSON_NUMERIC_CHECK)?><!--;-->
<!--var StockValueB = --><?//=json_encode((isset($monthlyStock['refine'])) ? $monthlyStock['refine'] : '', JSON_NUMERIC_CHECK)?><!--;-->
<!--var minv ='--><?//=json_encode($monthlyStock['min'], JSON_NUMERIC_CHECK)?><!--';-->
<!--var maxv = '--><?//=json_encode($monthlyStock['max'] , JSON_NUMERIC_CHECK)?><!--';-->

<!--</script>-->
