<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 12/18/2018
 * Time: 1:52 PM
 */
use backend\util\HelpUtil;
/*echo '<pre>';
print_r($refine);
die;*/
?>
<div class="tab-pane <?=(isset($_GET['poId'])) ? 'active' : '' ?> <?=(isset($_GET['selected_warehouse']) && $_GET['selected_warehouse'] == 1) ? 'active' : ''?>" id="isis">
    <form id="po_isis" method="post" class="form-horizontal">
        <div>
            <input type="hidden" name="category" value="<?php if (isset($_GET['category'])){echo $_GET['category'];}else if (isset($po->po_category)){ echo $po->po_category; }?>">
            <input type="hidden" name="warehouse" value="isis">
            <?= Yii::$app->controller->renderPartial('_poinfo', ['po' => $po,'warehouse'=>1,'isis_'=>true]); ?>
            <div class="content-box">
                <div class="card-header card-header-custom-css">
                    <h4 class="content-box-header primary-bg">
                        <span class="float-left">PO Details</span>
                        <?php if($status == 'Shipped'):?>
                            <!--<div class="alert alert-info col-md-3 text-center offset-md-3"><p>* SKU's reflecting to other active POs</p></div>-->
                            <div class="jq-toast-wrap bottom-right sku-nofitication">
                                <div class="jq-toast-single jq-has-icon jq-icon-info" style="text-align: left; display: block;">
                                                        <span class="jq-toast-loader jq-toast-loaded" style="-webkit-transition: width 2.6s ease-in;-o-transition: width 2.6s ease-in;transition: width 2.6s ease-in;background-color: #ff6849;">

                                                        </span>
                                    <span class="close-jq-toast-single">
                                                            ×
                                                        </span>
                                    <h2 class="jq-toast-heading">
                                        Notification
                                    </h2>
                                    * SKU's reflecting to other active POs
                                </div>
                            </div>
                        <?php endif; ?>
                        <span class="badge label btn bg-blue-alt font-size-11 mrg10R float-right" style="color: black"></span>
                    </h4>
                </div>


                <div class="content-box-wrapper">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped sticky-header">
                            <thead>
                            <tr>
                                <th class="form-checkbox-radio ">
                                    <input type="checkbox" name="all-isis" checked value="all-isis" class="po_isis_chk">
                                </th>
                                <th class="tg-kr94">SKU <br /> <input type="text" id="sku_filter" class="form-control" placeholder="Search Sku"/> </th>
                                <th>Status <br /> <input type="text" id="status_filter" class="form-control" placeholder="Search Status"></th>
                                <th>Type <br /> <input type="text" id="variations_filter" class="form-control" placeholder="Search Variation"></th>
                                <th class="tg-kr94 hide">12NC</th>
                                <th class="tg-kr94 hide">Selling Status</th>
                                <th class="tg-kr94 hide" style="width: 100px;">Cost Price <br /> (no GST)</th>
                                <th class="tg-kr94">Threshold <br/><input type="text" id="threshold_filter" class="form-control" placeholder="Search Threshold"></th>
                                <th class="tg-kr94">Deals Target</th>
                                <th class="tg-kr94">Current Stock <br /><input type="text" id="current_stock_filter" class="form-control" placeholder="Search Current Stock"></th>
                                <th class="tg-kr94">Stocks In-Transit<br /><input type="text" id="stock_in_transit_filter" class="form-control" placeholder="Search SIT"></th>
                                <th class="tg-kr94">Master Carton<br /><input type="text" id="master_carton_filter" class="form-control" placeholder="Search Master Carton"></th>
                                <th class="tg-kr94">Suggested <br /> Order Qty<br /><input type="text" id="suggested_order_qty_filter" class="form-control" placeholder="Search SOQ"></th>
                                <th class="tg-kr94">Philips Stocks<br /><input type="text" id="philips_stocks_filter" class="form-control" placeholder="Search Philips stock"></th>
                                <th class="tg-kr94">Order Qty</th>
                                <?php if ($po): ?>
                                    <th class="tg-kr94">Final Qty</th>
                                    <th class="tg-kr94">ER Qty</th>
                                <?php endif; ?>
                            </tr>
                            </thead>

                            <tbody class="gridData isis-data">
                            <tr class="temp-tr-isis"></tr>
                            <?php
                            //echo '<pre>';print_r($refine);die;
                            $random_color=0;
                            $SkusList=[];
                            foreach ($refine['a'] as $pdsKey=>$pds) :
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
                                if (!empty($focSkus)){
                                    //continue;
                                }
                                if ($child==1 || !empty($focSkus)){
                                    $code=$random_color % 2;

                                    /*
                                    * CHILD CHECK LOGIC STARTS HERE
                                    */
                                    if ($code==0)
                                        $style ='style="background-color: #'.HelpUtil::random_color(0).'"' ;
                                    else
                                        $style ='style="background-color: #'.HelpUtil::random_color(1).'"' ;

                                    $random_color++;
                                }

                                foreach ( $pds as $pd1 ) :

                                    foreach ( $pd1 as $pd ) :

                                        $SkusList[]=$pd['sku_id'];
                                        if (isset($pod['name']) && !in_array($pd['sku_id'], $pod['name']) && !empty($pod))
                                            continue;
                                        //echo '<pre>';print_r($po);die;
                                        if ( $po ){
                                            if (empty($pod)){
                                                continue;
                                            }
                                        }
                                        if( in_array($pd['sku_id'],$foc_sku_list) ){
                                            //continue;
                                        }
                                        $orderQty = ($po) ? $pod['order_qty'][$pd['sku_id']][0] : $pd['isis_order_stocks'];
                                        $finalorderQty = ($po) ? $pod['final_qty'][$pd['sku_id']][0] : $pd['isis_order_stocks'];
                                        $pd['isis_stks'] = ($po) ? $pod['current_stock'][$pd['sku_id']][0] : $pd['isis_stks'];
                                        $pd['philips_stks'] = ($po) ? $pod['philips_stocks'][$pd['sku_id']][0] : $pd['philips_stks'];

                                        ?>
                                        <tr class="" <?=$style?>>
                                            <td class="tg-kr94 form-checkbox-radio ">
                                                <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
                                                    <input type="checkbox" class="chk" checked name="isis_po_skus[]"
                                                           value="<?= $pd['stock_id'] ?>">
                                                <?php endif;
                                                if(($po) && ($po->po_status == 'Pending' || $po->po_status == 'Shipped') && $pod['is_finalize'][$pd['sku_id']][0] == '0' ): ?>
                                                    <a href="javascript:;" title="not included in ER" style="cursor: default"><i class="glyphicon glyphicon-remove-sign" style="color:red;font-size: 14px;"></i></a>
                                                <?php endif; ?>
                                            </td>
                                            <td class="tg-kr94 sku_td" <?=($is_parent == 1 && $pd['parent_sku_id'] == '0') ? "  data-toggle='tooltip' title='master carton'  style='border-left:4px solid blue'" : "" ?>>
                                                <?= ($child == 1 && $pd['parent_sku_id'] != '0') ? "&nbsp;&nbsp;&#x2192;&nbsp;".$pd['sku_id'] : $pd['sku_id'] ?>
                                                <?php /*echo ($pd['dealNo']) ? '<span tooltip="deal">'.$pd['dealView'].'</span>' : ''; */?>
                                                <input type="hidden"
                                                       value="<?= $pd['sku_id']; ?>"
                                                       name="sku_<?= $pd['stock_id'] ?>">
                                                <i class="fa fa-info-circle"  data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$pd['sku_id']?>','isis')" style="font-size: 15px;cursor: pointer;color: orange;"></i>
                                                <?= ($pd['philips_stks'] == '0') ? " <i style=\"color: red\" class=\"mdi mdi-information \" data-toggle='tooltip' title=\"We don't have phillips sku stock\"></i>" : "" ?>
                                            </td>
                                            <td class="status_td">
                                                <?=$pd['stock_status']?>
                                            </td>
                                            <td class="variations_td">
                                                <?= ($style=='') ? 'Single' : 'Variations' ?>
                                            </td>
                                            <td class="tg-kr94 hide">
                                                <?= $pd['nc12'] ?>
                                                <input type="hidden"
                                                       value="<?= $pd['nc12']; ?>"
                                                       name="nc12_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="hide"><?= $pd['stock_status'] ?></td>
                                            <td class="tg-kr94 hide">
                                                RM <?= $pd['cost_price'] ?>
                                                <input type="hidden"
                                                       value="<?= $pd['cost_price']; ?>"
                                                       name="cp_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94 threshold_td">
                                                <?= ($pd['threshold_org'] != "") ? $pd['threshold_org'] : 0 ?>

                                                <input type="hidden"
                                                       value="<?= ($pd['threshold_org'] != "") ? $pd['threshold_org'] : 0; ?>"
                                                       name="th_<?= $pd['stock_id'] ?>"></td>
                                            <td>
                                                <span ><?=$pd['dealNo']?></span></td>
                                            <td class="tg-kr94 current_stock_td"><?= ($pd['isis_stks'] + $pd['stocks_intransit']) - $pd['stocks_intransit']; ?>
                                                <input type="hidden"
                                                       value="<?= $pd['isis_stks']; ?>"
                                                       name="cs_<?= $pd['stock_id'] ?>"></td>
                                            <td class="tg-kr94 stock_in_transit_td">
                                                <?= ($pd['stocks_intransit']=='') ? 0 : $pd['stocks_intransit'] ?>
                                                &nbsp;<?= ($status == 'Shipped') ? '*' : '' ?>
                                                <input type="hidden"
                                                       value="<?= ($pd['stocks_intransit']=='') ? 0 : $pd['stocks_intransit'] ?>"
                                                       name="si_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <td class="tg-kr94 master_carton_td">
                                                <?=isset($pd['master_cotton']) ? $pd['master_cotton'] : 0;?>
                                            </td>
                                            <td class="tg-kr94 suggested_order_qty_td"><?= $pd['isis_order_stocks']; ?>
                                                <input type="hidden"
                                                       value="<?= $pd['isis_order_stocks']; ?>"
                                                       name="soq_<?= $pd['stock_id'] ?>"></td>
                                            <td class="tg-kr94 philips_stock_td"><?= $pd['philips_stks']; ?>
                                                <input type="hidden"
                                                       value="<?= $pd['philips_stks']; ?>"
                                                       name="ps_<?= $pd['stock_id'] ?>">


                                            </td>
                                            <td class="tg-kr94" style="padding: 5px;">
                                                <input type="text" class="numberinput custom-css-txt form-control
                                                <?php

                                                $foc_exist=HelpUtil::FocItemBySku($pd['stock_id']);
                                                if (HelpUtil::FocItemBySku($pd['stock_id'])==1){
                                                    $bind_foc_item='foc-underneath'.$pd['stock_id'];
                                                }
                                                ?>
                                                <?=isset($bind_foc_item) ? $bind_foc_item : '' ?>"
                                                    <?=( isset($po->po_status) &&  ($po->po_status == 'Pending' || $po->po_status == 'Shipped') ) ? 'readonly' : '';?>
                                                       <?php
                                                       if( $foc_exist ){
                                                           ?>
                                                           onkeyup="foc_binded_items('<?=$bind_foc_item?>',this.value)"
                                                        <?php
                                                       }
                                                       (!$po || $po->po_status == 'Draft') ? '' : 'readonly';
                                                       ?>
                                                        <?=( isset($po->po_status) && $po->po_status == 'Draft') ? 'readonly' : '';?>
                                                                                             value="<?= $orderQty; ?>"
                                                                                             name="stock_<?= $pd['stock_id'] ?>">
                                            </td>
                                            <?php if ($po): ?>
                                                <td class="tg-kr94" style="padding: 5px;">
                                                    <input type="text" onchange="UpdateFinalQty('<?=$pd['sku_id']?>',this.value,'<?=$_GET['poId']?>')" class="numberinput custom-css-txt form-control
                                                    <?php

                                                    $foc_exist=HelpUtil::FocItemBySku($pd['stock_id']);
                                                    if (HelpUtil::FocItemBySku($pd['stock_id'])==1){
                                                        $bind_foc_item='foc-underneath'.$pd['stock_id'];
                                                    }
                                                    ?>

                                                <?=isset($bind_foc_item) ? $bind_foc_item : '' ?>"
                                                        <?=( isset($po->po_status) &&  ($po->po_status == 'Pending' || $po->po_status == 'Shipped') ) ? 'readonly' : '';?>
                                                        <?php
                                                        if( $foc_exist ){
                                                            ?>
                                                            onkeyup="foc_binded_items('<?=$bind_foc_item?>',this.value)"
                                                            <?php
                                                        }
                                                        (!$po || $po->po_status == 'Draft') ? '' : 'readonly';
                                                        ( !$po || $po->po_status == 'Finalize') ? '' : 'readonly';
                                                        ?>
                                                                                                 value="<?= $finalorderQty ?>"
                                                                                                 name="final_stock_<?= $pd['stock_id'] ?>">
                                                </td>
                                                <td>
                                                    <?= $pod['er_qty'][$pd['sku_id']][0] ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php

                                        //$focSkus = \common\models\FocSkus::find()->where(['sku_id' => $pd['sid']])->asArray()->all();
                                        /*
                                         * SKU FOC LOGIC START HERE
                                         */
                                        $sql="SELECT * FROM products_relations pr
                                            INNER JOIN product_relations_skus prs ON
                                            prs.bundle_id = pr.id
                                            WHERE pr.relation_type = 'FOC' AND prs.main_sku_id = ".$pd['sid']." AND pr.end_at >= '".date('Y-m-d')."'
                                            AND pr.is_active = 1;";
                                        //echo $sql;die;
                                        //echo $sql;die;
                                        $focSkus = \common\models\ProductsRelations::findBySql($sql)->asArray()->all();
                                        if (!empty($focSkus)){
                                            continue;
                                        }
                                        //echo '<pre>';print_r($focSkus);die;
                                        //$ForDuplicateFocItems = [];
                                        if ($focSkus):
                                            //echo '<pre>';print_r($pdsKey);print_r($pds);print_r($pd);die;

                                            foreach ($focSkus as $sk):
                                                //check if sku presents in price sheet
                                                //echo '<pre>';print_r($sk);die;

                                                $ps = \common\models\Products::find()->where(['id' => $sk['child_sku_id']])->one();
                                            $ForDuplicateFocItems[$ps->sku][]=$ps->sku;
                                            $count_for_multiple_foc = count($ForDuplicateFocItems[$ps->sku])-1;
                                            //echo $count_for_multiple_foc;die;
                                                if(isset($pod['name']) && !in_array($ps->sku,$pod['name']))
                                                    continue;
                                                //$ForDuplicateFocItems[]
                                                $nc12 = "";
                                                //$nc12 = ($po) ? $pod['nc12'][$sk] : $nc12;
                                                $pdx = \common\models\ProductDetails::find()->where(['isis_sku' => trim($ps->sku)])->one();
                                                //echo '<pre>';print_r($pdx);die;
                                                $skuId = ($pdx) ? "foc_" . $sk['child_sku_id'] : "foc_" . $sk['child_sku_id'];
                                                //$orderQty = ($po && isset($pod['order_qty'][$ps->sku][$count_for_multiple_foc])) ? $pod['order_qty'][$ps->sku][$count_for_multiple_foc] : $sk['child_quantity'];
                                                $fianlorderQty = ($po && isset($pod['final_qty'][$ps->sku][$count_for_multiple_foc])) ? $pod['final_qty'][$ps->sku][$count_for_multiple_foc] : $sk['child_quantity'];

                                                $pd['philips_stks'] = ($po) ? $pod['philips_stocks'][$ps->sku][$count_for_multiple_foc] : $pd['philips_stks'];
                                                ?>
                                                <tr <?=$style?>>
                                                    <td class="tg-kr94 form-checkbox-radio ">
                                                        <?php if ($status == "" || (($po) && $po->po_status != 'Pending' && $po->po_status != 'Shipped') ): ?>
                                                            <input type="checkbox" class="chk" checked name="isis_po_skus[]"
                                                                   value="<?= $pd['sid'].'_'.$skuId ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="tg-kr94">
                                                        &#x2192;&nbsp;<?= $ps->sku ?>
                                                        <input type="hidden"
                                                               value="<?= $ps->sku ?>"
                                                               name="foc_sku_<?=$pd['sid']?>_<?= $skuId ?>">
                                                        <i style="color: orange" class="mdi mdi-information" data-toggle='tooltip' title="This is an FOC item">
                                                        </i>
                                                    </td>
                                                    <td>
                                                        <?= ($style=='') ? 'Single' : 'FOC' ?>
                                                    </td>
                                                    <td class="tg-kr94 hide" style="padding: 5px;">
                                                        <input type="text"  class=" form-control"
                                                               value="<?= $nc12; ?>"
                                                               name="foc_nc12_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="hide">
                                                        <?= $pd['stock_status'] ?>
                                                    </td>
                                                    <td class="tg-kr94 hide">0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_cp_<?=$pd['sid']?>_<?= $skuId ?>"></td>
                                                    <td class="tg-kr94">
                                                        0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_th_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">
                                                        0
                                                        <input type="hidden"
                                                               value="0"
                                                               name="foc_cs_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">
                                                        <?= ($pd['stocks_intransit']) ? 0 : $pd['stocks_intransit']; ?>
                                                        &nbsp;<?= ($status == 'Shipped') ? '*' : '' ?>
                                                        <input type="hidden"
                                                               value="<?= ($pd['stocks_intransit']) ? 0 : $pd['stocks_intransit']; ?>"
                                                               name="foc_si_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td>
                                                        <span ><?=$pd['dealNo']?></span>
                                                    </td>
                                                    <td class="tg-kr94">
                                                        <?= $pd['isis_order_stocks']; ?>
                                                        <input type="hidden"
                                                               value="<?= $pd['isis_order_stocks']; ?>"
                                                               name="foc_soq_<?=$pd['sid']?>_<?= $skuId ?>">
                                                    </td>
                                                    <td class="tg-kr94">
                                                        <?= $pd['philips_stks']; ?>
                                                        <input type="hidden" value="<?= $pd['philips_stks']; ?>" name="foc_ps_<?=$pd['sid']?>_<?= $skuId ?>">

                                                    </td>
                                                    <td class="tg-kr94" style="padding: 5px;">
                                                        <input type="text" class="numberinput custom-css-txt form-control <?=$bind_foc_item?>"
                                                                                                     value="<?= $orderQty ?>"
                                                                                                     name="stock_<?=$pd['sid']?>_<?= $skuId ?>" readonly>
                                                        <!--just to know the parent sku id i'm making hidden input-->
                                                    </td>
                                                    <?php if ($po): ?>
                                                        <td class="tg-kr94" style="padding: 5px;">
                                                            <input readonly type="text"

                                                                   class="numberinput custom-css-txt form-control <?=$bind_foc_item?>"
                                                                   value="<?= $fianlorderQty ?>"
                                                                   name="final_stock_<?=$pd['sid']?>_<?= $skuId ?>">
                                                        <td><?= $pod['er_qty'][$ps->sku][$count_for_multiple_foc] ?></td>
                                                        </td>
                                                    <?php endif; ?>
                                                    <input type="hidden" name="foc_parent_sku_<?=$pd['sid']?>_<?= $skuId ?>"
                                                           value="<?= $pd['sid'] ?>">
                                                </tr>

                                            <?php
                                            endforeach;
                                            //echo '<pre>';print_r($ForDuplicateFocItems);
                                        endif; ?>
                                    <?php
                                        $bind_foc_item='';
                                    endforeach; ?>
                                <?php endforeach; ?>
                            <?php endforeach;?>

                            <?php
                            if (!empty($po_bundles)){

                                $counter=0;
                                foreach ( $po_bundles as $key1=>$values ){
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
                                            <td class="tg-kr94 sku_td">
                                                <?=($key==0) ? '*' : '&nbsp;&nbsp;→&nbsp' ?>
                                                <?=$value['sku']?>
                                                <input type="hidden" value="<?=$value['sku']?>" name="bundle[sku_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                                <i class="fa fa-info-circle" data-toggle="modal" data-target="#extra-information-popup" onclick="ShowExtraInformation('<?=$value['sku']?>','isis')"
                                                   style="font-size: 15px;cursor: pointer;color: orange;">

                                                </i>
                                                <?php
                                                if ($key==0){
                                                    echo '<br />';
                                                    echo '<span style="font-weight:bold">Bundle Name : </span> '.$values['Bundle_info'][0]['relation_name'].'<br />';
                                                    echo '<span style="font-weight:bold">Bundle Price:</span> Rm '.$values['Bundle_info'][0]['bundle_cost'].'<br />';
                                                }
                                                ?>
                                            </td>
                                            <td class="status_td">
                                                <?=$value['extra_information']['a'][0]['stock_status']?>
                                            </td>
                                            <td class="variations_td">
                                                <?=$values['Bundle_info'][0]['relation_type']?>
                                            </td>
                                            <td class="tg-kr94 hide">
                                                <?=$value['nc12']?>
                                                <input type="hidden" value="<?=$value['nc12']?>" name="bundle[nc12_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="hide"></td>
                                            <td class="tg-kr94 hide">
                                                RM <?=$value['cost_price']?>
                                                <input type="hidden" value="<?=$value['cost_price']?>" name="bundle[cp_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94 threshold_td">
                                                <?=$value['threshold']?>
                                                <input type="hidden" value="<?=$value['threshold']?>" name="bundle[th_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td>
                                                <br>
                                                <span>
                                                    <?=$value['extra_information']['a'][0]['dealNo']?>
                                                </span>
                                            </td>
                                            <td class="tg-kr94 current_stock_td">
                                                <?=($value['current_stock'] + $value['extra_information']['a'][0]['stocks_intransit']) - $value['extra_information']['a'][0]['stocks_intransit']?>
                                                <input type="hidden" value="<?=$value['current_stock']?>" name="bundle[cs_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94 stock_in_transit_td">
                                                <?php $stock_in_transit = $value['extra_information']['a'][0]['stocks_intransit']; ?>
                                                <?=($stock_in_transit=='') ? 0 : $stock_in_transit?>&nbsp;
                                                <input type="hidden" value="<?=($stock_in_transit=='') ? 0 : $stock_in_transit?>" name="bundle[si_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94 master_carton_td">
                                                <?=$value['extra_information']['a'][0]['master_cotton'];?>
                                            </td>
                                            <td class="tg-kr94 suggested_order_qty_td">
                                                <?=$value['extra_information']['a'][0]['isis_order_stocks']?>
                                                <input type="hidden" value="<?=$value['extra_information']['a'][0]['isis_order_stocks']?>" name="bundle[soq_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94 philips_stock_td">
                                                <?=$value['extra_information']['a'][0]['philips_stks']?> <input type="hidden" value="<?=$value['extra_information']['a'][0]['philips_stks']?>" name="bundle[ps_<?=$value['bundle']?>_<?=$value['extra_information']['a'][0]['stock_id']?>]">
                                            </td>
                                            <td class="tg-kr94" style="padding: 5px;">
                                                <input type="text"
                                                       class="custom-css-txt form-control bundle-field-<?=$values['Bundle_info'][0]['id']?>"


                                                       value="<?=$value['final_order_qty']?>"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][quantity]"' : '' ?>
                                                    <?= ($key!=0) ? 'name="bundle[stock_'.$values['Bundle_info'][0]['id'].'_'.$value['extra_information']['a'][0]['stock_id'].']"' : '' ?>
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
                                                <input type="text" onchange="UpdateFinalQty('<?=$value['sku']?>',this.value,'<?=$_GET['poId']?>')"
                                                    <?php
                                                    if ($values['Bundle_info'][0]['relation_type']!='VB'){
                                                        if($key==0) {
                                                            echo '';
                                                        }else{
                                                            echo 'readonly ';
                                                        }
                                                    }
                                                    //echo '';
                                                    ?>
                                                        <?=($po->po_status == 'Pending' || $po->po_status == 'Shipped' ) ? 'readonly' : '';?>

                                                       class="custom-css-txt form-control bundle-field-<?=$values['Bundle_info'][0]['id']?>"
                                                    <?=($values['Bundle_info'][0]['relation_type']=='FOC') ? 'onkeyup="foc_binded_items('.$values['Bundle_info'][0]['id'].',this.value)"' : ''?>
                                                       value="<?=$value['final_order_qty']?>"
                                                    <?= ($key==0) ? 'name="bundle[information]['.$values['Bundle_info'][0]['id'].'][final_quantity]"' : '' ?>
                                                    <?= ($key!=0) ? 'name="bundle[final_'.$values['Bundle_info'][0]['id'].'_'.$value['extra_information']['a'][0]['stock_id'].']"' : '' ?>
                                                >
                                            </td>
                                            <td><?=$value['er_qty']?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>

                            <?php
                                }
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <div style="margin: 30px">

            <input type="hidden" value="" name="isis_po_skuss" class="isis_po_skus">
            <?php if ($po): ?>
                <input type="hidden" value="<?= $po->id ?>" name="po_id">


                <?php if ($po->po_status == 'Pending'): ?>
                    <input type="hidden" name="action" value="Force_ER"/>
                    <!-- <button type="submit" class="btn btn-danger btn-isis-po pull-right" style="margin-right: 10px"
                             value="Force_ER">Force ER Push
                     </button>-->
                <?php elseif ($po->po_status != 'Shipped' && $po->po_status != 'Partial Shipped'): ?>

                    <input type="submit" class="btn btn-warning btn-isis-po pull-right" name="button_clicked" value="Finalize" />
                <?php endif; ?>

                <?php if ($po->po_status == 'Draft'): ?>
                    <input type="submit" class="btn btn-info btn-isis-po pull-right" style="margin-right: 10px;" name="button_clicked" value="Save" />
                <?php endif; ?>
                <a href="/stocks/po-print?poid=<?= $po->id ?>&warehouse=<?= $po->po_warehouse ?>"
                   style="margin-right: 10px" class="btn-isis-po pull-right isis_print">
                    <i style="font-size: 25px;" class="fa fa-print"></i> Print
                </a>

            <?php else: ?>
                <button type="button" class="btn btn-info btn-isis-po pull-right initiate-po" value="Initiate PO">Initiate PO
                </button>
            <?php endif; ?>
        </div>
    </form>
    <?php if (!$po || $po->po_status == 'Draft'): ?>
        <div id="backToTheTut" data-warehouse="isis" class="add-prd badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
            <i class="glyph-icon  icon-plus" style="font-size: 27px;">

            </i>
            <?php
            if (time() < 1546257992){
                ?>
                <span class="badge"><b>New</b></span>
                <?php
            }
            ?>
        </div>
        <div id="backToTheTutBundle" data-warehouse="isis" class="add-bundle badge label btn bg-blue-alt font-size-11 mrg10R float-right notification">
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



    <input type="hidden" id="skus_Already_in_poisis" value="<?=implode(',',$SkusList)?>" />
    <input type="hidden" id="bundle_already_in_poisis" value="<?=$bundle_ids?>" />
</div>