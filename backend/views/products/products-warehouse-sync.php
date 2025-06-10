<?php
use yii\web\View;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product', 'url' => ['/products/product-sync-to-warehouse']];
$this->params['breadcrumbs'][] = 'Product Details';
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
                <h5>Products Sync to warehouse</h5>
            </div>
        </div>
    </div>
</div>
</div>

<span class="listing-page">
    <!--------status options----->
    <?= Yii::$app->controller->renderPartial('products-warehouse-sync-statuses-view', $_params_); ?>

<div class="card">
 <div class="card-body">
    <!---------filter------------>
        <?=  Yii::$app->controller->renderPartial('products-warehouse-sync-filters',$_params_); ?>
    <!--------listing------------>

       <?= Yii::$app->controller->renderPartial('products-warehouse-sync-record-view',$_params_); ?>



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

