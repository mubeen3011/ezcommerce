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
                        <h5>Update Stock/Price</h5>
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
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#home" role="tab">
                        <span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Stock & Price</span></a>
                </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#barcode_sku_mapping" role="tab"><span class="hidden-sm-up">
                                <i class="ti-user"></i></span> <span class="hidden-xs-down">Map Barcode & SKU</span></a>
                </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#style_sku_mapping" role="tab"><span class="hidden-sm-up">
                                <i class="ti-user"></i></span> <span class="hidden-xs-down">Map Style & SKU</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#image_sku_mapping" role="tab">
                        <span class="hidden-sm-up">
                        <i class="ti-user"></i></span> <span class="hidden-xs-down">Map Images Link & SKU</span>
                    </a>
                </li>
                <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#product_category_sku_mapping" role="tab"><span class="hidden-sm-up">
                                <i class="ti-user"></i></span> <span class="hidden-xs-down">Map Category & SKU</span></a>
                </li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content tabcontent-border">
                <!-------------first tab----------->
                <div class="tab-pane active" id="home" role="tabpanel">
                    <div class="p-20">
                        <h3><?= $title ?></h3>
                        <div class="row">

                            <div class="col-md-4" style="margin-left: 2%">
                                <select class="form-control" id="selection_option">
                                    <option value="select">SELECT OPTION</option>
                                    <option value="update_stock">Update Stock</option>
                                    <?php if ($role!='distributor'){ ?>
                                        <option value="update_price">Update Price</option>
                                    <?php }  ?>

                                </select>
                            </div>
                        </div>
                        <br/>
                        <div class="row update_stock_span" style="display:none">
                        <div class="row">
                            <div class="col-md-4" style="margin-left: 3%">

                                <div class="competitive-pricing-form">

                                    <?php
                                    // $form2 = ActiveForm::begin(['action' =>[$action_general_stock_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                    <form class="ftab-form" action="<?= $action_general_stock_import; ?>"  method="post" enctype='multipart/form-data'>
                                        <div class="form-group">
                                            <select class="form-control" name="by">
                                                <option value="sku">By Sku</option>
                                                <option value="barcode">By Barcode</option>

                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <select class="form-control" name="warehouse_id">
                                                <option value="0">SELECT WAREHOUSE</option>
                                                <?php foreach($warehouses as $warehouse)
                                                { ?>

                                                    <option value="<?= $warehouse['id'];?>"><?= $warehouse['name'];?></option>

                                                <?php }?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="file" name="csv" class="dropify" required >
                                        </div>
                                        <input type="hidden" name="general_stock" />

                                        <div class="form-group">
                                            <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit']) ?>
                                        </div>
                                    </form>
                                    <?php //ActiveForm::end(); ?>


                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="error_list_ftab" style="max-height:150px;height:150px;overflow-y: scroll">
                                </div>
                            </div>
                        </div>
                            <div class="col-md-12">
                                <b>sample</b>
                                <div class="competitive-pricing-form" style="background:#F6F6F6">

                                    <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                        <thead>
                                        <th>sku</th>
                                        <th>stock</th>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>SKU123</td>
                                            <td>24</td>
                                        </tr>
                                        </tbody>
                                    </table>


                                </div>
                            </div>
                        </div>

                        <div class="row update_price_span" style="display:none">
                            <div class="row">
                            <div class="col-md-4" style="margin-left: 3%">
                                <h4>Price Update</h4>
                                <div class="competitive-pricing-form">

                                    <?php
                                    //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                    <form class="ftab-form" action="<?= $action_general_price_import; ?>"  method="post" enctype='multipart/form-data'>
                                        <div class="form-group">
                                            <label>Select which price to update</label>
                                            <select class="form-control" name="update_column">
                                                <option value="rccp">RCCP</option>
                                                <option value="cost">Cost price</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <input type="file" name="csv" class="dropify" required >
                                        </div>
                                        <div class="form-group">
                                            <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit']) ?>
                                        </div>

                                    </form>
                                    <?php //ActiveForm::end(); ?>


                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="error_list_ftab" style="max-height:150px;height:150px;overflow-y: scroll">
                                </div>
                            </div>
                            </div>
                            <div class="col-md-12">
                                <b>sample</b>
                                <div class="competitive-pricing-form" style="background:#F6F6F6">

                                    <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                        <thead>
                                        <th>sku</th>
                                        <th>price</th>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td>SKU123</td>
                                            <td>199.99</td>
                                        </tr>
                                        </tbody>
                                    </table>


                                </div>
                            </div>
                        </div>


                    </div>
                </div>
                <!-------------------------second tab------------------>
                <div class="tab-pane  p-20" id="barcode_sku_mapping" role="tabpanel">

                        <h3>Map Barcodes With SKU</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <p>CSV & XLSX FILE *</p>
                            <div class="competitive-pricing-form">

                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form class="form_sku_mapping" action="/stocks/map-barcode-sku"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit_barcode']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list_barcode" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>

                </div>
                        <!----sample--->
                    <div class="col-md-12">
                        <b>sample</b>
                        <div class="competitive-pricing-form" style="background:#F6F6F6">

                            <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                <thead>
                                <th>sku</th>
                                <th>barcode</th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>SKU123</td>
                                    <td>1100111000</td>
                                </tr>
                                <tr>
                                    <td>SKU124</td>
                                    <td>0110111000</td>
                                </tr>
                                </tbody>
                            </table>


                        </div>
                    </div>
                        <!----sample--->

                </div>

                <!-------------------------third tab------------------>
                <div class="tab-pane  p-20" id="style_sku_mapping" role="tabpanel">

                    <h3>Map Style With SKU</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <p>CSV & XLSX FILE *</p>
                            <div class="competitive-pricing-form">

                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form class="form_sku_mapping" action="/stocks/map-style-sku"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit_style']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list_style" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>

                    </div>
                    <!----sample--->
                    <div class="col-md-12">
                        <b>sample</b>
                        <div class="competitive-pricing-form" style="background:#F6F6F6">

                            <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                <thead>
                                <th>sku</th>
                                <th>style</th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>SKU123</td>
                                    <td>Red</td>
                                </tr>
                                <tr>
                                    <td>SKU124</td>
                                    <td>green</td>
                                </tr>
                                </tbody>
                            </table>


                        </div>
                    </div>
                    <!----sample--->

                </div>
                <!-------------------------4th tab---------------------------->
                <div class="tab-pane  p-20" id="image_sku_mapping" role="tabpanel">

                    <h3>Map images link with SKU</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <p>CSV & XLSX FILE *</p>
                            <div class="competitive-pricing-form">

                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form class="form_sku_mapping" action="/stocks/upload-images-link"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit_image']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


                            </div>
                        </div>
                        <div class="col-md-4" style="padding-left:4%;border-left:2px solid gray">
                            <div>
                                <b>Download Missing images skus</b>
                                <br/>
                                <br/>
                                <form  action="/products/download-missing-images-skus"  method="post">
                                    Select Channel
                                <select class="form-control" name="channel_id">
                                    <?php foreach($channels as $channel):?>
                                        <option value="<?= $channel['id'];?>"><?= $channel['name'];?></option>
                                    <?php endforeach;?>
                                </select>
                                <br/>
                                <br/>
                                <button type="submit" class="btn btn-sm btn-info">
                                    <span class="fa fa-download"> Download</span>
                                </button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list_image" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>

                    </div>
                    <!----sample--->
                    <div class="col-md-12">
                        <b>sample</b>
                        <div class="competitive-pricing-form" style="background:#F6F6F6">

                            <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                <thead>
                                <th>sku</th>
                                <th>image</th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>SKU123</td>
                                    <td>http://google.com/folder/abc.jpg</td>
                                </tr>
                                <tr>
                                    <td>SKU124</td>
                                    <td>http://abc.com/folder/xyz.jpg</td>
                                </tr>
                                </tbody>
                            </table>


                        </div>
                    </div>
                    <!----sample--->

                </div>
                <!-------------------------4th tab end---------------------------->

                <!-------------------------five tab------------------>
                <div class="tab-pane  p-20" id="product_category_sku_mapping" role="tabpanel">

                    <h3>Map Product Categories With SKU (multiple categories also allowed)</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <p>CSV & XLSX FILE *</p>
                            <div class="competitive-pricing-form">
                                <?php
                                //  $form2 = ActiveForm::begin(['action' =>[$action_general_price_import] ,'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <form class="form_sku_mapping" action="/stocks/map-product-category-sku"  method="post" enctype='multipart/form-data'>
                                    <div class="form-group">
                                        <input type="file" name="csv" class="dropify" required >
                                    </div>
                                    <div class="form-group">
                                        <?= Html::submitButton('Update', ['class' => 'btn btn-success btn_submit_cats']) ?>
                                    </div>

                                </form>
                                <?php //ActiveForm::end(); ?>


                            </div>
                        </div>
                        <div class="col-md-4" style="padding-left:4%;border-left:2px solid gray">
                            <div>
                                <b>Download Categories List</b>
                                <br/>
                                <br/>
                                        <a href="/category/download-categries-csv" class="btn btn-sm btn-info fa fa-download" > Download</a>


                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="error_list_cats" style="max-height:150px;height:150px;overflow-y: scroll">
                            </div>
                        </div>

                    </div>
                    <!----sample--->
                    <div class="col-md-12">
                        <b>sample</b>
                        <div class="competitive-pricing-form" style="background:#F6F6F6">

                            <table class="export-csv table-bordered table-hover table tablesaw-swipe tablesaw-sortable">
                                <thead>
                                <th>sku</th>
                                <th>category_id</th>
                                <th>category_name</th>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>SKU123</td>
                                    <td>17,18,19</td>
                                    <td>Nike,Adidas,apparels</td>
                                </tr>
                                <tr>
                                    <td>SKU124</td>
                                    <td>17</td>
                                    <td>Nike</td>
                                </tr>

                                </tbody>
                            </table>


                        </div>
                    </div>
                    <!----sample--->

                </div>
                <!-------------------------5h tab end---------------------------->
            </div>
        </div>
    </div>
        <!----------------------tab-->









