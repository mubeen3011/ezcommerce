<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2/19/2018
 * Time: 11:18 AM
 */

use yii\jui\AutoComplete;

if ($refine):
    ?>
    <?php
    $SkusList=[];
    foreach ($refine['b'] as $pdsKey=>$pds) :
        $is_parent = 0;
        if (count($pds)==2){
            $is_parent = 1;
        }
        $child = isset( $pds['Child'] ) ? 1 : 0;
        $style='';
        $sql="SELECT * FROM products_relations pr
                                            INNER JOIN product_relations_skus prs ON
                                            prs.bundle_id = pr.id
                                            WHERE pr.relation_type = 'FOC' AND prs.main_sku_id = ".$pds['Parent'][0]['sid']." AND pr.end_at >= '".date('Y-m-d')."'
                                            AND pr.is_active = 1;";
        $focSkus = \common\models\ProductsRelations::findBySql($sql)->asArray()->all();
        if ($child==1 || !empty($focSkus)){
            $code=$pdsKey % 2;
            /*
            * CHILD CHECK LOGIC STARTS HERE
            */
            if ($code==0)
                $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(0).'"' ;
            else
                $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(1).'"' ;
        }
        foreach ( $pds as $pd1 ) :
            foreach ( $pd1 as $pd ) :
                $SkusList[]=$pd['sku_id'];

                if (isset($pod['name']) && !in_array($pd['sku_id'], $pod['name']) && !empty($pod))
                    continue;
                $orderQty = $pd['fbl_order_stocks'];
                $pd['blip_stks'] = $pd['blip_stks'];
                $pd['philips_stks'] =  $pd['philips_stks'];
                ?>
                <tr class="" <?=$style?>>
                    <td class="tg-kr94 form-checkbox-radio ">
                        <input type="checkbox" class="chk2" checked name="blip_po_skus[]"
                               value="<?= $pd['stock_id'] ?>">
                    </td>
                    <td class="tg-kr94 sku_td"><?= $pd['sku_id'] ?>
                        <?php echo ($pd['dealNo']) ? '<span class="label label-success">'.$pd['dealView'].'</span>' : ''; ?>
                        <input type="hidden"
                               value="<?= $pd['sku_id']; ?>"
                               name="sku_<?= $pd['stock_id'] ?>">
                        <i class="fa fa-info-circle"  data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$pd['sku_id']?>','fbl-blip')" style="font-size: 15px;cursor: pointer;color: orange;">
                        </i>
                    </td>
                    <td class="status_td">
                        <?=$pd['blip_stock_status']?>
                    </td>
                    <td class="variations_td">
                        <?=($style=='') ? 'Single' : 'FOC' ?>
                    </td>
                    <td class="tg-kr94 hide"><?= $pd['nc12'] ?>
                        <input type="hidden"
                               value="<?= $pd['nc12']; ?>"
                               name="nc12_<?= $pd['stock_id'] ?>"></td>
                    <td class="hide"><?= $pd['blip_stock_status'] ?></td>
                    <td class="tg-kr94 hide">RM <?= $pd['cost_price'] ?>
                        <input type="hidden"
                               value="<?= $pd['cost_price']; ?>"
                               name="cp_<?= $pd['stock_id'] ?>"></td>
                    <td class="tg-kr94 threshold_td"><?= $pd['threshold'] ?>

                        <input type="hidden"
                               value="<?= $pd['threshold']; ?>"
                               name="th_<?= $pd['stock_id'] ?>"></td>
                    <td><span class=""><?=$pd['dealNo']?></span></td>
                    <td class="tg-kr94 current_stock_td"><?= $pd['blip_stks']; ?>
                        <input type="hidden"
                               value="<?= $pd['blip_stks']; ?>"
                               name="cs_<?= $pd['stock_id'] ?>"></td>
                    <td class="tg-kr94 stock_in_transit_td">
                        <?= ($pd['fbl_stocks_intransit']=='') ? 0 : $pd['fbl_stocks_intransit']; ?>
                        <input type="hidden"
                               value="<?= ($pd['fbl_stocks_intransit']=='') ? 0 : $pd['fbl_stocks_intransit']; ?>"
                               name="si_<?= $pd['stock_id'] ?>">
                    </td>
                    <td class="tg-kr94 master_carton_td">
                        <?=isset($pd['master_cotton']) ? $pd['master_cotton'] : 0;?>
                    </td>
                    <td class="tg-kr94 suggested_order_qty_td"><?= $pd['fbl_order_stocks']; ?>
                        <input type="hidden"
                               value="<?= $pd['fbl_order_stocks']; ?>"
                               name="soq_<?= $pd['stock_id'] ?>"></td>
                    <td class="tg-kr94 philips_stock_td"><?= $pd['philips_stks']; ?>
                        <input type="hidden"
                               value="<?= $pd['philips_stks']; ?>"
                               name="ps_<?= $pd['stock_id'] ?>">
                    </td>
                    <td class="tg-kr94"><input type="text" class="form-control"
                                               value="<?= $_POST['sku_quantity']; ?>"
                                               name="stock_<?= $pd['stock_id'] ?>"
                            <?=($isPO) ? 'readonly' : '' ?>
                        >
                    </td>
                    <?php if($isPO): ?>
                        <td class="tg-kr94"><input type="text" class="form-control"
                                                   value=""
                                                   name="final_stock_<?= $pd['stock_id'] ?>"></td>

                    <?php endif; ?>
                </tr>
                <?php
                $sql="SELECT * FROM products_relations pr
                                            INNER JOIN product_relations_skus prs ON
                                            prs.bundle_id = pr.id
                                            WHERE pr.relation_type = 'FOC' AND prs.main_sku_id = ".$pd['sid']." AND pr.end_at >= '".date('Y-m-d')."'
                                            AND pr.is_active = 1;";
                //echo $sql;die;
                $focSkus = \common\models\ProductsRelations::findBySql($sql)->asArray()->all();
                if ($focSkus):
                    foreach ($focSkus as $sk):
                        //check if sku presents in price sheet
                        $ps = \common\models\Products::find()->where(['id' => $sk['child_sku_id']])->one();
                        if(isset($pod['name']) && !in_array($ps->sku,$pod['name']))
                            continue;
                        $nc12 = "";

