<?php


use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\daterange\DateRangePicker;

/* @var $this yii\web\View */
/* @var $model common\models\CompetitivePricing */
/* @var $form yii\widgets\ActiveForm */
$this->title = 'Import Competitive Pricing';
$this->params['breadcrumbs'][] = ['label' => 'Competitive Pricings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="competitive-pricing-create">

    <h1><?= Html::encode($this->title) ?> </h1>

    <hr>
    <div class="competitive-pricing-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

        <?= $form->field($cp, 'insert_date')->widget(DateRangePicker::classname(), [
            'options' => ['placeholder' => 'Enter CSV record date'],
            'pluginOptions' => [
                'autoclose'=>true,
                'singleDatePicker' => true,
                'showDropdowns' => true,
            ]
        ]);
        ?>


        <?php
        // With model & without ActiveForm
        echo $form->field($cp, 'csv')->widget(FileInput::classname(), [
            'options' => ['accept' => 'text/csv'],
        ]);

        ?>

        <div class="form-group">
            <?= Html::submitButton('Import', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>


    </div>
</div>