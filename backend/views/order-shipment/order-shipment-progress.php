<?php
if(isset($data) && !empty($data)): ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card" >
                <div class="card-body" >
                    <div class="table-responsive">
                        <table id="myTable" class="table table-bordered " >
                            <thead>
                            <tr>

                                <th>Order#</th>
                                <th>Created at</th>
                                <th>Shipped at</th>
                                <th>Status</th>
                                <th>Comment</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody >
                                <tr>
                                    <td><?= $order->order_number;?></td>
                                    <td><?= $order->created_at;?></td>
                                    <td><?= $data->added_at;?></td>
                                    <td><?= $data->status ;?></td>
                                    <td><?= $data->comment ;?></td>
                                    <td>
                                        <?php if($data->status=="failed") { ?>
                                            <a href="javascript:" data-id="<?= $data->id ;?>" data-action="remove" class="action_shipping_queue_btn">
                                                <span class="fa fa-trash"> Remove from queue</span>
                                            </a><br/>
                                            <a href="javascript:" data-id="<?= $data->id ;?>" data-action="retry" class="action_shipping_queue_btn">
                                                <span class="fa fa-sync"> Retry</span>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php   endif; ?>