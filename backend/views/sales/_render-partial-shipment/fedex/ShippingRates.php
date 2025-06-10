<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/5/2019
 * Time: 4:12 PM
 */
?>
<div style="width:100% ;padding: 10px 0px 0px 0px;border-radius: 15px;    margin-top: 15px;text-align: center;background-color: whitesmoke " class="shipping-rate-fedex">
    <h3>Estimated Shipping Charges</h3>
    <?php
    if ( $FedExShippingRates->HighestSeverity == 'FAILURE' || !isset($FedExShippingRates->RateReplyDetails) ){
        ?>
        <div class="col-md-12">
            <h3>
                FedEx Rates :
                <p style="font-size: 12px">
                    <?=$FedExShippingRates->Notifications->Message?>
                </p>
            </h3>
        </div>
    <?php
    }
    else if ($FedExShippingRates->HighestSeverity == 'WARNING' || $FedExShippingRates->HighestSeverity == 'SUCCESS') {
        ?>
        <!--<form class="floating-labels m-t-40">-->
            <div class="row">
                <div class="form-group m-b-40 col-md-12">
                    <div class="fedex-selected-shipping-type">
                        <?php
                        $DeliveryDate=$FedExShippingRates->RateReplyDetails->DeliveryTimestamp;
                        $dateDel= date('l m/d', strtotime($DeliveryDate));
                        $timeDel = date(' h:i A', strtotime($DeliveryDate));
                        ?>
                        <table align="center" style="width: 50%;">
                            <tbody>
                            <tr style="font-weight: bold;font-size: small;">
                                <td>Shipping Charges : </td>
                                <td style="color: #6ba03a;"><?=$FedExShippingRates->RateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Currency?>
                                    <?=$FedExShippingRates->RateReplyDetails->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount?></td>
                            </tr>
                            <tr style="font-weight: bold;font-size: small;">
                                <td>Estimate Delivery Date : </td>
                                <td ><?=$dateDel.' by '.$timeDel?></td>
                            </tr>
                            </tbody>
                        </table>
                        <?php
/*                        foreach ( $FedExShippingRates->RateReplyDetails as $val ):
                            $ServiceName = (isset($val->ServiceDescription->Description)) ? $val->ServiceDescription->Description : '';
                            $ServiceType = $val->ServiceType;
                            $CommitDate = (isset($val->CommitDetails->CommitTimestamp)) ? $val->CommitDetails->CommitTimestamp : 'No Commit Time';
                            $TotalNetChargeWithDutiesAndTaxes = $val->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Amount;
                            $TotalNetChargeWithDutiesAndTaxes .= $val->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetChargeWithDutiesAndTaxes->Currency;
                            */?><!--
                            <option value="<?/*=$ServiceType*/?>" <?/*= ($ServiceType=='FEDEX_2_DAY' || $ServiceType=='FEDEX_GROUND') ? 'selected' : 'disabled' */?>>
                                <b><?/*=$ServiceName*/?></b> / Commit Date : <?/*=$CommitDate*/?> / Total Charges : <?/*=$TotalNetChargeWithDutiesAndTaxes*/?>
                            </option>
                            --><?php
/*                        endforeach;
                        */?>
                    </div>

                </div>


                <div class="form-group m-b-40 col-md-12">
                    <button type="button" class="btn btn-success fedex-shipment-confirm" data-order-items="<?=$OrderItemIds?>" data-courier-id="<?=$CourierId?>">Click Here To Ship Now</button>
                </div>
            </div>
        <!--</form>-->
    <?php
    }else{
        ?>
        <div class="col-md-12">
            <h3>Unable to connect with FedEx</h3>
        </div>
    <?php
    }
    ?>
</div>