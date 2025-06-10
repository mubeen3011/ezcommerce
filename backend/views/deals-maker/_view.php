<?php

use common\models\Channels;
use kartik\daterange\DateRangePicker;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\DealsMaker */
/* @var $form yii\widgets\ActiveForm */
$channel = Channels::find()->where(['is_active' => '1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');
$data = ArrayHelper::map(\common\models\CostPrice::find()->where(['<>','sub_category','167'])->orderBy('sku asc')->asArray()->all(), 'id', 'sku');

$reasons = ['Competitor Top' => 'Competitor Top', 'Focus SKUs' => 'Focus SKUs', 'Philips Campaign' => 'Philips Campaign',
    'Flash Sale' => 'Flash Sale', 'Shocking Deal' => 'Shocking Deal', 'Aging Stocks' => 'Aging Stocks', 'EOL' => 'EOL',
    'Competitive Pricing' => 'Competitive Pricing', 'Outright' => 'Outright', 'Others' => 'Others'];
$status = ['Pending'=>'Pending','Adjustments'=>'Adjustments','Approved'=>'Approved','Reject'=>'Reject','Cancel'=>'Cancel'];
$dname = explode('_',$model->name);
$model->name = ($model->name && !$model->isNewRecord && isset($dname[1])) ? $dname[1] : '' ;
$prefixName = (!$model->isNewRecord) ? $dname[0] : '' ;
?>

<div class="content-box-wrapper">
    <?php $form = ActiveForm::begin(['id' => 'deal-maker-request']); ?>
    <div class="col-md-6">

        <?= "Prefix:".$prefixName . $form->field($model, 'name')->textInput(['maxlength' => true,'readonly'=>!$model->isNewRecord])->hint('(optional) ** Auto prefix with Channel and Id') ?>

        <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['class' => 'form-control', 'prompt' => 'Select Channel', 'disabled'=>!$model->isNewRecord]) ?>

        <?= $form->field($model, 'start_date')->textInput(['style'=>'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;','maxlength' => true,'class'=>'start_datetime form-control','autocomplete'=>'off','disabled'=>!$model->isNewRecord]); ?>

        <?= $form->field($model, 'end_date')->textInput(['style'=>'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;','maxlength' => true,'class'=>'end_datetime form-control','autocomplete'=>'off','disabled'=>!$model->isNewRecord]); ?>


        <?= $form->field($model, 'motivation')->textarea(['maxlength' => true,'disabled'=>!$model->isNewRecord]) ?>
    </div>
    <div class="col-md-12">
        <hr>
        <div class="multi-skus">

            <table style="width: 100%;max-height: 250px;overflow-y: auto"
                   class="dm-multi-sku table table-striped table-bordered nowrap">
                <tr style="text-align: center">
                    <td class="multi-sku" >
                        SKU
                    </td>
                    <td class="multi-sku-price" style="width:100px !important;">Price</td>
                    <td class="multi-sku-subsdiy" style="width:100px !important;">Subsidy</td>
                    <td class="multi-sku-qty" >Sales Target</td>
                    <td class="multi-sku-qty" style="width:100px !important;">
                        <div class="form-group field-dealsmaker-deal_qty">
                            <label class="control-label" for="dealsmaker-deal_qty">Margin %</label>
                        </div></td>
                    <td class="multi-sku-qty" style="width:100px !important;">
                        <div class="form-group field-dealsmaker-deal_qty">
                            <label class="control-label" for="dealsmaker-deal_qty">Margin RM</label>
                        </div>
                    <td class="multi-sku-qty">Reason</td>
                    <?php if(!$model->isNewRecord): ?>
                        <td class="multi-sku-qty">Status</td>
                        <td class="multi-sku-qty">Comments</td>
                    <?php endif; ?>
                    <td></td>


                </tr>
                <?php foreach ($multiSkus as $k => $v):
                    if(isset($v['status']) && $v['status'] != 'Approved')
                        continue;
                    $readonly =  'disabled';
                    ?>
                    <tr class='row-<?= str_replace('s_','',$k) ?>'>
                        <td><input <?=$readonly?> type='text' data-sku-id='<?= str_replace('s_','',$k) ?>'  class='skunames form-control' name='DM[<?= $k ?>][sku]'
                                   readonly value='<?= $v['sku'] ?>'></td>
                        <td><input <?=$readonly?> type='text'  data-sku-id='<?= $k ?>' class='form-control'
                                   name='DM[<?= $k ?>][price]' value='<?= $v['price'] ?>'></td>
                        <td><input <?=$readonly?> type='text'  data-sku-id='<?= $k ?>' class=' form-control'
                                   name='DM[<?= $k ?>][subsidy]' value='<?= $v['subsidy'] ?>'></td>
                        <td><input  <?=$readonly?> type='text'  data-sku-id='<?= $k ?>' class='form-control' name='DM[<?= $k ?>][qty]'
                                   value='<?= $v['qty'] ?>'></td>
                        <td><input <?=$readonly?> type='text' data-sku-id='<?= $k ?>' readonly class='form-control' name='DM[<?= $k ?>][margin_per]'  value='<?=$v['margin_per']?>%'></td>
                        <td><input <?=$readonly?> type='text'  data-sku-id='<?= $k ?>' readonly class='form-control' name='DM[<?= $k ?>][margin_rm]'  value='<?=$v['margin_rm']?>'></td>
                        <td><select <?=$readonly?> class='form-control' name='DM[<?= $k ?>][reason]'>
                                <?php foreach ($reasons as $kx=>$r):
                                    $selected = ($kx == $v['reason']) ? 'selected' : ''; ?>
                                <option <?=$selected?> value="<?=$kx?>"><?= $r ?></option>
                                <?php endforeach; ?>
                            </select></td>
                        <?php if(!$model->isNewRecord): ?>
                            <td>
                                <select <?=$readonly?> class='form-control' name='DM[<?= $k ?>][status]'>
                                    <?php foreach ($status as $kx=>$r):
                                        $selected = ($kx == $v['status']) ? 'selected' : ''; ?>
                                        <option <?=$selected?> value="<?=$kx?>"><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select></td>
                            <td><textarea <?=$readonly?> data-sku-id='<?= $k ?>' class='form-control'
                                                                                                 name='DM[<?= $k ?>][comments]'><?= $v['comments'] ?></textarea></td>
                        <?php endif; ?>
                        <td><a href='javascript:;' data-sku-id='<?= str_replace('s_','',$k) ?>'  class='dm-more up'><i class='glyph-icon icon-arrow-right'></i></a></td>
                    </tr>
                    <tr class='more-<?= str_replace('s_','',$k) ?> hide'>
                        <td></td>
                        <td>Cost Price: <span class='price_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td>Commission: <span class='commision_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td>Shipping: <span class='shipping_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td>Gross Profit: <span class='gp_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td>Price w/ subsidy: <span class='pws_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td>Customer Pays: <br/><span class='cp_<?= str_replace('s_','',$k) ?>'></span></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </table>

        </div>


        <?php ActiveForm::end(); ?>
    </div>
</div>
