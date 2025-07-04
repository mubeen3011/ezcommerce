<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 2/7/2019
 * Time: 12:12 PM
 */
$SkusList=[];
?>
<div class="tab-pane <?=(isset($_GET['poId'])) ? 'active' : '' ?> <?=(isset($_GET['selected_warehouse']) && $_GET['selected_warehouse'] == 8) ? 'active' : ''?>" id="LZD-ORB">
    <form method="post" id="po_lzd_orb">
        <input type="hidden" name="category" value="<?php if (isset($_GET['category'])){echo $_GET['category'];}?>">
        <input type="hidden" name="warehouse" value="LZD-ORB">
        <?= Yii::$app->controller->renderPartial('_poinfo', ['po' => $po,'warehouse'=>8]); ?>
        <div class="content-box">
            <div class="card-header card-header-custom-css">
                <h4 class="content-box-header primary-bg">
                    <span class="float-left">PO Details</span>

                    <span class="badge label btn bg-blue-alt font-size-11 mrg10R float-right" style="color: black"></span>

                </h4>
            </div>

            <div class="content-box-wrapper">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped sticky-header">
                        <thead>
                        <tr>
                            <th class="form-checkbox-radio ">
                                <input type="checkbox" name="all-blip" checked value="all-isis" class="po_lzd_orb_chk">
                            </th>
                            <th class="tg-kr94">SKU</th>
                            <th>Type</th>
                            <th class="tg-kr94 hide">12NC</th>
                            <th class="tg-kr94 hide">Selling Status</th>
                            <th class="tg-kr94">Cost Price (ext GST)</th>
                            <th class="tg-kr94">Threshold</th>
                            <th class="tg-kr94">Deals Target</th>
                            <th class="tg-kr94">Current Stock</th>
                            <th class="tg-kr94">Stocks In-Transit</th>
                            <th class="tg-kr94">Master Carton</th>
                            <th class="tg-kr94">Suggested Order Qty</th>
                            <th class="tg-kr94">Philips Stocks</th>
                            <th class="tg-kr94">Order Qty</th>
                            <?php if ($po): ?>
                                <th class="tg-kr94">Final Qty</th>
                                <th class="tg-kr94">IO Qty</th>
                            <?php endif; ?>
                        </tr>
                        </thead>
                        <tbody class="gridData LZD-ORB-data">
                        <tr class="temp-tr-blip"></tr>
                        <?php
                        if (isset($_GET['poId']))
                        {
                            ?>
                            <?php
                            $SkusList=[];
                            $random_color=0;
                            foreach ($refine['c'] as $pds) :
                                $is_parent = 0;
                                if (count($pds)==2){
                                    $is_parent = 1;
                                }
                                $child = isset( $pds['Child'] ) ? 1 : 0;
                                $p_included=(count($pds['Parent'][0]) > 1) ? 1 : 0;
                                $style='';
                                $sql="SELECT * FROM products_relations pr
                                                INNER JOIN product_relations_skus prs ON
                                                prs.bundle_id = pr.id
                                                WHERE pr.relation_type = 'FOC' AND prs.main_sku_id = ".$pds['Parent'][0]['sid']." AND pr.end_at >= '".date('Y-m-d')."'
                                                AND pr.is_active = 1;";
                                $focSkus = \common\models\ProductsRelations::findBySql($sql)->asArray()->all();
                                if ($child==1 || !empty($focSkus)){
                                    $code=$random_color % 2;

                                    /*
                                    * CHILD CHECK LOGIC STARTS HERE
                                    */
                                    if ($code==0)
                                        $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(0).'"' ;
                                    else
                                        $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(1).'"' ;

                                    $random_color++;
                                }
                                foreach ( $pds as $pd1 ) :
                                    foreach ( $pd1 as $pd ) :
                                        $SkusList[]=$pd['sku_id'];

                                        if (isset($pod['name']) && !in_array($pd['sku_id'], $pod['name']) && !empty($pod))
                                            continue;
                                        if ( $po ){
                                            if (empty($pod)){
                                                continue;
                                            }
                                        }
                                        if( in_array($pd['sku_id'],$foc_sku_list) ){
                                            continue;
                                        }
                                        $orderQty = ($po) ? $pod['order_qty'][$pd['sku_id']][0] : $pd['fbl909_order_stocks'];
                                        $finalorderQty = ($po) ? $pod['final_qty'][$pd['sku_id']][0] : $pd['fbl909_order_stocks'];
                                        $pd['909_stks'] = ($po) ? $pod['current_stock'][$pd['sku_id']][0] : $pd['909_stks'];
                                        $pd['philips_stks'] = ($po) ? $pod['philips_stocks'][$pd['sku_id']][0] : $pd['philips_stks'];
                                        ?>
                                        <tr class="" <?=$style?>>
                                            <td class="tg-kr94 form-checkbox-radio ">
                                                <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
                                                    <input type="checkbox" class="chk4" checked name="lzd_orb_po_skus[]"
                                                           value="<?= $pd['stock_id'] ?>">
                                                <?php endif;
                                                if(($po) && ($po->po_status == 'Pending' || $po->po_status == 'Shipped') && $pod['is_finalize'][$pd['sku_id']][0] == '0' ): ?>
                                                    <a href="javascript:;" title="not included in ER" style="cursor: default"><i class="glyphicon glyphicon-remove-sign" style="color:red;font-size: 14px;"></i></a>
                                                <?php endif; ?>

                                            </td>
                                            <td class="tg-kr94"   <?=($is_parent == 1 && $pd['parent_sku_id'] == '0') ? "  data-toggle='tooltip' title='master carton'  style='border-left:4px solid blue'" : "" ?>><?= ($child == 1 && $pd['parent_sku_id'] != '0') ? "&nbsp;&nbsp;&#x2192;&nbsp;".$pd['sku_id'] : $pd['sku_id'] ?>
                                                <?php /*echo ($pd['dealNo']) ? '<span tooltip="deal">'.$pd['dealView'].'</span>' : ''; */?>
                                                <input type="hidden"
                                                       value="<?= $pd['sku_id']; ?>"
                                                       name="sku_<?= $pd['stock_id'] ?>">

                                                <!--<i class="fa fa-info-circle"  data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?/*=$pd['sku_id']*/?>','fbl-909-4')" style="font-size: 15px;cursor: pointer;color: orange;"></i>-->

                                            </td>
                                            <td>
                                                <?= ($style=='') ? 'Single' : 'Variations' ?>
                                            </td>
                                            <td class="tg-kr94 hide">
                                                <?= $pd['nc12'] ?>
                                                <input type="hidden"
                                                       value="<?= $pd['nc12']; ?>"
                                                       name="nc12_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="hide">
                                                <?= $pd['f909_stock_status'] ?>
                                            </td>
                                            <td class="tg-kr94">
                                                RM <?= $pd['cost_price'] ?>
                                                <input type="hidden" value="<?= $pd['cost_price']; ?>" name="cp_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94">
                                                N/A

                                                <input type="hidden"
                                                       value="N/A"
                                                       name="th_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td>
                                                <span class="">
                                                    N/A
                                                </span>
                                            </td>
                                            <td class="tg-kr94">
                                                N/A
                                                <input type="hidden"
                                                       value="N/A"
                                                       name="cs_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94">
                                                N/A
                                                <input type="hidden"
                                                       value="N/A"
                                                       name="si_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94">
                                                <?=isset($pd['master_cotton']) ? $pd['master_cotton'] : 0;?>
                                            </td>
                                            <td class="tg-kr94">
                                                N/A
                                                <input type="hidden"
                                                       value="N/A"
                                                       name="soq_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94">
                                                <?= $pd['philips_stks']; ?>
                                                <input type="hidden"
                                                       value="<?= $pd['philips_stks']; ?>"
                                                       name="ps_<?= $pd['stock_id'] ?>">

                                            </td>
                                            <td class="tg-kr94" style="padding: 5px">
                                                <input type="text" class="numberinput custom-css-txt form-control<?php
                                                $foc_exist=\backend\util\HelpUtil::FocItemBySku($pd['stock_id']);
                                                if (\backend\util\HelpUtil::FocItemBySku($pd['stock_id'])==1){
                                                    $bind_foc_item='foc-underneath'.$pd['stock_id'];
                                                }
                                                ?>
                                                    <?=isset($bind_foc_item) ? $bind_foc_item : '' ?>"
                                                    <?=( isset($po->po_status) && $po->po_status == 'Draft') ? 'readonly' : '';?>
                                                    <?=( isset($po->po_status) && ($po->po_status == 'Pending' || $po->po_status == 'Shipped')) ? 'readonly' : '';?>
                                                    <?php
                                                    if( $foc_exist ){
                                                        ?>
                                                        onkeyup="foc_binded_items('<?=$bind_foc_item?>',this.value)"
                                                        <?php
                                                    }
                                                    ?>
                                                       value="<?= $orderQty; ?>"
                                                       name="stock_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <?php if ($po): ?>
                                                <td class="tg-kr94" style="padding: 5px;">
                                                    <input type="text"  class="numberinput custom-css-txt form-control
                                                        <?php

                                                    $foc_exist=\backend\util\HelpUtil::FocItemBySku($pd['stock_id']);
                                                    if (\backend\util\HelpUtil::FocItemBySku($pd['stock_id'])==1){
                                                        $bind_foc_item='foc-underneath'.$pd['stock_id'];
                                                    }
                                                    ?>
                                                    <?=isset($bind_foc_item) ? $bind_foc_item : '' ?>"
                                                        <?php
                                                        if( $foc_exist ){
                                                            ?>
                                                            onkeyup="foc_binded_items('<?=$bind_foc_item?>',this.value)"
                                                            <?php
                                                        }


                                                        ?>
                                                        <?=(!$po || $po->po_status == 'Pending') ? 'readonly' : '';?>

                                                           value="<?= $finalorderQty ?>"
                                                           name="final_stock_<?= $pd['stock_id'] ?>">
                                                </td>
                                                <td>
                                                    <?= $pod['er_qty'][$pd['sku_id']][0] ?>
                                                </td>
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
                                                $ForDuplicateFocItems[$ps->sku][]=$ps->sku;
                                                $count_for_multiple_foc = count($ForDuplicateFocItems[$ps->sku])-1;
                                                //echo $count_for_multiple_foc;die;

                                                if(isset($pod['name']) && !in_array($ps->sku,$pod['name']))
                                                    continue;

                                                $nc12 = "";
                                                //                                    $nc12 = ($po) ? $pod['nc12'][$sk] : $nc12;
                                                $pdx = \common\models\ProductDetails::find()->where(['isis_sku' => trim($ps->sku)])->one();
                                                $skuId = ($pdx) ? "foc_" . $sk['child_sku_id'] : "foc_" . $sk['child_sku_id'];
                                                //$orderQty = ($po && isset($pod['order_qty'][$ps->sku][$count_for_multiple_foc])) ? $pod['order_qty'][$ps->sku][$count_for_multiple_foc] : $sk['child_quantity'];
                                                $finalorderQty = ($po && isset($pod['final_qty'][$ps->sku][$count_for_multiple_foc])) ? $pod['final_qty'][$ps->sku][$count_for_multiple_foc] : '0';
                                                $pd['philips_stks'] = ($po) ?$pod['philips_stocks'][$ps->sku][$count_for_multiple_foc] : $pd['philips_stks'];
                                                ?>
                                                <tr <?=$style?>>
                                                    <td class="tg-kr94 form-checkbox-radio ">
                                                        <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
                                                            <input type="checkbox" class="chk4" checked name="lzd_orb_po_skus[]" value="<?= $pd['sid'].'_'.$skuId ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="tg-kr94">
                                                        &#x2192;&nbsp;<?= $ps->sku ?>
                                                        <input type="hidden" value="<?= $ps->sku ?>" name="foc_sku_<?=$pd['sid']?>_<?= $skuId ?>">
                                                        <i style="color: orange" class="mdi mdi-information" data-toggle='tooltip' title="This is an FOC item"></i>
                                                    </td>
                                                    <td>
                                                        <?= ($style=='') ? 'Single' : 'FOC' ?>
                                                    </td>
                                                    <td class="tg-kr94 hide" style="padding: 5px;">
                                                        <input type="text" class=" form-control" value="<?= $nc12; ?>"
                                                               name="foc_nc12_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="hide">
                                                        <?= $pd['stock_status'] ?>
                                                    </td>
                                                    <td class="tg-kr94">
                                                        0
                                                        <input type="hidden"value="0" name="foc_cp_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_th_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_cs_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">
                                                        0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_si_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td>
                                                        <span ><?=$pd['dealNo']?></span>
                                                    </td>
                                                    <td class="tg-kr94">
                                                        0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_soq_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94"><?= $pd['philips_stks']; ?>
                                                        <input type="hidden"
                                                               value="<?= $pd['philips_stks']; ?>"
                                                               name="foc_ps_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94"  style="padding: 5px">
                                                        <input type="text" class="numberinput form-control custom-css-txt <?=$bind_foc_item?>"
                                                               value="<?= $orderQty ?>"
                                                               name="stock_<?=$pd['sid']?>_<?= $skuId ?>" readonly>
                                                    </td>
                                                    <?php if ($po): ?>
                                                        <td class="tg-kr94" style="padding: 5px;">
                                                            <input readonly type="text" class="numberinput form-control custom-css-txt <?=$bind_foc_item?>"  value="<?= $finalorderQty ?>"
                                                                   name="final_stock_<?=$pd['sid']?>_<?= $skuId ?>">
                                                        </td>
                                                        <td><?= $pod['er_qty'][$ps->sku][$count_for_multiple_foc] ?></td>
                                                    <?php endif; ?>
                                                    <input type="hidden" name="foc_parent_sku_<?= $skuId ?>"

                                                           value="<?= $pd['sid'] ?>">
                                                </tr>
                                            <?php endforeach;
                                        endif;
                                        $bind_foc_item='';
                                        ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <?php
                            if (!empty($po_bundles)){
                                /*echo '<pre>';
                                print_r($po_bundles);
                                die;*/
                                $counter=0;
                                foreach ( $po_bundles as $key=>$values ){
                                    $code=$counter % 2;
                                    $counter++;
                                    /*
                                    * CHILD CHECK LOGIC STARTS HERE
                                    */
                                    if ($code==0)
                                        $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(0).'"' ;
                                    else
                                        $style ='style="background-color: #'.\backend\util\HelpUtil::random_color(1).'"' ;
                                    ?>
                                    <?php
                                    foreach ( $values['Sku_list'] as $key=>$value ){
                                        /*echo '<pre>';
                                        print_r($value);
                                        die;*/
                                        ?>
                                        <tr <?=$style?>  <?=($key==0) ? 'class="Bundle-Div"' : '' ?> <?=($key==0) ? 'id="Bundle-'.$values['Bundle_info'][0]['id'].'"' : ''?>>
                                            <td class="tg-kr94 form-checkbox-radio ">
                                                <?php
                                                if ($key==0){
                                                    ?>
                                                    <input type="checkbox" class="chk" checked="" name="bundle[information][<?=$values['Bundle_info'][0]['id']?>][bundle_id]" value="<?=$values['Bundle_info'][0]['id']?>">
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                            <td class="tg-kr94">
                                                <?=($key==0) ? '*' : '&nbsp;&nbsp;→&nbsp' ?>
                                                <?=$value['sku']?>
                                                <input type="hidden" value="<?=$value['sku']?>" name="bundle[sku_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                                <!--<i class="fa fa-info-circle" data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?/*=$value['sku']*/?>','fbl-909-4')"
                                                   style="font-size: 15px;cursor: pointer;color: orange;">

                                                </i>-->
                                                <?php
                                                if ($key==0){
                                                    echo '<br />';
                                                    echo '<span style="font-weight:bold">Bundle Name : </span> '.$values['Bundle_info'][0]['relation_name'].'<br />';
                                                    echo '<span style="font-weight:bold">Bundle Price:</span> Rm '.$values['Bundle_info'][0]['bundle_cost'].'<br />';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?=$values['Bundle_info'][0]['relation_type']?>
                                            </td>
                                            <td class="tg-kr94 hide">
                                                <?=$value['nc12']?>
                                                <input type="hidden" value="<?=$value['nc12']?>" name="bundle[nc12_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="hide"></td>
                                            <td class="tg-kr94">
                                                RM <?=$value['cost_price']?>
                                                <input type="hidden" value="<?=$value['cost_price']?>" name="bundle[cp_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94">
                                                0
                                                <input type="hidden" value="0" name="bundle[th_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td>
                                                <br>
                                                <span>
                                                        <?=$value['extra_information']['c'][0]['dealNo']?>
                                                </span>
                                            </td>
                                            <td class="tg-kr94">
                                                0
                                                <input type="hidden" value="<?=0?>" name="bundle[cs_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94">
                                                <?php $stock_in_transit = $value['extra_information']['c'][0]['fbl909_stocks_intransit']?>
                                                <?=($stock_in_transit=='') ? 0 : $stock_in_transit?>
                                                &nbsp;
                                                <input type="hidden" value="<?=($stock_in_transit=='') ? 0 : $stock_in_transit?>" name="bundle[si_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94">
                                                <?=$value['extra_information']['c'][0]['master_cotton'];?>
                                            </td>
                                            <td class="tg-kr94">
                                                <?=$value['extra_information']['c'][0]['fbl909_order_stocks']?>
                                                <input type="hidden" value="<?=$value['extra_information']['c'][0]['fbl909_order_stocks']?>" name="bundle[soq_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94">
                                                <?=$value['extra_information']['c'][0]['philips_stks']?> <input type="hidden" value="<?=$value['extra_information']['c'][0]['philips_stks']?>" name="bundle[ps_<?=$value['bundle']?>_<?=$value['extra_information']['c'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94" style="padding: 5px;">
                                                <input type="text"
                                                       class="custom-css-txt form-control"
                                                       value="<?=$value['final_order_qty']?>"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][quantity]"' : '' ?>
                                                    <?= ($key!=0) ? 'name="bundle[stock_'.$values['Bundle_info'][0]['id'].'_'.$value['extra_information']['c'][0]['stock_id'].']"' : '' ?>
                                                    <?php
                                                    if ($values['Bundle_info'][0]['relation_type']!='VB'){
                                                        if($key==0) {
                                                            echo '';
                                                        }else{
                                                            echo 'readonly';
                                                        }
                                                    }
                                                    ?>
                                                    <?=(!$po || $po->po_status == 'Draft') ? '' : 'readonly'?>
                                                    <?=(isset($po) && $po->po_status == 'Draft') ? 'readonly' : '';?>
                                                >
                                                <input type="hidden"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][type]" ' : '' ?>
                                                    <?= ($key==0) ? 'value="'.$values['Bundle_info'][0]['relation_type'].'" ' : '' ?>
                                                >
                                                <input type="hidden"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][bundle_cost]"' : '' ?>
                                                       value="<?=$value['bundle_cost']?>"
                                                />
                                            </td>
                                            <td class="tg-kr94" style="padding: 5px;">
                                                <input type="text"
                                                    <?=( isset($po->po_status) && ($po->po_status == 'Pending' || $po->po_status == 'Shipped')) ? 'readonly' : '';?>
                                                    <?php
                                                    if ($values['Bundle_info'][0]['relation_type']!='VB'){
                                                        if($key==0) {
                                                            echo '';
                                                        }else{
                                                            echo 'readonly';
                                                        }
                                                    }
                                                    ?>
                                                       class="custom-css-txt form-control bundle-field-<?=$values['Bundle_info'][0]['id']?>"
                                                    <?=($values['Bundle_info'][0]['relation_type']=='FOC') ? 'onkeyup="foc_binded_items('.$values['Bundle_info'][0]['id'].',this.value)"' : ''?>
                                                       value="<?=$value['final_order_qty']?>"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][final_quantity]"' : '' ?>
                                                    <?= ($key!=0) ? 'name="bundle[final_'.$values['Bundle_info'][0]['id'].'_'.$value['extra_information']['c'][0]['stock_id'].']"' : '' ?>
                                                >
                                            </td>
                                            <td>
                                                <?php
                                                if ($value['er_qty']){
                                                    echo $value['er_qty'];
                                                }else{
                                                    echo 0;
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>

                                    <?php
                                }
                            }
                            ?>
                        <?php
                        }
                        ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div style="margin: 30px">
            <?php if ($po): ?>
                <input type="hidden" value="<?= $po->id ?>" name="po_id">

                <?php if ($po->po_status == 'Pending'): ?>
                    <input type="hidden" name="action" value="Force_ER"/>
                    <!-- <button type="submit" class="btn btn-danger btn-lzd-orb-po pull-right" style="margin-right: 10px"
                             value="Force_ER">Force ER Push
                     </button>-->
                <?php elseif ($po->po_status != 'Shipped'): ?>

                    <input type="submit" class="btn btn-warning btn-lzd-orb-po pull-right" style="margin-right: 10px;" name="button_clicked" value="Finalize" />
                <?php endif; ?>

                <?php if ($po->po_status == 'Draft'): ?>
                    <input type="submit" class="btn btn-info btn-lzd-orb-po pull-right" style="margin-right: 10px;" name="button_clicked" value="Save" />
                <?php endif; ?>

                <?php if ($po->po_status == 'Pending'): ?>
                    <input type="submit" class="btn btn-info btn-lzd-orb-po pull-right hide" style="margin-right: 10px;" name="button_clicked" value="Mark Shipped" />
                <?php endif; ?>

                <a href="/stocks/po-print?poid=<?= $po->id ?>&warehouse=<?= $po->po_warehouse ?>"
                   style="margin-right: 10px" class=" btn-lzd-orb-po pull-right isis_print">
                    <i style="font-size: 25px;" class="fa fa-print"></i> Print
                </a>

            <?php else: ?>
                <button type="submit" class="btn btn-info btn-lzd-orb-po pull-right initiate-po" value="Initiate PO">Initiate PO
                </button>
            <?php endif; ?>
        </div>
    </form>
    <?php if (!$po || $po->po_status == 'Draft'): ?>
        <div id="backToTheTut" data-warehouse="LZD-ORB" class="add-prd badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
            <i class="glyph-icon  icon-plus" style="font-size: 27px;"></i>
            <?php
            if (time() < 1546257992){
                ?>
                <span class="badge"><b>New</b></span>
                <?php
            }
            ?>
        </div>
        <div id="backToTheTutBundle" data-warehouse="LZD-ORB" class="add-bundle badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
            Add
            <br />Bundle
            <?php
            if (time() < 1546257992){
                ?>
                <span class="badge"><b>New</b></span>
                <?php
            }
            ?>
        </div>
    <?php endif; ?>

    <input type="hidden" id="skus_Already_in_poLZD-ORB" value="<?=implode(',',$SkusList)?>" />
    <input type="hidden" id="bundle_already_in_poLZD-ORB" value="<?=$bundle_ids?>" />
</div>

