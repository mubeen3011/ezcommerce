<?php

use backend\util\HelpUtil;

$data=\backend\util\HelpUtil::getRequesterDealCount($isAdmin, $view);

?>
    <div class="table-responsive">
        <table class="table table-border dlm-admin-skus table table-striped table-bordered dataTable">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Shops</th>
                <th>Lowest Price</th>
                <th>Deal Price</th>
                <th>Target Sales</th>
                <th>Actual Sales</th>
                <th>Margins %</th>
                <th>Margins <?=Yii::$app->params['currency']?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            ?>
            <?php foreach ($data['result'] as $r):?>
                <tr>
                    <td><a target="_blank" href="/deals-maker/detail?sku=<?= $r['sku'] ?>"><?= $r['sku'] ?></a></td>
                    <td><?= $r['channel_name'] ?></td>
                    <td><?=Yii::$app->params['currency']?> <?= $r['low_price'] ?></td>
                    <td><?=Yii::$app->params['currency']?><?= $r['deal_price'] ?></td>
                    <td><?=Yii::$app->params['currency']?> <?= $r['deal_target'] * $r['deal_price'] ?></td>
                    <td><?=Yii::$app->params['currency']?> <?= $r['actual_sales'] * $r['deal_price'] ?></td>
                    <td><?= $r['deal_margin'] ?> %</td>
                    <td><?= $r['deal_margin_rm'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>


    <!--<p style="font-size: 14px;font-weight: bold">click <b><a href="/deals-maker/detail?page=1" class="dl-view">here</a></b> for detailed view</p>-->