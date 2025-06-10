<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/26/2019
 * Time: 11:13 AM
 */
if (  isset($_GET['Search']) && count($_GET['Search']) > 0 ){
    $clear_filter=1;
}else{
    $clear_filter=0;
}
/*echo '<pre>';
print_r($_GET);
die;
echo count($clear_filter);die;*/

?>
<style>
    th .select2-container--default {
        border: 1px solid #ced4da !important;
        min-height: 38px;
        margin-top: 15px;
        display: none;
    }
    .filters-visible{
        display: none;
    }
    .select2-dropdown--below{
        width : auto !important;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <h3>Unlisted Skus</h3>
                    </div>

                    <div class="col-md-4 col-sm-12">
                        <form method="get" class="warehouse-form">
                            Select Warehouse<select class="form-control warehouse-dd" name="warehouse">
                                <option></option>
                                <?php
                                foreach ( $warehouses as $value ){
                                    ?>
                                    <option value="<?=$value['id']?>" <?= ( isset($_GET['warehouse']) && $value['id'] == $_GET['warehouse']) ? 'selected' : '' ?>>
                                        <?=$value['name']?>
                                    </option>
                                <?php
                                }
                                ?>
                            </select>
                        </form>
                    </div>


                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
                <div id="example23_filter" class="dataTables_filter">

                    <button type="button" id="export-table-csv" class=" btn btn-info" >
                        <i class="fa fa-download"></i> Export
                    </button>
                    <button type="button" class=" btn btn-info" id="filters">
                        <i class="fa fa-filter"></i>
                    </button>
                    <?php
                    if ($clear_filter)
                    {
                        ?>
                        <a href="/inventory/warehouse-unlisted-skus?warehouse=<?=$_GET['warehouse']?>" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                        <?php
                    }
                    ?>
                    <!--<i class="mdi mdi-filter margin-right-filters-section" onclick="showfilters()" style="color: #009efb;font-size: 20px;float: left;cursor: pointer;" title="Click Here to show filters"></i>-->
                    <div class="dt-buttons margin-right-filters-section" style="padding-top: 3px;float: right;margin-right: 0px;">
                        <!--<a href="javasccript:;" class="dt-button buttons-csv buttons-html5" id="export" tabindex="0" aria-controls="example23"><i class="mdi mdi-download"></i> Export</a>-->
                    </div>
                </div>
                <div class="table-responsive">

                    <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                            <form autocomplete="off" class="unlisted-skus-filters">
                                <tr>
                                    <th class="min-th-width" scope="col" data-tablesaw-priority="persist" style="width: 30%;">SKU<br />
                                        <select name="Search[SKU]" class="select2-form-control select2 warehouse-skus inputs-margin filters-visible form-control">
                                            <option></option>
                                            <?php
                                            foreach ( $warehouseSkus as $value ){
                                                ?>
                                                <option <?=$value['sku']?> <?= (isset($_GET['Search']['SKU']) && $_GET['Search']['SKU'] == $value['sku'] ) ? 'selected' : '' ?>>
                                                    <?=$value['sku']?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </th>
                                    <th scope="col" data-tablesaw-priority="2" class="min-th-width footable-sortable sort sorting" style="width: 30%;" data-sort="desc">
                                        Available <br />
                                        <input  class="warehouse-sku-available inputs-margin filters-visible form-control" value="<?=isset($_GET['Search']['available']) ? $_GET['Search']['available'] : '' ?>" type="text" name="Search[available]">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="3" class="warehouse-shop min-th-width footable-sortable sort sorting" data-sort="desc">
                                        Shop <br />
                                        <select class="select2 inputs-margin filters-visible form-control" name="Search[shop]">
                                            <option></option>
                                            <?php
                                            foreach ( $warehouseChannels as $value ){
                                                ?>
                                                <option <?=(isset($_GET['Search']['shop']) && $_GET['Search']['shop'] == $value['channel_id'])  ? 'selected' : '' ?> value="<?=$value['channel_id']?>">
                                                    <?=$value['name']?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                        <input type="hidden" name="warehouse" value="<?=(isset($_GET['warehouse'])) ? $_GET['warehouse'] : ''?>" >
                                        <button style="display: none;">submit</button>
                                    </th>
                                </tr>

                            </form>
                        </thead>

                        <tbody class="gridData">

                        <?php
                        foreach ( $unListedSkus as $key=>$v ){

                            ?>
                            <tr>
                                <td><?=$v['sku']?></td>
                                <td><?=$v['available']?></td>
                                <td><?=$v['name']?></td>
                            </tr>

                            <?php
                        }
                        ?>
                        <?php
                        if (empty($unListedSkus)){
                            ?>
                            <tr>
                                <td style="text-align: center" colspan="3">No Record Found</td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>

                </div>

            </div>
        </div>
    </div>
</div>
