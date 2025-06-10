<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/26/2019
 * Time: 4:00 PM
 */
?>
<?php
if ( !empty($dates->pickup_time) ) :
?>
    <div class="form-group row shopee-pickup-time">
        <label for="example-month-input" class="col-2 col-form-label">Date</label>
        <div class="col-10">
            <select class="custom-select col-12 shopee_pickup_time" name="shopee_pickup_time">
                <?php
                foreach ( $dates->pickup_time as $val ){
                    ?>
                    <option value="<?=$val->pickup_time_id?>"><?=date('d-m-Y', $val->date)?></option>
                <?php
                }
                ?>
            </select>
        </div>
    </div>
<?php
else :
?>
    <div class="form-group row shopee-pickup-time">
        <div class="col-12">
            <h3 style="text-align: center;color: red;">Shopee Error : <?=$dates->msg?></h3>
        </div>
    </div>
<?php
endif;