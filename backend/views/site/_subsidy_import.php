<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 10/12/2017
 * Time: 2:02 PM
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

?>
<div class="competitive-pricing-create">
    <h3>Subsidy Import</h3>
    <hr>
    <div class="competitive-pricing-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <input type="file" name="csv" class="form-control" >

        <div class="form-group">
            <?= Html::submitButton('Import', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>


    </div>
</div>
