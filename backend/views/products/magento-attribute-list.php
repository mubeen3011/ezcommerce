<?php
use yii\web\View;
$this->title = '';
//$this->params['breadcrumbs'][] = ['label' => 'Sales target', 'url' => ['/sales/targets']];
$this->params['breadcrumbs'][] = ['label' => '|Product Magento Attribute List', 'url' => ['/products/products/product-magento-attribute-lists']];

$status_badge=['High'=>'success','Slow'=>'warning','Medium'=>'info','Not Moving'=>'danger'];
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Product Magento Attribute List</h3>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .statics
    {
        background: #F2F7F8;
    }
    .header-filter-inputs
    {
        display:none;
        font-size:12px;
    }
    .table td.static,
    .table th.static {
        white-space: nowrap;
        width: 1%;
    }
</style>
<!------------------------->
<div class="row">
    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <?php if(count($_GET) > 1): ?>
            <a data-toggle="tooltip" title="Clear filter" href="/products/product-magento-attribute-lists" type="button" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"> </i>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
            <i class="fa fa-filter"></i>
        </button>
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/products/export-magento-csv?<?=http_build_query($_GET)?>">
            <i class="fa fa-download"> </i>
        </a>
        <a class="btn btn-sm btn-secondary pull-right mr-2">Records
            <i class="fa fa-notes"> : <?= isset($total_records) ? $total_records:"x"?></i>
        </a>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <!-- Column -->
        <div class="card">


            <div class="card-body table-responsive">

                <!-----------record--------->
                <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                    <form  method="GET">
                        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 header-filter-inputs" style="padding-left:0px">
                            <?php if(isset($channels)):?>
                                <select class="form-control-sm" name="channel" onchange="this.form.submit()">
                                    <option value="">All shops</option>
                                    <?php foreach($channels as $chan){ ?>
                                        <option <?= (isset($_GET['channel']) && $_GET['channel']==$chan['id']) ? "selected":"";?> value="<?= $chan['id'];?>"><?= strtoupper($chan['name']);?></option>
                                    <?php } ?>
                                </select>
                            <?php endif;?>
                        </div>
                        <thead>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist" class="static">Category
                            <div id="ci-search-category">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['category']) ? $_GET['category']:"";?>" style="width: 100%;height:25px" name="category" value="<?= isset($_GET['category']) ? $_GET['category']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Brand
                            <div id="ci-search-brand">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['brand']) ? $_GET['brand']:"";?>" style="width: 100%;height:25px" name="brand" value="<?= isset($_GET['brand']) ? $_GET['brand']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Color
                            <div id="ci-search-color">
                                <input type="text"  data-toggle="tooltip" title="<?= isset($_GET['color']) ? $_GET['color']:"";?>" style="width: 100%;height:25px" name="color" value="<?= isset($_GET['color']) ? $_GET['color']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Size
                            <div id="ci-search-size">
                                <input type="text"  data-toggle="tooltip" title="<?= isset($_GET['size']) ? $_GET['size']:"";?>" style="width: 100%;height:25px" name="size" value="<?= isset($_GET['size']) ? $_GET['size']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Name
                            <div id="ci-search-name">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['name']) ? $_GET['name']:"";?>" style="width: 100%;height:25px" name="name" value="<?= isset($_GET['name']) ? $_GET['name']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Description
                            <div id="ci-search-description">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['description']) ? $_GET['description']:"";?>" style="width: 100%;height:25px" name="description" value="<?= isset($_GET['description']) ? $_GET['description']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Price
                            <div id="ci-search-price">
                                <input type="number" step="0.01" data-toggle="tooltip" title="<?= isset($_GET['price']) ? $_GET['price']:"";?>" style="width: 100%;height:25px" name="price" value="<?= isset($_GET['price']) ? $_GET['price']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Sku
                            <div id="ci-search-sku">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" style="width: 100%;height:25px" name="sku" value="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Parent Sku
                            <div id="ci-search-parentsku">
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['parent_sku']) ? $_GET['parent_sku']:"";?>" style="width: 100%;height:25px" name="parent_sku" value="<?= isset($_GET['parent_sku']) ? $_GET['parent_sku']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>

                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >image_url
                        </th>

                        </thead>
                        <input type="submit" style="display: none;">
                    </form>
                    <tbody>
                    <?php
                    if(isset($products) && !empty($products)) {
                        $count=0;
                        foreach($products as $product) { ?>
                            <tr id="parent_row_<?= $count;?>" data-id-pk="<?= $count;?>">
                                <td style="width: 15%;"><?= $product['category']?></td>
                                <td><?= $product['brand']?></td>
                                <td><?= $product['color']?></td>
                                <td><?= $product['size']?></td>
                                <td><?= $product['name']?></td>
                                <td><?= $product['description']?></td>
                                <td><?= $product['price']?></td>
                                <td><?= $product['sku']?></td>
                                <td><?= $product['parent_sku']?></td>
                                <td> <img src="<?= $product['image_url'] ?>" alt="IMG" width="50" class="img-thumbnail pull-left"></td>
                            </tr>

                            <?php $count++;}} ?>

                    </tbody>
                </table>
                <!--------------------------->
                <?php if (isset($products) && !empty($products)) { ?>
                    <table class="table-bordered table">

                        <tbody>
                        <tr>
                            <td colspan="14">
                                <!----pagination------>
                                <?= Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                                <!---------->
                            </td>
                        </tr>
                        </tbody>
                    </table>
                <?php } ?>
                <!--------------------------->
            </div>
        </div>
    </div>
