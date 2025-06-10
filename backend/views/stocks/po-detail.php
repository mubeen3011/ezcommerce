<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 10/16/2019
 * Time: 10:58 AM
 */
?>
<style>
    .bundle-child-skus{
        pointer-events: none;
    }
</style>
<div class="card">
    <div class="card-body">
        <div class=" row">
            <form method="post" class="form-horizontal" id="po-form" action="save-po">
                <input type="hidden" id="warehouseId" name="warehouseId" value="<?=$_GET['warehouseId']?>">
                <input type="hidden" id="warehouseId" name="warehouseType" value="<?=$warehouseDetail->warehouse?>">

                <?php if (  $po==null || $po->po_status == 'Draft' ): ?>
                    <div id="backToTheTut" data-warehouse="<?=$_GET['warehouseId']?>" class="add-prd badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
                        <i class="glyph-icon  icon-plus" style="font-size: 27px;"></i>
                    </div>
                    <div id="backToTheTutBundle" data-warehouse="<?=$_GET['warehouseId']?>" class="add-bundle badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
                        Add
                        <br />Bundle
                        <?php
                        if (time() < 1546257992){
                            ?>
                            <span class="badge"><b>New</b></span>
                            <?php
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div>
                    <?= Yii::$app->controller->renderPartial('_poinfo', ['po' => $po,'po_code'=>$PoCode,'warehouseDetail'=>$warehouseDetail]); ?>
                    <div class="content-box">

                        <div class="content-box-wrapper">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-striped sticky-header">
                                    <thead>
                                    <tr>
                                        <th class="form-checkbox-radio ">
                                            <input type="checkbox" name="all-po" checked value="all-po" class="po_chk">
                                        </th>
                                        <th class="tg-kr94">SKU <br /> <input type="text" id="sku_filter" class="form-control" placeholder="Search Sku"/> </th>
                                        <th>Status <br /> <input type="text" id="status_filter" class="form-control" placeholder="Search Status"></th>
                                        <th>Type <br /> <input type="text" id="variations_filter" class="form-control" placeholder="Search Variation"></th>
                                        <th class="tg-kr94 hide">12NC</th>
                                        <th class="tg-kr94 hide">Selling Status</th>
                                        <th class="tg-kr94 hide" style="width: 100px;">Cost Price <br /> (no GST)</th>
                                        <th class="tg-kr94">Threshold <br/><input type="text" id="threshold_filter" class="form-control" placeholder="Search Threshold"></th>
                                        <th class="tg-kr94">Transit Days Threshold <br/><input type="text" id="transit_days_threshold_filter" class="form-control" placeholder="Search Transit Days Threshold"></th>
                                        <th class="tg-kr94">Deals Target<br /><input type="text" id="deals_target_filter" class="form-control" placeholder="Search Deals Target"></th>
                                        <th class="tg-kr94">Current Stock <br /><input type="text" id="current_stock_filter" class="form-control" placeholder="Search Current Stock"></th>
                                        <th class="tg-kr94">
                                            <?php if (  $po==null || $po->po_status == 'Draft' ): ?>
                                            Stocks In-Transit (OverAll WareHouse)
                                            <?php else: ?>
                                            Stocks In-Transit (Current PO)
                                            <?php endif; ?>
                                            <br />
                                            <input type="text" id="stock_in_transit_filter" class="form-control" placeholder="Search SIT">
                                        </th>
                                        <th class="tg-kr94">Suggested <br /> Order Qty<br /><input type="text" id="suggested_order_qty_filter" class="form-control" placeholder="Search SOQ"></th>
                                        <th class="tg-kr94">Order Qty</th>
                                        <?php if ($po): ?>
                                            <th class="tg-kr94">Final Qty</th>
                                        <?php endif; ?>
                                        <?php if ($po && $po->po_status!='Draft'): ?>
                                            <th class="tg-kr94">ER Qty</th>
                                        <?php endif; ?>
                                    </tr>
                                    </thead>

                                    <tbody class="gridData po-sku-list">
                                    <tr class="temp-tr-po"></tr>
                                    <?php
                                    echo Yii::$app->controller->renderPartial('purchase_order/po-sku-list', ['po' => $po,'PoSkuList'=>$PoSkuList,
                                        'status'=>$status,'warehouse'=>$warehouseDetail]);
                                    ?>

                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
                <div style="margin: 30px">

                    <?php if ($po): ?>
                        <input type="hidden" value="<?= $po->id ?>" name="po_id">

                        <?php if ($po->po_status != 'Shipped' && $po->po_status != 'Partial Shipped' && $po->po_status != 'Pending'): ?>
                            <input type="submit" class="btn btn-warning btn-po pull-right" name="button_clicked" value="Finalize" />
                        <?php endif; ?>

                        <?php if ($po->po_status == 'Draft'): ?>
                            <input type="submit" class="btn btn-info btn-po pull-right" style="margin-right: 10px;" name="button_clicked" value="Save" />
                        <?php endif; ?>

                        <a href="/stocks/po-print?poid=<?= $po->id ?>&warehouse=<?= $po->warehouse_id ?>" style="margin-right: 10px" class="btn-po pull-right po_print">
                            <i style="font-size: 25px;" class="fa fa-print"></i> Print
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-info btn-po pull-right" value="Initiate PO">Initiate PO</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>
    <script type="text/javascript">
        var isPo = '<?=($po) ? '1' : '0'?>';
    </script>
<?=$this->render('popups/po-sku-information');?>
<?=$this->render('po-panels/add-sku-popup');?>
<?=$this->render('po-panels/add-bundle-popup');?>