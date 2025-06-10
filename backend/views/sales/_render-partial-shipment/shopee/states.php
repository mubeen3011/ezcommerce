<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/31/2020
 * Time: 4:27 PM
 */
?>
<div style="text-align:center;margin-top: 15px;">
    <select class="custom-select col-6 shopee-order-state">
        <option selected="">Select State</option>
        <?php
        foreach ( $states as $state ){
            ?>
            <option value="<?=$state?>"><?=$state?></option>
        <?php
        }
        ?>
    </select>
</div>
