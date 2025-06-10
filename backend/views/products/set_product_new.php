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
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
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
                        <h5>Set Product new</h5>
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
                        <a class="nav-link active" data-toggle="tab" href="#home" role="tab">
                        <span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Set Product New</span></a>
                </li>
              <!--  <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#barcode_sku_mapping" role="tab"><span class="hidden-sm-up">
                                <i class="ti-user"></i></span> <span class="hidden-xs-down">Map Barcode & SKU</span></a>
                </li>-->

            </ul>
            <!-- Tab panes -->
            <div class="tab-content tabcontent-border ">
                <!-------------first tab----------->
                <div class="tab-pane active p-20" id="home" role="tabpanel">
                    <div class="row">
                        <div class="col-md-5">
                            <p>CSV FILE *</p>
                            <div class="competitive-pricing-form">

                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form  id="form" action="/products/set-product-new-post"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <select class="form-control" name="channel">
                                            <option value="">Select Channel</option>
                                            <?php
                                            if(isset($channels)):
                                                foreach ($channels as $channel): ?>
                                                    <option value="<?= $channel['id'];?>"><?= $channel['name'];?></option>
                                           <?php endforeach;
                                            endif;
                                            ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <select class="form-control" name="action">
                                            <option value="add">Set as New</option>
                                            <option value="remove">Remove as New</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


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
                    <!----sample--->
                    <div class="col-md-12">
                        <b>Sample CSV</b>
                        <span class=""> ( follow name covention,make sure parent sku included )</span>
                        <div class="competitive-pricing-form" style="background:#F6F6F6">

                            <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                <thead>
                                <th>sku</th>
                                <th>set_new_from</th>
                                <th>set_new_to</th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>SKU123</td>
                                    <td>2021-01-02 00:00:00</td>
                                    <td>2021-01-31 00:00:00</td>
                                </tr>
                                <tr>
                                    <td>SKU456</td>
                                    <td>2021-01-02 00:00:00</td>
                                    <td>2021-01-31 00:00:00</td>
                                </tr>
                                </tbody>
                            </table>


                        </div>
                    </div>
                    <!----sample--->
                </div>
                <!-------------------------second tab------------------>
                <!--<div class="tab-pane  p-20"  role="tabpanel">
                </div>-->.
            </div>
        </div>
    </div>

<?php
$this->registerJs( <<< EOT_JS_CODE
/////////// form submit
     $('#form').submit(function(e){
    e.preventDefault();
    var submit_btn=".btn_submit";
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
			   display_notice(msg.status,msg.msg + " Updated SKUS => " + msg.updated_list);
				if(msg.hasOwnProperty('not_updated_list') && Object.keys(msg.not_updated_list).length)
				{
				    $('.error_list').html("<b>Following skus not updated</b>");	
					msg.not_updated_list.forEach(function(value,index){
					    $('.error_list').append("<li>"+ value +"</li>");	
					});
				}
				if(msg.hasOwnProperty('already_exists') && Object.keys(msg.already_exists).length)
				{
				    $('.already_exists_list').html("<b>Following skus with same record already exists</b>");	
					msg.already_exists.forEach(function(value,index){
					    $('.already_exists_list').append("<li>"+ value +"</li>");	
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
EOT_JS_CODE
);

