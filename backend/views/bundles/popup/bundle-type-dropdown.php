<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/18/2018
 * Time: 11:48 AM
 */
if (isset( $_GET['child_skus'] )){
    $Already_Child_Skus = explode(',',$_GET['child_skus']);
}else{
    $Already_Child_Skus = [];
}
?>
<div class="related">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group" style="float: left;">
                <label class="sr-only" for="action_id">Related Product</label>
                <select class="select2 select2-form-control child_foc_lists pull-left" name="child_skus[]">
                    <option></option>
                    <?php
                        foreach ($Sku_List as $key=>$value){
                            if (!in_array(trim($value['sku']),$Already_Child_Skus)){
                                ?>
                                <option value="<?=$value['sku']?>"><?=$value['sku']?></option>
                    <?php
                            }
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class="col-md-5">
            <div class="form-group" style="float: left;">
                <label class="sr-only" for="action_id">Quantity</label>
                <input type="number" value="1" class="form-control" name="Quantity[]" oninput="validity.valid||(value='');" onblur="if(this.value==0){this.value=1;}" min="1">
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-group">
                <span class="btn-danger btn remove-me">Remove</span>
            </div>
        </div>
    </div>
</div>