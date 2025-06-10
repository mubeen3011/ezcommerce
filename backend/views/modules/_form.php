<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

//$visibility=\yii\helpers\ArrayHelper::map($ok, 'id', 'value');

/* @var $this yii\web\View */
/* @var $model common\models\Modules */
/* @var $form yii\widgets\ActiveForm */

$main_options=$default_option="<option value=''>  --- Select Option ---</option>";
if(isset($controllers_list) && $controllers_list)
{
    $count=1;
    foreach ($controllers_list as $controller=>$values):
        $main_options .='<option value="'.$controller.'">'.$controller.'</option>';
        if(is_array($values) && $count==1)
        {
            foreach ($values as $key=>$val):
                $default_option .='<option value="'.$val.'">'.$val.'</option>';
            endforeach;
        }
        $count++;
    endforeach;

}

?>
<br/>
<div class="modules-form">

    <?php $form = ActiveForm::begin(['id' =>'module_create_form', 'validateOnSubmit' => false]); ?>
    <div class="row">
        <div class="col-4 form-group">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true,'required'=>true]) ?>
        </div>
        <div class="col-4 form-group">
            <label class="control-label" for="modules-parent_id">Parent Module</label>
            <select class="form-control" id="modules-parent_id" name="Modules[parent_id]">
                <option value="">Select parent cat</option>
                <?php if(isset($modules) && !empty($modules)) {
                    foreach($modules as $module){ ?>
                        <option value="<?= $module['id'];?>"><?= $module['name'];?></option>
               <?php if(isset($module['children']) && is_array($module['children']) ){
                    foreach($module['children'] as $child) { ?>
                        <option value="<?= $child['id'];?>"> &nbsp;-><?= $child['name'];?></option>
                   <?php }} //child   ?>

                <?php }}  //parent?>
            </select>
            <?php // $form->field($model, 'parent_id')->textInput() ?>
        </div>
        <div class="col-4 form-group">
            <label class="control-label" for="modules-controller_id">Controller Name</label>
             <select class="form-control controller_dd" id="modules-controller_id" name="Modules[controller_id]">
                 <?= $main_options; ?>
             </select>
            <?php // $form->field($model, 'controller_id')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-3 form-group">
            <label class="control-label" for="modules-view_id">View Method</label>
            <select class="form-control crud_dd_options" id="modules-view_id" name="Modules[view_id]">
                <?= $default_option; ?>
            </select>
            <?php // $form->field($model, 'view_id')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-update_id">Update Method</label>
            <select class="form-control crud_dd_options" id="modules-update_id" name="Modules[update_id]">
                <?= $default_option; ?>
            </select>
            <?php // $form->field($model, 'update_id')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-create_id">Create Method</label>
            <select class="form-control crud_dd_options" id="modules-create_id" name="Modules[create_id]">
                <?= $default_option; ?>
            </select>
            <?php // $form->field($model, 'create_id')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-delete_id">Delete Method</label>
            <select class="form-control crud_dd_options" id="modules-delete_id" name="Modules[delete_id]">
                <?= $default_option; ?>
            </select>
            <?php //$form->field($model, 'delete_id')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-3 form-group">
            <label class="control-label" for="modules-allowed_view_ids">Allowed View urls</label>
            <textarea id="modules-allowed_view_ids" name="Modules[allowed_view_ids]" class="form-control" placeholder="controller/function , controller2/function2"></textarea>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-allowed_update_id">Allowed update urls</label>
            <textarea id="modules-allowed_update_ids" name="Modules[allowed_update_ids]" class="form-control" placeholder="controller/function , controller2/function2"></textarea>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-allowed_create_id">Allowed create urls</label>
            <textarea id="modules-allowed_create_ids" name="Modules[allowed_create_ids]" class="form-control" placeholder="controller/function , controller2/function2"></textarea>
        </div>
        <div class="col-3 form-group">
            <label class="control-label" for="modules-allowed_delete_id">Allowed delete urls</label>
            <textarea id="modules-allowed_delete_ids" name="Modules[allowed_delete_ids]" class="form-control" placeholder="controller/function , controller2/function2"></textarea>
        </div>
    </div>

    <div class="row">

        <div class="col-3 form-group">
            <?= $form->field($model, 'params')->textinput(['placeholder'=>'?name=abc&page=2']) ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'menu_position')->dropDownList(array('sidebar'=>'sidebar','header'=>'header')); ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'visibility')->dropDownList(array(0=>'NO',1=>'Yes')); ?>
        </div>
        <div class="col-3 form-group">
            <?= $form->field($model, 'icon')->textInput(['maxlength' => true,'placeholder'=>'mdi mdi-tag  or fa fa-home']) ?>
        </div>
    </div>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success btn_submit']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
<?php
$controller_list=(isset($controllers_list) && !empty($controllers_list)) ? json_encode($controllers_list):"{}";
$this->registerJs( <<< EOT_JS_CODE
    var controller_list =$controller_list;
     $('.controller_dd').on('change',function(){
     let html='<option value=""> --- Select Option ---</option>';
     let selected_controller=$(this).val();  
     let options=controller_list[selected_controller];
     if(options)
     {
        options.forEach(function (item, index) {
                html +='<option value="' + item + '"> '+ item +' </option>';
            });
           
     }
      populate_crud_options(html);
   });

function populate_crud_options(html)
{
    if(html) {
        $('.crud_dd_options').empty().append(html);
    } else {
        display_notice('failure','Failed to update options');
    }
}

// form submission
$('#module_create_form').on('submit',function(e){
    e.preventDefault(); 
    var submit_btn=".btn_submit";
    $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
			beforeSend: function(){
									$(submit_btn).html("<span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);					
								},
			 success: function(msg){
				if(msg.status=="success") {
					location.reload();
				} else {
					display_notice('failure',msg.msg);
				}
					$(submit_btn).html("Save");
                    $(submit_btn).removeAttr("disabled");
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					$(submit_btn).html("Save");
                    $(submit_btn).removeAttr("disabled");
					display_notice('failure',errorThrown);
			} 		
		   });
});


EOT_JS_CODE
);
