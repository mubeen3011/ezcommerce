<?php

use common\models\Channels;
use kartik\daterange\DateRangePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\DealsMaker */
/* @var $form yii\widgets\ActiveForm */
$channel = Channels::find()->where(['is_active' => '1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');
$data = ArrayHelper::map(\common\models\Products::find()->where(['<>', 'sub_category', '167'])->andWhere(['is_active' => '1'])->orderBy('sku asc')->asArray()->all(), 'id', 'sku');
//$category = \common\models\Category::find()->where(['is_active' => '1'])->andWhere(['not in', 'map_with', ''])->groupBy(['map_with'])->orderBy('map_with')->asArray()->all();

if (isset($model->category) && $model->category!=''){
  //  $cats=rtrim($model->category ,',');
    //$category = \common\models\Category::find()->where(['in','id',rtrim($model->category ,',')])->orderBy('id')->asArray()->all();
   //print_r($model->category); die();
    $dd_categories=\common\models\Category::find()->where(['is_active'=>1])->asArray()->all();
    $dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
    $category_list =\backend\util\HelpUtil::dropdown_3_level($dd_categories);
}else{
    $category_list  = [];
}
$model->category = (!$model->isNewRecord) ? explode(',', rtrim($model->category ,',')) : '';
//echo '<pre>';print_r($category);die;
//$categoryList = ArrayHelper::map($category, 'id', 'name');
//echo '<pre>';print_r($categoryList);die;
// customer type
$customer_type = [
    ['id'=>'B2C','name'=>'B2C'],
    ['id'=>'B2B','name'=>'B2B']
];
$customerTypeList = ArrayHelper::map($customer_type, 'id', 'name');
$discount_type = [
    ['id'=>'','name'=>''],
    ['id'=>'Percentage','name'=>'Percentage'],
    ['id'=>'Amount','name'=>'Amount']
];
$discountTypeList = ArrayHelper::map($discount_type, 'id', 'name');

$reasons = ['Competitor Top' => 'Competitor Top', 'Focus SKUs' => 'Focus SKUs', 'Philips Campaign' => 'Philips Campaign',
    'Flash Sale' => 'Flash Sale', 'Shocking Deal' => 'Shocking Deal', 'Aging Stocks' => 'Aging Stocks', 'EOL' => 'EOL',
    'Competitive Pricing' => 'Competitive Pricing', 'Outright' => 'Outright','Mega Campaign'=>'Mega Campaign', 'Others' => 'Others'];
if ($model->pre_approve == 1)
    $status = ['Pending' => 'Pending', 'Adjustments' => 'Adjustments', 'Approved' => 'Submit', 'Reject' => 'Reject', 'Cancel' => 'Cancel', 'Submitted' => 'Approved'];
else
    $status = ['Pending' => 'Pending', 'Adjustments' => 'Adjustments', 'Approved' => 'Approved', 'Reject' => 'Reject', 'Cancel' => 'Cancel'];
if (time() >= strtotime($model->start_date) && time() <= strtotime($model->end_date))
    $approve_status = ['Approved' => 'Approved', 'Cancel' => 'Cancel'];
else
    $approve_status = ['Approved' => 'Approved', 'Cancel' => 'Cancel', 'Adjustments' => 'Adjustments'];

$dname = explode('_', $model->name);
$model->name = ($model->name && isset($dname[1])) ? $dname[1] : $model->name;
$prefixName = (!$model->isNewRecord) ? $dname[0] : '';
$is_deal_skus_error = $model->getErrors('requestedSkus');
//$model->category = explode(',', $model->category);
$stats = \backend\util\DealsUtil::_dealStats($model->id);
$changeAbleInputs=true;
?>
<style>
    .tg-kr944 {
        padding: 5px;
    }
    .full-width{
        width: 100%;
    }
    th label {
        font-weight: bold;
    }

    .tbody-td-padding {
        padding: 5px;
    }

    .input-text input {
        width: 100px;
    }

    .input-select select {
        width: 100px;
    }

    .form-group {
        margin-bottom: 10px;
    }

    .select2-container {
        margin: 8px !important;
        width: 200px !important;
    }

    .tg-kr944 select {
        width: 100px;
    }

    .tg-kr944 textarea {
        width: 100px;
    }
    #dealsmaker-motivation{
        height:38px;
    }
    .form-control{
        width:93%;
    }
    #dealsmaker-reasons{
        width:auto;
    }
    .field-dealsmaker-category .select2-container{
        width: 93% !important;
    }
</style>

<div class="col-md-12">
    <p for="dealsmaker-rn">Requested By <?= $model->requester->full_name ?> <strong
                style="font-weight: bold">(<?= "Prefix:" . $prefixName ?>)</strong></p>
