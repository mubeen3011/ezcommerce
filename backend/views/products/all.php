<?php
use yii\web\View;
$this->title = '';
//$this->params['breadcrumbs'][] = ['label' => 'Sales target', 'url' => ['/sales/targets']];
$this->params['breadcrumbs'][] = 'Product List';
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
                    <h3>Product List</h3>
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
                <a data-toggle="tooltip" title="Clear filter" href="/products" type="button" class="btn btn-danger btn-sm pull-right">
                    <i class="fa fa-filter"> </i>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
                <i class="fa fa-filter"></i>
            </button>
            <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/products/export-csv?<?=http_build_query($_GET)?>">
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
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist" class="static">SKU
                <div>
                    <input type="text" data-toggle="tooltip" title="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" style="width: 100%;height:25px" name="sku" value="<?= isset($_GET['sku']) ? $_GET['sku']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Name
                <div>
                    <input type="text" data-toggle="tooltip" title="<?= isset($_GET['name']) ? $_GET['name']:"";?>" style="width: 100%;height:25px" name="name" value="<?= isset($_GET['name']) ? $_GET['name']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="2" >Category
                <div>
                    <select  class="form-control-sm header-filter-inputs" style="width: 100%;height:25px" name="cat" >
                        <option value="" selected >Select</option>
                        <option value="0" <?= (isset($_GET['cat']) && $_GET['cat']=="0") ? "selected":"";?> >No Category</option>
                        <?php if($categories) {
                            foreach ($categories as $dd_category) { ?>
                                <option value="<?= $dd_category['key']?>" <?= (isset($_GET['cat']) && $_GET['cat']==$dd_category['key']) ? "selected":"";?> ><?= $dd_category['space'].$dd_category['value']?></option>
                            <?php }} ?>

                    </select>
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">BarCode
                <div>
                    <input type="text"  data-toggle="tooltip" title="<?= isset($_GET['barcode']) ? $_GET['barcode']:"";?>" style="width: 100%;height:25px" name="barcode" value="<?= isset($_GET['barcode']) ? $_GET['barcode']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Brand
                <div>
                    <select  class="form-control-sm header-filter-inputs" style="height:25px" name="brand" >
                        <option value="" selected >Select</option>
                        <?php if($brands) {
                            foreach ($brands as $brand) { if(!$brand['brand']){continue;} ?>
                                <option value="<?= $brand['brand']?>" <?= (isset($_GET['brand']) && $_GET['brand']==$brand['brand']) ? "selected":"";?> ><?= $brand['brand']?></option>
                            <?php }} ?>

                    </select>
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Style
                <div>
                    <select  class="form-control-sm header-filter-inputs" style="height:25px" name="style" >
                        <option value="" selected >Select</option>
                        <?php if($styles) {
                            foreach ($styles as $style) { if(!$style['style']){continue;} ?>
                                <option value="<?= $style['style']?>" <?= (isset($_GET['style']) && $_GET['style']==$style['style']) ? "selected":"";?> ><?= $style['style']?></option>
                            <?php }} ?>

                    </select>
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Cost Price
                <div>
                    <input type="number" step="0.01" data-toggle="tooltip" title="<?= isset($_GET['cost_price']) ? $_GET['cost_price']:"";?>" style="width: 100%;height:25px" name="cost_price" value="<?= isset($_GET['cost_price']) ? $_GET['cost_price']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">RCCP
                <div>
                    <input type="number" step="0.01" data-toggle="tooltip" title="<?= isset($_GET['rccp']) ? $_GET['rccp']:"";?>" style="width: 100%;height:25px" name="rccp" value="<?= isset($_GET['rccp']) ? $_GET['rccp']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="5">Extra Cost
                <div>
                    <input type="number" step="0.01" data-toggle="tooltip" title="<?= isset($_GET['extra_cost']) ? $_GET['extra_cost']:"";?>" style="width: 100%;height:25px" name="extra_cost" value="<?= isset($_GET['extra_cost']) ? $_GET['extra_cost']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="6">Promo Price
                <div>
                    <input type="number" step="0.01" data-toggle="tooltip" title="<?= isset($_GET['promo_price']) ? $_GET['promo_price']:"";?>" style="width: 100%;height:25px" name="promo_price" value="<?= isset($_GET['promo_price']) ? $_GET['promo_price']:"";?>" class="form-control-sm header-filter-inputs">
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="7">Stock Status
                <div>
                    <select class="form-control-sm header-filter-inputs" style="width: 100%;height:25px" name="stock_status">
                        <option value="" selected>Select</option>
                        <option value="High" <?= (isset($_GET['stock_status']) && $_GET['stock_status']=="High") ? "selected":"";?>>High</option>
                        <option value="Slow" <?= (isset($_GET['stock_status']) && $_GET['stock_status']=="Slow") ? "selected":"";?>>Slow</option>
                        <option value="Medium" <?= (isset($_GET['stock_status']) && $_GET['stock_status']=="Medium") ? "selected":"";?>>Medium</option>
                        <option value="Not Moving" <?= (isset($_GET['stock_status']) && $_GET['stock_status']=="Not Moving") ? "selected":"";?>>Not Moving</option>
                    </select>
                </div>
            </th>
            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="8">Active
                <div>
                    <select class="form-control-sm header-filter-inputs" style="width: 100%;height:25px" name="is_active">
                        <option value="" <?= (isset($_GET['is_active']) && !in_array($_GET['is_active'],["1","0"])) ? "selected":"";?> >Select</option>
                        <option value="1" <?= (isset($_GET['is_active']) && $_GET['is_active']=="1") ? "selected":"";?>>Yes</option>
                        <option value="0" <?= (isset($_GET['is_active']) && $_GET['is_active']=="0") ? "selected":"";?>>No</option>
                    </select>
                </div>
            </th>
            <th>Action</th>
            </thead>
            <input type="submit" style="display: none;">
            </form>
            <tbody>
            <?php
            if(isset($products) && !empty($products)) {
            $count=0;
            foreach($products as $product) { ?>
            <tr id="parent_row_<?= $count;?>" data-id-pk="<?= $count;?>">
                <td class="static"><span  data-id-pk='<?= $count; ?>' class="fa fa-plus show_items" style="cursor: pointer;padding-right:10px"> </span>
                    <!--<a  target="_blank" href="/inventory/warehouses-inventory-stocks?form-filter-used=true&&page=1&Search[sku]=<?php // $product['sku']?>">-->

                    <!--</a>-->
                    <?= yii\helpers\Html::a($product['sku'],array("products/detail","sku"=>$product['sku']));?>
                </td>
                <td><?= $product['name']?>
                    <?php if(isset($product['p_360']['admin_status'])){
                        if(in_array($product['p_360']['admin_status'],['draft','pending'])){ ?>
                            <span style="writing-mode: vertical-rl;color:black" class="badge badge-warning pull-right">Pending</span>
                        <?php }} ?>
                </td>
                <td>
                   <!-- <select data-sku="<?/*= $product['sku'] */?>"  style="width:70%" class="cp-cat-dd form-control form-control-sm">
                        <option value="0" <?/*= !($product['category']) ? 'selected' : '' */?> >No Category</option>
                        <?php /*if($categories) {
                            foreach ($categories as $dd_category) { */?>
                                <option value="<?/*= $dd_category['key']*/?>" <?/*= $product['category'] == $dd_category['key'] ? 'selected' : '' */?> ><?/*= $dd_category['space'].$dd_category['value']*/?></option>
                            <?php /*}} */?>

                    </select>-->
                    <select data-id="<?= $product['id'] ?>" class="cp-cat-dd category-selector select2 form-control full-width  select2-multiple"  multiple="multiple"  size="4" tabindex="-1" aria-hidden="true">
                        <?php if($categories) {
                            foreach ($categories as $dd_category) { ?>
                                <option value="<?= $dd_category['key']?>" <?= in_array($dd_category['key'],$product['category']) ? 'selected' : '' ?> ><?= $dd_category['space'].$dd_category['value']?></option>
                            <?php }} ?>
                    </select>
                </td>
                <td><input type="text" data-pk-id="<?= $product['id']?>" class="barcode_input" readonly style="border:none;background: transparent" value="<?= $product['barcode']?>"></td>
                <td><?= $product['brand']?></td>
                <td><?= $product['style']?></td>
                <td><?= $product['cost']?></td>
                <td><?= $product['rccp']?></td>
                <td >
                    <input  data-sku="<?= $product['sku']; ?>" class="extra_cost_column" style="border:none" type="number" step="0.01" value="<?= $product['extra_cost']?>"/>
                </td>
                <td><?= $product['promo_price'] ? $product['promo_price']:"x" ;?></td>
                <td><span class="badge badge-pill badge-<?= isset($status_badge[$product['stock_status']]) ? $status_badge[$product['stock_status']]:""; ?>"><?= $product['stock_status']?></span></td>
                <td>
                    <select data-sku="<?= $product['sku'] ?>" class="form-control form-control-sm" style="width:60%" id="dd_active">
                        <option value="1" <?= $product['is_active'] ? "selected":"";?>>Yes</option>
                        <option value="0" <?= $product['is_active'] ? "":"selected";?>>No</option>
                    </select>
                </td>
                <td><a href="/product-sync/manage?edit_non_360=yes&product_id=<?= $product['id'];?>"><span class="fa fa-edit"></span></a></td>
            </tr>
                <?php if($product['channels']) {?>
            <tr id="child_row_<?= $count ?>" style="display:none;background: #f6f6f6" class="child-row " >
                <td colspan="<?= (count($_GET) > 1) ? "10":"10" ?> "  class="ribbon-wrapper-reverse">
                    <div class="ribbon ribbon-corner ribbon-right ribbon-warning ribbon-bottom"><i class="fa fa-shopping-cart"></i></div>
                    <div clas="table-responsive">
                        <table id="myTable" class="table <!--full-color-table full-muted-table hover-table-->" style="background: #f6f6f6">
                            <tbody>
                            <tr>
                                <th>MarketPlace</th>
                                <th>Channel</th>
                                <th>Name</th>
                                <th>Item ID</th>
                                <th>EAN</th>
                                <th>Enabled</th>
                                <th>Status</th>
                            </tr>
                            <?php //if($product['channels']) {
                                foreach($product['channels'] as $channel){
                                    $channel_index=array_search($channel['channel_name'], array_column($channels,'id'));
                                ?>
                            <tr>
                            <td> <img src="<?= '/images/'.$channels[$channel_index]['marketplace'].".png"; ?>" alt="IMG" width="50" class="img-thumbnail pull-left"></td>
                            <td><?= $channels[$channel_index]['name'] ?></td>
                            <td><?= $channel['name']; ?></td>
                            <td><?= $channel['channel_sku_id']?></td>
                            <td><?= $channel['ean']?></td>
                                <td><i class="fa fa-circle text-<?= $channel['is_live'] ? "success":"danger";?>"></i> </td>
                                <td><?= $channel['deleted'] ? "Deleted":"<i class='fa fa-check text-success'></i>";?></td>
                            </tr>
                    <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>
                <?php } else {  ?>
            <tr id="child_row_<?= $count ?>" style="display:none;background: #f6f6f6" class="child-row " >
                <td colspan="8"> NO Channel Found</td>
            </tr>
                    <?php } ?>
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

