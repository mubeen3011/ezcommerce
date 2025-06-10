<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\warehouses */
/* @var $form yii\widgets\ActiveForm */
//echo '<pre>';print_r($model->settings);die;
$status = [1=>'Active',0=>'Inactive'];
$stock_deplete=[0=>'No',1=>'Yes'];
if ($model->settings!=''){
    $model->settings=json_decode($model->settings,1);
}
?>
<style>
    .tabcontent-border{
        border: none;
    }
</style>
<div class="warehouses-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="card">
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs customtab2" role="tablist">
                <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#home" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Basic Info</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#profile" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Assign Areas</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#messages" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-email"></i></span> <span class="hidden-xs-down">Stock badges</span></a> </li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane active show" id="home" role="tabpanel">
                    <div class="row" style="margin-top: 35px">
                        <div class="col-6 form-group">
                            <?= $form->field($model, 'name')->textInput(['maxlength' => true , 'required'=>true]) ?>
                        </div>
                        <div class="col-6 form-group">
                            <div class="form-group field-warehouses-channels">
                                <label class="control-label" for="warehouses-name">Channels</label>
                                <select class="select2 m-b-10 select2-multiple" style="width: 100%" multiple="multiple" data-placeholder="Choose" name="Warehouses[channels][]">
                                    <?php foreach ($channels as $val) {  ?>
                                        <option value="<?=$val['id']?>" <?=( isset($c_w_mapping) && in_array($val['id'],$c_w_mapping)) ? 'selected' : ''?>><?= $val['name']; ?></option>
                                    <?php } ?>
                                </select>

                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-6 form-group">
                            <?= $form->field($model, 'is_active')->dropDownList($status) ?>
                        </div>
                        <div class="col-6 form-group">
                            <?= $form->field($model, 'warehouse')->dropDownList(array_combine(Yii::$app->params['warehouse_types'],Yii::$app->params['warehouse_types']), ['class' => 'warehouse_type form-control'])->label('Warehouse Platform') ?>
                        </div>

                    </div>

                    <div class="config_space"></div>

                    <div class="row">
                        <div class="col-4 form-group">
                            <?= $form->field($model, 't1')->textInput(['type' => 'number','min' => 1 , 'required'=>true])->label('Threshold 1 (no of days)'); ?>
                        </div>
                        <div class="col-4 form-group">
                            <?= $form->field($model, 't2')->textInput(['type' => 'number','min' => 1 , 'required'=>true])->label('Threshold 2 (no of days)') ?>
                        </div>
                        <div class="col-4 form-group">
                            <?= $form->field($model, 'transit_days')->textInput(['type' => 'number','min' =>1 , 'required'=>true, 'value'=>1]) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-4 form-group">
                            <?= $form->field($model, 'city')->textInput(['type' => 'text', 'required'=>true]) ?>
                        </div>
                        <div class="col-4 form-group">
                            <?= $form->field($model, 'state')->textInput(['type' => 'text' , 'required'=>true]) ?>
                        </div>
                        <div class="col-4 form-group">
                            <?= $form->field($model, 'country')->textInput(['type' => 'text' , 'required'=>true]) ?>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-4 form-group">
                            <?= $form->field($model, 'zipcode')->textInput(['type' => 'number' , 'required'=>true]) ?>
                        </div>
                        <div class="col-8 form-group">
                            <?= $form->field($model, 'full_address')->textarea(); ?>
                        </div>


                    </div>
                    <div class="row">
                        <div class="col-6 form-group">
                            <?= $form->field($model, 'stock_deplete')->dropDownList($stock_deplete) ?>
                        </div>

                    </div>
                </div>
                <div class="tab-pane p-20" id="profile" role="tabpanel">
                    <div class="row" style="margin-top: 35px">
                        <h3>Auto assign orders to this warehouse for selected zipcodes</h3>
                        <div class="col-12 form-group">
                            <?=Yii::$app->controller->renderPartial('assign-orders-areas/zip-codes-list',['CS'=>$CS,'pre_selected_zip'=>$pre_selected_zip]);?>
                        </div>

                    </div>
                </div>
                <div class="tab-pane p-20" id="messages" role="tabpanel">

                    <div class="row" style="margin-top: 35px">
                        <div class="col-md-12 m-b-30">
                            <h3>Assign color to stock list badges</h3>
                        </div>
                        <div class="col-md-4 m-b-30">
                            <div class="example">
                                <h5 class="box-title">Out Of Stock</h5>
                                <input type="text" class="complex-colorpicker  form-control" name="Settings[StockListBadges][DaysListBadges][out_of_sock]" value="<?=(isset($model->settings['[StockListBadges]']['DaysListBadges']['out_of_sock'])) ? $model->settings['StockListBadges']['DaysListBadges']['out_of_sock'] : '#f62d51'?>" />
                            </div>
                            <div class="input-group" style="width:56%;margin-top: 20px">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">Days < </span>
                                </div>
                                <input type="number" value="<?=(isset($model->settings['StockListBadges']['DaysLevelRanges']['out_of_sock'])) ? $model->settings['StockListBadges']['DaysLevelRanges']['out_of_sock'] : '1'?>" class="form-control out-of-stock" min="1" name="Settings[StockListBadges][DaysLevelRanges][out_of_sock]">
                            </div>
                        </div>
                        <div class="col-md-4 m-b-30">
                            <div class="example">
                                <h5 class="box-title">Going to OOS</h5>
                                <input type="text" class="complex-colorpicker form-control" name="Settings[StockListBadges][DaysListBadges][out_of_stock_soon]" value="<?=(isset($model->settings['StockListBadges']['DaysListBadges']['out_of_stock_soon'])) ? $model->settings['StockListBadges']['DaysListBadges']['out_of_stock_soon'] : '#ffbc34'?>" />
                            </div>
                            <div class="input-group" style="width:56%;margin-top: 20px">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">Days > </span>
                                </div>
                                <input readonly type="number" value="<?=(isset($model->settings['StockListBadges']['DaysLevelRanges']['out_of_stock_soon_greater'])) ? $model->settings['StockListBadges']['DaysLevelRanges']['out_of_stock_soon_greater'] : '0'?>" class="form-control ooss_greater" min="0" name="Settings[StockListBadges][DaysLevelRanges][out_of_stock_soon_greater]">
                            </div>
                            <div class="input-group" style="width:56%;margin-top: 20px">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">Days < </span>
                                </div>
                                <input type="number" value="<?=(isset($model->settings['StockListBadges']['DaysLevelRanges']['out_of_sock_soon_less'])) ? $model->settings['StockListBadges']['DaysLevelRanges']['out_of_sock_soon_less'] : '30'?>" class="form-control ooss_less" min="0" name="Settings[StockListBadges][DaysLevelRanges][out_of_sock_soon_less]">
                            </div>
                        </div>
                        <div class="col-md-4 m-b-30">
                            <div class="example">
                                <h5 class="box-title">In Days</h5>
                                <input type="text" class="complex-colorpicker form-control" name="Settings[StockListBadges][DaysListBadges][in_stock]" value="<?=(isset($model->settings['StockListBadges']['DaysListBadges']['in_stock'])) ? $model->settings['StockListBadges']['DaysListBadges']['in_stock'] : '#55ce63'?>" />
                            </div>
                            <div class="input-group" style="width:56%;margin-top: 20px">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">Days > </span>
                                </div>
                                <input readonly type="number" class="form-control in_stock" value="<?=(isset($model->settings['StockListBadges']['DaysLevelRanges']['in_stock'])) ? $model->settings['StockListBadges']['DaysLevelRanges']['in_stock'] : '30'?>" min="0" name="Settings[StockListBadges][DaysLevelRanges][in_stock]">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?= Html::submitButton('Save', ['class' => 'btn btn-success pull-right']) ?>
    <?php ActiveForm::end(); ?>
</div>

<?php
$already_selected=(isset($model->warehouse) && !empty($model->warehouse)) ? $model->warehouse:"new_entry";
$config_fields=json_encode(Yii::$app->params['warehouse_types_config']);
$config_values=(isset($model->configuration) && !empty($model->configuration)) ? $model->configuration:"{}";
?>
<script>
    var config_values = <?=$config_values?>;
    var config_fields = <?=$config_fields?>;
    var already_selected = <?=$already_selected?>;
</script>