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

                <div class="col-sm-6 sm-6-shipping-rates">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-scale-balance"> WT (grams) *</small>
                            </span>
                        </div>
                        <input type="number" min="1" value="<?= isset($dimensions['weight_lb_part']) ? $dimensions['weight_lb_part']:1; ?>" required id="package_weight" name="package_weight" class="form-control" placeholder="Package Weight">
                    </div>
                    <br>
                </div>
                <br>
            </div>

            <div class="row">

                <!--<div class="col-sm-1">
                </div>-->
                <div class="col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-cash"> Charges to collect</small>
                            </span>
                        </div>
                        <input type="number" min="0" value="<?= isset($order->order_total) ? $order->order_total:0; ?>" required name="shipping_charges" class="form-control" placeholder="Charges">
                        <input type="hidden" value="<?= $courier->id ?>" name="courier">
                    </div>
                    <br>
                </div>


            </div>

            <!--<div class="row">
                <div class="col-sm-4">
                    <div class="input-group">
                        <a class="btn btn-success btn-sm btn-block get-shipping-rates-btn">Get shipping rates</a>
                    </div>
                </div>
            </div>-->
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
