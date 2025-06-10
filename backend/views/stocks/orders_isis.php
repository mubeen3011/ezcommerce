<?php

use backend\util\HelpUtil;
use common\models\Settings;
use yii\web\View;
$w_id = isset($_GET['selected_warehouse']) ? $_GET['selected_warehouse'] : '';
$date = (isset($po) && $po->po_initiate_date) ? date('d-F, Y',strtotime($po->po_initiate_date)) :  date('d-F, Y') ;
$this->title = '';
$this->params['breadcrumbs'][] = ['label' => 'PO List', 'url' => ['po']];
$this->params['breadcrumbs'][] = 'Products Purchase Order - '.$date;
$settings = Settings::find()->where(['name' => 'last_stock_api_update'])->one();
$status = (isset($po)) ? $po->po_status : "";


?>
<style>
    .card-header{
        color: black;
        height:50px;
    }
    .control-label{
        padding:4px;
    }
    .card-header-custom-css{
        padding: 13px;
    }
    .po-information-tab-heading{
        padding:12px;
    }
    .icon-plus{
        font-size:10px;
    }
    .initiate-po{
        margin-top: 10px;
    }
    .tg-kr94 input[type=text] {
        width: 95px;
    }

    .scrollTop {
        position: fixed;
        right: 4%;
        bottom: 10px;
        background-color: #00aced;
        padding: 10px;
        opacity: 0;
        transition: all 0.4s ease-in-out 0s;
    }

    .scrollTop a {
        cursor: hand;
        font-size: 12px;
        color: #fff;
    }
    table.floatThead-table {
        border-top: none;
        border-bottom: none;
        background-color: #fff;
    }
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?= \yii\widgets\Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <h3>Products Purchase Order - <?=$date?> - <?php
                    if ($w_id==1)
                        echo 'ISIS';
                    else if ( $w_id ==2 )
                        echo 'FBL Blip';
                    else if ( $w_id==3 )
                        echo 'FBL Avent';
                    else if ($w_id==7)
                        echo 'FBL 909';
                    else if ($w_id==8)
                        echo 'Lazada OutRight';
                    ?></h3>
                <!-- Nav tabs -->
                <?php if (!$po): ?>
                    <ul class="nav nav-tabs customtab2" role="tablist">
                    </ul>
                <?php endif; ?>
                <!-- Tab panes -->
                <div class="tab-content">
                    <?php
                    /*echo '<pre>';
                    print_r($refine);
                    die;*/
                    if (!isset($_GET['poId'])){
                        $isis_refine['a'] = $refine['a'];
                        $blip_refine['b'] = $refine['b'];
                        $fbl_909_refine['d'] = isset($refine['d']) ? $refine['d'] : [] ;
                        $fbl_909_4_refine['c'] = isset($refine['c']) ? $refine['c'] : [] ;

                        ?>
                        <?php
                        if ( $_GET['selected_warehouse'] == 1 ){
                            ?>
                            <?=$this->render('po-panels/isis',['po'=>$po,'status'=>$status,'refine'=>$isis_refine,'pod'=>$pod,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                            <?php
                        }
                        else if ( $_GET['selected_warehouse'] == 2 ){
                            ?>
                            <?=$this->render('po-panels/fbl-blip',['po'=>$po,'status'=>$status,'refine'=>$blip_refine,'pod'=>$pod,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                            <?php
                        }
                        else if ( $_GET['selected_warehouse'] == 3 ){
                            ?>
                            <?=$this->render('po-panels/fbl-909-avent',['po'=>$po,'status'=>$status,'refine'=>$fbl_909_refine,'pod'=>$pod,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                            <?php
                        }
                        else if ( $_GET['selected_warehouse'] == 7 ){
                            ?>
                            <?=$this->render('po-panels/fbl-909',['po'=>$po,'status'=>$status,'refine'=>$fbl_909_4_refine,'pod'=>$pod,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                            <?php
                        }else if ( $_GET['selected_warehouse'] == 8 ){
                            ?>
                            <?=$this->render('po-panels/lazada-orb',['po'=>$po,'status'=>$status,'refine'=>[],'pod'=>$pod,'bundle_ids'=>'1,2','foc_sku_list'=>[]]);?>
                            <?php
                        }
                        ?>






                        <?php
                    }else if( $_GET['warehouse']==1 ){
                        $isis_refine['a'] = $refine['a'];
                        ?>
                        <?=$this->render('po-panels/isis',['po'=>$po,'status'=>$status,'refine'=>$isis_refine,'pod'=>$pod,'po_bundles'=>$po_bundles
                            ,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                        <?php
                    }else if( $_GET['warehouse']==2 ){
                        $blip_refine['b'] = $refine['b'];
                        ?>
                        <?=$this->render('po-panels/fbl-blip',['po'=>$po,'status'=>$status,'refine'=>$blip_refine,'pod'=>$pod,'po_bundles'=>$po_bundles
                            ,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                        <?php
                    }else if( $_GET['warehouse'] == 3 ){
                        $fbl_909_refine['d'] = $refine['d'];
                        ?>
                        <?=$this->render('po-panels/fbl-909-avent',['po'=>$po,'status'=>$status,'refine'=>$fbl_909_refine,'pod'=>$pod,'po_bundles'=>$po_bundles
                            ,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                        <?php
                    }else if( $_GET['warehouse'] == 7 ){
                        $fbl_909_4_refine['c'] = $refine['c'];
                        ?>
                        <?=$this->render('po-panels/fbl-909',['po'=>$po,'status'=>$status,'refine'=>$fbl_909_4_refine,'pod'=>$pod,'po_bundles'=>$po_bundles
                            ,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                        <?php
                    }else if( $_GET['warehouse'] == 8 ){
                        $fbl_909_4_refine['c'] = $refine['c'];
                        //echo '<pre>';print_r($fbl_909_4_refine);die;
                        ?>
                        <?=$this->render('po-panels/lazada-orb',['po'=>$po,'status'=>$status,'refine'=>$fbl_909_4_refine,'pod'=>$pod,'po_bundles'=>$po_bundles
                            ,'bundle_ids'=>$bundle_ids,'foc_sku_list'=>$foc_sku_list]);?>
                        <?php
                    }
                    ?>
                    <?=$this->render('po-panels/add-sku-popup');?>
                    <?=$this->render('po-panels/add-bundle-popup');?>
                    <?=$this->render('popups/po-sku-information');?>
                    <div id="stop" class="scrollTop">
                        <span><a href="">Go Up</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        var isPo = '<?=($po) ? '1' : '0'?>';
    </script>
    <?php

    $this->registerJsFile('//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js', [View::POS_HEAD, 'depends' => [\frontend\assets\AppAsset::className()]]);
    $this->registerCssFile(
        '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css',
        ['depends' => [\frontend\assets\AppAsset::className()]]
    );
    $this->registerJsFile(
        '@web/ao-js/po.js?v='.time(),
        ['depends' => [\backend\assets\AppAsset::className()]]
    );
    $this->registerJsFile(
        '@web/monster-admin/js/jquery.floatThead.js',
        ['depends' => [\backend\assets\AppAsset::className()]]
    );
    $this->registerJs('
var $table = $(\'table.sticky-header\');
$table.floatThead({
    responsiveContainer: function($table){
        return $table.closest(\'.table-responsive\');
    }
});
',View::POS_END);
    ?>