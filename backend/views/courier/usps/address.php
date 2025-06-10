<div class="card">
    <div class="card-header" id="headingOne">
        <h2 class="mb-0">
            <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#address_collapse" aria-expanded="true" aria-controls="collapseOne">
                Address
            </button>
            <?php if(isset($status)) {  ?>
                <button type="button" class="btn btn-<?= $status=="success" ? 'success':'danger'; ?> btn-circle pull-right btn-sm"><i class="fa fa-<?= $status=="success" ? 'check':'times'; ?>"></i> </button>
            <?php } ?>
        </h2>
    </div>

    <div id="address_collapse" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
        <div class="card-body">

            <!---------------->
            <?php if(isset($error) && !empty($error)) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-danger"><?= $msg;?></div>
                    </div>

                </div>
            <?php } ?>
            <?php if(isset($info) && !empty($info)) { ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info"><?= $msg;?></div>
                    </div>

                </div>
            <?php } ?>
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
                                <input type="hidden" name="shipper_name" value="<?= isset($warehouse_address->display_name) ? $warehouse_address->display_name:"";?>" readonly class="form-control-sm"  placeholder="Name">
                                <input type="hidden" name="shipper_full_address" value="<?= isset($warehouse_address->full_address) ? $warehouse_address->full_address:"";?>" readonly class="form-control-sm"  placeholder="Full address">
                                <input type="hidden" name="shipper_phone" value="<?= isset($warehouse_address->phone) ? $warehouse_address->phone:"";?>" readonly class="form-control-sm"  placeholder="Phone">
                                <input type="text" name="shipper_address" value="<?= isset($warehouse_address->address) ? $warehouse_address->address:"";?>" readonly class="form-control-sm"  placeholder="Address">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="state">
                            <span class="input-group-text" id="basic-addon1" >
                                <i class="fa fa-bank"></i>
                            </span>
                                </div>
                                <input type="text" name="shipper_state" readonly  value="<?= isset($warehouse_address->state) ? $warehouse_address->state:"";?>" class="form-control-sm" placeholder="State">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="City">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-building"></i>
                                    </span>
                                </div>
                                <input readonly type="text" name="shipper_city" value="<?= isset($warehouse_address->city) ? $warehouse_address->city:"";?>" class="form-control-sm"  placeholder="City">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="Zip">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                </div>
                                <input readonly type="text"  name="shipper_zip" value="<?= isset($warehouse_address->zipcode) ? $warehouse_address->zipcode:"";?>" class="form-control-sm"  placeholder="Zip">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="Country">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                </div>
                                <input readonly type="text" name="shipper_country" value="<?= isset($warehouse_address->country) ? $warehouse_address->country:"";?>" class="form-control-sm"  placeholder="Country">
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
                                <input type="hidden" name="cust_name" value="<?= isset($cust_address['fname']) ? $cust_address['fname']:"";?>" class="form-control-sm"  placeholder="Address">
                                <input type="hidden" name="cust_phone" value="<?= isset($cust_address['phone']) ? $cust_address['phone']:"";?>" class="form-control-sm"  placeholder="Address">
                                <input type="text" name="cust_address" value="<?= isset($cust_address['address']) ? $cust_address['address']:"";?>" class="form-control-sm"  placeholder="Address">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="State">
                            <span class="input-group-text" id="basic-addon1">
                                <i class="fa fa-bank"></i>
                            </span>
                                </div>
                                <input type="text" id="cust_add_state" name="cust_state" value="<?= isset($cust_address['state']) ? $cust_address['state']:"";?>" class="form-control-sm" placeholder="State">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="City">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-building"></i>
                                    </span>
                                </div>
                                <input type="text" id="cust_add_city" name="cust_city"  value="<?= isset($cust_address['city']) ? $cust_address['city']:"";?>" class="form-control-sm"  placeholder="City">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="Zip">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                </div>
                                <input type="text" id="cust_add_zip" name="cust_zip"  value="<?= isset($cust_address['zip']) ? $cust_address['zip']:"";?>" class="form-control-sm"  placeholder="Zip">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend" data-toggle="tooltip" title="Country">
                                    <span class="input-group-text" id="basic-addon1">
                                        <i class="fa fa-flag"></i>
                                    </span>
                                </div>
                                <input type="text" id="cust_add_country" name="cust_country"  value="<?= isset($cust_address['country']) ? $cust_address['country']:"";?>" class="form-control-sm"  placeholder="Country">
                            </div>
                        </div>

                    </div>
                    <br/>
                    <div class="col-md-12">

                        <div class="col-sm-12">
                            <div class="input-group">

                                <a class="btn btn-success btn-sm validate_customer_address">Validate Address Again</a>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <?php if(isset($suggestions) && !empty($suggestions)) { ?>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <b>Address Suggestions:</b>
                    </div>
                    <div class="col-md-9">
                        <select class="form-control address_suggestions">
                            <?php foreach($suggestions as $k=>$suggestion) { ?>
                                <option data-att-city="<?= $suggestion['city']; ?>" data-att-state="<?= $suggestion['state']; ?>" data-att-zip="<?= $suggestion['zip']; ?>">
                                    <?= $suggestion['city']. " , " .$suggestion['state']. " , ".$suggestion['zip'] ;?>
                                </option>

                            <?php } ?>
                        </select>
                    </div>
                </div>
            <?php } ?>


            <!---------------->
        </div>
    </div>
</div>