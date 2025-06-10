<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Permissions';
$this->params['breadcrumbs'][] = $this->title;
?>
    <style>

        tr {
            line-height: 2px !important;
            min-height: 2px !important;
            height: 2px !important;
        }
    </style>
<div class="permissions-index">

    <p>
        <?php // Html::a('Create Permissions', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <div class="card">
        <div class="card-body">
            <!-- Nav tabs -->
            <div class="vtabs customvtab">
                <ul class="nav nav-tabs tabs-vertical" role="tablist">
                    <?php foreach ($roles as $role) { ?>
                    <li class="nav-item ">
                        <a class="nav-link role_tab" data-role-id="<?= $role['id'];?>" data-toggle="tab" href="#<?= $role['id'] ==1 ? 'sel-tab-'.$role['id']:'sel-tab-others';?>" role="tab">
                            <span class="hidden-sm-up"><i class="ti-home"></i></span>
                            <span class="hidden-xs-down"><?= $role['name'];?></span>
                        </a>
                    </li>
                   <?php } ?>
                </ul>
                <!-- Tab panes -->
                <div class="tab-content" style="padding: unset" >
                    <div class="tab-pane active" id="sel-tab-1" role="tabpanel">
                        <div class="p-20">ALL PERMISSIONS GIVEN TO SUPER ADMIN </div>
                    </div>
                    <div class="tab-pane" id="sel-tab-others" role="tabpanel">
                        <div class="p-20">

                                <div class="table-responsive" >
                                <table class="table">
                                <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>View</th>
                                    <th>Create</th>
                                    <th>Update</th>
                                    <th>Delete</th>
                                </tr>
                                </thead>
                                <tbody>

                            <?php if(isset($modules) && !empty($modules)) :
                                    foreach ($modules as $module) {
                                ?>
                                <tr>
                                    <td>
                                        <a href="javascript:void(0)">
                                        <?= $module['parent_id'] ? "&nbsp;&nbsp;&nbsp;----> " . $module['name']:"<b style='color: #E67E22'>".strtoupper($module['name'])."</b>";?>

                                        </a>
                                    </td>
                                    <td><input class="ind-checkbox" module-att="<?=$module['id'];?>"  type="checkbox" id="view-<?=$module['id'];?>"  name="view"></td>
                                    <td><input class="ind-checkbox" module-att="<?=$module['id'];?>" type="checkbox" id="create-<?=$module['id'];?>"  name="create"></td>
                                    <td><input class="ind-checkbox" module-att="<?=$module['id'];?>" type="checkbox"  id="update-<?=$module['id'];?>" name="update"></td>
                                    <td><input class="ind-checkbox"  module-att="<?=$module['id'];?>" type="checkbox" id="delete-<?=$module['id'];?>"  name="delete"></td>
                                 </tr>
                                  <?php
                                      if(isset($module['children']) && is_array($module['children'])) {  //second level
                                          foreach($module['children'] as $child) { //second level for ?>

                                              <tr>
                                                  <td>
                                                      <a href="javascript:void(0)">
                                                          <?= $child['parent_id'] ? "&nbsp;&nbsp;&nbsp;----> " . $child['name']:"<b style='color: #E67E22'>".strtoupper($child['name'])."</b>";?>

                                                      </a>
                                                  </td>
                                                  <td><input class="ind-checkbox" module-att="<?=$child['id'];?>"  type="checkbox" id="view-<?=$child['id'];?>"  name="view"></td>
                                                  <td><input class="ind-checkbox" module-att="<?=$child['id'];?>" type="checkbox" id="create-<?=$child['id'];?>"  name="create"></td>
                                                  <td><input class="ind-checkbox" module-att="<?=$child['id'];?>" type="checkbox"  id="update-<?=$child['id'];?>" name="update"></td>
                                                  <td><input class="ind-checkbox"  module-att="<?=$child['id'];?>" type="checkbox" id="delete-<?=$child['id'];?>"  name="delete"></td>
                                              </tr>

                                    <?php   //3rd level
                                          if(isset($child['children']) && is_array($child['children'])) {  //3rd level if
                                              foreach($child['children'] as $child_third) { //3rd level for ?>
                                                  <tr>
                                                      <td>
                                                          <a href="javascript:void(0)">
                                                              <?= $child_third['parent_id'] ? "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----> " . $child_third['name']:"<b style='color: #E67E22'>".strtoupper($child_third['name'])."</b>";?>

                                                          </a>
                                                      </td>
                                                      <td><input class="ind-checkbox" module-att="<?=$child_third['id'];?>"  type="checkbox" id="view-<?=$child_third['id'];?>"  name="view"></td>
                                                      <td><input class="ind-checkbox" module-att="<?=$child_third['id'];?>" type="checkbox" id="create-<?=$child_third['id'];?>"  name="create"></td>
                                                      <td><input class="ind-checkbox" module-att="<?=$child_third['id'];?>" type="checkbox"  id="update-<?=$child_third['id'];?>" name="update"></td>
                                                      <td><input class="ind-checkbox"  module-att="<?=$child_third['id'];?>" type="checkbox" id="delete-<?=$child_third['id'];?>"  name="delete"></td>
                                                  </tr>
                                <?php          }  } // 3rd levelend

                                      } } //second level end ?>
                            <?php } endif; // first level end ?>
                                </tbody>
                                </table>
                                </div>


                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?PHP //print_r( $dataProvider); die();?>
    <?php /*GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
           // ['class' => 'yii\grid\SerialColumn'],

           // 'id',
            [
                'attribute'=>'role_id',
                'value'=>function($model){
                    return $model->role->name;
                }
            ],
            [
                'attribute'=>'module_id',
                'value'=> function($model){
                                $parent_module=\common\models\Modules::find()->where(['id'=>$model->module->parent_id])->one();
                                $parent_name=isset($parent_module->name) ? $parent_module->name." -> ":"";
                                return $parent_name . $model->module->name;
                        }
            ],

            [
                'attribute'=>'create',
                'format' => 'raw',
                'value'=>function($model){
                 return '<input class="cb" type="checkbox" checked onclick="me('.json_encode($model).')">';
                    return  $model->create ? '<span class="fa fa-check"></span>':'<span class="fa fa-times"></span>';
                }
            ],
            [
                'attribute'=>'update',
                'format' => 'raw',
                'value'=>function($model){
                    return  $model->update ? '<span class="fa fa-check"></span>':'<span class="fa fa-times"></span>';
                }
            ],
            [
                'attribute'=>'view',
                'format' => 'raw',
                'value'=>function($model){
                    return  $model->view ? '<span class="fa fa-check"></span>':'<span class="fa fa-times"></span>';
                }
            ],
            [
                'attribute'=>'delete',
                'format' => 'raw',
                'value'=>function($model){
                    return  $model->delete ? '<span class="fa fa-check"></span>':'<span class="fa fa-times"></span>';
                }
            ],
            //'added_at',
            //'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]);*/ ?>
