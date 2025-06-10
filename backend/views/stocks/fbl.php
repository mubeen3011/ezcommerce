<?php

$this->title = 'Products Stocks FBL ';
$this->params['breadcrumbs'][] = $this->title;

?>
<span class="badge text-info font-size-10">
    <img src="/theme1/images/icons/synchronize.png" style="width: 15px;">
    Last Synchronize at: <?=$pdlist[0]['last_update']?>
</span>
<div class="pricing-index stk-tbl">
    <table class="table table-striped table-bordered nowrap " cellspacing="2" cellpadding="2" id="table">
        <thead>
        <tr>
            <th class="tg-kr94">SKUs</th>
            <th class="tg-kr94" colspan="7">Stocks</th>
        </tr>
        <tr>
            <th class="tg-kr94"></th>
            <th class="tg-kr94">Blip-FBL</th>
            <th class="tg-kr94">909-FBL</th>
        </tr>
        <tr>
            <th class="tg-kr94"></th>
            <th class="tg-kr94">Blip-FBL</th>
            <th class="tg-kr94">909-FBL</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <th class="tg-kr94">Stock</th>
            <th class="tg-kr94">Blip-FBL</th>
            <th class="tg-kr94">909-FBL</th>
        </tr>
        </tfoot>
        <tbody>
        <?php
        foreach ($pdlist as $pd) :?>
            <tr>

                <td class="tg-kr94"><?= $pd['isis_sku'] ?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_stock']) ? $pd['fbl_stock'] : '0' ;?></td>
                <td class="tg-kr94"><?=isset($pd['fbl_99_stock']) ? $pd['fbl_99_stock'] : '0' ;?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!--<script type="text/javascript">
    setTimeout("location.reload(true);", 900000);
</script>-->