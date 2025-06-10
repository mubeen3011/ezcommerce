<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 11/28/2019
 * Time: 3:41 PM
 */
/*echo '<pre>';
print_r($AddressValidation);
print_r($customerInfo);
print_r($warehouseInfo);
die;*/
?>
    <div class="card" style="margin-bottom:0px">
        <div class="card-header" id="headingOne" style="background-color: white;">
            <h2 class="mb-0">
                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#address_collapse" aria-expanded="true" aria-controls="collapseOne">
                    Address
                </button>
                <?php
                if ( isset($AddressValidation->AddressResults->State) && ($AddressValidation->AddressResults->State=='STANDARDIZED' || $AddressValidation->AddressResults->State=='STANDARDIZED' )  ){
                    ?>
                    <button type="button" class="btn btn-success btn-circle pull-right btn-sm"><i class="fa fa-check"></i> </button>
                <?php
                }else if ( $AddressValidation->HighestSeverity=='ERROR' && $AddressValidation->Notifications->Code=='1000' ){
                    ?>
                    <button type="button" class="btn btn-danger btn-circle pull-right btn-sm"><i class="fa fa-times"></i> </button>
                <?php
                }
                else if ( $AddressValidation->HighestSeverity=='SUCCESS' && isset($AddressValidation->AddressResults->State) && $AddressValidation->AddressResults->State == 'RAW' ){
                    ?>
                    <button type="button" class="btn btn-danger btn-circle pull-right btn-sm"><i class="fa fa-times"></i> </button>
                    <?php
                }
                else if ( $AddressValidation->HighestSeverity=='SUCCESS' && isset($AddressValidation->AddressResults->State) && $AddressValidation->AddressResults->State == 'NORMALIZED' ){
                    ?>
                    <button type="button" class="btn btn-danger btn-circle pull-right btn-sm"><i class="fa fa-times"></i> </button>
                    <?php
                }
                ?>

            </h2>
        </div>

        <div id="address_collapse" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample" style="">
            <div class="card-body">

                <!---------------->
                <div class="row">
                    <?=Yii::$app->controller->renderPartial('../sales/_render-partial-shipment/fedex/ValidateAddress',['AddressValidation'=>$AddressValidation,'customerInfo'=>$customerInfo,'warehouseInfo'=>$warehouseInfo]);?>
                </div>



                <!---------------->
            </div>
        </div>
    </div>
    <div class="card" style="margin-bottom:0px">
        <div class="card-header" id="headingOne" style="background-color: white;">
            <h2 class="mb-0">
                <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#customer_address" aria-expanded="false" aria-controls="collapseOne">
                    Order Detail
                </button>
            </h2>
        </div>

        <div id="customer_address" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample" style="">
            <div class="card-body" align="center">

                <!---------------->
                <div class="row">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Item SKU</th>
                            <th>Item status</th>
                            <th>ORDER #</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($OrderItems as $ItemDetail){
                            ?>
                            <tr>
                                <td><?=$ItemDetail['item_sku']?></td>
                                <td><?=$ItemDetail['item_status']?></td>
                                <td><?=$order_number?></td>
                            </tr>
                        <?php
                        }
                        ?>

                        </tbody>
                    </table>
                </div>



                <!---------------->
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:0px">
        <div class="card-header" id="headingOne" style="background-color: white;">
            <h2 class="mb-0">
                <button class="btn btn-link collapsed fedex-get-rates-accordian" type="button" data-toggle="collapse" data-target="#ship_packages" aria-expanded="false" aria-controls="collapseOne">
                    Shipping Rates
                </button>
            </h2>
        </div>

        <div id="ship_packages" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample" style="">
            <div class="card-body">

                <!---------------->
                <div class="row">
                    <table class="table fedex-tbl-order-items">
                        <thead>
                        <tr style="text-align: center;">
                            <th colspan="6"><h3><b>Order Items</b></h3></th>
                        </tr>
                        <tr>
                            <th>Sku</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Paid Price</th>
                            <th>SubTotal</th>
                            <th class="fedex-weight-cl-th" style="display: none;">Weight (LB)</th>
                            <th class="fedex-weight-cl-th" style="display: none;">Weight (OZ)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($OrderItems as $Key=>$Detail){
                            ?>
                            <tr>
                                <td><?=$Detail['item_sku']?></td>
                                <td><?=$Detail['quantity']?></td>
                                <td><?=$Detail['price']?></td>
                                <td><?=$Detail['paid_price']?></td>
                                <td><?=floatval($Detail['paid_price']*$Detail['quantity'])?></td>
                                <td class="fedex-weight-cl-td" style="display: none;">
                                    <div class="form-group">
                                        <input type="number" class="form-control fedex-package-items-weight" name="fedex-weight-lbs[]" value="0">
                                    </div>
                                </td>
                                <td class="fedex-weight-cl-td" style="display: none;">
                                    <div class="form-group">
                                        <input type="number" class="form-control fedex-package-items-weight-once" name="fedex-weight-once[]" value="12">
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>

                        </tbody>
                    </table>
                    <?=Yii::$app->controller->renderPartial('../sales/_render-partial-shipment/fedex/package-details',['AddressValidation'=>$AddressValidation,'ItemsCount'=>count($OrderItems),'customerInfo'=>$customerInfo]);?>
                </div>



                <!---------------->
            </div>
        </div>
    </div>



<input type="hidden" class="fedex-order-item-ids" value="<?=$OrderItemIds?>">
<input type="hidden" class="courier-id" value="<?=$CourierId?>">
<input type="hidden" class="warehouse-id" value="<?=$WarehouseId?>">
<input type="hidden" class="order-id" value="<?=$OrderId?>">
<input type="hidden" class="channel-id" value="<?=$channel_id?>">
<input type="hidden" class="marketplace" value="<?=$marketplace?>" />
<input type="hidden" class="channel_order_id" value="<?=$OrderDetails->order_id?>" />


