<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

$this->title = 'Login';
/*$this->params['breadcrumbs'][] = $this->title;*/
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
    <div class="login-register" style="background-image:url('//www.staffkart.com/anxpro/wp-content/uploads/2014/05/slide_7.png');">
        <img src="/monster-admin/assets/images/FA ezcommerce logo-01.png" class="center">

        <div class="login-box card">
            <div class="card-body">
                <!--<form class="form-horizontal form-material" id="loginform" action="index.html">-->
                <?php $form = ActiveForm::begin(['id' => 'login-form','options'=>['class' => 'form-horizontal form-material']]); ?>
                <h3 class="box-title m-b-20 text-center">Sign In</h3>
                <div class="form-group">
                    <div class="col-xs-12">
                        <!--<input class="form-control" type="text" required="" placeholder="Username">-->
                        <?= $form->field($model, 'username')->textInput(['autofocus' => true,'class'=>'form-control'
                        ]) ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-xs-12">
                        <!--<input class="form-control" type="password" required="" placeholder="Password">-->
                        <?= $form->field($model, 'password')->passwordInput(['class'=>'form-control']) ?>
                    </div>
                    <div class="col-md-12">
                        <a href="/site/request-password-reset" class="pull-right">Forgot Password</a>
                    </div>
                </div>

                <!-- <div class="form-group">
                        <div class="col-md-12">
                                <?/*= $form->field($model, 'rememberMe')->checkbox() */?>

                        </div>
                    </div>-->

                <div class="form-group text-center m-t-20">
                    <div class="col-xs-12">
                        <!--<button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light" type="submit">Log In</button>-->
                        <?= Html::submitButton('Login', ['class' => 'btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light', 'name' => 'login-button']) ?>
                    </div>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

</section>