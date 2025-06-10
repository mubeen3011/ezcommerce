<form role="form" class="form-horizontal" id="sales_filter_form">
    <div class="row filters" style="padding-bottom: 2%">
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['order_number']) ? $_GET['order_number'] :"";?>" name="order_number" class="form-control form-control-sm" placeholder="Order #">
            <input type="hidden" autocomplete="off" value="<?= isset($_GET['order_status']) ? $_GET['order_status'] :"";?>" name="order_status" class="form-control form-control-sm" placeholder="Order status">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['customer_name']) ? $_GET['customer_name'] :"";?>" name="customer_name" class="form-control form-control-sm" placeholder="Customer">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['product_name']) ? $_GET['product_name'] :"";?>" name="product_name" class="form-control form-control-sm" placeholder="Product Name">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['item_sku']) ? $_GET['item_sku'] :"";?>" name="item_sku" class="form-control form-control-sm" placeholder="Seller Sku">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" value="<?= isset($_GET['payment_method']) ? $_GET['payment_method'] :"";?>" name="payment_method" class="form-control form-control-sm" placeholder="Payment">
        </div>
        <div class="col-sm-2">
            <input type="text" autocomplete="off" pattern="[0-9/<>=]+" value="<?= isset($_GET['order_total']) ? $_GET['order_total'] :"";?>" name="order_total" class="form-control form-control-sm " placeholder="Paid Total">
        </div>

        <hr/>

        <div class="col-sm-2">
            <select class=" form-control form-control-sm" name="channel">
                <option value="">Select channel</option>
                <?php if(isset($channels) && !empty($channels)):
                    foreach($channels as $channel) { ?>
                        <option <?= (isset($_GET['channel']) && $_GET['channel']==$channel['id']) ?  "selected":"";?> value="<?= $channel['id'];?>"><?= $channel['name'];?></option>
                   <?php } endif;?>
            </select>
        </div>
        <div class="col-sm-2">
            <select class=" form-control form-control-sm" name="fulfilled_by">
                <option value="">Fullfilled by</option>
                <?php if(isset($warehouses) && !empty($warehouses)):
                    foreach($warehouses as $warehouse) {  ?>
                        <option <?= (isset($_GET['fulfilled_by']) && $_GET['fulfilled_by']==$warehouse['id']) ?  "selected":"";?> value="<?= $warehouse['id'];?>"><?= $warehouse['name'];?></option>
                    <?php } endif;?>
            </select>
        </div>
        <div class="col-sm-2">
            <select class=" form-control form-control-sm" name="order_city">
                <option value="">Select City</option>
                <?php if(isset($order_cities) && !empty($order_cities)):
                    foreach($order_cities as $city) {  ?>
                        <option <?= (isset($_GET['order_city']) && $_GET['order_city']==$city['city']) ?  "selected":"";?> value="<?= $city['city'];?>"><?= $city['city'];?></option>
                    <?php } endif;?>
            </select>
<!--            <input type="text" autocomplete="off"  value="--><?//= isset($_GET['order_city']) ? $_GET['order_city'] :"";?><!--" name="order_city" class="form-control form-control-sm " placeholder="City">-->
        </div>

        <div class="col-sm-2">
            <input type="text" value="<?= isset($_GET['order_created_at']) ? $_GET['order_created_at']:"";?>" name="order_created_at" autocomplete="off" class="form-control form-control-sm input-daterange-datepicker" placeholder="Order Created">
        </div>
        <div class="col-sm-2">
            <select class=" form-control form-control-sm" name="coupon_code">
                <option value="">Select Coupon</option>
                <?php if(isset($coupon_codes) && !empty($coupon_codes)):
                    foreach($coupon_codes as $coupon) { if(!$coupon['coupon_code']) continue;  ?>
                        <option <?= (isset($_GET['coupon_code']) && $_GET['coupon_code']==$coupon['coupon_code']) ?  "selected":"";?> value="<?= $coupon['coupon_code'];?>"><?= $coupon['coupon_code'];?></option>
                    <?php } endif;?>
            </select>
        </div>
        <div class="col-sm-2">
            <button class="btn btn-sm btn-secondary btn-block"> Search</button>
            <a id="bulk_ship_btn" data-toggle="modal" href="#courier_bulk_selection_modal" style="display:none" class="btn btn-sm btn-warning btn-block"><span class="fa fa-ship"> Ship Bulk </span></a>
        </div>
    </div>
</form>
