<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 3/10/2019
 * Time: 11:50 PM
 */

use yii\web\View;

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Product 360', 'url' => ['/product-360/all']];
$this->params['breadcrumbs'][] = 'All Products';

if (  count($_GET) <= 2 ){
    $clear_filter=0;
}elseif ( count($_GET) > 2 ){
    $clear_filter=1;
}
$filterData = \common\models\Products360Fields::find()->where(['in','status',['Draft','Publish']])->orderBy('sku asc')->asArray()->all();
$status = ['pending'=>'pending','shipped'=>'Shipped','canceled'=>'Canceled'];
?>
<style type="text/css">
    .tooltip {
        position: relative;
        display: inline-block;
        border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
    }

    /* Tooltip text */
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 120px;
        background-color: black;
        color: #fff;
        text-align: center;
        padding: 5px 0;
        border-radius: 6px;

        /* Position the tooltip text - see examples below! */
        position: absolute;
        z-index: 1;
    }

    /* Show the tooltip text when you mouse over the tooltip container */
    .tooltip:hover .tooltiptext {
        visibility: visible;
    }
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

    input.filter {
        text-align: center;
        font-size: 12px !important;
        font-weight: normal !important;
        color: #007fff;

    }

    /*.tg-kr94 > select {
        width:93px;
    }
    .tg-kr94 > input {
        width:80px;
    }*/
    .grid-view table thead{
        background-color: white;
    }
    .grid-view table {
        width:100%;
        overflow: hidden;
        border-collapse: collapse;
    }


    /*thead*/
    .grid-view table thead {
        position: relative;
        display: block; /*seperates the header from the body allowing it to be positioned*/
        width: auto;
        overflow: visible;
    }

    .grid-view table thead tr th {
        /*background-color: #99a;*/
        background-color: white;
        width: 133px !important;
    }




    /*tbody*/
    .grid-view table tbody {
        position: relative;
        display: block; /*seperates the tbody from the header*/
        width: 1240px;
        height: 600px;
        overflow: scroll;
    }


    .grid-view table tbody td {
        /*background-color: #bbc;*/
        width:133px !important;

    }
</style>
<style type="text/css">
    .ui-draggable .ui-dialog-titlebar {
        cursor: none;
        display: none;
    }

    .ui-dialog-buttonpane {
        display: none;
    }

    .ui-dialog {
        width: 650px !important;
        z-index: 1050;
    }

    .form-label {
        font-size: 12px;
    }
    th .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da !important;
        min-height: 38px;
        margin-top: 15px;
        display: none;
    }
    .modal-dialog{
            max-width: 1020px !important;
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
                        <h3>Proucts List</h3>
                    </div>

                    <div class="col-md-4 col-sm-12">

                    </div>


                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div id="example23_filter" class="dataTables_filter">

                    <button type="button" class=" btn btn-info" id="filters">
                        <i class="fa fa-filter"></i>
                    </button>
                    <?php
                    if ($clear_filter)
                    {
                        ?>
                        <a href="/product-360/all?view=skus&page=1" class=" btn btn-info  clear-filters" id="filters">
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

                <div class="">

                    <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable" data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable="" data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                        <form>
                            <tr>

                                <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="1" data-sort="desc">
                                    Sku<br />
                                    <input type="hidden" value="skus" name="view">
                                    <select name="Search[sku][]" class="select2 form-control inputs-margin filters-visible"
                                            multiple="multiple">
                                        <?php
                                        foreach ($filterData as $k => $v):
                                            $selected = (isset($_GET['Search']['sku']) && in_array($v['sku'], $_GET['Search']['sku']))
                                                ? 'selected' : '';
                                            ?>
                                            <option <?= $selected ?> value="<?= $v['sku'] ?>"><?= $v['sku'] ?></option>

                                        <?php endforeach; ?>
                                    </select>
                                </th>
                                <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="2" data-sort="desc">
                                    Product name <br />
                                    <input type="text" class="inputs-margin form-control filters-visible" name="Search[product_name]" value="<?= (isset($_GET['Search']['product_name'])) ? $_GET['Search']['product_name'] : '' ?>">
                                </th>
                                <th  class="min-th-width" scope="col" data-tablesaw-priority="3">Category<br /></th>
                                <th  class="min-th-width" scope="col" data-tablesaw-priority="4">Shop(s)<br /></th>
                                <th class="min-th-width" colspan="2" scope="col" data-tablesaw-priority="5">Status<br /> <input type="text" class="filters-visible inputs-margin form-control" name="Search[status]" value="<?= (isset($_GET['Search']['status'])) ? $_GET['Search']['status'] : '' ?>" placeholder="Status"></th>
                            </tr>
                            <input type="hidden" name="page" value="1"/>
                            <input type="submit" name="Filter" class="btn btn-success inputs-margin filters-visible" value="Apply Filter"/>
                        </form>
                        </thead>

                        <tbody class="gridData">
                        <?php
                        foreach ( $data as $key=>$value ){
                            ?>
                            <tr>
                                <td><?=$value['sku']?></td>
                                <td title="<?=$value['name']?>">
                                    <?=substr($value['name'],0,25);?>
                                </td>
                                <td><?=$value['category']?></td>
                                <td>
                                    <?php
                                        $marketplaces = explode(',',$value['marketplace']);
                                        echo "<a href='javascript:;' onclick='javascript:viewshops(".$value['id'].")'>".count($marketplaces)."</a>";
                                    ?>
                                </td>
                                <td colspan="2">
                                    <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($value['status']);?>">
                                            <?=$value['status']?>
                                        </span>
                                    </h4>
                                </td>


                                <!-- <td>
                                    <?php if($value['status'] == 'Draft'): ?>
                                    <a href="/product-360/manage?id=<?=$value['id']?>" title="Product Details"><span class="fa fa-eye"></span></a>
                                    <?php endif; ?>
                                </td> -->
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
                <?=Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,
                    'route'=>\Yii::$app->controller->module->requestedRoute]);?>
            </div>
        </div>
    </div>

</div>
<div id="MarketplacesModel" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Product Marketplace Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>

            </div>
            <div class="modal-body">

            </div>

        </div>

    </div>
</div>
<?php
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile(
    '/monster-admin/js/product-360-all.js?v=' . time(),
    ['depends' => [\backend\assets\AppAsset::className()]]
);
?>
