<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 6/2/2020
 * Time: 1:55 PM
 */
$this->params['breadcrumbs'][] = ['label' => 'Sales', 'url' => ['/sales/dashboard/Average Sku']];
$this->params['breadcrumbs'][] = 'Average Sku Sales';
if ( isset($_GET['Search']) )
    $clear_filter=1;
else
    $clear_filter=0;

?>
<style>
    .filters-visible{
        display: none;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-5 col-sm-12">
                        <h3>
                            <?php
                            $txt='';
                            if ( isset($_GET['mp']) && $_GET['mp']!=''  ){
                                $txt = " By Marketplace - ".ucwords($_GET['mp']);
                            }
                            else if ( isset($_GET['shop']) && $_GET['shop']!='' ){
                                $txt = " By Shop - ".ucwords($_GET['shop']);
                            }
                            ?>
                            Average Sku Sales <?=$txt?>
                        </h3>
                    </div>

                    <div class="col-md-3 col-sm-12">

                    </div>


                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div id="example23_filter" class="dataTables_filter">

                    <a href="/sales/download-average-sales-by-sku?<?=http_build_query($_GET)?>" type="button" class=" btn btn-info">
                        <i class="fa fa-download"></i> Export
                    </a>
                    <button type="button" class=" btn btn-info" id="ci-stock-filters"><i class="fa fa-filter"></i></button>
                    <?php if ( isset($_GET['Search']) ): ?>
                        <?php if ( isset($_GET['mp']) ): ?>
                        <a href="/sales/average-sales-by-sku?page=1&mp=<?=$_GET['mp']?>" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ( isset($_GET['shop']) ): ?>
                            <a href="/sales/average-sales-by-sku?page=1&shop=<?=$_GET['shop']?>" class=" btn btn-info  clear-filters" id="filters">
                                <i class="fa fa-filter"></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>

                <div class="table-responsive">

                    <?php if ( $avgSkuSales ): ?>
                    <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable"
                           data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable=""
                           data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                        <form id="ci-search-form" method="get" action="">
                            <tr>
                                <input type="hidden" name="form-filter-used" value="true"/>
                                <th scope="col" data-tablesaw-priority="persist" data-tablesaw-priority="1"
                                    class="min-th-width footable-sortable sort sorting " data-sort="desc">
                                    Sku
                                    <div class="filters-visible inputs-margin">
                                        <select name="Search[sku]" class="inputs-margin ci-sku-search" id="ci-search-sku">
                                            <option></option>
                                            <?php foreach ( $products as $value ): ?>
                                                <option value="<?=$value['sku']?>" <?=(isset($_GET['Search']['sku']) && $_GET['Search']['sku'] == $value['sku']) ? 'selected' : ''?>>
                                                    <?=$value['sku']?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                    </div>
                                    <?php
                                    if ( isset($_GET['mp']) )
                                    {
                                        ?>
                                        <input type="hidden" name="mp" value="<?=$_GET['mp']?>">
                                    <?php
                                    }
                                    ?>
                                    <?php
                                    if ( isset($_GET['shop']) )
                                    {
                                        ?>
                                        <input type="hidden" name="shop" value="<?=$_GET['shop']?>">
                                        <?php
                                    }
                                    ?>
                                    <input type="hidden" name="page" value="<?=$_GET['page']?>">
                                </th>

                                <?php
                                $cols=['selling_status'];
                                $selling_status_dropdown = ['low','high','medium','not moving'];

                                if ( isset($avgSkuSales[0]) ):
                                    foreach ( $avgSkuSales[0] as $key=>$value ):
                                        if ($key=='sku')
                                            continue;
                                        elseif ( !in_array($key,$cols) ){
                                        ?>
                                        <th>
                                            <?=str_replace('_',' ',$key)?>
                                            <br/>
                                            <input pattern="[=|>|<|>=|<=][0-9.]+$" name="Search[Dynamic_Params][<?=$key?>]" value="<?=(isset($_GET['Search']['Dynamic_Params'][$key])) ? $_GET['Search']['Dynamic_Params'][$key] : ''?>" class="form-control inputs-margin filters-visible"/>
                                        </th>
                                <?php
                                        }
                                        else{
                                            ?>
                                            <th>
                                                <?=str_replace('_',' ',$key)?>
                                                <br/>
                                                <select name="Search[Dynamic_Params][<?=$key?>]" class="form-control inputs-margin filters-visible" style="width: 100px;">
                                                    <?php
                                                    foreach ( $selling_status_dropdown as $status ){
                                                        ?>
                                                        <option value="<?=$status?>" <?=( isset($_GET['Search']['Dynamic_Params'][$key]) && $status==$_GET['Search']['Dynamic_Params'][$key]) ? 'selected' : ''?>><?=ucwords($status)?></option>
                                                    <?php
                                                    }
                                                    ?>
                                                </select>
                                            </th>
                                <?php
                                        }
                                ?>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </tr>
                            <input type="submit" style="display: none;">
                        </form>
                        </thead>

                        <tbody class="gridData">
                        <?php

                        foreach ( $avgSkuSales as $key=>$value ):

                            $color='';
                            if ($value['selling_status']=='Low')
                                $color='lightpink';
                            else if ($value['selling_status']=='Medium')
                                $color='#ffea91';
                            else if ($value['selling_status']=='High')
                                $color='#96ef83';

                            ?>
                            <tr>
                            <?php
                            foreach ( $value as $key1=>$value1 ):
                                if ( $key1=='selling_status' ):
                                    ?>
                                    <td style="background-color: <?=$color?>"><?=$value1?></td>
                                    <?php
                                else:
                                    ?>
                                    <td><?=$value1?></td>
                                <?php
                                endif;
                            ?>

                        <?php
                            endforeach;
                        ?>
                            </tr>
                                <?php
                        endforeach;
                        ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align: center;">
                        <h1>Sorry no record found</h1>
                    </div>
                    <?php endif; ?>
                </div>
                <?php unset($_GET['form-filter-used'])?>
                <?=Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,
                    'route'=>\Yii::$app->controller->module->requestedRoute])?>
            </div>
        </div>
    </div>

</div>