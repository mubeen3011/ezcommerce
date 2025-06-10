<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<div class="competitive-pricing-create">
    <h3>Philips stocks Import</h3>


    <div class="competitive-pricing-form">

        <?php
        $form = ActiveForm::begin(['action' =>['stocks/import-philips-stocks'] ,'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="form-group">
            <input type="file" name="csv" class="">
        </div>

        <div class="form-group">
            <?= Html::submitButton('Import', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>


    </div>
</div>
<hr>
<div class="competitive-pricing-create">
    <h3>Office stocks Import</h3>
    <span class="help-block label label-info">only CSV file format</span>
    <div class="competitive-pricing-form">

        <?php
        $form = ActiveForm::begin(['action' =>['stocks/import-office-stocks'] ,'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="form-group">
            <input type="file" name="csv" class="">
        </div>

        <div class="form-group">
            <?= Html::submitButton('Import', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>


    </div>
</div>