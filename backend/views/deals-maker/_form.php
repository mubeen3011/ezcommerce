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
$isTop = "";
if ( $model->isNewRecord ){
    $channel = Channels::find()->where(['is_active' => '1', 'is_fetch_sales' => '1'])->orderBy('id')->asArray()->all();
}else{
    $channel = Channels::find()->where(['is_active' => '1', 'is_fetch_sales' => '1','id'=>$model->channel_id])->orderBy('id')->asArray()->all();
}

$channelList = ArrayHelper::map($channel, 'id', 'name');

/*$category = \common\models\Category::find()->where(['is_active' => '1'])->andWhere(['parent_id' => null])->orderBy('id')->asArray()->all();

$categoryList = ArrayHelper::map($category, 'id', 'name');
$categoryList['all']='all';*/

$dd_categories=\common\models\Category::find()->where(['is_active'=>1])->asArray()->all();
$dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
$category_list =\backend\util\HelpUtil::dropdown_3_level($dd_categories);

// customer type
$customer_type = [
    ['id'=>'B2C','name'=>'B2C'],
    ['id'=>'B2B','name'=>'B2B']
];
$customerTypeList = ArrayHelper::map($customer_type, 'id', 'name');

// discount type
$discount_type = [
    ['id'=>'','name'=>''],
    ['id'=>'Percentage','name'=>'Percentage'],
    ['id'=>'Amount','name'=>'Amount']
];
$discountTypeList = ArrayHelper::map($discount_type, 'id', 'name');

$data=[];
if ( isset($model->channel_id) ){
    $skuAlreadyAdded = \backend\util\DealsUtil::skusInADeal($model->id);
    $skuList = \backend\util\DealsUtil::getActiveSkus([],$model->channel_id);
    $list =[];
    foreach ( $skuList as $value ){
        if ( !in_array($value['sku_id'], $skuAlreadyAdded) ){
            $list[$value['sku_id']]=$value['sku'];
        }
    }

    $data = $list;

}

//echo '<pre>';print_r($data);die;
$reasons = ['Competitor Top' => 'Competitor Top', 'Focus SKUs' => 'Focus SKUs', 'Philips Campaign' => 'Philips Campaign',
    'Flash Sale' => 'Flash Sale', 'Shocking Deal' => 'Shocking Deal', 'Aging Stocks' => 'Aging Stocks', 'EOL' => 'EOL',
    'Competitive Pricing' => 'Competitive Pricing', 'Outright' => 'Outright','Mega Campaign'=>'Mega Campaign', 'Others' => 'Others'];
if ($model->pre_approve == 1)
    $status = ['Pending' => 'Pending', 'Adjustments' => 'Adjustments', 'Approved' => 'Submit', 'Reject' => 'Reject', 'Cancel' => 'Cancel', 'Submitted' => 'Approved'];
else
    $status = ['Pending' => 'Pending', 'Adjustments' => 'Adjustments', 'Approved' => 'Approved', 'Reject' => 'Reject', 'Cancel' => 'Cancel'];
$model->name = ($model->name && !$model->isNewRecord) ? $model->name : '';
$prefixName = (!$model->isNewRecord) ? $model->name : '';
if ( $model->status=='draft' ){
    $isDisable = ($model->progress_per != 0) ? false : false;
}else{
    $isDisable = ($model->progress_per != 0) ? true : false;
}
//die;
$isDisableTxt = ($model->progress_per != 0) ? "disabled" : "";
$model->category = (!$model->isNewRecord) ? explode(',', rtrim($model->category ,',')) : '';
$stats = \backend\util\DealsUtil::_dealStats($model->id);

