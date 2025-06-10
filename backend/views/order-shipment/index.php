<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Shipping Details';
?>
<!---css file----->
<link href="/../css/sales_v1.css" rel="stylesheet">

<div class="row">
    <div class="col-lg-12">
        <div class="card" >
            <div class="card-body" >
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h5>Orders Shipment Details</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<span class="listing-page">

    <!--------status options----->
    <div class="row">

    <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= (!isset($_GET['order_status'])) ? 'active':"";?>"  href="/order-shipment" role="tab">

                    <span><span class="badge badge-secondary"><?= isset($order_counts) ? array_sum($order_counts):"X"; ?></span> Shipped </span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='in_progress') ? 'active':"";?>"  href="/order-shipment?order_status=in_progress" role="tab">
                    <span class="badge badge-secondary"><?= isset($order_counts['shipped']) ? $order_counts['shipped']:"X";?></span> In progress
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['order_status']) && $_GET['order_status']=='bulk_shipping_failed') ? 'active':"";?>"  href="/order-shipment?order_status=bulk_shipping_failed" role="tab">
                    <span class="badge badge-secondary"><?= isset($order_counts['shipped']) ? $order_counts['shipped']:"X";?></span> Bulk Shippping Failed
                </a>
            </li>
        </ul>


    </div>
    <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <a  title="Clear Filter" class="btn btn-sm btn-secondary pull-right" href="/sales/reporting" >
            <i class="fa fa-filter "></i>
        </a>&nbsp;
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/sales/export-csv?<?=http_build_query($_GET)?>">
            <i class="fa fa-download"> </i>
        </a> &nbsp;
        <a  class="btn btn-sm btn-secondary pull-right mr-2" >Records
            <i class="fa fa-notes"> : <?= isset($total_records) ? $total_records:"x";?></i>
        </a>

    </div>
</div>
    <!--------status options----->


    <div class="card">
 <div class="card-body">
    <!---------filter------------>

     <!--------listing------------>
     <?= Yii::$app->controller->renderPartial('record-view-list',$_params_); ?>

</div>
</div>

</span>
<div id="displayBox" style="display: none;">
    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
</div>
<?php
$this->registerJs(<<< EOT_JS_CODE
$(function(){
    $('.show_items').on('click',function(){
    let order_id_pk=$(this).attr('data-order-id-pk');
    $(this).toggleClass('fa-plus fa-minus')
    $('#order-items-record-' + order_id_pk).toggle();
    });
 });

EOT_JS_CODE
);
?>



