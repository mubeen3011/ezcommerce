<?php
/**
 * Created by PhpStorm.
 * User: Abdullah
 * Date: 10/17/2019
 * Time: 4:00 PM
 */
?>
<tr class="data-sku-<?=$SkuDetail['sku_id']?>">
    <td class="tg-kr94 form-checkbox-radio ">
        <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
            <input type="checkbox" class="chk data-checkbox-<?=$SkuDetail['sku_id']?>" checked name="po-selected-skus[]" value="<?= $SkuDetail['sku_id'] ?>">
        <?php endif;?>
        <input type="hidden" class="chk" disabled checked name="SkusAlreadyIncludedInPo[]" value="<?= $SkuDetail['sku_id'] ?>">
        <input type="hidden" class="chk" checked name="SKUS[<?=$SkuDetail['sku_id']?>][sku]" value="<?= $SkuDetail['sku'] ?>">
        <input type="hidden" class="chk" checked name="SKUS[<?=$SkuDetail['sku_id']?>][parent_sku_id]" value="<?= $SkuDetail['parent_sku_id'] ?>">
        <input type="hidden" class="chk" checked name="SKUS[<?=$SkuDetail['sku_id']?>][ean]" value="<?= $SkuDetail['ean'] ?>">
    </td>
    <td class="tg-kr94 sku_td">
        &nbsp;&nbsp;<?=($variation) ? '&#x2192;&nbsp;' : ''?><?=$SkuDetail['sku']?>
        <i class="fa fa-info-circle"  data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$SkuDetail['sku_id']?>',<?=$warehouse->id?>,<?=($po!=null) ? $po->id : 'null'?>,false)" style="font-size: 15px;cursor: pointer;color: orange;"></i>
    </td>
    <td class="status_td"><?=$SkuDetail['status']?></td>
    <td class="variations_td">Single</td>
    <td class="tg-kr94 hide">
        RM <?= $SkuDetail['cost_price'] ?>
        <input type="hidden" value="<?= $SkuDetail['cost_price']; ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][cost_price]">
    </td>
    <td class="tg-kr94 threshold_td">
        <?= $SkuDetail['threshold']['t1'] ?>
        <input type="hidden" value="<?= $SkuDetail['threshold']['t1'] ?>">
        <?php
        foreach ( $SkuDetail['threshold'] as $tname => $tvalue ){
            ?>
            <input type="hidden" value="<?=$tvalue?>" name="SKUS[<?=$SkuDetail['sku_id']?>][Threshold][<?=$tname?>]">
        <?php
        }
        ?>
    </td>
    <td class="tg-kr94 transit_days_threshold_td">
        <?= $SkuDetail['threshold']['transit_days_threshold'] ?>
        <input type="hidden" value="<?= $SkuDetail['threshold']['transit_days_threshold'] ?>">
        <input type="hidden" value="<?=$tvalue?>" name="SKUS[<?=$SkuDetail['sku_id']?>][Threshold][transit_days_threshold]">
    </td>
    <td class="deals_target_td">
        <span><?=$SkuDetail['total_target_deals']?></span>
        <input type="hidden" name="SKUS[<?=$SkuDetail['sku_id']?>][total_target_deals]" value="<?=$SkuDetail['total_target_deals']?>">
        <input type="hidden" name="SKUS[<?=$SkuDetail['sku_id']?>][deals_json]" value='<?=json_encode($SkuDetail['deals_target'])?>'>
        <input type="hidden" name="SKUS[<?=$SkuDetail['sku_id']?>][status]" value='<?=$SkuDetail['status']?>'>
    </td>
    <td class="tg-kr94 current_stock_td">
        <?= $SkuDetail['current_stock'] ?>
        <input type="hidden" value="<?= $SkuDetail['current_stock']; ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][current_stock]">
    </td>
    <td class="tg-kr94 stock_in_transit_td">
        <?=$SkuDetail['stock_in_transit']?>
        <input type="hidden" value="<?= $SkuDetail['stock_in_transit'] ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][stock_in_transit]">
    </td>
    <td class="tg-kr94 suggested_order_qty_td">
        <?= $SkuDetail['suggested_order_qty']; ?>
        <input type="hidden" value="<?= $SkuDetail['suggested_order_qty']; ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][suggested_order_qty]">
    </td>
    <td class="tg-kr94" style="padding: 5px;">
        <?php
        if ($po==null)
            $readonly='';
        else
            $readonly='readonly';

        if ($po==null && !isset($SkuDetail['order_qty']))
            $orderQty = $SkuDetail['suggested_order_qty'];

        if ( isset($SkuDetail['order_qty']) )
            $orderQty = $SkuDetail['order_qty'];

        if(isset($SkuDetail['final_order_qty']))
            $finalorderQty = $SkuDetail['final_order_qty'];
        else
            $finalorderQty=$orderQty;

        ?>
        <input type="text" class="numberinput custom-css-txt form-control" <?=$readonly?> value="<?= $orderQty; ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][order_qty]">
    </td>
    <?php if (isset($po)): ?>
        <td class="tg-kr94" style="padding: 5px;">
            <input type="text" <?=(isset($po->po_status) && $po->po_status!='Draft') ? 'readonly' : ''?> onchange="UpdateFinalQty('<?=$SkuDetail['sku_id']?>',this.value,'<?=$_GET['poId']?>')" class="numberinput custom-css-txt form-control" value="<?= $finalorderQty ?>" name="SKUS[<?=$SkuDetail['sku_id']?>][final_order_qty]">
        </td>
    <?php endif; ?>
    <?php
    if ( isset($po->po_status) && $po->po_status !='Draft' ){
        ?>
        <td>
            <?=$SkuDetail['er_qty']?>
        </td>
    <?php
    }
    ?>

</tr>