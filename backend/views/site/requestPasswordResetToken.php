<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \backend\models\PasswordResetRequestForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Request password reset';
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
                <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form','class'=>'form-horizontal form-material']); ?>
                    <h3 class="box-title m-b-20">Recover Password</h3>
                <?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>

                    <div class="form-group text-center m-t-20">
                        <?= Html::submitButton('Send', ['class' => 'btn btn-primary']) ?>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

</section>