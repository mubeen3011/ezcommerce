<?php
//print_r($data); die();
use common\models\Channels;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel common\models\search\OrderItemsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard']];
$this->params['breadcrumbs'][] = 'Sales Details';
$deals = \common\models\DealsMaker::find()->where(['status'=>'expired'])->orderBy('created_at desc')->asArray()->all();
$filterData = \common\models\Products::find()->Where(['is_active'=>1])->orderBy('selling_status asc')->asArray()->all();
$status = ['pending'=>'pending','shipped'=>'Shipped','canceled'=>'Canceled'];

if (  count($_GET) <= 2 ){
    $clear_filter=0;
}elseif ( count($_GET) > 2 ){
    $clear_filter=1;
}
$currentModule = Yii::$app->controller->id;
$ArrangeShipPermission=\backend\util\HelpUtil::AccessAllowed($currentModule,'arrange-shipment');
$AssignWarehouse = \backend\util\HelpUtil::AccessAllowed($currentModule,'assign-warehouse');
//echo '<pre>';print_r($data);die;
//echo $AssignWarehouse;die;
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
        .form-control
        {
            width:auto !important;
        }
    </style>

<?php if (Yii::$app->session->hasFlash('import_info')): ?>
    <h4 style="color: green;"><?=$flash_message=Yii::$app->session->getFlash('import_info');?></h4>
<?php endif; ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <div class=" row">

                        <div id="displayBox" style="display: none;">
                            <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <h3>Sales Details</h3>
                        </div>

                        <div class="col-md-4 col-sm-12">

                        </div>


                        <div class="col-md-4 col-sm-12">
                            <?= \yii\widgets\Breadcrumbs::widget([
                                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                            ]) ?>
                        </div>
                    </div>
                    <!--<button data-toggle="modal" data-target="#offlineSalesImportModal" type="button" id="upload-offline-sales" class="notification btn btn-info" >
                        <i class="fa fa-upload"></i> Import Offline Sales
                        <?php
/*                        if (time()<1541376000){
                            */?>
                            <span class="badge"><b>New</b></span>
                            <?php
