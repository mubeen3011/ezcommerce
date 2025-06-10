<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 5/16/2018
 * Time: 10:45 AM
 */

use backend\util\HelpUtil;
use common\models\Category;
use common\models\Channels;
use yii\helpers\ArrayHelper;
use yii\web\View;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['dashboard']];
$this->params['breadcrumbs'][] = 'Dashboard';
$userid= Yii::$app->user->identity;
$postDate = isset($_POST['filter']['date']) ? $_POST['filter']['date'] : "";
$type = isset($_POST['filter']['y_type']) ? $_POST['filter']['y_type'] : "monthly";
$year = isset($_POST['filter']['year']) ? $_POST['filter']['year'] : date('Y');
$marketplace = isset($_POST['filter']['marketplace']) ? $_POST['filter']['marketplace'] : "all";
$channeln = isset($_POST['filter']['shops']) ? $_POST['filter']['shops'] : "all";
$cat = isset($_POST['filter']['cat']) ? $_POST['filter']['cat'] : "all";
$brand_selected= isset($_POST['filter']['brand']) ? $_POST['filter']['brand'] : "all";
$forcast = HelpUtil::getMonthSales($postDate);
//echo $forcast; die();
$currency = Yii::$app->params['currency'];
$distributorsale = HelpUtil::getDistMonthSales($postDate);
//echo'<pre>';print_r($distributorsale);die;
$marketp = Channels::find()->distinct('marketplace')->where(['is_active'=>'1','is_fetch_sales' => '1'])->asArray()->all();
$marketpList = ArrayHelper::map($marketp, 'marketplace', 'marketplace');
if ($marketplace != 'all') {
    $channel = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1', 'marketplace' => strtolower($marketplace)])->asArray()->all();
    $channelList = ArrayHelper::map($channel, 'id', 'name');
} else {
    $channel = Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1'])->asArray()->all();
    $channelList = ArrayHelper::map($channel, 'id', 'name');
}
$category = Category::find()->where(['is_active' => '1','parent_id'=>null])->asArray()->all();
$categoryList = ArrayHelper::map($category, 'id', 'name');

