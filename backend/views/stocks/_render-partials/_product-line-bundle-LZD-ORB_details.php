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
    //echo '<pre>';print_r($refine);die;

    $SkusList=[];
    foreach ($refine['b'] as $pdsKey=>$pd) :
        $code=$_POST['bundle_in_html'] % 2;

        /*
        * CHILD CHECK LOGIC STARTS HERE
        */
        if ($code==0)
            $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(0).'"' ;
        else
            $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(1).'"' ;

        /*
        * CHILD CHECK LOGIC STARTS HERE
        */
        //echo '<pre>';print_r($bundle_info);print_r($pd);die;
        $SkusList[]=$pd['sku_id'];
        if (isset($pod['name']) && !in_array($pd['sku_id'], $pod['name']) && !empty($pod))
            continue;
        $orderQty = $pd['fbl_order_stocks'];
        $pd['blip_stks'] = $pd['blip_stks'];
        $pd['philips_stks'] = $pd['philips_stks'];
        ?>
        <tr <?=$style?>  <?=($pdsKey==0) ? 'class="Bundle-Div"' : '' ?> <?=($pdsKey==0) ? 'id="Bundle-'.$bundle_info['id'].'"' : ''?>>
            <td class="tg-kr94 form-checkbox-radio ">
                <?php
                if ($pdsKey==0){
                    ?>
                    <input type="checkbox" class="chk" checked="" name="bundle[information][<?=$bundle_info['id']?>][bundle_id]" value="<?=$bundle_info['id']?>">
                    <?php
                }
                ?>
            </td>
            <td class="tg-kr94">
                <?=($pdsKey==0) ? '*' : '&nbsp;&nbsp;→&nbsp' ?>
                <?= $pd['sku_id'] ?>
                <?php echo ($pd['dealNo']) ? '<span class="label label-success">'.$pd['dealView'].'</span>' : ''; ?>
                <input type="hidden" value="<?= $pd['sku_id']; ?>" name="bundle[sku_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">

                <i class="fa fa-info-circle"  data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$pd['sku_id']?>','fbl-blip')" style="font-size: 15px;cursor: pointer;color: orange;">

                </i>
                <?php
                if ($pdsKey==0){
                    echo '<br />';
                    echo '<span style="font-weight:bold">Bundle Name : </span> '.$bundle_info['relation_name'].'<br />';
                    echo '<span style="font-weight:bold">Bundle Price:</span> Rm '.$bundle_info['bundle_cost'].'<br />';
                }
                ?>
            </td>
            <td>
                <?=$bundle_info['relation_type']?>
            </td>
            <td class="tg-kr94 hide">
                <?= $pd['nc12'] ?>
                <input type="hidden"
                       value="<?= $pd['nc12']; ?>"
                       name="bundle[nc12_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
                <!--for bundle mapping-->
            </td>
            <td class="hide"><?=$pd['stock_status']?></td>
            <td class="tg-kr94">RM <?= $pd['cost_price'] ?>
                <input type="hidden" value="<?= $pd['cost_price']; ?>" name="bundle[cp_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
            </td>
            <td class="tg-kr94">
                <?= ($pd['threshold_org'] != "") ? $pd['threshold_org'] : 0; ?>
                <input type="hidden" value="<?= ($pd['threshold_org'] != "") ? $pd['threshold_org'] : 0; ?>"
                       name="bundle[th_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]"></td>
            <td>
                <span class=""><?=$pd['dealNo']?></span>
            </td>
            <td class="tg-kr94"><?= $pd['blip_stks']; ?>
                <input type="hidden" value="<?= $pd['blip_stks']; ?>" name="bundle[cs_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
            </td>
            <td class="tg-kr94">
                <?= ( $pd['fbl_stocks_intransit'] == '' ) ? 0 : $pd['fbl_stocks_intransit'] ?>
                <input type="hidden" value="<?= ( $pd['fbl_stocks_intransit'] == '' ) ? 0 : $pd['fbl_stocks_intransit']; ?>" name="bundle[si_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
            </td>
            <td class="tg-kr94">
                <?=isset($pd['master_cotton']) ? $pd['master_cotton'] : 0;?>
            </td>
            <td class="tg-kr94">
                <?= $pd['fbl_order_stocks']; ?>
                <input type="hidden" value="<?= $pd['fbl_order_stocks']; ?>" name="bundle[soq_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
            </td>
            <td class="tg-kr94"><?= $pd['philips_stks']; ?>
                <input type="hidden" value="<?= $pd['philips_stks']; ?>" name="bundle[ps_<?=$bundle_info['id']?>_<?= $pd['stock_id'] ?>]">
            </td>
            <td class="tg-kr94">
                <input type="text"
                       class="form-control bundle-field-<?=$bundle_info['id']?>"
                    <?=($bundle_info['relation_type']=='FOC') ? 'onkeyup="foc_binded_items('.$bundle_info['id'].',this.value)"' : ''?>
                    <?php
                    if ($bundle_info['relation_type']!='VB'){
                        if($pdsKey==0) {
                            echo '';
                        }else{
                            echo 'readonly';
                        }
                    }
                    ?>
                    <?=($isPO) ? 'readonly' : ''?>
                       value="<?= ($pdsKey==0) ? $_POST['sku_quantity'] :  $_POST['sku_quantity'] * $pd['bundle_sku_quantity']; ?>"
                    <?= ($pdsKey==0) ? 'name="bundle[information]['.$bundle_info['id'].'][quantity]"' : '' ?>
                    <?= ($pdsKey!=0) ? 'name="bundle[stock_'.$bundle_info['id'].'_'.$pd['stock_id'].']"' : '' ?>
                >
                <input type="hidden"
                    <?= ($pdsKey==0) ? 'name="bundle[information]['.$bundle_info['id'].'][type]" ' : '' ?>
                    <?= ($pdsKey==0) ? 'value="'.$bundle_info['relation_type'].'" ' : '' ?>
                >
                <input type="hidden"
                    <?= ($pdsKey==0) ? 'name="bundle[information]['.$bundle_info['id'].'][bundle_cost]"' : '' ?>

                       value="<?=$bundle_info['bundle_cost']?>"
                />
            </td>
            <?php if($isPO): ?>
                <td class="tg-kr94">
                    <input type="text" class="form-control bundle-field-<?=$bundle_info['id']?>"
                        <?=($bundle_info['relation_type']=='FOC') ? 'onkeyup="foc_binded_items('.$bundle_info['id'].',this.value)"' : ''?>
                           value="<?= ($pdsKey==0) ? $_POST['sku_quantity'] :  $_POST['sku_quantity'] * $pd['bundle_sku_quantity']; ?>"
                        <?= ($pdsKey==0) ? 'name="bundle[information]['.$bundle_info['id'].'][final_quantity]"' : '' ?>
                        <?= ($pdsKey!=0) ? 'name="bundle[final_'.$bundle_info['id'].'_'.$pd['stock_id'].']"' : '' ?>
                    >
                </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>

