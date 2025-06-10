<?php
use yii\web\View;
//use common\models\User;
$html = "";
$this->title = '';
$this->params['breadcrumbs'][] = 'Top Contributors';
$userid= Yii::$app->user->identity;
$currency = Yii::$app->params['currency'];
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body" style="height: auto !important;">
                <div class=" row">
                    <div class="col-md-4 col-sm-12">
                        <h3>Top Contributors</h3>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <!--<span class="fa fa-user" data-toggle="tooltip" title="Customer Type"></span>-->
                        <a class="btn btn-sm btn-info btn-rounded <?= !isset($_GET['customer_type']) ? "fa fa-check":"";?>" href="/sales/top-performers" > ALL</a> |
                        <a class="btn btn-sm btn-info btn-rounded <?= (isset($_GET['customer_type']) && $_GET['customer_type']=="b2b") ? "fa fa-check":"";?>" href="?customer_type=b2b"> B2B</a> |
                        <a class="btn btn-sm btn-info btn-rounded <?= (isset($_GET['customer_type']) && $_GET['customer_type']=="b2c") ? "fa fa-check":"";?>" href="?customer_type=b2c"> B2C</a>
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>

    .header-filter-inputs
    {
        display:none;
        font-size:12px;
    }
</style>
<div class="row">
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">
    </div>
    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 hidden-xs-down hidden-sm-down">

        <?php if(count($_GET) >0): ?>
            <a data-toggle="tooltip" title="Clear filter" href="/sales/top-performers" type="button" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"> </i>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-info btn-sm pull-right mr-2" id="filter-btn">
            <i class="fa fa-filter"></i>
        </button>
    </div>
</div>
    <!-----------------------Sales by marketplace-------------------------------->
<?php
$filtered_applied=false;
$name_filter=false;
if(count($_GET) > 0)
    $filtered_applied=true;

