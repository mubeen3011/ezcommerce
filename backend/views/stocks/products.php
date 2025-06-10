<?php

$this->title = 'Channels Products';
$this->params['breadcrumbs'][] = $this->title;

?>
<span class="badge text-info font-size-10">
    <img src="/theme1/images/icons/synchronize.png" style="width: 15px;">
    Last Synchronize at: <?=$products['last_update']?>
</span>
<div class="pricing-index stk-tbl">
    <table class="table table-striped table-bordered nowrap " cellspacing="2" cellpadding="2" id="table">
        <thead>
        <tr>
            <th class="tg-kr94">SKU</th>
            <th class="tg-kr94">Price</th>
            <th class="tg-kr94">Lazada Blip</th>
            <th class="tg-kr94">Lazada 909</th>
            <th class="tg-kr94">11 Street Blip</th>
            <th class="tg-kr94">11 Street 909</th>
            <th class="tg-kr94">Blip Shop</th>
            <th class="tg-kr94">Philips Shop</th>
        </tr>

        </thead>
        <tfoot>
        <tr>
            <th class="tg-kr94">SKU</th>
            <th class="tg-kr94">Price</th>
            <th class="tg-kr94">Lazada Blip</th>
            <th class="tg-kr94">Lazada 909</th>
            <th class="tg-kr94">11 Street Blip</th>
            <th class="tg-kr94">11 Street 909</th>
            <th class="tg-kr94">Blip Shop</th>
            <th class="tg-kr94">Philips Shop</th>
        </tr>
        </tfoot>
        <tbody>
        <?php
        foreach ($products as $k=>$pd) :
            if($k == 'last_update')
                continue;?>
            <tr>

                <td class="tg-kr94"><?= $k ?></td>
                <td class="tg-kr94">RM <?=$pd['price']?></td>
                <td class="tg-kr94"><?= isset($pd['blip_lazada_qty']) ? $pd['blip_lazada_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['909_lazada_qty']) ? $pd['909_lazada_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['blip_11Street_qty']) ? $pd['blip_11Street_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['909_11Street_qty']) ? $pd['909_11Street_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['blip_qty']) ? $pd['blip_qty'] : 'N/A' ?></td>
                <td class="tg-kr94"><?=  isset($pd['philips_qty']) ? $pd['philips_qty'] : 'N/A' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!--<script type="text/javascript">
    setTimeout("location.reload(true);", 900000);
</script>-->