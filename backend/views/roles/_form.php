<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\UserRoles */
/* @var $form yii\widgets\ActiveForm */

$controllerIds = [
    'pricing' => 'Pricing',
    'sales' => 'Sales',
    'settings' => 'Settings',
    'subsidy' => 'Subsidy',
    'competitive-pricing' => 'Competitive Pricing',
    'cost-price' => 'Pricing Sheet',
    'stocks' => 'Stocks',
    'user' => 'User',
    'deal-maker' => 'Deal Maker',
    'sellers' => 'Sellers',
    'channel-details' => 'Channel Details',
    'site' => 'Calculator'];

$selectedCIds = [];
if($model) {
    $ra = \common\models\RoleAccess::find()->where(['role_id' => $model->id])->all();
    if ($ra) {
        foreach ($ra as $v)
            $selectedCIds[] = $v->controller_id;
    }
    $model->controllerIds = $selectedCIds;
}
?>

<div class="user-roles-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'margin_limit')->textInput(['maxlength' => true]) ?>

    <?php
    echo $form->field($model, 'controllerIds')->widget(Select2::classname(), [
        'data' => $controllerIds,
        'language' => 'en',
        'options' => ['placeholder' => 'Select a access ...'],
        'pluginOptions' => [
            'allowClear' => true,
            'multiple' => true
        ],
    ]);

    ?>

    <?= $form->field($model, 'status')->checkbox() ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