<?php
$this->registerJs( <<< EOT_JS_CODE

  $('#selection_option').change(function(){
    let option_selected=$(this).val();
    $('.display_errors').html(""); // if error span has some errors clear it 
    if(option_selected=="update_stock"){
         $("." + option_selected + "_span").css('display','block');
         $(".update_price_span").css('display','none');
    } else if(option_selected=="update_price") {
    $("." + option_selected + "_span").css('display','block');
         $(".update_stock_span").css('display','none');
    } else {
        $(".update_price_span").css('display','none');
        $(".update_stock_span").css('display','none');
        
    }
       
    });
    
    /////////// form ajax submission
     $('.ftab-form').submit(function(e){
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
									$(submit_btn).html("Long list in progress <span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);
									 $('.error_list_ftab').html("");					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg + " Updated SKUS => " + msg.updated_list);
				if(msg.hasOwnProperty('not_updated_list') && Object.keys(msg.not_updated_list).length)
				{
				    $('.error_list_ftab').html("<b>Following list not updated</b>");	
					msg.not_updated_list.forEach(function(value,index){
					    $('.error_list_ftab').append("<li>"+ value +"</li>");	
					});
				}
					$(submit_btn).html("Update");
                    $(submit_btn).removeAttr("disabled");
                    if(msg.status=="success")
				    $('.ftab-form').trigger("reset");
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					$(submit_btn).html("Update");
                    $(submit_btn).removeAttr("disabled");
					display_notice('failure',errorThrown);
			} 		
		   });
       
    });
    /////////// form ajax submission for barcode and sku mapping
     $('.form_sku_mapping').submit(function(e){
    e.preventDefault();
    var submit_btn=".btn_submit_barcode";
    var error_span=".error_list_barcode";
    let action=$(this).attr('action');
    if(action=='/stocks/map-barcode-sku'){
        submit_btn=".btn_submit_barcode";
        error_span=".error_list_barcode";
    }else if(action=='/stocks/map-style-sku'){
    submit_btn=".btn_submit_style";
        error_span=".error_list_style";
    }else if(action=='/stocks/upload-images-link'){
         submit_btn=".btn_submit_image";
         error_span=".error_list_image";
    }
    else if(action=='/stocks/map-product-category-sku'){
        submit_btn=".btn_submit_cats";
        error_span=".error_list_cats";
    }
    //alert(error_span + action);
   // return;
    $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: new FormData(this),
            dataType: 'json',
            contentType: false,
			cache: false,
			processData:false,
			beforeSend: function(){
									$(submit_btn).html("Long list in progress <span class='fa fa-spinner fa-spin'></span>");
									$(submit_btn).attr("disabled", true);
									 $(error_span).html("");					
								},
			 success: function(msg){
			 
			   display_notice(msg.status,msg.msg + " Updated SKUS => " + msg.updated_list);
				if(msg.hasOwnProperty('not_updated_list') && Object.keys(msg.not_updated_list).length)
				{
				
				    $(error_span).html("<b>Following skus not updated</b>");	
					msg.not_updated_list.forEach(function(value,index){
					    $(error_span).append("<li>"+ value +"</li>");	
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

