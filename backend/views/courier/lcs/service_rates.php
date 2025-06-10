<?php if(isset($charges)) { ?>

  <div class="row">
        <div class="col-sm-6">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">
                        <small class="mdi mdi-cash"> Charges</small>
                    </span>
                </div>
                 <input type="number" min="0" value="<?= $charges?>" required name="shipping_charges" class="form-control" placeholder="Charges">
            </div>
            <br>
        </div>
        <div class="col-sm-6">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1" data-toggle="tooltip" title="Charges to collect from customer">
                        <small class="mdi mdi-cash"> Total charges to collect</small>
                    </span>
                </div>
                <input type="number" min="0" value="<?= ($charges + $order_total)?>" required name="total_package_charges" class="form-control" placeholder="total Charges">

            </div>
        </div>


    </div>

<?php } ?>
<?php if(isset($error) && !empty($error)) { ?>
<div class="col-sm-12">
    <?= $error; ?>
</div>
<?php } ?>