//                                    $nc12 = ($po) ? $pod['nc12'][$sk] : $nc12;
                        $pdx = \common\models\ProductDetails::find()->where(['isis_sku' => trim($ps->sku)])->one();
                        $skuId = ($pdx) ? $sk['child_sku_id'] : "foc_" . $sk['child_sku_id'];
                        $orderQty = ($po && isset($pod['order_qty'][$ps->sku])) ? $pod['order_qty'][$ps->sku] : $sk['child_quantity'];

                        $pd['philips_stks'] = ($po) ? $pod['philips_stocks'][$ps->sku] : $pd['philips_stks'];
                        ?>
                        <tr <?=$style?>>
                            <td class="tg-kr94 form-checkbox-radio ">
                                <input type="checkbox" class="chk2" checked name="blip_po_skus[]"
                                       value="<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><?= $pd['sku_id'] ?>
                                <?php echo ($pd['dealNo']) ? '<span class="label label-success">'.$pd['dealView'].'</span>' : ''; ?>
                                <input type="hidden"
                                       value="<?= $pd['sku_id']; ?>"
                                       name="sku_<?= $pd['stock_id'] ?>">
                            </td>
                            <td>
                                <?=($style=='') ? 'Single' : 'FOC' ?>
                            </td>
                            <td class="tg-kr94"><?= $pd['nc12'] ?>
                                <input type="hidden"
                                       value="<?= $pd['nc12']; ?>"
                                       name="nc12_<?= $pd['stock_id'] ?>"></td>
                            <td class="hide"><?= $pd['blip_stock_status'] ?></td>
                            <td class="tg-kr94 hide">RM <?= $pd['cost_price'] ?>
                                <input type="hidden"
                                       value="<?= $pd['cost_price']; ?>"
                                       name="cp_<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><?= $pd['threshold'] ?>

                                <input type="hidden"
                                       value="<?= $pd['threshold']; ?>"
                                       name="th_<?= $pd['stock_id'] ?>"></td>
                            <td><span class=""><?=$pd['dealNo']?></span></td>
                            <td class="tg-kr94"><?= ($pd['blip_stks'] + $pd['fbl_stocks_intransit']) - $pd['fbl_stocks_intransit']; ?>
                                <input type="hidden"
                                       value="<?= $pd['blip_stks']; ?>"
                                       name="cs_<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><?= $pd['fbl_stocks_intransit']; ?>
                                <input type="hidden"
                                       value="<?= $pd['fbl_stocks_intransit']; ?>"
                                       name="si_<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><?= $pd['fbl_order_stocks']; ?>
                                <input type="hidden"
                                       value="<?= $pd['fbl_order_stocks']; ?>"
                                       name="soq_<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><?= $pd['philips_stks']; ?>
                                <input type="hidden"
                                       value="<?= $pd['philips_stks']; ?>"
                                       name="ps_<?= $pd['stock_id'] ?>"></td>
                            <td class="tg-kr94"><input type="text" class="numberinput form-control"
                                                       value="<?= $pd['fbl_order_stocks']; ?>"
                                                       name="stock_<?= $pd['stock_id'] ?>"></td>
                            <?php if($isPO): ?>
                                <td class="tg-kr94"><input type="text" class="numberinput form-control"
                                                           value=""
                                                           name="final_stock_<?= $pd['stock_id'] ?>"></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach;
                endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>

<?php endif; ?>