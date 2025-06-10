<?php
use yii\web\View;
use yii\helpers\Html;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product', 'url' => ['/products/product-sync-to-warehouse']];
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

        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_name']) ? $_GET['product_name'] :"";?>" name="product_name" class="form-control form-control-sm" placeholder="Product Name">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_sku']) ? $_GET['product_sku'] :"";?>" name="product_sku" class="form-control form-control-sm" placeholder="SKU">
        </div>
        <div class="col-sm-2">
            <select class="form-control form-control-sm" name="parent_or_child">
                <option value="">choose Parent/Child</option>
                <option value="parent" <?= (isset($_GET['parent_or_child']) && $_GET['parent_or_child']=="parent") ? "selected":"";?>>parent</option>
                <option value="child" <?= (isset($_GET['parent_or_child']) && $_GET['parent_or_child']=="child") ? "selected":"";?>>child</option>
            </select>
        </div>
        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary btn-block"> Search</button>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <a  title="Clear Filter" class="btn btn-sm btn-danger pull-right" href="/products/products-not-assigned-to-warehouse" >
            <i class="fa fa-filter "></i>
        </a>&nbsp;
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/products/export-not-assigned-warehouse-products?<?=http_build_query($_GET)?>">
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

                        foreach($products as $product): ?>
                            <tr>
                                <td><?= $product['name'];?></td>
                                <td><?= $product['sku'];?></td>
                                <td>
                                    <select data-id="<?= $product['sku'] ?>"  class="form-control form-control-sm warehouse_dd"  name="warehouse_assign">
                                    <option value="" selected >Assign warehouse</option>
                                        <?php if($warehouses) {
                                            foreach ($warehouses as $warehouse) { //if(!$brand['brand']){continue;} ?>
                                                <option value="<?= $warehouse['id']?>"  ><?= $warehouse['name']?></option>
                                            <?php }} ?>

                                </select>

                                </td>
                                <td>Not Assigned</td>
                            </tr>
                        <?php endforeach;
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
 ///warehouse assign
    $(document).on('change','.warehouse_dd',function(){
    var warehouse_id=$(this).val();
    var sku=$(this).attr('data-id');
    if(confirm('Are You sure') && warehouse_id && sku)
    {
         $.ajax({
            type: "POST",
            url: '/products/assign-warehouse-to-product',
            data: {sku,warehouse_id},
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



