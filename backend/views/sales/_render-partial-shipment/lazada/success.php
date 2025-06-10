<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/4/2020
 * Time: 2:46 PM
 */
?>
<style>
    .submitLink {
        background-color: transparent;
        text-decoration: underline;
        border: none;
        color: blue;
        cursor: pointer;
    }
    .submitLink:focus {
        outline: none;
    }
</style>
<div style="text-align: center;">
    <i class="mdi mdi-check-circle-outline" style="font-size: 100px; color: green;"></i>
    <h2 class="sweet-alert-custom">Success</h2>
    <?php
    if ( trim($tracking_number)!='' ){
        ?>
        <p style="display: block;" class="sweet-alert-p-custom">Tracking no : <?=$tracking_number?></p>
        <?php
    }
    ?>
    <?php
    if ( trim($infoNote) != '' ){
        ?>
        <p style="display: block;" class="sweet-alert-p-custom"><?=$infoNote?></p>
    <?php
    }
    ?>
    <p style="display: block;" class="sweet-alert-p-custom">Drop your parcel at the nearest lazada dropoff point.</p>

    <?php
    if ( isset($shipping_label) && $shipping_label!='' )?>
        <form method="post" target="_blank" action="/sales/lazada-label">
            <input class="form-control" type="hidden" value="<?=$shipping_label?>" name="label" />
            <input type="submit" class="submitLink" value="Click Here To Print Shipping Label">
        </form>
    <?php
    ?>
    <?php
    if ( isset($invoiceLabel) && $invoiceLabel!='' )?>
    <form method="post" target="_blank" action="/sales/lazada-label">
        <input class="form-control" type="hidden" value="<?=$invoiceLabel?>" name="label" />
        <input type="submit" class="submitLink" value="Click Here To Print Invoice">
    </form>
    <?php
    ?>
</div>