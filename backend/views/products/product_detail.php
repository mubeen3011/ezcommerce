<?php
use yii\web\View;
use backend\util\HelpUtil;
$this->title = '';
$this->params['breadcrumbs'][] = 'Product Detail';
$currency = Yii::$app->params['currency'];
$total_inventory=0;
if(isset($inventory) && $inventory)
    $total_inventory=array_sum(array_column($inventory,'available'));
?>
<?php if(isset($product) && $product):?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Product Detail  (<?= isset($product->sku) ? $product->sku:"" ;?>)
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>
    <!------------------product detail-------------------->
    <div class="row">
        <div class="col-md-12 col-lg-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"></h4>
                    <h6 class="card-subtitle"></h6>
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#detail" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-home"></i></span>
                                <span class="hidden-xs-down">Detail</span></a>
                        </li>
                        <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#variations" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-user"></i></span>
                                <span class="hidden-xs-down">Parent/Variations</span></a>
                        </li>
                        <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#product_channels" role="tab" aria-selected="false">
                                <span class="hidden-sm-up"><i class="ti-email"></i></span> <span class="hidden-xs-down">Channels</span></a>
                        </li>
                    </ul>
                    <!-- Tab panes -->
                    <div class="tab-content tabcontent-border">
                        <!---------tab1---------->
                        <div class="tab-pane active show" id="detail" role="tabpanel" style="background: #F2F7F8;padding:3%">
                            <div class="row">
                                <!------col----------->
                                <div class="col-md-6 col-lg-6 col-sm-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex flex-row">
                                                <div class="">
                                                    <img src="<?= $product->image? $product->image:'/images/no_image-copy.jpg'?>" alt="user" class="img-circle" width="100"></div>
                                                <div class="p-l-20">
                                                    <h3 class="font-medium"><?= $product->name;?></h3>
                                                    <h6><?= $product->sku;?></h6>
                                                    <button class="btn btn-sm btn-secondary"><i class="ti-tag"></i>
                                                        <?= $product->parent_sku_id ? "Variation Product":"Parent Product";?>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                        <br/>
                                        <br/>
                                        <div>

                                        </div>
                                    </div>
                                </div>
                                <!----------col------------>
                                <div class="col-lg-6 col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row m-t-40">
                                                <div class="col b-r">
                                                    <h2 class="font-light"><?= $currency . $product->cost;?></h2>
                                                    <h6>Cost</h6></div>
                                                <div class="col b-r">
                                                    <h2 class="font-light"><?= $currency . $product->rccp;?></h2>
                                                    <h6>RCCP</h6></div>
                                                <div class="col">
                                                    <h2 class="font-light">
                                                        <?php if($sales['sales']){
                                                            echo $currency. round(str_replace(',','',$sales['sales'])/$sales['total_qty_ordered']);
                                                        } else{ echo "--";} ?>
                                                    </h2>
                                                    <h6>Avg Sale Price</h6>
                                                </div>

                                            </div>
                                            <br/>
                                            <hr/>
                                            <div>
                                                <b class="fa fa-tag"> Brand:</b><?= $product->brand ? $product->brand:" No Brand";?>
                                                <b class="fa fa-barcode pull-right"> Barcode:<?= $product->barcode ? $product->barcode:" No Barcode found";?></b>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div> <!---------tab1---------->
                        <!---------tab2---------->
                        <div class="tab-pane p-20" id="variations" role="tabpanel">
                            <div class="row">
                                <!------col----------->
                                <div class="col-md-6 col-lg-6 col-sm-12">
                                    <h2 class="title">Parent</h2>
                                    <div class="card">
                                        <div class="table-responsive card-body">
                                            <table class="tablesaw table-bordered table-hover table"  data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                                                <thead>
                                                <tr>
                                                    <th  scope="col">&nbsp;</th>
                                                    <th  scope="col">SKU</th>
                                                    <th  scope="col">Barcode</th>
                                                    <th scope="col">RCCP</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php if(isset($parent) && $parent):?>
                                                        <tr>
                                                            <td><img src="<?= $parent->image;?>"  width="50px" class="img-circle"></td>
                                                            <td> <?= yii\helpers\Html::a($parent->sku,array("products/detail","sku"=>$parent->sku));?></td>
                                                            <td><?= $parent->barcode;?></td>
                                                            <td><?= $parent->rccp;?></td>
                                                        </tr>
                                                <?php endif;?>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                                <!----------col------------>
                                <div class="col-lg-6 col-md-6">
                                    <h2 class="title">Variations</h2>
                                    <div class="card">
                                        <div class="table-responsive card-body">
                                            <table class="tablesaw table-bordered table-hover table"  data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                                                <thead>
                                                <tr>
                                                    <th  scope="col">&nbsp;</th>
                                                    <th  scope="col">SKU</th>
                                                    <th  scope="col">Barcode</th>
                                                    <th scope="col">RCCP</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php if(isset($variations) && $variations):?>
                                                    <?php foreach($variations as $var):?>
                                                    <tr>
                                                        <td><img src="<?= $var['image'];?>"  width="50px" class="img-circle"></td>
                                                        <td>
                                                            <?= yii\helpers\Html::a($var['sku'],array("products/detail","sku"=>$var['sku']));?>
                                                         </td>
                                                        <td><?= $var['barcode'];?></td>
                                                        <td><?= $var['rccp'];?></td>
                                                    </tr>
                                                    <?php endforeach;?>
                                                <?php endif;?>

                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div><!---------tab2---------->
                        <!---------tab3---------->
                        <div class="tab-pane p-20" id="product_channels" role="tabpanel">
                            <div class="row">

                                <div class="col-lg-12 col-sm-12 col-md-12">
                                    <h2 class="title">Product Channels</h2>
                                    <div class="card">
                                        <div class="table-responsive card-body">
                                            <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe" data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                                                <thead>
                                                <tr>
                                                    <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">&nbsp;</th>
                                                    <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Channel</th>
                                                    <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Marketplace</th>
                                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Is Live</th>
                                                    <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">Deleted</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php if(isset($p_channels) && $p_channels):?>
                                                    <?php foreach($p_channels as $p_channel):?>
                                                        <tr>
                                                            <td><img src="/images/<?= $p_channel['channel']['marketplace'];?>.jpg" alt="magento" style="max-width:125px;max-height:50px;" class="img-responsive img-circle"></td>
                                                            <td><?= $p_channel['channel']['name'];?></td>
                                                            <td><?= $p_channel['channel']['marketplace'];?></td>
                                                            <td><?= $p_channel['is_live'] ? "Yes":"NO";?></td>
                                                            <td><?= $p_channel['deleted'] ? "Yes":"NO";?></td>
                                                        </tr>
                                                    <?php endforeach;?>
                                                <?php endif;?>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>

                                </div>
                            </div>

                        </div><!---------tab3---------->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-------------------------------------------->


