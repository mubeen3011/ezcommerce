<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 4/28/2020
 * Time: 11:04 AM
 */

?>

<tr style="background-color: #FFD480" class="Bundle-Div" id="Bundle-<?=$SkuDetail['Bundle_Information']['bundle_id']?>">
    <td class="tg-kr94 form-checkbox-radio ">
        <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
            <input type="checkbox" onclick="BundleAddRemove('<?=$SkuDetail['Bundle_Information']['bundle_id']?>',this)" class="chk data-checkbox-<?=$SkuDetail['Bundle_Parent_Sku']['id']?> bundle-checkbox-<?=$SkuDetail['Bundle_Information']['bundle_id']?>" checked name="po-bundle-selected-skus[]" value="<?=$SkuDetail['Bundle_Information']['bundle_id']?>">
        <?php endif;?>
        <input type="hidden" class="chk" disabled checked name="BundleAlreadyIncludedInPo[]" value="<?=$SkuDetail['Bundle_Information']['bundle_id']?>">
    </td>
    <td class="tg-kr94 sku_td">
        * <?=$SkuDetail['Bundle_Parent_Sku']['sku']?>
        <input type="hidden" value="<?=$SkuDetail['Bundle_Parent_Sku']['sku']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][sku]">
        <i class="fa fa-info-circle" data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$SkuDetail['Bundle_Information']['sku_id']?>',<?=$warehouse->id?>,<?=($po!=null) ? $po->id : 'null'?>,<?=$SkuDetail['Bundle_Information']['bundle_id']?>)"
           style="font-size: 15px;cursor: pointer;color: orange;"></i>
        <br>
        <span style="font-weight:bold">Bundle Name : </span>
        <?=$SkuDetail['Bundle_Information']['bundle_name']?> <br>
        <span style="font-weight:bold">Bundle Price:</span>
        <?=$SkuDetail['Bundle_Information']['bundle_cost']?><br>
    </td>
    <td class="status_td">
        <?=$SkuDetail['Bundle_Information']['status']?>
    </td>
    <td class="variations_td">
        <?=$SkuDetail['Bundle_Information']['bundle_type']?>
    </td>
    <td class="tg-kr94 hide">
        <?=$SkuDetail['Bundle_Parent_Sku']['ean']?>
        <input type="hidden" value="<?=$SkuDetail['Bundle_Parent_Sku']['ean']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][ean]">
        <input type="hidden" value="<?=$SkuDetail['Bundle_Information']['bundle_id']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][bundle_id]">
        <!--for bundle mapping-->
    </td>

    <td class="tg-kr94 hide">RM <?=$SkuDetail['Bundle_Information']['bundle_cost']?> <input type="hidden" value="<?=$SkuDetail['Bundle_Information']['bundle_cost']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][bundle_cost_price]">
    </td>
    <td class="tg-kr94 threshold_td">
        <?=$SkuDetail['Bundle_Information']['threshold']['t1']?>
        <?php
        foreach ( $SkuDetail['Bundle_Information']['threshold'] as $tname => $tvalue ){
            ?>
            <input type="hidden" value="<?=$tvalue?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][Threshold][<?=$tname?>]">
            <?php
        }
        ?>
    </td>
    <td>
        <span class=""> <?=$SkuDetail['Bundle_Information']['threshold']['transit_days_threshold']?> </span>
        <input type="hidden" value="<?=$SkuDetail['Bundle_Information']['threshold']['transit_days_threshold']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][Threshold][transit_days_threshold]">
    </td>
    <td>
        <span class="">
            <?=$SkuDetail['Bundle_Information']['total_target_deals']?>
        </span>
        <input type="hidden" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][total_target_deals]" value="<?=$SkuDetail['Bundle_Information']['total_target_deals']?>">
        <input type="hidden" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][deals_json]" value='<?=json_encode($SkuDetail['Bundle_Information']['deals_target'])?>'>
        <input type="hidden" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][status]" value='<?=$SkuDetail['Bundle_Information']['status']?>'>
    </td>
    <td class="tg-kr94 current_stock_td">
        <?=$SkuDetail['Bundle_Information']['current_stock']?>
        <input type="hidden" value="<?=$SkuDetail['Bundle_Information']['current_stock']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][current_stock]">
    </td>
    <td class="tg-kr94 stock_in_transit_td">
        <?=$SkuDetail['Bundle_Information']['stock_in_transit']?>
        <input type="hidden" value="0" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][stock_in_transit]">
    </td>
    <!--<td class="tg-kr94 master_carton_td">
        <?/*=$SkuDetail['Bundle_Parent_Sku']['master_cotton']*/?>
    </td>-->
    <td class="tg-kr94 suggested_order_qty_td"><?=$SkuDetail['Bundle_Information']['suggested_order_qty']?>
        <input type="hidden" value="<?=$SkuDetail['Bundle_Information']['suggested_order_qty']?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][suggested_order_qty]">
    </td>
    <td class="tg-kr94">
        <?php
        if ($po==null)
            $readonly='';
        else
            $readonly='readonly';

        if ($po==null && !isset($SkuDetail['Bundle_Information']['order_qty']))
            $orderQty = $SkuDetail['Bundle_Information']['suggested_order_qty'];

        if ( isset($SkuDetail['Bundle_Information']['order_qty']) )
            $orderQty = $SkuDetail['Bundle_Information']['order_qty'];

        if(isset($SkuDetail['Bundle_Information']['final_order_qty']))
            $finalorderQty = $SkuDetail['Bundle_Information']['final_order_qty'];

        ?>
        <input type="text" class="form-control bundle-field-42" <?=$readonly?> value="<?=$orderQty?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][order_qty]" />
        <input type="hidden" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][type]" value="<?=$SkuDetail['Bundle_Information']['bundle_type']?>">
        <input type="hidden" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][bundle_cost]" value="<?=$SkuDetail['Bundle_Information']['bundle_cost']?>">
    </td>
    <?php if (isset($po)): ?>
        <td class="tg-kr94" style="padding: 5px;">
            <input type="text" <?=(isset($po->po_status) && $po->po_status!='Draft') ? 'readonly' : ''?> onchange="UpdateFinalQty('<?=$SkuDetail['Bundle_Information']['sku_id']?>',this.value,'<?=$_GET['poId']?>')" class="numberinput custom-css-txt form-control" value="<?= $finalorderQty ?>" name="bundle[<?=$SkuDetail['Bundle_Information']['bundle_id']?>][final_order_qty]">
        </td>
    <?php endif; ?>
