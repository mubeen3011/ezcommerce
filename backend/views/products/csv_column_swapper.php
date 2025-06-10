<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 4/23/2018
 * Time: 11:57 AM
 */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/*$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => 'Stock List', 'url' => ['stocks/all?pdqs=0']];
$this->params['breadcrumbs'][] = $this->title;*/
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['/products']];
$this->params['breadcrumbs'][] = $title;

?>
    <div class="row">
        <div class="col-lg-12">
            <div class="card" >
                <div class="card-body" >
                    <div class="panel panel-default">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                        <h5><?= $title;?></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!----------------------tab-->
    <div class="card">
        <div class="card-body">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['tab']) && $_GET['tab']=="product_swapper") ? " active":(!isset($_GET['tab']) ? "active":"") ?>" data-toggle="tab" href="#home" role="tab" >
                        <span class="hidden-sm-up"><i class="ti-home"></i></span>
                        <span class="hidden-xs-down">Convert CSV Columns</span></a>
                </li>
                  <li class="nav-item">
                      <a class="nav-link <?= (isset($_GET['tab']) && $_GET['tab']=="category_mapping") ? " active":""; ?>" data-toggle="tab" href="#cat_mapping" role="tab">
                          <span class="hidden-sm-up">
                      <i class="ti-user"></i></span>
                          <span class="hidden-xs-down">Category Mapping</span>
                      </a>
                  </li>
                <li class="nav-item">
                    <a class="nav-link <?= (isset($_GET['tab']) && $_GET['tab']=="csv_downloads") ? " active":""; ?>" data-toggle="tab" href="#csv_downloads" role="tab"><span class="hidden-sm-up">
                      <i class="ti-user"></i></span> <span class="hidden-xs-down">CSV Downloads</span></a>
                </li>

            </ul>
            <!-- Tab panes -->
            <div class="tab-content tabcontent-border ">
                <!-------------first tab----------->
                <div class="tab-pane p-20 <?= (isset($_GET['tab']) && $_GET['tab']=="product_swapper") ? " active":(!isset($_GET['tab']) ? "active":"") ?>" id="home" role="tabpanel">
                    <div class="row">
                        <div class="col-md-5">
                            <p>CSV FILE *</p>
                            <div class="competitive-pricing-form">

                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form  id="form_csv" action="/products/csv-columns-swapper"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" id="csv_input" class="dropify" required >
                                    </div>
                                    <!--<div class="append_valid_images">
                                        <input type="text" class="valid_images_input" >
                                    </div>-->
                                    <div class="form-group">
                                        <?= Html::submitButton('Convert', ['class' => 'btn btn-success btn_submit_csv']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list_csv" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>
                        <!--<div class="col-md-3">
                            <div class="already_exists_list" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>-->

                    </div>
                    <!----sample--->
                    <div class="col-md-12">
                    </div>
                    <!----sample--->
                </div>
                <!-------------------------second tab------------------>
                <div class="tab-pane  p-20 <?= (isset($_GET['tab']) && $_GET['tab']=="category_mapping") ? " active":""; ?>"  role="tabpanel" id="cat_mapping">
                    <div class="row">
                        <div class="col-md-5">
                            <p>Choose CSV File *</p>
                            <div class="competitive-pricing-form">
                                <form  id="form_cat_mapping" action="/products/category-mapping"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit_cm']) ?>
                                    </div>

                                </form>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="already_exists_list" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>

                    </div>
                </div>
                <!-------------------tab-------------------->
                <div class="tab-pane  p-20  <?= (isset($_GET['tab']) && $_GET['tab']=="csv_downloads") ? " active":""; ?>"  role="tabpanel" id="csv_downloads">
                    <table id="myTable" class="table table-bordered ">
                        <thead>
                        <tr>
                            <th>CSV input</th>
                            <th>Status</th>
                            <th>Added at</th>
                            <th>CSV output</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if($downloads):?>
                            <?php foreach ($downloads as $download):?>
                                <tr>
                                    <td>
                                        <a href="/<?= $download['input_csv_location'];?>">
                                        <?= $download['input_csv_name'];?>
                                        </a>
                                    </td>
                                    <td><?= $download['csv_processed'] ? "Completed":"<span class='fa fa-spinner fa-spin'></span>";?></td>
                                    <td><?= $download['csv_added_at'];?></td>
                                    <td>
                                        <?php if($download['csv_output']): ?>
                                            <a href="/<?= $download['csv_output'];?>">Download</a>
                                        <?php endif;?>

                                    </td>
                                    <td>x</td>
                                </tr>
                            <?php endforeach;?>
                        <?php endif;?>
                        </tbody>
                    </table>
                </div>
                <!-------------------tab-------------------->
            </div>
        </div>
    </div>
<?php
$this->registerJs(<<< EOT_JS_CODE

///////////////form cat mapping submit
/////////// form submit
     $('#form_cat_mapping').submit(function(e){
    e.preventDefault();
    var submit_btn=".btn_submit_cm";
    $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
			cache: false,
			processData:false,
			beforeSend: function(){
									$(submit_btn).html("Wait..... <span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);
									 $('.error_list').html("");					
								},
			 success: function(msg){
			   display_notice(msg.status,msg.msg + " Updated Record => " + msg.updated_list);
				if(msg.hasOwnProperty('not_updated_list') && Object.keys(msg.not_updated_list).length)
				{
				    $('.error_list').html("<b>Following categories not updated</b>");	
					msg.not_updated_list.forEach(function(value,index){
					    $('.error_list').append("<li>"+ value +"</li>");	
					});
				}
				    
					$(submit_btn).html("Update");
                    $(submit_btn).removeAttr("disabled");
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					$(submit_btn).html("Update");
                    $(submit_btn).removeAttr("disabled");
					display_notice('failure',errorThrown);
			} 		
		   });
       
    });
/////////// form submit csv
     $('#form_csv').submit(function(e){
    e.preventDefault();
    var submit_btn=".btn_submit_csv";
    $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
			cache: false,
			processData:false,
			beforeSend: function(){
									$(submit_btn).html("Wait..... <span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);
									 $('.error_list').html("");					
								},
			 success: function(msg){
			   display_notice(msg.status,msg.msg + " Updated Record => " + msg.updated_list);
				if(msg.hasOwnProperty('not_updated_list') && Object.keys(msg.not_updated_list).length)
				{
				    $('.error_list_csv').html("<b>Following skus not uploaded</b>");	
					msg.not_updated_list.forEach(function(value,index){
					    $('.error_list_csv').append("<li>"+ value +"</li>");	
					});
				}
				if(msg.status=='success')
				$('#form_csv').trigger("reset");
				    
					$(submit_btn).html("Convert");
                    $(submit_btn).removeAttr("disabled");
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					$(submit_btn).html("Convert");
                    $(submit_btn).removeAttr("disabled");
					display_notice('failure',errorThrown);
			} 		
		   });
       
    });
EOT_JS_CODE
);
?>




