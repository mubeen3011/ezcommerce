<?php
if(isset($data) && !empty($data)): ?>

        <div class="row">
            <div class="col-lg-12">
                <div class="card" >
                    <div class="card-body" >
                        <button class="btn btn-success btn-sm pull-right generate_sheet_submit" >Generate</button>
                        <div class="table-responsive">
                            <table id="myTable" class="table table-bordered " >
                                <thead>
                                <tr>

                                    <th><input type="checkbox" class="check_all_sheets"/></th>
                                    <th>Order#</th>
                                    <th>Created at</th>
                                    <th>Shipped at</th>
                                    <th>Customer</th>
                                </tr>
                                </thead>
                                <tbody >
                                 <?php  foreach($data as $order):
                                     $shipped_items_date=explode('@!',$order['items_shipped_at']);
                                     $shipped_items_date=array_unique($shipped_items_date);
                                     ?>
                                     <tr>
                                         <td><input data-order-id="<?= $order['id'];?>" type="checkbox" class="load_sheet_checkbox"/></td>
                                         <td><?= $order['order_number'];?></td>
                                         <td><?= $order['order_created_at'];?></td>
                                         <td>
                                             <?php foreach($shipped_items_date as $shipped):
                                                    echo $shipped ."<br/>";
                                              endforeach;?>
                                         </td>
                                         <td><?= $order['cust_fname'] ." ".$order['cust_lname'] ;?></td>
                                     </tr>
                                 <?php   endforeach; ?>
                                </tbody>
                            </table>
                            <button class="btn btn-success btn-sm generate_sheet_submit pull-right">Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php   endif; ?>