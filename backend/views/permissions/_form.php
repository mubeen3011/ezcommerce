<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;


/* @var $this yii\web\View */
/* @var $model common\models\Permissions */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="permissions-form">
    <br/>
    <br/>
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-6 form-group">
            <?= $form->field($model, 'role_id')->dropDownList($roles) ?>
        </div>
        <div class="col-6 form-group">
            <?= $form->field($model, 'module_id')->textInput() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-3 form-group">
            <?= $form->field($model, 'create')->checkbox(['class' => 'checkbox checkbox-success']) ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'update')->checkbox(['class' => 'form-control']) ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'view')->checkbox(['class' => 'form-control']) ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'delete')->checkbox(['class' => 'form-control']) ?>
        </div>
    </div>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
