<?php
use yii\web\View;
$this->title = '';
//$this->params['breadcrumbs'][] = ['label' => 'Sales target', 'url' => ['/sales/targets']];
$this->params['breadcrumbs'][] = 'Stock List Not managed/sync by Ezcom';
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
                    <h3>Stock List Not managed/sync by Ezcom</h3>
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
<div class="row">
    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

    </div>
    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <?php if(count($_GET) > 1): ?>
            <a data-toggle="tooltip" title="Clear filter" href="/stocks/stock-not-managed-by-ezcom" type="button" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"> </i>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
            <i class="fa fa-filter"></i>
        </button>
        <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/stocks/stock-not-managed-by-ezcom-export-csv?<?=http_build_query($_GET)?>">
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
                                <td class="static"><span  data-id-pk='<?= $count; ?>' class="fa fa-plus show_items" style="cursor: pointer;padding-right:10px"> </span>
                                    <?= yii\helpers\Html::a($product['sku'],array("products/detail","sku"=>$product['sku']));?>
                                </td>
                                <td><?= $product['name']?></td>
                                <td><?= $product['cost']?></td>
                                <td><?= $product['rccp']?></td>

                            </tr>
                            <?php if($product['channels']) {?>
                                <tr id="child_row_<?= $count ?>" style="display:none;background: #f6f6f6" class="child-row " >
                                    <td colspan="<?= (count($_GET) > 1) ? "4":"4" ?> "  class="ribbon-wrapper-reverse">
                                        <div class="ribbon ribbon-corner ribbon-right ribbon-warning ribbon-bottom"><i class="fa fa-shopping-cart"></i></div>
                                        <div clas="table-responsive">
                                            <table id="myTable" class="table <!--full-color-table full-muted-table hover-table-->" style="background: #f6f6f6">
                                                <tbody>
                                                <tr>
                                                    <th>Channel</th>
                                                    <th>Name</th>
                                                    <th>Is Active</th>
                                                    <th>Is Deleted</th>
                                                </tr>
                                                <?php //if($product['channels']) {
                                                foreach($product['channels'] as $channel){
                                                    $channel_index=array_search($channel['channel_name'], array_column($channels,'id'));
                                                    ?>
                                                    <tr>
                                                        <td><?= $channels[$channel_index]['name'] ?></td>
                                                        <td><?= $channel['name']; ?></td>
                                                        <td><?= $channel['is_live'] ? "yes":"No";?></td>
                                                        <td><?= $channel['deleted'] ? "Yes":"No";?></td>
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
    


EOT_JS_CODE
);
?>