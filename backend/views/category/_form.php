<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Category */
/* @var $form yii\widgets\ActiveForm */
//echo "<pre>";
//print_r($model->parent_id);
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card card-body">

            <div class="category-form">

                <?php $form = ActiveForm::begin(); ?>
                <div class="form-group">
                <label class="control-label" for="category-parent_id">Parent Category</label>
                <select class="form-control" id="category-parent_id" name="Category[parent_id]">
                    <option value="">Select parent cat</option>
                    <?php if(isset($cat_list) && !empty($cat_list)) {
                        foreach($cat_list as $cat){ ?>
                            <option <?= (isset($model->parent_id) && $model->parent_id==$cat['id']) ? "selected":"";?> value="<?= $cat['id'];?>"><?= $cat['name'];?></option>
                            <?php if(isset($cat['children']) && is_array($cat['children']) ){
                                foreach($cat['children'] as $child) { ?>
                                    <option <?= (isset($model->parent_id) && $model->parent_id==$child['id']) ? "selected":"";?> value="<?= $child['id'];?>"> &nbsp;... <?= $child['name'];?></option>
                                <?php }} //child   ?>

                        <?php }}  //parent?>
                </select>
                </div>
                <?= $form->field($model, 'name')->textInput(['maxlength' => true,'required'=>true]) ?>

                <?php // $form->field($model, 'parent_id')->textInput() ?>

                <?= $form->field($model, 'is_active')->dropDownList(['1'=>'YES','0'=>'NO']) ?>


                <div class="form-group">
                    <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>

        </div>
    </div>
</div>

