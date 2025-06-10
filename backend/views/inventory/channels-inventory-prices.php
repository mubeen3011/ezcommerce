<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 8/26/2019
 * Time: 4:31 PM
 */
$this->params['breadcrumbs'][] = ['label' => 'Inventory', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Shops Price Inventory';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <h3>Shops Price Inventory
                        </h3>
                    </div>

                    <div class="col-md-4 col-sm-12">

                    </div>


                    <div class="col-md-4 col-sm-12">
                        <?= \yii\widgets\Breadcrumbs::widget([
                            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        ]) ?>
                    </div>
                </div>

                <div id="example23_filter" class="dataTables_filter">

                    <button type="button" id="export-table-csv" class=" btn btn-info"><i class="fa fa-download"></i> Export</button>
                    <button type="button" class=" btn btn-info" id="ci-price-filters"><i class="fa fa-filter"></i></button>
                    <?php if ( isset($_GET['Search']['channel_sku']) ): ?>
                        <a href="/inventory/channels-inventory-prices?page=1" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                    <?php endif; ?>

                </div>

                <div class="">

                    <table id="tablesaw-datatable"
                           class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable"
                           data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable=""
                           data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                            <form id="ci-search-form" method="get" action="">
                                <tr>
                                    <th scope="col" data-tablesaw-priority="persist" class="min-th-width footable-sortable sort sorting" data-sort="desc">
                                        Shop Sku <br/><br/>
                                        <select name="Search[sku]" class="inputs-margin ci-sku-search" id="ci-search-sku">
                                            <option></option>

                                            <?php foreach ( $sku_list as $value ): ?>
                                                <option value="<?=$value['channel_sku']?>" <?=(isset($_GET['Search']['channel_sku']) && $_GET['Search']['channel_sku'] == $value['channel_sku']) ? 'selected' : ''?>>
                                                    <?=$value['channel_sku']?>
                                                </option>
                                            <?php endforeach; ?>

                                        </select>
                                        <input type="hidden" name="page" value="<?=$_GET['page']?>">
                                    </th>
                                    <?php if (isset($channels_stocks[0])) : ?>
                                        <?php foreach ( $channels_stocks[0] as $key=>$value ):
                                            if ($key == 'channel_sku')
                                                continue;
                                            ?>
                                            <th class="min-th-width footable-sortable sort sorting  " scope="col">
                                                <?= $key ?>
                                            </th>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </tr>
                            </form>
                        </thead>

                        <tbody class="gridData">

                        <?php foreach ( $channels_stocks as $key=>$value ): ?>
                            <tr>
                                <?php foreach ( $value as $key=>$detail ): ?>

                                    <td><?=$detail?></td>

                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>


                        </tbody>
                    </table>

                </div>
                <?=Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,
                    'route'=>\Yii::$app->controller->module->requestedRoute])?>
            </div>
        </div>
    </div>

</div>
