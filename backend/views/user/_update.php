<?php
use common\models\UserRoles;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;



$roles = UserRoles::find()->orderBy('id')->asArray()->all();
$roleList = ArrayHelper::map($roles, 'id', 'name');
?>
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <?=Yii::$app->session->getFlash('success');?>
        </div>
    </div>
</div>
<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>


    <?= $form->field($model, 'username')->textInput(['maxlength' => true,'disabled'=>true]) ?>

    <?= $form->field($model, 'full_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>


    <div class="row">
        <div class="col-6 form-group">
            <?php if(!$model->isNewRecord): ?>
                <?= $form->field($model, 'update_password')->passwordInput(['maxlength' => true]) ?>
            <?php else: ?>
                <?= $form->field($model, 'new_password')->passwordInput(['maxlength' => true]) ?>
            <?php endif; ?>
        </div>
        <div class="col-6 form-group">
            <?= $form->field($model, 'repeat_password')->passwordInput(['maxlength' => true]) ?>
        </div>
    </div>



    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
