<?php

use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Settings */
/* @var $form yii\widgets\ActiveForm */
$model->name = str_replace('_',' ',$model->name);
$model->name = ucwords($model->name);
?>

<div class="settings-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true,'disabled'=>true]) ?>
    <?= $form->field($model, 'orgName')->hiddenInput(['value' => $model->orgName])->label(false) ?>

    <?php if($model->orgName == 'deals_allow_margin_limit'):
        $value = json_decode($model->value,true);
        $model->margin = $value['margin_allow'];
        $model->limit = $value['total_skus'];
        ?>
        <div class="form-group field-settings-name">
            <label class="control-label" for="settings-name" style="font-weight: bold">(-ve) Margin Limit</label> <small style="color:#681818"><?=$model->margin?></small><br/>
            <!--<input  class="form-control ds" name="Settings[margin]"  data-slider-id='s-dm'  type="text" data-slider-min="5" data-slider-max="30" data-slider-step="1" data-slider-value="<?/*=(-1 * $model->margin)*/?>" >-->
            <div id="s-dm"></div>
            <div class="help-block" style="color: grey;">Negative margin limit for Deal Maker</div>
        </div>
        <div class="form-group field-settings-name">
            <label class="control-label" for="settings-name" style="font-weight: bold">Total SKU allowed:</label> <small style="color:#681818"><?=$model->limit?></small><br/>
            <input  class="form-control ds" name="Settings[limit]" id="s-dl" data-slider-id='s-dl'  type="text" data-slider-min="5" data-slider-max="30" data-slider-step="1" data-slider-value="<?=$model->limit?>" >
            <div id=""></div>
            <div class="help-block" style="color: grey;">Total no of SKU allowed for Deal Maker</div>
        </div>
    <?php elseif($model->orgName == 'shipping_cost'):
                 $value = json_decode($model->value,true);
                 $model->min_fbl_sc = $value['fbl_sc'];
                 $model->min_fbl_ppc = $value['fbl_ppc'];
                 $model->min_fbl_wc = isset($value['fbl_wc']) ? $value['fbl_wc'] : 0;
                 $model->isis_ppc = $value['isis_ppc'];
                 $model->isis_sc = $value['isis_sc'];
                 $model->isis_wc =  isset($value['isis_wc']) ? $value['isis_wc'] : 0;
                 $model->selling_cost = $value['selling_cost'];
                 ?>
        <div class="form-group field-settings-name">
            <label class="control-label" for="settings-name">Selling Cost (RM)</label><br/>
            <input  class="form-control" name="Settings[selling_cost]" value="<?=$model->selling_cost?>" >
            <div class="help-block">Least cost value for free pick and pack cost</div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">FBL  (Shipping cost)</label><br/>
                    <input  class="form-control" name="Settings[min_fbl_sc]" value="<?=$model->min_fbl_sc?>" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">FBL (Pick & Pack cost)</label><br/>
                    <input  class="form-control" name="Settings[min_fbl_ppc]" value="<?=$model->min_fbl_ppc?>" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">FBL (Warehouse cost)</label><br/>
                    <input  class="form-control" name="Settings[min_fbl_wc]" value="<?=$model->min_fbl_wc?>" >
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">ISIS  (Shipping cost)</label><br/>
                    <input  class="form-control" name="Settings[isis_sc]" value="<?=$model->isis_sc?>" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">ISIS (Pick & Pack cost)</label><br/>
                    <input  class="form-control" name="Settings[isis_ppc]" value="<?=$model->isis_ppc?>" >
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group field-settings-name">
                    <label class="control-label" for="settings-name">ISIS (Warehouse cost)</label><br/>
                    <input  class="form-control" name="Settings[isis_wc]" value="<?=$model->isis_wc?>" >
                </div>
            </div>
        </div>
        <div class="row">
            <?php elseif ($model->orgName == 'auto_deal_margin_settings'):
                $value = json_decode($model->value, true);
                $model->ss_high_margin = $value['ss']['high'];
                $model->ss_medium_margin = $value['ss']['medium'];
                $model->ss_slow_margin = $value['ss']['slow'];

                $model->sv_high_margin = $value['sv']['high'];
                $model->sv_medium_margin = $value['sv']['medium'];
                $model->sv_slow_margin = $value['sv']['slow'];

                $model->as_high_margin = $value['as']['high'];
                $model->as_medium_margin = $value['as']['medium'];
                $model->as_slow_margin = $value['as']['slow'];

                $model->stock_limit = $value['stock_limit_check'];
                $model->limit_margin = $value['limit_margins'];

                $model->weighted_avg_margin_1_1 = $value['weighted_avg_margins']['range_1']['from'];
                $model->weighted_avg_margin_1_2 = $value['weighted_avg_margins']['range_1']['to'];
                $model->allowed_margin_1 = $value['weighted_avg_margins']['range_1']['allowed_margin'];

                $model->weighted_avg_margin_2_1 = $value['weighted_avg_margins']['range_2']['from'];
                $model->weighted_avg_margin_2_2 = $value['weighted_avg_margins']['range_2']['to'];
                $model->allowed_margin_2 = $value['weighted_avg_margins']['range_2']['allowed_margin'];

                $model->weighted_avg_margin_3_1 = $value['weighted_avg_margins']['range_3']['from'];
                $model->weighted_avg_margin_3_2 = $value['weighted_avg_margins']['range_3']['to'];
                $model->allowed_margin_3 = $value['weighted_avg_margins']['range_3']['allowed_margin'];

                ?>
                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Selling Status</legend>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">High</label><br/>
                            <input class="form-control" type="number" name="Settings[ss_high_margin]"
                                   value="<?= $model->ss_high_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Medium</label><br/>
                            <input class="form-control" type="number" name="Settings[ss_medium_margin]"
                                   value="<?= $model->ss_medium_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Slow</label><br/>
                            <input class="form-control" type="number" name="Settings[ss_slow_margin]"
                                   value="<?= $model->ss_slow_margin ?>">
                        </div>
                    </fieldset>
                </div>
                <hr>
                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Stock Value</legend>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">High</label><br/>
                            <input class="form-control" type="number" name="Settings[sv_high_margin]"
                                   value="<?= $model->sv_high_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Medium</label><br/>
                            <input class="form-control" type="number" name="Settings[sv_medium_margin]"
                                   value="<?= $model->sv_medium_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Slow</label><br/>
                            <input class="form-control" type="number" name="Settings[sv_slow_margin]"
                                   value="<?= $model->sv_slow_margin ?>">
                        </div>
                    </fieldset>
                </div>
                <hr>
                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Aging Status</legend>
                        <div class="col-md-3">
                            <label class="control-label"  for="settings-name">High</label><br/>
                            <input class="form-control" type="number" name="Settings[as_high_margin]"
                                   value="<?= $model->as_high_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Medium</label><br/>
                            <input class="form-control" type="number" name="Settings[as_medium_margin]"
                                   value="<?= $model->as_medium_margin ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Slow</label><br/>
                            <input class="form-control" type="number" name="Settings[as_slow_margin]"
                                   value="<?= $model->as_slow_margin ?>">
                        </div>
                    </fieldset>
                </div>
                <hr />
                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Stock & Margins</legend>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Stock Limit</label><br/>
                            <input class="form-control" name="Settings[stock_limit]"
                                   value="<?= $model->stock_limit ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Limit Margin</label><br/>
                            <input class="form-control" name="Settings[limit_margin]"
                                   value="<?= $model->limit_margin ?>">
                        </div>
                    </fieldset>
                </div>

                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Weighted avg margins & allowed margins (FIRST)</legend>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Weighted avg margin Range (FROM)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_1_1]"
                                   value="<?= $model->weighted_avg_margin_1_1 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Weighted avg margin Range (TO)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_1_2]"
                                   value="<?= $model->weighted_avg_margin_1_2 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Allowed margin</label><br/>
                            <input class="form-control" type="number" name="Settings[allowed_margin_1]"
                                   value="<?= $model->allowed_margin_1 ?>">
                        </div>
                    </fieldset>
                </div>

                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Weighted avg margins & allowed margins (SECOND)</legend>
                        <div class="col-md-3">
                            <label class="control-label"  for="settings-name">Weighted avg margin Range (FROM)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_2_1]"
                                   value="<?= $model->weighted_avg_margin_2_1 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Weighted avg margin Range (TO)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_2_2]"
                                   value="<?= $model->weighted_avg_margin_2_2 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Allowed margin</label><br/>
                            <input class="form-control" type="number" name="Settings[allowed_margin_2]"
                                   value="<?= $model->allowed_margin_2 ?>">
                        </div>
                    </fieldset>
                </div>

                <div class="form-group field-settings-name">
                    <fieldset>
                        <legend>Weighted avg margins & allowed margins (THIRD)</legend>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Weighted avg margin Range (FROM)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_3_1]"
                                   value="<?= $model->weighted_avg_margin_3_1 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Weighted avg margin Range (TO)</label><br/>
                            <input class="form-control" type="number" name="Settings[weighted_avg_margin_3_2]"
                                   value="<?= $model->weighted_avg_margin_3_2 ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label" for="settings-name">Allowed margin</label><br/>
                            <input class="form-control" type="number" name="Settings[allowed_margin_3]"
                                   value="<?= $model->allowed_margin_3 ?>">
                        </div>
                    </fieldset>
                </div>

            <?php else: ?>

                <?= $form->field($model, 'value')->textInput(['maxlength' => true]) ?>
            <?php endif; ?>

            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                <?= ($model->orgName == "auto_deal_margin_settings") ?  Html::a('Update SKU weighted margins',['/cron/sku-margins-for-dm-update'] ,['class' => 'btn btn-warning btn-swm','target'=>'_blank']) : "" ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>

</div>
<script>
    var value = '<?=(-1 * $model->margin)?>';
</script>
<?php

$this->registerJs("/*$('#s-dm').bootstrapSlider({
        formatter: function(value) {
            //return 'Margin Limit: '   value * -1;
        }
    });$('#s-dl').bootstrapSlider({
        formatter: function(value) {
           // return 'Total SKU Limit: '   value;
        }
    });*/
    $(\"#s-dm\").ionRangeSlider({
    min: -30,
    max: -5,
    from: ".($model->margin)."
});
    ",
    View::POS_END
);
?>