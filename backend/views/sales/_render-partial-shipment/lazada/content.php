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
$invoice_number='';
if ( isset($OrderItems->data[0]->invoice_number) && $OrderItems->data[0]->invoice_number!='' ){
    $invoice_number = $OrderItems->data[0]->invoice_number;
}
?>
<table class="table color-table info-table lazada-order-fulfilment-items-table">
    <thead>
        <tr>
            <th>Order Nr.</th>
            <th>Items</th>
            <th>Provider</th>
            <th>Tracking ID</th>
            <th>Invoice Number</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="order-id"><?=$order_number?></td>
            <td><?= count($OrderItems->data).' / '.$OrderDetails->data->items_count ?></td>
            <td>

                <?php
                if ( $OrderItems->data[0]->shipment_provider=='' ){
                    ?>
                    <select class="form-control ShippingProvider">
                        <option></option>
                        <?php
                        foreach ( $ShippingProviders->data->shipment_providers as $Value ){
                            ?>
                            <option value="<?=$Value->name?>"><?=$Value->name?> (<?=($Value->cod) ? 'COD Available' : 'COD Unavailable'?>)</option>
                        <?php
                        }
                        ?>
                    </select>
                    <div class="form-control-feedback shipment-providers-validation-status hide" style="color: red">Required field.</div>
                <?php
                }else{
                    ?>
                    <?php
                    $shipment = explode(',',$OrderItems->data[0]->shipment_provider);
                    ?>
                    <input type="hidden" class="ShippingProvider" value="<?=$shipment[0]?>"/>
                    <?=(isset($shipment[0])) ? $shipment[0] : ''?>
                    <br />
                    <?=(isset($shipment[1])) ? $shipment[1] : ''?>
                <?php
                }
                ?>
                <input type="hidden" value="<?=$channel_id?>" id="channel_id"/>
            </td>
            <td class="tracking-code"><?=$OrderItems->data[0]->tracking_code?></td>
            <td>
                <div class="form-group">
                    <input readonly name="lazada_invoice_number" id="lazada_invoice_number" class="form-control" value="<?=($invoice_number!='') ? $invoice_number : round(microtime(true) * 1000)?>"/>
                    <div class="form-control-feedback invoice-validation-status hide" style="color: red">Required field.</div>
                </div>

            </td>
        </tr>
    </tbody>
</table>
<div style="float: right;">
    <button type="button" class="btn btn-warning waves-effect text-left lazada-save-invoice">Ready To Ship</button>
</div>

