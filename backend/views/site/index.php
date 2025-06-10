<?php

use backend\controllers\StocksController;
use backend\util\HelpUtil;
use yii\helpers\Url;
use yii\web\View;
//use common\models\User;

$sign = ($forcast['prev'] < 0) ? "down" : "up";
$html = "";
$this->title = '';
$this->params['breadcrumbs'][] = 'Dashboard';
$userid= Yii::$app->user->identity;
$currency = Yii::$app->params['currency'];
//$aging = HelpUtil::getAgingStock();
//echo '<pre>';print_r($aging);die;
?>
    <style type="text/css">

        .carousel-wrap {
            width: 1000px;
            margin: auto;
            position: relative;
        }
        .owl-carousel .owl-nav{
            overflow: hidden;
            height: 0px;
        }

        .owl-theme .owl-dots .owl-dot.active span,
        .owl-theme .owl-dots .owl-dot:hover span {
            background: #2caae1;
        }


        .owl-carousel .item {
            text-align: center;
        }
        .owl-carousel .nav-btn{
            height: 47px;
            position: absolute;
            width: 26px;
            cursor: pointer;
            top: 100px !important;
        }

        .owl-carousel .owl-prev.disabled,
        .owl-carousel .owl-next.disabled{
            pointer-events: none;
            opacity: 0.2;
        }

        .owl-carousel .prev-slide{
            background: url(/images/nav-icon.png) no-repeat scroll 0 0;
            left: -15px;
        }
        .owl-carousel .next-slide{
            background: url(/images/nav-icon.png) no-repeat scroll -24px 0px;
            right: -15px;
        }
        .owl-carousel .prev-slide:hover{
            background-position: 0px -53px;
        }
        .owl-carousel .next-slide:hover{
            background-position: -24px -53px;
        }

        span.img-text {
            text-decoration: none;
            outline: none;
            transition: all 0.4s ease;
            -webkit-transition: all 0.4s ease;
            -moz-transition: all 0.4s ease;
            -o-transition: all 0.4s ease;
            cursor: pointer;
            width: 100%;
            font-size: 23px;
            display: block;
            text-transform: capitalize;
        }
        span.img-text:hover {
            color: #2caae1;
        }


        .owl-carousel .owl-item img{
            max-width:150px !important;
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
      /*  .box {
            height: 120px !important;
        }*/
        /*******ribbon****/
    .label-secondary{
        background: #54667A;
    }

    </style>

<link href="/owl_courosal/dist/assets/owl.carousel.min.css" rel="stylesheet">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" style="height: auto !important;">
                    <div class=" row">
                        <div class="col-md-4 col-sm-12">
                            <h3  data-toggle="tooltip" title="In the memory of Umar Talha , one of the pioneer in laying foundation of EZ commerce ">UT Dashboard</h3>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <!--<span class="fa fa-user" data-toggle="tooltip" title="Customer Type"></span>-->
                            <a class="btn btn-sm btn-info btn-rounded <?= !isset($_GET['customer_type']) ? "fa fa-check":"";?>" href="/" > ALL</a> |
                            <a class="btn btn-sm btn-info btn-rounded <?= (isset($_GET['customer_type']) && $_GET['customer_type']=="b2b") ? "fa fa-check":"";?>" href="?customer_type=b2b"> B2B</a> |
                            <a class="btn btn-sm btn-info btn-rounded <?= (isset($_GET['customer_type']) && $_GET['customer_type']=="b2c") ? "fa fa-check":"";?>" href="?customer_type=b2c"> B2C</a>
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
        <!-- Column -->

        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-info">
                <div class="box bg-info text-center">
                    <h1 class="font-light text-white">
                           <?php  $total_revenue=isset($marketplace_sales_cont['total_revenue']) ? $marketplace_sales_cont['total_revenue']:0 ; ?>
                            <?= $currency . HelpUtil::number_format_short($total_revenue,2);?>
                    </h1>
                    <h6 class="text-white">Sales Revenue</h6>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-success">
                <div class="box text-center">
                    <h1 class="font-light text-white">
                        <?php
                            $total_orders=isset($marketplace_sales_cont['sales']) ? array_sum(array_column($marketplace_sales_cont['sales'],'orders')):0;
                            echo $total_orders;
                        ?>
                    </h1>
                    <h6 class="text-white">Total Orders
                    </h6>
                </div>
            </div>
        </div>
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-info">
                <div class="box text-center">
                    <h1 class="font-light text-white">
                            <?= $total_orders > 0 ? $currency . HelpUtil::number_format_short (ceil($total_revenue/$total_orders),2):'-';?>
                    </h1>
                    <h6 class="text-white">Average Basket Size</h6>
                </div>
            </div>
        </div>
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-primary card-inverse">
                <div class="box text-center">
                    <h1 class="font-light text-white total_customers_box">
                            <?php
                            $total_customers=isset($marketplace_sales_cont['sales']) ? array_sum(array_column($marketplace_sales_cont['sales'],'customers')):0;
                            echo $total_customers;
                            ?>
                    </h1>
                    <h6 class="text-white">Customers</h6>
                </div>
            </div>
        </div>
        <!-- Column -->
    </div>


    <!--    #########################################-->

    <!-- Row -->
    <div class="row">
        <!-- sales analytics -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" style="height: auto !important;" >
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
                    <div class="total-sales" ></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Row -->
<!-------==========sales by shop graph /per/month /per/quarter====-------------->

<?php if(isset($sales_by_shop_per_month['data'])) { ?>
    <div class="row">
        <!-- sales analytics -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-body" style="height: auto !important;">
                    <h4 class="card-title">Sales by shop
                        <a class="fa fa-filter text-info" data-toggle="tooltip" title="Filter" id="sales_by_shop_filter_btn">
                        </a>
                        <?php if(isset($_GET['graph_filter_applied'])){ ?>
                        <a class="fa fa-filter text-danger" data-toggle="tooltip" title="Clear Filter" href="/">
                        </a>
                        <?php } ?>
                        <span  style="cursor:pointer" class="fa fa-image text-secondary pull-right save_image_bg" data-toggle="tooltip" title="Save Image">
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
                                <div class="col-sm-2">
                                    <label class="radio-inline">&nbsp;</label>
                                    <button class="btn btn-sm btn-secondary btn-block"> APPLY</button>
                                </div>

                            </div>
                    </form>
                        <!-------------------->
                    <?php if(empty($sales_by_shop_per_month['data'])) { ?>
                        <h6 class="text-center card-title"> No Record Found</h6>
                    <?php } ?>
                    <span id="per_month_all_shops_graph">
                    <div id="sales-by-shop-per-month-graph"></div>
                    <!---colors---->
                    <?php if($sales_by_shop_per_month['data']) { ?>
                    <hr/>
                    <ul class="list-inline text-center">
                        <?php foreach($sales_by_shop_per_month['labels'] as $index=>$label) { ?>
                        <li>
                            <h6 style="font-size: 12px;">
                                <i class="fa fa-circle m-r-5 text-inverse" style="color: <?= isset($sales_by_shop_per_month['colors'][$index]) ? $sales_by_shop_per_month['colors'][$index]:"";?> !important"></i>
                                <?= ucfirst($label);?>
                            </h6>
                        </li>
                        <?php } ?>
                    </ul>
                    <?php } ?>
                    </span>
                    <!------------->
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<!--===============================pie chart by marketplace cntributiion and distributors sale=============================-->
<div class="card">
<div class="row ">

    <!----pie chart contribution by marketplace----------->
    <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12" id="pie_graph_span">
        <br/>
        <h4 class="card-title text-center">Contribution by Shops
            <span  style="cursor:pointer" class="fa fa-image text-secondary save_image_pie_graph" data-toggle="tooltip" title="Save Image">
              </span>
        </h4>
        <div id="chartContainer" style="height: 300px; width: 100%;"></div>
    </div>
    <!--========================distributors sale /insights===========================-->
    <?php if(isset($distributorsale) && !empty($distributorsale)) : ?>
        <br/><br/>
        <div class="col-md-6 col-lg-6"> <!----column ---->


            <div class="owl-carousel owl-theme" id="owl-carousel-dealers">
                <?php
                foreach($distributorsale as $dealer_insight) {  ;?>
                    <div class="card">
                        <br/>
                        <h4 class="card-title text-center">Distributor Insights</h4>
                        <br/><br/>
                        <div class="card-body analytics-info" style="padding-top:0">
                            <h4 class="card-title"><?= strtoupper($dealer_insight['dealer_name']);?>
                                <small class="pull-right">Orders: <?= $dealer_insight['orders'];?></small>
                            </h4>
                            <table class="" style="width:100%">
                                <tbody>
                                <tr>
                                    <td style="border-right:1px solid lightgray">
                                        <h6>Avg Monthly sale</h6></td>
                                    <td style="border-right:1px solid lightgray;padding-left:2%"><h6>Avg basket size</h6></td>
                                    <td style="padding-left:2%"><h6>Avg monthly orders</h6></td>
                                </tr>
                                <tr>
                                    <td style="border-right:1px solid lightgray"><?= $currency. HelpUtil::number_format_short(round($dealer_insight['sales']/($dealer_insight['first_order_days_passed']/30.417)),2);?></td>
                                    <td style="border-right:1px solid lightgray;padding-left:2%"><?= $currency. round($dealer_insight['sales']/$dealer_insight['orders']);?></td>
                                    <td style="padding-left:2%"><?= round($dealer_insight['orders']/($dealer_insight['first_order_days_passed']/30.417));?></td>
                                </tr>
                                </tbody>
                            </table>

                            <div>

                                <div class="row">
                                    <div class="col-md-6">

                                        <img src="/monster-admin/assets/images/users/1.png"  style="width:175px;height:100px;object-fit:contain"  class="img-responsive">
                                    </div>
                                    <div class="col-md-6">
                                        <br/><br/>
                                        <h1 class="font-light m-b-0" >
                                            <?= $currency. HelpUtil::number_format_short($dealer_insight['sales'],2) ;?>
                                        </h1>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                <?php } ?>


            </div>
        </div><!----- column --->
    <?php endif;?>
    <!--================================================-->
</div>
</div>

<!----------------------------------HL sales----------------------->
<?php if(isset($marketplace_sales_cont['sales']) && !empty($marketplace_sales_cont['sales'])) : ?>
<div class="row">
    <div class="col-12">
        <br>
        <br>
        <h4 class="card-title">Shop Insights</h4>
        <h6 class="card-subtitle">&nbsp;</code></h6>
    </div>
</div>
    <div class="owl-carousel owl theme" id="owl-carousel-platform">
        <?php
        $p_c=0;
        foreach($marketplace_sales_cont['sales'] as $platform_insight) { ;?>
        <div class="col-lg-12 ">
            <div class="card">
                <div class="card-body analytics-info">
                    <a href="sales/report-by-shop?shop=<?= $platform_insight['channel'];?>">
                    <h4 class="card-title"><?= strtoupper($platform_insight['channel']);?>
                        <small class="text-center"> ( view detail ) </small>
                        <small class="label label-rounded label-secondary pull-right "><?= ++$p_c; ?></small>
                    </h4>
                    </a>
                    <div class="table-responsive">
                    <table class="" style="width:100%">
                        <tbody>
                        <tr>
                                <td style="border-right:1px solid lightgray">
                                    <h6>Avg Monthly sale</h6></td>
                            <td style="border-right:1px solid lightgray;padding-left:2%"><h6>Avg basket size</h6></td>
                            <td style="padding-left:2%"><h6>Avg monthly orders</h6></td>
                        </tr>
                        <tr>
                                <td style="border-right:1px solid lightgray">
                                    <?php $first_order['months_passed']= $first_order['months_passed'] ? $first_order['months_passed']:1;?>
                                    <?= $currency. HelpUtil::number_format_short(round($platform_insight['sales']/$first_order['months_passed']),2);?>
                                </td>
                                <td style="border-right:1px solid lightgray;padding-left:2%"><?= $currency. round($platform_insight['sales']/$platform_insight['orders']);?></td>
                                <td style="padding-left:2%"><?= round($platform_insight['orders']/$first_order['months_passed']);?></td>
                        </tr>
                        </tbody>
                    </table>
                    </div>
                    <div>

                        <div class="row">
                            <div class="col-md-6 ">
                                <img src="<?=  Url::to('@web/').$platform_insight['channel_logo']?>" alt="<?= $platform_insight['channel']?>" style="max-width:175px;max-height:100px;"  class="img-responsive">
                            </div>
                            <div class="col-md-6">
                                <br/>
                                <h1 class="font-light m-b-0">
                                    <?= $currency. HelpUtil::number_format_short($platform_insight['sales'],2) ;?>
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

    </div>
<?php endif;?>


<!-----------------------Sales by marketplace-------------------------------->
<?php
$average_basket_size_td="";
$sales_total_mp=0; //total sales
$sales_total_mp_td="";
$orders_total_mp=0; //total orders
$orders_total_mp_td="";
$average_sale_per_month_td="";
$average_sale_per_week_td="";
$average_sale_per_day_td="";
$average_order_per_month_td="";
$average_order_per_week_td="";
$average_order_per_day_td="";

if(isset($marketplace_sales_cont) && !empty($marketplace_sales_cont)) {
        $marketplaces=array_column($marketplace_sales_cont['sales'],'channel');
        //print_r($marketplace);
    ?>
<div class="row">
    <div class="col-md-12">
    <div class="card">
        <div class="card-body table-responsive">
            <h4 class="card-title"><span class="fa fa-cart-plus"></span> Sales By Shop</h4>
    <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
    <thead>
    <tr>
        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist"></th>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Total sales to date</th>
        <?php foreach($marketplaces as $key=>$val){ ?>
        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="<?= $key;?>"><?= $val;?></th>
         <?php } ?>

    </tr>
    </thead>
    <tbody>
    <?php

    foreach($marketplace_sales_cont['sales'] as $row) {
        $sales_total_mp +=$row['sales'];
        $orders_total_mp +=$row['orders'];
        $average_basket_size_td .='<td class="static scale">'. $currency . round(($row['sales']/$row['orders'])).' </td>';
        $sales_total_mp_td .='<td class="static scale">'. $currency . HelpUtil::number_format_short(round($row['sales']),2).' </td>';
        $orders_total_mp_td .='<td class="static scale">'.  round($row['orders']).' </td>';
        $average_sale_per_month_td .='<td class="static scale">'. $currency . HelpUtil::number_format_short(round($row['sales']/$first_order['months_passed']),2).' </td>';
        $average_sale_per_week_td .='<td class="static scale">'. $currency . HelpUtil::number_format_short(round($row['sales']/$first_order['weeks_passed']),2).' </td>';
        $average_sale_per_day_td .='<td class="static scale">'. $currency . round($row['sales']/$first_order['days_passed']).' </td>';
        $average_order_per_month_td .='<td class="static scale">'.  round($row['orders']/$first_order['months_passed']).' </td>';
        $average_order_per_week_td .='<td class="static scale">'.  round($row['orders']/$first_order['weeks_passed']).' </td>';
        $average_order_per_day_td .='<td class="static scale">'.  round($row['orders']/$first_order['days_passed']).' </td>';
    } ?>
    <tr>
        <td class="static scalec"> Average Basket Size</td>
        <td class="static scalec"> <?= $currency . round($sales_total_mp/$orders_total_mp); ?></td>
        <?= $average_basket_size_td; ?>
    </tr>
    <tr id="parent_row_1" data-id-pk="1">
        <td class="static scalec"> <span  data-id-pk='1' class="fa fa-plus-circle show_items"></span> Sales</td>
        <td class="static scale"><?= $currency . HelpUtil::number_format_short(round($sales_total_mp),2);?> </td>
        <?= $sales_total_mp_td ;?>
    </tr>
            <tr class="child_row_1 child-row" style="display:none">
                <td class="static scalec"><span class="pull-right"> Average Sales/Month</span></td>
                <td class="static scale"><?= $currency . HelpUtil::number_format_short(round($sales_total_mp/$first_order['months_passed']),2);?> </td>
                <?= $average_sale_per_month_td;?>
            </tr>
            <tr class="child_row_1 child-row" style="display:none" >
                <td class="static scalec"><span class="pull-right"> Average Sales/Week</span></td>
                <td class="static scale"><?= $currency . HelpUtil::number_format_short(round($sales_total_mp/$first_order['weeks_passed']),2);?> </td>
                <?= $average_sale_per_week_td;?>
            </tr>
         <tr class="child_row_1 child-row" style="display:none" >
                <td class="static scalec"><span class="pull-right"> Average Sales/Day</span></td>
                <td class="static scale"><?= $currency . ceil($sales_total_mp/$first_order['days_passed']);?> </td>
               <?= $average_sale_per_day_td;?>
            </tr>
    <tr id="parent_row_2" data-id-pk="2>">
        <td class="static scalec"> <span  data-id-pk='2' class="fa fa-plus-circle show_items"></span> Orders</td>
        <td class="static scale"><?= $orders_total_mp;?> </td>
        <?= $orders_total_mp_td ;?>
    </tr>
             <tr class="child_row_2 child-row" style="display:none" >
                <td class="static scalec"><span class="pull-right"> Average Orders/Month</span></td>
                <td class="static scale"><?= round($orders_total_mp/$first_order['months_passed']);?></td>
                <?= $average_order_per_month_td;?>
            </tr>
              <tr class="child_row_2 child-row" style="display:none" >
                <td class="static scalec"><span class="pull-right"> Average Orders/Week</span></td>
                <td class="static scale"><?= round($orders_total_mp/$first_order['weeks_passed']);?> </td>
                <?= $average_order_per_week_td;?>
            </tr>
             <tr class="child_row_2 child-row" style="display:none" >
                <td class="static scalec"><span class="pull-right"> Average Orders/Day</span></td>
                <td class="static scale"><?= ceil($orders_total_mp/$first_order['days_passed']);?> </td>
                 <?= $average_order_per_day_td;?>
            </tr>

    </tbody>
    </table>
</div>
</div>
</div>
</div>
<?php } ?>
<!-----------------------Brands Insight-------------------------------->
<?php if(isset($brands_sales) && !empty($brands_sales)) : ?>
    <div class="row">
        <div class="col-12">
            <br>
            <br>
            <h4 class="card-title">Brand Insights</h4>
            <h6 class="card-subtitle">&nbsp;</code></h6>
        </div>
    </div>
    <div class="owl-carousel owl theme" id="owl-carousel-brands">
        <?php $b_c=0;foreach($brands_sales as $brand_sale):?>
        <div class="col-lg-12 ">
            <div class="card">
                <!--------card body------->
                <div class="card-body analytics-info">
                    <h4 class="card-title"><?= strtoupper($brand_sale['stats']['brand'] ? $brand_sale['stats']['brand']:'non brand' );?>
                        <small class="text-center"> ( <?= ceil(($brand_sale['stats']['sales']/$total_revenue)*100);?> % of Sales) </small>
                        <small class="label label-rounded label-secondary pull-right "><?= ++$b_c; ?></small>
                    </h4>
                    <!-----stats------>
                    <div class="table-responsive">
                        <table class="" style="width:100%">
                            <tbody>
                            <tr>
                                <td style="border-right:1px solid lightgray">
                                    <h6>Avg Monthly sale</h6></td>
                                <td style="border-right:1px solid lightgray;padding-left:2%"><h6>Avg basket size</h6></td>
                                <td style="padding-left:2%"><h6>Avg monthly orders</h6></td>
                            </tr>
                            <tr>
                                <td style="border-right:1px solid lightgray">
                                    <?php $brand_first_order_month_passed= isset($brand_sale['first_order']['months_passed']) ? $brand_sale['first_order']['months_passed']:($first_order['months_passed'] ? $first_order['months_passed'] :1);?>
                                    <?= $currency. HelpUtil::number_format_short(round($brand_sale['stats']['sales']/$brand_first_order_month_passed),2);?>
                                </td>
                                <td style="border-right:1px solid lightgray;padding-left:2%"><?= $currency. round($brand_sale['stats']['sales']/$brand_sale['stats']['orders']);?></td>
                                <td style="padding-left:2%"><?= round($brand_sale['stats']['orders']/$brand_first_order_month_passed);?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-----stats------>
                    <div class="row">
                        <div class="col-md-6 ">
                            <img src="/images/<?= str_replace(' ','_',$brand_sale['stats']['brand'] ? $brand_sale['stats']['brand']:'non_brand')?>.png" alt="<?= $brand_sale['stats']['brand']?>" style="max-width:175px;max-height:100px;"  class="img-responsive">
                        </div>
                        <div class="col-md-6">
                            <br/>
                            <h1 class="font-light m-b-0">
                                <?= $currency. HelpUtil::number_format_short($brand_sale['stats']['sales'],2) ;?>
                            </h1>
                        </div>
                    </div>
                    <!----------->
                </div>
                <!-------card body--------->
            </div>
        </div>
        <?php endforeach;?>
    </div>
<?php endif;?>
<!--------------------------------------------------------------------->
<!-----------------------product detail analytics-------------------------------->

    <div class="row">
        <div class="col-12">
            <br>

            <h4 class="card-title">Product Details Insights</h4>
            <br>
            <!--<h6 class="card-subtitle">Just add in div<code> class="chart easy-pie-chart-1"</code></h6>-->
        </div>
    </div>
    <div class="row">
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-info">
                <div class="box bg-info text-center">
                    <h1 class="font-light text-white"><?= isset($parent_and_variations['parent_count']) ? $parent_and_variations['parent_count']:'X'?></h1>
                    <h6 class="text-white">Total Parents</h6>
                </div>
            </div>
        </div>
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-primary card-inverse">
                <div class="box text-center">
                    <h1 class="font-light text-white"><?= isset($parent_and_variations['variation_count']) ? $parent_and_variations['variation_count']:'X'?></h1>
                    <h6 class="text-white">Total Variations</h6>
                </div>
            </div>
        </div>
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-success">
                <div class="box text-center">
                    <h1 class="font-light text-white top_5_contribution_span"></h1> <!-- getting value by below loop and populating through js--->
                    <h6 class="text-white">Top 5 Contribution </h6>
                </div>
            </div>
        </div>
        <!-- Column -->
        <div class="col-md-6 col-lg-3 col-xlg-3">
            <div class="card card-inverse card-info">
                <div class="box text-center">
                    <h1 class="font-light text-white less_than_five_percent_contributors_span">  </h1>
                    <h6 class="text-white ">Less than 5% contributors </h6>
                </div>
            </div>
        </div>
    </div>

<!------------top 5 products performer------------------>
<!--    #########################################-->
<?php
$top_5_product_sales=0; //
if(isset($top_performers['products']) && !empty($top_performers['products'])) : ?>
<div class="row">
    <div class="col-12">
        <br>
        <br>
        <h4 class="card-title">Top 5 Performers</h4>
        <h6 class="card-subtitle">&nbsp;</code></h6>
    </div>
    </div>
    <div class="owl-carousel" id="owl-carousel-product">
      <?php
        $count=0;

        foreach($top_performers['products'] as $top_5) {
            $top_5_product_sales +=$top_5['sales'];
            ?>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body ">
                    <h6 style="height:35px;" data-toggle="tooltip" title="<?= $top_5['name'];?>"><?= strlen($top_5['name']) > 65 ? substr($top_5['name'],0,65)."..." : $top_5['name'];?>

                    </h6>
                    <div class="row">
                        <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
                            <img src="<?=($top_5['image'] && $top_5['image']!='x') ?  $top_5['image']:'/images/no_image-copy.jpg';?>" alt="<?= $top_5['name'];?> " style="max-width:120px" class="img-responsive">
                        </div>
                        <div class="col-md-6 col-lg-6 col-xs-12 col-sm-12">
                            <br/>
                            <div class="stat-item">
                                 <h6 class="text-muted" style="font-family:none">
                                     <i style="font-size:7px" class="fa fa-arrow-right"></i>
                                     contribution
                                     <?= $sales_total_mp > 0 ? ceil(($top_5['sales']/$sales_total_mp)*100):" - " ?> %
                                 </h6>

                            </div>
                            <div class="stat-item">
                                <h6 class="text-muted" style="font-family:none">
                                    <i style="font-size:7px" class="fa fa-arrow-right"></i>
                                    Sale <?= $currency . HelpUtil::number_format_short($top_5['sales'],2);?>
                                </h6>

                            </div>
                            <div class="stat-item">
                                <h6 class="text-muted" style="font-family:none" data-toggle="tooltip" title="Average monthly sale">
                                    <i style="font-size:7px" class="fa fa-arrow-right"></i>
                                    Avg sale  <?= $currency. ceil($top_5['sales']/$first_order['months_passed']) ;?>
                                </h6>

                            </div>
                        </div>

                    </div>
                    <small class="label label-rounded label-secondary pull-right"><?= ($count+ 1); ?></small>
                </div>
            </div>
        </div>
            <?php if(++$count==5){break;}} ?>
    </div>
<?php endif;?>
<!-----------------------Sales by marketplace-------------------------------->
<?php
$above_five_percent_contributors_count=0; // how many products have conribution more than 5% , // for less than 5% contributors formula
$total_contributors=isset($top_performers['products']) ? count($top_performers['products']):0; // all products list sent
if(isset($top_performers['products']) && !empty($top_performers['products'])) : ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body table-responsive">
                <h4 class="card-title">
                    <span class="fa fa-cart-plus"> </span> Top 10 contributors
                    <a href="/sales/top-performers"><small class="pull-right">View all</small></a>
                </h4>

                <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                    <thead>
                    <tr>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Product</th>
                        <?php foreach($top_performers['marketplaces'] as $row) { ?>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4"><?= $row;?></th>
                        <?php }?>
                        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">%age contribution</th>

                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($top_performers['products'] as $index=>$row) {
                        /***variations in the beginning */
                        $parent_marketplace_sale=[]; // store very variation individual marketplace wise sale // showing at parent top against each mp
                        ob_start();
                         if(isset($row['children']) && !empty($row['children'])) {
                            foreach($row['children'] as $child=>$child_data){
                                $variation_contribution=0;
                                ?>
                                <tr class="child_row_tp_<?= $index;?> child-row" style="display:none">
                                    <td class="static scalec"> &nbsp; &nbsp;-- <?= yii\helpers\Html::a($child,array("products/detail","sku"=>$child));?></td>
                                    <?php foreach($top_performers['marketplaces'] as $mplace_name) { // marketplaces
                                        //sum of all variation  against each mp
                                            if(isset($parent_marketplace_sale[$index][$mplace_name])) // if already have index increment the record
                                                $parent_marketplace_sale[$index][$mplace_name] += isset($child_data[$mplace_name]['sales']) ? ($child_data[$mplace_name]['sales']):0;
                                            else
                                                $parent_marketplace_sale[$index][$mplace_name] = isset($child_data[$mplace_name]['sales']) ? $child_data[$mplace_name]['sales']:0;

                                        $variation_contribution += isset($child_data[$mplace_name]['sales']) ? $child_data[$mplace_name]['sales']:0;
                                        ?>
                                        <td class="static scale">
                                            <?= isset($child_data[$mplace_name]['sales']) ? $currency . $child_data[$mplace_name]['sales']:"-"?>

                                        </td>
                                    <?php }?>
                                    <td class="static scale"><?= number_format((($variation_contribution/$top_performers['total_sales']) * 100), 2, '.', '') ?> % </td>
                                </tr>
                            <?php }}
                        $content_variation = ob_get_clean(); ?>
                        <!------------variation portion ended------------>
                    <tr id="parent_row_tp_<?= $index;?>" data-id-pk="<?= $index;?>">
                        <td class="static scalec">
                            <span  data-id-pk='<?= $index;?>' class="fa fa-plus-circle show_items_tp"></span>
                            <?= yii\helpers\Html::a($row['name'],array("products/detail","sku"=>$row['sku']));?>
                        </td>
                        <?php foreach($top_performers['marketplaces'] as $mplace_name) { // marketplaces ?>
                            <td class="static scale">
                                <?php if(isset($parent_marketplace_sale[$index][$mplace_name]))
                                          echo  $currency. ceil($parent_marketplace_sale[$index][$mplace_name]);
                                      elseif(isset($row['markets'][$mplace_name]['sales']) )  // if product has not child and marketplace indexes set
                                          echo  $currency. ceil($row['markets'][$mplace_name]['sales']);
                                      else
                                          echo " - ";


                                ?>
                            </td>
                         <?php } ?>
                        <td class="static scale">
                            <?php
                                $percent_contr_overall_prdct=round((($row['sales']/$top_performers['total_sales']) * 100),2);
                                if($percent_contr_overall_prdct >= 5)
                                    $above_five_percent_contributors_count +=1; // maintain count of more than 5 % contributor

                            ?>
                            <?= $percent_contr_overall_prdct; ?> % <span class="pull-right <?= $percent_contr_overall_prdct < 5 ? "fa fa-circle text-warning":""?>"></span>
                        </td>
                    </tr>
                        <!------write variations------>
                        <?= $content_variation;  ?>
                        <!-------------------------->
                    <?php if($index==9){ break;};} ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif;?>
<?php
$less_than_five_percent_contributors=($total_contributors-$above_five_percent_contributors_count); // used in box to show
?>
<!--------------------------------------------------------------------------------->
<?php if(isset($warehouses_Stocks) && !empty($warehouses_Stocks)) : ?>
    <hr>
    <div class="row">
            <div class="col-lg-2 col-sm-12 col-md-2">
                <h2>Inventory</h2>
                        <div class="card card-inverse card-success">
                            <div onclick="location.href = '/inventory/warehouses-inventory-stocks?page=1'" class="box text-center" style="cursor: pointer;" id="stocks_value">
                                <h1 class="font-light text-white" style="font-size: 20px;">
                                    <b><?=$currency?> <?=HelpUtil::number_format_short($warehouses_Stocks['Total_inventory_amount'])."" ?></b></h1>
                                <h6 class="text-white">Total Inventory</h6>
                            </div>
                        </div>
            </div>
        <!------------col----------->
        <div class="col-lg-2 col-sm-12 col-md-2">
            <h2>&nbsp;</h2>
            <div class="card card-inverse card-info">
                <div class="box bg-info ?> text-center">

                    <h2 class="font-light text-white" style="font-size: 18px;">
                        <?=$currency?> <?= (isset($stocksTrans) && is_array($stocksTrans)) ? \backend\util\HelpUtil::number_format_short(array_sum(array_column($stocksTrans,'in_transit_amount'))) : 0 ?>
                    </h2>
                    <h6 class="text-white">Stocks in Transit</h6>
                </div>
            </div>
        </div>
        <!------------col----------->
    </div>
    <div class="row">
            <div class="col-lg-12 col-sm-12 col-md-12">
                <h2>&nbsp;</h2>
                <div class="owl-carousel owl theme" id="owl-carousel-warehouses">
                    <?php foreach ($warehouses_Stocks['warehouse_stocks'] as $ware_houses) {
                        if (!is_array($ware_houses))
                            continue;
                        ?>
                    <div class="col-lg-12 ">
                        <div class="card">
                            <!--------card body------->
                            <div class="card-body analytics-info">
                                <h4 class="card-title"><?= $ware_houses['warehouse_name'];?>
                                    <small class="label label-rounded label-secondary pull-right "></small>
                                </h4>
                                <!-----stats------>
                                <div class="row">
                                    <div class="col-md-6 ">
                                        <img src="/images/warehouse_icon.png" alt="<?= $ware_houses['warehouse_name'];?>" style="max-width:175px;max-height:100px;"  class="img-responsive">
                                    </div>
                                    <div class="col-md-6">
                                        <br/>
                                        <h3 class="font-light m-b-0">
                                            <?= $currency?> <?= \backend\util\HelpUtil::number_format_short($ware_houses['total_inventory_stocks'])."" ?>
                                        </h3>
                                    </div>
                                </div>
                                <!----------->
                                <small class="label label-rounded label-secondary pull-right ">WAREHOUSE</small>
                            </div>
                        </div>
                    </div>

                <?php } ?>
            </div>

            </div>

    </div>
        <!------------------------------------------>



<?php endif;?>
<!---------------------------------->

<!--------------------------------------------------------------------------------->
    <script>
        var salesForcastA = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-1)])) ? $salesForcastData['refine'][(date('Y')-1)] : '', JSON_NUMERIC_CHECK)?>;
        var salesForcastB = <?=json_encode((isset($salesForcastData['refine'][(date('Y')-2)])) ? $salesForcastData['refine'][(date('Y')-2)] : '', JSON_NUMERIC_CHECK)?>;
        var salesForcastD = <?=json_encode((isset($salesForcastData['refine'][date('Y')])) ? $salesForcastData['refine'][date('Y')] : '', JSON_NUMERIC_CHECK)?>;
        var minv ='<?=json_encode($salesForcastData['min'], JSON_NUMERIC_CHECK)?>';
        var maxv = '<?=json_encode($salesForcastData['max'] , JSON_NUMERIC_CHECK)?>';
      //  var salesMarketplace = <?php //json_encode($salesMarketplace['refine']); ?>;
        var sales_by_shop_per_month= <?= json_encode($sales_by_shop_per_month);?>;
        var sales_by_marketplace= <?= json_encode($marketplace_sales_cont);?>;
        /*** below variables are used to populate dashboard areas**/
        var top_five_product_sales_percent ='<?= $top_performers['total_sales'] ?  round(($top_5_product_sales/$top_performers['total_sales'] ) * 100):" - ";?> %'
        var less_than_five_percent_contributors='<?= $less_than_five_percent_contributors; ?>';


    </script>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script> <!-----pie chart------>
