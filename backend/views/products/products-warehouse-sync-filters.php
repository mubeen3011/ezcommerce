<?php if(isset($_GET['product_status']) && $_GET['product_status'] != "csv-upload" || empty($_GET['product_status'])) { ?>
<form role="form" class="form-horizontal" id="product_filter_form">
    <div class="row filters" style="padding-bottom: 2%">

        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_name']) ? $_GET['product_name'] :"";?>" name="product_name" class="form-control form-control-sm" placeholder="Product Name">
            <input type="hidden" autocomplete="off" value="<?= isset($_GET['product_status']) ? $_GET['product_status'] :"";?>" name="product_status" class="form-control form-control-sm" placeholder="product status">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_sku']) ? $_GET['product_sku'] :"";?>" name="product_sku" class="form-control form-control-sm" placeholder="Sku">
        </div>

        <div class="col-sm-2">
            <select class=" form-control form-control-sm" name="fulfilled_by">
                <option value="">Warehouse Select</option>
                <?php if(isset($warehouses) && !empty($warehouses)):
                    foreach($warehouses as $warehouse) {  ?>
                        <option <?= (isset($_GET['fulfilled_by']) && $_GET['fulfilled_by']==$warehouse['id']) ?  "selected":"";?> value="<?= $warehouse['id'];?>"><?= $warehouse['name'];?></option>
                    <?php } endif;?>
            </select>
        </div>


        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary btn-block"> Search</button>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

            <a  title="Clear Filter" class="btn btn-sm btn-danger pull-right" href="/products/product-sync-to-warehouse?product_status=<?= isset($_GET['product_status']) ? $_GET['product_status'] :"synced";?>" >
                <i class="fa fa-filter "></i>
            </a>&nbsp;
            <a  title="Export" class="btn btn-sm btn-success pull-right mr-2 export_sales_btns" href="/products/export-product-sync-to-warehouse?<?=http_build_query($_GET)?>">
                <i class="fa fa-download"> </i>
            </a> &nbsp;
            <a  class="btn btn-sm btn-secondary pull-right mr-2" >Records
                <i class="fa fa-notes"> : <?= isset($total_records) ? $total_records:"x";?></i>
            </a>

        </div>
    </div>
</form>
<?php }?>