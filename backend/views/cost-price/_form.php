<?php

use common\models\Category;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\CostPrice */
/* @var $form yii\widgets\ActiveForm */

$category = Category::find()->orderBy('id')->asArray()->all();
$categoryList = ArrayHelper::map($category, 'id', 'name');

?>

<div class="cost-price-form col-md-12">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'sku')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cost')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'rccp_cost')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'fbl')->checkbox()->hint('checked means shipping will cost RM 4.23 for LAZADA') ?>

    <?= $form->field($model, 'sub_category')->dropDownList($categoryList, ['prompt' => 'Select Category','id'=>'category_id']) ?>

    <?= $form->field($model, 'selling_status')->dropDownList(['High'=>'High','Medium'=>'Medium','Low'=>'Low'], ['prompt' => 'Select Selling Status',]) ?>


    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
