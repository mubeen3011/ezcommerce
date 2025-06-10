<?php
if(count($stocks)>0):
    foreach ($stocks as $pdkey=>$pd) :?>
        <?php
        if ( $pd['sku_id']=='' ){
            continue;
        }
            ?>
            <tr>

                <td class="tg-kr94 zui-sticky-col"><?= $pd['sku_id'] ?></td>
                <td class="tg-kr94"><?= $pd['is_active'] == 1 ? 'Yes' : 'No'?></td>
                <td class="tg-kr94 "><?= $pd['stock_status'] ?></td>
                <td class="tg-kr94"><?=isset($pd['isis_threshold']) ? $pd['isis_threshold'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['isis_threshold_critical']) ? $pd['isis_threshold_critical'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['isis_stks']) ? $pd['isis_stks'] : '0' ;?></td>
                <!--<td class="tg-kr94"><?/*=isset($pd['isis_order_stocks']) ? $pd['isis_order_stocks'] : '0' ;*/?></td>-->
                <td class="tg-kr94"><?= $pd['blip_stock_status'] ?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_blip_threshold']) ? $pd['fbl_blip_threshold'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_blip_threshold_critical']) ? $pd['fbl_blip_threshold_critical'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['blip_stks']) ? $pd['blip_stks'] : '0' ;?></td>
                <!--<td class="tg-kr94"><?/*=isset($pd['fbl_order_stocks']) ? $pd['fbl_order_stocks'] : '0' ;*/?></td>-->
                <td class="tg-kr94"><?= $pd['f909_stock_status'] ?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_909_threshold']) ? $pd['fbl_909_threshold'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_909_threshold_critical']) ? $pd['fbl_909_threshold_critical'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['909_stks']) ? $pd['909_stks'] : '0' ;?></td>

                <td class="tg-kr94"><?= $pd['avent_stock_status'] ?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_avent_threshold']) ? $pd['fbl_avent_threshold'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_avent_threshold_critical']) ? $pd['fbl_avent_threshold_critical'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['avent_stks']) ? $pd['avent_stks'] : '0' ;?></td>
                <!--<td class="tg-kr94"><?/*=isset($pd['fbl909_order_stocks']) ? $pd['fbl909_order_stocks'] : '0' ;*/?></td>-->
            </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr><td colspan='18'>Record not found.</td></tr>
<?php endif;?>
