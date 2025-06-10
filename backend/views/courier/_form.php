<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
/* @var $form yii\widgets\ActiveForm */
$courier_type=array_merge([''=>'Select Courier Type'],array_combine(array_keys(Yii::$app->params['courier_config']),array_keys(Yii::$app->params['courier_config'])));
$courier_mode= [''=>'Select Courier Mode', 'development' => 'Development', 'production' => 'Production'];
?>
 <div class="row">
        <div class="col-sm-12">
            <div class="card card-body">
                <?php $form = ActiveForm::begin([
                    'options' => ['enctype' => 'multipart/form-data'],
                ]); ?>

                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'type')->dropDownList(
                                    $courier_type
                                    , ['class' => 'courier_type form-control'])->label('Courier Type *') ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'name')->textInput(['required'=>true,'placeholder'=>'Name'])->label('Name *') ?>
                            </div>

                        </div>


                        <div class="row">

                            <div class="col-md-6">

                              <?= $form->field($model, 'mode')->dropDownList($courier_mode, ['maxlength' => 100]);?>

                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'url')->textInput(['class'=>'form-control','required'=>true,'placeholder'=>'http://www.example.com/api'])->label('Api URL Live*') ?>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'description')->textInput(['class'=>'form-control','placeholder'=>'Small Description / Introduction'])->label('Description') ?>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group field-warehouses-channels">
                                    <label class="control-label" for="warehouses-name">Warehouses *</label>
                                    <select required class="select2 m-b-10 select2-multiple" style="width: 100%" multiple="multiple" data-placeholder="Choose" name="Couriers[warehouses][]">
                                        <?php foreach ($warehouses as $val) {  ?>
                                            <option value="<?=$val['id']?>" <?=( isset($attached_warehouses) && in_array($val['id'],$attached_warehouses)) ? 'selected' : ''?>><?= $val['name']; ?></option>
                                        <?php } ?>
                                    </select>

                                </div>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'icon')->fileInput(['class'=>'form-control','placeholder'=>'Image/ Icon','accept' => 'image/*'])->label('Icon / Image') ?>
                            </div>
                        </div>
                        <div class="config_space"></div>
                        <div class="config_space_mode"></div>
                    </div>
                    <?php if (!Yii::$app->request->isAjax){ ?>
                        <div class="form-group">
                            <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                        </div>
                    <?php } ?>
                </form>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>

<?php
$already_selected=(isset($model->type) && !empty($model->type)) ? $model->type:"new_entry";
$config_fields=json_encode(Yii::$app->params['courier_config']);
$config_values=(isset($model->configuration) && !empty($model->configuration)) ? $model->configuration:"{}";


$config_fields_mode=json_encode(Yii::$app->params['courier_config']);
$config_values_mode=(isset($model->configuration_test) && !empty($model->configuration_test)) ? $model->configuration_test:"{}";
//echo "<pre>";print_r($config_values_mode);exit;
$this->registerJs( <<< EOT_JS_CODE
    var config_values =$config_values;
    var config_fields =$config_fields;
    var already_selected ="$already_selected";
    
    //for courier mode    
    var config_values_mode =$config_values_mode;
    var config_fields_mode =$config_fields_mode;
    
    
    $(document).ready(function(){ 
      //  console.log(config_fields_mode);
        if(already_selected!="new_entry") // if updating warehouse
        {
            create_config_html(config_values);
            create_config_html_mode(config_values_mode);
        }
    });
 
  $('.courier_type').change(function(){
        let selected_option=$(this).val();
        let prepared_columns;
        let prepared_columns_mode;
        if(already_selected=="new_entry") // if not update option
        { 
            prepared_columns=map_field_values('new',config_fields[selected_option]);
            prepared_columns_mode=map_field_values_mode('new',config_fields_mode[selected_option]);
           
        }
        else if(already_selected==selected_option) // show already filled default options
        {
             prepared_columns=config_values;
             prepared_columns_mode=config_values_mode;
        }
        else
        {
            prepared_columns=map_field_values('new',config_fields[selected_option]);
            prepared_columns_mode=map_field_values_mode('new',config_fields_mode[selected_option]);
        }
        
        create_config_html(prepared_columns);
        create_config_html_mode(prepared_columns_mode);
    });

 
 
function map_field_values(type,options)
{

    let result={};
    if(type=="new" && options)
    {
        options.forEach(function (item, index) {
                if(item!='db_api_key' && item!='db_api_user')  // because already these fields present 
                    result[item]="";  
                  
            });
         return result; 
    }
    return options;
}
 
function create_config_html(fields)
{
    empty_config_space()
    if(fields && Object.keys(fields).length)
    {
       
         let html ='<h6><b>Configuration</b></h6>';
         html +='<div class="row" style="background: lightgray;padding:1%">';
        
         let col_start='<div class="col-4 form-group">';
         let col_end='</div>';
           $.each(fields, function(key, value) {
                               
                    //console.log(key, value);
                    html +=col_start;
                            html +='<label class="control-label" for="configuration['+ key +']">'+ key +'</label>';
                            html +='<input required class="form-control" type="text" name="configuration['+ key +']" value="'+value+'">';
                    html +=col_end;
                });
            html +='</div><br/>';
            $('.config_space').html(html);

            
    }
    else
    {
        empty_config_space()
     
    }
    return;
}

function empty_config_space()  
{
    $('.config_space').html("");
    $('.config_space_mode').html("");
}

//for multiple select
$(".select2").select2();

//for courier mode
 
function map_field_values_mode(type,options)
{

    let result={};
    if(type=="new" && options)
    {
        options.forEach(function (item, index) {
                if(item!='db_api_key' && item!='db_api_user')  // because already these fields present 
                    result[item]="";  
                  
            });
         return result; 
    }
    return options;
}
 
function create_config_html_mode(fields)
{
    if(fields && Object.keys(fields).length)
    {
       
         let html ='<h6><b>Configuration for test Mode</b> (Optional)</h6>';
         html +='<div class="row" style="background: lightgray;padding:1%">';
        
         let col_start='<div class="col-4 form-group">';
         let col_end='</div>';
           $.each(fields, function(key, value) {
                    //console.log(key, value);
                    html +=col_start;
                            html +='<label class="control-label" for="configuration['+ key +']">'+ key +'</label>';
                            html +='<input required class="form-control" type="text" name="configuration_test['+ key +']" value="'+value+'">';
                    html +=col_end;
                });
            html +='</div><br/>';
            $('.config_space_mode').html(html);
            
    }
    else
    {   
   
        empty_config_space_mode()
    }
    return;
}







EOT_JS_CODE
);