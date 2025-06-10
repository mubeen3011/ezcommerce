<?php

use common\models\Category;
use common\models\Channels;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\PhilipsCostPrice;
/* @var $this yii\web\View */
/* @var $model common\models\ChannelsDetails */
/* @var $form yii\widgets\ActiveForm */

$channel = Channels::find()->orderBy('id')->Where(['is_active'=>'1'])->Where(['id'=>1])->orWhere(['id'=>2])->orWhere(['id'=>3])->asArray()->all();
$channelList = ArrayHelper::map($channel, 'id', 'name');

$connection = \Yii::$app->db;
$command = $connection->createCommand("SELECT *
FROM philips_cost_price pcp
WHERE pcp.id NOT IN 
(
SELECT sku_id
FROM skus_crawl
)");

$sku = $command->queryAll();

$skuList=ArrayHelper::map($sku,'id','sku');


if( isset($_GET['id']) ){
    $Data = \common\models\SkusCrawl::find()->where(['ID'=>$_GET['id']])->asArray()->all();
    $SkuId=\common\models\PhilipsCostPrice::find()->where(['id'=>$Data[0]['sku_id']])->asArray()->all()[0]['sku'];
}else{
    $SkuId='';
}


?>
<style>
    .control-label{
        padding:0px;
    }

</style>

<div class="channels-details-form">
    <p style="font-weight: bold">Note: If sku is not in list, There is a chance that it was already added in to the crawl system.</p>
    <?php $form = ActiveForm::begin();?>

    <?= $form->field($model, 'channel_id')->dropDownList($channelList, ['prompt' => 'Select Channel','id'=>'category_id','disabled'=>$Update]) ?>


    <?= $form->field($model, 'sku_id')->dropDownList($skuList, ['prompt' => 'Select Sku','id'=>'sku_id','disabled'=>$Update,'class'=>'select2 m-b-10 select2 form-control']) ?>


    <?= $form->field($model, 'product_ids')->textarea(['maxlength' => true,'rows'=>6,'cols'=>6])->label('Product Ids ( Seperate by ? )') ?>

    <div class="form-group">

        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$this->registerJs("$(\".select2\").select2();");