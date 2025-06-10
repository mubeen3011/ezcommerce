<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Sales Details';
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
                <h5>Sales Details</h5>
            </div>
        </div>
    </div>
</div>
</div>

<span class="listing-page">

    <!--------status options----->
    <?= Yii::$app->controller->renderPartial('orders-list-statuses-view',$_params_); ?>

<div class="card">
 <div class="card-body">
    <!---------filter------------>
        <?= Yii::$app->controller->renderPartial('orders-list-filters-view',$_params_); ?>
    <!--------listing------------>
        <?= Yii::$app->controller->renderPartial('orders-list-record-view',$_params_); ?>

</div>
</div>

</span>
<div id="displayBox" style="display: none;">
    <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
</div>
<?= Yii::$app->controller->renderPartial('popups/arrange-shipment'); ?>
<?= Yii::$app->controller->renderPartial('popups/shipment-process-modal'); ?>
<?= Yii::$app->controller->renderPartial('../courier/popups/shipment_history_modal'); ?>
<?= Yii::$app->controller->renderPartial('../courier/popups/bulk_shipment_selection'); ?>
<?= Yii::$app->controller->renderPartial('../courier/popups/modal'); ?>

<?php


$this->registerJsFile('ao-js/sales.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('ao-js/courier.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('ao-js/shipment_history.js?v=' . time(), [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJs(<<< EOT_JS_CODE
$(function(){
    $('.show_items').on('click',function(){
        let order_id_pk=$(this).attr('data-order-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('#order-items-record-' + order_id_pk).toggle();
    });
    
    
 
});
 $('.input-daterange-datepicker').daterangepicker({
locale: {
      format: 'YYYY-MM-DD',
        separator: ' to ',
    },
        autoUpdateInput: false,
        buttonClasses: ['btn', 'btn-sm'],
        
        applyClass: 'btn-danger',
        cancelClass: 'btn-inverse',
        ranges: {
           'Today': [moment(), moment()],
           'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           'Last 7 Days': [moment().subtract(6, 'days'), moment()],
           'Last 30 Days': [moment().subtract(29, 'days'), moment()],
           'This Month': [moment().startOf('month'), moment().endOf('month')],
           'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end, label) {
  $('.input-daterange-datepicker').val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
});



EOT_JS_CODE
);
?>

