<div class="card">
    <div class="card-header" id="headingTwo">
        <h2 class="mb-0">
            <button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                Order Detail
            </button>

        </h2>
    </div>
    <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
        <div class="card-body">
            <?php if(isset($channel) && !empty($channel)){ ?>
                <input type="hidden" name="channel_id" value="<?= $channel->id;?>">
                <?php } ?>
            <?php if(isset($order) && !empty($order)) { ?>


                <table class="table table-striped">
                    <thead>
                    <tr colspan>
                        <th>Item SKU</th>
                        <th>Item status</th>
                        <th>ORDER #</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($items as $item) { ?>
                        <tr>
                            <td><?= $item['item_sku']; ?></td>
                            <td><?= $item['item_status']; ?></td>
                            <td>
                                <?= $order->order_number;?>
                                <input type="hidden" name="order_number" value="<?= $order->order_number;?>">
                                <input type="hidden" name="order_id" value="<?= $order->id;?>">
                                <input type="hidden" name="order_item_pk[]" value="<?= $item['id'];?>">
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>
    </div>
</div>