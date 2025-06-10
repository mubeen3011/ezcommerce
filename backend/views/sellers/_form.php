<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Sellers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="sellers-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'channel_id')->hiddenInput(['maxlength' => true,'value'=>'1'])->label(false) ?>

    <?= $form->field($model, 'seller_name')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
