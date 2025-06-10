<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/4/2020
 * Time: 2:46 PM
 */
?>
<div style="text-align: center;">
    <i class="mdi mdi-check-circle-outline" style="font-size: 100px; color: green;"></i>
    <h2 class="sweet-alert-custom">Success</h2>
    <p style="display: block;" class="sweet-alert-p-custom">Successfully Arranged The Shipment.</p>
    <?php
    if ( trim($tracking_number)!='' ){
        ?>
        <p style="display: block;" class="sweet-alert-p-custom">Tracking no : <?=$tracking_number?></p>
    <?php
    }
    ?>
    <a href="<?=$airway_bill_link?>" target="_blank">
        <i class="fa fa-print"></i> Print Waybill
    </a>
</div>