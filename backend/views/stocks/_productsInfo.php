<?php
if(count($stocks)>0):
        foreach ($stocks as $k=>$pd) :
            if($k == 'last_update')
                continue;?>
            <tr>

                <td class="tg-kr94"><?= $k ?></td>
                <td class="tg-kr94">RM <?=$pd['price']?></td>
                <td class="tg-kr94"><?= isset($pd['blip_lazada_qty']) ? $pd['blip_lazada_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['909_lazada_qty']) ? $pd['909_lazada_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['deal4u_lazada_qty']) ? $pd['deal4u_lazada_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['blip_11Street_qty']) ? $pd['blip_11Street_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['909_11Street_qty']) ? $pd['909_11Street_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['blip_qty']) ? $pd['blip_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['philips_qty']) ? $pd['philips_qty'] : 'N/A' ?></td>
            </tr>
        <?php endforeach; ?>

<?php else: ?>

    <tr><td colspan='8'>Record not found.</td></tr>
<?php endif;?>
