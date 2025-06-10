<?php if(isset($order) && isset($order_items)): ?>
<div class="row" style="background:#f6f6f6;padding:4%">
        <div class="col-sm-4">
            <p><i class="fa fa-shopping-cart"> </i> Order#: <?= $order->order_number?></p>
            <p><i class="fa fa-calendar"> </i> Created at: <?= $order->order_created_at?></p>
            <p><i class="fa fa-truck"></i> Status: <?= $order->order_status?></p>
        </div>
        <div class="col-sm-4">
            <center><img alt="" src="/monster-admin/assets/images/logo-ecommerce-text.png" width="100px"/></center>
        </div>
        <div class="col-sm-4">
            <p><i class="fa fa-user"> </i> Customer Name: <?= $customer['customer_fname'];?></p>
            <p><i class="fa fa-phone"> </i> Customer Phone: <?= $customer['customer_number'];?></p>
            <p><i class="fa fa-building"></i> Customer City: <?= $customer['customer_city'];?></p>
        </div>
</div>
<table id="myTable" class="table table-bordered ">
    <thead>
    <tr>
        <th></th>
        <th>Image</th>
        <th>SKU</th>
        <th>Name</th>
        <th>Status</th>
        <th>Tracking Number</th>
        <th>Courier</th>
    </tr>
    </thead>
    <tbody>
    <?php if(isset($order_items)) {
        foreach($order_items as $item) { ?>
    <tr>
        <td>
            <span style="cursor:pointer" data-id-pk='<?= $item['id']; ?>' class="fa fa-plus show_ship_history_plus"></span>
        </td>
        <td><img alt="<?= $item['name'];?>" src="<?= ($item['image'] && $item['image']!='x') ? $item['image']:'/images/no_image-copy.jpg"';?>" width="50px"/></td>
        <td><?= $item['item_sku']; ?></td>
        <td><?= $item['name']; ?></td>
        <td>
            <?php if($item['courier']->type=="internal" && $item['item_status']=="shipped" && $item['shipping_current_status']) { ?>
                <select data-id-pk='<?= $item['id']; ?>' class="change_courier_status_manually form-control-sm">
                    <option <?= $item['item_status']=='shipped' ? "selected":"";?> disabled value="shipped">shipped</option>
                    <option value="completed">completed</option>
                    <option value="canceled">canceled</option>
                </select>
         <?php   } else {
                echo $item['item_status'];
            } ?>
        </td>
        <td><?= $item['tracking_number']; ?></td>
        <td><?= $item['courier']->name; ?></td>
    </tr>
    <tr id="item-shipment-record-<?= $item['id']; ?>" style="display:none">
        <td colspan="7">
            <!--<div>
                <span class="fa fa-file-pdf-o"> Label: <?/*= $item['shipping_label'];*/?></span>
            </div>
            <hr/>-->
            <?php foreach($item['shipping_current_status'] as $current_status) { ?>
                <div>
                    <span class="fa fa-truck"> Courier Status: <?= $current_status['courier_shipping_status'];?></span>
                      <span class="fa fa-notes" style="padding-left:15%">
                          <?php
                          if ( $current_status['packing_slip']!='' ){
                              ?>
                              <a target="_blank" href="/shipping-labels/<?= $current_status['packing_slip'];?>"> Packing Slip</a>
                              <?php
                          }else{
                              echo 'Packing Slip Not Available';
                          }
                          ?>
                      </span>
                    <?php if(in_array($item['courier']->type,['ups','usps','lcs','internal'])) { ?>
                     <span class="fa fa-notes" style="padding-left:15%">
                         <a target="_blank" href="/shipping-labels/<?= $item['shipping_label'];?>"> Label</a>
                      </span>
                    <?php } else if(in_array($item['courier']->type,['fedex','blueex'])){ ?>
                    <span class="fa fa-notes" style="padding-left:15%">
                        <a target="_blank" href="<?= $item['shipping_label'];?>"> Label</a>
                        </span>
                    <?php } ?>
                    <span class="pull-right fa fa-calendar"> Date: <?= $current_status['added_at'];?></span>
                </div>
                <hr/>
             <?php } ?>

            <?php foreach($item['shipping_history'] as $status) { ?>
                <div>
                    <span class="fa fa-truck"> Courier Status: <?= $status['courier_status'];?></span>
                    <span class="pull-right fa fa-calendar"> Date: <?= $status['added_at'];?></span>
                </div>
                <hr/>
            <?php } ?>
        </td>
    </tr>

    <?php }} ?>
    </tbody>
</table>
<?php endif; ?>
<?php if(isset($error)): ?>
    <p><?= $error;?></p>
<?php endif;?>
