<style>
    .ui-datepicker{
        z-index:2000 !important; /** to display calendar over modal not below**/
    }
    </style>
<div class="card">
    <div class="card-header" id="headingThree">
        <h2 class="mb-0">
            <button class="btn btn-link collapsed shipping_rates_accordian" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                Shipping Info
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
                                <small class="mdi mdi-truck-delivery"> service</small>
                            </span>
                        </div>
                        <select class="form-control" id="ups_service_type" name="service">
                            <option value="OLE">OverLand</option>
                            <option value="O">Overnight</option>
                            <option value="D">2NDDay</option>
                        </select>
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
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-truck-delivery"> Fragile</small>
                            </span>
                        </div>
                        <select class="form-control" id="ups_service_type" name="fragile">
                            <option value="No">NO</option>
                            <option value="Yes">YES</option>
                        </select>
                    </div>
                    <br>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-truck-delivery"> Insured</small>
                            </span>
                        </div>
                        <select class="form-control" id="ups_service_type" name="insurance">
                            <option value="0">NO</option>
                            <option value="1">YES</option>
                        </select>
                    </div>
                    <br>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-cash"> Order Total</small>
                            </span>
                        </div>
                        <input type="number" min="0" value="<?= isset($order->order_total) ? $order->order_total:0; ?>" required name="order_total" id="order_total_input" class="form-control" placeholder="Order total">
                        <input type="hidden" value="<?= $courier->id ?>" name="courier">
                    </div>
                    <br>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon1">
                                <small class="mdi mdi-newspaper"> </small>
                            </span>
                        </div>
                        <input type="text" name="instructions" class="form-control"  placeholder="Any instructions (optional)">

                    </div>
                </div>
            </div>

            <!--<div class="row">
                <div class="col-sm-4">
                    <div class="input-group">
                        <a class="btn btn-success btn-sm btn-block get-shipping-rates-btn">Calculate shipping rates</a>
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