$changeAbleInputs=true;
$test=true;
if ( $model->isNewRecord ){
    $changeAbleInputs=false;
}else if( $model->status == 'draft'){
    $changeAbleInputs=false;
}else{
    $changeAbleInputs=true;
}
$readonly='';
if ($model->status=='active'){
    $readonly='readonly';
}
if ($model->discount_type=='Percentage'){
    $price_read_only = 'readonly';
}else{
    $price_read_only = '';
}
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
        <p for="dealsmaker-rn"><strong style="font-weight: bold">(<?= "Prefix:" . $prefixName ?>)</strong></p>
    </div>
    <div class="content-box-wrapper">
        <?php $form = ActiveForm::begin(['id' => 'deal-maker-requests','options' => ['enctype' => 'multipart/form-data']]); ?>
        <input type="hidden" value=<?= (isset($additional_sku)) ? json_encode($additional_sku) : '[]' ?> class="additional-skus"/>
        <div class="col-md-12" style="padding: 0px;">

            <div class="row">
                <div class="col-md-4" >
                    <?= $form->field($model, 'name')->textInput(['required'=>true,'maxlength' => true, 'readonly' => !$model->isNewRecord])->hint('(optional) ** Auto prefix with Channel and Id') ?>
                </div>
                <div class="col-md-4" >
                    <?php
                    /*if ( $model->isNewRecord ){
                        $channelParam = ['options'=>[
                                '17'=>['enabled'=>true],
                            '21'=>['enabled'=>true],
                            '19'=>['enabled'=>true]
                        ],'class' => 'form-control deal_channel', 'prompt' => 'Select Channel', 'readonly' => !$model->isNewRecord];
                    }else{
                        $channelParam = ['class' => 'form-control deal_channel', 'readonly' => !$model->isNewRecord];
                    }
                    */?>
                    <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['class' => 'form-control deal_channel', 'prompt' => 'Select Channel', 'readonly' => !$model->isNewRecord])->label('Shop *') ?>
                </div>
                <style>
                    .select2{
                        margin:0px !important;
                    }
                </style>
                <div class="col-md-4" <!--style="max-width:0%;--><?/*= $isTop */?>">

                <div class="form-group field-dealsmaker-category">
                    <label class="control-label" for="dealsmaker-category">Category</label>
                    <input type="hidden" name="DealsMaker[category]" value="">
                    <select class="category-selector select2 form-control full-width" name="DealsMaker[category][]" multiple="" <?= $changeAbleInputs ? 'disabled':'';?> size="4" tabindex="-1" aria-hidden="true">
                        <?php
                        foreach($category_list as $cat_dd) { ?>
                            <option value="<?= $cat_dd['key'];?> " <?= (is_array($model->category) && in_array((int)$cat_dd['key'],$model->category))? "selected":"";?>> <?= $cat_dd['space'].$cat_dd['value'];?> </option>

                        <?php } ?>
                    </select>

                    <div class="help-block"></div>
                </div>
                <!-- $form->field($model, 'category')->dropDownList($categoryList, ['class' => 'category-selector select2 form-control full-width', 'multiple' => true, 'disabled'=>$changeAbleInputs]); -->
            </div>
            <?php
            $deal_status = ['expired','cancel'];
            ?>
        </div>


        <div class="row">
            <div class="col-md-4" >
                <?php
                $start_date_attr=['style' => 'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;', 'maxlength' => true, 'class' => 'start_datetime form-control', 'autocomplete' => 'off'];
                if ($model->status=='active'){
                    $start_date_attr['disabled']='disabled';
                }
                ?>
                <?= $form->field($model, 'start_date')->textInput($start_date_attr)->label('Start Date *'); ?>
            </div>
            <div class="col-md-4" >
                <?php
                $end_date_attr=['style' => 'background:url(/theme1/images/icons/calendar.png) no-repeat scroll 5px 1px;padding-left:38px;', 'maxlength' => true, 'class' => 'end_datetime form-control', 'autocomplete' => 'off'];
                if ($model->status=='active'){
                    $end_date_attr['disabled']='disabled';
                }
                ?>
                <?= $form->field($model, 'end_date')->textInput($end_date_attr)->label('End Date *'); ?>
            </div>
            <div class="col-md-4" >
                <?php
                $motivation_attr=['maxlength' => true];
                if ($model->status=='active'){
                    $motivation_attr['readonly']='';
                }
                ?>
                <?= $form->field($model, 'motivation')->textarea($motivation_attr)->label('Motivation *') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'discount_type')->dropDownList(array_merge($discountTypeList), ['class' => 'form-control full-width discount-type', 'disabled' => $changeAbleInputs]) ?>
            </div>
            <div class="col-md-4">
                <?php
                $customer_type_attr = ['class' => 'form-control full-width customer-type'];
                if($model->status=='active'){
                    $customer_type_attr['readonly']='readonly';
                }
                ?>
                <?= $form->field($model, 'customer_type')->dropDownList(array_merge($customerTypeList), $customer_type_attr) ?>
            </div>
            <div class="col-md-4" >
                <?php
                $budget_attr = ['maxlength' => true, 'class' => 'numberinput form-control'];
                if ($model->status=='active'){
                    $budget_attr['readonly']='readonly';
                }
                ?>
                <?= $form->field($model, 'budget')->textInput($budget_attr) ?>
            </div>
        </div>
        <div class="row last-row">
            <div class="col-md-4 <?=($model->isNewRecord || ($model->status=='draft' && $model->discount=='')) ? 'hide' : ''?>">
                <?php
                $discount_attr=['type' => 'number','min'=>1,'maxlength' => true, 'class' => 'discount-input numberinput form-control'];
                if ($model->status=='active'){
                    $discount_attr['readonly'] = 'readonly';
                }
                ?>
                <?= $form->field($model, 'discount')->textInput($discount_attr) ?>
            </div>
            <div class="col-md-4">
                <div class="col-md-6 <?=(in_array($model->status,$deal_status)) ? 'hide' : ''?>" style="max-width:49.5%;<?= $isTop ?>" >
                    <div class="form-group">
                        <label class="control-label" for="dealsmaker-import">Import csv</label>
                        <input type="file" name="deals_csv_import" accept=".csv"/>
                        Sample CSV : <a href="/sample-files/DEAL-IMPORT-SAMPLE-CSV.csv">Download CSV</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 hide" style="margin-top: 35px;">
                <?php
                ?>
                <?= $form->field($model, 'pre_approve', ['options' => ['class' => 'col-md-4 pull-left']])->checkbox(['maxlength' => true]) ?>
                <?php
                ?>
            </div>

            <input type="hidden" id="deal_id" value="<?= $model->id ?>"/>
        </div>

        <div class="row">

            <div class="col-md-4 col-lg-6 col-xlg-4">
                <?php
                if ($model->status == 'active' || $model->status == 'expired' || $model->status == 'new' || $model->status == 'draft'):
                    ?>
                    <h3 class="box-title m-b-0">Deal Stats By Targets</h3>
                    <h5>GMV (<?=Yii::$app->params['currency']?>)<span class="badge badge-info"><?=$stats['GMV']?></span></h5>
                    <h5>Sum Of Total Profit (<?=Yii::$app->params['currency']?>)<span class="badge badge-<?=($stats['Rm_Profit']>0) ? 'success' : 'danger' ?>"><?=$stats['Rm_Profit']?> </span></h5>
                    <h5>Total A&O Margin
                        <span class="badge badge-<?=($stats['Total_Margin_Percentage']>0) ? 'success' : 'danger' ?>">
                            <?=$stats['Total_Margin_Percentage']?> %
                        </span>
                    </h5>
                    <?php
                endif;
                ?>

                <?php
                $roi = ((double)array_sum($model->actual_sales) > 0) ? (((double)$model->budget / (double)array_sum($model->actual_sales)) * 100) : 0; ?>

                <?php
                if ($model->status == 'active' || $model->status == 'expired'):
                    $isTop = "top:-192px";
                    ?>
                    <h5>Target Sales (<?=Yii::$app->params['currency']?>) <span class="badge badge-info"><?=number_format(array_sum($model->target_sales))?></span></h5>
                    <h5>Actual Sales (<?=Yii::$app->params['currency']?>)<span class="badge badge-info"><?=number_format(array_sum($model->actual_sales), 2)?></span></h5>
                    <h5>ROI <span class="badge badge-info"><?=number_format($roi, 2) . '%'?></span></h5>
                <?php endif; ?>


            </div>

        </div>

    </div>
    <div class="col-md-12">
        <hr>
        <div class="multi-skus" style=" overflow-x: scroll;">

            <table id="demo-foo-row-toggler" style="width: 100%;max-height: 250px;overflow-y: auto;"
                   cellspacing="5"
                   class="dm-multi-sku dm-multi-sku table toggle-circle table-hover default breakpoint footable-loaded footable table-striped table-bordered nowrap">
                <thead>
                <tr class="sku-add-main">
                    <th class="">

                    </th>
                    <th class="multi-sku" style="width: 100px !important;padding: 12px;">
                        <div class="form-group field-dealsmaker-deal_price">
                            <label class="control-label" for="dealsmaker-deal_skus">SKU</label>
                            <select name="DealsMaker[skus]" class="main-sku-selector select2 deal-channel-sku-list select2-multiple multi-select-sku" placeholder="Select a SKU ..." id="multi-sku-sel">
                                <?php
                                if (isset($channelSku)){
                                    echo $channelSku;
                                }
                                ?>
                            </select>
                        </div>
                    </th>
                    <th class="multi-sku-price">
                        <?= $form->field($model, 'deal_price')->textInput(['maxlength' => true, 'class' => 'dm-price deal-td-width main-sku-price form-control form-control-sm', 'disabled' => $isDisable]) ?>
                    </th>

                    <th class="multi-sku-subsdiy"><?= $form->field($model, 'deal_subsidy')->textInput(['maxlength' => true, 'class' => 'deal-td-width dm-subsidy main-sku-subsidy form-control form-control-sm', 'disabled' => $isDisable]) ?></th>
                    <th class="multi-sku-qty">
                        <div class="form-group field-dealsmaker-deal_qty">
                            <label class="control-label" for="dealsmaker-deal_qty" data-toggle="tooltip" title="Current Stocks (OverAll)">C.S</label>
                            <input type="text" readonly class="form-control dm-cstock form-control-sm main-sku-stock deal-td-width main-sku-stock"
                                   value="" <?= $isDisableTxt ?>>
                        </div>
                    </th>
                    <th class="multi-sku-qty"><?= $form->field($model, 'deal_qty')->textInput(['maxlength' => true, 'class' => 'deal-td-width dm-qty main-sku-target form-control form-control-sm', 'disabled' => $isDisable])
                            ->label('S.T',['data-toggle'=>'tooltip','title'=>'Sales Target']) ?>
                    </th>
                    <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                        <th class="multi-sku-qty" data-toggle="tooltip" title="Actual Sales Count">A.S.C</th>
                    <?php endif; ?>
                    <th class="multi-sku-qty">
                        <div class="form-group field-dealsmaker-deal_qty">
                            <label class="control-label" for="dealsmaker-deal_qty">Margin %</label>
                            <input type="text" class="form-control dm-margin-per form-control-sm main-sku-margin-percentage deal-td-width" readonly>
                        </div>
                    </th>
                    <th class="multi-sku-qty">
                        <div class="form-group field-dealsmaker-deal_qty">
                            <label class="control-label" for="dealsmaker-deal_qty">Margin <?=Yii::$app->params['currency']?></label>
                            <input type="text" class="form-control dm-margin-rm form-control-sm main-sku-margin-amount deal-td-width" readonly>
                        </div>
                    <th class="multi-sku-qty"><?= $form->field($model, 'reasons')->dropDownList($reasons, ['prompt' => 'Select Reason', 'class' => 'dm-reason form-control form-control-sm', 'disabled' => $isDisable]) ?></th>
                    <?php if ($model->progress_per != 0): ?>
                        <th class="multi-sku-qty" style="line-height: 150px">Status</th>
                        <th class="multi-sku-qty" style="line-height: 150px">Comments</th>
                    <?php endif; ?>
                    <th class="multi-sku-qty"
                        style="vertical-align: bottom;line-height: 65px;"><?php if ( $model->status != 'cancel'): ?>
                            <a href="javascript:;" title="Add SKU" class="add-dm-sku"><i
                                        class="btn btn-info btn-mini glyph-icon icon-plus" style="height: 38px;
    padding: 10px;"></i>
                            </a>
                        <?php endif; ?>
                    </th>
                </tr>

                </thead>
                <tbody>
                <?php
                foreach ($multiSkus as $k => $v):
                    $sku = str_replace('s_', '', $k);
                    if (isset($_GET['sku']) && $_GET['sku'] != $sku)
                        continue;
                    $trClass='';
                    if ( !isset($v['form_cat']) && $v['form_cat']=='' ){
                        $trClass='line-item-row';
                    }else{
                        if (isset($v['form_cat']) && $v['form_cat']!=''){
                            $trClass='category-added category-row-';
                            $trClass.=$v['form_cat'];
                        }
                    };
                    ?>
                    <tr class='row-<?= str_replace('s_', '', $k) ?> <?=$trClass?>'>
                        <td class="footable-first-column">
                            <a style="color: #54667a;" href='javascript:;'
                               data-sku-id='<?= str_replace('s_', '', $k) ?>' class='dm-more up'>
                                <i class='mdi mdi-plus-circle-outline' data-toggle="tooltip" title="More info"
                                   style="font-size: 20px"></i>
                            </a>
                        </td>
                        <td class="tbody-td-padding td-sku-id">
                            <input style="width: auto" type='text' data-sku-id='<?= str_replace('s_', '', $k) ?>'
                                   class='skunames form-control form-control-sm' name='DM[<?= $k ?>][sku]'
                                   readonly value='<?= $v['sku'] ?>'>
                        </td>
                        <td class="tbody-td-padding td-sku-price input-text"><input type='number' data-sku-id='<?= $k ?>'
                                                                                    class='form-control list-sku-price form-control-sm deal-td-width'
                                                                                    name='DM[<?= $k ?>][price]'
                                                                                    <?=$price_read_only?>
                                                                                    value='<?= $v['price'] ?>' <?=$readonly?>>
                        </td>


                        <td class="tbody-td-padding td-sku-subsidy input-text"><input type='text' data-sku-id='<?= $k ?>'
                                                                                      class=' form-control list-sku-subsidy form-control-sm deal-td-width'
                                                                                      name='DM[<?= $k ?>][subsidy]'
                                                                                      value='<?= $v['subsidy'] ?>' <?=$readonly?>>
                        </td>
                        <td class="td-sku-stock"><input readonly type='text' class='form-control form-control-sm deal-td-width' name='DM[<?= $k ?>][stock]'
                                                        value="<?= isset($v['stocks']) ? $v['stocks'] : '0' ?>">
                        </td>
                        <td class="tbody-td-padding input-text td-sku-target"><input type='text' data-sku-id='<?= $k ?>'
                                                                                     class='form-control form-control-sm deal-td-width'
                                                                                     name='DM[<?= $k ?>][qty]'
                                                                                     value='<?= $v['qty'] ?>' <?=$readonly?>>
                        </td>
                        <?php if ($model->status == 'expired' || $model->status == 'active'): ?>
                            <td class="tbody-td-padding input-text"><input readonly type='text'
                                                                           data-sku-id='<?= $k ?>'
                                                                           class='form-control form-control-sm'
                                                                           name='DM[<?= $k ?>][qty]'
                                                                           value='<?= $v['aqty'] ?>'>
                            </td>
                        <?php endif; ?>

                        <td class="tbody-td-padding input-text td-sku-margin-percentage"><input type='text' data-sku-id='<?= $k ?>' readonly
                                                                                                class='form-control form-control-sm deal-td-width'
                                                                                                name='DM[<?= $k ?>][margin_per]'
                                                                                                value='<?= $v['margin_per'] ?>%'>
                        </td>
                        <td class="tbody-td-padding input-text td-sku-margin-amount"><input type='text' data-sku-id='<?= $k ?>' readonly
                                                                                            class='form-control form-control-sm deal-td-width'
                                                                                            name='DM[<?= $k ?>][margin_rm]'
                                                                                            value='<?= $v['margin_rm'] ?>'>
                        </td>
                        <td class="tbody-td-padding"><select <?=$readonly?> class='form-control form-control-sm' style="width: 150px;"
                                                                            name='DM[<?= $k ?>][reason]'>
                                <?php foreach ($reasons as $kx => $r):
                                    $selected = ($kx == $v['reason']) ? 'selected' : ''; ?>
                                    <option <?= $selected ?> value="<?= $kx ?>"><?= $r ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <?php if ($model->progress_per != 0): ?>
                            <td class="input-select">
                                <select <?= $readonly ?> class='form-control form-control-sm' name='DM[<?= $k ?>][status]'>
                                    <?php foreach ($status as $kx => $r):
                                        $selected = ($kx == $v['status']) ? 'selected' : ''; ?>
                                        <option <?= $selected ?> value="<?= $kx ?>"><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                    <textarea style="height: 30px;width: 250px;" data-sku-id='<?= $k ?>' class='form-control form-control-sm'
                                              name='DM[<?= $k ?>][comments]'><?= $v['comments'] ?></textarea>
                            </td>
                        <?php endif; ?>

                        <td class="tbody-td-padding">
                            <?php if ($v['status'] != 'Approved' && $model->status != 'cancel') : ?>
                                <a href='javascript:;' data-sku-id='<?= str_replace('s_', '', $k) ?>'
                                   class='dm-delete'>
                                    <i class='glyph-icon icon-trash' style='font-size:20px;color: red;'></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>
                </tbody>
            </table>

        </div>
        <?php
        if (isset($_GET['id'])){
            ?>
            <a href="/deals-maker/clone-csv?deal_id=<?=$_GET['id']?>" style="float: left;">Download CSV <i data-toggle="tooltip" data-original-title="When the sales team wants
to clone a deal, They will just download the CSV and upload it in a new deal to save time." class="mdi mdi-information"></i></a>
            <?php
        }
        ?>
        <div class="form-group" style="margin-top: 20px;
    float: right;">
            <?php if ($model->status != 'cancel' && $model->status != 'expired' && $model->status != 'active' ): ?>
                <?= Html::submitButton($model->isNewRecord ? 'Request' : 'Resubmit Request', ['class' => 'btn-req btn btn-success hide', 'name' => 'request-btn']) ?>
                <input type="hidden" name="save_as_draft" value="false" id="savedraft">
                <?php
                if ($model->status == 'draft' || $model->isNewRecord) {
                    ?>
                    <button class="hide btn-draft btn btn-warning" style="float: right;margin-left: 10px"
                            id="save-as-draft">Save as draft
                    </button>

                    <?php
                } else if (!$model->isNewRecord || $model->status == 'draft') {
                    echo Html::a('<i style="font-size: 25px;float: right;margin-left: 10px" class="fa fa-print"></i>', \yii\helpers\Url::to('/deals-maker/export?did=' . $model->id), ['class' => 'btn', 'title' => 'Export Deal Details', 'name' => 'export-btn',
                        'onClick' => 'exportThis()']);
                }
                if (time() >= strtotime($model->start_date) && time() <= strtotime($model->end_date)) {
                    echo '<button class="btn" style="float: right;margin-left: 10px" id="save-as-draft">Cancel Deal</button>';
                    echo '<input type="hidden" name="cancel_deal" value="cancel_deal">';
                }

                ?>

            <?php else:
                echo Html::a('<i style="font-size: 25px;float: right;margin-left: 10px" class="fa fa-print"></i>', \yii\helpers\Url::to('/deals-maker/export?did=' . $model->id), ['class' => 'btn', 'title' => 'Export Deal Details', 'name' => 'export-btn',
                    'onClick' => 'exportThis()']);
            endif; ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
    </div>
    <div id="SkuExceptionModel" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Deal Exception</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                </div>
                <div class="modal-body">

                </div>

            </div>

        </div>
    </div>

    <script type="text/javascript">
        var requestedSKUS = '<?= (int)(30 - \backend\util\HelpUtil::getRequesterDealCount(null))?>';
        var isNewRecord = '<?= $model->isNewRecord; ?>';

    </script>
<?php
$this->registerJs("$('#save-as-draft').click(function () {
        $('#savedraft').val('true');
    });
    
    ");
?>