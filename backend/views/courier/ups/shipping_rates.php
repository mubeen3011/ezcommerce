<div class="card">
    <div class="card-header" id="headingThree">
        <h2 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                Shipping Rates
            </button>
        </h2>
    </div>
    <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6 sm-6-shipping-rates">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-archive"> L x W x H (in) </small>
                            </span>
                        </div>
                        <input type="number" value="<?= isset($dimensions['length']) ? $dimensions['length']:""; ?>" name="package_length" id="package_length" min="1" class="form-control-sm dm_inputs" placeholder="Length">
                        <input type="number" value="<?= isset($dimensions['width']) ? $dimensions['width']:""; ?>"  min="1" id="package_width" name="package_width" class="form-control-sm dm_inputs" placeholder="Width">
                        <input type="number" value="<?= isset($dimensions['height']) ? $dimensions['height']:""; ?>" min="1" id="package_height"name="package_height" class="form-control-sm dm_inputs" placeholder="Height">
                    </div>
                    <br>
                </div>
                <br>

                <div class="col-sm-3 sm-3-shipping-rates">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-scale-balance"> WT (LBS) *</small>
                            </span>
                        </div>
                        <input type="number" min="1" value="<?= isset($dimensions['weight_lb_part']) ? $dimensions['weight_lb_part']:0; ?>" required id="package_weight" name="package_weight" class="form-control-sm wt_input" placeholder="Package Weight">
                    </div>
                    <br>
                </div>
                <div class="col-sm-3 sm-3-shipping-rates">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small > OZ *</small>
                            </span>
                        </div>
                        <input type="number" min="0" value="<?= isset($dimensions['weight_oz_part']) ? $dimensions['weight_oz_part']:0; ?>" required id="package_weight_oz" name="package_weight_oz" class="form-control-sm wt_input" placeholder="OZ weight">
                    </div>
                    <br>
                </div>
                <br>
            </div>
            <div class="row">
                <div class="col-sm-7">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-truck-delivery"> Service</small>
                            </span>
                        </div>
                        <select class="form-control" id="ups_service_type" name="service">
                            <option value="02">UPS 2nd Day Air</option>
                            <option value="59">UPS 2nd Day Air A.M.</option>
                            <option value="12">UPS 3 Day Select</option>
                            <option value="03">UPS Ground</option>
                            <option value="01">UPS Next Day Air</option>
                            <option value="14">UPS Next Day Air Early</option>
                            <option value="13">UPS Next Day Air Saver</option>
                            <option value="75">UPS Heavy Goods </option>
                        </select>
                    </div>
                    <br>
                </div>
                <br>
                <div class="col-sm-1"></div>
                <div class="col-sm-4">
                    <div class="input-group">
                        <a class="btn btn-success btn-sm btn-block get-shipping-rates-btn">Get shipping rates</a>
                    </div>
                    <br>
                </div>
                <br>
            </div>
            <div class="row">
                <div class="col-sm-7">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-basket"> Package</small>
                            </span>
                        </div>
                        <select class="form-control" id="package_type_dd" name="package_type">
                            <option value="02">Customer Supplied Package</option>
                            <option value="03">Tube 04 = PAK </option>
                            <option value="21">UPS Express Box </option>
                            <option value="24">UPS 25KG Box</option>
                            <option value="25">UPS 10KG Box</option>
                            <option value="30">Pallet </option>
                            <option value="2a">Small Express Box</option>
                            <option value="57">Parcels </option>
                            <option value="59">First Class </option>
                            <option value="60">Priority  </option>
                            <option value="67">Standard Flat  </option>
                        </select>
                    </div>
                    <br>
                </div>
                <div class="col-sm-1">
                </div>
                <div class="col-sm-4">
                    <button type="submit" class="btn btn-sm btn-block btn-secondary pull-right btn_submit_shipment">
                        <i class="fa fa-ship"> SHIP NOW </i>
                    </button>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12 shipping_rates_display"> </div>
            </div>
        </div>
    </div>
</div>