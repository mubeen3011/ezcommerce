<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/5/2019
 * Time: 12:17 PM
 */
?>
<div style="width:100%;padding: 10px 0px 0px 0px;border-radius: 15px;text-align: center;background-color: white;" class="validate-address-fedex">

    <?php
    if (  isset($AddressValidation->AddressResults->State) ){

        if ( $AddressValidation->AddressResults->State=='NORMALIZED' ){
            ?>

            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger">Customer address validation failed by Fedex</div>
                </div>

            </div>
            <?php
        }
        elseif ( $AddressValidation->AddressResults->State=='RAW' ){
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger">Address Validation
                        Service does not support the country for address validation.</div>
                </div>

            </div>

            <?php
        }
        else if ($AddressValidation->AddressResults->State!='STANDARDIZED'){
            ?>
            <h3>
                <p style="font-size: 12px;color:red">
                    System unable to connect with FedEx server. Please try again later.
                </p>
            </h3>
            <?php
        }

        ?>


        <div id="address_collapse" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample" style="">
            <div class="card-body">

                <!---------------->
                <div class="row">
                    <div class="col-md-6">

                        <div class="col-md-12">
                            <div class="ribbon-vwrapper p-b-40 p-t-30 card">
                                <div class="ribbon ribbon-bookmark ribbon-vertical-l ribbon-info"><i class="fa fa-building"></i></div>
                                <p class="ribbon-content"> Sender , Warehouse</p>
                            </div>
                        </div>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Adress">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-address-card"></i>
                                    </span>
                                    </div>
                                    <input type="hidden" name="shipper_name" value="<?=$warehouseInfo->name?>" readonly="" class="form-control-sm" placeholder="Name">
                                    <input type="hidden" name="shipper_phone" value="<?=$warehouseInfo->phone?>" readonly="" class="form-control-sm" placeholder="Phone">
                                    <input type="text" name="shipper_address" value="<?=$warehouseInfo->address?>" readonly="" class="form-control-sm" placeholder="Address">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="state">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fa fa-bank"></i>
                            </span>
                                    </div>
                                    <input type="text" name="shipper_state" readonly="" value="<?=$warehouseInfo->state?>" class="form-control-sm" placeholder="State">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="City">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    </div>
                                    <input readonly="" type="text" name="shipper_city" value="<?=$warehouseInfo->city?>" class="form-control-sm" placeholder="City">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Zip">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    </div>
                                    <input readonly="" type="text" name="shipper_zip" value="<?=$warehouseInfo->zipcode?>" class="form-control-sm" placeholder="Zip">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Country">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                    </div>
                                    <input readonly="" type="text" name="shipper_country" value="<?=$warehouseInfo->country?>" class="form-control-sm" placeholder="Country">
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="col-md-6">

                        <div class="col-md-12">
                            <div class="ribbon-vwrapper p-b-40 p-t-30 card">
                                <div class="ribbon ribbon-bookmark ribbon-vertical-l ribbon-info"><i class="fa fa-user"></i></div>
                                <p class="ribbon-content">Receiver , Customer</p>
                            </div>
                        </div>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Adress">
                                        <span class="input-group-text" id="basic-addon1">
                                            <i class="fa fa-address-card"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="cust_address" value="<?=(isset($customerInfo[0]['customer_address'])) ? $customerInfo[0]['customer_address'] : '' ?>" class="form-control-sm" placeholder="Address">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="State">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fa fa-bank"></i>
                            </span>
                                    </div>
                                    <input type="text" id="cust_add_state" name="cust_state" value="<?=(isset($customerInfo[0]['customer_state'])) ? $customerInfo[0]['customer_state'] : '' ?>" class="form-control-sm" placeholder="State">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="City">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-building"></i>
                                    </span>
                                    </div>
                                    <input type="text" id="cust_add_city" name="cust_city" value="<?=(isset($customerInfo[0]['customer_city'])) ? $customerInfo[0]['customer_city'] : '' ?>" class="form-control-sm" placeholder="City">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Zip">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    </div>
                                    <input type="text" id="cust_add_zip" name="cust_zip" value="<?=(isset($customerInfo[0]['customer_postcode'])) ? $customerInfo[0]['customer_postcode'] : '' ?>" class="form-control-sm" placeholder="Zip">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">
                                    <div class="input-group-prepend" data-toggle="tooltip" title="Country">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                    </div>
                                    <input type="text" id="cust_add_country" name="cust_country" value="<?=(isset($customerInfo[0]['customer_country'])) ? $customerInfo[0]['customer_country'] : '' ?>" class="form-control-sm" placeholder="Country">
                                </div>
                            </div>

                        </div>
                        <br>
                        <div class="col-md-12">

                            <div class="col-sm-12">
                                <div class="input-group">

                                    <a class="btn btn-success btn-sm validate_customer_address">Validate Address Again</a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>



                <!---------------->
            </div>
        </div>
    <?php

    }
    ?>
</div>