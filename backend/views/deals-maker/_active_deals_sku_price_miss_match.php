<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/25/2019
 * Time: 5:06 PM
 */
$data = \backend\util\DealsUtil::GetUnMatchedDealPricesWithShop($isAdmin);
//echo '<pre>';print_r($data);die;
?>
<div class="table-responsive">
    <table class="table table-border dlm-admin-skus table table-striped table-bordered dataTable">
        <thead>
            <tr>
                <th>Deal</th>
                <th>Sku</th>
                <th>Shop</th>
                <th>Deal Price</th>
                <th>Actual Price</th>
                <th>Deal Start</th>
                <th>Deal End</th>
                <th>Cause</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($data as $r):?>
            <tr>
                <td><a href="<?='/deals-maker/update?id='.$r['deal_id']?>"><?=$r['deal_name']?></a></td>
                <td><?= $r['sku'] ?></td>
                <td><?= $r['shop_name'] ?></td>
                <td>RM <?= $r['deal_price'] ?></td>
                <td>RM <?= $r['shop_price'] ?></td>
                <td><?=$r['start_date']?></td>
                <td><?=$r['end_date']?></td>
                <td><?=$r['Error']?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
