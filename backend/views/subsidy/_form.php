<?php

use common\models\Channels;
use common\models\CostPrice;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Subsidy */
/* @var $form yii\widgets\ActiveForm */

$channel = Channels::find()->where(['is_active'=>'1'])->orderBy('id')->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');

$sku = CostPrice::find()->orderBy('id')->asArray()->all();
$skuList = ArrayHelper::map($sku, 'id', 'name');
?>

<div class="subsidy-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'sku_id')->dropDownList($skuList, ['prompt' => 'Select Sku','id'=>'sku_id']) ?>

    <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['prompt' => 'Select Channel','id'=>'channel_id']) ?>

    <?= $form->field($model, 'subsidy')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'margins')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'ao_margins')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model,'updated_by')->hiddenInput(['value'=>Yii::$app->user->id])->label(false); ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php

$this->registerJsFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.full.js',
    [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);