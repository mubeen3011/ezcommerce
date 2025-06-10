<?php

use common\models\Channels;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$channel = Channels::find()->where(['is_active'=>'1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');

$data = ArrayHelper::map(\common\models\Products::find()->where(['is_active'=>'1'])->andWhere(['<>','sub_category','167'])->asArray()->all(),'id', 'sku');

/*echo '<pre>';
print_r($data);
die;*/

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Margin Calculator';

?>
<!--<h3><?/*= $this->title */?></h3>-->
<style>
    .control-label{
        padding:0px;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Margin Calculator</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div class="row">
                    <div class="calculator col-md-4" style="float: left;">
                        <?php $form = ActiveForm::begin(['id' => 'calculator-form']); ?>
                        <?= $form->field($model, 'sku')->dropDownList($data,['class' => 'select2 form-control','prompt'=>'Select Sku']) ?>
                        <?= $form->field($model, 'channel')->dropDownList($channelList,['class' => 'select2 form-control','prompt'=>'Select Channel']) ?>
                        <?= $form->field($model, 'cost')->textInput(['class' => 'form-control']) ?>
                        <?= $form->field($model, 'price_sell')->textInput(['class' => 'form-control']) ?>
                        <?= $form->field($model, 'subsidy')->textInput(['class' => 'form-control']) ?>
                        <?= $form->field($model, 'qty')->textInput(['class' => 'form-control']) ?>
                        <span id="is_lazada" style="display:<?= ( isset($channelDetail) && !empty($channelDetail) && $channelDetail['marketplace'] == 'lazada')  ? '' : 'none'?>">

                            <?= $form->field($model, 'fbl')->checkbox(); ?>
                        </span>

                        <div class="form-group">
                            <?= Html::submitButton('Calculate', ['class' => 'btn btn-success']) ?>
                        </div>
                        <?php ActiveForm::end(); ?>
                    </div><!-- calculator -->
                    <div class="col-md-6" style="float: left;">

                        <?php if(!empty($margins)): ?>
                            <table class="table table-bordered table-striped" style="font-size: 15px;">
                                <tr>
                                    <td>SKU Cost:</td>
                                    <td><?=$margins['sku_cost']?></td>
                                </tr>
                                <tr>
                                    <td>Actual SKU Cost:</td>
                                    <td><?=$margins['actual_cost']?></td>
                                </tr>
                                <tr>
                                    <td>Extra Cost:</td>
                                    <td><?=$margins['extra_cost']?></td>
                                </tr>
                                <tr>
                                    <td>Commission Cost:</td>
                                    <td><?=$margins['commission_per']?>%</td>
                                </tr>
                                <tr>
                                    <td>Payment Charges:</td>
                                    <td><?=$margins['payment_per']?>%</td>
                                </tr>
                                <tr>
                                    <td>Shipping Cost:</td>
                                    <td><?=$margins['shipping_cost']?></td>
                                </tr>
                                <tr>
                                    <td>Gross Profit:</td>
                                    <td><?=$margins['gross_profit']?></td>
                                </tr>
                                <tr>
                                    <td>Price with Subsidy:</td>
                                    <td><?=$margins['price_after_subsidy']?></td>
                                </tr>
                                <tr>
                                    <td>Margin %:</td>
                                    <td><?=$margins['margin_per']?></td>
                                </tr>
                                <tr>
                                    <td>Margins RM:</td>
                                    <td><?=$margins['margin_amount']?></td>
                                </tr>
                                <tr>
                                    <td>Margins with Quantity RM:</td>
                                    <td><?=$margins['margins_with_quantity']?></td>
                                </tr>
                                <tr>
                                    <td>Competitive Price Sell:</td>
                                    <?php
                                    if ($margins['subsidy']==0){
                                        $val=0;
                                    }else{
                                        $val=$margins['subsidy'] /100;
                                    }
                                    ?>
                                    <td>RM <?= round($margins['price_after_subsidy'] - ($margins['price_after_subsidy']) * ($val),2) ?></td>
                                </tr>
                            </table>


                        <?php endif; ?>
                    </div>
                </div>


            </div>
        </div>

    </div>
</div>
<?php
$this->registerJs("

$(document).ready(function () {
        $(\"#calculatorform-channel\").on('change',function () {
        
        
            var cid = $(this).val();
            $.ajax({
            async: false,
            type: 'post',
            url: '/api/channel-marketplace',
            data: {'channelid':cid},
            dataType: 'text',
            success: function (marketplace) {
                if( marketplace=='lazada' ){
                    $(\"#is_lazada\").show();
                }else{
                    $(\"#is_lazada\").hide();
                }
            },
            });

        })
    })
");
?>