<div class="row">
    <div class="col-md-3 col-lg-3 col-xlg-3">
        <div class="card card-inverse card-info">
            <div class="box bg-info text-center">
                <h1 class="font-light text-white">
                    <?= $currency. HelpUtil::number_format_short(($total_inventory * $product->rccp),2);?>
                </h1>
                <h6 class="text-white">Inventory Worth</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-lg-3 col-xlg-3">
        <div class="card card-inverse card-info">
            <div class="box bg-info text-center">
                <h1 class="font-light text-white">
                    <?= $total_inventory;?>
                </h1>
                <h6 class="text-white">Inventory</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-lg-3 col-xlg-3">
        <div class="card card-inverse card-info">
            <div class="box bg-info text-center">
                <h1 class="font-light text-white">
                    <?= ceil($sales['total_qty_ordered']/$first_order['months_passed']);?>
                </h1>
                <h6 class="text-white">Avg Monthly Sold</h6>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-lg-3 col-xlg-3">
        <div class="card card-inverse card-info">
            <div class="box bg-info text-center">
                <h1 class="font-light text-white">
                    <?= $sales['last_ordered'] ? $sales['last_ordered']:"--";?>
                </h1>
                <h6 class="text-white">Latest Ordered at</h6>
            </div>
        </div>
    </div>
