<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/18/2018
 * Time: 11:20 AM
 */
?>
<style>
    .error-color{
        color: red;
    }
    .select2-selection__rendered{
        width: 100% !important;
    }
    .related{
        margin-top:10px;
    }
</style>
<div id="newbundleModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Bundle Header</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">

                <h3>Select Bundle Type</h3>
                <form id="product_form">
                    <select class="form-control" name="product_type_bundle" id="product_type_bundle">
                        <option>FOC</option>
                        <option>FB</option>
                        <option>VB</option>
                    </select>

                    <label class = "" for="action_id">Bundle Name</label>
                    <input class="form-control" type="text" name="bundle_name">
                    <br />
                    <label class = "" for="action_id" id="main_product_label">Main Product</label>
                    <br />
                    <select class="select2 select2-form-control" name="main_sku" id="main_sku">
                        <option></option>
                        <?php
                        foreach ( $skus_without_foc as $key=>$value ) {
                            ?>
                            <option value="<?=$value['sku']?>"><?=$value['sku']?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <br />
                    <label class = "" for="action_id" id="bundle-price-label">Bundle Price</label>
                    <input type="number" name="bundle_price" class="form-control" />
                    <br />
                    <label class = "" for="action_id">Start Date</label>
                    <input class="form-control start_date" name="start_date">
                    <br />
                    <label class = "" for="action_id">End Date</label>
                    <input class="form-control end_date" name="end_date">
                </form>
                <h3>Child Products</h3>
                <form class="form-inline" role = "form" id="child_products_form">
                    <div class="related-products">
                        <div class="related">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class = "sr-only" for="action_id">Related Product</label>
                                        <select class="child_foc_lists select2 select2-form-control pull-left" name="child_skus[]">
                                            <option></option>
                                            <?php
                                            foreach ( $foc_skus as $key=>$value ) {
                                                ?>
                                                <option value="<?=$value['sku']?>"><?=$value['sku']?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label class = "sr-only" for="action_id">Quantity</label>
                                        <input type="number" value="1" oninput="validity.valid||(value='');" onblur="if(this.value==0){this.value=1;}" class="form-control" name="Quantity[]" min="1"/>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <span class="btn-danger btn remove-me">Remove</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
                <div class="col-md-12">
                    <button id="add-more-related-products" name="add-more-related-products" class="add-more-related-products btn btn-success">+</button>
                </div>
                <div class="row">
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <p id="bundle-add-status"></p>
                    </div>
                    <div class="col-md-4"></div>
                </div>
                <!-- Button -->

            </div>
            <div class="modal-footer">
                <button class="btn btn-success add-foc-bundle">Save</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>

</div>
