<?php
use yii\web\View;
$this->title = '';
//$this->params['breadcrumbs'][] = ['label' => 'Sales target', 'url' => ['/sales/targets']];
$this->params['breadcrumbs'][] = 'Shops Products';
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="panel panel-default">
                    <?= \yii\widgets\Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                    ]) ?>
                    <h3>Shops Products</h3>
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
    .select2-container--default .select2-selection--single
    {
        height:25px !important;
        border-color: black;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b
    {
        top:36%;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered
    {
        line-height: 25px;
    }
</style>
<!------------------------->
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <?php if(count($_GET) > 1): ?>
            <a data-toggle="tooltip" title="Clear filter" href="/channel-products" type="button" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"> </i>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
            <i class="fa fa-filter"></i>
        </button>
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/channel-products/export-csv?<?=http_build_query($_GET)?>">
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
            <?php
            /*
                    echo "<pre>";
                    print_r($products);*/
            ?>

            <div class="card-body table-responsive">

                <!-----------record--------->
                <table class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                    <form  method="GET" id="filter_form">
                        <thead>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist" class="static">SKU
                            <div class="header-filter-inputs" >
                                <select name="sku" class="select2 form-control-sm form-control" id="sku_filter_dd" style="width: 100%;height:25px !important">
                                    <option></option>
                                    <?php foreach ( $sku_list as $value ): ?>
                                        <option value="<?=$value['sku']?>" <?=(isset($_GET['sku']) && $_GET['sku'] == $value['sku']) ? 'selected' : ''?>>
                                            <?= $value['sku']?>
                                        </option>
                                    <?php endforeach; ?>

                                </select>
                            </div>

                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Name
                            <div>
                                <input type="text" data-toggle="tooltip" title="<?= isset($_GET['name']) ? $_GET['name']:"";?>" style="width: 100%;height:25px" name="name" value="<?= isset($_GET['name']) ? $_GET['name']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="1" >Channel

                            <div>
                                <select  class="form-control-sm header-filter-inputs" style="width: 100%;height:25px" name="channel" id="channel_filter_dd">
                                    <option value="" selected >Select</option>
                                    <?php if($channels) {
                                        foreach ($channels as $channel) { ?>
                                            <option value="<?= $channel['id']?>" <?= (isset($_GET['channel']) && $_GET['channel']==$channel['id']) ? "selected":"";?> ><?= $channel['name']?></option>
                                        <?php }} ?>

                                </select>
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="2" >Category
                            <div>
                                <select  class="form-control-sm header-filter-inputs" style="width: 100%;height:25px" name="cat" id="cat_filter_dd" >
                                    <option value="" selected >Select</option>
                                    <option value="0" <?= (isset($_GET['cat']) && $_GET['cat']=="0") ? "selected":"";?> >No Category</option>
                                    <?php if($categories) {
                                        foreach ($categories as $dd_category) { ?>
                                            <option value="<?= $dd_category['key']?>" <?= (isset($_GET['cat']) && $_GET['cat']==$dd_category['key']) ? "selected":"";?> ><?= $dd_category['space'].$dd_category['value']?></option>
                                        <?php }} ?>

                                </select>
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">Stock Update Limit %
                            <div>
                                <input type="number" step="1" data-toggle="tooltip" title="<?= isset($_GET['stock_update_limit']) ? $_GET['stock_update_limit']:"";?>" style="width: 100%;height:25px" name="stock_update_limit" value="<?= isset($_GET['stock_update_limit']) ? $_GET['stock_update_limit']:"";?>" class="form-control-sm header-filter-inputs">
                            </div>
                        </th>
                        <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">Shop Stock
                            <div>
                                <input type="number" step="1" data-toggle="tooltip" title="<?= isset($_GET['shop_stock']) ? $_GET['shop_stock']:"";?>" style="width: 100%;height:25px" name="shop_stock" value="<?= isset($_GET['shop_stock']) ? $_GET['shop_stock']:"";?>" class="form-control-sm header-filter-inputs">
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

                        </thead>
                        <input type="submit" style="display: none;">
                    </form>
                    <tbody>
                    <?php
                    if(isset($products) && !empty($products)) {
                        $count=0;
                        foreach($products as $product) { ?>
                            <tr id="parent_row_<?= $count;?>" data-id-pk="<?= $count;?>">
                                <td class="static">
                                    <a  target="_blank" href="/inventory/warehouses-inventory-stocks?form-filter-used=true&&page=1&Search[sku]=<?= $product['sku']?>">
                                        <?= $product['sku']?>
                                    </a>
                                </td>
                                <td><?= $product['name']?></td>
                                <td><?= $product['channel_name']?></td>
                                <td><?= $product['category'];?></td>
                                <td><input type="number" min="1" data-org-value="<?= $product['stock_update_percent'] ?>" data-pk-id="<?= $product['id']?>" class="form-control form-control-sm stock_update_percent_input" value="<?= $product['stock_update_percent'] ?>" readonly="true" ondblclick="this.readOnly='';"></td>
                                <td><?= $product['stock_qty']?></td>
                                <td><?= $product['cost']?></td>
                                <td><?= $product['rccp']?></td>
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
$this->registerJsFile('monster-admin/assets/plugins/select2/dist/js/select2.full.min.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);

$this->registerJs(<<< EOT_JS_CODE
$(".select2").select2();
$('#sku_filter_dd , #channel_filter_dd, #cat_filter_dd').change(function(){
$('#filter_form').submit();
});
 ////filter btn 
    $('#filter-btn').on('click',function(){
    $('.header-filter-inputs').toggle();
    });
    $('.stock_update_percent_input').change(function(){
        let cp_pk_id=$(this).attr('data-pk-id');
        let percent_value=$(this).val();
        let original_value_before_change=$(this).attr('data-org-value');
        if(confirm('Are You sure') && cp_pk_id)
        {
             $.ajax({
            type: "POST",
            url: '/channel-products/update-product-stock-percent/',
            data: {channel_pk_id:cp_pk_id,percent_value:percent_value},
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
        } else {
            $(this).val(original_value_before_change);
        }
    });
     $('.stock_update_percent_input').dblClick(function(){
     alert();
     });
EOT_JS_CODE
);
?>

