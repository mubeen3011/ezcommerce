<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/26/2019
 * Time: 2:32 PM
 */
/*echo '<pre>';
print_r($address);
die;*/
?>
<form class="form shopee-shipment-form">
    <div class="form-group m-t-40 row">
        <label for="example-text-input" class="col-2 col-form-label">Shipping Options</label>
        <div class="col-10">
            <input class="form-control" type="text" value="<?=$order_details->shipping_carrier?>" readonly id="example-text-input">
        </div>
    </div>
    <div class="form-group row shopee-addresses">
        <label for="example-email-input" class="col-2 col-form-label">Pickup Address</label>
        <div class="col-10">
            <div class="list-group">
                <?php
                $a = 1;
                foreach ($address->address_list as $val){
                    ?>
                    <a href="#" class="list-group-item list-group-item-action flex-column align-items-start" data-address-id="<?=$val->address_id?>" data-order-id="<?=$order_number?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h4 class="mb-1">Address <?=$a?></h4>
                        </div>
                        <p class="mb-1">
                            <br />
                            <?=$val->address?>
                            <br />
                            <?=$val->zipcode.' '.$val->state?>
                        </p>
                    </a>
                <?php
                    $a++;
                }
                ?>
            </div>
        </div>
    </div>
</form>
<div style="float: right;">
    <button type="button" disabled class="btn btn-success waves-effect text-left shopee-shipment-confirm">Confirm</button>
</div>