<?php

$this->registerJsFile(
    '@web/monster-admin/js/toastr.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);

$this->registerJsFile('/monster-admin/assets/plugins/chartist-js/dist/chartist.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/chartist-plugin-tooltip-master/dist/chartist-plugin-tooltip.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/echarts/echarts-all.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

/// bar chart
$this->registerJsFile('/monster-admin/assets/plugins/raphael/raphael-min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/morrisjs/morris.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

/// pie chart
//$this->registerJsFile('/monster-admin/assets/plugins/Chart.js/chartjs.init.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/Chart.js/Chart.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

//top 5 products chart
$this->registerJsFile('/monster-admin/assets/plugins/jquery.easy-pie-chart/dist/jquery.easypiechart.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/jquery.easy-pie-chart/easy-pie-chart.init.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

//
$this->registerJsFile('/monster-admin/assets/plugins/sparkline/jquery.sparkline.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/sparkline/jquery.charts-sparkline.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);

//$this->registerJsFile('/monster-admin/js/morris-data.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/js/aoa-main-chart.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/monster-admin/assets/plugins/css-chart/css-chart.css',['depends' => [\frontend\assets\AppAsset::className()]]);

////table
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
///owl corousal slider
$this->registerJsFile('owl_courosal/dist/owl.carousel.min.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
///html 2 canvas to save image of content/graph
$this->registerJsFile('html2canvas/html2canvas.min.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);

$this->registerJs(<<< EOT_JS_CODE
// save image graph shop/marketplace
$('.save_image_bg').click(function() {
    html2canvas(document.getElementById('per_month_all_shops_graph')).then(canvas => {
        var w = document.getElementById("per_month_all_shops_graph").offsetWidth;
        var h = document.getElementById("per_month_all_shops_graph").offsetHeight;
        save_image_as(canvas.toDataURL("image/jpeg"),new Date().toISOString().slice(0, 10) + 'main_bar.jpg');
    }).catch(function(e) {
        console.log(e.message);
    });
    
});

$('.save_image_pie_graph').click(function() {
    html2canvas(document.getElementById('pie_graph_span')).then(canvas => {
        var w = document.getElementById('pie_graph_span').offsetWidth;
        var h = document.getElementById('pie_graph_span').offsetHeight;
        save_image_as(canvas.toDataURL("image/jpeg"),new Date().toISOString().slice(0, 10) + 'main_pie.jpg');
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

 $('.show_items').on('click',function(){
        let id_pk=$(this).attr('data-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('.child_row_' + id_pk).toggle();
    });
    
    //for top 10 cntributors
     $('.show_items_tp').on('click',function(){
        let id_pk=$(this).attr('data-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('.child_row_tp_' + id_pk).toggle();
    });
    
    $(document).ready(function(){
          $('#owl-carousel-platform').owlCarousel({
           loop:true,
            margin:10,
            nav:true,
            //items:2,
            dots: false,
            responsiveClass:true,
            navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
            responsive:{
                    0:{
                        items:1,
                    },
                    600:{
                        items:2,
                    }
                }
            });
            
            ///////////warehouse corosal
            $('#owl-carousel-warehouses').owlCarousel({
           loop:true,
            margin:10,
            nav:true,
            //items:2,
            dots: false,
            responsiveClass:true,
            navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
            responsive:{
                    0:{
                        items:1,
                    },
                    600:{
                        items:2,
                    },
                    1000:{
                        items:3
                    }
                }
            });
            ///////////brands corosal
            $('#owl-carousel-brands').owlCarousel({
           loop:true,
            margin:10,
            nav:true,
            //items:2,
            dots: false,
            responsiveClass:true,
            navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
            responsive:{
                    0:{
                        items:1,
                    },
                    600:{
                        items:2,
                    }
                }
            });
            //////////////////////////////
             $('#owl-carousel-dealers').owlCarousel({
           loop:true,
            margin:10,
            nav:true,
            items:1,
            dots: false,
            navText:["<div class='nav-btn next-slide'></div>"]
            });
            
         /// scroller 
          $('#owl-carousel-product').owlCarousel({
             loop:true,
            margin:10,
            nav:true,
           // items:3,
            dots: false,
            responsiveClass:true,
            navText:["<div class='nav-btn prev-slide'></div>","<div class='nav-btn next-slide'></div>"],
             responsive:{
                    0:{
                        items:1
                    },
                    600:{
                        items:3
                    },
                    1000:{
                        items:3
                    }
                }
            });
});


////filter
$('#sales_by_shop_filter_btn').click(function(){
$('.sales_by_shop_filter_box').toggle();
});
$('.top_5_contribution_span').html(top_five_product_sales_percent);
$('.less_than_five_percent_contributors_span').html(less_than_five_percent_contributors + " Items" ); // show in box which are less than 5% in contribution
EOT_JS_CODE
);
?>
