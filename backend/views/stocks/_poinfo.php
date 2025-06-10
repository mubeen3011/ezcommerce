<?php
/*echo '<pre>';
print_r($po);
die;*/
?>
<?php
use yii\helpers\Html;
(isset($po->po_status) && ($po->po_status=='Pending' || $po->po_status=='Shipped' || $po->po_status=='Partial Shipped') ) ? $disabled=true : $disabled=false ?>
<div class="content-box">
    <div class="card-header po-information-tab-heading" style="height: 48px;">
        <h4 class="content-box-header primary-bg">
            <span class="float-left ">PO Information</span>

        </h4>
    </div>
    <div class="content-box-wrapper" style="margin-top: 10px;margin-right: 15px;">


        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">PO Date</label>
                        <?php
                        $date = (isset($po) && $po->po_initiate_date) ? date("d M Y", strtotime($po->po_initiate_date)) : date("d M Y") ?>
                        <input class="form-control" type="text" name="po_date" readonly value="<?= (isset($po) && $po->po_initiate_date) ? $po->po_initiate_date : date('Y-m-d H:i') ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">PO Code *</label>
                    <?php $date = (isset($po) && $po->po_initiate_date) ? date("d M Y", strtotime($po->po_initiate_date)) : date("d M Y"); ?>
                    <input name="po_code" class="form-control" readonly value="<?=$po_code?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Bill To</label>
                    <input class="form-control" type="text" name="po_bill" <?=(!isset($po) || ($po->po_status=='Draft')) ? '' : 'readonly'?> value="<?= (isset($po) && $po->po_bill) ? $po->po_bill : '' ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Ship To</label>
                    <?php $po_code = (isset($po) && $po->po_ship) ? $po->po_ship : ''; ?>
                    <input name="po_ship" class="form-control" <?=(!isset($po) || ($po->po_status=='Draft')) ? '' : 'readonly'?> value="<?=$po_code?>">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Status</label>

                        <?php if ( $po != null ) : ?>
                            <input type="text" readonly class="form-control" value="<?=$po->po_status?>" name="po_status"/>
                        <?php else: ?>
                            <input class="form-control" type="text" readonly name="po_status" value="New">
                        <?php endif; ?>

                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Delivery No</label>

                    <?php $remarks = (isset($po) && $po->remarks) ? $po->remarks : ''; ?>
                    <input type="text" <?=(!isset($po) || ($po->po_status=='Draft')) ? '' : 'readonly'?> class="form-control" value="<?=$remarks?>" name="remarks"/>


                </div>
            </div>
        </div>

        <div class="row">
            <?php
            if (isset($_GET['poId'])){
                ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Warehouse</label>
                        <div class="input-group">
                            <input name="warehouse_name" value="<?=$warehouseDetail->name?>" readonly type="text" class="form-control">
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

    </div>
</div>
<?php
$this->registerJs("$(\".ship_to\").on('change', function () {
        var wh = $(this).attr('data-wh');
        var to = $(this).val();
        if(to == 'customer')
        {
        $(\".\"+wh+\"_ship_c_add\").addClass('form-control');
            $(\".\"+wh+\"_ship_c_add\").removeClass('hide');
        } else {
            $(\".\"+wh+\"_ship_c_add\").removeClass('form-control');
            $(\".\"+wh+\"_ship_c_add\").addClass('hide');
        }

    });");