</div>

<div class="content-box-wrapper">
    <?php $form = ActiveForm::begin(['id' => 'deal-maker-request']); ?>

    <div class="col-md-12" style="padding: 0px;">
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'disabled' => 'disabled'])->hint('(optional) ** Auto prefix with Channel and Id') ?>

            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['class' => 'form-control', 'prompt' => 'Select Channel', 'disabled' => 'disabled']) ?>

            </div>
            <div class="col-md-4">
                <div class="form-group field-dealsmaker-category">
                    <label class="control-label " for="dealsmaker-category">Category</label>
                    <select class="category-selector select2 form-control full-width" name="DealsMaker[category][]" multiple="" <?= $changeAbleInputs ? 'disabled':'';?> size="4" tabindex="-1" aria-hidden="true">
                        <?php
                        foreach($category_list as $cat_dd) { ?>
                            <option value="<?= $cat_dd['key'];?> " <?= (is_array($model->category) && in_array((int)$cat_dd['key'],$model->category))? "selected":"";?>> <?= $cat_dd['space'].$cat_dd['value'];?> </option>

                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'start_date')->textInput(['style' => 'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;', 'maxlength' => true, 'disabled' => 'disabled', 'class' => 'start_datetime form-control']); ?>

            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'end_date')->textInput(['disabled' => 'disabled', 'style' => 'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;
padding-left:38px;', 'maxlength' => true, 'class' => 'end_datetime form-control']); ?>

            </div>
            <div class="col-md-4" style="max-width:49.5%">
                <?= $form->field($model, 'motivation')->textarea(['maxlength' => true,'readonly'=>true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'budget')->textInput(['maxlength' => true,'class'=>'numberinput form-control','readonly'=>true]) ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'customer_type')->dropDownList(array_merge($customerTypeList), ['class' => 'form-control full-width', 'disabled' => !$model->isNewRecord]) ?>
            </div>

            <div class="col-md-4">

                <?= $form->field($model, 'discount_type')->dropDownList(array_merge($discountTypeList), ['class' => 'form-control full-width discount-type', 'disabled' => $changeAbleInputs]) ?>
            </div>


        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'discount')->textInput(['type' => 'number','min'=>1,'maxlength' => true,'readonly'=>true, 'class' => 'discount-input numberinput form-control']) ?>
            </div>
            <div class="col-md-4 hide" style="margin-top: 35px;">
                <?= $form->field($model, 'pre_approve',['options'=>['class'=>'col-md-12 pull-left']])->checkbox(['maxlength' => true,'disabled' => !$model->isNewRecord]) ?>
                <?php
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-6 col-lg-6 col-xlg-4">

                    <table class="table">
                        <tbody>
                        <tr>
                            <td colspan="3"><h3 class="box-title m-b-0">Deal Stats By Targets</h3></td>
                        </tr>
                            <tr>
                            <?php
                    $roi = ((double)array_sum($model->actual_sales) > 0) ? (((double)$model->budget / (double)array_sum($model->actual_sales)) * 100) : 0; ?>
                    <?php
                    if ($model->status == 'active' || $model->status == 'expired'):
                        $isTop = "top:-192px";
                        ?>
                        <td><h5>Target Sales (<?=Yii::$app->params['currency']?>) <span class="badge badge-info"><?=number_format(array_sum($model->target_sales))?></span></h5></td>
                        <td><h5>Actual Sales (<?=Yii::$app->params['currency']?>)<span class="badge badge-info"><?=number_format(array_sum($model->actual_sales), 2)?></span></h5></td>
                        <td><h5>ROI <span class="badge badge-info"><?=number_format($roi, 2) . '%'?></span></h5></td>
                    <?php endif; ?>
                                <td><h5>GMV (<?=Yii::$app->params['currency']?>)<span class="badge badge-info"><?=$stats['GMV']?></span></h5></td>
                                <td><h5>Sum Of Total Profit (<?=Yii::$app->params['currency']?>)<span class="badge badge-<?=($stats['Rm_Profit']>0) ? 'success' : 'danger' ?>"><?=$stats['Rm_Profit']?> </span></h5></td>
                                <td>
                                    <h5>Total A&O Margin
                                        <span class="badge badge-<?=($stats['Total_Margin_Percentage']>0) ? 'success' : 'danger' ?>">
                                            <?=$stats['Total_Margin_Percentage']?> %
                                        </span>
                                    </h5>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="deal_id" value="<?= $model->id ?>"/>
    <input type="hidden" name="DealsMaker[pre_approve]" id="pre_approve"
           value="<?= ($model->pre_approve == 1) ? 1 : 0 ?>"/>
</div>

<div class="col-md-12">
    <hr>
    <div class="multi-skus">
        <div class="table-responsive m-t-40">
            <div class="dataTables_wrapper" style="overflow-y: auto;overflow-x: auto;">
                <table id="demo-foo-row-toggler"
                       class="dm-multi-sku table toggle-circle table-hover default breakpoint footable-loaded footable table-striped table-bordered nowrap">
                    <thead>
                    <tr style="text-align: center">
                        <th class="multi-sku-qty side-border footable-sortable">

                        </th>
                        <th data-toggle="true"
                            class="footable-visible footable-first-column footable-sortable footable-sorted multi-sku side-border"
                            style="border-left: 0px !important;">
                            SKUs
                        </th>
                        <th class="multi-sku-price side-border footable-visible footable-sortable">Price</th>
                        <th class="multi-sku-subsdiy side-border footable-visible footable-last-column footable-sortable">
                            Subsidy
                        </th>
                        <th class="multi-sku-subsdiy side-border footable-sortable footable-sortable" data-toggle="tooltip" title="Current Stocks (OverAll)">C.S</th>
                        <th class="multi-sku-qty side-border footable-sortable" data-toggle="tooltip" title="Sales Target">S.T</th>
                        <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                            <th class="multi-sku-qty side-border footable-sortable" data-toggle="tooltip" title="Actual Sales Count">A.S.C</th>
                        <?php endif; ?>
                        <th class="multi-sku-qty side-border footable-sortable">Margin %</th>
                        <th class="multi-sku-qty side-border footable-sortable">Margin <?=Yii::$app->params['currency']?></th>
                        <th class="multi-sku-qty side-border footable-sortable">Reasons</th>
                        <th class="multi-sku-qty side-border footable-sortable">Status</th>
                        <th class="multi-sku-qty side-border footable-sortable">Comments</th>

                    </tr>
                    </thead>
                    <?php
                    $Ccounter = 0;
                    foreach ($multiSkus as $k => $v):
                        $sku = str_replace('s_', '', $k);
                        if (isset($_GET['sku']) && $_GET['sku'] != $sku)
                            continue;

                        if ($Ccounter % 2 == 0) {
                            $rowClass = "white";
                        } else {
                            $rowClass = "#f2f7f8";
                        }
                        $bgColor = ($v['status'] == 'Approved') ? '#f5f5f5' : '';
                        $bgColor = ($v['status'] == 'Cancel' || $v['status'] == 'Reject') ? '#fbd3d3' : $bgColor;
                        $readonly = ($v['status'] == 'Approved') ? 'readonly' : '';
                        $statusx = ($v['status'] == 'Approved') ? $approve_status : $status;
                        ?>
                        <tr style="background-color: <?= $rowClass ?> !important;"
                            class='row-<?= str_replace('s_', '', $k) ?>'>
                            <!--<td class="tg-kr944" style="padding: 5px;background-color: <?/*=$bgColor*/
                            ?>"><input type='text' data-sku-id='<?/*= str_replace('s_','',$k) */
                            ?>'  class='form-control ' name='DM[<?/*= $k */
                            ?>][sku]'-->
                            <td class="footable-first-column">
                                <!--<span class="mdi mdi-plus-circle-outline" style="font-size: 25px;"></span>-->
                                <a style="color: #54667a;" href='javascript:;'
                                   data-sku-id='<?= str_replace('s_', '', $k) ?>' class='dm-more up'>
                                    <i class='mdi mdi-plus-circle-outline' data-toggle="tooltip" title="More info"
                                       style="font-size: 20px"></i>
                                </a>
                            </td>
                            <td>
                                <input style="width:auto;" type='text' data-sku-id='<?= str_replace('s_', '', $k) ?>'
                                       class='form-control form-control-sm' name='DM[<?= $k ?>][sku]'
                                       readonly value='<?= $v['sku'] ?>'></td>
                            <td class="tg-kr944"><input readonly type='text' data-sku-id='<?= $k ?>'
                                                        class='form-control form-control-sm'
                                                        name='DM[<?= $k ?>][price]' value='<?= $v['price'] ?>'></td>

                            <td class="tg-kr944"><input readonly type='text' data-sku-id='<?= $k ?>'
                                                        class=' form-control form-control-sm'
                                                        name='DM[<?= $k ?>][subsidy]' value='<?= $v['subsidy'] ?>'></td>
                            <!--<td class="tg-kr944" style="padding: 15px;background-color: <?/*=$bgColor*/
                            ?>"><?/*= $v['stocks'] */
                            ?></td>-->
                            <td>
                                <input name='DM[<?= $k ?>][stock]' value="<?= isset($v['stocks']) ? $v['stocks'] : '0' ?>" class="form-control form-control-sm" readonly>
                            </td>
                            <td class="tg-kr944"><input readonly type='text' data-sku-id='<?= $k ?>'
                                                        class='form-control form-control-sm' name='DM[<?= $k ?>][qty]'
                                                        value='<?= $v['qty'] ?>'></td>
                            <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                                <td class="tg-kr944"><input readonly type='text' data-sku-id='<?= $k ?>'
                                                            class='form-control form-control-sm' name='DM[<?= $k ?>][qty]'
                                                            value='<?= $v['aqty'] ?>'></td>
                            <?php endif; ?>

                            <td class="tg-kr944"><input style="font-weight: bold" readonly type='text'
                                                        data-sku-id='<?= $k ?>' readonly class='form-control form-control-sm'
                                                        name='DM[<?= $k ?>][margin_per]'
                                                        value='<?= $v['margin_per'] ?>%'></td>
                            <td class="tg-kr944"><input readonly type='text' data-sku-id='<?= $k ?>' readonly
                                                        class='form-control form-control-sm' name='DM[<?= $k ?>][margin_rm]'
                                                        value='<?= $v['margin_rm'] ?>'></td>
                            <td class="tg-kr944">
                                <input readonly class='form-control form-control-sm' value="<?= $v['reason'] ?>"
                                       name='DM[<?= $k ?>][reason]'></td>
                            <td class="tg-kr944">
                                <select class='form-control select-status form-control-sm' name='DM[<?= $k ?>][status]'>
                                    <?php foreach ($statusx as $kx => $r):
                                        $selected = ($kx == $v['status']) ? 'selected' : ''; ?>
                                        <option <?= $selected ?> value="<?= $kx ?>"><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select></td>
                            <td class="tg-kr944"><textarea style="width: 150px;height: 35px;
    overflow: auto;" data-sku-id='<?= $k ?>' class='form-control form-control-sm'
                                                           name='DM[<?= $k ?>][comments]'><?= $v['comments'] ?></textarea>
                            </td>

                        </tr>
                        <?php
                        $Ccounter++;
                    endforeach; ?>
                </table>

            </div>
        </div>


    </div>
    <div class="form-group" style="margin-top: 20px;float: right;">
        <?php if ($model->status != 'cancel' && $model->status != 'expired'): ?>
            <?php
            if ( ($model->progress_status == 'Partially Approved' || $model->progress_status == 'New')) {
                ?>
                <?= Html::submitButton('Submit', ['class' => 'btn btn-success update-deal-approval', 'name' => 'request-btn',
                    'onClick' => 'ApprovedValidation()']) ?>
                <?php

            }
            ?>

            <?php
            if (time() >= strtotime($model->start_date) && time() <= strtotime($model->end_date)) {
                //  echo '<button class="btn" style="float: right;margin-left: 10px" id="save-as-draft">Cancel Deal</button>';
                //  echo '<input type="hidden" name="cancel_deal" value="cancel_deal">';
            }
        endif;
        ?>

        <?= Html::a('<i style="font-size: 25px;float: right;margin-left: 10px" class="fa fa-print"></i>', \yii\helpers\Url::to('/deals-maker/export?did=' . $model->id), ['class' => 'btn', 'title' => 'Export Deal Details', 'name' => 'export-btn',
            'onClick' => 'exportThis()']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
</div>
<script type="text/javascript">
    var pv = '<?=$model->pre_approve?>';

    function ApprovedValidation() {

        var select_dropdowns = $('.select-status');
        var v = [];
        select_dropdowns.each(function () {
            v.push($(this).val());
        })
        if (pv == '1') {
            if (v.includes("Submitted")) {
                $("#deal-maker-request").submit();
            } else {
                alert('Please Submitted atleast one sku row.');
                $("#deal-maker-request").submit(function (e) {
                    e.preventDefault();
                });
            }
        } else {
            //alert('else');
            if (v.includes("Approved") || v.includes("Adjustments") || v.includes("Cancel")) {
                //var submitform = $("#deal-maker-request").submit();
                document.getElementById("deal-maker-request").submit(); // or $("#form_id")[0].submit();
            } else {
                //alert('inner else');
                alert('Please Approve atleast one sku row.');
                $("#deal-maker-request").submit(function (e) {
                    e.preventDefault();
                });
            }
        }

        //console.log(v);

    }

    var requestedSKUS = '<?= (int)(30 - \backend\util\HelpUtil::getRequesterDealCount())?>';
    var isNewRecord = '<?= $model->isNewRecord; ?>';
</script>
<style>
    .control-label {
        padding: 0px !important;
    }
</style>