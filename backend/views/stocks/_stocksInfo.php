<?php
if(count($stocks)>0):
    foreach ($stocks as $pd) :?>
        <?php
        if( !isset( $pd['isis_sku'] ) || $pd['isis_sku']=='' ){
            continue;
        }
        ?>
        <tr>

            <td class="tg-kr94"><?= $pd['isis_sku'] ?></td>
            <td class="tg-kr94"><?= $pd['name'] ?></td>
            <td class="tg-kr94"><?= $pd['selling_status'] ?></td>
            <!--<td class="tg-kr94"><?/*= $pd['avg_sales'] */?></td>-->
            <td class="tg-kr94"><?= $pd['total_stocks'] ?></td>
            <?php
            if($pdq == 0 ){
                ?>
                <td class="tg-kr94"><?=isset($pd['philips_stocks']) ? $pd['philips_stocks'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['office_stocks']) ? $pd['office_stocks'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['manual_stock']) ? $pd['manual_stock'] : '0' ;?></td>
                <?php
            }
            ?>

            <?php
            if($pdq == 0 || $pdq == 1 || $pdq == 2 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9){
                ?>
                <td class="tg-kr94"  ><?=$pd['stocks']?></td>
                <td class="tg-kr94"  ><?=$pd['goodQty']?></td>
                <td class="tg-kr94"  ><?=$pd['damagedQty']?></td>
                <td class="tg-kr94"  ><?=$pd['allocatingQty']?></td>
                <td class="tg-kr94"  ><?=$pd['processingQty']?></td>
                <?php
            }
            if($pdq == 0  || $pdq == 3 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9){
                ?>
                <td class="tg-kr94"><?=isset($pd['fbl_stock']) ? $pd['fbl_stock'] : '0' ;?></td>
                <?php
            }
            if($pdq == 0 || $pdq == 4 || $pdq == 5 || $pdq == 6 || $pdq == 7 || $pdq == 8 || $pdq == 9){
                ?>
                <td class="tg-kr94"><?=isset($pd['fbl_99_stock']) ? $pd['fbl_99_stock'] : '0' ;?></td>
                <?php
            }if($pdq == 0 || $pdq == 5){
                ?>
                <td class="tg-kr94"><?=isset($pd['fbl_pavent_stock']) ? $pd['fbl_pavent_stock'] : '0' ;?></td>
                <?php
            }
            ?>




        </tr>
    <?php endforeach; ?>
    <?php else: ?>

    <tr><td colspan='10'>Record not found or SKU is inactive.</td></tr>
    <?php endif;?>
