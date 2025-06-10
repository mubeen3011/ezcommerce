<?php
/**
 * Created by PhpStorm.
 * User: user_PC
 * Date: 8/26/2019
 * Time: 4:31 PM
 */
//echo '<pre>';print_r($warehouses_stocks);die;
$this->params['breadcrumbs'][] = ['label' => 'Inventory', 'url' => ['/stocks/dashboard']];
$this->params['breadcrumbs'][] = 'Warehouse Stock Inventory';
if ( isset($_GET['Search']) )
    $clear_filter=1;
else
    $clear_filter=0;

$params_top_filer="";
if(isset($_GET['record_per_page']) && $_GET['record_per_page'])
    $params_top_filer.="&record_per_page=". $_GET['record_per_page'];


?>
<style>
    .filters-visible{
        display: none;
    }
</style>
    <link href="/../css/sales_v1.css" rel="stylesheet">
    <div class="row">

    <div class="col-lg-9 col-md-9 col-sm-12 col-xs-12">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= (!isset($_GET['Search']['selling_status']) || (isset($_GET['Search']['selling_status']) && $_GET['Search']['selling_status']=="")) ? 'active':"";?>"  href="?page=1" role="tab">

                    <span><span class="badge badge-secondary"></span> All </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['Search']['selling_status']) && strtolower($_GET['Search']['selling_status'])=='slow') ? 'active':"";?>"  href="?Search[selling_status]=Slow&page=1<?=$params_top_filer?>" role="tab">

                    <span><span class="badge badge-secondary"></span> Slow </span>
                </a>
            </li>
            <li class="nav-item">
               <a class="nav-link <?= (isset($_GET['Search']['selling_status']) && strtolower($_GET['Search']['selling_status'])=='medium') ? 'active':"";?>"  href="?Search[selling_status]=Medium&page=1<?=$params_top_filer?>" role="tab">
                       <span><span class="badge badge-secondary"></span> Medium </span>
                   </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['Search']['selling_status']) && strtolower($_GET['Search']['selling_status'])=='high') ? 'active':"";?>"  href="?Search[selling_status]=High&page=1<?=$params_top_filer?>" role="tab">
                    <span><span class="badge badge-secondary"></span> High </span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= (isset($_GET['Search']['selling_status']) && strtolower($_GET['Search']['selling_status'])=='not moving') ? 'active':"";?>"  href="?Search[selling_status]=Not Moving&page=1<?=$params_top_filer?>" role="tab">
                        <span><span class="badge badge-secondary"></span> Not Moving </span>
                   </a>
            </li>
        </ul>
    </div>
    </div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class=" row">

                    <div id="displayBox" style="display: none;">
                        <img src="/monster-admin/assets/images/logo-gif/output_GVaFek.gif">
                    </div>
                    <div class="col-md-4 col-sm-12">
                        <h3>Warehouses Stock Inventory</h3>
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
                    <a style="color: white;" type="button" class=" btn btn-info"  data-toggle="modal" data-target="#myModal" ><i class="fa fa-download"></i> Export</a>
                    <button type="button" class=" btn btn-info" id="ci-stock-filters"><i class="fa fa-filter"></i></button>
                    <?php if ( isset($_GET['Search']) ): ?>
                        <a href="/inventory/warehouses-inventory-stocks?page=1" class=" btn btn-info  clear-filters" id="filters">
                            <i class="fa fa-filter"></i>
                        </a>
                    <?php endif; ?>

                </div>

                <div class="table-responsive">

                    <table id="tablesaw-datatable" class="export-csv tablesaw table-bordered table-hover table tablesaw-swipe tablesaw-sortable"
                           data-tablesaw-mode="swipe" data-tablesaw-mode-exclude="stack" data-tablesaw-sortable=""
                           data-tablesaw-minimap="" data-tablesaw-mode-switch="">
                        <thead>
                            <form id="ci-search-form" method="get" action="">
                                <tr>
                                    <input type="hidden" name="form-filter-used" value="true"/>
                                    <?php
                                    if ( isset($warehouses_stocks[0]) ):
                                        $counter = 2;
                                        foreach ( $warehouses_stocks[0] as $key=>$value ):
                                            //if ($key == 'sku')
                                               // continue;?>

                                            <!-- <th scope="col" data-tablesaw-priority="persist"
                                         data-sort="desc" style="width:50%">
                                         Sku
                                        <div class="filters-visible inputs-margin">
                                            <select name="Search[sku]" class="inputs-margin ci-sku-search" id="ci-search-sku">
                                                <option></option>
                                                <?php //foreach ( $sku_list as $value ): ?>
                                                    <option value="<?//=$value['sku']?><?//=(isset($_GET['Search']['sku']) && $_GET['Search']['sku'] == $value['sku']) ? 'selected' : ''?>
                                                        <?//=$value['sku']?>
                                                    </option>
                                                <?php //endforeach; ?>

