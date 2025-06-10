<?php
/*echo "display some info";

echo "<pre>";
print_r($status);

echo "<hr>";
print_r($msg);

echo "<hr>";
print_r($order);

echo "<hr>";
print_r($items);

echo "<hr>";
print_r($warehouse_address);
die('come');*/
use yii\widgets\ActiveForm;
?>
<style>
    .card-header{
        background: none;
    }
    .btn-circle.btn-sm {
        width: 28px;
        height: 26px;
        padding: 3px 6px;
        font-size: 14px;
    }
    .dm_inputs{
        width:78px;
    }
    .wt_input{
        width:82px;
    }
    .sm-3-shipping-rates{
        padding-right:0;
        padding-left:0;
    }
    .sm-6-shipping-rates{
        padding-right:0;
    }
    </style>
<div class="accordion" id="accordionExample">
    <?php $form = ActiveForm::begin([
        'method' => 'POST',
        'action' => ['/courier/submit-shipping'],
        'id' =>'ship_now_submit_form',
        'validateOnSubmit' => false
    ]); ?>

        <!---address--->
        <?=  Yii::$app->controller->renderPartial('tcs/address',$_params_);?>
        <!---orders--->
        <?= Yii::$app->controller->renderPartial('tcs/orders',$_params_);?>
        <!---shipping rates--->
         <?= Yii::$app->controller->renderPartial('tcs/shipping_rates',$_params_);?>

 <?php ActiveForm::end(); ?>
</div>

