<?php

use common\models\Channels;
use \common\models\Products;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */
/* @var $form yii\widgets\ActiveForm */

$channel = Channels::find()->where(['is_active'=>'1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');

$sku = Products::find()->orderBy('id')->asArray()->all();
$skuList = ArrayHelper::map($sku, 'id', 'sku');

?>

<div class="competitive-pricing-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'sku_id')->dropDownList($skuList, ['prompt' => 'Select Sku','id'=>'sku_id']) ?>

    <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['prompt' => 'Select Channel','id'=>'channel_id']) ?>

    <?= $form->field($model, 'seller_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'low_price')->textInput(['maxlength' => true]) ?>


    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