/*                        }
                        */?>

                    </button>-->
                    <div id="example23_filter" class="dataTables_filter">

                        <button type="button" id="export-table-csv" class=" btn btn-info" >
                            <i class="fa fa-download"></i> Export
                        </button>
                        <button type="button" class=" btn btn-info" data-toggle="modal" data-target="#myModal" data-whatever="@mdo">
                            <i class="fa fa-filter"></i> Advance Filter
                        </button>
                        <button type="button" class=" btn btn-info" id="filters">
                            <i class="fa fa-filter"></i>
                        </button>
                        <?php
                        if ($clear_filter)
                        {
                            ?>
                            <a href="/sales/reporting?view=skus&page=1" class=" btn btn-info  clear-filters" id="filters">
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
                            <form autocomplete="off">
                                <tr>
                                    <th class="min-th-width" scope="col" data-tablesaw-priority="persist">Order Id<br />
                                        <input type="text" class="inputs-margin form-control filters-visible" name="Search[order_id]" value="<?= (isset($_GET['Search']['order_id'])) ? $_GET['Search']['order_id'] : '' ?>" placeholder="Place comma(,) between order id">
                                    </th>
                                    <th scope="col" data-tablesaw-priority="2" class="min-th-width footable-sortable sort sorting" data-sort="desc">
                                        Order date <br />
                                        <input  class="inputs-margin filters-visible form-control input-daterange-datepicker" value="<?=isset($_GET['Search']['created']) ? $_GET['Search']['created'] : '' ?>" type="text" name="Search[created]">
                                    </th>
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="3" data-sort="desc">
                                        Product name <br />
                                        <input type="text" class="inputs-margin form-control filters-visible" name="Search[product_name]" value="<?= (isset($_GET['Search']['product_name'])) ? $_GET['Search']['product_name'] : '' ?>">
                                    </th>
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="4" data-sort="desc">
                                        Sku<br />
                                        <input type="hidden" value="skus" name="view">
                                        <select name="Search[sku][]" class=" select2 select2-multiple select2-form-control form-control inputs-margin  filters-visible"
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
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="4"  data-sort="desc">
                                        QTY<br />
                                        <input data-filter-type="operator" type="text" class="inputs-margin form-control filters-visible"  name="Search[quantity]" value="<?= (isset($_GET['Search']['quantity'])) ? $_GET['Search']['quantity'] : '' ?>" />
                                    </th>
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="5" data-sort="desc">
                                        <!--Threshold-->
                                        paid Price <br />
                                        <input data-filter-type="operator" type="text" class="inputs-margin form-control filters-visible"  name="Search[paid_price]" value="<?= (isset($_GET['Search']['paid_price'])) ? $_GET['Search']['paid_price'] : '' ?>" />
                                    </th>
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="5" data-sort="desc">
                                        <!--Threshold-->
                                        Subtotal <br />
                                        <input data-filter-type="operator" type="text" class="inputs-margin form-control filters-visible"  name="Search[sub_total]" value="<?= (isset($_GET['Search']['sub_total'])) ? $_GET['Search']['sub_total'] : '' ?>" />
                                    </th>
                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="5" data-sort="desc">
                                        <!--Threshold-->
                                        Tax <br />
                                        <input data-filter-type="operator" type="text" class="inputs-margin form-control filters-visible"  name="Search[item_tax]" value="<?= (isset($_GET['Search']['item_tax'])) ? $_GET['Search']['item_tax'] : '' ?>" />
                                    </th>
                                    <th data-toggle="tooltip" title="Channel" class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="6" data-sort="desc">
                                        <!--Threshold Critical-->
                                        <?=\backend\util\HelpUtil::getThShorthand('Shops')?><br />
                                        <select style="width: 100% !important;" name="Search[channel_id][]"
                                                class="inputs-margin select2 m-b-10 select2-form-control form-control select2-multiple filters-visible" multiple="multiple">
                                            <option></option>
                                            <?php
                                            foreach (Channels::find()->where(['is_active'=>'1','is_fetch_sales' => '1'])->asArray()->all() as $k => $v):
                                                $selected = (isset($_GET['Search']['channel_id'])
                                                    && in_array($v['id'], $_GET['Search']['channel_id']))
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v['id'] ?>"><?= $v['name'] ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                    </th>
                                    <th  class="min-th-width" scope="col" data-tablesaw-priority="8">Item Status<br />
                                        <input type="text" class="filters-visible inputs-margin form-control" name="Search[item_status]" value="<?= (isset($_GET['Search']['item_status'])) ? $_GET['Search']['item_status'] : '' ?>" placeholder="Order Item Status"></th>
                                    <th data-toggle="tooltip" title="Order Status" class="min-th-width" scope="col" data-tablesaw-priority="9">
                                        <?=\backend\util\HelpUtil::getThShorthand('Order Status')?><br />
                                        <select style="width: 100% !important;" name="Search[status]"
                                                class="inputs-margin select2 m-b-10 form-control filters-visible">
                                            <option></option>
                                            <?php
                                            foreach ($status as $k => $v):
                                                $selected = (isset($_GET['Search']['status'])
                                                    && $v == $_GET['Search']['status'])
                                                    ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?> value="<?= $v ?>"><?= ucwords($v) ?></option>

                                            <?php endforeach; ?>
                                        </select>
                                    </th>
                                    <?php
                                    if ($ArrangeShipPermission){
                                        ?>
                                        <th data-toggle="tooltip" title="Shipment" class="min-th-width" scope="col" data-tablesaw-priority="10">
                                            <?=\backend\util\HelpUtil::getThShorthand('Shipment')?><br />
                                        </th>
                                        <?php
                                    }
                                    ?>

                                   <!-- <th data-toggle="tooltip" title="Seller Center Status" class="min-th-width" scope="col" data-tablesaw-priority="10">Seller Center Status</th>-->
                                    <!--<th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="11" data-sort="desc">W/H<br />
                                        <select style="width: 100% !important;" name="Search[warehouse]"
                                                class="inputs-margin m-b-10  form-control filters-visible">
                                            <option></option>
                                            <option value="Dropshipping" <?= ( isset($_GET['Search']['warehouse']) && $_GET['Search']['warehouse'] == 'Dropshipping' ) ? 'selected' : '' ?>>ISIS</option>
                                            <option value="Own Warehouse" <?= ( isset($_GET['Search']['warehouse']) && $_GET['Search']['warehouse'] == 'Own Warehouse' ) ? 'selected' : '' ?> >FBL</option>
                                        </select>
                                    </th>-->
                                   <!-- <th data-toggle="tooltip" title="Ready to ship" class="min-th-width" scope="col" data-tablesaw-priority="12">Ready to ship</th>-->
                                    <?php
                                    if ($AssignWarehouse){
                                        ?>
                                        <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="10" data-sort="desc">
                                            <?=\backend\util\HelpUtil::getThShorthand('FulFilled By')?><br />
                                            <select style="width: 100% !important;" name="Search[fulfilled_by]"
                                                    class="inputs-margin select2 m-b-10 form-control filters-visible">
                                                <option></option>
                                                <?php
                                                foreach ($warehouses as $v):
                                                    $selected = (isset($_GET['Search']['fulfilled_by']) && $v['id'] == $_GET['Search']['fulfilled_by']) ? 'selected' : '';
                                                    ?>
                                                    <option <?= $selected ?> value="<?= $v['id'] ?>"><?= ucwords($v['name']) ?></option>

                                                <?php endforeach; ?>
                                            </select>
                                        </th>
                                        <?php
                                    }
                                    ?>

                                    <th class="min-th-width footable-sortable sort sorting  " scope="col" data-tablesaw-priority="13" data-sort="desc">
                                        View
                                        <input type="hidden" name="page" value="1"/>
                                        <input type="submit" style="display: none;"/>
                                    </th>

                                </tr>

                            </form>
                            </thead>

                            <tbody class="gridData">

                            <?php
                            foreach ( $data as $key=>$v ){

                                $itemIds = [];
                                $fullFilledBy=[];
                                //$TrackingNumbers = [];
                                foreach ($v as $value){
                                    if ( $value['item_status'] == 'pending' && $value['fulfilled_by_warehouse']!='' ){
                                        $itemIds[] = $value['order_item_id_pk'];
                                    }
                                    if ( $value['fulfilled_by_warehouse']!='' ){
                                        $fullFilledBy[] = $value['fulfilled_by_warehouse'];
                                    }
                                    if ($value['tracking_number']!=''){
                                        //$TrackingNumbers[] = $value['tracking_number'];
                                    }
                                }
                                ?>
                                <tr style="background: #f2f7f8;">
                                    <td><?=$key?></td>
                                    <td colspan="10"></td>
                                    <?php
                                    if ($ArrangeShipPermission){
                                        ?>
                                        <td>
                                           <!-- <button  class="btn btn-info ship_now">
                                                <i class="fas fa-shipping-fast"></i>
                                                Ship Now
                                            </button>-->
                                            <?php
                                            $orderStatus = explode(',',$v[0]['order_status']);
                                            //echo '<prE>';print_r($v[0]['order_status']);die;
                                            if ( in_array('pending',$orderStatus) && $v[0]['marketplace']!='amazon' /*amazon customer info is confidential*/
                                                && !empty($fullFilledBy) && !empty($itemIds)){ ?>

                                                <a data-order-id="<?= $v[0]['id']?>" data-ship-entity="order" data-item-id="" class="btn btn-outline-info waves-effect waves-light btn-sm  ship-now-btn" data-toggle="modal" data-target="#courier_selection_modal" data-backdrop="static">
                                                    <i class="fas fa-shipping-fast"></i> Ship Now
                                                </a>
                                        <?php       /* foreach ($courier_services as $comp){
                                                    */?><!--
                                                    <button type="button" class="<?/*=$comp['type']*/?>-color shipment-arrange btn btn-danger arrange-shipment" data-order-id="<?/*=$key*/?>" data-order-item-id="<?/*=implode(',',$itemIds)*/?>"
                                                            data-courier-id="<?/*=$comp['id']*/?>" data-courier-type="<?/*=$comp['type']*/?>" data-channel-id="<?/*=$value['channel_id']*/?>"
                                                            data-marketplace="<?/*=$value['marketplace']*/?>">
                                                        <i class="fas fa-shipping-fast"></i>
                                                        Ship Order - <?/*=$comp['name']*/?>
                                                    </button>
                                                    --><?php
/*                                                }*/
                                           } elseif ($value['tracking_number']!=''){
                                                echo '-';
                                            }else{
                                                echo 'N/A';
                                            }
                                            ?>


                                        </td>
                                        <?php
                                    }
                                    ?>

                                    <td></td>
                                    <td>
                                        <a href="/sales/item-detail?id=<?=$v[0]['id']?>" title="Order Details"><span class="fa fa-eye"></span></a>
                                    </td>
                                </tr>
                                <?php
                                foreach ($v as $value ){
                                    /*echo '<pre>';
                                    print_r($value);
                                    die;*/
                                    ?>
                                    <tr style="cursor:pointer">
                                        <td>&nbsp;→&nbsp;<?=$value['order_number']?></td>
                                        <td><?=$value['item_created_at']?></td>
                                        <td title="<?=$value['product_name']?>">
                                            <?=substr($value['product_name'],0,25);?>
                                        </td>
                                        <td><?=$value['sku']?></td>
                                        <td><?=$value['quantity']?></td>
                                        <td><?=$value['quantity'] > 0 ? ($value['sub_total'] / $value['quantity']): $value['sub_total'];?></td>
                                        <td><?=$value['sub_total']?></td>
                                        <td><?=$value['item_tax']?></td>
                                        <td><?=$value['name']?></td>

                                        <td>
                                            <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($value['item_status']);?>">
                                            <?=$value['item_status']?>
                                        </span>
                                            </h4>
                                        </td>

                                        <td>
                                            <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($value['order_status']);?>">
                                            <?=$value['order_status']?>
                                        </span>
                                            </h4>
                                        </td>
                                        <?php if ($ArrangeShipPermission) { ?>
                                            <td>
                                                <?php
                                                if ($value['item_status']=='pending' && $value['marketplace']!='amazon' /*amazon customer info is confidential*/ &&
                                                    $value['fulfilled_by_warehouse']!='' ){ ?>
                                                    <a data-order-id="<?= $value['id'];?>" data-ship-entity="order_item" data-item-id="<?=$value['order_item_id_pk']; ?>" class="btn btn-outline-info waves-effect waves-light btn-sm ship-now-btn" data-toggle="modal" data-target="#courier_selection_modal" data-backdrop="static">
                                                        <i class="fas fa-shipping-fast"></i> Ship Now
                                                    </a>
                                               <?php     /*foreach ($courier_services as $comp) {
                                                        */?><!--
                                                        <button style="font-size: 10px;" type="button" class="<?/*=$comp['type']*/?>-color shipment-arrange btn btn-danger arrange-shipment" data-order-id="<?/*=$value['order_number']*/?>" data-order-item-id="<?/*=$value['order_item_id_pk']*/?>"
                                                                data-courier-id="<?/*=$comp['id']*/?>" data-courier-type="<?/*=$comp['type']*/?>" data-channel-id="<?/*=$value['channel_id']*/?>"
                                                                data-marketplace="<?/*=$value['marketplace']*/?>">
                                                            <i class="fas fa-shipping-fast"></i>
                                                            Ship Item - <?/*=$comp['name']*/?>
                                                        </button>
                                                        --><?php
/*                                                    }*/
                                                }elseif ($value['tracking_number']!=''){
                                                    echo $value['tracking_number'].'<br />';
                                                    ?>
                                                    <a target="_blank" href="<?php if( $value['shipping_label']!='' && $value['courier_type']=='fedex' ) {
                                                        echo '/shipping_labels/Fedex-Item-'.$value['order_item_id_pk'].'.pdf';
                                                    }?>">
                                                        Print Label
                                                    </a>
                                                    <?php
                                                }else{
                                                    echo 'N/A';
                                                }
                                                ?>


                                            </td>
                                            <?php }  ?>
                                        <?php
                                        if ($AssignWarehouse){
                                            ?>
                                            <td>
                                                <select data-item-pk="<?= $value['order_item_id_pk'] ?>" <?=($value['item_status']=='shipped'||$value['fulfilled_by_warehouse']=='completed') ? 'disabled' : ''?>
                                                        class="form-control assign-warehouse" <?= (($value['fulfilled_by_warehouse']) != '' ) ? 'disabled' : '' ?>>
                                                    <option>-</option>
                                                    <?php
                                                    foreach ($warehouses as $v):
                                                        $selected = (isset($value['fulfilled_by_warehouse']) && $v['id'] == $value['fulfilled_by_warehouse']) ? 'selected' : '';
                                                    ?>
                                                        <option <?= $selected ?> value="<?= $v['id'] ?>"><?= ucwords($v['name']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php
                                                ?>
                                            </td>
                                            <?php
                                        }
                                        ?>
                                        <td>
                                            <a href="/sales/item-detail?id=<?=$value['id']?>" title="Order Details"><span class="fa fa-eye"></span></a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>

                                <?php
                            }
                            ?>
                            </tbody>
                        </table>

                    </div>
                    <?=Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                </div>
            </div>
        </div>

    </div>


<?= Yii::$app->controller->renderPartial('popups/upload-offline-sales'); ?>
<?= Yii::$app->controller->renderPartial('popups/arrange-shipment'); ?>
<?= Yii::$app->controller->renderPartial('../courier/popups/modal'); ?>
<?= Yii::$app->controller->renderPartial('_advanced_filter', ['filterdata' => $filterData, 'deals'=>$deals]); ?>

<?php


$this->registerJsFile(
    '//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js',
    [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerCssFile(
    '//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);
$this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.full.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile(
    '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
    ['depends' => [\frontend\assets\AppAsset::className()]]
);

$this->registerJsFile('ao-js/sales.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('ao-js/courier.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
?>