</div>
<?php
$this->registerJs( <<< EOT_JS_CODE
var current_checked_role;
$('.role_tab').click(function(){
    let role_id=$(this).attr('data-role-id');
    if(role_id)
    {
        current_checked_role=role_id;
        $.ajax({
            type: "POST",
            url: '/permissions/get-role-permissions',
            data: {'role_id':role_id},
            dataType: 'json',
			beforeSend: function(){
			                        uncheck_checkboxes(); // first uncheck all boxes
			                         $('input[type=checkbox]').attr("disabled", true);
									//display_notice('info','....');					
								},
			 success: function(msg){
				    if(msg.status=="success")
				    {
				       // display_notice('success','found');
				       iziToast.destroy();	
				        populate_permissions(msg.list);
				    }
				    else
				    {
				        display_notice('failure',msg.msg);
				    }
				$('input[type=checkbox]').removeAttr("disabled");
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
				//alert( errorThrown);
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    return;
});

function populate_permissions(list)
{
   // console.log(list);
    
     $.each(list, function(key, value) {
                     
                    $('#view-' + value['module_id']).prop( "checked", parseInt(value['view']) );
                    $('#create-' + value['module_id']).prop( "checked",parseInt( value['create']));
                    $('#update-' + value['module_id']).prop( "checked",parseInt( value['update']));
                    $('#delete-' + value['module_id']).prop( "checked", parseInt(value['delete']));
                   
                });
    
}
 function uncheck_checkboxes()
 {
    $('input[type=checkbox]').prop( "checked", false );
 }
 
 /// if any of crud option clicked
 $('.ind-checkbox').click(function(){
    let action_name=$(this).attr('name'); 
    let module_id=$(this).attr('module-att'); 
    let action_value=$(this).is(":checked") ? 1:0;
    if(!confirm('Are You sure'))
    {
        $(this).prop("checked",!action_value);  //revert checkbox
        return;
    }
   // alert(action_value);
    if(action_name && module_id && current_checked_role)
    {
         $.ajax({
            type: "POST",
            url: '/permissions/update-role-permissions',
            data: {'module_id':module_id,'action_name':action_name,'action_value':action_value,'role_id':current_checked_role},
            dataType: 'json',
			beforeSend: function(){
			                        
			                         $('input[type=checkbox]').attr("disabled", true);
									 display_notice('info','....');					
								},
			 success: function(msg){
				    display_notice(msg.status,msg.msg);
				    $('input[type=checkbox]').removeAttr("disabled");
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
				
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    else
    {
        display_notice('failure','failed to update');
    }
 });
 
EOT_JS_CODE
);
