<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Shops', 'url' => ['generic']];
$this->params['breadcrumbs'][] = 'Update Shop Details';
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-4 col-sm-12">
                        <h3><?= Html::encode('Update Shop Details') ?></h3>
                    </div>
                    <div class="col-md-4 col-sm-12">

                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div class="user-update">
                    <?= $this->render('_form', [
                            'model' => $model,
                            'attached_warehouses'=>$attached_warehouses
                        ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJs( <<< EOT_JS_CODE
$(document).on('change','.stck_lmt_checkbox',function(){
let pk_id=$(this).attr('data-wc-id');
let  action_value=$(this).is(":checked") ? 1:0;
if(pk_id && confirm('Are You Sure')){
$.ajax({
        type: "POST",
        url: '/channels/stock-upload-limit-toggle',
        data: {pk_id,action_value},
        dataType: 'json',
        beforeSend: function(){
                              display_notice('info','processing....');				
                            },
         success: function(msg){
         
                display_notice(msg.status,msg.msg);
            
         },
        error: function(XMLHttpRequest, textStatus, errorThrown) 
        { 
                $('.onoffswitch-checkbox').prop("disabled", false);
        } 		
       });
} else{
    $(this).prop("checked",!action_value);  //revert checkbox
    }
});

EOT_JS_CODE
);