$above_five_percent_contributors_count=0; // how many products have conribution more than 5% , // for less than 5% contributors formula
$total_contributors=isset($top_performers['products']) ? count($top_performers['products']):0; // all products list sent
if(isset($top_performers['products']) && !empty($top_performers['products'])) : ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-responsive">


                    <table  class="tablesaw table-bordered table-hover table" data-tablesaw-mode="swipe"  data-tablesaw-sortable-switch data-tablesaw-minimap data-tablesaw-mode-switch>
                        <form  method="GET">
                        <thead>
                        <tr>
                            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="persist">Product
                                <div>
                                    <input autocomplete="off" placeholder="SKU / Name" type="text" data-toggle="tooltip" title="<?= isset($_GET['filter_sku']) ? $_GET['filter_sku']:"";?>" style="width: 100%;height:25px" name="filter_sku" value="<?= isset($_GET['filter_sku']) ? $_GET['filter_sku']:"";?>" class="form-control-sm header-filter-inputs">
                                </div>
                            </th>
                            <th  scope="col" data-tablesaw-sortable-col data-tablesaw-priority="3">Stock</th>
                            <?php foreach($top_performers['marketplaces'] as $row) { ?>
                                <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4"><?= $row;?></th>
                            <?php }?>
                            <th scope="col" data-tablesaw-sortable-col data-tablesaw-priority="4">%age contribution</th>

                        </tr>
                        </thead>
                            <input type="submit" style="display: none;">
                        </form>
                        <tbody>
                        <?php foreach($top_performers['products'] as $index=>$row) {
                                ///if filtered applied filter it here because on query filter cant be applied for this module
                            ///
                                $show_record=true;
                                if(isset($_GET['filter_sku']) && $_GET['filter_sku']!=''){
                                    if(in_array(trim($_GET['filter_sku']),[$row['sku'],$row['name']]))
                                        $show_record=true;
                                    else
                                        $show_record=false;
                                }

                            /***variations in the beginning */
                            $parent_marketplace_sale=[]; // store very variation individual marketplace wise sale // showing at parent top against each mp
                            $total_parent_stock=isset($row['stock']) ? $row['stock']:0;
                            ob_start();
                            if(isset($row['children']) && !empty($row['children'])) {
                                foreach($row['children'] as $child=>$child_data){

                                    ////filter
                                    if(isset($_GET['filter_sku']) && $_GET['filter_sku']!='' && $show_record===false){
                                        $show_record=($child==$_GET['filter_sku']) ? true:false;
                                    }
                                    /// /////
                                    $variation_contribution=0;
                                    $total_parent_stock+=$child_data['stock'];
                                    ?>
                                    <tr class="child_row_tp_<?= $index;?> child-row" style="display:none">
                                        <td class="static scalec"> &nbsp; &nbsp;-- <?= $child;?></td>
                                        <td class="static scalec"> <?= isset($child_data['stock']) ? $child_data['stock']:"";?> </td>
                                        <?php foreach($top_performers['marketplaces'] as $mplace_name) { // marketplaces
                                            //sum of all variation  against each mp
                                            if(isset($parent_marketplace_sale[$index][$mplace_name])) // if already have index increment the record
                                                $parent_marketplace_sale[$index][$mplace_name] += isset($child_data[$mplace_name]['sales']) ? ($child_data[$mplace_name]['sales']):0;
                                            else
                                                $parent_marketplace_sale[$index][$mplace_name] = isset($child_data[$mplace_name]['sales']) ? $child_data[$mplace_name]['sales']:0;

                                            $variation_contribution += isset($child_data[$mplace_name]['sales']) ? $child_data[$mplace_name]['sales']:0;
                                            ?>
                                            <td class="static scale">
                                                <?= isset($child_data[$mplace_name]['sales']) ? $currency . $child_data[$mplace_name]['sales']:"-"?>

                                            </td>
                                        <?php }?>
                                        <td class="static scale"><?= number_format((($variation_contribution/$top_performers['total_sales']) * 100), 2, '.', '') ?> % </td>
                                    </tr>
                                <?php }}
                            $content_variation = ob_get_clean(); ?>
                            <!------------variation portion ended------------>
                            <?php
                                if($show_record===false)  // if filter applied this check will execute
                                    continue;
                                    ?>
                            <tr id="parent_row_tp_<?= $index;?>" data-id-pk="<?= $index;?>">
                                <td class="static scalec">
                                    <span  data-id-pk='<?= $index;?>' class="fa fa-plus-circle show_items_tp"></span>
                                    <?= $row['name'];?>
                                </td>
                                <td class="static scale">

                                    <?= $total_parent_stock;?>
                                </td>
                                <?php foreach($top_performers['marketplaces'] as $mplace_name) { // marketplaces ?>
                                    <td class="static scale">
                                        <?php if(isset($parent_marketplace_sale[$index][$mplace_name]))
                                            echo  $currency. ceil($parent_marketplace_sale[$index][$mplace_name]);
                                        elseif(isset($row['markets'][$mplace_name]['sales']) )  // if product has not child and marketplace indexes set
                                            echo  $currency. ceil($row['markets'][$mplace_name]['sales']);
                                        else
                                            echo " - ";


                                        ?>
                                    </td>
                                <?php } ?>
                                <td class="static scale">
                                    <?php
                                    $percent_contr_overall_prdct=round((($row['sales']/$top_performers['total_sales']) * 100),2);
                                    if($percent_contr_overall_prdct >= 5)
                                        $above_five_percent_contributors_count +=1; // maintain count of more than 5 % contributor

                                    ?>
                                    <?= $percent_contr_overall_prdct; ?> % <span class="pull-right <?= $percent_contr_overall_prdct < 5 ? "fa fa-circle text-warning":""?>"></span>
                                </td>
                            </tr>
                            <!------write variations------>
                            <?= $content_variation;  ?>
                            <!-------------------------->
                            <?php } ?>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif;?>

    <!---------------------------------->

<?php

$this->registerJsFile(
    '@web/monster-admin/js/toastr.js',
    ['depends' => [\backend\assets\AppAsset::className()]]
);


////table
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('monster-admin/assets/plugins/tablesaw-master/dist/tablesaw-init.js', [View::POS_END, 'depends' => [\frontend\assets\AppAsset::className()]]);

$this->registerJs(<<< EOT_JS_CODE
 
    
    //for top 10 cntributors
     $('.show_items_tp').on('click',function(){
        let id_pk=$(this).attr('data-id-pk');
        $(this).toggleClass('fa-plus fa-minus')
        $('.child_row_tp_' + id_pk).toggle();
    });
    ////filter btn 
    $('#filter-btn').on('click',function(){
    $('.header-filter-inputs').toggle();
    });
EOT_JS_CODE
);
?>