</tr>

<?php
//if ( isset($SkuDetail['Bundle_Child_Sku']) ){
    foreach ( $SkuDetail['Bundle_Child_Sku'] as $bundleChildDetail ){
?>
<tr style="background-color: #FFD480">
    <td class="tg-kr94 form-checkbox-radio ">
        <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
            <input type="checkbox" readonly class="chk bundle-child-skus data-checkbox-<?=$bundleChildDetail['id']?> bundle-checkbox-<?=$SkuDetail['Bundle_Information']['bundle_id']?>" checked name="po-bundle-selected-skus[]" value="<?=$bundleChildDetail['id']?>">
        <?php endif;?>
    </td>
    <td class="tg-kr94 sku_td">
        &nbsp;&nbsp;→&nbsp; <?=$bundleChildDetail['sku']?>
    </td>
    <td class="status_td"></td>
    <td class="variations_td"><?=$SkuDetail['Bundle_Information']['bundle_type']?></td>
    <td class="tg-kr94 hide">0 <input type="hidden" value="0"></td>
    <td class="tg-kr94 hide">RM 0.00 <input type="hidden" value="0.00" ></td>
    <td class="tg-kr94 threshold_td">0 <input type="hidden" value="0"></td>
    <td><span class="">0</span></td>
    <td class="tg-kr94 current_stock_td">0 <input type="hidden" value="0"></td>
    <td class="tg-kr94 stock_in_transit_td">0 <input type="hidden" value="0"></td>
    <td class="tg-kr94 master_carton_td">0</td>
    <td class="tg-kr94 suggested_order_qty_td">0 <input type="hidden" value="0"></td>
    <td class="tg-kr94"><input type="hidden"><input type="hidden" value="316.00"></td>
    <?php
    if ($po){
        ?>
        <td></td>
        <?php
    }
    ?>
</tr>
<?php
}
?>