$state_by=(isset($_GET['stats_by']) && $_GET['stats_by']=="orders") ? "orders":"amount";
?>
    <style>
        .panel-body {
            height: 200px !important;
        }

        .panel-heading {
            background-color: lightgrey;
        }

        #sales-target-div svg text {
            display: none;
        }

        .amcharts-export-menu-top-right {
            display: none
        }

        #month-chart-div {
            width: 100% !important;
            height: 500px !important;
        }
        .ct-series-a .ct-bar, .ct-series-a .ct-line, .ct-series-a .ct-point, .ct-series-a .ct-slice-donut
        {
            stroke:#298CB4 !important;
        }
        .sales-target-chart .ct-series-b .ct-bar
        {
            /*color:#ff331e !important;*/
            color:#00000073 !important;
        }
        .ct-series-b .ct-bar, .ct-series-b .ct-line, .ct-series-b .ct-point, .ct-series-b .ct-slice-donut
        {
            color:#B4DEE3 !important;
        }
        .color-graph-sale
        {
            color:#298CB4 !important;
        }
        .color-graph-target
        {
            color:#B4DEE3 !important;
            /*color:#00000073 !important;*/
        }
        /*.sales-target-chart .ct-bar
        {
            stroke-width:28px;
        }*/
    </style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body" style="height: auto !important;">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Sales Dashboard</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <a class="btn btn-sm btn-info btn-rounded <?= $state_by=="orders" ? "fa fa-check":"";?>" href="?stats_by=orders"> Sales Orders</a> |
                        <a class="btn btn-sm btn-info btn-rounded <?= $state_by=="amount" ? "fa fa-check":"";?>" href="?stats_by=amount"> Sales Amount</a>
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

        <div class="col-lg-12">

            <div class="card">
                <div class="card-body">
                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <h4>Yearly Sales&nbsp;
                                <button title="filter" type="button" class=" btn btn-info" id="filters">
                                    <i class="fa fa-filter"></i>
                                </button>

                            </h4>
                            <div class="filters-box <?=(isset($_POST['filter'])) ? '' : 'hide'?>">
                                <form class="col-md-12" method="post" style="font-size: 12px !important;">
                                    <label class="radio-inline">
                                        <input type="radio" name="filter[y_type]"
                                               value="monthly" <?= $type == 'monthly' ? 'checked' : '' ?>>Monthly
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="filter[y_type]"
                                               value="quarterly" <?= $type == 'quarterly' ? 'checked' : '' ?>>Quarterly
                                    </label>
                                    <label class="checkbox-inline">Year
                                        <select class="form-control" name="filter[year]">
                                             <?php
                                             for($i=date('Y')-2;$i<=date('Y');$i++)
                                            {
                                                $selected = ($i == $year) ? 'selected' : '';
                                                echo '<option '.$selected.' value='.$i.'>'.$i.'</option>';
                                            }
                                            ?>
                                        </select>
                                    </label>
                                    <label class="checkbox-inline">Marketplace
                                        <select class="form-control marketplace-filter" name="filter[marketplace]">
                                            <option value="all">All</option>
                                            <?php foreach ($marketpList as $mp): ?>
                                                <option value="<?= $mp ?>" <?= ($mp == strtolower($marketplace)) ? 'selected="selected"' : '' ?>><?= ucwords($mp) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                        <label class="checkbox-inline">Shops
                                            <select class="form-control shop-filter" name="filter[shops]">
                                                <option value="all">All</option>
                                                <?php foreach ($channelList as $k => $ch): ?>
                                                    <option class="shop-options" value="<?= $k ?>" <?= ($k == strtolower($channeln)) ? 'selected="selected"' : '' ?>><?= ucwords($ch) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                    <label class="checkbox-inline">Category
                                        <select class="form-control" name="filter[cat]">
                                            <option value="all">All</option>
                                            <?php foreach ($categoryList as $k => $ch):
                                                if($ch == '')
                                                    continue;
                                                ?>
                                                <option value="<?= $k ?>" <?= ($k == $cat) ? 'selected="selected"' : '' ?>><?= strtoupper($ch) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="checkbox-inline">Brand
                                        <select class="form-control" name="filter[brand]">
                                            <option value="all">All</option>
                                            <?php if(isset($brands) && !empty($brands)) : ?>
                                            <?php foreach ($brands as $brand):
                                                if(!$brand['brand'])
                                                    continue;
                                                ?>
                                                <option value="<?= $brand['brand'] ?>" <?= ($brand['brand']== $brand_selected) ? 'selected="selected"' : '' ?>><?= strtoupper($brand['brand']) ?></option>
                                            <?php endforeach; ?>
                                            <?php endif;?>
                                        </select>
                                    </label>
                                    <button type="submit" class="btn btn-small btn-info"><i class="fa fa-filter"></i>
                                        Filter
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-3 alert alert-info">
                                <h3>Total <?=($year != 'all') ? 'Year '.$year : ''?> <?= $state_by=="orders" ? "Orders":"Sales";?>: <span
                                            style="font-weight: bold"><?= $state_by=="orders" ? $yearSales['totalSales']:number_format($yearSales['totalSales'], 2) ?></span>
                                </h3>
                            </div>
                            <?php
                            if ( number_format($yearSales['totalSales'], 2)==0 ){
                                ?>
                                <h1 style="text-align: center">No Sales Found</h1>
                                <?php
                            }
                            ?>
                            <div class="panel-body" id="year-chart-div" style="height: 400px !important;padding: 0 !important;">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-------==========sales by shop graph /per/month /per/quarter====-------------->

            <?php if(isset($sales_by_shop_per_month['data'])) { ?>
                <div class="row">
                    <!-- sales analytics -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="height: auto !important;" id="graph_2_image">
                                <h4 class="card-title"><?= $state_by=="orders" ? "Orders":"Sales";?> by shop
                                    <a class="fa fa-filter text-info" data-toggle="tooltip" title="Filter" id="sales_by_shop_filter_btn">
                                    </a>
                                    <?php if(isset($_GET['graph_filter_applied'])){ ?>
                                        <a class="fa fa-filter text-danger" data-toggle="tooltip" title="Clear Filter" href="/sales/dashboard">
                                        </a>
                                    <?php } ?>
                                    <span  style="cursor:pointer" class="fa fa-image text-secondary pull-right graph_2_image" data-toggle="tooltip" title="Save Image">
                                 </span>
                                </h4>
                                <!-----filter---------->
                                <form action="" role="form" class="form-horizontal" >
                                    <div class="row sales_by_shop_filter_box" style="display:none">

                                        <div class="col-sm-3">
                                            <input name="graph_filter_applied" type="hidden" value="yes">
                                            <?php if(isset($_GET['customer_type'])){ ?>
                                                <input name="customer_type" type="hidden" value="<?= $_GET['customer_type'];?>">
                                            <?php } ?>
                                            <?php if(isset($_GET['stats_by'])){ ?>
                                                <input name="stats_by" type="hidden" value="<?= $_GET['stats_by'];?>">
                                            <?php } ?>
                                            <label class="radio-inline">Calendar sort</label>
                                            <select class="form-control form-control-sm" name="filter_graph_calendar_sort">
                                                <option value="monthly" <?= (isset($_GET['filter_graph_calendar_sort']) && $_GET['filter_graph_calendar_sort']=="monthly") ? "selected":"";?>>
                                                    Monthly
                                                </option>
                                                <option value="quarterly" <?= (isset($_GET['filter_graph_calendar_sort']) && $_GET['filter_graph_calendar_sort']=="quarterly") ? "selected":"";?>>
                                                    Quarterly
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="radio-inline">Display By</label>
                                            <select class="form-control form-control-sm" name="filter_graph_display_by">
                                                <option value="shop" <?= (isset($_GET['filter_graph_display_by']) && $_GET['filter_graph_display_by']=="shop") ? "selected":"";?>>
                                                    Shop
                                                </option>
                                                <option value="marketplace" <?= (isset($_GET['filter_graph_display_by']) && $_GET['filter_graph_display_by']=="marketplace") ? "selected":"";?>>
                                                    MarketPlace
                                                </option>

                                            </select>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="radio-inline">Year</label>
                                            <select class="form-control form-control-sm" name="filter_graph_year">
                                                <option value="">ALL</option>
                                                <?php for($y=date('Y');$y >= (date('Y')-2);$y--) { ?>
                                                    <option value="<?= $y;?>" <?= (isset($_GET['filter_graph_year']) && $_GET['filter_graph_year']==$y) ? "selected":"";?>><?= $y;?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <?php if(isset($categories) && $categories) { ?>
                                            <div class="col-sm-3">
                                                <label class="radio-inline">Categories</label>
                                                <select class="form-control form-control-sm" name="filter_cat">
                                                    <option value="">ALL</option>
                                                    <?php foreach($categories as $cat_row) { ?>
                                                        <option value="<?= $cat_row['id'];?>" <?= (isset($_GET['filter_cat']) && $_GET['filter_cat']==$cat_row['id']) ? "selected":"";?>><?= $cat_row['name'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php  } ?>
                                        <?php if(isset($brands) && $brands) { ?>
                                            <div class="col-sm-3">
                                                <label class="radio-inline">Brand</label>
                                                <select class="form-control form-control-sm" name="filter_brand">
                                                    <option value="">ALL</option>
                                                    <?php foreach($brands as $brand) { if(empty($brand['brand'])) continue; ?>
                                                        <option value="<?= $brand['brand'];?>" <?= (isset($_GET['filter_brand']) && $_GET['filter_brand']==$brand['brand']) ? "selected":"";?>><?= $brand['brand'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php  } ?>
                                        <?php if(isset($product_styles) && $product_styles) { ?>
                                            <div class="col-sm-3">
                                                <label class="radio-inline">Style</label>
                                                <select class="form-control form-control-sm" name="filter_style">
                                                    <option value="">ALL</option>
                                                    <?php foreach($product_styles as $product_style) { if(empty($product_style['style'])) continue; ?>
                                                        <option value="<?= $product_style['style'];?>" <?= (isset($_GET['filter_style']) && $_GET['filter_style']==$product_style['style']) ? "selected":"";?>><?= $product_style['style'];?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        <?php  } ?>
                                        <div class="col-sm-3">
                                            <label class="radio-inline">&nbsp;</label>
                                            <button class="btn btn-sm btn-secondary btn-block"> APPLY</button>
                                        </div>

                                    </div>
                                </form>
                                <!-------------------->
                                <?php if(empty($sales_by_shop_per_month['data'])) { ?>
                                    <h6 class="text-center card-title"> No Record Found</h6>
                                <?php } ?>
                                <div id="sales-by-shop-per-month-graph"></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <!------------------------------------------Target---------------------------------------->
            <?php if(isset($targets) && !empty($targets)) {  ?>
            <!-- Row -->
            <div class="row">
                <!-- Column -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body" >
                            <ul class="list-inline pull-right">
                                <li>
                                    <h6 class="text-muted"><i class="fa fa-circle m-r-5 color-graph-sale"></i>Sales</h6>
                                </li>
                                <li>
                                    <h6 class="text-muted"><i class="fa fa-circle m-r-5 color-graph-target"></i>Target</h6>
                                </li>

                            </ul>
                            <h4 class="card-title">Sales Target
                            </h4>
                            <div class="sales-target-chart" style="height: 366px;"></div>
                        </div>
                    </div>
                </div>
                <!--Column -->
                <!--<div class="col-lg-4">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Sales Prediction</h4>
                                    <div class="d-flex flex-row">
                                        <div class="align-self-center">
                                            <span class="display-6 text-primary">$3528</span>
                                            <h6 class="text-muted">10% Increased</h6>
                                            <h5>(150-165 Sales)</h5>
                                        </div>
                                        <div class="ml-auto">
                                            <div id="gauge-chart" style=" width:150px; height:150px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Sales Target Difference</h4>
                                    <div class="d-flex flex-row">
                                        <div class="align-self-center">
                                            <span class="display-6 text-success">$4316</span>
                                            <h6 class="text-muted">10% Increased</h6>

                                        </div>
                                        <div class="ml-auto">
                                            <div class="ct-chart" style="width:120px; height: 120px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>-->
                <!-- Column -->
            </div>
            <?php } ?>
            <!------------------------------------------Target---------------------------------------->
            <div class="card">
                <div class="card-body">
                    <div class="form-group col-md-12" >
                        <h4>Daily <?= $state_by=="orders" ? "Orders":"Sales";?>
                        </h4>
                        <form id="dfilter" action="/sales/dashboard<?= isset($_GET['stats_by']) ? "?stats_by=".$_GET['stats_by']:""; ?>" method="post">
                            <input name="_csrf-backend"
                                   value="yrt3DdYTmDpR3T9KB_z_hbByCmeOiY7KXSoSNd-yf-m_gzI9lWLceQLvfCBoz7jLnTdBHd3j4JxwTXcA5oYN0Q=="
                                   type="hidden">
                            <label class="control-label">Date Range:</label>

                            <div class="input-group mb-3">
                                <input class="form-control input-daterange-datepicker"
                                       value="<?= isset($_POST['filter']['date']) ? $_POST['filter']['date'] : '' ?>"
                                       type="text" name="filter[date]"/>
                                <div class="input-group-append">
                                       <span class="input-group-text">
                                           <span class="ti-calendar"></span>
                                       </span>
                                </div>
                            </div>
                        </form>
                        <div class=" col-md-12">
                            <div class="row">

                                <div class="col-md-3 alert alert-info">
                                <h3>Total <?=$state_by=="orders" ? " Orders":" Sales";?> <span
                                            style="font-weight: bold"><?= $forcast['cur'] ?></span>
                                </h3>
                                </div>
                                <div class ="">
                                    &nbsp;
                                    &nbsp;
                                </div>
    <!--                    </div>-->
                                <?php
                                if($userid['role_id'] != 8){

                                    foreach ($distributorsale as $dist_sale=>$value) {
                                        if (!is_array($dist_sale)) ?>
                                            <div class="col-md-3 alert alert-info">
                                            <h2 class="font-light text-black" style="font-size: 18px;font-weight: bold">
                                        <?= $currency ?> <?= \backend\util\HelpUtil::number_format_short($value['current_total']) . "" ?>
                                        </h2>
                                                <h6 class="text-black"><?= $value['username']; ?></h6>

                                            </div>
                                            <?php
                                    }
                                            ?>
                                <?php
                                    }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <h4>Daily <?= $state_by=="orders" ? "Orders":"Sales";?> - <?= ($postDate != '') ? 'from ' . $postDate : date('F, Y') ?></h4>
                            <div class="panel-body" id="month-chart-div"
                                 style="height: 400px !important;padding: 0 !important;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="col-md-12">
                        <div class="panel panel-default" >
                            <h4><?= $state_by=="orders" ? "Orders":"Sales";?> by Marketplace
                                - <?= ($postDate != '') ? 'from ' . $postDate : date('F, Y') ?>

                            </h4>
                            <div class="panel-body" id="shop-chart-div"
                                 style="height: 500px !important;padding: 0 !important;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php

/*$this->registerJs('
 var setVal = ' . $st['sales'] . ';
 var setTarget = ' . $st['target'] . ';
 var dapval = ' . $dapSales . ';
 var mccval = ' . $mccSales . ';
 var revenueList = ' . $revenueList . ';
 var monthlySales = ' . $monthlySales . ';
 var mpSales = ' . $mpSales . ';',
    View::POS_HEAD);*/
//echo $mpSales;die;
$this->registerJs('
 var setTarget = 100000;
 var monthlySales = ' . $monthlySales . ';
 var mpSales = ' . $mpSales . ';
 ',

    View::POS_HEAD);
?>
    <script>
        var monthlySales = <?=$monthlySales?>;
        <?php if(isset($targets) && !empty($targets)) ?>
        var targets=<?= json_encode($targets);?>;
        var mpSales = <?=$mpSales?>;
        var clickEv = true;
        var postDate = '<?=base64_encode($postDate)?>';
        var avgDaySales = '<?=$avgDaySales?>';
        var avgSixMonthDaySales = '<?=$avgSixMonthDaySales?>';
        var yearSales = <?=$yearSales['json']?>;
        // yearly sales params for custom links
        var cYear = '<?= $year ?>';
        var cType = '<?= $type ?>';
        var cMarketplace = '<?= $marketplace ?>';
        var cShop = '<?= $channeln ?>';
        var cCategory = '<?= $cat ?>';
        var currency = '<?= $state_by=="orders" ? "":Yii::$app->params['currency']?>';
        var sales_by_shop_per_month= <?= json_encode($sales_by_shop_per_month);?>; // sales by shop/marketplace /per mnth/quarter
        //var w = '<?//= $w ?>//';
      //  avgDaySales=1;
     //  console.log(targets);
    </script>
<?php
$this->registerJsFile('/monster-admin/amcharts/amcharts.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/amcharts/gauge.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/amcharts/serial.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js?v=' . time(), [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js?v=' . time(), [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/js/aoa-sales-chart.js?v=' . time(), [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

/// bar chart per month /quarter sales graph
$this->registerJsFile('/monster-admin/assets/plugins/raphael/raphael-min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/morrisjs/morris.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
///html 2 canvas to save image of content/graph
$this->registerJsFile('html2canvas/html2canvas.min.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);

$this->registerJs(<<< EOT_JS_CODE
 



$('.graph_2_image').click(function() {
    html2canvas(document.getElementById('graph_2_image')).then(canvas => {
        var w = document.getElementById("graph_2_image").offsetWidth;
        var h = document.getElementById("graph_2_image").offsetHeight;
        save_image_as(canvas.toDataURL("image/jpeg"),new Date().toISOString().slice(0, 10) + '_graph_2_image.jpg');
    }).catch(function(e) {
        console.log(e.message);
    });
    
});



function save_image_as(uri, filename) {

    var link = document.createElement('a');
     if (typeof link.download === 'string') {

        link.href = uri;
        link.download = filename;

        //Firefox requires the link to be in the body
        document.body.appendChild(link);

        //simulate click
        link.click();

        //remove the link when done
        document.body.removeChild(link);

    } else {

        window.open(uri);

    }
}
////filter
$('#sales_by_shop_filter_btn').click(function(){
$('.sales_by_shop_filter_box').toggle();
});
EOT_JS_CODE
);
?>
<script>
    var isPstDat = '<?=$postDate?>';
    var default_graph_sales_quarter = '<?=$quarter_sales?>';
</script>