<!--                                            </select>
                                        </div>-->

                                    <th style="width:70%;white-space: nowrap;" scope="col" data-tablesaw-priority="<?=$counter?>">
                                        <?php if ($key == 'sku'){?>
                                            sku <br/>
                                            <input autocomplete="off" type="text" id="ci-search-sku" name="Search[<?=$key?>]" value="<?=(isset($_GET['Search'][$key]) && $_GET['Search'][$key]!='') ? $_GET['Search'][$key] : ''?>" class="form-control filters-visible inputs-margin" />
                                            <input type="hidden" name="page" value="<?=$_GET['page']?>">
                                        <?php  }?>

                                        <input type="hidden" name="page" value="<?=$_GET['page']?>">
                                    </th>


                                                <?php
                                                if ($key == 'product_name'){ ?>
                                                    <th style="width:70%;white-space: nowrap;" scope="col" data-tablesaw-priority="<?=$counter?>">
                                                        Name <br/>
                                                        <input autocomplete="off" type="text" name="Search[<?=$key?>]" value="<?=(isset($_GET['Search'][$key]) && $_GET['Search'][$key]!='') ? $_GET['Search'][$key] : ''?>" class="form-control filters-visible inputs-margin" />
                                                    </th>

                                              <?php  }
                                                elseif ($key == 'brand'){ ?>
                                                    <th  style="width:65% ;white-space: nowrap;" scope="col" data-tablesaw-priority="<?=$counter?>">
                                                        <?= str_replace('_',' ',$key) ?> <br/>
                                                        <div class="filters-visible inputs-margin" style="width:100px !important">
                                                            <select name="Search[brand]" class="filters-visible inputs-margin ci-sku-search"  id="ci-search-category">
                                                                <option></option>

                                                                <?php foreach ($brands as $brand ): ?>
                                                                    <option value="<?=$brand ['brand']?>" <?=(isset($_GET['Search']['brand']) && $_GET['Search']['brand'] == $brand ['brand']) ? 'selected' : ''?>>
                                                                        <?=$brand ['brand']?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </th>

                                                    <?php
                                                }
                                                elseif ($key=='selling_status'){
                                                    ?>
                                                    <th  scope="col" data-tablesaw-priority="<?=$counter?>" style="width:65%; white-space: nowrap;">
                                                        <?= str_replace('_',' ',$key) ?> <br/>
                                                       <div class="filters-visible inputs-margin">
                                                            <select name="Search[selling_status]"  class="filters-visible inputs-margin ci-sku-search" id="ci-search-selling-status">
                                                                <option></option>
                                                                <?php foreach ( $selling_status as $value ): ?>
                                                                    <option value="<?=$value?>" <?=(isset($_GET['Search']['selling_status']) && $_GET['Search']['selling_status'] == $value) ? 'selected' : ''?>>
                                                                        <?=$value?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                    </th>
                                                    <?php
                                                }
                                                else{
                                                    ?>
                                                    <th  data-toggle="tooltip" title="<?= str_replace('_',' ',$key);?>" scope="col" data-tablesaw-priority="<?=$counter?>" style="width: 65%;white-space: nowrap;">
                                                       <?= mb_strimwidth(str_replace('_',' ',$key), 0, 12, ".."); ?>
                                                        <br/>

                                                        <input autocomplete="off" type="text" name="Search[having][<?=$key?>]" value="<?=(isset($_GET['Search']['having'][$key]) && $_GET['Search']['having'][$key]!='') ? $_GET['Search']['having'][$key] : ''?>" class="form-control filters-visible inputs-margin" />
                                                    </th>
                                                    <?php
                                                }
                                                ?>

                                            <?php
                                                $counter++;
                                            endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                                <input type="submit" style="display: none;">
                            </form>
                        </thead>

                        <tbody class="gridData">

                        <?php foreach ( $warehouses_stocks as $key=>$value ): ?>
                            <tr>
                                <?php foreach ( $value as $keys=>$detail ):?>

                                    <td >
                                        <?php
                                        if (strpos($keys, '_Est_OOS') !== false) {
                                            $out_of_stock = isset($StockLevel[$keys]['out_of_sock']) ? $StockLevel[$keys]['out_of_sock'] : 1;
                                            $soonOosGreater = isset($StockLevel[$keys]['out_of_stock_soon_greater']) ? $StockLevel[$keys]['out_of_stock_soon_greater'] : 0 ;
                                            $soonOosLess = isset($StockLevel[$keys]['out_of_sock_soon_less']) ? $StockLevel[$keys]['out_of_sock_soon_less'] : 30 ;
                                            $in_stock = isset($StockLevel[$keys]['in_stock']) ? $StockLevel[$keys]['in_stock'] : 30;

                                            $bgColor = '';
                                            if ( $detail < $out_of_stock )
                                                $bgColor=isset($StockBadges[$keys]['out_of_sock']) ? $StockBadges[$keys]['out_of_sock'] : 'white';
                                            elseif ($detail > $soonOosGreater && $detail<$soonOosLess )
                                                $bgColor=isset($StockBadges[$keys]['out_of_stock_soon']) ? $StockBadges[$keys]['out_of_stock_soon'] : 'white';
                                            elseif ($detail >= $in_stock )
                                                $bgColor=isset($StockBadges[$keys]['in_stock']) ? $StockBadges[$keys]['in_stock'] : 'white';
                                            $detail.=' Days';
                                            $color = ($bgColor=='white') ? 'black' : 'white';
                                            echo "<span class=\"badge badge-pill\" style=\"background-color: $bgColor;color:$color\">$detail</span>";
                                        }
                                        else{
                                            if($keys=="sku")
                                              echo  yii\helpers\Html::a($detail,array("products/detail","sku"=>$detail));
                                            else
                                                echo ($detail=='') ? 'N/A' : $detail;
                                        }
                                    ?>
                                    </td>

                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php
                        if (empty($warehouses_stocks)){
                            ?>
                            <tr>
                                <td style="text-align: center"><h2>No record found</h2></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                    </table>

                </div>
                <?php unset($_GET['form-filter-used'])?>
                <?=Yii::$app->controller->renderPartial('../layouts/dt-pagination',['total_records'=>$total_records,
                    'route'=>\Yii::$app->controller->module->requestedRoute])?>
            </div>
        </div>
    </div>

</div>
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog">
            <form action="/inventory/download-stock-report">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel">Export Stock List</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">

                    <h4>Please select the warehouses</h4>
                    <select id="export_csv_warehouses" class="select2" name="warehouses[]" multiple="multiple" required>
                        <option value="all">All</option>
                        <?php
                        foreach ( $warehouses as $value ){
                            ?>
                            <option value="<?=$value['id']?>"><?=$value['name']?></option>
                            <?php
                        }
                        ?>
                    </select>
                    <input type="hidden" value='<?=json_encode($_GET)?>' name="get_elements">
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-success export-csv waves-effect" value="Export CSV">
                    <button type="button" class="btn btn-info waves-effect" data-dismiss="modal">Close</button>
                </div>
            </div>
            </form>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
<?php
$this->registerJs(<<< EOT_JS_CODE
$(function(){
$('#col_dis_category').removeClass('tablesaw-cell-persist');
});



EOT_JS_CODE
);
?>