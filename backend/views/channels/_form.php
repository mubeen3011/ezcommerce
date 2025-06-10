<?php
use kartik\file\FileInput;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Channels */
/* @var $form yii\widgets\ActiveForm */
$marketplaces=array_merge([''=>'Select Market Place'],array_combine(array_keys(Yii::$app->params['marketplace_config']),array_keys(Yii::$app->params['marketplace_config'])));
$warehouses = \backend\util\HelpUtil::ChannelsWarehouses();
?>
<style>
    .checkbox-margin{
        margin-top: 25px;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <div class="card card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs customtab2" role="tablist">
                <li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#home" role="tab" aria-selected="true"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Basic Info</span></a> </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#warehouse_tab" role="tab" aria-selected="false"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Assigned Warehouses</span></a> </li>
            </ul>
            <br/>
            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane active show" id="home" role="tabpanel">
                    <!----------------------->
                    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
                    <form class="form-horizontal m-t-40">
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <?= $form->field($model, 'marketplace')->dropDownList(
                                        $marketplaces
                                        , ['class' => 'marketplace_type form-control'])->label('MarketPlace *')  ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'name')->textInput(['maxlength' => true,'required'=>true])->label('Shop Name *'); ?>
                                </div>



                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <?= $form->field($model, 'prefix')->textInput(['required'=>true,'placeholder'=>'AMZ-SHP'])->label('Prefix *') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'default_time_zone')->textInput(['class'=>'form-control','placeholder'=>'Asia/Kuala_Lumpur'])->label('Default Time Zone') ?>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <?= $form->field($model, 'api_user')->textInput(['class'=>'form-control','id'=>'api_user_input'])->label('Api User / Name') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'api_key')->textInput(['class'=>'form-control','id'=>'api_secret_input'])->label('Api secret / Password') ?>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-md-6">
                                    <?= $form->field($model, 'api_url')->textInput(['class'=>'form-control','required'=>true,'placeholder'=>'http://www.example.com/api'])->label('Api URL *') ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'api_version')->dropDownList(
                                        [''=>'default','mws'=>'mws - (amazon)','spa'=>'spa - (amazon)']
                                        , ['class' => 'form-control'])->label('Api version')  ?>
                                </div>

                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <?= $form->field($model, 'default_warehouse')->dropDownList(
                                        $warehouses
                                        , ['class' => 'default_warehouse_type form-control'])->label('Default Warehouse *')  ?>
                                </div>
                                <div class="col-md-6">
                                    <?= $form->field($model, 'stock_update_percent')->textInput(['class'=>'form-control','type'=>'number', 'min'=>'1','max'=>'100','placeholder'=>'Enter percentage'])->label('Stock update Limit %') ?>
                                </div>


                            </div>
                            <div class="config_space"></div>



                            <div class="row">
                                <div class="col-md-3">
                                    <?=$form->field($model, 'is_active')->checkbox(['class'=>'checkbox-margin'])?>

                                </div>

                                <div class="col-md-3" style="float: left;">
                                    <?= $form->field($model, 'is_sync_stocks')->checkbox(['class'=>'checkbox-margin']) ?>
                                </div>
                                <div class="col-md-3" style="float: left;">
                                    <?= $form->field($model, 'is_sync_prices')->checkbox(['class'=>'checkbox-margin']) ?>
                                </div>
                                <div class="col-md-3" style="float: left;">
                                    <?= $form->field($model, 'is_fetch_sales')->checkbox(['class'=>'checkbox-margin']) ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <?php if($model->logo):?>
                                    <img style="width:150px;height:100px" src="/<?=$model->logo?>">
                                    <?php endif;?>
                                    <?=$form->field($model, 'logo')->fileInput()?>
                                </div>
                            </div>
                        </div>
                        <?php if (!Yii::$app->request->isAjax){ ?>
                            <div class="form-group">
                                <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                            </div>
                        <?php } ?>
                    </form>
                    <?php ActiveForm::end(); ?>
                    <!----------------------->
                </div>
                <div class="tab-pane" id="warehouse_tab" role="tabpanel">
                    <!------------------------>
                    <div class="table-responsive" >
                        <table class="table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Is Attached</th>
                                <th>Stock Upload Limit applies</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php if(isset($attached_warehouses) && $attached_warehouses):
                                        foreach($attached_warehouses as $wh): ?>
                                        <tr>
                                            <td><?= $wh['warehouse']['id'];?></td>
                                            <td><?= $wh['warehouse']['name'];?></td>
                                            <td><?= $wh['is_active'] ? "Yes":"No";?></td>
                                            <td><input class="stck_lmt_checkbox" data-wc-id="<?= $wh['id'];?>" <?= $wh['stock_upload_limit_applies'] ? "checked":"";?> type="checkbox"/></td>
                                        </tr>
                                <?php endforeach;
                                endif;?>
                            </tbody>
                        </table>
                    </div>
                    <!------------------------>
                </div> <!------------------attached warehouse tab end------------------->
            </div><!--------------tab content----------->

        </div> <!--------------card body---------->
    </div>
</div><!----------------main  row------------------>
<?php
$already_selected=(isset($model->marketplace) && !empty($model->marketplace)) ? $model->marketplace:"new_entry";
$config_fields=json_encode(Yii::$app->params['marketplace_config']);
$config_values=(isset($model->auth_params) && !empty($model->auth_params)) ? $model->auth_params:"{}";
$this->registerJs( <<< EOT_JS_CODE
    var config_values =$config_values;
    var config_fields =$config_fields;
    var already_selected ="$already_selected";
    $(document).ready(function(){ 
        if(already_selected!="new_entry") // if updating warehouse
        {
            create_config_html(config_values);
        }
    });
 
  $('.marketplace_type').change(function(){
        let selected_option=$(this).val();
        check_user_and_key_fields(config_fields[selected_option]); //check if key and pass field required
        let prepared_columns;
        if(already_selected=="new_entry") // if not update option
        {
            prepared_columns=map_field_values('new',config_fields[selected_option]);
        }
        else if(already_selected==selected_option) // show already filled default options
        {
             prepared_columns=config_values;
        }
        else
        {
            prepared_columns=map_field_values('new',config_fields[selected_option]);
        }
        create_config_html(prepared_columns);
    });

 function check_user_and_key_fields(options) // to check if api user and api secret keys required or not 
 {
    if(!options)
    return;
    
    if (options.includes('db_api_user')) {
        $("#api_user_input").prop('required',true);
        $("label[for = api_user_input]").text("Api User / Name *");           
    } else { 
         $("#api_user_input").prop('required',false);
         $("label[for = api_user_input]").text("Api User / Name");
    }
    
    /****api_secret or password key*****/
     if (options.includes('db_api_key')) {
        $("#api_secret_input").prop('required',true);
        $("label[for = api_secret_input]").text("Api secret / Password *");           
    } else { 
         $("#api_secret_input").prop('required',false);
         $("label[for = api_secret_input]").text("Api secret / Password");
    }
 }
 
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
    if(fields && Object.keys(fields).length)
    {
       
         let html ='<h6>Configuration</h6>';
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
}
EOT_JS_CODE
);