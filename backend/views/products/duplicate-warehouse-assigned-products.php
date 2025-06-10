<?php
use yii\web\View;
use yii\helpers\Html;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product', 'url' => ['/products/duplicate-assigned-warehouse-products']];
$this->params['breadcrumbs'][] = 'Product Details';
?>
    <!---css file----->
    <link href="/../css/sales_v1.css" rel="stylesheet">

    <div class="row">
        <div class="col-lg-12">
            <div class="card" >
                <div class="card-body" >
                    <div class="panel panel-default">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                        <h5>Products Sync to warehouse</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span class="listing-page">
    <!--------status options----->
        <?= Yii::$app->controller->renderPartial('products-warehouse-sync-statuses-view', $_params_); ?>

        <div class="card">
 <div class="card-body">
    <!---------filter------------>
    <form role="form" class="form-horizontal" id="product_filter_form">
    <div class="row filters" style="padding-bottom: 2%">

        <div class="col-sm-3">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_name']) ? $_GET['product_name'] :"";?>" name="product_name" class="form-control form-control-sm" placeholder="Product Name">
        </div>
        <div class="col-sm-3">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_sku']) ? $_GET['product_sku'] :"";?>" name="product_sku" class="form-control form-control-sm" placeholder="SKU">
        </div>
        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary btn-block"> Search</button>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <a  title="Clear Filter" class="btn btn-sm btn-danger pull-right" href="/products/duplicate-assigned-warehouse-products" >
            <i class="fa fa-filter "></i>
        </a>&nbsp;
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/products/export-duplicate-assigned-warehouse-products?<?=http_build_query($_GET)?>">
            <i class="fa fa-download"> </i>
        </a> &nbsp;
            <a  class="btn btn-sm btn-secondary pull-right mr-2" >Records
                <i class="fa fa-notes"> : <?= isset($total_records) ? $total_records:"x";?></i>
            </a>

    </div>
    </div>
</form>
     <!---------filter------------>

     <!--------listing------------>
     <?php

     ?>
     <div class="row">
            <div class="col-12">
                <div class="tab-pane active table-responsive" id="home" role="tabpanel">
                    <table id="myTable" class="table table-bordered ">
                        <thead>
                            <th></th>
                            <th>Product Name</th>
                            <th>Sku</th>
                            <th>Warehouse</th>
                            <th>Status</th>
                        </thead>
                        <tbody>
                        <?php
                        //  echo "<pre>";
                        //  print_r($products);exit;
                        if (isset($products) && !empty($products)):
                            $count=1000;
                            foreach($products as $product): ?>
                                <tr>
                                 <td>
                                     <span data-toggle-pk-id='<?= $count; ?>' class="fa fa-plus show_items"></span>
                                 </td>
                                <td><?= $product['product_name'];?></td>
                                <td><?= $product['sku'];?></td>
                                <td>
                                    Multiple assigned

                                </td>
                                <td>Multiple</td>
                            </tr>
                                <!-----------child rows to show multiple warehouses------------->
                                <tr id="detail-row-record-<?= $count; ?>" style="display:none">
                                    <td colspan="5">
                                        <div clas="table-responsive">
                                            <table id="myTable" class="table full-color-table full-muted-table hover-table">
                                            <thead>
                                                <tr>
                                                    <th>Warehouse</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                                <tbody>
                                                <?php foreach ($product['warehouses'] as $index=>$warehouse_name):?>
                                                    <tr>
                                                    <td>
                                                        <b><?= $warehouse_name;?></b>
                                                    </td>
                                                    <td><?= $product['statuses'][$index]; ?></td>
                                                    <td>
                                                        <?php if($product['statuses'][$index]=='pending'):?>
                                                            <a data-toggle="tooltip" id="del_dup_Warehouse" title="Delete" data-pk-id="<?= $product['pk_ids'][$index]; ?>" class="fa fa-trash" style="color:red;cursor:pointer">

                                                            </a>
                                                        <?php endif;?>
                                                    </td>
                                                </tr>
                                                <?php endforeach;?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                <?php $count++; endforeach;
                        endif;?>
                        <tfoot>
                        <tr>
                            <td colspan="4">
                                <!----pagination------>

                                <?=  Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                                <!---------->
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
     </div>

     <!--------listing------------>


</div>
</div>

</span>
    <div id="displayBox" style="display: none;">
        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
    </div>
<?php
$this->registerJs( <<< EOT_JS_CODE
$(function(){
    $('.show_items').on('click',function(){
    
        let pk_id=$(this).attr('data-toggle-pk-id');
        $(this).toggleClass('fa-plus fa-minus')
        $('#detail-row-record-' + pk_id).toggle();
    });
    
    
 
});

 ///warehouse assign
    $(document).on('click','#del_dup_Warehouse',function(){
    var pk_id=$(this).attr('data-pk-id');
    if(confirm('Are You sure') && pk_id)
    {
         $.ajax({
            type: "POST",
            url: '/products/del-duplicate-assign-warehouse',
            data: {pk_id},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				location.reload();
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    return;
});
EOT_JS_CODE
);



