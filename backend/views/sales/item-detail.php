<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 3/22/2018
 * Time: 4:06 PM
 */

use common\models\OrderItems;
use common\models\Settings;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales Details', 'url' => ['reporting?view=skus&page=1']];
$this->params['breadcrumbs'][] = 'Sales Order Details';
$pp = 'RM ';
$items = OrderItems::find()->where(['order_id'=>$order->id])->all();
$currency = Yii::$app->params['currency'];
//var_dump($items);
//die();
$price_wgst = 0;
/*foreach ($items as $item)
{
    if($item->order->channel->marketplace == 'shop')
        $price_wgst = str_replace(['RM ',','],'',$item->price_wgst);

    $item->paid_price = str_replace(['RM ',','],'',$item->paid_price);
    $amt[] = $item->paid_price;
    $amt_wgst[] = $item->price_wgst;
    $dist[] = $item->discount;
}*/

$total_order_amount = $order->order_total;
/*$shopee_order_total = $order->order_total;
$order_total_without_discount = ( $order->order_shipping_fee != "" ) ? $order->order_total + $order->order_shipping_fee : $order->order_total;
$order->order_total = array_sum($amt);
$shop_order_total = array_sum($amt_wgst);
$turnover = ($order->channel->marketplace != 'street') ? $order->order_total : $order->order_total - array_sum($dist) ;
if($order->channel->marketplace == 'shop')
{
    $paid_amount = round(($shop_order_total + $order->order_shipping_fee - array_sum($dist)) * 1.06,2);
}
else if ( $order->channel->marketplace == 'lazada' ) {

    $paid_amount = ($total_order_amount + $order->order_shipping_fee) - array_sum($dist);
}
else if ( $order->channel->marketplace == 'shopee' ) {

    $paid_amount = $shopee_order_total;

}
else {
    $paid_amount = round(
        $order->order_total +
        (int) $order->order_shipping_fee -
        array_sum($dist),2);
}*/
?>
<?php //print_r($order->customersAddresses);?>
<style>
    legend.order_detail_legend {
        width:inherit; /* Or auto */
        padding:0 10px; /* To give a bit of padding on the left and right */
        border-bottom:none;
    }
    .summary {
        display: none !important;
    }
    .panel-body {
        height: 220px !important;
    }
    .box-border{
        border: 1px solid #eee;
        height: 204px;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card card-body printableArea">
            <div class=" row">
                <div class="col-md-4 col-sm-12">
                    <h3>Sales Order Details</h3>
                </div>
                <div class="col-md-4 col-sm-12">

                </div>
                <div class="col-md-4 col-sm-12">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                </div>
            </div>
            <hr>
            <div>
                <h3><b>INVOICE</b> <span class="pull-right">#<?=$order->order_number?></span></h3>
            </div>
                <?php
                if (isset($items[0]['courier_id']) && $items[0]['courier_id']!=''){
                    ?>
                    <div><h3 class="pull-right">ShippedBy : <?=$couriers[$items[0]['courier_id']]['name']?></h3></div>
                    <div><h3 class="pull-right">TrackingNumber : <?=$items[0]['tracking_number']?></h3></div>
                    <?php
                }
                ?>

            <hr>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Shipping Information
                        </div>
                        <div class="card-body box-border">

                            <?php
                            if(isset($order->customersAddresses)): ?>

                                <b class='fa fa-user'> <?= $order->customersAddresses->billing_fname ." ".$order->customersAddresses->billing_lname ;?></b><br/>
                                 <?php if($order->customersAddresses->billing_email){ ?>
                                     <b class='fa fa-envelope'> <?= $order->customersAddresses->billing_email;?></b><br/>
                                 <?php }elseif($order->customersAddresses->shipping_email){ ?>
                                     <b class='fa fa-envelope'> <?= $order->customersAddresses->shipping_email;?></b><br/>
                                 <?php } ?>
                                <b class='fa fa-phone'> <?= $order->customersAddresses->billing_number ;?></b><br/><br/>
                                <span class='fa fa-building'> <?= $order->customersAddresses->billing_address; ?></span><br/>
                                <span class=''> <?= $order->customersAddresses->billing_postal_code ; ?><br/>
                               <span class=''> <?=$order->customersAddresses->billing_postal_code ." , ".$order->customersAddresses->billing_city; ?><br/>
                               <span class=''> <?=$order->customersAddresses->billing_state ." , ".$order->customersAddresses->billing_country . PHP_EOL; ?>

                         <?php   endif; ?>
                        </div>
                    </div>

                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Order Information
                        </div>
                        <div class="card-body box-border">
                            <p class="card-text">
                                Date :</b> <i class="fa fa-calendar"></i> <?=$order->order_created_at?></p>
                                Channel: <?=$order->channel->name?><br />
                                <br/> Order Status: <b style="font-weight: bold"><?=ucwords($order->order_status)?></b><br />
                                <?php if(isset($order->coupon_code) && $order->coupon_code) { ?>
                                    <br/> Coupon Code: <b style="font-weight: bold"><?= $order->coupon_code?></b><br />
                                <?php } ?>
                        </div>
                    </div>

                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            Order Totals
                        </div>
                        <div class="card-body box-border">
                            <p class="card-text">
                                <?php $or_shipping=($order->order_shipping_fee) ? $order->order_shipping_fee:0; ?>
                                Subtotal  : <?=   $currency . " " .number_format(($total_order_amount - $or_shipping) + $order->order_discount,2) ?><br><br>
                            <span style="color: red;">Shipping: ( <?=$currency ?> <?= $order->order_shipping_fee ;?> )</span><br><br>
                            <span style="color: red;">Discount: ( <?=$currency ?> <?= $order->order_discount ;?>)</span><br><br>
                            <span style="color: red;">Tax: ( <?=$currency ?> <?= is_array($items) ? array_sum(array_column($items,'item_tax')):0.00 ;?>)</span><br><br>
                                <br><br>
                            </p>


                        </div>
                    </div>

                </div>
            </div>
            <?php //print_r($dp);?>
            <div class="row">

                <div class="col-md-12">
                    <div class="table-responsive m-t-40" style="clear: both;">
                        <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Paid price</th>
                                    <th>qty</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($items):
                                foreach ($items as $item)
                                { ?>

                                    <tr>
                                            <td><?= $item['item_sku'];?></td>
                                            <td><?= $item['price'];?></td>
                                            <td><?= $item['paid_price'];?></td>
                                            <td><?= $item['quantity'];?></td>
                                            <td><?= ($item['quantity'] * $item['paid_price']);?></td>
                                    </tr>

                               <?php } endif; ?>
                            </tbody>
                        </table>
                        <!-- GridView::widget([
                            'dataProvider' => $dp,
                            'columns' => [
                                ['attribute'=>'item_sku','value'=>'item_sku'],
                                'unit_selling_price',
                                'quantity',
                                'paid_price',
                            ],
                        ]); -->
                    </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="pull-right m-t-30 text-right">
                        <hr>
                        <h3><b>Total Bill : </b> <?=$currency ?> <?=  number_format($total_order_amount,2) ?></h3>
                    </div>
                    <div class="clearfix"></div>
                    <hr>
                    <div class="text-right">
                        <button id="print" class="btn btn-default btn-outline" type="button"> <span><i class="fa fa-print"></i> Print</span> </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJs("$(document).ready(function() {
        $(\"#print\").click(function() {
            var mode = 'iframe'; //popup
            var close = mode == \"popup\";
            var options = {
                mode: mode,
                popClose: close
            };
            $(\"div.printableArea\").printArea(options);
        });
    });");