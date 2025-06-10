<?php
if(isset($couriers) && !empty($couriers)) { ?>
    <ul class="list-unstyled">
        <?php  foreach ($couriers as $courier) : ?>
            <?php if (isset($courier['name']) && in_array($courier['type'],['lcs','blueex'])) : //for now only for lcs bulk shipment allowed ?>
                <li class="media">
                    <img class="d-flex mr-3" src="<?= $courier['icon'];?>" width="40" >
                    <div class="media-body">
                        <h5 class="mt-0 mb-1"><?= $courier['name'];?></h5>
                        <span><?= $courier['description'];?></span>
                        <button class="btn-rounded pull-right btn btn-sm btn-success select-courier-bulk" data-courier-id="<?= $courier['id'];?>" data-courier-type="<?= $courier['type'];?>">
                            Ship Now
                        </button>
                    </div>
                </li>
            <?php elseif ($courier['type']==null): ?>
                <li class="media">
                    <img class="d-flex mr-3" src="<?= $courier['icon'];?>" width="40" >
                    <div class="media-body">
                        <h5 class="mt-0 mb-1"><?= $courier['name'];?></h5>
                        <span style="color: darkorange;"><?= $courier['description'];?></span>
                    </div>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
<?php } elseif(isset($error) && !empty($error)) { ?>

    <div class="table-responsive">
        <table class="table color-table red-table">
            <thead>
            <tr>
                <th>Current Error >> </th>
                <th><?= $error;?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>1</td>
                <td>Items in 1 package should belong to same warehouse</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Items should have pending status</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Courier service must be assigned to warehouse</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Items must be assigned to some warehouse</td>
            </tr>
            </tbody>
        </table>
    </div>
<?php }  ?>
