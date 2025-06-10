<?php

use common\models\Category;
use common\models\Channels;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\ChannelsDetails */
/* @var $form yii\widgets\ActiveForm */
if(isset($model)):
$channel = Channels::findone(['id'=>$model->channel_id,'is_active' => '1']);
//$channelList = ArrayHelper::map($channel, 'id', 'name');

//$category = Category::find()->where(['is_main'=>'1'])->orderBy('id')->asArray()->all();
$category = Category::findone(['id'=>$model->category_id]);
endif;
//$categoryList = ArrayHelper::map($category, 'id', 'name');
?>

<div class="channels-details-form">
    <?php
    if ($type=='update'){
        ?>
        <div class="row">
            <div class="col-md-12 table-responsive ">
                <table class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                    <tbody>
                    <tr>
                        <td>Channel Name: <?= isset($channel->name) ? $channel->name :"Not Defined";?></td>
                        <td>Cat Name: <?= isset($category->name) ? $category->name :"Not Defined";?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    }
    ?>


    <?php $form = ActiveForm::begin(); ?>

    <!-- //$form->field($model, 'channel_id')->dropDownList($channelList, ['prompt' => 'Select Channel','id'=>'category_id']) ?> -->
    <?php
    if ($type=='create'){
        ?>
        <?=$form->field($model, 'channel_id')->dropDownList($channels, ['prompt' => 'Select Channel','id'=>'channel_id']) ?>
        <?=$form->field($model, 'category_id')->dropDownList([], ['prompt' => 'Select Category','id'=>'category_id']) ?>
    <?php
    }
    else if ( $type=='update' ){
        ?>
        <?= $form->field($model, 'channel_id')->hiddenInput(['value'=>$model->channel_id])->label(false); ?>
        <?= $form->field($model, 'category_id')->hiddenInput(['value'=>$model->category_id])->label(false); ?>
    <?php
    }
    ?>



    <?= $form->field($model, 'commission')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pg_commission')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'shipping')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model,'updated_by')->hiddenInput(['value'=>Yii::$app->user->id])->label(false); ?>

    <div class="form-group">

        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
