<tbody id="generic-tbody">
<?php
$serial=1;
$counter=0;
$RouteId= \Yii::$app->controller->module->requestedRoute;
$roleId = Yii::$app->user->identity->role_id;
//$DealTrEdit= ($RouteId=='deals-maker/generic-info' || 'deals-maker/generic-info-filter' || 'deals-maker/generic-info-sort' ) ? 'style="cursor:pointer"' : '';
//$DealTronClick = ($RouteId=='deals-maker/generic-info') ? 'onClick=EditDeal()' : '';
$DealsRoute=0;
$DealTrEdit='';

if ($RouteId=='deals-maker/generic-info' || $RouteId=='deals-maker/generic-info-filter' || $RouteId=='deals-maker/generic-info-sort')
{
    $DealTrEdit = '';
    $DealsRoute=1;
}
if ($RouteId=='bundles/generic-info' || $RouteId=='bundles/generic-info-filter' || $RouteId=='bundles/generic-info-sort')
{
    $DealTrEdit = 'style="cursor:pointer"';
    $Modal_Id = 1;
    //$Modal='onclick="showBundlePopup()"';
}
if( ($RouteId == 'cost-price/generic-info' || $RouteId == 'cost-price/generic-info-sort' || $RouteId == 'cost-price/generic-info-filter'))
{
   $dd_categories=\common\models\Category::find()->where(['is_active'=>1])->asArray()->all();
   $dd_categories=\backend\util\HelpUtil::make_child_parent_tree($dd_categories);
   $dd_categories =\backend\util\HelpUtil::dropdown_3_level($dd_categories);
   //print_r($dd_categories); die();
}
//echo '<pre>';echo $RouteId;print_r($okk);die;
//echo '<pre>';echo $RouteId;print_r($stocks);die;
if( count($stocks)>0 ){
    /*echo '<pre>';
    print_r($stocks);
    die;*/
    foreach ($stocks as $pd_key=>$pd){
        if ($counter % 2 != 0) {
            $class='odd';
        }else{
            $class='even';
        }
        ?>
        <tr class="<?=$class?>" <?=$DealTrEdit?>
        >
            <?php
            if(!is_array($pd)){
                continue;
            }
            ?>

            <?php
            foreach ($pd as $key=>$valz){
                if ( isset($Modal_Id) && $Modal_Id==1 && $key=='id' ){
                    continue;
                }
                if( ($RouteId == 'cost-price/generic-info' || $RouteId == 'cost-price/generic-info-sort' || $RouteId == 'cost-price/generic-info-filter')  && ($key == 'category_name'))
                { ?>

                    <td class="tg-kr94"  title="category name">

                        <select data-sku="<?= $pd['sku'] ?>" class="cp-cat-dd form-control">
                            <option value="0" <?= !($pd['category_name']) ? 'selected' : '' ?> >No Category</option>
                            <?php if($dd_categories) {
                                foreach ($dd_categories as $dd_category) { ?>
                                    <option value="<?= $dd_category['key']?>" <?= $pd['category_name'] == $dd_category['value'] ? 'selected' : '' ?> ><?= $dd_category['space'].$dd_category['value']?></option>
                             <?php }} ?>

                        </select>

                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='deals-maker/generic-info' || $RouteId=='deals-maker/generic-info-filter' || $RouteId=='deals-maker/generic-info-sort') &&
                    $key == 'name' && $DealsRoute == 1 && $_POST['pagename']!="SkuPerformance") {
                    //echo $key;
                    //echo $RouteId;die;
                    ?>
                    <td class="tg-kr94">
                        <a target="_blank" href="/deals-maker/update?id=<?=$pd['dm_id']?>">
                            <?=$valz?>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if ($key=='po_warehouseId'){
                    ?>
                    <td class="hide tg-kr94">
                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='reports/generic-info' || $RouteId=='reports/generic-info-filter' || $RouteId=='reports/generic-info-sort')
                    && $key=='api_response'){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        $api_response = json_decode($pd['api_response']);
                        if ( isset($api_response->detail[0]->message) )
                            echo $api_response->detail[0]->message;
                        if ( isset($api_response->msg) )
                            echo $api_response->msg;

                        ?>
                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='reports/generic-info' || $RouteId=='reports/generic-info-filter' || $RouteId=='reports/generic-info-sort')
                    && $key=='deal_name'){
                    ?>
                    <td class="tg-kr94">

                        <a href="/deals-maker/update?id=<?=$pd['deal_id']?>"><?=$valz?></a>
                        <?php
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='stocks/generic-info' || $RouteId=='stocks/generic-info-filter' || $RouteId=='stocks/generic-info-sort')
                    && $key=='po_status'){
                    ?>
                    <td class="tg-kr94">
                        <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($valz)?>"><?=$valz?></span></h4>
                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='warehouse/generic-info' || $RouteId=='warehouse/generic-info-filter' || $RouteId=='warehouse/generic-info-sort')
                    && $key=='channel_binded'){
                    $exp = str_replace(',','<br />',$valz);
                    ?>
                    <td class="tg-kr94">
                        <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($valz)?>"><?=$exp?></span></h4>
                    </td>
                    <?php
                    continue;
                }
                if ( ($RouteId=='warehouse/generic-info' || $RouteId=='warehouse/generic-info-filter' || $RouteId=='warehouse/generic-info-sort')
                    && $key=='is_active'){

                    ?>
                    <td class="tg-kr94">
                        <?php
                        if ($valz)
                            echo 'Active';
                        else
                            echo 'Inactive';
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if( ($RouteId=='warehouse/generic-info' || $RouteId=='warehouse/generic-info-filter' || $RouteId=='warehouse/generic-info-sort') && $key=='wid' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/warehouse/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                        <a   href="/warehouse/view?id=<?=$valz?>">
                            <i class="ti-eye"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }

                if ( ($RouteId=='claims/generic-info' || $RouteId=='claims/generic-info-filter' || $RouteId=='claims/generic-info-sort')
                    && $key=='order_status'){
                    ?>
                    <td class="tg-kr94">
                        <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($valz)?>"><?=$valz?></span></h4>
                    </td>
                    <?php
                    continue;
                }

                if ( ($RouteId=='sales/generic-info' || $RouteId=='sales/generic-info-filter' || $RouteId=='sales/generic-info-sort')
                    && $key=='item_status'){
                    ?>
                    <td class="tg-kr94">
                        <h4><span class="badge badge-pill <?=\backend\util\HelpUtil::getBadgeClass($valz)?>"><?=$valz?></span></h4>
                    </td>
                    <?php
                    continue;
                }
                /*echo '<pre>';
                print_r($pd);
                die;*/
                if ( $RouteId=='cost-price/generic-info' || $RouteId=='cost-price/generic-info-filter' || $RouteId=='cost-price/generic-info-sort' ){
                    if (  ($key=='sku' && isset($pd['cost']) && $pd['cost'] > 0)  ){
                        ?>
                        <td class="tg-kr94">
                            <a href="/competitive-pricing/crawl-sku-details?sku=<?=$valz?>" target="_blank"><?=$valz?></a>

                        </td>
                        <?php
                        continue;
                    }
                }

                if( $key =='created_at_finance_validation'){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        //$old_date = date('l, F d y h:i:s');              // returns Saturday, January 30 10 02:06:34
                        $old_date_timestamp = strtotime($valz);
                        $new_date = date('Y-m-d', $old_date_timestamp);
                        ?>
                        <?=$new_date?>
                    </td>
                    <?php
                    continue;
                }

                if( $key =='purchase_orderid' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/stocks/po-detail?poId=<?=$valz?>&warehouseId=<?=$stocks[$pd_key]['po_warehouseId']?>">
                            <i class="ti-eye"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='dm_id' && isset($pd['Requester']) ){
                    continue;
                }
                if( $key == 'shipping_type' && isset($pd['full_response']) ){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        $json = json_decode($pd['full_response']);
                        //echo '<pre>';print_r($json);die;
                        echo (isset($json->ShippingType)) ? $json->ShippingType : $json->shipping_type;
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if( $key == 'status' ){
                    ?>
                    <td class="tg-kr94">
                        <?php

                        if( $valz=='expired' ){
                            echo 'Expired';
                        }
                        elseif($valz=='active'){
                            echo 'Active';
                        }elseif( $valz=='new' ){
                            echo 'New';
                        }elseif ($valz=='draft'){
                            echo 'Draft';
                        }
                        ?>
                    </td>
                    <?php
                    continue;
                }

                if( $key=='uuser_id' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/user/view?id=<?=$valz?>">
                            <i class="mdi mdi-eye"></i>
                        </a>
                        <a   href="/user/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                        <a   href="/user/delete?id=<?=$valz?>">
                            <i class="mdi mdi-delete"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='role_id' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/roles/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='setting_id' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/settings/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='seller_id' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/sellers/view?id=<?=$valz?>">
                            <i class="mdi mdi-eye"></i>
                        </a>
                        <a   href="/sellers/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                        <a   href="/sellers/delete?id=<?=$valz?>">
                            <i class="mdi mdi-delete"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='cdid' ){
                    ?>
                    <td class="tg-kr94">
                        <a   href="/channels-details/view?id=<?=$valz?>">
                            <i class="mdi mdi-eye"></i>
                        </a>
                        <a   href="/channels-details/update?id=<?=$valz?>">
                            <i class="ti-pencil"></i>
                        </a>
                        <a   href="/channels-details/delete?id=<?=$valz?>">
                            <i class="mdi mdi-delete"></i>
                        </a>
                    </td>
                    <?php
                    continue;
                }


                if($key=='full_response'){
                    continue;
                }
                if( $RouteId=='settings/generic-info' AND $key=='name' ){
                    ?>
                    <td class="tg-kr94">
                        <?=ucwords(str_replace('_',' ',$valz))?>
                    </td>
                    <?php
                    continue;
                }
                if( strpos($RouteId, 'deals-maker') !== false && $key=='name' ){
                    ?>
                    <td class="tg-kr94" data-toggle="tooltip" title="<?=$valz?>">
                        <div class=" iffyTip wd100">
                            <?=$valz?>
                        </div>
                    </td>
                    <?php
                    continue;
                }
                if( $key=='overall_progress'){
                    ?>
                    <td class="tg-kr94">
                        <div class="">
                            <?php
                            if($valz >= 100){
                            ?>
                                <span class="label label-success" style=" width:100%"><?=$valz?>%</span>

                            <?php } else if($valz >=70 && $valz< 100){ ?>
                                <span class="label label-warning" style="width: <?=$valz?>%"><?=$valz?>%</span>
                            <?php } else if($valz <= 40){ ?>
                            <span class="label label-danger" style="min-width: 10%;"><?=$valz?>%</span>

                            <?php } else { ?>
                                <span class="label label-danger" style="width: <?=$valz?>%"><?=$valz?>%</span>

                            <?php }?>


                        </div>
                    </td>
                    <?php
                    continue;
                }
                if( ($RouteId == 'cost-price/generic-info' || $RouteId == 'cost-price/generic-info-sort' || $RouteId == 'cost-price/generic-info-filter')  &&
                    ($key == 'is_active'))
                { ?>
                    <td class="tg-kr94"  title="<?=$valz?>">
                        <div class=" iffyTip wd100">
                            <form id="sku_<?= $pd['sku'] ?>">
                                <input id="form-token"  type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                       value="<?= Yii::$app->request->csrfToken ?>"/>
                                <select data-sku="<?= $pd['sku'] ?>" class="cp-active-select form-control" name="active">
                                    <option value="1" <?= $valz == '1' ? 'selected' : '' ?> >Yes</option>
                                    <option value="0" <?= $valz == '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </form>
                        </div>
                    </td>
                    <?php
                    continue;
                }

                if( ($RouteId == 'bundles/generic-info' || $RouteId == 'bundles/generic-info-sort' || $RouteId == 'bundles/generic-info-filter'))
                { if($key=="is_active")  { ?>

                    <td class="tg-kr94" title="<?= $valz ?>">
                        <a href="javascript:;"
                        <?php if (isset($Modal_Id)) {
                                    $parms = $pd['id'];//. ',' . $valz ;
                                    echo 'onclick="javascript:changeBundleStatus(' . $parms . ')"';
                                } ?>
                           style="cursor: pointer"><?= $valz == '1' ? 'Activated' : 'DeActivated' ?></a>
                        <!--<div class="onoffswitch">
                            <input type="checkbox" name="" class="onoffswitch-checkbox" id="activeSwitch1"
                                <?php /*if (isset($Modal_Id)) {
                                    $parms = $pd['id'];// . ',' . $valz ;
                                    echo 'onclick="changeBundleStatus(' . $parms . ')"';
                                } */?>

                                <?/*= $valz == '1' ? 'checked' : '' */?>
                             >
                            <label class="onoffswitch-label" for="activeSwitch1">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>-->
                    </td>
                    <?php
                    continue;
                }
                else if($key=="relation_name") { ?>
                    <td class="tg-kr94"
                        <?php if (isset($Modal_Id)) {
                            echo 'onclick="showBundlePopup(' . $pd['id'] . ')"';
                        } ?>

                    ><?= $valz ?>
                    </td>
                <?php continue; }
                }
                else if( $key == 'is_active' ){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        if($valz==0)
                            echo 'No';
                        else
                            echo 'Yes';
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if( ($RouteId == 'cost-price/generic-info' || $RouteId == 'cost-price/generic-info-sort' || $RouteId == 'cost-price/generic-info-filter')  && $key == 'extra_cost'){
                    ?>
                    <td class="tg-kr94"  title="<?=$valz?>">
                        <div class=" iffyTip wd100">
                            <form id="sku_extra_cost<?= $pd['sku'] ?>">
                                <input id="form-token"  type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                       value="<?= Yii::$app->request->csrfToken ?>"/>
                                <input data-sku="<?= $pd['sku'] ?>" class="cp-extra-cost-update form-control" value="<?=$valz?>" type="number">
                            </form>
                        </div>
                    </td>
                    <?php
                    continue;
                }
                if( ($RouteId == 'cost-price/generic-info' || $RouteId == 'cost-price/generic-info-sort' || $RouteId == 'cost-price/generic-info-filter')  && $key == 'master_cotton'){
                    ?>
                    <td class="tg-kr94"  title="<?=$valz?>">
                        <div class=" iffyTip wd100">
                            <form id="sku_master_cotton<?= $pd['sku'] ?>">
                                <input id="form-token"  type="hidden" name="<?= Yii::$app->request->csrfParam ?>"
                                       value="<?= Yii::$app->request->csrfToken ?>"/>
                                <input min="0" data-sku="<?= $pd['sku'] ?>" class="cp-master-cotton-update form-control" value="<?=$valz?>" type="number">
                            </form>
                        </div>
                    </td>
                    <?php
                    continue;
                }
                if ($RouteId=='channels-details/generic-info' && $key=='channel_name'){
                    ?>
                    <td class="tg-kr94"><a href="/channels/update?id=<?=$pd['channel_id']?>"><?= $valz ?></a></td>
                    <?php
                    continue;
                }
                if ($RouteId=='channels-details/generic-info' && $key=='channel_id')
                    continue;
                if ( ($RouteId=='cost-price/generic-info' || $RouteId=='cost-price/generic-info-filter' ||
                            $RouteId=='cost-price/generic-info-sort') && $key=='manual_stock'){
                    ?>
                    <td class="tg-kr94">
                        <input type="number" value="<?=$valz?>" class="form-control" onblur="update_manual_stock(this.value,'<?=$pd['sku']?>')" data-sku-id="<?=$pd['sku']?>"/>
                    </td>
                    <?php
                    continue;
                }
                if ($RouteId=='channels/generic-info' && ($key=='price_sync' || $key=='stocks_sync' ) ){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        if ($valz=='0' AND $valz!='')
                            echo 'Sync is OFF';
                        else if ($valz==1)
                            echo 'Sync is ON';
                        else
                            echo '';
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if ($RouteId=='channels/generic-info' && ($key=='sku_stocks' || $key=='sku_price' ) ){
                    $skus_make_list=explode(',',$valz);

                    ?>
                    <td class="tg-kr94">
                        <?php
                        $counter=1;
                        foreach ( $skus_make_list as $value )
                        {
                            echo $value.',';
                            $counter++;
                            if($counter % 3 == 0){
                                echo '<br />';
                            }

                        }
                        ?>
                    </td>
                    <?php
                    continue;
                }
                if ($RouteId=='channels/generic-info' && $key=='id' ){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        echo '<i style="font-size: 21px;cursor: pointer;" onclick="edit_excluded_skus('.$valz.')" class="fa fa-edit"></i>';
                        ?>
                    </td>
                    <?php
                    continue;
                }
                ?>
                <?php
                if ($RouteId=='cost-price/generic-info' && $key=='bundle_name' ){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        if ($valz!=''){
                            $bundle_names = explode(',',$valz);
                            foreach ( $bundle_names as $value ){
                                echo '<a href="#">'.$value.'</a><br />';
                            }
                        }
                        ?>
                    </td>
                    <?php
                    continue;
                }
                ?>
                <?php
                if (($RouteId == 'deals-maker/generic-info' || $RouteId=='deals-maker/generic-info-filter' || $RouteId == 'deals-maker/generic-info-sort')
                    && ($key == 'overall_performance' )){
                    ?>
                    <td class="tg-kr94">
                        <?php
                        $Percentage = number_format((float)$valz, 2, '.', '');
                        //echo $Percentage;
                        $width = $Percentage;

                        if ( $Percentage>100 )
                            $width = 100;
                        else if ( $Percentage <= 0 )
                            $width = 1;

                        //$min_width = 'style="width: 94%;"';
                        if ( $Percentage >= 100 ){
                            $status = 'success';
                            $min_width = 'style="width: 100%;"';
                        }
                        else if ( $Percentage >= 70 && $Percentage < 100 ){
                            $status = 'warning';
                            $min_width = 'style="width: '.$Percentage.'%;"';
                        }
                        else if ( $Percentage > 20 && $Percentage < 70  ){
                            $status = 'danger';
                            $min_width = 'style="width: '.$Percentage.'%;"';
                        }
                        else if ( $Percentage<20 ){
                            $status = 'danger';
                            $min_width = 'style="min-width: 10%;"';
                        }

                        ?>
                        <!--<div class="progress-bar bg-<?/*=$status*/?>" role="progressbar" style="width: <?/*=$width*/?>%;height:12px;" role="progressbar"> <?/*=$Percentage*/?>% </div>-->
                        <span class="label label-<?=$status?>" <?=$min_width?> ><?=$Percentage?>%</span>
                    </td>
                    <?php
                    continue;
                }
                ?>
                <td class="tg-kr94"><?= $valz ?></td>
                <?php
            }
            ?>
        </tr>
        <?php
        $serial++;
        $counter++;
    }
}else{
    ?>
    <tr><td colspan='10'>Record not found or SKU is inactive.</td></tr>
    <?php
}
?>
</tbody>

<!-- BELOW MODAL CAN BE USE IF WE WANT TO SHOW IT ON TR CLICK -->
<div id="showBundlePopup" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Modal Header</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Some text in the modal.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>
