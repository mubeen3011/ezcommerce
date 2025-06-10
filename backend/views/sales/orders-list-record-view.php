<div class="row">
    <div class="col-12">
        <!-- Tab panes -->

        <div class="tab-pane active table-responsive" id="home" role="tabpanel">
            <?php
                $current_filtered_status="all";
                if(isset($_GET['order_status']) && $_GET['order_status'])
                    $current_filtered_status=$_GET['order_status'];
            ?>
            <!-------table------->
            <table id="myTable" class="table table-bordered ">
                <thead>
                <tr>
                    <?php if($current_filtered_status=="pending"):?>
                     <th></th>
                    <?php endif;?>
                    <th></th>
                    <th>Order No</th>
                    <th>Order Date</th>
                    <th>Items #</th>
                    <th>Order Total</th>
                    <th>Marketplace</th>
                    <th>Status</th>
                    <th>Real Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (isset($orders) && !empty($orders)) {
                    foreach($orders as $order){
                        $order_shipment_fulfilled=array_column($order['items'],'shipment_conditions_fulfilled');
                        $order_shipment_fulfilled=array_sum($order_shipment_fulfilled);
                        ?>

                        <tr>
                            <?php if($current_filtered_status=="pending"):?>

                                <td>
                                    <?php if(isset($order['bulk_ship_status'])){ // bulk shipment processed so dont show check box?>
                                        <?php if($order['bulk_ship_status']=="failed") { ?>
                                            <a data-order-id="<?= $order['order_id_pk']?>" data-toggle="modal" href="#shipment_process_modal" class="shipment-failure-reason-btn" title="Failed to ship">
                                                <span data-toggle="tooltip" title="Failed to ship" class="fa fa-warning text-danger"></span>
                                            </a>
                                        <?php }else{  ?>
                                        <span data-toggle="tooltip" title="Processed for bulk shipment" class="fa fa-spinner fa-spin"></span>
                                        <?php } ?>
                                <?php  } else { ?>
                                        <input data-order-id="<?= $order['order_id_pk']; ?>" class="checkbox_order" type="checkbox">
                                <?php }?>
                                </td>
                            <?php endif;?>

                            <td>
                                <span data-order-id-pk='<?= $order['order_id_pk']; ?>' class="fa fa-plus show_items"></span>
                            </td>
                            <td><?= $order['order_number']; ?></td>
                            <td><?= $order['order_created_at']; ?></td>
                            <td><?= array_sum(array_column($order['items'],'item_qty')); ?></td>
                            <td><?= round($order['order_total'],2); ?></td>
                            <td><?= $order['marketplace']; ?> </td>
                            <td>
                                <h4>
                            <span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($order['order_status']);?>">
                                <?= $order['order_status']; ?>
                            </span>
                                </h4>
                            </td>
                            <td><?= $order['order_market_status'] ; ?> </td>
                            <td>
                                <?php if($permissions['shipment'] && $order_shipment_fulfilled){ ?>
                                    <a data-order-id="<?= $order['order_id_pk'];?>" data-ship-entity="order" data-item-id="" class="btn btn-outline-secondary waves-effect waves-light btn-sm  ship-now-btn" data-toggle="modal" data-target="#courier_selection_modal" data-backdrop="static">
                                        <i  title="Ship Now" class="fas fa-truck text-success"></i>
                                    </a>
                                <?php } else { ?>
                                 <!--   <a  class="btn btn-outline-secondary waves-effect waves-light btn-sm ship-now-btn">
                                        <i  class="fas fa-truck text-info"></i>
                                        <i  class="fas fa-info text-info"></i>
                                    </a>-->
                                    <a data-order-id="<?= $order['order_id_pk'];?>" data-ship-entity="order" data-item-id="" class="btn btn-outline-secondary waves-effect waves-light btn-sm ship-history-btn" data-toggle="modal" data-target="#shipment-history-modal" data-backdrop="static">
                                        <i  class="fas fa-truck text-info"></i>
                                        <i  class="fas fa-info text-info"></i>
                                    </a>
                                <?php } ?>
                                <a href="/sales/item-detail?id=<?= $order['order_id_pk']?>" class="btn btn-outline-secondary waves-effect waves-light btn-sm ship-now-btn">
                                    <i  title="Order Detail" class="fas fa-eye text-secondary"></i>
                                </a>
                            </td>
                        </tr>

                        <tr id="order-items-record-<?= $order['order_id_pk']; ?>" style="display:none">
                            <?php if(isset($order['items']) && !empty($order['items'])) { ?>

                                <td colspan="<?= ($current_filtered_status=="pending") ? "10":"9";?>">
                                    <div clas="table-responsive">
                                        <table id="myTable" class="table full-color-table full-muted-table hover-table">
                                            <thead>
                                            <tr>
                                                <th>Image</th>
                                                <th>SKU</th>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Paid</th>
                                                <th>Qty</th>
                                                <th>Status</th>
                                                <th>Real Status</th>
                                                <th>Warehouse</th>
                                                <th>Shipment</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php  foreach($order['items'] as $item) { ?>
                                                <tr>
                                                    <td>
                                                        <!--<img src="/images/no_image-copy.jpg" width="50px"/>-->
                                                        <img alt="<?= $item['item_name'];?>" src="<?= ($item['item_image'] && $item['item_image']!='x') ? $item['item_image']:'/images/no_image-copy.jpg"';?>" width="50px"/>
                                                    </td>
                                                    <td>
                                                        <?= yii\helpers\Html::a($item['item_sku'],array("products/detail","sku"=>$item['item_sku']));?>

                                                    </td>
                                                    <td><?= $item['item_name'];?></td>
                                                    <td><?= $item['item_unit_price'];?></td>
                                                    <td><?= $item['item_paid_price'];?></td>
                                                    <td><?= $item['item_qty'];?></td>
                                                    <td><?= $item['item_status'];?>
                                                    <td><?= $item['item_market_status'];?>

                                                    </td>
                                                    <td>
                                                        <?php if($permissions['warehouse_assignment']) : // if current user has permission to change?>
                                                            <select data-item-pk="<?= $item['item_id_pk'] ?>" class="form-control form-control-sm dropdown-select assign-warehouse" <?= !empty($item['fulfilled_by_warehouse']) ? "disabled":"";?>>
                                                                <option>---</option>
                                                                <?php foreach($warehouses as $warehouse) { ?>
                                                                    <option <?= $item['fulfilled_by_warehouse']==$warehouse['id'] ? "selected":""; ?> value="<?= $warehouse['id'];?>">
                                                                        <?= $warehouse['name'];?>
                                                                    </option>
                                                                <?php } ?>
                                                            </select>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if(!empty($item['tracking_number'])) { ?>
                                                            <?= $item['tracking_number'] ."<br/>" . $item['courier_type'];?>
                                                            <?php
                                                            if (!empty($item['courier_id'])){
                                                                $orderItemIdPk = $item['item_id_pk'];
                                                                if ( $item['courier_type']=='shopee-fbs' ){

                                                                    echo '<br /><a href="'.$shippingLabel[$orderItemIdPk].'" target="_blank">Print Label</a>';
                                                                }
                                                                else if ( $item['courier_type']=='lazada-fbl' ){
                                                                    ?>
                                                                    <form method="post" target="_blank" action="/sales/lazada-label">
                                                                        <input class="form-control" type="hidden" value="<?=$shippingLabel[$orderItemIdPk]?>" name="label" />
                                                                        <input type="submit" style="background-color: transparent;text-decoration: underline;border: none;color: blue;cursor: pointer;" class="submitLink" value="Print Label">
                                                                    </form>
                                                                    <?php
                                                                }
                                                                else if ( $item['courier_type']=='fedex' ){
                                                                    ?>
                                                                    <br />
                                                                    <a target="_blank" href="<?php if( isset($shippingLabel[$orderItemIdPk]) ) {
                                                                        echo $shippingLabel[$orderItemIdPk];
                                                                    }?>">
                                                                        Print Label
                                                                    </a>
                                                                    <?php
                                                                }
                                                                elseif(in_array($item['courier_type'],['ups','usps','lcs','internal']))
                                                                { ?>
                                                                    <br />
                                                                    <a target="_blank" href="/shipping-labels/<?php if( isset($shippingLabel[$orderItemIdPk]) ) {
                                                                        echo $shippingLabel[$orderItemIdPk];
                                                                    }?>"> Print Label  </a>
                                                                    &nbsp ||
                                                                    <a target="_blank" href="/sales/get-packing-slip?order_item_id=<?= $item['item_id_pk'];?>"> Print Slip</a>
                                                                    <br/>
                                                                    <?php if($item['courier_type']=="usps") { ?>
                                                                    <a class="cancel_shipping_btn" href="javascript:" data-order-id="<?= $order['order_id_pk'];?>" data-tracking-number="<?= $item['tracking_number'];?>"> Cancel / Refund</a>
                                                                    <?php } ?>
                                                            <?php  }
                                                            }
                                                            ?>
                                                        <?php }
                                                        elseif($permissions['shipment'] && $item['shipment_conditions_fulfilled']) {  ?>
                                                            <a  data-order-id="<?= $order['order_id_pk'];?>" data-ship-entity="order_item" data-item-id="<?= $item['item_id_pk']; ?>" class="btn btn-outline-secondary waves-effect waves-light btn-sm ship-now-btn" data-toggle="modal" data-target="#courier_selection_modal" data-backdrop="static">
                                                                <i data-toggle="tooltip" title="Ship Now" class="fas fa-truck text-success"></i>
                                                            </a>
                                                        <?php }
                                                        else { ?>
                                                            <button disabled class="btn btn-outline-secondary waves-effect waves-light btn-sm ship-now-btn">
                                                                <i data-toggle="tooltip" title="Not Shipable" class="fas fa-truck text-danger"></i>
                                                            </button>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>

                                </td>
                            <?php }  else { ?>
                                <td colspan="7">No items Found</td>
                            <?php } ?>
                        </tr>

                    <?php } } else { ?>
                    <tr>
                        <td colspan="8">

                            <h4 style="text-align:center;text-shadow:1px 2px 2px black;color:#90A4AE">
                                No Record Found
                            </h4>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="9">
                        <!----pagination------>
                        <?= Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,'route'=>\Yii::$app->controller->module->requestedRoute])?>
                        <!---------->
                    </td>
                </tr>
                </tfoot>
            </table>
            <!-------table------->

        </div>

    </div>

</div>