</div>

<!------------------------->
<?php
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJs(<<< EOT_JS_CODE
$(".select2").select2();  // for multiple select dropdown
$('.show_items').on('click',function(){
        let id_pk=$(this).attr('data-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('#child_row_' + id_pk).toggle();
    });
     ////filter btn 
    $('#filter-btn').on('click',function(){
    $('.header-filter-inputs').toggle();
    });
    
    ///category change
    $(document).on('change','.cp-cat-dd',function(){
    var selected_cat=$(this).val();
    var selected_id=$(this).attr('data-id');
    //alert(selected_id);
    //return;
    if(confirm('Are You sure'))
    {
         $.ajax({
            type: "POST",
            url: '/products/update-product-category',
            data: {product_id:selected_id,cat:selected_cat},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    return;
});

 ///activate deactivate  drop down change
    $(document).on('change','#dd_active',function(){
    var selected_option=$(this).val();
    var selected_sku=$(this).attr('data-sku');
    if(confirm('Are You sure'))
    {
         $.ajax({
            type: "POST",
            url: 'products/activate-deactivate-product',
            data: {sku:selected_sku,option:selected_option},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
    }
    return;
});
$('.extra_cost_column').on('change', function() {

      let price=$(this).val();
      let sku=$(this).attr('data-sku');
      ///change value
      $.ajax({
            type: "POST",
            url: '/products/update-extra-price/',
            data: {sku:sku,extra_price:price},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');					
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
});
$(".barcode_input").dblclick(function(){
   $(this).attr("readonly", false); // Element(s) are now enabled.
   $(this).css('border','1px solid gray');
});
$(".barcode_input").blur(function(){
input.attr("readonly", true); 
    input.css('border','none');
});
$(".barcode_input").change(function(){
    let product_id=$(this).attr('data-pk-id');
    let barcode=$(this).val();
    let input=$(this);
    input.attr("readonly", true); 
    input.css('border','none');
    if(barcode==""){
        if(!confirm('Are You sure to remove barcode'))
            return; 
    }
    if(product_id) {
    $.ajax({
            type: "POST",
            url: '/products/update-barcode/',
            data: {id:product_id,barcode:barcode},
            dataType: 'json',
			beforeSend: function(){
									display_notice('info','updating..');
									
								},
			 success: function(msg){
				display_notice(msg.status,msg.msg);
				
			 },
			error: function(XMLHttpRequest, textStatus, errorThrown) 
			{ 
					display_notice('failure',errorThrown);
			} 		
		   });
  }
});
EOT_JS_CODE
);
?>

