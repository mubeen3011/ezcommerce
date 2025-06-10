<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\ResetPasswordForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Reset password';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
    .center {
        display: block;
        margin-left: auto;
        margin-right: auto;
        width: 20%;
        height: 16%;
    }
</style>
<section id="wrapper">
    <div class="login-register" style="background-image:url(http://www.staffkart.com/anxpro/wp-content/uploads/2014/05/slide_7.png);">
        <img src="/monster-admin/assets/images/FA ezcommerce logo-01.png" class="center">
        <div class="login-box card">
            <div class="card-body">
                <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
                <h3 class="box-title m-b-20">Reset Password</h3>
                <h4 style="color: green"><?=Yii::$app->session->getFlash('success');?></h4>
                <?= $form->field($model, 'password')->passwordInput(['autofocus' => true]) ?>
                <?= $form->field($model, 'repeat_password')->passwordInput(['autofocus' => true]) ?>

                <div class="form-group text-center m-t-20">
                    <?= Html::submitButton('Save', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

</section>
