<?php

use common\models\UserRoles;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;



$roles = UserRoles::find()->orderBy('id')->asArray()->all();
$roleList = ArrayHelper::map($roles, 'id', 'name');

?>

<div class="user-form">
    <?php $form = ActiveForm::begin(); ?>


    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

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


    <?= $form->field($model, 'role_id')->dropDownList($roleList, ['prompt' => 'Select User Role','id'=>'role_type','class'=>'form-control dd_user_roles']) ?>

    <span class="warehouse_span"></span>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$warehouses =(isset($warehouses) && !empty($warehouses)) ? json_encode(ArrayHelper::map($warehouses, 'id', 'name')):"{}";
$this->registerJs( <<< EOT_JS_CODE
    let warehouses=$warehouses;
     $(document).ready(function(){ 
      let selected=$('.dd_user_roles').find("option:selected").text()
        if(selected.toLowerCase()=="distributor")
        {
            html=create_html_field(warehouses);
             $('.warehouse_span').html(html);
        }
    });
    
    $('.dd_user_roles').change(function(){
    let selected_option=$(this).find("option:selected").text();
    if(selected_option.toLowerCase()=="distributor")
    {
        
        let html=null;
        if(warehouses && Object.keys(warehouses).length)
        {
          html=create_html_field(warehouses);
          $('.warehouse_span').html(html);
        }
    }
    else
    {
      $('.warehouse_span').html("");
    }
    
    return;
    });
    
   function create_html_field(warehouses)
   {
        let html='<div class="form-group">';
            html +='<label class="control-label" for="warehouse">Assign Warehouse (optional)</label>';
            html +='<select class="form-control" name="warehouse">';
            html +='<option value="0">Select Warehouse</option>'; 
            $.each(warehouses, function(key, value) {
                console.log(key + value);  
                 html +='<option value="'+ key +'">'+ value +'</option>';      
            });
        html +='</select>';
        html +='</div>';
        return html;
   }
EOT_JS_CODE
);
