<?php

use common\models\Settings;

//$this->title = 'Products Stocks ';
$this->params['breadcrumbs'][] = ['label' => 'Inventory Management', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Stock List';
$settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
?>
<?php
$settings = \common\models\Settings::find()->where(['name' => 'last_stock_api_update'])->one();
if( isset($settings->value) ){
//    Last Sync at: <?= $settings->value;
$last_sync = 'Last Sync at: '.$settings->value;
}
?>
    <style type="text/css">
        .filters-visible{
            display: none;
        }
        .thead-border{
            /*border: 1px solid black !important;*/
        }
        pre{
            display: none;
        }
        a.sort {
            color: #0b93d5;
            text-decoration: underline;
        }
        .blockPage{
            border:0px !important;
            background-color: transparent !important;
        }



        /*.tg-kr94 > select {
            width:93px;
        }
        .tg-kr94 > input {
            width:80px;
        }*/


    </style>
    <style type="text/css">
        pre{
            display: none;
        }
        .blockPage{
            border:0px !important;
            background-color: transparent !important;
        }
        .scroll {
            border: 0;
            border-collapse: collapse;
        }

        .scroll tr {
            display: flex;
        }

        .scroll td {
            padding: 3px;
            flex: 1 auto;
            border: 0px solid #aaa;
            width: 1px;
            word-wrap: break;
        }

        .scroll thead tr:after {
            content: '';
            overflow-y: scroll;
            visibility: hidden;
            height: 0;
        }

        .scroll thead th {
            flex: 1 auto;
            display: block;
            border: 0px solid #000;
        }

        .scroll tbody {
            display: block;
            width: 100%;
            overflow-y: auto;
            height: 400px;
        }


        input.filter {
            text-align: center;
            font-size: 12px !important;
            font-weight: normal !important;
            color: #007fff;

        }
        .remove-margin-generic-grid{
            margin-bottom: 0px !important;
        }

    </style>
<div class="row">
    <div id="displayBox" style="display: none;">
        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3 class="page-heading-generic-grid margin-special">Stock List </h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <div class="alert alert-warning" style="margin: 0px;"> <i class="ti-user"></i> <?=(isset($last_sync)) ? $last_sync : ''?>.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">×</span> </button>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <?php
                if(!empty($officeSku)): ?>
                    <hr>
                    <div class="alert alert-danger pull-left">
                        <button type="button" class="alert-close close" style="margin-top: -5px;margin-left: 10px;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <strong><?=implode(' , ',$officeSku)?></strong> not updated.
                    </div>

                <?php endif; ?>
                <form>
                    <div class="form-group">
                        <label for="pdqs-stocks"></label>
                        <select class="form-control" name="pdq-sel">
                            <option value="0" <?= ($pdq == '0') ? 'selected' : '' ?>>Get All Stocks</option>
                            <option value="5" <?= ($pdq == '5') ? 'selected' : '' ?>>OOS across all warehouse</option>
                            <option value="2" <?= ($pdq == '2') ? 'selected' : '' ?>>OOS in ISIS </option>
                            <option value="3" <?= ($pdq == '3') ? 'selected' : '' ?>>OOS in FBL Blip</option>
                            <option value="4" <?= ($pdq == '4') ? 'selected' : '' ?>>OOS in FBL 909</option>
                            <option value="1" <?= ($pdq == '1') ? 'selected' : '' ?>>All ISIS Stocks</option>

                        </select>

                    </div>
                </form>
                <div class="pricing-index stk-tbl" style="margin-top: 10px;">

                    <div class="">

                        <div id="example23_filter" class="dataTables_filter">
                            <button type="button" id="export-table-csv" class=" btn btn-info">
                                <i class="fa fa-download"></i> Export
                            </button>
                            <button type="button" class=" btn btn-info" id="filters">
                                <i class="fa fa-filter"></i>
                            </button>
                            <a href="javascript:;" class=" btn btn-info clear-filters hide" id="filters">
                                <i class="fa fa-filter"></i>
                            </a>
                            <div class="dt-buttons margin-right-filters-section" style="float: left;">
                            </div>
                            <label class="form-inline hidden-sm-down" style="float: right;display: none;">

                                <select id="records_per_page" style="height: 25px;" class=" input-sm">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="75">75</option>
                                    <option value="100">100</option>
                                </select>

                            </label>
                        </div>
                        <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                            <thead id="generic-thead">
                            <tr>
                                <th scope="col" data-tablesaw-priority="persist" class=" footable-sortable  sorting min-th-width ">
                                    SKU <i class="sort-arrows fa fa-sort sort sorting" data-field="isis_sku" data-sort="desc"></i><br />
                                    <input type="text" data-filter-field="isis_sku" data-filter-type="like" class="filter form-control filters-visible inputs-margin">
                                </th>
                                <th scope="col" data-tablesaw-priority="persist" class=" footable-sortable  sorting min-th-width ">
                                    CATEGORY <i class="sort-arrows fa fa-sort sort sorting" data-field="c.name" data-sort="desc"></i><br />
                                    <input type="text" data-filter-field="c.name" data-filter-type="like" class="filter form-control filters-visible inputs-margin">
                                </th>
                                <th scope="col" data-tablesaw-priority="2" class="footable-sortable  sorting min-th-width " data-field="isis_sku" data-sort="desc">
                                    Selling Status <i class="fa fa-sort sort-arrows" data-field="selling_status" data-sort="desc"></i><br />
                                    <input type="text" data-filter-field="selling_status" data-filter-type="like" class="filter form-control filters-visible inputs-margin">
                                </th>
                                <!--<th scope="col" data-tablesaw-priority="3" class="footable-sortable  sorting min-th-width " data-field="isis_sku" data-sort="desc">
                                    3'm Avg. Sales <br />
                                    <input type="text" data-filter-field="avg_sales" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                </th>-->
                                <th scope="col" data-tablesaw-priority="4" class="footable-sortable  sorting min-th-width " data-field="isis_sku" data-sort="desc">
                                    Total Stocks <br />
                                    <input type="text" data-filter-field="total_stocks" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                </th>
                                <?php
                                $periority_counter=4;
                                if( $pdq == 0  ){
                                    ?>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="philips_stocks" data-sort="desc">
                                        <!--<a class="sort" data-field="philips_stocks" data-sort="desc" href="javascript:;">Philips Stocks</a>-->
                                        Philips Stocks <i class="fa fa-sort sort-arrows" data-field="philips_stocks" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="philips_stocks" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="office_stocks" data-sort="desc">
                                        <!--<a class="sort" data-field="office_stocks" data-sort="desc" href="javascript:;">Office Stocks</a>-->
                                        Office Stocks <i class="fa fa-sort sort-arrows" data-field="office_stocks" data-sort="desc"></i> <br />
                                        <input type="text" data-filter-field="office_stocks" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="manual_stock" data-sort="desc">
                                        <!--<a class="sort" data-field="office_stocks" data-sort="desc" href="javascript:;">Office Stocks</a>-->
                                        Virtual Stocks <i class="fa fa-sort sort-arrows" data-field="manual_stock" data-sort="desc"></i> <br />
                                        <input type="text" data-filter-field="manual_stock" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <?php
                                }
                                ?>

                                <?php
                                if($pdq == 0 || $pdq == 1 || $pdq == 2 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9){
                                    ?>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="stocks" data-sort="desc">
                                        ISIS-Stock <i class="fa fa-sort sort-arrows" data-field="stocks" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="stocks" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>"  class="footable-sortable  sorting min-th-width " data-field="goodQty" data-sort="desc">
                                        ISIS-Good <i class="fa fa-sort sort-arrows" data-field="goodQty" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="goodQty" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="damagedQty" data-sort="desc">
                                        ISIS-Damaged <i class="fa fa-sort sort-arrows" data-field="damagedQty" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="damagedQty" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="allocatingQty" data-sort="desc">
                                        ISIS-Allocating <i class="fa fa-sort sort-arrows" data-field="allocatingQty" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="allocatingQty" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="processingQty" data-sort="desc">
                                        ISIS-Processing <i class="fa fa-sort sort-arrows" data-field="processingQty" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="processingQty" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <?php
                                }
                                if($pdq == 0 || $pdq == 3 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9){
                                    ?>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="fbl_stock" data-sort="desc">
                                        <!--<a class="footable-sortable" data-field="fbl_stock" data-sort="desc" href="javascript:;">Blip-FBL</a>-->
                                        Blip-FBL <i class="fa fa-sort sort-arrows" data-field="fbl_stock" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="fbl_stock" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <?php
                                }
                                if( ($pdq == 0 || $pdq == 4 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9) ){
                                    ?>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="fbl_99_stock" data-sort="desc">
                                    <!--<a class="footable-sortable" data-field="fbl_99_stock" data-sort="desc" href="javascript:;">909-FBL</a>-->
                                        909-FBL <i class="fa fa-sort sort-arrows" data-field="fbl_99_stock" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="fbl_99_stock" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <?php
                                }
                                if($pdq == 0 || $pdq == 5 ){
                                    ?>
                                    <th scope="col" data-tablesaw-priority="<?=++$periority_counter?>" class="footable-sortable  sorting min-th-width " data-field="fbl_pavent_stock" data-sort="desc">
                                    <!--<a class="footable-sortable" data-field="fbl_99_stock" data-sort="desc" href="javascript:;">909-FBL</a>-->
                                        Avent-FBL <i class="fa fa-sort sort-arrows" data-field="fbl_pavent_stock" data-sort="desc"></i><br />
                                        <input type="text" data-filter-field="fbl_pavent_stock" data-filter-type="operator" class="filter form-control filters-visible inputs-margin">
                                    </th>
                                    <?php
                                }
                                ?>


                            </tr>
                            <!-- filters -->

                            </thead>


                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var defaultUrl = '/stocks/stock-info';
        var sortUrl = '/stocks/stock-info-sort';
        var filterUrl = '/stocks/stock-info-filter';
        var pdqs = '<?= isset($_GET['pdqs']) ? $_GET['pdqs'] : '0' ?>';
    </script>
</div>

<?php

$this->registerJsFile(
    '@web/ao-js/table-filters.js',
    ['depends' => [\yii\web\JqueryAsset::className()]]
);
$this->registerJs("
$('#filters').click(function () {
        var hasClass=$('.inputs-margin').hasClass(\"filters-visible\");
        if( hasClass ){
            $('.inputs-margin').removeClass(\"filters-visible\");
        }else{
            $('.inputs-margin').addClass(\"filters-visible\");
        }

    });
");
?>