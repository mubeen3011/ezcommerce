<style>
    .ui-datepicker{
        z-index:2000 !important; /** to display calendar over modal not below**/
    }
    </style>
<div class="card">
    <div class="card-header" id="headingThree">
        <h2 class="mb-0">
            <button class="btn btn-link collapsed shipping_rates_accordian" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
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
                        <input type="number"  value="<?= isset($dimensions['width']) ? $dimensions['width']:""; ?>" min="1" id="package_width" name="package_width" class="form-control-sm dm_inputs" placeholder="Width">
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
                                <small class="mdi mdi-basket"> Package</small>
                            </span>
                        </div>
                        <select class="form-control" id="package_type_dd" name="package_type">
                            <option value="Package">Package</option>
                            <option value="Unknown">Unknown</option>
                            <option value="Postcard">Postcard</option>
                            <option value="Letter">Letter</option>
                            <option value="Large Envelope or Flat">Large Envelope or Flat</option>
                            <option value="Thick Envelope">Thick Envelope</option>
                            <option value="Small Flat Rate Box">Small Flat Rate Box</option>
                            <option value="Flat Rate Box">Flat Rate Box</option>
                            <option value="Large Flat Rate Box">Large Flat Rate Box</option>
                            <option value="Flat Rate Envelope">Flat Rate Envelope</option>
                            <option value="Flat Rate Padded Envelope">Flat Rate Padded Envelope</option>
                            <option value="Large Package">Large Package</option>
                            <option value="Oversized Package">Oversized Package</option>
                            <option value="Regional Rate Box A">Regional Rate Box A</option>
                            <option value="Regional Rate Box B">Regional Rate Box B</option>
                            <option value="Regional Rate Box C">Regional Rate Box C</option>
                            <option value="Legal Flat Rate Envelope">Legal Flat Rate Envelope</option>
                        </select>
                    </div>
                    <br>
                </div>
                <div class="col-sm-1">
                </div>
                <div class="col-sm-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-calendar"> Ship @ *</small>
                            </span>
                        </div>
                        <input autocomplete="off" type="text" name="ship_date" class="form-control ship_date_input" id="datepicker-autoclose" placeholder="mm/dd/yyyy">

                    </div>
                </div>


            </div>

            <div class="row">
                <div class="col-sm-4">
                    <div class="input-group">
                        <a class="btn btn-success btn-sm btn-block get-shipping-rates-btn">Get shipping rates</a>
                    </div>
                </div>
            </div>
            <!---- services and addons display fetched againsta package slected--->
            <div class="shipping_rates_display" style="margin-top:2%"> </div>
            <div class="row">
                <div class="col-sm-4 offset-sm-8">
                    <button type="submit" class="btn btn-sm btn-block btn-secondary pull-right btn_submit_shipment">
                        <i class="fa fa-ship"> SHIP NOW </i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
