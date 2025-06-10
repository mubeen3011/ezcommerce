<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 1/31/2020
 * Time: 5:50 PM
 */
?>
<div style="text-align:center;margin-top: 15px;" class="">
    <select class="custom-select col-6 shopee-order-branch">
        <?php
        foreach ($branches as $branch_id => $address){
            ?>
            <option value="<?=$branch_id?>" ><?=$address?></option>
        <?php
        }
        ?>
    </select>
</div>
