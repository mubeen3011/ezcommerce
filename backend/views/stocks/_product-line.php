<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/19/2018
 * Time: 11:18 AM
 */
?>
<style>
    .select2-container{
        width:100% !important;
    }
</style>
<div class="form-group">
    <label>Sku</label>
    <select name="line-item-sku" class="sku-selects select2 form-control" style="padding: 0px !important;">
        <option value="-1">Select SKU</option>
        <?php
        foreach ($ProductList as $detail):?>
            <option value="<?=$detail['sku_id']?>"><?=$detail['sku']?></option>

        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Quantity</label>
    <input type="number" value="1" class="sku-quantity form-control" oninput="validity.valid||(value='');" onchange="if(this.value<=0 ){this.value=1;}" placeholder="Quantity">
    <input type="hidden" name="warehouseId" value="<?=$warehouseId?>">
</div>

<div class="form-group pull-right">
    <input type="button" class="btn-check form-control btn btn-success" style="
    color: white;width: 70px;" value="Add">
</div>