</div>
<!------------------------------------stats ------------------------------->
    <div class="row">
        <!-- Column -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Sales Contribution</h4>
                    <div class="text-right">
                        <h2 class="font-light m-b-0"><i class="fa fa-percents text-success"></i>
                            <?php
                                $sku_total_sales=str_replace(',','',$sales['sales']);
                                $percent_age=0;
                                if($total_sales['sales'] && $sku_total_sales)
                                    $percent_age=ceil(($sku_total_sales/$total_sales['sales'])*100);
                            ?>
                            <?= $currency. HelpUtil::number_format_short(($sales['sales']),2);?>
                        </h2>
                        <span class="text-muted">Amount</span>
                    </div>
                    <span class="text-success"><?= $percent_age;?> %</span>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($percent_age +5);?>%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Column -->
        <!-- Column -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Avg Weekly Sales</h4>
                    <div class="text-right">
                        <h2 class="font-light m-b-0"><i class="ti-arrow-ups text-info"></i>
                            <?php
                            if($sku_total_sales):
                                echo $currency. HelpUtil::number_format_short(($sku_total_sales/$first_order['weeks_passed']),2);
                            else:
                                echo "--";
                            endif;

                            ?>
                        </h2>
                        <span class="text-muted">Amount</span>
                    </div>
                    <span class="text-info">&nbsp;</span>
                    <div class="progress">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Column -->
        <!-- Column -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Avg Monthly Sales</h4>
                    <div class="text-right">
                        <h2 class="font-light m-b-0"><i class="ti-arrow-ups text-purple"></i>
                            <?php
                                if($sku_total_sales):
                                    echo $currency. HelpUtil::number_format_short(($sku_total_sales/$first_order['months_passed']),2);
                                else:
                                    echo "--";
                                endif;

                            ?>
                        </h2>
                        <span class="text-muted">Amount</span>
                    </div>
                    <span class="text-purple">&nbsp;</span>
                    <div class="progress">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Column -->
        <!-- Column -->
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Total Ordered</h4>
                    <div class="text-right">
                        <h2 class="font-light m-b-0"><i class="ti-arrow-downs text-danger"></i>
                            <?= $sales['total_qty_ordered'] ? $sales['total_qty_ordered']:'--';?>
                        </h2>
                        <span class="text-muted">Orders</span>
                    </div>
                    <span class="text-danger">&nbsp;</span>
                    <div class="progress">
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: 100%; height: 6px;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Column -->
    </div>
<!-----------------------------Warehouse-------------------->
<div class="row">

    <div class="col-lg-12 col-sm-12 col-md-12">
        <h2 class="title">Inventory</h2>
        <div class="card">
            <div class="table-responsive card-body">
                <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe" data-tablesaw-sortable data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                    <thead>
                    <tr>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Warehouse</th>
                        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Stock</th>
                        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1">Stock In Transit</th>
                        <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Last Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(isset($inventory) && $inventory):?>
                        <?php foreach($inventory as $inv):?>
                    <tr>
                        <td><?= $inv['name'];?></td>
                        <td><?= $inv['available'];?></td>
                        <td><?= $inv['stock_in_transit'];?></td>
                        <td><?= $inv['updated_at'];?></td>
                    </tr>
                        <?php endforeach;?>
                        <?php else:?>
                    <tr>
                        <td colspan="4"><center>No Record Found</center></td>
                    </tr>
                    <?php endif;?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>
<!----------------------------------------------------------->
<div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">SKU Sales Chart</h4>
                    <!-----filter---------->
                    <form action="" role="form" class="form-horizontal" >
                        <div class="row sales_by_shop_filter_box">
                            <div class="col-sm-2">
                                <?php if(isset($_GET['graph_filter_applied'])){ ?>
                                    <a class="fa fa-filter text-danger" data-toggle="tooltip" title="Clear Filter" href="/products/detail?sku=<?= $_GET['sku'];?>">
                                    </a>
                                <?php } ?>
                            </div>
                            <div class="col-sm-2">
                                <input name="graph_filter_applied" type="hidden" value="yes">
                                <?php if(isset($_GET['customer_type'])){ ?>
                                    <input name="customer_type" type="hidden" value="<?= $_GET['customer_type'];?>">
                                <?php } ?>
                                <?php if(isset($_GET['sku'])){ ?>
                                    <input name="sku" type="hidden" value="<?= $_GET['sku'];?>">
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
                            <div class="col-sm-2">
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
                            <div class="col-sm-2">
                                <label class="radio-inline">Year</label>
                                <select class="form-control form-control-sm" name="filter_graph_year">
                                    <option value="">ALL</option>
                                    <?php for($y=date('Y');$y >= (date('Y')-2);$y--) { ?>
                                        <option value="<?= $y;?>" <?= (isset($_GET['filter_graph_year']) && $_GET['filter_graph_year']==$y) ? "selected":"";?>><?= $y;?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label class="radio-inline">&nbsp;</label>
                                <button class="btn btn-sm btn-secondary btn-block"> APPLY</button>
                            </div>

                        </div>
                    </form>
                    <!-------------------->
                    <div id="sales-by-shop-per-month-graph"></div>
                </div>
            </div>
        </div>

</div>

<!----------------------------------------------------------->
    <?php else:?>
        <h2>Sku Not Exist</h2>
    <?php endif;?>

<script>

    var sales_by_shop_per_month= <?= json_encode($monthly_sales_graph);?>;
    console.log(sales_by_shop_per_month);

</script>
<?php
/// bar chart
$this->registerJsFile('/monster-admin/assets/plugins/raphael/raphael-min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/assets/plugins/morrisjs/morris.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/monster-admin/js/aoa-main-chart.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/monster-admin/assets/plugins/css-chart/css-chart.css',['depends' => [\frontend\assets\AppAsset::className()]]);

?>